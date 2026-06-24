@extends('mobile.layouts.app')

@section('title', 'Mobile Sale')

@push('styles')
<style>
    .sale-order-card { position: relative; padding-top: 38px; }
    .sale-status-badge {
        position: absolute;
        right: 12px;
        top: 10px;
        border-radius: 999px;
        padding: 5px 9px;
        background: #e0f2fe;
        color: #0369a1;
        font-size: .72rem;
        font-weight: 800;
    }
    .sale-action-menu {
        position: relative;
        display: inline-block;
    }
    .sale-action-menu > summary {
        list-style: none;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background: #f1f5f9;
        color: #0f172a;
        font-size: 1.2rem;
        font-weight: 900;
        cursor: pointer;
    }
    .sale-action-menu > summary::-webkit-details-marker { display: none; }
    .sale-action-pop {
        position: absolute;
        left: 0;
        bottom: calc(100% + 6px);
        width: 150px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .18);
        padding: 6px;
        z-index: 5;
    }
    .sale-action-pop a {
        display: block;
        padding: 9px 10px;
        border-radius: 8px;
        color: #0f172a;
        text-decoration: none;
        font-size: .84rem;
        font-weight: 700;
    }
    .sale-line-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 74px 34px;
        gap: 8px;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e2e8f0;
    }
</style>
@endpush

@section('content')
@include('mobile.partials.profile-card', ['roleLabel' => auth()->user()?->job_title ?: 'Kinh doanh'])

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
        <button class="m-btn m-btn-primary" type="button" id="toggleCreateOrder">+ Tạo đơn nhanh</button>
        <a class="m-btn m-btn-outline" href="{{ route('pages.my_orders') }}">Đơn đã tạo</a>
    </div>
</div>

<div class="m-card" id="saleCreateOrderBox" style="display:none">
    <div class="m-row"><strong>Tạo đơn mới</strong><span class="m-label" id="createOrderTotal">0đ</span></div>
    <label class="m-label">Khách hàng</label>
    <select class="m-select" id="createCustomer"></select>
    <div style="height:8px"></div>
    <label class="m-label">Ngày giao</label>
    <input class="m-input" id="createDeliveryDate" type="date" value="{{ now()->addDay()->toDateString() }}">
    <div style="height:8px"></div>
    <label class="m-label">Ghi chú</label>
    <input class="m-input" id="createNote" placeholder="Ghi chú đơn hàng">
    <div style="height:10px"></div>
    <div class="m-row"><strong>Sản phẩm</strong></div>
    <input class="m-input" id="productSearch" placeholder="Tìm sản phẩm / SKU">
    <div style="height:8px"></div>
    <select class="m-select" id="productSelect"></select>
    <div style="height:8px"></div>
    <div class="m-row">
        <input class="m-input" id="productQty" type="number" min="1" value="1" style="max-width:110px">
        <button class="m-btn m-btn-outline" type="button" id="addProductLine">Thêm</button>
    </div>
    <div id="createOrderItems"></div>
    <div style="height:10px"></div>
    <button class="m-btn m-btn-primary" type="button" id="submitCreateOrder">Tạo đơn</button>
    <div id="createOrderMessage" style="margin-top:8px"></div>
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
    const createBox = document.getElementById('saleCreateOrderBox');
    const createCustomer = document.getElementById('createCustomer');
    const productSearch = document.getElementById('productSearch');
    const productSelect = document.getElementById('productSelect');
    const productQty = document.getElementById('productQty');
    const createOrderItems = document.getElementById('createOrderItems');
    const createOrderTotal = document.getElementById('createOrderTotal');
    const createOrderMessage = document.getElementById('createOrderMessage');
    let productRows = [];
    let selectedItems = [];

    const formatMoney = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#039;'}[char]));

    function customerCard(item) {
        return `<div class="m-card">
            <div class="m-row"><div><div class="m-value">${item.name}</div><div class="m-label">${item.phone}</div></div><span class="m-label">${item.status}</span></div>
            <div class="m-label">${item.address}</div>
        </div>`;
    }

    function orderCard(item) {
        return `<div class="m-card sale-order-card">
            <span class="sale-status-badge">${escapeHtml(item.status)}</span>
            <div class="m-row"><div class="m-value">${escapeHtml(item.code)}</div><span>${escapeHtml(item.created_at || '')}</span></div>
            <div class="m-row"><span>${escapeHtml(item.customer)}</span><span>${escapeHtml(item.phone || '')}</span></div>
            <div class="m-row"><span class="m-label">Tổng</span><strong>${formatMoney(item.total)}</strong></div>
            <div class="m-row"><span class="m-label">Còn nợ</span><strong>${formatMoney(item.amount_due)}</strong></div>
            <div class="m-row" style="margin-bottom:0">
                <details class="sale-action-menu">
                    <summary>⋯</summary>
                    <div class="sale-action-pop">
                        <a href="${item.detail_url}">Xem chi tiết</a>
                        <a href="{{ route('pages.my_orders') }}">Danh sách đơn</a>
                    </div>
                </details>
                <span class="m-label">Chức năng</span>
            </div>
        </div>`;
    }

    function renderCreateCustomers(rows) {
        createCustomer.innerHTML = rows.map((item) => `<option value="${item.id}">${escapeHtml(item.name)} - ${escapeHtml(item.phone || '')}</option>`).join('');
    }

    function renderProducts(rows) {
        productRows = rows;
        productSelect.innerHTML = rows.map((item, index) => `<option value="${index}">${escapeHtml(item.name)} - ${formatMoney(item.price)} - tồn ${item.available_stock}</option>`).join('');
    }

    function renderSelectedItems() {
        const total = selectedItems.reduce((sum, item) => sum + item.line_total, 0);
        createOrderTotal.textContent = formatMoney(total);
        createOrderItems.innerHTML = selectedItems.map((item, index) => `<div class="sale-line-item">
            <div><strong>${escapeHtml(item.name)}</strong><div class="m-label">${formatMoney(item.price)} x ${item.quantity}</div></div>
            <strong>${formatMoney(item.line_total)}</strong>
            <button class="m-btn m-btn-outline" type="button" data-remove-item="${index}" style="padding:7px">×</button>
        </div>`).join('') || '<div class="m-label">Chưa chọn sản phẩm.</div>';
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
        renderCreateCustomers(rows);
    }

    async function loadProducts() {
        const q = encodeURIComponent(productSearch.value || '');
        const res = await fetch(`{{ route('mobile.api.sale.products') }}?q=${q}`);
        const payload = await res.json();
        renderProducts(payload.data || []);
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
    document.getElementById('toggleCreateOrder').addEventListener('click', function () {
        createBox.style.display = createBox.style.display === 'none' ? 'block' : 'none';
    });
    let productTimer = null;
    productSearch.addEventListener('input', function () {
        window.clearTimeout(productTimer);
        productTimer = window.setTimeout(loadProducts, 250);
    });
    document.getElementById('addProductLine').addEventListener('click', function () {
        const product = productRows[Number(productSelect.value || 0)];
        if (!product) return;
        const quantity = Math.max(1, Number(productQty.value || 1));
        const factor = product.is_priced_by_kg ? Math.max(0.01, Number(product.kg || 1)) : 1;
        selectedItems.push({
            variant_id: product.id,
            name: product.name,
            price: product.price,
            quantity,
            line_total: product.price * quantity * factor
        });
        renderSelectedItems();
    });
    createOrderItems.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-item]');
        if (!button) return;
        selectedItems.splice(Number(button.dataset.removeItem), 1);
        renderSelectedItems();
    });
    document.getElementById('submitCreateOrder').addEventListener('click', async function () {
        createOrderMessage.innerHTML = '';
        if (!createCustomer.value || selectedItems.length === 0) {
            createOrderMessage.innerHTML = '<div class="m-alert m-alert-danger">Vui lòng chọn khách hàng và sản phẩm.</div>';
            return;
        }
        const res = await fetch('{{ route('mobile.api.sale.orders.store') }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'},
            body: JSON.stringify({
                customer_id: createCustomer.value,
                delivery_date: document.getElementById('createDeliveryDate').value,
                note: document.getElementById('createNote').value,
                items: selectedItems.map((item) => ({variant_id: item.variant_id, quantity: item.quantity}))
            })
        });
        const payload = await res.json();
        if (!res.ok) {
            createOrderMessage.innerHTML = `<div class="m-alert m-alert-danger">${escapeHtml(payload.message || 'Không tạo được đơn.')}</div>`;
            return;
        }
        selectedItems = [];
        renderSelectedItems();
        createOrderMessage.innerHTML = `<div class="m-alert m-alert-success">${escapeHtml(payload.message || 'Đã tạo đơn.')}</div>`;
        loadMetrics();
        loadOrders();
    });

    loadMetrics();
    loadCustomers();
    loadOrders();
    loadProducts();
    renderSelectedItems();
});
</script>
@endpush
