@extends('layouts.warehouse')

@section('title', 'Tồn kho Daily')

@push('styles')
<style>
    .daily-card { border: 1px solid #d8e5e3; border-radius: 10px; background: #fff; overflow: hidden; }
    .daily-filter { padding: 14px; border-bottom: 1px solid #e5e7eb; }
    .daily-display-config { padding: 12px 14px; border-bottom: 1px solid #e5e7eb; background: #f8fbfb; }
    .daily-display-options { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 18px; }
    .daily-display-option { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: .86rem; }
    .daily-table-wrap { overflow: auto; max-height: calc(100vh - 285px); }
    .daily-table { min-width: max-content; margin: 0; border-collapse: separate; border-spacing: 0; font-size: .82rem; }
    .daily-table th, .daily-table td { box-sizing: border-box; min-width: 82px; padding: 8px 9px; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .daily-table thead th { position: sticky; z-index: 4; color: #374151; font-weight: 700; text-align: center; }
    .daily-table thead tr:first-child th { top: 0; background: #dff3ef; }
    .daily-table thead tr:nth-child(2) th { top: 37px; background: #f0f9f7; }
    .daily-table .identity { text-align: left; min-width: 185px; width: 185px; }
    .daily-table .identity.sku, .daily-table .identity.unit { min-width: 90px; width: 90px; }
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
    .daily-resizable { position: relative; }
    .daily-resize-handle {
        position: absolute; top: 0; right: -4px; z-index: 10; width: 8px; height: 100%;
        cursor: col-resize; touch-action: none; user-select: none;
    }
    .daily-resize-handle:hover, .daily-resize-handle.is-resizing { background: rgba(15, 118, 110, .28); }
    .daily-table.is-resizing { cursor: col-resize; user-select: none; }
    .daily-col-hidden { display: none !important; }
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

    <div class="daily-display-config">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="small fw-bold mb-1"><i class="bi bi-layout-three-columns me-1"></i>Cấu hình hiển thị báo cáo</div>
                <div class="daily-display-options">
                    <label class="daily-display-option"><input class="form-check-input mt-0 js-daily-column-toggle" type="checkbox" value="opening" checked> Tồn đầu</label>
                    <label class="daily-display-option"><input class="form-check-input mt-0 js-daily-column-toggle" type="checkbox" value="import" checked> Nhập</label>
                    <label class="daily-display-option"><input class="form-check-input mt-0 js-daily-column-toggle" type="checkbox" value="total" checked> Tổng</label>
                    <label class="daily-display-option"><input class="form-check-input mt-0" type="checkbox" checked disabled> Xuất</label>
                    <label class="daily-display-option"><input class="form-check-input mt-0 js-daily-column-toggle" type="checkbox" value="closing" checked> Tồn cuối</label>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-primary" id="dailyApplyDisplay">Apply</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="dailyResetDisplay">Reset hiển thị</button>
            </div>
        </div>
        <div class="daily-help mt-2">Kéo mép phải tiêu đề cột để resize. Cấu hình hiển thị và độ rộng cột được tự động lưu trên trình duyệt này.</div>
    </div>

    <div class="daily-table-wrap">
        <table class="daily-table" id="dailyInventoryTable">
            <thead>
                <tr>
                    <th rowspan="2" class="identity sticky-col col-product daily-resizable" data-width-key="product">Sản phẩm<span class="daily-resize-handle"></span></th>
                    <th rowspan="2" class="identity sticky-col col-variant daily-resizable" data-width-key="variant">Biến thể<span class="daily-resize-handle"></span></th>
                    <th rowspan="2" class="identity sku sticky-col col-sku daily-resizable" data-width-key="sku">SKU<span class="daily-resize-handle"></span></th>
                    <th rowspan="2" class="identity unit sticky-col col-unit daily-resizable" data-width-key="unit">ĐVT<span class="daily-resize-handle"></span></th>
                    @foreach($dates as $date)
                        <th colspan="5" class="group-end js-daily-date-group">{{ $date['label'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($dates as $date)
                        <th class="daily-resizable" data-daily-column="opening" data-width-key="opening">Tồn đầu<span class="daily-resize-handle"></span></th>
                        <th class="daily-resizable" data-daily-column="import" data-width-key="import">Nhập<span class="daily-resize-handle"></span></th>
                        <th class="daily-resizable" data-daily-column="total" data-width-key="total">Tổng<span class="daily-resize-handle"></span></th>
                        <th class="daily-resizable" data-daily-column="export" data-width-key="export">Xuất<span class="daily-resize-handle"></span></th>
                        <th class="group-end daily-resizable" data-daily-column="closing" data-width-key="closing">Tồn cuối<span class="daily-resize-handle"></span></th>
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
                            <td data-daily-column="opening" data-width-key="opening">{{ number_format($day['opening']) }}</td>
                            <td class="import" data-daily-column="import" data-width-key="import">{{ number_format($day['import']) }}</td>
                            <td class="total" data-daily-column="total" data-width-key="total">{{ number_format($day['total']) }}</td>
                            <td class="export" data-daily-column="export" data-width-key="export">{{ number_format($day['export']) }}</td>
                            <td class="closing group-end" data-daily-column="closing" data-width-key="closing">{{ number_format($day['closing']) }}</td>
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
                            <td data-daily-column="opening" data-width-key="opening">{{ number_format($total['opening']) }}</td>
                            <td data-daily-column="import" data-width-key="import">{{ number_format($total['import']) }}</td>
                            <td data-daily-column="total" data-width-key="total">{{ number_format($total['total']) }}</td>
                            <td data-daily-column="export" data-width-key="export">{{ number_format($total['export']) }}</td>
                            <td class="group-end" data-daily-column="closing" data-width-key="closing">{{ number_format($total['closing']) }}</td>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('dailyInventoryTable');
    if (!table) return;

    const storageKey = 'warehouse.inventoryDaily.display.v1';
    const optionalColumns = ['opening', 'import', 'total', 'closing'];
    const allDailyColumns = ['opening', 'import', 'total', 'export', 'closing'];
    const defaultWidths = { product: 185, variant: 185, sku: 90, unit: 90, opening: 82, import: 82, total: 82, export: 82, closing: 82 };
    let settings = { visible: [...optionalColumns], widths: { ...defaultWidths } };

    try {
        const saved = JSON.parse(localStorage.getItem(storageKey));
        if (saved && Array.isArray(saved.visible) && saved.widths) {
            settings.visible = saved.visible.filter(column => optionalColumns.includes(column));
            Object.keys(defaultWidths).forEach(function (key) {
                const savedWidth = Number(saved.widths[key]);
                if (Number.isFinite(savedWidth)) {
                    settings.widths[key] = savedWidth;
                }
            });
        }
    } catch (error) {
        localStorage.removeItem(storageKey);
    }

    const saveSettings = function () {
        localStorage.setItem(storageKey, JSON.stringify(settings));
    };

    const updateStickyOffsets = function () {
        const productWidth = settings.widths.product;
        const variantWidth = settings.widths.variant;
        const skuWidth = settings.widths.sku;
        table.querySelectorAll('.col-variant').forEach(cell => cell.style.left = productWidth + 'px');
        table.querySelectorAll('.col-sku').forEach(cell => cell.style.left = (productWidth + variantWidth) + 'px');
        table.querySelectorAll('.col-unit').forEach(cell => cell.style.left = (productWidth + variantWidth + skuWidth) + 'px');
    };

    const applyWidths = function () {
        Object.entries(settings.widths).forEach(function ([key, width]) {
            table.querySelectorAll('[data-width-key="' + key + '"], .col-' + key).forEach(function (cell) {
                cell.style.width = width + 'px';
                cell.style.minWidth = width + 'px';
                cell.style.maxWidth = width + 'px';
            });
        });
        updateStickyOffsets();
    };

    const applyVisibility = function () {
        optionalColumns.forEach(function (column) {
            const visible = settings.visible.includes(column);
            table.querySelectorAll('[data-daily-column="' + column + '"]').forEach(function (cell) {
                cell.classList.toggle('daily-col-hidden', !visible);
            });
            const toggle = document.querySelector('.js-daily-column-toggle[value="' + column + '"]');
            if (toggle) toggle.checked = visible;
        });

        const visibleCount = allDailyColumns.filter(column => column === 'export' || settings.visible.includes(column)).length;
        table.querySelectorAll('.js-daily-date-group').forEach(group => group.colSpan = visibleCount);

        table.querySelectorAll('tr').forEach(function (row) {
            const dailyCells = Array.from(row.querySelectorAll('[data-daily-column]'));
            dailyCells.forEach(cell => cell.classList.remove('group-end'));
            for (let index = 0; index < dailyCells.length; index += allDailyColumns.length) {
                const lastVisible = dailyCells
                    .slice(index, index + allDailyColumns.length)
                    .filter(cell => !cell.classList.contains('daily-col-hidden'))
                    .pop();
                if (lastVisible) lastVisible.classList.add('group-end');
            }
        });
    };

    applyVisibility();
    applyWidths();

    document.getElementById('dailyApplyDisplay').addEventListener('click', function () {
        settings.visible = Array.from(document.querySelectorAll('.js-daily-column-toggle:checked')).map(input => input.value);
        applyVisibility();
        saveSettings();
    });

    document.getElementById('dailyResetDisplay').addEventListener('click', function () {
        settings = { visible: [...optionalColumns], widths: { ...defaultWidths } };
        applyVisibility();
        applyWidths();
        saveSettings();
    });

    table.querySelectorAll('.daily-resize-handle').forEach(function (handle) {
        handle.addEventListener('pointerdown', function (event) {
            event.preventDefault();
            const header = handle.closest('[data-width-key]');
            const widthKey = header.dataset.widthKey;
            const startX = event.clientX;
            const startWidth = header.getBoundingClientRect().width;
            const minWidth = ['product', 'variant'].includes(widthKey) ? 110 : 56;

            handle.setPointerCapture(event.pointerId);
            handle.classList.add('is-resizing');
            table.classList.add('is-resizing');

            const onMove = function (moveEvent) {
                settings.widths[widthKey] = Math.max(minWidth, Math.round(startWidth + moveEvent.clientX - startX));
                applyWidths();
            };
            const onUp = function () {
                handle.classList.remove('is-resizing');
                table.classList.remove('is-resizing');
                handle.removeEventListener('pointermove', onMove);
                handle.removeEventListener('pointerup', onUp);
                handle.removeEventListener('pointercancel', onUp);
                saveSettings();
            };

            handle.addEventListener('pointermove', onMove);
            handle.addEventListener('pointerup', onUp);
            handle.addEventListener('pointercancel', onUp);
        });
    });
});
</script>
@endpush
