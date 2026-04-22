<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class WarehouseDashboardController extends Controller
{
    private const READY_TO_PACK_STATUSES = [
        'approved',
        Order::STATUS_READY_TO_PACK,
    ];

    private const PACKED_STATUSES = [
        'packed',
        Order::STATUS_READY_TO_SHIP,
    ];

    private const EDITABLE_LOGISTICS_STATUSES = [
        Order::STATUS_PACKING,
    ];

    public function __construct()
    {
        $this->middleware(['auth', 'role:warehouse,admin']);
    }

    public function index(Request $request)
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : Carbon::today();

        $dateString = $selectedDate->toDateString();

        $dailyOrdersQuery = Order::with('customer')
            ->whereDate('created_at', $dateString);

        $dailyOrders = (clone $dailyOrdersQuery)
            ->latest('created_at')
            ->take(12)
            ->get();

        $approvalStats = [
            'pending_approval' => (clone $dailyOrdersQuery)
                ->whereIn('status', ['pending_leader_approval', 'pending_manager_approval', 'pending_warehouse_approval'])
                ->count(),
            'approved' => OrderHistory::where('action', 'approve_order')
                ->whereDate('created_at', $dateString)
                ->count(),
            'rejected' => OrderHistory::where('action', 'reject_order')
                ->whereDate('created_at', $dateString)
                ->count(),
        ];

        $stats = [
            'ready_to_pack' => Order::whereIn('status', self::READY_TO_PACK_STATUSES)->count(),
            'packing'       => Order::where('status', Order::STATUS_PACKING)->count(),
            'packed_today'  => Order::whereIn('status', self::PACKED_STATUSES)
                ->whereDate('updated_at', $dateString)->count(),
            'returning'     => Order::where('status', Order::STATUS_RETURNING)->count(),
            'done_today'    => Order::whereIn('status', self::PACKED_STATUSES)
                ->whereDate('updated_at', $dateString)->count(),
            'orders_in_day' => (clone $dailyOrdersQuery)->count(),
        ];

        $recentPacked = Order::with('customer')
            ->whereIn('status', self::PACKED_STATUSES)
            ->whereDate('updated_at', $dateString)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        return view('warehouse.dashboard', compact(
            'stats',
            'recentPacked',
            'selectedDate',
            'dailyOrders',
            'approvalStats'
        ));
    }

    /**
     * List orders awaiting packing or currently being packed.
     */
    public function orders(Request $request)
    {
        // Auto-cancel overdue orders before loading the page
        \Artisan::call('orders:auto-cancel-overdue');

        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $status = $request->input('status');
        $today = Carbon::today();
        $startDate = $today->copy()->subDays(6)->toDateString();

        $dailyCountsQuery = Order::query()
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $today->toDateString());

        if (!empty($status)) {
            $dailyCountsQuery->where('status', $status);
        }

        $dailyCounts = $dailyCountsQuery
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $quickDates = collect(range(0, 6))->map(function ($offset) use ($today, $dailyCounts, $selectedDate) {
            $date = $today->copy()->subDays($offset);
            $dateKey = $date->toDateString();
            $count = (int) ($dailyCounts[$dateKey] ?? 0);

            return [
                'date' => $dateKey,
                'label' => $offset === 0 ? 'Hôm nay' : $date->format('d/m'),
                'count' => $count,
                'available' => $count > 0,
                'active' => $dateKey === $selectedDate,
            ];
        });

        $ordersQuery = Order::with([
            'customer',
            'user',
            'warehouse',
            'histories.user.warehouse',
            'items.product.avatar.media',
            'items.variant' => function ($query) {
                $query->withAvailableStock()->with('avatar.media');
            },
        ])
            ->whereDate('created_at', $selectedDate);

        if (!empty($status)) {
            $ordersQuery->where('status', $status);
        }

        $orders = $ordersQuery
            ->orderByDesc('created_at')
            ->get();

        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $stockGuardResult = $this->buildPackingQueueStockGuards($orders, $managedWarehouseId, $selectedDate);
        $stockGuardMap = $stockGuardResult['guards'];
        $fifoRemainingStock = $stockGuardResult['remaining_by_variant']; // variantId => float remaining after FIFO

        $warehouseVariantIds = Inventory::query()
            ->when($managedWarehouseId, fn ($query) => $query->where('warehouse_id', $managedWarehouseId))
            ->pluck('product_variant_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Stock at the selected date (reconstructed from movements) per variant
        $displayedVariantIds = $orders
            ->flatMap(fn(Order $order) => $order->items->pluck('product_variant_id'))
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $stockPanelVariantIds = $warehouseVariantIds
            ->merge($displayedVariantIds)
            ->unique()
            ->values();

        // "Tồn kho khả dụng" per variant for the stock panel.
        // For today: use available_stock (quantity - reserved_quantity) — consistent with
        // the FIFO pool computation and the inventory page display.
        // For past dates: reconstruct from movements to show historical snapshot.
        $variantStock = $selectedDate === Carbon::today()->toDateString()
            ? $this->getAvailableByVariant(collect($stockPanelVariantIds), $managedWarehouseId)
            : $this->getStockAtDate(collect($stockPanelVariantIds), $managedWarehouseId, $selectedDate);

        $stockPanelVariants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $stockPanelVariantIds->all())
            ->get()
            ->sortBy(function (ProductVariant $variant) {
                return [
                    strtolower((string) ($variant->name ?: $variant->product?->name ?: '')),
                    strtolower((string) ($variant->sku ?: '')),
                ];
            })
            ->values();

        $orders->each(function (Order $order) use ($stockGuardMap) {
            $order->setAttribute('stock_guard', $stockGuardMap[$order->id] ?? [
                'has_shortage' => false,
                'can_start_packing' => true,
                'message' => null,
                'shortages' => [],
            ]);
        });

        return view('warehouse.orders.index', compact('orders', 'selectedDate', 'status', 'quickDates', 'fifoRemainingStock', 'variantStock', 'stockPanelVariants'));
    }

    /**
     * Start packing: ready_to_pack → packing
     */
    public function startPacking(Request $request, Order $order)
    {
        if (!$order->created_at || !$order->created_at->isToday()) {
            $message = 'Chỉ được xử lý đơn có ngày hôm nay.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        if (!in_array($order->status, self::READY_TO_PACK_STATUSES, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Đơn hàng không ở trạng thái Chờ đóng gói.',
                ], 422);
            }

            return back()->with('error', 'Đơn hàng không ở trạng thái Chờ đóng gói.');
        }

        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $stockCheck = $this->evaluateSingleOrderStock($order, $managedWarehouseId);

        if (!($stockCheck['can_start_packing'] ?? false)) {
            $message = 'Không đủ tồn kho để đóng hàng';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'stock_check' => [
                        'has_shortage' => true,
                        'can_start_packing' => false,
                        'shortages' => $stockCheck['shortages'] ?? [],
                        'import_url' => route('warehouse.stock-in'),
                        'import_hint' => 'Bạn cần Nhập kho để thực hiện công việc tiếp',
                    ],
                ], 422);
            }

            return back()->with('error', $message);
        }

        $statusBefore = $order->status;

        $order->update(['status' => Order::STATUS_PACKING]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'start_packing',
            'user_id'       => Auth::id(),
            'role'          => 'warehouse',
            'status_before' => $statusBefore,
            'status_after'  => Order::STATUS_PACKING,
            'note'          => 'Bắt đầu đóng gói đơn hàng',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Đã bắt đầu đóng gói đơn #' . $order->code,
                'order' => [
                    'id' => $order->id,
                    'status' => Order::STATUS_PACKING,
                    'status_label' => 'Đang đóng gói',
                    'status_class' => 'bg-warning text-dark',
                ],
            ]);
        }

        return back()->with('success', 'Đã bắt đầu đóng gói đơn #' . $order->code);
    }

    private function evaluateSingleOrderStock(Order $order, ?int $warehouseId): array
    {
        // Always scope to TODAY: consistent with what the orders-list page shows,
        // and ensures we check against current physical stock and today's queue only.
        $singleCollection = collect([$order]);
        $result = $this->buildPackingQueueStockGuards($singleCollection, $warehouseId, now()->toDateString());

        return $result['guards'][$order->id] ?? [
            'has_shortage' => false,
            'can_start_packing' => true,
            'message' => null,
            'shortages' => [],
        ];
    }

    private function buildPackingQueueStockGuards(Collection $orders, ?int $warehouseId, string $forDate = null): array
    {
        $queueStatuses = array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]);
        $forDate = $forDate ?? now()->toDateString();

        // IDs of the orders currently displayed on the page
        $displayedOrderIds = $orders
            ->filter(fn (Order $order) => in_array($order->status, $queueStatuses, true))
            ->pluck('id')
            ->flip()
            ->toArray();

        if (empty($displayedOrderIds)) {
            return ['guards' => [], 'remaining_by_variant' => []];
        }

        // Load ALL queued orders from the SAME date for FIFO simulation (oldest first).
        // Cross-date FIFO is wrong: old orders from other dates should have been auto-cancelled.
        $allQueueOrders = Order::with(['items.product', 'items.variant.product'])
            ->whereIn('status', $queueStatuses)
            ->whereDate('created_at', $forDate)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($allQueueOrders->isEmpty()) {
            return ['guards' => [], 'remaining_by_variant' => []];
        }

        $variantIds = $allQueueOrders
            ->flatMap(fn (Order $order) => $order->items->pluck('product_variant_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // All order-item IDs in this FIFO set (used for the reservation add-back below).
        $fifoOrderItemIds = $allQueueOrders
            ->flatMap(fn (Order $o) => $o->items->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        // ── FIFO pool: available stock + today's own reservations ──────────────────────
        //
        // "Tồn kho khả dụng" = quantity - reserved_quantity.
        // reserved_quantity already includes the reservations committed to today's queued
        // orders, so starting from available_stock gives a near-zero pool (all stock is
        // already logically claimed). We add back those reservations so the FIFO can
        // re-simulate from scratch who gets priority.
        //
        // Pool = available_stock + today_fifo_reservations
        //      = (quantity - reserved_quantity) + today_fifo_reservations
        //      = quantity - external_reservations   (mathematically equivalent)
        //
        // This keeps the pool consistent with the "Khả dụng" number shown on the
        // inventory page and the warehouse/orders stock panel.
        // ──────────────────────────────────────────────────────────────────────────────
        $availableByVariant = $this->getAvailableByVariant($variantIds, $warehouseId);

        // Reservations belonging to today's FIFO orders, grouped by variant.
        $todayResvByVariant = [];
        if ($fifoOrderItemIds->isNotEmpty()) {
            $todayResvByVariant = InventoryReservation::query()
                ->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
                ->whereIn('inventory_reservations.order_item_id', $fifoOrderItemIds->all())
                ->when($warehouseId, fn ($q) => $q->where('inventories.warehouse_id', $warehouseId))
                ->selectRaw('inventories.product_variant_id, COALESCE(SUM(inventory_reservations.quantity), 0) as qty')
                ->groupBy('inventories.product_variant_id')
                ->pluck('qty', 'product_variant_id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $stockByVariant = [];
        foreach ($variantIds as $vid) {
            $vid = (int) $vid;
            $stockByVariant[$vid] = max(
                0,
                ((int) ($availableByVariant[$vid] ?? 0)) + ((int) ($todayResvByVariant[$vid] ?? 0))
            );
        }

        // Running pool — decremented only when a complete order can be packed (FIFO)
        $remainingByVariant = array_map(fn ($v) => (float) $v, $stockByVariant);

        $guards = [];

        foreach ($allQueueOrders as $order) {
            $shortages         = [];
            $pendingDeductions = [];  // staged; applied only if order has NO shortage

            foreach ($order->items as $item) {
                $variantId   = (int) $item->product_variant_id;
                $neededQty   = (float) $item->quantity;

                if ($variantId <= 0 || $neededQty <= 0) {
                    continue;
                }

                $remaining = (float) ($remainingByVariant[$variantId] ?? 0.0);

                if ($remaining < $neededQty) {
                    $shortages[] = [
                        'order_id'      => (int) $order->id,
                        'order_code'    => (string) $order->code,
                        'order_item_id' => (int) $item->id,
                        'variant_id'    => $variantId,
                        'variant_name'  => (string) ($item->variant?->name ?? $item->product?->name ?? ('SP #' . $variantId)),
                        'required_qty'  => $neededQty,
                        'available_qty' => $remaining,
                        'short_qty'     => round($neededQty - $remaining, 3),
                        'reason'        => $remaining <= 0 ? 'blocked_by_prior_order' : 'insufficient_stock',
                    ];
                } else {
                    $pendingDeductions[$variantId] = ($pendingDeductions[$variantId] ?? 0.0) + $neededQty;
                }
            }

            $hasShortage = !empty($shortages);

            if (!$hasShortage) {
                foreach ($pendingDeductions as $vid => $consume) {
                    $remainingByVariant[$vid] = max(0.0, ($remainingByVariant[$vid] ?? 0.0) - $consume);
                }
            }
            // Order with shortage: deduct NOTHING — stock stays for later orders

            if (isset($displayedOrderIds[$order->id])) {
                $guards[$order->id] = [
                    'has_shortage'      => $hasShortage,
                    'can_start_packing' => !$hasShortage,
                    'message'           => $hasShortage ? 'Không đủ tồn kho để đóng hàng' : null,
                    'shortages'         => $shortages,
                ];
            }
        }

        return [
            'guards'               => $guards,
            'remaining_by_variant' => $remainingByVariant,  // stock left after FIFO queue consumed
        ];
    }

    /**
     * Reconstruct stock quantity for each variant at the END of a given date.
     * Formula: qty_at_date = current_quantity - SUM(movements after date)
     * Movements after the selected date are reversed to get the historical snapshot.
     */
    private function getStockAtDate(Collection $variantIds, ?int $warehouseId, string $date): array
    {
        if ($variantIds->isEmpty()) {
            return [];
        }

        $inventoryQuery = Inventory::query()
            ->select('id', 'product_variant_id', 'quantity', 'reserved_quantity')
            ->whereIn('product_variant_id', $variantIds->all());

        if ($warehouseId) {
            $inventoryQuery->where('warehouse_id', $warehouseId);
        }

        $inventories = $inventoryQuery->get();

        if ($inventories->isEmpty()) {
            return [];
        }

        // Net movements that happened AFTER the selected date — reverse these to get historical qty
        $movementsAfter = InventoryMovement::query()
            ->selectRaw('inventory_id, COALESCE(SUM(quantity), 0) as qty_delta')
            ->whereIn('inventory_id', $inventories->pluck('id')->all())
            ->whereDate('created_at', '>', $date)
            ->groupBy('inventory_id')
            ->pluck('qty_delta', 'inventory_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $result = [];
        foreach ($inventories as $inv) {
            $vid          = (int) $inv->product_variant_id;
            $currentQty   = (int) $inv->quantity;
            $afterDelta   = (int) ($movementsAfter[$inv->id] ?? 0);
            $qtyAtDate    = $currentQty - $afterDelta;

            $result[$vid] = ($result[$vid] ?? 0) + max(0, $qtyAtDate);
        }

        return $result;
    }

    /**
     * Re-run Ráp đơn hàng for ALL dates that still have queued orders.
     * Persists stock_sufficient and stock_shortage_detail on every queued order.
     * Called automatically after any stock-in creation or adjustment.
     */
    private function syncAllQueuedOrdersStockSufficiency(?int $warehouseId): void
    {
        $queueStatuses = array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]);

        // Find all distinct dates that have queued orders.
        $dates = Order::whereIn('status', $queueStatuses)
            ->selectRaw('DATE(created_at) as order_date')
            ->groupBy('order_date')
            ->pluck('order_date');

        foreach ($dates as $forDate) {
            $allQueueOrders = Order::with(['items.variant'])
                ->whereIn('status', $queueStatuses)
                ->whereDate('created_at', $forDate)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($allQueueOrders->isEmpty()) {
                continue;
            }

            $result = $this->buildPackingQueueStockGuards($allQueueOrders, $warehouseId, $forDate);
            $guards = $result['guards'];

            $sequence = 0;
            foreach ($allQueueOrders as $order) {
                $sequence++;
                $guard      = $guards[$order->id] ?? ['has_shortage' => false, 'shortages' => []];
                $hasShortage = (bool) ($guard['has_shortage'] ?? false);

                $order->update([
                    'daily_sequence'        => $sequence,
                    'stock_sufficient'      => $hasShortage ? 0 : 1,
                    'stock_shortage_detail' => $hasShortage ? ($guard['shortages'] ?? []) : null,
                ]);
            }
        }
    }

    private function getTotalQuantityByVariant(Collection $variantIds, ?int $warehouseId): array
    {
        if ($variantIds->isEmpty()) {
            return [];
        }

        $query = Inventory::query()
            ->selectRaw('product_variant_id, COALESCE(SUM(quantity), 0) as total_qty')
            ->whereIn('product_variant_id', $variantIds->all());

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query
            ->groupBy('product_variant_id')
            ->pluck('total_qty', 'product_variant_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function getAvailableByVariant(Collection $variantIds, ?int $warehouseId): array
    {
        if ($variantIds->isEmpty()) {
            return [];
        }

        $query = Inventory::query()
            ->selectRaw('product_variant_id, COALESCE(SUM(quantity - reserved_quantity), 0) as available_qty')
            ->whereIn('product_variant_id', $variantIds->all());

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query
            ->groupBy('product_variant_id')
            ->pluck('available_qty', 'product_variant_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function getInventorySnapshotByVariant(Collection $variantIds, ?int $warehouseId, string $date): array
    {
        if ($variantIds->isEmpty()) {
            return [];
        }

        $selectedDate = Carbon::parse($date)->toDateString();

        if ($selectedDate === Carbon::today()->toDateString()) {
            return Inventory::query()
                ->selectRaw('product_variant_id, COALESCE(SUM(quantity), 0) as total_qty, COALESCE(SUM(reserved_quantity), 0) as total_reserved')
                ->whereIn('product_variant_id', $variantIds->all())
                ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
                ->groupBy('product_variant_id')
                ->get()
                ->mapWithKeys(function ($row) {
                    $quantity = (int) ($row->total_qty ?? 0);
                    $reserved = (int) ($row->total_reserved ?? 0);

                    return [
                        (int) $row->product_variant_id => [
                            'quantity' => $quantity,
                            'reserved' => $reserved,
                            'available' => max(0, $quantity - $reserved),
                        ],
                    ];
                })
                ->all();
        }

        $historicalQuantity = $this->getStockAtDate($variantIds, $warehouseId, $selectedDate);

        return collect($variantIds)
            ->mapWithKeys(function ($variantId) use ($historicalQuantity) {
                $quantity = (int) ($historicalQuantity[(int) $variantId] ?? 0);

                return [
                    (int) $variantId => [
                        'quantity' => $quantity,
                        'reserved' => 0,
                        'available' => $quantity,
                    ],
                ];
            })
            ->all();
    }

    private function getInventorySnapshotStats(?int $warehouseId, string $date): array
    {
        $selectedDate = Carbon::parse($date)->toDateString();

        if ($selectedDate === Carbon::today()->toDateString()) {
            $inventoryBase = Inventory::query()
                ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId));

            $stockAgg = (clone $inventoryBase)
                ->selectRaw('COALESCE(SUM(quantity), 0) as total_qty, COALESCE(SUM(reserved_quantity), 0) as total_reserved')
                ->first();

            return [
                'total_quantity' => (int) ($stockAgg->total_qty ?? 0),
                'total_reserved' => (int) ($stockAgg->total_reserved ?? 0),
                'total_available' => max(0, (int) ($stockAgg->total_qty ?? 0) - (int) ($stockAgg->total_reserved ?? 0)),
                'low_stock' => (clone $inventoryBase)
                    ->whereColumn('quantity', '<=', 'low_stock_threshold')
                    ->where('quantity', '>', 0)
                    ->count(),
                'out_of_stock' => (clone $inventoryBase)
                    ->where('quantity', 0)
                    ->count(),
            ];
        }

        $inventories = Inventory::query()
            ->select('id', 'quantity', 'low_stock_threshold')
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->get();

        if ($inventories->isEmpty()) {
            return [
                'total_quantity' => 0,
                'total_reserved' => 0,
                'total_available' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
            ];
        }

        $movementsAfter = InventoryMovement::query()
            ->selectRaw('inventory_id, COALESCE(SUM(quantity), 0) as qty_delta')
            ->whereIn('inventory_id', $inventories->pluck('id')->all())
            ->whereDate('created_at', '>', $selectedDate)
            ->groupBy('inventory_id')
            ->pluck('qty_delta', 'inventory_id');

        $totalQuantity = 0;
        $lowStock = 0;
        $outOfStock = 0;

        foreach ($inventories as $inventory) {
            $quantity = max(0, (int) $inventory->quantity - (int) ($movementsAfter[$inventory->id] ?? 0));
            $threshold = (int) ($inventory->low_stock_threshold ?: 0);

            $totalQuantity += $quantity;

            if ($quantity <= 0) {
                $outOfStock++;
                continue;
            }

            if ($threshold > 0 && $quantity <= $threshold) {
                $lowStock++;
            }
        }

        return [
            'total_quantity' => $totalQuantity,
            'total_reserved' => 0,
            'total_available' => $totalQuantity,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
        ];
    }

    private function getReservedByOrderItem(Collection $orderItemIds, ?int $warehouseId): array
    {
        if ($orderItemIds->isEmpty()) {
            return [];
        }

        $query = InventoryReservation::query()
            ->selectRaw('inventory_reservations.order_item_id, COALESCE(SUM(inventory_reservations.quantity), 0) as reserved_qty')
            ->whereIn('inventory_reservations.order_item_id', $orderItemIds->all());

        if ($warehouseId) {
            $query->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
                ->where('inventories.warehouse_id', $warehouseId);
        }

        return $query
            ->groupBy('inventory_reservations.order_item_id')
            ->pluck('reserved_qty', 'inventory_reservations.order_item_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    /**
     * Warehouse updates actual package weight and shipping fee for an order.
     */
    public function updateLogistics(Request $request, Order $order)
    {
        $expectsJson = $request->expectsJson();

        if (!$order->created_at || !$order->created_at->isToday()) {
            $message = 'Chỉ được xử lý đơn có ngày hôm nay.';

            if ($expectsJson) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        if (!in_array($order->status, self::EDITABLE_LOGISTICS_STATUSES, true)) {
            if ($expectsJson) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Không thể cập nhật kg/phí ship ở trạng thái hiện tại của đơn hàng.',
                ], 422);
            }

            return back()->with('error', 'Không thể cập nhật kg/phí ship ở trạng thái hiện tại của đơn hàng.');
        }

        $order->loadMissing('items');

        $itemIds = $order->items->pluck('id')->all();

        $rules = [
            'item_id' => ['nullable', 'integer'],
            'item_actual_weight' => ['nullable', 'numeric', 'min:0'],
            'charge_shipping_fee' => ['nullable', 'boolean'],
            'shipping_fee'  => ['nullable', 'numeric', 'min:0', 'required_if:charge_shipping_fee,1'],
            'charge_foam_box_fee' => ['nullable', 'boolean'],
            'foam_box_price' => ['nullable', 'numeric', 'min:0', 'required_if:charge_foam_box_fee,1'],
        ];

        if ($request->filled('item_id')) {
            $rules['item_actual_weight'] = ['required', 'numeric', 'min:0'];
        }

        $validated = $request->validate($rules);

        $oldWeight = $order->actual_weight;
        $oldShippingFee = $order->shipping_fee;
        $oldChargeShippingFee = $order->charge_shipping_fee;
        $oldFoamBoxPrice = $order->foam_box_price;
        $oldChargeFoamBoxFee = $order->charge_foam_box_fee;

        if ($request->filled('item_id')) {
            $itemId = (int) $validated['item_id'];
            if (!in_array($itemId, $itemIds, true)) {
                if ($expectsJson) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Sản phẩm không thuộc đơn hàng này.',
                    ], 422);
                }

                return back()->with('error', 'Sản phẩm không thuộc đơn hàng này.');
            }

            $item = $order->items->firstWhere('id', $itemId);
            if ($item) {
                $newWeight = round((float) $validated['item_actual_weight'], 3);
                $item->actual_weight = $newWeight;
                // Giữ lại KL kho cân lần đầu để đối chiếu hao hụt sau này
                if ($item->packed_weight === null) {
                    $item->packed_weight = $newWeight;
                }
                $item->save();
            }
        }

        $chargeShippingFee = $oldChargeShippingFee;
        $shippingFee = (float) ($oldShippingFee ?? 0);
        if ($request->has('charge_shipping_fee')) {
            $chargeShippingFee = $request->boolean('charge_shipping_fee');
            $shippingFee = $chargeShippingFee
                ? round((float) ($validated['shipping_fee'] ?? 0), 2)
                : 0.0;
        }

        $chargeFoamBoxFee = $oldChargeFoamBoxFee;
        $foamBoxPrice = (float) ($oldFoamBoxPrice ?? 0);
        if ($request->has('charge_foam_box_fee')) {
            $chargeFoamBoxFee = $request->boolean('charge_foam_box_fee');
            $foamBoxPrice = $chargeFoamBoxFee
                ? round((float) ($validated['foam_box_price'] ?? 0), 2)
                : 0.0;
        }

        $actualWeight = round((float) $order->items()->sum('actual_weight'), 3);

        $order->update([
            'actual_weight' => $actualWeight,
            'charge_shipping_fee' => $chargeShippingFee,
            'shipping_fee' => $shippingFee,
            'charge_foam_box_fee' => $chargeFoamBoxFee,
            'foam_box_price' => $foamBoxPrice,
            // Keep total_weight aligned with real measured package weight in warehouse flow.
            'total_weight' => $actualWeight,
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'warehouse_update_logistics',
            'user_id'       => Auth::id(),
            'role'          => 'warehouse',
            'status_before' => $order->status,
            'status_after'  => $order->status,
            'note'          => sprintf(
                'Cập nhật logistics: Kg thực tế %s → %s | Tính phí ship %s → %s | Phí ship %s → %s | Thùng xốp %s → %s | Giá thùng xốp %s → %s',
                number_format((float) $oldWeight, 3, '.', ''),
                number_format($actualWeight, 3, '.', ''),
                ((bool) $oldChargeShippingFee) ? 'Có' : 'Không',
                $chargeShippingFee ? 'Có' : 'Không',
                number_format((float) $oldShippingFee, 2, '.', ''),
                number_format($shippingFee, 2, '.', ''),
                ((bool) $oldChargeFoamBoxFee) ? 'Có' : 'Không',
                $chargeFoamBoxFee ? 'Có' : 'Không',
                number_format((float) $oldFoamBoxPrice, 2, '.', ''),
                number_format($foamBoxPrice, 2, '.', '')
            ),
        ]);

        if ($request->filled('item_id')) {
            if ($expectsJson) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Đã lưu kg thực tế cho sản phẩm trong đơn #' . $order->code,
                    'order' => [
                        'id' => $order->id,
                        'actual_weight' => (float) $actualWeight,
                    ],
                ]);
            }

            return back()->with('success', 'Đã lưu kg thực tế cho sản phẩm trong đơn #' . $order->code);
        }

        if ($expectsJson) {
            return response()->json([
                'ok' => true,
                'message' => 'Đã cập nhật phí ship/thùng xốp cho đơn #' . $order->code,
                'order' => [
                    'id' => $order->id,
                    'actual_weight' => (float) $actualWeight,
                    'shipping_fee' => (float) $shippingFee,
                    'foam_box_price' => (float) $foamBoxPrice,
                    'charge_shipping_fee' => (bool) $chargeShippingFee,
                    'charge_foam_box_fee' => (bool) $chargeFoamBoxFee,
                ],
            ]);
        }

        return back()->with('success', 'Đã cập nhật phí ship/thùng xốp cho đơn #' . $order->code);
    }

    /**
     * Complete packing: packing → packed_waiting_pickup (ready to ship)
     */
    public function completePacking(Request $request, Order $order)
    {
        if (!$order->created_at || !$order->created_at->isToday()) {
            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }

        if ($order->status !== Order::STATUS_PACKING) {
            return back()->with('error', 'Đơn hàng không đang ở trạng thái Đang đóng gói.');
        }

        if ($order->actual_weight === null || $order->shipping_fee === null) {
            return back()->with('error', 'Vui lòng cập nhật Kg thực tế và phí ship trước khi hoàn thành đóng gói.');
        }

        $order->update(['status' => Order::STATUS_READY_TO_SHIP]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'complete_packing',
            'user_id'       => Auth::id(),
            'role'          => 'warehouse',
            'status_before' => Order::STATUS_PACKING,
            'status_after'  => Order::STATUS_READY_TO_SHIP,
            'note'          => 'Hoàn thành đóng gói – Sẵn sàng giao hàng',
        ]);

        return back()->with('success', 'Đơn #' . $order->code . ' đã đóng gói xong, sẵn sàng giao!');
    }

    /**
     * Admin can reopen a packed warehouse order back to packing for edits.
     */
    public function reopenPacking(Request $request, Order $order)
    {
        $user = $request->user();

        abort_unless($user?->hasRole('admin'), 403);

        if (!$order->created_at || !$order->created_at->isToday()) {
            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }

        if (!in_array($order->status, self::PACKED_STATUSES, true)) {
            return back()->with('error', 'Chỉ có thể bỏ khóa các đơn đang ở bước hoàn tất kho.');
        }

        $previousStatus = $order->status;

        $order->update([
            'status' => Order::STATUS_PACKING,
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'admin_reopen_packing',
            'user_id'       => Auth::id(),
            'role'          => 'admin',
            'status_before' => $previousStatus,
            'status_after'  => Order::STATUS_PACKING,
            'note'          => 'Admin bỏ khóa chỉnh sửa và đưa đơn quay lại bước đóng gói của kho',
        ]);

        return back()->with('success', 'Admin đã bỏ khóa chỉnh sửa cho đơn #' . $order->code . '.');
    }

    /**
     * List returning orders waiting for warehouse confirmation.
     */
    public function returns()
    {
        $managedWarehouseId = Auth::user()->warehouse_id ? (int) Auth::user()->warehouse_id : null;

        $orders = Order::with(['customer', 'shipper', 'items.variant', 'items.product', 'warehouse', 'returnWarehouse'])
            ->where('status', Order::STATUS_RETURNING)
            ->orderBy('updated_at', 'desc')
            ->get();

        $orders->each(function (Order $order) {
            $resolvedWarehouse = $this->resolveReturnWarehouse($order);

            $order->setAttribute('resolved_return_warehouse_id', $resolvedWarehouse?->id);
            $order->setAttribute('resolved_return_warehouse_name', $resolvedWarehouse?->name);
        });

        return view('warehouse.returns.index', compact('orders', 'managedWarehouseId'));
    }

    /**
     * Confirm return receipt: returning → returned_completed + restore inventory
     */
    public function confirmReturn(Order $order)
    {
        if ($order->status !== Order::STATUS_RETURNING) {
            return back()->with('error', 'Đơn hàng không đang ở trạng thái Đang trả hàng.');
        }

        $managedWarehouseId = Auth::user()->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $resolvedReturnWarehouse = $this->resolveReturnWarehouse($order);
        $returnWarehouseId = $resolvedReturnWarehouse?->id;

        if ($managedWarehouseId && (!$returnWarehouseId || $managedWarehouseId !== $returnWarehouseId)) {
            return back()->with('error', 'Bạn chỉ có thể xác nhận đơn trả về đúng kho mình quản lý.');
        }

        if (!$returnWarehouseId) {
            return back()->with('error', 'Đơn trả này chưa xác định kho nhận. Vui lòng yêu cầu shipper chọn kho trả về.');
        }

        DB::transaction(function () use ($order, $returnWarehouseId) {
            $order->update(['status' => Order::STATUS_RETURNED_COMPLETED]);

            // Restore inventory for each item
            foreach ($order->items as $item) {
                Inventory::where('product_variant_id', $item->product_variant_id)
                    ->where('warehouse_id', $returnWarehouseId)
                    ->increment('quantity', $item->quantity);
            }

            OrderHistory::create([
                'order_id'      => $order->id,
                'action'        => 'confirm_return',
                'user_id'       => Auth::id(),
                'role'          => 'warehouse',
                'status_before' => Order::STATUS_RETURNING,
                'status_after'  => Order::STATUS_RETURNED_COMPLETED,
                'note'          => 'Kho xác nhận đã nhận hàng trả vào kho ' . ($resolvedReturnWarehouse?->name ?? ('ID ' . $returnWarehouseId)) . ' – Tồn kho đã cập nhật',
            ]);
        });

        return back()->with('success', 'Đã xác nhận nhập kho hàng trả – Đơn #' . $order->code);
    }

    protected function resolveReturnWarehouse(Order $order): ?Warehouse
    {
        if ($order->relationLoaded('returnWarehouse') && $order->returnWarehouse) {
            return $order->returnWarehouse;
        }

        if ($order->relationLoaded('warehouse') && $order->warehouse) {
            return $order->warehouse;
        }

        $warehouseId = $this->resolveReturnWarehouseId($order);
        if ($warehouseId) {
            return Warehouse::find($warehouseId);
        }

        $warehouseName = $this->extractReturnWarehouseName((string) ($order->shipper_note ?? ''));

        if (!$warehouseName) {
            $returnHistoryNote = OrderHistory::query()
                ->where('order_id', $order->id)
                ->where('action', 'return_request')
                ->latest('id')
                ->value('note');

            $warehouseName = $this->extractReturnWarehouseName((string) ($returnHistoryNote ?? ''));
        }

        if (!$warehouseName) {
            return null;
        }

        return Warehouse::query()->where('name', $warehouseName)->first();
    }

    protected function resolveReturnWarehouseId(Order $order): ?int
    {
        if (Schema::hasColumn('orders', 'return_warehouse_id') && !empty($order->return_warehouse_id)) {
            return (int) $order->return_warehouse_id;
        }

        if (Schema::hasColumn('orders', 'warehouse_id') && !empty($order->warehouse_id)) {
            return (int) $order->warehouse_id;
        }

        return null;
    }

    protected function extractReturnWarehouseName(string $text): ?string
    {
        if (trim($text) === '') {
            return null;
        }

        if (preg_match('/Kho trả về:\s*([^|]+)/u', $text, $matches) !== 1) {
            return null;
        }

        $warehouseName = trim((string) ($matches[1] ?? ''));

        return $warehouseName !== '' ? $warehouseName : null;
    }

    /**
     * Stock In (Nhập kho) - View list of stock in documents
     */
    public function stockIn(Request $request)
    {
        $query = InventoryDocument::where('type', 'import')
            ->with('warehouse', 'user', 'supplier', 'items.productVariant.product');

        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $supplierId = $request->input('supplier_id');
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $from = $request->input('from_date', Carbon::now()->subDays(30)->toDateString());
        $to   = $request->input('to_date',   Carbon::now()->toDateString());
        $query->whereBetween('document_date', [$from, $to]);

        $stockInDocuments = $query->latest('document_date')->paginate(15);
        $warehouses       = $warehouseId
            ? Warehouse::where('id', $warehouseId)->get()
            : Warehouse::all();
        $productVariants  = ProductVariant::with('product')->orderBy('name')->get();
        $maxEdits         = (int) (\App\Models\Setting::get('stock_in_max_edits', 3));
        $suppliers        = \App\Models\Supplier::active()->orderBy('name')->get();

        return view('warehouse.stock-in.index', compact('stockInDocuments', 'from', 'to', 'warehouses', 'productVariants', 'maxEdits', 'suppliers', 'supplierId'));
    }

    /**
     * Store a new Phiếu Nhập Kho (import document).
     */
    public function storeStockIn(Request $request)
    {
        return $this->storeDocument($request, 'import');
    }

    /**
     * Stock Out (Xuất kho) - View list of stock out documents
     */
    public function stockOut(Request $request)
    {
        $query = InventoryDocument::where('type', 'export')
            ->with('warehouse', 'user', 'items.productVariant.product');

        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $from = $request->input('from_date', Carbon::now()->subDays(30)->toDateString());
        $to   = $request->input('to_date',   Carbon::now()->toDateString());
        $query->whereBetween('document_date', [$from, $to]);

        $stockOutDocuments = $query->latest('document_date')->paginate(15);
        $warehouses        = $warehouseId
            ? Warehouse::where('id', $warehouseId)->get()
            : Warehouse::all();
        $productVariants   = ProductVariant::with('product')->orderBy('name')->get();

        return view('warehouse.stock-out.index', compact('stockOutDocuments', 'from', 'to', 'warehouses', 'productVariants'));
    }

    /**
     * Store a new Phiếu Xuất Kho (export document).
     */
    public function storeStockOut(Request $request)
    {
        return $this->storeDocument($request, 'export');
    }

    /**
     * Show a single inventory document (for warehouse users).
     */
    public function showDocument(InventoryDocument $document)
    {
        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId && (int) $document->warehouse_id !== (int) $warehouseId) {
            abort(403, 'Bạn không có quyền xem phiếu kho này.');
        }
        $document->load('items.productVariant.product', 'warehouse', 'user', 'edits.user');
        return view('warehouse.document-show', compact('document'));
    }

    /**
     * Return JSON data for populating the edit-stock-in modal.
     */
    public function editStockIn(Request $request, InventoryDocument $document)
    {
        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId && (int) $document->warehouse_id !== (int) $warehouseId) {
            abort(403, 'Bạn không có quyền sửa phiếu này.');
        }
        if ($document->type !== 'import') {
            abort(400, 'Chỉ hỗ trợ điều chỉnh phiếu nhập kho.');
        }

        if (!$document->document_date->isToday()) {
            return response()->json([
                'ok' => false,
                'message' => 'Chỉ được điều chỉnh phiếu nhập kho trong ngày hôm nay.',
            ], 422);
        }

        $maxEdits = (int) (\App\Models\Setting::get('stock_in_max_edits', 3));
        if ($document->edit_count >= $maxEdits) {
            return response()->json([
                'ok' => false,
                'message' => "Phiếu này đã đạt giới hạn {$maxEdits} lần điều chỉnh.",
            ], 422);
        }

        $document->load('items.productVariant.product');

        return response()->json([
            'ok'         => true,
            'document'   => [
                'id'            => $document->id,
                'document_number' => $document->document_number ?? '#' . $document->id,
                'document_date' => $document->document_date->format('Y-m-d'),
                'notes'         => $document->notes,
                'shipping_fee'  => (float) $document->shipping_fee,
                'edit_count'    => $document->edit_count,
                'max_edits'     => $maxEdits,
            ],
            'items' => $document->items->map(fn ($item) => [
                'id'                 => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'variant_name'       => $item->productVariant?->name
                                        ?? $item->productVariant?->product?->name
                                        ?? 'SP #' . $item->product_variant_id,
                'sku'                => $item->productVariant?->sku ?? '',
                'quantity'           => (int) $item->quantity,
                'unit_cost'          => (float) $item->unit_cost,
            ])->values(),
        ]);
    }

    /**
     * Apply an edit to a stock-in document (adjust quantities/costs).
     */
    public function updateStockIn(Request $request, InventoryDocument $document)
    {
        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId && (int) $document->warehouse_id !== (int) $warehouseId) {
            abort(403, 'Bạn không có quyền sửa phiếu này.');
        }
        if ($document->type !== 'import') {
            return back()->with('error', 'Chỉ hỗ trợ điều chỉnh phiếu nhập kho.');
        }

        if (!$document->document_date->isToday()) {
            return back()->with('error', 'Chỉ được điều chỉnh phiếu nhập kho trong ngày hôm nay.');
        }

        $maxEdits = (int) (\App\Models\Setting::get('stock_in_max_edits', 3));
        if ($document->edit_count >= $maxEdits) {
            return back()->with('error', "Phiếu này đã đạt giới hạn {$maxEdits} lần điều chỉnh.");
        }

        $validated = $request->validate([
            'notes'                      => 'nullable|string|max:1000',
            'edit_notes'                 => 'nullable|string|max:500',
            'shipping_fee'               => 'nullable|numeric|min:0',
            'items'                      => 'required|array|min:1',
            'items.*.id'                 => 'required|integer|exists:inventory_document_items,id',
            'items.*.quantity'           => 'required|integer|min:0',
            'items.*.unit_cost'          => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated, $document, $maxEdits) {
                $document->load('items');

                $itemMap = $document->items->keyBy('id');

                // Ensure all submitted item IDs belong to this document.
                foreach ($validated['items'] as $row) {
                    if (!$itemMap->has((int) $row['id'])) {
                        throw new \RuntimeException('Dòng sản phẩm không thuộc phiếu này.');
                    }
                }

                $changes = [];

                foreach ($validated['items'] as $row) {
                    $item      = $itemMap[(int) $row['id']];
                    $oldQty    = (int) $item->quantity;
                    $newQty    = (int) $row['quantity'];
                    $oldCost   = (float) $item->unit_cost;
                    $newCost   = (float) $row['unit_cost'];
                    $delta     = $newQty - $oldQty;

                    if ($delta === 0 && $newCost === $oldCost) {
                        continue; // Nothing changed for this item
                    }

                    // Adjust inventory quantity by delta.
                    if ($delta !== 0) {
                        $inventory = Inventory::lockForUpdate()
                            ->where('product_variant_id', $item->product_variant_id)
                            ->where('warehouse_id', $document->warehouse_id)
                            ->first();

                        if (!$inventory) {
                            throw new \RuntimeException(
                                'Không tìm thấy bản ghi tồn kho cho sản phẩm #' . $item->product_variant_id
                            );
                        }

                        if ($delta < 0) {
                            // Reducing import quantity — ensure we don't go below quantity
                            // already consumed by "Hoàn tất đóng hàng" orders (packed_waiting_pickup
                            // and downstream statuses). These are "hard" deductions that cannot
                            // be undone by re-running Ráp đơn hàng.
                            $completedPackingStatuses = [
                                Order::STATUS_READY_TO_SHIP, // packed_waiting_pickup
                                Order::STATUS_DELIVERING,
                                Order::STATUS_RETURNED_COMPLETED,
                                'delivering',
                                'delivered',
                                'completed',
                                'shipping',
                            ];

                            $completedQty = (int) \App\Models\OrderItem::query()
                                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                                ->join('inventory_reservations', 'inventory_reservations.order_item_id', '=', 'order_items.id')
                                ->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
                                ->where('order_items.product_variant_id', $item->product_variant_id)
                                ->where('inventories.warehouse_id', $document->warehouse_id)
                                ->whereIn('orders.status', $completedPackingStatuses)
                                ->sum('inventory_reservations.quantity');

                            $newTotal = (int) $inventory->quantity + $delta;
                            if ($newTotal < $completedQty) {
                                throw new \RuntimeException(
                                    'Không thể giảm tồn kho thấp hơn số lượng đã hoàn tất đóng hàng.'
                                );
                            }
                        }

                        // Record adjustment movement.
                        InventoryMovement::create([
                            'inventory_id'   => $inventory->id,
                            'quantity'       => $delta,
                            'type'           => 'adjustment',
                            'reference_id'   => $document->id,
                            'reference_type' => InventoryDocument::class,
                            'user_id'        => Auth::id(),
                        ]);

                        $inventory->increment('quantity', $delta);

                        // Sync variant stock.
                        $totalStock = (int) Inventory::where('product_variant_id', $item->product_variant_id)->sum('quantity');
                        ProductVariant::where('id', $item->product_variant_id)->update(['stock' => $totalStock]);
                    }

                    $changes[] = [
                        'item_id'    => $item->id,
                        'variant_id' => $item->product_variant_id,
                        'old_qty'    => $oldQty,
                        'new_qty'    => $newQty,
                        'old_cost'   => $oldCost,
                        'new_cost'   => $newCost,
                    ];

                    $item->update(['quantity' => $newQty, 'unit_cost' => $newCost]);
                }

                // Update document header.
                $document->update([
                    'notes'        => $validated['notes'] ?? $document->notes,
                    'shipping_fee' => $validated['shipping_fee'] ?? $document->shipping_fee,
                    'edit_count'   => $document->edit_count + 1,
                ]);

                // Record edit history.
                \App\Models\InventoryDocumentEdit::create([
                    'inventory_document_id' => $document->id,
                    'user_id'               => Auth::id(),
                    'edit_number'           => $document->edit_count, // already incremented above
                    'notes'                 => $validated['edit_notes'] ?? null,
                    'changes'               => $changes ?: null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // After stock adjustment, re-run Ráp đơn hàng to refresh stock_sufficient on all queued orders.
        $this->syncAllQueuedOrdersStockSufficiency(
            Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null
        );

        return redirect()->route('warehouse.stock-in')->with('success',
            'Đã điều chỉnh phiếu ' . ($document->document_number ?? '#' . $document->id) . ' thành công.'
        );
    }

    /**
     * Shared logic for creating import/export documents.
     */
    private function storeDocument(Request $request, string $type)
    {
        $isImport = $type === 'import';

        $validated = $request->validate([
            'document_date' => 'required|date',
            'warehouse_id'  => 'required|exists:warehouses,id',
            'supplier_id'   => $isImport ? 'required|exists:suppliers,id' : 'nullable|exists:suppliers,id',
            'shipping_fee'  => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string|max:1000',
            'items'         => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_cost'          => 'required|numeric|min:0',
        ]);

        // Warehouse users can only create documents for their own warehouse
        $userWarehouseId = Auth::user()->warehouse_id;
        if ($userWarehouseId && (int) $validated['warehouse_id'] !== (int) $userWarehouseId) {
            return back()->withErrors(['warehouse_id' => 'Bạn chỉ được tạo phiếu cho kho của mình.'])->withInput();
        }

        try {
            DB::transaction(function () use ($validated, $type) {
                $document = InventoryDocument::create([
                    'type'          => $type,
                    'document_date' => $validated['document_date'],
                    'warehouse_id'  => $validated['warehouse_id'],
                    'supplier_id'   => $validated['supplier_id'] ?? null,
                    'shipping_fee'  => $validated['shipping_fee'] ?? 0,
                    'notes'         => $validated['notes'] ?? null,
                    'user_id'       => Auth::id(),
                ]);

                foreach ($validated['items'] as $itemData) {
                    $document->items()->create([
                        'product_variant_id' => $itemData['product_variant_id'],
                        'quantity'           => $itemData['quantity'],
                        'unit_cost'          => $itemData['unit_cost'],
                    ]);

                    $inventory = Inventory::firstOrCreate(
                        [
                            'product_variant_id' => $itemData['product_variant_id'],
                            'warehouse_id'       => $validated['warehouse_id'],
                        ],
                        ['quantity' => 0]
                    );

                    $qty = (int) $itemData['quantity'];
                    if ($type === 'export') {
                        $available = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                        if ($available < $qty) {
                            throw new \RuntimeException('Số lượng xuất vượt quá tồn kho khả dụng cho sản phẩm.');
                        }
                        $qty = -$qty;
                    }

                    InventoryMovement::create([
                        'inventory_id'   => $inventory->id,
                        'quantity'       => $qty,
                        'type'           => $type,
                        'reference_id'   => $document->id,
                        'reference_type' => InventoryDocument::class,
                        'user_id'        => Auth::id(),
                    ]);

                    $inventory->increment('quantity', $qty);

                    $totalStock = (int) Inventory::where('product_variant_id', $itemData['product_variant_id'])->sum('quantity');
                    ProductVariant::where('id', $itemData['product_variant_id'])->update(['stock' => $totalStock]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        $label = $type === 'import' ? 'nhập' : 'xuất';
        $route = $type === 'import' ? 'warehouse.stock-in' : 'warehouse.stock-out';

        // After a new stock-in, re-run Ráp đơn hàng to refresh stock_sufficient on all queued orders.
        if ($type === 'import') {
            $this->syncAllQueuedOrdersStockSufficiency(
                Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null
            );
        }

        return redirect()->route($route)->with('success', 'Đã tạo phiếu ' . $label . ' kho thành công.');
    }

    /**
     * Inventory (Tồn kho) - View current stock levels
     */
    public function inventory(Request $request)
    {
        $warehouseId = Auth::user()->warehouse_id;
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();
        $dayStart = Carbon::parse($selectedDate)->startOfDay();
        $dayEnd = Carbon::parse($selectedDate)->endOfDay();

        $isTodaySnapshot = $selectedDate === Carbon::today()->toDateString();

        $inventoryScope = function ($query) use ($warehouseId, $status, $isTodaySnapshot) {
            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            if (!$isTodaySnapshot) {
                return;
            }

            if ($status === 'low_stock') {
                $query->whereColumn('quantity', '<=', 'low_stock_threshold');
            } elseif ($status === 'out_of_stock') {
                $query->where('quantity', 0);
            }
        };

        $products = Product::query()
            ->with([
                'avatar.media',
                'variants' => function ($variantQuery) use ($inventoryScope, $dayStart, $dayEnd) {
                    $variantQuery->with([
                        'avatar.media',
                        'inventories' => function ($inventoryQuery) use ($inventoryScope, $dayStart, $dayEnd) {
                            $inventoryScope($inventoryQuery);

                            $inventoryQuery->with([
                                'warehouse',
                                'movements' => function ($movementQuery) use ($dayStart, $dayEnd) {
                                    $movementQuery->whereBetween('created_at', [$dayStart, $dayEnd]);
                                },
                                'reservations' => function ($reservationQuery) use ($dayStart, $dayEnd) {
                                    $reservationQuery->whereBetween('reserved_at', [$dayStart, $dayEnd]);
                                },
                            ])
                                ->orderBy('warehouse_id');
                        },
                    ])
                        ->whereHas('inventories', $inventoryScope)
                        ->orderBy('name')
                        ->orderBy('sku');
                },
            ])
            ->whereHas('variants', function ($variantQuery) use ($inventoryScope) {
                $variantQuery->whereHas('inventories', $inventoryScope);
            })
            ->when($search !== '', function ($productQuery) use ($search, $inventoryScope) {
                $productQuery->where(function ($searchQuery) use ($search, $inventoryScope) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', function ($variantQuery) use ($search, $inventoryScope) {
                            $variantQuery->whereHas('inventories', $inventoryScope)
                                ->where(function ($variantSearchQuery) use ($search) {
                                    $variantSearchQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('sku', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->orderBy('name')
            ->paginate(12);

        $movementQuery = InventoryMovement::query()
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->whereHas('inventory', function ($inventoryQuery) use ($warehouseId) {
                    $inventoryQuery->where('warehouse_id', $warehouseId);
                });
            });

        $reservationQuery = InventoryReservation::query()
            ->whereBetween('reserved_at', [$dayStart, $dayEnd])
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->whereHas('inventory', function ($inventoryQuery) use ($warehouseId) {
                    $inventoryQuery->where('warehouse_id', $warehouseId);
                });
            });

        $variantIds = $products->getCollection()
            ->flatMap(fn (Product $product) => $product->variants->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $snapshotByVariant = $this->getInventorySnapshotByVariant($variantIds, $warehouseId, $selectedDate);

        $products->getCollection()->transform(function (Product $product) use ($snapshotByVariant) {
            $product->setRelation('variants', $product->variants->map(function (ProductVariant $variant) use ($snapshotByVariant) {
                $snapshot = $snapshotByVariant[(int) $variant->id] ?? [
                    'quantity' => 0,
                    'reserved' => 0,
                    'available' => 0,
                ];

                $variant->setAttribute('snapshot_quantity', (int) $snapshot['quantity']);
                $variant->setAttribute('snapshot_reserved', (int) $snapshot['reserved']);
                $variant->setAttribute('snapshot_available', (int) $snapshot['available']);

                return $variant;
            }));

            return $product;
        });

        if (!$isTodaySnapshot && in_array($status, ['low_stock', 'out_of_stock'], true)) {
            $products->setCollection(
                $products->getCollection()
                    ->map(function (Product $product) use ($status) {
                        $filteredVariants = $product->variants->filter(function (ProductVariant $variant) use ($status) {
                            $quantity = (int) ($variant->snapshot_quantity ?? 0);
                            $threshold = max(5, (int) $variant->inventories->sum(fn ($inventory) => (int) ($inventory->low_stock_threshold ?: 5)));

                            if ($status === 'out_of_stock') {
                                return $quantity <= 0;
                            }

                            return $quantity > 0 && $quantity <= $threshold;
                        })->values();

                        $product->setRelation('variants', $filteredVariants);

                        return $product;
                    })
                    ->filter(fn (Product $product) => $product->variants->isNotEmpty())
                    ->values()
            );
        }

        $snapshotStats = $this->getInventorySnapshotStats($warehouseId, $selectedDate);
        $inventoryBase = Inventory::query()
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId));

        $stats = [
            'total_items'            => (clone $inventoryBase)->count(),
            'total_products'         => Product::whereHas('variants', function ($variantQuery) use ($warehouseId) {
                $variantQuery->whereHas('inventories', function ($inventoryQuery) use ($warehouseId) {
                    if ($warehouseId) {
                        $inventoryQuery->where('warehouse_id', $warehouseId);
                    }
                });
            })->count(),
            'total_quantity'         => $snapshotStats['total_quantity'],
            'total_reserved'         => $snapshotStats['total_reserved'],
            'total_available'        => $snapshotStats['total_available'],
            'low_stock'              => $snapshotStats['low_stock'],
            'out_of_stock'           => $snapshotStats['out_of_stock'],
            'daily_import'           => (clone $movementQuery)->where('quantity', '>', 0)->sum('quantity'),
            'daily_export'           => abs((int) ((clone $movementQuery)->where('quantity', '<', 0)->sum('quantity'))),
            'daily_reserved'         => (clone $reservationQuery)->sum('quantity'),
            'daily_reservation_rows' => (clone $reservationQuery)->count(),
        ];

        return view('warehouse.inventory.index', compact('products', 'stats', 'selectedDate'));
    }

    /**
     * Manually trigger auto-cancel of overdue orders to restore accurate stock.
     */
    public function cancelOverdueOrders(Request $request)
    {
        \Artisan::call('orders:auto-cancel-overdue');
        $output = trim(\Artisan::output());

        // Parse how many orders were cancelled from command output
        preg_match('/đã hủy (\d+) đơn/u', $output, $matches);
        $count = (int) ($matches[1] ?? 0);

        if ($count > 0) {
            return back()->with('success', "Đã hủy {$count} đơn quá hạn và trả lại tồn kho.");
        }

        return back()->with('info', 'Không có đơn nào quá hạn cần xử lý.');
    }

    /**
     * Product Management (Quản lý theo sản phẩm) - View product inventory across warehouses
     */
    public function products(Request $request)
    {
        $query = Product::with(['variants' => function ($q) {
            $q->with('inventory');
        }]);

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($variantQuery) use ($search) {
                        $variantQuery->where('sku', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $products = $query->latest()->paginate(15);

        return view('warehouse.products.index', compact('products'));
    }

    /**
     * Reports & Statistics - Comprehensive warehouse metrics
     */
    public function reports(Request $request)
    {
        // Default to current day if not specified
        $rangeType = $request->input('range_type', 'day');
        $selectedDate = $request->input('selected_date', Carbon::now()->toDateString());

        $warehouseId = Auth::user()->warehouse_id;

        // Calculate date range based on selection
        $dates = $this->getDateRange($rangeType, $selectedDate);
        $from = Carbon::parse($dates['from']);
        $to = Carbon::parse($dates['to']);

        // Stock In Statistics
        $stockInData = InventoryDocument::where('type', 'import')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('document_date', [$from, $to])
            ->with('items')
            ->get()
            ->groupBy(function ($doc) use ($rangeType) {
                if ($rangeType === 'day' || $rangeType === 'custom') {
                    return $doc->document_date->format('d/m');
                } elseif ($rangeType === 'week') {
                    return 'W' . $doc->document_date->weekOfYear;
                } elseif ($rangeType === 'month') {
                    return $doc->document_date->format('m/Y');
                }
                return $doc->document_date->format('Y');
            })
            ->map(fn($docs) => [
                'count' => $docs->count(),
                'quantity' => $docs->flatMap(fn($d) => $d->items)->sum('quantity'),
            ]);

        // Stock Out Statistics
        $stockOutData = InventoryDocument::where('type', 'export')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('document_date', [$from, $to])
            ->with('items')
            ->get()
            ->groupBy(function ($doc) use ($rangeType) {
                if ($rangeType === 'day' || $rangeType === 'custom') {
                    return $doc->document_date->format('d/m');
                } elseif ($rangeType === 'week') {
                    return 'W' . $doc->document_date->weekOfYear;
                } elseif ($rangeType === 'month') {
                    return $doc->document_date->format('m/Y');
                }
                return $doc->document_date->format('Y');
            })
            ->map(fn($docs) => [
                'count' => $docs->count(),
                'quantity' => $docs->flatMap(fn($d) => $d->items)->sum('quantity'),
            ]);

        // Inventory Movement Statistics
        $movementData = InventoryMovement::whereHas('inventory', fn($q) => $q->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId)))
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy('type')
            ->map(fn($movements) => [
                'count' => $movements->count(),
                'quantity' => $movements->sum('quantity'),
            ]);

        // Top products by movement
        $topProducts = InventoryMovement::whereHas('inventory', fn($q) => $q->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId)))
            ->whereBetween('created_at', [$from, $to])
            ->with('inventory.productVariant.product')
            ->get()
            ->groupBy('inventory.product_variant_id')
            ->map(fn($movements) => [
                'product' => $movements->first()->inventory->productVariant->product,
                'variant' => $movements->first()->inventory->productVariant,
                'quantity' => $movements->sum('quantity'),
                'count' => $movements->count(),
            ])
            ->sortByDesc('quantity')
            ->take(10);

        // Statistics by product and variant (based on import/export documents)
        $reportItems = InventoryDocumentItem::query()
            ->whereHas('document', function ($q) use ($warehouseId, $from, $to) {
                $q->whereBetween('document_date', [$from, $to])
                    ->whereIn('type', ['import', 'export'])
                    ->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId));
            })
            ->with([
                'document:id,type,warehouse_id,document_date',
                'productVariant:id,product_id,name,sku',
                'productVariant.product:id,name,slug,unit',
            ])
            ->get();

        $variantStats = $reportItems
            ->groupBy('product_variant_id')
            ->map(function ($items) {
                $first = $items->first();
                $variant = $first?->productVariant;
                $product = $variant?->product;

                $inQty = (int) $items
                    ->filter(fn($item) => $item->document?->type === 'import')
                    ->sum('quantity');

                $outQty = (int) $items
                    ->filter(fn($item) => $item->document?->type === 'export')
                    ->sum('quantity');

                return [
                    'product_id' => $product?->id,
                    'product_name' => $product?->name ?? 'N/A',
                    'product_sku' => $product?->sku,
                    'unit_label' => $product?->unit_label ?? 'Cái',
                    'variant_id' => $variant?->id,
                    'variant_name' => $variant?->name ?? 'N/A',
                    'variant_sku' => $variant?->sku,
                    'in_qty' => $inQty,
                    'out_qty' => $outQty,
                    'net_qty' => $inQty - $outQty,
                ];
            })
            ->sortByDesc('net_qty')
            ->values();

        $productStats = $variantStats
            ->groupBy('product_id')
            ->map(function ($items) {
                $first = $items->first();
                $inQty = (int) $items->sum('in_qty');
                $outQty = (int) $items->sum('out_qty');

                return [
                    'product_id' => $first['product_id'],
                    'product_name' => $first['product_name'],
                    'product_sku' => $first['product_sku'],
                    'unit_label' => $first['unit_label'] ?? 'Cái',
                    'variant_count' => $items->count(),
                    'in_qty' => $inQty,
                    'out_qty' => $outQty,
                    'net_qty' => $inQty - $outQty,
                ];
            })
            ->sortByDesc('net_qty')
            ->values();

        // Overall statistics
        $totals = [
            'total_stock_in' => InventoryDocument::where('type', 'import')
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->whereBetween('document_date', [$from, $to])
                ->withSum('items', 'quantity')
                ->get()
                ->sum('items_sum_quantity') ?? 0,
            'total_stock_out' => InventoryDocument::where('type', 'export')
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->whereBetween('document_date', [$from, $to])
                ->withSum('items', 'quantity')
                ->get()
                ->sum('items_sum_quantity') ?? 0,
            'total_movements' => InventoryMovement::whereHas('inventory', fn($q) => $q->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId)))
                ->whereBetween('created_at', [$from, $to])
                ->count(),
        ];

        return view('warehouse.reports.index', compact(
            'rangeType',
            'selectedDate',
            'from',
            'to',
            'stockInData',
            'stockOutData',
            'movementData',
            'topProducts',
            'totals',
            'productStats',
            'variantStats'
        ));
    }

    /**
     * Helper method to calculate date range
     */
    private function getDateRange($rangeType, $selectedDate)
    {
        $date = Carbon::parse($selectedDate);

        return match ($rangeType) {
            'day' => [
                'from' => $date->toDateString(),
                'to' => $date->toDateString(),
            ],
            'week' => [
                'from' => $date->startOfWeek()->toDateString(),
                'to' => $date->endOfWeek()->toDateString(),
            ],
            'month' => [
                'from' => $date->startOfMonth()->toDateString(),
                'to' => $date->endOfMonth()->toDateString(),
            ],
            'year' => [
                'from' => $date->startOfYear()->toDateString(),
                'to' => $date->endOfYear()->toDateString(),
            ],
            'custom' => [
                'from' => $selectedDate,
                'to' => $selectedDate,
            ],
            default => [
                'from' => $date->toDateString(),
                'to' => $date->toDateString(),
            ],
        };
    }
}
