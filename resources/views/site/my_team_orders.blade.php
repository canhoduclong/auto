@extends('layouts.site')

@section('content')
<style>
    .tmo-page {
        --tmo-border: #dbe4ef;
        --tmo-soft: #f8fafc;
        --tmo-ink: #0f172a;
        --tmo-muted: #64748b;
        --tmo-primary: #0f766e;
    }
    .tmo-page .tmo-hero {
        background: linear-gradient(120deg, #1f4a7c 0%, #2d7ba8 55%, #4aa3a8 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1rem 1.1rem;
        margin-bottom: .9rem;
    }
    .tmo-page .tmo-kpi {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(17, 24, 39, 0.06);
    }
    .tmo-page .tmo-kpi .value { font-weight: 700; font-size: 1.25rem; }
    .tmo-page .tmo-card {
        border: 1px solid var(--tmo-border);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(17, 24, 39, 0.05);
        background: #fff;
    }
    .tmo-page .tmo-card-header {
        border-bottom: 1px solid var(--tmo-border);
        padding: .75rem .9rem;
        background: #fff;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
    .tmo-page .tmo-card-body {
        padding: .9rem;
    }
    .tmo-page .tmo-collapse-toggle {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .tmo-page .tmo-aside-sticky {
        position: sticky;
        top: 86px;
    }
    .tmo-page .tmo-sales-list {
        display: grid;
        gap: .45rem;
        max-height: 320px;
        overflow: auto;
    }
    .tmo-page .tmo-sale-chip {
        width: 100%;
        text-align: left;
        border: 1px solid var(--tmo-border);
        background: #fff;
        border-radius: 10px;
        padding: .45rem .55rem;
        font-size: .84rem;
        color: #334155;
    }
    .tmo-page .tmo-sale-chip.active {
        border-color: #0f766e;
        background: #ecfdf5;
        color: #0f766e;
    }
    .tmo-page .tmo-order-toolbar {
        position: sticky;
        top: 76px;
        z-index: 12;
        background: #fff;
        border: 1px solid var(--tmo-border);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(17, 24, 39, 0.05);
        padding: .65rem .75rem;
    }
    .tmo-page .tmo-summary {
        border: 1px solid var(--tmo-border);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(17, 24, 39, 0.05);
        padding: .75rem;
    }
    .tmo-page .tmo-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .6rem;
    }
    .tmo-page .tmo-summary-item {
        border: 1px solid #e5edf7;
        border-radius: 10px;
        padding: .55rem .6rem;
        background: #f8fafc;
    }
    .tmo-page .tmo-summary-label {
        font-size: .74rem;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: .03em;
        margin-bottom: .2rem;
    }
    .bg-card{
        background-color:#e9eff5 !important;
    }
    .tmo-page .tmo-summary-value {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
    }
    .tmo-page .tmo-product-stats {
        margin-top: .65rem;
    }
    .tmo-page .tmo-product-vertical {
        display: grid;
        gap: .35rem;
        margin-top: .45rem;
    }
    .tmo-page .tmo-product-vertical-row {
        display: grid;
        grid-template-columns: 56px 1.5fr repeat(5, minmax(90px, auto));
        gap: .35rem;
        border: 1px solid #e5edf7;
        border-radius: 8px;
        padding: .36rem .45rem;
        background: #f8fafc;
        font-size: .8rem;
        align-items: center;
    }
    .tmo-page .tmo-product-vertical-head {
        background: #eef2f7;
        color: #475569;
        font-size: .73rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 700;
    }
    .tmo-page .tmo-orders {
        display: grid;
        gap: .75rem;
    }
    .tmo-page .tmo-order-row {
        border: 1px solid var(--tmo-border);
        border-radius: 12px;
        background: #fff;
        padding: .7rem .8rem;
    }
    .tmo-page .tmo-order-top {
        display: grid;
        grid-template-columns: 1.3fr 1fr 1fr 1fr 1fr auto;
        gap: .5rem;
        align-items: center;
    }
    .tmo-page .tmo-code {
        font-weight: 700;
        color: #0d4d77;
    }
    .tmo-page .tmo-mini {
        font-size: .79rem;
        color: var(--tmo-muted);
    }
    .tmo-page .tmo-status {
        display: inline-block;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        padding: .24rem .54rem;
    }
    .tmo-page .tmo-status-pending { background: #f59e0b; color: #000; }
    .tmo-page .tmo-status-approved { background: #d1e7dd; color: #0f5132; }
    .tmo-page .tmo-status-rejected { background: #f8d7da; color: #842029; }
    .tmo-page .tmo-status-default { background: #e2e8f0; color: #334155; }
    .tmo-page .tmo-order-row {
        transition: background-color .28s ease, border-color .28s ease, box-shadow .28s ease, transform .22s ease;
    }
    .tmo-page .tmo-order-row.flash {
        transform: scale(1.01);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    }
    .tmo-page .tmo-row-pending {
        border-left: 4px solid #111827;
    }
    .tmo-page .tmo-row-approved {
        border-left: 4px solid #15803d;
        background: #f0fdf4;
    }
    .tmo-page .tmo-row-rejected {
        border-left: 4px solid #dc2626;
        background: #fef2f2;
    }
    .tmo-page .tmo-row-old {
        border-left: 4px solid #6b7280;
        background: #f3f4f6;
    }
    .tmo-page .tmo-detail {
        margin-top: .55rem;
        border-top: 1px dashed var(--tmo-border);
        padding-top: .55rem;
    }
    .tmo-page .tmo-products {
        display: grid;
        gap: .35rem;
    }
    .tmo-page .tmo-product-line {
        display: grid;
        grid-template-columns: 1.2fr repeat(4, minmax(90px, auto));
        gap: .35rem;
        align-items: center;
        font-size: .82rem;
        border: 1px solid #e7edf5;
        border-radius: 8px;
        background: var(--tmo-soft);
        padding: .32rem .45rem;
    }
    .tmo-page .tmo-product-head {
        font-size: .73rem;
        color: var(--tmo-muted);
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .tmo-page .tmo-empty {
        border: 1px dashed var(--tmo-border);
        border-radius: 12px;
        background: #fff;
        color: #64748b;
        text-align: center;
        padding: 1rem;
    }
    .tmo-page .tmo-auto-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    @media (max-width: 1199.98px) {
        .tmo-page .tmo-order-top {
            grid-template-columns: 1fr 1fr 1fr;
        }
    }
    @media (max-width: 991.98px) {
        .tmo-page .tmo-aside-sticky,
        .tmo-page .tmo-order-toolbar {
            position: static;
        }
        .tmo-page .tmo-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .tmo-page .tmo-auto-grid {
            grid-template-columns: 1fr;
        }
        .tmo-page .tmo-product-line {
            grid-template-columns: 1fr;
            gap: .2rem;
        }
        .tmo-page .tmo-product-vertical-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container py-3 tmo-page">
    <div class="tmo-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Duyệt Đơn Của Team</h4>
                <div class="opacity-75">Bố cục tối ưu để lọc sale, quét nhanh đơn và thao tác duyệt.</div>
            </div>
            <div class="small opacity-75">Leader: {{ $user->name }}</div>
        </div>
    </div>

    

    <div class="tmo-card mb-3">
        <div class="tmo-card-header d-flex justify-content-between align-items-center">
            <div class="fw-semibold">Khu vực duyệt tự động</div>
            <button class="btn btn-sm btn-outline-primary tmo-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#autoApproveCollapse" aria-expanded="false" aria-controls="autoApproveCollapse">
                <i class="bi bi-chevron-expand"></i>
                Mở / Thu gọn
            </button>
        </div>
        <div class="collapse" id="autoApproveCollapse">
            <div class="tmo-card-body">
                <form method="POST" action="{{ route('pages.my_tearm_orders.auto_approve') }}" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="from_date" value="{{ $fromDate }}">
                    <input type="hidden" name="to_date" value="{{ $toDate }}">

                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-3 h-100">
                            <h6 class="mb-2">Điều kiện theo số lượng</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="condition_item_qty" name="condition_item_qty" value="1" {{ old('condition_item_qty') ? 'checked' : '' }}>
                                <label class="form-check-label" for="condition_item_qty">Bật điều kiện số lượng sản phẩm trong đơn</label>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label mb-1">SL tối thiểu</label><input type="number" min="1" class="form-control" name="min_item_qty" value="{{ old('min_item_qty') }}"></div>
                                <div class="col-6"><label class="form-label mb-1">SL tối đa</label><input type="number" min="1" class="form-control" name="max_item_qty" value="{{ old('max_item_qty') }}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-3 h-100">
                            <h6 class="mb-2">Điều kiện theo giá trị đơn</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="condition_order_total" name="condition_order_total" value="1" {{ old('condition_order_total') ? 'checked' : '' }}>
                                <label class="form-check-label" for="condition_order_total">Bật điều kiện tổng giá trị đơn hàng</label>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label mb-1">Giá trị tối thiểu</label><input type="number" min="0" step="1000" class="form-control" name="min_order_total" value="{{ old('min_order_total') }}"></div>
                                <div class="col-6"><label class="form-label mb-1">Giá trị tối đa</label><input type="number" min="0" step="1000" class="form-control" name="max_order_total" value="{{ old('max_order_total') }}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded-3 p-3">
                            <h6 class="mb-2">Discount theo sản lượng</h6>
                            <div class="tmo-auto-grid">
                                <div><label class="form-label mb-1">Mốc 20 (freeship)</label><input type="number" min="0" step="1000" class="form-control" name="freeship_20_amount" value="{{ old('freeship_20_amount') }}"></div>
                                <div><label class="form-label mb-1">Mốc 30</label><input type="number" min="0" step="1000" class="form-control" name="discount_30_amount" value="{{ old('discount_30_amount') }}"></div>
                                <div><label class="form-label mb-1">Mốc 40</label><input type="number" min="0" step="1000" class="form-control" name="discount_40_amount" value="{{ old('discount_40_amount') }}"></div>
                                <div><label class="form-label mb-1">Mốc 50</label><input type="number" min="0" step="1000" class="form-control" name="discount_50_amount" value="{{ old('discount_50_amount') }}"></div>
                                <div><label class="form-label mb-1">Mốc 70</label><input type="number" min="0" step="1000" class="form-control" name="discount_70_amount" value="{{ old('discount_70_amount') }}"></div>
                                <div><label class="form-label mb-1">Mốc 80</label><input type="number" min="0" step="1000" class="form-control" name="discount_80_amount" value="{{ old('discount_80_amount') }}"></div>
                                <div><label class="form-label mb-1">Mốc 100</label><input type="number" min="0" step="1000" class="form-control" name="discount_100_amount" value="{{ old('discount_100_amount') }}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-7">
                        <div class="border rounded-3 p-3">
                            <h6 class="mb-2">Khách hàng đặc biệt</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="use_special_customer_discount" name="use_special_customer_discount" value="1" {{ old('use_special_customer_discount') ? 'checked' : '' }}>
                                <label class="form-check-label" for="use_special_customer_discount">Bật discount riêng cho khách hàng đặc biệt</label>
                            </div>
                            <div><label class="form-label mb-1">Mức discount khách đặc biệt</label><input type="number" min="0" step="1000" class="form-control" name="special_customer_discount_amount" value="{{ old('special_customer_discount_amount') }}"></div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">Duyệt đơn tự động</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php
        $viewerRoleSlugs = $user->roles->pluck('name')
            ->map(fn($role) => strtolower((string) $role))
            ->values()
            ->all();

        $saleGroups = $orders->getCollection()
            ->groupBy(fn($order) => $order->user?->id ?: 0)
            ->filter(fn($items, $userId) => (int) $userId > 0)
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'id' => $first->user?->id,
                    'name' => $first->user?->name,
                    'team' => $first->user?->team?->name,
                    'count' => $items->count(),
                ];
            })
            ->values();
    @endphp

    <div class="row g-3" id="tmoMainRow">
        <div class="col-12 col-lg-3">
            <div class="tmo-aside-sticky d-grid gap-3">
                <div class="tmo-card">
                    <div class="tmo-card-header fw-semibold text-uppercase bg-card">Lọc dữ liệu</div>
                    <div class="tmo-card-body">
                        <form method="GET" action="{{ route('pages.my_tearm_orders') }}" class="row g-2 js-filter-form">
                            <div class="col-12">
                                <label class="form-label mb-1">Tìm kiếm</label>
                                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Mã đơn, khách, sale">
                            </div>
                            <div class="col-6 col-lg-12">
                                <label class="form-label mb-1">Từ ngày</label>
                                <input type="date" class="form-control" name="from_date" value="{{ $fromDate }}">
                            </div>
                            <div class="col-6 col-lg-12">
                                <label class="form-label mb-1">Đến ngày</label>
                                <input type="date" class="form-control" name="to_date" value="{{ $toDate }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Trạng thái đơn</label>
                                <input type="text" class="form-control" name="status" value="{{ request('status') }}" placeholder="pending_leader_approval">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="pending_only" id="pending_only" value="1" {{ $pendingOnly ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pending_only">Chỉ đơn tới lượt tôi duyệt</label>
                                </div>
                            </div>
                            <div class="col-12 d-grid gap-2">
                                <button class="btn btn-primary" type="submit">Áp dụng lọc</button>
                                <a href="{{ route('pages.my_tearm_orders') }}" class="btn btn-outline-secondary">Đơn hôm nay</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tmo-card">
                    <div class="tmo-card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Danh sách sale</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSaleFilter">Bỏ chọn</button>
                    </div>
                    <div class="tmo-card-body">
                        <div class="tmo-sales-list" id="saleQuickList">
                            @forelse($saleGroups as $sale)
                                <button type="button" class="tmo-sale-chip js-sale-chip" data-sale-id="{{ $sale['id'] }}">
                                    <div class="fw-semibold">{{ $sale['name'] }}</div>
                                    <div class="tmo-mini">Team: {{ $sale['team'] ?? '-' }} | {{ $sale['count'] }} đơn</div>
                                </button>
                            @empty
                                <div class="tmo-mini">Không có sale trong danh sách hiện tại.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9">

            <div class="row g-3 mb-2">
                <div class="col-12 col-md-3">
                    <div class="card tmo-kpi"><div class="card-body"><div class="text-muted small">Tổng đơn</div><div class="value text-primary" id="sumTotal">{{ number_format($stats['total']) }}</div></div></div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="card tmo-kpi"><div class="card-body"><div class="text-muted small">Chờ leader duyệt</div><div class="value text-warning" id="sumPending">{{ number_format($stats['pending']) }}</div></div></div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="card tmo-kpi"><div class="card-body"><div class="text-muted small">Đã duyệt</div><div class="value text-success" id="sumApproved">{{ number_format($stats['approved']) }}</div></div></div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="card tmo-kpi">
                        <div class="card-body">
                            <div class="text-muted small"><span>Từ chối</span> <i class="bi bi-info-circle text-danger" ></i></div>  
                            <div class="value tmo-summary-value text-danger" id="sumRejected">{{ number_format($stats['rejected']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tmo-summary mb-3" id="orderSummaryPanel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Thống kê theo bộ lọc</div>
                    <div class="tmo-mini">Tính trên danh sách đơn đang hiển thị</div>
                </div>
                <div class="tmo-summary-grid">
                    <div class="tmo-summary-item">
                        <div class="tmo-summary-label">Tổng hàng hóa</div>
                        <div class="tmo-summary-value" id="sumItemLines">0 mặt hàng</div>
                    </div>
                    <div class="tmo-summary-item">
                        <div class="tmo-summary-label">Tổng số lượng</div>
                        <div class="tmo-summary-value" id="sumItemQty">0</div>
                    </div>
                    <div class="tmo-summary-item">
                        <div class="tmo-summary-label">Tổng giá trị đơn hàng</div>
                        <div class="tmo-summary-value" id="sumOrderTotal">0 đ</div>
                    </div>
                </div>
                <div class="tmo-product-stats">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="tmo-summary-label mb-0">Hàng - Số lượng</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleProductStatsBtn">
                            <i class="bi bi-chevron-expand"></i>
                            Chi tiết
                        </button>
                    </div>
                    <div class="d-none" id="sumProductDetailWrap">
                        <div class="tmo-product-vertical" id="sumProductDetailList"></div>
                    </div>
                </div>
            </div>

            <div class="tmo-order-toolbar mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="fw-semibold">Danh sách đơn hàng</div>
                    <div class="tmo-mini" id="saleFilterState">Hiển thị toàn bộ đơn theo bộ lọc hiện tại.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center  gap-1">
                        <label class="small text-muted mb-0" for="orderSort" style="min-width:60px">Sắp xếp:</label>
                        <select id="orderSort" class="form-select form-select-sm" style="min-width: 210px;">
                            <option value="created_desc">Ngày tạo mới nhất</option>
                            <option value="created_asc">Ngày tạo cũ nhất</option>
                            <option value="total_desc">Giá trị đơn giảm dần</option>
                            <option value="total_asc">Giá trị đơn tăng dần</option>
                            <option value="delivery_asc">Giờ giao sớm nhất</option>
                            <option value="delivery_desc">Giờ giao muộn nhất</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <label class="small text-muted mb-0" for="perPageSelect"  style="min-width:60px">Hiển thị:</label>
                        <select id="perPageSelect" class="form-select form-select-sm" style="min-width: 96px;">
                            @foreach([10, 15, 25, 50, 100] as $pp)
                                <option value="{{ $pp }}" {{ (int) ($perPage ?? 15) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="tmo-orders" id="orderList">
                @forelse($orders as $order)
                    @php
                        $step = $currentStepByOrder[$order->id] ?? null;
                        $canApprove = $canApproveByOrder[$order->id] ?? false;
                        $canProcess = $canApprove && optional($order->created_at)?->isToday();
                        $hasPassedViewerStep = $order->approvals->contains(function ($approval) use ($viewerRoleSlugs) {
                            $roleSlug = strtolower((string) optional($approval->step)->role_slug);
                            return $approval->status === 'approved' && in_array($roleSlug, $viewerRoleSlugs, true);
                        });
                        $visualStatus = ($order->status !== 'rejected' && $hasPassedViewerStep)
                            ? 'approved'
                            : (string) $order->status;

                        $statusClass = match ($visualStatus) {
                            'pending_leader_approval' => 'tmo-status-pending',
                            'approved' => 'tmo-status-approved',
                            'rejected' => 'tmo-status-rejected',
                            default => 'tmo-status-default',
                        };
                        $statusLabel = match ($visualStatus) {
                            'pending_leader_approval' => 'Chờ Duyệt',
                            'approved' => 'Đã Duyệt',
                            'rejected' => 'Từ Chối',
                            default => $visualStatus,
                        };
                        $isOldOrder = !optional($order->created_at)?->isToday();
                        $rowStateClass = match ($visualStatus) {
                            'approved' => 'tmo-row-approved',
                            'rejected' => 'tmo-row-rejected',
                            'pending_leader_approval' => $isOldOrder ? 'tmo-row-old' : 'tmo-row-pending',
                            default => $isOldOrder ? 'tmo-row-old' : 'tmo-row-pending',
                        };
                        $formatSignedMoney = static function (float $amount): string {
                            $prefix = $amount < 0 ? '+' : '-';

                            return $prefix . number_format(abs($amount), 0, ',', '.') . ' đ';
                        };
                        $discountTotal = (float) ($order->total_discount
                            ?? (($order->item_discount_total ?? 0) + ($order->extra_discount_total ?? 0)));
                        $deliveryTs = 0;
                        try {
                            if (!empty($order->delivery_time)) {
                                $timeStr = $order->delivery_time;
                                if (preg_match('/^(\d{1,2})h(\d{0,2})$/', $timeStr, $matches)) {
                                    $hour = (int) $matches[1];
                                    $min = (int) ($matches[2] ?: 0);
                                    $deliveryTs = \Carbon\Carbon::today()->setTime($hour, $min)->timestamp;
                                } elseif (preg_match('/^(\d{1,2}):(\d{2})$/', $timeStr, $matches)) {
                                    $hour = (int) $matches[1];
                                    $min = (int) $matches[2];
                                    $deliveryTs = \Carbon\Carbon::today()->setTime($hour, $min)->timestamp;
                                } else {
                                    $deliveryTs = \Carbon\Carbon::parse($timeStr)->timestamp;
                                }
                            }
                        } catch (\Throwable $e) {
                            $deliveryTs = 0;
                        }
                        $createdTs = optional($order->created_at)?->timestamp ?? 0;
                        $hasInvalidSizeItems = $order->items->contains(function ($it) {
                            $unitWeight = (float) ($it->effective_unit_weight ?? 0);
                            return $unitWeight <= 0;
                        });
                    @endphp

                    <article class="tmo-order-row js-order-row {{ $rowStateClass }}"
                        data-order-id="{{ $order->id }}"
                        data-sale-id="{{ $order->user?->id }}"
                        data-total="{{ (float) $order->total }}"
                        data-item-lines="{{ (int) ($order->items?->count() ?? 0) }}"
                        data-item-qty="{{ (float) ($order->items?->sum('quantity') ?? 0) }}"
                        data-created="{{ $createdTs }}"
                        data-delivery="{{ $deliveryTs }}"
                        data-is-old="{{ $isOldOrder ? 1 : 0 }}">

                        <div class="tmo-order-top">
                            <div>
                                <div class="tmo-code">{{ $order->code }}</div>
                                <div class="tmo-mini">Tạo: {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $order->user->name ?? '-' }}</div>
                                <div class="tmo-mini">Team: {{ $order->user?->team?->name ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ number_format((float) $order->total, 0, ',', '.') }} đ</div>
                                <div class="tmo-mini">Điều chỉnh: {{ $formatSignedMoney($discountTotal) }}</div>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $order->delivery_time ? (function($timeStr) {
                                    try {
                                        if (preg_match('/^(\d{1,2})h(\d{0,2})$/', $timeStr, $matches)) {
                                            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                                            $min = str_pad($matches[2] ?: 0, 2, '0', STR_PAD_LEFT);
                                            return $hour . ':' . $min . ' ' . now()->format('d/m');
                                        } elseif (preg_match('/^(\d{1,2}):(\d{2})$/', $timeStr, $matches)) {
                                            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                                            $min = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                                            return $hour . ':' . $min . ' ' . now()->format('d/m');
                                        } else {
                                            return \Carbon\Carbon::parse($timeStr)->format('H:i d/m');
                                        }
                                    } catch (\Throwable $e) {
                                        return $timeStr;
                                    }
                                })($order->delivery_time) : '-' }}</div>
                                <div class="tmo-mini">Giờ giao</div>
                            </div>
                            <div>
                                <span class="tmo-status js-order-status {{ $statusClass }}" data-status="{{ $visualStatus }}">{{ $statusLabel }}</span>
                                <div class="tmo-mini mt-1">Bước: {{ $step?->step?->role_slug ?? 'Không có' }}</div>
                            </div>
                            <div class="d-flex gap-1 flex-wrap justify-content-end">
                                <a href="{{ route('pages.team_order_detail', $order) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#quickOrder{{ $order->id }}" aria-expanded="false" aria-controls="quickOrder{{ $order->id }}">Xem nhanh</button>
                                @if($canProcess)
                                    <form method="POST" action="{{ route('orders.approve', $order) }}" class="js-approval-form" data-action="approve">
                                        @csrf
                                        <input type="hidden" name="note" value="Leader duyệt từ trang team orders">
                                        <button type="submit" class="btn btn-sm btn-success js-approve-btn"
                                            @if($hasInvalidSizeItems)
                                                disabled
                                                title="Có sản phẩm chưa có size hoặc KL tạm tính = 0. Vui lòng cập nhật size sản phẩm trước khi duyệt."
                                            @endif>Duyệt</button>
                                        @if($hasInvalidSizeItems)
                                            <div class="text-danger" style="font-size:.72rem;margin-top:2px;">Size/KL = 0</div>
                                        @endif
                                    </form>
                                    <form method="POST" action="{{ route('orders.reject', $order) }}" class="js-approval-form" data-action="reject">
                                        @csrf
                                        <input type="hidden" name="note" value="Leader từ chối từ trang team orders">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Xác nhận từ chối đơn này?')">Từ chối</button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        @if($hasInvalidSizeItems)
                            @php
                                $invalidItemNames = $order->items
                                    ->filter(fn($it) => (float)($it->effective_unit_weight ?? 0) <= 0)
                                    ->map(fn($it) => $it->product?->name ?? $it->variant?->name ?? 'Sản phẩm')
                                    ->implode(', ');
                            @endphp
                            <div id="approveErr_{{ $order->id }}" class="text-danger small mt-2 px-1">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Không thể duyệt: <strong>{{ $invalidItemNames }}</strong> có KL quy đổi = 0. Vui lòng cập nhật size/KL sản phẩm.
                            </div>
                        @endif

                        <div class="collapse tmo-detail" id="quickOrder{{ $order->id }}">
                            @if(($order->items ?? collect())->isNotEmpty())
                                <div class="tmo-products">
                                    <div class="tmo-product-line tmo-product-head">
                                        <div>Sản phẩm</div>
                                        <div>Số lượng</div>
                                        <div>Tổng</div>
                                        <div>Size</div>
                                        <div>Đơn giá</div>
                                        <div>Tạm tính</div>
                                    </div>
                                    @foreach($order->items as $item)
                                        @php
                                            $productName = $item->product->name ?? $item->variant->name ?? 'Sản phẩm';
                                            $unitLabel = strtoupper((string) ($item->product?->unit_label ?? $item->variant?->product?->unit_label ?? 'cai'));
                                            $sizeLabel = $item->variant->size ?? $item->variant->name ?? '-';
                                            $qty = (float) ($item->quantity ?? 0);
                                            $displayTotalValue = (float) ($item->display_total_value ?? 0);
                                            $displayTotalUnit = (string) ($item->display_total_unit ?? $unitLabel);
                                            $displayTotalLabel = (string) ($item->display_total_label ?? ($qty . ' ' . $displayTotalUnit));
                                            $price = (float) ($item->price ?? 0);
                                            $actualSizeValue = 0;
                                            if (preg_match('/(\d+(\.\d+)?)/', $sizeLabel, $matches)) {
                                                $actualSizeValue = (float) $matches[1];
                                            }
                                            $sizeValue = $actualSizeValue > 0 ? $actualSizeValue : 1; // mặc định = 1 nếu rỗng/invalid
                                            $effectiveUnitWeight = (float) ($item->effective_unit_weight ?? 0);
                                            $estimatedWeight = round($qty * $effectiveUnitWeight, 3);
                                            $lineSubtotal = (float) ($item->total ?? 0);
                                            if ($lineSubtotal <= 0) {
                                                $lineSubtotal = $qty * $price;
                                            }
                                        @endphp
                                        <div class="tmo-product-line">
                                            <div class="fw-semibold js-product-line"
                                                data-product-name="{{ $productName }}"
                                                data-product-qty="{{ $qty }}"
                                                data-product-total-value="{{ $displayTotalValue }}"
                                                data-product-total-unit="{{ $displayTotalUnit }}"
                                                data-product-size="{{ $sizeLabel }}"
                                                data-product-size-val="{{ $actualSizeValue }}"
                                                data-product-est-weight="{{ $estimatedWeight }}"
                                                data-product-price="{{ $price }}"
                                                data-product-subtotal="{{ $lineSubtotal }}">{{ $productName }}</div>
                                            <div>{{ rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') }}</div>
                                            <div>{{ $displayTotalLabel }}</div>
                                            <div>{{ $sizeLabel }}</div>
                                            <div>{{ number_format($price, 0, ',', '.') }} đ</div>
                                            <div>{{ number_format($lineSubtotal, 0, ',', '.') }} đ</div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="tmo-mini">Không có dữ liệu sản phẩm.</div>
                            @endif

                            @if(!empty($order->note))
                                <div class="mt-2 tmo-mini"><strong>Ghi chú:</strong> {{ $order->note }}</div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="tmo-empty">Không có đơn hàng phù hợp.</div>
                @endforelse
            </div>

            <div class="mt-3" id="orderPaginationWrap">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let activeSaleId = null;
    let productDetailVisible = false;

    function getMainRow() {
        return document.getElementById('tmoMainRow');
    }

    function getOrderList() {
        return document.getElementById('orderList');
    }

    function getRows() {
        const orderList = getOrderList();
        return orderList ? Array.from(orderList.querySelectorAll('.js-order-row')) : [];
    }

    function rowStateClass(status, isOld) {
        if (status === 'approved') {
            return 'tmo-row-approved';
        }
        if (status === 'rejected') {
            return 'tmo-row-rejected';
        }
        if (String(isOld) === '1') {
            return 'tmo-row-old';
        }
        return 'tmo-row-pending';
    }

    function statusBadgeClass(status) {
        if (status === 'approved') {
            return 'tmo-status-approved';
        }
        if (status === 'rejected') {
            return 'tmo-status-rejected';
        }
        if (status === 'pending_leader_approval') {
            return 'tmo-status-pending';
        }
        return 'tmo-status-default';
    }

    function applySaleFilter() {
        const saleState = document.getElementById('saleFilterState');
        const saleChips = Array.from(document.querySelectorAll('.js-sale-chip'));
        const rows = getRows();

        rows.forEach(function (row) {
            const visible = !activeSaleId || row.dataset.saleId === String(activeSaleId);
            row.style.display = visible ? '' : 'none';
        });

        if (!saleState) {
            return;
        }

        if (!activeSaleId) {
            saleState.textContent = 'Hiển thị toàn bộ đơn theo bộ lọc hiện tại.';
            return;
        }

        const active = saleChips.find(function (chip) {
            return chip.dataset.saleId === String(activeSaleId);
        });
        const saleName = active ? (active.querySelector('.fw-semibold')?.textContent || '') : '';
        saleState.textContent = 'Đang xem nhanh đơn của sale: ' + saleName;

        recalcSummary();
    }

    function formatMoney(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' đ';
    }

    function formatQty(value) {
        const normalized = Number(value || 0);
        return normalized % 1 === 0
            ? String(normalized)
            : normalized.toLocaleString('en-US', { maximumFractionDigits: 3 });
    }

    function recalcSummary() {
        const rows = getRows().filter(function (row) {
            return row.style.display !== 'none';
        });

        let approved = 0;
        let pending = 0;
        let rejected = 0;
        let totalValue = 0;
        let totalGoods = 0;
        let totalQty = 0;
        const itemMap = new Map();

        rows.forEach(function (row) {
            const statusEl = row.querySelector('.js-order-status');
            const status = (statusEl ? statusEl.dataset.status : '').trim().toLowerCase();

            if (status === 'approved') {
                approved += 1;
            } else if (status === 'rejected') {
                rejected += 1;
            } else {
                pending += 1;
            }

            totalValue += Number(row.dataset.total || 0);
            row.querySelectorAll('.js-product-line').forEach(function (line) {
                const name = (line.dataset.productName || '').trim();
                const totalUnit = (line.dataset.productTotalUnit || '').trim();
                const size = (line.dataset.productSize || '').trim();
                const qty = Number(line.dataset.productQty || 0);
                const totalValue = Number(line.dataset.productTotalValue || 0);
                const estWeight = Number(line.dataset.productEstWeight || 0);
                const price = Number(line.dataset.productPrice || 0);
                const subtotal = Number(line.dataset.productSubtotal || 0);

                if (!name) {
                    return;
                }

                const key = [name, totalUnit, size, price].join('||');
                const current = itemMap.get(key) || {
                    name: name,
                    totalUnit: totalUnit || '-',
                    size: size || '-',
                    qty: 0,
                    totalValue: 0,
                    estWeight: 0,
                    price: price,
                    subtotal: 0,
                };

                current.qty += qty;
                current.totalValue += totalValue;
                current.estWeight += estWeight;
                current.subtotal += subtotal;

                itemMap.set(key, current);
            });
        });

        totalGoods = itemMap.size;
        totalQty = Array.from(itemMap.values()).reduce(function (sum, item) {
            return sum + Number(item.qty || 0);
        }, 0);

        const sumStatus = document.getElementById('sumStatus');
        const sumItemLines = document.getElementById('sumItemLines');
        const sumItemQty = document.getElementById('sumItemQty');
        const sumOrderTotal = document.getElementById('sumOrderTotal');
        const sumProductDetailList = document.getElementById('sumProductDetailList');

        if (sumStatus) {
            sumStatus.textContent = approved + ' / ' + pending + ' / ' + rejected;
        }
        if (sumItemLines) {
            sumItemLines.textContent = formatQty(totalGoods) + ' mặt hàng';
        }
        if (sumItemQty) {
            sumItemQty.textContent = formatQty(totalQty);
        }
        if (sumOrderTotal) {
            sumOrderTotal.textContent = formatMoney(totalValue);
        }

        if (sumProductDetailList) {
            const items = Array.from(itemMap.values()).sort(function (a, b) {
                return b.qty - a.qty;
            });

            if (!items.length) {
                sumProductDetailList.innerHTML = '<div class="tmo-mini">Không có dữ liệu hàng hóa.</div>';
            } else {
                const head = '<div class="tmo-product-vertical-row tmo-product-vertical-head"><div>STT</div><div>Sản phẩm</div><div>Số lượng</div><div>Tổng</div><div>Size</div><div>Đơn giá</div><div>Tạm tính</div></div>';
                const body = items.map(function (item, index) {
                    const displayTotalText = formatQty(item.totalValue) + ' ' + item.totalUnit;

                    return '<div class="tmo-product-vertical-row">'
                        + '<div>' + (index + 1) + '</div>'
                        + '<div class="fw-semibold">' + item.name + '</div>'
                        + '<div>' + formatQty(item.qty) + '</div>'
                        + '<div>' + displayTotalText + '</div>'
                        + '<div>' + item.size + '</div>'
                        + '<div>' + formatMoney(item.price) + '</div>'
                        + '<div>' + formatMoney(item.subtotal) + '</div>'
                        + '</div>';
                }).join('');

                sumProductDetailList.innerHTML = head + body;
            }
        }
    }

    function applySort() {
        const orderList = getOrderList();
        const sortSelect = document.getElementById('orderSort');
        if (!orderList || !sortSelect) {
            return;
        }

        const rows = getRows();
        const mode = sortSelect.value;

        rows.sort(function (a, b) {
            const getNum = function (row, key) {
                return Number(row.dataset[key] || 0);
            };

            if (mode === 'created_asc') return getNum(a, 'created') - getNum(b, 'created');
            if (mode === 'created_desc') return getNum(b, 'created') - getNum(a, 'created');
            if (mode === 'total_asc') return getNum(a, 'total') - getNum(b, 'total');
            if (mode === 'total_desc') return getNum(b, 'total') - getNum(a, 'total');
            if (mode === 'delivery_asc') return getNum(a, 'delivery') - getNum(b, 'delivery');
            if (mode === 'delivery_desc') return getNum(b, 'delivery') - getNum(a, 'delivery');
            return 0;
        });

        rows.forEach(function (row) {
            orderList.appendChild(row);
        });

        applySaleFilter();
        recalcSummary();
    }

    function appendAjaxParam(url) {
        const parsed = new URL(url, window.location.origin);
        parsed.searchParams.set('ajax', '1');
        return parsed.toString();
    }

    async function reloadMainRow(url) {
        const targetUrl = appendAjaxParam(url || window.location.href);
        const response = await fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newMain = doc.getElementById('tmoMainRow');
        const oldMain = getMainRow();

        if (newMain && oldMain) {
            oldMain.replaceWith(newMain);
            bindInteractiveEvents();
            recalcSummary();
        } else {
            window.location.href = targetUrl.replace(/([?&])ajax=1(&|$)/, '$1').replace(/[?&]$/, '');
        }
    }

    async function submitApproval(form) {
        const action = form.dataset.action;
        const row = form.closest('.js-order-row');
        if (!row) {
            return;
        }

        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html,application/xhtml+xml',
            },
        });

        if (!response.ok) {
            showToast('Không thể cập nhật trạng thái đơn.', 'error');
            return;
        }

        const newStatus = action === 'approve' ? 'approved' : 'rejected';
        const statusBadge = row.querySelector('.js-order-status');

        row.classList.remove('tmo-row-pending', 'tmo-row-approved', 'tmo-row-rejected', 'tmo-row-old');
        row.classList.add(rowStateClass(newStatus, row.dataset.isOld));
        row.classList.add('flash');

        if (statusBadge) {
            statusBadge.classList.remove('tmo-status-pending', 'tmo-status-approved', 'tmo-status-rejected', 'tmo-status-default');
            statusBadge.classList.add(statusBadgeClass(newStatus));
            statusBadge.dataset.status = newStatus;
            statusBadge.textContent = newStatus === 'approved' ? 'Đã Duyệt' : newStatus === 'rejected' ? 'Từ Chối' : 'Chờ Duyệt';
        }

        const forms = row.querySelectorAll('.js-approval-form');
        forms.forEach(function (f) {
            const btn = f.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
            }
        });

        setTimeout(function () {
            row.classList.remove('flash');
        }, 600);

        showToast(action === 'approve' ? 'Đơn đã được duyệt.' : 'Đơn đã bị từ chối.', action === 'approve' ? 'success' : 'warning');
        recalcSummary();
    }

    function bindInteractiveEvents() {
        const sortSelect = document.getElementById('orderSort');
        const saleChips = Array.from(document.querySelectorAll('.js-sale-chip'));
        const clearSaleBtn = document.getElementById('clearSaleFilter');
        const filterForm = document.querySelector('.js-filter-form');
        const paginationWrap = document.getElementById('orderPaginationWrap');
        const perPageSelect = document.getElementById('perPageSelect');
        const toggleProductStatsBtn = document.getElementById('toggleProductStatsBtn');
        const sumProductDetailWrap = document.getElementById('sumProductDetailWrap');

        saleChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                const saleId = chip.dataset.saleId;
                if (activeSaleId && String(activeSaleId) === String(saleId)) {
                    activeSaleId = null;
                    chip.classList.remove('active');
                } else {
                    activeSaleId = saleId;
                    saleChips.forEach(function (c) { c.classList.remove('active'); });
                    chip.classList.add('active');
                }
                applySaleFilter();
            });
        });

        if (clearSaleBtn) {
            clearSaleBtn.addEventListener('click', function () {
                activeSaleId = null;
                saleChips.forEach(function (c) { c.classList.remove('active'); });
                applySaleFilter();
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', applySort);
        }

        if (filterForm) {
            filterForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const url = new URL(filterForm.action, window.location.origin);
                const data = new FormData(filterForm);
                data.forEach(function (value, key) {
                    if (value !== null && String(value).trim() !== '') {
                        url.searchParams.set(key, value);
                    }
                });

                if (!data.get('pending_only')) {
                    url.searchParams.delete('pending_only');
                }

                if (perPageSelect) {
                    url.searchParams.set('per_page', perPageSelect.value);
                }

                reloadMainRow(url.toString());
            });
        }

        if (paginationWrap) {
            paginationWrap.querySelectorAll('a.page-link').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    const targetUrl = new URL(link.href, window.location.origin);
                    if (perPageSelect) {
                        targetUrl.searchParams.set('per_page', perPageSelect.value);
                    }
                    reloadMainRow(targetUrl.toString());
                });
            });
        }

        if (perPageSelect) {
            perPageSelect.addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', perPageSelect.value);
                url.searchParams.set('page', '1');
                reloadMainRow(url.toString());
            });
        }

        if (toggleProductStatsBtn && sumProductDetailWrap) {
            const renderToggleLabel = function () {
                toggleProductStatsBtn.textContent = productDetailVisible ? 'Ẩn chi tiết' : 'Chi tiết';
            };

            renderToggleLabel();
            sumProductDetailWrap.classList.toggle('d-none', !productDetailVisible);

            toggleProductStatsBtn.addEventListener('click', function () {
                productDetailVisible = !productDetailVisible;
                sumProductDetailWrap.classList.toggle('d-none', !productDetailVisible);
                renderToggleLabel();
            });
        }

        document.querySelectorAll('.js-approval-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (form.dataset.action === 'reject' && !window.confirm('Xác nhận từ chối đơn này?')) {
                    return;
                }
                if (form.dataset.action === 'approve') {
                    const row = form.closest('.js-order-row');
                    if (row) {
                        const invalidLines = [];
                        row.querySelectorAll('.js-product-line').forEach(function (line) {
                            const estWeight = parseFloat(line.dataset.productEstWeight || '0');
                            if (estWeight <= 0) {
                                invalidLines.push(line.dataset.productName || 'Sản phẩm');
                            }
                        });
                        if (invalidLines.length > 0) {
                            const errId = 'approveErr_' + (row.dataset.orderId || '');
                            let errEl = document.getElementById(errId);
                            if (!errEl) {
                                errEl = document.createElement('div');
                                errEl.id = errId;
                                errEl.className = 'text-danger small mt-2 px-1';
                                const orderTop = row.querySelector('.tmo-order-top');
                                if (orderTop) {
                                    orderTop.insertAdjacentElement('afterend', errEl);
                                } else {
                                    row.appendChild(errEl);
                                }
                            }
                            errEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Không thể duyệt: <strong>' + invalidLines.join(', ') + '</strong> có KL quy đổi = 0.';
                            return;
                        }
                    }
                }
                submitApproval(form);
            });
        });

        applySort();
        recalcSummary();
    }

    bindInteractiveEvents();
});
</script>
@endsection
