@extends('layouts.site')

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
    .table > :not(caption) > * > *{
        padding: .5rem .3rem;
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
    .min50 {
        min-width: 50px !important;
    }
    .min80 {
        min-width: 80px !important;
    }
    .form-control-sm {
        padding: .25rem .0rem .25rem .3rem !important;
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
    .discount-switch {
        display: inline-flex;
        width: 100%;
    }
    .discount-switch .btn {
        padding: .2rem .55rem;
        font-size: .78rem;
    }
    .selling-price-stepper {
        display: inline-flex;
        align-items: stretch;
        min-width: 164px;
    }
    .selling-price-stepper .btn {
        width: 34px;
        border-color: #cbd5e1;
        border-radius: 0;
        color: #0f766e;
        font-size: 1rem;
        font-weight: 900;
    }
    .selling-price-stepper .btn:first-child { border-radius: 5px 0 0 5px; }
    .selling-price-stepper .btn:last-child { border-radius: 0 5px 5px 0; }
    .selling-price-stepper .btn:disabled { color: #94a3b8; background: #f1f5f9; opacity: 1; }
    .selling-price-value {
        min-width: 96px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 8px;
        border-block: 1px solid #cbd5e1;
        background: #fff;
        color: #047857;
        font-size: .84rem;
        font-weight: 900;
        white-space: nowrap;
    }
    .selling-price-feedback {
        display: none;
        margin-top: .35rem;
        font-size: .78rem;
        color: #dc3545;
    }
    .selling-price-feedback.active {
        display: block;
    }
    .price.price-invalid,
    .row-total.row-total-invalid {
        color: #dc3545;
        font-weight: 700;
    }
    .discount-input.is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 .2rem rgba(220,53,69,.15) !important;
    }
    .variant-picker-empty {
        padding: 16px;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        border: 1px dashed rgba(148, 163, 184, .5);
        border-radius: 10px;
    }
    .variant-picker-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin: 10px 0;
    }
    .variant-picker-toolbar #per-page-select {
        width: 74px;
    }
    .variant-picker-list {
        display: grid;
        gap: 8px;
    }
    .variant-picker-item {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) 150px auto;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 10px;
        background: #fff;
    }
    .variant-picker-main {
        display: flex;
        align-items: center;
        min-width: 0;
        gap: 9px;
    }
    .variant-picker-thumb {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        object-fit: cover;
        background: #e2e8f0;
        border: 1px solid rgba(148, 163, 184, .35);
        flex: 0 0 auto;
    }
    .variant-picker-copy {
        min-width: 0;
    }
    .variant-picker-name {
        color: #0f172a;
        font-weight: 800;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .variant-picker-star {
        color: #f59e0b;
        margin-right: 3px;
    }
    .variant-picker-meta {
        display: flex;
        flex-wrap: nowrap;
        gap: 5px;
        margin-top: 4px;
        color: #64748b;
        font-size: .73rem;
        overflow: hidden;
    }
    .variant-picker-meta span {
        padding: 2px 6px;
        border-radius: 999px;
        background: #f1f5f9;
        white-space: nowrap;
    }
    .variant-picker-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
    }
    .variant-picker-stats > div {
        padding: 6px 8px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid rgba(148, 163, 184, .22);
    }
    .variant-picker-label {
        display: block;
        color: #64748b;
        font-size: .66rem;
        line-height: 1;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .variant-picker-stats strong {
        display: block;
        color: #0f172a;
        font-size: .86rem;
        white-space: nowrap;
    }
    .variant-picker-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        flex-wrap: nowrap;
    }
    .variant-picker-actions .btn {
        white-space: nowrap;
        padding: .25rem .5rem;
        font-size: .78rem;
    }
    .monitor-product-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .monitor-product-list { display: grid; gap: 8px; }
    .monitor-product-card { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 9px; background: #fff; }
    .monitor-product-card.is-open { border-color: #0f766e; box-shadow: 0 0 0 2px rgba(15, 118, 110, .08); }
    .monitor-product-choice { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 12px; padding: 10px; border: 0; background: #fff; color: #0f172a; text-align: left; }
    .monitor-product-main { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .monitor-product-main > span:last-child { min-width: 0; }
    .monitor-product-thumb { width: 52px; height: 52px; flex: 0 0 auto; border-radius: 7px; object-fit: cover; }
    .monitor-product-name, .monitor-product-meta { display: block; }
    .monitor-product-name { overflow: hidden; font-size: .84rem; text-overflow: ellipsis; white-space: nowrap; }
    .monitor-product-meta { margin-top: 3px; color: #64748b; font-size: .7rem; }
    .monitor-product-choice-label { flex: 0 0 auto; color: #0f766e; font-size: .75rem; font-weight: 800; }
    .monitor-product-card.is-open .monitor-product-choice-label i { transform: rotate(180deg); }
    .monitor-product-variants { padding: 10px; border-top: 1px solid #e5e7eb; background: #f8fafc; }
    .monitor-variant-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    .monitor-variant-option { display: grid; gap: 2px; min-height: 74px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #334155; text-align: left; font-size: .72rem; }
    .monitor-variant-option:hover { border-color: #0f766e; background: #ecfdf5; }
    .monitor-variant-size { color: #0f172a; font-size: .84rem; font-weight: 900; }
    .monitor-variant-option small { color: #64748b; }
    @media (max-width: 992px) {
        .variant-picker-item {
            grid-template-columns: 1fr;
            gap: 8px;
        }
        .variant-picker-actions {
            justify-content: flex-start;
            flex-wrap: wrap;
        }
        .variant-picker-name {
            white-space: normal;
        }
        .variant-picker-meta {
            flex-wrap: wrap;
        }
        .monitor-variant-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        .monitor-product-choice-label { font-size: 0; }
        .monitor-product-choice-label i { font-size: .8rem; }
        .monitor-variant-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<section class="checkout-page">
    @php
        $defaultAddress = $customer->addresses->firstWhere('is_default', 1) ?: $customer->addresses->first();
        $preferredAddress = $defaultAddress->note ?? $customer->address;
    @endphp
    <div class="container checkout-shell">
        <div class="checkout-hero">
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;opacity:.8;">My Customers</div>
            <h1 class="h4 mb-1 fw-bold">Lên đơn cho {{ $customer->name }}</h1>
            <p class="mb-0" style="opacity:.85;">Giao diện tạo đơn tương tự trang chỉnh sửa đơn, cho phép chọn sản phẩm trực tiếp và cập nhật tổng đơn realtime.</p>
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

        <form action="{{ route('my_customer.order.store', $customer) }}" method="POST" id="my-customer-order-create-form">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="checkout-panel mb-3">
                        <div class="checkout-panel-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0">Thông tin khách hàng</h2>
                                <span class="text-muted small">Dựa trên thông tin khách hàng hiện tại.</span>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Khách hàng</label>
                                    <input type="text" class="form-control" value="{{ $customer->name }}" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Số điện thoại</label>
                                    <input type="text" class="form-control" value="{{ $customer->phone }}" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="email" class="form-control" value="{{ $customer->email }}" readonly>
                                </div>
                                @php
                                    $selectedRoute = $customer->truckRoute;
                                    if (!$selectedRoute && $customer->truck_station_id) {
                                        $selectedRoute = $customer->truckRouteByStation;
                                    }
                                    $hasTransportSelection = !empty($customer->truck_route_id) || !empty($customer->truck_station_id);
                                    $truckStationName = $customer->truckStation?->name
                                        ?: ($selectedRoute?->stops?->first()?->station?->name);
                                    $selectedRouteName = $selectedRoute?->name;
                                @endphp
                                @if($hasTransportSelection)
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Trạm nhận</label>
                                        <input type="text" class="form-control" value="{{ $truckStationName ?: 'Chưa cập nhật' }}" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tuyến vận chuyển</label>
                                        <input type="text" class="form-control" value="{{ $selectedRouteName ?: 'Chưa cập nhật' }}" readonly>
                                    </div>
                                @endif
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Giờ giao hàng</label>
                                    <input type="text" name="delivery_time" id="delivery_time" class="form-control" value="{{ old('delivery_time', $customer->delivery_time) }}" placeholder="Ví dụ: 9h-11h hoặc sau 17h">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tên người nhận</label>
                                    <input type="text" name="recipient_name" class="form-control" value="{{ old('recipient_name', $customer->name) }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Số điện thoại người nhận</label>
                                    <input type="text" name="recipient_phone" class="form-control" value="{{ old('recipient_phone', $customer->phone) }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email người nhận</label>
                                    <input type="email" name="recipient_email" class="form-control" value="{{ old('recipient_email', $customer->email) }}">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Địa chỉ nhận hàng</label>
                                    <textarea name="recipient_address" rows="3" class="form-control" required>{{ old('recipient_address', $preferredAddress) }}</textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Ghi chú đơn hàng</label>
                                    <textarea name="note" rows="3" class="form-control" placeholder="Ghi chú cho đơn hàng">{{ old('note', $customer->note) }}</textarea>
                                </div>
                                <div class="col-12 mb-3">
                                    <input type="hidden" name="warehouse_can_adjust" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="warehouse_can_adjust" value="1"
                                               id="warehouse_can_adjust" {{ old('warehouse_can_adjust') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="warehouse_can_adjust">Cho phép kho điều chỉnh</label>
                                    </div>
                                    <div class="form-text">Kho có thể thay đổi số lượng sản phẩm mà không cần sale xác nhận.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-panel mb-3">
                        <div class="checkout-panel-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0">Sản phẩm trong đơn</h2>
                                <span class="text-muted small">Thêm hoặc xoá sản phẩm trước khi lưu đơn.</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table checkout-table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-center">Size</th>
                                            <th class="text-center">Giá bán</th>
                                            <th class="text-center">SL</th>
                                            <th class="text-center">Tổng</th>
                                            <th class="text-center">Tạm tính</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-items-container"></tbody>
                                </table>
                                <div id="empty-cart-message" class="text-center text-muted py-4">
                                    Chưa có sản phẩm nào. Hãy tìm và thêm sản phẩm vào đơn.</div>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-panel">
                        <div class="checkout-panel-body">
                            <h2 class="h6 fw-bold mb-3">Chọn sản phẩm và biến thể</h2>
                            <div class="input-group mb-2">
                                <button class="btn btn-success" type="button" id="variant-show-all-button">
                                    <i class="bi bi-plus-circle me-1"></i> Thêm sản phẩm
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="text" id="variant-search" class="form-control" placeholder="Nhập tên sản phẩm, SKU hoặc size biến thể">
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
                                    <div class="btn-group discount-switch mb-1" role="group" aria-label="Loai chiet khau tong don">
                                        <input class="btn-check order-discount-type-input" type="radio" name="order_discount_type" id="my-customer-order-discount-decrease" value="decrease" {{ old('order_discount_type', 'decrease') === 'decrease' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary" for="my-customer-order-discount-decrease">Giảm</label>
                                        <input class="btn-check order-discount-type-input" type="radio" name="order_discount_type" id="my-customer-order-discount-increase" value="increase" {{ old('order_discount_type') === 'increase' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary" for="my-customer-order-discount-increase">Tăng</label>
                                    </div>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1000"
                                        class="form-control form-control-sm"
                                        id="orderDiscountInput"
                                        name="order_discount"
                                        value="{{ old('order_discount', 0) }}">
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
                                <div class="alert alert-danger py-2 px-3 mb-0 d-none w-100" id="sellingPriceValidationAlert"></div>
                            </div>

                            <div class="d-flex gap-2 mt-2">
                                <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-outline-secondary w-50">Quay lại</a>
                                <button type="submit" class="btn btn-primary w-50" id="myCustomerOrderSubmitButton">Tạo đơn</button>
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
    const variantShowAllButton = document.getElementById('variant-show-all-button');
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
    const getOrderDiscountTypeInput = () => document.querySelector('.order-discount-type-input:checked');
    const form = document.getElementById('my-customer-order-create-form');
    const submitButton = document.getElementById('myCustomerOrderSubmitButton');
    const validationAlert = document.getElementById('sellingPriceValidationAlert');
    const emptyCartMessage = document.getElementById('empty-cart-message');
    let itemIndex = cartContainer.querySelectorAll('.cart-item-row').length;
    let searchTimeout = null;
    let currentVariantSearchPage = 1;
    let currentVariantSearchPerPage = 5;
    let currentVariantShowAll = false;

    function formatNumber(num) {
        return new Intl.NumberFormat('vi-VN').format(num);
    }

    /**
     * Chuẩn hóa hiển thị kg.
     * Input:  "1,000 kg" | "1.250 kg" | "1.200 kg" | 1.25 (number)
     * Output: "1kg"      | "1.25kg"   | "1.2kg"    | "1.25kg"
     */
    function formatKg(value) {
        // Nếu là number, dùng trực tiếp; nếu là string thì parse
        let num;
        if (typeof value === 'number') {
            num = value;
        } else {
            const cleaned = String(value)
                .replace(/\s/g, '')         // bỏ khoảng trắng
                .replace(/kg/gi, '')        // bỏ chữ kg
                .replace(/\./g, '')         // bỏ dấu chấm phân cách hàng nghìn (vi-VN dùng . cho nghìn)
                .replace(',', '.');         // chuyển dấu , thập phân thành .
            num = parseFloat(cleaned);
        }
        if (!isFinite(num)) return value;
        // Loại bỏ số 0 dư ở cuối thập phân, tối đa 3 chữ số thập phân
        const str = num.toFixed(3).replace(/\.?0+$/, '');
        return `${str}kg`;
    }

    function getCartVariantIds() {
        return Array.from(cartContainer.querySelectorAll('.cart-item-row'))
            .map((row) => row.getAttribute('data-variant-id'))
            .filter(Boolean);
    }

    function updateNameIndexes() {
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach((row, idx) => {
            row.querySelectorAll('input[name^="items["]').forEach((input) => {
                const match = input.name.match(/^items\[\d+\]\[(.+)\]$/);
                if (match) {
                    input.name = `items[${idx}][${match[1]}]`;
                }
            });
        });
    }

    function updateCartVisibility() {
        const hasRows = cartContainer.querySelectorAll('.cart-item-row').length > 0;
        emptyCartMessage.style.display = hasRows ? 'none' : 'block';
    }

    function formatMoney(num) {
        return `${formatNumber(Math.max(0, num))}đ`;
    }

    function toNumber(value, fallback = 0) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function validateRowSellingPrice(row) {
        const priceEl = row.querySelector('.price');
        const discountInput = row.querySelector('.discount-input');
        const discountTypeInput = row.querySelector('.discount-type-input');
        const feedbackEl = row.querySelector('.selling-price-feedback');
        const rowTotalEl = row.querySelector('.row-total');

        if (!priceEl || !discountInput || !feedbackEl) {
            return false;
        }

        const basePrice = parseFloat(priceEl.getAttribute('data-price') || '0');
        const minPrice = parseFloat(priceEl.getAttribute('data-min-price') || '0');
        const discountValue = Math.max(0, parseFloat(discountInput.value || '0'));
        const discountType = discountTypeInput?.value === 'increase' ? 'increase' : 'decrease';
        const sellingPrice = discountType === 'increase'
            ? basePrice + discountValue
            : basePrice - discountValue;
        const invalid = discountType === 'decrease' && sellingPrice < minPrice;

        discountInput.classList.toggle('is-invalid', invalid);
        priceEl.classList.toggle('price-invalid', invalid);
        rowTotalEl?.classList.toggle('row-total-invalid', invalid);
        feedbackEl.classList.toggle('active', invalid);
        feedbackEl.textContent = invalid
            ? `Giá thấp nhất được nhập: ${formatMoney(minPrice)}. Giá bán hiện tại: ${formatMoney(sellingPrice)}.`
            : '';

        return invalid;
    }

    function updateCartTotal() {
        let subtotal = 0;
        let itemDiscount = 0;
        let hasInvalidSellingPrice = false;
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach((row) => {
            const priceEl = row.querySelector('.price');
            const qtyInput = row.querySelector('.quantity-input');
            const discountInput = row.querySelector('.discount-input');
            const discountTypeInput = row.querySelector('.discount-type-input');
            const rowTotalEl = row.querySelector('.row-total');
            const price = parseFloat(priceEl?.getAttribute('data-price') || '0');
            const minPrice = parseFloat(priceEl?.getAttribute('data-min-price') || '0');
            const quantity = Math.max(1, parseInt(qtyInput?.value || '0', 10));
            const isPricedByKg = row.dataset.isPricedByKg === '1';
            let unitDiscount = toNumber(discountInput?.value, 0);
            const discountType = discountTypeInput?.value === 'increase' ? 'increase' : 'decrease';
            unitDiscount = Math.max(0, unitDiscount);

            const lineWeightEl = row.querySelector('.line-weight');
            if (lineWeightEl) {
                const unitWeight = parseFloat(lineWeightEl.getAttribute('data-unit-weight') || '0');
                const weightUnit = lineWeightEl.getAttribute('data-weight-unit') || 'Kg';
                const lineWeight = Math.max(0, unitWeight * quantity);
                lineWeightEl.textContent = isPricedByKg
                    ? formatKg(lineWeight)
                    : `${formatNumber(quantity)} ${weightUnit}`;
            }

            discountInput.max = discountType === 'decrease' ? String(Math.max(price - minPrice, 0)) : '';

            const weightEl = row.querySelector('.line-weight');
            const unitWeight = weightEl
                ? parseFloat(weightEl.getAttribute('data-unit-weight') || '0')
                : 0;
            const pricingFactor = isPricedByKg ? Math.max(unitWeight, 0) : 1;
            const lineSubtotal = price * quantity * pricingFactor;
            const lineAdjustment = (discountType === 'increase' ? -1 : 1) * unitDiscount * quantity * pricingFactor;
            const lineTotal = Math.max(lineSubtotal - lineAdjustment, 0);
            const sellingPrice = discountType === 'increase' ? price + unitDiscount : price - unitDiscount;

            const sellingPriceEl = row.querySelector('.selling-price-value');
            if (sellingPriceEl) {
                sellingPriceEl.textContent = formatMoney(sellingPrice);
            }
            const decreaseButton = row.querySelector('.selling-price-decrease');
            if (decreaseButton) {
                decreaseButton.disabled = sellingPrice <= minPrice;
            }

            if (rowTotalEl) {
                rowTotalEl.textContent = `${formatNumber(lineTotal)}đ`;
            }
            subtotal += lineSubtotal;
            itemDiscount += lineAdjustment;
            hasInvalidSellingPrice = validateRowSellingPrice(row) || hasInvalidSellingPrice;
        });

        const subtotalAfterItemDiscount = Math.max(subtotal - itemDiscount, 0);
        let orderDiscount = parseFloat(orderDiscountInput?.value || '0');
        const orderDiscountType = getOrderDiscountTypeInput()?.value === 'increase' ? 'increase' : 'decrease';
        orderDiscount = Math.max(0, orderDiscountType === 'decrease' ? Math.min(orderDiscount, subtotalAfterItemDiscount) : orderDiscount);
        const orderAdjustment = (orderDiscountType === 'increase' ? -1 : 1) * orderDiscount;

        if (orderDiscountInput) {
            orderDiscountInput.value = String(Math.round(orderDiscount));
        }

        const totalDiscount = itemDiscount + orderAdjustment;
        const finalTotal = Math.max(subtotalAfterItemDiscount - orderAdjustment, 0);
        const lineCount = cartContainer.querySelectorAll('.cart-item-row').length;

        const formatSigned = (value) => `${value < 0 ? '+' : '-'}${formatNumber(Math.abs(value))}đ`;

        subtotalEl.textContent = `${formatNumber(subtotal)}đ`;
        itemDiscountEl.textContent = formatSigned(itemDiscount);
        discountEl.textContent = formatSigned(totalDiscount);
        totalEl.textContent = `${formatNumber(finalTotal)}đ`;
        totalFooterEl.textContent = `${formatNumber(finalTotal)}đ`;
        lineCountEl.textContent = `${lineCount}`;
        breakdownGoodsEl.textContent = `${formatNumber(subtotal)}đ`;
        breakdownItemDiscountEl.textContent = formatSigned(itemDiscount);
        breakdownExtraDiscountEl.textContent = formatSigned(orderAdjustment);
        breakdownFinalTotalEl.textContent = `${formatNumber(finalTotal)}đ`;

        if (submitButton) {
            submitButton.disabled = hasInvalidSellingPrice;
        }

        if (validationAlert) {
            validationAlert.classList.toggle('d-none', !hasInvalidSellingPrice);
            validationAlert.textContent = hasInvalidSellingPrice
                ? 'Có sản phẩm vi phạm giá tối thiểu. Giá thấp nhất được nhập: vui lòng kiểm tra các ô discount đang tô đỏ.'
                : '';
        }

        return !hasInvalidSellingPrice;
    }

    function fetchVariantData(url, data) {
        currentVariantSearchPage = Number(data.page || 1);
        currentVariantSearchPerPage = Number(data.per_page || currentVariantSearchPerPage || 5);
        currentVariantShowAll = data.show_all === true || data.show_all === 1 || data.show_all === '1';

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
        currentVariantShowAll = false;
        if (term.length < 2) {
            variantSearchResults.innerHTML = '';
            return;
        }

        fetchVariantData('{{ route('site.orders.variants.ajax') }}', {
            page: page,
            search: term,
            per_page: currentVariantSearchPerPage,
            view: 'products',
            exclude_ids: getCartVariantIds()
        });
    }

    function refreshVariantResults(page = currentVariantSearchPage) {
        const term = (variantSearchInput.value || '').trim();
        if (currentVariantShowAll || term.length < 2) {
            fetchVariantData('{{ route('site.orders.variants.ajax') }}', {
                page: page,
                per_page: currentVariantSearchPerPage,
                view: 'products',
                show_all: 1,
                exclude_ids: getCartVariantIds()
            });
            return;
        }

        performVariantSearch(page);
    }

    if (variantShowAllButton) {
        variantShowAllButton.addEventListener('click', function () {
            fetchVariantData('{{ route('site.orders.variants.ajax') }}', {
                page: 1,
                per_page: currentVariantSearchPerPage,
                view: 'products',
                show_all: 1,
                exclude_ids: getCartVariantIds()
            });
            if (variantSearchInput) {
                variantSearchInput.value = '';
            }
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
        const productChoice = event.target.closest('.monitor-product-choice');
        if (productChoice) {
            event.preventDefault();
            const card = productChoice.closest('.monitor-product-card');
            const variants = card?.querySelector('.monitor-product-variants');
            if (!card || !variants) {
                return;
            }

            const willOpen = variants.hidden;
            variantSearchResults.querySelectorAll('.monitor-product-card.is-open').forEach((openCard) => {
                if (openCard !== card) {
                    openCard.classList.remove('is-open');
                    openCard.querySelector('.monitor-product-choice')?.setAttribute('aria-expanded', 'false');
                    const openVariants = openCard.querySelector('.monitor-product-variants');
                    if (openVariants) openVariants.hidden = true;
                }
            });
            card.classList.toggle('is-open', willOpen);
            productChoice.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            variants.hidden = !willOpen;
            return;
        }

        const addBtn = event.target.closest('.monitor-variant-option, .add-variant-to-cart');
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
        const variantSize = addBtn.dataset.variantSize || '';
        const variantPrice = parseFloat(addBtn.dataset.variantPrice || '0');
        const variantImage = addBtn.dataset.variantImage || 'https://via.placeholder.com/48';
        const variantUnitLabel = addBtn.dataset.variantUnitLabel || 'Cái';
        const variantWeight = parseFloat(addBtn.dataset.variantWeight || '0');
        const variantIsPricedByKg = addBtn.dataset.variantIsPricedByKg === '1';
        const variantMinPrice = parseFloat(addBtn.dataset.variantMinPrice || '0');

        const row = document.createElement('tr');
        row.className = 'cart-item-row';
        row.setAttribute('data-variant-id', variantId);
        row.setAttribute('data-is-priced-by-kg', variantIsPricedByKg ? '1' : '0');
        row.innerHTML = `
            <td>
                <div class="checkout-product">
                    <img src="${variantImage}" alt="${variantName}">
                    <div class="checkout-product-meta">
                        <div class="checkout-product-title">${variantName}</div>
                        <div class="checkout-product-sub">${variantSku}</div>
                    </div>
                </div>
                <input type="hidden" name="items[${itemIndex}][variant_id]" value="${variantId}">
                <input type="hidden" name="item_weight[${variantId}]" value="${variantWeight.toFixed(3)}">
            </td>
            <td class="text-center">${variantSize || variantSku}</td>
            <td class="price text-center" data-price="${variantPrice}" data-min-price="${variantMinPrice}">
                <div class="selling-price-stepper">
                    <button type="button" class="btn btn-sm selling-price-decrease" aria-label="Giảm đơn giá 1.000 đồng" title="Giảm 1.000đ" ${variantPrice <= variantMinPrice ? 'disabled' : ''}>−</button>
                    <span class="selling-price-value">${formatMoney(variantPrice)}</span>
                    <button type="button" class="btn btn-sm selling-price-increase" aria-label="Tăng đơn giá 1.000 đồng" title="Tăng 1.000đ">+</button>
                </div>
                <input type="hidden" class="discount-type-input" name="item_discount_type[${variantId}]" value="decrease">
                <input type="hidden" class="discount-input" name="item_discount[${variantId}]" value="0">
                <div class="selling-price-feedback"></div>
            </td>
            <td class="text-center">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm quantity-input min50" min="1" value="1" required>
            </td>
            <td class="text-center">
                <span class="line-weight" data-unit-weight="${variantWeight.toFixed(3)}" data-weight-unit="${variantUnitLabel}">
                    ${variantIsPricedByKg ? formatKg(variantWeight) : `1 ${variantUnitLabel}`}
                </span>
            </td>
            <td class="text-end row-total">${formatNumber(variantPrice)}đ</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-cart-item">&times;</button>
            </td>
        `;

        cartContainer.appendChild(row);
        itemIndex += 1;
        updateNameIndexes();
        updateCartTotal();
        updateCartVisibility();

        refreshVariantResults(currentVariantSearchPage);
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
        const search = parsedUrl.searchParams.get('search') || (variantSearchInput.value || '').trim();
        const shouldShowAll = currentVariantShowAll || search.length < 2;
        fetchVariantData('{{ route('site.orders.variants.ajax') }}', {
            page: parsedUrl.searchParams.get('page') || currentVariantSearchPage,
            search: search,
            per_page: parsedUrl.searchParams.get('per_page') || currentVariantSearchPerPage,
            view: 'products',
            show_all: shouldShowAll ? 1 : 0,
            exclude_ids: getCartVariantIds()
        });
    });

    variantSearchResults.addEventListener('change', function (event) {
        if (event.target.id !== 'per-page-select') {
            return;
        }

        currentVariantSearchPerPage = Number(event.target.value || 5);
        refreshVariantResults(1);
    });

    cartContainer.addEventListener('click', function (event) {
        const priceButton = event.target.closest('.selling-price-decrease, .selling-price-increase');
        if (priceButton) {
            const row = priceButton.closest('.cart-item-row');
            const priceEl = row?.querySelector('.price');
            const discountInput = row?.querySelector('.discount-input');
            const discountTypeInput = row?.querySelector('.discount-type-input');
            if (!row || !priceEl || !discountInput || !discountTypeInput) {
                return;
            }

            const basePrice = toNumber(priceEl.dataset.price, 0);
            const minPrice = toNumber(priceEl.dataset.minPrice, 0);
            const adjustment = Math.max(0, toNumber(discountInput.value, 0));
            const currentPrice = discountTypeInput.value === 'increase'
                ? basePrice + adjustment
                : basePrice - adjustment;
            const step = priceButton.classList.contains('selling-price-increase') ? 1000 : -1000;
            const sellingPrice = Math.max(minPrice, currentPrice + step);

            discountTypeInput.value = sellingPrice > basePrice ? 'increase' : 'decrease';
            discountInput.value = String(Math.abs(sellingPrice - basePrice));
            updateCartTotal();
            return;
        }

        const removeBtn = event.target.closest('.remove-cart-item');
        if (!removeBtn) {
            return;
        }

        removeBtn.closest('.cart-item-row')?.remove();
        updateNameIndexes();
        updateCartTotal();
        updateCartVisibility();

        if (currentVariantShowAll || variantSearchInput.value.trim().length >= 2) {
            refreshVariantResults(1);
        }
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

    cartContainer.addEventListener('change', function (event) {
        if (!event.target.classList.contains('discount-type-input')) {
            return;
        }

        updateCartTotal();
    });

    if (orderDiscountInput) {
        orderDiscountInput.addEventListener('input', updateCartTotal);
    }

    document.querySelectorAll('.order-discount-type-input').forEach((input) => {
        input.addEventListener('change', updateCartTotal);
    });

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
            return;
        }

        if (!updateCartTotal()) {
            event.preventDefault();
        }
    });

    updateNameIndexes();
    updateCartTotal();
    updateCartVisibility();
});
</script>
@endpush
