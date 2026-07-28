<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ImportedSalesOrderCompletionController extends Controller
{
    public function index(Request $request)
    {
        $showCompleted = $request->boolean('completed');
        $orders = Order::query()
            ->with(['customer:id,name,customer_code', 'user:id,name', 'shipper:id,name', 'warehouse:id,name', 'accountingReconciliation', 'accountingSalesEntries'])
            ->whereNotNull('accounting_sales_import_batch_id')
            ->where('needs_operational_completion', !$showCompleted)
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->paginate(30)->withQueryString();

        $layout = $request->user()->hasRole('warehouse') && !$request->user()->isAdmin()
            ? 'layouts.warehouse'
            : ($request->user()->hasRole(['account', 'accountant', 'accounting']) ? 'layouts.accounting' : 'layouts.app');

        return view('orders.imported-sales-completion', [
            'orders' => $orders,
            'warehouses' => Warehouse::where('status', true)->orderBy('name')->get(['id', 'name']),
            'shippers' => User::whereHas('roles', fn ($query) => $query->whereIn('name', ['Shipper', 'shipper', 'manager_shipper']))
                ->orderBy('name')->get(['id', 'name']),
            'showCompleted' => $showCompleted,
            'layout' => $layout,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        abort_unless($order->accounting_sales_import_batch_id, 404);
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'shipper_id' => ['required', 'integer', 'exists:users,id'],
            'operational_completion_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $isShipper = User::whereKey($data['shipper_id'])
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Shipper', 'shipper', 'manager_shipper']))->exists();
        if (!$isShipper) {
            return back()->withErrors(['shipper_id' => 'Tài khoản được chọn không thuộc nhóm Shipper.']);
        }

        $order->forceFill([
            'warehouse_id' => $data['warehouse_id'],
            'shipper_id' => $data['shipper_id'],
            'needs_operational_completion' => false,
            'operational_completion_note' => ($data['operational_completion_note'] ?? null) ?: 'Đã bổ sung Kho và Shipper cho đơn lịch sử.',
            'operational_completed_by' => $request->user()->id,
            'operational_completed_at' => now(),
        ])->save();

        return back()->with('success', 'Đã hoàn chỉnh thông tin vận hành cho đơn '.($order->code ?: '#'.$order->id).'.');
    }
}
