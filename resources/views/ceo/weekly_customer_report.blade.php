@extends('layouts.ceo')

@section('title', 'Báo cáo tuần - Khách hàng')

@push('styles')
<style>
    .weekly-report-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding: 20px 0;
    }
    .report-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        border-left: 5px solid #28a745;
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
        margin-bottom: 0;
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
    .customer-name {
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
    .revenue-cell {
        font-family: 'Courier New', monospace;
        font-weight: 500;
    }
    .zero-revenue {
        color: #6c757d;
        opacity: 0.6;
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
<div class="weekly-report-container">
    <div class="container-fluid">
        <div class="report-header">
            <h1><i class="bi bi-people-fill"></i> Báo cáo tuần - Khách hàng</h1>
            <p>Theo dõi doanh thu khách hàng theo ngày trong tuần</p>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-title">Tổng doanh thu tuần</div>
                <div class="card-value">{{ number_format($totalRevenue ?? 0, 0, ',', '.') }} đ</div>
            </div>
            <div class="summary-card">
                <div class="card-title">Số khách hàng</div>
                <div class="card-value">{{ number_format($totalCustomers ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="card-title">Doanh thu trung bình/ngày</div>
                <div class="card-value">{{ number_format($avgDailyRevenue ?? 0, 0, ',', '.') }} đ</div>
            </div>
        </div>

        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left; width: 250px;">Khách hàng</th>
                        <th>T2</th>
                        <th>T3</th>
                        <th>T4</th>
                        <th>T5</th>
                        <th>T6</th>
                        <th>T7</th>
                        <th>CN</th>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
                        $dayTotals = array_fill_keys($days, 0);
                    @endphp

                    @if(isset($customerWeeklyData) && is_array($customerWeeklyData))
                        @foreach($customerWeeklyData as $customerName => $customerData)
                            <tr>
                                <td class="customer-name">{{ $customerName }}</td>
                                @foreach($days as $day)
                                    @php
                                        $revenue = $customerData[$day] ?? 0;
                                        $dayTotals[$day] += $revenue;
                                    @endphp
                                    <td class="revenue-cell {{ $revenue == 0 ? 'zero-revenue' : '' }}">
                                        {{ number_format($revenue, 0, ',', '.') }}
                                    </td>
                                @endforeach
                                <td class="revenue-cell" style="font-weight: 600;">
                                    {{ number_format($customerData['total'] ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    <!-- Dòng tổng doanh thu theo ngày -->
                    <tr class="revenue-row">
                        <td class="customer-name" style="font-weight: 700;">Tổng doanh thu theo ngày</td>
                        @if(isset($dailyRevenue) && is_array($dailyRevenue))
                            @foreach($days as $day)
                                <td class="revenue-cell" style="font-weight: 600; color: #17a2b8;">
                                    {{ number_format($dailyRevenue[$day] ?? 0, 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="revenue-cell" style="font-weight: 700; color: #17a2b8; font-size: 1.1rem;">
                                {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}
                            </td>
                        @else
                            <td colspan="8" style="text-align: center; font-size: 1.2rem;">
                                {{ number_format($totalRevenue ?? 0, 0, ',', '.') }} VNĐ
                            </td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection