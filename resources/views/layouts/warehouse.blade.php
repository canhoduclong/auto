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
            --sidebar-collapsed-width: 64px;
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
            transition: width .2s ease, transform .22s ease, box-shadow .2s ease;
        }
        .wh-brand {
            padding: 1rem 1.25rem; background: var(--sidebar-bg-strong); color: #fff; font-weight: 700;
            font-size: 1.02rem; display: flex; align-items: center; gap: .5rem; border-bottom: 1px solid rgba(255,255,255,.15);
        }
        .wh-brand .badge { font-size: .65rem; background: var(--theme-accent); color: #1f2937; }
        .wh-sidebar-control {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 30px;
            width: 30px;
            height: 30px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: rgba(255,255,255,.1);
            color: #fff;
        }
        .wh-sidebar-control:hover,
        .wh-sidebar-control:focus-visible { background: rgba(255,255,255,.2); color: #fff; }
        .wh-nav-section {
            padding: .75rem 1.25rem .25rem; font-size: .78rem; color: #fef3c7;
            text-transform: uppercase; letter-spacing: .08em; font-weight: 600;
        }
        .wh-nav-link {
            display: flex; align-items: center; gap: .6rem; padding: .55rem 1.25rem;
            color: #e6fffb; font-size: .95rem; text-decoration: none;
            border-left: 3px solid transparent; transition: all .15s;
        }
        .wh-nav-link > i { flex: 0 0 1.15rem; text-align: center; }
        .wh-nav-label { min-width: 0; }
        .wh-nav-link:hover { color: #fff; background: rgba(255,255,255,.08); border-left-color: var(--theme-accent); }
        .wh-nav-link.active { color: #fff; background: rgba(255,193,7,.16); border-left-color: var(--sidebar-active); }
        .wh-nav-link .badge { margin-left: auto; font-size: .65rem; }
        .wh-main { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); min-height: 100vh; display: flex; flex-direction: column; }
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
        .wh-notification-menu {
            width: min(560px, calc(100vw - var(--sidebar-width) - 2rem));
            max-width: min(560px, calc(100vw - var(--sidebar-width) - 2rem));
            max-height: min(70vh, 620px);
            overflow-y: auto;
            overflow-x: hidden;
            white-space: normal;
        }
        .wh-notification-menu .dropdown-item {
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .wh-notification-menu .wh-notification-content {
            min-width: 0;
        }
        @media (max-width: 768px) {
            .wh-sidebar {
                width: min(86vw, 320px);
                transform: translateX(-100%);
                transition: transform .22s ease;
            }
            .wh-sidebar.mobile-open { transform: translateX(0); }
            .wh-main { margin-left: 0; width: 100%; }
            .wh-topbar { padding: .65rem .85rem; }
            .wh-content {
                padding: .9rem;
                padding-bottom: calc(.9rem + env(safe-area-inset-bottom, 0px));
            }
            .wh-notification-menu {
                width: calc(100vw - 1rem);
                max-width: calc(100vw - 1rem);
            }
        }
        .wh-sidebar nav {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            padding-bottom: .35rem;
        }
        @media (min-width: 768px) {
            .wh-sidebar {
                display: flex !important;
                visibility: visible !important;
                transform: none !important;
            }
            .wh-main {
                margin-left: var(--sidebar-width) !important;
                width: calc(100% - var(--sidebar-width)) !important;
                transition: margin-left .2s ease, width .2s ease;
            }
            body.wh-sidebar-is-collapsed .wh-sidebar {
                width: var(--sidebar-collapsed-width);
            }
            body.wh-sidebar-is-collapsed .wh-main {
                margin-left: var(--sidebar-collapsed-width) !important;
                width: calc(100% - var(--sidebar-collapsed-width)) !important;
            }
            body.wh-sidebar-is-collapsed .wh-sidebar.wh-sidebar-unfold {
                width: var(--sidebar-width);
                box-shadow: .35rem 0 1.25rem rgba(3, 43, 40, .28);
                z-index: 210;
            }
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-brand {
                justify-content: center;
                padding-left: .75rem;
                padding-right: .75rem;
            }
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-brand-label,
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-brand .badge,
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-brand > .bi-box-seam-fill,
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-nav-label,
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-nav-link .badge,
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-nav-section,
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-account {
                display: none !important;
            }
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-nav-link {
                justify-content: center;
                gap: 0;
                padding-left: .75rem;
                padding-right: .75rem;
            }
            body.wh-sidebar-is-collapsed .wh-sidebar:not(.wh-sidebar-unfold) .wh-nav-link > i {
                font-size: 1.15rem;
            }
        }
    </style>
</head>
<body class="{{ !empty($isMobileClient) ? 'is-mobile-client' : '' }}">
    <script>
        try {
            if (window.localStorage.getItem('warehouse.sidebar.collapsed') === '1') {
                document.body.classList.add('wh-sidebar-is-collapsed');
            }
        } catch (error) {
            // Use the expanded default when browser storage is unavailable.
        }
    </script>
    @include('layouts.notifications')

    <!-- Sidebar -->
    <aside class="wh-sidebar" id="warehouse-sidebar">
        <div class="wh-brand">
            <i class="bi bi-box-seam-fill fs-5"></i>
            <span class="wh-brand-label">Warehouse</span>
            <span class="badge ms-auto">WH</span>
            <button type="button" class="wh-sidebar-control d-none d-md-inline-flex js-wh-sidebar-control" aria-controls="warehouse-sidebar" aria-expanded="true" title="Thu nhỏ menu">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                <span class="visually-hidden">Thu nhỏ menu</span>
            </button>
        </div>
        <nav class="mt-1 flex-grow-1 overflow-auto">
            <div class="wh-nav-section">Tổng quan</div>
            <a href="{{ route('warehouse.dashboard') }}" class="wh-nav-link {{ request()->routeIs('warehouse.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i><span class="wh-nav-label">Bảng điều khiển</span>
            </a>
            <a href="{{ route('warehouse.production-dashboard') }}" class="wh-nav-link {{ request()->routeIs('warehouse.production-dashboard') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line-fill"></i><span class="wh-nav-label">Bảng điều khiển sản xuất</span>
            </a>
            <a href="{{ route('department-notifications.index', ['layout' => 'warehouse']) }}" class="wh-nav-link {{ request()->routeIs('department-notifications.*') && request('layout') === 'warehouse' ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i><span class="wh-nav-label">Tạo thông báo</span>
            </a>
            <a href="{{ route('warehouse.orders') }}" class="wh-nav-link {{ request()->routeIs('warehouse.orders') ? 'active' : '' }}">
                <i class="bi bi-box2-fill"></i><span class="wh-nav-label">Đơn cần đóng gói</span>
            </a>
            <a href="{{ route('warehouse.order-adjustments.index') }}" class="wh-nav-link {{ request()->routeIs('warehouse.order-adjustments.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard2-check-fill"></i><span class="wh-nav-label">Duyệt điều chỉnh sản lượng</span>
                @if(($warehouseAdjustmentQueueCount ?? 0) > 0)
                    <span class="badge rounded-pill bg-danger ms-auto">{{ $warehouseAdjustmentQueueCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.imported-sales-orders.index') }}" class="wh-nav-link {{ request()->routeIs('admin.imported-sales-orders.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i><span class="wh-nav-label">Hoàn chỉnh đơn lịch sử</span>
            </a>
            <a href="{{ route('warehouse.transfers.incoming') }}" class="wh-nav-link {{ request()->routeIs('warehouse.transfers.incoming') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i><span class="wh-nav-label">Tiếp nhận Đơn</span>
            </a>
            <a href="{{ route('warehouse.inventory-transfers.incoming') }}" class="wh-nav-link {{ request()->routeIs('warehouse.inventory-transfers.incoming') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-in-down"></i><span class="wh-nav-label">Tiếp nhận hàng</span>
            </a>
            <a href="{{ route('warehouse.procurement-receipts.index') }}" class="wh-nav-link {{ request()->routeIs('warehouse.procurement-receipts.*') ? 'active' : '' }}">
                <i class="bi bi-basket2-fill"></i><span class="wh-nav-label">Nhập kho từ thu mua</span>
            </a>

            <div class="wh-nav-section">Quản lý kho</div>
            <a href="{{ route('warehouse.stock-in') }}" class="wh-nav-link {{ request()->routeIs('warehouse.stock-in') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i><span class="wh-nav-label">Nhập Kho</span>
            </a>
            <a href="{{ route('warehouse.supplier-prices.index') }}" class="wh-nav-link {{ request()->routeIs('warehouse.supplier-prices.*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i><span class="wh-nav-label">Bảng giá thu mua</span>
            </a>
            <a href="{{ route('warehouse.stock-out') }}" class="wh-nav-link {{ request()->routeIs('warehouse.stock-out') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-right"></i><span class="wh-nav-label">Xuất Kho</span>
            </a>
            <a href="{{ route('warehouse.stock-out.orders') }}" class="wh-nav-link {{ request()->routeIs('warehouse.stock-out.orders') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i><span class="wh-nav-label">Đơn Xuất Kho</span>
            </a>
            <a href="{{ route('warehouse.transfers.index') }}" class="wh-nav-link {{ request()->routeIs('warehouse.transfers.index', 'warehouse.dispatch-slips.*', 'warehouse.order-transfers', 'warehouse.order-transfers.*', 'warehouse.inventory-transfers.index', 'warehouse.inventory-transfers.edit') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i><span class="wh-nav-label">Điều chuyển</span>
            </a>
            <a href="{{ route('warehouse.inventory') }}" class="wh-nav-link {{ request()->routeIs('warehouse.inventory') ? 'active' : '' }}">
                <i class="bi bi-stack"></i><span class="wh-nav-label">Tồn Kho</span>
            </a>
            <a href="{{ route('warehouse.stocktakes.index') }}" class="wh-nav-link {{ request()->routeIs('warehouse.stocktakes.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard2-check"></i><span class="wh-nav-label">Kiểm Kê Tồn Đầu</span>
            </a>
            <a href="{{ route('warehouse.inventory-daily') }}" class="wh-nav-link {{ request()->routeIs('warehouse.inventory-daily') ? 'active' : '' }}">
                <i class="bi bi-calendar3"></i><span class="wh-nav-label">Tồn kho Daily</span>
            </a>
            <a href="{{ route('warehouse.products') }}" class="wh-nav-link {{ request()->routeIs('warehouse.products') ? 'active' : '' }}">
                <i class="bi bi-box"></i><span class="wh-nav-label">Sản Phẩm</span>
            </a>

            <div class="wh-nav-section">Tài chính</div>
            <a href="{{ route('warehouse.finance-requests.index') }}" class="wh-nav-link {{ request()->routeIs('warehouse.finance-requests.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i><span class="wh-nav-label">Phiếu yêu cầu</span>
            </a>

            <div class="wh-nav-section">Nhiệm vụ & Trả hàng</div>
            <a href="{{ route('tasks.my-tasks') }}" class="wh-nav-link {{ request()->routeIs('tasks.my-tasks') || request()->routeIs('task-assignments.assigned-to-me') ? 'active' : '' }}">
                <i class="bi bi-list-task"></i><span class="wh-nav-label">Nhiệm vụ</span>
            </a>
            <a href="{{ route('task-assignments.in-progress') }}" class="wh-nav-link {{ request()->routeIs('task-assignments.in-progress') || request()->routeIs('task-assignments.complete-form') ? 'active' : '' }}">
                <i class="bi bi-check2-circle"></i><span class="wh-nav-label">Thực hiện</span>
            </a>
            <a href="{{ route('warehouse.returns') }}" class="wh-nav-link {{ request()->routeIs('warehouse.returns') ? 'active' : '' }}">
                <i class="bi bi-arrow-return-left"></i><span class="wh-nav-label">Đơn trả về</span>
            </a>

            <div class="wh-nav-section">Báo cáo</div>
            <a href="{{ route('warehouse.reports') }}" class="wh-nav-link {{ request()->routeIs('warehouse.reports') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i><span class="wh-nav-label">Thống Kê</span>
            </a>
        </nav>
        <!-- Account info moved to header -->
        <div class="wh-account p-3 border-top border-secondary d-none d-md-block" style="opacity:0.3;pointer-events:none;">
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
                            'account', 'accountant', 'accounting' => [
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
                <!-- Chuông thông báo động (giả lập) -->
                <div class="dropdown me-2">
                    @php
                        $user = auth()->user();
                        $recentWarehouseNotifications = $user ? getWarehouseNotifications($user, 7) : collect();
                    @endphp
                    <a href="#" class="position-relative" id="dropdownNotification" data-bs-toggle="dropdown" aria-expanded="false" title="Thông báo">
                        <i class="bi bi-bell fs-4"></i>
                        @if($recentWarehouseNotifications->count() || ($warehouseAdjustmentQueueCount ?? 0) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.7rem;" id="notification-badge">{{ $recentWarehouseNotifications->count() + ($warehouseAdjustmentQueueCount ?? 0) }}</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm wh-notification-menu" aria-labelledby="dropdownNotification">
                        <li class="dropdown-header">Thông báo gần đây</li>
                        @if(($warehouseAdjustmentQueueCount ?? 0) > 0)
                            <li>
                                <a class="dropdown-item d-flex align-items-start gap-2 bg-warning-subtle" href="{{ route('warehouse.order-adjustments.index') }}">
                                    <i class="bi bi-clipboard2-check-fill text-warning flex-shrink-0 mt-1"></i>
                                    <div class="wh-notification-content">
                                        <div class="fw-semibold small">{{ $warehouseAdjustmentQueueCount }} yêu cầu điều chỉnh sản lượng cần xác nhận</div>
                                        <div class="text-muted small">Đã được Kế toán duyệt và chuyển tới Kho</div>
                                    </div>
                                </a>
                            </li>
                        @endif
                        @if($recentWarehouseNotifications->count())
                            @foreach($recentWarehouseNotifications as $noti)
                                <li>
                                    <a class="dropdown-item d-flex align-items-start gap-2" href="{{ $noti['link'] ?? '#' }}">
                                        @php
                                            $icon = match($noti['type']) {
                                                'new_order' => 'bi-receipt-cutoff text-danger',
                                                'warehouse' => 'bi-box-seam text-primary',
                                                'sale' => 'bi-person-badge text-warning',
                                                'shipper' => 'bi-truck text-success',
                                                default => 'bi-info-circle text-secondary',
                                            };
                                        @endphp
                                        <i class="bi {{ $icon }} flex-shrink-0 mt-1"></i>
                                        <div class="wh-notification-content">
                                            <div class="fw-semibold small">{!! $noti['title'] !!}</div>
                                            <div class="text-muted small">{{ $noti['meta'] ?? '' }} &middot; <span>{{ $noti['time'] ?? '' }}</span></div>
                                            @if(!empty($noti['details']))
                                                <div class="mt-1 small">
                                                    @foreach($noti['details'] as $detail)
                                                        <div>{{ $detail['name'] }}: {{ rtrim(rtrim(number_format($detail['quantity'], 2, ',', '.'), '0'), ',') }} × {{ number_format($detail['price']) }}đ = {{ number_format($detail['line_total']) }}đ</div>
                                                    @endforeach
                                                    <div class="fw-semibold mt-1">Tổng tiền: {{ number_format($noti['total'] ?? 0) }}đ</div>
                                                    <div>Ghi chú: {{ $noti['note'] ?: 'Không có' }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li><span class="dropdown-item text-muted">Không có thông báo nào</span></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center text-primary" href="{{ route('warehouse.notifications') }}">Xem tất cả thông báo</a></li>
                    </ul>
                </div>
                <!-- Dropdown tài khoản -->
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
                @include('layouts.partials.role_switcher')
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
            const sidebarControl = document.querySelector('.js-wh-sidebar-control');

            if (sidebar && sidebarControl) {
                const storageKey = 'warehouse.sidebar.collapsed';
                const controlIcon = sidebarControl.querySelector('i');
                const controlText = sidebarControl.querySelector('.visually-hidden');
                let unfoldTimer;

                const updateSidebarControl = function () {
                    const isCollapsed = document.body.classList.contains('wh-sidebar-is-collapsed');
                    const isUnfolded = sidebar.classList.contains('wh-sidebar-unfold');
                    let label = 'Thu nhỏ menu';
                    let iconClass = 'bi-chevron-left';

                    if (isCollapsed && isUnfolded) {
                        label = 'Neo menu đang mở';
                        iconClass = 'bi-pin-angle-fill';
                    } else if (isCollapsed) {
                        label = 'Mở và neo menu';
                        iconClass = 'bi-chevron-right';
                    }

                    sidebarControl.setAttribute('title', label);
                    sidebarControl.setAttribute('aria-label', label);
                    sidebarControl.setAttribute('aria-expanded', String(!isCollapsed || isUnfolded));
                    controlText.textContent = label;
                    controlIcon.className = 'bi ' + iconClass;
                };

                const setCollapsed = function (isCollapsed, remember) {
                    document.body.classList.toggle('wh-sidebar-is-collapsed', isCollapsed);
                    sidebar.classList.remove('wh-sidebar-unfold');
                    if (remember) {
                        try {
                            window.localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
                        } catch (error) {
                            // Sidebar remains usable when storage is unavailable.
                        }
                    }
                    updateSidebarControl();
                };

                let initiallyCollapsed = false;
                try {
                    initiallyCollapsed = window.localStorage.getItem(storageKey) === '1';
                } catch (error) {
                    initiallyCollapsed = false;
                }
                setCollapsed(initiallyCollapsed, false);

                sidebar.querySelectorAll('.wh-nav-link').forEach(function (link) {
                    const label = link.querySelector('.wh-nav-label');
                    if (label) link.setAttribute('title', label.textContent.trim());
                });

                sidebarControl.addEventListener('click', function () {
                    setCollapsed(!document.body.classList.contains('wh-sidebar-is-collapsed'), true);
                });

                sidebar.addEventListener('mouseenter', function () {
                    window.clearTimeout(unfoldTimer);
                    if (!document.body.classList.contains('wh-sidebar-is-collapsed')) return;
                    unfoldTimer = window.setTimeout(function () {
                        sidebar.classList.add('wh-sidebar-unfold');
                        updateSidebarControl();
                    }, 150);
                });

                sidebar.addEventListener('mouseleave', function () {
                    window.clearTimeout(unfoldTimer);
                    if (!document.body.classList.contains('wh-sidebar-is-collapsed')) return;
                    unfoldTimer = window.setTimeout(function () {
                        sidebar.classList.remove('wh-sidebar-unfold');
                        updateSidebarControl();
                    }, 150);
                });
            }

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
    @include('layouts.partials.session_expiry_redirect')
    @stack('scripts')
</body>
</html>
