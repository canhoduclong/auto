@extends('layouts.warehouse')

@section('title', 'Đơn hàng cần xử lý')
@section('subtitle', 'Xem và xử lý đơn theo ngày')

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
    }
    .wh-order-index {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 2;
        font-size: .72rem;
        font-weight: 700;
        border-radius: 999px;
        padding: 3px 8px;
        background: #0f172a;
        color: #fff;
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
        padding: 10px 0;
        border-top: 1px dashed #e2e8f0;
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
        grid-template-columns: 48px minmax(50px, 1fr) 42px 52px 45px 70px 61px 76px;
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
</style>
@endpush

@section('content')
@php
    $formatKg = static function (float|int|string $value): string {
        $num = (float) $value;
        $str = rtrim(rtrim(number_format($num, 3, '.', ''), '0'), '.');
        return $str . 'kg';
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
            <form method="GET" action="{{ route('warehouse.orders') }}" class="row g-2 align-items-end">
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
                    <a href="{{ route('warehouse.orders') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Hôm nay
                    </a>
                    <div class="mx-3">
                         
                        <div class="wh-quick-wrap">
                            @foreach($quickDates as $quickDate)
                                @if($quickDate['available'])
                                    <a href="{{ route('warehouse.orders', array_filter(['date' => $quickDate['date'], 'status' => $status ?: null])) }}"
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
            <a href="{{ route('warehouse.dashboard') }}" class="btn btn-outline-secondary btn-sm">
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
    <div class="row g-3">
        @foreach($orders as $order)
            @php
                $isTodaySelected = \Illuminate\Support\Carbon::parse($selectedDate ?? now()->toDateString())->isToday();
                $canProcessThisOrder = $isTodaySelected && $order->created_at->isToday();
                $meta = $statusMeta[$order->status] ?? ['label' => $order->status, 'class' => 'bg-secondary'];
                $isReadyToPack = in_array($order->status, ['approved', 'ready_to_pack'], true);
                $isPacking = $order->status === 'packing';
                $isPackedReadonly = in_array($order->status, ['packed', 'packed_waiting_pickup', 'delivering', 'delivered', 'completed'], true);
                $canAdminReopenPacking = auth()->user()?->hasRole('admin') && in_array($order->status, ['packed', 'packed_waiting_pickup'], true);
                $packingHistory = $order->histories
                    ?->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
                    ->sortByDesc('id')
                    ->first();
                $sourceWarehouseName = $order->warehouse?->name ?: $packingHistory?->user?->warehouse?->name;
                $packedByName = $packingHistory?->user?->name;
                $packedAt = $packingHistory?->created_at?->format('d/m/Y H:i');
                $stockGuard = $order->stock_guard ?? [];
                $hasStockShortage = (bool) ($stockGuard['has_shortage'] ?? false);
                $canStartPacking = (bool) ($stockGuard['can_start_packing'] ?? true);
                $stockShortages = collect($stockGuard['shortages'] ?? []);
            @endphp
            <div class="col-12 col-lg-8 col-xxl-6">
                <div class="card wh-order-card js-order-card" data-order-id="{{ $order->id }}">
                     
                    <span class="wh-order-index">#{{ $loop->iteration }}</span>
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $order->code }}</div>
                            <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <span class="badge {{ $meta['class'] }} js-order-status">{{ $meta['label'] }}</span>
                    </div>
                    

                    <div class="card-body">
                        <div class="mb-2">
                            <div class="fw-semibold">{{ $order->customer?->name ?? '—' }}</div>
                            @if($order->customer?->phone)
                                <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $order->customer->phone }}</div>
                            @endif
                        </div>

                        <div class="wh-section">
                            <div class="wh-logistics-title">Thông tin giao hàng</div>
                            <div class="small text-muted mb-1">
                                <i class="bi bi-geo-alt me-1"></i>
                                {{ $order->customer?->address ?: 'Chưa có địa chỉ' }}
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-clock me-1"></i>
                                Giờ giao: {{ $order->delivery_time ?: ($order->customer?->delivery_time ?: 'Chưa cập nhật') }}
                            </div>
                            @if($isPackedReadonly)
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-box-seam me-1"></i>
                                    Từ kho: {{ $sourceWarehouseName ?: 'Chưa xác định' }}
                                </div>
                            @endif
                        </div>

                        <div class="wh-section pb-0">
                            <div class="wh-logistics-title">Danh sách sản phẩm cần đóng & cập nhật kho</div>
                            @if($hasStockShortage && $isReadyToPack)
                                <div class="wh-stock-alert" title="Không thể bắt đầu đóng hàng khi tồn kho chưa đáp ứng.">
                                    <details>
                                        <summary>Không đủ tồn kho để đóng hàng</summary>
                                        <ul>
                                            @foreach($stockShortages as $shortage)
                                                <li>
                                                    {{ $shortage['variant_name'] ?? 'Sản phẩm' }}:
                                                    cần {{ number_format((int) ($shortage['required_qty'] ?? 0)) }},
                                                    khả dụng {{ number_format((int) ($shortage['available_qty'] ?? 0)) }}
                                                    @if(($shortage['reason'] ?? '') === 'blocked_by_prior_order')
                                                        (bị ảnh hưởng bởi đơn ưu tiên trước)
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="mt-1">
                                            <a href="{{ route('warehouse.stock-in') }}">Bạn cần Nhập kho để thực hiện công việc tiếp</a>
                                        </div>
                                    </details>
                                </div>
                            @endif
                            <div class="wh-item-table-wrap">
                                <div class="wh-item-table-head">
                                    <div>Ảnh</div>
                                    <div>Sản phẩm</div>
                                    <div class="text-center">Size</div>
                                    <div class="text-center">SL</div>                                    
                                    <div class="text-center">Tổng</div>
                                    <div class="text-center">Khối lượng</div>
                                    <div class="text-center">Đơn giá</div>
                                    <div class="text-end">Thành tiền</div>
                                    
                                </div>
                                <ul class="wh-item-list">
                                    @foreach($order->items as $item)
                                        @php

                                            $variant = $item->variant;
                                            $orderedQty = (int) $item->quantity;
                                            $unitPrice = (float) ($item->price ?? 0);
                                            $unitLabel = $variant?->product?->unit_label ?? '--'; 
                                            $pricedByKg = (bool) $item->effective_priced_by_kg;
                                            $weightUnitLabel = $pricedByKg ? 'Kg' : $unitLabel;
                                            $itemActualWeight = is_null($item->actual_weight) ? null : (float) $item->actual_weight;
                                            $lineTotal = $pricedByKg
                                                ? (!is_null($itemActualWeight) ? ($itemActualWeight * $unitPrice) : null)
                                                : ($orderedQty * $unitPrice);
                                            $variantSize = $variant?->size;
                                            $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                                ? rtrim(rtrim(number_format((float) $variantSize, 2, '.', ''), '0'), '.')
                                                : '-';
                                            if ($pricedByKg) {
                                                $displayActualWeight = (!is_null($itemActualWeight) && (float) $itemActualWeight > 0)
                                                    ? $formatKg((float) $itemActualWeight)
                                                    : '---';
                                            } else {
                                                $nonKgVal = (!is_null($itemActualWeight) && (float) $itemActualWeight > 0)
                                                    ? (float) $itemActualWeight
                                                    : round((float) $item->effective_unit_weight * $orderedQty, 3);
                                                $displayActualWeight = rtrim(rtrim(number_format($nonKgVal, 3, '.', ''), '0'), '.') . ' ' . $unitLabel;
                                            }
                                            $imagePath = $variant?->avatar?->media?->file_path
                                                ?? $item->product?->avatar?->media?->file_path
                                                ?? null;
                                        @endphp
                                        <li class="wh-item-row">
                                            <div class="wh-item-table-row" data-unit-price="{{ number_format($unitPrice, 2, '.', '') }}" data-weight-unit="{{ $weightUnitLabel }}">
                                                <div>
                                                    @if($imagePath)
                                                        <img class="wh-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}">
                                                    @else
                                                        <span class="wh-item-thumb-placeholder">
                                                            <i class="bi bi-image"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="wh-item-name">
                                                    {{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}
                                                    @if($variant?->sku)
                                                        <span class="text-muted small">({{ $variant->sku }})</span>
                                                    @endif
                                                </div>
                                                <div class="wh-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                                <div class="wh-item-cell"><strong>{{ number_format($orderedQty) }}</strong></div>
                                                <div class="wh-item-cell"><strong>{{ $item->display_total_label }}</strong></div>
                                                
                                               
                                                @if(!$isPackedReadonly && $canProcessThisOrder)
                                                    @php
                                                        $defaultComputedWeight = round((float) $item->effective_unit_weight * $orderedQty, 3);
                                                        $itemWeightDefault = is_null($item->actual_weight)
                                                            ? ($defaultComputedWeight > 0 ? number_format($defaultComputedWeight, 3, '.', '') : '')
                                                            : number_format((float) $item->actual_weight, 3, '.', '');
                                                    @endphp
                                                    @if($pricedByKg)
                                                        <div class="wh-item-action js-packing-only {{ $isPacking ? '' : 'd-none' }}">
                                                            <form action="{{ route('warehouse.orders.logistics', $order) }}" method="POST" class="js-logistics-item-form wh-compact-form justify-content-end">
                                                                @csrf
                                                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                                <input type="number" name="item_actual_weight" class="form-control form-control-sm actual_weight js-weight-input"
                                                                    value="{{ $itemWeightDefault }}"
                                                                    placeholder="{{ $weightUnitLabel }}"
                                                                    min="0" step="0.001" required
                                                                    inputmode="decimal"
                                                                    data-qty="{{ $orderedQty }}"
                                                                    data-size="{{ is_numeric($variantSize) && (float)$variantSize > 0 ? (float)$variantSize : 0 }}">
                                                                <button class="btn btn-outline-primary btn-sm js-logistics-submit-btn" type="submit">Lưu</button>
                                                            </form>
                                                        </div>
                                                        <div class="wh-readonly-item js-ready-only {{ $isPacking ? 'd-none' : '' }}">{{ $displayActualWeight }}</div>
                                                    @else
                                                        <div class="wh-readonly-item">{{ $displayActualWeight }}</div>
                                                    @endif
                                                @else
                                                    <div class="wh-readonly-item js-item-readonly-kg">
                                                        {{ $displayActualWeight }}
                                                    </div>
                                                @endif
                                           
                                                <div class="wh-item-cell">{{ number_format($unitPrice) }}đ</div>
                                                <div class="wh-item-cell js-item-total-amount">
                                                    <strong>{{ !is_null($lineTotal) ? number_format($lineTotal) . 'đ' : ($pricedByKg ? '---' : number_format($orderedQty * $unitPrice) . 'đ') }}</strong>
                                                </div>
                                             </div>
                                             <div class="js-weight-error text-danger text-center px-1" style="font-size:.72rem;display:none;"></div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            @if(!$isPackedReadonly && $canProcessThisOrder)
                                <div class="small text-muted mt-2 js-ready-only {{ $isReadyToPack ? '' : 'd-none' }}">
                                    Bấm <strong>Đóng hàng</strong> để bật nhập kg thực tế theo sản phẩm và thông tin phí.
                                </div>
                            @endif

                            @if(!$isPackedReadonly && $canProcessThisOrder)
                                <form action="{{ route('warehouse.orders.logistics', $order) }}" method="POST" class="mt-2 js-logistics-fee-form js-packing-only {{ $isPacking ? '' : 'd-none' }}">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="wh-meta-label mb-1">Phí ship</label>
                                            <input type="number" name="shipping_fee" class="form-control form-control-sm"
                                                   value="{{ number_format((float) ($order->shipping_fee ?? 0), 2, '.', '') }}"
                                                   min="0" step="0.01">
                                        </div>
                                        <div class="col-6">
                                            <label class="wh-meta-label mb-1">Giá thùng xốp</label>
                                            <input type="number" name="foam_box_price" class="form-control form-control-sm"
                                                   value="{{ number_format((float) ($order->foam_box_price ?? 0), 2, '.', '') }}"
                                                   min="0" step="0.01">
                                        </div>
                                        <div class="col-6 d-flex align-items-end">
                                            <div class="form-check mb-1">
                                                <input type="hidden" name="charge_shipping_fee" value="0">
                                                <input class="form-check-input" type="checkbox" name="charge_shipping_fee" value="1"
                                                       id="charge_shipping_fee_{{ $order->id }}"
                                                       {{ ($order->charge_shipping_fee ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="charge_shipping_fee_{{ $order->id }}">
                                                    Tính ship
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6 d-flex align-items-end">
                                            <div class="form-check mb-1">
                                                <input type="hidden" name="charge_foam_box_fee" value="0">
                                                <input class="form-check-input" type="checkbox" name="charge_foam_box_fee" value="1"
                                                       id="charge_foam_box_fee_{{ $order->id }}"
                                                       {{ ($order->charge_foam_box_fee ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="charge_foam_box_fee_{{ $order->id }}">
                                                    Thêm thùng xốp
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12 d-grid">
                                            <button class="btn btn-outline-primary btn-sm js-logistics-submit-btn" type="submit">
                                                <i class="bi bi-save2 me-1"></i>Lưu phí giao hàng / thùng xốp
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top py-2">
                        @if($isPackedReadonly)
                            <div class="wh-section border-top-0 pt-0 mb-2">
                                <div class="wh-logistics-title">Thông tin đơn hàng hoàn chỉnh</div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="wh-meta-label">Kg thực tế</div>
                                        <div class="wh-meta-value text-primary">{{ $order->actual_weight !== null ? $formatKg((float) $order->actual_weight) : '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Phí ship</div>
                                        <div class="wh-meta-value text-info">{{ $order->shipping_fee !== null ? number_format((float) $order->shipping_fee) . 'đ' : '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Tính phí ship</div>
                                        <div class="wh-meta-value">{{ ($order->charge_shipping_fee ?? true) ? 'Có' : 'Không' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Thùng xốp</div>
                                        <div class="wh-meta-value">{{ ($order->charge_foam_box_fee ?? false) ? 'Có' : 'Không' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Giá thùng xốp</div>
                                        <div class="wh-meta-value text-info">{{ $order->foam_box_price !== null ? number_format((float) $order->foam_box_price) . 'đ' : '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Trạng thái</div>
                                        <div class="wh-meta-value">Đã khóa chỉnh sửa</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Từ kho</div>
                                        <div class="wh-meta-value">{{ $sourceWarehouseName ?: 'Chưa xác định' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="wh-meta-label">Nhân viên kho</div>
                                        <div class="wh-meta-value">{{ $packedByName ?: 'Chưa xác định' }}</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="wh-meta-label">Thời điểm đóng gói</div>
                                        <div class="wh-meta-value">{{ $packedAt ?: 'Chưa có dữ liệu' }}</div>
                                    </div>
                                    @if($canAdminReopenPacking)
                                        <div class="col-12">
                                            <form action="{{ route('warehouse.orders.reopen-packing', $order) }}" method="POST" class="d-grid">
                                                @csrf
                                                <button class="btn btn-outline-warning btn-sm" type="submit">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Admin bỏ khóa chỉnh sửa
                                                </button>
                                            </form>
                                            <div class="small text-muted mt-1">
                                                Đưa đơn quay lại bước đang đóng gói để warehouse chỉnh sửa lại dữ liệu.
                                            </div>
                                        </div>
                                    @endif
                                    
                                </div>
                            </div>
                        @endif

                        @if($canProcessThisOrder && ($isReadyToPack || $isPacking))
                            @if($isReadyToPack)
                                @if($canStartPacking)
                                    <form action="{{ route('warehouse.orders.start-packing', $order) }}" method="POST" class="d-grid js-start-packing-form">
                                        @csrf
                                        <button class="btn btn-primary btn-sm js-start-packing-btn" type="submit">
                                            <i class="bi bi-box2 me-1"></i>Đóng hàng
                                        </button>
                                    </form>
                                @else
                                    <div class="d-grid gap-1">
                                        <button class="btn btn-danger btn-sm" type="button" disabled>
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Không đủ hàng – Chờ nhập kho
                                        </button>
                                        @if($stockShortages->isNotEmpty())
                                            <div class="wh-stock-alert mt-1">
                                                <details>
                                                    <summary>Chi tiết thiếu hàng ({{ $stockShortages->count() }} sản phẩm)</summary>
                                                    <ul>
                                                        @foreach($stockShortages as $shortage)
                                                            <li>
                                                                <strong>{{ $shortage['variant_name'] ?? 'Sản phẩm' }}</strong>:
                                                                cần {{ number_format((float)($shortage['required_qty'] ?? 0), 0) }},
                                                                còn {{ number_format((float)($shortage['available_qty'] ?? 0), 0) }}
                                                                @php $shortQty = (float)($shortage['short_qty'] ?? 0); @endphp
                                                                @if($shortQty > 0)
                                                                    <span class="text-danger">(thiếu {{ number_format($shortQty, 0) }})</span>
                                                                @endif
                                                                @if(($shortage['reason'] ?? '') === 'blocked_by_prior_order')
                                                                    <span class="text-warning">– bị chặn bởi đơn ưu tiên trước</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                    <div class="mt-1">
                                                        <a href="{{ route('warehouse.stock-in') }}">Nhập kho để tiếp tục</a>
                                                    </div>
                                                </details>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endif

                            <form action="{{ route('warehouse.orders.complete-packing', $order) }}" method="POST" class="d-grid js-complete-packing-form {{ $isPacking ? '' : 'd-none' }}">
                                @csrf
                                <button class="btn btn-success btn-sm">
                                    <i class="bi bi-check2-all me-1"></i>Hoàn thành đóng gói
                                </button>
                            </form>
                        @else
                            @php
                                $isNotReceived = in_array($order->status, [
                                    'approved',
                                    'ready_to_pack',
                                    'pending',
                                    'pending_leader_approval',
                                    'pending_manager_approval',
                                    'pending_warehouse_approval',
                                ], true);
                            @endphp
                            <span class="badge {{ $isNotReceived ? 'bg-secondary' : 'bg-success' }}">
                                {{ $isNotReceived ? 'Chưa tiếp nhận' : 'Đã xử lý' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
                            readonlyKg.textContent = actualWeight > 0 ? (actualWeight.toFixed(3).replace(/\.?0+$/, '') + 'kg') : '---';
                        }
                    }
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
                if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('btn-secondary'); submitBtn.classList.remove('btn-outline-primary'); }
                return false;
            }
            function setValid() {
                if (errEl) errEl.style.display = 'none';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('btn-secondary'); submitBtn.classList.add('btn-outline-primary'); }
                return true;
            }
            if (!errEl) return true;
            if (size <= 0 || isNaN(qty) || qty <= 0) return setValid();
            if (isNaN(val)) return setInvalid('Nhập số kg hợp lệ.');
            const min = qty * (size - 0.25);
            const max = qty * (size + 0.25);
            if (val < min || val > max) {
                return setInvalid(`Kg phải trong khoảng ${min.toFixed(3)} – ${max.toFixed(3)} (SL ${qty} × size ${size} ± 0.25)`);
            }
            return setValid();
        }

        document.querySelectorAll('.js-weight-input').forEach(function (input) {
            input.addEventListener('input', function () { validateWeightInput(input); });
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

                        card.querySelectorAll('.js-ready-only').forEach(function (el) {
                            el.classList.add('d-none');
                        });
                        card.querySelectorAll('.js-packing-only').forEach(function (el) {
                            el.classList.remove('d-none');
                        });
                        card.querySelector('.js-start-packing-form')?.classList.add('d-none');
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
    });
</script>
@endpush
