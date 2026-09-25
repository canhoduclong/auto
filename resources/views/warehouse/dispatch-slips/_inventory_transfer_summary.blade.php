<span>
    <strong>{{ $transfer->transfer_code ?: '#'.$transfer->id }}</strong>
    <span class="d-block small text-muted">
        {{ $transfer->items->count() }} mặt hàng · Tổng SL: {{ number_format((int) $transfer->items->sum('quantity')) }} · {{ number_format((float) $transfer->items->sum('weight_kg'), 3, ',', '.') }} kg
    </span>
    <span class="dispatch-inventory-items">
        @forelse($transfer->items as $item)
            @php
                $variant = $item->variant;
                $productName = $variant?->product?->name ?? $variant?->name ?? 'Sản phẩm';
                $variantName = $variant?->name;
                $sku = $variant?->sku;
            @endphp
            <span class="dispatch-inventory-item">
                <span class="dispatch-inventory-item-name">
                    {{ $productName }}
                    @if($variantName || $sku)
                        <small>{{ collect([$variantName, $sku])->filter()->unique()->join(' · ') }}</small>
                    @endif
                </span>
                <span class="dispatch-inventory-item-quantity">
                    SL: {{ number_format((int) $item->quantity) }}
                    <small>{{ number_format((float) $item->weight_kg, 3, ',', '.') }} kg</small>
                </span>
            </span>
        @empty
            <span class="small text-muted">Chưa có chi tiết mặt hàng.</span>
        @endforelse
    </span>
</span>
