<div class="offcanvas-menu-overlay"></div>
<div class="offcanvas-menu-wrapper">
    <div class="offcanvas__widget">
        <a href="#"><i class="fa fa-cart-plus"></i></a>
        <a href="#" class="search-switch"><i class="fa fa-search"></i></a>
        <a href="#" class="primary-btn">Add Car</a>
    </div>
    <div class="offcanvas__logo">
        <a href="./index.html"><img src="img/logo.png" alt=""></a>
    </div>
    <div id="mobile-menu-wrap"></div>
    <ul class="offcanvas__widget__add">
        <li><i class="fa fa-clock-o"></i> Week day: 08:00 am to 18:00 pm</li>
        <li><i class="fa fa-envelope-o"></i> Info.colorlib@gmail.com</li>
    </ul>
    <div class="offcanvas__phone__num">
        <i class="fa fa-phone"></i>
        <span>(+12) 345 678 910</span>
    </div>
    <div class="offcanvas__social">
        <a href="#"><i class="fa fa-facebook"></i></a>
        <a href="#"><i class="fa fa-twitter"></i></a>
        <a href="#"><i class="fa fa-google"></i></a>
        <a href="#"><i class="fa fa-instagram"></i></a>
    </div>
</div>
<header class="header">
    <div class="header__top">
            <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <ul class="header__top__widget">
                        <li><i class="fa fa-clock-o"></i>{{ $settings['slogan']->value ?? 'Your slogan here' }}</li>
                        <li><i class="fa fa-envelope-o"></i>  (+12) 345 678 910</li>
                    </ul>
                </div>
                <div class="col-lg-5  d-flex justify-content-end align-items-center">
                    <ul class="header__top__widget"> 
                        <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                        <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fa fa-google"></i></a></li>
                        <li><a href="#"><i class="fa fa-instagram"></i></a></li>
                    </ul> 
                    <x-cart-widget :cartCount="count(session('cart', []))" class="me-3" />
                    @auth
                        <div class="dropdown">
                            <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu text-small" aria-labelledby="dropdownUser1">
                                <li><a class="dropdown-item" href="{{ route('pages.my_dashboard') }}">Hồ sơ</a></li>
                                <li><a class="dropdown-item" href="{{ route('pages.my_orders') }}">Đơn hàng của bạn</a></li>
                                <li><a class="dropdown-item" href="{{ route('pages.my_customer') }}">Khách hàng của bạn</a></li>
                                <li><a class="dropdown-item" href="{{ url('/dashboard') }}">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault();
                                                        document.getElementById('logout-form').submit();">
                                        Đăng xuất
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="">Login</a>
                    @endauth
                
                </div>
            </div>
        </div>
    </div> 
     <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="header__logo">
                    <a href="./">
                        @if(isset($settings['logo']) && $settings['logo']->value)
                            @php
                                $media = App\Models\Media::find($settings['logo']->value);
                            @endphp
                            @if($media)
                                <img src="{{ asset('storage/' . $media->file_path) }}" alt="logo" height="50">
                            @endif
                        @else
                            <h2>{{ $settings['brand_name']->value ?? 'My Website' }}</h2>
                        @endif
                    </a>
                </div>
            </div>
            <div class="col-lg-8 d-flex justify-content-end align-items-center">
                <div class="header__nav ">
                    <nav class="header__menu "> 
                        <ul class="mb-0 pb-0">
                            <li><a href="{{ route('home') }}" class="nav-link px-2 link-secondary">Trang chủ</a></li>
                            <li><a href="{{ route('pages.about') }}" class="nav-link px-2 link-dark">Giới thiệu</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Sản phẩm
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item"  href="{{ route('site.variants') }}" >Biến thể mới</a></li>
                                    <li><a class="dropdown-item" href="{{ route('pages.products_by_category') }}">Sản phẩm (phân loại)</a></li>
                                    <li><a class="dropdown-item" href="{{ route('pages.product_list') }}">Sản phẩm (danh sách)</a></li>
                                </ul>
                            </li>
                            <li><a href="{{ route('posts.list') }}" class="nav-link px-2 link-dark">Tin tức</a></li>
                            <li><a href="{{ route('pages.contact') }}" class="nav-link px-2 link-dark">Liên hệ</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <div class="canvas__open"><i class="fa fa-bars"></i></div>
    </div> 
</header>

 