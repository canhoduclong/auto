@extends('layouts.site')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-3"> 
            <div class="list-group">
                <a href="{{ route('site.variants') }}" class="list-group-item list-group-item-action {{ !isset($category) ? 'active' : '' }}">
                    DANH MỤC HÀNG HÓA
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('site.variants', ['category' => $cat->slug]) }}" class="list-group-item list-group-item-action {{ (isset($category) && $category->id == $cat->id) ? 'active' : '' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-md-9">
            <h1>SẢN PHẨM</h1>

            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($variants as $variant)
                            <tr>
                                <td>
                                    @if($variant->media)
                                        <img src="{{ asset('storage/' . $variant->media->file_path) }}" alt="{{ $variant->product->name }}" width="80">
                                    @elseif($variant->product->avatar && $variant->product->avatar->media)
                                        <img src="{{ asset('storage/' . $variant->product->avatar->media->file_path) }}" alt="{{ $variant->product->name }}" width="80">
                                    @else
                                        <img src="https://via.placeholder.com/80" alt="placeholder" width="80">
                                    @endif
                                </td>
                                <td>{{ $variant->product->name }}</td>
                                <td>{{ $variant->sku }}</td>
                                <td>{{ number_format($variant->latestPriceRule?->price ?? 0) }}</td>
                                <td>{{ $variant->stock }}</td>
                                <td>
                                    @if($variant->slug)
                                    <a href="{{ route('pages.variant_detail', $variant->slug) }}" class="btn btn-info btn-sm">View</a>
                                    @endif
                                    <a href="{{ route('orders.create_new', ['variant_id' => $variant->id]) }}" class="btn btn-primary btn-sm">Lên đơn</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{ $variants->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection