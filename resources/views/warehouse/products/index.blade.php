@extends('layouts.warehouse')

@section('title', 'Quản Lý Sản Phẩm')

@section('content')
<style>
    .wh-page-header {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
    
    .wh-product-card {
        background: white;
        border-radius: .75rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        margin-bottom: 1rem;
        border-left: 4px solid #fa709a;
    }
    
    .wh-product-name {
        font-weight: 700;
        font-size: 1.05rem;
        color: #212529;
        margin-bottom: .5rem;
    }
    
    .wh-product-meta {
        font-size: .875rem;
        color: #6c757d;
        margin-bottom: .75rem;
        display: flex;
        gap: 1.5rem;
    }
    
    .wh-product-meta span {
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    
    .wh-variants-list {
        background: #f8f9fa;
        border-radius: .5rem;
        padding: .75rem;
        font-size: .85rem;
        margin-top: .75rem;
    }
    
    .wh-variant-item {
        padding: .5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e9ecef;
    }
    
    .wh-variant-item:last-child { border-bottom: none; }
    
    .wh-stat-card {
        background: white;
        padding: 1rem;
        border-radius: .75rem;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
    }
    .wh-stat-value { font-size: 1.875rem; font-weight: 700; color: #fa709a; }
    .wh-stat-label { font-size: .875rem; color: #6c757d; margin-top: .25rem; }
    
    .wh-empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #6c757d;
    }
</style>

<div class="wh-page-header">
    <h1><i class="bi bi-box"></i> Quản Lý Sản Phẩm</h1>
    <p>Quản lý tồn kho sản phẩm theo từng sản phẩm</p>
</div>

<!-- Filter Panel -->
<div class="wh-filter-group">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label fw-600">Tìm kiếm sản phẩm</label>
            <input type="text" name="search" class="form-control" placeholder="Tên hoặc SKU..." value="{{ request('search') }}">
        </div>
        <div class="col-md-5">
            <label class="form-label fw-600">Danh mục</label>
            <select name="category_id" class="form-select">
                <option value="">Tất cả danh mục</option>
                @php
                    $categories = \App\Models\Category::all();
                @endphp
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-search"></i> Lọc
            </button>
            <a href="{{ route('warehouse.products') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Statistics -->
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="wh-stat-card">
            <div class="wh-stat-value">{{ $products->total() }}</div>
            <div class="wh-stat-label">Tổng sản phẩm</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="wh-stat-card">
            @php
                $totalVariants = $products->flatMap(fn($p) => $p->variants)->count();
            @endphp
            <div class="wh-stat-value">{{ $totalVariants }}</div>
            <div class="wh-stat-label">Tổng biến thể</div>
        </div>
    </div>
</div>

<!-- Products List -->
@if($products->count() > 0)
    @foreach($products as $product)
        <div class="wh-product-card">
            <div class="wh-product-name">{{ $product->name }}</div>
            <div class="wh-product-meta">
                <span>
                    <i class="bi bi-barcode"></i>
                    SKU: {{ $product->sku ?? '-' }}
                </span>
                <span>
                    <i class="bi bi-tag"></i>
                    {{ $product->category?->name ?? 'N/A' }}
                </span>
                <span>
                    <i class="bi bi-diagram-3"></i>
                    {{ $product->variants->count() }} biến thể
                </span>
                <span>
                    <i class="bi bi-box-seam"></i>
                    Đơn vị: {{ strtolower($product->unit_label) }}
                </span>
            </div>
            
            @if($product->variants->count() > 0)
                <div class="wh-variants-list">
                    @foreach($product->variants as $variant)
                        @php
                            $inv = $variant->inventory->first();
                            $qty = $inv?->quantity ?? 0;
                            $reserved = $inv?->reserved_quantity ?? 0;
                            $available = $qty - $reserved;
                        @endphp
                        <div class="wh-variant-item">
                            <div>
                                <strong>{{ $variant->name }}</strong>
                                <br>
                                <small class="text-muted">SKU: {{ $variant->sku ?? '-' }}</small>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge bg-primary">Tồn: {{ number_format((float) $qty, 0, ',', '.') }}</span>
                                <span class="badge bg-warning">Cọc: {{ number_format((float) $reserved, 0, ',', '.') }}</span>
                                <span class="badge bg-info">Sẵn: {{ number_format((float) $available, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
    
    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $products->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
@else
    <div style="background: white; border-radius: .75rem; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
        <div class="wh-empty-state">
            <div style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;">
                <i class="bi bi-inbox"></i>
            </div>
            <h5>Không có sản phẩm</h5>
            <p>Chưa có sản phẩm nào phù hợp với tiêu chí tìm kiếm</p>
        </div>
    </div>
@endif
@endsection
