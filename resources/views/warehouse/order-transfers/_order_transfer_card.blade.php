@props(['order'])
<div class="order-transfer-card d-flex align-items-start" data-id="{{ $order->id }}">
    <div class="me-3 pt-1">
        <input type="checkbox" class="form-check-input order-select-checkbox" value="{{ $order->id }}">
        <div class="small text-muted mt-2 order-index">{{ $order->daily_sequence ?? '' }}</div>
    </div>
    <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">#{{ $order->code ?? $order->id }} - {{ $order->customer?->name ?? '—' }}</div>
                <div class="small text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                <div class="small text-muted">SĐT: {{ $order->customer?->phone ?? '—' }}</div>
                <div class="small text-muted">Địa chỉ: {{ $order->customer?->address ?? '—' }}</div>
                <div class="small text-muted mt-1">Kho quản lý: <span class="fw-semibold text-primary">{{ $order->warehouse?->name ?? '—' }}</span></div>
            </div>
            <div class="text-end">
                <span class="badge bg-info">{{ $order->status_label ?? $order->status }}</span>
                <div class="small text-muted mt-1">SL: {{ $order->items->sum('quantity') }}</div>
                <div class="small fw-semibold text-primary mt-1">KL bàn giao: {{ number_format($order->transferBaselineWeight(), 3, ',', '.') }} kg</div>
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
                        · KL: {{ number_format((float) ($item->packed_weight ?? $item->actual_weight ?? $item->total_weight ?? ((float) $item->quantity * $item->effective_unit_weight)), 3, ',', '.') }} kg
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
