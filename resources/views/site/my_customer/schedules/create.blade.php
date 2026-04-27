@extends('layouts.site')

@push('styles')
<style>
    .schedule-create-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .schedule-create-shell {
        max-width: 1240px;
    }
    .table > :not(caption) > * > *{
        padding: .5rem .3rem;
    }
    .schedule-create-hero {
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(15, 118, 110, 0.86));
        color: #f8fafc;
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.14);
        padding: 22px 24px;
        margin-bottom: 18px;
    }
    .schedule-create-panel {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 20px;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
    }
    .schedule-create-panel-body {
        padding: 20px;
    }
    .schedule-create-table {
        margin-bottom: 0;
        vertical-align: middle;
    }
    .schedule-create-table thead th {
        background: #f8fafc;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #334155;
        white-space: nowrap;
    }
    .schedule-product {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 240px;
    }
    .schedule-product img {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid rgba(148, 163, 184, 0.25);
        background: #e2e8f0;
    }
    .schedule-product-meta {
        line-height: 1.2;
    }
    .schedule-product-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #0f172a;
    }
    .schedule-product-sub {
        font-size: 0.74rem;
        color: #64748b;
    }
    .schedule-create-table .form-control.form-control-sm {
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
    .schedule-summary {
        position: sticky;
        top: 20px;
    }
    .schedule-summary-grid {
        display: grid;
        gap: 10px;
        margin-bottom: 14px;
    }
    .schedule-kpi {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        background: #f8fafc;
    }
    .schedule-kpi-label {
        display: block;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .schedule-kpi-value {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }
    .schedule-summary-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
        color: #334155;
        font-size: 0.9rem;
    }
    .schedule-summary-total {
        margin-top: 4px;
        padding-top: 10px;
        border-top: 1px dashed rgba(148, 163, 184, 0.45);
    }
    .schedule-dates-section {
        margin-top: 20px;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.28);
    }
    .schedule-customer-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
    }
    .schedule-customer-item {
        border: 1px solid rgba(148, 163, 184, 0.28);
        background: #f8fafc;
        border-radius: 12px;
        padding: 10px 12px;
    }
    .schedule-customer-item .lbl {
        font-size: .72rem;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: .05em;
    }
    .schedule-customer-item .val {
        font-size: .9rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: 2px;
        word-break: break-word;
    }
    .schedule-date-row {
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr) 128px;
        gap: 10px;
        align-items: center;
        margin-bottom: 10px;
    }
    .schedule-date-index {
        text-align: center;
        border: 1px solid rgba(148, 163, 184, 0.28);
        background: #fff;
        border-radius: 10px;
        font-weight: 800;
        color: #334155;
        padding: 8px 0;
    }
    .schedule-date-hint {
        margin-top: 4px;
        font-size: .78rem;
        color: #64748b;
    }
    .schedule-date-toolbar {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    .schedule-mode-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }
    .schedule-mode-tab {
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 14px;
        background: #fff;
        padding: 12px 14px;
        min-width: 220px;
        cursor: pointer;
        transition: all .18s ease;
    }
    .schedule-mode-tab.active {
        border-color: rgba(15, 118, 110, 0.45);
        background: rgba(15, 118, 110, 0.08);
        box-shadow: inset 0 0 0 1px rgba(15, 118, 110, 0.16);
    }
    .schedule-mode-tab-title {
        font-size: .92rem;
        font-weight: 800;
        color: #0f172a;
    }
    .schedule-mode-tab-sub {
        margin-top: 4px;
        font-size: .8rem;
        color: #64748b;
        line-height: 1.4;
    }
    .schedule-mode-pane {
        display: none;
    }
    .schedule-mode-pane.active {
        display: block;
    }
    .schedule-daily-box {
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 14px;
        background: #f8fafc;
        padding: 16px;
    }
    .schedule-daily-note {
        margin-top: 12px;
        padding: 12px 14px;
        border-radius: 12px;
        background: #eff6ff;
        border: 1px solid rgba(96, 165, 250, 0.28);
        color: #1e3a8a;
        font-size: .86rem;
    }
    @media (max-width: 768px) {
        .schedule-customer-grid {
            grid-template-columns: 1fr;
        }
        .schedule-date-row {
            grid-template-columns: 1fr;
        }
    }
    .empty-schedule-message {
        text-align: center;
        padding: 60px 20px;
        color: #555;
    }
    .empty-schedule-message i {
        font-size: 3rem;
        color: #ccc;
    }
</style>
@endpush

@section('content')
<section class="schedule-create-page">
    <div class="container schedule-create-shell">
        <div class="schedule-create-hero">
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;opacity:.8;">Lên lịch đơn hàng</div>
            <h1 class="h4 mb-1 fw-bold">
                @if($selectedCustomer)
                    Tạo lịch lên đơn cho {{ $selectedCustomer->name }}
                @else
                    Tạo lịch lên đơn tự động
                @endif
            </h1>
            <p class="mb-0" style="opacity:.85;">Thêm sản phẩm, rồi chọn các ngày muốn lên đơn. Hệ thống sẽ tự động tạo một đơn cho mỗi ngày.</p>
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

        <form action="{{ route('my_customer.schedules.store') }}" method="POST" id="schedule-create-form">
            @csrf
            <input type="hidden" name="customer_id" id="customer-id-input" value="{{ $selectedCustomer?->id ?? '' }}">
            <input type="hidden" name="schedule_mode" id="schedule-mode-input" value="{{ old('schedule_mode', 'specific_dates') }}">

            <div class="row g-3">
                <div class="col-lg-8">
                    {{-- Customer Panel --}}
                    <div class="schedule-create-panel mb-3">
                        <div class="schedule-create-panel-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0">Thông tin khách hàng</h2>
                                <span class="text-muted small">Chọn khách hàng từ popup để cập nhật đầy đủ thông tin.</span>
                            </div>

                            <div class="d-flex align-items-stretch gap-2 mb-2">
                                <div id="selected-customer-display" class="form-control d-flex align-items-center justify-content-between flex-grow-1" style="min-height:38px; cursor:default;">
                                    <span id="selected-customer-name" class="{{ $selectedCustomer ? '' : 'd-none' }}">
                                        {{ $selectedCustomer?->name }}
                                        @if($selectedCustomer?->phone)
                                            <small class="text-muted ms-1">{{ $selectedCustomer->phone }}</small>
                                        @endif
                                    </span>
                                    <span id="no-customer-placeholder" class="text-muted {{ $selectedCustomer ? 'd-none' : '' }}">Chưa chọn khách hàng</span>
                                    <button type="button" id="clear-customer-btn" class="btn-close ms-2 {{ $selectedCustomer ? '' : 'd-none' }}" style="font-size:.6rem;" aria-label="Xóa"></button>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm px-3" id="open-customer-picker-btn" title="Chọn khách hàng">
                                    <i class="bi bi-search me-1"></i>Chọn
                                </button>
                            </div>

                            <div class="schedule-customer-grid" id="customer-info-grid">
                                <div class="schedule-customer-item">
                                    <div class="lbl">Tên khách</div>
                                    <div class="val" id="customer-info-name">{{ $selectedCustomer?->name ?: '—' }}</div>
                                </div>
                                <div class="schedule-customer-item">
                                    <div class="lbl">Số điện thoại</div>
                                    <div class="val" id="customer-info-phone">{{ $selectedCustomer?->phone ?: '—' }}</div>
                                </div>
                                <div class="schedule-customer-item">
                                    <div class="lbl">Email</div>
                                    <div class="val" id="customer-info-email">{{ $selectedCustomer?->email ?: '—' }}</div>
                                </div>
                                <div class="schedule-customer-item">
                                    <div class="lbl">Mã khách hàng</div>
                                    <div class="val" id="customer-info-code">{{ $selectedCustomer?->customer_code ?: '—' }}</div>
                                </div>
                                <div class="schedule-customer-item" style="grid-column: 1 / -1;">
                                    <div class="lbl">Công ty / Địa chỉ</div>
                                    <div class="val" id="customer-info-company">
                                        @if($selectedCustomer)
                                            {{ $selectedCustomer->company_name ?: '—' }}
                                            @if($selectedCustomer->address)
                                                <div class="mt-1" style="font-weight:600;color:#334155;">{{ $selectedCustomer->address }}</div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     {{-- Date Selection Panel --}}
                    <div class="schedule-create-panel mb-3">
                        <div class="schedule-create-panel-body">
                            <h2 class="h6 fw-bold mb-2">Chọn cách lên đơn</h2>
                            <p class="text-muted small mb-3">Chỉ chọn 1 trong 2 chế độ: lên đơn mỗi ngày hoặc tạo lịch theo các ngày cụ thể.</p>

                            <div class="schedule-mode-tabs d-flex" role="tablist" aria-label="Chế độ lên đơn">
                                <button type="button" class="schedule-mode-tab" data-mode="daily_auto" aria-pressed="false">
                                    <div class="schedule-mode-tab-title">Lên đơn mỗi ngày</div>
                                    <div class="schedule-mode-tab-sub">Tự chạy hằng ngày từ hôm nay. .</div>
                                </button>
                                <button type="button" class="schedule-mode-tab" data-mode="specific_dates" aria-pressed="false">
                                    <div class="schedule-mode-tab-title">Chọn ngày tự động lên đơn</div>
                                    <div class="schedule-mode-tab-sub">Mỗi ngày hợp lệ sẽ tạo một lịch riêng.</div>
                                </button>
                            </div>

                            <div class="schedule-mode-pane" data-mode-pane="daily_auto">
                                <div class="schedule-daily-box">
                                    <div class="fw-bold mb-2">Lên đơn tự động mỗi ngày</div>
                                    <div class="text-muted small mb-3">Hệ thống sẽ dùng danh sách sản phẩm bên dưới để chạy lệnh mỗi ngày, bắt đầu từ hôm nay.</div>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="approval-required-input" name="approval_required" value="1" {{ old('approval_required') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="approval-required-input">Sale duyệt trước khi xác nhận</label>
                                    </div>

                                    <div class="schedule-daily-note">
                                        Nếu bật <strong>Sale duyệt</strong>: mỗi ngày hệ thống sẽ tạo lịch hôm nay và đưa vào trạng thái cần review để sale xác nhận.<br>
                                        Nếu không bật: hệ thống sẽ tự kiểm tra giá, tồn kho và tạo đơn ngay khi hợp lệ.
                                    </div>
                                </div>
                            </div>

                            <div class="schedule-mode-pane" data-mode-pane="specific_dates">
                                <div id="date-rows-container">
                                    <div class="schedule-date-row" data-row="1">
                                        <div class="schedule-date-index">#1</div>
                                        <div>
                                            <input type="date" class="form-control schedule-date-input" min="{{ date('Y-m-d') }}">
                                            <div class="schedule-date-hint">Chọn ngày lên đơn chính thức</div>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger remove-date-row" disabled>
                                            <i class="bi bi-trash me-1"></i>Xóa
                                        </button>
                                    </div>
                                </div>

                                <div class="schedule-date-toolbar">
                                    <button type="button" id="add-date-row-button" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i>Thêm ngày
                                    </button>
                                    <div class="small text-muted d-flex align-items-center">Tối thiểu 1 dòng ngày, không trùng lặp.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Products Panel --}}
                    <div class="schedule-create-panel mb-3">
                        <div class="schedule-create-panel-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0">Sản phẩm trong lịch</h2>
                                <span class="text-muted small">Thêm hoặc xoá sản phẩm.</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table schedule-create-table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-center">SKU</th>
                                            <th class="text-center">Giá hiện tại</th>
                                            <th class="text-center">Số lượng</th>
                                            <th class="text-center">Tổng cộng</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-items-container"></tbody>
                                </table>
                                <div id="empty-cart-message" class="empty-schedule-message">
                                    <i class="bi bi-inbox"></i>
                                    <p class="mb-0 mt-2">Chưa có sản phẩm nào. Hãy tìm và thêm sản phẩm vào lịch.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Add Products Panel --}}
                    <div class="schedule-create-panel mb-3">
                        <div class="schedule-create-panel-body">
                            <h2 class="h6 fw-bold mb-3">Tìm và thêm sản phẩm</h2>
                            <div class="input-group">
                                <input type="text" id="variant-search" class="form-control" placeholder="Nhập SKU hoặc tên sản phẩm">
                                <button class="btn btn-outline-secondary" type="button" id="variant-search-button">Tìm</button>
                            </div>
                            <div id="variant-search-results" class="mt-3"></div>
                        </div>
                    </div>

                   
                </div>

                {{-- Summary Sidebar --}}
                <div class="col-lg-4">
                    <div class="schedule-create-panel schedule-summary">
                        <div class="schedule-create-panel-body">
                            <h2 class="h6 fw-bold mb-3">Tóm tắt</h2>

                            <div class="schedule-summary-grid">
                                <div class="schedule-kpi">
                                    <span class="schedule-kpi-label">Tạm tính</span>
                                    <span class="schedule-kpi-value" id="summarySubtotal">0đ</span>
                                </div>
                                <div class="schedule-kpi">
                                    <span class="schedule-kpi-label">Số dòng sản phẩm</span>
                                    <span class="schedule-kpi-value" id="summaryLineCount">0</span>
                                </div>
                                <div class="schedule-kpi">
                                    <span class="schedule-kpi-label" id="summaryDateLabel">Số ngày lên đơn</span>
                                    <span class="schedule-kpi-value" id="summaryDateCount">0</span>
                                </div>
                            </div>

                            <div class="schedule-summary-line">
                                <strong>Tổng cộng</strong>
                                <strong id="summaryTotalFooter">0đ</strong>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <a href="{{ $selectedCustomer ? route('my_customer.show', $selectedCustomer) : route('my_customer.schedules.index') }}" class="btn btn-outline-secondary w-50">Quay lại</a>
                                <button type="submit" class="btn btn-primary w-50" id="scheduleSubmitButton">Tạo lịch</button>
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
    const customerIdInput = document.getElementById('customer-id-input');
    const customerPickerModal = document.getElementById('customerPickerModal');
    const customerPickerResults = document.getElementById('customer-picker-results');
    const customerSearchInput = document.getElementById('customer-search-input');
    const customerSortSelect = document.getElementById('customer-sort-select');
    const customerPerPageSelect = document.getElementById('customer-per-page-select');
    const openPickerBtn = document.getElementById('open-customer-picker-btn');
    const clearCustomerBtn = document.getElementById('clear-customer-btn');
    const selectedCustomerName = document.getElementById('selected-customer-name');
    const noCustomerPlaceholder = document.getElementById('no-customer-placeholder');
    const customerInfoName = document.getElementById('customer-info-name');
    const customerInfoPhone = document.getElementById('customer-info-phone');
    const customerInfoEmail = document.getElementById('customer-info-email');
    const customerInfoCode = document.getElementById('customer-info-code');
    const customerInfoCompany = document.getElementById('customer-info-company');

    const cartContainer = document.getElementById('cart-items-container');
    const emptyCartMessage = document.getElementById('empty-cart-message');
    const variantSearchInput = document.getElementById('variant-search');
    const variantSearchButton = document.getElementById('variant-search-button');
    const variantSearchResults = document.getElementById('variant-search-results');
    const dateRowsContainer = document.getElementById('date-rows-container');
    const addDateRowButton = document.getElementById('add-date-row-button');
    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryLineCount = document.getElementById('summaryLineCount');
    const summaryDateLabel = document.getElementById('summaryDateLabel');
    const summaryDateCount = document.getElementById('summaryDateCount');
    const summaryTotalFooter = document.getElementById('summaryTotalFooter');
    const form = document.getElementById('schedule-create-form');
    const scheduleModeInput = document.getElementById('schedule-mode-input');
    const scheduleModeTabs = Array.from(document.querySelectorAll('.schedule-mode-tab'));
    const scheduleModePanes = Array.from(document.querySelectorAll('[data-mode-pane]'));

    let itemIndex = 0;
    let searchTimeout = null;
    let cpSearchTimeout = null;
    let cpCurrentPage = 1;
    let dateRowSeq = dateRowsContainer?.querySelectorAll('.schedule-date-row').length || 0;
    const cpAjaxUrl = '{{ route('site.orders.customers.ajax') }}';

    function formatNumber(num) {
        return new Intl.NumberFormat('vi-VN').format(num);
    }

    function formatMoney(num) {
        return `${formatNumber(Math.max(0, num))}đ`;
    }

    function toNumber(value, fallback = 0) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function fillCustomerInfo(data) {
        const name = data.name || '';
        const phone = data.phone || '';
        const email = data.email || '';
        const code = data.code || '';
        const company = data.company || '';
        const address = data.address || '';

        if (customerInfoName) customerInfoName.textContent = name || '—';
        if (customerInfoPhone) customerInfoPhone.textContent = phone || '—';
        if (customerInfoEmail) customerInfoEmail.textContent = email || '—';
        if (customerInfoCode) customerInfoCode.textContent = code || '—';
        if (customerInfoCompany) {
            customerInfoCompany.textContent = company || '—';
            if (address) {
                const addrEl = document.createElement('div');
                addrEl.className = 'mt-1';
                addrEl.style.fontWeight = '600';
                addrEl.style.color = '#334155';
                addrEl.textContent = address;
                customerInfoCompany.appendChild(addrEl);
            }
        }

        if (selectedCustomerName) {
            selectedCustomerName.textContent = name || 'Khách hàng';
            if (phone) {
                const phoneEl = document.createElement('small');
                phoneEl.className = 'text-muted ms-1';
                phoneEl.textContent = phone;
                selectedCustomerName.appendChild(phoneEl);
            }
            selectedCustomerName.classList.remove('d-none');
        }
        if (noCustomerPlaceholder) noCustomerPlaceholder.classList.add('d-none');
        if (clearCustomerBtn) clearCustomerBtn.classList.remove('d-none');
    }

    function clearCustomerInfo() {
        if (customerIdInput) customerIdInput.value = '';
        if (selectedCustomerName) selectedCustomerName.classList.add('d-none');
        if (noCustomerPlaceholder) noCustomerPlaceholder.classList.remove('d-none');
        if (clearCustomerBtn) clearCustomerBtn.classList.add('d-none');
        if (customerInfoName) customerInfoName.textContent = '—';
        if (customerInfoPhone) customerInfoPhone.textContent = '—';
        if (customerInfoEmail) customerInfoEmail.textContent = '—';
        if (customerInfoCode) customerInfoCode.textContent = '—';
        if (customerInfoCompany) customerInfoCompany.textContent = '—';
    }

    function loadCustomers(page) {
        cpCurrentPage = page || 1;
        const sortParts = (customerSortSelect?.value || 'name|asc').split('|');
        const sortBy = sortParts[0] || 'name';
        const sortDir = sortParts[1] || 'asc';

        if (customerPickerResults) {
            customerPickerResults.innerHTML = '<div class="text-center text-muted py-4">Đang tải...</div>';
        }

        const params = new URLSearchParams({
            q: customerSearchInput?.value?.trim() || '',
            per_page: customerPerPageSelect?.value || '15',
            sort_by: sortBy,
            sort_dir: sortDir,
            page: String(cpCurrentPage),
            mode: 'single',
            scope: 'my_customers',
        });

        fetch(`${cpAjaxUrl}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(data => {
                if (customerPickerResults) {
                    customerPickerResults.innerHTML = data.html || '<div class="text-center text-muted py-4">Không có dữ liệu.</div>';
                }
            })
            .catch(() => {
                if (customerPickerResults) {
                    customerPickerResults.innerHTML = '<div class="text-danger text-center py-4">Không tải được danh sách khách hàng.</div>';
                }
            });
    }

    if (openPickerBtn) {
        openPickerBtn.addEventListener('click', function () {
            const modal = bootstrap.Modal.getOrCreateInstance(customerPickerModal);
            modal.show();
            loadCustomers(1);
        });
    }

    if (customerPickerModal) {
        customerPickerModal.addEventListener('shown.bs.modal', function () {
            customerSearchInput?.focus();
        });
    }

    if (customerSearchInput) {
        customerSearchInput.addEventListener('input', function () {
            clearTimeout(cpSearchTimeout);
            cpSearchTimeout = setTimeout(() => loadCustomers(1), 300);
        });
    }

    [customerSortSelect, customerPerPageSelect].forEach(el => {
        el?.addEventListener('change', () => loadCustomers(1));
    });

    if (customerPickerResults) {
        customerPickerResults.addEventListener('click', function (e) {
            const pageBtn = e.target.closest('.customer-page-btn');
            if (pageBtn) {
                e.preventDefault();
                loadCustomers(parseInt(pageBtn.dataset.page || '1', 10));
                return;
            }

            const sortLink = e.target.closest('.customer-sort-link');
            if (sortLink) {
                e.preventDefault();
                if (customerSortSelect) {
                    customerSortSelect.value = `${sortLink.dataset.sortBy}|${sortLink.dataset.sortDir}`;
                }
                loadCustomers(1);
                return;
            }

            const selectBtn = e.target.closest('.select-customer-btn');
            if (selectBtn) {
                e.preventDefault();
                const id = selectBtn.dataset.customerId || '';
                if (customerIdInput) customerIdInput.value = id;
                fillCustomerInfo({
                    name: selectBtn.dataset.customerName || '',
                    phone: selectBtn.dataset.customerPhone || '',
                    email: selectBtn.dataset.customerEmail || '',
                    address: selectBtn.dataset.customerAddress || '',
                    company: selectBtn.dataset.customerCompany || '',
                    code: selectBtn.dataset.customerCode || '',
                });
                bootstrap.Modal.getInstance(customerPickerModal)?.hide();
            }
        });
    }

    if (clearCustomerBtn) {
        clearCustomerBtn.addEventListener('click', function () {
            clearCustomerInfo();
        });
    }

    // Cart Management
    function getCartVariantIds() {
        return Array.from(cartContainer.querySelectorAll('.cart-item-row'))
            .map(row => row.getAttribute('data-variant-id'))
            .filter(Boolean);
    }

    function updateNameIndexes() {
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach((row, idx) => {
            row.querySelectorAll('input[name^="items["]').forEach(input => {
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

    function updateCartTotal() {
        let subtotal = 0;
        Array.from(cartContainer.querySelectorAll('.cart-item-row')).forEach(row => {
            const priceEl = row.querySelector('.price');
            const qtyInput = row.querySelector('.quantity-input');
            const rowTotalEl = row.querySelector('.row-total');
            const price = toNumber(priceEl?.getAttribute('data-price'), 0);
            const quantity = Math.max(1, parseInt(qtyInput?.value || '0', 10));
            const lineTotal = price * quantity;

            if (rowTotalEl) {
                rowTotalEl.textContent = formatMoney(lineTotal);
            }
            subtotal += lineTotal;
        });

        const lineCount = cartContainer.querySelectorAll('.cart-item-row').length;
        summarySubtotal.textContent = formatMoney(subtotal);
        summaryLineCount.textContent = lineCount;
        summaryTotalFooter.textContent = formatMoney(subtotal);
        updateDateSummary();
    }

    function performVariantSearch(page = 1) {
        const term = (variantSearchInput.value || '').trim();
        if (term.length < 2) {
            variantSearchResults.innerHTML = '';
            return;
        }

        variantSearchResults.innerHTML = '<div class="text-center text-muted py-3">Đang tải...</div>';

        fetch(`{{ route('orders.ajax_variant_search') }}?search=${encodeURIComponent(term)}&page=${page}&exclude_ids=${getCartVariantIds().join(',')}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(payload => {
                variantSearchResults.innerHTML = payload.html || '';
            })
            .catch(() => {
                variantSearchResults.innerHTML = '<div class="text-danger text-center py-3">Không tải được danh sách.</div>';
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
        if (!addBtn) return;
        event.preventDefault();

        const variantId = String(addBtn.dataset.variantId || '');
        if (!variantId || cartContainer.querySelector(`.cart-item-row[data-variant-id="${variantId}"]`)) {
            alert('Biến thể này đã có trong lịch.');
            return;
        }

        const variantName = addBtn.dataset.variantName || 'N/A';
        const variantSku = addBtn.dataset.variantSku || 'N/A';
        const variantPrice = parseFloat(addBtn.dataset.variantPrice || '0');
        const variantStock = parseInt(addBtn.dataset.variantStock || '0', 10);
        const variantImage = addBtn.dataset.variantImage || 'https://via.placeholder.com/48';

        const row = document.createElement('tr');
        row.className = 'cart-item-row';
        row.setAttribute('data-variant-id', variantId);
        row.innerHTML = `
            <td>
                <div class="schedule-product">
                    <img src="${variantImage}" alt="${variantName}">
                    <div class="schedule-product-meta">
                        <div class="schedule-product-title">${variantName}</div>
                        <div class="schedule-product-sub">${variantSku}</div>
                    </div>
                </div>
                <input type="hidden" name="items[${itemIndex}][variant_id]" value="${variantId}">
            </td>
            <td class="text-center">${variantSku}</td>
            <td class="text-center price" data-price="${variantPrice}">${formatMoney(variantPrice)}</td>
            <td class="text-center">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm quantity-input min50" min="1" max="${variantStock > 0 ? variantStock : ''}" value="1" required>
            </td>
            <td class="text-center row-total">${formatMoney(variantPrice)}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-cart-item">&times;</button>
            </td>
        `;

        cartContainer.appendChild(row);
        itemIndex++;
        updateNameIndexes();
        updateCartTotal();
        updateCartVisibility();
        performVariantSearch(1);
    });

    cartContainer.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('.remove-cart-item');
        if (!removeBtn) return;
        removeBtn.closest('.cart-item-row')?.remove();
        updateNameIndexes();
        updateCartTotal();
        updateCartVisibility();
    });

    cartContainer.addEventListener('input', function (event) {
        if (event.target.classList.contains('quantity-input')) {
            const quantity = parseInt(event.target.value || '1', 10);
            if (Number.isNaN(quantity) || quantity < 1) {
                event.target.value = '1';
            }
            updateCartTotal();
        }
    });

    // Date rows selection
    function renumberDateRows() {
        const rows = Array.from(dateRowsContainer.querySelectorAll('.schedule-date-row'));
        rows.forEach((row, idx) => {
            const indexEl = row.querySelector('.schedule-date-index');
            if (indexEl) indexEl.textContent = `#${idx + 1}`;
            row.setAttribute('data-row', String(idx + 1));
            const removeBtn = row.querySelector('.remove-date-row');
            if (removeBtn) {
                removeBtn.disabled = rows.length === 1;
            }
        });
    }

    function getUniqueSelectedDates() {
        const values = Array.from(dateRowsContainer.querySelectorAll('.schedule-date-input'))
            .map(input => (input.value || '').trim())
            .filter(Boolean);
        return Array.from(new Set(values)).sort();
    }

    function syncScheduleDateInputs() {
        form.querySelectorAll('input[name="schedule_dates[]"]').forEach(el => el.remove());
        if ((scheduleModeInput?.value || 'specific_dates') !== 'specific_dates') {
            return [];
        }
        const dates = getUniqueSelectedDates();
        dates.forEach(date => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'schedule_dates[]';
            hidden.value = date;
            form.appendChild(hidden);
        });
        return dates;
    }

    function updateDateSummary() {
        const currentMode = scheduleModeInput?.value || 'specific_dates';
        if (currentMode !== 'specific_dates') {
            if (summaryDateLabel) summaryDateLabel.textContent = 'Chu kỳ lên đơn';
            summaryDateCount.textContent = 'Mỗi ngày';
            syncScheduleDateInputs();
            return [];
        }

        const dates = syncScheduleDateInputs();
        if (summaryDateLabel) summaryDateLabel.textContent = 'Số ngày lên đơn';
        summaryDateCount.textContent = dates.length;
        return dates;
    }

    function setScheduleMode(mode) {
        const currentMode = mode === 'daily_auto' ? 'daily_auto' : 'specific_dates';
        if (scheduleModeInput) {
            scheduleModeInput.value = currentMode;
        }

        scheduleModeTabs.forEach(tab => {
            const active = tab.dataset.mode === currentMode;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        scheduleModePanes.forEach(pane => {
            pane.classList.toggle('active', pane.dataset.modePane === currentMode);
        });

        updateDateSummary();
    }

    function addDateRow(initialValue = '') {
        dateRowSeq++;
        const row = document.createElement('div');
        row.className = 'schedule-date-row';
        row.setAttribute('data-row', String(dateRowSeq));
        row.innerHTML = `
            <div class="schedule-date-index">#${dateRowSeq}</div>
            <div>
                <input type="date" class="form-control schedule-date-input" min="{{ date('Y-m-d') }}" value="${initialValue}">
                <div class="schedule-date-hint">Chọn ngày lên đơn chính thức</div>
            </div>
            <button type="button" class="btn btn-outline-danger remove-date-row">
                <i class="bi bi-trash me-1"></i>Xóa
            </button>
        `;
        dateRowsContainer.appendChild(row);
        renumberDateRows();
        updateDateSummary();
    }

    addDateRowButton?.addEventListener('click', function () {
        addDateRow('');
    });

    scheduleModeTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            setScheduleMode(tab.dataset.mode || 'specific_dates');
        });
    });

    dateRowsContainer?.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('.remove-date-row');
        if (!removeBtn) return;
        const rows = dateRowsContainer.querySelectorAll('.schedule-date-row');
        if (rows.length <= 1) {
            alert('Cần ít nhất một dòng ngày.');
            return;
        }
        removeBtn.closest('.schedule-date-row')?.remove();
        renumberDateRows();
        updateDateSummary();
    });

    dateRowsContainer?.addEventListener('input', function (event) {
        if (!event.target.classList.contains('schedule-date-input')) return;
        updateDateSummary();
    });

    // Form Validation
    form.addEventListener('submit', function (event) {
        if (!customerIdInput.value) {
            event.preventDefault();
            alert('Vui lòng chọn khách hàng');
            return;
        }
        if (cartContainer.querySelectorAll('.cart-item-row').length === 0) {
            event.preventDefault();
            alert('Vui lòng thêm ít nhất một sản phẩm');
            return;
        }

        const currentMode = scheduleModeInput?.value || 'specific_dates';
        if (currentMode !== 'specific_dates') {
            syncScheduleDateInputs();
            return;
        }

        const allDateValues = Array.from(dateRowsContainer.querySelectorAll('.schedule-date-input'))
            .map(input => (input.value || '').trim())
            .filter(Boolean);
        const uniqueDateValues = Array.from(new Set(allDateValues));

        if (uniqueDateValues.length === 0) {
            event.preventDefault();
            alert('Vui lòng chọn ít nhất một ngày hợp lệ.');
            return;
        }

        if (allDateValues.length !== uniqueDateValues.length) {
            event.preventDefault();
            alert('Các ngày lên đơn đang bị trùng. Vui lòng kiểm tra lại.');
            return;
        }

        syncScheduleDateInputs();
    });

    renumberDateRows();
    setScheduleMode(scheduleModeInput?.value || 'specific_dates');
    updateDateSummary();
    updateCartVisibility();
    updateCartTotal();
});
</script>
@endpush
