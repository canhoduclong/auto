@extends('layouts.warehouse')

@section('title', 'Tồn Kho')

@push('styles')
<style>
/* ── Stat cards ───────────────────────────── */
.inv-stat {
    background: #fff;
    border-radius: 16px;
    padding: 22px 24px;
    box-shadow: 0 4px 18px rgba(15,23,42,.07);
    display: flex;
    align-items: center;
    gap: 16px;
    border-left: 4px solid transparent;
    transition: box-shadow .2s, transform .2s;
}
.inv-stat:hover { box-shadow: 0 10px 30px rgba(15,23,42,.11); transform: translateY(-2px); }
.inv-stat.blue  { border-left-color: #0ea5e9; }
.inv-stat.amber { border-left-color: #f59e0b; }
.inv-stat.red   { border-left-color: #ef4444; }
.inv-stat__icon {
    width: 50px; height: 50px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.blue  .inv-stat__icon { background: #e0f2fe; color: #0ea5e9; }
.amber .inv-stat__icon { background: #fef3c7; color: #f59e0b; }
.red   .inv-stat__icon { background: #fee2e2; color: #ef4444; }
.inv-stat__num { font-size: 1.8rem; font-weight: 900; color: #0f172a; line-height: 1; }
.inv-stat__lbl { font-size: .78rem; color: #64748b; margin-top: 4px; font-weight: 500; }

/* ── Filter card ──────────────────────────── */
.filter-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 4px 18px rgba(15,23,42,.06);
}

/* ── Table card ───────────────────────────── */
.inv-table-wrap {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 18px rgba(15,23,42,.07);
    overflow: hidden;
}
.inv-table-wrap .table { margin: 0; font-size: .875rem; }
.inv-table-wrap thead { background: #f8fafc; }
.inv-table-wrap thead th {
    padding: 12px 14px;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}
.inv-table-wrap tbody td {
    padding: 13px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.inv-table-wrap tbody tr:last-child td { border-bottom: 0; }
.inv-table-wrap tbody tr:hover { background: #f8fafc; }

/* ── Product cell ─────────────────────────── */
.prod-avatar {
    width: 40px; height: 40px; border-radius: 10px;
    background: #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: #94a3b8; flex-shrink: 0; overflow: hidden;
}
.prod-avatar img { width: 100%; height: 100%; object-fit: cover; }
.prod-name { font-weight: 700; color: #0f172a; font-size: .875rem; line-height: 1.3; }
.prod-sku  { font-size: .72rem; color: #94a3b8; font-family: monospace; }
.prod-summary {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.variant-badge {
    display: inline-block;
    background: #f1f5f9;
    color: #475569;
    font-size: .7rem;
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 600;
}
.variant-name {
    font-size: .83rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.35;
}
.variant-detail {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.variant-subtext {
    font-size: .72rem;
    color: #64748b;
    margin-top: 4px;
}
.warehouse-badges {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.variant-divider td {
    border-top: 1px dashed #e2e8f0;
}
.group-start td {
    background: linear-gradient(180deg, rgba(248,250,252,.95), rgba(255,255,255,1));
}
.inv-stat__sub {
    font-size: .72rem;
    color: #94a3b8;
    margin-top: 4px;
}
.daily-note {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e3a8a;
    border-radius: 12px;
    padding: 10px 12px;
    font-size: .82rem;
    margin-bottom: 14px;
}

/* ── Qty cells ────────────────────────────── */
.qty-pill {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 42px; padding: 4px 10px;
    border-radius: 20px; font-weight: 800; font-size: .82rem;
}
.qty-primary  { background: #dbeafe; color: #1d4ed8; }
.qty-reserved { background: #fef3c7; color: #92400e; }
.qty-avail    { background: #d1fae5; color: #065f46; }

/* ── Stock progress ───────────────────────── */
.stock-bar-wrap { min-width: 90px; }
.stock-bar-bg {
    height: 6px; border-radius: 4px; background: #e2e8f0; overflow: hidden;
}
.stock-bar-fill { height: 100%; border-radius: 4px; transition: width .4s; }
.stock-bar-fill.good    { background: #10b981; }
.stock-bar-fill.warning { background: #f59e0b; }
.stock-bar-fill.danger  { background: #ef4444; }
.stock-bar-label { font-size: .7rem; color: #94a3b8; margin-top: 3px; }

/* ── Status badge ─────────────────────────── */
.status-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 20px;
    font-size: .72rem; font-weight: 700;
}
.status-chip.good    { background: #d1fae5; color: #065f46; }
.status-chip.warning { background: #fef3c7; color: #92400e; }
.status-chip.danger  { background: #fee2e2; color: #991b1b; }
.status-chip i { font-size: .8rem; }
.daily-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: .72rem;
    font-weight: 700;
}
.daily-chip.import { background: #dcfce7; color: #166534; }
.daily-chip.export { background: #fee2e2; color: #991b1b; }
.daily-chip.reserve { background: #fef3c7; color: #92400e; }

/* ── Empty state ──────────────────────────── */
.empty-state {
    padding: 60px 24px;
    text-align: center;
    color: #94a3b8;
}
.empty-state i { font-size: 3rem; display: block; margin-bottom: 12px; }
.empty-state h6 { color: #475569; font-weight: 700; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 gap-3 flex-wrap">
    <div>
        <h4 class="fw-900 mb-0" style="color:#0f172a;">
            <i class="bi bi-boxes text-primary me-2"></i>Quản lý Tồn Kho
        </h4>
        <p class="text-muted small mb-0 mt-1">Theo dõi số lượng tồn, đặt cọc và cảnh báo ngưỡng</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('warehouse.stock-in') }}" class="btn btn-sm btn-success">
            <i class="bi bi-arrow-down-circle me-1"></i>Nhập kho
        </a>
        <a href="{{ route('warehouse.stock-out') }}" class="btn btn-sm btn-warning">
            <i class="bi bi-arrow-up-circle me-1"></i>Xuất kho
        </a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
        <div class="inv-stat blue">
            <div class="inv-stat__icon"><i class="bi bi-boxes"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['total_items']) }}</div>
                <div class="inv-stat__lbl">Biến thể tồn kho</div>
                <div class="inv-stat__sub">{{ number_format($stats['total_products']) }} sản phẩm</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="inv-stat amber">
            <div class="inv-stat__icon"><i class="bi bi-arrow-down-circle"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['daily_import']) }}</div>
                <div class="inv-stat__lbl">Nhập trong ngày</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="inv-stat red">
            <div class="inv-stat__icon"><i class="bi bi-arrow-up-circle"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['daily_export']) }}</div>
                <div class="inv-stat__lbl">Xuất trong ngày</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="inv-stat amber">
            <div class="inv-stat__icon"><i class="bi bi-bookmark-check"></i></div>
            <div>
                <div class="inv-stat__num">{{ number_format($stats['daily_reserved']) }}</div>
                <div class="inv-stat__lbl">Đặt cọc trong ngày</div>
                <div class="inv-stat__sub">{{ number_format($stats['daily_reservation_rows']) }} lượt đặt</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="filter-card mb-4">
    <div class="daily-note">
        <i class="bi bi-calendar3 me-1"></i>
        Đang hiển thị dữ liệu xử lý trong ngày: <strong>{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>
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
<div class="inv-table-wrap mb-4">
    <table class="table">
        <thead>
            <tr>
                <th style="width:28%">Sản phẩm</th>
                <th style="width:32%">Biến thể</th>
                <th class="text-center">Tổng tồn</th>
                <th class="text-center">Đặt cọc</th>
                <th class="text-center">Khả dụng</th>
                <th class="text-center">Xử lý trong ngày</th>
                <th style="width:14%">Ngưỡng / Mức</th>
                <th class="text-center">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                @php
                    $variants = $product->variants->filter(fn ($variant) => $variant->inventories->isNotEmpty())->values();
                    $rowCount = max(1, $variants->count());
                    $totalQuantity = $variants->sum(fn ($variant) => $variant->inventories->sum('quantity'));
                    $totalReserved = $variants->sum(fn ($variant) => $variant->inventories->sum('reserved_quantity'));
                    $available = max(0, $totalQuantity - $totalReserved);
                    $threshold = $variants->sum(function ($variant) {
                        return $variant->inventories->sum(function ($inventory) {
                            return (int) ($inventory->low_stock_threshold ?: 5);
                        });
                    });
                    $threshold = max($threshold, 5);

                    $hasOutOfStock = $variants->contains(function ($variant) {
                        return $variant->inventories->contains(fn ($inventory) => (int) $inventory->quantity <= 0);
                    });

                    $hasLowStock = $variants->contains(function ($variant) {
                        return $variant->inventories->contains(function ($inventory) {
                            $variantThreshold = (int) ($inventory->low_stock_threshold ?: 5);

                            return (int) $inventory->quantity > 0
                                && (int) $inventory->quantity <= $variantThreshold;
                        });
                    });

                    if ($hasOutOfStock) {
                        $status = 'danger';
                        $statusText = 'Hết hàng';
                        $statusIcon = 'bi-x-circle-fill';
                        $barClass = 'danger';
                        $pct = 0;
                    } elseif ($hasLowStock || $totalQuantity <= $threshold) {
                        $status = 'warning';
                        $statusText = 'Sắp hết';
                        $statusIcon = 'bi-exclamation-triangle-fill';
                        $barClass = 'warning';
                        $pct = min(100, round($totalQuantity / max(1, $threshold) * 100));
                    } else {
                        $status = 'good';
                        $statusText = 'Đủ hàng';
                        $statusIcon = 'bi-check-circle-fill';
                        $barClass = 'good';
                        $pct = min(100, round($totalQuantity / max(1, $threshold * 3) * 100));
                    }

                    $imgSrc = null;
                    $productImagePath = $product->avatar?->media?->file_path;
                    if ($productImagePath) {
                        $imgSrc = asset('storage/' . $productImagePath);
                    }
                @endphp
                @foreach($variants as $variant)
                    @php
                        $variantQuantity = $variant->inventories->sum('quantity');
                        $variantReserved = $variant->inventories->sum('reserved_quantity');
                        $variantAvailable = max(0, $variantQuantity - $variantReserved);
                        $variantImportedToday = $variant->inventories->sum(function ($inventory) {
                            return $inventory->movements
                                ->where('quantity', '>', 0)
                                ->sum('quantity');
                        });
                        $variantExportedToday = $variant->inventories->sum(function ($inventory) {
                            return abs((int) $inventory->movements
                                ->where('quantity', '<', 0)
                                ->sum('quantity'));
                        });
                        $variantReservedToday = $variant->inventories->sum(function ($inventory) {
                            return $inventory->reservations->sum('quantity');
                        });
                        $variantThreshold = max(5, (int) $variant->inventories->sum(function ($inventory) {
                            return (int) ($inventory->low_stock_threshold ?: 5);
                        }));

                        if ($variantQuantity <= 0) {
                            $variantStatus = 'danger';
                            $variantStatusText = 'Hết hàng';
                            $variantStatusIcon = 'bi-x-circle-fill';
                            $variantBarClass = 'danger';
                            $variantPct = 0;
                        } elseif ($variantQuantity <= $variantThreshold) {
                            $variantStatus = 'warning';
                            $variantStatusText = 'Sắp hết';
                            $variantStatusIcon = 'bi-exclamation-triangle-fill';
                            $variantBarClass = 'warning';
                            $variantPct = min(100, round($variantQuantity / max(1, $variantThreshold) * 100));
                        } else {
                            $variantStatus = 'good';
                            $variantStatusText = 'Đủ hàng';
                            $variantStatusIcon = 'bi-check-circle-fill';
                            $variantBarClass = 'good';
                            $variantPct = min(100, round($variantQuantity / max(1, $variantThreshold * 3) * 100));
                        }
                    @endphp
                    <tr class="{{ $loop->first ? 'group-start' : 'variant-divider' }}">
                        @if($loop->first)
                            <td rowspan="{{ $rowCount }}" class="align-top">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="prod-avatar">
                                        @if($imgSrc)
                                            <img src="{{ $imgSrc }}" alt="{{ $product->name }}"
                                                 onerror="this.parentElement.innerHTML='<i class=\'bi bi-box-seam\'></i>'">
                                        @else
                                            <i class="bi bi-box-seam"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="prod-name">{{ Str::limit($product->name, 45) }}</div>
                                        <div class="prod-sku">{{ $variants->count() }} biến thể trong kho</div>
                                        <div class="prod-summary">
                                            <span class="qty-pill qty-primary">Tồn {{ $totalQuantity }}</span>
                                            <span class="qty-pill qty-reserved">Cọc {{ $totalReserved }}</span>
                                            <span class="qty-pill qty-avail">Sẵn {{ $available }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        @endif

                        <td>
                            <div class="variant-detail">
                                <div>
                                    <div class="variant-name">{{ $variant->name ?: $product->name }}</div>
                                    <div class="variant-subtext">SKU: {{ $variant->sku ?: 'Chưa có SKU' }}</div>
                                </div>
                                <span class="variant-badge">{{ $variant->inventories->count() }} vị trí kho</span>
                            </div>
                            <div class="warehouse-badges">
                                @foreach($variant->inventories as $inventory)
                                    <span class="variant-badge">
                                        {{ $inventory->warehouse?->name ?? 'Kho mặc định' }}: {{ $inventory->quantity }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="qty-pill qty-primary">{{ $variantQuantity }}</span>
                        </td>
                        <td class="text-center">
                            @if($variantReserved > 0)
                                <span class="qty-pill qty-reserved">{{ $variantReserved }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="qty-pill qty-avail">{{ $variantAvailable }}</span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                <span class="daily-chip import"><i class="bi bi-plus"></i>{{ $variantImportedToday }}</span>
                                <span class="daily-chip export"><i class="bi bi-dash"></i>{{ $variantExportedToday }}</span>
                                <span class="daily-chip reserve"><i class="bi bi-bookmark-check"></i>{{ $variantReservedToday }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="stock-bar-wrap">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="prod-sku">Tối thiểu: {{ $variantThreshold }}</span>
                                    <span class="prod-sku">{{ $variantPct }}%</span>
                                </div>
                                <div class="stock-bar-bg">
                                    <div class="stock-bar-fill {{ $variantBarClass }}" style="width:{{ $variantPct }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="status-chip {{ $variantStatus }}">
                                <i class="bi {{ $variantStatusIcon }}"></i>
                                {{ $variantStatusText }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
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
