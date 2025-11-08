@php
use App\Models\Setting;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ Setting::get('brand_name', 'My Website') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @stack('styles')
</head>
<body>
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
    
                <div class="text-end d-flex align-items-center">
                    <p class="slogan me-3 mb-0">{{ $settings['slogan']->value ?? 'Your slogan here' }}</p>
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
                        <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        @yield('content')
    </main>

    @yield('footer')

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>