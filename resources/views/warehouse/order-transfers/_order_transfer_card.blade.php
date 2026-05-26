@props(['order'])
<div class="order-transfer-card d-flex align-items-start" data-id="{{ $order->id }}">
    <div class="me-3 pt-1">
        <input type="checkbox" class="form-check-input order-select-checkbox" value="{{ $order->id }}">
        <div class="small text-muted mt-2 order-index">{{ $loop->iteration ?? '' }}</div>
    </div>
    <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">#{{ $order->code ?? $order->id }} - {{ $order->customer?->name ?? '—' }}</div>
                <div class="small text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                <div class="small text-muted">SĐT: {{ $order->customer?->phone ?? '—' }}</div>
                <div class="small text-muted">Địa chỉ: {{ $order->customer?->address ?? '—' }}</div>
            </div>
            <div class="text-end">
                <span class="badge bg-info">{{ $order->status_label ?? $order->status }}</span>
                <div class="small text-muted mt-1">SL: {{ $order->items->sum('quantity') }}</div>
            </div>
        </div>
        <div class="mt-2">
            <ul class="list-unstyled mb-0">
                @foreach($order->items as $item)
                    <li class="small">
                        <span class="fw-semibold">{{ $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}</span>
                        @if($item->variant?->sku)
                            <span class="text-muted">({{ $item->variant->sku }})</span>
                        @endif
                        - SL: {{ $item->quantity }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
