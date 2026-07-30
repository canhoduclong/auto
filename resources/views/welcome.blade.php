@extends('layouts.site')

@push('styles')
<style>
.hero-wrap .slider-item {
    min-height: 540px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.hero-wrap .overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(110deg, rgba(10, 19, 37, 0.82) 0%, rgba(10, 19, 37, 0.35) 55%, rgba(10, 19, 37, 0.1) 100%);
}

.hero-content {
    color: #fff;
    position: relative;
    z-index: 2;
    max-width: 560px;
    padding: 48px 28px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(3px);
}

.hero-eyebrow {
    font-size: 13px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #9ad2ff;
    font-weight: 700;
}

.hero-title {
    font-size: 44px;
    font-weight: 800;
    line-height: 1.15;
    margin: 10px 0 12px;
}

.hero-desc {
    font-size: 16px;
    color: rgba(255, 255, 255, 0.92);
    margin-bottom: 20px;
}

.hero-actions .btn {
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 600;
}

.home-metric {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 12px 24px rgba(20, 31, 56, 0.08);
    padding: 16px;
    margin-top: -28px;
    position: relative;
    z-index: 5;
}

.home-metric .metric-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.home-metric .metric-value {
    font-size: 24px;
    font-weight: 800;
    color: #111827;
}

.section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 12px 0 18px;
}

.section-head h4 {
    margin: 0;
    font-size: 28px;
    letter-spacing: 0.4px;
}

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

.featured-products-section {
    background: linear-gradient(180deg, #fffaf2, #ffffff);
}

.featured-products-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 18px;
    margin: 34px 0 22px;
}

.featured-products-eyebrow {
    color: #7c5b3f;
    font-size: .82rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.featured-products-head h4 {
    margin: 4px 0 0;
    color: #2f1f17;
    font-size: clamp(1.45rem, 2vw, 2rem);
    font-weight: 900;
}

.featured-products-head p {
    margin: 6px 0 0;
    color: #5f4633;
}

.featured-products-link {
    border: 1px solid #56292d;
    border-radius: 8px;
    color: #56292d;
    font-weight: 800;
    padding: 9px 14px;
    text-decoration: none;
    white-space: nowrap;
}

.featured-products-link:hover {
    background: #56292d;
    color: #fff;
}

.home-product-card {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
    border: 1px solid #ead8bf;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 10px 22px rgba(86, 41, 45, .07);
    transition: transform .15s ease, box-shadow .15s ease;
}

.home-product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 32px rgba(86, 41, 45, .13);
}

.home-product-card__image {
    display: block;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background: #fff7e6;
}

.home-product-card__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .2s ease;
}

.home-product-card:hover .home-product-card__image img {
    transform: scale(1.03);
}

.home-product-card__body {
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: 15px;
}

.home-product-card__category {
    color: #7c5b3f;
    font-size: .78rem;
    margin-bottom: 4px;
}

.home-product-card h5 {
    min-height: 42px;
    margin: 0 0 10px;
    font-size: 1rem;
    line-height: 1.3;
    font-weight: 900;
}

.home-product-card h5 a {
    color: #2f1f17;
    text-decoration: none;
}

.home-product-card h5 a:hover {
    color: #56292d;
}

.home-product-card__meta {
    display: grid;
    gap: 5px;
    margin-bottom: 10px;
    color: #5f4633;
    font-size: .86rem;
}

.home-product-card__sizes {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 34px;
    margin-bottom: 14px;
}

.home-product-card__sizes span {
    display: inline-flex;
    align-items: center;
    min-height: 25px;
    padding: 3px 8px;
    border: 1px solid #f0dfc7;
    border-radius: 6px;
    background: #fffaf2;
    color: #5f4633;
    font-size: .8rem;
    font-weight: 800;
    line-height: 1.2;
}

.home-product-card__sizes .is-muted {
    color: #9a7a5f;
    font-weight: 500;
}

.home-product-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #f0dfc7;
}

.home-product-card__price {
    color: #b45309;
    font-size: 1.05rem;
    font-weight: 900;
}

.home-product-card__button {
    border: 0;
    border-radius: 7px;
    background: #56292d;
    color: #fff;
    font-size: .85rem;
    font-weight: 800;
    white-space: nowrap;
}

.home-product-card__button:hover {
    background: #3f1e21;
    color: #fff;
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

.mission-section {
    position: relative;
    padding: 52px 0 34px;
    background:
        radial-gradient(circle at top left, rgba(255, 214, 102, 0.12), transparent 24%),
        linear-gradient(135deg, #0d2438 0%, #134967 52%, #0f766e 100%);
}

.mission-shell {
    position: relative;
    overflow: hidden;
    border-radius: 0;
    padding: 0;
    background: transparent;
    box-shadow: none;
}

.mission-shell::before,
.mission-shell::after {
    content: "";
    position: absolute;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    pointer-events: none;
}

.mission-shell::before {
    width: 280px;
    height: 280px;
    top: -120px;
    right: -90px;
}

.mission-shell::after {
    width: 220px;
    height: 220px;
    bottom: -100px;
    left: -60px;
}

.mission-pro {
    position: relative;
    z-index: 1;
    padding: 32px;
    border-radius: 0;
    background: transparent;
    border: 0;
    backdrop-filter: none;
}

.mission-head span {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1.6px;
    color: #f8fafc;
    font-weight: 700;
}

.mission-head h2 {
    margin: 18px 0 14px;
    font-size: 38px;
    line-height: 1.12;
    color: #ffffff;
}

.mission-head p {
    max-width: 640px;
    color: rgba(248, 250, 252, 0.84);
    margin-bottom: 22px;
    font-size: 15px;
}

.mission-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 22px;
}

.mission-stat {
    border-radius: 18px;
    padding: 16px 18px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.14);
}

.mission-stat strong {
    display: block;
    color: #ffffff;
    font-size: 28px;
    line-height: 1;
    margin-bottom: 8px;
}

.mission-stat span {
    color: rgba(255, 255, 255, 0.72);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.mission-points {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.mission-point {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    background: rgba(255, 255, 255, 0.08);
    padding: 16px;
    color: #f8fafc;
    min-height: 100%;
}

.mission-point-index {
    flex: 0 0 38px;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 214, 102, 0.18);
    color: #ffd666;
    font-size: 13px;
    font-weight: 800;
}

.mission-point strong {
    display: block;
    margin-bottom: 4px;
    font-size: 15px;
    color: #ffffff;
}

.mission-point span {
    display: block;
    font-size: 13px;
    color: rgba(248, 250, 252, 0.78);
}

.mission-form {
    border-radius: 24px;
    background: #ffffff;
    border: 1px solid rgba(207, 223, 238, 0.85);
    box-shadow: 0 18px 36px rgba(6, 24, 44, 0.18);
    padding: 22px;
    height: 100%;
}

.mission-form-top {
    padding-bottom: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.mission-form-top span {
    display: inline-block;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #0f766e;
    font-weight: 700;
    margin-bottom: 8px;
}

.mission-form h5 {
    margin-bottom: 8px;
    color: #16324f;
    font-size: 22px;
}

.mission-form p {
    color: #475569;
    font-size: 14px;
    margin-bottom: 0;
}

.mission-trust-list {
    display: grid;
    gap: 10px;
    margin-bottom: 18px;
}

.mission-trust-item {
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 16px;
    background: #f8fbff;
    border: 1px solid #d9e6f2;
    padding: 12px 14px;
}

.mission-trust-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d8f3e8 0%, #e0f2fe 100%);
    color: #0f766e;
    font-weight: 800;
}

.mission-trust-item strong {
    display: block;
    font-size: 14px;
    color: #0f172a;
}

.mission-trust-item span {
    display: block;
    font-size: 12px;
    color: #64748b;
}

.mission-form .form-control {
    border-radius: 14px;
    border-color: #d0dbea;
    min-height: 46px;
    padding: 12px 14px;
}

.mission-form textarea.form-control {
    min-height: 118px;
}

.mission-form .btn-group-mission {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.mission-form .btn-group-mission .site-btn,
.mission-form .btn-group-mission .partner-btn {
    padding: 12px 18px;
    border-radius: 14px;
    font-size: 14px;
}

.fresh-grid {
    margin-top: 22px;
}

.fresh-card {
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    background: rgba(255, 255, 255, 0.08);
    padding: 16px 12px;
    text-align: center;
    transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    box-shadow: 0 10px 24px rgba(6, 24, 44, 0.12);
}

.fresh-card:hover {
    transform: translateY(-4px);
    background: rgba(255, 255, 255, 0.12);
    box-shadow: 0 16px 28px rgba(6, 24, 44, 0.16);
}

.fresh-card .feature__item__icon {
    width: 66px;
    height: 66px;
    margin: 0 auto 10px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
}

.fresh-card .feature__item__icon img {
    width: 36px;
    height: 36px;
    object-fit: contain;
}

.fresh-card h6 {
    margin: 0;
    color: #ffffff;
    font-size: 14px;
}

.latest-news-section {
    position: relative;
    padding: 72px 0 54px;
    margin-top: 34px;
    background:
        linear-gradient(180deg, #f6f8fb 0%, #eef3f8 100%);
}

.latest-news-section::before {
    content: "";
    position: absolute;
    top: -34px;
    left: 0;
    right: 0;
    height: 68px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, #f6f8fb 100%);
}

.section-divider {
    position: relative;
    padding: 8px 0 0;
}

.section-divider::before {
    content: "";
    display: block;
    width: min(180px, 40%);
    height: 1px;
    margin: 0 auto;
    background: linear-gradient(90deg, rgba(148, 163, 184, 0) 0%, rgba(148, 163, 184, 0.8) 50%, rgba(148, 163, 184, 0) 100%);
}

.section-divider::after {
    content: "";
    display: block;
    width: 14px;
    height: 14px;
    margin: -7px auto 0;
    border-radius: 999px;
    background: #f59e0b;
    box-shadow: 0 0 0 8px #ffffff;
}

.latest-news-shell {
    border-radius: 0;
    padding: 8px 0 0;
    background: transparent;
    border: 0;
    box-shadow: none;
}

.latest-pro-head {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 16px;
    margin-bottom: 22px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e2e8f0;
}

.latest-pro-head span {
    display: inline-block;
    margin-bottom: 10px;
    color: #b45309;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    font-weight: 800;
}

.latest-pro-head h2 {
    margin: 0 0 8px;
    font-size: 36px;
    color: #0f172a;
    line-height: 1.15;
}

.latest-pro-head p {
    margin: 0;
    max-width: 640px;
    color: #475569;
    font-size: 15px;
}

.latest-pro-head .btn {
    border-radius: 999px;
    padding: 10px 18px;
    font-weight: 700;
}

.latest-news-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 18px;
}

.latest-news-col {
    grid-column: span 4;
}

.latest-news-col.is-featured {
    grid-column: span 6;
}

.latest-card {
    display: flex;
    flex-direction: column;
    border-radius: 22px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.07);
    height: 100%;
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}

.latest-card:hover {
    transform: translateY(-5px);
    border-color: #d5dee9;
    box-shadow: 0 22px 38px rgba(15, 23, 42, 0.12);
}

.latest-card .latest__blog__item__pic {
    position: relative;
    height: 220px;
    background-size: cover;
    background-position: center;
}

.latest-card .latest__blog__item__pic::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0) 40%, rgba(15, 23, 42, 0.18) 100%);
}

.latest-card .latest__blog__item__text {
    padding: 18px 18px 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.news-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #64748b;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
    margin-bottom: 12px;
}

.news-meta::before {
    content: "";
    width: 26px;
    height: 2px;
    border-radius: 999px;
    background: #f59e0b;
}

.latest-card h5 {
    margin-bottom: 10px;
    min-height: 58px;
    color: #0f172a;
    font-size: 21px;
    line-height: 1.3;
}

.latest-card p {
    color: #475569;
    margin-bottom: 18px;
    flex: 1;
}

.latest-card a {
    color: #0f172a;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.latest-news-col.is-featured .latest-card {
    background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
}

.latest-news-col.is-featured .latest-card .latest__blog__item__pic {
    height: 290px;
}

.latest-news-col.is-featured .latest-card h5 {
    font-size: 28px;
    min-height: auto;
}

@media (max-width: 768px) {
    .hero-wrap .slider-item {
        min-height: 420px;
    }

    .hero-content {
        margin: 16px;
        padding: 24px 18px;
    }

    .hero-title {
        font-size: 30px;
    }

    .section-head {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .mission-shell,
    .mission-pro,
    .latest-news-shell {
        padding: 16px;
    }

    .mission-shell,
    .mission-pro,
    .latest-news-shell {
        border-radius: 0;
    }

    .mission-head h2,
    .latest-pro-head h2 {
        font-size: 26px;
    }

    .mission-stats,
    .mission-points,
    .latest-news-grid {
        grid-template-columns: 1fr;
    }

    .latest-news-col,
    .latest-news-col.is-featured {
        grid-column: span 12;
    }

    .latest-news-col.is-featured .latest-card .latest__blog__item__pic {
        height: 220px;
    }

    .latest-news-section {
        margin-top: 20px;
        padding-top: 52px;
    }
}
</style>
@endpush


@section('content')
   
<section class="hero spad set-bg mb-0 pb-0" > 
    <div class="hero-wrap">
        <div class="home-slider owl-carousel">
            @forelse($sliderMedia ?? collect() as $media)
                <div class="slider-item" style="background-image:url({{ asset('storage/' . $media->file_path) }});">
                    <div class="overlay"></div>
                    <div class="container">
                        <div class="row no-gutters slider-text align-items-center justify-content-start">
                            <div class="col-md-6 ftco-animate">
                                <div class="text w-100">
                                    <div class="hero-content">
                                        <div class="hero-eyebrow">{{ $settings['slogan']->value ?? 'Giải pháp thực phẩm chuyên nghiệp' }}</div>
                                        <h1 class="hero-title">{{ $settings['brand_name']->value ?? 'Hoàng Long TNT' }}</h1>
                                        <p class="hero-desc">Giao hàng tận nơi.</p>
                                        <div class="hero-actions">
                                            <a href="{{ route('pages.product_list') }}" class="btn btn-warning me-2">Xem sản phẩm</a>
                                            <a href="{{ route('pages.contact') }}" class="btn btn-outline-light">Liên hệ ngay</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="slider-item" style="background-image:url({{ asset('img/hero/hero-bg.jpg') }});">
                    <div class="overlay"></div>
                    <div class="container">
                        <div class="row no-gutters slider-text align-items-center justify-content-start">
                            <div class="col-md-6 ftco-animate">
                                <div class="text w-100">
                                    <div class="hero-content">
                                        <div class="hero-eyebrow">{{ $settings['slogan']->value ?? 'Giải pháp thực phẩm chuyên nghiệp' }}</div>
                                        <h1 class="hero-title">{{ $settings['brand_name']->value ?? 'Hoàng Long TNT' }}</h1>
                                        <p class="hero-desc">Chua có hình ảnh</p>
                                        <div class="hero-actions">
                                            <a href="{{ route('pages.product_list') }}" class="btn btn-warning me-2">Xem sản phẩm</a>
                                            <a href="{{ route('pages.contact') }}" class="btn btn-outline-light">Liên hệ ngay</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section> 
<section  class="product featured-products-section my-0">
<div class="container"> 
    <div class="row pb-4"> 
        <div class="col-md-12 pb-4">
            <div class="featured-products-head">
                <div>
                    <div class="featured-products-eyebrow">Sản phẩm tươi sạch</div>
                    <h4>Sản phẩm chủ đạo</h4>
                    <p>Chọn sản phẩm để xem đầy đủ size, tồn kho và lên đơn nhanh.</p>
                </div>
                <a href="{{ route('pages.product_list') }}" class="featured-products-link">Xem tất cả</a>
            </div>

            <div class="row g-3">
                @foreach($featuredProducts as $product)
                    @php
                        $image = $product->avatar?->media?->file_path
                            ? asset('storage/' . $product->avatar->media->file_path)
                            : 'https://via.placeholder.com/420x320?text=San+pham';
                        $availableVariants = $product->variants->filter(fn ($variant) => (int) ($variant->available_stock ?? 0) > 0);
                        $prices = $product->variants->map(fn ($variant) => (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0))->filter(fn ($price) => $price > 0);
                        $minPrice = $prices->min();
                        $sizeLabels = $availableVariants->map(function ($variant) {
                            $attributeSize = $variant->values->firstWhere('attribute.code', 'size')?->value;
                            return $variant->size ?: $attributeSize;
                        })->filter()->unique()->values();
                    @endphp
                    <div class="col-sm-6 col-lg-3">
                        <article class="home-product-card">
                            <a href="{{ route('pages.product_detail', $product->slug) }}" class="home-product-card__image">
                                <img src="{{ $image }}" alt="{{ $product->name }}">
                            </a>
                            <div class="home-product-card__body">
                                <div class="home-product-card__category">{{ $product->category?->name ?? 'Chưa phân loại' }}</div>
                                <h5>
                                    <a href="{{ route('pages.product_detail', $product->slug) }}">{{ $product->name }}</a>
                                </h5>

                                <div class="home-product-card__meta">
                                    <span><i class="bi bi-rulers"></i> {{ $sizeLabels->count() }} size còn hàng</span>
                                    <span><i class="bi bi-box-seam"></i> {{ $availableVariants->sum('available_stock') }} {{ strtolower($product->unit_label) }}</span>
                                </div>

                                <div class="home-product-card__sizes">
                                    @forelse($sizeLabels->take(5) as $size)
                                        <span>{{ $size }}</span>
                                    @empty
                                        <span class="is-muted">Chưa có size còn hàng</span>
                                    @endforelse
                                    @if($sizeLabels->count() > 5)
                                        <span>+{{ $sizeLabels->count() - 5 }}</span>
                                    @endif
                                </div>

                                <div class="home-product-card__footer">
                                    <div>
                                        <div class="small text-muted">Giá từ</div>
                                        <div class="home-product-card__price">
                                            {{ $minPrice ? number_format($minPrice, 0, ',', '.') . 'đ' : 'Liên hệ' }}
                                        </div>
                                    </div>
                                    <a href="{{ route('pages.product_detail', $product->slug) }}" class="btn home-product-card__button">
                                        Chọn sản phẩm
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
</section> 
  <section class="feature py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="feature__text">
                        <div class="section-title">
                            <span>Về chúng tôi</span>
                            <h2>SỨ MỆNH SẢN PHẨM TƯƠI SẠCH </h2>
                        </div>
                        <div class="feature__text__desc">
                           <p>Hoàng long TNT mang lại giải pháp thực phẩm tươi sạch cho hộ gia đình và doanh nghiệp giúp cuộc sống thêm an toàn </p>
                            <div class="contact__form">
                                <form action="#">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <input type="text" placeholder="Name" name="fname">
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="text" placeholder="Email" name="email">
                                        </div>
                                    </div>
                                    <input type="text" placeholder="Subject" name="subject">
                                    <textarea placeholder="Your Question" name="question"></textarea>
                                    <button type="submit" class="site-btn">GỬI LIÊN HỆ</button>
                                    <button type="reset" class="site-btn partner-btn">NHẬP LẠI</button>
                                </form>
                            </div>


                        </div>
                        
                    </div>
                </div>
                <div class="col-lg-4 offset-lg-4">
                    <div class="row">
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-1.png" alt="">
                                </div>
                                <h6>Gà</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-2.png" alt="">
                                </div>
                                <h6>Vịt</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-3.png" alt="">
                                </div>
                                <h6>...</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-4.png" alt="">
                                </div>
                                <h6>....</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-5.png" alt="">
                                </div>
                                <h6>Rau xanh</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-6.png" alt="">
                                </div>
                                <h6>...</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="section-divider" aria-hidden="true"></div>
 
    <div class="map">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12"> 
                    <div class="sc_googlemap_content_wrap">
                        <div class="sc_googlemap">
                            <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.642530245644!2d106.61261387365528!3d10.762008859462329!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752d0078dc7fbf%3A0x1f9e629c52b1072c!2zQ8O0bmcgVHkgVGjhu7FjIHBo4bqpbSBIb8OgbmcgTG9uZyBUTlQ!5e0!3m2!1svi!2s!4v1773195184642!5m2!1svi!2s"
                            scrolling="no"
                            marginheight="0"
                            marginwidth="0"
                            frameborder="0"
                            width="100%"
                            height="400px"
                            aria-label="One"></iframe>
                        </div>
                     </div>
                </div>
            </div>
        </div>
    </div>

@endsection
