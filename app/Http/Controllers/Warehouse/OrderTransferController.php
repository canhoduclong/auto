<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderTransfer;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderTransferController extends Controller
{
    public function index(Request $request)
    {
        // Lấy user và kho đang quản lý
        $user = auth()->user();
        $warehouseId = $user->warehouse_id;
        $search = trim((string) $request->input('search', ''));
        $from = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->toDateString()
            : Carbon::today()->subDays(30)->toDateString();
        $to = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->toDateString()
            : Carbon::today()->toDateString();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        // Lấy danh sách đơn chưa điều chuyển, chỉ thuộc kho user quản lý
        $orders = Order::whereNull('order_transfer_id')
            ->whereIn('status', ['ready_to_ship', 'packing', 'packed', 'packed_waiting_pickup'])
            ->where(function ($dateQuery) use ($from, $to): void {
                $dateQuery->where(function ($normalQuery) use ($from, $to): void {
                    $normalQuery->whereNull('accounting_sales_import_batch_id')
                        ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);
                })->orWhere(function ($importQuery) use ($from, $to): void {
                    $importQuery->whereNotNull('accounting_sales_import_batch_id')
                        ->whereBetween('delivery_date', [$from, $to]);
                });
            })
            ->where(function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId)
                      ->orWhereNull('warehouse_id');
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->with(['customer', 'items.variant', 'warehouse'])
            ->orderBy('daily_sequence')
            ->paginate(20, ['*'], 'orders_page')
            ->withQueryString();

        // Lấy danh sách shipper và kho
        $shippers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['shipper', 'manager_shipper']);
        })->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        // Lấy đầy đủ phiếu điều chuyển, hỗ trợ tìm lại theo mã đơn.
        $recentTransfers = OrderTransfer::with([
            'orders.customer',
            'orders.warehouseTransfers' => fn ($query) => $query->latest('id'),
            'shipper',
            'warehouse',
        ])
            ->when($warehouseId, fn ($query) => $query->whereHas(
                'orders.warehouseTransfers',
                fn ($warehouseTransferQuery) => $warehouseTransferQuery->where('source_warehouse_id', $warehouseId)
            ))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('orders', function ($orderQuery) use ($search) {
                    $orderQuery->where('code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (OrderTransfer $transfer) {
                $statuses = $transfer->orders
                    ->map(fn (Order $order) => $order->warehouseTransfers->first()?->status)
                    ->filter()
                    ->values();

                $transfer->setAttribute('can_delete', $statuses->isEmpty() || $statuses->every(
                    fn ($status) => $status === WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP
                ));
                $transfer->setAttribute('is_completed', $statuses->isNotEmpty() && $statuses->every(
                    fn ($status) => $status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED
                ));
                $transfer->setAttribute('status_label', $this->transferStatusLabel($statuses));

                return $transfer;
            });
        $transferGroups = $recentTransfers->groupBy(fn (OrderTransfer $transfer) => $transfer->created_at->toDateString());

        return view('warehouse.order-transfers', compact(
            'orders',
            'shippers',
            'warehouses',
            'recentTransfers',
            'transferGroups',
            'search',
            'from',
            'to'
        ));
    }

    public function destroy($id)
    {
        $transfer = OrderTransfer::with('orders.warehouseTransfers')->findOrFail($id);
        $warehouseId = auth()->user()?->warehouse_id ? (int) auth()->user()->warehouse_id : null;
        if ($warehouseId && !$transfer->orders
            ->flatMap->warehouseTransfers
            ->contains(fn (WarehouseTransfer $warehouseTransfer) => (int) $warehouseTransfer->source_warehouse_id === $warehouseId)) {
            abort(403, 'Phiếu điều chuyển không thuộc kho bạn quản lý.');
        }

        $hasStartedTransfer = $transfer->orders
            ->flatMap->warehouseTransfers
            ->contains(fn (WarehouseTransfer $warehouseTransfer) => $warehouseTransfer->status !== WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP);

        if ($hasStartedTransfer) {
            return back()->with('error', 'Phiếu đã được shipper nhận hoặc kho đích tiếp nhận nên không thể xóa.');
        }

        DB::transaction(function () use ($transfer) {
            WarehouseTransfer::query()
                ->whereIn('order_id', $transfer->orders->pluck('id'))
                ->where('status', WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP)
                ->delete();
            Order::where('order_transfer_id', $transfer->id)->update(['order_transfer_id' => null]);
            $transfer->delete();
        });

        return redirect()->route('warehouse.order-transfers')->with('success', 'Đã xóa phiếu điều chuyển!');
    }

    public function detachWaitingTransfer(OrderTransfer $transfer, Order $order)
    {
        if ((int) $order->order_transfer_id !== (int) $transfer->id) {
            return back()->with('error', 'Đơn hàng không thuộc phiếu điều chuyển này.');
        }

        $warehouseTransfer = $order->warehouseTransfers()->latest('id')->first();
        $detachableStatuses = [
            WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
            WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
        ];
        if (!$warehouseTransfer || !in_array($warehouseTransfer->status, $detachableStatuses, true)) {
            return back()->with('error', 'Chỉ có thể gỡ phiếu điều chuyển khi đơn đang chờ shipper nhận hoặc chờ kho chuyển đến tiếp nhận.');
        }

        $warehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if ($warehouseId && (int) $warehouseTransfer->source_warehouse_id !== $warehouseId) {
            abort(403, 'Đơn điều chuyển không thuộc kho bạn quản lý.');
        }

        try {
            DB::transaction(function () use ($transfer, $order, $warehouseTransfer): void {
                $lockedWarehouseTransfer = WarehouseTransfer::query()
                    ->lockForUpdate()
                    ->findOrFail($warehouseTransfer->id);
                $detachableStatuses = [
                    WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                    WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
                ];
                if (!in_array($lockedWarehouseTransfer->status, $detachableStatuses, true)) {
                    throw new \RuntimeException('Phiếu điều chuyển của đơn không còn ở trạng thái có thể gỡ.');
                }

                $needsStockRestore = $lockedWarehouseTransfer->status === WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE;
                if ($needsStockRestore && $lockedWarehouseTransfer->export_document_id) {
                    $movements = InventoryMovement::query()
                        ->with('inventory')
                        ->where('reference_type', \App\Models\InventoryDocument::class)
                        ->where('reference_id', $lockedWarehouseTransfer->export_document_id)
                        ->where('quantity', '<', 0)
                        ->lockForUpdate()
                        ->get();

                    foreach ($movements as $movement) {
                        $inventory = $movement->inventory;
                        if (!$inventory) {
                            continue;
                        }

                        $restoreQty = abs((int) $movement->quantity);
                        $inventory->increment('quantity', $restoreQty);
                        InventoryMovement::create([
                            'inventory_id' => $inventory->id,
                            'quantity' => $restoreQty,
                            'type' => 'adjustment',
                            'reference_id' => $lockedWarehouseTransfer->id,
                            'reference_type' => WarehouseTransfer::class,
                            'user_id' => Auth::id(),
                        ]);
                    }

                    $movements->pluck('inventory.product_variant_id')
                        ->filter()
                        ->unique()
                        ->each(function ($variantId): void {
                            $totalStock = (int) \App\Models\Inventory::query()
                                ->where('product_variant_id', $variantId)
                                ->sum('quantity');
                            ProductVariant::query()->whereKey($variantId)->update(['stock' => $totalStock]);
                        });
                }

                $lockedWarehouseTransfer->update([
                    'status' => WarehouseTransfer::STATUS_CANCELLED,
                    'note' => trim(($lockedWarehouseTransfer->note ? $lockedWarehouseTransfer->note . ' | ' : '')
                        . ($needsStockRestore
                            ? 'Kho nguồn gỡ đơn khỏi phiếu điều chuyển khi đang chờ kho đích tiếp nhận; đã hoàn tồn kho nguồn để chọn shipper khác.'
                            : 'Kho nguồn gỡ đơn khỏi phiếu điều chuyển khi đang chờ shipper nhận để chọn shipper khác.')),
                ]);
                $order->forceFill(['order_transfer_id' => null])->save();

                OrderHistory::create([
                    'order_id' => $order->id,
                    'action' => 'warehouse_transfer_detached_waiting_receive',
                    'user_id' => Auth::id(),
                    'role' => 'warehouse',
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => 'Gỡ phiếu điều chuyển #' . $transfer->id . ' khỏi đơn hàng'
                        . '; hủy vận chuyển #' . $lockedWarehouseTransfer->id
                        . ($needsStockRestore ? ' và hoàn tồn kho nguồn' : '')
                        . ' để chọn shipper khác. Đơn hàng vẫn được giữ nguyên.',
                ]);

                if (!$transfer->orders()->exists()) {
                    $transfer->delete();
                }
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã gỡ phiếu điều chuyển khỏi đơn. Đơn hàng được giữ nguyên và có thể chọn shipper khác.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shipper_id' => 'required|exists:users,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_ids' => 'required|string',
        ]);

        $orderIds = array_filter(explode(',', $data['order_ids']));
        if (empty($orderIds)) {
            return back()->withErrors(['order_ids' => 'Vui lòng chọn ít nhất một đơn hàng.']);
        }


        $sourceWarehouseId = auth()->user()?->warehouse_id ? (int) auth()->user()->warehouse_id : null;
        if ($sourceWarehouseId && $sourceWarehouseId === (int) $data['warehouse_id']) {
            return back()->withErrors(['warehouse_id' => 'Kho nhận phải khác kho đang quản lý.']);
        }

        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->whereNull('order_transfer_id')
            ->whereIn('status', ['ready_to_ship', 'packing', 'packed', 'packed_waiting_pickup'])
            ->when($sourceWarehouseId, fn ($query) => $query->where('warehouse_id', $sourceWarehouseId))
            ->get();

        if ($orders->count() !== count($orderIds)) {
            return back()->withErrors(['order_ids' => 'Một hoặc nhiều đơn không hợp lệ, đã được điều chuyển hoặc không thuộc kho đang quản lý.']);
        }

        $orderTransfer = DB::transaction(function () use ($data, $orders, $sourceWarehouseId) {
            $orderTransfer = OrderTransfer::query()
                ->with(['orders.warehouseTransfers' => fn ($query) => $query->latest('id')])
                ->where('shipper_id', $data['shipper_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->whereDate('created_at', Carbon::today())
                ->when($sourceWarehouseId, fn ($query) => $query->whereHas(
                    'orders.warehouseTransfers',
                    fn ($warehouseTransferQuery) => $warehouseTransferQuery->where('source_warehouse_id', $sourceWarehouseId)
                ))
                ->latest('id')
                ->get()
                ->first(function (OrderTransfer $transfer) {
                    $latestStatuses = $transfer->orders
                        ->map(fn (Order $order) => $order->warehouseTransfers->first()?->status)
                        ->filter();

                    return $latestStatuses->isNotEmpty()
                        && $latestStatuses->every(fn ($status) => $status === WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP);
                });

            if (!$orderTransfer) {
                $orderTransfer = OrderTransfer::create([
                    'shipper_id' => $data['shipper_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'notes' => null,
                    'created_by' => auth()->id(),
                ]);
            }

            foreach ($orders as $order) {
                $order->order_transfer_id = $orderTransfer->id;
                $order->save();
                WarehouseTransfer::create([
                    'order_id' => $order->id,
                    'source_warehouse_id' => $order->warehouse_id,
                    'target_warehouse_id' => $data['warehouse_id'],
                    'shipper_id' => $data['shipper_id'],
                    'status' => \App\Models\WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                    'packed_total_weight' => $order->transferBaselineWeight(),
                ]);
            }

            return $orderTransfer;
        });

        return redirect()->route('warehouse.order-transfers')
            ->with('success', 'Đã cập nhật phiếu điều chuyển ngày hôm nay #' . $orderTransfer->id . '.');
    }

    private function transferStatusLabel($statuses): string
    {
        if ($statuses->isEmpty()) {
            return 'Chưa có đơn';
        }
        if ($statuses->every(fn ($status) => $status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED)) {
            return 'Đã tiếp nhận hoàn tất';
        }
        if ($statuses->contains(WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE)) {
            return 'Chờ kho đích tiếp nhận';
        }
        if ($statuses->contains(WarehouseTransfer::STATUS_IN_TRANSIT)) {
            return 'Đang vận chuyển';
        }
        if ($statuses->contains(WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP)) {
            return 'Chờ shipper nhận';
        }

        return 'Đã hủy / hoàn lại';
    }
}
