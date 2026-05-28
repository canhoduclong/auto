@extends('layouts.app', [
    'menu' => 'product',
])

@section('content')
<div class="content"  id="ProductList">
<h2>{{ __('admin.product.list') }}</h2> 
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
 <form action="{{ route('products.index') }}" method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="name" class="form-control" placeholder="{{ __('admin.product.search_placeholder') }}" value="{{ request('name') }}">
            <select name="category_id" class="form-control">
                <option value="">{{ __('admin.product.category') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @if(request('category_id') == $category->id) selected @endif>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <select name="status_filter" class="form-control">
                <option value="all" {{ ($statusFilter ?? 'active') === 'all' ? 'selected' : '' }}>Tất cả sản phẩm</option>
                <option value="active" {{ ($statusFilter ?? 'active') === 'active' ? 'selected' : '' }}>Sản phẩm đang hoạt động</option>
                <option value="deleted" {{ ($statusFilter ?? 'active') === 'deleted' ? 'selected' : '' }}>Sản phẩm đã xóa</option>
            </select>
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">{{ __('admin.product.search') }}</button>
            </div>
        </div>
    </form>
    @can('create', App\Models\Product::class)
        <a href="{{ route('products.create', ['page' => $page, 'perPage' => $perPage]) }}" class="btn btn-success mb-3">{{ __('admin.product.create') }}</a>
    @endcan
    @can('update', App\Models\Product::class)
        <a href="{{ route('products.price-management.index') }}" class="btn btn-outline-primary mb-3">Quản lý giá sản phẩm</a>
    @endcan
    <div class="card"> 
        <div class="card-header">
            <h5 class="mb-0">{{ __('admin.product.list') }}</h5>
        </div>

        <div class="card-body d-flex justify-content-between"> 
            <div class="filter-area">  
                <div class="input-group">
                    <input type="text" list-control="search-input" class="form-control" placeholder="{{ __('admin.product.name') }}"> 
                    <a href="#" list-control="search-button" class="btn btn-secondary" >
                        <span class="material-symbols-rounded" style="line-height: 1 !important;">{{ __('admin.product.search') }}</span> 
                    </a>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="{{ route('products.index') }}" id="perPageForm" class="d-flex align-items-center">
                    @foreach(request()->except('perPage', 'page') as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <label for="perPage" class="me-1 mb-0 small text-muted">Hiển thị:</label>
                    <select name="perPage" id="perPage" class="form-select form-select-sm w-auto" onchange="document.getElementById('perPageForm').submit()">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
                @can('create', App\Models\Product::class)
                    <a class="btn btn-outline-success btn-sm" href="{{ route('products.create', ['page' => $page, 'perPage' => $perPage]) }}">
                        <i class="ph-plus ph-sm me-2"></i>
                        {{ __('admin.product.create') }}
                    </a> 
                @endcan
            </div>

        </div> 
      
        
        <div class="product-container product-bdr">
    <table class="table border product-list-table"> 
        <thead class="product-header-bg">
            <tr>
                <th>
                    <span class="d-flex align-items-center padding-cell pl-0">
                        <span>{{ __('admin.product.image') }}</span>
                    </span>
                </th> 
                        <th>Sản phẩm</th>
                        <th>Đơn vị tính</th>
                        <th>Trạng thái</th>
                
                <th class="text-center" >
                    <div class="padding-cell">
                        {{ __('admin.product.actions') }} <i class="ph-arrow-circle-dowsn"></i>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody> 
            @foreach($products as $key => $product)  
            <tr id="product-row-{{ $product->id }}">
                <td width="15%">
                   
                    @if($product->avatar && $product->avatar->media)
                        <img src="{{ asset('storage/' . $product->avatar->media->file_path) }}" width="80" id="product-image-{{ $product->id }}">
                    @else
                        <span id="product-image-{{ $product->id }}">No image</span>
                    @endif

                </td>  
                    <td>
                        <a href="{{ route('products.edit', ['product' => $product->id, 'page' => $page, 'perPage' => $perPage]) }}" class="product-name" data-product-id="{{ $product->id }}">
                            {{ $product->name }}
                        </a>
                        <div class="text-muted small">{{ $product->brand->name ?? '' }}{{ ($product->brand->name ?? '') && ($product->category->name ?? '') ? ' / ' : '' }}{{ $product->category->name ?? '' }}</div>
                    </td>
                    <td>{{ $product->unit_label }}</td>
                <td>
                    @if($product->status)
                        <span class="badge bg-success">Đang hoạt động</span>
                    @else
                        <span class="badge bg-danger">Đã xóa</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex justify-content-end list-actions"> 
                        
                        @can('update', $product)
                            <a href="{{ route('products.edit', ['product' => $product->id, 'page' => $page, 'perPage' => $perPage] ) }}" class="btn btn-warning btn-sm me-1">
                                <i class="ph ph-pencil-line"></i>
                            </a>
                        @endcan
    
                         @can('update', $product)
                            <a href="{{ route('products.edit', ['product' => $product->id, 'page' =>  request()->page, 'perPage' => $perPage ]) }}" class="btn btn-primary btn-sm">Sửa</a>
                        @endcan

                        @can('update', $product)
                            <a href="{{ route('products.price-management.show', $product) }}" class="btn btn-outline-info btn-sm ms-1">Cập nhật giá</a>
                        @endcan

                        @can('delete', $product)
                        @if(auth()->user()->hasRole('admin') && $product->status)
                            <form action="{{ route('products.destroy', ['product' => $product->id, 'page' => $page, 'perPage' => $perPage ]) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa không?')">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </form>
                        @endif
                        @endcan

                        @can('update', $product)
                        @if(auth()->user()->hasRole('admin') && !$product->status)
                            <form action="{{ route('products.restore', ['product' => $product->id, 'page' => $page, 'perPage' => $perPage]) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Bạn có chắc chắn muốn khôi phục sản phẩm này không?')">
                                    <i class="ph ph-arrow-counter-clockwise"></i>
                                </button>
                            </form>
                        @endif
                        @endcan
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table> 
</div> 
 

<div class="d-flex justify-content-between mx-0 mb-3 small mt-3">
    <div class="d-flex align-items-center"></div>
    <div class="ms-auto">
        <div class="">
            <nav>
                <ul class="pagination">
                    <li class="page-item {{ $page == 1 ? 'disabled' : ''}}">
                        <a class="page-link" 
                            href="{{ route('products.index', [
                                'page' => $page > 1 ? $page - 1 : 1,
                                'perPage' => $perPage,
                                'keyword' => request()->keyword
                            ]) }}">Trang trước</a>
                    </li>
                    @for ($i=1;$i<=$pageCount;$i++)
                        <li class="page-item {{ $page == $i ? 'disabled active' : ''}}">
                            <a class="page-link" href="{{ 
                                route('products.index', [
                                    'page' => $i,
                                    'perPage' => $perPage,
                                    'keyword' => request()->keyword
                                ]) }}">{{ $i }}</a>
                        </li>
                    @endfor
                        
                    <li class="page-item {{ $page == $pageCount ? 'disabled' : '' }}">
                        <a class="page-link" 
                        href="{{ route('products.index', [
                            'page' => $page < $pageCount ? $page + 1 : $pageCount,
                            'perPage' => $perPage,
                            'keyword' => request()->keyword
                        ]) }}">Trang sau</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>


 


    </div>
</div>

@push('scripts')
<script>
    $(function() {
        $(document).on('click', '.choose-image-btn', function() {
            let productId = $(this).data('product-id');
            var url = "{{ route('media.library.popup') }}?callback=selectProductImage&product_id=" + productId;
            window.open(url, 'Media Library', 'width=1024,height=768');
        });

        window.selectProductImage = function(media, productId) {
            $('#quick-edit-media-id-' + productId).val(media.id);
            $('#quick-edit-preview-image-' + productId).attr('src', media.url);
        };
    });
</script>
@endpush

@endsection