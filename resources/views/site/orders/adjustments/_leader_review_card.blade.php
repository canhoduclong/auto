@php
    $compact = (bool) ($compact ?? false);
    $approvalRoleLabel = $approvalRoleLabel ?? $adjustment->approvalRoleLabel(
        $adjustment->currentPendingApprovalStep()?->step?->role_slug
    );
    $order = $adjustment->order;
    $targetId = 'leader-adjustment-review-'.$adjustment->id;
    $jumpUrl = request()->fullUrlWithQuery([
        'tab' => 'today',
        'view' => 'cards',
        'highlight' => $adjustment->order_id,
        'page' => 1,
    ]).'#'.$targetId;
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

@if($compact)
    <article class="leader-adjustment-review is-compact">
        <a href="{{ $jumpUrl }}" class="leader-adjustment-review-link" title="Tới đơn cần duyệt">
            <span class="leader-adjustment-queue-number">{{ $order?->daily_sequence ?? '!' }}</span>
            <span class="leader-adjustment-queue-copy">
                <span class="leader-adjustment-queue-title">Yêu cầu #{{ $adjustment->id }} · {{ $order?->customer?->name ?? 'Khách hàng' }}</span>
                <span class="leader-adjustment-queue-meta">Đơn {{ $order?->code ?: ('#'.$adjustment->order_id) }} · Sale {{ $order?->user?->short_name ?: ($order?->user?->name ?? '—') }}</span>
            </span>
            <span class="leader-adjustment-jump"><i class="bi bi-arrow-down-circle"></i>Tới đơn</span>
        </a>
    </article>
@else
    <article id="{{ $targetId }}" class="leader-adjustment-review is-detail">
        <div class="leader-adjustment-detail-intro">
            <span class="leader-adjustment-detail-label">Yêu cầu #{{ $adjustment->id }}</span>
            <span>Sale {{ $order?->user?->short_name ?: ($order?->user?->name ?? '—') }} · {{ optional($adjustment->submitted_at)->format('d/m H:i') }}</span>
        </div>
        @if(trim((string) $adjustment->adjustment_note) !== '')
            <div class="leader-adjustment-reason"><strong>Nội dung:</strong> {{ $adjustment->adjustment_note }}</div>
        @endif
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
        @include('site.orders.adjustments._fee_changes', ['adjustment' => $adjustment, 'dense' => true])

        <div class="leader-adjustment-review-actions">
            <a href="{{ route('site.order-adjustments.show', $adjustment) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Xem đầy đủ</a>
            <form method="POST" action="{{ route('site.order-adjustments.approve', $adjustment) }}">
                @csrf
                <input type="hidden" name="note" value="{{ $approvalRoleLabel }} duyệt từ trang theo dõi đơn hàng">
                <button class="btn btn-sm btn-success" onclick="return confirm('Duyệt yêu cầu điều chỉnh #{{ $adjustment->id }}?')"><i class="bi bi-check2-circle me-1"></i>Duyệt yêu cầu</button>
            </form>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#leaderAdjustmentRejectOrder{{ $adjustment->id }}"><i class="bi bi-x-circle me-1"></i>Từ chối</button>
        </div>
        <div class="collapse" id="leaderAdjustmentRejectOrder{{ $adjustment->id }}">
            <form method="POST" action="{{ route('site.order-adjustments.reject', $adjustment) }}" class="leader-adjustment-reject-form">
                @csrf
                <label class="form-label">Lý do từ chối</label>
                <textarea name="reason" class="form-control form-control-sm" rows="2" required placeholder="Nêu rõ nội dung sale cần chỉnh lại..."></textarea>
                <button class="btn btn-sm btn-danger mt-2">Xác nhận từ chối</button>
            </form>
        </div>
    </article>
@endif
