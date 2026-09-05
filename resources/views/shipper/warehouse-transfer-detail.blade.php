@extends('layouts.shipper')

@section('title', 'Chi tiết phiếu điều chuyển')
@section('subtitle', $dispatchSlip->code . ' · ' . $dispatchSlip->business_date->format('d/m/Y'))

@section('content')
@php
    $statusMeta = [
        'pending_shipper_pickup' => ['Cần nhận', 'pending', 'bi-box-arrow-in-down'],
        'in_transit' => ['Đang vận chuyển', 'transit', 'bi-truck'],
        'delivered_waiting_receive' => ['Đã giao · Chờ kho nhận', 'waiting', 'bi-building-check'],
        'received_completed' => ['Hoàn tất · Kho đã nhận', 'completed', 'bi-check2-circle'],
        'cancelled' => ['Đã hoàn lại', 'cancelled', 'bi-arrow-counterclockwise'],
    ];
@endphp

<style>
    .transfer-timeline { position: sticky; top: 56px; z-index: 10; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; margin-bottom: 20px; overflow-x: auto; }
    .transfer-timeline-track { display: flex; align-items: center; gap: 20px; width: max-content; min-width: 100%; }
    .transfer-timeline-row { display: flex; align-items: center; gap: 10px; flex: 0 0 auto; }
    .transfer-timeline-time { width: 64px; min-width: 64px; min-height: 40px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #6366f1; font-weight: 700; color: #4338ca; background: #fff; }
    .transfer-nav-pill { display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: 50%; font-weight: 700; color: #fff; text-decoration: none; border: 2px solid transparent; }
    .transfer-nav-pill.wh-transfer-pending, .wh-transfer-status-badge.wh-transfer-pending { background-color: #0f766e !important; color: #fff !important; }
    .js-transfer-card.wh-transfer-pending { border-color: #0f766e !important; }
    .transfer-nav-pill.wh-transfer-transit, .wh-transfer-status-badge.wh-transfer-transit { background-color: #f59e0b !important; color: #1f2937 !important; }
    .js-transfer-card.wh-transfer-transit { border-color: #f59e0b !important; }
    .transfer-nav-pill.wh-transfer-waiting, .wh-transfer-status-badge.wh-transfer-waiting { background-color: #0ea5e9 !important; color: #fff !important; }
    .js-transfer-card.wh-transfer-waiting { border-color: #0ea5e9 !important; }
    .transfer-nav-pill.wh-transfer-completed, .wh-transfer-status-badge.wh-transfer-completed { background-color: #15803d !important; color: #fff !important; }
    .js-transfer-card.wh-transfer-completed { border-color: #15803d !important; }
    .transfer-nav-pill.wh-transfer-cancelled, .wh-transfer-status-badge.wh-transfer-cancelled { background-color: #dc3545 !important; color: #fff !important; }
    .js-transfer-card.wh-transfer-cancelled { border-color: #dc3545 !important; }
    .wh-transfer-status-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: .4rem .65rem; font-size: .78rem; font-weight: 700; }
    .transfer-list { max-width: 980px; margin: 0 auto; }
    .transfer-card-toggle { white-space: nowrap; }
    .transfer-card-chevron { transition: transform .2s ease; }
    .transfer-card-toggle[aria-expanded="true"] .transfer-card-chevron { transform: rotate(180deg); }
    .transfer-card-summary { min-width: 0; }
    .transfer-card-actions { flex: 0 0 auto; }
    @media (max-width: 767px) {
        .transfer-timeline { position: static; }
        .transfer-card-actions { width: 100%; justify-content: flex-end; flex-wrap: wrap; }
    }
</style>

<div id="transfer-ajax-notice" class="d-none alert" role="alert"></div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <a href="{{ route('shipper.warehouse-transfers', ['date' => $today]) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Quay lại danh sách</a>
            <h5 class="mb-1 mt-2">{{ $dispatchSlip->code }}</h5>
            <div class="text-muted">{{ $dispatchSlip->sourceWarehouse?->name ?? '—' }} → {{ $dispatchSlip->targetWarehouse?->name ?? '—' }}</div>
        </div>
        <div class="text-md-end">
            <div><i class="bi bi-calendar3 me-1"></i>{{ $dispatchSlip->business_date->format('d/m/Y') }}</div>
            <div class="small text-muted">{{ $dispatchSlip->entries_count }} mục · Shipper: {{ $dispatchSlip->shipper?->short_name ?: ($dispatchSlip->shipper?->name ?? '—') }}</div>
            <span class="badge mt-1 {{ $dispatchSlip->status === 'finalized' ? 'bg-light text-dark border' : ($dispatchSlip->status === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $dispatchSlip->status === 'finalized' ? 'Phiếu đã chốt' : ($dispatchSlip->status === 'cancelled' ? 'Đã hủy' : 'Đang mở') }}</span>
        </div>
    </div>
</div>

<div class="js-transfer-dashboard" data-detail-url="{{ route('shipper.warehouse-transfers.show', $dispatchSlip) }}">
    @include('shipper._warehouse_transfer_dashboard', ['transfers' => $transfers, 'statusMeta' => $statusMeta])
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const notice = document.getElementById('transfer-ajax-notice');

    function showNotice(message, type) {
        notice.textContent = message;
        notice.className = 'alert alert-' + type;
        notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function bindDashboard() {
        const navBar = document.querySelector('.transfer-timeline');
        if (navBar) navBar.style.top = (document.querySelector('.sp-topbar')?.offsetHeight || 56) + 'px';

        function bindAjaxForm(selector, fallbackMessage, confirmationMessage) {
            document.querySelectorAll(selector).forEach(function (form) {
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (confirmationMessage && !window.confirm(confirmationMessage)) return;
                    const button = form.querySelector('button[type="submit"]');
                    button.disabled = true;
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST', body: new FormData(form),
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(payload.message || fallbackMessage);

                        const dashboard = document.querySelector('.js-transfer-dashboard');
                        const refreshed = await fetch(dashboard.dataset.detailUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!refreshed.ok) throw new Error('Đã xác nhận nhưng chưa thể cập nhật giao diện.');
                        const documentHtml = new DOMParser().parseFromString(await refreshed.text(), 'text/html');
                        const newDashboard = documentHtml.querySelector('.js-transfer-dashboard');
                        if (!newDashboard) throw new Error('Không thể đọc dữ liệu phiếu vừa cập nhật.');
                        dashboard.innerHTML = newDashboard.innerHTML;
                        bindDashboard();
                        showNotice(payload.message, 'success');
                    } catch (error) {
                        showNotice(error.message, 'danger');
                        if (button.isConnected) button.disabled = false;
                    }
                });
            });
        }

        bindAjaxForm('.js-pickup-form', 'Không thể xác nhận phiếu điều chuyển.');
        bindAjaxForm('.js-deliver-form', 'Không thể hoàn thành giao hàng.', 'Xác nhận đã giao hàng cho kho nhận?');

        document.querySelectorAll('.js-rollback-transfer-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!window.confirm('Xác nhận hoàn lại phiếu điều chuyển này trước khi kho nhận xác nhận?')) return;
                const reason = window.prompt('Nhập lý do hoàn lại (không bắt buộc):', '');
                if (reason === null) return;
                form.querySelector('input[name="rollback_note"]').value = reason.trim();
                form.submit();
            }, { once: true });
        });

        document.querySelectorAll('.transfer-nav-pill[href^="#transfer-card-"]').forEach(function (link) {
            link.addEventListener('click', function () {
                const details = document.querySelector(link.getAttribute('href'))?.querySelector('.js-transfer-details');
                if (details && window.bootstrap?.Collapse) window.bootstrap.Collapse.getOrCreateInstance(details, { toggle: false }).show();
            });
        });
    }

    bindDashboard();
});
</script>
@endpush
@endsection
