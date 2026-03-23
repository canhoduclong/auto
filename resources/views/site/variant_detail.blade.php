@extends('layouts.site')

@push('styles')
<style>
    .variant-page {
        padding-bottom: 28px;
    }
    .variant-head {
        background: linear-gradient(135deg, #f8fbff 0%, #f1f7ff 100%);
        border: 1px solid #e2ebf5;
        border-radius: 16px;
        padding: 18px 20px;
        margin-bottom: 20px;
    }
    .variant-head__title {
        margin: 0;
        font-size: 1.65rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
    }
    .variant-head__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }
    .variant-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 4px 10px;
        background: #e8f0fb;
        color: #1e3a8a;
        font-size: .78rem;
        font-weight: 700;
    }
    .variant-main-image {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 6px 20px rgba(15, 23, 42, .05);
    }
    .variant-main-image img {
        width: 100%;
        height: 460px;
        object-fit: cover;
        display: block;
    }
    .variant-gallery {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
    }
    .variant-gallery img {
        width: 100%;
        height: 88px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }
    .variant-info-box {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 6px 20px rgba(15, 23, 42, .05);
    }
    .variant-price {
        font-size: 1.8rem;
        font-weight: 800;
        color: #dc2626;
        line-height: 1.2;
        margin-bottom: 8px;
    }
    .stock-good {
        color: #047857;
        font-weight: 700;
    }
    .stock-bad {
        color: #b91c1c;
        font-weight: 700;
    }
    .variant-desc {
        border-top: 1px dashed #cbd5e1;
        margin-top: 14px;
        padding-top: 14px;
        color: #334155;
    }
    .section-block {
        margin-top: 24px;
    }
    .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 12px;
    }

    .product-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 0;
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
        min-height: 50px;
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
    .product-card .btn-info {
        font-size: 15px;
        color: #ffffff;
        font-weight: 700;
        display: inline-block;
        padding: 8px 12px;
        background: #56292d;
        border: none;
        border-radius: 8px;
    }

    .category-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
    }
    .category-panel__header {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(180deg, #f8fafc, #ffffff);
    }
    .category-panel__title {
        margin: 0;
        font-size: .9rem;
        color: #0f172a;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .category-panel__subtitle {
        margin: 4px 0 0;
        font-size: .75rem;
        color: #64748b;
    }
    .category-list {
        list-style: none;
        margin: 0;
        padding: 10px;
    }
    .category-list li + li {
        margin-top: 6px;
    }
    .category-link {
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 10px;
        border: 1px solid transparent;
        padding: 10px 12px;
        color: #1f2937;
        background: #fff;
        transition: all .2s ease;
        font-weight: 600;
    }
    .category-link:hover {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #0f172a;
    }
    .category-link.active {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .category-link__name {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }
    .category-link__name i {
        color: #94a3b8;
    }
    .category-link.active .category-link__name i {
        color: #2563eb;
    }
    .category-link__count {
        min-width: 24px;
        text-align: center;
        border-radius: 999px;
        padding: 2px 8px;
        background: #dbeafe;
        color: #1e40af;
        font-size: .72rem;
        font-weight: 700;
    }
    .category-link.active .category-link__count {
        background: #1d4ed8;
        color: #fff;
    }
    @media (max-width: 991.98px) {
        .variant-main-image img { height: 360px; }
    }
    @media (max-width: 575.98px) {
        .variant-main-image img { height: 300px; }
        .variant-gallery { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
</style>
@endpush

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
<div class="container variant-page">
    <div class="variant-head">
        <h1 class="variant-head__title">{{ $product->name }} - {{ $variant->name }}</h1>
        <div class="variant-head__meta">
            <span class="variant-chip"><i class="fa fa-barcode"></i> SKU: {{ $variant->sku ?: 'N/A' }}</span>
            @if($product->category)
                <span class="variant-chip"><i class="fa fa-folder-open"></i> {{ $product->category->name }}</span>
            @endif
            <span class="variant-chip"><i class="fa fa-cubes"></i> Tồn kho: {{ max(0, (int) $variant->available_stock) }}</span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="variant-main-image">
                @if($variant->avatar && $variant->avatar->media)
                    <img src="{{ asset('storage/' . $variant->avatar->media->file_path) }}" alt="{{ $product->name }}">
                @elseif($product->avatar && $product->avatar->media)
                    <img src="{{ asset('storage/' . $product->avatar->media->file_path) }}" alt="{{ $product->name }}">
                @else
                    <img src="https://via.placeholder.com/800x800.png?text=No+Image" alt="{{ $product->name }}">
                @endif
            </div>

            @if($product->gallery->count() > 0)
                <div class="variant-gallery">
                    @foreach($product->gallery as $link)
                        @if($link->media)
                            <img src="{{ asset('storage/' . $link->media->file_path) }}" alt="{{ $product->name }}">
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="col-lg-6">
            <div class="variant-info-box">
                <div class="variant-price">{{ number_format($variant->latestPriceRule?->price ?? 0, 0, ',', '.') }} VNĐ</div>
                <p class="mb-3">
                    <strong>Trạng thái kho:</strong>
                    @if($variant->available_stock > 0)
                        <span class="stock-good">Còn hàng</span>
                    @else
                        <span class="stock-bad">Hết hàng</span>
                    @endif
                </p>

                @if($variant->available_stock > 0)
                    <button class="btn btn-warning btn-lg add-to-cart" data-variant-id="{{ $variant->id }}">
                        <i class="bi bi-cart-plus"></i> Thêm vào giỏ hàng
                    </button>
                @else
                    <button class="btn btn-secondary btn-lg" disabled>
                        <i class="bi bi-x-circle"></i> Hết hàng
                    </button>
                @endif

                <div class="variant-desc">
                    <strong>Mô tả sản phẩm</strong>
                    <div class="mt-2">{!! $product->description !!}</div>
                </div>

                <div class="mt-4">
                    <h6 class="mb-2">Chia sẻ sản phẩm</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('pages.variant_detail', $variant)) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> Facebook</a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('pages.variant_detail', $variant)) }}&text={{ urlencode($product->name) }}" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-twitter"></i> Twitter</a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('pages.variant_detail', $variant)) }}&title={{ urlencode($product->name) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-linkedin"></i> LinkedIn</a>
                        <a href="https://zalo.me/share?url={{ urlencode(route('pages.variant_detail', $variant)) }}" target="_blank" class="btn btn-info btn-sm">Zalo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-block">
        <div class="row g-4">
            <div class="col-lg-3">
                @php
                    $activeCategoryId = $product->category_id;
                    $totalProductsInAllCategories = (int) $categories->sum('products_count');
                @endphp
                <div class="category-panel sticky-top" style="top: 90px;">
                    <div class="category-panel__header">
                        <h5 class="category-panel__title">Danh mục sản phẩm</h5>
                        <p class="category-panel__subtitle">Chọn danh mục để lọc nhanh sản phẩm</p>
                    </div>
                    <ul class="category-list">
                        <li>
                            <a href="{{ route('pages.products_by_category') }}" class="category-link {{ !$activeCategoryId ? 'active' : '' }}">
                                <span class="category-link__name">
                                    <i class="bi bi-grid"></i>
                                    Tất cả sản phẩm
                                </span>
                                <span class="category-link__count">{{ $totalProductsInAllCategories }}</span>
                            </a>
                        </li>
                        @foreach($categories as $category)
                            <li>
                                <a href="{{ route('pages.products_by_category', $category) }}" class="category-link {{ ((int) $category->id === (int) $activeCategoryId) ? 'active' : '' }}">
                                    <span class="category-link__name">
                                        <i class="bi bi-tag"></i>
                                        {{ $category->name }}
                                    </span>
                                    <span class="category-link__count">{{ $category->products_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="col-lg-9">
                <h3 class="section-title">Sản phẩm cùng loại</h3>

                @if($other_variants->count() > 0)
                    <div class="row">
                        @foreach($other_variants as $other_variant)
                            <div class="col-md-4 mb-4">
                                <div class="car__item product-card">
                                    <div class="car__item__pic__slider owl-carousel">
                                        @if($other_variant->avatar && $other_variant->avatar->media)
                                            <img src="{{ asset('storage/' . $other_variant->avatar->media->file_path) }}" alt="{{ $other_variant->product->name }}">
                                        @elseif($other_variant->product->avatar && $other_variant->product->avatar->media)
                                            <img src="{{ asset('storage/' . $other_variant->product->avatar->media->file_path) }}" alt="{{ $other_variant->product->name }}">
                                        @else
                                            <img src="https://via.placeholder.com/500x350.png?text=No+Image" alt="{{ $other_variant->product->name }}">
                                        @endif
                                    </div>
                                    <div class="car__item__text">
                                        <div class="car__item__text__inner">
                                            <h5>
                                                <a href="{{ route('pages.variant_detail', $other_variant) }}" class="text-uppercase">
                                                    {{ $other_variant->product->name }} - {{ $other_variant->name }}
                                                </a>
                                            </h5>
                                            @if($other_variant->sku)
                                                <p class="product-meta">Mã sản phẩm: {{ $other_variant->sku }}</p>
                                            @endif
                                            <p class="product-price">{{ number_format($other_variant->latestPriceRule?->price ?? 0, 0, '.', ',') }} VNĐ</p>
                                            <div class="btn-group">
                                                <a href="{{ route('pages.variant_detail', $other_variant) }}" class="btn btn-info btn-sm">Chi tiết</a>
                                                <button class="btn btn-warning btn-sm add-to-cart" data-variant-id="{{ $other_variant->id }}">
                                                    <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-light border">Không có sản phẩm nào khác trong danh mục này.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
