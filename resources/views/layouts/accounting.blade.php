<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Accounting - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}" type="text/css">
    <style>
        :root {
            --acc-bg: #f4f6fb;
            --acc-sidebar: #0f172a;
            --acc-panel: #ffffff;
            --acc-line: #e2e8f0;
            --acc-text: #0f172a;
            --acc-muted: #64748b;
            --acc-brand: #0ea5e9;
            --theme-primary: var(--acc-brand);
            --theme-primary-hover: #0284c7;
        }
        body {
            margin: 0;
            background: var(--acc-bg);
            color: var(--acc-text);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        .acc-shell {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
            transition: grid-template-columns .18s ease;
        }
        body.acc-sidebar-collapsed .acc-shell {
            grid-template-columns: 76px 1fr;
        }
        .acc-sidebar {
            background: linear-gradient(180deg, #0f172a, #0b1220);
            color: #dbeafe;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(148, 163, 184, 0.18);
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #5f7a96 transparent;
        }
        body.acc-sidebar-collapsed .acc-sidebar {
            overflow-y: auto;
            overflow-x: hidden;
        }
        .acc-sidebar::-webkit-scrollbar {
            width: 10px;
        }
        .acc-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .acc-sidebar::-webkit-scrollbar-thumb {
            background: #5f7a96;
            border-radius: 5px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        .acc-sidebar::-webkit-scrollbar-thumb:hover {
            background: #7b94b0;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        .acc-brand {
            padding: 18px;
            font-weight: 800;
            letter-spacing: 0.02em;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            min-height: 78px;
        }
        .acc-brand-text { min-width: 0; }
        .acc-brand small {
            display: block;
            font-weight: 500;
            color: #93c5fd;
            margin-top: 4px;
        }
        .acc-sidebar-collapse {
            width: 32px;
            height: 32px;
            border: 1px solid rgba(125, 211, 252, 0.24);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.72);
            color: #dbeafe;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            transition: background .15s, color .15s, transform .15s;
        }
        .acc-sidebar-collapse:hover {
            background: rgba(14, 165, 233, 0.2);
            color: #fff;
        }
        body.acc-sidebar-collapsed .acc-brand {
            padding: 14px 10px;
            align-items: center;
            justify-content: center;
        }
        body.acc-sidebar-collapsed .acc-brand-text {
            display: none;
        }
        body.acc-sidebar-collapsed .acc-sidebar-collapse i {
            transform: rotate(180deg);
        }
        .acc-nav {
            padding: 12px;
            flex: 1;
        }
        .acc-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #cbd5e1;
            text-decoration: none;
            padding: 9px 12px;
            border-radius: 10px;
            margin-bottom: 2px;
            font-size: 13.5px;
            font-weight: 500;
            transition: background .15s, color .15s;
            white-space: nowrap;
            overflow: hidden;
        }
        .acc-nav a i { font-size: 17px; flex-shrink: 0; opacity: .85; }
        .acc-nav a:hover { background: rgba(14, 165, 233, 0.15); color: #fff; }
        .acc-nav a.active {
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.28), rgba(59, 130, 246, 0.18));
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(125, 211, 252, 0.35);
        }
        .acc-nav a.active i { opacity: 1; }
        .acc-nav .nav-section {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #475569;
            padding: 12px 12px 4px;
        }
        body.acc-sidebar-collapsed .acc-nav {
            padding: 10px 8px;
            overflow: visible;
        }
        body.acc-sidebar-collapsed .acc-nav .nav-section {
            height: 1px;
            padding: 7px 0 0;
            margin: 7px 8px 6px;
            overflow: hidden;
            color: transparent;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
        }
        body.acc-sidebar-collapsed .acc-nav a {
            position: relative;
            justify-content: center;
            gap: 0;
            width: 46px;
            height: 42px;
            padding: 0 !important;
            margin: 0 auto 6px;
            border-radius: 12px;
            font-size: 0;
            overflow: visible;
        }
        body.acc-sidebar-collapsed .acc-nav a i {
            font-size: 19px;
            margin: 0;
        }
        body.acc-sidebar-collapsed .acc-nav a:hover::after {
            content: attr(data-label);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            z-index: 500;
            min-width: max-content;
            max-width: 260px;
            padding: 7px 10px;
            border-radius: 9px;
            background: #0f172a;
            color: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .22);
            font-size: 12.5px;
            font-weight: 700;
            line-height: 1.2;
            pointer-events: none;
        }
        body.acc-sidebar-collapsed .acc-nav a:hover::before {
            content: "";
            position: absolute;
            left: calc(100% + 6px);
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: #0f172a;
            z-index: 501;
            pointer-events: none;
        }
        .acc-menu-tooltip {
            position: fixed;
            z-index: 1000;
            min-width: max-content;
            max-width: 260px;
            padding: 7px 10px;
            border-radius: 9px;
            background: #0f172a;
            color: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .22);
            font-size: 12.5px;
            font-weight: 700;
            line-height: 1.2;
            pointer-events: none;
            opacity: 0;
            transform: translate(8px, -50%);
            transition: opacity .08s ease;
        }
        .acc-menu-tooltip.show {
            opacity: 1;
        }
        .acc-sidebar-footer {
            padding: 12px;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
        }
        body.acc-sidebar-collapsed .acc-sidebar-footer {
            padding: 10px 8px;
        }
        body.acc-sidebar-collapsed .acc-sidebar-footer button {
            width: 46px !important;
            height: 42px;
            padding: 0;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0;
        }
        body.acc-sidebar-collapsed .acc-sidebar-footer button i {
            font-size: 18px;
            margin: 0;
        }
        .acc-main {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .acc-topbar {
            background: rgba(255,255,255,0.95);
            border-bottom: 1px solid var(--acc-line);
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .acc-content { padding: 16px 18px; }
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
            grid-template-columns: repeat(5, minmax(0, 1fr));
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
            .acc-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .acc-kpi { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 992px) {
            .acc-shell,
            body.acc-sidebar-collapsed .acc-shell { grid-template-columns: 1fr; }
            .acc-sidebar {
                display: flex;
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: min(86vw, 320px);
                z-index: 220;
                transform: translateX(-100%);
                transition: transform 0.22s ease;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .acc-sidebar.mobile-open {
                transform: translateX(0);
            }
            .acc-kpi { grid-template-columns: 1fr; }
            .acc-topbar { padding: .65rem .85rem; }
            .acc-content { padding: .9rem; }
            .acc-sidebar-collapse { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body class="{{ !empty($isMobileClient) ? 'is-mobile-client' : '' }}">
<div class="acc-shell">
    <aside class="acc-sidebar">
        <div class="acc-brand">
            <div class="acc-brand-text">
                <span style="font-size:15px">&#x1F4CA; Kế Toán</span>
                <small>Kế toán &mdash; đối soát &mdash; báo cáo</small>
            </div>
            <button type="button" class="acc-sidebar-collapse js-acc-collapse" aria-label="Thu gọn menu" title="Thu gọn menu">
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>
        </div>

        <nav class="acc-nav">
            <div class="nav-section">Tổng quan</div>
            <a href="{{ route('accounting.dashboard') }}" class="{{ request()->routeIs('accounting.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('department-notifications.index', ['layout' => 'accounting']) }}" class="{{ request()->routeIs('department-notifications.*') && request('layout') === 'accounting' ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Tạo thông báo
            </a>

            <div class="nav-section">Đơn hàng</div>
            <a href="{{ route('accounting.workflow-simulation.index') }}" class="{{ request()->routeIs('accounting.workflow-simulation.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3-fill"></i> Mô phỏng quy trình
            </a>
            <a href="{{ route('accounting.orders') }}" class="{{ request()->routeIs('accounting.orders*') ? 'active' : '' }}">
                <i class="bi bi-bag-check"></i> Danh sách đơn hàng
            </a>
            <a href="{{ route('accounting.daily-orders') }}" class="{{ request()->routeIs('accounting.daily-orders') ? 'active' : '' }}">
                <i class="bi bi-calendar3"></i> Đơn hàng hàng ngày
            </a>
            <a href="{{ route('accounting.daily-sales') }}" class="{{ request()->routeIs('accounting.daily-sales') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i> Thống kê bán hàng
            </a>
            <a href="{{ route('accounting.reconciliation') }}" class="{{ request()->routeIs('accounting.reconciliation') ? 'active' : '' }}">
                <i class="bi bi-check2-square"></i> Đối soát đơn hàng
            </a>
            <a href="{{ route('accounting.sales-ledger.index') }}" class="{{ request()->routeIs('accounting.sales-ledger.*') ? 'active' : '' }}">
                <i class="bi bi-table"></i> Sổ doanh số kế toán
            </a>
            <a href="{{ route('admin.imported-sales-orders.index') }}" class="{{ request()->routeIs('admin.imported-sales-orders.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i> Hoàn chỉnh đơn lịch sử
            </a>

            <div class="nav-section">Ship</div>
            <a href="{{ route('accounting.shippers') }}" class="{{ request()->routeIs('accounting.shippers*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> Quản lý Shipper
            </a>
            <a href="{{ route('accounting.shipping-costs') }}" class="{{ request()->routeIs('accounting.shipping-costs*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i> Quản lý chi phí ship
            </a>

            <div class="nav-section">Tài chính</div>
            <a href="{{ route('accounting.cashflow') }}" class="{{ request()->routeIs('accounting.cashflow') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Thu chi
            </a>
            <a href="{{ route('accounting.transactions.create') }}" class="{{ request()->routeIs('accounting.transactions.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle"></i> Tạo giao dịch
            </a>
            <a href="{{ route('accounting.payment-matching') }}" class="{{ request()->routeIs('accounting.payment-matching*') ? 'active' : '' }}">
                <i class="bi bi-bank"></i> Form thanh toán
            </a>
            <a href="{{ route('accounting.financial-reports') }}" class="{{ request()->routeIs('accounting.financial-reports') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> Báo cáo tài chính
            </a>
            <a href="{{ route('accounting.accounts.index') }}" class="{{ request()->routeIs('accounting.accounts.index') || request()->routeIs('accounting.accounts.create') || request()->routeIs('accounting.accounts.edit') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Tài khoản
            </a>
            <a href="{{ route('accounting.accounts.balance-history') }}" class="{{ request()->routeIs('accounting.accounts.balance-history') ? 'active' : '' }}" style="padding-left:2rem;font-size:13px">
                <i class="bi bi-graph-up-down"></i> Lịch sử thay đổi số dư
            </a>
            <a href="{{ route('accounting.accounts.adjustments') }}" class="{{ request()->routeIs('accounting.accounts.adjustments') ? 'active' : '' }}" style="padding-left:2rem;font-size:13px">
                <i class="bi bi-clock-history"></i> Lịch sử nạp / rút tiền
            </a>
            <a href="{{ route('accounting.accounts.index') }}?action=deposit" style="padding-left:2rem;font-size:13px">
                <i class="bi bi-plus-circle text-success"></i> Nạp / Rút tiền
            </a>
            <a href="{{ route('accounting.transaction-categories.index') }}" class="{{ request()->routeIs('accounting.transaction-categories.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i> Quản trị danh mục giao dịch
            </a>

            <div class="nav-section">Công nợ</div>
            <a href="{{ route('accounting.customer-debts') }}" class="{{ request()->routeIs('accounting.customer-debts*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Công nợ khách hàng
            </a>
            <a href="{{ route('accounting.supplier-debts') }}" class="{{ request()->routeIs('accounting.supplier-debts') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Công nợ nhà cung cấp
            </a>

            <div class="nav-section">Chính sách</div>
            <a href="{{ route('accounting.commissions') }}" class="{{ request()->routeIs('accounting.commissions') ? 'active' : '' }}">
                <i class="bi bi-award"></i> Hoa hồng khách hàng
            </a>
            <a href="{{ route('accounting.discounts') }}" class="{{ request()->routeIs('accounting.discounts') ? 'active' : '' }}">
                <i class="bi bi-percent"></i> Chiết khấu khách hàng
            </a>

            <div class="nav-section">Kho</div>
            <a href="{{ route('accounting.inventory') }}" class="{{ request()->routeIs('accounting.inventory') ? 'active' : '' }}">
                <i class="bi bi-boxes"></i> Thống kê kho
            </a>
        </nav>

        <div class="acc-sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </button>
            </form>
        </div>
    </aside>
    <div class="mobile-drawer-overlay d-lg-none js-acc-overlay"></div>

    <main class="acc-main">
        <header class="acc-topbar">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light d-lg-none js-acc-toggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                <strong>@yield('title', 'Accounting')</strong>
                <div class="text-muted small">@yield('subtitle', 'Khu vuc nghiep vu ke toan')</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @include('layouts.partials.role_switcher')
                <span class="text-muted small" style="white-space: nowrap;">{{ auth()->user()->name ?? 'Accounting' }} | {{ now()->format('d/m/Y H:i') }}</span>
            </div>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" href="{{ route('pages.my_dashboard') }}">
                                    <i class="bi bi-house me-2"></i>Dashboard chính
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-gear me-2"></i>Hồ sơ
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline-block w-100">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
        </header>

        <section class="acc-content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @yield('accounting_content')
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.acc-sidebar');
    const toggle = document.querySelector('.js-acc-toggle');
    const overlay = document.querySelector('.js-acc-overlay');
    const collapse = document.querySelector('.js-acc-collapse');
    const storageKey = 'accountingSidebarCollapsed';

    document.querySelectorAll('.acc-nav a').forEach(function (link) {
        const label = link.textContent.replace(/\s+/g, ' ').trim();
        if (label) {
            link.setAttribute('data-label', label);
            if (!link.getAttribute('title')) {
                link.setAttribute('title', label);
            }
        }
    });

    const tooltip = document.createElement('div');
    tooltip.className = 'acc-menu-tooltip';
    document.body.appendChild(tooltip);

    const hideTooltip = function () {
        tooltip.classList.remove('show');
    };

    const showTooltip = function (link) {
        if (!document.body.classList.contains('acc-sidebar-collapsed')) {
            hideTooltip();
            return;
        }

        const label = link.getAttribute('data-label') || link.getAttribute('title') || '';
        if (!label) {
            hideTooltip();
            return;
        }

        const rect = link.getBoundingClientRect();
        tooltip.textContent = label;
        tooltip.style.left = (rect.right + 12) + 'px';
        tooltip.style.top = (rect.top + rect.height / 2) + 'px';
        tooltip.classList.add('show');
    };

    document.querySelectorAll('.acc-nav a').forEach(function (link) {
        link.addEventListener('mouseenter', function () { showTooltip(link); });
        link.addEventListener('focus', function () { showTooltip(link); });
        link.addEventListener('mouseleave', hideTooltip);
        link.addEventListener('blur', hideTooltip);
    });

    if (sidebar) {
        sidebar.addEventListener('scroll', hideTooltip);
    }

    if (collapse) {
        const setCollapsed = function (collapsed) {
            document.body.classList.toggle('acc-sidebar-collapsed', collapsed);
            collapse.setAttribute('aria-label', collapsed ? 'Mở rộng menu' : 'Thu gọn menu');
            collapse.setAttribute('title', collapsed ? 'Mở rộng menu' : 'Thu gọn menu');
            localStorage.setItem(storageKey, collapsed ? '1' : '0');
        };

        setCollapsed(localStorage.getItem(storageKey) === '1');
        collapse.addEventListener('click', function () {
            setCollapsed(!document.body.classList.contains('acc-sidebar-collapsed'));
        });
    }

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
