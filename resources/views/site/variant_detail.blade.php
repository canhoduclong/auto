@extends('layouts.site')

@section('breadcrumb')
<div class="breadcrumb-option set-bg mb-4 pb-4" data-setbg="{{ asset('img/breadcrumb-bg.jpg') }}" style="background-image: url(&quot;img/breadcrumb-bg.jpg&quot;);">
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
        <div class="col-md-12 mb-4">
            <h1>{{ $product->name }} - {{ $variant->name }}</h1>
        </div>
        <div class="col-md-4">
            @if($variant->avatar && $variant->avatar->media)
                <img src="{{ asset('storage/' . $variant->avatar->media->file_path) }}" class="img-fluid" alt="{{ $product->name }}">
            @elseif($product->avatar && $product->avatar->media)
                <img src="{{ asset('storage/' . $product->avatar->media->file_path) }}" class="img-fluid" alt="{{ $product->name }}">
            @endif
        </div>
        <div class="col-md-8">
            
            <p><strong>SKU:</strong> {{ $variant->sku }}</p>
            <p><strong>Price:</strong> {{ number_format($variant->latestPriceRule?->price ?? 0) }}</p>
            <p><strong>Stock:</strong> {{ $variant->stock }}</p>
            <p><strong>Description:</strong></p>
            <div>{!! $product->description !!}</div>

            <hr>
            <div class="social-sharing mt-4">
                <h5>Share this product:</h5>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('pages.variant_detail', $variant)) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> Facebook</a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('pages.variant_detail', $variant)) }}&text={{ urlencode($product->name) }}" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-twitter"></i> Twitter</a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('pages.variant_detail', $variant)) }}&title={{ urlencode($product->name) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-linkedin"></i> LinkedIn</a>
                <a href="https://zalo.me/share?url={{ urlencode(route('pages.variant_detail', $variant)) }}" target="_blank" class="btn btn-info btn-sm">Zalo</a>
            </div>

        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <h4>Gallery</h4>
            <div class="row">
                @foreach($product->gallery as $link)
                    @if($link->media)
                        <div class="col-md-3">
                            <img src="{{ asset('storage/' . $link->media->file_path) }}" class="img-fluid mb-3">
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 my-4">
            <button class="btn btn-warning btn-sm add-to-cart" data-variant-id="{{ $variant->id }}">
                <i class="bi bi-cart-plus"></i> Add to Cart
            </button>
        </div>
    </div>

    <hr>

    <h3>Sản phẩm khác</h3>
    @if($other_variants->count() > 0)
        <div class="row">
            @foreach($other_variants as $other_variant)
                <div class="col-md-4">
                    <div class="card mb-4">
                        @if($other_variant->avatar && $other_variant->avatar->media)
                            <img src="{{ asset('storage/' . $other_variant->avatar->media->file_path) }}" class="card-img-top" alt="{{ $other_variant->product->name }}">
                        @elseif($other_variant->product->avatar && $other_variant->product->avatar->media)
                            <img src="{{ asset('storage/' . $other_variant->product->avatar->media->file_path) }}" class="card-img-top" alt="{{ $other_variant->product->name }}">
                        @endif
                        <div class="card-body">
                            <h5 class="card-title">{{ $other_variant->product->name }} - {{ $other_variant->name }}</h5>
                            <p class="card-text">SKU: {{ $other_variant->sku }}</p>
                            <a href="{{ route('pages.variant_detail', $other_variant) }}" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p>No other variants available.</p>
    @endif
</div>
@endsection
