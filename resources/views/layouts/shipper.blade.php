<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shipper – @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}" type="text/css">
    @stack('styles')
    <style>
        :root {
            --sidebar-width: 240px;
            --theme-primary: #0f766e;
            --theme-primary-hover: #115e59;
            --theme-primary-soft: #ccfbf1;
            --theme-accent: #ffc107;
            --theme-accent-hover: #e0a800;
            --sidebar-bg: #0b5f59;
            --sidebar-bg-strong: #084c47;
            --sidebar-active: #ffc107;
        }
        body { min-height: 100vh; background: #f7fbfb; font-family: 'Inter', system-ui, sans-serif; }
        .sp-sidebar {
            width: var(--sidebar-width); min-height: 100vh; background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 200; display: flex; flex-direction: column;
        }
        .sp-brand {
            padding: 1rem 1.25rem; background: var(--sidebar-bg-strong); color: #fff; font-weight: 700;
            font-size: .95rem; display: flex; align-items: center; gap: .5rem; border-bottom: 1px solid rgba(255, 255, 255, .15);
        }
        .sp-brand .badge { font-size: .65rem; background: var(--theme-accent); color: #1f2937; }
        .sp-nav-section {
            padding: .75rem 1.25rem .25rem; font-size: .7rem; color: #fef3c7;
            text-transform: uppercase; letter-spacing: .08em; font-weight: 600;
        }
        .sp-nav-link {
            display: flex; align-items: center; gap: .6rem; padding: .55rem 1.25rem;
            color: #e6fffb; font-size: .875rem; text-decoration: none;
            border-left: 3px solid transparent; transition: all .15s;
        }
        .sp-nav-link:hover { color: #fff; background: rgba(255,255,255,.08); border-left-color: var(--theme-accent); }
        .sp-nav-link.active { color: #fff; background: rgba(255,193,7,.16); border-left-color: var(--sidebar-active); }
        .sp-nav-link .badge { margin-left: auto; font-size: .65rem; }
        .sp-main { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }
        .sp-topbar {
            background: #fff; border-bottom: 1px solid #d8ece9; padding: .8rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100;
        }
        .sp-content { padding: 1.5rem; flex: 1; }
        .mobile-drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.45);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .2s ease;
            z-index: 190;
        }
        body.mobile-menu-open { overflow: hidden; }
        body.mobile-menu-open .mobile-drawer-overlay {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .sp-topbar-left,
        .sp-topbar-right { min-width: 0; }
        .sp-topbar-title {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .sp-current-time { white-space: nowrap; }
        .stat-card { border: none; border-radius: .75rem; transition: transform .15s; }
        .stat-card:hover { transform: translateY(-2px); }

        .btn-primary {
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
        }
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: var(--theme-primary-hover);
            border-color: var(--theme-primary-hover);
        }

        .btn-success {
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
        }
        .btn-success:hover,
        .btn-success:focus,
        .btn-success:active {
            background-color: var(--theme-primary-hover);
            border-color: var(--theme-primary-hover);
        }

        .btn-outline-success,
        .btn-outline-info,
        .btn-outline-primary {
            color: var(--theme-primary);
            border-color: var(--theme-primary);
        }
        .btn-outline-success:hover,
        .btn-outline-info:hover,
        .btn-outline-primary:hover,
        .btn-outline-success:focus,
        .btn-outline-info:focus,
        .btn-outline-primary:focus {
            color: #fff;
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
        }

        .text-success,
        .text-success-emphasis,
        .text-info {
            color: var(--theme-primary) !important;
        }

        .bg-info,
        .bg-success {
            background-color: var(--theme-primary) !important;
        }

        .badge.bg-info,
        .badge.bg-success,
        .badge.text-bg-info,
        .badge.text-bg-success {
            background-color: var(--theme-primary) !important;
            color: #fff !important;
        }

        .btn-warning {
            background-color: var(--theme-accent);
            border-color: var(--theme-accent);
            color: #1f2937;
        }

        .btn-warning:hover,
        .btn-warning:focus,
        .btn-warning:active {
            background-color: var(--theme-accent-hover);
            border-color: var(--theme-accent-hover);
            color: #1f2937;
        }

        .btn-outline-warning {
            color: #9a6700;
            border-color: var(--theme-accent);
        }

        .btn-outline-warning:hover,
        .btn-outline-warning:focus,
        .btn-outline-warning:active {
            background-color: var(--theme-accent);
            border-color: var(--theme-accent);
            color: #1f2937;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(15, 118, 110, 0.42);
            box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.16);
        }

        a {
            color: var(--theme-primary);
        }

        a:hover {
            color: var(--theme-primary-hover);
        }

        @media (max-width: 768px) {
            .sp-sidebar {
                width: min(84vw, 320px);
                transform: translateX(-100%);
                transition: transform .22s ease;
                box-shadow: 0 12px 30px rgba(2, 6, 23, 0.28);
            }
            .sp-sidebar.mobile-open { transform: translateX(0); }
            .sp-main { margin-left: 0; }
            .sp-topbar {
                padding: .65rem .85rem;
                gap: .5rem;
                align-items: flex-start;
            }
            .sp-topbar-left {
                flex: 1 1 auto;
                max-width: calc(100% - 128px);
            }
            .sp-topbar-right {
                flex: 0 0 auto;
                gap: .4rem !important;
            }
            .sp-topbar-title { font-size: .95rem; }
            .sp-topbar-subtitle,
            .sp-current-time { display: none; }
            .sp-mobile-role {
                font-size: .72rem;
                padding: .22rem .45rem;
            }
            .sp-content { padding: .9rem; }
            .sp-nav-link { font-size: .9rem; padding: .7rem 1rem; }
            .sp-nav-section { padding: .8rem 1rem .3rem; }
        }
    </style>
</head>
<body class="{{ !empty($isMobileClient) ? 'is-mobile-client' : '' }}">
    <!-- Sidebar -->
    <aside class="sp-sidebar" id="shipper-sidebar">
        <div class="sp-brand">
            <i class="bi bi-bicycle fs-5"></i>
            <span>Shipper</span>
            <span class="badge ms-auto">SP</span>
        </div>
        <nav class="mt-1 flex-grow-1 overflow-auto">
            <div class="sp-nav-section">Tổng quan</div>
            <a href="{{ route('shipper.dashboard') }}" class="sp-nav-link {{ request()->routeIs('shipper.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="sp-nav-section">Nhận đơn</div>
            <a href="{{ route('shipper.available') }}" class="sp-nav-link {{ request()->routeIs('shipper.available') ? 'active' : '' }}">
                <i class="bi bi-collection"></i> Đơn có thể nhận
            </a>

            <div class="sp-nav-section">Lịch trình</div>
            <a href="{{ route('shipper.delivery-schedules') }}" class="sp-nav-link {{ request()->routeIs('shipper.delivery-schedules') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i> Lộ trình giao hàng
            </a>

            <div class="sp-nav-section">Đang giao</div>
            <a href="{{ route('shipper.my-orders') }}" class="sp-nav-link {{ request()->routeIs('shipper.my-orders') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Đơn giao của tôi
            </a>
            <a href="{{ route('shipper.warehouse-transfers') }}" class="sp-nav-link {{ request()->routeIs('shipper.warehouse-transfers') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Điều chuyển kho
            </a>

            <div class="sp-nav-section">Lịch sử</div>
            <a href="{{ route('shipper.history') }}" class="sp-nav-link {{ request()->routeIs('shipper.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Lịch sử giao hàng
            </a>
            <a href="{{ route('shipper.delivery-statistics') }}" class="sp-nav-link {{ request()->routeIs('shipper.delivery-statistics') ? 'active' : '' }}">
                <i class="bi bi-table"></i> Thống kê giao hàng
            </a>

            <div class="sp-nav-section">Tài chính</div>
            <a href="{{ route('shipper.finance-requests.index') }}" class="sp-nav-link {{ request()->routeIs('shipper.finance-requests.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> Phiếu yêu cầu của tôi
            </a>

            @if(auth()->user()->hasRole('manager_shipper') || auth()->user()->hasRole('admin'))
                <div class="sp-nav-section">Quản lý ship</div>
                <a href="{{ route('shipper.manage-assignments') }}" class="sp-nav-link {{ request()->routeIs('shipper.manage-assignments') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> Điều phối đơn hàng
                </a>
                <a href="{{ route('shipper.customers') }}" class="sp-nav-link {{ request()->routeIs('shipper.customers') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Quản lý khách hàng
                </a>
                <a href="{{ route('shipper.manage-fees') }}" class="sp-nav-link {{ request()->routeIs('shipper.manage-fees') ? 'active' : '' }}">
                    <i class="bi bi-cash-coin"></i> Quản lý phí ship
                </a>
                <a href="{{ route('shipper.shipping-fee-report') }}" class="sp-nav-link {{ request()->routeIs('shipper.shipping-fee-report') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow"></i> Báo cáo chi phí ship
                </a>
                <a href="{{ route('shipper.team-report') }}" class="sp-nav-link {{ request()->routeIs('shipper.team-report') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart-line"></i> Báo cáo đội hình ship
                </a>
                @endif

                
        </nav>
        <div class="p-3 border-top border-success border-opacity-25">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-person-circle text-success-emphasis"></i>
                <div class="small">
                    <div class="text-white fw-semibold" style="font-size:.8rem;">{{ auth()->user()->name }}</div>
                    <div style="color:#fef3c7;font-size:.7rem;">Shipper</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm w-100" style="font-size:.8rem;">
                    <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                </button>
            </form>
        </div>
    </aside>
    <div class="mobile-drawer-overlay d-md-none js-sp-overlay"></div>

    <!-- Main content -->
    <div class="sp-main">
        <header class="sp-topbar">
            @php
                $currentUser = auth()->user();
                $layoutSwitchTargets = collect($currentUser?->roles ?? [])
                    ->map(function ($role) {
                        $roleName = strtolower((string) $role->name);

                        return match ($roleName) {
                            'account', 'accountant', 'accounting' => [
                                'label' => 'Kế toán',
                                'href' => url('/accounting'),
                            ],
                            'warehouse' => [
                                'label' => 'Kho',
                                'href' => url('/warehouse'),
                            ],
                            'ceo' => [
                                'label' => 'CEO',
                                'href' => url('/ceo'),
                            ],
                            'admin' => [
                                'label' => 'Admin',
                                'href' => url('/admin/dashboard'),
                            ],
                            'shipper' => null,
                            default => null,
                        };
                    })
                    ->filter()
                    ->unique('href')
                    ->values();
            @endphp
            <div class="d-flex align-items-center gap-2 sp-topbar-left">
                <button type="button" class="btn btn-light d-md-none js-sp-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="shipper-sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                <h6 class="mb-0 fw-semibold sp-topbar-title">@yield('title', 'Dashboard')</h6>
                @hasSection('subtitle')
                    <div class="text-muted sp-topbar-subtitle" style="font-size:.8rem;">@yield('subtitle')</div>
                @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 sp-topbar-right">
                <!-- Role Switcher Dropdown -->
                @if($currentUser->roles->count() > 1)
                    <div class="dropdown">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle sp-mobile-role" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-badge"></i> {{ ucfirst(session('active_role', 'Vai trò')) }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header">Chọn vai trò</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            @foreach($currentUser->roles as $role)
                                @php
                                    $roleName = strtolower((string) $role->name);
                                    $isActive = strtolower(session('active_role')) === strtolower($role->name);
                                    $roleLabel = match ($roleName) {
                                        'account', 'accountant', 'accounting' => 'Kế toán',
                                        'package' => 'Đóng hàng',
                                        'warehouse' => 'Kho',
                                        'manager_shipper' => 'Điều phối ship',
                                        default => ucfirst($role->name),
                                    };
                                @endphp
                                <li>
                                    <form action="{{ route('role.switch', $role->name) }}" method="POST" class="d-inline-block w-100">
                                        @csrf
                                        <button type="submit" 
                                            class="dropdown-item d-flex align-items-center gap-2 {{ $isActive ? 'bg-light' : '' }}"
                                            title="Chuyển sang vai trò {{ $role->name }}">
                                            <i class="bi {{ $isActive ? 'bi-check-circle-fill text-primary' : 'bi-circle' }}"></i>
                                            <span>{{ $roleLabel }}</span>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <span class="text-muted small sp-current-time">
                    <i class="bi bi-clock me-1"></i>
                    <span id="current-time">{{ now()->format('H:i') }}</span>
                    – {{ now()->format('d/m/Y') }}
                </span>
            </div>
        </header>

        <div class="sp-content">
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
        (function tick() {
            const el = document.getElementById('current-time');
            if (el) el.textContent = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit', minute:'2-digit'});
            setTimeout(tick, 60000);
        })();

        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sp-sidebar');
            const toggle = document.querySelector('.js-sp-toggle');
            const overlay = document.querySelector('.js-sp-overlay');
            const navLinks = document.querySelectorAll('.sp-sidebar .sp-nav-link');

            if (sidebar && toggle && overlay) {
                const closeDrawer = function () {
                    sidebar.classList.remove('mobile-open');
                    document.body.classList.remove('mobile-menu-open');
                    toggle.setAttribute('aria-expanded', 'false');
                };

                toggle.addEventListener('click', function () {
                    const willOpen = !sidebar.classList.contains('mobile-open');
                    sidebar.classList.toggle('mobile-open', willOpen);
                    document.body.classList.toggle('mobile-menu-open', willOpen);
                    toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });

                overlay.addEventListener('click', closeDrawer);

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeDrawer();
                    }
                });

                navLinks.forEach(function (link) {
                    link.addEventListener('click', function () {
                        if (window.matchMedia('(max-width: 768px)').matches) {
                            closeDrawer();
                        }
                    });
                });
            }
        });
    </script>
    @include('layouts.partials.session_expiry_redirect')
    @stack('scripts')
</body>
</html>
