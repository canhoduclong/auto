@extends('layouts.warehouse')

@section('title', 'Tồn Kho')

@push('styles')
<style>
.inv-page-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #111827;
}

.inv-summary-list {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
    margin-bottom: 14px;
    overflow: hidden;
}

.inv-toolbar {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
    padding: 12px;
    margin-bottom: 14px;
}

.inv-summary-head {
    padding: 9px 12px;
    border-bottom: 1px solid #e5e7eb;
    font-size: .82rem;
    font-weight: 600;
    color: #374151;
}

.inv-summary-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 1rem;
}

.inv-summary-table th,
.inv-summary-table td {
    padding: 7px 10px;
    border-top: 1px solid #f3f4f6;
}

.inv-summary-table th {
    background: #f9fafb;
    font-size: .7rem;
    color: #4b5563;
    text-transform: uppercase;
    letter-spacing: .03em;
}

.inv-summary-table td.num,
.inv-summary-table th.num {
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.inv-toggle {
    border: 0;
    background: transparent;
    color: #111827;
    font-weight: 600;
    padding: 0;
    text-align: left;
}

.inv-toggle .caret {
    display: inline-block;
    width: 14px;
    color: #6b7280;
}

.inv-toggle .toggle-label {
    color: #4b5563;
    font-size: .75rem;
    margin-left: 6px;
}

.inv-child-row {
    display: none;
    background: #fcfcfd;
}

.inv-child-row td {
    border-top: 1px solid #e5e7eb;
    padding: 0;
}

.inv-child-row td.num {
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.inv-indent {
    padding-left: 48px;
    color: #374151;
}

.inv-product-name {
    font-weight: 600;
}

.inv-variant-name {
    font-weight: 500;
}

.inv-stat-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 14px;
    font-size: .78rem;
    color: #374151;
    margin-top: 8px;
}

.inv-stat-inline strong {
    color: #111827;
}

.inv-note {
    font-size: .78rem;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 8px;
    margin-bottom: 10px;
}

.empty-state {
    padding: 28px 20px;
    color: #6b7280;
    text-align: center;
}

</style>
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3 gap-3 flex-wrap">
    <div>
        <div class="inv-page-title">Tồn kho</div>
        <p class="text-muted small mb-0 mt-1">Hiển thị theo cấu trúc: Tồn đầu ngày, Nhập, Đang book, Xuất, Tồn cuối ngày</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('warehouse.stock-in') }}" class="btn btn-sm btn-outline-secondary">
            Nhập kho
        </a>
        <form method="POST" action="{{ route('warehouse.inventory.cancel-overdue') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary"
                title="Hủy các đơn quá ngày chưa xử lý và trả lại tồn kho chính xác"
                onclick="return confirm('Xử lý hủy đơn quá hạn và trả tồn kho?\nThao tác này không thể hoàn tác.')">
                Trả tồn kho đơn quá hạn
            </button>
        </form>
        <a href="{{ route('warehouse.stock-out') }}" class="btn btn-sm btn-outline-secondary">
            Xuất kho
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

@php
    $summaryRows = $products->getCollection()->map(function ($product) {
        $variants = $product->variants
            ->filter(fn ($variant) => $variant->inventories->isNotEmpty())
            ->sortBy(fn ($variant) => mb_strtolower((string) ($variant->name ?? '')))
            ->values();

        $closing = (int) $variants->sum(fn ($variant) => (int) ($variant->snapshot_quantity ?? $variant->inventories->sum('quantity')));
        $import = (int) $variants->sum(fn ($variant) => (int) $variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '>', 0)->sum('quantity')));
        $reserved = (int) $variants->sum(fn ($variant) => (int) ($variant->snapshot_reserved ?? $variant->inventories->sum('reserved_quantity')));
        $export = (int) $variants->sum(fn ($variant) => (int) abs($variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '<', 0)->sum('quantity'))));
        $opening = (int) ($closing - $import + $export);

        $variantRows = $variants->map(function ($variant) {
            $vClosing = (int) ($variant->snapshot_quantity ?? $variant->inventories->sum('quantity'));
            $vImport = (int) $variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '>', 0)->sum('quantity'));
            $vReserved = (int) ($variant->snapshot_reserved ?? $variant->inventories->sum('reserved_quantity'));
            $vExport = (int) abs($variant->inventories->sum(fn ($inv) => $inv->movements->where('quantity', '<', 0)->sum('quantity')));
            $vOpening = (int) ($vClosing - $vImport + $vExport);

            return [
                'name' => (string) ($variant->name ?: ($variant->product?->name ?? 'Biến thể')),
                'unit' => (string) ($variant->product?->unit_label ?? '—'),
                'opening' => $vOpening,
                'import' => $vImport,
                'reserved' => $vReserved,
                'export' => $vExport,
                'closing' => $vClosing,
            ];
        })->values();

        $unitLabels = $variantRows
            ->pluck('unit')
            ->filter(fn ($unit) => $unit !== '—' && $unit !== '')
            ->unique()
            ->values();
        $productUnit = $unitLabels->count() === 1 ? (string) $unitLabels->first() : ($unitLabels->count() > 1 ? 'Nhiều DVT' : '—');

        return [
            'product_id' => (int) $product->id,
            'name' => (string) $product->name,
            'unit' => $productUnit,
            'variant_count' => (int) $variants->count(),
            'opening' => $opening,
            'import' => $import,
            'reserved' => $reserved,
            'export' => $export,
            'closing' => $closing,
            'variants' => $variantRows,
        ];
    })->sortBy(fn ($row) => mb_strtolower((string) $row['name']))->values();

    $summaryTotals = [
        'opening' => (int) $summaryRows->sum('opening'),
        'import' => (int) $summaryRows->sum('import'),
        'reserved' => (int) $summaryRows->sum('reserved'),
        'export' => (int) $summaryRows->sum('export'),
        'closing' => (int) $summaryRows->sum('closing'),
    ];
@endphp

<div class="inv-toolbar">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-lg-4 col-md-6">
            <label class="form-label fw-600 small mb-1">Tìm kiếm</label>
            <input type="text" name="search" class="form-control"
                placeholder="Tên sản phẩm, tên biến thể..."
                value="{{ request('search') }}">
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-600 small mb-1">Ngày xử lý</label>
            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-600 small mb-1">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Hàng sắp hết</option>
                <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Hàng hết</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-outline-secondary flex-fill">Lọc</button>
            <a href="{{ route('warehouse.inventory') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </form>

    <div class="inv-note mt-3 mb-0 pb-0 border-0">
        Đang hiển thị dữ liệu xử lý trong ngày: <strong>{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>.
        <span class="d-block mt-1">Quy ước: Tồn cuối ngày hôm nay = Tồn đầu ngày của ngày tiếp theo.</span>
    </div>

    <div class="inv-stat-inline">
        <span>Tổng sản phẩm: <strong>{{ number_format($summaryRows->count()) }}</strong></span>
        <span>Tồn đầu: <strong>{{ number_format($summaryTotals['opening']) }}</strong></span>
        <span>Nhập: <strong>{{ number_format($summaryTotals['import']) }}</strong></span>
        <span>Đang book: <strong style="color:#1d4ed8;">{{ number_format($summaryTotals['reserved']) }}</strong></span>
        <span>Xuất: <strong>{{ number_format($summaryTotals['export']) }}</strong></span>
        <span>Tồn cuối: <strong>{{ number_format($summaryTotals['closing']) }}</strong></span>
    </div>
</div>

<div class="inv-summary-list">
    <div class="inv-summary-head">Danh sách thống kê tồn kho (sản phẩm và biến thể cùng một cấu trúc cột)</div>
    <div class="table-responsive">
        <table class="inv-summary-table">
            <thead>
                <tr>
                    <th style="min-width: 280px;">Tên sản phẩm / biến thể</th>
                    <th style="min-width: 100px;">DVT</th>
                    <th class="num" style="min-width: 110px;">Tồn đầu</th>
                    <th class="num" style="min-width: 90px;">Nhập</th>
                    <th class="num" style="min-width: 100px;">Đang book</th>
                    <th class="num" style="min-width: 90px;">Xuất</th>
                    <th class="num" style="min-width: 120px;">Tồn cuối</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaryRows as $row)
                    <tr>
                        <td>
                            <button type="button" class="inv-toggle js-inv-toggle" data-target="inv-child-{{ $row['product_id'] }}">
                                <span class="caret">▸</span>
                                <span class="inv-product-name">{{ $row['name'] }}</span>
                            </button>
                        </td>
                        <td>{{ $row['unit'] }}</td>
                        <td class="num"><strong>{{ number_format($row['opening']) }}</strong></td>
                        <td class="num">{{ number_format($row['import']) }}</td>
                        <td class="num" style="color:#1d4ed8;">{{ number_format($row['reserved']) }}</td>
                        <td class="num">{{ number_format($row['export']) }}</td>
                        <td class="num">{{ number_format($row['closing']) }}</td>
                    </tr>
                    <tr id="inv-child-{{ $row['product_id'] }}" class="inv-child-row">
                        <td class="inv-indent inv-variant-name">Biến thể</td>
                        <td>—</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                    </tr>
                    @foreach($row['variants'] as $variantRow)
                        <tr class="inv-child-row inv-child-of-{{ $row['product_id'] }}">
                            <td class="inv-indent">{{ $variantRow['name'] }}</td>
                            <td>{{ $variantRow['unit'] }}</td>
                            <td class="num"><strong>{{ number_format($variantRow['opening']) }}</strong></td>
                            <td class="num">{{ number_format($variantRow['import']) }}</td>
                            <td class="num" style="color:#1d4ed8;">{{ number_format($variantRow['reserved']) }}</td>
                            <td class="num">{{ number_format($variantRow['export']) }}</td>
                            <td class="num">{{ number_format($variantRow['closing']) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">Không có dữ liệu sản phẩm trong danh sách hiện tại.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($products->count() > 0)
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="text-muted small">
            Hiển thị <strong>{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong>
            / <strong>{{ $products->total() }}</strong> sản phẩm
        </div>
        {{ $products->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
@else
    <div class="inv-summary-list">
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

@push('scripts')
<script>
document.querySelectorAll('.js-inv-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const targetId = button.getAttribute('data-target');
        const headerRow = document.getElementById(targetId);
        if (!headerRow) {
            return;
        }

        const productId = targetId.replace('inv-child-', '');
        const childRows = document.querySelectorAll('.inv-child-of-' + productId);

        const isOpen = headerRow.style.display === 'table-row';
        const nextDisplay = isOpen ? 'none' : 'table-row';

        headerRow.style.display = nextDisplay;
        childRows.forEach(function (row) {
            row.style.display = nextDisplay;
        });

        const caret = button.querySelector('.caret');
        if (caret) {
            caret.textContent = isOpen ? '▸' : '▾';
        }

        const label = button.querySelector('.toggle-label');
        if (label) {
            label.textContent = isOpen ? '(Xem thêm)' : '(Thu gọn)';
        }
    });
});
</script>
@endpush

@endsection
