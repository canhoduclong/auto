@if($order->is_special_pickup)
<div class="alert alert-info my-3">
    <strong>Hàng đặc biệt — đã hoàn tất giao hàng tại kho</strong>
    <div>Shipper trên đơn: {{ $order->shipper?->name ?? '#'.$order->shipper_id }} (shipper mặc định tại thời điểm tạo đơn).</div>
    <div>{{ $order->accountingReconciliation?->status === 'confirmed' ? 'Kế toán đã xác nhận doanh thu.' : 'Chờ kế toán xác nhận doanh thu.' }} Thanh toán: {{ ['paid' => 'Đã thanh toán', 'partially_paid' => 'Thanh toán một phần', 'partial' => 'Thanh toán một phần'][$order->payment_status] ?? 'Chưa thanh toán' }}.</div>
    <div class="small">@if($order->special_import_document_id)Phiếu nhập #{{ $order->special_import_document_id }} · @endif Phiếu xuất #{{ $order->special_export_document_id }}@if($order->special_payment_id) · Phiếu thu #{{ $order->special_payment_id }}@endif</div>
</div>
@endif
