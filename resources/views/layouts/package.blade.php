<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đóng hàng – @yield('title', 'Đóng hàng')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}" type="text/css">
    @stack('styles')
    <style>
        :root {
            --sidebar-width: 240px;
            --theme-primary: #0d6efd;
            --theme-primary-hover: #0a58ca;
            --theme-accent: #ffc107;
            --theme-accent-hover: #e0a800;
            --sidebar-bg: #0d223f;
            --sidebar-bg-strong: #0a1830;
            --sidebar-active: #ffc107;
        }
        body {
            min-height: 100vh;
            background: #f7fbfb;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 1.04rem;
        }
        .pkg-sidebar {
            width: var(--sidebar-width); min-height: 100vh; background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 200; display: flex; flex-direction: column;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
        }
        .pkg-brand {
            padding: 1rem 1.25rem; background: var(--sidebar-bg-strong); color: #fff; font-weight: 700;
            font-size: 1.02rem; display: flex; align-items: center; gap: .5rem; border-bottom: 1px solid rgba(255,255,255,.15);
        }
        .pkg-brand .badge { font-size: .65rem; background: var(--theme-accent); color: #1f2937; }
        .pkg-nav-section {
            padding: .75rem 1.25rem .25rem; font-size: .78rem; color: #fef3c7;
            text-transform: uppercase; letter-spacing: .08em; font-weight: 600;
        }
        .pkg-nav-link {
            display: flex; align-items: center; gap: .6rem; padding: .55rem 1.25rem;
            color: #e6fffb; font-size: .95rem; text-decoration: none;
            border-left: 3px solid transparent; transition: all .15s;
        }
        .pkg-nav-link:hover { color: #fff; background: rgba(255,255,255,.08); border-left-color: var(--theme-accent); }
        .pkg-nav-link.active { color: #fff; background: rgba(255,193,7,.16); border-left-color: var(--sidebar-active); }
        .pkg-nav-link .badge { margin-left: auto; font-size: .65rem; }
        .pkg-main { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }
        .pkg-topbar {
            background: #fff; border-bottom: 1px solid #d8ece9; padding: .8rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100;
        }
        .pkg-topbar .breadcrumb { margin-bottom: 0; font-size: .95rem; }
        .pkg-topbar h6 { font-size: 1.08rem; }
        .pkg-topbar .small { font-size: .93rem !important; }
        .pkg-content { padding: 1.5rem; flex: 1; }
        @media (max-width: 768px) {
            .pkg-sidebar {
                width: min(86vw, 320px);
                transform: translateX(-100%);
                transition: transform .22s ease;
            }
            .pkg-sidebar.mobile-open { transform: translateX(0); }
            .pkg-main { margin-left: 0; }
            .pkg-topbar { padding: .65rem .85rem; }
            .pkg-content {
                padding: .9rem;
                padding-bottom: calc(.9rem + env(safe-area-inset-bottom, 0px));
            }
        }
        .pkg-sidebar nav {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            padding-bottom: .35rem;
        }
    </style>
</head>
<body class="{{ !empty($isMobileClient) ? 'is-mobile-client' : '' }}">
    @include('layouts.notifications')
    <!-- Sidebar -->
    <aside class="pkg-sidebar">
        <div class="pkg-brand">
            <i class="bi bi-box2-fill fs-5"></i>
            <span>Đóng hàng</span>
            <span class="badge ms-auto">PKG</span>
        </div>
        <nav class="mt-1 flex-grow-1 overflow-auto">
            <div class="pkg-nav-section">Đóng hàng</div>
            <a href="{{ route('package.dashboard') }}" class="pkg-nav-link {{ request()->routeIs('package.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('package.orders') }}" class="pkg-nav-link {{ request()->routeIs('package.orders*') ? 'active' : '' }}">
                <i class="bi bi-list-ol"></i> Nhận đơn đóng hàng
            </a>
            <a href="{{ route('package.incoming-orders') }}" class="pkg-nav-link {{ request()->routeIs('package.incoming-orders*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Tiếp nhận đơn
            </a>
            <a href="{{ route('package.incoming-inventory') }}" class="pkg-nav-link {{ request()->routeIs('package.incoming-inventory*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-in-down"></i> Tiếp nhận hàng
            </a>
            <a href="{{ route('package.incoming-returns') }}" class="pkg-nav-link {{ request()->routeIs('package.incoming-returns*') ? 'active' : '' }}">
                <i class="bi bi-arrow-return-left"></i> Tiếp nhận đơn trả về
            </a>
            <a href="{{ route('package.order-changes') }}" class="pkg-nav-link {{ request()->routeIs('package.order-changes') ? 'active' : '' }}">
                <i class="bi bi-pencil-square"></i> Yêu cầu thay đổi đơn
            </a>
            <div class="pkg-nav-section">Tồn kho</div>
            <a href="{{ route('package.inventory') }}" class="pkg-nav-link {{ request()->routeIs('package.inventory') ? 'active' : '' }}">
                <i class="bi bi-stack"></i> Thống kê tồn kho
            </a>
        </nav>
    </aside>
    <div class="pkg-main">
        <header class="pkg-topbar">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light d-md-none js-pkg-toggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h6 class="mb-0 fw-semibold">@yield('title', 'Đóng hàng')</h6>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center gap-2 text-decoration-none" id="dropdownAccount" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 text-secondary"></i>
                        <div class="small text-end d-none d-md-block">
                            <div class="fw-semibold">{{ auth()->user()->name }}</div>
                            <div style="font-size:.85rem; color:#64748b;">{{ auth()->user()->warehouse?->name ?? 'Chưa gán kho' }}</div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dropdownAccount">
                        <li class="dropdown-header">Tài khoản</li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person-lines-fill me-2"></i> Thông tin cá nhân</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-key me-2"></i> Đổi mật khẩu</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Đăng xuất</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        <div class="pkg-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.pkg-sidebar');
            const toggle = document.querySelector('.js-pkg-toggle');
            if (sidebar && toggle) {
                toggle.addEventListener('click', function () {
                    sidebar.classList.toggle('mobile-open');
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
