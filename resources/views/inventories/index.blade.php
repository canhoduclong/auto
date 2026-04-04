@extends('layouts.app')

@push('styles')
<style>
    .inv-page .inv-title {
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .inv-page .inv-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
    }

    .inv-page .inv-stat-label {
        color: #64748b;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .inv-page .inv-stat-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #0f172a;
    }

    .inv-page .inv-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 0;
    }

    .inv-page .inv-table td {
        vertical-align: middle;
    }

    .inv-page .inv-meta {
        color: #64748b;
        font-size: 0.8rem;
    }

    .inv-page .inv-modal-table th {
        white-space: nowrap;
    }

    .inv-page .daily-series {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 0.65rem;
    }

    .inv-page .daily-series-item {
        display: block;
        text-decoration: none;
        color: inherit;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.6rem 0.7rem;
        background: #eef6ff;
        transition: all 0.18s ease;
    }

    .inv-page .daily-series-item:hover {
        border-color: #93c5fd;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.15);
    }

    .inv-page .daily-series-item.is-empty {
        background: #f1f5f9;
        border-color: #e2e8f0;
        color: #64748b;
    }

    .inv-page .daily-series-item.is-active {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    }

    .inv-page .daily-series-date {
        font-size: 0.8rem;
        font-weight: 700;
    }

    .inv-page .daily-series-value {
        margin-top: 0.15rem;
        font-size: 0.92rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid inv-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="inv-title mb-1">{{ __('inventory.titles.inventories') }}</h1>
            <div class="text-muted small">
                Quan ly ton kho ro rang theo kho, theo ngay va theo khoang thoi gian.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary border">Ban ghi hien tai: {{ number_format($globalSummary['filtered_rows']) }}</span>
            <span class="badge bg-light text-dark border">Tat ca kho: {{ number_format($globalSummary['warehouse_count']) }}</span>
        </div>
    </div>

    <div class="card inv-card mb-3">
        <div class="card-body pb-0">
            <ul class="nav nav-tabs" id="inventoryStatsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-tab-pane" type="button" role="tab" aria-controls="overview-tab-pane" aria-selected="{{ $activeTab === 'overview' ? 'true' : 'false' }}">
                        Xem thong ke
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'total' ? 'active' : '' }}" id="total-stats-tab" data-bs-toggle="tab" data-bs-target="#total-stats-tab-pane" type="button" role="tab" aria-controls="total-stats-tab-pane" aria-selected="{{ $activeTab === 'total' ? 'true' : 'false' }}">
                        Thong ke tong
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content" id="inventoryStatsTabsContent">
        <div class="tab-pane fade {{ $activeTab === 'overview' ? 'show active' : '' }}" id="overview-tab-pane" role="tabpanel" aria-labelledby="overview-tab" tabindex="0">

    <div class="card inv-card mb-3">
        <div class="card-header bg-white border-0 pt-3 pb-0">
            <h6 class="mb-0">{{ __('inventory.filters.title') }}</h6>
        </div>
        <div class="card-body pt-3">
            <form method="GET" action="{{ route('inventories.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="active_tab" value="{{ $activeTab }}">
                <div class="col-md-3">
                    <label class="form-label">{{ __('inventory.labels.warehouse') }}</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">{{ __('inventory.filters.all_warehouses') }}</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ (string) $warehouseId === (string) $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('inventory.filters.selected_date') }}</label>
                    <input type="date" class="form-control" name="selected_date" value="{{ $selectedDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('inventory.filters.range_quick') }}</label>
                    <select name="range_preset" class="form-select">
                        <option value="week" {{ $rangeStats['range_preset'] === 'week' ? 'selected' : '' }}>{{ __('inventory.filters.week') }}</option>
                        <option value="month" {{ $rangeStats['range_preset'] === 'month' ? 'selected' : '' }}>{{ __('inventory.filters.month') }}</option>
                        <option value="custom" {{ $rangeStats['range_preset'] === 'custom' ? 'selected' : '' }}>{{ __('inventory.filters.custom') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('inventory.filters.from_date') }}</label>
                    <input type="date" class="form-control" name="from_date" value="{{ $rangeStats['from_date'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('inventory.filters.to_date') }}</label>
                    <input type="date" class="form-control" name="to_date" value="{{ $rangeStats['to_date'] }}">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('inventory.buttons.view_stats') }}</button>
                    <a href="{{ route('inventories.index') }}" class="btn btn-outline-secondary">{{ __('inventory.buttons.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card inv-card h-100">
                <div class="card-body">
                    <div class="inv-stat-label">Tong san pham (tat ca kho)</div>
                    <div class="inv-stat-value">{{ number_format($globalSummary['on_hand']) }}</div>
                    <div class="text-muted small mt-1">Tong so luong ton kho gom tat ca kho.</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card inv-card h-100">
                <div class="card-body">
                    <div class="inv-stat-label">Tong SKU dang quan ly</div>
                    <div class="inv-stat-value">{{ number_format($globalSummary['total_sku']) }}</div>
                    <div class="text-muted small mt-1">So bien the san pham co du lieu ton kho.</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card inv-card h-100">
                <div class="card-body">
                    <div class="inv-stat-label">San pham kha dung</div>
                    <div class="inv-stat-value">{{ number_format($globalSummary['available']) }}</div>
                    <div class="text-muted small mt-1">Tong so luong co the ban (tru da giu cho).</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card inv-card h-100">
                <div class="card-body">
                    <div class="inv-stat-label">So luong dang giu cho</div>
                    <div class="inv-stat-value">{{ number_format($globalSummary['reserved']) }}</div>
                    <div class="text-muted small mt-1">Tong da reserve tren toan he thong.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card inv-card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Xem thong ke chi tiet theo tung kho</h6>
            <span class="text-muted small">Tong hop ton kho cua tat ca kho</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle inv-table mb-0">
                    <thead>
                        <tr>
                            <th>Kho</th>
                            <th class="text-end">So dong ton</th>
                            <th class="text-end">Ton</th>
                            <th class="text-end">Giu cho</th>
                            <th class="text-end">Kha dung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouseSummary as $warehouseRow)
                            <tr>
                                <td class="fw-semibold">{{ $warehouseRow->warehouse_name }}</td>
                                <td class="text-end">{{ number_format((int) $warehouseRow->row_count) }}</td>
                                <td class="text-end">{{ number_format((int) $warehouseRow->on_hand_sum) }}</td>
                                <td class="text-end">{{ number_format((int) $warehouseRow->reserved_sum) }}</td>
                                <td class="text-end fw-semibold text-success">{{ number_format((int) $warehouseRow->available_sum) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Khong co du lieu thong ke theo kho.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="card inv-card h-100">
                <div class="card-header bg-white border-0 pb-0">{{ __('inventory.sections.stock_summary') }}</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="inv-stat-label">{{ __('inventory.labels.on_hand') }}</span>
                        <strong>{{ number_format($stockSummary['on_hand']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="inv-stat-label">{{ __('inventory.labels.reserved') }}</span>
                        <strong>{{ number_format($stockSummary['reserved']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="inv-stat-label">{{ __('inventory.labels.available') }}</span>
                        <strong class="text-success">{{ number_format($stockSummary['available']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card inv-card h-100">
                <div class="card-header bg-white border-0 pb-0">{{ __('inventory.sections.daily_stats', ['date' => \Carbon\Carbon::parse($selectedDate)->format('d/m/Y')]) }}</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span class="inv-stat-label">{{ __('inventory.labels.document_count') }}</span><strong>{{ number_format($dailyStats['document_count']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="inv-stat-label">{{ __('inventory.labels.import_qty') }}</span><strong class="text-success">+{{ number_format($dailyStats['import_qty']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="inv-stat-label">{{ __('inventory.labels.export_qty') }}</span><strong class="text-danger">-{{ number_format($dailyStats['export_qty']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="inv-stat-label">{{ __('inventory.labels.adjustment_qty') }}</span><strong>{{ number_format($dailyStats['adjustment_qty']) }}</strong></div>
                    <div class="d-flex justify-content-between"><span class="inv-stat-label">{{ __('inventory.labels.net_qty') }}</span><strong>{{ number_format($dailyStats['net_qty']) }}</strong></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card inv-card h-100">
                <div class="card-header bg-white border-0 pb-0">{{ __('inventory.sections.range_stats', ['from' => \Carbon\Carbon::parse($rangeStats['from_date'])->format('d/m/Y'), 'to' => \Carbon\Carbon::parse($rangeStats['to_date'])->format('d/m/Y')]) }}</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span class="inv-stat-label">{{ __('inventory.labels.document_count') }}</span><strong>{{ number_format($rangeStats['document_count']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="inv-stat-label">{{ __('inventory.labels.import_qty') }}</span><strong class="text-success">+{{ number_format($rangeStats['import_qty']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="inv-stat-label">{{ __('inventory.labels.export_qty') }}</span><strong class="text-danger">-{{ number_format($rangeStats['export_qty']) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="inv-stat-label">{{ __('inventory.labels.adjustment_qty') }}</span><strong>{{ number_format($rangeStats['adjustment_qty']) }}</strong></div>
                    <div class="d-flex justify-content-between"><span class="inv-stat-label">{{ __('inventory.labels.net_qty') }}</span><strong>{{ number_format($rangeStats['net_qty']) }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card inv-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">{{ __('inventory.sections.inventory_detail') }}</h6>
            <span class="text-muted small">Tong ban ghi: {{ number_format($inventories->total()) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle inv-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>{{ __('inventory.labels.product_variant') }}</th>
                            <th>{{ __('inventory.labels.warehouse') }}</th>
                            <th>{{ __('inventory.labels.on_hand') }}</th>
                            <th>{{ __('inventory.labels.reserved') }}</th>
                            <th>{{ __('inventory.labels.available') }}</th>
                            <th>{{ __('inventory.labels.low_stock_threshold') }}</th>
                            <th>{{ __('inventory.labels.updated_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventories as $inventory)
                            <tr>
                                <td class="fw-semibold">{{ $inventory->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $inventory->productVariant->sku ?? ('#' . $inventory->product_variant_id) }}</div>
                                    <div class="inv-meta">{{ $inventory->productVariant->product->name ?? '' }}</div>
                                </td>
                                <td>{{ $inventory->warehouse->name ?? ('#' . $inventory->warehouse_id) }}</td>
                                <td>{{ number_format($inventory->on_hand) }}</td>
                                <td>{{ number_format($inventory->reserved) }}</td>
                                <td>
                                    <span class="badge {{ $inventory->available > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border">
                                        {{ number_format($inventory->available) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $threshold = (int) ($inventory->low_stock_threshold ?? 0);
                                        $isLow = $threshold > 0 && (int) $inventory->available <= $threshold;
                                    @endphp
                                    <span class="badge {{ $isLow ? 'bg-warning text-dark' : 'bg-light text-dark' }} border">
                                        {{ number_format($threshold) }}
                                    </span>
                                </td>
                                <td>{{ optional($inventory->updated_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Khong co du lieu ton kho.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $inventories->links() }}
        </div>
    </div>

        </div>

        <div class="tab-pane fade {{ $activeTab === 'total' ? 'show active' : '' }}" id="total-stats-tab-pane" role="tabpanel" aria-labelledby="total-stats-tab" tabindex="0">
            <div class="card inv-card mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Thong ke tong ton kho</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('inventories.index') }}" class="row g-2 align-items-end mb-3">
                        <input type="hidden" name="active_tab" value="total">
                        <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
                        <input type="hidden" name="range_preset" value="{{ $rangeStats['range_preset'] }}">
                        <input type="hidden" name="from_date" value="{{ $rangeStats['from_date'] }}">
                        <input type="hidden" name="to_date" value="{{ $rangeStats['to_date'] }}">
                        <div class="col-12 col-md-3">
                            <label class="form-label mb-1">Thong ke theo ngay</label>
                            <input type="date" class="form-control" name="selected_date" value="{{ $dailyExportOrderStats['date'] }}">
                        </div>
                        <div class="col-12 col-md-auto">
                            <button type="submit" class="btn btn-primary">Xem thong ke ngay</button>
                        </div>
                        <div class="col-12 col-md-auto">
                            <span class="text-muted small">Mac dinh la ngay hien tai.</span>
                        </div>
                    </form>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">7 ngay gan nhat</h6>
                            <span class="text-muted small">Ngay co du lieu se hien noi bat, ngay khong co hang hien mau xam.</span>
                        </div>
                        <div class="daily-series">
                            @foreach($dailyExportSeries as $series)
                                <a
                                    href="{{ route('inventories.index', array_merge(request()->query(), ['selected_date' => $series['date'], 'active_tab' => 'total'])) }}"
                                    class="daily-series-item {{ $series['has_data'] ? '' : 'is-empty' }} {{ $series['date'] === $dailyExportOrderStats['date'] ? 'is-active' : '' }}"
                                >
                                    <div class="daily-series-date">{{ $series['label'] }}</div>
                                    <div class="daily-series-value">Xuat: {{ number_format($series['exported_qty']) }}</div>
                                    <div class="daily-series-value">Don: {{ number_format($series['order_count']) }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-3">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="inv-stat-label">Don hang da xuat</div>
                                <div class="inv-stat-value">{{ number_format($dailyExportOrderStats['order_count']) }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="inv-stat-label">So luong hang da xuat</div>
                                <div class="inv-stat-value">{{ number_format($dailyExportOrderStats['exported_qty']) }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="inv-stat-label">Thong ke tien ship</div>
                                <div class="inv-stat-value">{{ number_format((float) $dailyExportOrderStats['shipping_fee_total'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="inv-stat-label">Gia tri don hang xuat</div>
                                <div class="inv-stat-value">{{ number_format((float) $dailyExportOrderStats['order_value_total'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="inv-stat-label">Tong san pham</div>
                                <div class="inv-stat-value">{{ number_format($totalStats['product_count']) }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="inv-stat-label">Tong bien the</div>
                                <div class="inv-stat-value">{{ number_format($totalStats['variant_count']) }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="inv-stat-label">Tong so luong ton</div>
                                <div class="inv-stat-value">{{ number_format($totalStats['quantity_sum']) }}</div>
                                <div class="small text-muted mt-1">Kha dung: {{ number_format($totalStats['available_sum']) }} | Giu cho: {{ number_format($totalStats['reserved_sum']) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <h6 class="mb-2">Tong hop theo san pham</h6>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-striped mb-0 inv-modal-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>San pham</th>
                                            <th class="text-end">Don vi</th>
                                            <th class="text-end">So bien the</th>
                                            <th class="text-end">On hand</th>
                                            <th class="text-end">Kha dung</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($productTotals as $productTotal)
                                            <tr>
                                                <td>{{ $productTotal->product_name }}</td>
                                                <td class="text-end">{{ \App\Enums\ProductUnit::tryFrom((string) ($productTotal->product_unit ?? 'cai'))?->label() ?? 'Cái' }}</td>
                                                <td class="text-end">{{ number_format((int) $productTotal->variant_count) }}</td>
                                                <td class="text-end">{{ number_format((int) $productTotal->on_hand_sum) }}</td>
                                                <td class="text-end">{{ number_format(max(0, (int) $productTotal->available_sum)) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Khong co du lieu.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <h6 class="mb-2">Tong hop theo bien the</h6>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-striped mb-0 inv-modal-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>SKU</th>
                                            <th>San pham</th>
                                            <th class="text-end">Don vi</th>
                                            <th class="text-end">On hand</th>
                                            <th class="text-end">Kha dung</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($variantTotals as $variantTotal)
                                            <tr>
                                                <td class="fw-semibold">{{ $variantTotal->sku ?: ('#' . $variantTotal->variant_id) }}</td>
                                                <td>{{ $variantTotal->product_name }}</td>
                                                <td class="text-end">{{ \App\Enums\ProductUnit::tryFrom((string) ($variantTotal->product_unit ?? 'cai'))?->label() ?? 'Cái' }}</td>
                                                <td class="text-end">{{ number_format((int) $variantTotal->on_hand_sum) }}</td>
                                                <td class="text-end">{{ number_format(max(0, (int) $variantTotal->available_sum)) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Khong co du lieu.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection