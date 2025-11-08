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
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">{{ __('admin.product.search') }}</button>
            </div>
        </div>
    </form>
    @can('create', App\Models\Product::class)
        <a href="{{ route('products.create') }}" class="btn btn-success mb-3">{{ __('admin.product.create') }}</a>
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
            
            @if(auth()->user()->hasPermission('add'))
                <a class="btn btn-outline-success btn-sm" href="{{ route('products.create') }}">
                    <i class="ph-plus ph-sm me-2"></i>
                    {{ __('admin.product.create') }}
                </a> 
            @endif

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
                        <th>
                            <a href="{{ route('products.index', array_merge(request()->query(), ['sort_by' => 'name', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc'])) }}">
                                Name
                            </a>
                        </th>
                        <th>Brand</th>
                        <th>Category</th>
                <th>
                    <span class="d-flex align-items-center padding-cell pl-0">
                        <span>{{ __('admin.product.stock') }}</span>
                        <span class="column-controls ms-auto">
                            <span
                                list-action="sort"
                                sort-by="price"
                                sort-direction="{{ $sort_by == 'price' ? $sort_direction : 'asc' }}"
                                class="list_column_action ms-2 {{ $sort_by == 'price' ? 'active' : '' }}">
                                <i class="ph ph-funnel-simple"></i>
                            </span>
                        </span>
                    </span>
                </th>
                
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
                    </td>
                    <td>{{ $product->brand->name ?? '' }}</td>
                    <td>{{ $product->category->name ?? '' }}</td>
                <td id="product-stock-{{ $product->id }}">{{ $product->stock ?? '' }}</td>
                <td>
                    <div class="d-flex justify-content-end list-actions"> 
                        
                        @if(auth()->user()->hasPermission('edit'))
                            <a href="{{ route('products.edit', ['product' => $product->id, 'page' =>  request()->page, 'perPage' => $perPage] ) }}" class="btn btn-warning btn-sm me-1">
                                <i class="ph ph-pencil-line"></i>
                            </a>
                        @endif
    
                        @can('update', $product)
                            <button type="button" class="btn btn-info btn-sm me-1 quick-edit-btn" 
                                    data-id="{{ $product->id }}" 
                                    data-url="{{ route('products.getQuickEditForm', $product->id) }}">
                                Sửa nhanh
                            </button>
                        @endcan
                    
                         @can('update', $product)
                            <a href="{{ route('products.edit', ['product' => $product->id, 'page' =>  request()->page, 'perPage' => $perPage ]) }}" class="btn btn-primary btn-sm">Sửa</a>
                        @endcan

                        @can('delete', $product)
                            <form action="{{ route('products.destroy', ['product' => $product->id, 'page' =>  request()->page, 'perPage' => $perPage ]) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa không?')">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </form>
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
        // Quick Edit for Products
        $(document).on('click', '.quick-edit-btn', function() {
            let btn = this;
            let tr = btn.closest('tr');
            if (!tr) return;

            if (tr.nextSibling && tr.nextSibling.classList && tr.nextSibling.classList.contains('quick-edit-row')) {
                tr.nextSibling.remove();
                return;
            }

            let url = btn.getAttribute('data-url');

            $.get(url, function(data) {
                let newRow = document.createElement('tr');
                newRow.classList.add('quick-edit-row');
                let td = document.createElement('td');
                td.colSpan = tr.children.length;
                td.innerHTML = data;
                newRow.appendChild(td);
                tr.parentNode.insertBefore(newRow, tr.nextSibling);
            });
        });

        $(document).on('submit', '.quick-edit-form-instance', function(e) {
            e.preventDefault();
            let form = this;
            let formData = new FormData(form);
            let id = $(form).closest('.quick-edit-row').prev().find('.quick-edit-btn').data('id');

            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#product-name-' + id).text(response.product.name);
                    $('#product-stock-' + id).text(response.product.stock);
                    if(response.product.image_url) {
                        var image_element = $("<img />", { 
                            id: 'product-image-'+id, 
                            src: response.product.image_url, 
                            width: 80 
                        });
                        $('#product-image-' + id).replaceWith(image_element);
                    } else {
                        $('#product-image-' + id).replaceWith('<span id="product-image-' + id + '">No image</span>');
                    }
                    $(form).closest('.quick-edit-row').remove();
                    showToast(response.message, 'success');
                },
                error: function(response) {
                    var errors = response.responseJSON.errors;
                    var error_html = '';
                    $.each(errors, function(key, value) {
                        error_html += '<li>' + value + '</li>';
                    });
                    alert('Có lỗi xảy ra:\n' + error_html);
                }
            });
        });

        $(document).on('click', '.cancel-quick-edit', function() {
            $(this).closest('.quick-edit-row').remove();
        });

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