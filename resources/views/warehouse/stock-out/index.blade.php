@extends('layouts.warehouse')

@section('title', 'Xuất Kho')

@section('content')
<style>
    .wh-page-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
    .wh-stat-value { font-size: 1.875rem; font-weight: 700; color: #f5576c; }
    .wh-stat-label { font-size: .875rem; color: #6c757d; margin-top: .25rem; }
    
    .wh-empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #6c757d;
    }
    .wh-empty-state-icon { font-size: 3rem; color: #dee2e6; margin-bottom: 1rem; }
</style>

<div class="wh-page-header">
    <h1><i class="bi bi-box-arrow-right"></i> Xuất Kho</h1>
    <p>Quản lý các phiếu xuất hàng từ kho</p>
</div>

<!-- Filter Panel -->
<div class="wh-filter-group">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-600">Từ ngày</label>
            <input type="date" name="from_date" class="form-control" value="{{ $from }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-600">Đến ngày</label>
            <input type="date" name="to_date" class="form-control" value="{{ $to }}">
        </div>
        <div class="col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-search"></i> Lọc
            </button>
            <a href="{{ route('warehouse.stock-out') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-clockwise"></i> Làm mới
            </a>
        </div>
    </form>
</div>

<!-- Statistics -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="wh-stat-card">
            <div class="wh-stat-value">{{ $stockOutDocuments->total() }}</div>
            <div class="wh-stat-label">Tổng phiếu xuất</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="wh-stat-card">
            <div class="wh-stat-value">
                @php
                    $totalQty = $stockOutDocuments->flatMap(fn($doc) => $doc->items)->sum('quantity');
                @endphp
                {{ number_format($totalQty) }}
            </div>
            <div class="wh-stat-label">Tổng số lượng xuất</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="wh-stat-card">
            <div class="wh-stat-value">
                {{ $stockOutDocuments->count() }}
            </div>
            <div class="wh-stat-label">Trên trang này</div>
        </div>
    </div>
</div>

<!-- Table -->
@if($stockOutDocuments->count() > 0)
    <div class="wh-table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã Phiếu</th>
                    <th>Ngày Xuất</th>
                    <th>Kho</th>
                    <th>Người Xuất</th>
                    <th class="text-center">Số Lượng</th>
                    <th class="text-center">Số Mặt Hàng</th>
                    <th>Phí Vận Chuyển</th>
                    <th class="text-center">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockOutDocuments as $doc)
                    <tr>
                        <td>
                            <strong>#{{ $doc->id }}</strong>
                        </td>
                        <td>{{ $doc->document_date->format('d/m/Y') }}</td>
                        <td>{{ $doc->warehouse->name }}</td>
                        <td>{{ $doc->user->name ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge bg-danger">
                                {{ $doc->items->sum('quantity') }}
                            </span>
                        </td>
                        <td class="text-center">{{ $doc->items->count() }} mặt hàng</td>
                        <td>
                            {{ $doc->shipping_fee ? number_format($doc->shipping_fee, 0) . ' ₫' : '-' }}
                        </td>
                        <td class="text-center">
                            <a href="{{ route('inventory-documents.show', $doc) }}" class="btn btn-sm btn-info" title="Xem chi tiết">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-3 d-flex justify-content-center">
        {{ $stockOutDocuments->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
@else
    <div class="wh-table-card">
        <div class="wh-empty-state">
            <div class="wh-empty-state-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <h5>Không có phiếu xuất kho</h5>
            <p>Hãy tạo phiếu xuất kho mới để bắt đầu</p>
            <a href="{{ route('inventory-documents.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tạo phiếu xuất
            </a>
        </div>
    </div>
@endif
@endsection
