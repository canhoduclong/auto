@extends('mobile.layouts.app')

@section('title', 'Mobile Warehouse')

@section('content')
<div class="m-header">
    <h1 class="m-title">Mobile Warehouse</h1>
    <p class="m-subtitle">Đơn chờ đóng gói, cảnh báo thiếu hàng, thao tác nhanh.</p>
</div>

<div class="m-card">
    <div class="m-grid">
        <input type="date" class="m-input" id="whDate" value="{{ now()->toDateString() }}">
        <select class="m-select" id="whStatus">
            <option value="">Tất cả trạng thái</option>
            <option value="approved">Chờ đóng gói</option>
            <option value="ready_to_pack">Chờ đóng gói</option>
            <option value="packing">Đang đóng gói</option>
            <option value="packed_waiting_pickup">Chờ shipper</option>
        </select>
    </div>
</div>

<div id="whAlert"></div>
<div id="whOrders"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('whDate');
    const statusInput = document.getElementById('whStatus');
    const ordersEl = document.getElementById('whOrders');
    const alertEl = document.getElementById('whAlert');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function renderShortage(shortages) {
        if (!shortages || !shortages.length) {
            return '';
        }

        const rows = shortages.map((s) => `<li>${s.variant_name}: cần ${s.required_qty}, khả dụng ${s.available_qty}</li>`).join('');

        return `<div class="m-alert m-alert-danger">
            <strong>Không đủ tồn kho để đóng hàng</strong>
            <ul style="margin:6px 0 0;padding-left:18px;">${rows}</ul>
            <a href="{{ route('warehouse.stock-in') }}" style="display:inline-block;margin-top:8px;font-weight:700;color:#991b1b;">Bạn cần Nhập kho để thực hiện công việc tiếp</a>
        </div>`;
    }

    function orderCard(order) {
        const canStart = !!order.can_start_packing;
        const isReady = ['approved', 'ready_to_pack'].includes(order.status);

        return `<div class="m-card">
            <div class="m-row"><div class="m-value">${order.code}</div><span class="m-label">${order.status}</span></div>
            <div class="m-row"><span>${order.customer}</span><span>${order.created_at || ''}</span></div>
            <div class="m-label">${order.phone} - ${order.address}</div>
            <div class="m-row" style="margin-top:8px;"><span class="m-label">Sản phẩm</span><strong>${order.items_count}</strong></div>
            ${renderShortage(order.shortages || [])}
            ${isReady ? `<button class="m-btn ${canStart ? 'm-btn-primary' : 'm-btn-warn'} js-wh-start" data-id="${order.id}" ${canStart ? '' : 'disabled'}>${canStart ? 'Đóng hàng' : 'Unable'}</button>` : ''}
        </div>`;
    }

    async function loadOrders() {
        const params = new URLSearchParams({ date: dateInput.value, status: statusInput.value || '' });
        const res = await fetch(`{{ route('mobile.api.warehouse.orders') }}?${params.toString()}`);
        const payload = await res.json();
        const rows = payload.data || [];

        ordersEl.innerHTML = rows.map(orderCard).join('') || '<div class="m-card"><span class="m-label">Không có đơn để xử lý.</span></div>';
    }

    async function startPacking(orderId) {
        alertEl.innerHTML = '';
        const res = await fetch(`{{ url('/m/api/warehouse/orders') }}/${orderId}/start-packing`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await res.json().catch(() => ({}));
        if (!res.ok || payload.ok === false) {
            const msg = payload?.message || 'Không thể chuyển đơn sang đang đóng gói.';
            alertEl.innerHTML = `<div class="m-alert m-alert-danger">${msg}</div>`;
            return;
        }

        alertEl.innerHTML = `<div class="m-alert m-alert-success">${payload.message || 'Đã bắt đầu đóng gói.'}</div>`;
        loadOrders();
    }

    ordersEl.addEventListener('click', function (event) {
        const btn = event.target.closest('.js-wh-start');
        if (!btn) {
            return;
        }

        event.preventDefault();
        if (btn.disabled) {
            return;
        }

        startPacking(btn.getAttribute('data-id'));
    });

    dateInput.addEventListener('change', loadOrders);
    statusInput.addEventListener('change', loadOrders);

    loadOrders();
});
</script>
@endpush
