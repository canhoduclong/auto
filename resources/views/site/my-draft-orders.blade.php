@extends(($monitoringEmbedded ?? false) ? 'layouts.embedded' : 'layouts.site')

@section('title', 'Đơn hàng Mẫu')

@push('styles')
<style>
    .drafts-page { padding: 0 0 48px; background: transparent; }
    .drafts-shell { max-width: none; margin: 0; padding: 0; }
    .draft-template-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 15px 14px 10px; border-left: 7px solid #f8fafc; background: #fff; }
    .draft-template-title h1 { display: inline-block; margin: 0; padding-bottom: 6px; border-bottom: 3px solid #f59e0b; font-size: 1rem; font-weight: 900; text-transform: uppercase; }
    .draft-template-create { border-radius: 6px; font-size: .75rem; font-weight: 800; }
    .draft-template-sort { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 18px 4px 12px; }
    .draft-template-sort-actions { display: flex; flex-wrap: wrap; gap: 6px; }
    .draft-template-sort .btn { border-radius: 4px; font-size: .7rem; }
    .draft-workspace { display: grid; grid-template-columns: 230px minmax(0, 1fr); gap: 16px; align-items: start; }
    .draft-customer-sidebar { position: sticky; top: 12px; overflow: hidden; border: 1px solid #dce6f1; border-radius: 8px; background: #fff; box-shadow: 0 5px 16px rgba(15, 23, 42, .05); }
    .draft-customer-sidebar-title { padding: 11px 12px; border-bottom: 1px solid #e5e7eb; color: #0f4770; font-size: .72rem; font-weight: 900; text-transform: uppercase; }
    .draft-customer-sidebar-all, .draft-customer-row-link { display: flex; min-width: 0; flex: 1; align-items: center; justify-content: space-between; gap: 8px; padding: 9px 10px; color: #334155; font-size: .72rem; font-weight: 800; text-decoration: none; }
    .draft-customer-sidebar-all { border-bottom: 1px solid #eef2f7; }
    .draft-customer-sidebar-all:hover, .draft-customer-sidebar-all.is-active, .draft-customer-row-link:hover, .draft-customer-sidebar-item.is-active { background: #eaf5f6; color: #075985; }
    .draft-customer-sidebar-item { display: flex; align-items: stretch; border-bottom: 1px solid #eef2f7; }
    .draft-customer-sidebar-item:last-child { border-bottom: 0; }
    .draft-customer-name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .draft-customer-count { display: inline-flex; min-width: 22px; height: 22px; align-items: center; justify-content: center; border-radius: 50%; background: #64748b; color: #fff; font-size: .65rem; }
    .draft-customer-pin { width: 36px; flex: 0 0 36px; border: 0; border-left: 1px solid #eef2f7; background: #fff; color: #94a3b8; }
    .draft-customer-pin:hover, .draft-customer-pin.is-pinned { color: #d97706; background: #fffbeb; }
    .draft-customer-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 12px 4px 0; }
    .draft-customer-filter .input-group { width: min(100%, 520px); }
    .draft-customer-filter .form-control, .draft-customer-filter .btn { height: 38px; }
    .draft-customer-filter .btn { white-space: nowrap; }
    .draft-customer-filter-result { color: #64748b; font-size: .72rem; }
    .draft-template-list { display: grid; gap: 16px; }
    .draft-template-row { display: grid; grid-template-columns: minmax(0, 1fr) 132px; gap: 14px; align-items: start; }
    .draft-template-card { min-width: 0; padding: 14px 16px; border: 1px solid #dce6f1; border-radius: 7px; background: #fff; box-shadow: 0 5px 16px rgba(15, 23, 42, .06); }
    .draft-template-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
    .draft-template-name { color: #0f172a; font-size: .82rem; font-weight: 900; text-transform: uppercase; }
    .draft-template-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 3px; color: #64748b; font-size: .68rem; }
    .draft-template-status { display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border-radius: 999px; background: #fff1f2; color: #be123c; font-size: .68rem; font-weight: 800; white-space: nowrap; }
    .draft-template-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .draft-template-status.is-confirmed { background: #ecfdf5; color: #047857; }
    .draft-template-status.is-error { background: #fef2f2; color: #b91c1c; }
    .draft-template-section { padding: 9px 0; border-bottom: 1px dashed #dce6f1; }
    .draft-template-section-title { margin-bottom: 6px; color: #334155; font-size: .67rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
    .draft-template-delivery { display: grid; gap: 4px; color: #64748b; font-size: .7rem; }
    .draft-template-products { width: 100%; margin: 0; font-size: .7rem; }
    .draft-template-products th { padding: 6px 4px; border-color: #dce6f1; color: #64748b; font-size: .61rem; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
    .draft-template-products td { padding: 7px 4px; border-color: #edf2f7; vertical-align: middle; }
    .draft-template-thumb { width: 34px; height: 34px; border-radius: 5px; border: 1px solid #e2e8f0; object-fit: cover; }
    .draft-template-product-name { color: #0f172a; font-weight: 800; }
    .draft-template-total { display: grid; grid-template-columns: 95px 105px; justify-content: end; gap: 8px; margin-top: 5px; padding-top: 7px; border-top: 1px solid #dce6f1; font-size: .8rem; font-weight: 900; text-align: right; }
    .draft-template-pagination {
        position: sticky;
        z-index: 24;
        bottom: 0;
        display: flex;
        justify-content: center;
        margin-top: 2px;
        padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
        border: 1px solid #dce6f1;
        border-radius: 9px 9px 0 0;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 -5px 18px rgba(15, 23, 42, .1);
        backdrop-filter: blur(8px);
    }
    .draft-template-pagination nav { width: 100%; }
    .draft-template-pagination .pagination { justify-content: center; margin: 0; }
    .draft-template-actions { display: grid; gap: 8px; }
    .draft-template-actions .btn { display: inline-flex; align-items: center; justify-content: center; gap: 5px; border-radius: 6px; font-size: .72rem; font-weight: 800; }
    .draft-template-editor { padding-top: 10px; }
    .draft-confirm-review { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 10px; padding: 11px 13px; border: 1px solid #86efac; border-radius: 8px; background: #f0fdf4; color: #166534; }
    .draft-confirm-review[hidden] { display: none; }
    .draft-confirm-review-title { font-size: .78rem; font-weight: 900; }
    .draft-confirm-review-help { margin-top: 2px; font-size: .7rem; }
    .draft-template-editor-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .draft-template-editor-grid .is-wide { grid-column: 1 / -1; }
    .draft-template-editor label { margin-bottom: 3px; color: #64748b; font-size: .66rem; font-weight: 800; }
    .draft-edit-delivery { display: grid; grid-template-columns: minmax(0, 2fr) minmax(150px, 1fr) minmax(145px, 1fr); gap: 12px; padding: 10px 0; border-bottom: 1px dashed #dce6f1; }
    .draft-edit-delivery .input-group-text { border: 0; background: transparent; color: #527394; padding-left: 0; }
    .draft-edit-delivery .form-control { border: 0; border-bottom: 1px solid transparent; border-radius: 0; padding-left: 2px; }
    .draft-edit-delivery .form-control:focus { border-bottom-color: #0f766e; box-shadow: none; }
    .draft-edit-extra { display: grid; gap: 10px; padding: 12px 0; border-bottom: 1px dashed #dce6f1; }
    .draft-edit-note { min-height: 76px; resize: vertical; }
    .draft-truck-toggle { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border: 1px solid #bae6d3; border-radius: 8px; background: #f0fdf4; }
    .draft-truck-fields { padding: 12px; border: 1px solid #dce6f1; border-radius: 8px; background: #f8fafc; }
    .draft-truck-fields .is-wide { grid-column: 1 / -1; }
    .draft-truck-fields[hidden] { display: none; }
    .draft-truck-section-title { display: flex; align-items: center; gap: 7px; margin-bottom: 9px; color: #0f4770; font-size: .72rem; font-weight: 900; }
    .draft-truck-section-number { display: inline-flex; width: 21px; height: 21px; align-items: center; justify-content: center; border-radius: 50%; background: #e0f2fe; color: #0369a1; }
    .draft-truck-existing-picker { display: grid; grid-template-columns: minmax(180px, 220px) minmax(0, 1fr); gap: 9px; align-items: stretch; }
    .draft-truck-selected { min-width: 0; padding: 8px 11px; border: 1px solid #bfdbfe; border-radius: 6px; background: #fff; color: #334155; font-size: .7rem; }
    .draft-truck-selected strong { display: block; color: #075985; font-size: .74rem; }
    .draft-truck-selected.is-empty { display: flex; align-items: center; color: #94a3b8; font-style: italic; }
    .draft-truck-divider { display: flex; align-items: center; gap: 10px; margin: 13px 0; color: #94a3b8; font-size: .62rem; font-weight: 900; letter-spacing: .12em; }
    .draft-truck-divider::before, .draft-truck-divider::after { content: ''; flex: 1; border-top: 1px dashed #cbd5e1; }
    .draft-truck-manual-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
    .draft-truck-station-toolbar { display: grid; grid-template-columns: minmax(0, 1fr) 220px; gap: 9px; }
    .draft-truck-station-list { display: grid; gap: 8px; max-height: 430px; margin-top: 12px; overflow-y: auto; }
    .draft-truck-station-option { width: 100%; padding: 10px 12px; border: 1px solid #dce6f1; border-radius: 8px; background: #fff; color: #334155; text-align: left; }
    .draft-truck-station-option:hover, .draft-truck-station-option.is-selected { border-color: #0ea5e9; background: #f0f9ff; }
    .draft-truck-station-option strong { display: block; color: #075985; font-size: .78rem; }
    .draft-truck-station-option span { display: block; margin-top: 2px; color: #64748b; font-size: .69rem; }
    .draft-template-note { margin-top: 5px; padding: 7px 9px; border-left: 3px solid #f59e0b; background: #fffbeb; color: #475569; white-space: pre-line; }
    .draft-edit-product-head { margin-top: 8px; }
    .draft-edit-products { width: 100%; margin: 4px 0 0; font-size: .72rem; }
    .draft-edit-products th { padding: 7px 6px; border-color: #dce6f1; color: #52617a; font-size: .62rem; font-weight: 900; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; }
    .draft-edit-products td { padding: 7px 6px; border-color: #edf2f7; vertical-align: middle; }
    .draft-edit-products .draft-edit-image { width: 54px; height: 54px; border: 1px solid #dce6f1; border-radius: 8px; object-fit: cover; }
    .draft-edit-products .draft-edit-product-name { display: block; color: #0f172a; font-size: .82rem; font-weight: 900; }
    .draft-edit-products .draft-edit-product-variant { display: block; color: #64748b; font-size: .68rem; }
    .draft-edit-products .draft-edit-quantity { width: 78px; min-width: 68px; }
    .draft-price-stepper { display: inline-flex; align-items: center; min-width: 205px; border: 1px solid #cbd5e1; border-radius: 7px; overflow: hidden; }
    .draft-price-stepper .btn { width: 40px; border: 0; border-radius: 0; color: #087f5b; font-size: 1rem; font-weight: 900; }
    .draft-price-stepper .draft-edit-price-value { min-width: 122px; padding: 7px 8px; border-inline: 1px solid #cbd5e1; color: #047857; font-size: .82rem; font-weight: 900; text-align: center; }
    .draft-edit-line-weight { color: #047857; font-size: .85rem; font-weight: 900; white-space: nowrap; }
    .draft-edit-line-total { font-weight: 900; white-space: nowrap; }
    .draft-edit-total { display: grid; grid-template-columns: auto minmax(100px, auto); justify-content: end; gap: 18px; padding: 9px 6px 20px; color: #0f172a; font-size: .8rem; font-weight: 900; text-align: right; }
    .draft-edit-footer { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 8px 4px 0; border-top: 1px solid #dce6f1; }
    .draft-edit-footer-actions { display: flex; flex-wrap: wrap; gap: 10px; }
    .draft-automation { padding-top: 12px; }
    .draft-automation-intro { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 12px; padding: 12px 14px; border: 1px solid #dce6f1; border-radius: 8px; background: #f8fafc; }
    .draft-automation-modes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .draft-automation-mode { position: relative; display: block; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; }
    .draft-automation-mode:has(input:checked) { border-color: #198754; background: #f0fdf4; box-shadow: inset 0 0 0 1px #198754; }
    .draft-automation-mode input { position: absolute; top: 12px; right: 12px; }
    .draft-automation-mode-title { padding-right: 24px; color: #0f172a; font-size: .78rem; font-weight: 900; }
    .draft-automation-mode-help { margin-top: 4px; color: #64748b; font-size: .7rem; line-height: 1.45; }
    .draft-automation-dates { margin-top: 12px; padding: 12px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; }
    .draft-automation-date-row { display: grid; grid-template-columns: minmax(180px, 260px) auto; gap: 8px; margin-bottom: 8px; }
    .draft-automation-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 12px; padding-top: 10px; border-top: 1px solid #dce6f1; }
    .draft-automation-label { display: inline-flex; align-items: center; gap: 4px; color: #0f766e; font-size: .68rem; font-weight: 800; }
    .draft-automation-label.is-off { color: #64748b; }
    .draft-picker { margin-top: 8px; padding: 12px; border: 1px solid #dbe5ef; border-radius: 8px; background: #f8fafc; }
    .draft-picker-search { display: flex; gap: 8px; margin-bottom: 10px; }
    .draft-selected-customer { min-width: 0; color: #334155; font-size: .75rem; font-weight: 700; }
    .draft-product-picker .monitor-product-list { max-height: 430px; overflow-y: auto; }
    .draft-template-empty { padding: 48px 20px; border: 1px solid #dce6f1; border-radius: 8px; background: #fff; color: #64748b; text-align: center; }
    @media (max-width: 767.98px) {
        .draft-template-row { grid-template-columns: 1fr; }
        .draft-workspace { grid-template-columns: 1fr; }
        .draft-customer-sidebar { position: static; max-height: 280px; overflow-y: auto; }
        .draft-template-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .draft-template-sort { align-items: flex-start; flex-direction: column; }
        .draft-customer-filter { align-items: stretch; flex-direction: column; }
        .draft-customer-filter .input-group { width: 100%; }
        .draft-template-products { min-width: 650px; }
        .draft-template-editor-grid { grid-template-columns: 1fr; }
        .draft-template-editor-grid .is-wide { grid-column: auto; }
        .draft-edit-delivery { grid-template-columns: 1fr; }
        .draft-truck-existing-picker, .draft-truck-manual-fields, .draft-truck-station-toolbar { grid-template-columns: 1fr; }
        .draft-truck-manual-fields .is-wide { grid-column: auto; }
        .draft-edit-products { min-width: 820px; }
        .draft-edit-footer { align-items: stretch; flex-direction: column; gap: 10px; }
        .draft-edit-footer-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .draft-automation-modes { grid-template-columns: 1fr; }
        .draft-automation-intro, .draft-automation-footer { flex-direction: column; }
    }
</style>
@endpush

@section('content')
@php
    $variantMap = $variants->keyBy('id');
    $currentSortBy = $sortBy ?? 'created_at';
    $currentSortDir = $sortDir ?? 'desc';
    $sortDirFor = fn (string $field): string => $currentSortBy === $field && $currentSortDir === 'asc' ? 'desc' : 'asc';
    $sortIconFor = fn (string $field): string => $currentSortBy !== $field ? 'fa-sort' : ($currentSortDir === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc');
    $variantCatalog = $variants->map(fn ($variant) => [
        'id' => $variant->id,
        'product_id' => $variant->product_id,
        'label' => ($variant->size ?: ($variant->name ?: $variant->sku)) . ($variant->sku ? ' · ' . $variant->sku : ''),
        'kg' => (float) ($variant->kg ?: $variant->size ?: 1),
        'price' => (float) ($variant->latestPriceRule?->price ?? $variant->price ?? $variant->product?->default_price ?? $variant->product?->price ?? 0),
        'product_name' => $variant->product?->name ?: 'Sản phẩm',
        'image' => $variant->product?->avatar?->media?->file_path ? asset('storage/' . $variant->product->avatar->media->file_path) : '',
    ])->values();
    $draftCustomerIndexUrl = ($monitoringEmbedded ?? false)
        ? route('pages.my_orders.monitoring')
        : route('pages.my_order_drafts');
    $draftCustomerBaseQuery = request()->except(['draft_customer_id', 'page']);
@endphp

<section class="drafts-page">
    <div class="container drafts-shell">
        <div class="draft-template-title">
            <h1>Đơn hàng Mẫu</h1>
            @if($monitoringEmbedded ?? false)
                <form method="POST" action="{{ route('pages.my_order_drafts.store') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success draft-template-create">
                        <i class="bi bi-plus-circle me-1"></i>Tạo mới
                    </button>
                </form>
            @endif
        </div>

        @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger mt-3">{{ $errors->first() }}</div>@endif

        <form class="draft-customer-filter" method="GET" action="{{ ($monitoringEmbedded ?? false) ? route('pages.my_orders.monitoring') : route('pages.my_order_drafts') }}">
            @if($monitoringEmbedded ?? false)<input type="hidden" name="tab" value="drafts">@endif
            <input type="hidden" name="sort_by" value="{{ $currentSortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $currentSortDir }}">
            <div class="input-group" style="width:min(100%, 290px)">
                <span class="input-group-text fw-bold"><i class="bi bi-calendar3 me-1"></i>Ngày lên đơn</span>
                <input type="date" class="form-control" name="draft_date" value="{{ $selectedDraftDate }}" required onchange="this.form.submit()">
            </div>
            <div class="input-group">
                <input type="search"
                       class="form-control"
                       name="customer_search"
                       value="{{ $customerSearch ?? '' }}"
                       autocomplete="off"
                       placeholder="Tìm khách hàng theo tên hoặc số điện thoại..."
                       aria-label="Tìm khách hàng trong đơn mẫu">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Tìm kiếm</button>
                @if(filled($customerSearch ?? ''))
                    <a class="btn btn-outline-secondary" href="{{ ($monitoringEmbedded ?? false) ? route('pages.my_orders.monitoring', ['tab' => 'drafts', 'draft_date' => $selectedDraftDate, 'sort_by' => $currentSortBy, 'sort_dir' => $currentSortDir]) : route('pages.my_order_drafts', ['draft_date' => $selectedDraftDate, 'sort_by' => $currentSortBy, 'sort_dir' => $currentSortDir]) }}" title="Xóa từ khóa tìm kiếm"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
            @if(filled($customerSearch ?? ''))
                <span class="draft-customer-filter-result">Tìm thấy <strong>{{ $drafts->total() }}</strong> đơn mẫu phù hợp.</span>
            @endif
        </form>

        <div class="draft-template-sort">
            <div class="d-flex align-items-center gap-2 small text-muted"><span class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></span><span>Sắp xếp nhanh:</span></div>
            <div class="draft-template-sort-actions">
                @foreach(['created_at' => 'Ngày tạo', 'unit_price' => 'Tổng tiền', 'customer_name' => 'Khách hàng', 'status' => 'Trạng thái'] as $field => $label)
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => $field, 'sort_dir' => $sortDirFor($field)]) }}" class="btn btn-sm btn-outline-secondary">{{ $label }} <i class="fa {{ $sortIconFor($field) }}"></i></a>
                @endforeach
            </div>
        </div>

        @unless($monitoringEmbedded ?? false)
        <div class="draft-workspace">
            <aside class="draft-customer-sidebar" aria-label="Khách hàng có đơn mẫu">
                <div class="draft-customer-sidebar-title"><i class="bi bi-people me-1"></i>Khách hàng đơn mẫu</div>
                <a class="draft-customer-sidebar-all {{ !$selectedDraftCustomerId ? 'is-active' : '' }}" href="{{ $draftCustomerIndexUrl.'?'.http_build_query($draftCustomerBaseQuery) }}#draft-list-start">
                    <span>Tất cả khách hàng</span><span class="draft-customer-count">{{ $draftCustomers->count() }}</span>
                </a>
                <div class="draft-customer-items">
                    @forelse($draftCustomers as $draftCustomer)
                        <div class="draft-customer-sidebar-item {{ $selectedDraftCustomerId === $draftCustomer->id ? 'is-active' : '' }}" data-draft-customer-row data-pinned="{{ $draftCustomer->is_pinned ? '1' : '0' }}">
                            <a class="draft-customer-row-link" href="{{ $draftCustomerIndexUrl.'?'.http_build_query(array_merge($draftCustomerBaseQuery, ['draft_customer_id' => $draftCustomer->id])) }}#draft-list-start" title="Mở đơn mẫu của {{ $draftCustomer->name }}">
                                <span class="draft-customer-name">{{ $draftCustomer->name }}</span>
                                <span class="draft-customer-count">{{ (int) $draftCustomer->drafts_count }}</span>
                            </a>
                            <button type="button" class="draft-customer-pin js-draft-customer-pin {{ $draftCustomer->is_pinned ? 'is-pinned' : '' }}"
                                    data-pin-url="{{ route('pages.my_order_drafts.customers.pin', $draftCustomer) }}"
                                    aria-label="{{ $draftCustomer->is_pinned ? 'Bỏ ghim' : 'Ghim' }} {{ $draftCustomer->name }}"
                                    title="{{ $draftCustomer->is_pinned ? 'Bỏ ghim khách hàng' : 'Ghim khách hàng lên đầu' }}">
                                <i class="bi {{ $draftCustomer->is_pinned ? 'bi-star-fill' : 'bi-star' }}"></i>
                            </button>
                        </div>
                    @empty
                        <div class="p-3 text-muted small">Chưa có khách hàng trong đơn mẫu.</div>
                    @endforelse
                </div>
            </aside>
            <div id="draft-list-start">
        @else
            <div id="draft-list-start">
        @endunless
        <div class="draft-template-list">
            @forelse($drafts as $draft)
                @php
                    $draftItems = collect($draft->parsed_items ?: [[
                        'product_text' => $draft->product_text,
                        'product_variant_id' => $draft->product_variant_id,
                        'quantity' => $draft->quantity,
                        'size_kg' => $draft->size_kg,
                        'unit_price' => $draft->unit_price,
                    ]]);
                    $draftTotal = $draftItems->sum(function ($item) {
                        $quantity = max(0, (float) ($item['quantity'] ?? 0));
                        $weight = max(0.01, (float) ($item['size_kg'] ?? 1));
                        return $quantity * $weight * max(0, (float) ($item['unit_price'] ?? 0));
                    });
                    $selectedSchedule = $draft->automatedSchedules->first();
                    $hasOrderForSelectedDate = (bool) $selectedSchedule?->generated_order_id;
                    $statusText = $hasOrderForSelectedDate
                        ? 'Đã lên đơn '.\Carbon\Carbon::parse($selectedDraftDate)->format('d/m/Y')
                        : ($draft->status === 'error' ? 'Có lỗi' : 'Chưa lên ngày '.\Carbon\Carbon::parse($selectedDraftDate)->format('d/m/Y'));
                    $statusClass = $hasOrderForSelectedDate ? 'is-confirmed' : ($draft->status === 'error' ? 'is-error' : '');
                    $isEditingDraft = (int) request('edit') === (int) $draft->id;
                    $usesTruckStation = (bool) $draft->use_truck_station
                        || filled($draft->truck_station_id)
                        || filled($draft->truck_brand_name)
                        || filled($draft->truck_station_name)
                        || filled($draft->truck_station_address);
                    $selectedTruckStation = $draft->truck_station_id ? $truckStations->firstWhere('id', (int) $draft->truck_station_id) : null;
                @endphp
                <article class="draft-template-row" data-draft-card data-draft-id="{{ $draft->id }}" data-draft-editable="1">
                    <div class="draft-template-card">
                        <div class="draft-template-head">
                            <div>
                                <div class="draft-template-name">{{ $draft->customer_name ?: ($draft->customer?->name ?: 'Chưa chọn khách hàng') }}</div>
                                <div class="draft-template-meta">
                                    <span>{{ $draft->created_at?->format('d/m/Y H:i') }}</span>
                                    @if($draft->phone)<span><i class="bi bi-telephone me-1"></i>{{ $draft->phone }}</span>@endif
                                    <span>MẪU-{{ $draft->id }}</span>
                                    @if($draft->automation_mode)
                                        <span class="draft-automation-label {{ $draft->automation_enabled ? '' : 'is-off' }}">
                                            <i class="bi bi-power"></i>
                                            {{ $draft->automation_mode === \App\Models\TextOrderDraft::AUTOMATION_DAILY ? 'Tự động hằng ngày' : 'Theo lịch' }}
                                            · {{ $draft->automation_enabled ? 'Đang bật' : 'Đang tắt' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <span class="draft-template-status {{ $statusClass }}">{{ $statusText }}</span>
                        </div>

                        <div class="collapse {{ $isEditingDraft ? '' : 'show' }} draft-template-details" id="draftDetails{{ $draft->id }}">
                            <div class="draft-template-section">
                                <div class="draft-template-section-title">Giao hàng</div>
                                <div class="draft-template-delivery">
                                    <span><i class="bi bi-calendar3 me-1"></i>Ngày đang kiểm tra: {{ \Carbon\Carbon::parse($selectedDraftDate)->format('d/m/Y') }}</span>
                                    @if($hasOrderForSelectedDate)
                                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>Đã tạo đơn {{ $selectedSchedule->generatedOrder?->code ?: '#'.$selectedSchedule->generated_order_id }}</span>
                                    @endif
                                    <span><i class="bi bi-geo-alt me-1"></i>Địa chỉ nhận hàng: {{ $draft->address ?: 'Chưa cập nhật' }}</span>
                                    <span><i class="bi bi-clock me-1"></i>Giờ giao: {{ $draft->delivery_time ?: 'Chưa cập nhật' }}</span>
                                    @if($usesTruckStation)
                                        <span><i class="bi bi-truck me-1"></i>Nhà xe: {{ $draft->truck_brand_name ?: ($draft->truckStation?->brand?->name ?: '—') }}</span>
                                        <span><i class="bi bi-building me-1"></i>Trạm gửi: {{ $draft->truck_station_name ?: ($draft->truckStation?->name ?: '—') }} · {{ $draft->truck_station_address ?: ($draft->truckStation?->address ?: 'Chưa cập nhật địa chỉ') }}</span>
                                        @if($draft->truck_station_phone || $draft->truck_receive_time)
                                            <span><i class="bi bi-telephone me-1"></i>{{ $draft->truck_station_phone ?: ($draft->truckStation?->phone ?: '—') }} · Giờ nhận: {{ $draft->truck_receive_time ?: 'Chưa cập nhật' }}</span>
                                        @endif
                                    @endif
                                </div>
                                @if(trim((string) $draft->note) !== '')
                                    <div class="draft-template-note"><strong>Ghi chú:</strong> {{ $draft->note }}</div>
                                @endif
                            </div>
                            <div class="draft-template-section border-bottom-0 pb-0">
                                <div class="draft-template-section-title">Danh sách sản phẩm</div>
                                <div class="table-responsive">
                                    <table class="table table-sm draft-template-products">
                                        <thead><tr><th>Ảnh</th><th>Sản phẩm</th><th class="text-end">SL</th><th class="text-end">Size</th><th class="text-end">Tổng</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
                                        <tbody>
                                            @foreach($draftItems as $item)
                                                @php
                                                    $itemVariant = $variantMap->get((int) ($item['product_variant_id'] ?? 0));
                                                    $quantity = max(0, (float) ($item['quantity'] ?? 0));
                                                    $weight = max(0.01, (float) ($item['size_kg'] ?? $itemVariant?->effective_kg ?? 1));
                                                    $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
                                                    $lineTotal = $quantity * $weight * $unitPrice;
                                                    $productName = $itemVariant?->product?->name ?? ($item['product_text'] ?? 'Sản phẩm');
                                                    $imagePath = $itemVariant?->product?->avatar?->media?->file_path;
                                                @endphp
                                                <tr>
                                                    <td>@if($imagePath)<img class="draft-template-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $productName }}">@else<i class="bi bi-image text-muted"></i>@endif</td>
                                                    <td><span class="draft-template-product-name">{{ $productName }}</span>@if($itemVariant?->sku)<span class="d-block text-muted">{{ $itemVariant->sku }}</span>@endif</td>
                                                    <td class="text-end fw-bold">{{ number_format($quantity, 0, ',', '.') }}</td>
                                                    <td class="text-end">{{ $itemVariant?->size ?: number_format($weight, 2, '.', '') }}</td>
                                                    <td class="text-end fw-bold">{{ rtrim(rtrim(number_format($quantity * $weight, 3, ',', '.'), '0'), ',') }} kg</td>
                                                    <td class="text-end">{{ number_format($unitPrice, 0, ',', '.') }}đ</td>
                                                    <td class="text-end fw-bold">{{ number_format($lineTotal, 0, ',', '.') }}đ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="draft-template-total"><span>Tổng cộng:</span><strong>{{ number_format($draftTotal, 0, ',', '.') }}đ</strong></div>
                            </div>
                        </div>

                            <div class="collapse draft-template-editor {{ $isEditingDraft ? 'show' : '' }}" id="draftEdit{{ $draft->id }}">
                                <input type="hidden" name="sale_id" value="{{ auth()->id() }}">
                                <input type="hidden" name="customer_id" value="{{ $draft->customer_id }}">
                                <input type="hidden" name="truck_brand_id" value="{{ $draft->truck_brand_id }}">
                                <input type="hidden" name="customer_name" value="{{ $draft->customer_name }}">
                                <input type="hidden" name="phone" value="{{ $draft->phone }}">
                                <input type="hidden" name="delivery_date" value="{{ $selectedDraftDate }}">

                                <div class="draft-confirm-review js-draft-confirm-review" hidden>
                                    <div>
                                        <div class="draft-confirm-review-title"><i class="bi bi-clipboard-check me-1"></i>Kiểm tra đơn trước khi xác nhận</div>
                                        <div class="draft-confirm-review-help">Điều chỉnh khách hàng, sản phẩm và số lượng cho lần lên đơn ngày {{ \Carbon\Carbon::parse($selectedDraftDate)->format('d/m/Y') }}.</div>
                                    </div>
                                    <span class="badge bg-success">Chưa tạo đơn</span>
                                </div>

                                <div class="draft-picker draft-customer-picker" hidden>
                                        <div class="draft-picker-search">
                                            <input type="search" class="form-control form-control-sm draft-customer-search" placeholder="Tìm theo tên, số điện thoại hoặc email...">
                                            <button type="button" class="btn btn-sm btn-primary draft-customer-search-button"><i class="bi bi-search me-1"></i>Tìm</button>
                                        </div>
                                        <div class="draft-customer-results"><div class="text-center text-muted py-3">Nhập từ khóa hoặc xem danh sách khách hàng.</div></div>
                                </div>

                                <div class="draft-template-section-title mt-2">Giao hàng</div>
                                <div class="draft-edit-delivery">
                                    <div class="input-group input-group-sm"><span class="input-group-text"><i class="bi bi-geo-alt me-1"></i>Địa chỉ nhận hàng:</span><input name="address" class="form-control" value="{{ $draft->address }}" placeholder="Chưa cập nhật"></div>
                                    <div class="input-group input-group-sm"><span class="input-group-text"><i class="bi bi-calendar3 me-1"></i>Ngày lên đơn:</span><input class="form-control fw-bold" value="{{ \Carbon\Carbon::parse($selectedDraftDate)->format('d/m/Y') }}" readonly></div>
                                    <div class="input-group input-group-sm"><span class="input-group-text"><i class="bi bi-clock me-1"></i>Giờ giao:</span><input name="delivery_time" class="form-control" value="{{ $draft->delivery_time }}" placeholder="Chưa cập nhật"></div>
                                </div>

                                <div class="draft-edit-extra">
                                    <div>
                                        <label class="form-label" for="draftNote{{ $draft->id }}">Ghi chú đơn hàng</label>
                                        <textarea name="note" id="draftNote{{ $draft->id }}" class="form-control form-control-sm draft-edit-note" maxlength="10000" placeholder="Nhập ghi chú giao hàng, đóng gói hoặc yêu cầu riêng...">{{ $draft->note }}</textarea>
                                    </div>
                                    <div class="draft-truck-toggle">
                                        <div>
                                            <div class="fw-bold small"><i class="bi bi-truck me-1"></i>Gửi hàng qua nhà xe</div>
                                            <div class="text-muted small">Bật để chọn hoặc nhập thông tin trạm xe cần gửi.</div>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input js-draft-use-truck" type="checkbox" role="switch" name="use_truck_station" id="draftUseTruck{{ $draft->id }}" value="1" @checked($usesTruckStation)>
                                            <label class="visually-hidden" for="draftUseTruck{{ $draft->id }}">Gửi hàng qua nhà xe</label>
                                        </div>
                                    </div>
                                    <div class="draft-truck-fields" data-draft-truck-fields @if(!$usesTruckStation) hidden @endif>
                                        <input type="hidden" name="truck_station_id" value="{{ $draft->truck_station_id }}">
                                        <div class="draft-truck-section-title"><span class="draft-truck-section-number">1</span>Chọn nhà xe có sẵn (chọn tên trạm xe)</div>
                                        <div class="draft-truck-existing-picker">
                                            <button type="button" class="btn btn-sm btn-outline-primary js-open-truck-station-modal"><i class="bi bi-search me-1"></i>Chọn tên trạm xe</button>
                                            <div class="draft-truck-selected js-draft-truck-selected {{ $selectedTruckStation ? '' : 'is-empty' }}">
                                                @if($selectedTruckStation)
                                                    <strong>{{ $selectedTruckStation->name }}</strong>
                                                    <span>{{ collect([$selectedTruckStation->brand?->name, $selectedTruckStation->address, $selectedTruckStation->phone])->filter()->join(' · ') }}</span>
                                                @else
                                                    Chưa chọn trạm xe có sẵn
                                                @endif
                                            </div>
                                        </div>

                                        <div class="draft-truck-divider"><span>HOẶC</span></div>
                                        <div class="draft-truck-section-title"><span class="draft-truck-section-number">2</span>Nhập thông tin nhà xe</div>
                                        <div class="draft-truck-manual-fields">
                                        <div>
                                            <label class="form-label">Tên nhà xe</label>
                                            <input name="truck_brand_name" class="form-control form-control-sm js-manual-truck-field" maxlength="255" value="{{ $draft->truck_brand_name ?: ($draft->truckStation?->brand?->name ?? '') }}" placeholder="Ví dụ: Phương Trang">
                                        </div>
                                        <div>
                                            <label class="form-label">Tên trạm / điểm gửi</label>
                                            <input name="truck_station_name" class="form-control form-control-sm js-manual-truck-field" maxlength="255" value="{{ $draft->truck_station_name ?: ($draft->truckStation?->name ?? '') }}" placeholder="Ví dụ: Bến xe Miền Đông">
                                        </div>
                                        <div class="is-wide">
                                            <label class="form-label">Địa chỉ trạm xe</label>
                                            <input name="truck_station_address" class="form-control form-control-sm js-manual-truck-field" maxlength="255" value="{{ $draft->truck_station_address ?: ($draft->truckStation?->address ?? '') }}" placeholder="Địa chỉ nhận hàng của nhà xe">
                                        </div>
                                        <div>
                                            <label class="form-label">Số điện thoại trạm</label>
                                            <input name="truck_station_phone" class="form-control form-control-sm js-manual-truck-field" maxlength="30" value="{{ $draft->truck_station_phone ?: ($draft->truckStation?->phone ?? '') }}">
                                        </div>
                                        <div>
                                            <label class="form-label">Giờ nhà xe nhận hàng</label>
                                            <input name="truck_receive_time" class="form-control form-control-sm" maxlength="255" value="{{ $draft->truck_receive_time }}" placeholder="Ví dụ: trước 17h">
                                        </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="draft-edit-product-head">
                                    <div class="draft-template-section-title mb-0">Danh sách sản phẩm</div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm draft-edit-products">
                                        <thead><tr><th>#</th><th>Ảnh</th><th>Sản phẩm</th><th class="text-center">Size</th><th>SL</th><th class="text-center">Đơn giá</th><th class="text-end">Tổng</th><th class="text-end">Thành tiền</th></tr></thead>
                                        <tbody class="draft-edit-items">
                                        @foreach($draftItems as $itemIndex => $item)
                                            @php
                                                $selectedVariant = $variantMap->get((int) ($item['product_variant_id'] ?? 0));
                                                $selectedProductId = (int) ($selectedVariant?->product_id ?? 0);
                                                $editImagePath = $selectedVariant?->product?->avatar?->media?->file_path;
                                                $editProductName = $selectedVariant?->product?->name ?? ($item['product_text'] ?? 'Chưa chọn sản phẩm');
                                                $editVariantLabel = $selectedVariant?->sku ?: ($selectedVariant?->name ?: 'Chọn biến thể');
                                                $editWeight = max(.01, (float) ($item['size_kg'] ?? $selectedVariant?->effective_kg ?? 1));
                                                $editQuantity = max(1, (int) ($item['quantity'] ?? 1));
                                                $editPrice = max(0, (float) ($item['unit_price'] ?? 0));
                                            @endphp
                                            <tr data-draft-item>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger js-remove-draft-item" title="Xóa sản phẩm" aria-label="Xóa sản phẩm"><i class="bi bi-x"></i></button></td>
                                                <td><img class="draft-edit-image {{ $editImagePath ? '' : 'd-none' }}" src="{{ $editImagePath ? asset('storage/' . $editImagePath) : '' }}" alt=""><i class="bi bi-image text-muted draft-edit-image-placeholder {{ $editImagePath ? 'd-none' : '' }}"></i></td>
                                                <td>
                                                    <span class="draft-edit-product-name">{{ $editProductName }}</span><span class="draft-edit-product-variant">{{ $editVariantLabel }}</span>
                                                    <select class="js-draft-product d-none"><option value="{{ $selectedProductId }}" selected></option></select>
                                                    <select name="item_product_variant_id" class="js-draft-variant d-none" @disabled(!$selectedProductId)><option value="{{ $selectedVariant?->id }}" selected></option></select>
                                                    <input type="hidden" name="item_product_text" value="{{ $item['product_text'] ?? '' }}">
                                                    <input type="hidden" name="item_size_kg" value="{{ $editWeight }}">
                                                </td>
                                                <td class="text-center fw-bold draft-edit-size">{{ $selectedVariant?->size ?: rtrim(rtrim(number_format($editWeight, 3, '.', ''), '0'), '.') }}</td>
                                                <td><input type="number" name="item_quantity" class="form-control form-control-sm draft-edit-quantity" min="1" value="{{ $editQuantity }}"></td>
                                                <td class="text-center"><div class="draft-price-stepper"><button type="button" class="btn btn-sm js-draft-price-decrease">−</button><span class="draft-edit-price-value">{{ number_format($editPrice, 0, ',', '.') }}đ</span><button type="button" class="btn btn-sm js-draft-price-increase">+</button><input type="hidden" name="item_unit_price" value="{{ $editPrice }}"></div></td>
                                                <td class="text-end draft-edit-line-weight">{{ rtrim(rtrim(number_format($editQuantity * $editWeight, 3, ',', '.'), '0'), ',') }}kg</td>
                                                <td class="text-end draft-edit-line-total">{{ number_format($editQuantity * $editWeight * $editPrice, 0, ',', '.') }}đ</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if(!$hasOrderForSelectedDate)
                                    <div class="draft-picker draft-product-picker" hidden>
                                        <div class="draft-picker-search">
                                            <input type="search" class="form-control form-control-sm draft-product-search" placeholder="Tìm sản phẩm, SKU hoặc size...">
                                            <button type="button" class="btn btn-sm btn-primary draft-product-search-button"><i class="bi bi-search me-1"></i>Tìm</button>
                                        </div>
                                        <div class="draft-product-results"><div class="text-center text-muted py-3">Đang tải danh sách sản phẩm...</div></div>
                                    </div>
                                    <div class="draft-edit-total"><span>Tổng cộng:</span><strong class="draft-edit-grand-total">{{ number_format($draftTotal, 0, ',', '.') }}đ</strong></div>
                                    <div class="draft-edit-footer">
                                        <div class="draft-edit-footer-actions">
                                            <button type="button" class="btn btn-sm btn-outline-success js-draft-product-toggle"><i class="bi bi-plus-circle me-1"></i>Thêm sản phẩm</button>
                                            <button type="button" class="btn btn-sm btn-outline-success js-draft-customer-toggle"><i class="bi bi-person-check me-1"></i>Chọn khách hàng</button>
                                        </div>
                                        <div class="draft-edit-footer-actions">
                                            <button type="button" class="btn btn-sm btn-outline-secondary js-cancel-draft-confirm" hidden><i class="bi bi-x-circle me-1"></i>Hủy</button>
                                            <button type="button" class="btn btn-sm btn-outline-success js-save-draft"><i class="bi bi-check2 me-1"></i>Lưu thay đổi</button>
                                            <button type="button" class="btn btn-sm btn-success js-confirm-draft" hidden><i class="bi bi-check2-circle me-1"></i>Xác nhận lên đơn {{ \Carbon\Carbon::parse($selectedDraftDate)->format('d/m') }}</button>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @php
                                $automationDates = collect($draft->automation_dates ?: [now('Asia/Bangkok')->toDateString()]);
                                $automationMode = $draft->automation_mode ?: \App\Models\TextOrderDraft::AUTOMATION_DAILY;
                            @endphp
                            <div class="collapse draft-automation" data-draft-automation>
                                <div class="draft-automation-intro">
                                    <div>
                                        <div class="draft-template-section-title mb-1">Cấu hình lên đơn</div>
                                        <div class="small text-muted">Sản phẩm được sao chép từ đơn mẫu; giá bán được lấy lại theo giá hiện tại vào ngày hệ thống thực hiện.</div>
                                    </div>
                                    <div class="form-check form-switch flex-shrink-0">
                                        <input class="form-check-input js-automation-enabled" type="checkbox" role="switch" id="draftAutomationEnabled{{ $draft->id }}" @checked($draft->automation_enabled)>
                                        <label class="form-check-label fw-bold small" for="draftAutomationEnabled{{ $draft->id }}">Bật tự động</label>
                                    </div>
                                </div>

                                <div class="draft-automation-modes">
                                    <label class="draft-automation-mode">
                                        <input type="radio" class="form-check-input js-automation-mode" name="automation_mode_{{ $draft->id }}" value="daily" @checked($automationMode === \App\Models\TextOrderDraft::AUTOMATION_DAILY)>
                                        <span class="draft-automation-mode-title d-block">1. Tự động lên đơn hằng ngày</span>
                                        <span class="draft-automation-mode-help d-block">Mỗi ngày tạo đúng một đơn mới từ đơn mẫu này.</span>
                                    </label>
                                    <label class="draft-automation-mode">
                                        <input type="radio" class="form-check-input js-automation-mode" name="automation_mode_{{ $draft->id }}" value="scheduled" @checked($automationMode === \App\Models\TextOrderDraft::AUTOMATION_SCHEDULED)>
                                        <span class="draft-automation-mode-title d-block">2. Lên đơn theo lịch</span>
                                        <span class="draft-automation-mode-help d-block">Chỉ tạo đơn vào những ngày được chọn bên dưới.</span>
                                    </label>
                                </div>

                                <div class="draft-automation-dates" @if($automationMode !== \App\Models\TextOrderDraft::AUTOMATION_SCHEDULED) hidden @endif>
                                    <div class="draft-template-section-title">Các ngày lên đơn</div>
                                    <div class="draft-automation-date-list">
                                        @foreach($automationDates as $automationDate)
                                            <div class="draft-automation-date-row">
                                                <input type="date" class="form-control form-control-sm js-automation-date" min="{{ now('Asia/Bangkok')->toDateString() }}" value="{{ $automationDate }}">
                                                <button type="button" class="btn btn-sm btn-outline-danger js-remove-automation-date" title="Xóa ngày"><i class="bi bi-trash"></i></button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary js-add-automation-date"><i class="bi bi-plus-circle me-1"></i>Thêm ngày</button>
                                </div>

                                @if($draft->automation_last_run_at || $draft->automation_last_error)
                                    <div class="small mt-2 {{ $draft->automation_last_error ? 'text-danger' : 'text-muted' }}">
                                        @if($draft->automation_last_error)
                                            <i class="bi bi-exclamation-triangle me-1"></i>{{ $draft->automation_last_error }}
                                        @else
                                            Lần chạy gần nhất: {{ $draft->automation_last_run_at?->format('d/m/Y H:i') }}
                                        @endif
                                    </div>
                                @endif

                                <div class="draft-automation-footer">
                                    <span class="small text-muted">Hai chế độ loại trừ nhau; mỗi đơn mẫu chỉ chọn được một chế độ.</span>
                                    <button type="button" class="btn btn-sm btn-success js-save-draft-automation"><i class="bi bi-calendar2-check me-1"></i>Lưu cấu hình</button>
                                </div>
                            </div>
                    </div>

                    <div class="draft-template-actions">
                        <button type="button" class="btn btn-sm btn-outline-success js-show-draft-editor"><i class="bi bi-pencil"></i>Sửa mẫu</button>
                        <button type="button" class="btn btn-sm btn-outline-info js-show-draft-details"><i class="bi bi-eye"></i>Chi tiết</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-copy-draft"><i class="bi bi-files"></i>Sao chép đơn</button>
                        <button type="button" class="btn btn-sm btn-outline-primary js-show-draft-automation"><i class="bi bi-calendar2-check"></i>Lịch lên đơn</button>
                        @if(!$hasOrderForSelectedDate)
                            <button type="button" class="btn btn-sm btn-success js-review-draft-confirm"><i class="bi bi-check2-circle"></i>Lên đơn {{ \Carbon\Carbon::parse($selectedDraftDate)->format('d/m') }}</button>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-success" disabled><i class="bi bi-check-circle"></i>Đã lên đơn</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-danger js-delete-draft"><i class="bi bi-trash"></i>Xóa đơn</button>
                    </div>
                </article>
            @empty
                <div class="draft-template-empty"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Chưa có đơn hàng mẫu.</div>
            @endforelse
        </div>
        @if($drafts->hasPages())
            <footer class="draft-template-pagination" aria-label="Phân trang danh sách đơn mẫu">
                {{ $drafts->appends(request()->query())->links('pagination::bootstrap-5') }}
            </footer>
        @endif
            </div>
        @unless($monitoringEmbedded ?? false)</div>@endunless
    </div>
</section>

<div class="modal fade" id="draftTruckStationModal" tabindex="-1" aria-labelledby="draftTruckStationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="draftTruckStationModalLabel">Chọn nhà xe / trạm xe</h5>
                    <div class="small text-muted">Tìm kiếm và chọn một trạm xe có sẵn trong hệ thống.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="draft-truck-station-toolbar">
                    <input type="search" class="form-control js-truck-station-search" placeholder="Tìm tên trạm, nhà xe, địa chỉ, số điện thoại...">
                    <select class="form-select js-truck-brand-filter" aria-label="Lọc theo nhà xe">
                        <option value="">Tất cả nhà xe</option>
                        @foreach($truckStations->pluck('brand')->filter()->unique('id')->sortBy('name') as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="draft-truck-station-list">
                    @foreach($truckStations as $station)
                        <button type="button" class="draft-truck-station-option js-select-truck-station"
                            data-station-id="{{ $station->id }}"
                            data-brand-id="{{ $station->brand_id }}"
                            data-brand-name="{{ $station->brand?->name ?? '' }}"
                            data-station-name="{{ $station->name }}"
                            data-address="{{ $station->address ?? '' }}"
                            data-phone="{{ $station->phone ?? '' }}"
                            data-search="{{ mb_strtolower(collect([$station->name, $station->brand?->name, $station->address, $station->phone])->filter()->join(' ')) }}">
                            <strong>{{ $station->name }}</strong>
                            <span>{{ collect([$station->brand?->name, $station->address, $station->phone])->filter()->join(' · ') ?: 'Chưa cập nhật thông tin' }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="js-truck-station-empty text-center text-muted py-4" @if($truckStations->isNotEmpty()) hidden @endif>Không tìm thấy trạm xe phù hợp.</div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary js-use-manual-truck-station"><i class="bi bi-pencil-square me-1"></i>Nhập thông tin nhà xe</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const baseUrl = @json($actionBaseUrl);
    const customerEndpoint = @json(route('site.orders.customers.ajax'));
    const variantEndpoint = @json(route('site.orders.variants.ajax'));
    const variants = @json($variantCatalog);
    const today = @json(now('Asia/Bangkok')->toDateString());
    const truckStationModalElement = document.getElementById('draftTruckStationModal');
    const truckStationModal = truckStationModalElement ? bootstrap.Modal.getOrCreateInstance(truckStationModalElement) : null;
    let activeTruckEditor = null;
    const notify = (message, type = 'success') => typeof window.showToast === 'function' ? window.showToast(message, type) : window.alert(message);
    const money = value => new Intl.NumberFormat('vi-VN').format(Math.round(Number(value) || 0)) + 'đ';
    const quantity = row => Math.max(1, Number.parseInt(row.querySelector('[name="item_quantity"]').value || '1', 10));
    const weight = row => Math.max(.01, Number(row.querySelector('[name="item_size_kg"]').value) || 1);
    const price = row => Math.max(0, Number(row.querySelector('[name="item_unit_price"]').value) || 0);
    const normalizeSearch = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('vi');
    const renderSelectedTruckStation = (editor, station = null) => {
        const summary = editor?.querySelector('.js-draft-truck-selected');
        if (!summary) return;
        summary.replaceChildren();
        summary.classList.toggle('is-empty', !station);
        if (!station) {
            summary.textContent = 'Chưa chọn trạm xe có sẵn';
            return;
        }
        const title = document.createElement('strong');
        title.textContent = station.stationName || 'Trạm xe';
        const meta = document.createElement('span');
        meta.textContent = [station.brandName, station.address, station.phone].filter(Boolean).join(' · ');
        summary.append(title, meta);
    };
    const clearSelectedTruckStation = editor => {
        if (!editor) return;
        editor.querySelector('[name="truck_station_id"]').value = '';
        editor.querySelector('[name="truck_brand_id"]').value = '';
        renderSelectedTruckStation(editor);
    };
    const filterTruckStations = () => {
        if (!truckStationModalElement) return;
        const keyword = normalizeSearch(truckStationModalElement.querySelector('.js-truck-station-search').value.trim());
        const brandId = truckStationModalElement.querySelector('.js-truck-brand-filter').value;
        let visible = 0;
        truckStationModalElement.querySelectorAll('.js-select-truck-station').forEach(option => {
            const matches = (!keyword || normalizeSearch(option.dataset.search).includes(keyword))
                && (!brandId || option.dataset.brandId === brandId);
            option.hidden = !matches;
            if (matches) visible++;
        });
        truckStationModalElement.querySelector('.js-truck-station-empty').hidden = visible > 0;
    };
    const updateDraftTotals = editor => {
        let total = 0;
        editor.querySelectorAll('[data-draft-item]').forEach(row => {
            const lineWeight = quantity(row) * weight(row);
            const lineTotal = lineWeight * price(row);
            row.querySelector('.draft-edit-price-value').textContent = money(price(row));
            row.querySelector('.draft-edit-line-weight').textContent = `${new Intl.NumberFormat('vi-VN', {maximumFractionDigits: 3}).format(lineWeight)}kg`;
            row.querySelector('.draft-edit-line-total').textContent = money(lineTotal);
            total += lineTotal;
        });
        const grandTotal = editor.querySelector('.draft-edit-grand-total');
        if (grandTotal) grandTotal.textContent = money(total);
    };
    const escapeHtml = value => {
        const node = document.createElement('div');
        node.textContent = String(value ?? '');
        return node.innerHTML;
    };
    const populateVariants = (row, productId, selectedId = null) => {
        const select = row.querySelector('.js-draft-variant');
        select.innerHTML = '<option value="">Chọn biến thể</option>';
        variants.filter(variant => Number(variant.product_id) === Number(productId)).forEach(variant => {
            const option = new Option(variant.label, variant.id, false, Number(selectedId) === Number(variant.id));
            select.add(option);
        });
        select.disabled = !productId;
    };
    const resetItemRow = row => {
        row.querySelector('.js-draft-product').value = '';
        populateVariants(row, null);
        row.querySelector('[name="item_quantity"]').value = 1;
        row.querySelector('[name="item_size_kg"]').value = 1;
        row.querySelector('[name="item_unit_price"]').value = 0;
        row.querySelector('[name="item_product_text"]').value = '';
        row.querySelector('.draft-edit-product-name').textContent = 'Chưa chọn sản phẩm';
        row.querySelector('.draft-edit-product-variant').textContent = 'Chọn biến thể';
        row.querySelector('.draft-edit-size').textContent = '—';
        row.querySelector('.draft-edit-image').src = '';
        row.querySelector('.draft-edit-image').classList.add('d-none');
        row.querySelector('.draft-edit-image-placeholder').classList.remove('d-none');
    };
    const loadCustomers = async (editor, page = 1) => {
        const results = editor.querySelector('.draft-customer-results');
        const target = new URL(customerEndpoint, window.location.origin);
        target.searchParams.set('mode', 'single');
        target.searchParams.set('scope', 'my_customers');
        target.searchParams.set('q', editor.querySelector('.draft-customer-search').value.trim());
        target.searchParams.set('per_page', '15');
        target.searchParams.set('page', String(page));
        target.searchParams.set('sort_by', editor.dataset.customerSortBy || 'manual');
        target.searchParams.set('sort_dir', editor.dataset.customerSortDir || 'asc');
        results.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span>Đang tải khách hàng...</div>';
        try {
            const response = await fetch(target, {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}});
            const data = await response.json();
            if (!response.ok || !data.html) throw new Error('Không thể tải danh sách khách hàng.');
            results.innerHTML = data.html;
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger py-2 mb-0">${escapeHtml(error.message)}</div>`;
        }
    };
    const loadProducts = async (editor, url = variantEndpoint) => {
        const results = editor.querySelector('.draft-product-results');
        const target = new URL(url, window.location.origin);
        target.searchParams.set('view', 'products');
        target.searchParams.set('search', editor.querySelector('.draft-product-search').value.trim());
        target.searchParams.set('per_page', editor.dataset.productPerPage || '10');
        target.searchParams.set('page', target.searchParams.get('page') || '1');
        results.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span>Đang tải sản phẩm...</div>';
        try {
            const response = await fetch(target, {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}});
            const data = await response.json();
            if (!response.ok || !data.html) throw new Error('Không thể tải danh sách sản phẩm.');
            results.innerHTML = data.html;
            const selectedIds = new Set(Array.from(editor.querySelectorAll('.js-draft-variant')).map(select => String(select.value)).filter(Boolean));
            results.querySelectorAll('.monitor-variant-option').forEach(button => {
                const selected = selectedIds.has(String(button.dataset.variantId || ''));
                button.classList.toggle('is-selected', selected);
                button.disabled = selected;
                if (selected) button.title = 'Biến thể đã có trong đơn mẫu';
            });
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger py-2 mb-0">${escapeHtml(error.message)}</div>`;
        }
    };
    const addVariantToDraft = (editor, button) => {
        const variantId = String(button.dataset.variantId || '');
        if (!variantId) return;
        if (Array.from(editor.querySelectorAll('.js-draft-variant')).some(select => String(select.value) === variantId)) {
            notify('Biến thể này đã có trong đơn mẫu.', 'error');
            return;
        }
        let row = Array.from(editor.querySelectorAll('[data-draft-item]')).find(item => !item.querySelector('.js-draft-variant').value);
        if (!row) {
            row = editor.querySelector('[data-draft-item]').cloneNode(true);
            resetItemRow(row);
            editor.querySelector('.draft-edit-items').append(row);
        }
        const productId = button.closest('.monitor-product-card')?.dataset.productId || '';
        row.querySelector('.js-draft-product').value = productId;
        populateVariants(row, productId, variantId);
        row.querySelector('[name="item_quantity"]').value = 1;
        row.querySelector('[name="item_size_kg"]').value = Math.max(.01, Number(button.dataset.variantWeight) || 1);
        row.querySelector('[name="item_unit_price"]').value = Math.max(0, Number(button.dataset.variantPrice) || 0);
        row.querySelector('[name="item_product_text"]').value = `${button.dataset.variantName || 'Sản phẩm'} ${button.dataset.variantSize || button.dataset.variantSku || ''}`.trim();
        row.querySelector('.draft-edit-product-name').textContent = button.dataset.variantName || 'Sản phẩm';
        row.querySelector('.draft-edit-product-variant').textContent = button.dataset.variantSku || button.dataset.variantSize || 'Biến thể';
        row.querySelector('.draft-edit-size').textContent = button.dataset.variantSize || button.dataset.variantWeight || '—';
        const image = row.querySelector('.draft-edit-image');
        if (button.dataset.variantImage) {
            image.src = button.dataset.variantImage;
            image.classList.remove('d-none');
            row.querySelector('.draft-edit-image-placeholder').classList.add('d-none');
        }
        updateDraftTotals(editor);
        button.classList.add('is-selected');
        button.disabled = true;
        button.title = 'Biến thể đã có trong đơn mẫu';
    };
    const rowData = card => {
        const editor = card.querySelector('.draft-template-editor');
        const value = name => editor?.querySelector(`[name="${name}"]`)?.value || null;
        return {
            sale_id: value('sale_id'), customer_id: value('customer_id'), customer_name: value('customer_name'), phone: value('phone'), address: value('address'),
            truck_brand_id: value('truck_brand_id'), truck_station_id: value('truck_station_id'), truck_brand_name: value('truck_brand_name'), truck_station_address: value('truck_station_address'),
            use_truck_station: editor?.querySelector('.js-draft-use-truck')?.checked ? 1 : 0,
            truck_station_name: value('truck_station_name'), truck_station_phone: value('truck_station_phone'), truck_receive_time: value('truck_receive_time'),
            delivery_date: value('delivery_date'), delivery_time: value('delivery_time'), note: value('note'),
            items: Array.from(editor?.querySelectorAll('[data-draft-item]') || []).map(item => ({
                product_variant_id: item.querySelector('[name="item_product_variant_id"]')?.value || null,
                quantity: item.querySelector('[name="item_quantity"]')?.value || null,
                size_kg: item.querySelector('[name="item_size_kg"]')?.value || null,
                unit_price: item.querySelector('[name="item_unit_price"]')?.value || null,
                product_text: item.querySelector('[name="item_product_text"]')?.value || null,
            }))
        };
    };
    const request = async (card, suffix, method = 'POST') => {
        const response = await fetch(`${baseUrl}/${card.dataset.draftId}${suffix}`, {
            method,
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(rowData(card)),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Không thể thực hiện thao tác.');
        return payload;
    };
    const setDraftConfirmationReview = (card, enabled) => {
        if (!card) return;
        const editor = card.querySelector('.draft-template-editor');
        const review = editor?.querySelector('.js-draft-confirm-review');
        const confirmButton = editor?.querySelector('.js-confirm-draft');
        const cancelButton = editor?.querySelector('.js-cancel-draft-confirm');
        card.dataset.confirming = enabled ? '1' : '0';
        if (review) review.hidden = !enabled;
        if (confirmButton) confirmButton.hidden = !enabled;
        if (cancelButton) cancelButton.hidden = !enabled;
    };
    const openDraftConfirmationReview = card => {
        if (!card) return;
        card.querySelector('.draft-template-details')?.classList.remove('show');
        card.querySelector('[data-draft-automation]')?.classList.remove('show');
        const editor = card.querySelector('.draft-template-editor');
        editor?.classList.add('show');
        setDraftConfirmationReview(card, true);
        updateDraftTotals(editor);
        editor?.scrollIntoView({behavior: 'smooth', block: 'start'});
        editor?.querySelector('[name="item_quantity"]')?.focus({preventScroll: true});
    };
    document.addEventListener('click', async event => {
        const customerPin = event.target.closest('.js-draft-customer-pin');
        if (customerPin) {
            customerPin.disabled = true;
            try {
                const response = await fetch(customerPin.dataset.pinUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                    body: JSON.stringify({is_pinned: !customerPin.classList.contains('is-pinned')}),
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Không thể cập nhật ghim khách hàng.');
                notify(payload.message || 'Đã cập nhật ghim khách hàng.');
                window.location.reload();
            } catch (error) {
                notify(error.message, 'error');
                customerPin.disabled = false;
            }
            return;
        }

        const openTruckStationModalButton = event.target.closest('.js-open-truck-station-modal');
        if (openTruckStationModalButton) {
            activeTruckEditor = openTruckStationModalButton.closest('.draft-template-editor');
            const selectedId = activeTruckEditor?.querySelector('[name="truck_station_id"]')?.value || '';
            truckStationModalElement.querySelector('.js-truck-station-search').value = '';
            truckStationModalElement.querySelector('.js-truck-brand-filter').value = '';
            truckStationModalElement.querySelectorAll('.js-select-truck-station').forEach(option => {
                option.classList.toggle('is-selected', option.dataset.stationId === selectedId);
            });
            filterTruckStations();
            truckStationModal?.show();
            return;
        }
        const truckStationOption = event.target.closest('.js-select-truck-station');
        if (truckStationOption && activeTruckEditor) {
            const station = truckStationOption.dataset;
            activeTruckEditor.querySelector('[name="truck_station_id"]').value = station.stationId || '';
            activeTruckEditor.querySelector('[name="truck_brand_id"]').value = station.brandId || '';
            activeTruckEditor.querySelector('[name="truck_brand_name"]').value = station.brandName || '';
            activeTruckEditor.querySelector('[name="truck_station_name"]').value = station.stationName || '';
            activeTruckEditor.querySelector('[name="truck_station_address"]').value = station.address || '';
            activeTruckEditor.querySelector('[name="truck_station_phone"]').value = station.phone || '';
            renderSelectedTruckStation(activeTruckEditor, station);
            truckStationModal?.hide();
            return;
        }
        const useManualTruckStationButton = event.target.closest('.js-use-manual-truck-station');
        if (useManualTruckStationButton && activeTruckEditor) {
            clearSelectedTruckStation(activeTruckEditor);
            activeTruckEditor.querySelectorAll('.js-manual-truck-field').forEach(input => input.value = '');
            truckStationModal?.hide();
            activeTruckEditor.querySelector('[name="truck_brand_name"]')?.focus();
            return;
        }
        const reviewConfirmButton = event.target.closest('.js-review-draft-confirm');
        if (reviewConfirmButton) {
            openDraftConfirmationReview(reviewConfirmButton.closest('[data-draft-card]'));
            return;
        }
        const cancelConfirmButton = event.target.closest('.js-cancel-draft-confirm');
        if (cancelConfirmButton) {
            const card = cancelConfirmButton.closest('[data-draft-card]');
            setDraftConfirmationReview(card, false);
            card?.querySelector('.draft-template-editor')?.classList.remove('show');
            card?.querySelector('.draft-template-details')?.classList.add('show');
            return;
        }
        const showEditorButton = event.target.closest('.js-show-draft-editor');
        if (showEditorButton) {
            const card = showEditorButton.closest('[data-draft-card]');
            setDraftConfirmationReview(card, false);
            card?.querySelector('.draft-template-details')?.classList.remove('show');
            card?.querySelector('[data-draft-automation]')?.classList.remove('show');
            card?.querySelector('.draft-template-editor')?.classList.add('show');
            return;
        }
        const showDetailsButton = event.target.closest('.js-show-draft-details');
        if (showDetailsButton) {
            const card = showDetailsButton.closest('[data-draft-card]');
            setDraftConfirmationReview(card, false);
            card?.querySelector('.draft-template-editor')?.classList.remove('show');
            card?.querySelector('[data-draft-automation]')?.classList.remove('show');
            card?.querySelector('.draft-template-details')?.classList.add('show');
            return;
        }
        const showAutomationButton = event.target.closest('.js-show-draft-automation');
        if (showAutomationButton) {
            const card = showAutomationButton.closest('[data-draft-card]');
            setDraftConfirmationReview(card, false);
            card?.querySelector('.draft-template-details')?.classList.remove('show');
            card?.querySelector('.draft-template-editor')?.classList.remove('show');
            card?.querySelector('[data-draft-automation]')?.classList.add('show');
            return;
        }
        const addAutomationDateButton = event.target.closest('.js-add-automation-date');
        if (addAutomationDateButton) {
            const list = addAutomationDateButton.closest('.draft-automation-dates').querySelector('.draft-automation-date-list');
            const row = document.createElement('div');
            row.className = 'draft-automation-date-row';
            row.innerHTML = `<input type="date" class="form-control form-control-sm js-automation-date" min="${today}" value="${today}"><button type="button" class="btn btn-sm btn-outline-danger js-remove-automation-date" title="Xóa ngày"><i class="bi bi-trash"></i></button>`;
            list.append(row);
            return;
        }
        const removeAutomationDateButton = event.target.closest('.js-remove-automation-date');
        if (removeAutomationDateButton) {
            const list = removeAutomationDateButton.closest('.draft-automation-date-list');
            const rows = list.querySelectorAll('.draft-automation-date-row');
            if (rows.length === 1) rows[0].querySelector('.js-automation-date').value = '';
            else removeAutomationDateButton.closest('.draft-automation-date-row').remove();
            return;
        }
        const customerToggle = event.target.closest('.js-draft-customer-toggle');
        if (customerToggle) {
            const editor = customerToggle.closest('[data-draft-card]')?.querySelector('.draft-template-editor');
            if (!editor) return;
            const picker = editor.querySelector('.draft-customer-picker');
            picker.hidden = !picker.hidden;
            if (!picker.hidden) loadCustomers(editor);
            return;
        }
        const customerSearch = event.target.closest('.draft-customer-search-button');
        if (customerSearch) {
            loadCustomers(customerSearch.closest('.draft-template-editor'));
            return;
        }
        const customerPage = event.target.closest('.draft-customer-results .customer-page-btn');
        if (customerPage && !customerPage.disabled) {
            loadCustomers(customerPage.closest('.draft-template-editor'), Number(customerPage.dataset.page) || 1);
            return;
        }
        const customerSort = event.target.closest('.draft-customer-results .customer-sort-link');
        if (customerSort) {
            event.preventDefault();
            const editor = customerSort.closest('.draft-template-editor');
            editor.dataset.customerSortBy = customerSort.dataset.sortBy || 'manual';
            editor.dataset.customerSortDir = customerSort.dataset.sortDir || 'asc';
            loadCustomers(editor);
            return;
        }
        const customerButton = event.target.closest('.draft-customer-results .select-customer-btn');
        if (customerButton) {
            const editor = customerButton.closest('.draft-template-editor');
            editor.querySelector('[name="customer_id"]').value = customerButton.dataset.customerId || '';
            editor.querySelector('[name="customer_name"]').value = customerButton.dataset.customerName || '';
            editor.querySelector('[name="phone"]').value = customerButton.dataset.customerPhone || '';
            editor.querySelector('[name="address"]').value = customerButton.dataset.customerAddress || '';
            const card = editor.closest('[data-draft-card]');
            card.querySelector('.draft-template-name').textContent = customerButton.dataset.customerName || 'Chưa chọn khách hàng';
            const selectedCustomer = editor.querySelector('.draft-selected-customer');
            if (selectedCustomer) selectedCustomer.textContent = [customerButton.dataset.customerName, customerButton.dataset.customerPhone].filter(Boolean).join(' · ');
            const phoneMeta = card.querySelector('.draft-template-meta .draft-customer-phone');
            if (phoneMeta) phoneMeta.textContent = customerButton.dataset.customerPhone || '';
            editor.querySelector('.draft-customer-picker').hidden = true;
            return;
        }
        const productToggle = event.target.closest('.js-draft-product-toggle');
        if (productToggle) {
            const editor = productToggle.closest('.draft-template-editor');
            const picker = editor.querySelector('.draft-product-picker');
            picker.hidden = !picker.hidden;
            if (!picker.hidden) loadProducts(editor);
            return;
        }
        const productSearch = event.target.closest('.draft-product-search-button');
        if (productSearch) {
            loadProducts(productSearch.closest('.draft-template-editor'));
            return;
        }
        const productChoice = event.target.closest('.draft-product-results .monitor-product-choice');
        if (productChoice) {
            const card = productChoice.closest('.monitor-product-card');
            const variantsPanel = card.querySelector('.monitor-product-variants');
            const willOpen = variantsPanel.hidden;
            const results = productChoice.closest('.draft-product-results');
            results.querySelectorAll('.monitor-product-card.is-open').forEach(openCard => {
                if (openCard !== card) {
                    openCard.classList.remove('is-open');
                    openCard.querySelector('.monitor-product-choice')?.setAttribute('aria-expanded', 'false');
                    openCard.querySelector('.monitor-product-variants').hidden = true;
                }
            });
            card.classList.toggle('is-open', willOpen);
            variantsPanel.hidden = !willOpen;
            productChoice.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            return;
        }
        const productPage = event.target.closest('.draft-product-results .pagination a');
        if (productPage) {
            event.preventDefault();
            loadProducts(productPage.closest('.draft-template-editor'), productPage.href);
            return;
        }
        const variantButton = event.target.closest('.draft-product-results .monitor-variant-option');
        if (variantButton && !variantButton.disabled) {
            addVariantToDraft(variantButton.closest('.draft-template-editor'), variantButton);
            return;
        }
        const removeItemButton = event.target.closest('.js-remove-draft-item');
        if (removeItemButton) {
            const editor = removeItemButton.closest('.draft-template-editor');
            const rows = editor.querySelectorAll('[data-draft-item]');
            const removedVariantId = removeItemButton.closest('[data-draft-item]').querySelector('.js-draft-variant').value;
            if (rows.length === 1) resetItemRow(rows[0]);
            else removeItemButton.closest('[data-draft-item]').remove();
            if (removedVariantId) {
                const pickerButton = editor.querySelector(`.draft-product-results .monitor-variant-option[data-variant-id="${CSS.escape(removedVariantId)}"]`);
                if (pickerButton) {
                    pickerButton.disabled = false;
                    pickerButton.classList.remove('is-selected');
                    pickerButton.removeAttribute('title');
                }
            }
            updateDraftTotals(editor);
            return;
        }
        const priceButton = event.target.closest('.js-draft-price-decrease, .js-draft-price-increase');
        if (priceButton) {
            const row = priceButton.closest('[data-draft-item]');
            const input = row.querySelector('[name="item_unit_price"]');
            const step = priceButton.classList.contains('js-draft-price-increase') ? 1000 : -1000;
            input.value = Math.max(0, price(row) + step);
            updateDraftTotals(priceButton.closest('.draft-template-editor'));
            return;
        }
        const deleteButton = event.target.closest('.js-delete-draft');
        if (deleteButton) {
            const card = deleteButton.closest('[data-draft-card]');
            if (!card || !confirm('Bạn chắc chắn muốn xóa đơn mẫu này? Thao tác này không thể hoàn tác.')) return;
            deleteButton.disabled = true;
            try {
                const payload = await request(card, '', 'DELETE');
                notify(payload.message || 'Đã xóa đơn mẫu.');
                card.remove();
                const list = document.querySelector('.draft-template-list');
                if (list && !list.querySelector('[data-draft-card]')) {
                    list.innerHTML = '<div class="draft-template-empty"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Chưa có đơn hàng mẫu.</div>';
                }
            } catch (error) {
                notify(error.message, 'error');
                deleteButton.disabled = false;
            }
            return;
        }
        const automationSaveButton = event.target.closest('.js-save-draft-automation');
        if (automationSaveButton) {
            const card = automationSaveButton.closest('[data-draft-card]');
            const panel = card.querySelector('[data-draft-automation]');
            const mode = panel.querySelector('.js-automation-mode:checked')?.value || 'daily';
            const dates = Array.from(panel.querySelectorAll('.js-automation-date')).map(input => input.value).filter(Boolean);
            if (mode === 'scheduled' && dates.length === 0) {
                notify('Hãy chọn ít nhất một ngày lên đơn.', 'error');
                return;
            }
            automationSaveButton.disabled = true;
            try {
                if (card.dataset.draftEditable === '1') await request(card, '', 'PUT');
                const response = await fetch(`${baseUrl}/${card.dataset.draftId}/automation`, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                    body: JSON.stringify({
                        automation_mode: mode,
                        automation_enabled: panel.querySelector('.js-automation-enabled').checked,
                        automation_dates: [...new Set(dates)].sort(),
                    }),
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Không thể lưu lịch lên đơn.');
                notify(payload.message || 'Đã lưu cấu hình lịch lên đơn.');
                window.location.reload();
            } catch (error) {
                notify(error.message, 'error');
                automationSaveButton.disabled = false;
            }
            return;
        }
        const button = event.target.closest('.js-save-draft, .js-copy-draft, .js-confirm-draft');
        if (!button) return;
        const card = button.closest('[data-draft-card]');
        if (!card) return;
        if (button.classList.contains('js-confirm-draft')) {
            const selectedDate = rowData(card).delivery_date;
            if (!selectedDate) {
                notify('Hãy chọn ngày lên đơn.', 'error');
                return;
            }
        }
        button.disabled = true;
        try {
            const payload = button.classList.contains('js-save-draft')
                ? await request(card, '', 'PUT')
                : await request(card, button.classList.contains('js-copy-draft') ? '/copy' : '/confirm');
            notify(payload.message || 'Thao tác thành công.');
            window.location.reload();
        } catch (error) {
            notify(error.message, 'error');
            button.disabled = false;
        }
    });
    document.addEventListener('change', event => {
        const truckToggle = event.target.closest('.js-draft-use-truck');
        if (truckToggle) {
            const fields = truckToggle.closest('.draft-edit-extra')?.querySelector('[data-draft-truck-fields]');
            if (fields) fields.hidden = !truckToggle.checked;
            return;
        }
        const automationMode = event.target.closest('.js-automation-mode');
        if (automationMode) {
            const panel = automationMode.closest('[data-draft-automation]');
            panel.querySelector('.draft-automation-dates').hidden = automationMode.value !== 'scheduled';
            return;
        }
        const perPage = event.target.closest('.draft-product-results #per-page-select');
        if (perPage) {
            const editor = perPage.closest('.draft-template-editor');
            editor.dataset.productPerPage = perPage.value;
            loadProducts(editor);
            return;
        }
        const productSelect = event.target.closest('.js-draft-product');
        if (productSelect) {
            populateVariants(productSelect.closest('[data-draft-item]'), productSelect.value);
            return;
        }
        const variantSelect = event.target.closest('.js-draft-variant');
        if (variantSelect) {
            const row = variantSelect.closest('[data-draft-item]');
            const variant = variants.find(item => Number(item.id) === Number(variantSelect.value));
            if (!variant) return;
            row.querySelector('[name="item_size_kg"]').value = variant.kg;
            row.querySelector('[name="item_unit_price"]').value = variant.price;
            row.querySelector('[name="item_product_text"]').value = `${variant.product_name} ${variant.label}`.trim();
            row.querySelector('.draft-edit-product-name').textContent = variant.product_name;
            row.querySelector('.draft-edit-product-variant').textContent = variant.label;
            row.querySelector('.draft-edit-size').textContent = variant.label.split(' · ')[0];
            if (variant.image) {
                row.querySelector('.draft-edit-image').src = variant.image;
                row.querySelector('.draft-edit-image').classList.remove('d-none');
                row.querySelector('.draft-edit-image-placeholder').classList.add('d-none');
            }
            updateDraftTotals(variantSelect.closest('.draft-template-editor'));
        }
    });
    document.addEventListener('input', event => {
        if (event.target.matches('.js-truck-station-search')) {
            filterTruckStations();
            return;
        }
        if (event.target.matches('.js-manual-truck-field')) {
            clearSelectedTruckStation(event.target.closest('.draft-template-editor'));
            return;
        }
        if (!event.target.matches('.draft-edit-quantity')) return;
        updateDraftTotals(event.target.closest('.draft-template-editor'));
    });
    truckStationModalElement?.querySelector('.js-truck-brand-filter')?.addEventListener('change', filterTruckStations);
    document.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;
        const customerSearch = event.target.closest('.draft-customer-search');
        if (customerSearch) {
            event.preventDefault();
            loadCustomers(customerSearch.closest('.draft-template-editor'));
            return;
        }
        const productSearch = event.target.closest('.draft-product-search');
        if (productSearch) {
            event.preventDefault();
            loadProducts(productSearch.closest('.draft-template-editor'));
        }
    });
});
</script>
@endpush
