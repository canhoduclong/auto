<article class="monitor-applied-adjustment">
    <div class="monitor-applied-adjustment-head">
        <div class="monitor-applied-adjustment-title"><i class="bi bi-check-circle-fill me-1"></i>Yêu cầu #{{ $adjustment->id }} của Sale {{ $adjustment->requester?->name ?? '—' }} đã duyệt và áp dụng vào đơn</div>
        <div class="monitor-applied-adjustment-meta">
            Hoàn tất {{ optional($adjustment->completed_at)->format('d/m/Y H:i') }} ·
            <a href="{{ route('site.order-adjustments.show', $adjustment) }}">Xem hồ sơ</a>
        </div>
    </div>
</article>
