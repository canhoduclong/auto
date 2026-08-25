@php
    $compact = (bool) ($compact ?? false);
    $order = $adjustment->order;
    $targetId = 'leader-adjustment-review-'.$adjustment->id;
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
    $changedFeesCount = collect((array) ($adjustment->fee_changes ?? []))->filter(function ($change): bool {
        $original = (array) ($change['original'] ?? []);
        $adjusted = (array) ($change['adjusted'] ?? []);
        return (bool) ($original['enabled'] ?? false) !== (bool) ($adjusted['enabled'] ?? false)
            || abs((float) ($original['value'] ?? 0) - (float) ($adjusted['value'] ?? 0)) > .001;
    })->count();
    $formatNumber = static fn ($value, int $precision = 3) => rtrim(rtrim(number_format((float) $value, $precision, ',', '.'), '0'), ',');
@endphp

<article @if(!$compact) id="{{ $targetId }}" @endif class="leader-adjustment-review {{ $compact ? 'is-compact' : 'is-detail' }}">
    @if($compact)
        <a href="#{{ $targetId }}" class="leader-adjustment-review-link">
    @endif
            <header class="leader-adjustment-review-head">
                <div>
                    <div class="leader-adjustment-review-title">Yêu cầu #{{ $adjustment->id }} · {{ $order?->customer?->name ?? 'Khách hàng' }}</div>
                    <div class="leader-adjustment-review-meta">
                        Đơn {{ $order?->code ?: ('#'.$adjustment->order_id) }} · Sale {{ $order?->user?->short_name ?: ($order?->user?->name ?? '—') }} · {{ optional($adjustment->submitted_at)->format('d/m H:i') }}
                    </div>
                </div>
                @if($compact)<span class="leader-adjustment-jump"><i class="bi bi-arrow-down-circle"></i>Tới đơn cần duyệt</span>@endif
            </header>
            <div class="leader-adjustment-reason"><strong>Nội dung:</strong> {{ $adjustment->adjustment_note ?: 'Sale không nhập ghi chú.' }}</div>
            <div class="leader-adjustment-change-list">
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
                @if($changedFeesCount > 0)<span><strong>Phí/chiết khấu:</strong> {{ $changedFeesCount }} khoản thay đổi</span>@endif
                @if($changedItems->isEmpty() && empty($adjustment->order_changes) && $changedFeesCount === 0)<span>Không phát hiện dòng số liệu thay đổi.</span>@endif
            </div>
    @if($compact)
        </a>
    @else
        @include('site.orders.adjustments._fee_changes', ['adjustment' => $adjustment])
    @endif

    <div class="leader-adjustment-review-actions">
        <a href="{{ route('site.order-adjustments.show', $adjustment) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Xem đầy đủ</a>
        <form method="POST" action="{{ route('site.order-adjustments.approve', $adjustment) }}">
            @csrf
            <input type="hidden" name="note" value="Leader duyệt từ trang theo dõi đơn hàng">
            <button class="btn btn-sm btn-success" onclick="return confirm('Duyệt yêu cầu điều chỉnh #{{ $adjustment->id }}?')"><i class="bi bi-check2-circle me-1"></i>Duyệt yêu cầu</button>
        </form>
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#leaderAdjustmentReject{{ $compact ? 'Queue' : 'Order' }}{{ $adjustment->id }}"><i class="bi bi-x-circle me-1"></i>Từ chối</button>
    </div>
    <div class="collapse" id="leaderAdjustmentReject{{ $compact ? 'Queue' : 'Order' }}{{ $adjustment->id }}">
        <form method="POST" action="{{ route('site.order-adjustments.reject', $adjustment) }}" class="leader-adjustment-reject-form">
            @csrf
            <label class="form-label">Lý do từ chối</label>
            <textarea name="reason" class="form-control form-control-sm" rows="2" required placeholder="Nêu rõ nội dung sale cần chỉnh lại..."></textarea>
            <button class="btn btn-sm btn-danger mt-2">Xác nhận từ chối</button>
        </form>
    </div>
</article>
