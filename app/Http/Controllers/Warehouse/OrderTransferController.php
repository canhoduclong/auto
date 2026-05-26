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
        // Lấy danh sách đơn chưa điều chuyển
        $orders = Order::whereNull('order_transfer_id')
            ->whereIn('status', ['ready_to_ship', 'packing'])
            ->with(['customer', 'items.variant'])
            ->paginate(20);

        // Lấy danh sách shipper và kho
        $shippers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['shipper', 'manager_shipper']);
        })->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('warehouse.order-transfers', compact('orders', 'shippers', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transfers' => 'required|array',
            'transfers.*.shipper_id' => 'required|exists:users,id',
            'transfers.*.warehouse_id' => 'required|exists:warehouses,id',
            'transfers.*.order_ids' => 'required|array|min:1',
            'transfers.*.order_ids.*' => 'required|exists:orders,id',
            'transfers.*.notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['transfers'] as $transfer) {
                // Tạo phiếu điều chuyển (giả sử có model OrderTransfer)
                $orderTransfer = \App\Models\OrderTransfer::create([
                    'shipper_id' => $transfer['shipper_id'],
                    'warehouse_id' => $transfer['warehouse_id'],
                    'notes' => $transfer['notes'] ?? null,
                ]);
                // Gán đơn vào phiếu
                Order::whereIn('id', $transfer['order_ids'])
                    ->update(['order_transfer_id' => $orderTransfer->id]);
            }
        });

        return redirect()->route('warehouse.order-transfers')->with('success', 'Tạo phiếu điều chuyển thành công!');
    }
}
