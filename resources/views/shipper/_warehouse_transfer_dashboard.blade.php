@php
    $extractDeliveryHour = static function ($deliveryTime): ?int {
        if (!preg_match('/^\s*(\d{1,2})(?=\D|$)/u', trim((string) $deliveryTime), $matches)) return null;
        $hour = (int) $matches[1];
        return $hour >= 0 && $hour <= 23 ? $hour : null;
    };
    $timelineTransfers = $transfers->groupBy(function ($transfer) use ($extractDeliveryHour) {
        return $extractDeliveryHour($transfer->order?->delivery_time ?: $transfer->order?->customer?->delivery_time);
    })->forget(null)->sortKeys();
@endphp

<div class="transfer-timeline">
    <div class="fw-bold text-muted mb-2"><i class="bi bi-clock-history me-1"></i>Điều hướng theo giờ giao</div>
    <div class="transfer-timeline-track">
        @forelse($timelineTransfers as $hour => $hourTransfers)
            <div class="transfer-timeline-row">
                <span class="transfer-timeline-time">{{ $hour }}:00</span>
                <div class="d-flex flex-nowrap gap-2">
                    @foreach($hourTransfers as $transfer)
                        @php $meta = $statusMeta[$transfer->status] ?? ['Khác', 'completed', 'bi-circle']; @endphp
                        <a href="#transfer-card-{{ $transfer->id }}" class="transfer-nav-pill wh-transfer-{{ $meta[1] }}" title="{{ $transfer->order?->customer?->name ?? 'Đơn hàng' }} - {{ $meta[0] }}">{{ $transfer->sequence_number }}</a>
                    @endforeach
                </div>
            </div>
        @empty
            <span class="text-muted small">Không có đơn có giờ giao.</span>
        @endforelse
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($statusMeta as $status => $meta)
        <span class="wh-transfer-status-badge wh-transfer-{{ $meta[1] }}"><i class="bi {{ $meta[2] }} me-1"></i>{{ $meta[0] }}: {{ $transfers->where('status', $status)->count() }}</span>
    @endforeach
</div>

<div class="transfer-list d-flex flex-column gap-3">
    @forelse($transfers as $transfer)
        @php $meta = $statusMeta[$transfer->status] ?? ['Khác', 'completed', 'bi-circle']; @endphp
        @include('shipper._transfer_card', ['transfer' => $transfer, 'status' => $meta[1]])
    @empty
        <div class="card border-0 shadow-sm text-center py-5">
            <i class="bi bi-truck fs-1 text-muted"></i>
            <p class="mt-2 text-muted">Phiếu này không có chuyến điều chuyển nào.</p>
        </div>
    @endforelse
</div>
