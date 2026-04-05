@extends('layouts.warehouse')

@section('title', 'Báo Cáo & Thống Kê')

@section('content')
<style>
    .wh-page-header {
        background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        color: white;
        padding: 2rem 1.5rem;
        margin-bottom: 2rem;
        border-radius: .75rem;
    }
    .wh-page-header h1 { font-size: 1.875rem; font-weight: 700; margin: 0; }
    .wh-page-header p { margin: 0.25rem 0 0; opacity: 0.95; }
    
    .wh-filter-group {
        background: white;
        padding: 1.5rem;
        border-radius: .75rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
    }
    
    .wh-stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: .75rem;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        transition: transform .2s;
    }
    .wh-stat-card:hover { transform: translateY(-2px); }
    .wh-stat-value { font-size: 2rem; font-weight: 700; color: #30cfd0; }
    .wh-stat-label { font-size: .875rem; color: #6c757d; margin-top: .5rem; }
    
    .wh-chart-card {
        background: white;
        padding: 1.5rem;
        border-radius: .75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        margin-bottom: 1.5rem;
    }
    
    .wh-chart-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #212529;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .wh-table-card {
        background: white;
        border-radius: .75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        overflow: hidden;
    }
    
    .wh-table-card table { margin: 0; }
    .wh-table-card thead {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    .wh-table-card th {
        padding: 1rem 0.75rem;
        font-weight: 600;
        font-size: .875rem;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .wh-table-card td {
        padding: .875rem 0.75rem;
        border-bottom: 1px solid #e9ecef;
    }
    .wh-table-card tbody tr:hover { background: #f8f9fa; }
    
    .wh-stat-row {
        display: flex;
        justify-content: space-between;
        padding: .75rem 0;
        border-bottom: 1px solid #e9ecef;
    }
    .wh-stat-row:last-child { border-bottom: none; }
    
    .wh-stat-label-text {
        color: #6c757d;
        font-size: .875rem;
    }
    .wh-stat-value-text {
        font-weight: 700;
        font-size: 1.1rem;
        color: #30cfd0;
    }
</style>

<div class="wh-page-header">
    <h1><i class="bi bi-graph-up"></i> Báo Cáo & Thống Kê</h1>
    <p>Phân tích chi tiết hoạt động kho theo thời gian</p>
</div>

<!-- Filter Panel -->
<div class="wh-filter-group">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-600">Loại Kỳ Báo Cáo</label>
            <select name="range_type" class="form-select" onchange="this.form.submit();">
                <option value="day" {{ $rangeType === 'day' ? 'selected' : '' }}>Hôm nay (Ngày)</option>
                <option value="week" {{ $rangeType === 'week' ? 'selected' : '' }}>Tuần này</option>
                <option value="month" {{ $rangeType === 'month' ? 'selected' : '' }}>Tháng này</option>
                <option value="year" {{ $rangeType === 'year' ? 'selected' : '' }}>Năm này</option>
                <option value="custom" {{ $rangeType === 'custom' ? 'selected' : '' }}>Tùy chỉnh</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-600">
                @if($rangeType === 'day')
                    Chọn Ngày
                @elseif($rangeType === 'week')
                    Chọn Tuần
                @elseif($rangeType === 'month')
                    Chọn Tháng
                @elseif($rangeType === 'year')
                    Chọn Năm
                @else
                    Từ Ngày
                @endif
            </label>
            <input type="date" name="selected_date" class="form-control" value="{{ $selectedDate }}" onchange="this.form.submit();">
        </div>
        <div class="col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-arrow-repeat"></i> Cập Nhật
            </button>
            <a href="{{ route('warehouse.reports') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-clockwise"></i> Làm Mới
            </a>
        </div>
    </form>
</div>

<!-- Date Range Display -->
<div class="alert alert-info mb-3" role="alert">
    <i class="bi bi-calendar-event"></i>
    <strong>Kỳ báo cáo:</strong>
    {{ $from->format('d/m/Y') }} đến {{ $to->format('d/m/Y') }}
</div>

<!-- Overall Statistics -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="wh-stat-card">
            <div class="wh-stat-value">{{ number_format($totals['total_stock_in']) }}</div>
            <div class="wh-stat-label">Tổng Nhập Kho</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="wh-stat-card">
            <div class="wh-stat-value">{{ number_format($totals['total_stock_out']) }}</div>
            <div class="wh-stat-label">Tổng Xuất Kho</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="wh-stat-card">
            <div class="wh-stat-value">{{ number_format($totals['total_movements']) }}</div>
            <div class="wh-stat-label">Tổng Giao Dịch</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- Stock In Analysis -->
    <div class="col-md-6">
        <div class="wh-chart-card">
            <div class="wh-chart-title"><i class="bi bi-box-seam"></i> Nhập Kho</div>
            @if($stockInData->count() > 0)
                @foreach($stockInData as $period => $data)
                    <div class="wh-stat-row">
                        <span class="wh-stat-label-text">{{ $period }}: {{ $data['count'] }} phiếu</span>
                        <span class="wh-stat-value-text">{{ number_format($data['quantity']) }} sản phẩm</span>
                    </div>
                @endforeach
            @else
                <p class="text-muted text-center py-3">Không có dữ liệu nhập kho</p>
            @endif
        </div>
    </div>
    
    <!-- Stock Out Analysis -->
    <div class="col-md-6">
        <div class="wh-chart-card">
            <div class="wh-chart-title"><i class="bi bi-box-arrow-right"></i> Xuất Kho</div>
            @if($stockOutData->count() > 0)
                @foreach($stockOutData as $period => $data)
                    <div class="wh-stat-row">
                        <span class="wh-stat-label-text">{{ $period }}: {{ $data['count'] }} phiếu</span>
                        <span class="wh-stat-value-text">{{ number_format($data['quantity']) }} sản phẩm</span>
                    </div>
                @endforeach
            @else
                <p class="text-muted text-center py-3">Không có dữ liệu xuất kho</p>
            @endif
        </div>
    </div>
</div>

<!-- Inventory Movement Analysis -->
<div class="wh-chart-card">
    <div class="wh-chart-title"><i class="bi bi-arrow-left-right"></i> Phân Tích Giao Dịch</div>
    @if($movementData->count() > 0)
        @foreach($movementData as $type => $data)
            <div class="wh-stat-row">
                <span class="wh-stat-label-text">
                    @if($type === 'in')
                        <i class="bi bi-arrow-down-circle"></i> Giao dịch Nhập
                    @elseif($type === 'out')
                        <i class="bi bi-arrow-up-circle"></i> Giao dịch Xuất
                    @else
                        <i class="bi bi-arrow-left-right"></i> {{ ucfirst($type) }}
                    @endif
                    : {{ $data['count'] }} giao dịch
                </span>
                <span class="wh-stat-value-text">{{ number_format($data['quantity']) }} sản phẩm</span>
            </div>
        @endforeach
    @else
        <p class="text-muted text-center py-3">Không có dữ liệu giao dịch</p>
    @endif
</div>

<!-- Top Products -->
@if($topProducts->count() > 0)
    <div class="wh-table-card">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th colspan="2">
                        <i class="bi bi-star"></i> Top Sản Phẩm Phát Sinh Tối Đa
                    </th>
                        <th class="text-center">ĐVT</th>
                    <th class="text-center">Số Lượng</th>
                    <th class="text-center">Số Giao Dịch</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $item)
                    <tr>
                        <td style="width: 40px;">
                            <strong>{{ $loop->iteration }}</strong>
                        </td>
                        <td>
                            <div>
                                <strong>{{ $item['product']->name }}</strong>
                                <br>
                                <small class="text-muted">{{ $item['variant']->name }}</small>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark">{{ $item['product']->unit_label ?? 'Cái' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ number_format($item['quantity']) }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info">{{ $item['count'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="row g-3 mt-1">
    <div class="col-12 col-xl-6">
        <div class="wh-table-card h-100">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th colspan="2"><i class="bi bi-box"></i> Thống Kê Theo Sản Phẩm</th>
                        <th class="text-center">ĐVT</th>
                        <th class="text-center">Nhập</th>
                        <th class="text-center">Xuất</th>
                        <th class="text-center">Chênh lệch</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productStats as $item)
                        <tr>
                            <td style="width: 40px;"><strong>{{ $loop->iteration }}</strong></td>
                            <td>
                                <div>
                                    <strong>{{ $item['product_name'] }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        SKU: {{ $item['product_sku'] ?? '-' }} | {{ $item['variant_count'] }} biến thể
                                    </small>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark">{{ $item['unit_label'] ?? 'Cái' }}</span></td>
                            <td class="text-center"><span class="badge bg-success">{{ number_format($item['in_qty']) }}</span></td>
                            <td class="text-center"><span class="badge bg-danger">{{ number_format($item['out_qty']) }}</span></td>
                            <td class="text-center">
                                <span class="badge {{ $item['net_qty'] >= 0 ? 'bg-primary' : 'bg-warning text-dark' }}">
                                    {{ number_format($item['net_qty']) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Không có dữ liệu theo sản phẩm</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="wh-table-card h-100">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th colspan="2"><i class="bi bi-diagram-3"></i> Thống Kê Theo Biến Thể</th>
                        <th class="text-center">ĐVT</th>
                        <th class="text-center">Nhập</th>
                        <th class="text-center">Xuất</th>
                        <th class="text-center">Chênh lệch</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($variantStats as $item)
                        <tr>
                            <td style="width: 40px;"><strong>{{ $loop->iteration }}</strong></td>
                            <td>
                                <div>
                                    <strong>{{ $item['variant_name'] }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {{ $item['product_name'] }} | SKU: {{ $item['variant_sku'] ?? '-' }}
                                    </small>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark">{{ $item['unit_label'] ?? 'Cái' }}</span></td>
                            <td class="text-center"><span class="badge bg-success">{{ number_format($item['in_qty']) }}</span></td>
                            <td class="text-center"><span class="badge bg-danger">{{ number_format($item['out_qty']) }}</span></td>
                            <td class="text-center">
                                <span class="badge {{ $item['net_qty'] >= 0 ? 'bg-primary' : 'bg-warning text-dark' }}">
                                    {{ number_format($item['net_qty']) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Không có dữ liệu theo biến thể</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="mt-3 d-flex gap-2 justify-content-end">
    <button class="btn btn-sm btn-outline-primary" onclick="window.print();">
        <i class="bi bi-printer"></i> In Báo Cáo
    </button>
    <a href="#" class="btn btn-sm btn-outline-success" title="Tải Excel">
        <i class="bi bi-file-earmark-excel"></i> Tải Excel
    </a>
</div>
@endsection
