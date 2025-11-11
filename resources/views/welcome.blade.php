@extends('layouts.site')



@section('content')
   
<section class="hero spad set-bg" > 
    <div class="hero-wrap">
	    <div class="home-slider owl-carousel">
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

	      <div class="slider-item" style="background-image:url(img/slider-2.jpg);">
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
	    </div>
	  </div>
</section>



<div class="container">
    <div class="banner__item">
        <img src="img/banner.jpg" alt=""> 
    </div>
    <!-- Danh mục sản phẩm -->
    <div class="row mt-5">
        <div class="col-md-3">
            <div class="car__sidebar">
                <div class="car__filter">
                    <h5>DANH MỤC SẢN PHẨM</h5> 
                    <ul>
                        @foreach($categories as $category)
                            <li><a href="#" class="list-group-item list-group-item-action">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <!-- Sản phẩm mới -->
            <div class="row">
                <div class="col-12">
                    <h2>Sản phẩm mới</h2>
                </div>
            </div>

            <div class="row">
                 @foreach($products as $product)
                <div class="col-lg-4 col-md-4">
                    <div class="car__item">
                        <div class="car__item__pic__slider owl-carousel">
                            <img src="img/cars/car-1.jpg" alt="">
                            <img src="img/cars/car-8.jpg" alt="">
                            <img src="img/cars/car-6.jpg" alt="">
                            <img src="img/cars/car-3.jpg" alt="">
                        </div>
                        <div class="car__item__text">
                            <div class="car__item__text__inner">
                                <div class="label-date">2016</div>
                                <h5><a href="#">{{ $product->name }}</a></h5>
                                <ul>
                                    <li><span>35,000</span> mi</li>
                                    <li>Auto</li>
                                    <li><span>700</span> hp</li>
                                </ul>
                            </div>
                            <div class="car__item__price">
                                <span class="car-option">For Rent</span>
                                <h6>$218<span>/Month</span></h6>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
                      

        </div>
    </div>
</div>

  <section class="feature spad">
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
                            <div class="latest__blog__item__pic set-bg" data-setbg="img/latest-blog/lb-1.jpg" style="background-image: url(&quot;img/latest-blog/lb-1.jpg&quot;);">
                                <ul>
                                    <li>By Polly Williams</li>
                                    <li>Dec 19, 2018</li>
                                    <li>Comment</li>
                                </ul>
                            </div>
                            <div class="latest__blog__item__text">
                                <h5>{{ $post->title }}</h5>
                                <p>{{ $post->excerpt }}.</p>
                                <a href="#">Xem thêm <i class="fa fa-long-arrow-right"></i></a>
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
                            src="https://maps.google.com/maps?t=m&output=embed&iwloc=near&z=14&q=40.66964776535992,-74.22803132273303"
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

 