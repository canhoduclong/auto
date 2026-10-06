@php
    $originalOrder = $adjustment->order;
    $businessDate = $originalOrder->accounting_sales_import_batch_id ? $originalOrder->delivery_date : $originalOrder->created_at;
    $address = $originalOrder->customer?->addresses?->firstWhere('is_default', 1) ?? $originalOrder->customer?->addresses?->first();
    $originalField = static fn($field, $fallback) => data_get($adjustment->order_changes, $field.'.original', $fallback);
@endphp
<div class="row g-2 small mb-3">
    <div class="col-md-6"><strong>Ngày nghiệp vụ:</strong> {{ $businessDate?->format('d/m/Y') ?? '—' }}</div>
    <div class="col-md-6"><strong>Ngày tạo đơn:</strong> {{ $originalOrder->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
    <div class="col-md-6"><strong>Khách hàng:</strong> {{ $originalOrder->customer?->name ?: '—' }}</div>
    <div class="col-md-6"><strong>Nhân viên:</strong> {{ $originalOrder->user?->name ?: '—' }}</div>
    <div class="col-md-6"><strong>Người nhận:</strong> {{ $originalField('recipient_name', $originalOrder->recipient_name ?: $originalOrder->customer?->name) ?: '—' }}</div>
    <div class="col-md-6"><strong>Điện thoại:</strong> {{ $originalField('recipient_phone', $originalOrder->recipient_phone ?: $originalOrder->customer?->phone) ?: '—' }}</div>
    <div class="col-12"><strong>Địa chỉ giao:</strong> {{ $originalOrder->recipient_address ?: $address?->note ?: $originalOrder->customer?->address ?: '—' }}</div>
    <div class="col-md-6"><strong>Ngày giao:</strong> {{ $originalOrder->delivery_date?->format('d/m/Y') ?? '—' }} {{ $originalField('delivery_time', $originalOrder->delivery_time) }}</div>
    <div class="col-md-6"><strong>Trạng thái:</strong> {{ \App\Models\Order::statusOptions()[$originalOrder->status] ?? $originalOrder->status }}</div>
    @if($originalOrder->note)
    <div class="col-12"><strong>Ghi chú đơn hàng:</strong> {{ $originalOrder->note }}</div>
    @endif
</div>
