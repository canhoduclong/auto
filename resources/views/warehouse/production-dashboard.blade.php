@extends('layouts.warehouse')

@section('title', 'Bảng điều khiển sản xuất')

@push('styles')
<style>
    .production-hero { border: 0; border-radius: 16px; background: linear-gradient(135deg, #084c47, #0f766e); color: #fff; box-shadow: 0 12px 28px rgba(8,76,71,.18); }
    .production-date-form { display: flex; gap: .5rem; align-items: center; }
    .production-date-form input { min-width: 150px; }
    .production-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; }
    .production-kpi { padding: 14px; border: 1px solid #dbe9e7; border-radius: 14px; background: #fff; box-shadow: 0 6px 16px rgba(15,23,42,.05); }
    .production-kpi-label { min-height: 34px; color: #64748b; font-size: .76rem; font-weight: 700; text-transform: uppercase; }
    .production-kpi-value { margin-top: 5px; color: #153f3b; font-size: 1.25rem; font-weight: 850; }
    .production-kpi-note { color: #7c8b99; font-size: .73rem; }
    .production-panel { height: 100%; border: 0; border-radius: 14px; box-shadow: 0 7px 20px rgba(15,23,42,.06); overflow: hidden; }
    .production-panel .card-header { padding: .8rem 1rem; background: #fff; border-bottom-color: #e5efed; font-weight: 800; }
    .production-table { font-size: .83rem; }
    .production-table th { color: #64748b; font-size: .7rem; text-transform: uppercase; white-space: nowrap; }
    .production-trend { display: flex; min-height: 165px; align-items: end; gap: 10px; padding: 14px 8px 4px; }
    .production-trend-day { display: flex; flex: 1; min-width: 40px; height: 145px; flex-direction: column; justify-content: end; align-items: center; gap: 4px; }
    .production-trend-bar { width: min(34px, 70%); min-height: 2px; border-radius: 7px 7px 2px 2px; background: linear-gradient(180deg, #24a99d, #0f766e); }
    .production-trend-value { color: #334155; font-size: .66rem; font-weight: 750; white-space: nowrap; }
    .production-trend-label { color: #64748b; font-size: .68rem; }
    @media (max-width: 1199.98px) { .production-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 575.98px) {
        .production-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .production-hero .card-body { display: block !important; }
        .production-date-form { margin-top: 12px; }
        .production-date-form input { min-width: 0; width: 100%; }
    }
</style>
@endpush

@section('content')
@include('layouts.partials.department_broadcasts')

@php
    $productionSummary = $productionDashboard['summary'] ?? [];
@endphp
<section aria-labelledby="production-dashboard-title">
    <div class="card production-hero mb-3">
        <div class="card-body d-flex align-items-center justify-content-between gap-3">
            <div>
                <h1 id="production-dashboard-title" class="h4 fw-bold mb-1">Bảng điều khiển sản xuất nhà máy</h1>
                <div class="small text-white-50">Theo dõi nhập vịt, phân loại, sản lượng, hao hụt và chi phí theo kho.</div>
            </div>
            <form method="GET" action="{{ route('warehouse.production-dashboard') }}" class="production-date-form">
                <label for="production-date" class="visually-hidden">Ngày vận hành</label>
                <input id="production-date" type="date" name="date" value="{{ $productionDashboard['date'] ?? $selectedDate->toDateString() }}" class="form-control form-control-sm">
                <button class="btn btn-warning btn-sm fw-bold" type="submit"><i class="bi bi-funnel me-1"></i>Xem ngày</button>
            </form>
        </div>
    </div>

    <div class="production-kpis mb-3">
        <div class="production-kpi">
            <div class="production-kpi-label">Vịt/hàng nhập</div>
            <div class="production-kpi-value">{{ number_format((int) ($productionSummary['input_quantity'] ?? 0), 0, ',', '.') }} con</div>
            <div class="production-kpi-note">{{ format_kg($productionSummary['input_weight'] ?? 0) }} · {{ $productionSummary['receipt_count'] ?? 0 }} phiếu</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Thành phẩm sản xuất</div>
            <div class="production-kpi-value">{{ format_kg($productionSummary['finished_weight'] ?? 0) }}</div>
            <div class="production-kpi-note">{{ $productionSummary['production_batch_count'] ?? 0 }} mẻ · Hiệu suất {{ number_format($productionSummary['yield_percent'] ?? 0, 2, ',', '.') }}%</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Phụ phẩm thu hồi</div>
            <div class="production-kpi-value">{{ format_kg($productionSummary['component_weight'] ?? 0) }}</div>
            <div class="production-kpi-note">Đã ghi nhận từ các mẻ hoàn tất</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Hao hụt sản xuất</div>
            <div class="production-kpi-value text-danger">{{ format_kg($productionSummary['loss_weight'] ?? 0) }}</div>
            <div class="production-kpi-note">{{ number_format($productionSummary['loss_percent'] ?? 0, 2, ',', '.') }}% đầu vào</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Hàng loại khi nhập</div>
            <div class="production-kpi-value text-warning-emphasis">{{ format_kg($productionSummary['reject_weight'] ?? 0) }}</div>
            <div class="production-kpi-note">Theo phân loại thực nhận</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Chi phí nhập bình quân</div>
            <div class="production-kpi-value">{{ number_format($productionSummary['average_input_cost'] ?? 0, 0, ',', '.') }}đ/kg</div>
            <div class="production-kpi-note">Hao hụt ước tính {{ number_format($productionSummary['estimated_loss_cost'] ?? 0, 0, ',', '.') }}đ</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-success"></i>Sản lượng theo loại và size</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle production-table mb-0">
                        <thead><tr><th>Sản phẩm</th><th>Size</th><th class="text-end">Số mẻ</th><th class="text-end">Đầu vào</th><th class="text-end">Thành phẩm</th><th class="text-end">Hao hụt</th></tr></thead>
                        <tbody>
                            @forelse(($productionDashboard['production_by_variant'] ?? []) as $row)
                                <tr>
                                    <td class="fw-semibold">{{ trim($row['product_name'].' '.$row['variant_name']) }}</td>
                                    <td>{{ $row['size'] !== null ? number_format($row['size'], 1, ',', '.').' kg' : '—' }}</td>
                                    <td class="text-end">{{ $row['batch_count'] }}</td>
                                    <td class="text-end">{{ format_kg($row['input_weight']) }}</td>
                                    <td class="text-end fw-bold text-success">{{ format_kg($row['finished_weight']) }}</td>
                                    <td class="text-end text-danger">{{ format_kg($row['loss_weight']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có mẻ sản xuất hoàn tất trong ngày.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Thành phẩm 7 ngày gần nhất</div>
                <div class="card-body pt-1">
                    <div class="production-trend" aria-label="Biểu đồ sản lượng thành phẩm 7 ngày">
                        @foreach(($productionDashboard['trend'] ?? []) as $day)
                            @php
                                $barHeight = max(2, round(($day['finished_weight'] / ($productionDashboard['trend_max'] ?? 1)) * 105));
                            @endphp
                            <div class="production-trend-day" title="Thành phẩm {{ format_kg($day['finished_weight']) }}; hao hụt {{ format_kg($day['loss_weight']) }}">
                                <span class="production-trend-value">{{ number_format($day['finished_weight'], 0, ',', '.') }}</span>
                                <span class="production-trend-bar" style="height: {{ $barHeight }}px"></span>
                                <span class="production-trend-label">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-truck me-2 text-success"></i>Đầu vào theo loại</div>
                <div class="table-responsive"><table class="table table-sm production-table mb-0">
                    <thead><tr><th>Loại vịt/hàng</th><th class="text-end">Số lượng</th><th class="text-end">Khối lượng</th></tr></thead>
                    <tbody>
                    @forelse(($productionDashboard['input_types'] ?? []) as $row)
                        <tr><td class="fw-semibold">{{ $row['label'] }}</td><td class="text-end">{{ number_format($row['quantity'], 0, ',', '.') }}</td><td class="text-end">{{ format_kg($row['weight']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Chưa có phiếu nhập trong ngày.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-ui-checks-grid me-2 text-success"></i>Phân loại thực nhận</div>
                <div class="table-responsive"><table class="table table-sm production-table mb-0">
                    <thead><tr><th>Loại</th><th>Size</th><th class="text-end">Số lượng</th><th class="text-end">Kg</th></tr></thead>
                    <tbody>
                    @forelse(($productionDashboard['classifications'] ?? []) as $row)
                        <tr class="{{ $row['type'] === 'reject' ? 'table-warning' : '' }}"><td class="fw-semibold">{{ $row['type_label'] }}</td><td>{{ $row['size'] !== null ? number_format($row['size'], 1, ',', '.') : '—' }}</td><td class="text-end">{{ number_format($row['quantity'], 0, ',', '.') }}</td><td class="text-end">{{ number_format($row['weight'], 1, ',', '.') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu phân loại.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-cash-stack me-2 text-success"></i>Cơ cấu chi phí</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span>Giá trị hàng</span><strong>{{ number_format($productionSummary['purchase_cost'] ?? 0, 0, ',', '.') }}đ</strong></div>
                    @foreach(($productionDashboard['operating_costs'] ?? []) as $cost)
                        <div class="d-flex justify-content-between small py-1 border-top"><span>{{ $cost['label'] }}</span><strong>{{ number_format($cost['amount'], 0, ',', '.') }}đ</strong></div>
                    @endforeach
                    <div class="d-flex justify-content-between mt-2 pt-2 border-top border-2"><span class="fw-bold">Tổng chi phí nhập</span><strong class="text-success">{{ number_format($productionSummary['total_cost'] ?? 0, 0, ',', '.') }}đ</strong></div>
                    <div class="alert alert-warning small mt-3 mb-0 py-2"><i class="bi bi-exclamation-triangle me-1"></i>Giá trị hao hụt ước tính theo giá nhập bình quân: <strong>{{ number_format($productionSummary['estimated_loss_cost'] ?? 0, 0, ',', '.') }}đ</strong></div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
