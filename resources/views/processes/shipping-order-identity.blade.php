<div class="shipping-order-customer">{{ $order?->customer?->name ?? 'Chưa có khách hàng' }}</div>
<div class="shipping-order-meta">{{ $order?->code ?? '—' }} <span aria-hidden="true">·</span> Ngày tạo: {{ $order?->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
