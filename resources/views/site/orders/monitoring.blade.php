@extends('layouts.site')

@push('styles')
<style>
    .monitor-page {
        --monitor-blue: #0b5d87;
        --monitor-teal: #087f78;
        --monitor-border: #dce6f1;
        --monitor-soft: #f5f8fc;
        background: #f8fafc;
        min-height: 75vh;
        padding: 28px 0 64px;
    }
    .monitor-shell { max-width: 1180px; margin: 0 auto; }
    .monitor-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 14px;
        padding: 0 8px 14px;
    }
    .monitor-title {
        margin: 0;
        padding-bottom: 5px;
        border-bottom: 3px solid #f59e0b;
        color: #111827;
        font-size: 1.08rem;
        font-weight: 900;
        text-transform: uppercase;
    }
    .monitor-date-form { display: flex; align-items: center; gap: 8px; }
    .monitor-date-form .form-control { width: 160px; height: 36px; border-radius: 4px; }
    .monitor-date-form .btn { height: 36px; border-radius: 3px; font-weight: 700; }
    .monitor-panel {
        border: 1px solid var(--monitor-border);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .05);
    }
    .monitor-sequences {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 12px;
    }
    .monitor-sequence {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #64748b;
        color: #fff;
        font-size: .78rem;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(15, 23, 42, .18);
    }
    .monitor-sequence:hover { background: var(--monitor-teal); color: #fff; transform: translateY(-1px); }
    .monitor-summary-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px 8px;
    }
    .monitor-summary-toggle {
        border: 0;
        background: transparent;
        color: #64748b;
        font-size: .82rem;
    }
    .monitor-sort-group { display: flex; flex-wrap: wrap; gap: 6px; }
    .monitor-sort-group .btn { border-radius: 4px; font-size: .78rem; }
    .monitor-summary-table { padding: 0 12px 12px; }
    .monitor-summary-table table { margin: 0; font-size: .8rem; }
    .monitor-summary-table thead th {
        border: 0;
        background: #eaf1f8;
        color: #334155;
        font-size: .68rem;
        letter-spacing: .05em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .monitor-summary-table th:first-child { border-radius: 9px 0 0 9px; }
    .monitor-summary-table th:last-child { border-radius: 0 9px 9px 0; }
    .monitor-summary-table td { border-color: #e8eef5; vertical-align: middle; }
    .monitor-layout {
        display: grid;
        grid-template-columns: 185px minmax(0, 1fr);
        gap: 14px;
        margin-top: 18px;
    }
    .monitor-sidebar { display: grid; gap: 14px; align-content: start; }
    .monitor-filter-block { overflow: hidden; }
    .monitor-filter-title {
        padding: 10px 14px;
        border-bottom: 1px solid var(--monitor-border);
        color: var(--monitor-blue);
        font-size: .78rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .monitor-filter-list { max-height: 280px; overflow-y: auto; }
    .monitor-filter-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 9px 13px;
        border-bottom: 1px solid #eef2f7;
        color: #075985;
        font-size: .82rem;
        font-weight: 700;
        text-decoration: none;
    }
    .monitor-filter-link:last-child { border-bottom: 0; }
    .monitor-filter-link:hover,
    .monitor-filter-link.active { background: #eaf5f6; color: #075985; }
    .monitor-filter-count {
        min-width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #64748b;
        color: #fff;
        font-size: .68rem;
    }
    .monitor-orders { display: grid; gap: 10px; }
    .monitor-order {
        scroll-margin-top: 100px;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 126px;
    }
    .monitor-order-main { min-width: 0; padding: 14px 16px 10px; }
    .monitor-order-head {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) minmax(290px, 360px);
        align-items: start;
        gap: 18px;
        padding-bottom: 10px;
        border-bottom: 1px solid #edf2f7;
    }
    .monitor-order-person { display: flex; align-items: center; gap: 12px; }
    .monitor-order-number {
        flex: 0 0 auto;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #64748b;
        color: #fff;
        font-weight: 900;
    }
    .monitor-order-name { color: #075985; font-weight: 800; }
    .monitor-order-code { color: #64748b; font-size: .72rem; }
    .monitor-timeline { padding-top: 2px; }
    .monitor-timeline-track {
        position: relative;
        display: flex;
        justify-content: space-between;
        padding: 0 7px;
    }
    .monitor-timeline-track::before {
        content: "";
        position: absolute;
        top: 8px;
        left: 14px;
        right: 14px;
        height: 3px;
        background: #d9e4ef;
    }
    .monitor-timeline-progress {
        position: absolute;
        top: 8px;
        left: 14px;
        height: 3px;
        background: linear-gradient(90deg, #0e7490, #2563eb);
    }
    .monitor-timeline-dot {
        position: relative;
        z-index: 1;
        width: 18px;
        height: 18px;
        border: 2px solid #cbd9e8;
        border-radius: 50%;
        background: #fff;
    }
    .monitor-timeline-dot.done { border-color: #0e7490; background: #0e7490; }
    .monitor-timeline-dot.current {
        border-color: #2563eb;
        background: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .13);
    }
    .monitor-timeline-labels {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        margin-top: 5px;
        color: #64748b;
        font-size: .62rem;
        text-align: center;
    }
    .monitor-meta {
        display: grid;
        gap: 4px;
        padding: 10px 2px;
        color: #526071;
        font-size: .78rem;
    }
    .monitor-items { width: 100%; margin: 0; font-size: .76rem; }
    .monitor-items th {
        border-bottom: 1px solid #dfe8f2;
        color: #64748b;
        font-size: .64rem;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .monitor-items td { border-color: #edf2f7; vertical-align: middle; }
    .monitor-order-total { padding-top: 8px; text-align: right; color: #111827; font-weight: 900; }
    .monitor-order-footer {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: flex-start;
        gap: 10px;
        padding: 14px 10px;
        border-left: 1px solid #edf2f7;
        background: #fbfdff;
    }
    .monitor-status {
        padding: 5px 9px;
        border-radius: 999px;
        background: #eaf1f8;
        color: #334155;
        font-size: .7rem;
        font-weight: 800;
    }
    .monitor-actions { display: grid; gap: 7px; }
    .monitor-actions .btn { width: 100%; border-radius: 4px; font-size: .72rem; font-weight: 700; }
    .monitor-order > .collapse { grid-column: 1 / -1; }
    .monitor-empty { padding: 44px 20px; text-align: center; color: #64748b; }
    .monitor-pagination { padding: 14px 0 0; }
    .monitor-bulk-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
    }
    .monitor-bulk-actions > div { display: flex; flex-wrap: wrap; gap: 8px; }
    .monitor-create { margin-bottom: 18px; overflow: hidden; }
    .monitor-create[hidden] { display: none !important; }
    .monitor-create-head { padding: 16px 18px 0; }
    .monitor-create-head h2 { margin: 0; color: #9a3412; font-size: 1rem; font-weight: 900; text-transform: uppercase; }
    .monitor-create-steps {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        padding: 18px 24px 14px;
    }
    .monitor-create-step { position: relative; color: #64748b; text-align: center; font-size: .75rem; font-weight: 700; }
    .monitor-create-step:not(:last-child)::after {
        content: "";
        position: absolute;
        z-index: 0;
        top: 17px;
        left: calc(50% + 24px);
        width: calc(100% - 48px);
        height: 2px;
        background: #fed7aa;
    }
    .monitor-create-step-number {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        margin: 0 auto 6px;
        border-radius: 50%;
        background: #64748b;
        color: #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, .2);
    }
    .monitor-create-step.is-active { color: #9a3412; }
    .monitor-create-step.is-active .monitor-create-step-number,
    .monitor-create-step.is-done .monitor-create-step-number { background: #c2410c; }
    .monitor-create-body { padding: 8px 18px 18px; }
    .monitor-create-pane[hidden] { display: none !important; }
    .monitor-create-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; }
    .monitor-create-search { display: flex; gap: 8px; margin-bottom: 12px; }
    .monitor-create-search .form-control { min-width: 0; }
    .monitor-selected-table { border: 1px solid #fed7aa; border-radius: 9px; overflow: hidden; background: #fffaf3; }
    .monitor-selected-table table { margin: 0; font-size: .78rem; }
    .monitor-selected-table input { width: 80px; }
    .monitor-create-total { padding: 10px 12px; border-top: 1px solid #fed7aa; text-align: right; color: #b45309; font-weight: 900; }
    .monitor-customer-selected { padding: 12px 14px; border: 1px solid #99f6e4; border-radius: 8px; background: #f0fdfa; }
    .monitor-confirm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .monitor-confirm-card { padding: 14px; border: 1px solid var(--monitor-border); border-radius: 9px; background: #f8fafc; }
    .monitor-confirm-card h3 { font-size: .82rem; font-weight: 900; text-transform: uppercase; }
    .monitor-finish { padding: 24px; text-align: center; }
    .monitor-finish-icon { color: #059669; font-size: 2.8rem; }
    .monitor-create .variant-picker-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .monitor-create .variant-picker-list { display: grid; gap: 8px; max-height: 360px; overflow-y: auto; }
    .monitor-create .variant-picker-item { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; align-items: center; gap: 12px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; }
    .monitor-create .variant-picker-main { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .monitor-create .variant-picker-thumb { width: 48px; height: 48px; border-radius: 7px; object-fit: cover; }
    .monitor-create .variant-picker-copy { min-width: 0; }
    .monitor-create .variant-picker-name { font-size: .82rem; font-weight: 800; }
    .monitor-create .variant-picker-meta { display: flex; flex-wrap: wrap; gap: 8px; color: #64748b; font-size: .68rem; }
    .monitor-create .variant-picker-stats { display: flex; gap: 12px; font-size: .72rem; }
    .monitor-create .variant-picker-stats > div { display: grid; }
    .monitor-create .variant-picker-label { color: #64748b; }
    .monitor-create .variant-picker-actions { display: flex; gap: 4px; }
    .monitor-create .variant-picker-actions .btn-warning,
    .monitor-create .variant-picker-actions .btn-outline-warning,
    .monitor-create .variant-picker-actions .btn-outline-secondary { display: none; }
    .monitor-create .pagination { margin-bottom: 0; }
    @media (max-width: 991.98px) {
        .monitor-layout { grid-template-columns: 1fr; }
        .monitor-sidebar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .monitor-page { padding-top: 18px; }
        .monitor-toolbar { align-items: flex-start; }
        .monitor-date-form { width: 100%; }
        .monitor-date-form .form-control { flex: 1; width: auto; }
        .monitor-sidebar { grid-template-columns: 1fr; }
        .monitor-order-head { grid-template-columns: 1fr; }
        .monitor-order { display: block; }
        .monitor-order-footer {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #edf2f7;
            border-left: 0;
        }
        .monitor-actions { display: flex; flex-wrap: wrap; }
        .monitor-actions .btn { width: auto; }
        .monitor-timeline { min-width: 0; }
        .monitor-order-main { padding: 12px; }
        .monitor-create-steps { padding-inline: 8px; }
        .monitor-create-step { font-size: .66rem; }
        .monitor-confirm-grid { grid-template-columns: 1fr; }
        .monitor-create .variant-picker-item { grid-template-columns: 1fr auto; }
        .monitor-create .variant-picker-stats { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $statusLabels = \App\Models\Order::statusOptions() + [
        \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL => 'Chờ Leader duyệt',
        \App\Models\Order::STATUS_PENDING_MANAGER_APPROVAL => 'Chờ Manager duyệt',
        \App\Models\Order::STATUS_APPROVED => 'Đã duyệt',
        \App\Models\Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
        \App\Models\Order::STATUS_PACKING => 'Đang đóng gói',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'Chờ vận chuyển',
        \App\Models\Order::STATUS_DELIVERING => 'Đang giao hàng',
        \App\Models\Order::STATUS_RETURNING => 'Đang trả hàng',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'Đã nhập kho trả hàng',
        'pending_warehouse_approval' => 'Chờ Kho duyệt',
        'shipping' => 'Đang vận chuyển',
        'picked_up' => 'Đã lấy hàng',
    ];
    $timelineSteps = ['Đặt đơn', 'Duyệt', 'Kho', 'Vận chuyển', 'Hoàn tất'];
    $timelineMap = [
        \App\Models\Order::STATUS_ORDER_PLACED => 0,
        \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL => 1,
        \App\Models\Order::STATUS_PENDING_MANAGER_APPROVAL => 1,
        'pending_warehouse_approval' => 1,
        \App\Models\Order::STATUS_ORDER_CONFIRMED => 1,
        \App\Models\Order::STATUS_APPROVED => 1,
        \App\Models\Order::STATUS_READY_TO_PACK => 2,
        \App\Models\Order::STATUS_PACKING => 2,
        \App\Models\Order::STATUS_PACKED => 2,
        \App\Models\Order::STATUS_READY_TO_SHIP => 3,
        \App\Models\Order::STATUS_DELIVERING => 3,
        \App\Models\Order::STATUS_IN_DELIVERY => 3,
        'shipping' => 3,
        'picked_up' => 3,
        \App\Models\Order::STATUS_DELIVERED => 4,
        \App\Models\Order::STATUS_COMPLETED => 4,
        \App\Models\Order::STATUS_RETURNING => 3,
        \App\Models\Order::STATUS_RETURNED => 4,
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 4,
    ];
    $formatQuantity = static fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    $sortUrl = static function (string $field) use ($sortBy, $sortDir): string {
        $nextDirection = $sortBy === $field && $sortDir === 'asc' ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort_by' => $field, 'sort_dir' => $nextDirection, 'page' => 1]);
    };
    $saleFilterQuery = request()->except(['sale_id', 'page']);
    $customerFilterQuery = request()->except(['customer_id', 'page']);
@endphp

<section class="monitor-page">
    <div class="container monitor-shell">
        <div class="monitor-toolbar">
            <h1 class="monitor-title">Theo dõi đơn hàng ngày</h1>
            <form class="monitor-date-form" method="GET" action="{{ route('pages.my_orders.monitoring') }}">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}">
                @if($keyword !== '')<input type="hidden" name="keyword" value="{{ $keyword }}">@endif
                @if($selectedStatus !== '')<input type="hidden" name="status" value="{{ $selectedStatus }}">@endif
                <button type="submit" class="btn btn-sm btn-success">Lọc</button>
            </form>
            <span class="small text-muted">
                {{ number_format($stats['total_orders']) }} đơn ·
                {{ $formatQuantity($stats['total_quantity']) }} sản phẩm ·
                {{ number_format($stats['total_value'], 0, ',', '.') }}đ
            </span>
        </div>

        <div class="monitor-panel mb-3">
            @if($orders->count())
                <div class="monitor-sequences" aria-label="Điều hướng nhanh theo số thứ tự đơn">
                    @foreach($orders->getCollection()->sortBy(fn ($order) => $order->daily_sequence ?? PHP_INT_MAX) as $sequenceOrder)
                        <a class="monitor-sequence" href="#monitor-order-{{ $sequenceOrder->id }}" title="{{ $sequenceOrder->customer?->name ?? $sequenceOrder->code }}">
                            {{ $sequenceOrder->daily_sequence ?? $loop->iteration }}
                        </a>
                    @endforeach
                </div>
            @else
                <div class="px-3 py-2 small text-muted">Không có số thứ tự đơn trong ngày đã chọn.</div>
            @endif
        </div>

        <div class="monitor-panel mb-3">
            <div class="monitor-summary-head">
                <button class="monitor-summary-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#monitorProductSummary" aria-expanded="true">
                    <i class="bi bi-chevron-up me-1"></i>Ẩn / hiện chi tiết hàng hóa
                </button>
                <div class="monitor-sort-group">
                    <a href="{{ $sortUrl('created_at') }}" class="btn btn-sm btn-outline-secondary">Ngày tạo <i class="bi bi-arrow-down-up"></i></a>
                    <a href="{{ $sortUrl('total') }}" class="btn btn-sm btn-outline-secondary">Tổng tiền <i class="bi bi-arrow-down-up"></i></a>
                    <a href="{{ $sortUrl('customer_name') }}" class="btn btn-sm btn-outline-secondary">Khách hàng <i class="bi bi-arrow-down-up"></i></a>
                    <a href="{{ $sortUrl('status') }}" class="btn btn-sm btn-outline-secondary">Trạng thái <i class="bi bi-arrow-down-up"></i></a>
                </div>
            </div>
            <div class="collapse show" id="monitorProductSummary">
                <div class="monitor-summary-table table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Tổng</th>
                                <th>Size</th>
                                <th>Đơn giá</th>
                                <th class="text-end">Tạm tính</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productRows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $row['name'] }}</td>
                                    <td>{{ $formatQuantity($row['quantity']) }}</td>
                                    <td>{{ $formatQuantity($row['total']) }} {{ $row['unit'] }}</td>
                                    <td>{{ $row['size'] }}</td>
                                    <td>{{ number_format($row['price'], 0, ',', '.') }}đ</td>
                                    <td class="text-end fw-semibold">{{ number_format($row['subtotal'], 0, ',', '.') }}đ</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">Không có hàng hóa phù hợp.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('pages.my_orders.monitoring') }}" class="monitor-panel p-2 mb-3">
            <div class="row g-2 align-items-center">
                <input type="hidden" name="date" value="{{ $selectedDate }}">
                <div class="col-lg-5">
                    <input type="text" name="keyword" class="form-control form-control-sm" value="{{ $keyword }}" placeholder="Mã đơn, khách hàng, sale, shipper...">
                </div>
                <div class="col-lg-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tất cả trạng thái</option>
                        @foreach($statusLabels as $statusKey => $statusName)
                            <option value="{{ $statusKey }}" @selected($selectedStatus === $statusKey)>{{ $statusName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <select name="per_page" class="form-select form-select-sm">
                        @foreach([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }} đơn / trang</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-grid">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Lọc dữ liệu</button>
                </div>
            </div>
        </form>

        <div class="monitor-panel monitor-bulk-actions mb-3">
            <div>
                @if($canApproveAnyOrder)
                    <form method="POST" action="{{ route('pages.my_orders.monitoring.approve_all') }}" onsubmit="return confirm('Duyệt tất cả đơn đang tới lượt bạn theo bộ lọc hiện tại?');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="keyword" value="{{ $keyword }}">
                        <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        <input type="hidden" name="sale_id" value="{{ $selectedSaleId }}">
                        <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-check2-all me-1"></i>Duyệt tất cả
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('pages.my_orders.monitoring.refresh_sequence') }}" onsubmit="return confirm('Cập nhật lại số thứ tự ưu tiên cho các đơn đang thiếu số?');">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="keyword" value="{{ $keyword }}">
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                    <input type="hidden" name="sale_id" value="{{ $selectedSaleId }}">
                    <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                </form>
            </div>
            <button type="button" class="btn btn-sm btn-success" id="monitorOpenCreate">
                <i class="bi bi-plus-circle me-1"></i>Thêm đơn
            </button>
        </div>

        <section class="monitor-panel monitor-create" id="monitorCreateOrder" hidden aria-label="Tạo đơn hàng mới">
            <div class="monitor-create-head d-flex align-items-center justify-content-between gap-2">
                <h2>Tạo đơn hàng mới</h2>
                <button type="button" class="btn-close" id="monitorCloseCreate" aria-label="Đóng"></button>
            </div>
            <div class="monitor-create-steps" aria-label="Các bước tạo đơn">
                @foreach(['Chọn sản phẩm', 'Chọn khách hàng', 'Xác nhận', 'Hoàn thành'] as $createStep)
                    <div class="monitor-create-step {{ $loop->first ? 'is-active' : '' }}" data-create-step-indicator="{{ $loop->iteration }}">
                        <span class="monitor-create-step-number">{{ $loop->iteration }}</span>
                        <span>{{ $createStep }}</span>
                    </div>
                @endforeach
            </div>
            <div class="monitor-create-body">
                <div class="monitor-create-pane" data-create-pane="1">
                    <div class="monitor-create-search">
                        <input type="search" class="form-control form-control-sm" id="monitorVariantSearch" placeholder="Tìm sản phẩm, SKU hoặc size...">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="monitorVariantSearchButton"><i class="bi bi-search me-1"></i>Tìm</button>
                    </div>
                    <div id="monitorVariantResults" class="mb-3">
                        <div class="text-center text-muted py-4">Đang tải danh sách sản phẩm...</div>
                    </div>
                    <div class="monitor-selected-table">
                        <div class="px-3 pt-3 fw-bold">Sản phẩm đã chọn</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Sản phẩm</th><th>Size</th><th>Đơn giá</th><th>Số lượng</th><th></th></tr></thead>
                                <tbody id="monitorSelectedItems"><tr><td colspan="5" class="text-center text-muted py-3">Chưa chọn sản phẩm.</td></tr></tbody>
                            </table>
                        </div>
                        <div class="monitor-create-total">Tạm tính: <span id="monitorCreateTotal">0đ</span></div>
                    </div>
                    <div class="monitor-create-actions">
                        <button type="button" class="btn btn-sm btn-success" data-create-next="2">Chọn khách hàng <i class="bi bi-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <div class="monitor-create-pane" data-create-pane="2" hidden>
                    <div class="monitor-create-search">
                        <input type="search" class="form-control form-control-sm" id="monitorCustomerSearch" placeholder="Tìm theo tên, số điện thoại hoặc email...">
                        <button type="button" class="btn btn-sm btn-primary" id="monitorCustomerSearchButton"><i class="bi bi-search me-1"></i>Lọc</button>
                    </div>
                    <div id="monitorSelectedCustomer" class="monitor-customer-selected mb-3" hidden></div>
                    <div id="monitorCustomerResults"><div class="text-center text-muted py-4">Đang tải danh sách khách hàng...</div></div>
                    <div class="monitor-create-actions justify-content-between">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-create-back="1"><i class="bi bi-arrow-left me-1"></i>Sản phẩm</button>
                        <button type="button" class="btn btn-sm btn-success" data-create-next="3">Xác nhận <i class="bi bi-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <div class="monitor-create-pane" data-create-pane="3" hidden>
                    <div class="monitor-confirm-grid">
                        <div class="monitor-confirm-card">
                            <h3>Khách hàng</h3>
                            <div id="monitorConfirmCustomer"></div>
                            <div class="mt-3">
                                <label class="form-label small fw-bold" for="monitorRecipientAddress">Địa chỉ nhận hàng</label>
                                <textarea class="form-control form-control-sm" id="monitorRecipientAddress" rows="2"></textarea>
                            </div>
                            <div class="mt-2">
                                <label class="form-label small fw-bold" for="monitorDeliveryTime">Giờ giao hàng</label>
                                <input class="form-control form-control-sm" id="monitorDeliveryTime" placeholder="Ví dụ: 9h-11h hoặc sau 17h">
                            </div>
                            <div class="mt-2">
                                <label class="form-label small fw-bold" for="monitorOrderNote">Ghi chú</label>
                                <textarea class="form-control form-control-sm" id="monitorOrderNote" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="monitor-confirm-card">
                            <h3>Sản phẩm</h3>
                            <div id="monitorConfirmItems"></div>
                            <div class="border-top mt-2 pt-2 text-end fw-bold text-success">Tổng cộng: <span id="monitorConfirmTotal">0đ</span></div>
                        </div>
                    </div>
                    <div class="monitor-create-actions justify-content-between">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-create-back="2"><i class="bi bi-arrow-left me-1"></i>Khách hàng</button>
                        <button type="button" class="btn btn-sm btn-warning fw-bold" id="monitorSubmitOrder"><i class="bi bi-check2 me-1"></i>Tạo đơn</button>
                    </div>
                </div>

                <div class="monitor-create-pane monitor-finish" data-create-pane="4" hidden>
                    <i class="bi bi-check-circle-fill monitor-finish-icon"></i>
                    <h3 class="mt-2">Tạo đơn hàng thành công</h3>
                    <p class="text-muted" id="monitorFinishMessage"></p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="#" class="btn btn-sm btn-outline-primary" id="monitorCreatedOrderLink">Xem chi tiết</a>
                        <a href="#" class="btn btn-sm btn-success" id="monitorBackToOrders">Về danh sách đơn</a>
                    </div>
                </div>
            </div>
        </section>

        <div class="monitor-layout">
            <aside class="monitor-sidebar">
                <div class="monitor-panel monitor-filter-block">
                    <div class="monitor-filter-title">Sale</div>
                    <div class="monitor-filter-list">
                        <a class="monitor-filter-link {{ $selectedSaleId === 0 ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', $saleFilterQuery) }}">
                            <span>Tất cả Sale</span><span class="monitor-filter-count">{{ $saleFilters->sum('count') }}</span>
                        </a>
                        @foreach($saleFilters as $sale)
                            <a class="monitor-filter-link {{ $selectedSaleId === $sale['id'] ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', array_merge($saleFilterQuery, ['sale_id' => $sale['id']])) }}">
                                <span>{{ $sale['name'] }}</span><span class="monitor-filter-count">{{ $sale['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="monitor-panel monitor-filter-block">
                    <div class="monitor-filter-title">Khách hàng</div>
                    <div class="monitor-filter-list">
                        <a class="monitor-filter-link {{ $selectedCustomerId === 0 ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', $customerFilterQuery) }}">
                            <span>Tất cả khách hàng</span><span class="monitor-filter-count">{{ $customerFilters->sum('count') }}</span>
                        </a>
                        @foreach($customerFilters as $customer)
                            <a class="monitor-filter-link {{ $selectedCustomerId === $customer['id'] ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', array_merge($customerFilterQuery, ['customer_id' => $customer['id']])) }}">
                                <span>{{ $customer['name'] }}</span><span class="monitor-filter-count">{{ $customer['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>

            <main>
                <div class="monitor-orders">
                    @forelse($orders as $order)
                        @php
                            $canApprove = $canApproveByOrder[$order->id] ?? false;
                            $hasInvalidSizeItems = $order->items->contains(
                                fn ($item) => (float) ($item->effective_unit_weight ?? 0) <= 0
                            );
                            $timelineIndex = $timelineMap[$order->status] ?? 0;
                            $timelinePercent = ($timelineIndex / 4) * 100;
                            $deliveryAddress = $order->recipient_address ?: ($order->customer?->address ?: 'Chưa cập nhật địa chỉ');
                            $deliveryTime = $order->delivery_time ?: ($order->customer?->delivery_time ?: 'Chưa cập nhật');
                            $canManageOrder = (int) $order->user_id === (int) auth()->id();
                            $isEditable = $canManageOrder && (
                                !empty($order->copied_from_order_id)
                                || ($order->status === \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL && $order->created_at?->isToday())
                            );
                            $canCancel = $canManageOrder
                                && $order->created_at?->isToday()
                                && in_array($order->status, \App\Models\Order::CANCELLABLE_STATUSES, true);
                        @endphp
                        <article class="monitor-panel monitor-order" id="monitor-order-{{ $order->id }}">
                            <div class="monitor-order-main">
                                <div class="monitor-order-head">
                                    <div class="monitor-order-person">
                                        <div class="monitor-order-number">{{ $order->daily_sequence ?? $loop->iteration }}</div>
                                        <div>
                                            <div class="monitor-order-name">{{ $order->customer?->name ?? 'Khách hàng' }}</div>
                                            <div class="monitor-order-code">
                                                {{ $order->code ?: ('#' . $order->id) }}
                                                · Sale: {{ $order->user?->name ?? '—' }}
                                                · {{ $order->created_at?->format('H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="monitor-timeline">
                                        <div class="monitor-timeline-track">
                                            <div class="monitor-timeline-progress" style="width: {{ $timelinePercent }}%"></div>
                                            @foreach($timelineSteps as $stepIndex => $stepName)
                                                <span class="monitor-timeline-dot {{ $stepIndex < $timelineIndex ? 'done' : ($stepIndex === $timelineIndex ? 'current' : '') }}" title="{{ $stepName }}"></span>
                                            @endforeach
                                        </div>
                                        <div class="monitor-timeline-labels">
                                            @foreach($timelineSteps as $stepName)<span>{{ $stepName }}</span>@endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="monitor-meta">
                                    <span><i class="bi bi-geo-alt me-1"></i>Địa chỉ nhận hàng: {{ $deliveryAddress }}</span>
                                    <span><i class="bi bi-clock me-1"></i>Giờ giao: {{ $deliveryTime }}</span>
                                    @if($order->shipper)
                                        <span><i class="bi bi-truck me-1"></i>Shipper: {{ $order->shipper->name }}</span>
                                    @endif
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm monitor-items">
                                        <thead>
                                            <tr>
                                                <th>Sản phẩm</th>
                                                <th class="text-end">SL</th>
                                                <th class="text-end">Size</th>
                                                <th class="text-end">Tổng</th>
                                                <th class="text-end">Đơn giá</th>
                                                <th class="text-end">Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($order->items as $item)
                                                @php
                                                    $itemName = $item->product?->name
                                                        ?? $item->variant?->product?->name
                                                        ?? $item->variant?->name
                                                        ?? 'Sản phẩm';
                                                    $lineTotal = (float) ($item->total ?? 0);
                                                    if ($lineTotal <= 0) {
                                                        $lineTotal = (float) ($item->quantity ?? 0) * (float) ($item->price ?? 0);
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="fw-semibold">{{ $itemName }}</td>
                                                    <td class="text-end">{{ $formatQuantity($item->quantity) }}</td>
                                                    <td class="text-end">{{ $item->variant?->size ?? '-' }}</td>
                                                    <td class="text-end fw-semibold">{{ $item->display_total_label }}</td>
                                                    <td class="text-end">{{ number_format((float) $item->price, 0, ',', '.') }}đ</td>
                                                    <td class="text-end fw-semibold">{{ number_format($lineTotal, 0, ',', '.') }}đ</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="text-center text-muted">Đơn chưa có sản phẩm.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="monitor-order-total">{{ number_format((float) $order->total, 0, ',', '.') }}đ</div>
                            </div>
                            <div class="monitor-order-footer">
                                <span class="monitor-status">{{ $statusLabels[$order->status] ?? str_replace('_', ' ', $order->status) }}</span>
                                <div class="monitor-actions">
                                    @if($canApprove)
                                        <form method="POST" action="{{ route('site.orders.approve', $order) }}" class="js-monitor-approval-form">
                                            @csrf
                                            <input type="hidden" name="note" value="Duyệt từ trang theo dõi đơn hàng">
                                            <button type="submit" class="btn btn-sm btn-success" @disabled($hasInvalidSizeItems)
                                                @if($hasInvalidSizeItems) title="Có sản phẩm chưa có size hoặc khối lượng quy đổi bằng 0." @endif>
                                                <i class="bi bi-check2 me-1"></i>Duyệt
                                            </button>
                                        </form>
                                        @if($hasInvalidSizeItems)
                                            <div class="small text-danger">Size/KL = 0</div>
                                        @endif
                                    @endif
                                    <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#monitorExtra{{ $order->id }}">
                                        <i class="bi bi-eye me-1"></i>Chi tiết
                                    </button>
                                    @if($isEditable)
                                        <a class="btn btn-sm btn-outline-warning" href="{{ route('site.orders.edit', $order) }}"><i class="bi bi-pencil me-1"></i>Sửa đơn</a>
                                    @endif
                                    @if($canManageOrder)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('site.orders.copy', $order->id) }}"><i class="bi bi-files me-1"></i>Copy đơn</a>
                                        <a class="btn btn-sm btn-warning" href="{{ route('site.order-adjustments.create', $order) }}"><i class="bi bi-arrow-left-right me-1"></i>Yêu cầu điều chỉnh</a>
                                        @if($canCancel)
                                            <form method="POST" action="{{ route('site.orders.cancel', $order) }}" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Hủy đơn hàng</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="collapse" id="monitorExtra{{ $order->id }}">
                                <div class="px-3 py-2 border-top small text-muted">
                                    <div><strong>Điện thoại:</strong> {{ $order->recipient_phone ?: ($order->customer?->phone ?: 'Chưa cập nhật') }}</div>
                                    <div><strong>Ghi chú:</strong> {{ $order->note ?: 'Không có ghi chú' }}</div>
                                    <div><strong>Thanh toán:</strong> {{ ucfirst((string) ($order->payment_status ?: 'unpaid')) }}</div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="monitor-panel monitor-empty">
                            <i class="bi bi-inbox fs-1"></i>
                            <h5 class="mt-2">Không có đơn hàng phù hợp</h5>
                            <p class="mb-0">Hãy chọn ngày hoặc thay đổi bộ lọc.</p>
                        </div>
                    @endforelse
                </div>

                @if($orders->hasPages())
                    <div class="monitor-pagination">{{ $orders->links('pagination::bootstrap-5') }}</div>
                @endif
            </main>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const createPanel = document.getElementById('monitorCreateOrder');
    const openButton = document.getElementById('monitorOpenCreate');
    if (!createPanel || !openButton) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const variantEndpoint = @json(route('site.orders.variants.ajax'));
    const customerEndpoint = @json(route('site.orders.customers.ajax'));
    const storeEndpoint = @json(route('pages.my_orders.monitoring.store'));
    const selectedItems = new Map();
    let selectedCustomer = null;
    let variantsLoaded = false;
    let customersLoaded = false;

    const money = value => new Intl.NumberFormat('vi-VN').format(Math.round(Number(value) || 0)) + 'đ';
    const itemLineTotal = item => item.price * item.quantity * (item.isPricedByKg ? item.weight : 1);
    const orderTotal = () => Array.from(selectedItems.values()).reduce((sum, item) => sum + itemLineTotal(item), 0);
    const notify = (message, type = 'error') => {
        if (typeof window.showToast === 'function') window.showToast(message, type);
        else window.alert(message);
    };

    function setStep(step) {
        createPanel.querySelectorAll('[data-create-pane]').forEach(pane => {
            pane.hidden = Number(pane.dataset.createPane) !== step;
        });
        createPanel.querySelectorAll('[data-create-step-indicator]').forEach(indicator => {
            const value = Number(indicator.dataset.createStepIndicator);
            indicator.classList.toggle('is-active', value === step);
            indicator.classList.toggle('is-done', value < step);
        });
        if (step === 3) renderConfirmation();
        createPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderItems() {
        const body = document.getElementById('monitorSelectedItems');
        if (!selectedItems.size) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Chưa chọn sản phẩm.</td></tr>';
        } else {
            body.innerHTML = Array.from(selectedItems.values()).map(item => `
                <tr data-selected-variant="${item.id}">
                    <td><strong>${escapeHtml(item.name)}</strong><div class="small text-muted">${escapeHtml(item.sku || '')}</div></td>
                    <td>${escapeHtml(item.size || '—')}</td>
                    <td>${money(item.price)}</td>
                    <td><input type="number" class="form-control form-control-sm monitor-item-quantity" min="1" max="100000" value="${item.quantity}"></td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger monitor-remove-item" aria-label="Xóa"><i class="bi bi-x"></i></button></td>
                </tr>`).join('');
        }
        document.getElementById('monitorCreateTotal').textContent = money(orderTotal());
    }

    function escapeHtml(value) {
        const node = document.createElement('div');
        node.textContent = String(value ?? '');
        return node.innerHTML;
    }

    async function loadVariants(url = variantEndpoint, search = null) {
        const results = document.getElementById('monitorVariantResults');
        const target = new URL(url, window.location.origin);
        target.searchParams.set('per_page', target.searchParams.get('per_page') || '10');
        if (search !== null) {
            target.searchParams.set('search', search);
            target.searchParams.set('page', '1');
        }
        results.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Đang tải sản phẩm...</div>';
        try {
            const response = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error('Không thể tải danh sách sản phẩm.');
            results.innerHTML = data.html;
            variantsLoaded = true;
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error.message)}</div>`;
        }
    }

    async function loadCustomers(page = 1) {
        const results = document.getElementById('monitorCustomerResults');
        const target = new URL(customerEndpoint, window.location.origin);
        target.searchParams.set('mode', 'single');
        target.searchParams.set('scope', 'my_customers');
        target.searchParams.set('q', document.getElementById('monitorCustomerSearch').value.trim());
        target.searchParams.set('per_page', '15');
        target.searchParams.set('page', String(page));
        results.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Đang tải khách hàng...</div>';
        try {
            const response = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.html) throw new Error('Không thể tải danh sách khách hàng.');
            results.innerHTML = data.html;
            customersLoaded = true;
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error.message)}</div>`;
        }
    }

    function renderSelectedCustomer() {
        const preview = document.getElementById('monitorSelectedCustomer');
        preview.hidden = !selectedCustomer;
        if (!selectedCustomer) return;
        preview.innerHTML = `<strong>${escapeHtml(selectedCustomer.name)}</strong>
            <div class="small text-muted"><i class="bi bi-telephone me-1"></i>${escapeHtml(selectedCustomer.phone || 'Chưa có SĐT')}</div>
            <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>${escapeHtml(selectedCustomer.address || 'Chưa có địa chỉ')}</div>`;
    }

    function renderConfirmation() {
        if (!selectedCustomer) return;
        document.getElementById('monitorConfirmCustomer').innerHTML = `<strong>${escapeHtml(selectedCustomer.name)}</strong>
            <div>${escapeHtml(selectedCustomer.phone || 'Chưa có SĐT')}</div>`;
        document.getElementById('monitorRecipientAddress').value = selectedCustomer.address || '';
        document.getElementById('monitorConfirmItems').innerHTML = Array.from(selectedItems.values()).map(item => `
            <div class="d-flex justify-content-between gap-2 border-bottom py-2 small">
                <span><strong>${escapeHtml(item.name)}</strong> · ${escapeHtml(item.size || '—')} × ${item.quantity}</span>
                <strong>${money(itemLineTotal(item))}</strong>
            </div>`).join('');
        document.getElementById('monitorConfirmTotal').textContent = money(orderTotal());
    }

    openButton.addEventListener('click', () => {
        createPanel.hidden = false;
        setStep(1);
        if (!variantsLoaded) loadVariants();
    });
    document.getElementById('monitorCloseCreate').addEventListener('click', () => { createPanel.hidden = true; });
    document.getElementById('monitorVariantSearchButton').addEventListener('click', () => loadVariants(variantEndpoint, document.getElementById('monitorVariantSearch').value.trim()));
    document.getElementById('monitorVariantSearch').addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); document.getElementById('monitorVariantSearchButton').click(); }
    });
    document.getElementById('monitorCustomerSearchButton').addEventListener('click', () => loadCustomers());
    document.getElementById('monitorCustomerSearch').addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); loadCustomers(); }
    });

    createPanel.addEventListener('click', event => {
        const add = event.target.closest('#monitorVariantResults .add-variant-to-cart');
        if (add) {
            event.preventDefault();
            const id = Number(add.dataset.variantId);
            if (!selectedItems.has(id)) {
                selectedItems.set(id, {
                    id,
                    name: add.dataset.variantName || 'Sản phẩm',
                    sku: add.dataset.variantSku || '',
                    size: add.dataset.variantSize || '',
                    price: Number(add.dataset.variantPrice) || 0,
                    weight: Number(add.dataset.variantWeight) || 1,
                    isPricedByKg: add.dataset.variantIsPricedByKg === '1',
                    quantity: 1
                });
                renderItems();
            }
            add.classList.remove('btn-primary');
            add.classList.add('btn-success');
            add.innerHTML = '<i class="bi bi-check2 me-1"></i>Đã chọn';
            return;
        }

        const variantPage = event.target.closest('#monitorVariantResults .pagination a');
        if (variantPage) { event.preventDefault(); loadVariants(variantPage.href); return; }

        const remove = event.target.closest('.monitor-remove-item');
        if (remove) {
            selectedItems.delete(Number(remove.closest('[data-selected-variant]').dataset.selectedVariant));
            renderItems();
            return;
        }

        const customerButton = event.target.closest('#monitorCustomerResults .select-customer-btn');
        if (customerButton) {
            selectedCustomer = {
                id: Number(customerButton.dataset.customerId),
                name: customerButton.dataset.customerName || 'Khách hàng',
                phone: customerButton.dataset.customerPhone || '',
                email: customerButton.dataset.customerEmail || '',
                address: customerButton.dataset.customerAddress || ''
            };
            renderSelectedCustomer();
            return;
        }

        const customerPage = event.target.closest('#monitorCustomerResults .customer-page-btn');
        if (customerPage && !customerPage.disabled) { loadCustomers(Number(customerPage.dataset.page) || 1); return; }

        const next = event.target.closest('[data-create-next]');
        if (next) {
            const step = Number(next.dataset.createNext);
            if (step === 2 && !selectedItems.size) { notify('Vui lòng chọn ít nhất một sản phẩm.'); return; }
            if (step === 2 && !customersLoaded) loadCustomers();
            if (step === 3 && !selectedCustomer) { notify('Vui lòng chọn khách hàng.'); return; }
            setStep(step);
            return;
        }

        const back = event.target.closest('[data-create-back]');
        if (back) setStep(Number(back.dataset.createBack));
    });

    createPanel.addEventListener('input', event => {
        const input = event.target.closest('.monitor-item-quantity');
        if (!input) return;
        const item = selectedItems.get(Number(input.closest('[data-selected-variant]').dataset.selectedVariant));
        if (item) {
            item.quantity = Math.max(1, Number.parseInt(input.value || '1', 10));
            document.getElementById('monitorCreateTotal').textContent = money(orderTotal());
        }
    });

    createPanel.addEventListener('change', event => {
        if (event.target.matches('#monitorVariantResults #per-page-select')) {
            const target = new URL(variantEndpoint, window.location.origin);
            target.searchParams.set('search', document.getElementById('monitorVariantSearch').value.trim());
            target.searchParams.set('per_page', event.target.value);
            loadVariants(target);
        }
    });

    document.getElementById('monitorSubmitOrder').addEventListener('click', async event => {
        const button = event.currentTarget;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang tạo...';
        try {
            const response = await fetch(storeEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    customer_id: selectedCustomer.id,
                    items: Array.from(selectedItems.values()).map(item => ({ variant_id: item.id, quantity: item.quantity })),
                    recipient_address: document.getElementById('monitorRecipientAddress').value.trim(),
                    delivery_time: document.getElementById('monitorDeliveryTime').value.trim(),
                    note: document.getElementById('monitorOrderNote').value.trim()
                })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(validationMessage || data.message || 'Không thể tạo đơn hàng.');
            }
            document.getElementById('monitorFinishMessage').textContent = data.message;
            document.getElementById('monitorCreatedOrderLink').href = data.order.url;
            document.getElementById('monitorBackToOrders').href = data.monitoring_url;
            setStep(4);
            notify(data.message, 'success');
        } catch (error) {
            notify(error.message || 'Không thể kết nối máy chủ.');
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-check2 me-1"></i>Tạo đơn';
        }
    });

    const highlightedOrder = new URLSearchParams(window.location.search).get('highlight');
    if (highlightedOrder) {
        const card = document.getElementById(`monitor-order-${highlightedOrder}`);
        if (card) {
            card.style.boxShadow = '0 0 0 3px rgba(245, 158, 11, .35), 0 8px 24px rgba(15, 23, 42, .08)';
            setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'center' }), 250);
        }
    }
})();

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.js-monitor-approval-form');
    if (!form) return;

    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    if (button) button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Không thể duyệt đơn.');
        }

        showToast(data.message || 'Đã duyệt đơn.', 'success');
        window.location.reload();
    } catch (error) {
        if (button) button.disabled = false;
        showToast(error.message || 'Không thể kết nối máy chủ.', 'error');
    }
});
</script>
@endpush
