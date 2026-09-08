@php
    $orderDate = $order->accounting_sales_import_batch_id
        ? $order->delivery_date
        : $order->created_at;
@endphp
<div class="d-flex flex-wrap gap-2 gap-md-3 text-muted small mt-2">
    <span><i class="bi bi-calendar3 me-1"></i>Ngày đơn: <strong class="text-dark">{{ $orderDate?->format('d/m/Y') ?? 'Chưa có ngày' }}</strong></span>
    <span><i class="bi bi-person-badge me-1"></i>Sale: <strong class="text-dark">{{ $order->user?->name ?: 'Chưa có sale' }}</strong></span>
</div>
