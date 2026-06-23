@extends('mobile.layouts.app')

@section('title', 'Mobile Shipper')

@section('content')
@include('mobile.partials.profile-card', ['roleLabel' => auth()->user()?->job_title ?: 'Giao hàng'])

<div id="spAlert"></div>
<div id="spOrders"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ordersEl = document.getElementById('spOrders');
    const alertEl = document.getElementById('spAlert');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const fmt = (v) => new Intl.NumberFormat('vi-VN').format(Number(v || 0)) + 'đ';

    function orderCard(order) {
        const canAction = order.status === 'delivering';

        return `<div class="m-card">
            <div class="m-row"><div class="m-value">${order.code}</div><span class="m-label">${order.status}</span></div>
            <div class="m-row"><span>${order.customer}</span><span>${order.updated_at || ''}</span></div>
            <div class="m-label">${order.phone} - ${order.address}</div>
            <div class="m-row" style="margin-top:8px;"><span class="m-label">Tổng đơn</span><strong>${fmt(order.total)}</strong></div>
            <div class="m-row"><span class="m-label">Cần thu</span><strong>${fmt(order.amount_due)}</strong></div>
            <div class="m-grid" style="margin-top:8px;">
                <button class="m-btn m-btn-primary js-sp-complete" data-id="${order.id}" ${canAction ? '' : 'disabled'}>Giao thành công</button>
                <button class="m-btn m-btn-danger js-sp-failed" data-id="${order.id}" ${canAction ? '' : 'disabled'}>Giao thất bại</button>
            </div>
        </div>`;
    }

    async function loadOrders() {
        const res = await fetch('{{ route('mobile.api.shipper.today') }}');
        const payload = await res.json();
        const rows = payload.data || [];

        ordersEl.innerHTML = rows.map(orderCard).join('') || '<div class="m-card"><span class="m-label">Không có đơn giao hôm nay.</span></div>';
    }

    async function completeOrder(orderId) {
        const amount = window.prompt('Nhập số tiền đã thu (đ), để trống = 0', '0');
        if (amount === null) {
            return;
        }

        const method = window.prompt('Phương thức thanh toán: cash hoặc transfer', 'cash');
        if (method === null) {
            return;
        }

        const res = await fetch(`{{ url('/m/api/shipper/orders') }}/${orderId}/complete`, {
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

        const payload = await res.json().catch(() => ({}));
        if (!res.ok || payload.ok === false) {
            alertEl.innerHTML = `<div class="m-alert m-alert-danger">${payload.message || 'Không thể cập nhật trạng thái.'}</div>`;
            return;
        }

        alertEl.innerHTML = `<div class="m-alert m-alert-success">${payload.message || 'Thành công.'}</div>`;
        loadOrders();
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
            completeOrder(completeBtn.getAttribute('data-id'));
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
