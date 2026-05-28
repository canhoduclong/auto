<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class OrderTransferController extends Controller
{
    public function index(Request $request)
    {
        // Lấy user và kho đang quản lý
        $user = auth()->user();
        $warehouseId = $user->warehouse_id;
        // Lấy danh sách đơn chưa điều chuyển, chỉ thuộc kho user quản lý
        $orders = Order::whereNull('order_transfer_id')
            ->where('warehouse_id', $warehouseId)
            ->whereIn('status', ['ready_to_ship', 'packing', 'packed', 'packed_waiting_pickup'])
            ->with(['customer', 'items.variant', 'warehouse'])
            ->paginate(20);

        // Lấy danh sách shipper và kho
        $shippers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['shipper', 'manager_shipper']);
        })->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        // Lấy các phiếu điều chuyển mới nhất (kèm đơn)
        $recentTransfers = \App\Models\OrderTransfer::with(['orders.customer', 'shipper', 'warehouse'])
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return view('warehouse.order-transfers', compact('orders', 'shippers', 'warehouses', 'recentTransfers'));
    }
    public function destroy($id)
    {
        $transfer = \App\Models\OrderTransfer::with('orders')->findOrFail($id);
        DB::transaction(function () use ($transfer) {
            // Gỡ liên kết order_transfer_id khỏi các đơn hàng
            \App\Models\Order::where('order_transfer_id', $transfer->id)->update(['order_transfer_id' => null]);
            $transfer->delete();
        });
        return redirect()->route('warehouse.order-transfers')->with('success', 'Đã xóa phiếu điều chuyển!');
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


        DB::transaction(function () use ($data, $orderIds) {
            $orderTransfer = \App\Models\OrderTransfer::create([
                'shipper_id' => $data['shipper_id'],
                'warehouse_id' => $data['warehouse_id'],
                'notes' => null,
                'created_by' => auth()->id(),
            ]);
            // Gán order_transfer_id cho các đơn hàng
            $orders = Order::whereIn('id', $orderIds)->get();
            foreach ($orders as $order) {
                $order->order_transfer_id = $orderTransfer->id;
                $order->save();
                // Tạo WarehouseTransfer cho từng đơn
                \App\Models\WarehouseTransfer::create([
                    'order_id' => $order->id,
                    'source_warehouse_id' => $order->warehouse_id,
                    'target_warehouse_id' => $data['warehouse_id'],
                    'shipper_id' => $data['shipper_id'],
                    'status' => \App\Models\WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                ]);
            }
        });

        return redirect()->route('warehouse.order-transfers')->with('success', 'Tạo phiếu điều chuyển thành công!');
    }
}
