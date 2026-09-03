@extends('layouts.shipper')

@section('title', 'Điều chuyển')
@section('subtitle', 'Nhận - giao hàng điều chuyển giữa các kho')

@section('content')
@php
    $selectedDate = $today ?? null;
    $statusMeta = [
        'pending_shipper_pickup' => ['Cần nhận', 'pending', 'bi-box-arrow-in-down'],
        'in_transit' => ['Đang vận chuyển', 'transit', 'bi-truck'],
        'delivered_waiting_receive' => ['Giao kho', 'waiting', 'bi-building-check'],
        'received_completed' => ['Kho đã nhận', 'completed', 'bi-check2-circle'],
    ];
    $extractDeliveryHour = static function ($deliveryTime): ?int {
        if (!preg_match('/^\s*(\d{1,2})(?=\D|$)/u', trim((string) $deliveryTime), $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        return $hour >= 0 && $hour <= 23 ? $hour : null;
    };
    $timelineTransfers = $transfers->groupBy(function ($transfer) use ($extractDeliveryHour) {
        $deliveryTime = $transfer->order?->delivery_time ?: $transfer->order?->customer?->delivery_time;
        return $extractDeliveryHour($deliveryTime);
    })->forget(null)->sortKeys();
    $timelineHours = $timelineTransfers->keys();
@endphp

<style>
    .transfer-timeline {
        position: sticky;
        top: 56px;
        z-index: 10;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 20px;
        overflow-x: auto;
    }
    .transfer-timeline-track {
        display: flex;
        align-items: center;
        gap: 20px;
        width: max-content;
        min-width: 100%;
    }
    .transfer-timeline-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 0 0 auto;
    }
    .transfer-timeline-time {
        width: 64px;
        min-width: 64px;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #6366f1;
        font-weight: 700;
        color: #4338ca;
        background: #fff;
    }
    .transfer-nav-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 50%;
        font-weight: 700;
        color: #fff;
        text-decoration: none;
        border: 2px solid transparent;
    }
    .transfer-nav-pill.wh-transfer-pending,
    .wh-transfer-status-badge.wh-transfer-pending { background-color: #0f766e !important; color: #fff !important; }
    .js-transfer-card.wh-transfer-pending { border-color: #0f766e !important; }
    .transfer-nav-pill.wh-transfer-transit,
    .wh-transfer-status-badge.wh-transfer-transit { background-color: #f59e0b !important; color: #1f2937 !important; }
    .js-transfer-card.wh-transfer-transit { border-color: #f59e0b !important; }
    .transfer-nav-pill.wh-transfer-waiting,
    .wh-transfer-status-badge.wh-transfer-waiting { background-color: #0ea5e9 !important; color: #fff !important; }
    .js-transfer-card.wh-transfer-waiting { border-color: #0ea5e9 !important; }
    .transfer-nav-pill.wh-transfer-completed,
    .wh-transfer-status-badge.wh-transfer-completed { background-color: #64748b !important; color: #fff !important; }
    .js-transfer-card.wh-transfer-completed { border-color: #64748b !important; }
    .wh-transfer-status-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .4rem .65rem;
        font-size: .78rem;
        font-weight: 700;
    }
    .transfer-list {
        max-width: 980px;
        margin: 0 auto;
    }
    .transfer-card-toggle {
        width: 100%;
        border: 0;
        text-align: left;
        cursor: pointer;
    }
    .transfer-card-toggle:hover { background: #f8fafc !important; }
    .transfer-card-chevron { transition: transform .2s ease; }
    .transfer-card-toggle[aria-expanded="true"] .transfer-card-chevron { transform: rotate(180deg); }
    @media (max-width: 767px) {
        .transfer-timeline {
            position: static;
        }
    }
</style>

@if($selectedDate)
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 alert alert-info py-2 mb-3">
        <span>
            <i class="bi bi-calendar-event me-1"></i>
            Ngày điều chuyển <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>
        </span>
        <form method="GET" class="d-flex gap-2">
            <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm">
            <button class="btn btn-sm btn-primary" type="submit">Xem</button>
        </form>
    </div>
@endif

<div class="transfer-timeline">
    <div class="fw-bold text-muted mb-2"><i class="bi bi-clock-history me-1"></i>Điều hướng theo giờ giao</div>
    <div class="transfer-timeline-track">
        @forelse($timelineHours as $hour)
            <div class="transfer-timeline-row">
                <span class="transfer-timeline-time">{{ $hour }}:00</span>
                <div class="d-flex flex-nowrap gap-2">
                    @foreach($timelineTransfers->get($hour, collect()) as $transfer)
                        @php
                            $meta = $statusMeta[$transfer->status] ?? ['Khác', 'completed', 'bi-circle'];
                        @endphp
                        <a href="#transfer-card-{{ $transfer->id }}"
                           class="transfer-nav-pill wh-transfer-{{ $meta[1] }}"
                           title="{{ $transfer->order?->customer?->name ?? 'Đơn hàng' }} - {{ $meta[0] }}">
                            {{ $transfer->sequence_number }}
                        </a>
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
        <span class="wh-transfer-status-badge wh-transfer-{{ $meta[1] }}">
            <i class="bi {{ $meta[2] }} me-1"></i>{{ $meta[0] }}: {{ $transfers->where('status', $status)->count() }}
        </span>
    @endforeach
</div>

<div class="transfer-list d-flex flex-column gap-3">
    @forelse($transfers as $transfer)
        @php $meta = $statusMeta[$transfer->status] ?? ['Khác', 'completed', 'bi-circle']; @endphp
        @include('shipper._transfer_card', ['transfer' => $transfer, 'status' => $meta[1]])
    @empty
        <div class="card border-0 shadow-sm text-center py-5">
            <i class="bi bi-truck fs-1 text-muted"></i>
            <p class="mt-2 text-muted">Không có phiếu điều chuyển nào.</p>
        </div>
    @endforelse
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const navBar = document.querySelector('.transfer-timeline');
    if (navBar) {
        navBar.style.top = (document.querySelector('.sp-topbar')?.offsetHeight || 56) + 'px';
    }

    document.querySelectorAll('.js-rollback-transfer-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!window.confirm('Xác nhận hoàn lại phiếu điều chuyển này trước khi kho nhận xác nhận?')) return;
            const reason = window.prompt('Nhập lý do hoàn lại (không bắt buộc):', '');
            if (reason === null) return;
            form.querySelector('input[name="rollback_note"]').value = reason.trim();
            form.submit();
        });
    });

    document.querySelectorAll('.transfer-nav-pill[href^="#transfer-card-"]').forEach(function (link) {
        link.addEventListener('click', function () {
            const card = document.querySelector(link.getAttribute('href'));
            const details = card?.querySelector('.js-transfer-details');
            if (details && window.bootstrap?.Collapse) {
                window.bootstrap.Collapse.getOrCreateInstance(details, { toggle: false }).show();
            }
        });
    });
});
</script>
@endpush
@endsection
