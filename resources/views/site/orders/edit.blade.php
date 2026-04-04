@extends('layouts.site')

@php
    $orderCode = $order->code ?: ('#' . $order->id);
@endphp

@push('styles')
<style>
    .checkout-page {
        background:
            radial-gradient(circle at top left, rgba(20, 184, 166, 0.1), transparent 26%),
            radial-gradient(circle at top right, rgba(245, 158, 11, 0.08), transparent 26%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        padding: 34px 0 48px;
    }
    .checkout-shell {
        max-width: 1240px;
    }
    .checkout-hero {
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(15, 118, 110, 0.86));
        color: #f8fafc;
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.14);
        padding: 22px 24px;
        margin-bottom: 18px;
    }
    .checkout-panel {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 20px;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
    }
    .checkout-panel-body {
        padding: 20px;
    }
    .checkout-table {
        margin-bottom: 0;
        vertical-align: middle;
    }
    .checkout-table thead th {
        background: #f8fafc;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #334155;
        white-space: nowrap;
    }
    .checkout-product {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 240px;
    }
    .checkout-product img {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid rgba(148, 163, 184, 0.25);
        background: #e2e8f0;
    }
    .checkout-product-meta {
        line-height: 1.2;
    }
    .checkout-product-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #0f172a;
    }
    .checkout-product-sub {
        font-size: 0.74rem;
        color: #64748b;
    }
    .checkout-table .form-control.form-control-sm {
        min-width: 50px;
    }
    .min50{
        min-width: 50px;
    }
    .min80{
        min-width: 80px;
    }

    .line-weight {
        font-weight: 700;
        color: #0f766e;
        white-space: nowrap;
    }
    .checkout-summary {
        position: sticky;
        top: 20px;
    }
    .checkout-summary-grid {
        display: grid;
        gap: 10px;
        margin-bottom: 14px;
    }
    .checkout-kpi {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        background: #f8fafc;
    }
    .checkout-kpi-label {
        display: block;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .checkout-kpi-value {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }
    .checkout-kpi-value.discount {
        color: #b45309;
    }
    .checkout-kpi-value.total {
        color: #0f766e;
    }
    .checkout-summary-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
        color: #334155;
        font-size: 0.9rem;
    }
    .checkout-summary-total {
        margin-top: 4px;
        padding-top: 10px;
        border-top: 1px dashed rgba(148, 163, 184, 0.45);
    }
    .checkout-breakdown {
        margin-top: 10px;
        padding: 12px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 12px;
        background: #f8fafc;
    }
    .checkout-breakdown-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 0.9rem;
        color: #334155;
        margin-bottom: 8px;
    }
    .checkout-breakdown-item.total {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed rgba(148, 163, 184, 0.45);
    }
</style>
@endpush

@section('content')
<section class="checkout-page">
    <div class="container checkout-shell">
        <div class="checkout-hero">
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;opacity:.8;">My Orders</div>
            <h1 class="h4 mb-1 fw-bold">Sửa đơn {{ $orderCode }}</h1>
            <p class="mb-0" style="opacity:.85;">Giao diện chỉnh sửa theo kiểu checkout, hỗ trợ thêm biến thể sản phẩm trực tiếp vào đơn.</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('site.orders.update', $order) }}" method="POST" id="my-order-edit-form">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="checkout-panel mb-3">
                        <div class="checkout-panel-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0">Thông tin khách hàng</h2>
                                <span class="text-muted small">Trình bày tương tự khu vực checkout.</span>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="customer_id" class="form-label fw-bold">Khách hàng</label>
                                    <select name="customer_id" id="customer_id" class="form-select" required>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ (int) old('customer_id', $order->customer_id) === (int) $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="delivery_time" class="form-label fw-bold">Giờ giao hàng</label>
                                    <input type="text" name="delivery_time" id="delivery_time" class="form-control"
                                           value="{{ old('delivery_time', $order->delivery_time) }}" placeholder="Ví dụ: 9h-11h hoặc sau 17h">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="recipient_name" class="form-label fw-bold">Họ tên người nhận</label>
                                    <input type="text" name="recipient_name" id="recipient_name" class="form-control" required
                                           value="{{ old('recipient_name', $order->recipient_name ?? $order->customer?->name ?? '') }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="recipient_phone" class="form-label fw-bold">Số điện thoại</label>
                                    <input type="text" name="recipient_phone" id="recipient_phone" class="form-control" required
                                           value="{{ old('recipient_phone', $order->recipient_phone ?? $order->customer?->phone ?? '') }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="recipient_email" class="form-label fw-bold">Email</label>
                                    <input type="email" name="recipient_email" id="recipient_email" class="form-control"
                                           value="{{ old('recipient_email', $order->recipient_email ?? $order->customer?->email ?? '') }}">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="recipient_address" class="form-label fw-bold">Địa chỉ nhận hàng</label>
                                    <textarea name="recipient_address" id="recipient_address" rows="3" class="form-control" required>{{ old('recipient_address', $order->recipient_address ?? $order->customer?->address ?? '') }}</textarea>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="note" class="form-label fw-bold">Ghi chú</label>
                                    <textarea name="note" id="note" rows="3" class="form-control" placeholder="Ghi chú cho đơn hàng">{{ old('note', $order->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-panel mb-3">
                        <div class="checkout-panel-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0">Sản phẩm trong đơn</h2>
                                <span class="text-muted small">Thêm hoặc bỏ sản phẩm trước khi lưu.</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table checkout-table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Size</th>
                                            <th>Đơn giá</th>
                                            <th>CK giá</th>
                                            <th>SL</th>
                                            <th>ĐVT</th>
                                            <th>Khối lượng</th>
                                            <th>Thành tiền</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-items-container">
                                        @foreach($order->items as $index => $item)
                                            @php
                                                $variant = $item->variant;
                                                $imageUrl = 'https://via.placeholder.com/48';
                                                if ($variant?->media) {
                                                    $imageUrl = asset('storage/' . $variant->media->file_path);
                                                } elseif ($variant?->product?->avatar?->media) {
                                                    $imageUrl = asset('storage/' . $variant->product->avatar->media->file_path);
                                                }
                                                $unitPrice = (float) ($item->price ?? 0);
                                                $qty = (int) ($item->quantity ?? 1);
                                                $unitLabel = $variant?->product?->unit_label ?? 'Cái';
                                                $weightUnitLabel = in_array((string) ($variant?->product?->unit ?? 'cai'), ['con', 'cai'], true) ? 'Kg' : $unitLabel;
                                                $unitWeight = (float) old('item_weight.' . ($variant?->id), $item->unit_weight ?? 0);
                                                if ($unitWeight <= 0) {
                                                    $sizeRaw = strtolower(str_replace(',', '.', trim((string) ($variant?->size ?? ''))));
                                                    preg_match('/([0-9]*\.?[0-9]+)/', $sizeRaw, $sizeMatches);
                                                    $unitWeight = (float) ($sizeMatches[1] ?? 0);
                                                    if (str_contains($sizeRaw, 'g') && !str_contains($sizeRaw, 'kg')) {
                                                        $unitWeight = $unitWeight / 1000;
                                                    }
                                                    $unitWeight = round(max(0, $unitWeight), 3);
                                                }
                                                $unitDiscount = (float) old('item_discount.' . ($variant?->id), $item->unit_discount ?? 0);
                                                $unitDiscount = max(0, min($unitDiscount, $unitPrice));
                                                $lineTotal = (float) ($item->total ?? (($unitPrice - $unitDiscount) * $qty));
                                            @endphp
                                            <tr class="cart-item-row" data-variant-id="{{ $variant?->id }}">
                                                <td>
                                                    <div class="checkout-product">
                                                        <img src="{{ $imageUrl }}" alt="{{ $variant?->product?->name ?? 'Product' }}">
                                                        <div class="checkout-product-meta">
                                                            <div class="checkout-product-title">{{ $variant?->product?->name ?? '--' }}</div>
                                                            <div class="checkout-product-sub">{{ $variant?->sku ?? '--' }}</div>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="items[{{ $index }}][variant_id]" value="{{ $variant?->id }}">
                                                </td>
                                                <td>{{ $variant?->size ?? '--' }}</td>
                                                <td class="price" data-price="{{ $unitPrice }}">{{ number_format($unitPrice, 0, ',', '.') }}đ</td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        class="form-control form-control-sm discount-input"
                                                        name="item_discount[{{ $variant?->id }}]"
                                                        min="0"
                                                        step="1000"
                                                        max="{{ $unitPrice }}"
                                                        value="{{ number_format($unitDiscount, 0, '.', '') }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm quantity-input" min="1" value="{{ $qty }}" required>
                                                </td>
                                                <td><span class="text-muted small">{{ $unitLabel }}</span></td>
                                                <td>
                                                    <span class="line-weight" data-unit-weight="{{ number_format((float) $unitWeight, 3, '.', '') }}" data-weight-unit="{{ $weightUnitLabel }}">
                                                        {{ number_format((float) ($unitWeight * $qty), 3, ',', '.') }} {{ $weightUnitLabel }}
                                                    </span>
                                                </td>
                                                <td class="row-total">{{ number_format($lineTotal, 0, ',', '.') }}đ</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-cart-item">&times;</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-panel">
                        <div class="checkout-panel-body">
                            <h2 class="h6 fw-bold mb-3">Thêm biến thể sản phẩm</h2>
                            <div class="input-group">
                                <input type="text" id="variant-search" class="form-control" placeholder="Nhập SKU hoặc tên sản phẩm">
                                <button class="btn btn-outline-secondary" type="button" id="variant-search-button">Tìm</button>
                            </div>
                            <div id="variant-search-results" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="checkout-panel checkout-summary">
                        <div class="checkout-panel-body">
                            <h2 class="h6 fw-bold mb-3">Tóm tắt đơn hàng</h2>

                            <div class="checkout-summary-grid border-top pt-3 mt-3">
                                <div class="checkout-kpi">
                                    <span class="checkout-kpi-label">Tạm tính</span>
                                    <span class="checkout-kpi-value" id="summarySubtotal">0đ</span>
                                </div>
                                <div class="checkout-kpi">
                                    <span class="checkout-kpi-label">Giảm sản phẩm</span>
                                    <span class="checkout-kpi-value discount" id="summaryItemDiscount">0đ</span>
                                </div>
                                <div class="checkout-kpi">
                                    <span class="checkout-kpi-label">CK tổng đơn</span>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1000"
                                        class="form-control form-control-sm"
                                        id="orderDiscountInput"
                                        name="order_discount"
                                        value="{{ old('order_discount', $order->order_discount ?? 0) }}">
                                </div>
                                <div class="checkout-kpi">
                                    <span class="checkout-kpi-label">Tổng giảm</span>
                                    <span class="checkout-kpi-value discount" id="summaryDiscount">0đ</span>
                                </div>
                                <div class="checkout-kpi">
                                    <span class="checkout-kpi-label">Thanh toán</span>
                                    <span class="checkout-kpi-value total" id="summaryTotal">0đ</span>
                                </div>
                            </div>

                            <div class="checkout-summary-line">
                                <span>Số dòng sản phẩm</span>
                                <strong id="summaryLineCount">0</strong>
                            </div>
                            <div class="checkout-summary-line checkout-summary-total">
                                <strong>Tổng cộng</strong>
                                <strong id="summaryTotalFooter">0đ</strong>
                            </div>

                            <div class="checkout-breakdown">
                                <div class="checkout-breakdown-item">
                                    <span>Tiền hàng</span>
                                    <strong id="breakdownGoods">0đ</strong>
                                </div>
                                <div class="checkout-breakdown-item">
                                    <span>Tiền giảm</span>
                                    <strong id="breakdownItemDiscount">0đ</strong>
                                </div>
                                <div class="checkout-breakdown-item">
                                    <span>Giảm thêm (discount ngoài)</span>
                                    <strong id="breakdownExtraDiscount">0đ</strong>
                                </div>
                                <div class="checkout-breakdown-item total">
                                    <strong>Tổng tiền cuối cùng</strong>
                                    <strong id="breakdownFinalTotal">0đ</strong>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-secondary w-50">Quay lại</a>
                                <button type="submit" class="btn btn-primary w-50">Lưu thay đổi</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cartContainer = document.getElementById('cart-items-container');
    const variantSearchInput = document.getElementById('variant-search');
    const variantSearchButton = document.getElementById('variant-search-button');
    const variantSearchResults = document.getElementById('variant-search-results');
    const subtotalEl = document.getElementById('summarySubtotal');
    const itemDiscountEl = document.getElementById('summaryItemDiscount');
    const discountEl = document.getElementById('summaryDiscount');
    const totalEl = document.getElementById('summaryTotal');
    const totalFooterEl = document.getElementById('summaryTotalFooter');
    const lineCountEl = document.getElementById('summaryLineCount');
    const breakdownGoodsEl = document.getElementById('breakdownGoods');
    const breakdownItemDiscountEl = document.getElementById('breakdownItemDiscount');
    const breakdownExtraDiscountEl = document.getElementById('breakdownExtraDiscount');
    const breakdownFinalTotalEl = document.getElementById('breakdownFinalTotal');
    const orderDiscountInput = document.getElementById('orderDiscountInput');
    const form = document.getElementById('my-order-edit-form');
    let itemIndex = cartContainer.querySelectorAll('.cart-item-row').length;
    let searchTimeout = null;
    let currentVariantSearchPage = 1;
    let currentVariantSearchPerPage = 5;

    function formatNumber(num) {
        return new Intl.NumberFormat('vi-VN').format(num);
    }

    function getCartVariantIds() {
        return Array.from(cartContainer.querySelectorAll('.cart-item-row'))
            .map((row) => row.getAttribute('data-variant-id'))
            .filter(Boolean);
    }

    function updateNameIndexes() {
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach((row, idx) => {
            const variantInput = row.querySelector('input[type="hidden"][name*="[variant_id]"]');
            const qtyInput = row.querySelector('.quantity-input');
            if (variantInput) {
                variantInput.name = `items[${idx}][variant_id]`;
            }
            if (qtyInput) {
                qtyInput.name = `items[${idx}][quantity]`;
            }
        });
    }

    function updateCartTotal() {
        let subtotal = 0;
        let itemDiscount = 0;
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach((row) => {
            const priceEl = row.querySelector('.price');
            const qtyInput = row.querySelector('.quantity-input');
            const discountInput = row.querySelector('.discount-input');
            const rowTotalEl = row.querySelector('.row-total');
            const price = parseFloat(priceEl?.getAttribute('data-price') || '0');
            const quantity = parseInt(qtyInput?.value || '0', 10);
            let unitDiscount = parseFloat(discountInput?.value || '0');
            unitDiscount = Math.max(0, Math.min(unitDiscount, price));

            const lineWeightEl = row.querySelector('.line-weight');
            if (lineWeightEl) {
                const unitWeight = parseFloat(lineWeightEl.getAttribute('data-unit-weight') || '0');
                const weightUnit = lineWeightEl.getAttribute('data-weight-unit') || 'Kg';
                const lineWeight = Math.max(0, unitWeight * Math.max(quantity, 0));
                lineWeightEl.textContent = `${lineWeight.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${weightUnit}`;
            }

            if (discountInput) {
                discountInput.max = String(price);
                discountInput.value = String(Math.round(unitDiscount));
            }

            const lineSubtotal = price * quantity;
            const lineDiscount = unitDiscount * quantity;
            const lineTotal = Math.max(lineSubtotal - lineDiscount, 0);

            if (rowTotalEl) {
                rowTotalEl.textContent = `${formatNumber(lineTotal)}đ`;
            }
            subtotal += lineSubtotal;
            itemDiscount += lineDiscount;
        });

        const subtotalAfterItemDiscount = Math.max(subtotal - itemDiscount, 0);
        let orderDiscount = parseFloat(orderDiscountInput?.value || '0');
        orderDiscount = Math.max(0, Math.min(orderDiscount, subtotalAfterItemDiscount));

        if (orderDiscountInput) {
            orderDiscountInput.value = String(Math.round(orderDiscount));
        }

        const totalDiscount = itemDiscount + orderDiscount;
        const finalTotal = Math.max(subtotalAfterItemDiscount - orderDiscount, 0);
        const lineCount = cartContainer.querySelectorAll('.cart-item-row').length;

        subtotalEl.textContent = `${formatNumber(subtotal)}đ`;
        itemDiscountEl.textContent = `${formatNumber(itemDiscount)}đ`;
        discountEl.textContent = `${formatNumber(totalDiscount)}đ`;
        totalEl.textContent = `${formatNumber(finalTotal)}đ`;
        totalFooterEl.textContent = `${formatNumber(finalTotal)}đ`;
        lineCountEl.textContent = `${lineCount}`;
        breakdownGoodsEl.textContent = `${formatNumber(subtotal)}đ`;
        breakdownItemDiscountEl.textContent = `${formatNumber(itemDiscount)}đ`;
        breakdownExtraDiscountEl.textContent = `${formatNumber(orderDiscount)}đ`;
        breakdownFinalTotalEl.textContent = `${formatNumber(finalTotal)}đ`;
    }

    function fetchVariantData(url, data) {
        currentVariantSearchPage = Number(data.page || 1);
        currentVariantSearchPerPage = Number(data.per_page || currentVariantSearchPerPage || 5);

        const query = new URLSearchParams(data).toString();
        variantSearchResults.innerHTML = '<div class="text-center text-muted py-3">Đang tải...</div>';

        fetch(`${url}?${query}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((response) => response.json())
            .then((payload) => {
                variantSearchResults.innerHTML = payload.html || '';
            })
            .catch(() => {
                variantSearchResults.innerHTML = '<div class="text-danger text-center py-3">Không tải được danh sách biến thể.</div>';
            });
    }

    function performVariantSearch(page = 1) {
        const term = (variantSearchInput.value || '').trim();
        if (term.length < 2) {
            variantSearchResults.innerHTML = '';
            return;
        }

        fetchVariantData('{{ route('orders.ajax_variant_search') }}', {
            page: page,
            search: term,
            per_page: currentVariantSearchPerPage,
            exclude_ids: getCartVariantIds()
        });
    }

    variantSearchInput.addEventListener('input', function () {
        window.clearTimeout(searchTimeout);
        searchTimeout = window.setTimeout(() => performVariantSearch(1), 300);
    });

    variantSearchButton.addEventListener('click', function () {
        performVariantSearch(1);
    });

    variantSearchResults.addEventListener('click', function (event) {
        const addBtn = event.target.closest('.add-variant-to-cart');
        if (!addBtn) {
            return;
        }
        event.preventDefault();

        const variantId = String(addBtn.dataset.variantId || '');
        if (!variantId) {
            return;
        }

        if (cartContainer.querySelector(`.cart-item-row[data-variant-id="${variantId}"]`)) {
            alert('Biến thể này đã có trong đơn.');
            return;
        }

        const variantName = addBtn.dataset.variantName || 'N/A';
        const variantSku = addBtn.dataset.variantSku || 'N/A';
        const variantPrice = parseFloat(addBtn.dataset.variantPrice || '0');
        const variantStock = parseInt(addBtn.dataset.variantStock || '0', 10);
        const variantImage = addBtn.dataset.variantImage || 'https://via.placeholder.com/48';
        const variantUnitLabel = addBtn.dataset.variantUnitLabel || 'Cái';
        const variantWeight = parseFloat(addBtn.dataset.variantWeight || '0');
        const variantWeightUnitLabel = addBtn.dataset.variantWeightUnitLabel || 'Kg';

        const row = document.createElement('tr');
        row.className = 'cart-item-row';
        row.setAttribute('data-variant-id', variantId);
        row.innerHTML = `
            <td>
                <div class="checkout-product">
                    <img src="${variantImage}" alt="${variantName}">
                    <div class="checkout-product-meta">
                        <div class="checkout-product-title">${variantName}</div>
                    </div>
                </div>
                <input type="hidden" name="items[${itemIndex}][variant_id]" value="${variantId}">
            </td>
            <td>${variantSku}</td>
            <td class="price" data-price="${variantPrice}">${formatNumber(variantPrice)}đ</td>
            <td>
                <input
                    type="number"
                    class="form-control form-control-sm discount-input"
                    name="item_discount[${variantId}]"
                    min="0"
                    step="1000"
                    max="${variantPrice}"
                    value="0">
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm quantity-input" min="1" max="${variantStock > 0 ? variantStock : ''}" value="1" required>
            </td>
            <td><span class="text-muted small">${variantUnitLabel}</span></td>
            <td>
                <span class="line-weight" data-unit-weight="${variantWeight.toFixed(3)}" data-weight-unit="${variantWeightUnitLabel}">
                    ${variantWeight.toLocaleString('vi-VN', { minimumFractionDigits: 3, maximumFractionDigits: 3 })} ${variantWeightUnitLabel}
                </span>
            </td>
            <td class="row-total">${formatNumber(variantPrice)}đ</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove-cart-item">&times;</button>
            </td>
        `;

        cartContainer.appendChild(row);
        itemIndex += 1;
        updateNameIndexes();
        updateCartTotal();

        const currentResultItem = addBtn.closest('.list-group-item');
        if (currentResultItem) {
            currentResultItem.remove();
        } else {
            performVariantSearch(currentVariantSearchPage);
        }
    });

    variantSearchResults.addEventListener('click', function (event) {
        const pageLink = event.target.closest('.pagination a');
        if (!pageLink) {
            return;
        }
        event.preventDefault();
        const url = pageLink.getAttribute('href');
        if (!url) {
            return;
        }

        const parsedUrl = new URL(url, window.location.origin);
        fetchVariantData('{{ route('orders.ajax_variant_search') }}', {
            page: parsedUrl.searchParams.get('page') || currentVariantSearchPage,
            search: parsedUrl.searchParams.get('search') || (variantSearchInput.value || '').trim(),
            per_page: parsedUrl.searchParams.get('per_page') || currentVariantSearchPerPage,
            exclude_ids: getCartVariantIds()
        });
    });

    variantSearchResults.addEventListener('change', function (event) {
        if (event.target.id !== 'per-page-select') {
            return;
        }

        currentVariantSearchPerPage = Number(event.target.value || 5);
        performVariantSearch(1);
    });

    cartContainer.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('.remove-cart-item');
        if (!removeBtn) {
            return;
        }

        const rows = cartContainer.querySelectorAll('.cart-item-row');
        if (rows.length <= 1) {
            alert('Đơn hàng phải có ít nhất 1 sản phẩm.');
            return;
        }

        removeBtn.closest('.cart-item-row')?.remove();
        updateNameIndexes();
        updateCartTotal();
    });

    cartContainer.addEventListener('input', function (event) {
        if (!event.target.classList.contains('quantity-input') && !event.target.classList.contains('discount-input')) {
            return;
        }

        if (event.target.classList.contains('quantity-input')) {
            const quantity = parseInt(event.target.value || '1', 10);
            if (Number.isNaN(quantity) || quantity < 1) {
                event.target.value = '1';
            }
        }

        updateCartTotal();
    });

    if (orderDiscountInput) {
        orderDiscountInput.addEventListener('input', updateCartTotal);
    }

    form.addEventListener('submit', function (event) {
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach((row) => {
            const variantInput = row.querySelector('input[type="hidden"][name*="[variant_id]"]');
            const variantId = variantInput?.value ? String(variantInput.value).trim() : '';

            if (!variantId) {
                row.remove();
            }
        });

        updateNameIndexes();

        if (cartContainer.querySelectorAll('.cart-item-row').length < 1) {
            event.preventDefault();
            alert('Đơn hàng phải có ít nhất 1 sản phẩm.');
        }
    });

    updateNameIndexes();
    updateCartTotal();
});
</script>
@endpush
