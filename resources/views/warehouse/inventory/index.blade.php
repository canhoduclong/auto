@extends('layouts.warehouse')

@section('title', 'Tồn Kho')

@push('styles')
<style>
/* Clean and compact inventory UI */
.inv-stat {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 92px;
}
.inv-stat.blue { border-left: 3px solid #0ea5e9; }
.inv-stat.amber { border-left: 3px solid #f59e0b; }
.inv-stat.red { border-left: 3px solid #ef4444; }
.inv-stat__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.blue .inv-stat__icon { background: #e0f2fe; color: #0284c7; }
.amber .inv-stat__icon { background: #fef3c7; color: #b45309; }
.red .inv-stat__icon { background: #fee2e2; color: #dc2626; }
.inv-stat__num {
    font-size: 1.4rem;
    line-height: 1;
    font-weight: 800;
    color: #0f172a;
}
.inv-stat__lbl {
    margin-top: 3px;
    font-size: .76rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.inv-stat__sub {
    margin-top: 3px;
    font-size: .74rem;
    color: #94a3b8;
}

.filter-card,
.inv-table-wrap {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}
.filter-card {
    padding: 14px 16px;
}
.daily-note {
    margin-bottom: 12px;
    border: 1px solid #dbeafe;
    background: #f8fbff;
    border-radius: 10px;
    color: #1e3a8a;
    font-size: .8rem;
    padding: 8px 10px;
}

.inv-table-wrap {
    overflow: hidden;
}
.inv-table-wrap .table {
    margin: 0;
    font-size: .84rem;
}
.inv-table-wrap thead th {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    color: #64748b;
    font-size: .69rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    padding: 10px 12px;
    white-space: nowrap;
}
.inv-table-wrap tbody td {
    padding: 11px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.inv-table-wrap tbody tr:last-child td {
    border-bottom: 0;
}

.prod-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #e2e8f0;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.prod-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.prod-name {
    font-size: .85rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}
.prod-sku {
    font-size: .71rem;
    color: #94a3b8;
    font-family: monospace;
}
.prod-summary,
.warehouse-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 7px;
}

.variant-detail {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
}
.variant-name {
    font-size: .82rem;
    font-weight: 700;
    color: #0f172a;
}
.variant-subtext {
    font-size: .71rem;
    color: #64748b;
    margin-top: 3px;
}
.variant-badge {
    display: inline-block;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    font-size: .68rem;
    font-weight: 600;
    padding: 2px 8px;
}

.group-start td {
    background: #fcfdff;
}
.variant-divider td {
    border-top: 1px dashed #e2e8f0;
}

.qty-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    border-radius: 999px;
    padding: 3px 9px;
    font-size: .78rem;
    font-weight: 700;
}
.qty-primary { background: #e0f2fe; color: #075985; }
.qty-reserved { background: #fef3c7; color: #92400e; }
.qty-avail { background: #dcfce7; color: #166534; }

.daily-chip {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    border-radius: 999px;
    padding: 2px 7px;
    font-size: .69rem;
    font-weight: 700;
}
.daily-chip.import { background: #dcfce7; color: #166534; }
.daily-chip.export { background: #fee2e2; color: #991b1b; }
.daily-chip.reserve { background: #fef3c7; color: #92400e; }

.stock-bar-wrap {
    min-width: 88px;
}
.stock-bar-bg {
    height: 5px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}
.stock-bar-fill {
    height: 100%;
    border-radius: 999px;
}
.stock-bar-fill.good { background: #22c55e; }
.stock-bar-fill.warning { background: #f59e0b; }
.stock-bar-fill.danger { background: #ef4444; }

.status-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    padding: 3px 8px;
    font-size: .69rem;
    font-weight: 700;
}
.status-chip.good { background: #dcfce7; color: #166534; }
.status-chip.warning { background: #fef3c7; color: #92400e; }
.status-chip.danger { background: #fee2e2; color: #991b1b; }

.empty-state {
    padding: 44px 24px;
    text-align: center;
    color: #94a3b8;
}
.empty-state i {
    display: block;
    font-size: 2.2rem;
    margin-bottom: 10px;
}
.empty-state h6 {
    font-weight: 700;
    color: #475569;
}

@media (max-width: 992px) {
    .inv-stat {
        min-height: 84px;
    }
}

@media (max-width: 768px) {
    .filter-card {
        padding: 12px;
    }
    .inv-table-wrap {
        overflow-x: auto;
    }
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
