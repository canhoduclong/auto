<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaskAssignment;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseTransfer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseApiController extends BaseApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $user = $request->user();
        $warehouseId = $user->warehouse_id ? (int) $user->warehouse_id : null;
        $date = $request->filled('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : now()->toDateString();
        $readyToPackStatuses = ['approved', Order::STATUS_READY_TO_PACK];
        $packedStatuses = ['packed', Order::STATUS_READY_TO_SHIP];

        $applyWarehouseScope = function ($query) use ($warehouseId, $user, $readyToPackStatuses) {
            if ($warehouseId && $user?->hasRole('warehouse')) {
                $query->where(function ($warehouseScope) use ($warehouseId, $readyToPackStatuses) {
                    $warehouseScope->where('warehouse_id', $warehouseId)
                        ->orWhere(function ($sharedScope) use ($readyToPackStatuses) {
                            $sharedScope->whereNull('warehouse_id')
                                ->whereIn('status', array_merge($readyToPackStatuses, [Order::STATUS_PACKING]));
                        });
                });
            }

            return $query;
        };

        $dailyOrdersQuery = Order::query()
            ->with('customer:id,name,phone,address')
            ->whereDate('created_at', $date);

        $applyWarehouseScope($dailyOrdersQuery);

        $stats = [
            'ready_to_pack' => $applyWarehouseScope(Order::query())
                ->whereIn('status', $readyToPackStatuses)
                ->whereDate('created_at', $date)
                ->count(),
            'packed' => $applyWarehouseScope(Order::query())
                ->whereIn('status', $packedStatuses)
                ->whereDate('updated_at', $date)
                ->count(),
            'packing' => $applyWarehouseScope(Order::query())
                ->where('status', Order::STATUS_PACKING)
                ->whereDate('created_at', $date)
                ->count(),
            'returning' => $applyWarehouseScope(Order::query())
                ->where('status', Order::STATUS_RETURNING)
                ->whereDate('created_at', $date)
                ->count(),
            'returned' => 0,
            'assigned_tasks' => 0,
            'completed_tasks' => 0,
            'transfers_incoming' => WarehouseTransfer::query()
                ->where('target_warehouse_id', $warehouseId)
                ->where('status', WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE)
                ->count(),
            'transfers_completed' => WarehouseTransfer::query()
                ->where('target_warehouse_id', $warehouseId)
                ->where('status', WarehouseTransfer::STATUS_RECEIVED_COMPLETED)
                ->whereDate('updated_at', $date)
                ->count(),
            'done_today' => $applyWarehouseScope(Order::query())
                ->whereIn('status', $packedStatuses)
                ->whereDate('updated_at', $date)
                ->count(),
            'orders_in_day' => (clone $dailyOrdersQuery)->count(),
            'receiving' => WarehouseInventoryTransfer::query()
                ->where('target_warehouse_id', $warehouseId)
                ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
                ->count(),
            'received' => WarehouseInventoryTransfer::query()
                ->where('target_warehouse_id', $warehouseId)
                ->where('status', WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED)
                ->whereDate('updated_at', $date)
                ->count(),
        ];

        $tasks = collect([
            [
                'key' => 'orders',
                'label' => 'Đơn cần đóng gói',
                'total' => ($stats['ready_to_pack'] ?? 0) + ($stats['packing'] ?? 0) + ($stats['packed'] ?? 0),
                'done' => $stats['packed'] ?? 0,
                'route_key' => 'orders',
            ],
            [
                'key' => 'incoming_transfers',
                'label' => 'Đơn điều chuyển đến',
                'total' => ($stats['transfers_incoming'] ?? 0) + ($stats['transfers_completed'] ?? 0),
                'done' => $stats['transfers_completed'] ?? 0,
                'route_key' => 'incoming_transfers',
            ],
            [
                'key' => 'incoming_inventory_transfers',
                'label' => 'Tiếp nhận hàng',
                'total' => ($stats['receiving'] ?? 0) + ($stats['received'] ?? 0),
                'done' => $stats['received'] ?? 0,
                'route_key' => 'incoming_inventory_transfers',
            ],
            [
                'key' => 'returns',
                'label' => 'Tiếp nhận đơn hoàn trả',
                'total' => ($stats['returning'] ?? 0) + ($stats['returned'] ?? 0),
                'done' => $stats['returned'] ?? 0,
                'route_key' => 'returns',
            ],
            [
                'key' => 'tasks',
                'label' => 'Nhiệm vụ được giao',
                'total' => ($stats['assigned_tasks'] ?? 0) + ($stats['completed_tasks'] ?? 0),
                'done' => $stats['completed_tasks'] ?? 0,
                'route_key' => 'tasks',
            ],
        ])->map(function (array $task, int $index) {
            $total = (int) $task['total'];
            $done = (int) $task['done'];
            $status = $this->dashboardTaskStatus($total, $done);
            $percent = $total > 0 ? (int) round($done / $total * 100) : 0;

            return array_merge($task, [
                'sequence' => $index + 1,
                'total' => $total,
                'done' => $done,
                'percent' => $percent,
                'status' => $status,
                'status_label' => match ($status) {
                    'todo' => 'Cần làm',
                    'inprogress' => 'Đang làm',
                    'done' => 'Đã hoàn thành',
                    default => 'Chưa có nhiệm vụ',
                },
                'color' => match ($status) {
                    'todo' => '#dc3545',
                    'inprogress' => '#f59e42',
                    'done' => '#198754',
                    default => '#b0b0b0',
                },
            ]);
        })->values();

        $products = Product::with(['variants.inventories' => function ($q) use ($warehouseId) {
            if ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        }, 'variants.inventories.movements', 'variants.product'])
            ->orderBy('name')
            ->get();

        $summaryRows = $products->map(function ($product) {
            $variants = $product->variants
                ->filter(fn ($variant) => $variant->inventories->isNotEmpty())
                ->sortBy(fn ($variant) => mb_strtolower((string) ($variant->name ?? '')))
                ->values();

            $closing = (int) $variants->sum(fn ($variant) => (int) ($variant->snapshot_quantity ?? $variant->inventories->sum('quantity')));
            $import = (int) $variants->sum(fn ($variant) => (int) $variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '>', 0)->sum('quantity')));
            $reserved = (int) $variants->sum(fn ($variant) => (int) ($variant->snapshot_reserved ?? $variant->inventories->sum('reserved_quantity')));
            $export = (int) $variants->sum(fn ($variant) => (int) abs($variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '<', 0)->sum('quantity'))));
            $opening = (int) ($closing - $import + $export);

            $variantRows = $variants->map(function ($variant) {
                $vClosing = (int) ($variant->snapshot_quantity ?? $variant->inventories->sum('quantity'));
                $vImport = (int) $variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '>', 0)->sum('quantity'));
                $vReserved = (int) ($variant->snapshot_reserved ?? $variant->inventories->sum('reserved_quantity'));
                $vExport = (int) abs($variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '<', 0)->sum('quantity')));
                $vOpening = (int) ($vClosing - $vImport + $vExport);

                return [
                    'name' => (string) ($variant->name ?: ($variant->product?->name ?? 'Biến thể')),
                    'unit' => (string) ($variant->product?->unit_label ?? '—'),
                    'opening' => $vOpening,
                    'import' => $vImport,
                    'reserved' => $vReserved,
                    'export' => $vExport,
                    'closing' => $vClosing,
                ];
            })->filter(fn ($row) => (int) $row['closing'] > 0)->values();

            $unitLabels = $variantRows
                ->pluck('unit')
                ->filter(fn ($unit) => $unit !== '—' && $unit !== '')
                ->unique()
                ->values();
            $productUnit = $unitLabels->count() === 1 ? (string) $unitLabels->first() : ($unitLabels->count() > 1 ? 'Nhiều DVT' : '—');

            return [
                'product_id' => (int) $product->id,
                'name' => (string) $product->name,
                'unit' => $productUnit,
                'variant_count' => (int) $variants->count(),
                'opening' => $opening,
                'import' => $import,
                'reserved' => $reserved,
                'export' => $export,
                'closing' => $closing,
                'variants' => $variantRows,
            ];
        })
            ->filter(fn ($row) => (int) $row['closing'] > 0)
            ->sortBy(fn ($row) => mb_strtolower((string) $row['name']))
            ->values();

        $recentPacked = $applyWarehouseScope(Order::with('customer:id,name'))
            ->whereIn('status', $packedStatuses)
            ->whereDate('updated_at', $date)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->map(fn (Order $order, int $index) => [
                'sequence' => $index + 1,
                'id' => (int) $order->id,
                'code' => (string) ($order->code ?? ('#' . $order->id)),
                'customer_name' => (string) ($order->customer?->name ?? '—'),
                'total' => (float) ($order->total ?? 0),
                'updated_time' => optional($order->updated_at)->format('H:i'),
                'updated_at' => optional($order->updated_at)->toIso8601String(),
            ])
            ->values();

        $reminders = $tasks
            ->filter(fn ($task) => (int) $task['total'] > 0 && (int) $task['done'] < (int) $task['total'])
            ->map(fn ($task) => [
                'label' => $task['label'],
                'percent' => $task['percent'],
                'message' => ((int) $task['done'] === 0) ? 'Hãy bắt đầu ngay!' : 'Hãy tiếp tục...',
            ])
            ->values();

        return $this->ok([
            'selected_date' => $date,
            'stats' => $stats,
            'tasks' => $tasks,
            'receiving_alert' => [
                'show' => ($stats['receiving'] ?? 0) > 0,
                'count' => $stats['receiving'] ?? 0,
                'message' => 'Cần tiếp nhận hàng: Hiện có ' . ($stats['receiving'] ?? 0) . ' phiếu điều chuyển chờ tiếp nhận!',
                'route_key' => 'incoming_inventory_transfers',
            ],
            'legend' => [
                ['label' => 'Cần làm', 'color' => '#dc3545'],
                ['label' => 'Đang làm', 'color' => '#f59e42'],
                ['label' => 'Đã hoàn thành', 'color' => '#198754'],
                ['label' => 'Chưa có nhiệm vụ', 'color' => '#b0b0b0'],
            ],
            'work_reminders' => $reminders,
            'changes' => [
                ['icon' => 'edit', 'label' => 'Yêu cầu thay đổi đơn hàng từ sale', 'badge' => 'Mới', 'color' => '#0d6efd', 'badge_color' => '#0dcaf0'],
                ['icon' => 'chat', 'label' => 'Sale trả lời khách hàng', 'badge' => 'Đã trả lời', 'color' => '#198754', 'badge_color' => '#198754'],
                ['icon' => 'truck', 'label' => 'Phiếu điều chuyển kho chờ ship nhận', 'badge' => 'Chờ ship', 'color' => '#ffc107', 'badge_color' => '#ffc107'],
            ],
            'inventory_summary' => [
                'title' => 'Danh sách thống kê tồn kho (sản phẩm và biến thể cùng một cấu trúc cột)',
                'totals' => [
                    'opening' => (int) $summaryRows->sum('opening'),
                    'import' => (int) $summaryRows->sum('import'),
                    'reserved' => (int) $summaryRows->sum('reserved'),
                    'export' => (int) $summaryRows->sum('export'),
                    'closing' => (int) $summaryRows->sum('closing'),
                ],
                'rows' => $summaryRows,
            ],
            'recent_packed' => $recentPacked,
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $status = (string) $request->query('status', '');
        $date = (string) $request->query('date', now()->toDateString());
        $user = $request->user();
        $warehouseId = $user->warehouse_id ? (int) $user->warehouse_id : null;
        $sharedQueueStatuses = ['approved', Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING];

        $query = Order::query()
            ->with([
                'customer:id,name,phone,address,delivery_time',
                'warehouse:id,name',
                'items.product:id,name,unit',
                'items.variant' => fn ($q) => $q->withAvailableStock()->with('product:id,name,unit'),
            ])
            ->where(function ($q) {
                $q->whereNull('is_return_order')->orWhere('is_return_order', false);
            })
            ->whereDate('created_at', $date)
            ->when($warehouseId && $user->hasRole('warehouse'), function ($q) use ($warehouseId, $sharedQueueStatuses) {
                $q->where(function ($warehouseScope) use ($warehouseId, $sharedQueueStatuses) {
                    $warehouseScope->where('warehouse_id', $warehouseId)
                        ->orWhere(function ($sharedScope) use ($sharedQueueStatuses) {
                            $sharedScope->whereNull('warehouse_id')
                                ->whereIn('status', $sharedQueueStatuses);
                        });
                });
            });

        if ($status !== '') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['approved', Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING, 'packed', Order::STATUS_READY_TO_SHIP]);
        }

        $orders = $query
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(50)
            ->through(fn (Order $order) => $this->warehouseOrderPayload($order));

        return $this->paginated($orders);
    }

    public function startPacking(Request $request, Order $order): JsonResponse
    {
        $this->ensureWarehouseRole($request);

        return $this->callWebWarehouseAction($request, fn () => app(WarehouseDashboardController::class)->startPacking($request, $order));
    }

    public function completePacking(Request $request, Order $order): JsonResponse
    {
        $this->ensureWarehouseRole($request);

        return $this->callWebWarehouseAction($request, fn () => app(WarehouseDashboardController::class)->completePacking($request, $order));
    }

    public function updateLogistics(Request $request, Order $order): JsonResponse
    {
        $this->ensureWarehouseRole($request);

        return $this->callWebWarehouseAction($request, fn () => app(WarehouseDashboardController::class)->updateLogistics($request, $order));
    }

    public function requestAdjustment(Request $request, Order $order): JsonResponse
    {
        $this->ensureWarehouseRole($request);

        return $this->callWebWarehouseAction($request, fn () => app(WarehouseDashboardController::class)->requestAdjustment($request, $order));
    }

    public function inventory(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $warehouseId = $request->user()->warehouse_id ? (int) $request->user()->warehouse_id : null;

        $query = Inventory::query()
            ->with(['productVariant.product'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest('updated_at');

        $items = $query->paginate(30)->through(function (Inventory $inventory) {
            $variant = $inventory->productVariant;
            $product = $variant?->product;

            return [
                'id' => (int) $inventory->id,
                'variant_id' => (int) ($inventory->product_variant_id ?? 0),
                'sku' => (string) ($variant?->sku ?? ''),
                'product_name' => (string) ($product?->name ?? 'San pham'),
                'variant_name' => (string) ($variant?->name ?? ''),
                'quantity' => (int) ($inventory->quantity ?? 0),
                'reserved' => (int) ($inventory->reserved_quantity ?? 0),
                'available' => max(0, (int) ($inventory->quantity ?? 0) - (int) ($inventory->reserved_quantity ?? 0)),
                'updated_at' => optional($inventory->updated_at)->toIso8601String(),
            ];
        });

        return $this->paginated($items);
    }

    public function returns(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $warehouseId = $request->user()->warehouse_id ? (int) $request->user()->warehouse_id : null;

        $query = OrderReturn::query()
            ->with(['order:id,code,status', 'customer:id,name,phone', 'warehouse:id,name'])
            ->latest('updated_at');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $this->paginated($query->paginate(20));
    }

    public function tasks(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $user = $request->user();

        $tasks = TaskAssignment::query()
            ->with(['creator:id,name', 'assignees.user:id,name'])
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhereHas('assignees', fn ($q) => $q->where('user_id', $user->id));
            })
            ->latest('updated_at')
            ->paginate(20);

        return $this->paginated($tasks);
    }

    public function products(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $query = ProductVariant::query()->with('product:id,name')->latest('id');

        if ($request->filled('keyword')) {
            $keyword = (string) $request->query('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('sku', 'like', '%' . $keyword . '%');
            });
        }

        return $this->paginated($query->paginate(30));
    }

    public function scanLookup(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120'],
        ]);

        $code = trim((string) $validated['code']);
        $variant = ProductVariant::query()
            ->with('product:id,name')
            ->where('sku', $code)
            ->orWhere('name', 'like', '%' . $code . '%')
            ->first();

        if (!$variant) {
            return $this->fail('Khong tim thay san pham theo ma scan', 404);
        }

        return $this->ok([
            'id' => (int) $variant->id,
            'sku' => (string) ($variant->sku ?? ''),
            'name' => (string) ($variant->name ?? ''),
            'product_name' => (string) ($variant->product?->name ?? ''),
            'is_priced_by_kg' => (bool) $variant->effective_priced_by_kg,
            'kg' => (float) $variant->effective_kg,
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $limit = min(50, max(1, (int) $request->query('limit', 20)));

        $items = $request->user()
            ->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id' => (string) $n->id,
                'title' => (string) ($n->data['title'] ?? 'Thong bao'),
                'message' => (string) ($n->data['message'] ?? ''),
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => optional($n->created_at)->toIso8601String(),
            ])
            ->values();

        return $this->ok($items);
    }

    private function ensureWarehouseRole(Request $request): void
    {
        $user = $request->user();
        if (!$user || !($user->hasRole('warehouse') || $user->hasRole('admin'))) {
            abort(403, 'Role khong duoc phep truy cap API warehouse');
        }
    }

    private function dashboardTaskStatus(int $total, int $done): string
    {
        if ($total === 0) {
            return 'none';
        }

        if ($done === 0) {
            return 'todo';
        }

        if ($done < $total) {
            return 'inprogress';
        }

        return 'done';
    }

    private function warehouseOrderPayload(Order $order): array
    {
        $statusMeta = $this->warehouseStatusMeta((string) $order->status);
        $items = $order->items->map(fn ($item) => $this->warehouseOrderItemPayload($item))->values();

        return [
            'id' => (int) $order->id,
            'code' => (string) ($order->code ?: '#' . $order->id),
            'daily_sequence' => $order->daily_sequence ? (int) $order->daily_sequence : null,
            'status' => (string) $order->status,
            'status_label' => $statusMeta['label'],
            'status_color' => $statusMeta['color'],
            'priority_state' => $this->priorityState((string) $order->status),
            'customer' => [
                'name' => (string) ($order->customer?->name ?? '—'),
                'phone' => (string) ($order->customer?->phone ?? ''),
                'address' => (string) ($order->customer?->address ?? ''),
            ],
            'shipping_address' => (string) ($order->recipient_address ?: $order->customer?->address ?: ''),
            'delivery_time' => (string) ($order->delivery_time ?: $order->customer?->delivery_time ?: ''),
            'created_at' => optional($order->created_at)->toIso8601String(),
            'updated_at' => optional($order->updated_at)->toIso8601String(),
            'total' => (float) ($order->total ?? 0),
            'actual_weight' => $order->actual_weight === null ? null : (float) $order->actual_weight,
            'shipping_fee' => $order->shipping_fee === null ? null : (float) $order->shipping_fee,
            'foam_box_price' => $order->foam_box_price === null ? null : (float) $order->foam_box_price,
            'charge_shipping_fee' => (bool) ($order->charge_shipping_fee ?? true),
            'charge_foam_box_fee' => (bool) ($order->charge_foam_box_fee ?? false),
            'warehouse_adjustment_status' => (string) ($order->warehouse_adjustment_status ?? Order::WAREHOUSE_ADJUSTMENT_STATUS_NONE),
            'warehouse_adjustment_note' => (string) ($order->warehouse_adjustment_note ?? ''),
            'warehouse_adjustment_rejected_reason' => (string) ($order->warehouse_adjustment_rejected_reason ?? ''),
            'warehouse_adjustment_changes' => $order->warehouse_adjustment_changes ?? [],
            'can_start_packing' => in_array((string) $order->status, ['approved', Order::STATUS_READY_TO_PACK], true)
                && $order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION
                && $order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
            'can_complete_packing' => (string) $order->status === Order::STATUS_PACKING
                && $order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION
                && $order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
            'can_request_adjustment' => in_array((string) $order->status, ['approved', Order::STATUS_READY_TO_PACK], true)
                && $order->created_at?->isToday(),
            'items' => $items,
        ];
    }

    private function warehouseOrderItemPayload($item): array
    {
        $variant = $item->variant;
        $product = $item->product ?: $variant?->product;
        $quantity = (float) ($item->quantity ?? 0);
        $unitPrice = (float) ($item->price ?? 0);
        $pricedByKg = (bool) $item->effective_priced_by_kg;
        $actualWeight = $item->actual_weight === null ? null : (float) $item->actual_weight;
        $weight = $actualWeight ?? round((float) $item->effective_unit_weight * $quantity, 3);
        $lineTotal = $pricedByKg
            ? ($actualWeight === null ? null : $actualWeight * $unitPrice)
            : $quantity * $unitPrice;

        return [
            'id' => (int) $item->id,
            'product_variant_id' => (int) ($item->product_variant_id ?? 0),
            'product_name' => (string) ($variant?->name ?? $product?->name ?? 'Sản phẩm'),
            'sku' => (string) ($variant?->sku ?? ''),
            'size' => (string) ($variant?->size ?? ''),
            'quantity' => $quantity,
            'display_total' => (string) $item->display_total_label,
            'weight' => $weight,
            'actual_weight' => $actualWeight,
            'weight_label' => $this->formatWeight($weight, $pricedByKg ? 'kg' : ($product?->unit_label ?? 'Cái')),
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'is_priced_by_kg' => $pricedByKg,
            'available_stock' => (int) ($variant?->available_stock ?? 0),
        ];
    }

    private function warehouseStatusMeta(string $status): array
    {
        return match ($status) {
            'approved', Order::STATUS_READY_TO_PACK => ['label' => 'Chờ đóng gói', 'color' => 'gray'],
            Order::STATUS_PACKING => ['label' => 'Đang đóng gói', 'color' => 'amber'],
            'packed' => ['label' => 'Đã đóng gói', 'color' => 'green'],
            Order::STATUS_READY_TO_SHIP => ['label' => 'Chờ shipper nhận', 'color' => 'green'],
            Order::STATUS_DELIVERING => ['label' => 'Đang giao', 'color' => 'green'],
            Order::STATUS_DELIVERED => ['label' => 'Đã giao', 'color' => 'green'],
            Order::STATUS_COMPLETED => ['label' => 'Hoàn thành', 'color' => 'green'],
            'pending' => ['label' => 'Chờ duyệt', 'color' => 'gray'],
            'pending_leader_approval' => ['label' => 'Chờ trưởng nhóm duyệt', 'color' => 'gray'],
            'pending_manager_approval' => ['label' => 'Chờ quản lý duyệt', 'color' => 'gray'],
            'pending_warehouse_approval' => ['label' => 'Chờ kho duyệt', 'color' => 'gray'],
            'rejected' => ['label' => 'Từ chối', 'color' => 'red'],
            default => ['label' => $status, 'color' => 'gray'],
        };
    }

    private function priorityState(string $status): string
    {
        if ($status === Order::STATUS_PACKING) {
            return 'packing';
        }

        if (in_array($status, ['packed', Order::STATUS_READY_TO_SHIP, Order::STATUS_DELIVERING, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true)) {
            return 'packed';
        }

        return 'unpacked';
    }

    private function formatWeight(float $value, string $unit): string
    {
        $formatted = rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
        return $formatted . ' ' . $unit;
    }

    private function callWebWarehouseAction(Request $request, callable $callback): JsonResponse
    {
        Auth::setUser($request->user());
        $request->headers->set('Accept', 'application/json');

        $response = $callback();
        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);
            if (($payload['ok'] ?? true) === false) {
                return $this->fail((string) ($payload['message'] ?? 'Thao tac that bai'), $response->getStatusCode(), $payload);
            }

            return $this->ok($payload, (string) ($payload['message'] ?? 'OK'));
        }

        return $this->ok(null, 'Thao tac thanh cong');
    }
}
