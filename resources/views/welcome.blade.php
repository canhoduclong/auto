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
<section  class="product my-0">
<div class="container"> 
    <div class="row mt-5"> 
        <div class="col-md-12">
            <!-- Sản phẩm mới -->
            <div class="my-2 py-4 text-center">
                <h4 class="brand-color text-uppercase fw-bold  text-center">Sản phẩm chủ đạo</h4>
                <!--a href="{{ route('pages.product_list') }}" class="btn btn-outline-dark btn-sm">Xem tất cả</a-->
            </div> 
            <div class="row">
                @foreach($variants as $variant)
                @if(!$variant->product)
                    @continue
                @endif
                <div class="col-lg-3 col-md-3">
                    <div class="car__item product-card">
                        <div class="car__item__pic__slider owl-carousel">
                            @if(!empty($variant->product->avatar) && $variant->product->avatar->media)
                               <img src="{{ asset('storage/'.$variant->product->avatar->media->file_path) }}" >
                            @endif
                            @foreach(($variant->product->gallery ?? collect()) as $link)
                               @if($link->media)
                                   <img src="{{ asset('storage/' . $link->media->file_path) }}">
                               @endif
                           @endforeach
                        </div>
                        <div class="car__item__text">
                            <div class="car__item__text__inner"> 
                                <h5><a href="{{ route('pages.variant_detail', $variant) }}" class="text-uppercase">{{ $variant->product->name }} - {{ $variant->name }}</a></h5>
                                 @if($variant->sku)
                                    <p class="product-meta">Mã sản phẩm: {{ $variant->sku }}</p>
                                @endif
                                <p class="product-price">{{ number_format($variant->final_price, 0, '.', ',') }} VNĐ</p>
                                <div class="btn-group"> 
                                    <a href="{{ route('pages.variant_detail', $variant) }}" class="btn  btn-brand btn-sm">Chi tiết</a>
                                    <button class="btn btn-warning btn-sm add-to-cart" data-variant-id="{{ $variant->id }}">
                                        <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                                    </button>  
                                </div>
                            </div>
                        </div>
                    </div>
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
                                <h6>Bò</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-4.png" alt="">
                                </div>
                                <h6>Heo</h6>
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
                                <h6>Trái cây</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="latest spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title">
                        <span>Bản tin hàng ngày</span>
                        <h2>TIN MỚI CẬP NHẬT</h2> 
                    </div>
                </div>
            </div>
            <div class="row"> 
                @foreach($posts as $post) 
                    <div class="col-lg-4 col-md-6">
                        <div class="latest__blog__item">
                            @if($post->image)
                            <div class="latest__blog__item__pic set-bg" 
                                data-setbg="{{ asset('storage/' . $post->image) }}" 
                                style="background-image: url(&quot;{{ asset('storage/' . $post->image) }}&quot;);"
                                > 
                            @else
                                <div class="latest__blog__item__pic set-bg" 
                                data-setbg="img/latest-blog/lb-1.jpg" 
                                style="background-image: url(&quot;img/latest-blog/lb-1.jpg&quot;);"
                                >
                            @endif 
                                
                            </div>
                            <div class="latest__blog__item__text">
                                <h5>{{ $post->title }}</h5>
                                <p>{{ $post->excerpt }}.</p>
                                <a href="{{ route('posts.show', $post) }}">Xem thêm <i class="fa fa-long-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                @endforeach  
            </div>
        </div>
    </section>
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

 