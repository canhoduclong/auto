<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CEO - @yield('title', 'Executive')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @stack('styles')
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
            min-height: 100vh;
            background: linear-gradient(180deg, var(--ceo-sidebar-bg) 0%, var(--ceo-sidebar-strong) 100%);
            color: #e2e8f0;
            z-index: 100;
            display: flex;
            flex-direction: column;
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
            overflow: auto;
            flex: 1;
        }
        .ceo-nav-link {
            display: flex;
            align-items: center;
            gap: .65rem;
            color: #dbeafe;
            text-decoration: none;
            border-radius: 12px;
            padding: .55rem .7rem;
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
        @media (max-width: 992px) {
            .ceo-sidebar {
                transform: translateX(-100%);
            }
            .ceo-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <aside class="ceo-sidebar">
        <div class="ceo-brand">
            <span class="dot"></span>
            <span>CEO Executive</span>
        </div>
        <nav class="ceo-nav">
            <a href="{{ route('ceo.dashboard') }}" class="ceo-nav-link {{ request()->routeIs('ceo.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('ceo.revenue') }}" class="ceo-nav-link {{ request()->routeIs('ceo.revenue') ? 'active' : '' }}">
                <i class="bi bi-currency-dollar"></i> Doanh thu
            </a>
            <a href="{{ route('ceo.orders') }}" class="ceo-nav-link {{ request()->routeIs('ceo.orders') ? 'active' : '' }}">
                <i class="bi bi-bag-check"></i> Đơn hàng
            </a>
            <a href="{{ route('ceo.sales') }}" class="ceo-nav-link {{ request()->routeIs('ceo.sales') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> Hiệu suất Sale
            </a>
            <a href="{{ route('ceo.debts') }}" class="ceo-nav-link {{ request()->routeIs('ceo.debts') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i> Công nợ
            </a>
            <a href="{{ route('ceo.warehouse') }}" class="ceo-nav-link {{ request()->routeIs('ceo.warehouse') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Kho
            </a>
            <a href="{{ route('ceo.shipper') }}" class="ceo-nav-link {{ request()->routeIs('ceo.shipper') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Shipper
            </a>
            <a href="{{ route('ceo.customers') }}" class="ceo-nav-link {{ request()->routeIs('ceo.customers') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Khách hàng
            </a>
            <a href="{{ route('ceo.alerts') }}" class="ceo-nav-link {{ request()->routeIs('ceo.alerts') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle"></i> Cảnh báo
            </a>
            <a href="{{ route('ceo.reports') }}" class="ceo-nav-link {{ request()->routeIs('ceo.reports') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Báo cáo
            </a>
        </nav>
    </aside>

    <main class="ceo-main">
        <header class="ceo-topbar">
            <div>
                <strong>@yield('title', 'Executive')</strong>
                <div class="text-muted small">@yield('subtitle', 'Bảng điều hành CEO')</div>
            </div>
            <div class="ceo-user">
                {{ auth()->user()->name ?? 'CEO' }} | {{ now()->format('d/m/Y H:i') }}
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
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
