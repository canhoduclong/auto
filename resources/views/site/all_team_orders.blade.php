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
    .tmo-page .tmo-card-body { padding: .9rem; }
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
    .tmo-page .tmo-summary-value {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
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
        transition: background-color .28s ease, border-color .28s ease, box-shadow .28s ease, transform .22s ease;
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
    .tmo-page .tmo-row-pending { border-left: 4px solid #111827; }
    .tmo-page .tmo-row-approved { border-left: 4px solid #15803d; background: #f0fdf4; }
    .tmo-page .tmo-row-rejected { border-left: 4px solid #dc2626; background: #fef2f2; }
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
        .tmo-page .tmo-order-top { grid-template-columns: 1fr 1fr 1fr; }
    }
    @media (max-width: 991.98px) {
        .tmo-page .tmo-aside-sticky,
        .tmo-page .tmo-order-toolbar { position: static; }
        .tmo-page .tmo-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tmo-page .tmo-auto-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="container py-3 tmo-page">
    <div class="tmo-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">Duyet Don PKD</h4>
                <div class="opacity-75">Bo cuc giong Team Orders de manager theo doi va duyet nhanh.</div>
            </div>
            <div class="small opacity-75">Manager: {{ $user->name }}</div>
        </div>
    </div>

    <div class="tmo-card mb-3">
        <div class="tmo-card-header d-flex justify-content-between align-items-center">
            <div class="fw-semibold">Khu vuc duyet tu dong (Manager)</div>
            <button class="btn btn-sm btn-outline-primary tmo-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#autoApproveCollapse" aria-expanded="false" aria-controls="autoApproveCollapse">
                <i class="bi bi-chevron-expand"></i>
                Mo / Thu gon
            </button>
        </div>
        <div class="collapse" id="autoApproveCollapse">
            <div class="tmo-card-body">
                <form method="POST" action="{{ route('pages.all_tearm_orders.auto_approve') }}" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="from_date" value="{{ $fromDate }}">
                    <input type="hidden" name="to_date" value="{{ $toDate }}">
                    <input type="hidden" name="team_id" value="{{ request('team_id') }}">

                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-3 h-100">
                            <h6 class="mb-2">Dieu kien theo so luong</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="condition_item_qty" name="condition_item_qty" value="1" {{ old('condition_item_qty') ? 'checked' : '' }}>
                                <label class="form-check-label" for="condition_item_qty">Bat dieu kien so luong san pham trong don</label>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label mb-1">SL toi thieu</label><input type="number" min="1" class="form-control" name="min_item_qty" value="{{ old('min_item_qty') }}"></div>
                                <div class="col-6"><label class="form-label mb-1">SL toi da</label><input type="number" min="1" class="form-control" name="max_item_qty" value="{{ old('max_item_qty') }}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-3 h-100">
                            <h6 class="mb-2">Dieu kien theo gia tri don</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="condition_sale_price" name="condition_sale_price" value="1" {{ old('condition_sale_price') ? 'checked' : '' }}>
                                <label class="form-check-label" for="condition_sale_price">Bat dieu kien tong gia tri don hang</label>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label mb-1">Gia tri toi thieu</label><input type="number" min="0" step="1000" class="form-control" name="min_sale_price" value="{{ old('min_sale_price') }}"></div>
                                <div class="col-6"><label class="form-label mb-1">Gia tri toi da</label><input type="number" min="0" step="1000" class="form-control" name="max_sale_price" value="{{ old('max_sale_price') }}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded-3 p-3">
                            <h6 class="mb-2">Discount theo san luong</h6>
                            <div class="tmo-auto-grid">
                                <div><label class="form-label mb-1">Moc 20 (freeship)</label><input type="number" min="0" step="1000" class="form-control" name="freeship_20_amount" value="{{ old('freeship_20_amount') }}"></div>
                                <div><label class="form-label mb-1">Moc 30</label><input type="number" min="0" step="1000" class="form-control" name="discount_30_amount" value="{{ old('discount_30_amount') }}"></div>
                                <div><label class="form-label mb-1">Moc 40</label><input type="number" min="0" step="1000" class="form-control" name="discount_40_amount" value="{{ old('discount_40_amount') }}"></div>
                                <div><label class="form-label mb-1">Moc 50</label><input type="number" min="0" step="1000" class="form-control" name="discount_50_amount" value="{{ old('discount_50_amount') }}"></div>
                                <div><label class="form-label mb-1">Moc 70</label><input type="number" min="0" step="1000" class="form-control" name="discount_70_amount" value="{{ old('discount_70_amount') }}"></div>
                                <div><label class="form-label mb-1">Moc 80</label><input type="number" min="0" step="1000" class="form-control" name="discount_80_amount" value="{{ old('discount_80_amount') }}"></div>
                                <div><label class="form-label mb-1">Moc 100</label><input type="number" min="0" step="1000" class="form-control" name="discount_100_amount" value="{{ old('discount_100_amount') }}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-7">
                        <div class="border rounded-3 p-3">
                            <h6 class="mb-2">Khach hang dac biet</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="use_special_customer_discount" name="use_special_customer_discount" value="1" {{ old('use_special_customer_discount') ? 'checked' : '' }}>
                                <label class="form-check-label" for="use_special_customer_discount">Bat discount rieng cho khach hang dac biet</label>
                            </div>
                            <div><label class="form-label mb-1">Muc discount khach dac biet</label><input type="number" min="0" step="1000" class="form-control" name="special_customer_discount_amount" value="{{ old('special_customer_discount_amount') }}"></div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">Duyet don tu dong</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php
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
                    <div class="tmo-card-header fw-semibold text-uppercase">Loc du lieu</div>
                    <div class="tmo-card-body">
                        <form method="GET" action="{{ route('pages.all_tearm_orders') }}" class="row g-2">
                            <div class="col-12">
                                <label class="form-label mb-1">Tim kiem</label>
                                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Ma don, khach, sale">
                            </div>
                            <div class="col-6 col-lg-12">
                                <label class="form-label mb-1">Tu ngay</label>
                                <input type="date" class="form-control" name="from_date" value="{{ $fromDate }}">
                            </div>
                            <div class="col-6 col-lg-12">
                                <label class="form-label mb-1">Den ngay</label>
                                <input type="date" class="form-control" name="to_date" value="{{ $toDate }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Team</label>
                                <select class="form-select" name="team_id">
                                    <option value="">Tat ca team</option>
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Trang thai don</label>
                                <input type="text" class="form-control" name="status" value="{{ request('status') }}" placeholder="pending_manager_approval">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="pending_only" id="pending_only" value="1" {{ $pendingOnly ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pending_only">Chi don toi luot toi duyet</label>
                                </div>
                            </div>
                            <div class="col-12 d-grid gap-2">
                                <button class="btn btn-primary" type="submit">Ap dung loc</button>
                                <a href="{{ route('pages.all_tearm_orders') }}" class="btn btn-outline-secondary">Don hom nay</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tmo-card">
                    <div class="tmo-card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Danh sach sale</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSaleFilter">Bo chon</button>
                    </div>
                    <div class="tmo-card-body">
                        <div class="tmo-sales-list" id="saleQuickList">
                            @forelse($saleGroups as $sale)
                                <button type="button" class="tmo-sale-chip js-sale-chip" data-sale-id="{{ $sale['id'] }}">
                                    <div class="fw-semibold">{{ $sale['name'] }}</div>
                                    <div class="tmo-mini">Team: {{ $sale['team'] ?? '-' }} | {{ $sale['count'] }} don</div>
                                </button>
                            @empty
                                <div class="tmo-mini">Khong co sale trong danh sach hien tai.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9">
            <div class="row g-3 mb-2">
                <div class="col-12 col-md-4">
                    <div class="card tmo-kpi"><div class="card-body"><div class="text-muted small">Tong don</div><div class="value text-primary">{{ number_format($stats['total']) }}</div></div></div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card tmo-kpi"><div class="card-body"><div class="text-muted small">Cho manager duyet</div><div class="value text-warning">{{ number_format($stats['pending']) }}</div></div></div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card tmo-kpi"><div class="card-body"><div class="text-muted small">Da duyet</div><div class="value text-success">{{ number_format($stats['approved']) }}</div></div></div>
                </div>
            </div>

            <div class="tmo-summary mb-3" id="orderSummaryPanel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Thong ke theo bo loc</div>
                    <div class="tmo-mini">Tinh tren danh sach hien thi</div>
                </div>
                <div class="tmo-summary-grid">
                    <div class="tmo-summary-item">
                        <div class="tmo-summary-label">So don dang hien thi</div>
                        <div class="tmo-summary-value" id="sumVisibleOrders">0</div>
                    </div>
                    <div class="tmo-summary-item">
                        <div class="tmo-summary-label">Tong gia tri don</div>
                        <div class="tmo-summary-value" id="sumOrderTotal">0 đ</div>
                    </div>
                    <div class="tmo-summary-item">
                        <div class="tmo-summary-label">Da duyet / Cho duyet / Tu choi</div>
                        <div class="tmo-summary-value" id="sumStatus">0 / 0 / 0</div>
                    </div>
                </div>
            </div>

            <div class="tmo-order-toolbar mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="fw-semibold">Danh sach don hang</div>
                    <div class="tmo-mini" id="saleFilterState">Hien thi toan bo don theo bo loc hien tai.</div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <label class="small text-muted mb-0" for="orderSort" style="min-width:60px">Sap xep:</label>
                    <select id="orderSort" class="form-select form-select-sm" style="min-width: 210px;">
                        <option value="created_desc">Ngay tao moi nhat</option>
                        <option value="created_asc">Ngay tao cu nhat</option>
                        <option value="total_desc">Gia tri don giam dan</option>
                        <option value="total_asc">Gia tri don tang dan</option>
                    </select>
                </div>
            </div>

            <div class="tmo-orders" id="orderList">
                @forelse($orders as $order)
                    @php
                        $step = $currentStepByOrder[$order->id] ?? null;
                        $canApprove = $canApproveByOrder[$order->id] ?? false;
                        $canProcess = $canApprove && optional($order->created_at)?->isToday();
                        $statusClass = match ($order->status) {
                            'pending_manager_approval' => 'tmo-status-pending',
                            'approved' => 'tmo-status-approved',
                            'rejected' => 'tmo-status-rejected',
                            default => 'tmo-status-default',
                        };
                        $statusLabel = match ($order->status) {
                            'pending_manager_approval' => 'Cho Duyet',
                            'approved' => 'Da Duyet',
                            'rejected' => 'Tu Choi',
                            default => $order->status,
                        };
                        $rowStateClass = match ($order->status) {
                            'approved' => 'tmo-row-approved',
                            'rejected' => 'tmo-row-rejected',
                            default => 'tmo-row-pending',
                        };
                        $createdTs = optional($order->created_at)?->timestamp ?? 0;
                    @endphp

                    <article class="tmo-order-row js-order-row {{ $rowStateClass }}"
                        data-order-id="{{ $order->id }}"
                        data-sale-id="{{ $order->user?->id }}"
                        data-total="{{ (float) $order->total }}"
                        data-created="{{ $createdTs }}">

                        <div class="tmo-order-top">
                            <div>
                                <div class="tmo-code">{{ $order->code }}</div>
                                <div class="tmo-mini">Tao: {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $order->customer->name ?? '-' }}</div>
                                <div class="tmo-mini">{{ $order->customer->phone ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $order->user->name ?? '-' }}</div>
                                <div class="tmo-mini">Team: {{ $order->user?->team?->name ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ number_format((float) $order->total, 0, ',', '.') }} đ</div>
                                <div class="tmo-mini">Tong gia tri don</div>
                            </div>
                            <div>
                                <span class="tmo-status js-order-status {{ $statusClass }}" data-status="{{ $order->status }}">{{ $statusLabel }}</span>
                                <div class="tmo-mini mt-1">Buoc: {{ $step?->step?->role_slug ?? 'Khong co' }}</div>
                            </div>
                            <div class="d-flex gap-1 flex-wrap justify-content-end">
                                <a href="{{ route('pages.team_order_detail', $order) }}" class="btn btn-sm btn-outline-primary">Chi tiet</a>
                                @if($canProcess)
                                    <form method="POST" action="{{ route('site.orders.approve', $order) }}">
                                        @csrf
                                        <input type="hidden" name="note" value="Manager duyet tu trang all team orders">
                                        <button type="submit" class="btn btn-sm btn-success">Duyet</button>
                                    </form>
                                    <form method="POST" action="{{ route('site.orders.reject', $order) }}">
                                        @csrf
                                        <input type="hidden" name="note" value="Manager tu choi tu trang all team orders">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Xac nhan tu choi don nay?')">Tu choi</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="tmo-empty">Khong co don hang phu hop.</div>
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

    function getRows() {
        const orderList = document.getElementById('orderList');
        return orderList ? Array.from(orderList.querySelectorAll('.js-order-row')) : [];
    }

    function formatMoney(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' đ';
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
            recalcSummary();
            return;
        }

        if (!activeSaleId) {
            saleState.textContent = 'Hien thi toan bo don theo bo loc hien tai.';
            recalcSummary();
            return;
        }

        const active = saleChips.find(function (chip) {
            return chip.dataset.saleId === String(activeSaleId);
        });
        const saleName = active ? (active.querySelector('.fw-semibold')?.textContent || '') : '';
        saleState.textContent = 'Dang xem nhanh don cua sale: ' + saleName;
        recalcSummary();
    }

    function recalcSummary() {
        const rows = getRows().filter(function (row) {
            return row.style.display !== 'none';
        });

        let approved = 0;
        let pending = 0;
        let rejected = 0;
        let totalValue = 0;

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
        });

        const sumVisibleOrders = document.getElementById('sumVisibleOrders');
        const sumOrderTotal = document.getElementById('sumOrderTotal');
        const sumStatus = document.getElementById('sumStatus');

        if (sumVisibleOrders) {
            sumVisibleOrders.textContent = String(rows.length);
        }
        if (sumOrderTotal) {
            sumOrderTotal.textContent = formatMoney(totalValue);
        }
        if (sumStatus) {
            sumStatus.textContent = approved + ' / ' + pending + ' / ' + rejected;
        }
    }

    function applySort() {
        const orderList = document.getElementById('orderList');
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
            return 0;
        });

        rows.forEach(function (row) {
            orderList.appendChild(row);
        });

        applySaleFilter();
    }

    const saleChips = Array.from(document.querySelectorAll('.js-sale-chip'));
    const clearSaleBtn = document.getElementById('clearSaleFilter');
    const sortSelect = document.getElementById('orderSort');

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

    applySort();
    recalcSummary();
});
</script>
@endsection
