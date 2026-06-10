@extends($ordersLayout ?? 'layouts.warehouse')

@section('title', $ordersPageTitle ?? 'Đơn hàng cần xử lý')
@section('subtitle', $ordersPageSubtitle ?? 'Xem và xử lý đơn theo ngày')

@push('styles')
<style>
    .actual_weight {
        width: 96px;
    }
    .wh-summary-pill {
        border-radius: 999px;
        padding: 7px 12px;
        font-size: .82rem;
        font-weight: 700;
    }
    .wh-order-card {
        position: relative;
        border: 0;
        border-radius: 12px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        height: 100%;
        scroll-margin-top: 140px;
    }
    .wh-order-index {
        border-radius: 50px;
        width: 40px;
        z-index: 2; 
        font-weight: 700; 
        padding: 6px 8px;
        background: #0f172a;
        color: #fff;
        margin-right: 12px;
    }
    .wh-order-index.is-packed {
        background: #198754;
        color: #fff;
    }
    .wh-order-index.is-packing {
        --bs-bg-opacity: 1;
        background: rgba(var(--bs-warning-rgb), var(--bs-bg-opacity)) !important;
        color: #212529;
    }
    .wh-order-index.is-unpacked {
        background: #64748b;
        color: #fff;
    }
    .card-desript{
        font-size: .75rem;
        color: #64748b;
    }
    .wh-meta-label {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .wh-meta-value {
        font-size: .9rem;
        font-weight: 700;
        color: #0f172a;
    }
    .wh-section {
        padding: 0; 
    }
    .wh-logistics-title {
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
        grid-template-columns: 48px minmax(50px, 1fr) 42px 52px 90px 90px 61px 76px;
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
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .wh-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: center;
    }
    .wh-item-cell strong {
        color: #0f172a;
    }
    .wh-item-action {
        text-align: right;
    }
    .wh-warning-action-btn {
        --bs-bg-opacity: 1;
        background-color: rgba(var(--bs-warning-rgb), var(--bs-bg-opacity)) !important;
        border-color: rgba(var(--bs-warning-rgb), var(--bs-bg-opacity)) !important;
        color: #212529 !important;
    }
    .wh-warning-action-btn:hover,
    .wh-warning-action-btn:focus,
    .wh-warning-action-btn:active {
        --bs-bg-opacity: .9;
        background-color: rgba(var(--bs-warning-rgb), var(--bs-bg-opacity)) !important;
        border-color: rgba(var(--bs-warning-rgb), var(--bs-bg-opacity)) !important;
        color: #212529 !important;
    }
    .wh-compact-form {
        display: flex;
        gap: 6px;
        align-items: center;
    }
    .wh-readonly-item {
        font-size: .78rem;
        color: #475569;
        font-weight: 600;
        text-align: center
    }
    .wh-quick-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .wh-quick-pill {
        border-radius: 999px;
        padding: 6px 10px;
        font-size: .78rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
    }
    .wh-quick-pill.active {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .wh-quick-pill.disabled {
        opacity: .55;
        background: #f8fafc;
        color: #64748b;
        border-style: dashed;
        pointer-events: none;
    }
    .wh-quick-count {
        min-width: 20px;
        height: 20px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
        background: #e2e8f0;
        color: #334155;
        padding: 0 6px;
    }
    .wh-quick-pill.active .wh-quick-count {
        background: #2563eb;
        color: #fff;
    }
    .wh-stock-alert {
        border: 1px solid #fca5a5;
        background: #fef2f2;
        color: #991b1b;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 10px;
    }
    .wh-stock-alert summary {
        cursor: pointer;
        font-weight: 700;
    }
    .wh-stock-alert ul {
        margin: 8px 0 0;
        padding-left: 16px;
    }
    .wh-stock-alert a {
        color: #9a3412;
        font-weight: 700;
    }
    .wh-stock-panel {
        border: 1px solid #dbe4ef;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }
    .wh-stock-panel .card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
    .wh-stock-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 10px;
    }
    .wh-stock-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px;
        background: #fff;
    }
    .wh-stock-name {
        font-size: .86rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 8px;
    }
    .stock-bar-wrap {
        margin-bottom: 8px;
    }
    .stock-bar-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: .74rem;
        color: #64748b;
        margin-bottom: 4px;
    }
    .stock-bar-track {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }
    .stock-bar-fill {
        height: 100%;
        border-radius: 999px;
    }
    .stock-bar-fill.stock-available { background: linear-gradient(90deg, #2563eb, #0ea5e9); }
    .stock-bar-fill.stock-ordered { background: linear-gradient(90deg, #f59e0b, #f97316); }
    .stock-bar-fill.stock-packed { background: linear-gradient(90deg, #16a34a, #22c55e); }
    @media (max-width: 575px) {
        .wh-item-table-head,
        .wh-item-table-row {
            grid-template-columns: 44px minmax(140px, 1.15fr) 40px 52px 54px 84px 96px 124px;
            min-width: 700px;
            gap: 6px;
        }
    }

    /* ── Stock Drawer (offcanvas) ───────────────────────── */
    :root { --stock-drawer-width: min(92vw, 460px); }
    #stockDrawer {
        width: var(--stock-drawer-width);
    }
    body.stock-drawer-pinned .wh-orders-shell {
        padding-right: calc(var(--stock-drawer-width) + 16px);
        transition: padding-right .25s ease;
    }
    body.stock-drawer-pinned #stockDrawer {
        visibility: visible !important;
        transform: none !important;
        box-shadow: -4px 0 24px rgba(15,23,42,0.10);
    }
    #stockDrawer .offcanvas-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    #stockDrawer .offcanvas-body { 
        overflow-y: auto;
    }
    #stockDrawerPinBtn.pinned {
        background: #dbeafe;
        border-color: #2563eb;
        color: #1d4ed8;
    }
    .wh-stock-trigger-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .wh-stock-item.is-short {
        border-color: #fca5a5;
        background: #fff8f8;
    }
    .wh-stock-item.is-short .wh-stock-name {
        color: #991b1b;
    }
    .wh-stock-shortage-badge {
        display: inline-block;
        font-size: .7rem;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 999px;
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fca5a5;
        margin-left: 4px;
    }
    .wh-stock-summary {
        border: 1px solid #e2e8f0; 
        background: #f8fafc;
        padding: 10px 12px;
        margin-bottom: 14px;
        font-size: .82rem;
    }
    .wh-stock-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 3px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .wh-stock-summary-row:last-child { border-bottom: 0; }
    .wh-stock-summary-row .label { color: #64748b; }
    .wh-stock-summary-row .value { font-weight: 700; color: #0f172a; }
    .wh-stock-summary-row .value.danger { color: #dc2626; }
    .wh-stock-list {
        border: 1px solid #e2e8f0; 
        overflow: hidden;
        background: #fff;
    }
    .wh-stock-row {
        display: grid;
        grid-template-columns: minmax(110px, 2.1fr) minmax(52px, .8fr) minmax(50px, .95fr) minmax(50px, .95fr) minmax(55px, .9fr) minmax(60px, 1fr);
        column-gap: 8px;
        align-items: center;
        padding: 6px 9px;
        border-bottom: 1px solid #eef2f7;
    }
    .wh-stock-row:last-child {
        border-bottom: 0;
    }
    .wh-stock-row.is-short {
        background: #fff8f8;
    }
    .wh-stock-row.head {
        background: #f8fafc;
        font-size: .74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #334155;
    }
    .wh-stock-col {
        min-width: 0;
    }
    .wh-stock-col.num {
        text-align: right;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }
    .wh-stock-col.col-size {
        text-align: center;
        font-weight: 700;
        color: #334155;
        white-space: nowrap;
    }
    .wh-stock-col.col-available {
        color: var(--theme-primary);
    }
    .wh-stock-col.col-ordered {
        color: #984107;
    }
    .wh-stock-col.col-packed {
        color: #600240;
    }
    .wh-stock-col.num.is-low {
        color: #dc2626;
    }
    .wh-stock-product-name {
        font-size: .86rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .wh-stock-product-sku {
        font-size: .74rem;
        color: #64748b;
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    @media (max-width: 560px) {
        .wh-stock-row {
            grid-template-columns: minmax(136px, 1.8fr) repeat(5, minmax(50px, .72fr));
            column-gap: 6px;
            padding: 9px 10px;
        }
        .wh-stock-row.head {
            font-size: .67rem;
        }
        .wh-stock-col.num {
            font-size: .8rem;
        }
    }
    .wh-drawer-date-badge {
        display: inline-block;
        font-size: .75rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        margin-top: 2px;
    }
    .wh-adjustment-pending-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fff;
    }
    .wh-adjustment-picker-results .list-group-item {
        gap: 10px;
        align-items: center;
    }

    [id^="order-card-"] {
        scroll-margin-top: 150px;
    }
    
    /* ── Order Sequence Navigation ───────────────────────── */
    .wh-order-nav-area {
        position: sticky;
        top: 75px;
        z-index: 95;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .wh-order-nav-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 8px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        color: #fff !important;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .wh-order-nav-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .wh-order-nav-pill.is-packed {
        background-color: #198754;
    }
    .wh-order-nav-pill.is-packing {
        --bs-bg-opacity: 1;
        background-color: rgba(var(--bs-warning-rgb), var(--bs-bg-opacity)) !important;
        color: #212529 !important;
    }
    .wh-order-nav-pill.is-unpacked {
        background-color: #64748b;
    }
</style>
@endpush

@section('content')
@if($orderChangesMode ?? false)
    @include('package.order_changes')
@else
@php
    $formatCompactDecimal = static function (float|int|string $value, int $decimals = 2): string {
        $num = (float) $value;
        $str = number_format($num, $decimals, ',', '.');
        return rtrim(rtrim($str, '0'), ',');
    };
    $formatKg = static function (float|int|string $value) use ($formatCompactDecimal): string {
        return $formatCompactDecimal($value) . ' kg';
    };
    $fifoRemainingStock = $fifoRemainingStock ?? [];
    $statusMeta = [
        'approved' => ['label' => 'Chờ đóng gói', 'class' => 'bg-primary'],
        'ready_to_pack' => ['label' => 'Chờ đóng gói', 'class' => 'bg-primary'],
        'packing' => ['label' => 'Đang đóng gói', 'class' => 'bg-warning text-dark'],
        'packed' => ['label' => 'Đã đóng gói', 'class' => 'bg-info text-dark'],
        'packed_waiting_pickup' => ['label' => 'Chờ shipper nhận', 'class' => 'bg-info text-dark'],
        'delivering' => ['label' => 'Đang giao', 'class' => 'bg-secondary'],
        'delivered' => ['label' => 'Đã giao', 'class' => 'bg-success'],
        'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-success'],
        'pending' => ['label' => 'Chờ duyệt', 'class' => 'bg-light text-dark'],
        'pending_leader_approval' => ['label' => 'Chờ trưởng nhóm duyệt', 'class' => 'bg-light text-dark'],
        'pending_manager_approval' => ['label' => 'Chờ quản lý duyệt', 'class' => 'bg-light text-dark'],
        'pending_warehouse_approval' => ['label' => 'Chờ kho duyệt', 'class' => 'bg-light text-dark'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'bg-danger'],
    ];

    $packedLikeStatuses = ['packed', 'packed_waiting_pickup', 'delivering', 'delivered', 'completed'];

    $variantStock = $variantStock ?? [];
    $stockPanelVariants = $stockPanelVariants ?? collect();
    $orderStatsByVariant = $orders
        ->flatMap(function ($order) use ($packedLikeStatuses, $variantStock) {
            return $order->items->map(function ($item) use ($order, $packedLikeStatuses, $variantStock) {
                $variant = $item->variant;
                $productName = $variant?->name ?? $item->product?->name ?? 'Sản phẩm';
                $orderedQty = (float) ($item->quantity ?? 0);
                $packedQty = in_array((string) $order->status, $packedLikeStatuses, true) ? $orderedQty : 0;
                $vid = (int) ($item->product_variant_id ?? 0);
                // Warehouse-specific stock (not cross-warehouse available_stock)
                $warehouseStock = isset($variantStock[$vid]) ? (float) $variantStock[$vid] : (float) ($variant?->available_stock ?? 0);

                return [
                    'variant_id'  => $vid,
                    'name'        => $productName,
                    'raw_stock'   => max(0, $warehouseStock),
                    'ordered_qty' => $orderedQty,
                    'packed_qty'  => $packedQty,
                ];
            });
        })
        ->filter(fn($row) => (int) ($row['variant_id'] ?? 0) > 0)
        ->groupBy('variant_id')
        ->map(function ($rows) use ($fifoRemainingStock) {
            $first     = $rows->first();
            $variantId = (int) ($first['variant_id'] ?? 0);
            $rawStock  = (float) ($first['raw_stock'] ?? 0);
            // fifo_remaining = stock left after ALL globally-queued prior orders consume their share
            // This matches exactly what the FIFO packing guard uses to allow/block packing
            $fifoRemaining = isset($fifoRemainingStock[$variantId])
                ? max(0, (float) $fifoRemainingStock[$variantId])
                : $rawStock;
            return [
                'name'          => $first['name'] ?? 'Sản phẩm',
                'raw_stock'     => $rawStock,
                'fifo_remaining'=> $fifoRemaining,
                'ordered_qty'   => (float) $rows->sum('ordered_qty'),
                'packed_qty'    => (float) $rows->sum('packed_qty'),
            ];
        })
        ->map(function ($item) {
            $fifoRemaining = (float) $item['fifo_remaining'];
            $ordered       = (float) $item['ordered_qty'];
            // Shortage = gap between what this date needs vs what FIFO left for it
            // (consistent with packing guard logic)
            $shortage = max(0, $ordered - $fifoRemaining);
            return array_merge($item, [
                'shortage' => $shortage,
                'is_short' => $shortage > 0,
            ]);
        })
        ->keyBy('variant_id');

    $inventoryStats = collect($stockPanelVariants)
        ->map(function ($variant) use ($orderStatsByVariant, $fifoRemainingStock, $variantStock) {
            $variantId = (int) $variant->id;
            $rawStock = max(0, (float) ($variantStock[$variantId] ?? $variant->available_stock ?? 0));
            $orderStat = $orderStatsByVariant->get($variantId, []);
            $orderedQty = (float) ($orderStat['ordered_qty'] ?? 0);
            $packedQty = (float) ($orderStat['packed_qty'] ?? 0);
            $fifoRemaining = isset($fifoRemainingStock[$variantId])
                ? max(0, (float) $fifoRemainingStock[$variantId])
                : $rawStock;
            $name = $variant->name ?: $variant->product?->name ?: 'Sản phẩm';
            $shortage = max(0, $orderedQty - $fifoRemaining);

            return [
                'variant_id' => $variantId,
                'name' => $name,
                'sku' => $variant->sku,
                'size' => $variant->size,
                'raw_stock' => $rawStock,
                'fifo_remaining' => $fifoRemaining,
                'ordered_qty' => $orderedQty,
                'packed_qty' => $packedQty,
                'shortage' => $shortage,
                'is_short' => $shortage > 0,
            ];
        })
        ->filter(fn ($item) => (float) $item['raw_stock'] > 0 || (float) $item['ordered_qty'] > 0 || (float) $item['packed_qty'] > 0)
        ->sortByDesc(fn($i) => [(int)$i['is_short'], $i['ordered_qty'], $i['raw_stock']])
        ->values();

    // Summary totals
    $stockSummary = [
        'total_products' => $inventoryStats->count(),
        'short_products' => $inventoryStats->where('is_short', true)->count(),
        'total_ordered'  => $inventoryStats->sum('ordered_qty'),
        'total_packed'   => $inventoryStats->sum('packed_qty'),
        'total_shortage' => $inventoryStats->sum('shortage'),
    ];
@endphp
<div class="wh-orders-shell">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Ngày</label>
                    <input type="date" name="date" class="form-control" value="{{ $selectedDate ?? now()->toDateString() }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="ready_to_pack" {{ ($status ?? '') === 'ready_to_pack' ? 'selected' : '' }}>Chờ đóng gói</option>
                        <option value="packing" {{ ($status ?? '') === 'packing' ? 'selected' : '' }}>Đang đóng gói</option>
                        <option value="packed" {{ ($status ?? '') === 'packed' ? 'selected' : '' }}>Đã đóng gói</option>
                        <option value="packed_waiting_pickup" {{ ($status ?? '') === 'packed_waiting_pickup' ? 'selected' : '' }}>Chờ shipper nhận</option>
                    </select>
                </div>
                <div class="col-md-8 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-funnel me-1"></i>Lọc
                    </button>
                    <a href="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Hôm nay
                    </a>
                    <div class="mx-3">
                         
                        <div class="wh-quick-wrap">
                            @foreach($quickDates as $quickDate)
                                @if($quickDate['available'])
                                    <a href="{{ route(($orderRoutePrefix ?? 'warehouse') . '.orders', array_filter(['date' => $quickDate['date'], 'status' => $status ?: null])) }}"
                                    class="wh-quick-pill {{ $quickDate['active'] ? 'active' : '' }}">
                                        {{ $quickDate['label'] }}
                                        <span class="wh-quick-count">{{ $quickDate['count'] }}</span>
                                    </a>
                                @else
                                    <span class="wh-quick-pill disabled">
                                        {{ $quickDate['label'] }}
                                        <span class="wh-quick-count">0</span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </form>

            
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-dark wh-summary-pill">Tổng đơn: {{ $orders->count() }}</span>
            <span class="badge bg-primary wh-summary-pill">Chờ đóng gói: {{ $orders->whereIn('status', ['approved', 'ready_to_pack'])->count() }}</span>
            <span class="badge bg-warning text-dark wh-summary-pill">Đang đóng: {{ $orders->where('status', 'packing')->count() }}</span>
            <span class="badge bg-danger wh-summary-pill">Sale từ chối điều chỉnh: {{ $orders->where('warehouse_adjustment_status', \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED)->count() }}</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button"
                class="btn btn-outline-info btn-sm wh-stock-trigger-btn"
                id="stockDrawerToggleBtn"
                data-bs-toggle="offcanvas"
                data-bs-target="#stockDrawer"
                aria-controls="stockDrawer">
                <i class="bi bi-bar-chart-steps"></i>Tồn kho
                @if($inventoryStats->isNotEmpty())
                    <span class="badge bg-info text-dark ms-1">{{ $inventoryStats->count() }}</span>
                @endif
            </button>
            <a href="{{ route($packingDashboardRoute ?? 'warehouse.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>



    @if($orders->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <i class="bi bi-check2-all fs-1 text-success"></i>
            <p class="mt-2 text-muted">Không có đơn nào cần xử lý lúc này.</p>
        </div>
    @else
    @php
        $packedOrders = $orders->filter(fn($o) => in_array((string)$o->status, $packedLikeStatuses, true));
        $unpackedOrders = $orders->reject(fn($o) => in_array((string)$o->status, $packedLikeStatuses, true))->sortBy('daily_sequence');
    @endphp

    <div class="wh-order-nav-area mb-4">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-bold text-muted me-1"><i class="bi bi-list-ol me-1"></i>Điều hướng nhanh:</span>
            @foreach($orders->sortBy('daily_sequence') as $navOrder)
                @php
                    $isPackedNav = in_array((string)$navOrder->status, $packedLikeStatuses, true);
                    $isPackingNav = (string) $navOrder->status === 'packing';
                    $navStateClass = $isPackingNav ? 'is-packing' : ($isPackedNav ? 'is-packed' : 'is-unpacked');
                    $sequenceNumber = $navOrder->daily_sequence ?? $loop->iteration;
                @endphp
                <a href="javascript:void(0);"
                   onclick="document.getElementById('order-card-{{ $navOrder->id }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' });"
                   class="wh-order-nav-pill {{ $navStateClass }}"
                   data-order-id="{{ $navOrder->id }}"
                   title="{{ $navOrder->customer?->name ?? 'Đơn hàng' }}">
                    {{ $sequenceNumber }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="row g-4">
        <!-- Cột trái: Đơn chưa đóng -->
        <div class="col-12 col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold" style="color:#64748b;">
                    <i class="bi bi-box-seam me-2"></i>Chưa đóng hàng
                </h5>
                <span class="badge" style="background:#64748b;color:#fff;">{{ $unpackedOrders->count() }} đơn</span>
            </div>
            <div class="row g-3">
                @foreach($unpackedOrders as $order)
                    @include('warehouse.orders._order_card', ['order' => $order, 'statusMeta' => $statusMeta, 'activeTransfersByOrder' => $activeTransfersByOrder ?? [], 'selectedDate' => $selectedDate ?? now()->toDateString()])
                @endforeach
            </div>
        </div>

        <!-- Cột phải: Đơn đã đóng -->
        <div class="col-12 col-lg-6 border-start">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold text-success">
                    <i class="bi bi-check-circle me-2"></i>Đã đóng hàng
                </h5>
                <span class="badge bg-success rounded-pill">{{ $packedOrders->count() }} đơn</span>
            </div>
            <div class="row g-3">
                @foreach($packedOrders as $order)
                    @include('warehouse.orders._order_card', ['order' => $order, 'statusMeta' => $statusMeta, 'activeTransfersByOrder' => $activeTransfersByOrder ?? [], 'selectedDate' => $selectedDate ?? now()->toDateString()])
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Stock Offcanvas Drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="stockDrawer" aria-labelledby="stockDrawerLabel" data-bs-scroll="true" data-bs-backdrop="false">
    <div class="offcanvas-header">
        <div id="stockDrawerLabel">
            
            <div class="text-uppercase fw-bold" style="font-size:1rem;">
                <i class="bi bi-calendar3 me-1"></i>
                @php
                    $displayDate = \Illuminate\Support\Carbon::parse($selectedDate);
                    $isToday = $displayDate->isToday();
                @endphp
                {{ $isToday ? 'Hôm nay – ' : '' }}{{ $displayDate->format('d/m/Y') }}
                @if($status)
                    &middot; {{ $statusMeta[$status]['label'] ?? $status }}
                @endif
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="stockDrawerPinBtn" class="btn btn-sm btn-outline-secondary" title="Neo cố định bên phải">
                <i class="bi bi-pin-angle me-1"></i>Neo
            </button>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
        </div>
    </div>
    <div class="warehouse-body">
        @if($inventoryStats->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2">Không có đơn hàng nào trong ngày này.</p>
            </div>
        @else
           
            <div class="wh-stock-list">
                <div class="wh-stock-row head">
                    <div class="wh-stock-col">Tên sản phẩm</div>
                    <div class="wh-stock-col col-size">Size</div>
                    <div class="wh-stock-col num">Tồn kho</div>
                    <div class="wh-stock-col num col-available">Khả dụng</div>
                    <div class="wh-stock-col num col-ordered">SL đặt</div>
                    <div class="wh-stock-col num col-packed">Đã đóng</div>
                </div>
                @foreach($inventoryStats as $stockItem)
                    @php
                        $isShort = (bool) ($stockItem['is_short'] ?? false);
                    @endphp
                    <div class="wh-stock-row {{ $isShort ? 'is-short' : '' }}">
                        <div class="wh-stock-col">
                            <div class="wh-stock-product-name" title="{{ $stockItem['name'] }}">{{ $stockItem['name'] }}</div>
                            @if(!empty($stockItem['sku']))
                                <div class="wh-stock-product-sku" title="SKU: {{ $stockItem['sku'] }}">SKU: {{ $stockItem['sku'] }}</div>
                            @endif
                        </div>
                        <div class="wh-stock-col col-size">{{ (is_numeric($stockItem['size']) && (float) $stockItem['size'] > 0) ? rtrim(rtrim(number_format((float) $stockItem['size'], 2, '.', ''), '0'), '.') : '-' }}</div>
                        <div class="wh-stock-col num">{{ number_format((float) ($stockItem['raw_stock'] ?? 0), 0, ',', '.') }}</div>
                        <div class="wh-stock-col num col-available">{{ number_format((float) ($stockItem['fifo_remaining'] ?? 0), 0, ',', '.') }}</div>
                        <div class="wh-stock-col num col-ordered">{{ number_format((float) ($stockItem['ordered_qty'] ?? 0), 0, ',', '.') }}</div>
                        <div class="wh-stock-col num col-packed">{{ number_format((float) ($stockItem['packed_qty'] ?? 0), 0, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="warehouseAdjustmentProductModal" tabindex="-1" aria-labelledby="warehouseAdjustmentProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="warehouseAdjustmentProductModalLabel">Chọn sản phẩm thêm vào đơn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <input type="text" id="warehouse-adjustment-product-search" class="form-control form-control-sm" placeholder="Tìm theo tên sản phẩm hoặc SKU">
                    </div>
                    <div class="col-md-4">
                        <select id="warehouse-adjustment-product-sort" class="form-select form-select-sm">
                            <option value="id|desc">Mới nhất</option>
                            <option value="id|asc">Cũ nhất</option>
                            <option value="sku|asc">SKU A → Z</option>
                            <option value="sku|desc">SKU Z → A</option>
                            <option value="stock|desc">Tồn kho giảm dần</option>
                            <option value="stock|asc">Tồn kho tăng dần</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="warehouse-adjustment-product-per-page" class="form-select form-select-sm">
                            <option value="5">5 / trang</option>
                            <option value="10" selected>10 / trang</option>
                            <option value="25">25 / trang</option>
                            <option value="50">50 / trang</option>
                        </select>
                    </div>
                </div>
                <div id="warehouse-adjustment-product-results" class="wh-adjustment-picker-results">
                    <div class="text-center text-muted py-4">Đang tải danh sách sản phẩm...</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function formatCompactDecimal(value, decimals = 2) {
            const num = Number(value);
            if (Number.isNaN(num)) return '';
            return num.toLocaleString('vi-VN', {
                minimumFractionDigits: 0,
                maximumFractionDigits: decimals,
            });
        }

        // ── Stock Drawer Pin/Unpin ────────────────────────────────
        const stockDrawerEl  = document.getElementById('stockDrawer');
        const stockPinBtn    = document.getElementById('stockDrawerPinBtn');
        const PINNED_KEY     = 'wh_stock_drawer_pinned';
        let isPinned         = localStorage.getItem(PINNED_KEY) === '1';

        if (stockDrawerEl && typeof bootstrap !== 'undefined') {
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(stockDrawerEl, {
                backdrop: false,
                scroll: true,
            });

            function applyPinnedState(open) {
                if (isPinned) {
                    document.body.classList.add('stock-drawer-pinned');
                    if (open !== false) bsOffcanvas.show();
                    if (stockPinBtn) {
                        stockPinBtn.innerHTML = '<i class="bi bi-pin-fill me-1"></i>Bỏ neo';
                        stockPinBtn.classList.add('pinned');
                    }
                } else {
                    document.body.classList.remove('stock-drawer-pinned');
                    if (stockPinBtn) {
                        stockPinBtn.innerHTML = '<i class="bi bi-pin-angle me-1"></i>Neo';
                        stockPinBtn.classList.remove('pinned');
                    }
                }
            }

            // Prevent closing via Esc when pinned
            stockDrawerEl.addEventListener('hide.bs.offcanvas', function (e) {
                if (isPinned) e.preventDefault();
            });

            stockPinBtn?.addEventListener('click', function () {
                isPinned = !isPinned;
                localStorage.setItem(PINNED_KEY, isPinned ? '1' : '0');
                if (!isPinned) {
                    applyPinnedState();
                    bsOffcanvas.hide();
                } else {
                    applyPinnedState();
                }
            });

            // Auto-open if was pinned
            if (isPinned) applyPinnedState(true);
        }
        // ─────────────────────────────────────────────────────────

        const warehouseProductModal = document.getElementById('warehouseAdjustmentProductModal');
        const warehouseProductResults = document.getElementById('warehouse-adjustment-product-results');
        const warehouseProductSearch = document.getElementById('warehouse-adjustment-product-search');
        const warehouseProductSort = document.getElementById('warehouse-adjustment-product-sort');
        const warehouseProductPerPage = document.getElementById('warehouse-adjustment-product-per-page');
        const warehouseProductSearchUrl = '{{ route('orders.ajax_variant_search') }}';
        let currentAdjustmentOrderId = null;
        let warehouseProductSearchTimer = null;

        function getAdjustmentContainer(orderId) {
            return document.getElementById(`new-adjustment-items-${orderId}`);
        }

        function getExcludedVariantIds(orderId) {
            const pendingContainer = getAdjustmentContainer(orderId);
            const pendingVariantIds = pendingContainer
                ? Array.from(pendingContainer.querySelectorAll('input[name$="[product_variant_id]"]')).map((input) => input.value)
                : [];

            return Array.from(new Set(pendingVariantIds)).filter(Boolean);
        }

        function loadWarehouseProducts(page = 1) {
            if (!warehouseProductResults) {
                return;
            }

            const [sortBy, sortDir] = (warehouseProductSort?.value || 'id|desc').split('|');
            const params = new URLSearchParams({
                search: warehouseProductSearch?.value?.trim() || '',
                page: String(page),
                per_page: warehouseProductPerPage?.value || '10',
                sort_by: sortBy || 'id',
                sort_dir: sortDir || 'desc',
            });

            getExcludedVariantIds(currentAdjustmentOrderId).forEach((id) => {
                params.append('exclude_ids[]', id);
            });

            warehouseProductResults.innerHTML = '<div class="text-center text-muted py-4">Đang tải danh sách sản phẩm...</div>';

            fetch(`${warehouseProductSearchUrl}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
                .then((response) => response.json())
                .then((data) => {
                    warehouseProductResults.innerHTML = data.html || '<div class="text-center text-muted py-4">Không có dữ liệu.</div>';
                    const embeddedPerPage = warehouseProductResults.querySelector('#per-page-select');
                    if (embeddedPerPage && warehouseProductPerPage) {
                        embeddedPerPage.value = warehouseProductPerPage.value;
                    }
                })
                .catch(() => {
                    warehouseProductResults.innerHTML = '<div class="text-center text-danger py-4">Không tải được danh sách sản phẩm.</div>';
                });
        }

        function appendAdjustmentProduct(orderId, variantData) {
            const container = getAdjustmentContainer(orderId);
            if (!container) {
                return;
            }

            const existingRow = container.querySelector(`[data-variant-id="${variantData.id}"]`);
            if (existingRow) {
                const quantityInput = existingRow.querySelector('.js-adjustment-new-item-qty');
                quantityInput.value = String((parseInt(quantityInput.value || '0', 10) || 0) + 1);
                return;
            }

            const nextIndex = parseInt(container.getAttribute('data-next-index') || '0', 10) || 0;
            container.setAttribute('data-next-index', String(nextIndex + 1));
            const item = document.createElement('div');
            item.className = 'wh-adjustment-pending-item';
            item.setAttribute('data-variant-id', String(variantData.id));
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-end gap-2 flex-wrap">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${variantData.name}</div>
                        <div class="small text-muted">SKU: ${variantData.sku || '---'} | Giá: ${variantData.price}</div>
                    </div>
                    <div class="d-flex align-items-end gap-2">
                        <div style="min-width: 140px;">
                        <label class="form-label small mb-1">Số lượng thêm</label>
                        <input type="hidden" name="new_items[${nextIndex}][product_variant_id]" value="${variantData.id}">
                        <input type="number" min="1" step="1" name="new_items[${nextIndex}][quantity]" class="form-control form-control-sm js-adjustment-new-item-qty" value="1">
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm js-remove-adjustment-item mb-1">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            container.appendChild(item);
        }

        document.querySelectorAll('.js-open-adjustment-product-picker').forEach((button) => {
            button.addEventListener('click', function () {
                currentAdjustmentOrderId = this.getAttribute('data-order-id');
                loadWarehouseProducts(1);
            });
        });

        warehouseProductSearch?.addEventListener('input', function () {
            clearTimeout(warehouseProductSearchTimer);
            warehouseProductSearchTimer = setTimeout(() => loadWarehouseProducts(1), 300);
        });

        warehouseProductSort?.addEventListener('change', function () {
            loadWarehouseProducts(1);
        });

        warehouseProductPerPage?.addEventListener('change', function () {
            loadWarehouseProducts(1);
        });

        warehouseProductResults?.addEventListener('click', function (event) {
            const addButton = event.target.closest('.add-variant-to-cart');
            if (addButton && currentAdjustmentOrderId) {
                event.preventDefault();
                appendAdjustmentProduct(currentAdjustmentOrderId, {
                    id: addButton.getAttribute('data-variant-id'),
                    name: addButton.getAttribute('data-variant-name') || 'Sản phẩm',
                    sku: addButton.getAttribute('data-variant-sku') || '',
                    price: addButton.getAttribute('data-variant-price') || '0',
                });
                loadWarehouseProducts(1);
                return;
            }

            const pageLink = event.target.closest('.pagination a');
            if (pageLink) {
                event.preventDefault();
                const url = new URL(pageLink.getAttribute('href'), window.location.origin);
                loadWarehouseProducts(parseInt(url.searchParams.get('page') || '1', 10));
            }
        });

        warehouseProductResults?.addEventListener('change', function (event) {
            if (event.target.id === 'per-page-select' && warehouseProductPerPage) {
                warehouseProductPerPage.value = event.target.value;
                loadWarehouseProducts(1);
            }
        });

        document.addEventListener('click', function (event) {
            const markRemoveButton = event.target.closest('.js-mark-adjustment-item-remove');
            if (markRemoveButton) {
                const targetName = markRemoveButton.getAttribute('data-target-name');
                if (targetName) {
                    const quantityInput = document.querySelector(`input[name="${CSS.escape(targetName)}"]`);
                    if (quantityInput) {
                        quantityInput.value = '0';
                        quantityInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
                return;
            }

            const removeButton = event.target.closest('.js-remove-adjustment-item');
            if (!removeButton) {
                return;
            }

            const item = removeButton.closest('.wh-adjustment-pending-item');
            item?.remove();
        });

        async function submitLogisticsForm(form) {
            const submitBtn = form.querySelector('.js-logistics-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                let payload = {};
                try {
                    payload = await response.json();
                } catch (e) {
                    payload = {};
                }

                if (!response.ok || payload.ok === false) {
                    throw new Error(payload.message || 'Lưu thông tin kho thất bại.');
                }

                if (typeof showToast === 'function') {
                    showToast(payload.message || 'Đã lưu thông tin kho.', 'success');
                }

                if (form.classList.contains('js-logistics-item-form')) {
                    const row = form.closest('.wh-item-table-row');
                    if (row) {
                        const unitPrice = parseFloat(row.dataset.unitPrice || '0');
                        const weightUnit = row.dataset.weightUnit || 'kg';
                        const weightInput = form.querySelector('input[name="item_actual_weight"]');
                        const actualWeight = parseFloat(weightInput?.value || '0');
                        const amountCell = row.querySelector('.js-item-total-amount strong');

                        if (amountCell) {
                            if (!Number.isNaN(actualWeight)) {
                                const lineTotal = Math.round(unitPrice * actualWeight);
                                amountCell.textContent = new Intl.NumberFormat('vi-VN').format(lineTotal) + 'đ';
                            } else {
                                amountCell.textContent = '---';
                            }
                        }

                        const readonlyKg = row.querySelector('.js-item-readonly-kg');
                        if (readonlyKg && !Number.isNaN(actualWeight)) {
                            readonlyKg.textContent = actualWeight > 0 ? (formatCompactDecimal(actualWeight) + ' kg') : '---';
                        }

                        if (submitBtn && amountCell && amountCell.textContent.trim() !== '---') {
                            submitBtn.classList.remove('wh-warning-action-btn');
                            submitBtn.classList.add('btn-secondary');
                        }
                    }
                }

                if (form.classList.contains('js-logistics-fee-form') && submitBtn) {
                    submitBtn.classList.remove('wh-warning-action-btn');
                    submitBtn.classList.add('btn-secondary');
                }
            } catch (error) {
                if (typeof showToast === 'function') {
                    showToast(error.message || 'Có lỗi xảy ra khi lưu.', 'error');
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            }
        }

        function validateWeightInput(input) {
            const qty = parseFloat(input.dataset.qty || '0');
            const size = parseFloat(input.dataset.size || '0');
            const val = parseFloat(input.value);
            const errEl = input.closest('li')?.querySelector('.js-weight-error');
            const submitBtn = input.closest('form')?.querySelector('.js-logistics-submit-btn');
            function setInvalid(msg) {
                if (errEl) { errEl.textContent = msg; errEl.style.display = ''; }
                if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('btn-secondary'); submitBtn.classList.remove('wh-warning-action-btn'); }
                return false;
            }
            function setValid() {
                if (errEl) errEl.style.display = 'none';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('btn-secondary'); submitBtn.classList.add('wh-warning-action-btn'); }
                return true;
            }
            if (!errEl) return true;
            if (size <= 0 || isNaN(qty) || qty <= 0) return setValid();
            if (isNaN(val)) return setInvalid('Nhập số kg hợp lệ.');
            const min = qty * (size - 0.25);
            const max = qty * (size + 0.25);
            if (val < min || val > max) {
                return setInvalid(`Kg phải trong khoảng ${formatCompactDecimal(min)} – ${formatCompactDecimal(max)} (SL ${formatCompactDecimal(qty)} × size ${formatCompactDecimal(size)} ± 0,25)`);
            }
            return setValid();
        }

        document.querySelectorAll('.js-weight-input').forEach(function (input) {
            input.addEventListener('input', function () {
                const submitBtn = input.closest('form')?.querySelector('.js-logistics-submit-btn');
                if (submitBtn) {
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.classList.add('wh-warning-action-btn');
                }
                validateWeightInput(input);
            });
        });

        document.querySelectorAll('.js-logistics-fee-form input').forEach(function (input) {
            input.addEventListener('input', function () {
                const submitBtn = input.closest('form')?.querySelector('.js-logistics-submit-btn');
                if (submitBtn) {
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.classList.add('wh-warning-action-btn');
                }
            });
            input.addEventListener('change', function () {
                const submitBtn = input.closest('form')?.querySelector('.js-logistics-submit-btn');
                if (submitBtn) {
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.classList.add('wh-warning-action-btn');
                }
            });
        });

        document.querySelectorAll('.js-logistics-item-form, .js-logistics-fee-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const weightInput = form.querySelector('.js-weight-input');
                if (weightInput && !validateWeightInput(weightInput)) return;
                submitLogisticsForm(form);
            });
        });

        document.querySelectorAll('.js-start-packing-form').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const submitBtn = form.querySelector('.js-start-packing-btn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    });

                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (e) {
                        payload = {};
                    }

                    if (!response.ok || payload.ok === false) {
                        throw new Error(payload.message || 'Không thể chuyển đơn sang trạng thái đóng gói.');
                    }

                    const card = form.closest('.js-order-card');
                    if (card) {
                        const statusEl = card.querySelector('.js-order-status');
                        if (statusEl && payload.order) {
                            statusEl.className = 'badge js-order-status ' + (payload.order.status_class || 'bg-warning text-dark');
                            statusEl.textContent = payload.order.status_label || 'Đang đóng gói';
                        }

                        const orderIndexEl = card.querySelector('.wh-order-index');
                        if (orderIndexEl) {
                            orderIndexEl.classList.remove('is-packed', 'is-unpacked');
                            orderIndexEl.classList.add('is-packing');
                        }

                        const navPill = document.querySelector(`.wh-order-nav-pill[data-order-id="${card.dataset.orderId || ''}"]`);
                        if (navPill) {
                            navPill.classList.remove('is-packed', 'is-unpacked');
                            navPill.classList.add('is-packing');
                        }

                        card.querySelectorAll('.js-ready-only').forEach(function (el) {
                            el.classList.add('d-none');
                        });
                        card.querySelectorAll('.js-packing-only').forEach(function (el) {
                            el.classList.remove('d-none');
                        });
                        card.querySelector('.js-start-packing-form')?.classList.add('d-none');
                        card.querySelector('.js-undo-packing-form')?.classList.remove('d-none');
                        card.querySelector('.js-complete-packing-form')?.classList.remove('d-none');
                    }

                    if (typeof showToast === 'function') {
                        showToast(payload.message || 'Đã bắt đầu đóng gói đơn hàng.', 'success');
                    }
                } catch (error) {
                    if (typeof showToast === 'function') {
                        showToast(error.message || 'Có lỗi xảy ra khi đóng hàng.', 'error');
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                }
            });
        });

        document.querySelectorAll('.js-undo-packing-form').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const submitBtn = form.querySelector('.js-undo-packing-btn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    });

                    const payload = await response.json();
                    if (!response.ok || payload.ok === false) {
                        throw new Error(payload.message || 'Không thể hoàn tác nhận đơn.');
                    }

                    const card = form.closest('.js-order-card');
                    if (card) {
                        const statusEl = card.querySelector('.js-order-status');
                        if (statusEl && payload.order) {
                            statusEl.className = 'badge js-order-status ' + (payload.order.status_class || 'bg-secondary');
                            statusEl.textContent = payload.order.status_label || 'Chờ đóng gói';
                        }

                        const orderIndexEl = card.querySelector('.wh-order-index');
                        if (orderIndexEl) {
                            orderIndexEl.classList.remove('is-packed', 'is-packing');
                            orderIndexEl.classList.add('is-unpacked');
                        }

                        const navPill = document.querySelector(`.wh-order-nav-pill[data-order-id="${card.dataset.orderId || ''}"]`);
                        if (navPill) {
                            navPill.classList.remove('is-packed', 'is-packing');
                            navPill.classList.add('is-unpacked');
                        }

                        card.querySelectorAll('.js-ready-only').forEach(function (el) {
                            el.classList.remove('d-none');
                        });
                        card.querySelectorAll('.js-packing-only').forEach(function (el) {
                            el.classList.add('d-none');
                        });
                        card.querySelector('.js-start-packing-form')?.classList.remove('d-none');
                        card.querySelector('.js-undo-packing-form')?.classList.add('d-none');
                        card.querySelector('.js-complete-packing-form')?.classList.add('d-none');
                    }

                    if (typeof showToast === 'function') {
                        showToast(payload.message || 'Đã hoàn tác nhận đơn.', 'success');
                    }
                } catch (error) {
                    if (typeof showToast === 'function') {
                        showToast(error.message || 'Có lỗi xảy ra khi hoàn tác nhận đơn.', 'error');
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                }
            });
        });
    });
</script>
@endpush
