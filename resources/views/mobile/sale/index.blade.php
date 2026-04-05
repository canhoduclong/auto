@extends('mobile.layouts.app')

@section('title', 'Mobile Sale')

@section('content')
<div class="m-header">
    <h1 class="m-title">Mobile Sale</h1>
    <p class="m-subtitle">Khách hàng, đơn hàng, công nợ và chiết khấu/hoa hồng.</p>
</div>

<div class="m-card">
    <div class="m-grid">
        <div>
            <div class="m-label">Đơn tháng</div>
            <div class="m-value" id="saleMetricOrders">0</div>
        </div>
        <div>
            <div class="m-label">Doanh thu</div>
            <div class="m-value" id="saleMetricRevenue">0đ</div>
        </div>
        <div>
            <div class="m-label">Công nợ</div>
            <div class="m-value" id="saleMetricDebt">0đ</div>
        </div>
        <div>
            <div class="m-label">Rule HH/CK</div>
            <div class="m-value" id="saleMetricCommission">0</div>
        </div>
    </div>
</div>

<div class="m-card">
    <div class="m-grid">
        <a class="m-btn m-btn-primary" href="{{ route('orders.create') }}">+ Tạo đơn nhanh</a>
        <a class="m-btn m-btn-outline" href="{{ route('pages.my_orders') }}">Đơn đã tạo</a>
    </div>
</div>

<div class="m-card">
    <div class="m-row">
        <strong>Khách hàng</strong>
    </div>
    <input class="m-input" id="saleSearchCustomer" placeholder="Tìm tên / số điện thoại">
</div>
<div id="saleCustomerList"></div>

<div class="m-card">
    <div class="m-row">
        <strong>Đơn gần đây</strong>
    </div>
    <select class="m-select" id="saleOrderStatus">
        <option value="">Tất cả trạng thái</option>
        <option value="pending">Chờ duyệt</option>
        <option value="approved">Đã duyệt</option>
        <option value="packing">Đang đóng gói</option>
        <option value="delivering">Đang giao</option>
        <option value="completed">Hoàn thành</option>
    </select>
</div>
<div id="saleOrderList"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerList = document.getElementById('saleCustomerList');
    const orderList = document.getElementById('saleOrderList');
    const customerSearch = document.getElementById('saleSearchCustomer');
    const orderStatus = document.getElementById('saleOrderStatus');

    const formatMoney = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';

    function customerCard(item) {
        return `<div class="m-card">
            <div class="m-row"><div><div class="m-value">${item.name}</div><div class="m-label">${item.phone}</div></div><span class="m-label">${item.status}</span></div>
            <div class="m-label">${item.address}</div>
        </div>`;
    }

    function orderCard(item) {
        return `<div class="m-card">
            <div class="m-row"><div class="m-value">${item.code}</div><span class="m-label">${item.status}</span></div>
            <div class="m-row"><span>${item.customer}</span><span>${item.created_at || ''}</span></div>
            <div class="m-row"><span class="m-label">Tổng</span><strong>${formatMoney(item.total)}</strong></div>
            <div class="m-row"><span class="m-label">Còn nợ</span><strong>${formatMoney(item.amount_due)}</strong></div>
            <a class="m-btn m-btn-outline" href="${item.detail_url}">Xem chi tiết</a>
        </div>`;
    }

    async function loadMetrics() {
        const res = await fetch('{{ route('mobile.api.sale.metrics') }}');
        const payload = await res.json();
        const data = payload.data || {};
        document.getElementById('saleMetricOrders').textContent = data.order_count_month || 0;
        document.getElementById('saleMetricRevenue').textContent = formatMoney(data.revenue_month || 0);
        document.getElementById('saleMetricDebt').textContent = formatMoney(data.debt_month || 0);
        document.getElementById('saleMetricCommission').textContent = data.active_commission_rules || 0;
    }

    async function loadCustomers() {
        const q = encodeURIComponent(customerSearch.value || '');
        const res = await fetch(`{{ route('mobile.api.sale.customers') }}?q=${q}`);
        const payload = await res.json();
        const rows = payload.data || [];
        customerList.innerHTML = rows.map(customerCard).join('') || '<div class="m-card"><span class="m-label">Không có khách hàng phù hợp.</span></div>';
    }

    async function loadOrders() {
        const status = encodeURIComponent(orderStatus.value || '');
        const res = await fetch(`{{ route('mobile.api.sale.orders') }}?status=${status}`);
        const payload = await res.json();
        const rows = payload.data || [];
        orderList.innerHTML = rows.map(orderCard).join('') || '<div class="m-card"><span class="m-label">Chưa có đơn hàng.</span></div>';
    }

    let customerTimer = null;
    customerSearch.addEventListener('input', function () {
        window.clearTimeout(customerTimer);
        customerTimer = window.setTimeout(loadCustomers, 250);
    });

    orderStatus.addEventListener('change', loadOrders);

    loadMetrics();
    loadCustomers();
    loadOrders();
});
</script>
@endpush
