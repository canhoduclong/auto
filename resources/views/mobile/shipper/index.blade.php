@extends('mobile.layouts.app')

@section('title', 'Mobile Shipper')

@section('content')
@include('mobile.partials.profile-card', ['roleLabel' => auth()->user()?->job_title ?: 'Giao hàng'])

<div id="spAlert"></div>
<div id="spOrders"></div>
@endsection

@push('styles')
<style>
    .sp-order-card {
        transition: border-color .18s ease, box-shadow .18s ease;
    }
    .sp-order-card.is-completed {
        border-color: #cbd5e1;
        box-shadow: 0 6px 16px rgba(100, 116, 139, .1);
    }
    .sp-status {
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
        padding: 4px 8px;
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .sp-status.is-delivering {
        background: #ecfeff;
        color: #0e7490;
    }
    .sp-status.is-delivered,
    .sp-status.is-completed {
        background: #f1f5f9;
        color: #475569;
    }
    .sp-btn-processing {
        background: #facc15;
        color: #713f12;
        border: 1px solid #eab308;
        opacity: 1 !important;
    }
    .sp-btn-done {
        background: #e2e8f0;
        color: #475569;
        border: 1px solid #cbd5e1;
        opacity: 1 !important;
    }
    .sp-completed-detail {
        margin-top: 10px;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        padding: 10px;
    }
    .sp-completed-detail strong {
        color: #0f172a;
    }
    .sp-item-list {
        margin: 8px 0 0;
        padding: 0;
        list-style: none;
        display: grid;
        gap: 6px;
    }
    .sp-item-list li {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        border-top: 1px solid #e2e8f0;
        padding-top: 6px;
        font-size: .86rem;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ordersEl = document.getElementById('spOrders');
    const alertEl = document.getElementById('spAlert');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const completingOrders = new Set();

    const fmt = (v) => new Intl.NumberFormat('vi-VN').format(Number(v || 0)) + 'đ';
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));
    const statusClass = (status) => String(status || 'unknown').replace(/[^a-z0-9_-]/gi, '-').toLowerCase();

    function completedDetail(order) {
        const items = Array.isArray(order.items) ? order.items : [];
        const itemHtml = items.length
            ? `<ul class="sp-item-list">${items.map((item) => `<li><span>${esc(item.name)} <span class="m-label">${esc(item.sku)}</span></span><strong>x${Number(item.quantity || 0)}</strong></li>`).join('')}</ul>`
            : '';

        return `<div class="sp-completed-detail">
            <div class="m-row"><strong>Chi tiết đơn hoàn thiện</strong><span class="sp-status is-${statusClass(order.status)}">${esc(order.status || 'delivered')}</span></div>
            <div class="m-row"><span class="m-label">Mã đơn</span><strong>${esc(order.code)}</strong></div>
            <div class="m-row"><span class="m-label">Khách hàng</span><strong>${esc(order.customer)}</strong></div>
            <div class="m-label">${esc(order.phone)} - ${esc(order.address)}</div>
            <div class="m-row" style="margin-top:8px;"><span class="m-label">Đã thu</span><strong>${fmt(order.collected_amount ?? order.amount_due)}</strong></div>
            <div class="m-row"><span class="m-label">Hoàn tất lúc</span><strong>${esc(order.delivered_at || order.updated_at || '')}</strong></div>
            ${itemHtml}
        </div>`;
    }

    function orderCard(order) {
        const canAction = order.status === 'delivering';
        const isDone = ['delivered', 'completed'].includes(order.status);

        return `<div class="m-card m-mobile-order-card sp-order-card ${isDone ? 'is-completed' : ''}" data-order-card="${order.id}">
            <span class="m-mobile-status-badge sp-status is-${statusClass(order.status)}" data-role="status">${esc(order.status)}</span>
            <div class="m-row"><div class="m-value">${esc(order.code)}</div></div>
            <div class="m-row"><span>${esc(order.customer)}</span><span>${esc(order.updated_at || '')}</span></div>
            <div class="m-label">${esc(order.phone)} - ${esc(order.address)}</div>
            <div class="m-row" style="margin-top:8px;"><span class="m-label">Tổng đơn</span><strong>${fmt(order.total)}</strong></div>
            <div class="m-row"><span class="m-label">Cần thu</span><strong>${fmt(order.amount_due)}</strong></div>
            <div class="m-grid" style="margin-top:8px;">
                <button class="m-btn ${isDone ? 'sp-btn-done' : 'm-btn-primary'} js-sp-complete" data-id="${order.id}" ${canAction ? '' : 'disabled'}>${isDone ? 'Đã hoàn thành' : 'Giao thành công'}</button>
                <button class="m-btn m-btn-danger js-sp-failed" data-id="${order.id}" ${canAction ? '' : 'disabled'}>Giao thất bại</button>
            </div>
            <div class="js-sp-complete-detail">${isDone ? completedDetail(order) : ''}</div>
        </div>`;
    }

    async function loadOrders() {
        const res = await fetch('{{ route('mobile.api.shipper.today') }}');
        const payload = await res.json();
        const rows = payload.data || [];

        ordersEl.innerHTML = rows.map(orderCard).join('') || '<div class="m-card"><span class="m-label">Không có đơn giao hôm nay.</span></div>';
    }

    function setCompleteButtonState(button, state) {
        button.classList.remove('m-btn-primary', 'sp-btn-processing', 'sp-btn-done');

        if (state === 'processing') {
            button.classList.add('sp-btn-processing');
            button.textContent = 'Đang thực hiện...';
            button.disabled = true;
            return;
        }

        if (state === 'done') {
            button.classList.add('sp-btn-done');
            button.textContent = 'Đã hoàn thành';
            button.disabled = true;
            return;
        }

        button.classList.add('m-btn-primary');
        button.textContent = 'Giao thành công';
        button.disabled = false;
    }

    async function completeOrder(orderId, button) {
        if (completingOrders.has(orderId)) {
            return;
        }

        const amount = window.prompt('Nhập số tiền đã thu (đ), để trống = 0', '0');
        if (amount === null) {
            return;
        }

        const method = window.prompt('Phương thức thanh toán: cash hoặc transfer', 'cash');
        if (method === null) {
            return;
        }

        completingOrders.add(orderId);
        setCompleteButtonState(button, 'processing');

        let res;
        let payload = {};

        try {
            res = await fetch(`{{ url('/m/api/shipper/orders') }}/${orderId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    collected_amount: Number(amount || 0),
                    payment_method: method,
                }),
            });
            payload = await res.json().catch(() => ({}));
        } catch (error) {
            alertEl.innerHTML = '<div class="m-alert m-alert-danger">Mạng không ổn định, vui lòng thử lại.</div>';
            completingOrders.delete(orderId);
            setCompleteButtonState(button, 'ready');
            return;
        }

        if (!res.ok || payload.ok === false) {
            alertEl.innerHTML = `<div class="m-alert m-alert-danger">${payload.message || 'Không thể cập nhật trạng thái.'}</div>`;
            completingOrders.delete(orderId);
            setCompleteButtonState(button, 'ready');
            return;
        }

        alertEl.innerHTML = `<div class="m-alert m-alert-success">${payload.message || 'Thành công.'}</div>`;
        const card = button.closest('[data-order-card]');
        const failBtn = card?.querySelector('.js-sp-failed');
        const statusEl = card?.querySelector('[data-role="status"]');
        const detailEl = card?.querySelector('.js-sp-complete-detail');

        setCompleteButtonState(button, 'done');
        completingOrders.delete(orderId);

        if (failBtn) {
            failBtn.disabled = true;
        }

        if (statusEl) {
            statusEl.textContent = payload.data?.status || 'delivered';
            statusEl.className = `m-mobile-status-badge sp-status is-${statusClass(payload.data?.status || 'delivered')}`;
        }

        if (card) {
            card.classList.add('is-completed');
        }

        if (detailEl && payload.data) {
            detailEl.innerHTML = completedDetail(payload.data);
        }
    }

    async function failOrder(orderId) {
        const reason = window.prompt('Lý do giao thất bại');
        if (!reason) {
            return;
        }

        const res = await fetch(`{{ url('/m/api/shipper/orders') }}/${orderId}/failed`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ reason }),
        });

        const payload = await res.json().catch(() => ({}));
        if (!res.ok || payload.ok === false) {
            alertEl.innerHTML = `<div class="m-alert m-alert-danger">${payload.message || 'Không thể cập nhật trạng thái.'}</div>`;
            return;
        }

        alertEl.innerHTML = `<div class="m-alert m-alert-success">${payload.message || 'Đã cập nhật giao thất bại.'}</div>`;
        loadOrders();
    }

    ordersEl.addEventListener('click', function (event) {
        const completeBtn = event.target.closest('.js-sp-complete');
        if (completeBtn) {
            completeOrder(completeBtn.getAttribute('data-id'), completeBtn);
            return;
        }

        const failBtn = event.target.closest('.js-sp-failed');
        if (failBtn) {
            failOrder(failBtn.getAttribute('data-id'));
        }
    });

    loadOrders();
});
</script>
@endpush
