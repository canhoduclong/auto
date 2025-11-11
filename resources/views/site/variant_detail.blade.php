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
            <h1>{{ $product->name }}</h1>
        </div>
        <div class="col-md-4">
            @if($variant->image)
                <img src="{{ asset('storage/' . $variant->image->file_path) }}" class="img-fluid" alt="{{ $product->name }}">
            @endif
        </div>
        <div class="col-md-8">
            
            <p><strong>SKU:</strong> {{ $variant->sku }}</p>
            <p><strong>Price:</strong> {{ number_format($variant->latestPriceRule?->price ?? 0) }}</p>
            <p><strong>Stock:</strong> {{ $variant->stock }}</p>
            <p><strong>Description:</strong></p>
            <div>{!! $product->description !!}</div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 my-4">
            <button class="btn btn-warning btn-sm add-to-cart" data-variant-id="{{ $product->id }}">
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
                        @if($other_variant->image)
                            <img src="{{ asset('storage/' . $other_variant->image->file_path) }}" class="card-img-top" alt="{{ $other_variant->product->name }}">
                        @endif
                        <div class="card-body">
                            <h5 class="card-title">{{ $other_variant->product->name }}</h5>
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
