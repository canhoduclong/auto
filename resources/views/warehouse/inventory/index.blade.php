@extends('layouts.warehouse')

@section('title', 'Tồn Kho')

@section('content')
<style>
    .wh-page-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
    
    .wh-stat-card {
        background: white;
        padding: 1rem;
        border-radius: .75rem;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
    }
    .wh-stat-value { font-size: 1.875rem; font-weight: 700; color: #4facfe; }
    .wh-stat-label { font-size: .875rem; color: #6c757d; margin-top: .25rem; }
    
    .wh-status-badge {
        display: inline-block;
        padding: .25rem .5rem;
        border-radius: .25rem;
        font-size: .75rem;
        font-weight: 600;
    }
    .wh-status-good { background: #d4edda; color: #155724; }
    .wh-status-warning { background: #fff3cd; color: #856404; }
    .wh-status-danger { background: #f8d7da; color: #721c24; }
    
    .wh-empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #6c757d;
    }
</style>

<div class="wh-page-header">
    <h1><i class="bi bi-stack"></i> Tồn Kho</h1>
    <p>Theo dõi mức tồn của các sản phẩm</p>
</div>

<!-- Statistics -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="wh-stat-card">
            <div class="wh-stat-value">{{ $stats['total_items'] }}</div>
            <div class="wh-stat-label">Tổng mặt hàng</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="wh-stat-card">
            <div class="wh-stat-value">{{ $stats['low_stock'] }}</div>
            <div class="wh-stat-label">Hàng sắp hết</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="wh-stat-card">
            <div class="wh-stat-value">{{ $stats['out_of_stock'] }}</div>
            <div class="wh-stat-label">Hàng hết</div>
        </div>
    </div>
</div>

<!-- Filter Panel -->
<div class="wh-filter-group">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-600">Tìm kiếm sản phẩm</label>
            <input type="text" name="search" class="form-control" placeholder="Tên hoặc SKU..." value="{{ request('search') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-600">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả</option>
                <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Hàng sắp hết</option>
                <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Hàng hết</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-search"></i> Lọc
            </button>
            <a href="{{ route('warehouse.inventory') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-clockwise"></i> Làm mới
            </a>
        </div>
    </form>
</div>

<!-- Table -->
@if($inventories->count() > 0)
    <div class="wh-table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Sản Phẩm</th>
                    <th>SKU</th>
                    <th>Biến Thể</th>
                    <th class="text-center">Tồn Kho</th>
                    <th class="text-center">Đã Đặt Cọc</th>
                    <th class="text-center">Có Sẵn</th>
                    <th class="text-center">Ngưỡng Cảnh Báo</th>
                    <th class="text-center">Trạng Thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inventories as $inv)
                    @php
                        $available = $inv->quantity - $inv->reserved_quantity;
                        if ($inv->quantity <= 0) {
                            $status = 'danger';
                            $statusText = 'Hết hàng';
                        } elseif ($inv->quantity <= $inv->low_stock_threshold) {
                            $status = 'warning';
                            $statusText = 'Sắp hết';
                        } else {
                            $status = 'good';
                            $statusText = 'Đủ hàng';
                        }
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $inv->productVariant->product->name }}</strong>
                        </td>
                        <td>
                            <small class="text-muted">{{ $inv->productVariant->product->sku ?? '-' }}</small>
                        </td>
                        <td>
                            <small>{{ $inv->productVariant->name ?? '-' }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ $inv->quantity }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning">{{ $inv->reserved_quantity }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info">{{ $available }}</span>
                        </td>
                        <td class="text-center">
                            {{ $inv->low_stock_threshold }}
                        </td>
                        <td class="text-center">
                            <span class="wh-status-badge wh-status-{{ $status }}">
                                {{ $statusText }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-3 d-flex justify-content-center">
        {{ $inventories->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
@else
    <div class="wh-table-card">
        <div class="wh-empty-state">
            <div style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;">
                <i class="bi bi-inbox"></i>
            </div>
            <h5>Không có tồn kho</h5>
            <p>Chưa có sản phẩm nào trong kho</p>
        </div>
    </div>
@endif
@endsection
