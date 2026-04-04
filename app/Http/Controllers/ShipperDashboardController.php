<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ShipperDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:shipper,admin']);
    }

    public function index()
    {
        $userId = Auth::id();
        $today  = Carbon::today();

        $stats = [
            'today_total'    => Order::where('shipper_id', $userId)->whereDate('created_at', $today)->count(),
            'delivering'     => Order::where('shipper_id', $userId)->where('status', Order::STATUS_DELIVERING)->count(),
            'delivered_today'=> Order::where('shipper_id', $userId)
                                    ->where('status', 'delivered')
                                    ->whereDate('delivered_at', $today)->count(),
            'returning'      => Order::where('shipper_id', $userId)->where('status', Order::STATUS_RETURNING)->count(),
            'cod_today'      => Order::where('shipper_id', $userId)
                                    ->where('status', 'delivered')
                                    ->whereDate('delivered_at', $today)
                                    ->sum('collected_amount'),
            'available'      => Order::where('status', Order::STATUS_READY_TO_SHIP)->whereNull('shipper_id')->count(),
        ];

        return view('shipper.dashboard', compact('stats'));
    }

    /**
     * Orders ready to be picked up by a shipper.
     */
    public function available(Request $request)
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $today = Carbon::today();
        $startDate = $today->copy()->subDays(6)->toDateString();

        $dailyCounts = Order::query()
            ->selectRaw('DATE(updated_at) as day_key, COUNT(*) as total')
            ->where('status', Order::STATUS_READY_TO_SHIP)
            ->whereNull('shipper_id')
            ->whereDate('updated_at', '>=', $startDate)
            ->whereDate('updated_at', '<=', $today->toDateString())
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

        $orders = Order::with(['customer.addresses', 'items.variant.product'])
            ->where('status', Order::STATUS_READY_TO_SHIP)
            ->whereNull('shipper_id')
            ->where(function ($query) use ($selectedDate) {
                $query->whereDate('updated_at', $selectedDate)
                    ->orWhereDate('created_at', $selectedDate);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('shipper.available', compact('orders', 'selectedDate', 'quickDates'));
    }

    /**
     * Accept an order: packed_waiting_pickup → delivering (with concurrency lock)
     */
    public function accept(Order $order)
    {
        $accepted = DB::transaction(function () use ($order) {
            $fresh = Order::with('items')
                ->where('id', $order->id)
                ->where('status', Order::STATUS_READY_TO_SHIP)
                ->whereNull('shipper_id')
                ->where(function ($query) {
                    $today = Carbon::today()->toDateString();

                    $query->whereDate('updated_at', $today)
                        ->orWhereDate('created_at', $today);
                })
                ->lockForUpdate()
                ->first();

            if (!$fresh) {
                return false;
            }

            $packingHistory = $fresh->histories()
                                    ->with('user')
                                    ->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
                                    ->latest('id')
                                    ->first();

            $warehouseId = (int) ($fresh->warehouse_id
                ?: $packingHistory?->user?->warehouse_id
                ?: 0);

            $fresh->update([
                'shipper_id' => Auth::id(),
                'status'     => Order::STATUS_DELIVERING,
            ]);

            OrderHistory::create([
                'order_id'      => $fresh->id,
                'action'        => 'shipper_accepted',
                'user_id'       => Auth::id(),
                'role'          => 'shipper',
                'status_before' => Order::STATUS_READY_TO_SHIP,
                'status_after'  => Order::STATUS_DELIVERING,
                'note'          => 'Shipper nhận đơn để giao',
            ]);

            if ($warehouseId <= 0) {
                throw new \RuntimeException('Không xác định được kho xuất cho đơn hàng này.');
            }

            $document = InventoryDocument::create([
                'type'          => 'export',
                'document_date' => now()->toDateString(),
                'warehouse_id'  => $warehouseId,
                'notes'         => 'Xuất kho cho đơn #' . $fresh->code,
                'shipping_fee'  => (float) ($fresh->shipping_fee ?? 0),
                'user_id'       => Auth::id(),
            ]);

            $fresh->loadMissing('items');

            foreach ($fresh->items as $item) {
                $document->items()->create([
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                    'unit_cost'          => $item->price ?? 0,
                ]);

                $this->deductStockForAcceptedOrderItem($fresh, $document, $item, $warehouseId);
            }

            return true;

        });
        
        if (!$accepted) {
            return back()->with('error', 'Đơn hàng này không còn khả dụng hoặc không thuộc ngày lên đón hôm nay.');
        }

        return redirect()->route('shipper.my-orders')
            ->with('success', 'Đã nhận đơn #' . $order->code . ' thành công!');
    }

    /**
     * My delivering orders.
     */
    public function myOrders()
    {
        $orders = Order::with(['customer.addresses', 'items.variant.product', 'warehouse', 'histories.user.warehouse'])
            ->where('shipper_id', Auth::id())
            ->whereIn('status', [Order::STATUS_DELIVERING, Order::STATUS_COMPLETED])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('shipper.delivering', compact('orders'));
    }

    /**
     * Show delivery confirmation form.
     */
    public function deliveredForm(Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 403, 'Đơn không đang giao.');

        $order->load(['customer.addresses', 'items.variant.product']);

        return view('shipper.deliver-form', compact('order'));
    }

    /**
     * Confirm delivery: delivering → delivered
     */
    public function markDelivered(Request $request, Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 422, 'Đơn không đang giao.');

        $request->validate([
            'collected_amount' => 'required|numeric|min:0',
            'payment_method'   => 'required|in:cash,transfer',
            'proof_image'      => 'required|image|max:5120',
        ]);

        $imagePath = $request->file('proof_image')->store('order-proofs', 'public');

        $order->update([
            'status'           => 'delivered',
            'collected_amount' => $request->collected_amount,
            'delivered_at'     => now(),
            'proof_images'     => [$imagePath],
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'delivered',
            'user_id'       => Auth::id(),
            'role'          => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after'  => 'delivered',
            'note'          => 'Giao hàng thành công. Đã thu: ' . number_format($request->collected_amount) . 'đ – ' . ($request->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản'),
        ]);

        return redirect()->route('shipper.my-orders')->with('success', 'Xác nhận giao hàng thành công!');
    }

    /**
     * Show return form.
     */
    public function returnForm(Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 403, 'Đơn không đang giao.');

        $order->load(['customer.addresses', 'items.variant.product', 'warehouse', 'histories.user.warehouse']);
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $sourceWarehouse = $this->resolveOrderWarehouse($order);

        return view('shipper.return-form', compact('order', 'warehouses', 'sourceWarehouse'));
    }

    /**
     * Submit return: delivering → returning
     */
    public function storeReturn(Request $request, Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 422, 'Đơn không đang giao.');

        $request->validate([
            'return_reason' => 'required|string',
            'return_note'   => 'nullable|string|max:500',
            'return_image'  => 'required|image|max:5120',
            'return_warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $imagePath = $request->file('return_image')->store('order-returns-proof', 'public');
        $returnWarehouse = Warehouse::query()->findOrFail((int) $request->input('return_warehouse_id'));

        $shipperNote = trim((string) $request->input('return_note', ''));
        $warehouseNote = 'Kho trả về: ' . $returnWarehouse->name;
        $shipperNote = $shipperNote !== '' ? $shipperNote . ' | ' . $warehouseNote : $warehouseNote;

        $updateData = [
            'status'        => Order::STATUS_RETURNING,
            'return_reason' => $request->return_reason,
            'shipper_note'  => $shipperNote,
            'proof_images'  => [$imagePath],
        ];

        if (Schema::hasColumn('orders', 'return_warehouse_id')) {
            $updateData['return_warehouse_id'] = $returnWarehouse->id;
        }

        if (Schema::hasColumn('orders', 'warehouse_id')) {
            $updateData['warehouse_id'] = $returnWarehouse->id;
        }

        $order->update($updateData);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'return_request',
            'user_id'       => Auth::id(),
            'role'          => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after'  => Order::STATUS_RETURNING,
            'note'          => 'Shipper gửi trả hàng: ' . $request->return_reason . ' | ' . $warehouseNote,
        ]);

        return redirect()->route('shipper.my-orders')->with('success', 'Đã gửi yêu cầu trả hàng về kho.');
    }

    /**
     * Delivery history.
     */
    public function history(Request $request)
    {
        $date = $request->input('date');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $period = $request->input('period');

        // Default landing state: show today's deliveries when no filters are provided.
        if (empty($date) && empty($fromDate) && empty($toDate) && empty($period)) {
            $period = 'today';
        }

        if (empty($date) && !empty($period)) {
            $today = Carbon::today();

            if ($period === 'today') {
                $date = $today->toDateString();
            } elseif (in_array($period, ['7', '15', '30'], true)) {
                $fromDate = $today->copy()->subDays(((int) $period) - 1)->toDateString();
                $toDate = $today->toDateString();
            }
        }

        $query = Order::with('customer')
            ->where('shipper_id', Auth::id())
            ->whereIn('status', ['delivered', Order::STATUS_RETURNING, Order::STATUS_RETURNED_COMPLETED, 'completed'])
            ->when(!empty($date), function ($q) use ($date) {
                $q->whereDate('updated_at', $date);
            }, function ($q) use ($fromDate, $toDate) {
                if (!empty($fromDate)) {
                    $q->whereDate('updated_at', '>=', $fromDate);
                }

                if (!empty($toDate)) {
                    $q->whereDate('updated_at', '<=', $toDate);
                }
            });

        $summaryOrders = (clone $query)->get([
            'status',
            'shipping_fee',
            'charge_shipping_fee',
            'collected_amount',
        ]);

        $stats = [
            'total' => $summaryOrders->count(),
            'delivered' => $summaryOrders->where('status', 'delivered')->count(),
            'returning' => $summaryOrders->where('status', Order::STATUS_RETURNING)->count(),
            'completed' => $summaryOrders->where('status', 'completed')->count(),
            'total_ship_fee' => $summaryOrders->sum(function ($order) {
                return ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0;
            }),
            'total_collected' => (float) $summaryOrders->sum(function ($order) {
                return (float) ($order->collected_amount ?? 0);
            }),
        ];

        $orders = $query
            ->orderByDesc('updated_at')
            ->paginate(20);

        $filters = [
            'date' => $date,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'period' => $period,
        ];

        return view('shipper.history', compact('orders', 'stats', 'filters'));
    }

    protected function authorizeShipper(Order $order): void
    {
        if ($order->shipper_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }
    }

    protected function resolveOrderWarehouse(Order $order): ?Warehouse
    {
        if ($order->warehouse) {
            return $order->warehouse;
        }

        $packingHistory = $order->histories
            ->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
            ->sortByDesc('id')
            ->first();

        return $packingHistory?->user?->warehouse;
    }

    private function deductStockForAcceptedOrderItem(Order $order, InventoryDocument $document, $item, int $warehouseId): void
    {
        $remaining = (int) $item->quantity;

        $reservations = InventoryReservation::query()
            ->where('order_item_id', $item->id)
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            if ($remaining <= 0) {
                break;
            }

            $inventory = Inventory::query()->lockForUpdate()->find($reservation->inventory_id);
            if (!$inventory) {
                continue;
            }

            $deductQty = min($remaining, (int) $reservation->quantity);
            if ($deductQty <= 0) {
                continue;
            }

            if ((int) $inventory->quantity < $deductQty || (int) $inventory->reserved_quantity < $deductQty) {
                throw new \RuntimeException('Dữ liệu tồn kho đặt trước không hợp lệ khi xuất kho.');
            }

            $inventory->quantity -= $deductQty;
            $inventory->reserved_quantity -= $deductQty;
            $inventory->save();

            InventoryMovement::create([
                'inventory_id'   => $inventory->id,
                'quantity'       => -$deductQty,
                'type'           => 'export',
                'reference_id'   => $document->id,
                'reference_type' => InventoryDocument::class,
                'user_id'        => Auth::id(),
            ]);

            $remaining -= $deductQty;
            $reservation->quantity -= $deductQty;

            if ((int) $reservation->quantity <= 0) {
                $reservation->delete();
            } else {
                $reservation->save();
            }
        }

        if ($remaining > 0) {
            $inventories = Inventory::query()
                ->where('product_variant_id', $item->product_variant_id)
                ->where('warehouse_id', $warehouseId)
                ->orderByDesc('quantity')
                ->lockForUpdate()
                ->get();

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $available = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                if ($available <= 0) {
                    continue;
                }

                $deductQty = min($remaining, $available);

                $inventory->quantity -= $deductQty;
                $inventory->save();

                InventoryMovement::create([
                    'inventory_id'   => $inventory->id,
                    'quantity'       => -$deductQty,
                    'type'           => 'export',
                    'reference_id'   => $document->id,
                    'reference_type' => InventoryDocument::class,
                    'user_id'        => Auth::id(),
                ]);

                $remaining -= $deductQty;
            }
        }

        if ($remaining > 0) {
            throw new \RuntimeException('Không đủ tồn kho khả dụng để xuất cho đơn #' . ($order->code ?: $order->id));
        }

        $this->syncVariantStockFromInventories((int) $item->product_variant_id);
    }

    private function syncVariantStockFromInventories(int $variantId): void
    {
        $totalStock = (int) Inventory::query()
            ->where('product_variant_id', $variantId)
            ->sum('quantity');

        ProductVariant::query()
            ->where('id', $variantId)
            ->update(['stock' => $totalStock]);
    }
}
