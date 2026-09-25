@extends('layouts.ceo')

@section('title', 'Báo cáo tuần')

@push('styles')
<style>
    .weekly-report-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 20px 0;
    }
    .report-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .report-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .report-header p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 0;
    }
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-left: 5px solid #667eea;
        transition: transform 0.2s ease;
    }
    .summary-card:hover {
        transform: translateY(-2px);
    }
    .summary-card .card-title {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        margin-bottom: 10px;
    }
    .summary-card .card-value {
        font-size: 2rem;
        font-weight: 700;
        color: #343a40;
        margin-bottom: 8px;
    }
    .summary-card .card-meta {
        color: #64748b;
        font-size: .85rem;
        line-height: 1.5;
    }
    .summary-card .change {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border-radius: 999px;
        padding: 2px 8px;
        font-size: .78rem;
        font-weight: 700;
    }
    .summary-card .change.up,
    .change-pill.up {
        background: #dcfce7;
        color: #166534;
    }
    .summary-card .change.down,
    .change-pill.down {
        background: #fee2e2;
        color: #991b1b;
    }
    .summary-card .change.flat,
    .change-pill.flat {
        background: #e2e8f0;
        color: #334155;
    }
    .summary-card .change.new,
    .change-pill.new {
        background: #dbeafe;
        color: #1e40af;
    }
    .filter-card,
    .chart-card {
        background: white;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    .chart-card {
        min-height: 340px;
    }
    .report-table {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .report-table table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }
    .report-table th {
        background: #f8f9fa;
        padding: 15px 12px;
        text-align: center;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .report-table td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
        text-align: right;
        font-size: 0.9rem;
    }
    .report-table tbody tr:hover {
        background: #f8f9fa;
    }
    .product-name {
        text-align: left;
        font-weight: 500;
        color: #343a40;
    }
    .total-row {
        background: #fff3cd;
        font-weight: 700;
        color: #856404;
    }
    .total-row td {
        border-top: 2px solid #ffc107;
    }
    .revenue-row {
        background: #d1ecf1;
        font-weight: 700;
        color: #0c5460;
    }
    .revenue-row td {
        border-top: 2px solid #17a2b8;
    }
    .quantity-cell {
        font-family: 'Courier New', monospace;
        font-weight: 500;
    }
    .zero-quantity {
        color: #6c757d;
        opacity: 0.6;
    }
    .change-pill {
        display: inline-flex;
        border-radius: 999px;
        padding: 3px 9px;
        font-size: .78rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .empty-state {
        color: #64748b;
        padding: 28px 12px;
        text-align: center;
    }
    .table-section-title {
        margin: 28px 0 12px;
    }
    .table-section-title h2 {
        color: #1f2937;
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0 0 4px;
    }
    .table-section-title p {
        color: #64748b;
        margin: 0;
    }
    @media (max-width: 768px) {
        .report-header {
            padding: 20px;
        }
        .report-header h1 {
            font-size: 2rem;
        }
        .summary-cards {
            grid-template-columns: 1fr;
        }
        .report-table {
            font-size: 0.8rem;
        }
        .report-table th,
        .report-table td {
            padding: 8px 6px;
        }
    }
</style>
@endpush

@section('content')
@php
    $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
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
            <p>Tuần hiện tại: {{ $period['current_label'] ?? '' }} | Kỳ trước: {{ $period['previous_label'] ?? '' }}</p>
        </div>

        <div class="filter-card">
            <form method="GET" action="{{ route('ceo.weekly-report') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="week" class="form-label fw-semibold">Chọn tuần</label>
                    <input type="date" id="week" name="week" class="form-control" value="{{ $period['selected'] ?? now()->toDateString() }}">
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Xem báo cáo</button>
                    <a href="{{ route('ceo.weekly-report') }}" class="btn btn-light border">Tuần hiện tại</a>
                </div>
            </form>
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

        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th style=" width: 200px;" class="text-end">Mặt hàng</th>
                        <th>T2</th>
                        <th>T3</th>
                        <th>T4</th>
                        <th>T5</th>
                        <th>T6</th>
                        <th>T7</th>
                        <th>CN</th>
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
                            <td colspan="12" class="empty-state">
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
                            <td class="quantity-cell  text-center" style="font-weight: 700; color: #17a2b8; font-size: 1.1rem;">
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
                            <td colspan="11" style="text-align: center; font-size: 1.2rem;">
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
                        <th style="width: 260px;" class="text-end">Biến thể sản phẩm</th>
                        <th>T2</th>
                        <th>T3</th>
                        <th>T4</th>
                        <th>T5</th>
                        <th>T6</th>
                        <th>T7</th>
                        <th>CN</th>
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
                            <td colspan="9" class="empty-state">
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
