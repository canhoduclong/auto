@extends('layouts.warehouse')

@section('title', 'Tồn Kho')

@push('styles')
<style>
/* ── Stat summary row ── */
.inv-stat {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.inv-stat.blue  { border-left: 3px solid #0ea5e9; }
.inv-stat.amber { border-left: 3px solid #f59e0b; }
.inv-stat.red   { border-left: 3px solid #ef4444; }
.inv-stat__icon {
    width: 38px; height: 38px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; flex-shrink: 0;
}
.blue  .inv-stat__icon { background: #e0f2fe; color: #0284c7; }
.amber .inv-stat__icon { background: #fef3c7; color: #b45309; }
.red   .inv-stat__icon { background: #fee2e2; color: #dc2626; }
.inv-stat__num  { font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1; }
.inv-stat__lbl  { font-size: .72rem; color: #64748b; text-transform: uppercase; letter-spacing: .03em; margin-top: 3px; }
.inv-stat__sub  { font-size: .7rem; color: #94a3b8; margin-top: 2px; }

.filter-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; }
.daily-note { border: 1px solid #dbeafe; background: #f8fbff; border-radius: 8px; color: #1e3a8a; font-size: .78rem; padding: 7px 10px; margin-bottom: 10px; }

/* ── Unified inventory list ── */
.inv-list-wrap {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

/* Sticky column header */
.inv-list-head {
    display: grid;
    grid-template-columns: 260px 1fr 110px 110px 110px;
    padding: 8px 16px;
    background: #f1f5f9;
    border-bottom: 2px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 2;
}
.inv-list-head-col {
    font-size: .63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #64748b;
    text-align: center;
}
.inv-list-head-col:first-child,
.inv-list-head-col:nth-child(2) { text-align: left; }

/* Product group header */
.inv-group-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-top: 2px solid #e2e8f0;
    cursor: default;
}
.inv-group-header:first-of-type { border-top: 0; }
.inv-group-thumb {
    width: 36px; height: 36px;
    border-radius: 8px;
    background: #e2e8f0;
    color: #94a3b8;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; flex-shrink: 0;
    font-size: 1rem;
}
.inv-group-thumb img { width: 100%; height: 100%; object-fit: cover; }
.inv-group-name {
    font-size: .85rem;
    font-weight: 700;
    color: #0f172a;
    flex: 1;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.inv-group-summary { display: flex; gap: 6px; flex-shrink: 0; }
.inv-gsumchip {
    font-size: .68rem; font-weight: 700;
    border-radius: 999px;
    padding: 2px 8px;
}
.inv-gsumchip.stock  { background: #e0f2fe; color: #075985; }
.inv-gsumchip.booked { background: #fef3c7; color: #92400e; }
.inv-gsumchip.avail  { background: #dcfce7; color: #166534; }
.inv-status-dot {
    width: 8px; height: 8px;
    border-radius: 50%; flex-shrink: 0;
    margin-left: 4px;
}
.inv-status-dot.good    { background: #22c55e; }
.inv-status-dot.warning { background: #f59e0b; }
.inv-status-dot.danger  { background: #ef4444; }

/* Variant row */
.inv-variant-row {
    display: grid;
    grid-template-columns: 260px 1fr 110px 110px 110px;
    align-items: center;
    padding: 10px 16px;
    border-bottom: 1px solid #f1f5f9;
}
.inv-variant-row:last-child { border-bottom: 0; }
.inv-variant-row.low-stock  { background: #fffbeb; }
.inv-variant-row.out-stock  { background: #fff5f5; }

.inv-vpad { padding-left: 46px; } /* indent under product thumb */
.inv-vname {
    font-size: .82rem;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.inv-vsku { font-size: .69rem; color: #94a3b8; font-family: monospace; margin-top: 1px; }

/* Number cells */
.ipc-num-cell { text-align: center; }
.ipc-num {
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1;
    display: block;
}
.ipc-num.stock  { color: #0369a1; }
.ipc-num.booked { color: #b45309; }
.ipc-num.avail  { color: #16a34a; }
.ipc-num.zero   { color: #94a3b8; }
.ipc-num.danger { color: #dc2626; }
.ipc-num-label {
    font-size: .6rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-top: 2px;
    display: block;
}

/* Daily chips */
.inv-daily-chips { display: flex; gap: 4px; flex-wrap: wrap; }
.ipc-chip {
    font-size: .66rem;
    font-weight: 700;
    border-radius: 999px;
    padding: 2px 7px;
}
.ipc-chip.import  { background: #dcfce7; color: #166534; }
.ipc-chip.export  { background: #fee2e2; color: #991b1b; }
.ipc-chip.reserve { background: #fef3c7; color: #92400e; }

/* Empty & pagination */
.empty-state { padding: 44px 24px; text-align: center; color: #94a3b8; }
.empty-state i { display: block; font-size: 2.2rem; margin-bottom: 10px; }
.empty-state h6 { font-weight: 700; color: #475569; }

/* Warning stat */
.inv-stat.green { border-left: 3px solid #22c55e; }
.inv-stat.gray  { border-left: 3px solid #94a3b8; }
.green .inv-stat__icon { background: #dcfce7; color: #16a34a; }
.gray  .inv-stat__icon { background: #f1f5f9; color: #64748b; }

@media (max-width: 768px) {
    .inv-list-head,
    .inv-variant-row {
        grid-template-columns: 1fr 80px 80px 80px;
    }
    .inv-list-head-col:nth-child(2),
    .inv-variant-row > .inv-vpad { display: none; }
    .ipc-num { font-size: 1rem; }
}
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 gap-3 flex-wrap">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0f172a;">
            <i class="bi bi-boxes text-primary me-2"></i>Quản lý Tồn Kho
        </h4>
        <p class="text-muted small mb-0 mt-1">Theo dõi số lượng tồn, đặt cọc và cảnh báo ngưỡng</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('warehouse.stock-in') }}" class="btn btn-sm btn-success">
            <i class="bi bi-arrow-down-circle me-1"></i>Nhập kho
        </a>
        <form method="POST" action="{{ route('warehouse.inventory.cancel-overdue') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary"
                title="Hủy các đơn quá ngày chưa xử lý và trả lại tồn kho chính xác"
                onclick="return confirm('Xử lý hủy đơn quá hạn và trả tồn kho?\nThao tác này không thể hoàn tác.')">
                <i class="bi bi-arrow-clockwise me-1"></i>Trả tồn kho đơn quá hạn
            </button>
        </form>
        <a href="{{ route('warehouse.stock-out') }}" class="btn btn-sm btn-warning">
            <i class="bi bi-arrow-up-circle me-1"></i>Xuất kho
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
        <i class="bi bi-info-circle me-1"></i>{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Stat Cards — Row 1: Tổng quan tồn kho --}}
<div class="row g-3 mb-3">
    <div class="col-lg-3 col-sm-6">
        <div class="inv-stat blue">
            <div class="inv-stat__icon"><i class="bi bi-boxes"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['total_quantity']) }}</div>
                <div class="inv-stat__lbl">Tổng tồn kho</div>
                <div class="inv-stat__sub">{{ number_format($stats['total_items']) }} biến thể · {{ number_format($stats['total_products']) }} SP</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="inv-stat green">
            <div class="inv-stat__icon"><i class="bi bi-check2-circle"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['total_available']) }}</div>
                <div class="inv-stat__lbl">Khả dụng</div>
                <div class="inv-stat__sub">Tồn - Đang book</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="inv-stat amber">
            <div class="inv-stat__icon"><i class="bi bi-bookmark-check"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['total_reserved']) }}</div>
                <div class="inv-stat__lbl">Đang book (tổng)</div>
                <div class="inv-stat__sub">Hôm nay: +{{ number_format($stats['daily_reserved']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        @if($stats['out_of_stock'] > 0)
        <div class="inv-stat red">
            <div class="inv-stat__icon"><i class="bi bi-exclamation-octagon"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['out_of_stock']) }}</div>
                <div class="inv-stat__lbl">Hết hàng</div>
                <div class="inv-stat__sub">
                    @if($stats['low_stock'] > 0)
                        + {{ number_format($stats['low_stock']) }} sắp hết
                    @else
                        biến thể hết hàng
                    @endif
                </div>
            </div>
        </div>
        @elseif($stats['low_stock'] > 0)
        <div class="inv-stat amber">
            <div class="inv-stat__icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['low_stock']) }}</div>
                <div class="inv-stat__lbl">Sắp hết hàng</div>
                <div class="inv-stat__sub">dưới ngưỡng cảnh báo</div>
            </div>
        </div>
        @else
        <div class="inv-stat green">
            <div class="inv-stat__icon"><i class="bi bi-shield-check"></i></div>
            <div>
                <div class="inv-stat__num">OK</div>
                <div class="inv-stat__lbl">Tồn kho ổn định</div>
                <div class="inv-stat__sub">Không có hàng thiếu</div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Stat Cards — Row 2: Hoạt động trong ngày --}}
<div class="row g-3 mb-4">
    <div class="col-lg-4 col-sm-6">
        <div class="inv-stat blue" style="border-left-color:#0ea5e9;">
            <div class="inv-stat__icon" style="background:#e0f2fe;color:#0284c7;"><i class="bi bi-arrow-down-circle"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['daily_import']) }}</div>
                <div class="inv-stat__lbl">Nhập trong ngày</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="inv-stat red">
            <div class="inv-stat__icon"><i class="bi bi-arrow-up-circle"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['daily_export']) }}</div>
                <div class="inv-stat__lbl">Xuất trong ngày</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="inv-stat gray">
            <div class="inv-stat__icon"><i class="bi bi-calendar-event"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['daily_reservation_rows']) }}</div>
                <div class="inv-stat__lbl">Lượt đặt trong ngày</div>
                <div class="inv-stat__sub">{{ number_format($stats['daily_reserved']) }} đơn vị</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="filter-card mb-4">
    <div class="daily-note">
        <i class="bi bi-calendar3 me-1"></i>
        Đang hiển thị dữ liệu xử lý trong ngày: <strong>{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>
        @if($selectedDate !== now()->toDateString())
            <span class="d-block mt-1">Số tồn và khả dụng đang dùng snapshot cuối ngày để đồng bộ với màn warehouse/orders.</span>
        @endif
    </div>
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-lg-4 col-md-6">
            <label class="form-label fw-600 small mb-1">Tìm kiếm</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0"
                    placeholder="Tên sản phẩm, SKU biến thể..."
                    value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-600 small mb-1">Ngày xử lý</label>
            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-600 small mb-1">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                <option value="low_stock"    {{ request('status') === 'low_stock'    ? 'selected' : '' }}>
                    ⚠️ Hàng sắp hết
                </option>
                <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>
                    ❌ Hàng hết
                </option>
            </select>
        </div>
        <div class="col-lg-2 col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-funnel me-1"></i>Lọc
            </button>
            <a href="{{ route('warehouse.inventory') }}" class="btn btn-light border">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </form>
</div>

{{-- Table --}}
@if($products->count() > 0)
<div class="inv-list-wrap mb-4">

    {{-- Sticky column headers --}}
    <div class="inv-list-head">
        <div class="inv-list-head-col">Sản phẩm</div>
        <div class="inv-list-head-col">Biến thể / SKU</div>
        <div class="inv-list-head-col">Tồn kho</div>
        <div class="inv-list-head-col">Đang book</div>
        <div class="inv-list-head-col">Khả dụng</div>
    </div>

    @foreach($products as $product)
        @php
            $variants = $product->variants->filter(fn ($v) => $v->inventories->isNotEmpty())->values();
            $totalQty      = (int) $variants->sum(fn ($v) => (int) ($v->snapshot_quantity ?? $v->inventories->sum('quantity')));
            $totalReserved = (int) $variants->sum(fn ($v) => (int) ($v->snapshot_reserved ?? $v->inventories->sum('reserved_quantity')));
            $totalAvail    = (int) $variants->sum(fn ($v) => (int) ($v->snapshot_available ?? max(0, $v->inventories->sum('quantity') - $v->inventories->sum('reserved_quantity'))));

            $hasOutOfStock = $variants->contains(fn ($v) =>
                (int) ($v->snapshot_quantity ?? $v->inventories->sum('quantity')) <= 0);
            $hasLowStock = !$hasOutOfStock && $variants->contains(fn ($v) =>
                ((int) ($v->snapshot_quantity ?? $v->inventories->sum('quantity')) > 0)
                && ((int) ($v->snapshot_quantity ?? $v->inventories->sum('quantity')) <= max(5, (int) $v->inventories->sum(fn ($inv) => (int) ($inv->low_stock_threshold ?: 5)))));

            $dotClass = $hasOutOfStock ? 'danger' : ($hasLowStock ? 'warning' : 'good');

            $imgSrc = $product->avatar?->media?->file_path
                ? asset('storage/' . $product->avatar->media->file_path)
                : null;
        @endphp

        {{-- Product group header --}}
        <div class="inv-group-header">
            <div class="inv-group-thumb">
                @if($imgSrc)
                    <img src="{{ $imgSrc }}" alt="{{ $product->name }}"
                        onerror="this.parentElement.innerHTML='<i class=\'bi bi-box-seam\'></i>'">
                @else
                    <i class="bi bi-box-seam"></i>
                @endif
            </div>
            <div class="inv-group-name" title="{{ $product->name }}">{{ $product->name }}</div>
            <div class="inv-group-summary">
                <span class="inv-gsumchip stock">Tồn {{ number_format($totalQty) }}</span>
                @if($totalReserved > 0)
                    <span class="inv-gsumchip booked">Book {{ number_format($totalReserved) }}</span>
                @endif
                <span class="inv-gsumchip avail">Sẵn {{ number_format($totalAvail) }}</span>
            </div>
            <div class="inv-status-dot {{ $dotClass }}"></div> 
        </div>

        {{-- Variant rows --}}
        @foreach($variants as $variant)
            @php
                $vQty      = (int) ($variant->snapshot_quantity ?? $variant->inventories->sum('quantity'));
                $vReserved = (int) ($variant->snapshot_reserved ?? $variant->inventories->sum('reserved_quantity'));
                $vAvail    = (int) ($variant->snapshot_available ?? max(0, $vQty - $vReserved));
                $vThresh   = max(5, (int) $variant->inventories->sum(fn ($inv) => (int) ($inv->low_stock_threshold ?: 5)));
                $vRow      = $vQty <= 0 ? 'out-stock' : ($vQty <= $vThresh ? 'low-stock' : '');

                $vImport  = (int) $variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '>', 0)->sum('quantity'));
                $vExport  = (int) abs($variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '<', 0)->sum('quantity')));
                $vResvDay = (int) $variant->inventories->sum(fn ($inv) => $inv->reservations->sum('quantity'));
            @endphp
            <div class="inv-variant-row {{ $vRow }}">
                {{-- Col 1: empty (product column, indent) --}}
                <div class="inv-vpad"></div>

                {{-- Col 2: variant name + SKU --}}
                <div>
                    <div class="inv-vname" title="{{ $variant->name ?: $product->name }}">{{ Str::limit($variant->name ?: $product->name, 35) }}</div>
                    <div class="inv-vsku">{{ $variant->sku ?: '—' }}</div>
                </div>

                {{-- Col 3: Tồn kho --}}
                <div class="ipc-num-cell">
                    <span class="ipc-num {{ $vQty <= 0 ? 'danger' : 'stock' }}">{{ number_format($vQty) }}</span>
                    <span class="ipc-num-label">tồn</span>
                </div>

                {{-- Col 4: Đang book (tổng + hôm nay) --}}
                <div class="ipc-num-cell">
                    <span class="ipc-num {{ $vReserved > 0 ? 'booked' : 'zero' }}">{{ number_format($vReserved) }}</span>
                    <span class="ipc-num-label">book</span>
                </div>

                {{-- Col 5: Khả dụng --}}
                <div class="ipc-num-cell">
                    <span class="ipc-num {{ $vAvail <= 0 ? 'danger' : 'avail' }}">{{ number_format($vAvail) }}</span>
                    <span class="ipc-num-label">sẵn</span>
                </div>
            </div>
        @endforeach
    @endforeach

</div>

{{-- Pagination --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="text-muted small">
        Hiển thị <strong>{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong>
        / <strong>{{ $products->total() }}</strong> sản phẩm
    </div>
    {{ $products->appends(request()->query())->links('pagination::bootstrap-5') }}
</div>

@else
<div class="inv-table-wrap">
    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h6>Không có dữ liệu tồn kho</h6>
        <p class="small mb-3">
            @if(request()->hasAny(['search','status','date']))
                Không tìm thấy kết quả phù hợp với bộ lọc hiện tại.
            @else
                Chưa có sản phẩm nào trong kho.
            @endif
        </p>
        @if(request()->hasAny(['search','status','date']))
            <a href="{{ route('warehouse.inventory') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>Xoá bộ lọc
            </a>
        @endif
    </div>
</div>
@endif

@endsection
