@extends('layouts.ceo')

@section('title', 'CEO Dashboard')
@section('subtitle', 'Tổng quan điều hành doanh nghiệp')

@push('styles')
<style>
    .ceo-grid { display: grid; gap: 14px; }
    .ceo-kpi { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .ceo-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }
    .ceo-card .label { color: #64748b; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
    .ceo-card .value { font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-top: 8px; }
    .ceo-two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .ceo-chart-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }
    .ceo-filter-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
        align-items: end;
    }
    .ceo-filter-grid .form-select,
    .ceo-filter-grid .form-control {
        min-height: 40px;
    }
    .ceo-chart-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
    }
    .ceo-chart-summary .item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px;
        background: #f8fafc;
    }
    .ceo-chart-summary .label {
        color: #64748b;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .ceo-chart-summary .value {
        color: #0f172a;
        font-size: 1.1rem;
        font-weight: 800;
        margin-top: 4px;
    }
    .ceo-table { margin-bottom: 0; }
    .ceo-table th { font-size: .75rem; color: #64748b; text-transform: uppercase; }
    .ceo-alert { border-left: 4px solid transparent; }
    .ceo-alert.high { border-left-color: #ef4444; }
    .ceo-alert.medium { border-left-color: #f59e0b; }
    .ceo-alert.low { border-left-color: #0ea5e9; }
    .ceo-header-line {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    @media (max-width: 1200px) {
        .ceo-kpi { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ceo-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 900px) {
        .ceo-two { grid-template-columns: 1fr; }
        .ceo-chart-summary { grid-template-columns: 1fr; }
    }
    @media (max-width: 576px) { .ceo-kpi { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="ceo-card mb-3">
    <form method="GET" action="{{ route('ceo.dashboard') }}" class="ceo-filter-grid">
        <div>
            <label class="form-label mb-1">Chu kỳ</label>
            <select class="form-select" name="range">
                <option value="day" {{ request('range', 'month') === 'day' ? 'selected' : '' }}>Theo ngày</option>
                <option value="week" {{ request('range') === 'week' ? 'selected' : '' }}>Theo tuần</option>
                <option value="month" {{ request('range', 'month') === 'month' ? 'selected' : '' }}>Theo tháng</option>
                <option value="year" {{ request('range') === 'year' ? 'selected' : '' }}>Theo năm</option>
                <option value="custom" {{ request('range') === 'custom' ? 'selected' : '' }}>Từ ngày - đến ngày</option>
            </select>
        </div>
        <div>
            <label class="form-label mb-1">Từ ngày</label>
            <input type="date" name="from_date" class="form-control" value="{{ request('from_date', $from->format('Y-m-d')) }}">
        </div>
        <div>
            <label class="form-label mb-1">Đến ngày</label>
            <input type="date" name="to_date" class="form-control" value="{{ request('to_date', $to->format('Y-m-d')) }}">
        </div>
        <div>
            <label class="form-label mb-1">Nhóm dữ liệu</label>
            <select class="form-select" name="group_by">
                <option value="day" {{ ($groupBy ?? 'week') === 'day' ? 'selected' : '' }}>Theo ngày</option>
                <option value="week" {{ ($groupBy ?? 'week') === 'week' ? 'selected' : '' }}>Theo tuần</option>
                <option value="month" {{ ($groupBy ?? 'week') === 'month' ? 'selected' : '' }}>Theo tháng</option>
                <option value="quarter" {{ ($groupBy ?? 'week') === 'quarter' ? 'selected' : '' }}>Theo quý</option>
                <option value="year" {{ ($groupBy ?? 'week') === 'year' ? 'selected' : '' }}>Theo năm</option>
            </select>
        </div>
        <div>
            <label class="form-label mb-1 d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">Xem biểu đồ</button>
        </div>
    </form>
</div>

<div class="ceo-header-line">
    <span class="badge text-bg-light border">{{ $rangeLabel }}</span>
    <span class="badge text-bg-light border">{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</span>
</div>

<div class="ceo-grid">
    <div class="ceo-chart-card">
        <h6 class="mb-2">Biểu đồ thay đổi giá bán và sản lượng</h6>
        <div class="small text-muted mb-3">Biểu đồ cột theo từng mốc thay đổi giá trong khoảng thời gian đã chọn (sản lượng được đồng bộ theo cùng mốc).</div>
        <canvas id="ceoPriceVolumeChart" height="100"></canvas>

        <div class="ceo-chart-summary">
            <div class="item">
                <div class="label">Lần thay đổi giá</div>
                <div class="value">{{ number_format($trend['summary']['total_price_changes'] ?? 0) }}</div>
            </div>
            <div class="item">
                <div class="label">Tổng sản lượng</div>
                <div class="value">{{ number_format($trend['summary']['total_quantity'] ?? 0) }}</div>
            </div>
            <div class="item">
                <div class="label">Giá bán TB</div>
                <div class="value">{{ number_format((float) ($trend['summary']['avg_price_whole_period'] ?? 0), 0, ',', '.') }} đ</div>
            </div>
        </div>
    </div>

    <div class="ceo-kpi">
        <div class="ceo-card">
            <div class="label">Doanh thu thuần</div>
            <div class="value">{{ number_format($overview['net_revenue']) }} đ</div>
        </div>
        <div class="ceo-card">
            <div class="label">Tổng đơn hàng</div>
            <div class="value">{{ number_format($overview['total_orders']) }}</div>
        </div>
        <div class="ceo-card">
            <div class="label">Tỷ lệ hoàn tất</div>
            <div class="value">{{ number_format($overview['completion_rate'], 1) }}%</div>
        </div>
        <div class="ceo-card">
            <div class="label">Công nợ</div>
            <div class="value">{{ number_format($overview['debt_total']) }} đ</div>
        </div>
    </div>

    <div class="ceo-two">
        <div class="ceo-card">
            <h6 class="mb-3">Top Sale</h6>
            <div class="table-responsive">
                <table class="table ceo-table table-sm align-middle">
                    <thead><tr><th>Nhân sự</th><th class="text-end">Đơn</th><th class="text-end">Doanh số</th></tr></thead>
                    <tbody>
                    @forelse($salesTop as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format($row->total_orders) }}</td>
                            <td class="text-end">{{ number_format($row->total_amount) }} đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Không có dữ liệu</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ceo-card">
            <h6 class="mb-3">Top Khách Hàng</h6>
            <div class="table-responsive">
                <table class="table ceo-table table-sm align-middle">
                    <thead><tr><th>Khách hàng</th><th class="text-end">Đơn</th><th class="text-end">Doanh số</th></tr></thead>
                    <tbody>
                    @forelse($customerTop as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format($row->total_orders) }}</td>
                            <td class="text-end">{{ number_format($row->total_amount) }} đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Không có dữ liệu</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ceo-two">
        <div class="ceo-card">
            <h6 class="mb-3">Hiệu suất Shipper</h6>
            <div class="table-responsive">
                <table class="table ceo-table table-sm align-middle">
                    <thead><tr><th>Shipper</th><th class="text-end">Đơn</th><th class="text-end">Tỷ lệ thành công</th></tr></thead>
                    <tbody>
                    @forelse($shipperTop as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format($row->total_orders) }}</td>
                            <td class="text-end">{{ number_format($row->success_rate, 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Không có dữ liệu</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ceo-card">
            <h6 class="mb-3">Cảnh báo cần xử lý</h6>
            <div class="d-grid gap-2">
                @foreach($alerts as $alert)
                    <div class="ceo-alert {{ $alert['level'] }} p-2 rounded border bg-light">
                        <div class="fw-semibold">{{ $alert['title'] }}</div>
                        <div class="small text-muted">{{ $alert['description'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const el = document.getElementById('ceoPriceVolumeChart');
        if (!el || typeof Chart === 'undefined') {
            return;
        }

        const labels = @json($trend['labels'] ?? []);
        const avgPrices = @json($trend['avg_prices'] ?? []);
        const quantities = @json($trend['quantities'] ?? []);
        new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Giá bán trung bình',
                        data: avgPrices,
                        yAxisID: 'yPrice',
                        backgroundColor: 'rgba(14, 165, 233, 0.65)',
                        borderColor: '#0ea5e9',
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 30,
                    },
                    {
                        label: 'Sản lượng',
                        data: quantities,
                        yAxisID: 'yQty',
                        backgroundColor: 'rgba(20, 184, 166, 0.55)',
                        borderColor: '#14b8a6',
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 30,
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    yPrice: {
                        type: 'linear',
                        position: 'left',
                        title: { display: true, text: 'Giá bán (đ)' },
                        ticks: {
                            callback: function (value) {
                                return Number(value).toLocaleString('vi-VN');
                            }
                        }
                    },
                    yQty: {
                        type: 'linear',
                        position: 'right',
                        title: { display: true, text: 'Sản lượng / số lần đổi giá' },
                        beginAtZero: true,
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    })();
</script>
@endpush
