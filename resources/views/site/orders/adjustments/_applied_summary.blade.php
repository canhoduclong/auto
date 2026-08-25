@php
    $changedItems = $adjustment->items->filter(fn ($item) =>
        ! $item->order_item_id
        || (float) $item->original_quantity !== (float) $item->adjusted_quantity
        || abs((float) $item->original_price - (float) $item->adjusted_price) > .001
        || abs((float) $item->original_weight - (float) $item->adjusted_weight) > .001
    );
    $orderChangeLabels = [
        'recipient_name' => 'Người nhận',
        'recipient_phone' => 'Số điện thoại',
        'delivery_time' => 'Giờ giao hàng',
    ];
    $formatNumber = static fn ($value, int $precision = 3) => rtrim(rtrim(number_format((float) $value, $precision, ',', '.'), '0'), ',');
@endphp

<article class="monitor-applied-adjustment">
    <div class="monitor-applied-adjustment-head">
        <div class="monitor-applied-adjustment-title"><i class="bi bi-check-circle-fill me-1"></i>Yêu cầu #{{ $adjustment->id }} đã duyệt và áp dụng vào đơn</div>
        <div class="monitor-applied-adjustment-meta">
            Hoàn tất {{ optional($adjustment->completed_at)->format('d/m/Y H:i') }} ·
            <a href="{{ route('site.order-adjustments.show', $adjustment) }}">Xem hồ sơ</a>
        </div>
    </div>

    @if(trim((string) $adjustment->adjustment_note) !== '')
        <div class="monitor-sent-adjustment-meta"><strong>Nội dung:</strong> {{ $adjustment->adjustment_note }}</div>
    @endif

    <div class="monitor-applied-adjustment-changes">
        @foreach($changedItems as $item)
            @php
                $productName = $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm';
                $changes = collect();
                if (!$item->order_item_id) $changes->push('Bổ sung mới SL '.$formatNumber($item->adjusted_quantity));
                elseif ((float) $item->original_quantity !== (float) $item->adjusted_quantity) $changes->push('SL '.$formatNumber($item->original_quantity).' → '.$formatNumber($item->adjusted_quantity));
                if (abs((float) $item->original_price - (float) $item->adjusted_price) > .001) $changes->push('Giá '.number_format((float) $item->original_price, 0, ',', '.').'đ → '.number_format((float) $item->adjusted_price, 0, ',', '.').'đ');
                if (abs((float) $item->original_weight - (float) $item->adjusted_weight) > .001) $changes->push('KL '.$formatNumber($item->original_weight).' → '.$formatNumber($item->adjusted_weight).' kg');
            @endphp
            <span><strong>{{ $productName }}:</strong> {{ $changes->implode(' · ') }}</span>
        @endforeach
        @foreach((array) ($adjustment->order_changes ?? []) as $field => $change)
            <span><strong>{{ $orderChangeLabels[$field] ?? $field }}:</strong> {{ data_get($change, 'original') ?: '—' }} → {{ data_get($change, 'adjusted') ?: '—' }}</span>
        @endforeach
    </div>

    @include('site.orders.adjustments._fee_changes', ['adjustment' => $adjustment, 'dense' => true])
</article>
