@extends('layouts.site')

@section('breadcrumb')
<div class="breadcrumb-option set-bg mb-4 pb-4" data-setbg="{{ asset('img/breadcrumb-bg.jpg') }}" style="background-image: url(&quot;{{ asset('img/breadcrumb-bg.jpg') }}&quot;);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb__text">
                        <h2>Sản phẩm</h2>
                        <div class="breadcrumb__links">
                            <a href="./"><i class="fa fa-home"></i> Trang chủ</a>
                            <a href="{{ route('pages.products_by_category') }}"><i class="fa fa-home"></i> Sản phẩm</a>
                            <span> {{ $product->name }}</span>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
     
@endsection
@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-12 mb-4">
                    <h1>{{ $product->name }} - {{ $variant->name }}</h1>
                </div>
                <div class="col-md-6">
                    @if($variant->avatar && $variant->avatar->media)
                        <img src="{{ asset('storage/' . $variant->avatar->media->file_path) }}" class="img-fluid" alt="{{ $product->name }}">
                    @elseif($product->avatar && $product->avatar->media)
                        <img src="{{ asset('storage/' . $product->avatar->media->file_path) }}" class="img-fluid" alt="{{ $product->name }}">
                    @else
                        <img src="https://via.placeholder.com/400x400.png?text=No+Image" class="img-fluid" alt="{{ $product->name }}">
                    @endif

                    @if($product->gallery->count() > 0)
                        <div class="row mt-3">
                            @foreach($product->gallery as $link)
                                @if($link->media)
                                    <div class="col-3">
                                        <img src="{{ asset('storage/' . $link->media->file_path) }}" class="img-fluid mb-3">
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <p><strong>SKU:</strong> {{ $variant->sku }}</p>
                    <p><strong>Giá:</strong> <span class="fs-4 text-danger">{{ number_format($variant->latestPriceRule?->price ?? 0) }} VNĐ</span></p>
                    <p><strong>Kho:</strong> {{ $variant->stock > 0 ? 'Còn hàng' : 'Hết hàng' }}</p>
                    
                    <button class="btn btn-warning btn-lg add-to-cart" data-variant-id="{{ $variant->id }}">
                        <i class="bi bi-cart-plus"></i> Thêm vào giỏ hàng
                    </button>

                    <hr>

                    <div class="mt-4">
                        <strong>Mô tả:</strong>
                        <div>{!! $product->description !!}</div>
                    </div>
                    
                    <hr>
                    <div class="social-sharing mt-4">
                        <h5>Chia sẻ sản phẩm:</h5>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('pages.variant_detail', $variant)) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> Facebook</a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('pages.variant_detail', $variant)) }}&text={{ urlencode($product->name) }}" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-twitter"></i> Twitter</a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('pages.variant_detail', $variant)) }}&title={{ urlencode($product->name) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-linkedin"></i> LinkedIn</a>
                        <a href="https://zalo.me/share?url={{ urlencode(route('pages.variant_detail', $variant)) }}" target="_blank" class="btn btn-info btn-sm">Zalo</a>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <div class="related-products">
                <div class="row">
                    <div class="col-md-3">
                         <div class="card sticky-top">
                            <div class="card-header">
                                <h4>Danh mục sản phẩm</h4>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    @foreach($categories as $category)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="{{ route('pages.products_by_category', $category) }}">{{ $category->name }}</a>
                                            <span class="badge bg-primary rounded-pill">{{ $category->products_count }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9 mb-4">
                        <h3>Sản phẩm cùng loại</h3> 
                        @if($other_variants->count() > 0)
                            <div class="row">
                                @foreach($other_variants as $other_variant)
                                    <div class="col-md-4">
                                        <div class="card mb-4">
                                            <a href="{{ route('pages.variant_detail', $other_variant) }}">
                                                @if($other_variant->avatar && $other_variant->avatar->media)
                                                    <img src="{{ asset('storage/' . $other_variant->avatar->media->file_path) }}" class="card-img-top" alt="{{ $other_variant->product->name }}">
                                                @elseif($other_variant->product->avatar && $other_variant->product->avatar->media)
                                                    <img src="{{ asset('storage/' . $other_variant->product->avatar->media->file_path) }}" class="card-img-top" alt="{{ $other_variant->product->name }}">
                                                @else
                                                    <img src="https://via.placeholder.com/300x200.png?text=No+Image" class="card-img-top" alt="{{ $other_variant->product->name }}">
                                                @endif
                                            </a>
                                            <div class="card-body">
                                                <h5 class="card-title"><a href="{{ route('pages.variant_detail', $other_variant) }}">{{ $other_variant->product->name }} - {{ $other_variant->name }}</a></h5>
                                                @if($other_variant->latestPriceRule)
                                                <p class="card-text text-danger">{{ number_format($other_variant->latestPriceRule->price, 0, ',', '.') }} VNĐ</p>
                                                @endif
                                                <a href="{{ route('pages.variant_detail', $other_variant) }}" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p>Không có sản phẩm nào khác trong danh mục này.</p>
                        @endif
                    </div>
                   
                </div>
               
            </div>
        </div> 
    </div>
</div>
@endsection
