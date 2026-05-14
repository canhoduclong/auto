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
            .sp-sidebar { transform: translateX(-100%); transition: transform .22s ease; }
            .sp-sidebar.mobile-open { transform: translateX(0); }
            .sp-main { margin-left: 0; }
            .sp-topbar { padding: .65rem .85rem; }
            .sp-content { padding: .9rem; }
        }
    </style>
</head>
<body class="{{ !empty($isMobileClient) ? 'is-mobile-client' : '' }}">
    <!-- Sidebar -->
    <aside class="sp-sidebar">
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
                <i class="bi bi-calendar-event"></i> Lịch trình giao hàng
            </a>

            <div class="sp-nav-section">Đang giao</div>
            <a href="{{ route('shipper.my-orders') }}" class="sp-nav-link {{ request()->routeIs('shipper.my-orders') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Đơn của tôi
            </a>

            <div class="sp-nav-section">Lịch sử</div>
            <a href="{{ route('shipper.history') }}" class="sp-nav-link {{ request()->routeIs('shipper.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Lịch sử giao
            </a>

            @if(auth()->user()->hasRole('manager_shipper'))
                <div class="sp-nav-section">Quản lý ship</div>
                <a href="{{ route('shipper.manage-assignments') }}" class="sp-nav-link {{ request()->routeIs('shipper.manage-assignments') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> Gán đơn cho ship
                </a>
                <a href="{{ route('shipper.manage-fees') }}" class="sp-nav-link {{ request()->routeIs('shipper.manage-fees') ? 'active' : '' }}">
                    <i class="bi bi-cash-coin"></i> Quản lý phí ship
                </a>
                <a href="{{ route('shipper.route-planning') }}" class="sp-nav-link {{ request()->routeIs('shipper.route-planning') ? 'active' : '' }}">
                    <i class="bi bi-map"></i> Sắp xếp tuyến đường
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
                            'accountant', 'accounting' => [
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
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light d-md-none js-sp-toggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                <h6 class="mb-0 fw-semibold">@yield('title', 'Dashboard')</h6>
                @hasSection('subtitle')
                    <div class="text-muted" style="font-size:.8rem;">@yield('subtitle')</div>
                @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Role Switcher Dropdown -->
                @if($currentUser->roles->count() > 1)
                    <div class="dropdown">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-badge"></i> {{ ucfirst(session('active_role', 'Vai trò')) }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header">Chọn vai trò</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            @foreach($currentUser->roles as $role)
                                @php
                                    $roleName = strtolower((string) $role->name);
                                    if (in_array($roleName, ['accountant', 'accounting'], true)) {
                                        continue;
                                    }
                                    $isActive = strtolower(session('active_role')) === strtolower($role->name);
                                @endphp
                                <li>
                                    <form action="{{ route('role.switch', $role->name) }}" method="POST" class="d-inline-block w-100">
                                        @csrf
                                        <button type="submit" 
                                            class="dropdown-item d-flex align-items-center gap-2 {{ $isActive ? 'bg-light' : '' }}"
                                            title="Chuyển sang vai trò {{ $role->name }}">
                                            <i class="bi {{ $isActive ? 'bi-check-circle-fill text-primary' : 'bi-circle' }}"></i>
                                            <span>{{ ucfirst($role->name) }}</span>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <span class="text-muted small">
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

            if (sidebar && toggle && overlay) {
                const closeDrawer = function () {
                    sidebar.classList.remove('mobile-open');
                    document.body.classList.remove('mobile-menu-open');
                };

                toggle.addEventListener('click', function () {
                    sidebar.classList.add('mobile-open');
                    document.body.classList.add('mobile-menu-open');
                });

                overlay.addEventListener('click', closeDrawer);
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
