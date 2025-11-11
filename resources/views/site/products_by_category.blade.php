@extends('layouts.site')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
@endpush

@section('header')
<header class="p-3 mb-3 border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
            <a href="/" class="d-flex align-items-center mb-2 mb-lg-0 text-dark text-decoration-none">
                @if(isset($settings['logo']) && $settings['logo']->value)
                    @php
                        $media = App\Models\Media::find($settings['logo']->value);
                    @endphp
                    @if($media)
                        <img src="{{ asset('storage/' . $media->file_path) }}" alt="logo" height="40">
                    @endif
                @else
                    <h2>{{ $settings['brand_name']->value ?? 'My Website' }}</h2>
                @endif
            </a>

            <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                <li><a href="#" class="nav-link px-2 link-secondary">Home</a></li>
            </ul>

            <div class="text-end d-flex align-items-center">
                <p class="slogan me-3 mb-0">{{ $settings['slogan']->value ?? 'Your slogan here' }}</p>
                <x-cart-widget :cartCount="count(session('cart', []))" class="me-3" />
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                @endauth
            </div>
        </div>
    </div>
</header>
@endsection
@section('breadcrumb')
<div class="breadcrumb-option set-bg mb-4 pb-4" data-setbg="img/breadcrumb-bg.jpg" style="background-image: url(&quot;img/breadcrumb-bg.jpg&quot;);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb__text">
                        <h2>Sản phẩm</h2>
                        <div class="breadcrumb__links">
                            <a href="./"><i class="fa fa-home"></i> Trang chủ</a>
                            <span>Sản phẩm</span>
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
        <div class="col-md-3">
            
            <div class="list-group">
                <a href="{{ route('pages.products_by_category', ['category' => null, 'date' => request('date')]) }}" class="list-group-item list-group-item-action {{ !isset($category) ? 'active' : '' }}">
                    All Categories
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('pages.products_by_category', ['category' => $cat->slug, 'date' => request('date')]) }}" class="list-group-item list-group-item-action {{ (isset($category) && $category->id == $cat->id) ? 'active' : '' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-md-9"> 
            <div class="card mb-4">
                <div class="card-body">
                    <form action="{{ route('pages.products_by_category', ['category' => $category->slug ?? null]) }}" method="GET">
                         
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <input type="number" name="min_price" class="form-control" placeholder="Min Price" value="{{ request('min_price') }}">
                            </div>
                            <div class="col-md-4">
                                <input type="number" name="max_price" class="form-control" placeholder="Max Price" value="{{ request('max_price') }}">
                            </div>
                            <div class="col-md-2"> 
                                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                            </div> 
                        </div> 
                    </form>
                </div>
            </div>

            <div class="row">
                @foreach($variants as $variant)
                @if($variant->slug)
                <div class="col-md-3 mb-4">
                    <div class="card">
                       
                        <a href="{{ route('pages.variant_detail', $variant->slug) }}">
                             
                            @if($variant->product->avatar && $variant->product->avatar->media)
                            
                                <img src="{{ asset('storage/' . $variant->product->avatar->media->file_path) }}" class="card-img-top" alt="{{ $variant->product->name }}">
                            @else
                                <img src="https://via.placeholder.com/300x200" class="card-img-top" alt="placeholder">
                            @endif
                        </a>
                        <div class="card-body">
                            <h5 class="card-title"><a href="{{ route('pages.variant_detail', $variant->slug) }}">{{ $variant->product->name }}</a></h5>
                            <p class="card-text">SKU: {{ $variant->sku }}</p>
                            <p class="card-text">Price: {{ number_format($variant->latestPriceRule?->price ?? 0) }}</p>
                            <p class="card-text">Stock: {{ $variant->stock }}</p>
                            <a href="{{ route('pages.variant_detail', $variant->slug) }}" class="btn btn-info btn-sm">View</a>
                            <button class="btn btn-warning btn-sm add-to-cart" data-variant-id="{{ $variant->id }}">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button> 
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>

            {{ $variants->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@include('site._cart_scripts')

 

 