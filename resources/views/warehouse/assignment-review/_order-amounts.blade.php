@php
    $reviewSummary = app(\App\Services\WarehousePackedOrderService::class)->summary($order, useActualWeight: true, usePackedQuantity: true);
@endphp
<div class="fw-bold">{{ number_format($reviewSummary['total'], 0, ',', '.') }}đ</div>
@if($reviewSummary['extra_discount_total'] != 0)
<div class="small fw-normal text-muted">Tiền hàng: {{ number_format($reviewSummary['items_subtotal'], 0, ',', '.') }}đ</div>
<div class="small fw-normal {{ $reviewSummary['extra_discount_total'] > 0 ? 'text-danger' : 'text-primary' }}">{{ $reviewSummary['extra_discount_total'] > 0 ? 'Chiết khấu' : 'Điều chỉnh tăng' }}: {{ $reviewSummary['extra_discount_total'] > 0 ? '−' : '+' }}{{ number_format(abs($reviewSummary['extra_discount_total']), 0, ',', '.') }}đ</div>
@if($reviewSummary['extra_discount_total'] > 0 && $reviewSummary['goods_after_discount'] == 0)<div class="small fw-normal text-danger">Toàn bộ giá trị hàng</div>@endif
<div class="small fw-normal text-muted">Hàng sau chiết khấu: {{ number_format($reviewSummary['goods_after_discount'], 0, ',', '.') }}đ</div>
@if($reviewSummary['shipping_fee'] > 0)<div class="small fw-normal text-muted">Phí ship: {{ number_format($reviewSummary['shipping_fee'], 0, ',', '.') }}đ</div>@endif
@if($reviewSummary['vat_amount'] > 0)<div class="small fw-normal text-muted">VAT: {{ number_format($reviewSummary['vat_amount'], 0, ',', '.') }}đ</div>@endif
@if($reviewSummary['foam_box_fee'] > 0)<div class="small fw-normal text-muted">Phí thùng: {{ number_format($reviewSummary['foam_box_fee'], 0, ',', '.') }}đ</div>@endif
@endif
