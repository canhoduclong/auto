@extends('layouts.site')

@php
    $orderCode = $order->code ?: ('#' . $order->id);
    $formatKgLocal = static function (float $value): string {
        $normalized = max(0, round($value, 3));
        return number_format($normalized, 3, ',', '.') . ' kg';
    };
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
    .min50{
        min-width: 50px  !important;
    }
    .min80{
        min-width: 80px !important;
    }
    .form-control-sm { 
        padding: .25rem .0rem .25rem .3rem !important
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
                                    <label class="form-label fw-bold">Khách hàng</label>
                                    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $order->customer_id) }}" required>
                                    <div class="d-flex align-items-stretch gap-2">
                                        <div id="selected-customer-display" class="form-control d-flex align-items-center justify-content-between flex-grow-1" style="min-height:38px; cursor:default;">
                                            <span id="selected-customer-name" class="{{ $order->customer ? '' : 'd-none' }}">
                                                {{ $order->customer?->name }}
                                                @if($order->customer?->phone)
                                                    <small class="text-muted ms-1">{{ $order->customer->phone }}</small>
                                                @endif
                                            </span>
                                            <span id="no-customer-placeholder" class="text-muted {{ $order->customer ? 'd-none' : '' }}">Chưa chọn khách hàng</span>
                                            <button type="button" id="clear-customer-btn" class="btn-close ms-2 {{ $order->customer ? '' : 'd-none' }}" style="font-size:.6rem;" aria-label="Xóa"></button>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm px-3" id="open-customer-picker-btn" title="Chọn khách hàng">
                                            &#128269; Chọn
                                        </button>
                                    </div>
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
                                            <th class="text-center">Size</th>
                                            <th class="text-center">Đơn giá</th>
                                            <th class="text-center">CK giá</th>
                                            <th class="text-center">SL</th>
                                            <th class="text-center">Tổng</th>
                                            <th class="text-center">Tạm tính</th>
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
                                                $unitDiscountType = old('item_discount_type.' . ($variant?->id), $item->discount_type ?? 'decrease');
                                                $unitDiscountType = $unitDiscountType === 'increase' ? 'increase' : 'decrease';
                                                $isPricedByKg = old('item_is_priced_by_kg.' . ($variant?->id), $item->is_priced_by_kg ?? ($variant?->is_priced_by_kg ?? $variant?->product?->is_priced_by_kg ?? true));
                                                $isPricedByKg = filter_var($isPricedByKg, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                                $isPricedByKg = $isPricedByKg === null ? true : $isPricedByKg;
                                                $minPrice = (float) ($variant?->latestPriceRule?->min_price ?? 0);
                                                $unitDiscount = max(0, $unitDiscount);
                                                $lineUnitPrice = $unitDiscountType === 'increase'
                                                    ? ($unitPrice + $unitDiscount)
                                                    : ($unitPrice - $unitDiscount);
                                                $pricingFactor = $isPricedByKg ? max($unitWeight, 0) : 1;
                                                $lineTotal = (float) ($item->total ?? ($lineUnitPrice * $qty * $pricingFactor));
                                            @endphp
                                            <tr class="cart-item-row" data-variant-id="{{ $variant?->id }}" data-is-priced-by-kg="{{ $isPricedByKg ? '1' : '0' }}">
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
                                                <td class="price" data-price="{{ $unitPrice }}" data-min-price="{{ $minPrice }}">{{ number_format($unitPrice, 0, ',', '.') }}đ</td>
                                                <td>
                                                    <div class="btn-group discount-switch mb-1" role="group" aria-label="Loai chiet khau">
                                                        <input class="btn-check discount-type-input" type="radio" name="item_discount_type[{{ $variant?->id }}]" id="discount-decrease-{{ $variant?->id }}" value="decrease" {{ $unitDiscountType === 'decrease' ? 'checked' : '' }}>
                                                        <label class="btn btn-outline-secondary" for="discount-decrease-{{ $variant?->id }}">Giảm</label>
                                                        <input class="btn-check discount-type-input" type="radio" name="item_discount_type[{{ $variant?->id }}]" id="discount-increase-{{ $variant?->id }}" value="increase" {{ $unitDiscountType === 'increase' ? 'checked' : '' }}>
                                                        <label class="btn btn-outline-secondary" for="discount-increase-{{ $variant?->id }}">Tăng</label>
                                                    </div>
                                                    <input
                                                        type="number"
                                                        class="form-control form-control-sm discount-input min80"
                                                        name="item_discount[{{ $variant?->id }}]"
                                                        min="0"
                                                        step="1000"
                                                        max="{{ $unitDiscountType === 'decrease' ? max($unitPrice - $minPrice, 0) : '' }}"
                                                        value="{{ number_format($unitDiscount, 0, '.', '') }}">
                                                    <div class="selling-price-feedback"></div>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm quantity-input min50" min="1" value="{{ $qty }}" required>
                                                </td>
                                                <td class="text-end">
                                                    <span
                                                        class="line-weight"
                                                        data-unit-weight="{{ number_format((float) $unitWeight, 3, '.', '') }}"
                                                        data-weight-unit="{{ $unitLabel }}"
                                                        data-display-mode="{{ $isPricedByKg ? 'kg' : 'unit' }}">
                                                        @if($isPricedByKg)
                                                            {{ $formatKgLocal((float) ($unitWeight * $qty)) }}
                                                        @else
                                                            {{ number_format((float) $qty, 0, ',', '.') }} {{ $unitLabel }}
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="row-total text-end">{{ number_format($lineTotal, 0, ',', '.') }}đ</td>
                                                <td class="text-end">
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
                            <div class="input-group mb-2">
                                <button class="btn btn-success" type="button" id="variant-show-all-button">
                                    <i class="bi bi-plus-circle me-1"></i> Thêm sản phẩm
                                </button>
                            </div>
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
                                    <div class="btn-group discount-switch mb-1" role="group" aria-label="Loai chiet khau tong don" id="orderDiscountTypeSwitch">
                                        <input class="btn-check order-discount-type-input" type="radio" name="order_discount_type" id="order-discount-decrease" value="decrease" {{ old('order_discount_type', $order->order_discount_type ?? 'decrease') === 'decrease' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary" for="order-discount-decrease">Giảm</label>
                                        <input class="btn-check order-discount-type-input" type="radio" name="order_discount_type" id="order-discount-increase" value="increase" {{ old('order_discount_type', $order->order_discount_type ?? 'decrease') === 'increase' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary" for="order-discount-increase">Tăng</label>
                                    </div>
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
                                <div class="alert alert-danger py-2 px-3 mb-0 d-none w-100" id="sellingPriceValidationAlert"></div>
                            </div>

                            <div class="d-flex gap-2 mt-2">
                                <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-secondary w-50">Quay lại</a>
                                <button type="submit" class="btn btn-primary w-50" id="orderEditSubmitButton">Lưu thay đổi</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

{{-- Customer Picker Modal --}}
<div class="modal fade" id="customerPickerModal" tabindex="-1" aria-labelledby="customerPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="customerPickerModalLabel">Chọn khách hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <input type="text" id="customer-search-input" class="form-control form-control-sm"
                            placeholder="Tìm theo tên, SĐT, email...">
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <label class="input-group-text" for="customer-sort-select">Sắp xếp</label>
                            <select id="customer-sort-select" class="form-select form-select-sm">
                                <option value="name|asc" selected>Tên A → Z</option>
                                <option value="name|desc">Tên Z → A</option>
                                <option value="phone|asc">SĐT tăng dần</option>
                                <option value="phone|desc">SĐT giảm dần</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <label class="input-group-text" for="customer-per-page-select">Hiển thị</label>
                            <select id="customer-per-page-select" class="form-select form-select-sm">
                                <option value="10">10</option>
                                <option value="15" selected>15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div id="customer-picker-results">
                    <div class="text-center text-muted py-4">Đang tải danh sách khách hàng...</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Nút Thêm sản phẩm: load toàn bộ biến thể (không truyền keyword, phân trang)
    const variantShowAllButton = document.getElementById('variant-show-all-button');
    if (variantShowAllButton) {
        variantShowAllButton.addEventListener('click', function () {
            // Gọi API /my-orders/variants/ajax, không truyền keyword, phân trang như search
            fetchVariantData(variantSearchButton.dataset.url, {
                page: 1,
                per_page: currentVariantSearchPerPage
            });
            if (variantSearchInput) variantSearchInput.value = '';
        });
    }
    // ── Customer Picker ──────────────────────────────────────────────
    const customerPickerModal   = document.getElementById('customerPickerModal');
    const customerPickerResults = document.getElementById('customer-picker-results');
    const customerSearchInput   = document.getElementById('customer-search-input');
    const customerSortSelect    = document.getElementById('customer-sort-select');
    const customerPerPageSelect = document.getElementById('customer-per-page-select');
    const openPickerBtn         = document.getElementById('open-customer-picker-btn');
    const clearCustomerBtn      = document.getElementById('clear-customer-btn');
    const customerIdInput       = document.getElementById('customer_id');
    const selectedCustomerName  = document.getElementById('selected-customer-name');
    const noCustomerPlaceholder = document.getElementById('no-customer-placeholder');

    let cpSearchTimeout   = null;
    let cpCurrentPage     = 1;
    const cpAjaxUrl       = '{{ route('site.orders.customers.ajax') }}';

    function loadCustomers(page) {
        cpCurrentPage = page || 1;
        const sortParts = (customerSortSelect?.value || 'name|asc').split('|');
        const sortBy    = sortParts[0] || 'name';
        const sortDir   = sortParts[1] || 'asc';

        customerPickerResults.innerHTML = '<div class="text-center text-muted py-4">Đang tải...</div>';

        const params = new URLSearchParams({
            q:        customerSearchInput?.value?.trim() || '',
            per_page: customerPerPageSelect?.value || '15',
            sort_by:  sortBy,
            sort_dir: sortDir,
            page:     String(cpCurrentPage),
            mode:     'single',
        });

        fetch(`${cpAjaxUrl}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(data => {
                customerPickerResults.innerHTML = data.html || '<div class="text-center text-muted py-4">Không có dữ liệu.</div>';
            })
            .catch(() => {
                customerPickerResults.innerHTML = '<div class="text-danger text-center py-4">Không tải được danh sách khách hàng.</div>';
            });
    }

    // Open modal → load customers
    if (openPickerBtn) {
        openPickerBtn.addEventListener('click', function () {
            const modal = bootstrap.Modal.getOrCreateInstance(customerPickerModal);
            modal.show();
            loadCustomers(1);
        });
    }

    // Auto-load when modal is shown (also covers programmatic open)
    if (customerPickerModal) {
        customerPickerModal.addEventListener('shown.bs.modal', function () {
            customerSearchInput?.focus();
        });
    }

    // Search with debounce
    if (customerSearchInput) {
        customerSearchInput.addEventListener('input', function () {
            clearTimeout(cpSearchTimeout);
            cpSearchTimeout = setTimeout(() => loadCustomers(1), 300);
        });
    }

    // Sort / per-page changes
    [customerSortSelect, customerPerPageSelect].forEach(el => {
        el?.addEventListener('change', () => loadCustomers(1));
    });

    // Delegation: pagination buttons and sort links inside the results
    if (customerPickerResults) {
        customerPickerResults.addEventListener('click', function (e) {
            const pageBtn = e.target.closest('.customer-page-btn');
            if (pageBtn) {
                e.preventDefault();
                loadCustomers(parseInt(pageBtn.dataset.page, 10) || 1);
                return;
            }

            const sortLink = e.target.closest('.customer-sort-link');
            if (sortLink) {
                e.preventDefault();
                const sortBy  = sortLink.dataset.sortBy;
                const sortDir = sortLink.dataset.sortDir;
                if (customerSortSelect) {
                    customerSortSelect.value = `${sortBy}|${sortDir}`;
                }
                loadCustomers(1);
                return;
            }

            const selectBtn = e.target.closest('.select-customer-btn');
            if (selectBtn) {
                e.preventDefault();
                const id      = selectBtn.dataset.customerId;
                const name    = selectBtn.dataset.customerName    || '';
                const phone   = selectBtn.dataset.customerPhone   || '';
                const email   = selectBtn.dataset.customerEmail   || '';
                const address = selectBtn.dataset.customerAddress || '';

                // Set hidden input
                if (customerIdInput) customerIdInput.value = id;

                // Update display
                if (selectedCustomerName) {
                    selectedCustomerName.innerHTML = `<strong>${name}</strong>`
                        + (phone ? ` <small class="text-muted ms-1">${phone}</small>` : '');
                    selectedCustomerName.classList.remove('d-none');
                }
                if (noCustomerPlaceholder) noCustomerPlaceholder.classList.add('d-none');
                if (clearCustomerBtn)      clearCustomerBtn.classList.remove('d-none');

                // Auto-fill recipient fields if empty
                const recipientName    = document.getElementById('recipient_name');
                const recipientPhone   = document.getElementById('recipient_phone');
                const recipientEmail   = document.getElementById('recipient_email');
                const recipientAddress = document.getElementById('recipient_address');
                if (recipientName    && !recipientName.value.trim()    && name)    recipientName.value    = name;
                if (recipientPhone   && !recipientPhone.value.trim()   && phone)   recipientPhone.value   = phone;
                if (recipientEmail   && !recipientEmail.value.trim()   && email)   recipientEmail.value   = email;
                if (recipientAddress && !recipientAddress.value.trim() && address) recipientAddress.value = address;

                // Close modal
                bootstrap.Modal.getInstance(customerPickerModal)?.hide();
                return;
            }
        });
    }

    // Clear selected customer
    if (clearCustomerBtn) {
        clearCustomerBtn.addEventListener('click', function () {
            if (customerIdInput)       customerIdInput.value = '';
            if (selectedCustomerName)  selectedCustomerName.classList.add('d-none');
            if (noCustomerPlaceholder) noCustomerPlaceholder.classList.remove('d-none');
            clearCustomerBtn.classList.add('d-none');
        });
    }

    // ── Cart & Variants ──────────────────────────────────────────────
    const cartContainer = document.getElementById('cart-items-container');
    const variantSearchInput = document.getElementById('variant-search');
    const variantSearchButton = document.getElementById('variant-search-button');
    // Lưu URL ajax từ thuộc tính data-url nếu có, fallback route cũ nếu không
    if (variantSearchButton && !variantSearchButton.dataset.url) {
        variantSearchButton.dataset.url = variantSearchButton.getAttribute('data-url') || '{{ route('site.orders.variants.ajax') }}';
    }
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
    const form = document.getElementById('my-order-edit-form');
    const submitButton = document.getElementById('orderEditSubmitButton');
    const validationAlert = document.getElementById('sellingPriceValidationAlert');
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

    function formatMoney(num) {
        return `${formatNumber(Math.max(0, num))}đ`;
    }

    function toNumber(value, fallback = 0) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function formatKg(value) {
        const num = typeof value === 'number' ? value : parseFloat(String(value).replace(/\s/g, '').replace(/kg/gi, '').replace(/\./g, '').replace(',', '.'));
        if (!isFinite(num)) return value;
        return num.toFixed(3).replace(/\.?0+$/, '') + 'kg';
    }

    function validateRowSellingPrice(row) {
        const priceEl = row.querySelector('.price');
        const discountInput = row.querySelector('.discount-input');
        const discountTypeInput = row.querySelector('.discount-type-input:checked');
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
            ? `Giá bán ${formatMoney(sellingPrice)} thấp hơn giá Min ${formatMoney(minPrice)}.`
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
            const discountTypeInput = row.querySelector('.discount-type-input:checked');
            const rowTotalEl = row.querySelector('.row-total');
            const price = parseFloat(priceEl?.getAttribute('data-price') || '0');
            const minPrice = parseFloat(priceEl?.getAttribute('data-min-price') || '0');
            const quantity = parseInt(qtyInput?.value || '0', 10);
            const isPricedByKg = row.dataset.isPricedByKg === '1';
            let unitDiscount = toNumber(discountInput?.value, 0);
            const discountType = discountTypeInput?.value === 'increase' ? 'increase' : 'decrease';
            unitDiscount = Math.max(0, unitDiscount);

            const lineWeightEl = row.querySelector('.line-weight');
            if (lineWeightEl) {
                const unitWeight = parseFloat(lineWeightEl.getAttribute('data-unit-weight') || '0');
                const weightUnit = lineWeightEl.getAttribute('data-weight-unit') || 'Kg';
                const lineWeight = Math.max(0, unitWeight * Math.max(quantity, 0));
                if (isPricedByKg) {
                    lineWeightEl.textContent = formatKg(lineWeight);
                } else {
                    lineWeightEl.textContent = `${Math.max(quantity, 0).toLocaleString('vi-VN')} ${weightUnit}`;
                }
            }

            if (discountInput) {
                discountInput.max = discountType === 'decrease' ? String(Math.max(price - minPrice, 0)) : '';
            }

            const weightEl = row.querySelector('.line-weight');
            const unitWeight = weightEl
                ? parseFloat(weightEl.getAttribute('data-unit-weight') || '0')
                : 0;
            const pricingFactor = isPricedByKg ? Math.max(unitWeight, 0) : 1;
            const lineSubtotal = price * quantity * pricingFactor;
            const lineAdjustment = (discountType === 'increase' ? -1 : 1) * unitDiscount * quantity * pricingFactor;
            const lineTotal = Math.max(lineSubtotal - lineAdjustment, 0);

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
                ? 'Có sản phẩm đang có giá bán thấp hơn giá Min. Vui lòng điều chỉnh trước khi lưu đơn.'
                : '';
        }

        return !hasInvalidSellingPrice;
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
        const variantSize = addBtn.dataset.variantSize || '';
        const variantPrice = parseFloat(addBtn.dataset.variantPrice || '0');
        const variantStock = parseInt(addBtn.dataset.variantStock || '0', 10);
        const variantImage = addBtn.dataset.variantImage || 'https://via.placeholder.com/48';
        const variantUnitLabel = addBtn.dataset.variantUnitLabel || 'Cái';
        const variantWeight = parseFloat(addBtn.dataset.variantWeight || '0');
        const variantWeightUnitLabel = addBtn.dataset.variantWeightUnitLabel || 'Kg';
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
                    </div>
                </div>
                <input type="hidden" name="items[${itemIndex}][variant_id]" value="${variantId}">
            </td>
            <td>${variantSize || variantSku}</td>
            <td class="price" data-price="${variantPrice}" data-min-price="${variantMinPrice}">${formatNumber(variantPrice)}đ</td>
            <td>
                <div class="btn-group discount-switch mb-1" role="group" aria-label="Loai chiet khau">
                    <input class="btn-check discount-type-input" type="radio" name="item_discount_type[${variantId}]" id="discount-decrease-${variantId}" value="decrease" checked>
                    <label class="btn btn-outline-secondary" for="discount-decrease-${variantId}">Giảm</label>
                    <input class="btn-check discount-type-input" type="radio" name="item_discount_type[${variantId}]" id="discount-increase-${variantId}" value="increase">
                    <label class="btn btn-outline-secondary" for="discount-increase-${variantId}">Tăng</label>
                </div>
                <input
                    type="number"
                    class="form-control form-control-sm discount-input"
                    name="item_discount[${variantId}]"
                    min="0"
                    step="1000"
                    max="${variantPrice}"
                    value="0">
                <div class="selling-price-feedback"></div>
            </td>
            <td>
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm quantity-input" min="1" max="${variantStock > 0 ? variantStock : ''}" value="1" required>
            </td>
            <td class="text-end">
                <span class="line-weight" data-unit-weight="${variantWeight.toFixed(3)}" data-weight-unit="${variantUnitLabel}" data-display-mode="${variantIsPricedByKg ? 'kg' : 'unit'}">
                    ${variantIsPricedByKg
                        ? formatKg(variantWeight)
                        : `1 ${variantUnitLabel}`}
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
});
</script>
@endpush
