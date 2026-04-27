@extends('layouts.site')

@push('styles')
<style>
    .schidx-page {
        --border: #dbe4ef;
        --soft: #f8fafc;
        --ink: #0f172a;
        --muted: #64748b;
        --teal: #0f766e;
    }
    .schidx-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.15), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 32%, #f4f7fb 100%);
        padding: 32px 0 64px;
    }
    .schidx-hero {
        background: linear-gradient(120deg, #152238 0%, #1f4a7c 55%, #2d7ba8 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1rem 1.2rem;
        margin-bottom: 1rem;
    }
    .schidx-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: .6rem;
        margin-top: .8rem;
    }
    .schidx-kpi {
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.14);
        border-radius: 10px;
        padding: .65rem .8rem;
        text-align: center;
    }
    .schidx-kpi-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        opacity: .78;
        margin-bottom: .25rem;
    }
    .schidx-kpi-value {
        font-size: 1.4rem;
        font-weight: 800;
        line-height: 1;
    }
    .schidx-card {
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(17,24,39,.05);
        background: #fff;
    }
    .schidx-card-header {
        border-bottom: 1px solid var(--border);
        padding: .65rem .85rem;
        font-weight: 700;
        font-size: .84rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--muted);
        background: var(--soft);
        border-radius: 12px 12px 0 0;
    }
    .schidx-card-body { padding: .85rem; }
    .schidx-aside {
        position: sticky;
        top: 82px;
    }
    .schidx-selected-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: var(--teal);
        border-radius: 999px;
        padding: .25rem .7rem;
        font-size: .82rem;
        font-weight: 700;
        margin-top: .45rem;
    }
    .schidx-selected-chip .rm { cursor: pointer; opacity: .65; }
    .schidx-selected-chip .rm:hover { opacity: 1; }
    /* Sidebar customer list */
    .schidx-cust-search {
        border: 0;
        border-bottom: 1px solid var(--border);
        border-radius: 0;
        font-size: .82rem;
        padding: .45rem .75rem;
        width: 100%;
        background: var(--soft);
    }
    .schidx-cust-search:focus { outline: none; border-bottom-color: var(--teal); background: #fff; }
    .schidx-cust-list {
        max-height: 320px;
        overflow-y: auto;
        padding: .3rem .4rem;
    }
    .schidx-cust-item {
        display: flex;
        align-items: center;
        gap: .4rem;
        width: 100%;
        background: none;
        border: 0;
        border-radius: 8px;
        padding: .45rem .5rem;
        text-align: left;
        cursor: pointer;
        font-size: .83rem;
        color: var(--ink);
        transition: background .12s;
        border-left: 3px solid transparent;
    }
    .schidx-cust-item:hover { background: #f1f5f9; }
    .schidx-cust-item.active {
        background: #f0fdf4;
        border-left-color: var(--teal);
        font-weight: 700;
        color: var(--teal);
    }
    .schidx-cust-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .schidx-cust-phone { font-size: .73rem; color: var(--muted); margin-top: .05rem; }
    .schidx-cust-badge {
        flex-shrink: 0;
        background: #e2e8f0;
        color: #475569;
        font-size: .72rem;
        font-weight: 700;
        border-radius: 999px;
        padding: .1rem .45rem;
        min-width: 22px;
        text-align: center;
    }
    .schidx-cust-item.active .schidx-cust-badge { background: var(--teal); color: #fff; }
    .schidx-toolbar {
        position: sticky;
        top: 72px;
        z-index: 12;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(17,24,39,.05);
        padding: .6rem .8rem;
        margin-bottom: .75rem;
    }
    .schidx-list { display: grid; gap: .65rem; }
    .schidx-row {
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        padding: .7rem .85rem;
        transition: box-shadow .2s;
    }
    .schidx-row:hover { box-shadow: 0 8px 22px rgba(17,24,39,.08); }
    .schidx-row-need-review { border-left: 4px solid #dc2626; }
    .schidx-row-pending     { border-left: 4px solid #f59e0b; }
    .schidx-row-approved    { border-left: 4px solid #16a34a; background: #f0fdf4; }
    .schidx-row-generated   { border-left: 4px solid #2563eb; background: #eff6ff; }
    .schidx-row-top {
        display: grid;
        grid-template-columns: 1.4fr 1.1fr 1fr 1fr auto;
        gap: .5rem;
        align-items: start;
    }
    .schidx-customer-name { font-weight: 700; color: var(--ink); }
    .schidx-customer-phone { font-size: .78rem; color: var(--muted); }
    .schidx-badge {
        display: inline-block;
        padding: .22rem .55rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .schidx-badge-pending     { background: #fef3c7; color: #92400e; }
    .schidx-badge-need-review { background: #fee2e2; color: #991b1b; }
    .schidx-badge-approved    { background: #dcfce7; color: #166534; }
    .schidx-badge-generated   { background: #dbeafe; color: #0c4a6e; }
    .schidx-badge-ok          { background: #dcfce7; color: #166534; }
    .schidx-badge-changed     { background: #fed7aa; color: #92400e; }
    .schidx-badge-insufficient { background: #fecaca; color: #991b1b; }
    .schidx-mini { font-size: .78rem; color: var(--muted); }
    .schidx-detail {
        margin-top: .6rem;
        border-top: 1px dashed var(--border);
        padding-top: .6rem;
        display: none;
    }
    .schidx-detail.open { display: block; }
    .schidx-items-grid { display: grid; gap: .3rem; }
    .schidx-item-row {
        display: grid;
        grid-template-columns: 1.6fr repeat(4, minmax(90px, auto));
        gap: .3rem;
        font-size: .8rem;
        border: 1px solid #e7edf5;
        border-radius: 8px;
        background: var(--soft);
        padding: .3rem .45rem;
        align-items: center;
    }
    .schidx-item-head {
        font-size: .72rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
    }
    .schidx-toggle-btn {
        background: none;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: .3rem .6rem;
        font-size: .8rem;
        cursor: pointer;
        color: var(--muted);
        white-space: nowrap;
    }
    .schidx-toggle-btn:hover { border-color: #94a3b8; color: var(--ink); }
    /* Active/Stop toggle */
    .schidx-active-btn {
        border: none;
        border-radius: 8px;
        padding: .28rem .7rem;
        font-size: .78rem;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: opacity .15s;
    }
    .schidx-active-btn:disabled { opacity: .6; cursor: wait; }
    .schidx-active-btn.is-on  { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .schidx-active-btn.is-off { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .schidx-row-stopped { opacity: .62; }
    .schidx-empty {
        border: 1px dashed var(--border);
        border-radius: 12px;
        background: #fff;
        color: var(--muted);
        text-align: center;
        padding: 2.5rem 1rem;
    }
    /* Summary panel */
    .schidx-summary {
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(17,24,39,.05);
        padding: .75rem;
        margin-bottom: .75rem;
    }
    .schidx-daily-box {
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(17,24,39,.05);
        padding: .75rem;
        margin-bottom: .75rem;
    }
    .schidx-daily-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .6rem;
        margin-bottom: .65rem;
    }
    .schidx-daily-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .55rem;
        margin-bottom: .65rem;
    }
    .schidx-daily-kpi {
        border: 1px solid #e5edf7;
        border-radius: 10px;
        padding: .5rem .6rem;
        background: #f8fafc;
    }
    .schidx-daily-kpi-label {
        font-size: .72rem;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: .03em;
        margin-bottom: .2rem;
    }
    .schidx-daily-kpi-value {
        font-size: .98rem;
        font-weight: 700;
        color: #0f172a;
    }
    .schidx-daily-list {
        display: grid;
        gap: .5rem;
    }
    .schidx-daily-row {
        border: 1px solid #e5edf7;
        border-radius: 10px;
        background: #f8fafc;
        padding: .55rem .65rem;
    }
    .schidx-daily-row-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .4rem;
        margin-bottom: .35rem;
    }
    .schidx-daily-row-name {
        font-size: .9rem;
        font-weight: 700;
        color: #0f172a;
    }
    .schidx-daily-row-sub {
        font-size: .78rem;
        color: #64748b;
    }
    .schidx-daily-row-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
    }
    .schidx-daily-chip {
        display: inline-block;
        font-size: .72rem;
        font-weight: 700;
        border-radius: 999px;
        padding: .18rem .5rem;
    }
    .schidx-daily-chip.active { background: #dcfce7; color: #166534; }
    .schidx-daily-chip.stopped { background: #fee2e2; color: #991b1b; }
    .schidx-daily-chip.approval { background: #fef3c7; color: #92400e; }
    .schidx-daily-chip.auto { background: #dbeafe; color: #1d4ed8; }
    .schidx-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .6rem;
        margin-bottom: .65rem;
    }
    .schidx-summary-item {
        border: 1px solid #e5edf7;
        border-radius: 10px;
        padding: .55rem .6rem;
        background: #f8fafc;
    }
    .schidx-summary-label {
        font-size: .74rem;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: .03em;
        margin-bottom: .2rem;
    }
    .schidx-summary-value {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
    }
    .schidx-product-vertical { display: grid; gap: .35rem; margin-top: .45rem; }
    .schidx-product-row {
        display: grid;
        grid-template-columns: 48px 1.6fr repeat(5, minmax(80px, auto));
        gap: .35rem;
        border: 1px solid #e5edf7;
        border-radius: 8px;
        padding: .36rem .45rem;
        background: #f8fafc;
        font-size: .8rem;
        align-items: center;
    }
    .schidx-product-head {
        background: #eef2f7;
        color: #475569;
        font-size: .73rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 700;
    }
    @media (max-width: 767.98px) {
        .schidx-daily-grid { grid-template-columns: repeat(2, 1fr); }
        .schidx-summary-grid { grid-template-columns: repeat(2, 1fr); }
        .schidx-product-row  { grid-template-columns: 36px 1.2fr 1fr 1fr; }
        .schidx-product-row > div:nth-child(n+5) { display: none; }
    }
    @media (max-width: 991.98px) {
        .schidx-aside { position: static; }
        .schidx-toolbar { position: static; }
        .schidx-kpi-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 767.98px) {
        .schidx-row-top { grid-template-columns: 1fr 1fr; }
        .schidx-row-top > div:last-child { grid-column: 1 / -1; }
        .schidx-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .schidx-item-row { grid-template-columns: 1fr 1fr; }
        .schidx-item-head { display: none; }
    }
</style>
@endpush

@section('content')
<div class="schidx-page">
    <div class="container">

        {{-- Hero --}}
        <div class="schidx-hero">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="mb-1 fw-bold">Lên đơn theo lịch</h4>
                    <div class="opacity-75 small">Kiểm soát giá & tồn kho, xem nhanh chi tiết từng lịch</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('my_customer.schedules.evaluate_today') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light fw-bold">
                            <i class="bi bi-search me-1"></i>Kiểm tra hôm nay
                        </button>
                    </form>
                    <a href="{{ route('my_customer.schedules.create') }}" class="btn btn-sm fw-bold" style="background:#0f766e;color:#fff;">
                        <i class="bi bi-plus-circle me-1"></i>Tạo lịch
                    </a>
                </div>
            </div>
            <div class="schidx-kpi-grid">
                <div class="schidx-kpi">
                    <div class="schidx-kpi-label">Tất cả</div>
                    <div class="schidx-kpi-value">{{ array_sum($counts) }}</div>
                </div>
                <div class="schidx-kpi">
                    <div class="schidx-kpi-label">Pending</div>
                    <div class="schidx-kpi-value">{{ $counts['pending'] }}</div>
                </div>
                <div class="schidx-kpi">
                    <div class="schidx-kpi-label">Cần review</div>
                    <div class="schidx-kpi-value">{{ $counts['need_review'] }}</div>
                </div>
                <div class="schidx-kpi">
                    <div class="schidx-kpi-label">Approved</div>
                    <div class="schidx-kpi-value">{{ $counts['approved'] }}</div>
                </div>
                <div class="schidx-kpi">
                    <div class="schidx-kpi-label">Đã tạo đơn</div>
                    <div class="schidx-kpi-value">{{ $counts['generated'] }}</div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 rounded-3 mb-3">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 rounded-3 mb-3">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            </div>
        @endif

        {{-- Delete confirm modal --}}
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="deleteModalLabel">
                            <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Xác nhận xóa lịch
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <p class="mb-1">Bạn sắp xóa lịch lên đơn sau:</p>
                        <div class="rounded-3 p-3 mb-0" style="background:#fee2e2;border:1px solid #fecaca;">
                            <div class="fw-bold" id="del-customer-name"></div>
                            <div class="text-muted small" id="del-schedule-date"></div>
                        </div>
                        <p class="mt-3 mb-0 text-muted small">Hành động này không thể hoàn tác.</p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <form id="delete-form" method="POST" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger fw-bold">
                                <i class="bi bi-trash me-1"></i>Xác nhận xóa
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Sidebar --}}
            <div class="col-12 col-lg-3">
                <div class="schidx-aside d-grid gap-3">

                    {{-- Filter form --}}
                    <div class="schidx-card">
                        <div class="schidx-card-header">Lọc dữ liệu</div>
                        <div class="schidx-card-body">
                            <form method="GET" action="{{ route('my_customer.schedules.index') }}" class="row g-2" id="schedFilterForm">
                                <input type="hidden" name="customer_id" id="sidebar-customer-id" value="{{ $activeCustomerId ?: '' }}">

                                <div class="col-12">
                                    <label class="form-label mb-1 small fw-bold">Tìm kiếm</label>
                                    <input type="text" class="form-control form-control-sm" name="search" value="{{ $search }}" placeholder="Tên, SĐT khách hàng">
                                </div>
                                <div class="col-6 col-lg-12">
                                    <label class="form-label mb-1 small fw-bold">Từ ngày</label>
                                    <input type="date" class="form-control form-control-sm" name="from_date" value="{{ $fromDate }}">
                                </div>
                                <div class="col-6 col-lg-12">
                                    <label class="form-label mb-1 small fw-bold">Đến ngày</label>
                                    <input type="date" class="form-control form-control-sm" name="to_date" value="{{ $toDate }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label mb-1 small fw-bold">Trạng thái</label>
                                    <select class="form-select form-select-sm" name="status">
                                        <option value="all" {{ $activeStatus === 'all' ? 'selected' : '' }}>Tất cả</option>
                                        <option value="pending" {{ $activeStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="need_review" {{ $activeStatus === 'need_review' ? 'selected' : '' }}>Cần review</option>
                                        <option value="approved" {{ $activeStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="generated" {{ $activeStatus === 'generated' ? 'selected' : '' }}>Đã tạo đơn</option>
                                    </select>
                                </div>
                                <div class="col-12 d-grid gap-2 mt-1">
                                    <button class="btn btn-sm btn-primary" type="submit">Áp dụng</button>
                                    <a href="{{ route('my_customer.schedules.index') }}" class="btn btn-sm btn-outline-secondary">Xoá bộ lọc</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Customer list --}}
                    <div class="schidx-card">
                        <div class="schidx-card-header d-flex align-items-center justify-content-between">
                            <span>Khách hàng</span>
                            <span class="badge bg-secondary" style="font-size:.72rem;">{{ $myCustomers->count() }}</span>
                        </div>
                        <div class="schidx-card-body p-0">
                            <input type="text" id="cust-list-search" class="schidx-cust-search"
                                placeholder="&#128269; Tìm khách hàng...">
                            <div class="schidx-cust-list" id="cust-list">
                                <button type="button"
                                    class="schidx-cust-item {{ !$activeCustomerId ? 'active' : '' }}"
                                    data-cust-id=""
                                    data-cust-name="Tất cả khách hàng">
                                    <div class="schidx-cust-name">Tất cả khách hàng</div>
                                    <span class="schidx-cust-badge">{{ $myCustomers->sum('schedule_count') }}</span>
                                </button>
                                @foreach($myCustomers as $c)
                                <button type="button"
                                    class="schidx-cust-item {{ $activeCustomerId == $c->id ? 'active' : '' }}"
                                    data-cust-id="{{ $c->id }}"
                                    data-cust-name="{{ $c->name }}"
                                    data-cust-search="{{ strtolower($c->name . ' ' . $c->phone) }}">
                                    <div class="flex-grow-1 min-width-0" style="min-width:0;">
                                        <div class="schidx-cust-name">{{ $c->name }}</div>
                                        @if($c->phone)
                                        <div class="schidx-cust-phone">{{ $c->phone }}</div>
                                        @endif
                                    </div>
                                    <span class="schidx-cust-badge">{{ $c->schedule_count }}</span>
                                </button>
                                @endforeach
                                @if($myCustomers->isEmpty())
                                <div class="text-muted small text-center py-3">Chưa có lịch nào.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main content --}}
            <div class="col-12 col-lg-9">
                {{-- Daily auto-order section --}}
                <div class="schidx-daily-box">
                    <div class="schidx-daily-head">
                        <div>
                            <div class="fw-semibold">Đơn lên tự động hàng ngày</div>
                            <div class="schidx-mini">Danh sách cấu hình chạy mỗi ngày bạn đã tạo</div>
                        </div>
                        <a href="{{ route('my_customer.schedules.create') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i>Tạo cấu hình mới
                        </a>
                    </div>

                    <div class="schidx-daily-grid">
                        <div class="schidx-daily-kpi">
                            <div class="schidx-daily-kpi-label">Tổng cấu hình</div>
                            <div class="schidx-daily-kpi-value">{{ $dailyStats['total'] }}</div>
                        </div>
                        <div class="schidx-daily-kpi">
                            <div class="schidx-daily-kpi-label">Đang hoạt động</div>
                            <div class="schidx-daily-kpi-value">{{ $dailyStats['active'] }}</div>
                        </div>
                        <div class="schidx-daily-kpi">
                            <div class="schidx-daily-kpi-label">Cần sale duyệt</div>
                            <div class="schidx-daily-kpi-value">{{ $dailyStats['approval_required'] }}</div>
                        </div>
                    </div>

                    @if($dailySchedules->isEmpty())
                        <div class="schidx-empty py-4">
                            <i class="bi bi-repeat d-block mb-2" style="font-size:1.4rem;"></i>
                            Chưa có cấu hình lên đơn hàng ngày.
                        </div>
                    @else
                        <div class="schidx-daily-list">
                            @foreach($dailySchedules as $daily)
                                <div class="schidx-daily-row">
                                    <div class="schidx-daily-row-top">
                                        <div>
                                            <div class="schidx-daily-row-name">{{ $daily->customer->name ?? 'N/A' }}</div>
                                            <div class="schidx-daily-row-sub">
                                                {{ $daily->customer->phone ?? 'Không có SĐT' }} • Bắt đầu từ {{ optional($daily->start_date)->format('d/m/Y') }}
                                            </div>
                                        </div>
                                        <div class="schidx-mini">#RULE-{{ $daily->id }}</div>
                                    </div>
                                    <div class="schidx-daily-row-meta">
                                        <span class="schidx-daily-chip {{ $daily->is_active ? 'active' : 'stopped' }}">
                                            {{ $daily->is_active ? 'Đang bật' : 'Đang tắt' }}
                                        </span>
                                        <span class="schidx-daily-chip {{ $daily->approval_required ? 'approval' : 'auto' }}">
                                            {{ $daily->approval_required ? 'Sale duyệt trước tạo đơn' : 'Tự động tạo đơn' }}
                                        </span>
                                        <span class="schidx-daily-chip auto">{{ (int) $daily->items_count }} mặt hàng</span>
                                        <span class="schidx-daily-chip auto">SL: {{ number_format((int) ($daily->total_qty ?? 0)) }}</span>
                                        <span class="schidx-daily-chip auto">Hôm nay: {{ (int) $daily->schedules_today_count }} lịch</span>
                                        <span class="schidx-daily-chip auto">Đã tạo: {{ (int) $daily->generated_today_count }}</span>
                                        <span class="schidx-daily-chip auto">Review: {{ (int) $daily->need_review_today_count }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Status tab toolbar --}}
                <div class="schidx-toolbar" id="schedTabToolbar">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @php
                            $tabs = [
                                'all'         => ['label' => 'Tất cả',     'count' => array_sum($counts)],
                                'pending'     => ['label' => 'Pending',    'count' => $counts['pending']],
                                'need_review' => ['label' => 'Cần review', 'count' => $counts['need_review']],
                                'approved'    => ['label' => 'Approved',   'count' => $counts['approved']],
                                'generated'   => ['label' => 'Đã tạo đơn','count' => $counts['generated']],
                            ];
                        @endphp
                        @foreach($tabs as $key => $tab)
                            <button type="button"
                               class="btn btn-sm js-tab-btn {{ $activeStatus === $key ? 'btn-primary' : 'btn-outline-secondary' }}"
                               data-status="{{ $key }}">
                                {{ $tab['label'] }}
                                <span class="badge {{ $activeStatus === $key ? 'bg-white text-primary' : 'bg-secondary' }} ms-1 js-tab-count" data-status="{{ $key }}">{{ $tab['count'] }}</span>
                            </button>
                        @endforeach
                        <div class="ms-auto schidx-mini" id="schedTotalInfoTab">{{ $schedules->total() }} lịch</div>
                    </div>
                </div>

                {{-- Summary panel --}}
                <div class="schidx-summary" id="schedSummaryPanel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold">Thống kê theo bộ lọc</div>
                        <div class="schidx-mini">Tính trên lịch đang hiển thị</div>
                    </div>
                    <div class="schidx-summary-grid">
                        <div class="schidx-summary-item">
                            <div class="schidx-summary-label">Tổng mặt hàng</div>
                            <div class="schidx-summary-value" id="sumItemLines">0 mặt hàng</div>
                        </div>
                        <div class="schidx-summary-item">
                            <div class="schidx-summary-label">Tổng số lượng</div>
                            <div class="schidx-summary-value" id="sumItemQty">0</div>
                        </div>
                        <div class="schidx-summary-item">
                            <div class="schidx-summary-label">Tổng tạm tính</div>
                            <div class="schidx-summary-value" id="sumItemTotal">0 đ</div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="schidx-summary-label mb-0">Chi tiết hàng hóa</div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleSumDetailBtn" onclick="toggleSumDetail()">
                                <i class="bi bi-chevron-expand me-1"></i>Chi tiết
                            </button>
                        </div>
                        <div class="d-none" id="sumDetailWrap">
                            <div class="schidx-product-vertical" id="sumDetailList"></div>
                        </div>
                    </div>
                </div>

                {{-- Sort + List + Pagination (AJAX target) --}}
                <div id="schedResultsWrap">
                    @include('site.my_customer.schedules._results')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(id, customerName, dateStr) {
    document.getElementById('del-customer-name').textContent = customerName;
    document.getElementById('del-schedule-date').textContent = 'Ngày ' + dateStr + ' — #' + id;
    const form = document.getElementById('delete-form');
    form.action = '/my-customer/schedules/' + id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function toggleDetail(id, btn) {
    const detail = document.getElementById('detail-' + id);
    const icon = btn.querySelector('i');
    const open = detail.classList.toggle('open');
    icon.className = open ? 'bi bi-chevron-up me-1' : 'bi bi-chevron-down me-1';
    btn.querySelector('i').nextSibling.textContent = open ? 'Đóng' : 'Chi tiết';
}

function formatMoney(n) {
    if (!n) return '0 đ';
    return Number(n).toLocaleString('vi-VN') + ' đ';
}
function formatQty(n) {
    return Number(n).toLocaleString('vi-VN');
}

function recalcSummary() {
    const lines = document.querySelectorAll('.js-product-line');
    const itemMap = new Map();
    let totalQty = 0;
    let totalSubtotal = 0;

    lines.forEach(function (line) {
        const name     = (line.dataset.productName  || '').trim();
        const size     = (line.dataset.productSize  || '-').trim();
        const unit     = (line.dataset.productUnit  || 'cái').trim();
        const qty      = Number(line.dataset.productQty      || 0);
        const price    = Number(line.dataset.productPrice    || 0);
        const subtotal = Number(line.dataset.productSubtotal || 0);
        if (!name) return;

        const key = [name, size, price].join('||');
        const cur = itemMap.get(key) || { name, size, unit, qty: 0, price, subtotal: 0 };
        cur.qty      += qty;
        cur.subtotal += subtotal;
        itemMap.set(key, cur);

        totalQty      += qty;
        totalSubtotal += subtotal;
    });

    const sumItemLines = document.getElementById('sumItemLines');
    const sumItemQty   = document.getElementById('sumItemQty');
    const sumItemTotal = document.getElementById('sumItemTotal');
    const sumDetailList = document.getElementById('sumDetailList');

    if (sumItemLines) sumItemLines.textContent = formatQty(itemMap.size) + ' mặt hàng';
    if (sumItemQty)   sumItemQty.textContent   = formatQty(totalQty);
    if (sumItemTotal) sumItemTotal.textContent  = formatMoney(totalSubtotal);

    if (sumDetailList) {
        const items = Array.from(itemMap.values()).sort((a, b) => b.qty - a.qty);
        if (!items.length) {
            sumDetailList.innerHTML = '<div class="schidx-mini text-center py-2">Không có dữ liệu.</div>';
        } else {
            const head = '<div class="schidx-product-row schidx-product-head">'
                + '<div>STT</div><div>Sản phẩm</div><div class="text-center">Số lượng</div>'
                + '<div class="text-center">Tổng</div><div>Size</div>'
                + '<div class="text-end">Đơn giá</div><div class="text-end">Tạm tính</div></div>';
            const body = items.map((item, i) =>
                '<div class="schidx-product-row">'
                + '<div>' + (i + 1) + '</div>'
                + '<div class="fw-semibold">' + item.name + '</div>'
                + '<div class="text-center">' + formatQty(item.qty) + '</div>'
                + '<div class="text-center">' + formatQty(item.qty) + ' ' + item.unit + '</div>'
                + '<div>' + item.size + '</div>'
                + '<div class="text-end">' + formatMoney(item.price) + '</div>'
                + '<div class="text-end">' + formatMoney(item.subtotal) + '</div>'
                + '</div>'
            ).join('');
            sumDetailList.innerHTML = head + body;
        }
    }
}

function toggleSumDetail() {
    const wrap = document.getElementById('sumDetailWrap');
    const btn  = document.getElementById('toggleSumDetailBtn');
    const open = wrap.classList.toggle('d-none');
    btn.innerHTML = open
        ? '<i class="bi bi-chevron-expand me-1"></i>Chi tiết'
        : '<i class="bi bi-chevron-contract me-1"></i>Đóng';
    if (!open) recalcSummary();
}

const _csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* ---- Sidebar customer list ---- */
(function () {
    const list       = document.getElementById('cust-list');
    const searchEl   = document.getElementById('cust-list-search');
    const hiddenInput = document.getElementById('sidebar-customer-id');

    if (!list) return;

    /* Client-side search filter */
    if (searchEl) {
        searchEl.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            list.querySelectorAll('.schidx-cust-item').forEach(function (btn) {
                const text = (btn.dataset.custSearch || btn.dataset.custName || '').toLowerCase();
                btn.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    }

    /* Click to filter */
    list.addEventListener('click', function (e) {
        const btn = e.target.closest('.schidx-cust-item');
        if (!btn) return;

        list.querySelectorAll('.schidx-cust-item').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        if (hiddenInput) hiddenInput.value = btn.dataset.custId || '';

        const p = window.getFormParams ? window.getFormParams() : new URLSearchParams(window.location.search);
        p.delete('page');
        if (window.loadResults) window.loadResults(p);
    });
})();

async function toggleActiveSchedule(btn) {
    btn.disabled = true;
    try {
        const res = await fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': _csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });
        if (!res.ok) throw new Error('Request failed');
        const data = await res.json();
        const row  = document.getElementById('schedule-row-' + btn.dataset.id);
        if (data.is_active) {
            btn.className = 'schidx-active-btn is-on';
            btn.innerHTML = '<i class="bi bi-play-fill me-1"></i>Đang chạy';
            row?.classList.remove('schidx-row-stopped');
        } else {
            btn.className = 'schidx-active-btn is-off';
            btn.innerHTML = '<i class="bi bi-stop-fill me-1"></i>Đã dừng';
            row?.classList.add('schidx-row-stopped');
        }
    } catch (e) {
        alert('Lỗi: không thể thay đổi trạng thái.');
    } finally {
        btn.disabled = false;
    }
}

/* ---- AJAX scheduler for sort / tabs / filter / pagination ---- */
/* loadResults and getFormParams are exposed globally for the customer picker */
(function () {
    const INDEX_URL = '{{ route('my_customer.schedules.index') }}';
    let _activeParams = new URLSearchParams(window.location.search);
    let _loading = false;

    /* Build the spinner overlay */
    const spinner = document.createElement('div');
    spinner.id = 'schedSpinner';
    spinner.style.cssText = 'position:absolute;inset:0;background:rgba(255,255,255,.55);'
        + 'z-index:20;display:none;align-items:center;justify-content:center;border-radius:8px;';
    spinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.getElementById('schedResultsWrap').style.position = 'relative';
    document.getElementById('schedResultsWrap').appendChild(spinner);

    function showSpinner() { spinner.style.display = 'flex'; }
    function hideSpinner() { spinner.style.display = 'none'; }

    async function loadResults(params) {
        if (_loading) return;
        _loading = true;
        showSpinner();
        try {
            const url = INDEX_URL + '?' + params.toString();
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': _csrf,
                },
            });
            if (!res.ok) throw new Error('Server error ' + res.status);
            const data = await res.json();

            /* Swap results HTML */
            document.getElementById('schedResultsWrap').innerHTML = data.html;
            document.getElementById('schedResultsWrap').style.position = 'relative';
            document.getElementById('schedResultsWrap').appendChild(spinner);

            /* Update tab counts & active state */
            const allCount = Object.values(data.counts).reduce((a, b) => a + b, 0);
            const countsWithAll = Object.assign({ all: allCount }, data.counts);
            document.querySelectorAll('.js-tab-btn').forEach(btn => {
                const st = btn.dataset.status;
                const cnt = countsWithAll[st] ?? 0;
                const isActive = st === data.activeStatus;
                btn.className = 'btn btn-sm js-tab-btn ' + (isActive ? 'btn-primary' : 'btn-outline-secondary');
                const badge = btn.querySelector('.js-tab-count');
                if (badge) {
                    badge.textContent = cnt;
                    badge.className = 'badge ms-1 js-tab-count ' + (isActive ? 'bg-white text-primary' : 'bg-secondary');
                }
            });
            const totalInfoTab = document.getElementById('schedTotalInfoTab');
            if (totalInfoTab) totalInfoTab.textContent = data.total + ' lịch';

            /* Push URL */
            window.history.pushState({}, '', url);
            _activeParams = params;

            /* Re-run summary JS */
            recalcSummary();
            const sumDetailWrap = document.getElementById('sumDetailWrap');
            if (sumDetailWrap && !sumDetailWrap.classList.contains('d-none')) recalcSummary();
        } catch (e) {
            console.error('Schedule AJAX error:', e);
        } finally {
            _loading = false;
            hideSpinner();
        }
    }

    function getFormParams() {
        const form = document.getElementById('schedFilterForm');
        const fd = new FormData(form);
        const p = new URLSearchParams();
        for (const [k, v] of fd.entries()) {
            if (v !== '') p.set(k, v);
        }
        // carry sort/dir from current params
        if (_activeParams.has('sort'))     p.set('sort',     _activeParams.get('sort'));
        if (_activeParams.has('sort_dir')) p.set('sort_dir', _activeParams.get('sort_dir'));
        return p;
    }
    /* Expose for customer picker */
    window.getFormParams = getFormParams;
    window.loadResults   = loadResults;

    /* Intercept sort buttons (delegated — re-rendered on each swap) */
    document.getElementById('schedResultsWrap').addEventListener('click', function (e) {
        const btn = e.target.closest('.js-sort-btn');
        if (!btn) return;
        e.preventDefault();
        const p = getFormParams();
        p.set('sort',     btn.dataset.sort);
        p.set('sort_dir', btn.dataset.dir);
        p.delete('page');
        loadResults(p);
    });

    /* Intercept pagination links (delegated) */
    document.getElementById('schedResultsWrap').addEventListener('click', function (e) {
        const link = e.target.closest('nav a[href]');
        if (!link) return;
        e.preventDefault();
        const url = new URL(link.href);
        loadResults(url.searchParams);
    });

    /* Intercept status tabs */
    document.getElementById('schedTabToolbar').addEventListener('click', function (e) {
        const btn = e.target.closest('.js-tab-btn');
        if (!btn) return;
        const p = getFormParams();
        p.set('status', btn.dataset.status);
        p.delete('page');
        loadResults(p);
    });

    /* Intercept filter form submit */
    document.getElementById('schedFilterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const p = getFormParams();
        p.delete('page');
        loadResults(p);
    });

    document.addEventListener('DOMContentLoaded', function () {
        recalcSummary();
    });
})();
</script>
@endpush
