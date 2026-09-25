<div class="product-container product-bdr">
    <table class="table border product-list-table"> 
        <thead class="product-header-bg">
            <tr>
                <th>
                    <span class="d-flex align-items-center padding-cell pl-0">
                        <span>{{ __('admin.product.image') }}</span>
                    </span>
                </th> 
                <th  style="text-align: center"> 
                    <span class="d-flex align-items-center padding-cell pl-0">
                        <span>{{ __('admin.product.name') }}</span>
                        <span class="column-controls ms-auto">
                            <span
                                list-action="sort"
                                sort-by="name"
                                sort-direction="{{ $sort_by == 'name' ? $sort_direction : 'asc' }}"
                                class="list_column_action ms-2 {{ $sort_by == 'name' ? 'active' : '' }}">
                                <i class="ph ph-funnel-simple"></i>
                            </span>
                        </span>
                    </span>
                </th>
                
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
                
                <th class="text-center" style="width: 20px;">
                    <div class="padding-cell">
                        {{ __('admin.actions') }} <i class="ph-arrow-circle-dowsn"></i>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody> 
            @foreach($products as $key => $product)  
            <tr>
                <td width="1%">
                    
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="Product Image" width="100">
                        @else
                            No image
                        @endif
                   
                </td>  
                <td>{{ $product->name }}</td> 
                <td>{{ $product->stock }}</td>
                <td>

                    @if(auth()->user()->hasPermission('edit'))
                        <a href="{{ route('products.edit', ['product' => $product->id, 'page' =>  request()->page, 'perPage' => $perPage] ) }}" class="btn btn-warning btn-sm me-1">
                             <i class="ph ph-pencil-line"></i>
                        </a>
                    @endif
 
                </td>
                <td>

                    <div class="d-flex justify-content-end list-actions"> 
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

<div class="pagin">
    {{ $products->appends(request()->input())->links() }}
</div>

<div class="d-flex justify-content-between mx-0 mb-3 small mt-3">
    <div class="d-flex align-items-center"></div>
    <div class="ms-auto">
        <div class="">
            <nav>
                <ul class="pagination">
                    <li class="page-item {{ $page == 1 ? 'disabled' : ''}}">
                        <a class="page-link" 
                            href="{{ route('products.list', [
                                'page' => $page > 1 ? $page - 1 : 1,
                                'perPage' => $perPage,
                                'keyword' => request()->keyword
                            ]) }}">Trang trước</a>
                    </li>
                    @for ($i=1;$i<=$pageCount;$i++)
                        <li class="page-item {{ $page == $i ? 'disabled active' : ''}}">
                            <a class="page-link" href="{{ 
                                route('products.list', [
                                    'page' => $i,
                                    'perPage' => $perPage,
                                    'keyword' => request()->keyword
                                ]) }}">{{ $i }}</a>
                        </li>
                    @endfor
                        
                    <li class="page-item {{ $page == $pageCount ? 'disabled' : '' }}">
                        <a class="page-link" 
                        href="{{ route('products.list', [
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


<script> 
 
    $(function() {  
        productList.getDeleteCampaignsButtons().forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                let url = button.getAttribute('href');
                productList.deleteCampaign(url);
            });
        });        
    });

    var productList = {
        init: function() {
            // events
            this.events();
        },
        getDeleteCampaignsButtons() {
            return ProductIndex.productList.getContent().querySelectorAll('[list-action="delete-product"]');
        },
        deleteCampaign(url) { 

            new Dialog('confirm', {
                message: "{{ trans('products.delete._confirm') }}",
                ok: function() {
                    ProductIndex.productList.addLoadingEffect();
                    // load list via ajax
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data:{
                            _method: 'delete' ,
                            _token: CSRF_TOKEN,  
                        },
                    }).done(function(response) {
                        notify({
                            type: response.status,
                            message: response.message,
                        });
                        // load list
                        ProductIndex.productList.load();
                    }).fail(function(jqXHR, textStatus, errorThrown){
                    }).always(function() {
                    });
                }
            })
        },
    }
</script>

