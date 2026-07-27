@extends(($monitoringEmbedded ?? false) ? 'layouts.embedded' : 'layouts.site')

@push('styles')
<style>
    .orders-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .orders-shell {
        max-width: 1180px;
        margin: 0 auto;
    }
    .orders-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 9px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(21, 34, 56, 0.18);
        overflow: hidden;
        position: relative;
    }
    .orders-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        top: -60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .orders-kpi {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 9px;
        padding: 18px;
        min-height: 100%;
        backdrop-filter: blur(6px);
    }
    .orders-kpi-label {
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.68);
        margin-bottom: 8px;
    }
    .orders-kpi-value {
        font-size: 1.7rem;
        font-weight: 800;
        line-height: 1;
    }
    .orders-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 9px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }
    .orders-filter {
        padding: 24px;
    }
    .orders-filter .form-control,
    .orders-filter .form-select {
        height: 48px;
        border-radius: 9px;
        border-color: #d8deea;
    }
    .orders-filter .btn {
        height: 48px;
        border-radius: 9px;
        font-weight: 700;
    }
    .orders-monitor-filter-form {
        display: grid;
        grid-template-columns: minmax(150px, 1.15fr) minmax(150px, 1.15fr) minmax(145px, 1fr) minmax(145px, 1fr) auto auto;
        gap: 18px;
        align-items: end;
    }
    .orders-monitor-filter-form .orders-monitor-action .btn {
        min-width: 126px;
        white-space: nowrap;
    }
    .orders-customer-filter {
        grid-column: 1 / -1;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }
    .orders-customer-filter .btn {
        min-width: 258px;
        color: #087b72;
        border-color: #087b72;
    }
    .orders-customer-filter .btn:hover,
    .orders-customer-filter .btn:focus {
        color: #fff;
        background: #087b72;
    }
    .orders-selected-customer {
        color: #475569;
        font-size: .82rem;
    }
    .orders-customer-modal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 12px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .2);
    }
    .orders-customer-modal .modal-header {
        border-bottom-color: #e7edf4;
        background: #f8fafc;
    }
    .orders-customer-modal .modal-footer {
        border-top-color: #e7edf4;
    }
    .orders-side-panel {
        position: sticky;
        top: 84px;
    }
    .orders-side-head {
        padding: 22px 22px 12px;
        border-bottom: 1px solid #eef2f7;
    }
    .orders-side-body {
        padding: 14px 22px 22px;
    }
    .customer-picker {
        border: 1px solid #e5eaf3;
        border-radius: 9px;
        background: #f8fafc;
        padding: 14px;
    }
    .customer-collapse-toggle {
        height: 40px !important;
        border-radius: 9px !important;
        font-weight: 700;
    }
    .customer-collapse-action {
        font-size: .78rem;
        font-weight: 700;
        color: #1d4ed8;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .customer-list-scroll {
        max-height: 280px;
        overflow-y: auto;
        border: 1px solid #e5eaf3;
        border-radius: 9px;
        background: #fff;
        padding: 8px;
    }
    .customer-list-item {
        border: 1px solid #d8deea;
        background: #fff;
        border-radius: 9px;
        padding: 8px 10px;
        white-space: normal;
        transition: all .2s ease;
        cursor: pointer;
        margin-bottom: 6px;
    }
    .customer-list-item:last-child {
        margin-bottom: 0;
    }
    .customer-list-item .form-check-input {
        margin-top: 3px;
    }
    .customer-list-name {
        display: block;
        font-size: .93rem;
        line-height: 1.25;
        color: #0f172a;
    }
    .customer-list-item:hover {
        border-color: #91a4c7;
    }


    .customer-list-item.active {
        border-color: #1d4ed8;
        box-shadow: inset 0 0 0 1px rgba(29, 78, 216, 0.18);
        background: #eff6ff;
    }
    .orders-section-head {
        padding: 22px 24px 0;
    }
    .my-orders-sequence-nav {
        position: sticky;
        top: 84px;
        z-index: 90;
        margin: 16px 18px 0;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 15px rgba(15, 23, 42, .08);
    }
    .my-orders-sequence-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 9px;
        border: 0;
        border-radius: 999px;
        color: #fff;
        font-size: .9rem;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 2px 4px rgba(15, 23, 42, .16);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .my-orders-sequence-pill:hover,
    .my-orders-sequence-pill:focus {
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 9px rgba(15, 23, 42, .22);
    }
    .my-orders-sequence-pill.is-unpacked {
        background: #64748b;
    }
    .my-orders-sequence-pill.is-packing {
        background: #f59e0b;
        color: #212529;
    }
    .my-orders-sequence-pill.is-packed {
        background: #198754;
    }
    .my-orders-sequence-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 54px;
        height: 54px;
        border-radius: 12px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 1.5rem;
        font-weight: 900;
        line-height: 1;
    }
    .my-orders-sequence-badge small {
        display: block;
        font-size: .55rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    [id^="my-order-card-"] {
        scroll-margin-top: 155px;
    }
    .orders-table-wrap {
        padding: 0 18px 18px;
    }
    .orders-table {
        margin-bottom: 0;
        min-width: 920px;
    }
    .orders-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        border-bottom: 1px solid #e8edf5;
        padding: 16px 14px;
        white-space: nowrap;
    }
    .orders-table tbody td {
        padding: 18px 14px;
        border-color: #edf2f7;
        vertical-align: middle;
    }
    .orders-code {
        font-weight: 800;
        color: #1e293b;
    }
    .orders-subtle {
        font-size: .82rem;
        color: #64748b;
    }
    .orders-products {
        display: grid;
        gap: 6px;
    }
    .orders-products-head {
        display: grid;
        grid-template-columns: 2fr 80px 120px 120px 120px;
        gap: 8px;
        border: 1px solid #dbe4ef;
        border-radius: 8px;
        background: #eef3f9;
        padding: 6px 10px;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #475569;
        font-weight: 700;
    }
    .orders-product-line {
        display: grid;
        grid-template-columns: 2fr 80px 120px 120px 120px;
        gap: 8px;
        border: 1px solid #e5eaf3;
        border-radius: 8px;
        padding: 6px 10px;
        background: #f8fafc;
        font-size: .8rem;
        align-items: center;
    }
    .orders-product-name {
        color: #0f172a;
        font-weight: 600;
    }
    .orders-product-cell {
        color: #334155;
        text-align: right;
        white-space: nowrap;
    }
    .orders-product-qty {
        color: #334155;
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
    }
    .orders-product-total {
        color: #0f172a;
        font-weight: 700;
    }
    .mc-customer-card {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: box-shadow 0.2s ease;
    }
    .mc-customer-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }
    .row-table {
        display: grid;
        grid-template-columns: 102px minmax(106px, 1.4fr) 88px;
        gap: 4px;
        align-items: center;
    }
    .row-title {   
        color: #64748b; 
        padding-right: 5px;
    }
    .customer-body{  
        padding: 0;
    }
    .customer-tax-body{  
        padding: 0 0 0 14px;
    }
    .transport-info-body{  
        padding: 0 0 0 14px;
    }
    .logistics-body {  
         
    }
    .transport-body {  
        padding: 0 0 0 14px;
    }
    .wh-order-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding-bottom: 7px;
        border-bottom: 1px solid #eef2f7;
    }
    .wh-meta-label {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .wh-meta-value {
        font-size: .92rem;
        font-weight: 700;
        color: #0f172a;
    }
    .wh-section {
        padding: 0 0 9px 0;
        border-top: 1px dashed #e2e8f0;
    }
    .transport-info {
        margin-top: 12px;
    }
    .transport-info-tax {
        margin-top: 12px;
    }
    .wh-logistics-title, .logistics-title, .customer-title, .customer-tax-title,.transport-title {
        font-size: .78rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    
    .wh-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .wh-item-table-wrap {
        overflow-x: auto;
    }
    .wh-item-table-head,
    .wh-item-table-row {
        display: grid;
        grid-template-columns: 48px minmax(180px, 1.4fr) 50px 50px 49px 76px 85px;
        gap: 8px;
        align-items: center;
    }
    .wh-item-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 6px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .wh-item-row {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .wh-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .wh-item-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #fff;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }
    .wh-item-thumb-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        margin-left: auto;
        margin-right: auto;
    }
    .wh-item-name {
        font-size: .86rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
    }
    .wh-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: center;
    }
    .wh-item-cell strong {
        color: #0f172a;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
    }
    .status-pending { background: #fff7ed; color: #c2410c; }
    .status-progress { background: #eff6ff; color: #1d4ed8; }
    .status-success { background: #ecfdf5; color: #047857; }
    .status-danger { background: #fef2f2; color: #b91c1c; }
    .status-muted { background: #f1f5f9; color: #475569; }
    .orders-total {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
    }
    .orders-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .orders-actions .btn {
        border-radius: 4px;
        font-weight: 700;
        padding: 6px 7px;
    }
    .orders-mobile-list {
        display: none;
        padding: 0 16px 16px;
    }
    .orders-mobile-card {
        border: 1px solid #e7ecf3;
        border-radius: 22px;
        padding: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    }
    .orders-mobile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .orders-mobile-item span {
        display: block;
        font-size: .75rem;
        color: #64748b;
        margin-bottom: 4px;
    }
    .orders-empty {
        padding: 42px 24px 52px;
        text-align: center;
        color: #64748b;
    }
    .order-total-table-wrap {
        overflow-x: auto;
    }
    .order-total-table-head {
        display: grid;
        grid-template-columns: 84px minmax(106px, 1.4fr) 88px;
        gap: 4px;
        align-items: center;
    }


    @media (max-width: 991.98px) {
        .orders-hero {
            padding: 22px;
            border-radius: 24px;
        }
        .orders-side-panel {
            position: static;
        }
        .orders-filter {
            padding: 20px;
        }
        .orders-monitor-filter-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 767.98px) {
        .orders-page {
            padding: 12px 0 48px;
        }
        .orders-shell {
            padding: 0 10px;
        }
        /* Hero */
        .orders-hero {
            padding: 14px;
            border-radius: 16px;
        }
        .orders-hero h4 { font-size: 1rem; margin-bottom: 4px; }
        .orders-kpi-value {
            font-size: 1.15rem;
        }
        .orders-kpi {
            padding: 10px 12px;
            border-radius: 14px;
        }
        /* Filter panel */
        .orders-filter {
            padding: 12px;
        }
        .orders-monitor-filter-form {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .orders-customer-filter {
            grid-column: auto;
        }
        .orders-customer-filter .btn {
            width: 100%;
            min-width: 0;
        }

        /* Table ẩn, mobile list hiện */
        .orders-table-wrap {
            display: none;
        }
        .orders-mobile-list {
            display: block;
            padding: 0;
        }
        .orders-mobile-card {
            border-radius: 14px;
            padding: 12px;
        }
        .orders-mobile-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        /* Order head: stack dọc */
        .wh-order-head {
            flex-direction: column;
            gap: 8px;
            align-items: flex-start;
        }

        /* Item table: scroll ngang thay vì stack */
        .wh-item-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .wh-item-table-head,
        .wh-item-table-row {
            grid-template-columns: 36px minmax(120px, 1.6fr) 44px 44px 44px 64px 72px;
            gap: 5px;
            font-size: .74rem;
        }
        .wh-item-thumb, .wh-item-thumb-placeholder {
            width: 32px;
            height: 32px;
        }
        .wh-item-name { font-size: .79rem; }
        .wh-item-cell { font-size: .74rem; }

        /* Product line (orders-products) stack */
        .orders-products-head,
        .orders-product-line {
            grid-template-columns: 1fr;
            gap: 4px;
        }

        /* Actions: stretch full width */
        .orders-actions {
            justify-content: stretch;
            flex-wrap: wrap;
        }
        .orders-actions .btn {
            flex: 1 1 auto;
            text-align: center;
            padding: 7px 10px;
            font-size: .8rem;
            border-radius: 10px;
        }

        /* Customer list */
        .customer-list-scroll {
            max-height: 200px;
        }

        /* row-table trong collapse sections */
        .row-table {
            grid-template-columns: 90px 1fr;
        }

        /* Collapse sections padding */
        .customer-body, .customer-tax-body,
        .transport-info-body, .logistics-body, .transport-body {
            padding: 0;
        }

        /* Section headings */
        .wh-logistics-title, .logistics-title, .customer-title,
        .customer-tax-title, .transport-title {
            font-size: .72rem;
        }

        /* KPI bar 2 cols */
        .orders-kpi-row {
            grid-template-columns: 1fr 1fr !important;
        }
    }

    @media (max-width: 479.98px) {
        .orders-mobile-grid {
            grid-template-columns: 1fr;
        }
        .row-table {
            grid-template-columns: 80px 1fr;
            font-size: .78rem;
        }
        .orders-actions .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $ordersIndexUrl = ($monitoringEmbedded ?? false)
        ? route('pages.my_orders.monitoring', ['tab' => 'my_orders'])
        : route('pages.my_orders');
    $statusLabels = \App\Models\Order::statusOptions() + [
        \App\Models\Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
        \App\Models\Order::STATUS_PACKING => 'Đang đóng gói',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'Chờ giao đơn vị vận chuyển',
        \App\Models\Order::STATUS_DELIVERING => 'Đang giao hàng',
        \App\Models\Order::STATUS_RETURNING => 'Đang trả hàng',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'Đã nhập kho trả hàng',
        'shipping' => 'Đang vận chuyển',
        'picked_up' => 'Đã lấy hàng',
    ];

    $statusClasses = [
        \App\Models\Order::STATUS_COMPLETED => 'status-success',
        \App\Models\Order::STATUS_DELIVERED => 'status-success',
        \App\Models\Order::STATUS_ORDER_PLACED => 'status-pending',
        \App\Models\Order::STATUS_ORDER_CONFIRMED => 'status-progress',
        \App\Models\Order::STATUS_PACKED => 'status-progress',
        \App\Models\Order::STATUS_IN_DELIVERY => 'status-progress',
        \App\Models\Order::STATUS_READY_TO_PACK => 'status-pending',
        \App\Models\Order::STATUS_PACKING => 'status-progress',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'status-progress',
        \App\Models\Order::STATUS_DELIVERING => 'status-progress',
        \App\Models\Order::STATUS_RETURNING => 'status-danger',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'status-muted',
        \App\Models\Order::STATUS_RETURNED => 'status-danger',
        \App\Models\Order::STATUS_CANCELLED => 'status-danger',
        'shipping' => 'status-progress',
        'picked_up' => 'status-progress',
    ];

    $pageOrders = $orders->getCollection();
    $totalValue = $pageOrders->sum('total');
    $completedCount = $pageOrders->whereIn('status', [\App\Models\Order::STATUS_COMPLETED, \App\Models\Order::STATUS_DELIVERED])->count();
    $returnableCount = $pageOrders->filter(function ($order) {
        return in_array($order->status, ['picked_up', 'shipping', 'completed'], true);
    })->count();

    $currentSortBy = $sortBy ?? request('sort_by', 'created_at');
    $currentSortDir = strtolower($sortDir ?? request('sort_dir', 'desc'));

    $sortDirFor = function (string $field) use ($currentSortBy, $currentSortDir): string {
        if ($currentSortBy === $field && $currentSortDir === 'asc') {
            return 'desc';
        }

        return 'asc';
    };

    $sortIconFor = function (string $field) use ($currentSortBy, $currentSortDir): string {
        if ($currentSortBy !== $field) {
            return 'fa-sort text-muted';
        }

        return $currentSortDir === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc';
    };
@endphp

<section class="orders-page">
    <div class="container orders-shell">
        @unless($monitoringEmbedded ?? false)
        <div class="orders-hero mb-4">
            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-5">
                    <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.65);">Order Center</div>
                    <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Đơn hàng của {{ $user->name }}</h1>
                    <p class="mb-0" style="color:rgba(255,255,255,.8);max-width:520px;">
                        Theo dõi toàn bộ đơn hàng, lọc theo khách hàng và thời gian, đồng thời thao tác nhanh với các đơn đủ điều kiện trả hàng.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="orders-kpi">
                                <div class="orders-kpi-label">Đơn trên trang</div>
                                <div class="orders-kpi-value">{{ $orders->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="orders-kpi">
                                <div class="orders-kpi-label">Giá trị đơn</div>
                                <div class="orders-kpi-value">{{ number_format($totalValue, 0, ',', '.') }}đ</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="orders-kpi">
                                <div class="orders-kpi-label">Đã hoàn tất</div>
                                <div class="orders-kpi-value">{{ $completedCount }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endunless

        <div class="row g-4">
            @unless($monitoringEmbedded ?? false)
            <div class="col-xl-4">
                <div class="orders-panel orders-side-panel">
                     
                    <div class="orders-side-body mx-0 my-0 px-0 py-0">
                        <div class="customer-picker">
                            <div class="mb-3">
                                <label for="customerSearchInput" class="form-label fw-bold">Tìm khách hàng</label>
                                <input
                                    type="text"
                                    id="customerSearchInput"
                                    class="form-control"
                                    value="{{ $customerSearch ?? '' }}"
                                    placeholder="Tìm theo tên, SĐT hoặc email"
                                >
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <div class="small text-muted" id="selectedCustomerLabel">
                                    @if(!empty($selectedCustomerIds ?? []))
                                        Đã chọn: <strong>{{ count($selectedCustomerIds) }} khách hàng</strong>
                                    @else
                                        Đã chọn: <strong>Tất cả khách hàng</strong>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearCustomerSelection">
                                    Bỏ chọn
                                </button>
                            </div>

                            <div id="customerListingContainer">
                                @include('site.orders.partials.customer_listing', [
                                    'customers' => $customers,
                                    'selectedCustomerIds' => $selectedCustomerIds ?? [],
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endunless

            <div class="{{ ($monitoringEmbedded ?? false) ? 'col-12' : 'col-xl-8' }}">
                <div class="orders-panel mb-4">
                    <div class="orders-filter">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h2 class="h5 mb-1 fw-bold">Bộ lọc đơn hàng</h2>
                                <p class="mb-0 text-muted">Lọc theo trạng thái, thanh toán và thời gian tạo đơn.</p>
                            </div>
                            @if(request()->filled('customer_id') || !empty(request('customer_ids', [])) || request()->filled('customer_query') || request()->filled('payment_status') || request()->filled('status') || request()->filled('from_date') || request()->filled('to_date'))
                                <a href="{{ $ordersIndexUrl }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-refresh me-2"></i>Xóa bộ lọc
                                </a>
                            @endif
                        </div>

                        <form action="{{ $ordersIndexUrl }}" method="GET" class="{{ ($monitoringEmbedded ?? false) ? 'orders-monitor-filter-form' : 'row g-3 align-items-end' }}" id="ordersFilterForm">
                            @if($monitoringEmbedded ?? false)<input type="hidden" name="tab" value="my_orders">@endif
                            <input type="hidden" name="customer_query" id="customer_query" value="{{ $customerSearch ?? '' }}">
                            <input type="hidden" name="sort_by" value="{{ $currentSortBy }}">
                            <input type="hidden" name="sort_dir" value="{{ $currentSortDir }}">
                            @if(!empty($isTrashView))
                                <input type="hidden" name="trash" value="1">
                            @endif
                            <div id="selectedCustomerInputs">
                                @foreach(($selectedCustomerIds ?? []) as $selectedCustomerId)
                                    <input type="hidden" name="customer_ids[]" value="{{ (int) $selectedCustomerId }}">
                                @endforeach
                            </div>

                            <div class="{{ ($monitoringEmbedded ?? false) ? 'orders-monitor-field' : 'col-md-3' }}">
                                <label for="payment_status" class="form-label fw-bold">Thanh toán</label>
                                <select name="payment_status" id="payment_status" class="form-select">
                                    <option value="">Tất cả</option>
                                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
                                    <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                </select>
                            </div>
                            <div class="{{ ($monitoringEmbedded ?? false) ? 'orders-monitor-field' : 'col-md-3' }}">
                                <label for="status" class="form-label fw-bold">Trạng thái</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">Tất cả</option>
                                    @foreach($statusLabels as $statusKey => $statusName)
                                        <option value="{{ $statusKey }}" {{ request('status') === $statusKey ? 'selected' : '' }}>{{ $statusName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="{{ ($monitoringEmbedded ?? false) ? 'orders-monitor-field' : 'col-md-2' }}">
                                <label for="from_date" class="form-label fw-bold">Từ ngày</label>
                                <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                            </div>
                            <div class="{{ ($monitoringEmbedded ?? false) ? 'orders-monitor-field' : 'col-md-2' }}">
                                <label for="to_date" class="form-label fw-bold">Đến ngày</label>
                                <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                            </div>
                            @if($monitoringEmbedded ?? false)
                                <input type="hidden" name="per_page" id="per_page" value="{{ (int) ($perPage ?? request('per_page', 10)) }}">
                            @else
                            <div class="col-md-2">
                                <label for="per_page" class="form-label fw-bold">Hiển thị</label>
                                <select name="per_page" id="per_page" class="form-select">
                                    @foreach([10, 20, 50, 100] as $size)
                                        <option value="{{ $size }}" {{ (int) ($perPage ?? request('per_page', 10)) === $size ? 'selected' : '' }}>{{ $size }} / trang</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="{{ ($monitoringEmbedded ?? false) ? 'orders-monitor-action' : 'col-md-4 d-grid' }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search me-1"></i>Lọc đơn
                                </button>
                            </div>
                            <div class="{{ ($monitoringEmbedded ?? false) ? 'orders-monitor-action' : 'col-md-4 d-grid' }}">
                                <button type="button" class="btn btn-outline-primary" onclick="setTodayOrders()" style="font-size:0.95rem;">
                                    Đơn hôm nay
                                </button>
                            </div>
                            @if($monitoringEmbedded ?? false)
                            <div class="orders-customer-filter">
                                <button type="button" class="btn btn-outline-primary" id="openOrdersCustomerPicker">
                                    <i class="bi bi-calendar2-check me-2"></i>Chọn khách hàng
                                </button>
                                <span class="orders-selected-customer" id="ordersSelectedCustomerLabel">
                                    @if(($selectedCustomers ?? collect())->isNotEmpty())
                                        Đang lọc: <strong>{{ $selectedCustomers->pluck('name')->implode(', ') }}</strong>
                                    @else
                                        Đang xem tất cả khách hàng
                                    @endif
                                </span>
                            </div>
                            @else
                            <div class="col-md-4 d-grid">
                                <a href="{{ ($monitoringEmbedded ?? false) ? route('pages.my_orders.monitoring', ['tab' => 'schedules']) : route('my_customer.schedules.index') }}" class="btn btn-outline-primary ">
                                    <i class="bi bi-calendar2-check"></i> Lịch lên đơn
                                </a>
                            </div>
                            @endif
                        </form>
                    </div>
                </div>

                <div id="ordersListingContainer">
                    @include('site.orders.partials.orders_listing', [
                        'orders' => $orders,
                        'user' => $user,
                        'sortBy' => $currentSortBy,
                        'sortDir' => $currentSortDir,
                        'stockWarnings' => $stockWarnings ?? [],
                        'isTrashView' => $isTrashView ?? false,
                    ])
                </div>
            </div>
        </div>

        @if($monitoringEmbedded ?? false)
        <div class="modal fade orders-customer-modal" id="ordersCustomerPickerModal" tabindex="-1" aria-labelledby="ordersCustomerPickerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title fw-bold" id="ordersCustomerPickerModalLabel">Chọn khách hàng</h5>
                            <div class="small text-muted">Chọn một khách hàng để lọc danh sách đơn.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <input type="search" id="ordersCustomerSearch" class="form-control" placeholder="Tìm theo tên, SĐT hoặc email...">
                            </div>
                            <div class="col-md-4">
                                <select id="ordersCustomerSort" class="form-select" aria-label="Sắp xếp khách hàng">
                                    <option value="manual|asc">Ghim / thứ tự ưu tiên</option>
                                    <option value="name|asc">Tên A → Z</option>
                                    <option value="name|desc">Tên Z → A</option>
                                    <option value="phone|asc">SĐT tăng dần</option>
                                    <option value="phone|desc">SĐT giảm dần</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="ordersCustomerPerPage" class="form-select" aria-label="Số khách hàng mỗi trang">
                                    <option value="10">10 khách hàng</option>
                                    <option value="15" selected>15 khách hàng</option>
                                    <option value="25">25 khách hàng</option>
                                    <option value="50">50 khách hàng</option>
                                </select>
                            </div>
                        </div>
                        <div id="ordersCustomerPickerResults">
                            <div class="text-center text-muted py-5">Đang tải danh sách khách hàng...</div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-outline-danger" id="clearOrdersCustomerFilter">
                            <i class="bi bi-x-circle me-1"></i>Xem tất cả khách hàng
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <script>
            (function () {
                const endpoint = @json(route('site.orders.customers.ajax'));
                const searchInput = document.getElementById('customerSearchInput');
                const selectedInputsContainer = document.getElementById('selectedCustomerInputs');
                const customerQueryInput = document.getElementById('customer_query');
                const listingContainer = document.getElementById('customerListingContainer');
                const selectedCustomerLabel = document.getElementById('selectedCustomerLabel');
                const clearCustomerSelectionButton = document.getElementById('clearCustomerSelection');
                const selectedIds = new Set(@json(array_map('intval', $selectedCustomerIds ?? [])));
                let debounceTimer = null;

                if (!searchInput || !selectedInputsContainer || !customerQueryInput || !listingContainer || !selectedCustomerLabel || !clearCustomerSelectionButton) {
                    return;
                }

                const syncSelectedInputs = () => {
                    selectedInputsContainer.innerHTML = '';
                    Array.from(selectedIds).sort((a, b) => a - b).forEach((id) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'customer_ids[]';
                        input.value = String(id);
                        selectedInputsContainer.appendChild(input);
                    });
                };

                const updateSelectedLabel = () => {
                    const count = selectedIds.size;
                    if (count > 0) {
                        selectedCustomerLabel.innerHTML = 'Đã chọn: <strong>' + count + ' khách hàng</strong>';
                    } else {
                        selectedCustomerLabel.innerHTML = 'Đã chọn: <strong>Tất cả khách hàng</strong>';
                    }
                };

                const loadCustomers = (page = 1) => {
                    const params = new URLSearchParams();
                    params.set('q', searchInput.value || '');
                    params.set('page', String(page));
                    params.set('selected_ids', Array.from(selectedIds).join(','));

                    fetch(endpoint + '?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            listingContainer.innerHTML = data.html || '';
                            customerQueryInput.value = searchInput.value || '';
                        })
                        .catch(() => {
                            listingContainer.innerHTML = '<div class="small text-danger">Không thể tải danh sách khách hàng.</div>';
                        });
                };

                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => loadCustomers(1), 300);
                });

                listingContainer.addEventListener('click', function (event) {
                    const pageButton = event.target.closest('[data-customer-page]');
                    if (pageButton) {
                        const page = parseInt(pageButton.getAttribute('data-customer-page') || '1', 10);
                        loadCustomers(page > 0 ? page : 1);
                    }
                });

                listingContainer.addEventListener('change', function (event) {
                    const checkbox = event.target.closest('.customer-checkbox');
                    if (!checkbox) {
                        return;
                    }

                    const customerId = parseInt(checkbox.getAttribute('data-customer-id') || '0', 10);
                    const listItem = checkbox.closest('.customer-list-item');

                    if (customerId === 0) {
                        selectedIds.clear();
                        loadCustomers();
                        syncSelectedInputs();
                        updateSelectedLabel();
                        if (typeof window.refreshOrdersList === 'function') {
                            window.refreshOrdersList(1);
                        }
                        return;
                    }

                    if (checkbox.checked) {
                        selectedIds.add(customerId);
                    } else {
                        selectedIds.delete(customerId);
                    }

                    if (listItem) {
                        listItem.classList.toggle('active', checkbox.checked);
                    }

                    syncSelectedInputs();
                    updateSelectedLabel();
                    if (typeof window.refreshOrdersList === 'function') {
                        window.refreshOrdersList(1);
                    }
                });

                clearCustomerSelectionButton.addEventListener('click', function () {
                    selectedIds.clear();
                    syncSelectedInputs();
                    updateSelectedLabel();
                    loadCustomers(1);
                    if (typeof window.refreshOrdersList === 'function') {
                        window.refreshOrdersList(1);
                    }
                });

                syncSelectedInputs();
                updateSelectedLabel();
            })();
        </script>

        @if($monitoringEmbedded ?? false)
        <script>
            (function () {
                const endpoint = @json(route('site.orders.customers.ajax'));
                const modalElement = document.getElementById('ordersCustomerPickerModal');
                const openButton = document.getElementById('openOrdersCustomerPicker');
                const clearButton = document.getElementById('clearOrdersCustomerFilter');
                const searchInput = document.getElementById('ordersCustomerSearch');
                const sortSelect = document.getElementById('ordersCustomerSort');
                const perPageSelect = document.getElementById('ordersCustomerPerPage');
                const results = document.getElementById('ordersCustomerPickerResults');
                const selectedInputs = document.getElementById('selectedCustomerInputs');
                const selectedLabel = document.getElementById('ordersSelectedCustomerLabel');
                let selectedCustomerIds = @json(array_map('intval', $selectedCustomerIds ?? []));
                let searchTimer = null;

                if (!modalElement || !openButton || !results || !selectedInputs || !selectedLabel) {
                    return;
                }

                // This tab is rendered inside the monitoring page before the parent
                // layout loads Bootstrap. Resolve the modal lazily when the user
                // interacts with the picker so an early `bootstrap is not defined`
                // error does not prevent the click handlers below from being bound.
                const getModal = () => window.bootstrap?.Modal
                    ? window.bootstrap.Modal.getOrCreateInstance(modalElement)
                    : null;

                const syncCustomerFilter = (customer = null) => {
                    selectedInputs.innerHTML = '';

                    selectedCustomerIds.forEach((id) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'customer_ids[]';
                        input.value = String(id);
                        selectedInputs.appendChild(input);
                    });

                    selectedLabel.innerHTML = customer
                        ? 'Đang lọc: <strong>' + escapeHtml(customer.name) + '</strong>'
                        : 'Đang xem tất cả khách hàng';
                };

                const escapeHtml = (value) => {
                    const element = document.createElement('div');
                    element.textContent = String(value || '');
                    return element.innerHTML;
                };

                const loadCustomers = (page = 1) => {
                    const [sortBy, sortDir] = (sortSelect.value || 'manual|asc').split('|');
                    const params = new URLSearchParams({
                        q: searchInput.value.trim(),
                        per_page: perPageSelect.value || '15',
                        sort_by: sortBy || 'manual',
                        sort_dir: sortDir || 'asc',
                        page: String(page),
                        mode: 'single',
                        scope: 'orders',
                        selected_ids: selectedCustomerIds.join(','),
                    });

                    results.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>Đang tải danh sách khách hàng...</div>';

                    fetch(endpoint + '?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error('Không thể tải danh sách khách hàng.');
                            }
                            return response.json();
                        })
                        .then((data) => {
                            results.innerHTML = data.html || '<div class="text-center text-muted py-5">Không tìm thấy khách hàng.</div>';
                        })
                        .catch((error) => {
                            results.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message) + '</div>';
                        });
                };

                openButton.addEventListener('click', function () {
                    const modal = getModal();
                    if (!modal) {
                        results.innerHTML = '<div class="alert alert-danger mb-0">Không thể mở danh sách khách hàng. Vui lòng tải lại trang.</div>';
                        return;
                    }

                    modal.show();
                    loadCustomers(1);
                });

                modalElement.addEventListener('shown.bs.modal', function () {
                    searchInput.focus();
                });

                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => loadCustomers(1), 300);
                });

                [sortSelect, perPageSelect].forEach((element) => {
                    element.addEventListener('change', () => loadCustomers(1));
                });

                results.addEventListener('click', function (event) {
                    const pageButton = event.target.closest('.customer-page-btn');
                    if (pageButton) {
                        event.preventDefault();
                        loadCustomers(parseInt(pageButton.dataset.page || '1', 10));
                        return;
                    }

                    const sortLink = event.target.closest('.customer-sort-link');
                    if (sortLink) {
                        event.preventDefault();
                        sortSelect.value = (sortLink.dataset.sortBy || 'manual') + '|' + (sortLink.dataset.sortDir || 'asc');
                        loadCustomers(1);
                        return;
                    }

                    const selectButton = event.target.closest('.select-customer-btn');
                    if (!selectButton) {
                        return;
                    }

                    selectedCustomerIds = [parseInt(selectButton.dataset.customerId, 10)];
                    syncCustomerFilter({ name: selectButton.dataset.customerName || 'Khách hàng' });
                    getModal()?.hide();
                    window.refreshOrdersList?.(1);
                });

                clearButton.addEventListener('click', function () {
                    selectedCustomerIds = [];
                    syncCustomerFilter();
                    getModal()?.hide();
                    window.refreshOrdersList?.(1);
                });
            })();
        </script>
        @endif

        <script>
            (function () {
                const ordersEndpoint = @json($ordersIndexUrl);
                const ordersFilterForm = document.getElementById('ordersFilterForm');
                const ordersListingContainer = document.getElementById('ordersListingContainer');
                const sortByInput = ordersFilterForm.querySelector('input[name="sort_by"]');
                const sortDirInput = ordersFilterForm.querySelector('input[name="sort_dir"]');

                const syncCollapseActionLabels = () => {
                    const collapseButtons = ordersListingContainer.querySelectorAll('[data-bs-toggle="collapse"][data-bs-target]');
                    collapseButtons.forEach((button) => {
                        const actionLabel = button.querySelector('[data-collapse-label]');
                        if (!actionLabel) {
                            return;
                        }

                        actionLabel.textContent = button.getAttribute('aria-expanded') === 'true' ? 'Hide' : 'Show';
                    });
                };

                const buildFormParams = () => {
                    const formData = new FormData(ordersFilterForm);
                    const params = new URLSearchParams();
                    formData.forEach((value, key) => {
                        if (value !== null && String(value).trim() !== '') {
                            params.append(key, String(value));
                        }
                    });

                    return params;
                };

                const loadOrders = (page = 1) => {
                    const params = buildFormParams();
                    params.set('page', String(page));

                    const ajaxParams = new URLSearchParams(params.toString());
                    ajaxParams.set('ajax', '1');

                    const requestUrl = new URL(ordersEndpoint, window.location.origin);
                    requestUrl.search = ajaxParams.toString();

                    fetch(requestUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            ordersListingContainer.innerHTML = data.html || '';
                            syncCollapseActionLabels();
                            const nextUrl = new URL(ordersEndpoint, window.location.origin);
                            nextUrl.search = params.toString();
                            window.history.replaceState({}, '', nextUrl);
                        })
                        .catch(() => {
                            // keep existing UI if request fails
                        });
                };

                window.refreshOrdersList = loadOrders;

                window.setTodayOrders = function () {
                    const today = @json(now()->toDateString());
                    document.getElementById('from_date').value = today;
                    document.getElementById('to_date').value = today;
                    loadOrders(1);
                };

                ordersFilterForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    loadOrders(1);
                });

                ['payment_status', 'status', 'from_date', 'to_date', 'per_page'].forEach((fieldId) => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.addEventListener('change', function () {
                            loadOrders(1);
                        });
                    }
                });

                ordersListingContainer.addEventListener('click', function (event) {
                    const sortLink = event.target.closest('[data-order-sort-link]');
                    if (sortLink) {
                        event.preventDefault();
                        const url = new URL(sortLink.getAttribute('href'), window.location.origin);
                        const sortBy = url.searchParams.get('sort_by') || 'created_at';
                        const sortDir = url.searchParams.get('sort_dir') || 'desc';
                        sortByInput.value = sortBy;
                        sortDirInput.value = sortDir;
                        loadOrders(1);
                        return;
                    }

                    const pageLink = event.target.closest('.pagination a');
                    if (pageLink) {
                        event.preventDefault();
                        const url = new URL(pageLink.getAttribute('href'), window.location.origin);
                        const page = parseInt(url.searchParams.get('page') || '1', 10);
                        loadOrders(page > 0 ? page : 1);
                    }
                });

                ordersListingContainer.addEventListener('shown.bs.collapse', function () {
                    syncCollapseActionLabels();
                });

                ordersListingContainer.addEventListener('hidden.bs.collapse', function () {
                    syncCollapseActionLabels();
                });

                syncCollapseActionLabels();

            })();
        </script>
    </div>
</section>
@endsection
