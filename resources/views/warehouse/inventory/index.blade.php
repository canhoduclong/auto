@extends(request()->routeIs('ceo.warehouse-inventory') ? 'layouts.ceo' : 'layouts.warehouse')

@section('title', 'Tồn Kho Tổng Hợp')

@push('styles')
<style>
    .inv-card { border: 1px solid #d1d5db; border-radius: 10px; background: #fff; overflow: hidden; }
    .inv-toolbar { padding: 14px; border-bottom: 1px solid #e5e7eb; }
    .inv-table-wrap { overflow: auto; max-height: calc(100vh - 300px); }
    .inv-table { min-width: max-content; margin: 0; border-collapse: separate; border-spacing: 0; font-size: .82rem; }
    .inv-table th, .inv-table td { padding: 8px 9px; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .inv-table thead th { position: sticky; z-index: 4; text-align: center; font-weight: 700; color: #374151; }
    .inv-table thead tr:first-child th { top: 0; background: #dff3ef; }
    .inv-table thead tr:nth-child(2) th { top: 37px; background: #f0f9f7; }
    .inv-table .identity { position: sticky; left: 0; z-index: 3; min-width: 230px; max-width: 230px; text-align: left; background: #fff; }
    .inv-table thead .identity { z-index: 6; }
    .inv-table .unit { position: sticky; left: 230px; z-index: 3; min-width: 85px; text-align: left; background: #fff; box-shadow: 3px 0 5px rgba(15,23,42,.08); }
    .inv-table thead .unit { z-index: 6; }
    .inv-table .warehouse-end { border-right: 2px solid #76aaa3; }
    .inv-table .available { color: #047857; background: #f0fdf4; font-weight: 700; }
    .inv-table .book { color: #1d4ed8; background: #eff6ff; font-weight: 700; }
    .inv-table .total { background: #fffbeb; font-weight: 700; }
    .inv-table .variant-row { display: none; background: #fcfcfd; }
    .inv-table .variant-row .identity, .inv-table .variant-row .unit { background: #fcfcfd; }
    .inv-table tfoot td { position: sticky; bottom: 0; z-index: 4; background: #d1fae5; font-weight: 800; }
    .inv-table tfoot .identity, .inv-table tfoot .unit { z-index: 5; background: #d1fae5; }
    .inv-toggle { border: 0; background: transparent; padding: 0; font-weight: 700; text-align: left; }
</style>
@endpush

@section('content')
@php
    $isCeoInventoryView = request()->routeIs('ceo.warehouse-inventory');
    $inventoryIndexRoute = $isCeoInventoryView ? 'ceo.warehouse-inventory' : 'warehouse.inventory';
@endphp
<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h1 class="h5 fw-bold mb-1">Tồn kho tổng hợp các kho</h1>
        <div class="text-muted small">Mỗi kho gồm Tồn đầu, Nhập, Xuất, Tồn cuối. Available = Tổng tồn cuối - Book.</div>
    </div>
    @unless($isCeoInventoryView)
        <div class="d-flex gap-2">
            <a href="{{ route('warehouse.stock-in') }}" class="btn btn-sm btn-outline-secondary">Nhập kho</a>
            <a href="{{ route('warehouse.stock-out') }}" class="btn btn-sm btn-outline-secondary">Xuất kho</a>
        </div>
    @endunless
</div>

<div class="inv-card">
    <div class="inv-toolbar">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold mb-1">Tìm sản phẩm / biến thể / SKU</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nhập từ khóa...">
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-1">Ngày</label>
                <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-lg-3 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-1">Trạng thái tổng</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="low_stock" @selected(request('status') === 'low_stock')>Sắp hết (≤ 5)</option>
                    <option value="out_of_stock" @selected(request('status') === 'out_of_stock')>Hết hàng</option>
                </select>
            </div>
            <div class="col-lg-1 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-1">Số dòng</label>
                <select name="per_page" class="form-select">
                    @foreach([25, 50, 100, 200] as $perPageOption)
                        <option value="{{ $perPageOption }}" @selected((int) request('per_page', 50) === $perPageOption)>{{ $perPageOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill">Xem tồn kho</button>
                <a href="{{ route($inventoryIndexRoute) }}" class="btn btn-outline-secondary">Đặt lại</a>
            </div>
        </form>
    </div>

    <div class="inv-table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th rowspan="2" class="identity">Sản phẩm / biến thể</th>
                    <th rowspan="2" class="unit">ĐVT</th>
                    @foreach($warehouses as $warehouse)
                        <th colspan="4" class="warehouse-end">{{ $warehouse->name }}</th>
                    @endforeach
                    <th colspan="4">Tổng mặt bằng</th>
                </tr>
                <tr>
                    @foreach($warehouses as $warehouse)
                        <th>Tồn đầu</th><th>Nhập</th><th>Xuất</th><th class="warehouse-end">Tồn cuối</th>
                    @endforeach
                    <th>Available</th><th>Book</th><th>Tổng xuất</th><th>Tổng tồn cuối</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $row)
                    <tr>
                        <td class="identity">
                            <button class="inv-toggle js-inv-toggle" type="button" data-product="{{ $row['product_id'] }}">
                                <span class="js-caret">+</span> {{ $row['name'] }}
                            </button>
                        </td>
                        <td class="unit">{{ $row['unit'] }}</td>
                        @foreach($warehouses as $warehouse)
                            @php
                                $values = $row['warehouses'][(string) $warehouse->id];
                            @endphp
                            <td>{{ number_format($values['opening']) }}</td><td>{{ number_format($values['import']) }}</td><td>{{ number_format($values['export']) }}</td><td class="warehouse-end">{{ number_format($values['closing']) }}</td>
                        @endforeach
                        <td class="available">{{ number_format($row['available']) }}</td>
                        <td class="book">{{ number_format($row['book']) }}</td>
                        <td class="total">{{ number_format($row['total_export']) }}</td>
                        <td class="total">{{ number_format($row['total_closing']) }}</td>
                    </tr>
                    @foreach($row['variants'] as $variant)
                        <tr class="variant-row js-variant-{{ $row['product_id'] }}">
                            <td class="identity ps-4">{{ $variant['name'] }} <span class="text-muted">({{ $variant['sku'] }})</span></td>
                            <td class="unit">{{ $variant['unit'] }}</td>
                            @foreach($warehouses as $warehouse)
                                @php
                                    $values = $variant['warehouses'][(string) $warehouse->id];
                                @endphp
                                <td>{{ number_format($values['opening']) }}</td><td>{{ number_format($values['import']) }}</td><td>{{ number_format($values['export']) }}</td><td class="warehouse-end">{{ number_format($values['closing']) }}</td>
                            @endforeach
                            <td class="available">{{ number_format($variant['available']) }}</td>
                            <td class="book">{{ number_format($variant['book']) }}</td>
                            <td class="total">{{ number_format($variant['total_export']) }}</td>
                            <td class="total">{{ number_format($variant['total_closing']) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="{{ 6 + ($warehouses->count() * 4) }}" class="text-center py-5 text-muted">Không có dữ liệu tồn kho phù hợp.</td></tr>
                @endforelse
            </tbody>
            @if($products->isNotEmpty())
                <tfoot><tr>
                    <td class="identity">Tổng toàn bộ</td><td class="unit"></td>
                    @foreach($warehouses as $warehouse)
                        @php
                            $values = $summaryTotals['warehouses'][(string) $warehouse->id];
                        @endphp
                        <td>{{ number_format($values['opening']) }}</td><td>{{ number_format($values['import']) }}</td><td>{{ number_format($values['export']) }}</td><td class="warehouse-end">{{ number_format($values['closing']) }}</td>
                    @endforeach
                    <td>{{ number_format($summaryTotals['available']) }}</td><td>{{ number_format($summaryTotals['book']) }}</td><td>{{ number_format($summaryTotals['total_export']) }}</td><td>{{ number_format($summaryTotals['total_closing']) }}</td>
                </tr></tfoot>
            @endif
        </table>
    </div>
</div>

@if($products->hasPages())
    <div class="mt-3">{{ $products->links('pagination::bootstrap-5') }}</div>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-inv-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const rows = document.querySelectorAll('.js-variant-' + button.dataset.product);
        const open = rows.length > 0 && rows[0].style.display === 'table-row';
        rows.forEach(row => row.style.display = open ? 'none' : 'table-row');
        button.querySelector('.js-caret').textContent = open ? '+' : '-';
    });
});
</script>
@endpush
