@extends('layouts.site')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
.product-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 24px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
}

.product-card .car__item__pic__slider {
    background: #f4f6f8;
}

.product-card .car__item__pic__slider img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}

.product-card .car__item__text {
    padding: 16px;
}

.product-card h5 {
    min-height: 54px;
    font-size: 16px;
    line-height: 1.35;
    margin-bottom: 8px;
}

.product-card .product-meta {
    color: #4b5563;
    font-size: 14px;
    margin: 2px 0;
}

.product-card .product-price {
    font-size: 18px;
    color: #b45309;
    font-weight: 800;
    margin-top: 8px;
}

.product-card .btn-group {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.product-card .btn-group .btn {
    border-radius: 8px;
    padding: 8px 10px;
}

.site-btn {
	font-size: 15px;
	color: #ffffff;
	font-weight: 700;
	display: inline-block;
	padding: 15px 35px 12px 38px;
	background: #56292d;
	border: none;
	border-radius: 2px;
}
.btn-brand{
	font-size: 15px;
	color: #ffffff;
	font-weight: 700;
	display: inline-block;
	padding: 15px 35px 12px 38px;
	background: #56292d;
	border: none;
	border-radius: 2px;
}
.btn-info{
	font-size: 15px;
	color: #ffffff;
	font-weight: 700;
	display: inline-block;
	padding: 15px 35px 12px 38px;
	background: #56292d;
	border: none;
	border-radius: 2px;
}
</style>
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
<x-breadcrumb :items="[
    ['label' => 'Danh mục sản phẩm', 'url' => route('pages.products_by_category')],
    ['label' => $category->name ?? 'Tất cả sản phẩm', 'url' => '']
]"/> 
@endsection
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-3">
            <div class="mb-3">
                <h5 class="text-uppercase fw-bold mb-2">Danh mục sản phẩm</h5>
            </div>
            <div class="list-group">
                <a href="{{ route('pages.products_by_category', ['category' => null, 'date' => request('date'), 'min_price' => request('min_price'), 'max_price' => request('max_price')]) }}" class="list-group-item list-group-item-action {{ !$category ? 'active' : '' }}">
                    Tất cả sản phẩm
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('pages.products_by_category', ['category' => $cat->slug, 'date' => request('date'), 'min_price' => request('min_price'), 'max_price' => request('max_price')]) }}" class="list-group-item list-group-item-action {{ ($category && $category->id == $cat->id) ? 'active' : '' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-md-9"> 
            <div class="my-2 py-2 text-center">
                <h4 class="brand-color text-uppercase fw-bold text-center">
                    {{ $category->name ?? 'Sản phẩm chủ đạo' }}
                </h4>
            </div>

            

            <div class="row">
                @foreach($variants as $variant)
                @if($variant->slug && $variant->product)
                <div class="col-md-4 mb-4">
                    <div class="car__item product-card">
                        <div class="car__item__pic__slider owl-carousel">
                            @if($variant->product->avatar && $variant->product->avatar->media)
                                <img src="{{ asset('storage/' . $variant->product->avatar->media->file_path) }}" alt="{{ $variant->product->name }}">
                            @endif
                            @foreach(($variant->product->gallery ?? collect()) as $link)
                                @if($link->media)
                                    <img src="{{ asset('storage/' . $link->media->file_path) }}" alt="{{ $variant->product->name }}">
                                @endif
                            @endforeach
                        </div>
                        <div class="car__item__text">
                            <div class="car__item__text__inner">
                                <h5><a href="{{ route('pages.variant_detail', $variant->slug) }}" class="text-uppercase">{{ $variant->product->name }} - {{ $variant->name }}</a></h5>
                                @if($variant->sku)
                                    <p class="product-meta">Mã sản phẩm: {{ $variant->sku }}</p>
                                @endif
                                <p class="product-price">{{ number_format($variant->latestPriceRule?->price ?? 0, 0, '.', ',') }} VNĐ</p>
                                <div class="btn-group">
                                    <a href="{{ route('pages.variant_detail', $variant->slug) }}" class="btn btn-info btn-sm">Chi tiết</a>
                                    <button class="btn btn-warning btn-sm add-to-cart" data-variant-id="{{ $variant->id }}">
                                        <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                                    </button>
                                </div>
                            </div>
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

 

 