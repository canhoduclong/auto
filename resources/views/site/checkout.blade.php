@extends('layouts.site')

@php
    if (!function_exists('format_kg')) {
        function format_kg($value): string {
            $str = rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
            return $str . 'kg';
        }
    }
    $cart = session('cart', []);
    $summarySubtotal = 0;
    $summaryItemDiscount = 0;
    $summaryOrderDiscount = max(0, (float) old('order_discount', 0));
    $summaryOrderDiscountType = old('order_discount_type', (string) session('order_discount_type', 'decrease')) === 'increase' ? 'increase' : 'decrease';
    $summaryDiscount = 0;
    $summaryTotal = 0;
    $summaryWeight = 0;

    foreach ($cart as $id => $details) {
        $unitPrice = (float) ($details['price'] ?? 0);
        $quantity = (int) ($details['quantity'] ?? 0);
        $inputDiscount = (float) old('item_discount.' . $id, 0);
        $inputDiscountType = old('item_discount_type.' . $id, (string) ($details['discount_type'] ?? 'decrease')) === 'increase' ? 'increase' : 'decrease';
        $defaultWeight = (float) ($details['unit_weight'] ?? 0);
        $inputWeight = (float) old('item_weight.' . $id, $defaultWeight);
        $isPricedByKg = (bool) ($details['is_priced_by_kg'] ?? true);
        $minPrice = (float) ($details['min_price'] ?? 0);
        $unitDiscount = max(0, $inputDiscount);
        if ($inputDiscountType === 'decrease') {
            $unitDiscount = min($unitDiscount, max($unitPrice - $minPrice, 0));
        }
        $unitWeight = max(0, round($inputWeight, 3));
        $pricingFactor = $isPricedByKg ? $unitWeight : 1;
        $lineSubtotal = $unitPrice * $quantity * $pricingFactor;
        $lineDiscount = ($inputDiscountType === 'increase' ? -1 : 1) * $unitDiscount * $quantity * $pricingFactor;
        $lineTotal = max($lineSubtotal - $lineDiscount, 0);
        $lineWeight = $unitWeight * $quantity;

        $summarySubtotal += $lineSubtotal;
        $summaryItemDiscount += $lineDiscount;
        $summaryTotal += $lineTotal;
        $summaryWeight += $lineWeight;
    }

    if ($summaryOrderDiscountType === 'decrease') {
        $summaryOrderDiscount = min($summaryOrderDiscount, $summaryTotal);
    }
    $summaryOrderDiscountSigned = $summaryOrderDiscountType === 'increase' ? -1 * $summaryOrderDiscount : $summaryOrderDiscount;
    $summaryDiscount = $summaryItemDiscount + $summaryOrderDiscountSigned;
    $summaryTotal = max($summaryTotal - $summaryOrderDiscountSigned, 0);
@endphp

@push('styles')
<style>
    .checkout-page {
        --checkout-ink: #0f172a;
        --checkout-muted: #64748b;
        --checkout-line: rgba(148, 163, 184, 0.28);
        --checkout-surface: #ffffff;
        --checkout-accent: #0f766e;
        --checkout-accent-soft: #ecfeff;
        --checkout-warm: #f59e0b;
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

    .checkout-hero h1 {
        margin: 8px 0 6px;
        font-size: clamp(1.4rem, 2.5vw, 2rem);
        font-weight: 900;
        letter-spacing: -0.02em;
    }

    .checkout-hero p {
        margin: 0;
        color: rgba(248, 250, 252, 0.82);
        font-size: 0.92rem;
    }

    .checkout-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 999px;
        padding: 6px 10px;
        background: rgba(255, 255, 255, 0.08);
    }

    .checkout-panel {
        background: var(--checkout-surface);
        border: 1px solid var(--checkout-line);
        border-radius: 20px;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
    }

    .checkout-panel-body {
        padding: 20px;
    }

    .checkout-panel-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: var(--checkout-ink);
    }

    .checkout-panel-subtitle {
        margin: 6px 0 0;
        color: var(--checkout-muted);
        font-size: 0.86rem;
        line-height: 1.5;
    }

    .checkout-block-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 16px;
    }

    .checkout-form-group {
        margin-bottom: 14px;
    }

    .checkout-form-label {
        font-weight: 700;
        color: var(--checkout-ink);
        margin-bottom: 6px;
    }

    .checkout-form-group .form-control,
    .checkout-form-group .form-select {
        border-radius: 12px;
        border: 1px solid var(--checkout-line);
        min-height: 44px;
    }

    .checkout-form-group .form-control:focus,
    .checkout-form-group .form-select:focus {
        border-color: rgba(20, 184, 166, 0.75);
        box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.14);
    }

    .checkout-customer-panel {
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.25);
    }

    .checkout-table-wrap {
        border: 1px solid var(--checkout-line);
        border-radius: 14px;
        overflow: hidden;
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
        border-bottom-width: 1px;
        white-space: nowrap;
    }

    .checkout-product {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 250px;
    }

    .checkout-product img {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 10px;
        background: #e2e8f0;
        border: 1px solid rgba(148, 163, 184, 0.25);
    }

    .checkout-product-name {
        margin: 0 0 2px;
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--checkout-ink);
    }

    .checkout-product-meta {
        margin: 0;
        font-size: 0.78rem;
        color: var(--checkout-muted);
    }

    .checkout-price,
    .checkout-line-total,
    .checkout-qty {
        font-weight: 700;
        color: var(--checkout-ink);
        white-space: nowrap;
    }

    .checkout-discount-input {
        width: 130px;
        min-width: 120px;
    }

    .checkout-discount-note {
        display: block;
        margin-top: 4px;
        font-size: 0.74rem;
        color: var(--checkout-muted);
    }

    .discount-switch {
        display: inline-flex;
        width: 100%;
    }

    .discount-switch .btn {
        padding: .2rem .55rem;
        font-size: 0.78rem;
    }

    .selling-price-feedback {
        display: none;
        margin-top: 0.35rem;
        font-size: 0.78rem;
        color: #dc3545;
    }

    .selling-price-feedback.active {
        display: block;
    }

    .checkout-price.price-invalid,
    .checkout-line-total.row-total-invalid {
        color: #dc3545;
        font-weight: 700;
    }

    .checkout-discount-input.is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .15) !important;
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
        color: var(--checkout-muted);
        margin-bottom: 4px;
    }

    .checkout-kpi-value {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--checkout-ink);
    }

    .checkout-kpi-value.discount {
        color: #b45309;
    }

    .checkout-kpi-value.total {
        color: var(--checkout-accent);
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

    .checkout-summary-line strong {
        color: var(--checkout-ink);
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

    .checkout-breakdown-item:last-child {
        margin-bottom: 0;
    }

    .checkout-breakdown-item strong {
        color: var(--checkout-ink);
    }

    .checkout-breakdown-item.total {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed rgba(148, 163, 184, 0.45);
    }

    .checkout-breakdown-item.total strong:last-child {
        color: var(--checkout-accent);
        font-size: 1.05rem;
    }

    .checkout-summary-total {
        margin-top: 4px;
        padding-top: 10px;
        border-top: 1px dashed rgba(148, 163, 184, 0.45);
    }

    .checkout-summary-total strong:last-child {
        color: var(--checkout-accent);
        font-size: 1.15rem;
    }

    .checkout-actions {
        margin-top: 14px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .checkout-btn-submit {
        min-height: 44px;
        border-radius: 12px;
        border: 0;
        background: linear-gradient(135deg, #0f766e, #14b8a6);
        color: #fff;
        font-weight: 800;
        box-shadow: 0 10px 22px rgba(15, 118, 110, 0.2);
    }

    .checkout-btn-submit:hover {
        color: #fff;
        background: linear-gradient(135deg, #115e59, #0f766e);
    }

    .checkout-note {
        border-radius: 12px;
        border: 1px solid rgba(15, 118, 110, 0.2);
        background: var(--checkout-accent-soft);
        color: #115e59;
        padding: 10px 12px;
        font-size: 0.82rem;
        line-height: 1.45;
    }

    @media (max-width: 991.98px) {
        .checkout-summary {
            position: static;
        }

        .checkout-panel-body {
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')
<section class="checkout-page">
    <div class="container checkout-shell">
        <div class="checkout-hero">
            <span class="checkout-eyebrow"><i class="bi bi-cart-check"></i> Thanh toán đơn hàng</span>
            <h1>Xác nhận đơn với discount giá bán và discount tổng đơn</h1>
            <p>Nhập thông tin nhận hàng, discount theo giá bán từng sản phẩm, thêm discount tổng đơn và kiểm tra tổng thanh toán trước khi đặt đơn.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <div class="fw-bold mb-1">Không thể tạo đơn hàng:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('orders.store_from_cart') }}" method="POST" id="checkoutForm">
            @csrf
            <input type="hidden" name="customer_id" id="selected_customer_id" value="{{ old('customer_id') }}">

            <div class="row g-3 align-items-start">
                <div class="col-lg-8">
                    <div class="checkout-panel mb-3">
                        <div class="checkout-panel-body">
                            <div class="checkout-block-head">
                                <div>
                                    <h2 class="checkout-panel-title">Thông tin người nhận</h2>
                                    <p class="checkout-panel-subtitle">Dùng khách hàng đã có hoặc nhập nhanh thông tin mới cho đơn hàng này.</p>
                                </div>
                            </div>

                            @auth
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnToggleCustomerPicker">Chọn khách hàng</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearCustomer" style="display:none;">Bỏ chọn</button>
                                <div id="selectedCustomerPreview" class="alert alert-info mt-2 mb-0 py-2 px-3" style="display:none;"></div>
                            </div>

                            <div id="customerPickerPanel" class="checkout-customer-panel p-3 mb-3" style="display:none;">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-8">
                                        <label for="customer_search" class="form-label mb-1">Tìm khách hàng (tên, email, số điện thoại)</label>
                                        <input type="text" id="customer_search" class="form-control" placeholder="Nhập từ khóa tìm kiếm...">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="customer_per_page" class="form-label mb-1">Số dòng</label>
                                        <select id="customer_per_page" class="form-select">
                                            <option value="10">10</option>
                                            <option value="15" selected>15</option>
                                            <option value="20">20</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary w-100" id="btnSearchCustomer">Tìm</button>
                                    </div>
                                </div>

                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tên</th>
                                                <th>Email</th>
                                                <th>Số điện thoại</th>
                                                <th>Địa chỉ</th>
                                                <th>Ghi chú</th>
                                                <th width="100">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="customerSearchResults">
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">Nhập từ khóa để tìm khách hàng.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div id="customerPaginationInfo" class="text-muted small"></div>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCustomerPrev">Trước</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCustomerNext">Sau</button>
                                    </div>
                                </div>
                            </div>
                            @endauth

                            <div class="row">
                                <div class="col-md-6 checkout-form-group">
                                    <label for="recipient_name" class="checkout-form-label">Họ tên người nhận</label>
                                    <input type="text" class="form-control @error('recipient_name') is-invalid @enderror"
                                        id="recipient_name" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name ?? '') }}" required>
                                    @error('recipient_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 checkout-form-group">
                                    <label for="recipient_phone" class="checkout-form-label">Số điện thoại</label>
                                    <input type="text" class="form-control @error('recipient_phone') is-invalid @enderror"
                                        id="recipient_phone" name="recipient_phone" value="{{ old('recipient_phone') }}" required>
                                    @error('recipient_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 checkout-form-group">
                                    <label for="recipient_email" class="checkout-form-label">Email</label>
                                    <input type="email" class="form-control @error('recipient_email') is-invalid @enderror"
                                        id="recipient_email" name="recipient_email" value="{{ old('recipient_email', auth()->user()->email ?? '') }}">
                                    @error('recipient_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 checkout-form-group">
                                    <label for="delivery_time" class="checkout-form-label">Giờ giao hàng</label>
                                    <input type="text" class="form-control @error('delivery_time') is-invalid @enderror"
                                        id="delivery_time" name="delivery_time" value="{{ old('delivery_time') }}"
                                        placeholder="Ví dụ: 9h-11h hoặc sau 17h">
                                    @error('delivery_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 checkout-form-group">
                                    <label for="recipient_address" class="checkout-form-label">Địa chỉ nhận hàng</label>
                                    <textarea class="form-control @error('recipient_address') is-invalid @enderror"
                                        id="recipient_address" name="recipient_address" rows="3" required>{{ old('recipient_address') }}</textarea>
                                    @error('recipient_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 checkout-form-group mb-0">
                                    <label for="note" class="checkout-form-label">Ghi chú</label>
                                    <textarea class="form-control" id="note" name="note" rows="3">{{ old('note') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-panel">
                        <div class="checkout-panel-body">
                            <div class="checkout-block-head">
                                <div>
                                    <h2 class="checkout-panel-title">Sản phẩm trong đơn</h2>
                                    <p class="checkout-panel-subtitle">Nhập discount theo giá bán từng sản phẩm. Khối lượng được tính tự động theo quy cách biến thể và số lượng trong giỏ hàng.</p>
                                </div>
                            </div>

                            <div class="checkout-table-wrap">
                                <div class="table-responsive">
                                    <table class="table checkout-table">
                                        <thead>
                                            <tr>
                                                <th>Sản phẩm</th>
                                                <th>Đơn giá</th> 
                                                <th>CK Giá</th>
                                                <th>Số lượng</th>
                                                <th>Size</th>
                                                <th>Tạm tính</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cart as $id => $details)
                                                @php
                                                   // echo "<pre>";  print_r($details);   echo "</pre>";

                                                    $unitPrice = (float) ($details['price'] ?? 0);
                                                    $quantity = (int) ($details['quantity'] ?? 0);
                                                    $inputDiscount = (float) old('item_discount.' . $id, 0);
                                                    $unitSize = (float) ($details['unit_weight'] ?? 0);
                                                    $isPricedByKg = (bool) ($details['is_priced_by_kg'] ?? true);
                                                    $inputDiscountType = old('item_discount_type.' . $id, (string) ($details['discount_type'] ?? 'decrease')) === 'increase' ? 'increase' : 'decrease';
                                                    $minPrice = (float) ($details['min_price'] ?? 0);
                                                    $unitDiscount = max(0, $inputDiscount);
                                                    $pricingFactor = $isPricedByKg ? max($unitSize, 0) : 1;
                                                    $lineSubtotal = $unitPrice * $quantity * $pricingFactor;
                                                    $lineAdjustment = ($inputDiscountType === 'increase' ? -1 : 1) * $unitDiscount * $quantity * $pricingFactor;
                                                    $lineTotal = max($lineSubtotal - $lineAdjustment, 0);
                                                @endphp
                                                <tr class="checkout-item-row"
                                                    data-unit-price="{{ $unitPrice }}"
                                                    data-quantity="{{ $quantity }}"
                                                    data-unit-size="{{ $unitSize }}"
                                                    data-is-priced-by-kg="{{ $isPricedByKg ? '1' : '0' }}"
                                                    data-min-price="{{ $minPrice }}"
                                                    data-variant-id="{{ $id }}">
                                                    <td>
                                                        <div class="checkout-product">
                                                            @if(!empty($details['image']))
                                                                <img src="{{ asset('storage/' . $details['image']) }}" alt="{{ $details['name'] }}">
                                                            @endif
                                                            <div>
                                                                <p class="checkout-product-name">{{ $details['name'] }}</p>
                                                                <p class="checkout-product-meta">SKU: {{ $details['sku'] ?? '-' }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="checkout-price">{{ number_format($unitPrice, 0, ',', '.') }}đ</td>
                                                    
                                                    <td>
                                                        <div class="btn-group discount-switch mb-1" role="group" aria-label="Loai chiet khau">
                                                            <input class="btn-check checkout-discount-type" type="radio" name="item_discount_type[{{ $id }}]" id="checkout-discount-decrease-{{ $id }}" value="decrease" data-discount-type-input {{ $inputDiscountType === 'decrease' ? 'checked' : '' }}>
                                                            <label class="btn btn-outline-secondary" for="checkout-discount-decrease-{{ $id }}">Giảm</label>
                                                            <input class="btn-check checkout-discount-type" type="radio" name="item_discount_type[{{ $id }}]" id="checkout-discount-increase-{{ $id }}" value="increase" data-discount-type-input {{ $inputDiscountType === 'increase' ? 'checked' : '' }}>
                                                            <label class="btn btn-outline-secondary" for="checkout-discount-increase-{{ $id }}">Tăng</label>
                                                        </div>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="1000"
                                                            max="{{ $inputDiscountType === 'decrease' ? max($unitPrice - $minPrice, 0) : '' }}"
                                                            class="form-control form-control-sm checkout-discount-input"
                                                            name="item_discount[{{ $id }}]"
                                                            value="{{ old('item_discount.' . $id, 0) }}"
                                                            data-discount-input>
                                                        <div class="selling-price-feedback"></div>
                                                    </td>
                                                    <td class="checkout-qty">{{ $quantity }}</td>
                                                    <td>
                                                        <div class="checkout-weight">{{ number_format((float) $unitSize, 3, ',', '.') }} {{ $details['unit_label'] ?? 'Cái' }}</div>
                                                        <div class="text-muted small">{{ $isPricedByKg ? 'Tính theo kg' : 'Tính theo đơn vị' }}</div>
                                                         
                                                    </td>
                                                    <td class="checkout-line-total" data-line-total>{{ number_format($lineTotal, 0, ',', '.') }}đ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <aside class="checkout-summary">
                        <div class="checkout-panel">
                            <div class="checkout-panel-body">
                                <div class="checkout-block-head">
                                    <div>
                                        <h2 class="checkout-panel-title">Tóm tắt thanh toán</h2>
                                        <p class="checkout-panel-subtitle">Kiểm tra lại tổng giá trị trước khi tạo đơn hàng.</p>
                                    </div>
                                </div>

                                <div class="checkout-summary-grid">
                                    <div class="checkout-kpi">
                                        <span class="checkout-kpi-label">Tạm tính</span>
                                        <span class="checkout-kpi-value" id="summarySubtotal">{{ number_format($summarySubtotal, 0, ',', '.') }}đ</span>
                                    </div>
                                    <div class="checkout-kpi">
                                        <span class="checkout-kpi-label">Discount sản phẩm</span>
                                        <span class="checkout-kpi-value discount" id="summaryItemDiscount">{{ number_format($summaryItemDiscount, 0, ',', '.') }}đ</span>
                                    </div>
                                    <div class="checkout-kpi">
                                        <span class="checkout-kpi-label">Discount tổng đơn</span>
                                        <div class="mt-1">
                                            <div class="btn-group discount-switch mb-1" role="group" aria-label="Loai chiet khau tong don">
                                                <input class="btn-check order-discount-type-input" type="radio" name="order_discount_type" id="checkout-order-discount-decrease" value="decrease" {{ $summaryOrderDiscountType === 'decrease' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-secondary" for="checkout-order-discount-decrease">Giảm</label>
                                                <input class="btn-check order-discount-type-input" type="radio" name="order_discount_type" id="checkout-order-discount-increase" value="increase" {{ $summaryOrderDiscountType === 'increase' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-secondary" for="checkout-order-discount-increase">Tăng</label>
                                            </div>
                                            <input type="number" min="0" step="1000" class="form-control form-control-sm" id="orderDiscountInput" name="order_discount" value="{{ old('order_discount', 0) }}">
                                            <small class="checkout-discount-note">Giảm trực tiếp trên tổng đơn sau discount sản phẩm.</small>
                                        </div>
                                    </div>
                                    <div class="checkout-kpi">
                                        <span class="checkout-kpi-label">Tổng discount</span>
                                        <span class="checkout-kpi-value discount" id="summaryDiscount">{{ number_format($summaryDiscount, 0, ',', '.') }}đ</span>
                                    </div>
                                    <div class="checkout-kpi">
                                        <span class="checkout-kpi-label">Thanh toán</span>
                                        <span class="checkout-kpi-value total" id="summaryTotal">{{ number_format($summaryTotal, 0, ',', '.') }}đ</span>
                                    </div>
                                    <div class="checkout-kpi">
                                        <span class="checkout-kpi-label">Tổng khối lượng</span>
                                        <span class="checkout-kpi-value" id="summaryWeight">{{ format_kg($summaryWeight) }}</span>
                                    </div>
                                </div>

                                <div class="checkout-summary-line">
                                    <span>Số dòng sản phẩm</span>
                                    <strong>{{ count($cart) }}</strong>
                                </div>
                                <div class="checkout-summary-line checkout-summary-total">
                                    <strong>Tổng cộng</strong>
                                    <strong id="summaryTotalFooter">{{ number_format($summaryTotal, 0, ',', '.') }}đ</strong>
                                </div>

                                <div class="checkout-breakdown">
                                    <div class="checkout-breakdown-item">
                                        <span>Tiền hàng</span>
                                        <strong id="breakdownGoods">{{ number_format($summarySubtotal, 0, ',', '.') }}đ</strong>
                                    </div>
                                    <div class="checkout-breakdown-item">
                                        <span>Tiền giảm (discount)</span>
                                        <strong id="breakdownItemDiscount">{{ number_format($summaryItemDiscount, 0, ',', '.') }}đ</strong>
                                    </div>
                                    <div class="checkout-breakdown-item">
                                        <span>Giảm thêm (discount ngoài)</span>
                                        <strong id="breakdownExtraDiscount">{{ number_format($summaryOrderDiscount, 0, ',', '.') }}đ</strong>
                                    </div>
                                    <div class="checkout-breakdown-item total">
                                        <strong>Tổng tiền cuối cùng</strong>
                                        <strong id="breakdownFinalTotal">{{ number_format($summaryTotal, 0, ',', '.') }}đ</strong>
                                    </div>
                                </div>

                                <div class="checkout-note mt-2">
                                    Discount theo giá bán sản phẩm và discount tổng đơn sẽ được áp dụng và lưu trực tiếp vào đơn hàng.
                                </div>

                                <div class="alert alert-danger py-2 px-3 mt-2 mb-0 d-none" id="sellingPriceValidationAlert"></div>

                                <div class="checkout-actions">
                                    <a href="{{ route('cart.show') }}" class="btn btn-outline-secondary flex-grow-1">Quay lại giỏ hàng</a>
                                    <button type="submit" class="btn checkout-btn-submit flex-grow-1" id="checkoutSubmitButton">Đặt hàng</button>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const rows = Array.from(document.querySelectorAll('.checkout-item-row'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const subtotalEl = document.getElementById('summarySubtotal');
    const discountEl = document.getElementById('summaryDiscount');
    const itemDiscountEl = document.getElementById('summaryItemDiscount');
    const totalEl = document.getElementById('summaryTotal');
    const weightEl = document.getElementById('summaryWeight');
    const totalFooterEl = document.getElementById('summaryTotalFooter');
    const orderDiscountInput = document.getElementById('orderDiscountInput');
    const breakdownGoodsEl = document.getElementById('breakdownGoods');
    const breakdownItemDiscountEl = document.getElementById('breakdownItemDiscount');
    const breakdownExtraDiscountEl = document.getElementById('breakdownExtraDiscount');
    const breakdownFinalTotalEl = document.getElementById('breakdownFinalTotal');
    const getOrderDiscountTypeInput = () => document.querySelector('.order-discount-type-input:checked');
    const submitButton = document.getElementById('checkoutSubmitButton');
    const validationAlert = document.getElementById('sellingPriceValidationAlert');

    if (!rows.length) return;

    const formatMoney = value =>
        new Intl.NumberFormat('vi-VN').format(Math.max(0, value)) + 'đ';

    const formatSignedMoney = value =>
        `${value < 0 ? '+' : '-'}${new Intl.NumberFormat('vi-VN').format(Math.abs(value))}đ`;

    const formatWeight = value => {
        const num = Math.max(0, value);
        const str = num.toFixed(3).replace(/\.?0+$/, '');
        return str + 'kg';
    };

    const toNumber = (value, fallback = 0) => {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    };

    function validateSellingPriceRow(row) {
        const unitPrice = toNumber(row.dataset.unitPrice, 0);
        const minPrice = toNumber(row.dataset.minPrice, 0);
        const discountInput = row.querySelector('[data-discount-input]');
        const discountTypeInput = row.querySelector('[data-discount-type-input]:checked');
        const linePriceEl = row.querySelector('.checkout-price');
        const lineTotalEl = row.querySelector('[data-line-total]');
        const feedbackEl = row.querySelector('.selling-price-feedback');

        if (!discountInput || !feedbackEl) {
            return false;
        }

        const discountValue = Math.max(0, toNumber(discountInput.value, 0));
        const discountType = discountTypeInput?.value === 'increase' ? 'increase' : 'decrease';
        const sellingPrice = discountType === 'increase'
            ? unitPrice + discountValue
            : unitPrice - discountValue;
        const invalid = discountType === 'decrease' && sellingPrice < minPrice;

        discountInput.classList.toggle('is-invalid', invalid);
        linePriceEl?.classList.toggle('price-invalid', invalid);
        lineTotalEl?.classList.toggle('row-total-invalid', invalid);
        feedbackEl.classList.toggle('active', invalid);
        feedbackEl.textContent = invalid
            ? `Giá Min : ${formatMoney(minPrice)}. Giá bán hiện tại: ${formatMoney(sellingPrice)}.`
            : '';

        return invalid;
    }

    function recalcLocal() {
        let subtotal = 0;
        let itemDiscount = 0;
        let totalWeight = 0;
        let hasInvalidSellingPrice = false;

        rows.forEach(row => {
            const unitPrice = toNumber(row.dataset.unitPrice, 0);
            const quantity = toNumber(row.dataset.quantity, 0);
            const unitSize = toNumber(row.dataset.unitSize, 0);
            const isPricedByKg = row.dataset.isPricedByKg === '1';
            const minPrice = toNumber(row.dataset.minPrice, 0);

            const discountInput = row.querySelector('[data-discount-input]');
            const discountTypeInput = row.querySelector('[data-discount-type-input]:checked');
            const lineTotalEl = row.querySelector('[data-line-total]');

            if (!discountInput || !lineTotalEl) return;

            let unitDiscount = toNumber(discountInput.value, 0);
            const discountType = discountTypeInput?.value === 'increase' ? 'increase' : 'decrease';
            unitDiscount = Math.max(0, unitDiscount);
            discountInput.max = discountType === 'decrease' ? String(Math.max(unitPrice - minPrice, 0)) : '';

            const pricingFactor = isPricedByKg ? Math.max(unitSize, 0) : 1;
            const lineSubtotal = unitPrice * quantity * pricingFactor;
            const lineDiscount = (discountType === 'increase' ? -1 : 1) * unitDiscount * quantity * pricingFactor;
            const lineTotal = Math.max(lineSubtotal - lineDiscount, 0);

            subtotal += lineSubtotal;
            itemDiscount += lineDiscount;
            totalWeight += unitSize * quantity;

            lineTotalEl.textContent = formatMoney(lineTotal);
            hasInvalidSellingPrice = validateSellingPriceRow(row) || hasInvalidSellingPrice;
        });

        let orderDiscount = toNumber(orderDiscountInput.value, 0);
        const subtotalAfterItemDiscount = Math.max(subtotal - itemDiscount, 0);
        const orderDiscountType = getOrderDiscountTypeInput()?.value === 'increase' ? 'increase' : 'decrease';

        orderDiscount = Math.max(0, orderDiscountType === 'decrease' ? Math.min(orderDiscount, subtotalAfterItemDiscount) : orderDiscount);
        if (orderDiscount < 0) {
            orderDiscountInput.value = '0';
        }
        const orderAdjustment = orderDiscountType === 'increase' ? -1 * orderDiscount : orderDiscount;

        const totalDiscount = itemDiscount + orderAdjustment;
        const total = Math.max(subtotalAfterItemDiscount - orderAdjustment, 0);

        subtotalEl.textContent = formatMoney(subtotal);
        itemDiscountEl.textContent = formatSignedMoney(itemDiscount);
        discountEl.textContent = formatSignedMoney(totalDiscount);
        totalEl.textContent = formatMoney(total);
        weightEl.textContent = formatWeight(totalWeight);
        totalFooterEl.textContent = formatMoney(total);
        breakdownGoodsEl.textContent = formatMoney(subtotal);
        breakdownItemDiscountEl.textContent = formatSignedMoney(itemDiscount);
        breakdownExtraDiscountEl.textContent = formatSignedMoney(orderAdjustment);
        breakdownFinalTotalEl.textContent = formatMoney(total);

        if (submitButton) {
            submitButton.disabled = hasInvalidSellingPrice;
        }

        if (validationAlert) {
            validationAlert.classList.toggle('d-none', !hasInvalidSellingPrice);
            validationAlert.textContent = hasInvalidSellingPrice
                ? 'Có sản phẩm vi phạm giá tối thiểu. Giá Min : vui lòng kiểm tra các ô discount đang tô đỏ.'
                : '';
        }

        return !hasInvalidSellingPrice;
    }

    async function syncDiscountAjax() {
        if (!recalcLocal()) {
            return;
        }

        const itemDiscount = {};

        rows.forEach(row => {
            const variantId = row.dataset.variantId;
            const discountInput = row.querySelector('[data-discount-input]');
            if (variantId && discountInput) {
                itemDiscount[variantId] = discountInput.value;
            }
        });

        try {
            const response = await fetch('/checkout/update-discount', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    item_discount: itemDiscount,
                    item_discount_type: Object.fromEntries(rows.map((row) => {
                        const variantId = row.dataset.variantId;
                        const typeInput = row.querySelector('[data-discount-type-input]:checked');

                        return [variantId, typeInput?.value || 'decrease'];
                    })),
                    order_discount: orderDiscountInput.value,
                    order_discount_type: getOrderDiscountTypeInput()?.value || 'decrease'
                })
            });

            const data = await response.json();

            if (!data.success) return;

            subtotalEl.textContent = data.summary.formatted_subtotal;
            itemDiscountEl.textContent = data.summary.formatted_item_discount;
            discountEl.textContent = data.summary.formatted_discount;
            totalEl.textContent = data.summary.formatted_total;
            weightEl.textContent = data.summary.formatted_weight;
            totalFooterEl.textContent = data.summary.formatted_total;
            breakdownGoodsEl.textContent = data.summary.formatted_subtotal;
            breakdownItemDiscountEl.textContent = data.summary.formatted_item_discount;
            breakdownExtraDiscountEl.textContent = data.summary.formatted_order_discount;
            breakdownFinalTotalEl.textContent = data.summary.formatted_total;

        } catch (e) {
            console.error(e);
        }
    }

    function triggerUpdate() {
        const isValid = recalcLocal();
        if (isValid) {
            syncDiscountAjax();
        }
    }

    rows.forEach(row => {
        const discountInput = row.querySelector('[data-discount-input]');
        if (discountInput) {
            discountInput.addEventListener('input', triggerUpdate);
            discountInput.addEventListener('change', triggerUpdate);
        }
    });

    orderDiscountInput.addEventListener('input', triggerUpdate);
    orderDiscountInput.addEventListener('change', triggerUpdate);

    document.querySelectorAll('.order-discount-type-input').forEach((input) => {
        input.addEventListener('change', triggerUpdate);
    });

    rows.forEach(row => {
        row.querySelectorAll('[data-discount-type-input]').forEach((discountTypeInput) => {
            discountTypeInput.addEventListener('change', triggerUpdate);
        });
    });

    document.getElementById('checkoutForm')?.addEventListener('submit', (event) => {
        if (!recalcLocal()) {
            event.preventDefault();
        }
    });

    recalcLocal();
})();
</script>
@endpush

@auth
@push('scripts')
<script>
(() => {
    const pickerPanel = document.getElementById('customerPickerPanel');
    const togglePickerBtn = document.getElementById('btnToggleCustomerPicker');
    const clearCustomerBtn = document.getElementById('btnClearCustomer');
    const searchInput = document.getElementById('customer_search');
    const searchBtn = document.getElementById('btnSearchCustomer');
    const perPageSelect = document.getElementById('customer_per_page');
    const resultsBody = document.getElementById('customerSearchResults');
    const infoText = document.getElementById('customerPaginationInfo');
    const prevBtn = document.getElementById('btnCustomerPrev');
    const nextBtn = document.getElementById('btnCustomerNext');
    const selectedPreview = document.getElementById('selectedCustomerPreview');

    if (!pickerPanel || !togglePickerBtn || !clearCustomerBtn || !searchInput || !searchBtn || !perPageSelect || !resultsBody || !infoText || !prevBtn || !nextBtn || !selectedPreview) {
        return;
    }

    const selectedCustomerIdInput = document.getElementById('selected_customer_id');
    const recipientName = document.getElementById('recipient_name');
    const recipientEmail = document.getElementById('recipient_email');
    const recipientPhone = document.getElementById('recipient_phone');
    const recipientAddress = document.getElementById('recipient_address');
    const deliveryTimeInput = document.getElementById('delivery_time');
    const noteInput = document.getElementById('note');

    const state = {
        page: 1,
        lastPage: 1,
        q: '',
    };

    function setReadonlyBySelection(selected) {
        recipientName.readOnly = selected;
        recipientEmail.readOnly = selected;
        recipientPhone.readOnly = selected;
    }

    function renderLoading() {
        resultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Đang tải...</td></tr>';
    }

    function renderEmpty() {
        resultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Không tìm thấy khách hàng.</td></tr>';
    }

    function applySelectedCustomer(customer) {
        const name = customer.name || '';
        const email = customer.email || '';
        const phone = customer.phone || '';
        const address = customer.address || '';
        const deliveryTime = customer.delivery_time || '';
        const note = customer.note || '';

        recipientName.value = name;
        recipientEmail.value = email;
        recipientPhone.value = phone;
        recipientAddress.value = address;
        if (!deliveryTimeInput.value || deliveryTimeInput.dataset.autofilled === '1') {
            deliveryTimeInput.value = deliveryTime;
            deliveryTimeInput.dataset.autofilled = '1';
        }
        noteInput.value = note;

        selectedCustomerIdInput.value = String(customer.id);

        selectedPreview.style.display = 'block';
        selectedPreview.innerHTML = '<strong>Đã chọn:</strong> ' + escapeHtml(name) +
            ' | ' + escapeHtml(email || '-') +
            ' | ' + escapeHtml(phone || '-') +
            ' | ' + escapeHtml(address || '-');
        clearCustomerBtn.style.display = 'inline-block';
        setReadonlyBySelection(true);
    }

    function clearSelectedCustomer() {
        selectedCustomerIdInput.value = '';
        selectedPreview.style.display = 'none';
        selectedPreview.innerHTML = '';
        clearCustomerBtn.style.display = 'none';
        setReadonlyBySelection(false);
    }

    function renderRows(items) {
        if (!items.length) {
            renderEmpty();
            return;
        }

        resultsBody.innerHTML = items.map((customer) => {
            const payload = encodeURIComponent(JSON.stringify({
                id: customer.id,
                name: customer.name || '',
                email: customer.email || '',
                phone: customer.phone || '',
                address: customer.address || '',
                delivery_time: customer.delivery_time || '',
                note: customer.note || '',
            }));

            return '<tr>' +
                '<td>' + escapeHtml(customer.name || '') + '</td>' +
                '<td>' + escapeHtml(customer.email || '') + '</td>' +
                '<td>' + escapeHtml(customer.phone || '') + '</td>' +
                '<td>' + escapeHtml(customer.address || '') + '</td>' +
                '<td>' + escapeHtml(customer.note || '') + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-primary btn-select-customer" data-customer="' + payload + '">Chọn</button></td>' +
                '</tr>';
        }).join('');
    }

    async function fetchCustomers(page = 1) {
        state.page = page;
        state.q = (searchInput.value || '').trim();

        renderLoading();

        const params = new URLSearchParams({
            q: state.q,
            page: String(state.page),
            per_page: String(perPageSelect.value || '15'),
        });

        try {
            const res = await fetch('{{ route('cart.customers.search') }}?' + params.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                let message = 'Lỗi tải dữ liệu';
                try {
                    const errJson = await res.json();
                    if (errJson && errJson.message) {
                        message = errJson.message;
                    }
                } catch (e) {
                    // Ignore JSON parse errors for non-JSON responses.
                }
                throw new Error(message);
            }

            const json = await res.json();
            const items = json.data || [];
            const meta = json.meta || {};

            state.page = Number(meta.current_page || 1);
            state.lastPage = Number(meta.last_page || 1);

            renderRows(items);

            const total = Number(meta.total || 0);
            infoText.textContent = total > 0
                ? ('Trang ' + state.page + '/' + state.lastPage + ' - Tổng ' + total + ' khách hàng')
                : 'Không có dữ liệu';

            prevBtn.disabled = state.page <= 1;
            nextBtn.disabled = state.page >= state.lastPage;
        } catch (e) {
            resultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Lỗi khi tải danh sách khách hàng: ' + escapeHtml(e.message || 'unknown') + '</td></tr>';
            infoText.textContent = '';
            prevBtn.disabled = true;
            nextBtn.disabled = true;
        }
    }

    togglePickerBtn.addEventListener('click', () => {
        const opening = pickerPanel.style.display === 'none';
        pickerPanel.style.display = opening ? 'block' : 'none';
        if (opening) {
            fetchCustomers(1);
        }
    });

    searchBtn.addEventListener('click', () => fetchCustomers(1));
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            fetchCustomers(1);
        }
    });
    perPageSelect.addEventListener('change', () => fetchCustomers(1));

    prevBtn.addEventListener('click', () => {
        if (state.page > 1) {
            fetchCustomers(state.page - 1);
        }
    });

    nextBtn.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            fetchCustomers(state.page + 1);
        }
    });

    resultsBody.addEventListener('click', (event) => {
        const btn = event.target.closest('.btn-select-customer');
        if (!btn) {
            return;
        }

        const raw = btn.getAttribute('data-customer');
        if (!raw) {
            return;
        }

        const customer = JSON.parse(decodeURIComponent(raw));
        applySelectedCustomer(customer);
    });

    clearCustomerBtn.addEventListener('click', clearSelectedCustomer);
    deliveryTimeInput.addEventListener('input', () => {
        deliveryTimeInput.dataset.autofilled = '0';
    });

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (selectedCustomerIdInput.value) {
        setReadonlyBySelection(true);
        clearCustomerBtn.style.display = 'inline-block';
    }
})();
</script>
@endpush
@endauth
