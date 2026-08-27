<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CEO - @yield('title', 'Executive')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}" type="text/css">
    @stack('styles')
    {{-- Accounting component styles (used when accounting views render via CEO layout) --}}
    <style>
        :root {
            --acc-bg: #f4f6fb;
            --acc-panel: #ffffff;
            --acc-line: #e2e8f0;
            --acc-muted: #64748b;
        }
        .acc-card {
            border: 1px solid var(--acc-line);
            border-radius: 14px;
            background: var(--acc-panel);
            box-shadow: 0 8px 20px rgba(15,23,42,0.04);
        }
        .acc-card .card-body { padding: 16px; }
        .acc-filter {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
        .acc-kpi {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .acc-kpi .item {
            border: 1px solid var(--acc-line);
            border-radius: 12px;
            background: #fff;
            padding: 12px;
        }
        .acc-kpi .item .label { color: var(--acc-muted); font-size: 12px; text-transform: uppercase; }
        .acc-kpi .item .value { font-size: 22px; font-weight: 800; margin-top: 5px; }
        .table thead th {
            text-transform: uppercase;
            font-size: 12px;
            color: var(--acc-muted);
            letter-spacing: 0.03em;
        }
        @media (max-width: 1200px) {
            .acc-filter { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .acc-kpi { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            .acc-filter { grid-template-columns: 1fr; }
            .acc-kpi { grid-template-columns: 1fr; }
        }
    </style>
    <style>
        :root {
            --ceo-sidebar-width: 260px;
            --ceo-sidebar-bg: #0b1220;
            --ceo-sidebar-strong: #050a14;
            --ceo-primary: #0ea5e9;
            --ceo-accent: #14b8a6;
            --ceo-bg: #f4f7fb;
        }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--ceo-bg);
            font-family: 'Inter', system-ui, sans-serif;
            color: #0f172a;
        }
        .ceo-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--ceo-sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--ceo-sidebar-bg) 0%, var(--ceo-sidebar-strong) 100%);
            color: #e2e8f0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #5f7a96 transparent;
        }
        .ceo-sidebar::-webkit-scrollbar {
            width: 10px;
        }
        .ceo-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .ceo-sidebar::-webkit-scrollbar-thumb {
            background: #5f7a96;
            border-radius: 5px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        .ceo-sidebar::-webkit-scrollbar-thumb:hover {
            background: #7b94b0;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        .ceo-brand {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            display: flex;
            align-items: center;
            gap: .65rem;
            font-weight: 800;
            color: #fff;
        }
        .ceo-brand .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ceo-primary), var(--ceo-accent));
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.2);
        }
        .ceo-nav {
            padding: .8rem;
            flex: 1;
        }
        .ceo-nav-footer {
            padding: .8rem;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
        }
        .ceo-nav-form {
            margin: 0;
        }
        .ceo-nav-logout {
            width: 100%;
            border: 1px solid rgba(248, 113, 113, 0.45);
            background: rgba(127, 29, 29, 0.18);
            color: #fee2e2;
            cursor: pointer;
            text-align: left;
        }
        .ceo-nav-logout:hover {
            background: rgba(220, 38, 38, 0.25);
            color: #fff;
        }
        .ceo-nav-link {
            display: flex;
            align-items: center;
            gap: .65rem;
            color: #dbeafe;
            text-decoration: none;
            border-radius: 12px;
            padding: .35rem .7rem;
            margin-bottom: .35rem;
            font-size: .92rem;
            transition: .15s ease;
        }
        .ceo-nav-link:hover {
            background: rgba(14, 165, 233, 0.18);
            color: #fff;
        }
        .ceo-nav-link.active {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.24), rgba(20, 184, 166, 0.26));
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(125, 211, 252, 0.38);
        }
        .ceo-main {
            margin-left: var(--ceo-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .ceo-topbar {
            position: sticky;
            top: 0;
            z-index: 90;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #e2e8f0;
            padding: .8rem 1.4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ceo-content {
            padding: 1.2rem 1.4rem;
            flex: 1;
        }
        .ceo-user {
            font-size: .9rem;
            color: #475569;
        }
        /* Presence user list styles */
        .user-presence-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            display: inline-block;
            flex-shrink: 0;
        }
        .user-presence-dot.online {
            background: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.22);
        }
        .user-presence-dot.offline {
            background: #94a3b8;
            box-shadow: 0 0 0 2px rgba(148, 163, 184, 0.18);
        }
        .presence-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .presence-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .presence-avatar-wrap .presence-status-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid #fff;
        }
        .presence-status-dot.online  { background: #22c55e; }
        .presence-status-dot.offline { background: #94a3b8; }
        .presence-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-bottom: 1px solid rgba(0,0,0,.07);
            cursor: default;
            transition: background .12s;
        }
        .presence-row:hover { background: rgba(0,0,0,.03); }
        .presence-row:last-child { border-bottom: none; }
        @media (max-width: 992px) {
            .ceo-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: min(86vw, 320px);
                z-index: 220;
                transform: translateX(-100%);
                transition: transform 0.22s ease;
            }
            .ceo-sidebar.mobile-open {
                transform: translateX(0);
            }
            .ceo-main {
                margin-left: 0;
            }
            .ceo-topbar {
                padding: .65rem .85rem;
            }
            .ceo-content {
                padding: .9rem;
            }
        }
    </style>
</head>
<body class="{{ !empty($isMobileClient) ? 'is-mobile-client' : '' }}">
    <aside class="ceo-sidebar">
        <div class="ceo-brand">
            <span class="dot"></span>
            <span>CEO Executive</span>
        </div>
        <nav class="ceo-nav">
            <div style="padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.06em;">Tổng Quan</div>
            <a href="{{ route('ceo.dashboard') }}" class="ceo-nav-link {{ request()->routeIs('ceo.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('ceo.profile') }}" class="ceo-nav-link {{ request()->routeIs('ceo.profile') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i> Hồ sơ CEO
            </a>

            <div style="padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.06em; margin-top: 8px;">Đơn Hàng</div>
            <a href="{{ route('ceo.orders') }}" class="ceo-nav-link {{ request()->routeIs('ceo.orders') ? 'active' : '' }}">
                <i class="bi bi-bag-check"></i> Đơn hàng
            </a>
            <a href="{{ route('ceo.daily-sales') }}" class="ceo-nav-link {{ request()->routeIs('ceo.daily-sales') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i> Thống kê bán hàng
            </a>

            <div style="padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.06em; margin-top: 8px;">Khách Hàng</div>
            <a href="{{ route('ceo.customers') }}" class="ceo-nav-link {{ request()->routeIs('ceo.customers') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Khách hàng lớn
            </a>
            <a href="{{ route('ceo.customers-list') }}" class="ceo-nav-link {{ request()->routeIs('ceo.customers-list') ? 'active' : '' }}">
                <i class="bi bi-card-list"></i> Danh sách khách hàng
            </a>
            <a href="{{ route('ceo.users-list') }}" class="ceo-nav-link {{ request()->routeIs('ceo.users-list') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Danh sách user
            </a>

            <div style="padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.06em; margin-top: 8px;">Tài Chính</div>
            <a href="{{ route('ceo.revenue') }}" class="ceo-nav-link {{ request()->routeIs('ceo.revenue') ? 'active' : '' }}">
                <i class="bi bi-currency-dollar"></i> Doanh thu
            </a>
            <a href="{{ route('ceo.cashflow') }}" class="ceo-nav-link {{ request()->routeIs('ceo.cashflow', 'ceo.cashflow.show', 'ceo.transactions.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Thu chi
            </a>
            <a href="{{ route('ceo.finance-requests.index') }}" class="ceo-nav-link {{ request()->routeIs('ceo.finance-requests.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> Phiếu yêu cầu
            </a>
            <a href="{{ route('ceo.financial-reports') }}" class="ceo-nav-link {{ request()->routeIs('ceo.financial-reports') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> Báo cáo tài chính
            </a>

            <div style="padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.06em; margin-top: 8px;">Hoạt Động</div>
            <a href="{{ route('ceo.sales') }}" class="ceo-nav-link {{ request()->routeIs('ceo.sales') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> Hiệu suất Sale
            </a>
            <a href="{{ route('ceo.debts') }}" class="ceo-nav-link {{ request()->routeIs('ceo.debts') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i> Công nợ
            </a>
            <a href="{{ route('ceo.shipper') }}" class="ceo-nav-link {{ request()->routeIs('ceo.shipper') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Shipper
            </a>
            <a href="{{ route('ceo.shipper-costs') }}" class="ceo-nav-link {{ request()->routeIs('ceo.shipper-costs') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Chi phí Shipper
            </a>

            <div style="padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.06em; margin-top: 8px;">Kho</div>
            <a href="{{ route('ceo.warehouse') }}" class="ceo-nav-link {{ request()->routeIs('ceo.warehouse') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Tổng quan kho
            </a>
            <a href="{{ route('ceo.warehouse-dispatch-slips.index') }}" class="ceo-nav-link {{ request()->routeIs('ceo.warehouse-dispatch-slips.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-spreadsheet"></i> Phiếu xuất kho tổng
            </a>

            <div style="padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.06em; margin-top: 8px;">Báo Cáo</div>
            <a href="{{ route('ceo.reports') }}" class="ceo-nav-link {{ request()->routeIs('ceo.reports') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Báo cáo điều hành
            </a>
            <a href="{{ route('ceo.loss-report') }}" class="ceo-nav-link {{ request()->routeIs('ceo.loss-report') ? 'active' : '' }}">
                <i class="bi bi-droplet-half"></i> Báo cáo hao hụt
            </a>
            <a href="{{ route('ceo.weekly-report') }}" class="ceo-nav-link {{ request()->routeIs('ceo.weekly-report') ? 'active' : '' }}">
                <i class="bi bi-calendar-week"></i> Báo cáo tuần
            </a>
            <a href="{{ route('ceo.weekly-customer-report') }}" class="ceo-nav-link {{ request()->routeIs('ceo.weekly-customer-report') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Báo cáo KH tuần
            </a>
            <a href="{{ route('ceo.alerts') }}" class="ceo-nav-link {{ request()->routeIs('ceo.alerts') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle"></i> Cảnh báo
            </a>

            <div style="padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.06em; margin-top: 8px;">Quản Lý</div>
            <a href="{{ route('ceo.price-management.index') }}" class="ceo-nav-link {{ request()->routeIs('ceo.price-management.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> Quản lý giá
            </a>
            <a href="{{ route('ceo.task-management.index') }}" class="ceo-nav-link {{ request()->routeIs('ceo.task-management.*') ? 'active' : '' }}">
                <i class="bi bi-checklist-rtl"></i> Giao việc
            </a>
            <a href="{{ route('department-notifications.index', ['layout' => 'ceo']) }}" class="ceo-nav-link {{ request()->routeIs('department-notifications.*') && request('layout') === 'ceo' ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Tạo thông báo
            </a>
        </nav>
        <div class="ceo-nav-footer">
            <form method="POST" action="{{ route('logout') }}" class="ceo-nav-form">
                @csrf
                <button type="submit" class="ceo-nav-link ceo-nav-logout">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </button>
            </form>
        </div>
    </aside>
    <div class="mobile-drawer-overlay d-lg-none js-ceo-overlay"></div>

    <main class="ceo-main">
        <header class="ceo-topbar">
            @php
                $currentUser = auth()->user();
                $hasUserLastSeenColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'last_seen_at');
                $onlineWindowMinutes = 5;
                $presenceUsers = ($hasUserLastSeenColumn)
                    ? (\App\Models\User::query()
                        ->select(['id', 'name', 'email', 'avatar', 'google_avatar', 'last_seen_at'])
                        ->orderByDesc('last_seen_at')
                        ->take(20)
                        ->get())
                    : collect();
                $presenceUsers = $presenceUsers->map(function ($user) use ($onlineWindowMinutes) {
                    $isOnline = !empty($user->last_seen_at) && $user->last_seen_at->gte(now()->subMinutes($onlineWindowMinutes));
                    $user->is_online = $isOnline;
                    return $user;
                });
                $onlineUsersCount  = $presenceUsers->where('is_online', true)->count();
                $offlineUsersCount = $presenceUsers->where('is_online', false)->count();
                $presenceUsers = $presenceUsers->where('is_online', true)
                    ->concat($presenceUsers->where('is_online', false)->take(10))
                    ->values();
            @endphp
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light d-lg-none js-ceo-toggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <strong>@yield('title', 'Executive')</strong>
                    <div class="text-muted small">@yield('subtitle', 'Bảng điều hành CEO')</div>
                </div>
            </div>
            @php
                $layoutSwitchTargets = collect($currentUser?->roles ?? [])
                    ->map(function ($role) {
                        $roleName = strtolower((string) $role->name);

                        return match ($roleName) {
                            'account', 'accountant', 'accounting' => [
                                'key' => 'accounting',
                                'label' => 'Kế toán',
                                'href' => url('/accounting'),
                            ],
                            'warehouse' => [
                                'key' => 'warehouse',
                                'label' => 'Kho',
                                'href' => url('/warehouse'),
                            ],  
                            'shipper' => [
                                'key' => 'shipper',
                                'label' => 'Shipper',
                                'href' => url('/shipper'),
                            ],
                            'admin' => [
                                'key' => 'admin',
                                'label' => 'Admin',
                                'href' => url('/dashboard'),
                            ],
                            'ceo' => null,
                            default => null,
                        };
                    })
                    ->filter()
                    ->unique('href')
                    ->values();
            @endphp
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                @include('layouts.partials.role_switcher')
                <div class="ceo-user text-end">
                    <span>{{ $currentUser->name ?? 'CEO' }} | {{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            </div>
        </header>
        <section class="ceo-content">
            @if(session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger py-2">{{ session('error') }}</div>
            @endif

            @yield('content')
            @yield('accounting_content')
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.ceo-sidebar');
            const toggle = document.querySelector('.js-ceo-toggle');
            const overlay = document.querySelector('.js-ceo-overlay');

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
    @include('layouts.partials.session_expiry_redirect')
    @stack('scripts')
</body>
</html>
