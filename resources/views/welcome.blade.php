@extends('layouts.site')



@section('content')
   
<section class="hero spad set-bg" > 
    <div class="hero-wrap">
	    <div class="home-slider owl-carousel">

            <div class="slider-item" style="background-image:url(img/SLIDER-3.jpg);">
                <div class="overlay"></div>
                <div class="container">
                <div class="row no-gutters slider-text align-items-center justify-content-start">
                    <div class="col-md-6 ftco-animate">
                        <div class="text w-100">
                            
                        </div>
                    </div>
                    </div>
                </div>
            </div>

	      <div class="slider-item" style="background-image:url(img/slider-1.png);">
	      	<div class="overlay"></div>
	        <div class="container">
	          <div class="row no-gutters slider-text align-items-center justify-content-start">
		          <div class="col-md-6 ftco-animate">
		          	<div class="text w-100">
		          		<h2>Dịch vụ dọn xe AUTO TÂY BẮC</h2>
			            <h1 class="mb-4">DỌN XE CHUYÊN NGHIỆP </h1> 
		            </div>
		          </div>
		        </div>
	        </div>
	      </div>

     
	    </div>
	  </div>
</section>

<div class="container">
    <div class="banner__item mb-4">
        <img src="{{ asset('storage/media/banner-auto-tay-bac.png') }}" alt=""> 
    </div>
</div>

@include('partials.product-search')  

<div class="container"> 
    <div class="row mt-5"> 
        <div class="col-md-12">
            <!-- Sản phẩm mới -->
            <div class="row">
                <div class="col-12 text-center mb-4">
                    <h4 class="fs-3 brand-color text-uppercase fw-bold">SẢN PHẨM MỚI</h4>
                </div>
            </div> 
            <div class="row">
                 @foreach($variants as $variant)
                <div class="col-lg-3 col-md-3">
                    <div class="car__item">
                        <div class="car__item__pic__slider owl-carousel">
                            @if(!empty($variant->product->avatar) && $variant->product->avatar->media)
                               <img src="{{ asset('storage/'.$variant->product->avatar->media->file_path) }}" >
                            @endif
                            @foreach($variant->product->gallery as $link)
                               @if($link->media)
                                   <img src="{{ asset('storage/' . $link->media->file_path) }}">
                               @endif
                           @endforeach
                        </div>
                        <div class="car__item__text">
                            <div class="car__item__text__inner"> 
                                <h5><a href="{{ route('pages.variant_detail', $variant) }}" class=" text-uppercase ">{{ $variant->product->name }} - {{ $variant->name }}</a></h5>
                                @if($variant->product->brand)
                                    <p class="card-text">Thương hiệu: {{ $variant->product->brand->name }}</p>
                                @endif 
                                @if($variant->sku)
                                    <p class="card-text">Mã sản phẩm: {{ $variant->sku }}</p>
                                @endif
                                <p class="card-text">Giá: {{ number_format($variant->final_price, 0, '.', ',') }} VNĐ</p>
                                <div class="car__item__prưice mt-2 mb-4"> 
                                    <a href="{{ route('pages.variant_detail', $variant) }}" class="btn btn-info  btn-brand btn-sm">Chi tiết</a>
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

  <section class="feature  my-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="feature__text">
                        <div class="section-title">
                            <span>DỊCH VỤ CỦA CHÚNG TÔI</span>
                            <h2>AUTO TÂY BẮC </h2>
                        </div>
                        <div class="feature__text__desc">
                           <p>Bạn cần chúng tôi hỗ trợ về xe? hãy để lại liên hệ để chúng tôi có thể phục vụ bạn được tốt hơn</p>
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
                                <h6>Engine</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-2.png" alt="">
                                </div>
                                <h6>Turbo</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-3.png" alt="">
                                </div>
                                <h6>Colling</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-4.png" alt="">
                                </div>
                                <h6>Suspension</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-5.png" alt="">
                                </div>
                                <h6>Electrical</h6>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-4 col-6">
                            <div class="feature__item">
                                <div class="feature__item__icon">
                                    <img src="img/feature/feature-6.png" alt="">
                                </div>
                                <h6>Brakes</h6>
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
                            src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3917.028662810542!2d106.47366717504458!3d10.961207389198991!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMTDCsDU3JzQwLjQiTiAxMDbCsDI4JzM0LjUiRQ!5e0!3m2!1svi!2s!4v1763396056744!5m2!1svi!2s"
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

 