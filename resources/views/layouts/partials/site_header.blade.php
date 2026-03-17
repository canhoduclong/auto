<div class="offcanvas-menu-overlay"></div>
<div class="offcanvas-menu-wrapper">
    <div class="offcanvas__widget">
        <a href="#"><i class="fa fa-cart-plus"></i></a>
        <a href="#" class="search-switch"><i class="fa fa-search"></i></a>
        <a href="#" class="primary-btn">{{ __('site.add') }}</a>
    </div>
    <div class="offcanvas__logo">
        <a href="{{ route('home') }}"><img src="{{ asset('img/logo-auto-taybac.png') }}" alt=""></a>
    </div>
    <div id="mobile-menu-wrap"></div>
    <ul class="offcanvas__widget__add">
        <li><i class="fa fa-clock-o"></i> {{ __('site.weekday_hours') }}</li>
        <li><i class="fa fa-envelope-o"></i> email@gmail.com</li>
    </ul>
    <div class="offcanvas__phone__num">
        <i class="fa fa-phone"></i>
        <span>0909 990 909</span>
    </div>
    <div class="offcanvas__social">
        <a href="#"><i class="fa fa-facebook"></i></a>
        <a href="#"><i class="fa fa-twitter"></i></a>
        <a href="#"><i class="fa fa-google"></i></a>
        <a href="#"><i class="fa fa-instagram"></i></a>
    </div>
</div>
<header class="header ">
    <div class="header__top">
            <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <ul class="header__top__widget">
                        <li><i class="fa fa-clock-o"></i>{{ $settings['slogan']->value ?? __('site.slogan_fallback') }}</li>
                        <li><i class="fa fa-phone"></i>  {{ $settings['HOTLINE']->value ?? '0909 990 909' }}</li>
                    </ul>
                </div>
                <div class="col-lg-5  d-flex justify-content-end align-items-center">
                    <ul class="header__top__widget me-3">
                        <li><a href="{{ route('locale.switch', 'vi') }}">{{ __('common.language.vi') }}</a></li>
                        <li><a href="{{ route('locale.switch', 'en') }}">{{ __('common.language.en') }}</a></li>
                    </ul>
                    <ul class="header__top__widget">
                        <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                        <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fa fa-google"></i></a></li>
                        <li><a href="#"><i class="fa fa-instagram"></i></a></li>
                    </ul> 
                   
                
                </div>
            </div>
        </div>
    </div> 
     <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="header__logo"> 
                    <a href="{{ route('home') }}">
                        @if(isset($settings['logo']) && $settings['logo']->value)
                            @php
                                $media = App\Models\Media::find($settings['logo']->value);
                            @endphp
                            @if($media)
                                <img src="{{ asset('storage/' . $media->file_path) }}" alt="logo" height="50">
                            @endif
                        @else
                            <h2>{{ $settings['brand_name']->value ?? __('site.logo_fallback') }}</h2>
                        @endif
                    </a>
                </div>
            </div>
            <div class="col-lg-8 d-flex justify-content-end align-items-center">
                <div class="header__nav ">
                    <nav class="header__menu "> 
                        <ul class="mb-0 ml-0 pb-0 pl-0">
                            <li><a href="{{ route('home') }}" class="nav-link px-2 link-secondary">{{ __('site.home') }}</a></li>
                            <li><a href="{{ route('pages.about') }}" class="nav-link px-2 link-dark">{{ __('site.about') }}</a></li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('pages.products_by_category') }}" >
                                    {{ __('site.products') }}
                                </a> 
                            </li>
                            <li><a href="{{ route('posts.list') }}" class="nav-link px-2 link-dark">{{ __('site.posts') }}</a></li>
                            <li><a href="{{ route('pages.contact') }}" class="nav-link px-2 link-dark">{{ __('site.contact') }}</a></li>
                        </ul> 
 
                    </nav>
                    <div class="header__nav__widget">
                        <div class="header__nav__widget__btn  d-flex justify-content-end align-items-center">

                            <x-cart-widget :cartCount="count(session('cart', []))" class="me-3" /> 
                            @auth
                                <div class="dropdown">
                                    <a href="#" class="d-block link-white text-decoration-none dropdown-toggle btn btn-outline-primary" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ Auth::user()->name }}
                                    </a>
                                    <ul class="dropdown-menu text-small" aria-labelledby="dropdownUser1">
                                        <li><a class="dropdown-item" href="{{ route('pages.my_dashboard') }}">{{ __('site.profile') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('pages.my_orders') }}">{{ __('site.my_orders') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('pages.my_customer') }}">{{ __('site.my_customers') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ url('/dashboard') }}">{{ __('site.dashboard') }}</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logout') }}"
                                                onclick="event.preventDefault();
                                                                document.getElementById('logout-form').submit();">
                                                {{ __('site.logout') }}
                                            </a>
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                                @csrf
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-outline-primary">{{ __('site.login') }}</a>
                            @endauth

                             
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="canvas__open"><i class="fa fa-bars"></i></div>
    </div> 
</header>

 