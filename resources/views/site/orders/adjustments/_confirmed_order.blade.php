@php
    $beforeAdjustment = $beforeAdjustment ?? false;
    $businessDate = $order->accounting_sales_import_batch_id ? $order->delivery_date : $order->created_at;
@endphp
<section class="adjustment-card" id="{{ $beforeAdjustment ? 'original-order' : 'confirmed-order' }}">
    <div class="adjustment-card-head">
        <h2 class="adjustment-card-title"><i class="bi bi-receipt"></i>{{ $beforeAdjustment ? 'Đơn trước khi sửa' : ($adjustment->status === 'completed' ? 'Đơn hàng sau xác nhận' : 'Đơn hàng hiện tại') }}</h2>
        <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">{{ $order->code }}</a>
    </div>
    <div class="adjustment-card-body">
        @if($beforeAdjustment)
            <p class="text-muted small">Toàn bộ đơn hàng hiện tại để đối chiếu với nội dung đề nghị thay đổi bên dưới. Yêu cầu điều chỉnh này chưa được áp dụng.</p>
        @elseif($adjustment->status === 'approved')
            <div class="alert alert-warning">Yêu cầu đã được duyệt, đang chờ Kho xác nhận. Các thay đổi chưa được áp dụng vào đơn hàng bên dưới.</div>
        @else
            <p class="text-muted small">Điều chỉnh đã được áp dụng. Dưới đây là thông tin hiện tại của toàn bộ đơn hàng.</p>
        @endif
        @php
            $address = $order->customer?->addresses?->firstWhere('is_default', 1) ?? $order->customer?->addresses?->first();
        @endphp
        <div class="row g-3">
            <div class="col-md-6"><strong>Ngày nghiệp vụ của đơn:</strong> {{ optional($businessDate)->format('d/m/Y') ?: '—' }}</div>
            <div class="col-md-6"><strong>Ngày tạo đơn:</strong> {{ optional($order->created_at)->format('d/m/Y H:i') ?: '—' }}</div>
            <div class="col-md-6"><strong>Khách hàng:</strong> {{ $order->customer?->name ?: '—' }}</div>
            <div class="col-md-6"><strong>Nhân viên:</strong> {{ $order->user?->name ?: '—' }}</div>
            <div class="col-md-6"><strong>Người nhận:</strong> {{ $order->recipient_name ?: $order->customer?->name ?: '—' }}</div>
            <div class="col-md-6"><strong>Điện thoại:</strong> {{ $order->recipient_phone ?: $order->customer?->phone ?: '—' }}</div>
            <div class="col-12"><strong>Địa chỉ giao:</strong> {{ $order->recipient_address ?: $address?->note ?: $order->customer?->address ?: '—' }}</div>
            <div class="col-md-6"><strong>Ngày giao:</strong> {{ optional($order->delivery_date)->format('d/m/Y') ?: '—' }} {{ $order->delivery_time }}</div>
            <div class="col-md-6"><strong>Trạng thái:</strong> {{ \App\Models\Order::statusOptions()[$order->status] ?? $order->status }}</div>
            @if($order->note)<div class="col-12"><strong>Ghi chú đơn hàng:</strong> {{ $order->note }}</div>@endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table adjustment-table align-middle">
            <thead><tr><th>Sản phẩm / biến thể</th><th>ĐVT</th><th class="text-end">Số lượng</th><th class="text-end">Khối lượng (kg)</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
            <tbody>
                @foreach($order->items as $orderItem)
                    <tr>
                        <td><div class="adjustment-product">{{ $orderItem->product?->name ?: $orderItem->variant?->product?->name ?: $orderItem->imported_name ?: 'Sản phẩm' }}</div><div class="small text-muted">{{ $orderItem->variant?->name }} @if($orderItem->variant?->sku) · {{ $orderItem->variant->sku }} @endif</div></td>
                        <td>{{ $orderItem->product?->unit_label ?: $orderItem->variant?->product?->unit_label ?: '—' }}</td>
                        <td class="text-end">{{ $compactNumber($orderItem->quantity) }}</td>
                        <td class="text-end">{{ $orderItem->effective_priced_by_kg ? $compactNumber((float) $orderItem->quantity > 0 ? ($orderItem->actual_weight ?? $orderItem->total_weight ?? 0) : 0) : '—' }}</td>
                        <td class="text-end">{{ $money($orderItem->price) }} / {{ $orderItem->effective_priced_by_kg ? 'kg' : ($orderItem->product?->unit_label ?: 'đơn vị') }}</td>
                        <td class="text-end">{{ $money($orderItem->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="adjustment-card-body">
        <div class="adjustment-meta-list">
            <div class="adjustment-meta-row"><span>Tiền hàng</span><span>{{ $money($order->items->sum('total')) }}</span></div>
            <div class="adjustment-meta-row"><span>Chiết khấu thêm</span><span>{{ $money($order->extra_discount_total) }}</span></div>
            @if($order->charge_vat)<div class="adjustment-meta-row"><span>VAT</span><span>{{ $money($order->vat_amount) }}</span></div>@endif
            @if($order->charge_shipping_fee)<div class="adjustment-meta-row"><span>Phí vận chuyển</span><span>{{ $money($order->shipping_fee) }}</span></div>@endif
            @if($order->collect_customer_shipping_fee)<div class="adjustment-meta-row"><span>Phí giao hàng thu khách</span><span>{{ $money($order->customer_shipping_fee) }}</span></div>@endif
            @if($order->charge_foam_box_fee)<div class="adjustment-meta-row"><span>Phí thùng xốp</span><span>{{ $money($order->foam_box_price) }}</span></div>@endif
            @foreach($order->additionalFees as $fee)<div class="adjustment-meta-row"><span>{{ $fee->fee_name }}</span><span>{{ $fee->direction === 'discount' ? '-' : '+' }}{{ $money($fee->amount) }}</span></div>@endforeach
            <div class="adjustment-meta-row"><span>Tổng đơn hàng</span><span>{{ $money($order->total) }}</span></div>
            <div class="adjustment-meta-row"><span>Đã thanh toán</span><span>{{ $money(max(0, (float) $order->amount_paid, (float) ($order->collected_amount ?? 0))) }}</span></div>
            <div class="adjustment-meta-row"><span>Còn phải thu</span><span>{{ $money($order->amount_due) }}</span></div>
            <div class="adjustment-meta-row"><span>Thanh toán</span><span>{{ ['paid' => 'Đã thanh toán', 'partial' => 'Thanh toán một phần', 'partially_paid' => 'Thanh toán một phần', 'unpaid' => 'Chưa thanh toán'][$order->payment_status] ?? $order->payment_status }}</span></div>
        </div>
    </div>
</section>
