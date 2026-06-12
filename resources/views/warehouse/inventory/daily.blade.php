@extends('layouts.warehouse')

@section('title', 'Tồn kho Daily')

@push('styles')
<style>
    .daily-card { border: 1px solid #d8e5e3; border-radius: 10px; background: #fff; overflow: hidden; }
    .daily-filter { padding: 14px; border-bottom: 1px solid #e5e7eb; }
    .daily-table-wrap { overflow: auto; max-height: calc(100vh - 285px); }
    .daily-table { min-width: max-content; margin: 0; border-collapse: separate; border-spacing: 0; font-size: .82rem; }
    .daily-table th, .daily-table td { min-width: 82px; padding: 8px 9px; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .daily-table thead th { position: sticky; z-index: 4; color: #374151; font-weight: 700; text-align: center; }
    .daily-table thead tr:first-child th { top: 0; background: #dff3ef; }
    .daily-table thead tr:nth-child(2) th { top: 37px; background: #f0f9f7; }
    .daily-table .identity { text-align: left; min-width: 185px; max-width: 220px; }
    .daily-table .identity.sku, .daily-table .identity.unit { min-width: 90px; max-width: 110px; }
    .daily-table .sticky-col { position: sticky; z-index: 2; background: #fff; }
    .daily-table thead .sticky-col { z-index: 6; }
    .daily-table .col-product { left: 0; }
    .daily-table .col-variant { left: 185px; }
    .daily-table .col-sku { left: 370px; }
    .daily-table .col-unit { left: 460px; box-shadow: 3px 0 5px rgba(15, 23, 42, .08); }
    .daily-table tbody tr:hover td { background: #f8fbfb; }
    .daily-table tbody tr:hover .sticky-col { background: #f8fbfb; }
    .daily-table .group-end { border-right: 2px solid #9fcac4; }
    .daily-table .import { color: #047857; background: #f0fdf4; }
    .daily-table .export { color: #b91c1c; background: #fef2f2; }
    .daily-table .total { background: #fffbeb; font-weight: 600; }
    .daily-table .closing { background: #eff6ff; font-weight: 700; }
    .daily-table tfoot td { position: sticky; bottom: 0; z-index: 3; background: #ecfdf5; font-weight: 700; }
    .daily-table tfoot .sticky-col { z-index: 5; background: #d1fae5; }
    .daily-help { color: #5f6f6d; font-size: .82rem; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h1 class="h5 fw-bold mb-1">Tồn kho Daily</h1>
        <div class="daily-help">Công thức mỗi ngày: <strong>Tồn đầu + Nhập = Tổng</strong>, <strong>Tổng - Xuất = Tồn cuối</strong>.</div>
    </div>
    <a href="{{ route('warehouse.inventory') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-stack me-1"></i>Tồn kho
    </a>
</div>

<div class="daily-card">
    <div class="daily-filter">
        <form method="GET" action="{{ route('warehouse.inventory-daily') }}" class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-semibold mb-1">Tìm sản phẩm / biến thể / SKU</label>
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Nhập từ khóa...">
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-1">Từ ngày</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-1">Đến ngày</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Xem báo cáo</button>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <a href="{{ route('warehouse.inventory-daily') }}" class="btn btn-outline-secondary w-100">Đặt lại</a>
            </div>
        </form>
        <div class="daily-help mt-2">Hiển thị tối đa 31 ngày mỗi lần. Bảng có thể cuộn ngang; các cột nhận diện sản phẩm được giữ cố định.</div>
    </div>

    <div class="daily-table-wrap">
        <table class="daily-table">
            <thead>
                <tr>
                    <th rowspan="2" class="identity sticky-col col-product">Sản phẩm</th>
                    <th rowspan="2" class="identity sticky-col col-variant">Biến thể</th>
                    <th rowspan="2" class="identity sku sticky-col col-sku">SKU</th>
                    <th rowspan="2" class="identity unit sticky-col col-unit">ĐVT</th>
                    @foreach($dates as $date)
                        <th colspan="5" class="group-end">{{ $date['label'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($dates as $date)
                        <th>Tồn đầu</th>
                        <th>Nhập</th>
                        <th>Tổng</th>
                        <th>Xuất</th>
                        <th class="group-end">Tồn cuối</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($dailyRows as $row)
                    <tr>
                        <td class="identity sticky-col col-product fw-semibold text-wrap">{{ $row['product'] }}</td>
                        <td class="identity sticky-col col-variant text-wrap">{{ $row['variant'] }}</td>
                        <td class="identity sku sticky-col col-sku">{{ $row['sku'] }}</td>
                        <td class="identity unit sticky-col col-unit">{{ $row['unit'] }}</td>
                        @foreach($dates as $date)
                            @php($day = $row['days'][$date['date']])
                            <td>{{ number_format($day['opening']) }}</td>
                            <td class="import">{{ number_format($day['import']) }}</td>
                            <td class="total">{{ number_format($day['total']) }}</td>
                            <td class="export">{{ number_format($day['export']) }}</td>
                            <td class="closing group-end">{{ number_format($day['closing']) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + ($dates->count() * 5) }}" class="text-center py-5 text-muted">Không tìm thấy dữ liệu tồn kho phù hợp.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($dailyRows->isNotEmpty())
                <tfoot>
                    <tr>
                        <td class="identity sticky-col col-product">Cộng trang</td>
                        <td class="identity sticky-col col-variant"></td>
                        <td class="identity sku sticky-col col-sku"></td>
                        <td class="identity unit sticky-col col-unit"></td>
                        @foreach($dates as $date)
                            @php($total = $pageTotals[$date['date']])
                            <td>{{ number_format($total['opening']) }}</td>
                            <td>{{ number_format($total['import']) }}</td>
                            <td>{{ number_format($total['total']) }}</td>
                            <td>{{ number_format($total['export']) }}</td>
                            <td class="group-end">{{ number_format($total['closing']) }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

@if($variants->hasPages())
    <div class="mt-3">{{ $variants->links('pagination::bootstrap-5') }}</div>
@endif
@endsection
