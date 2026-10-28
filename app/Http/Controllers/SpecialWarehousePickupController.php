<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\SpecialWarehousePickupService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecialWarehousePickupController extends Controller
{
    public function options(Request $request, SpecialWarehousePickupService $service)
    {
        $data = $request->validate(['customer_id' => 'required|integer|exists:customers,id', 'variant_ids' => 'required|array|min:1|max:100', 'variant_ids.*' => 'required|integer|distinct|exists:product_variants,id']);
        $customer = Customer::findOrFail($data['customer_id']);
        $service->authorizeCustomer($customer, $request->user());
        if (!$service->eligible($customer, $data['variant_ids'])) return response()->json(['eligible' => false]);
        $customer->load('defaultShipper:id,name');
        return response()->json([
            'variants' => \App\Models\ProductVariant::with('product')->whereIn('id', $data['variant_ids'])->get()->map(fn ($variant) => ['id' => $variant->id, 'name' => $variant->product?->name.' · '.$variant->name, 'is_priced_by_kg' => (bool) $variant->effective_priced_by_kg]),
            'eligible' => true, 'shipper' => $customer->defaultShipper?->only(['id', 'name']),
            'warehouses' => $service->warehouses($request->user())->orderBy('name')->get(['id', 'name']),
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, SpecialWarehousePickupService $service)
    {
        $data = $request->validate([
            'creation_token' => 'required|uuid', 'customer_id' => 'required|integer|exists:customers,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'account_id' => 'prohibited', 'amount_received' => 'prohibited', 'cash_received' => 'prohibited',
            'stock_source' => ['required', Rule::in(['existing', 'new_receipt'])],
            'receipt_source' => 'required_if:stock_source,new_receipt|nullable|string|max:500',
            'goods_received' => 'required|accepted',
            'charge_vat' => 'nullable|boolean', 'vat_percent' => 'nullable|numeric|min:0|max:100', 'note' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1|max:100',
            'items.*.variant_id' => 'required|integer|distinct|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1|max:100000',
            'items.*.unit_price' => 'required|numeric|gt:0|max:999999999999.99',
            'items.*.actual_weight' => 'required|numeric|min:0.001|max:99999999.999',
            'items.*.unit_cost' => 'required_if:stock_source,new_receipt|nullable|numeric|min:0|max:999999999999.99',
        ], [
            'account_id.prohibited' => 'Phần thu tiền do kế toán xử lý. Vui lòng tải lại trang để dùng luồng giao hàng mới.',
            'amount_received.prohibited' => 'Phần thu tiền do kế toán xử lý. Vui lòng tải lại trang để dùng luồng giao hàng mới.',
            'cash_received.prohibited' => 'Phần thu tiền do kế toán xử lý. Vui lòng tải lại trang để dùng luồng giao hàng mới.',
        ]);
        $order = $service->create($data, $request->user());
        return response()->json(['success' => true, 'message' => 'Đã hoàn tất giao hàng tại kho. Đơn chờ kế toán xác nhận doanh thu và cập nhật thanh toán; giữ shipper mặc định của khách.', 'order' => ['id' => $order->id, 'code' => $order->code, 'url' => route('site.orders.show', $order)]], 201);
    }
}
