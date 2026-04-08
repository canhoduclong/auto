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
            <h1><i class="bi bi-bar-chart-line"></i> Báo cáo tuần</h1>
            <p>Theo dõi hiệu suất bán hàng theo ngày trong tuần</p>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-title">Tổng doanh thu tuần</div>
                <div class="card-value">{{ number_format($totalRevenue ?? 0, 0, ',', '.') }} đ</div>
            </div>
            <div class="summary-card">
                <div class="card-title">Tổng số lượng sản phẩm</div>
                <div class="card-value">{{ number_format($totalQuantity ?? 0, 0, ',', '.') }}</div>
            </div>
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
                    </tr>
                </thead>
                <tbody>
                    @php
                        $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
                        $dayTotals = array_fill_keys($days, 0);
                    @endphp

                    @if(isset($weeklyData) && is_array($weeklyData))
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
                            </tr>
                        @endforeach
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