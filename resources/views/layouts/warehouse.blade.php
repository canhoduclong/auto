<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kho hàng – @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}" type="text/css">
    @stack('styles')
    <style>
        :root {
            --sidebar-width: 240px;
            --theme-primary: #0f766e;
            --theme-primary-hover: #115e59;
            --theme-accent: #ffc107;
            --theme-accent-hover: #e0a800;
            --sidebar-bg: #0b5f59;
            --sidebar-bg-strong: #084c47;
            --sidebar-active: #ffc107;
        }
        body {
            min-height: 100vh;
            background: #f7fbfb;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 1.04rem;
        }
        .wh-sidebar {
            width: var(--sidebar-width); min-height: 100vh; background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 200; display: flex; flex-direction: column;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
        }
        .wh-brand {
            padding: 1rem 1.25rem; background: var(--sidebar-bg-strong); color: #fff; font-weight: 700;
            font-size: 1.02rem; display: flex; align-items: center; gap: .5rem; border-bottom: 1px solid rgba(255,255,255,.15);
        }
        .wh-brand .badge { font-size: .65rem; background: var(--theme-accent); color: #1f2937; }
        .wh-nav-section {
            padding: .75rem 1.25rem .25rem; font-size: .78rem; color: #fef3c7;
            text-transform: uppercase; letter-spacing: .08em; font-weight: 600;
        }
        .wh-nav-link {
            display: flex; align-items: center; gap: .6rem; padding: .55rem 1.25rem;
            color: #e6fffb; font-size: .95rem; text-decoration: none;
            border-left: 3px solid transparent; transition: all .15s;
        }
        .wh-nav-link:hover { color: #fff; background: rgba(255,255,255,.08); border-left-color: var(--theme-accent); }
        .wh-nav-link.active { color: #fff; background: rgba(255,193,7,.16); border-left-color: var(--sidebar-active); }
        .wh-nav-link .badge { margin-left: auto; font-size: .65rem; }
        .wh-main { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }
        .wh-topbar {
            background: #fff; border-bottom: 1px solid #d8ece9; padding: .8rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100;
        }
        .wh-topbar .breadcrumb { margin-bottom: 0; font-size: .95rem; }
        .wh-topbar h6 { font-size: 1.08rem; }
        .wh-topbar .small { font-size: .93rem !important; }
        .wh-mobile-logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-height: 34px;
            padding: 0 .65rem;
            border-radius: 999px;
            border: 1px solid #f5c2c7;
            background: #fff;
            color: #b42318;
            font-size: .83rem;
            font-weight: 700;
        }
        .wh-mobile-logout-btn:hover {
            background: #fff5f5;
            color: #912018;
        }
        .wh-content { padding: 1.5rem; flex: 1; }
        .stat-card { border: none; border-radius: .75rem; transition: transform .15s; }
        .stat-card:hover { transform: translateY(-2px); }

        .btn-primary,
        .btn-info,
        .btn-success {
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active,
        .btn-info:hover,
        .btn-info:focus,
        .btn-info:active,
        .btn-success:hover,
        .btn-success:focus,
        .btn-success:active {
            background-color: var(--theme-primary-hover);
            border-color: var(--theme-primary-hover);
        }

        .btn-outline-primary,
        .btn-outline-info,
        .btn-outline-success {
            color: var(--theme-primary);
            border-color: var(--theme-primary);
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-info:hover,
        .btn-outline-info:focus,
        .btn-outline-success:hover,
        .btn-outline-success:focus {
            color: #fff;
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
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
            color: #1f2937;
            background-color: var(--theme-accent);
            border-color: var(--theme-accent);
        }

        .text-primary,
        .text-info,
        .text-success {
            color: var(--theme-primary) !important;
        }

        .bg-primary,
        .bg-info,
        .bg-success {
            background-color: var(--theme-primary) !important;
        }

        .badge.bg-primary,
        .badge.bg-info,
        .badge.bg-success,
        .badge.text-bg-primary,
        .badge.text-bg-info,
        .badge.text-bg-success {
            background-color: var(--theme-primary) !important;
            color: #fff !important;
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
            .wh-sidebar {
                width: min(86vw, 320px);
                transform: translateX(-100%);
                transition: transform .22s ease;
            }
            .wh-sidebar.mobile-open { transform: translateX(0); }
            .wh-main { margin-left: 0; }
            .wh-topbar { padding: .65rem .85rem; }
            .wh-content {
                padding: .9rem;
                padding-bottom: calc(.9rem + env(safe-area-inset-bottom, 0px));
            }
        }
        .wh-sidebar nav {
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
    <aside class="wh-sidebar">
        <div class="wh-brand">
            <i class="bi bi-box-seam-fill fs-5"></i>
            <span>Warehouse</span>
            <span class="badge ms-auto">WH</span>
        </div>
        <nav class="mt-1 flex-grow-1 overflow-auto">
            <div class="wh-nav-section">Tổng quan</div>
            <a href="{{ route('warehouse.dashboard') }}" class="wh-nav-link {{ request()->routeIs('warehouse.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="wh-nav-section">Quản lý tồn kho</div>
            <a href="{{ route('warehouse.stock-in') }}" class="wh-nav-link {{ request()->routeIs('warehouse.stock-in') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Nhập Kho
            </a>
            <a href="{{ route('warehouse.stock-out') }}" class="wh-nav-link {{ request()->routeIs('warehouse.stock-out') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-right"></i> Xuất Kho
            </a>
            <a href="{{ route('warehouse.stock-out.orders') }}" class="wh-nav-link {{ request()->routeIs('warehouse.stock-out.orders') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Đơn Xuất Kho
            </a>
            <a href="{{ route('warehouse.inventory-transfers.index') }}" class="wh-nav-link {{ request()->routeIs('warehouse.inventory-transfers.index') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Điều chuyển hàng
            </a>
            <a href="{{ route('warehouse.inventory') }}" class="wh-nav-link {{ request()->routeIs('warehouse.inventory') ? 'active' : '' }}">
                <i class="bi bi-stack"></i> Tồn Kho
            </a>
            <a href="{{ route('warehouse.products') }}" class="wh-nav-link {{ request()->routeIs('warehouse.products') ? 'active' : '' }}">
                <i class="bi bi-box"></i> Sản Phẩm
            </a>

            <div class="wh-nav-section">Đóng gói</div>
            <a href="{{ route('warehouse.orders') }}" class="wh-nav-link {{ request()->routeIs('warehouse.orders') ? 'active' : '' }}">
                <i class="bi bi-boxes"></i> Đơn cần xử lý
            </a>

            <div class="wh-nav-section">Nhiệm vụ kho</div>
            <a href="{{ route('tasks.my-tasks') }}" class="wh-nav-link {{ request()->routeIs('tasks.my-tasks') || request()->routeIs('task-assignments.assigned-to-me') ? 'active' : '' }}">
                <i class="bi bi-list-task"></i> Nhiệm vụ
            </a>
            <a href="{{ route('task-assignments.in-progress') }}" class="wh-nav-link {{ request()->routeIs('task-assignments.in-progress') || request()->routeIs('task-assignments.complete-form') ? 'active' : '' }}">
                <i class="bi bi-check2-circle"></i> Thực hiện
            </a>

            <div class="wh-nav-section">Trả hàng</div>
            <a href="{{ route('warehouse.returns') }}" class="wh-nav-link {{ request()->routeIs('warehouse.returns') ? 'active' : '' }}">
                <i class="bi bi-arrow-return-left"></i> Đơn trả về
            </a>
            <a href="{{ route('warehouse.transfers.incoming') }}" class="wh-nav-link {{ request()->routeIs('warehouse.transfers.incoming') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-in-down"></i> Tiếp nhận đơn
            </a>
            <a href="{{ route('warehouse.inventory-transfers.incoming') }}" class="wh-nav-link {{ request()->routeIs('warehouse.inventory-transfers.incoming') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-in-down"></i> Tiếp nhận hàng
            </a>

            <div class="wh-nav-section">Báo cáo</div>
            <a href="{{ route('warehouse.reports') }}" class="wh-nav-link {{ request()->routeIs('warehouse.reports') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i> Thống Kê
            </a>
        </nav>
        <div class="p-3 border-top border-secondary">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-person-circle text-secondary"></i>
                <div class="small">
                    <div class="text-white fw-semibold" style="font-size:.95rem;">{{ auth()->user()->name }}</div>
                    <div style="color:#fef3c7;font-size:.82rem;">{{ auth()->user()->warehouse?->name ?? 'Chưa được gán kho' }}</div>
                    <div class="email" style="color:#ffffff;font-size:.72rem;">{{ auth()->user()->email }}</div>
                </div> 
            </div>
            
             
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm w-100" style="font-size:.9rem;">
                    <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                </button>
            </form>
        </div>
    </aside>
    <div class="mobile-drawer-overlay d-md-none js-wh-overlay"></div>

    <!-- Main content -->
    <div class="wh-main">
        <header class="wh-topbar">
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
                            'shipper' => [
                                'label' => 'Shipper',
                                'href' => url('/shipper'),
                            ],
                            'ceo' => [
                                'label' => 'CEO',
                                'href' => url('/ceo'),
                            ],
                            'admin' => [
                                'label' => 'Admin',
                                'href' => url('/admin/dashboard'),
                            ],
                            'warehouse' => null,
                            default => null,
                        };
                    })
                    ->filter()
                    ->unique('href')
                    ->values();
            @endphp
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light d-md-none js-wh-toggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                <h6 class="mb-0 fw-semibold">@yield('title', 'Dashboard')</h6>
                @if(trim($__env->yieldContent('subtitle_clock')) === '1')
                    <div class="text-muted" style="font-size:.9rem;">
                        <i class="bi bi-clock me-1"></i>
                        <span data-current-time>{{ now()->format('H:i') }}</span>
                        – {{ now()->format('d/m/Y') }}
                    </div>
                @elseif(View::hasSection('subtitle'))
                    <div class="text-muted" style="font-size:.9rem;">@yield('subtitle')</div>
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
                <form method="POST" action="{{ route('logout') }}" class="d-md-none">
                    @csrf
                    <button type="submit" class="wh-mobile-logout-btn" aria-label="Đăng xuất">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Đăng xuất</span>
                    </button>
                </form>
                @if(trim($__env->yieldContent('subtitle_clock')) !== '1')
                    <span class="text-muted small">
                        <i class="bi bi-clock me-1"></i>
                        <span data-current-time>{{ now()->format('H:i') }}</span>
                        – {{ now()->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        </header>

        <div class="wh-content">
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
        // Live clock
        (function tick() {
            const timeText = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit', minute:'2-digit'});
            document.querySelectorAll('[data-current-time]').forEach(function (el) {
                el.textContent = timeText;
            });
            setTimeout(tick, 60000);
        })();

        function showToast(message, type = 'success') {
            const container = document.getElementById('notification-container');
            if (!container) {
                window.alert(message);
                return;
            }

            const toastEl = document.createElement('div');
            toastEl.classList.add('toast', 'border-0', 'shadow-lg', 'overflow-hidden');
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');

            let headerClass = 'bg-success text-white';
            if (type === 'error') {
                headerClass = 'bg-danger text-white';
            } else if (type === 'warning') {
                headerClass = 'bg-warning text-dark';
            } else if (type === 'info') {
                headerClass = 'bg-info text-dark';
            }

            const toastLabels = @json(__('common.toast_types'));
            const closeLabel = @json(__('common.actions.close'));
            const typeLabel = toastLabels[type] || type;

            toastEl.innerHTML = `
                <div class="toast-header ${headerClass}">
                    <strong class="me-auto">${typeLabel}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="${closeLabel}"></button>
                </div>
                <div class="toast-body bg-white">${message}</div>
            `;

            container.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('notification-container');
            if (!container) {
                // continue to enable sidebar toggle even when no toast container exists
            }

            if (container) {
                container.querySelectorAll('.toast').forEach(function (toastEl) {
                    const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
                    toast.show();
                    toastEl.addEventListener('hidden.bs.toast', function () {
                        toastEl.remove();
                    });
                });
            }

            const sidebar = document.querySelector('.wh-sidebar');
            const toggle = document.querySelector('.js-wh-toggle');
            const overlay = document.querySelector('.js-wh-overlay');

            if (sidebar && toggle && overlay) {
                const closeDrawer = function () {
                    sidebar.classList.remove('mobile-open');
                    document.body.classList.remove('mobile-menu-open');
                };

                const menuLinks = sidebar.querySelectorAll('a.wh-nav-link');
                menuLinks.forEach(function (link) {
                    link.addEventListener('click', closeDrawer);
                });

                toggle.addEventListener('click', function () {
                    sidebar.classList.add('mobile-open');
                    document.body.classList.add('mobile-menu-open');
                });

                overlay.addEventListener('click', closeDrawer);

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeDrawer();
                    }
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
