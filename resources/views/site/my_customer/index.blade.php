@extends('layouts.site')

@push('styles')
<style>
    .mc-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .mc-shell { 
        margin: 0 auto;
    }
    .mc-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 28px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(21, 34, 56, 0.18);
        position: relative;
        overflow: hidden;
    }
    .mc-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        top: -60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .mc-kpi {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 16px;
        min-height: 100%;
        backdrop-filter: blur(6px);
    }
    .mc-kpi-label {
        font-size: .75rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .68);
        margin-bottom: 6px;
    }
    .mc-kpi-value {
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1;
    }
    .mc-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }
    .mc-filter {
        padding: 22px;
    }
    .mc-customer-card {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: box-shadow 0.2s ease;
    }
    .mc-customer-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    .mc-filter .form-select {
        height: 40px;
        border-radius: 8px;
        border-color: #d8deea;
    }
    .mc-filter .btn {
        height: 48px;
        border-radius: 14px;
        font-weight: 700;
    }
    .mc-section-head {
        padding: 15px 24px;
    }
    .mc-action-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .mc-action-group .btn {
        border-radius: 12px;
        font-weight: 700;
        padding: 9px 14px;
    }
    .mc-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 14px;
    }
    .mc-tab-btn {
        border: 1px solid #dbe4ef;
        background: #fff;
        color: #334155;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: .85rem;
        font-weight: 700;
        cursor: pointer;
        transition: .2s ease;
    }
    .mc-tab-btn:hover {
        border-color: #93c5fd;
        color: #1d4ed8;
    }
    .mc-tab-btn.active {
        border-color: #1d4ed8;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .mc-tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        border-radius: 999px;
        background: rgba(29, 78, 216, 0.1);
        margin-left: 6px;
        padding: 0 7px;
        font-size: .74rem;
    }
    .mc-sort-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .mc-sort-btn {
        border: 1px solid #dbe4ef;
        background: #fff;
        color: #334155;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: .82rem;
        font-weight: 700;
        cursor: pointer;
    }
    .mc-sort-btn.active {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .mc-sort-arrow {
        margin-left: 6px;
        font-size: .85rem;
        color: #64748b;
        vertical-align: middle;
    }
    .mc-table-wrap {
        padding: 0 18px 18px;
    }
    .mc-table {
        min-width: 1060px;
        margin-bottom: 0;
    }
    .mc-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        border-bottom: 1px solid #e8edf5;
        white-space: nowrap;
        padding: 15px 12px;
    }
    .mc-table tbody td {
        padding: 16px 12px;
        border-color: #edf2f7;
        vertical-align: middle;
    }
    .mc-name {
        font-weight: 800;
        color: #1e293b;
    }
    .mc-subtle {
        font-size: .82rem;
        color: #64748b;
    }
    .mc-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 700;
    }
    .mc-status-active {
        background: #ecfdf5;
        color: #047857;
    }
    .mc-status-free {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #86efac;
    }
    .mc-status-ordered {
        background: #f5f3ff;
        color: #6d28d9;
    }
        .mc-priority-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            margin-right: 6px;
            margin-top: 4px;
        }
        .mc-priority-p1 { background: #fee2e2; color: #b91c1c; }
        .mc-priority-p2 { background: #fff7ed; color: #c2410c; }
        .mc-priority-p3 { background: #ecfeff; color: #0f766e; }
        .mc-owner-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            background: #eef2ff;
            color: #3730a3;
            margin-top: 4px;
        }
    .mc-orders-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 700;
        font-size: .8rem;
    }
    .mc-actions {
           
    }
    .mc-actions .btn {
        border-radius: 10px;
        padding: 6px 9px;
        font-size: .8rem;
    }
    .mc-alert-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        padding: 22px;
        margin-bottom: 24px;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.05);
    }
    .mc-alert-title {
        font-size: .95rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: #0f172a;
    }
    .mc-alert-item {
        border-radius: 18px;
        padding: 14px 16px;
        background: #f8fafc;
        margin-bottom: 12px;
    }
    .mc-alert-item:last-child {
        margin-bottom: 0;
    }
    .mc-alert-label {
        color: #475569;
        font-size: .82rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 6px;
        display: block;
    }
    .mc-alert-value {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .mc-alert-meta {
        color: #64748b;
        font-size: .82rem;
    }
    .mc-area-panel {
        padding: 22px;
    }
    .mc-area-title {
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 12px;
        color: #0f172a;
    }
    /* accordion region tree */
    .mc-rt-city {
        border: 1px solid #dbe4ef;
        border-radius: 10px;
        margin-bottom: .35rem;
        overflow: hidden;
    }
    .mc-rt-city-btn {
        width: 100%; text-align: left; border: none; background: #fff;
        padding: .42rem .65rem; font-size: .83rem; color: #334155;
        cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        transition: background .12s;
    }
    .mc-rt-city-btn:hover { background: #f1f5f9; }
    .mc-rt-city-btn.active { background: #eff6ff; color: #1d4ed8; font-weight: 700; }
    .mc-rt-city-btn .mc-rt-arrow { font-size: .65rem; transition: transform .2s; }
    .mc-rt-city-btn.open .mc-rt-arrow { transform: rotate(90deg); }
    .mc-rt-wards {
        display: none;
        padding: 4px 6px 6px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    .mc-rt-wards.open { display: block; }
    .mc-rt-ward-btn {
        display: block; width: 100%; text-align: left; border: none;
        background: transparent; padding: .28rem .55rem;
        font-size: .78rem; color: #475569; cursor: pointer; border-radius: 6px;
        transition: background .1s;
    }
    .mc-rt-ward-btn:hover { background: #dbeafe; color: #1d4ed8; }
    .mc-rt-ward-btn.active { background: #bfdbfe; color: #1e40af; font-weight: 600; }
    .mc-rt-badge {
        display: inline-block; padding: 1px 6px; border-radius: 99px;
        background: #e2e8f0; color: #64748b; font-size: .68rem; margin-left: 4px;
    }
    .area-filter-summary {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        color: #475569;
    }
    .mc-empty {
        padding: 42px 24px 52px;
        text-align: center;
        color: #64748b;
    }
    .mc-mobile-list {
        display: none;
        padding: 0 16px 16px;
    }
    .mc-mobile-card {
        border: 1px solid #e7ecf3;
        border-radius: 22px;
        padding: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    }
    .mc-mobile-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin: 12px 0;
    }
    .mc-mobile-meta span {
        display: block;
        font-size: .74rem;
        color: #64748b;
        margin-bottom: 3px;
    }
    @media (max-width: 991.98px) {
        .mc-hero {
            padding: 22px;
            border-radius: 24px;
        }
        .mc-filter {
            padding: 20px;
        }
    }
    @media (max-width: 767.98px) {
        .mc-page {
            padding: 20px 0 48px;
        }
        .mc-shell {
            padding: 0 12px;
        }
        .mc-hero {
            padding: 18px;
        }
        .mc-kpi-value {
            font-size: 1.35rem;
        }
        .mc-table-wrap {
            display: none;
        }
        .mc-mobile-list {
            display: block;
        }
        .mc-mobile-meta {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@php
    $currentPerPage = (int) request('per_page', 10);
    $activeTab = $activeTab ?? request('tab', 'all');
    if (!in_array($activeTab, ['all', 'processing', 'trash'], true)) {
        $activeTab = 'all';
    }
    $tabCounts = $tabCounts ?? [
        'all' => 0,
        'processing' => 0,
        'trash' => 0,
    ];
    $sortBy = $sortBy ?? request('sort_by');
    $sortDir = $sortDir ?? request('sort_dir', 'asc');
    $pageCustomers = $customers->getCollection();
    $pageOrdersCount = (int) $pageCustomers->sum('orders_count');
    $withPhoneCount = (int) $pageCustomers->filter(fn($customer) => !empty($customer->phone))->count();
@endphp

<section class="mc-page">
    <div class="container mc-shell">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="mc-hero mb-4">
            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-5">
                    <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.65);">Customer Center</div>
                    <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Khách hàng của bạn</h1>
                    <p class="mb-0" style="color:rgba(255,255,255,.82);max-width:520px;">
                        Quản lý tệp khách hàng, truy cập nhanh lịch sử đơn và thực hiện thao tác bán hàng chỉ trong một màn hình.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="mc-kpi">
                                <div class="mc-kpi-label">Tổng khách</div>
                                <div class="mc-kpi-value">{{ number_format($customers->total()) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="mc-kpi">
                                <div class="mc-kpi-label">Trên trang</div>
                                <div class="mc-kpi-value">{{ number_format($customers->count()) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="mc-kpi">
                                <div class="mc-kpi-label">Đơn hàng</div>
                                <div class="mc-kpi-value">{{ number_format($pageOrdersCount) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="mc-kpi">
                                <div class="mc-kpi-label">Có SĐT</div>
                                <div class="mc-kpi-value">{{ number_format($withPhoneCount) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="mc-alert-panel">
                    <div class="mc-alert-title">Chú ý quan trọng</div>
                    <div class="mc-alert-item">
                        <span class="mc-alert-label">Lịch hẹn sắp tới</span>
                        @if($upcomingReminders->isNotEmpty())
                            @foreach($upcomingReminders as $reminder)
                                <div class="mc-alert-value">{{ optional($reminder->customer)->name ?? 'Khách hàng chưa xác định' }}</div>
                                <div class="mc-alert-meta">{{ $reminder->remind_at?->format('d/m/Y H:i') }} - {{ \Illuminate\Support\Str::limit($reminder->title ?? $reminder->note ?? 'Không có nội dung', 80) }}</div>
                                @if(!$loop->last)
                                    <hr class="my-2" style="border-color: rgba(148, 163, 184, .22);">
                                @endif
                            @endforeach
                        @else
                            <div class="mc-alert-value">Không có lịch hẹn mới.</div>
                            <div class="mc-alert-meta">Tạo lịch nhắc hoặc cập nhật tình trạng khách hàng để theo dõi.</div>
                        @endif
                    </div>
                    <div class="mc-alert-item">
                        <span class="mc-alert-label">Ghi chú mới nhất</span>
                        @if($latestCareLog)
                            <div class="mc-alert-value">{{ optional($latestCareLog->customer)->name ?? 'Khách hàng chưa xác định' }}</div>
                            <div class="mc-alert-meta">{{ $latestCareLog->created_at?->format('d/m/Y H:i') }} - {{ \Illuminate\Support\Str::limit($latestCareLog->note, 100) }}</div>
                        @else
                            <div class="mc-alert-value">Chưa có ghi chú chăm sóc.</div>
                            <div class="mc-alert-meta">Hãy thêm ghi chú mới khi tương tác với khách.</div>
                        @endif
                    </div>
                </div>
                <div class="mc-panel mb-4">
                    <div class="mc-area-panel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="mc-area-title mb-0">Khu vực</div>
                            <button class="btn btn-link btn-sm p-0 text-secondary" id="mc-clear-region" style="font-size:.8rem;">Tất cả</button>
                        </div>

                        @if(!empty($locationTree) && $locationTree->isNotEmpty())
                            <div id="mc-region-tree">
                            @foreach($locationTree as $city => $cityData)
                                @php $wardCount = count($cityData['wards']); @endphp
                                <div class="mc-rt-city">
                                    <button class="mc-rt-city-btn{{ request('city') === $city ? ' active open' : '' }}"
                                            data-city="{{ $city }}">
                                        <span>{{ $city }}<span class="mc-rt-badge">{{ $cityData['customer_count'] }}</span></span>
                                        @if($wardCount)<i class="bi bi-chevron-right mc-rt-arrow"></i>@endif
                                    </button>
                                    @if($wardCount)
                                    <div class="mc-rt-wards{{ request('city') === $city ? ' open' : '' }}">
                                        @foreach($cityData['wards'] as $ward => $wardData)
                                        <button class="mc-rt-ward-btn{{ (request('city') === $city && request('ward') === ($ward ?: '')) ? ' active' : '' }}"
                                                data-city="{{ $city }}" data-ward="{{ $ward }}">
                                            {{ $ward ?: 'Chưa rõ phường/xã' }}
                                            <span class="mc-rt-badge">{{ $wardData['customer_count'] }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            @endforeach
                            </div>
                        @else
                            <div class="text-muted small">Chưa có dữ liệu khu vực khách hàng.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="mc-panel mb-4">
                    <div class="mc-filter">
                        <form id="mc-search-form" action="{{ route('pages.my_customer') }}" method="GET" class="row g-2 align-items-end mb-3">
                            <div class="col-lg-6">
                                <label class="form-label small text-uppercase fw-bold text-muted mb-1">Tìm kiếm</label>
                                <input type="text" id="mc-search-input" name="search" class="form-control" placeholder="Tên, email, số điện thoại..." value="{{ $search ?? '' }}">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label small text-uppercase fw-bold text-muted mb-1">Trạng thái</label>
                                <select id="mc-status-filter" name="customer_status_filter" class="form-select">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="free" {{ request('customer_status_filter') === 'free' ? 'selected' : '' }}>Khách tự do</option>
                                    <option value="active" {{ request('customer_status_filter') === 'active' ? 'selected' : '' }}>Đang được chăm sóc</option>
                                    <option value="ordered" {{ request('customer_status_filter') === 'ordered' ? 'selected' : '' }}>Đã đặt đơn</option>
                                </select>
                            </div>
                            <input type="hidden" name="city" value="{{ request('city') }}">
                            <input type="hidden" name="ward" value="{{ request('ward') }}">
                            <input type="hidden" name="street" value="{{ request('street') }}">
                            <input type="hidden" name="tab" value="{{ $activeTab }}">
                            <div class="col-lg-3 col-md-6 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-search"></i> Lọc
                                </button>
                                @if(!empty($search))
                                    <a href="{{ route('pages.my_customer', ['tab' => $activeTab]) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                        <div class="mc-tabs" id="mc-tabs">
                            <button type="button" class="mc-tab-btn{{ $activeTab === 'all' ? ' active' : '' }}" data-tab="all">
                                Tất cả <span class="mc-tab-count" data-count-key="all">{{ (int) ($tabCounts['all'] ?? 0) }}</span>
                            </button>
                            <button type="button" class="mc-tab-btn{{ $activeTab === 'processing' ? ' active' : '' }}" data-tab="processing">
                                Đang lấy <span class="mc-tab-count" data-count-key="processing">{{ (int) ($tabCounts['processing'] ?? 0) }}</span>
                            </button>
                            <button type="button" class="mc-tab-btn{{ $activeTab === 'trash' ? ' active' : '' }}" data-tab="trash">
                                Thùng rác <span class="mc-tab-count" data-count-key="trash">{{ (int) ($tabCounts['trash'] ?? 0) }}</span>
                            </button>
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-9">
                                <label class="form-label small text-uppercase fw-bold text-muted mb-1">Sắp xếp nhanh</label>
                                <div class="mc-sort-row" id="mc-sort-row">
                                    <button type="button" class="mc-sort-btn" data-sort="size">Size <i class="bi bi-arrow-down-up mc-sort-arrow"></i></button>
                                    <button type="button" class="mc-sort-btn" data-sort="production">Sản lượng <i class="bi bi-arrow-down-up mc-sort-arrow"></i></button>
                                    <button type="button" class="mc-sort-btn" data-sort="delivery_time">Giờ giao <i class="bi bi-arrow-down-up mc-sort-arrow"></i></button>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label small text-uppercase fw-bold text-muted mb-1">Hiển thị</label>
                                <select id="perPage" class="form-select">
                                    <option value="10" {{ $currentPerPage === 10 ? 'selected' : '' }}>10 khách hàng</option>
                                    <option value="20" {{ $currentPerPage === 20 ? 'selected' : '' }}>20 khách hàng</option>
                                    <option value="50" {{ $currentPerPage === 50 ? 'selected' : '' }}>50 khách hàng</option>
                                    <option value="100" {{ $currentPerPage === 100 ? 'selected' : '' }}>100 khách hàng</option>
                                </select>
                            </div>
                            
                        </div>
                    </div>
                </div>

        <div class="mc-panel">
            <div class="mc-section-head d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Danh sách khách hàng</h4>
                    <div id="pagination-info" class="text-muted small">
                        Hiển thị {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} của {{ $customers->total() }} khách hàng | Trang {{ $customers->currentPage() }}/{{ $customers->lastPage() }}
                    </div>
                </div>
                <div class="mc-action-group">
                    <a href="{{ route('my_customer.schedules.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-calendar2-check"></i> Lịch lên đơn
                    </a>
                    <button type="button" class="btn btn-outline-secondary ms-1" id="refreshPriorityBtn" title="Làm mới priority">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <a href="{{ route('my_customer.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Thêm mới
                    </a>
                    <a href="{{ route('my_customer.import_form') }}" class="btn btn-info text-white">
                        <i class="bi bi-upload"></i> Nhập danh sách
                    </a>
                    <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display:none;">
                        <i class="bi bi-trash"></i> <span id="bulkDeleteLabel">Đưa vào thùng rác</span>
                    </button>
                </div>
            </div>
        </div>
 
 <div class="mt-3">
            <form id="bulkDeleteForm" action="{{ route('my_customer.bulk_delete') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="_ids" id="bulkDeleteIds">
            </form>

            <div id="customer-list" class="row g-3">
                @foreach($customers as $customer)
                    @php
                        $addressText = $customer->address ?: '';
                        if (!$addressText && $customer->addresses->first()) {
                            $address = $customer->addresses->first();
                            $parts = array_filter([$address->house_number, $address->street, $address->ward, $address->city]);
                            $addressText = implode(', ', $parts);
                        }
                        $myPriority = $customer->priorities->first();
                        $myPriorityLevel = (int) ($myPriority?->priority_level ?? 0);
                        $myPriorityScore = (int) ($myPriority?->care_score ?? 0);
                        $myPriorityExpire = $myPriority?->expire_date?->format('d/m/Y');
                        $ownerName = $customer->currentOwner?->name ?? $customer->assignedTo?->name ?? $customer->user?->name;
                        $isFreeCustomer = (string) $customer->customer_status === 'free' || $customer->isFree();
                        $statusLabel = match((string) $customer->customer_status) {
                            'ordered'  => 'Đã đặt đơn',
                            'free'     => 'Khách tự do',
                            default    => ($isFreeCustomer ? 'Khách tự do' : 'Đang được chăm sóc'),
                        };
                        $statusClass = $isFreeCustomer ? 'mc-status-free' : ((string) $customer->customer_status === 'ordered' ? 'mc-status-ordered' : 'mc-status-active');
                    @endphp
                    <div class="col-12">
                        <div class="mc-customer-card border rounded p-3 bg-white">
                            <div class="row justify-content-between">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start gap-3">
                                        
                                        <div>
                                                                                        <h6 class="mb-1 fw-bold fs-5">{{ $customer->name }}</h6>
                                            @if($customer->updated_at)
                                                <small class="text-muted fst-italic"><i class="bi bi-clock me-1"></i>Cập nhật: {{ $customer->updated_at->format('d/m/Y') }}</small><br>
                                            @endif
                                            @if($customer->type)
                                                <small class="text-muted">Phân loại: {{ $customer->type->name }}</small><br>
                                            @endif
                                            @if($myPriorityLevel > 0)
                                                <span class="{{ $myPriorityLevel === 1 ? 'mc-priority-p1' : ($myPriorityLevel === 2 ? 'mc-priority-p2' : 'mc-priority-p3') }}">
                                                    Ưu tiên: P{{ $myPriorityLevel }}
                                                    @if($myPriorityScore > 0)
                                                        · {{ $myPriorityScore }}đ
                                                    @endif
                                                    @if($myPriorityExpire)
                                                        · HSD {{ $myPriorityExpire }}
                                                    @endif
                                                </span>
                                            @else
                                                <span class=" mc-priority-p3">Ưu tiên: Chưa phân hạng</span>
                                            @endif
                                            @if($ownerName)
                                                <span class="mc-owner">Owner : {{ $ownerName }}</span>
                                            @endif
                                            <span class="mc-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                            <br>
                                            @if($customer->brand)
                                                <small class="text-muted">Brand: {{ $customer->brand }}</small><br>
                                            @endif
                                            
                                            @if($customer->phone)
                                                <small class="fw-bold fs-6"><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</small><br>
                                            @endif
                                            @if($addressText)
                                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $addressText }}</small><br>
                                            @endif
                                            @if($customer->truckRoute)
                                                <div class="mt-2 mb-2">
                                                    <table class="table table-sm table-bordered mb-0" style="font-size:0.92em;">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Nhà xe</th>
                                                                <th>Điện thoại</th>
                                                                <th>Điểm đi</th>
                                                                <th>Giờ đi</th>
                                                                <th>Điểm đến</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>{{ $customer->truckRoute->brand->name ?? '' }}</td>
                                                                <td>{{ $customer->truckRoute->brand->phone ?? '' }}</td>
                                                                <td>{{ $customer->truckRoute->stops[0]->station->name ?? '' }}</td>
                                                                <td>{{ $customer->truckRoute->departure_time ?? '' }}</td>
                                                                <td>{{ $customer->truckRoute->stops[count($customer->truckRoute->stops)-1]->station->name ?? '' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                            @if($customer->email)
                                                <small class="text-muted"><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5 d-flex flex-column justify-content-between">
                                    <div class="row">
                                        <div class="col-12  text-end">
                                            @if($customer->production)
                                                 
                                                    <small class="text-muted">Sản lượng: <strong>{{ $customer->production }}</strong></small>
                                                
                                            @endif
                                            @if($customer->size)
                                                
                                                    <small class="text-muted">Size: <strong>{{ $customer->size }}</strong></small>
                                                
                                            @endif
                                            <small class="text-muted">Mã KH: <strong>{{ $customer->customer_code ?: '#'.$customer->id }}</strong></small>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        @if($customer->delivery_time)
                                            <div class="col-12  text-end">
                                                <small class="text-muted">Giờ giao: {{ $customer->delivery_time }}</small>
                                            </div>
                                        @endif
                                       <div class="text-end">
                                            @if($customer->total_debt)
                                            <div class="text-muted">Công nợ: <strong>{{ number_format($customer->total_debt ?? 0, 0, ',', '.') }} đ</strong></div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-end align-items-center">
                                             <div class="text-end me-3">
                                                <small class="text-muted">Đơn: {{ $customer->orders_count }}</small>
                                            </div>
                                            <div class="text-end me-3 mt-1">
                                             <input type="checkbox" name="ids[]" value="{{ $customer->id }}" class=" customer-checkbox">
                                            </div>
                                             <div class="mc-actions justify-content-end gap-2">
                                                @if($activeTab === 'trash')
                                                    <button type="button" class="btn btn-outline-success btn-sm js-restore-customer" data-id="{{ $customer->id }}" title="Khôi phục"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm js-force-delete-customer" data-id="{{ $customer->id }}" title="Xóa vĩnh viễn"><i class="bi bi-trash-fill"></i></button>
                                                @else
                                                    @if($isFreeCustomer)
                                                        <button type="button" class="btn btn-success btn-sm js-takeover-customer" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}" title="Nhận khách về danh sách của bạn">
                                                            <i class="bi bi-person-plus-fill me-1"></i>Nhận khách
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-outline-info btn-sm" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                                    <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-outline-success btn-sm" title="Lên đơn hàng"><i class="bi bi-file-text"></i></a>
                                                    <a href="{{ route('my_customer.show', ['customer' => $customer, 'tab' => 'payments']) }}" class="btn btn-outline-secondary btn-sm" title="Thanh toán"><i class="bi bi-cash"></i></a>
                                                    <a href="{{ route('my_customer.edit', $customer) }}" class="btn btn-outline-warning btn-sm" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                                    <button type="button" class="btn btn-outline-danger btn-sm js-delete-customer" data-id="{{ $customer->id }}" title="Đưa vào thùng rác"><i class="bi bi-trash"></i></button>
                                                @endif
                                            </div>
                                           
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 d-flex justify-content-center">
                <div id="pagination-links">
                    {{ $customers->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
    
</section>

@endsection

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';
    let currentParams = {
        search: '{{ $search ?? '' }}',
        city: '{{ request('city') }}',
        ward: '{{ request('ward') }}',
        street: '{{ request('street') }}',
        customer_status_filter: '{{ request('customer_status_filter', '') }}',
        tab: '{{ $activeTab }}',
        sort_by: '{{ $sortBy ?? '' }}',
        sort_dir: '{{ $sortDir ?? 'asc' }}',
        per_page: {{ $currentPerPage }},
        page: {{ (int) request('page', 1) }}
    };

    const _mcUrls = {
        show:        "{{ route('my_customer.show', ':id') }}",
        edit:        "{{ route('my_customer.edit', ':id') }}",
        order:       "{{ route('my_customer.order.create', ':id') }}",
        payment:     "{{ route('my_customer.show', ['customer' => ':id', 'tab' => 'payments']) }}",
        destroy:     "{{ route('my_customer.destroy', ':id') }}",
        restore:     "{{ route('my_customer.restore', ':id') }}",
        forceDelete: "{{ route('my_customer.force_delete', ':id') }}",
    };
    function mcUrl(key, id) { return _mcUrls[key].replace(':id', id); }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function updateTabButtons(activeTab, tabCounts) {
        document.querySelectorAll('#mc-tabs .mc-tab-btn').forEach(btn => {
            const isActive = btn.dataset.tab === activeTab;
            btn.classList.toggle('active', isActive);
        });

        if (!tabCounts) return;
        document.querySelectorAll('[data-count-key]').forEach(el => {
            const key = el.dataset.countKey;
            if (Object.prototype.hasOwnProperty.call(tabCounts, key)) {
                el.textContent = String(tabCounts[key] ?? 0);
            }
        });
    }

    function updateSortButtons() {
        document.querySelectorAll('#mc-sort-row .mc-sort-btn').forEach(btn => {
            const field = btn.dataset.sort;
            const isActive = currentParams.sort_by === field;
            btn.classList.toggle('active', isActive);
            const arrowEl = btn.querySelector('.mc-sort-arrow');
            if (!arrowEl) return;
            if (!isActive) {
                arrowEl.className = 'bi bi-arrow-down-up mc-sort-arrow';
            } else {
                arrowEl.className = currentParams.sort_dir === 'desc'
                    ? 'bi bi-sort-down mc-sort-arrow'
                    : 'bi bi-sort-up mc-sort-arrow';
            }
        });
    }

    function updatePaginationInfo(pagination) {
        const info = document.getElementById('pagination-info');
        info.textContent = `Hiển thị ${pagination.from || 0} - ${pagination.to || 0} của ${pagination.total} khách hàng | Trang ${pagination.current_page}/${pagination.last_page}`;
    }

    function updatePagination(pagination) {
        const container = document.getElementById('pagination-links');
        container.innerHTML = pagination.links;
        container.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const url = new URL(this.href);
                const page = url.searchParams.get('page') || 1;
                loadCustomers({ page });
            });
        });
    }

    function actionButtonsHtml(customer) {
        const trashTab = currentParams.tab === 'trash';
        if (trashTab) {
            return `
                <button type="button" class="btn btn-outline-success btn-sm js-restore-customer" data-id="${customer.id}" title="Khôi phục"><i class="bi bi-arrow-counterclockwise"></i></button>
                <button type="button" class="btn btn-outline-danger btn-sm js-force-delete-customer" data-id="${customer.id}" title="Xóa vĩnh viễn"><i class="bi bi-trash-fill"></i></button>
            `;
        }

        return `
            ${customer.is_free_customer ? `<button type="button" class="btn btn-success btn-sm js-takeover-customer" data-id="${customer.id}" data-name="${escapeHtml(customer.name || '')}" title="Nhận khách về danh sách của bạn"><i class="bi bi-person-plus-fill me-1"></i>Nhận khách</button>` : ''}
            <a href="${mcUrl('show', customer.id)}" class="btn btn-outline-info btn-sm" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
            <a href="${mcUrl('order', customer.id)}" class="btn btn-outline-success btn-sm" title="Lên đơn hàng"><i class="bi bi-file-text"></i></a>
            <a href="${mcUrl('payment', customer.id)}" class="btn btn-outline-secondary btn-sm" title="Thanh toán"><i class="bi bi-cash"></i></a>
            <a href="${mcUrl('edit', customer.id)}" class="btn btn-outline-warning btn-sm" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
            <button type="button" class="btn btn-outline-danger btn-sm js-delete-customer" data-id="${customer.id}" title="Đưa vào thùng rác"><i class="bi bi-trash"></i></button>
        `;
    }

    function updateBulkDeleteButton() {
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkDeleteLabel = document.getElementById('bulkDeleteLabel');
        const hiddenInput = document.getElementById('bulkDeleteIds');
        const checkboxes = document.querySelectorAll('.customer-checkbox');
        const selectedIds = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
        hiddenInput.value = selectedIds.join(',');

        if (currentParams.tab === 'trash') {
            bulkDeleteBtn.style.display = 'none';
            return;
        }

        bulkDeleteBtn.style.display = selectedIds.length > 0 ? 'inline-block' : 'none';
        bulkDeleteLabel.textContent = selectedIds.length > 0
            ? `Đưa vào thùng rác (${selectedIds.length})`
            : 'Đưa vào thùng rác';
    }

    function bindRowActions() {
        document.querySelectorAll('.customer-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteButton);
        });

        document.querySelectorAll('.js-delete-customer').forEach(btn => {
            btn.addEventListener('click', async function () {
                if (!confirm('Bạn có chắc chắn muốn đưa khách hàng này vào thùng rác không?')) return;
                const id = this.dataset.id;
                await fetch(mcUrl('destroy', id), {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                loadCustomers({ page: 1 });
            });
        });

        document.querySelectorAll('.js-restore-customer').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.dataset.id;
                await fetch(mcUrl('restore', id), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                loadCustomers({ page: 1 });
            });
        });

        document.querySelectorAll('.js-force-delete-customer').forEach(btn => {
            btn.addEventListener('click', async function () {
                if (!confirm('Xóa vĩnh viễn khách hàng này? Thao tác không thể hoàn tác.')) return;
                const id = this.dataset.id;
                await fetch(mcUrl('forceDelete', id), {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                loadCustomers({ page: 1 });
            });
        });

        document.querySelectorAll('.js-takeover-customer').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.dataset.id;
                const name = this.dataset.name || 'khách hàng này';
                if (!confirm(`Nhận ${name} về danh sách của bạn với Ưu tiên 1?`)) return;
                this.disabled = true;
                const res = await fetch(`/my-customer/${id}/takeover`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    loadCustomers({ page: 1 });
                } else {
                    alert(data.message || 'Không thể nhận khách hàng này.');
                    this.disabled = false;
                }
            });
        });
    }

    function updateCustomerList(customers) {
        const container = document.getElementById('customer-list');
        if (customers.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="mc-empty">
                        <i class="bi bi-inbox" style="font-size:2.6rem;"></i>
                        <h5 class="mt-3 mb-2">${currentParams.tab === 'trash' ? 'Thùng rác trống' : 'Chưa có khách hàng'}</h5>
                        <p class="mb-3">${currentParams.tab === 'trash' ? 'Chưa có khách hàng bị xóa mềm.' : 'Hãy thêm mới hoặc nhập danh sách để bắt đầu quản lý tệp khách hàng.'}</p>
                    </div>
                </div>
            `;
            updateBulkDeleteButton();
            return;
        }

        container.innerHTML = customers.map(customer => {
            const addressText = escapeHtml(customer.address_text || '');
            const name = escapeHtml(customer.name || '');
            const email = escapeHtml(customer.email || '');
            const phone = escapeHtml(customer.phone || '');
            const production = escapeHtml(customer.production || '');
            const size = escapeHtml(customer.size || '');
            const deliveryTime = escapeHtml(customer.delivery_time || '');
            const status = escapeHtml(customer.status || 'active');
            const code = escapeHtml(customer.customer_code || ('#' + customer.id));
            const updatedAt = escapeHtml(customer.updated_at_formatted || '');
            const deletedAt = escapeHtml(customer.deleted_at_formatted || '');
            const typeName = customer.type ? escapeHtml(customer.type.name || '') : '';
            const brand = escapeHtml(customer.brand || '');
            const priorityLevelRaw = customer.my_priority_level;
            const priorityLevel = priorityLevelRaw !== null && priorityLevelRaw !== undefined ? Number(priorityLevelRaw) : 0;
            const priorityScore = Number(customer.my_priority_score || 0);
            const priorityExpire = escapeHtml(customer.my_priority_expire_at || '');
            const ownerName = escapeHtml(customer.current_owner_name || '');
            const priorityClass = priorityLevel === 1 ? 'mc-priority-p1' : (priorityLevel === 2 ? 'mc-priority-p2' : 'mc-priority-p3');
            const priorityLabel = priorityLevel > 0
                ? `Ưu tiên: P${priorityLevel}${priorityScore > 0 ? ` · ${priorityScore}đ` : ''}${priorityExpire ? ` · HSD ${priorityExpire}` : ''}`
                : 'Ưu tiên: Chưa phân hạng';
            const isFreeCustomer = customer.is_free_customer;
            const customerStatusRaw = customer.customer_status || '';
            const statusLabel = isFreeCustomer ? 'Khách tự do' : (customerStatusRaw === 'ordered' ? 'Đã đặt đơn' : 'Đang được chăm sóc');
            const statusClass = isFreeCustomer ? 'mc-status-free' : (customerStatusRaw === 'ordered' ? 'mc-status-ordered' : 'mc-status-active');

            return `
            <div class="col-12">
                <div class="mc-customer-card border rounded p-3 bg-white">
                    <div class="row justify-content-between">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-3">
                                <div>
                                    <div class="mb-1"><span class="fw-bold fs-5 text-uppercase">${name}</span></div>
                                    <div class="mb-2">
                                        ${updatedAt ? `<small class="text-muted fst-italic"><i class="bi bi-clock me-1"></i>Cập nhật: ${updatedAt}</small>` : ''}
                                        <span class=" fst-italic">, Mã KH: <strong>${code}</strong> - Trạng thái: <strong>${status}</strong></span> 
                                        ${deletedAt ? `<small class="text-danger fst-italic"><i class="bi bi-trash me-1"></i>Đã xóa: ${deletedAt}</small>` : ''}
                                        ${brand ? `<small class="text-muted">Brand: ${brand}</small><br>` : ''} 
                                        ${phone ? `<small class="fw-bold fs-6"><i class="bi bi-telephone me-1"></i>${phone}</small><br>` : ''}
                                        ${addressText ? `<small class="text-muted"><i class="bi bi-geo-alt me-1"></i>${addressText}</small><br>` : ''}
                                        ${email ? `<small class="text-muted"><i class="bi bi-envelope me-1"></i>${email}</small><br>` : ''} 
                                       
                                    </div>
                                    <div class="">
                                        <span class="mc-priority-pill ${priorityClass}">${priorityLabel}</span> 
                                        <span class="mc-status-badge ${statusClass}">${statusLabel}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 d-flex flex-column justify-content-between">
                            <div class="row">
                                <div class="col-12 text-end">
                                    ${production ? `<small class="text-muted">Sản lượng: <strong>${production}</strong></small>` : ''}
                                    ${size ? `<small class="text-muted ms-2">Size: <strong>${size}</strong></small>` : ''}
                                </div>
                            </div>
                            <div class="row g-2">
                                ${deliveryTime ? `<div class="col-12 text-end"><small class="text-muted">Giờ giao: ${deliveryTime}</small></div>` : ''}
                                <div class="text-end">
                                    <div class="text-muted">Công nợ: <strong>${Number(customer.total_debt || 0).toLocaleString('vi-VN')} đ</strong></div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="d-flex justify-content-end align-items-center">
                                    <div class="text-end me-3">
                                        <small class="text-muted">Đơn: {{ $customer->orders_count }}</small>
                                    </div>
                                    ${currentParams.tab === 'trash' ? '' : `<div class="text-end me-3 mt-1"><input type="checkbox" name="ids[]" value="${customer.id}" class="customer-checkbox"></div>`}
                                    <div class="mc-actions justify-content-end gap-2">${actionButtonsHtml(customer)}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');

        bindRowActions();
        updateBulkDeleteButton();
    }

    async function loadCustomers(params = {}) {
        Object.assign(currentParams, params);
        const queryString = new URLSearchParams(currentParams).toString();

        try {
            const response = await fetch('{{ route('pages.my_customer.ajax') }}?' + queryString, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();

            currentParams.sort_by = data.sort_by || '';
            currentParams.sort_dir = data.sort_dir || 'asc';

            updateCustomerList(data.customers || []);
            updatePagination(data.pagination || { current_page: 1, last_page: 1, from: 0, to: 0, total: 0, links: '' });
            updatePaginationInfo(data.pagination || { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 });
            updateTabButtons(data.active_tab || currentParams.tab, data.tab_counts || null);
            updateSortButtons();
        } catch (error) {
            console.error('Error loading customers:', error);
        }
    }

    document.querySelectorAll('#mc-tabs .mc-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.dataset.tab;
            loadCustomers({ tab, page: 1 });
        });
    });

    document.querySelectorAll('#mc-sort-row .mc-sort-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const field = this.dataset.sort;
            if (currentParams.sort_by !== field) {
                loadCustomers({ sort_by: field, sort_dir: 'asc', page: 1 });
                return;
            }

            const nextDir = currentParams.sort_dir === 'asc' ? 'desc' : 'asc';
            loadCustomers({ sort_dir: nextDir, page: 1 });
        });
    });

    document.getElementById('perPage').addEventListener('change', function () {
        loadCustomers({ per_page: this.value, page: 1 });
    });

    document.getElementById('mc-status-filter').addEventListener('change', function () {
        loadCustomers({ customer_status_filter: this.value, page: 1 });
    });

    document.getElementById('bulkDeleteBtn').addEventListener('click', async function (e) {
        e.preventDefault();
        const ids = document.getElementById('bulkDeleteIds').value;
        if (!ids) {
            alert('Vui lòng chọn ít nhất một khách hàng để đưa vào thùng rác.');
            return;
        }
        if (!confirm('Bạn có chắc chắn muốn đưa các khách hàng đã chọn vào thùng rác không?')) {
            return;
        }

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_ids', ids);

        await fetch('{{ route('my_customer.bulk_delete') }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData,
        });

        loadCustomers({ page: 1 });
    });

    document.getElementById('mc-search-form')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const searchValue = document.getElementById('mc-search-input')?.value || '';
        loadCustomers({ search: searchValue, page: 1 });
    });

    bindRowActions();
    updateBulkDeleteButton();
    updateSortButtons();

    /* ── Region tree accordion ──────────────────────────── */
    (function () {
        const tree = document.getElementById('mc-region-tree');
        if (!tree) return;

        tree.querySelectorAll('.mc-rt-city-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const city   = btn.dataset.city;
                const wardsEl = btn.nextElementSibling;
                const isOpen  = btn.classList.contains('open');

                // collapse all
                tree.querySelectorAll('.mc-rt-city-btn').forEach(b => b.classList.remove('open','active'));
                tree.querySelectorAll('.mc-rt-wards').forEach(w => w.classList.remove('open'));
                tree.querySelectorAll('.mc-rt-ward-btn').forEach(b => b.classList.remove('active'));

                if (!isOpen) {
                    btn.classList.add('open', 'active');
                    if (wardsEl && wardsEl.classList.contains('mc-rt-wards')) wardsEl.classList.add('open');
                    currentParams.city  = city;
                    currentParams.ward  = '';
                } else {
                    currentParams.city  = '';
                    currentParams.ward  = '';
                }
                loadCustomers({ page: 1 });
            });
        });

        tree.querySelectorAll('.mc-rt-ward-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                const city   = btn.dataset.city;
                const ward   = btn.dataset.ward;
                const isActive = btn.classList.contains('active');

                tree.querySelectorAll('.mc-rt-ward-btn').forEach(b => b.classList.remove('active'));
                tree.querySelectorAll('.mc-rt-city-btn').forEach(b => b.classList.remove('active'));

                if (!isActive) {
                    btn.classList.add('active');
                    const pb = tree.querySelector(`.mc-rt-city-btn[data-city="${CSS.escape(city)}"]`);
                    if (pb) pb.classList.add('active');
                    currentParams.city  = city;
                    currentParams.ward  = ward;
                } else {
                    // deselect ward, keep city
                    const pb = tree.querySelector(`.mc-rt-city-btn[data-city="${CSS.escape(city)}"]`);
                    if (pb) pb.classList.add('active');
                    currentParams.city  = city;
                    currentParams.ward  = '';
                }
                loadCustomers({ page: 1 });
            });
        });

        document.getElementById('mc-clear-region')?.addEventListener('click', () => {
            tree.querySelectorAll('.mc-rt-city-btn').forEach(b => b.classList.remove('open','active'));
            tree.querySelectorAll('.mc-rt-wards').forEach(w => w.classList.remove('open'));
            tree.querySelectorAll('.mc-rt-ward-btn').forEach(b => b.classList.remove('active'));
            currentParams.city = '';
            currentParams.ward = '';
            loadCustomers({ page: 1 });
        });
    })();

    loadCustomers();
</script>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('refreshPriorityBtn');
    if (btn) {
        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            fetch("{{ route('my_customer.refresh_priority') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
                alert(data.message || 'Đã làm mới priority!');
                window.location.reload();
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
                alert('Có lỗi xảy ra!');
            });
        });
    }
});
</script>
