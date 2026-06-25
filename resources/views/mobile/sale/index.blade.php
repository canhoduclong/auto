@extends('mobile.layouts.app')

@section('title', 'Mobile Sale')

@push('styles')
<style>
    .sale-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 10px;
    }
    .sale-tab-btn {
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        background: #fff;
        color: #334155;
        padding: 11px 12px;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }
    .sale-tab-btn.active {
        background: #0f766e;
        border-color: #0f766e;
        color: #fff;
    }
    .sale-tab-panel[hidden] { display: none; }
    .sale-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .sale-section-head .m-btn {
        width: auto;
        min-width: 142px;
        padding: 10px 12px;
    }
    .sale-line-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 74px 34px;
        gap: 8px;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .sale-order-home-card {
        padding: 14px 12px 62px;
        padding-bottom: 62px;
        border-radius: 18px;
    }
    .sale-order-home-card .m-mobile-status-badge {
        top: 14px;
        right: 14px;
        max-width: 150px;
        border-radius: 3px;
        padding: 8px 10px;
        background: #dcfce7;
        color: #22c55e;
        font-size: .95rem;
        line-height: 1;
        letter-spacing: 0;
    }
    .sale-order-header {
        display: grid;
        grid-template-columns: 54px minmax(0, 1fr);
        gap: 10px;
        align-items: start;
        padding-right: 142px;
        margin-bottom: 14px;
    }
    .sale-order-no {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: #64748b;
        color: #fff;
        font-size: 1.28rem;
        font-weight: 900;
    }
    .sale-order-customer {
        min-width: 0;
        font-size: 1.18rem;
        line-height: 1.15;
        font-weight: 900;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .sale-order-date {
        margin-top: 6px;
        color: #64748b;
        font-size: 1rem;
    }
    .sale-order-eye {
        position: absolute;
        top: 74px;
        right: 80px;
        width: 24px;
        height: 16px;
        border: 2px solid currentColor;
        border-radius: 50%;
        color: #0f969a;
        text-decoration: none;
    }
    .sale-order-eye::after {
        content: "";
        position: absolute;
        left: 50%;
        top: 50%;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
        transform: translate(-50%, -50%);
    }
    .sale-order-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .sale-order-table th {
        border: 0;
        border-bottom: 1px solid #cbd5e1;
        color: #64748b;
        font-size: .78rem;
        font-weight: 900;
        text-align: left;
        padding: 7px 4px;
    }
    .sale-order-table td {
        border: 0;
        padding: 10px 4px 4px;
        font-size: .94rem;
        font-weight: 800;
        vertical-align: top;
    }
    .sale-order-table .num {
        text-align: right;
        white-space: nowrap;
    }
    .sale-order-product {
        overflow-wrap: anywhere;
    }
    .sale-order-subline {
        margin-top: 12px;
        color: #475569;
        font-size: 1rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }
    .sale-order-total {
        margin-top: 20px;
        text-align: right;
        font-size: 1.62rem;
        line-height: 1;
        font-weight: 950;
    }
    .sale-create-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    @media (max-width: 390px) {
        .sale-order-header {
            grid-template-columns: 44px minmax(0, 1fr);
            padding-right: 118px;
        }
        .sale-order-no {
            width: 42px;
            height: 42px;
            font-size: 1.05rem;
        }
        .sale-order-total { font-size: 1.28rem; }
        .sale-order-table th,
        .sale-order-table td { font-size: .78rem; }
        .sale-order-home-card .m-mobile-status-badge { font-size: .82rem; }
        .sale-order-eye { right: 62px; }
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

<div class="sale-tabs" role="tablist" aria-label="Sale mobile">
    <button class="sale-tab-btn active" type="button" data-sale-tab="customers">Khách hàng</button>
    <button class="sale-tab-btn" type="button" data-sale-tab="orders">Đơn hàng</button>
</div>

<section class="sale-tab-panel" data-sale-panel="customers">
    <div class="m-card">
        <div class="m-row">
            <strong>Khách hàng</strong>
        </div>
        <input class="m-input" id="saleSearchCustomer" placeholder="Tìm tên / số điện thoại">
    </div>
    <div id="saleCustomerList"></div>
</section>

<section class="sale-tab-panel" data-sale-panel="orders" hidden>
    <div class="m-card">
        <div class="sale-section-head">
            <strong>Đơn hàng</strong>
            <button class="m-btn m-btn-primary" type="button" id="toggleCreateOrder">+ Tạo mới đơn hàng</button>
        </div>
        <div style="height:10px"></div>
        <select class="m-select" id="saleOrderStatus">
            <option value="">Tất cả trạng thái</option>
            <option value="order_placed">Đã đặt</option>
            <option value="pending">Chờ duyệt</option>
            <option value="approved">Đã duyệt</option>
            <option value="packing">Đang đóng gói</option>
            <option value="delivering">Đang giao</option>
            <option value="completed">Hoàn thành</option>
        </select>
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
        <div class="m-row">
            <select class="m-select" id="productSort">
                <option value="stock">Còn hàng nhiều trước</option>
                <option value="name">Tên A-Z</option>
                <option value="sku">SKU</option>
                <option value="newest">Mới nhất</option>
            </select>
            <label class="m-label" style="display:flex;align-items:center;gap:6px;white-space:nowrap">
                <input type="checkbox" id="productInStock" checked>
                Còn hàng
            </label>
        </div>
        <div style="height:8px"></div>
        <select class="m-select" id="productSelect"></select>
        <div style="height:8px"></div>
        <div class="m-row">
            <input class="m-input" id="productQty" type="number" min="1" value="1" style="max-width:110px">
            <button class="m-btn m-btn-outline" type="button" id="addProductLine">Thêm</button>
        </div>
        <div id="createOrderItems"></div>
        <div style="height:10px"></div>
        <div class="sale-create-actions">
            <button class="m-btn m-btn-outline" type="button" id="cancelCreateOrder">Đóng</button>
            <button class="m-btn m-btn-primary" type="button" id="submitCreateOrder">Tạo đơn</button>
        </div>
        <div id="createOrderMessage" style="margin-top:8px"></div>
    </div>

    <div id="saleOrderList"></div>
</section>
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
    const productSort = document.getElementById('productSort');
    const productInStock = document.getElementById('productInStock');
    const productSelect = document.getElementById('productSelect');
    const productQty = document.getElementById('productQty');
    const createOrderItems = document.getElementById('createOrderItems');
    const createOrderTotal = document.getElementById('createOrderTotal');
    const createOrderMessage = document.getElementById('createOrderMessage');
    const tabButtons = document.querySelectorAll('[data-sale-tab]');
    const tabPanels = document.querySelectorAll('[data-sale-panel]');
    let productRows = [];
    let selectedItems = [];

    const formatMoney = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
    const formatCardMoney = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' đ';
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#039;'}[char]));
    const formatNumber = (value, digits = 2) => {
        const number = Number(value || 0);
        return new Intl.NumberFormat('vi-VN', {
            minimumFractionDigits: 0,
            maximumFractionDigits: digits
        }).format(number);
    };
    const formatFixed = (value, digits = 2) => {
        const number = Number(value || 0);
        return new Intl.NumberFormat('vi-VN', {
            minimumFractionDigits: digits,
            maximumFractionDigits: digits
        }).format(number);
    };

    function switchTab(tab) {
        tabButtons.forEach((button) => button.classList.toggle('active', button.dataset.saleTab === tab));
        tabPanels.forEach((panel) => panel.hidden = panel.dataset.salePanel !== tab);
        if (tab === 'orders') {
            loadOrders();
        }
    }

    function customerCard(item) {
        return `<div class="m-card">
            <div class="m-row"><div><div class="m-value">${item.name}</div><div class="m-label">${item.phone}</div></div><span class="m-label">${item.status}</span></div>
            <div class="m-label">${item.address}</div>
        </div>`;
    }

    function orderCard(item) {
        const items = Array.isArray(item.items) ? item.items : [];
        const firstItem = items[0] || {};
        const itemRows = items.length
            ? items.slice(0, 3).map((line) => `<tr>
                <td class="sale-order-product">${escapeHtml(line.display_name || line.name || '-')}</td>
                <td class="num">${formatNumber(line.quantity, 2)}</td>
                <td class="num">${formatFixed(line.size, 2)}</td>
                <td class="num">${escapeHtml(line.total_label || '')}</td>
                <td class="num">${formatCardMoney(line.unit_price || 0)}</td>
            </tr>`).join('')
            : `<tr><td colspan="5" class="sale-order-product">Chưa có sản phẩm</td></tr>`;
        const moreText = items.length > 3 ? `<div class="sale-order-subline">+${items.length - 3} sản phẩm khác</div>` : '';
        const firstLineText = firstItem.display_name
            ? `<div class="sale-order-subline">${escapeHtml(firstItem.display_name)}${firstItem.size ? ' - ' + formatFixed(firstItem.size, 2) : ''}</div>`
            : '';

        return `<div class="m-card m-mobile-order-card sale-order-home-card">
            <span class="m-mobile-status-badge">${escapeHtml(item.status)}</span>
            <a class="sale-order-eye" href="${item.detail_url}" aria-label="Xem chi tiết"></a>
            <div class="sale-order-header">
                <div class="sale-order-no">${escapeHtml(item.number || item.id || '')}</div>
                <div>
                    <div class="sale-order-customer">${escapeHtml(item.customer)}</div>
                    <div class="sale-order-date">${escapeHtml(item.created_at || '')}</div>
                </div>
            </div>
            <table class="sale-order-table">
                <thead>
                    <tr>
                        <th>SẢN PHẨM</th>
                        <th class="num" style="width:42px">SL</th>
                        <th class="num" style="width:56px">SIZE</th>
                        <th class="num" style="width:76px">TỔNG</th>
                        <th class="num" style="width:92px">ĐƠN GIÁ</th>
                    </tr>
                </thead>
                <tbody>${itemRows}</tbody>
            </table>
            ${firstLineText}
            ${moreText}
            <div class="sale-order-total">${formatCardMoney(item.total)}</div>
            <div class="m-card-action-bottom">
                <details class="m-card-menu">
                    <summary>⋯</summary>
                    <div class="m-card-menu-pop">
                        <a href="${item.detail_url}">Xem chi tiết</a>
                        <a href="${item.edit_url}">Sửa đơn</a>
                        <a href="${item.copy_url}">Sao chép đơn</a>
                        <a href="{{ route('pages.my_orders') }}">Tất cả đơn</a>
                    </div>
                </details>
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
        const sortBy = encodeURIComponent(productSort.value || 'stock');
        const inStock = productInStock.checked ? 1 : 0;
        const res = await fetch(`{{ route('mobile.api.sale.products') }}?q=${q}&sort_by=${sortBy}&in_stock=${inStock}`);
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

    tabButtons.forEach((button) => button.addEventListener('click', () => switchTab(button.dataset.saleTab)));

    orderStatus.addEventListener('change', loadOrders);
    document.getElementById('toggleCreateOrder').addEventListener('click', function () {
        createBox.style.display = createBox.style.display === 'none' ? 'block' : 'none';
        createOrderMessage.innerHTML = '';
    });
    document.getElementById('cancelCreateOrder').addEventListener('click', function () {
        createBox.style.display = 'none';
        createOrderMessage.innerHTML = '';
    });
    let productTimer = null;
    productSearch.addEventListener('input', function () {
        window.clearTimeout(productTimer);
        productTimer = window.setTimeout(loadProducts, 250);
    });
    productSort.addEventListener('change', loadProducts);
    productInStock.addEventListener('change', loadProducts);
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
            kg: product.kg,
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
        const submitButton = this;
        createOrderMessage.innerHTML = '';
        if (!createCustomer.value || selectedItems.length === 0) {
            createOrderMessage.innerHTML = '<div class="m-alert m-alert-danger">Vui lòng chọn khách hàng và sản phẩm.</div>';
            return;
        }
        submitButton.disabled = true;
        submitButton.textContent = 'Đang tạo...';
        let res;
        let payload = {};
        try {
            res = await fetch('{{ route('mobile.api.sale.orders.store') }}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'},
                body: JSON.stringify({
                    customer_id: createCustomer.value,
                    delivery_date: document.getElementById('createDeliveryDate').value,
                    note: document.getElementById('createNote').value,
                    items: selectedItems.map((item) => ({variant_id: item.variant_id, quantity: item.quantity}))
                })
            });
            payload = await res.json().catch(() => ({}));
        } catch (error) {
            createOrderMessage.innerHTML = '<div class="m-alert m-alert-danger">Mạng không ổn định, vui lòng thử lại.</div>';
            submitButton.disabled = false;
            submitButton.textContent = 'Tạo đơn';
            return;
        }
        submitButton.disabled = false;
        submitButton.textContent = 'Tạo đơn';
        if (!res.ok) {
            const errors = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
            createOrderMessage.innerHTML = `<div class="m-alert m-alert-danger">${escapeHtml(errors || payload.message || 'Không tạo được đơn.')}</div>`;
            return;
        }
        selectedItems = [];
        renderSelectedItems();
        document.getElementById('createNote').value = '';
        productQty.value = 1;
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
