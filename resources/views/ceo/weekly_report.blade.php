@extends('layouts.ceo')

@section('title', 'Báo cáo tuần')

@push('styles')
<style>
    .weekly-report-container{padding:4px 0 24px;color:#243449;font-size:14px}
    .weekly-report-container .container-fluid{padding:0}
    .weekly-report-container .report-header{padding:20px 24px;background:#fff;border:1px solid #dce4ee;border-left:4px solid #1769aa;margin-bottom:18px}
    .weekly-report-container .report-header h1{font-size:24px;font-weight:700;margin:0 0 8px}
    .weekly-report-container .report-header p{color:#68788d;font-size:13px;margin:0}
    .weekly-report-container .summary-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}
    .weekly-report-container .summary-card{padding:18px;background:white;border:1px solid #dce4ee;border-top:3px solid #1769aa}
    .weekly-report-container .card-title{font-size:12px;color:#61738a;font-weight:600;margin-bottom:10px}
    .weekly-report-container .card-value{font-size:23px;font-weight:700;margin-bottom:8px}
    .weekly-report-container .card-meta{font-size:12px;color:#68788d;line-height:1.6}
    .weekly-report-container .change,.weekly-report-container .change-pill{display:inline-flex;padding:3px 7px;font-size:12px;font-weight:600;white-space:nowrap}
    .weekly-report-container .up{background:#e5f5ed;color:#237448}.weekly-report-container .down{background:#fdecec;color:#b53737}.weekly-report-container .flat{background:#eef1f5;color:#586879}.weekly-report-container .new{background:#eaf2ff;color:#235ea5}
    .weekly-report-container .filter-card,.weekly-report-container .chart-card{background:#fff;padding:18px 20px;border:1px solid #dce4ee;margin-bottom:20px}
    .weekly-report-container .chart-card{height:340px}
    .weekly-report-container .form-control,.weekly-report-container .btn{font-size:13px}
    .weekly-report-container .week-shortcuts{border-top:1px solid #e5eaf1;padding-top:16px;margin-top:16px;display:flex;flex-wrap:wrap;gap:8px}
    .weekly-report-container .week-shortcuts .btn{border-radius:0;padding:9px 14px}
    .weekly-report-container .report-table{background:white;border:1px solid #dce4ee;border-radius:0;overflow-x:auto;margin-bottom:20px}
    .weekly-report-container .report-table table{width:100%;margin:0;border-collapse:collapse;font-size:14px}
    .weekly-report-container .report-table th{background:#edf3f9;padding:13px 14px;text-align:center;font-size:12px;font-weight:700;color:#405b78;border:1px solid #dce4ee;white-space:nowrap;text-transform:none;letter-spacing:0}
    .weekly-report-container .report-table th small{display:block;font-weight:400;color:#718198;margin-top:4px}
    .weekly-report-container .report-table td{padding:13px 14px;border:1px solid #e4eaf1;text-align:right;font-size:14px;font-variant-numeric:tabular-nums;white-space:nowrap}
    .weekly-report-container .report-table tbody tr:nth-child(even){background:#fafbfd}
    .weekly-report-container .report-table tbody tr:hover{background:#f1f7fc}
    .weekly-report-container .report-table td.product-name{white-space:normal;text-align:left;font-weight:600;min-width:240px;color:#243449}
    .weekly-report-container .report-table .total-row{background:#edf3f9;font-weight:700}
    .weekly-report-container .report-table .revenue-row{background:#e8f5f2;font-weight:700;color:#176c5b}
    .weekly-report-container .quantity-cell{font-family:inherit;font-weight:400}
    .weekly-report-container .zero-quantity{color:#98a5b5}
    .weekly-report-container .report-table .empty-state{color:#718198;padding:32px;text-align:center}
    .weekly-report-container .table-section-title{margin:24px 0 12px}
    .weekly-report-container .table-section-title h2{font-size:18px;font-weight:700;margin:0 0 5px}
    .weekly-report-container .table-section-title p{font-size:13px;color:#68788d;margin:0}
    @media(max-width:1100px){.weekly-report-container .summary-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:600px){.weekly-report-container .summary-cards{grid-template-columns:1fr}.weekly-report-container .report-header{padding:16px}.weekly-report-container .card-value{font-size:21px}}
</style>
@endpush

@section('content')
@php
    $formatMoney = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
    $formatNumber = fn ($value) => number_format((float) $value, 0, ',', '.');
    $changeClass = function ($percent, $current = null, $previous = null) {
        if ($percent === null) {
            return 'new';
        }
        if ((float) $percent > 0) {
            return 'up';
        }
        if ((float) $percent < 0) {
            return 'down';
        }

        return 'flat';
    };
    $changeLabel = function ($percent) {
        if ($percent === null) {
            return 'Mới';
        }

        $prefix = (float) $percent > 0 ? '+' : '';

        return $prefix . number_format((float) $percent, 1, ',', '.') . '%';
    };
    $chartPayload = $chartData ?? [
        'labels' => [],
        'revenue' => [],
        'previousRevenue' => [],
        'quantity' => [],
        'previousQuantity' => [],
    ];
@endphp
<div class="weekly-report-container">
    <div class="container-fluid">
        <div class="report-header">
            <h1><i class="bi bi-bar-chart-line"></i> Báo cáo tuần</h1>
            <p>Kỳ báo cáo: {{ $period['current_label'] ?? '' }} | Kỳ trước: {{ $period['previous_label'] ?? '' }}</p>
        </div>

        <div class="filter-card">
            <div class="row g-3">
                <div class="col-lg-6"><form method="GET" action="{{ route('ceo.weekly-report') }}" class="d-flex flex-wrap align-items-end gap-2">
                    <div><label for="month" class="form-label fw-semibold">Các tuần của tháng</label><input type="month" id="month" name="month" class="form-control" value="{{ $selectedMonth->format('Y-m') }}" required></div>
                    <input type="hidden" name="month_week" value="1"><button class="btn btn-primary">Xem tháng</button>
                </form></div>
                <div class="col-lg-6"><form method="GET" action="{{ route('ceo.weekly-report') }}" class="d-flex flex-wrap align-items-end gap-2">
                    <div><label for="week" class="form-label fw-semibold">Tuần lịch (Thứ 2 – Chủ nhật)</label><input type="date" id="week" name="week" class="form-control" value="{{ $period['selected'] }}" required></div>
                    <button class="btn btn-outline-primary">Xem tuần lịch</button><a href="{{ route('ceo.weekly-report') }}" class="btn btn-outline-secondary">Tuần hiện tại</a>
                </form></div>
            </div>
            <nav class="week-shortcuts" aria-label="Chọn tuần trong tháng">
                @foreach($monthWeeks as $monthWeek)<a href="{{ route('ceo.weekly-report',['month'=>$selectedMonth->format('Y-m'),'month_week'=>$monthWeek['number']]) }}" class="btn {{ $selectedMonthWeek===$monthWeek['number']?'btn-primary':'btn-outline-secondary' }}" @if($selectedMonthWeek===$monthWeek['number']) aria-current="page" @endif>{{ $monthWeek['label'] }}</a>@endforeach
            </nav>
            <div class="small text-muted mt-2">Tuần trong tháng tính từ ngày 1, mỗi kỳ tối đa 7 ngày. Kỳ trước là cùng khoảng ngày lùi 7 ngày.</div>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-title">Tổng doanh thu tuần</div>
                <div class="card-value">{{ $formatMoney($summary['revenue']['current'] ?? 0) }}</div>
                <div class="card-meta">
                    Kỳ trước: {{ $formatMoney($summary['revenue']['previous'] ?? 0) }}
                    <span class="change {{ $changeClass($summary['revenue']['change_percent'] ?? 0) }}">
                        {{ $changeLabel($summary['revenue']['change_percent'] ?? 0) }}
                    </span>
                </div>
            </div>
            <div class="summary-card">
                <div class="card-title">Tổng số lượng sản phẩm</div>
                <div class="card-value">{{ $formatNumber($summary['quantity']['current'] ?? 0) }}</div>
                <div class="card-meta">
                    Kỳ trước: {{ $formatNumber($summary['quantity']['previous'] ?? 0) }}
                    <span class="change {{ $changeClass($summary['quantity']['change_percent'] ?? 0) }}">
                        {{ $changeLabel($summary['quantity']['change_percent'] ?? 0) }}
                    </span>
                </div>
            </div>
            <div class="summary-card">
                <div class="card-title">Số đơn hoàn tất</div>
                <div class="card-value">{{ $formatNumber($summary['orders']['current'] ?? 0) }}</div>
                <div class="card-meta">
                    Kỳ trước: {{ $formatNumber($summary['orders']['previous'] ?? 0) }}
                    <span class="change {{ $changeClass($summary['orders']['change_percent'] ?? 0) }}">
                        {{ $changeLabel($summary['orders']['change_percent'] ?? 0) }}
                    </span>
                </div>
            </div>
            <div class="summary-card">
                <div class="card-title">Giá trị đơn trung bình</div>
                <div class="card-value">{{ $formatMoney($summary['average_order_value']['current'] ?? 0) }}</div>
                <div class="card-meta">
                    Kỳ trước: {{ $formatMoney($summary['average_order_value']['previous'] ?? 0) }}
                    <span class="change {{ $changeClass($summary['average_order_value']['change_percent'] ?? 0) }}">
                        {{ $changeLabel($summary['average_order_value']['change_percent'] ?? 0) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="chart-card">
            <canvas id="weeklyReportChart" height="110"></canvas>
        </div>

        <div class="table-section-title"><h2>Sản lượng và doanh thu theo ngày</h2><p>Đơn đã giao / hoàn tất trong kỳ được chọn, so sánh với kỳ trước.</p></div>
        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th style=" width: 200px;" class="text-start">Mặt hàng</th>
                        @foreach($days as $day)<th>{{ $day }}<small>{{ $dayDates[$day] }}</small></th>@endforeach
                        <th>Tổng</th>
                        <th>Kỳ trước</th>
                        <th>Biến động</th>
                        <th>Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $dayTotals = array_fill_keys($days, 0);
                    @endphp

                    @if(isset($weeklyData) && is_array($weeklyData) && count($weeklyData) > 0)
                        @foreach($weeklyData as $productName => $productData)
                            <tr>
                                <td class="product-name">{{ $productName }}</td>
                                @foreach($days as $day)
                                    @php
                                        $quantity = $productData[$day] ?? 0;
                                        $dayTotals[$day] += $quantity;
                                    @endphp
                                    <td class=" text-center quantity-cell {{ $quantity == 0 ? 'zero-quantity' : '' }}">
                                        {{ number_format($quantity, 0, ',', '.') }}
                                    </td>
                                @endforeach
                                <td class="quantity-cell text-center" style="font-weight: 600;">
                                    {{ number_format($productData['total'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="quantity-cell text-center">
                                    {{ number_format($productData['previous_total'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="change-pill {{ $changeClass($productData['change_percent'] ?? 0) }}">
                                        {{ $changeLabel($productData['change_percent'] ?? 0) }}
                                    </span>
                                </td>
                                <td class="quantity-cell text-center">
                                    {{ $formatMoney($productData['revenue'] ?? 0) }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="{{ count($days)+5 }}" class="empty-state">
                                Không có dữ liệu đơn hoàn tất trong tuần đã chọn.
                            </td>
                        </tr>
                    @endif

                    <!-- Dòng tổng số lượng -->
                    <tr class="total-row">
                        <td class="product-name" style="font-weight: 700;">Tổng số lượng</td>
                        @foreach($days as $day)
                            <td class="quantity-cell  text-center">
                                {{ number_format($dayTotals[$day], 0, ',', '.') }}
                            </td>
                        @endforeach
                        <td class="quantity-cell  text-center">
                            {{ number_format(array_sum($dayTotals), 0, ',', '.') }}
                        </td>
                        <td class="quantity-cell text-center">
                            {{ number_format($summary['quantity']['previous'] ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            <span class="change-pill {{ $changeClass($summary['quantity']['change_percent'] ?? 0) }}">
                                {{ $changeLabel($summary['quantity']['change_percent'] ?? 0) }}
                            </span>
                        </td>
                        <td class="quantity-cell text-center">
                            {{ $formatMoney($summary['revenue']['current'] ?? 0) }}
                        </td>
                    </tr>

                    <!-- Dòng tổng doanh thu theo ngày -->
                    <tr class="revenue-row">
                        <td class="product-name" style="font-weight: 700;">Doanh thu ngày</td>
                        @if(isset($dailyRevenue) && is_array($dailyRevenue))
                            @foreach($days as $day)
                                <td class="quantity-cell text-center" style="font-weight: 600; color: #17a2b8;">
                                    {{ number_format($dailyRevenue[$day] ?? 0, 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="quantity-cell  text-center" style="font-weight: 700; color: #17a2b8; font-size: 14px;">
                                {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="quantity-cell text-center" style="font-weight: 600; color: #17a2b8;">
                                {{ number_format($summary['revenue']['previous'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="change-pill {{ $changeClass($summary['revenue']['change_percent'] ?? 0) }}">
                                    {{ $changeLabel($summary['revenue']['change_percent'] ?? 0) }}
                                </span>
                            </td>
                            <td class="quantity-cell text-center" style="font-weight: 700; color: #17a2b8;">
                                {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                            </td>
                        @else
                            <td colspan="{{ count($days)+4 }}" style="text-align: center; font-size: 14px;">
                                {{ number_format($totalRevenue ?? 0, 0, ',', '.') }} VNĐ
                            </td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-section-title">
            <h2>Thống kê số lượng biến thể theo ngày</h2>
            <p>Sắp xếp theo tổng số lượng trong tuần giảm dần để hỗ trợ dự báo nhu cầu tuần tiếp theo.</p>
        </div>

        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 260px;" class="text-start">Biến thể sản phẩm</th>
                        @foreach($days as $day)<th>{{ $day }}<small>{{ $dayDates[$day] }}</small></th>@endforeach
                        <th>Tổng tuần</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $variantDayTotals = array_fill_keys($days, 0);
                    @endphp

                    @if(isset($variantWeeklyData) && is_array($variantWeeklyData) && count($variantWeeklyData) > 0)
                        @foreach($variantWeeklyData as $variantName => $variantData)
                            <tr>
                                <td class="product-name">{{ $variantName }}</td>
                                @foreach($days as $day)
                                    @php
                                        $quantity = $variantData[$day] ?? 0;
                                        $variantDayTotals[$day] += $quantity;
                                    @endphp
                                    <td class="text-center quantity-cell {{ $quantity == 0 ? 'zero-quantity' : '' }}">
                                        {{ number_format($quantity, 0, ',', '.') }}
                                    </td>
                                @endforeach
                                <td class="text-center quantity-cell" style="font-weight: 700;">
                                    {{ number_format($variantData['total'] ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="{{ count($days)+2 }}" class="empty-state">
                                Không có dữ liệu biến thể trong tuần đã chọn.
                            </td>
                        </tr>
                    @endif

                    <tr class="total-row">
                        <td class="product-name" style="font-weight: 700;">Tổng số lượng</td>
                        @foreach($days as $day)
                            <td class="text-center quantity-cell">
                                {{ number_format($variantDayTotals[$day], 0, ',', '.') }}
                            </td>
                        @endforeach
                        <td class="text-center quantity-cell">
                            {{ number_format(array_sum($variantDayTotals), 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('weeklyReportChart');
        if (!el || typeof Chart === 'undefined') {
            return;
        }

        const chartData = @json($chartPayload);

        new Chart(el, {
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Doanh thu tuần hiện tại',
                        data: chartData.revenue,
                        backgroundColor: 'rgba(102, 126, 234, 0.75)',
                        borderRadius: 6,
                        yAxisID: 'money',
                    },
                    {
                        type: 'line',
                        label: 'Doanh thu kỳ trước',
                        data: chartData.previousRevenue,
                        borderColor: '#94a3b8',
                        backgroundColor: '#94a3b8',
                        borderDash: [6, 4],
                        tension: 0.25,
                        yAxisID: 'money',
                    },
                    {
                        type: 'line',
                        label: 'Số lượng tuần hiện tại',
                        data: chartData.quantity,
                        borderColor: '#16a34a',
                        backgroundColor: '#16a34a',
                        tension: 0.25,
                        yAxisID: 'quantity',
                    },
                    {
                        type: 'line',
                        label: 'Số lượng kỳ trước',
                        data: chartData.previousQuantity,
                        borderColor: '#f97316',
                        backgroundColor: '#f97316',
                        borderDash: [6, 4],
                        tension: 0.25,
                        yAxisID: 'quantity',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = Number(context.raw || 0).toLocaleString('vi-VN');
                                return context.dataset.yAxisID === 'money'
                                    ? `${context.dataset.label}: ${value} đ`
                                    : `${context.dataset.label}: ${value}`;
                            },
                        },
                    },
                },
                scales: {
                    money: {
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            callback: value => Number(value || 0).toLocaleString('vi-VN') + ' đ',
                        },
                    },
                    quantity: {
                        type: 'linear',
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: value => Number(value || 0).toLocaleString('vi-VN'),
                        },
                    },
                },
            },
        });
    });
</script>
@endpush
