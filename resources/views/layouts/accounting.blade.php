<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Accounting - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --acc-bg: #f4f6fb;
            --acc-sidebar: #0f172a;
            --acc-panel: #ffffff;
            --acc-line: #e2e8f0;
            --acc-text: #0f172a;
            --acc-muted: #64748b;
            --acc-brand: #0ea5e9;
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
        }
        .acc-sidebar {
            background: linear-gradient(180deg, #0f172a, #0b1220);
            color: #dbeafe;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(148, 163, 184, 0.18);
        }
        .acc-brand {
            padding: 18px;
            font-weight: 800;
            letter-spacing: 0.02em;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }
        .acc-brand small {
            display: block;
            font-weight: 500;
            color: #93c5fd;
            margin-top: 4px;
        }
        .acc-nav {
            padding: 12px;
            flex: 1;
            overflow: auto;
        }
        .acc-nav a {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #dbeafe;
            text-decoration: none;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 6px;
            font-size: 14px;
        }
        .acc-nav a:hover { background: rgba(14, 165, 233, 0.15); }
        .acc-nav a.active {
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.22), rgba(59, 130, 246, 0.14));
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(125, 211, 252, 0.35);
        }
        .acc-sidebar-footer {
            padding: 12px;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
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
            .acc-shell { grid-template-columns: 1fr; }
            .acc-sidebar { display: none; }
            .acc-kpi { grid-template-columns: 1fr; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="acc-shell">
    <aside class="acc-sidebar">
        <div class="acc-brand">
            Accounting Workspace
            <small>Ke toan - doi soat - bao cao</small>
        </div>

        <nav class="acc-nav">
            <a href="{{ route('accounting.dashboard') }}" class="{{ request()->routeIs('accounting.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="{{ route('accounting.customer-debts') }}" class="{{ request()->routeIs('accounting.customer-debts') ? 'active' : '' }}"><i class="bi bi-people"></i> Cong no khach hang</a>
            <a href="{{ route('accounting.supplier-debts') }}" class="{{ request()->routeIs('accounting.supplier-debts') ? 'active' : '' }}"><i class="bi bi-building"></i> Cong no nha cung cap</a>
            <a href="{{ route('accounting.cashflow') }}" class="{{ request()->routeIs('accounting.cashflow') ? 'active' : '' }}"><i class="bi bi-cash-stack"></i> Thu chi</a>
            <a href="{{ route('accounting.reconciliation') }}" class="{{ request()->routeIs('accounting.reconciliation') ? 'active' : '' }}"><i class="bi bi-check2-square"></i> Doi soat don hang</a>
            <a href="{{ route('accounting.inventory') }}" class="{{ request()->routeIs('accounting.inventory') ? 'active' : '' }}"><i class="bi bi-boxes"></i> Thong ke kho</a>
            <a href="{{ route('accounting.commissions') }}" class="{{ request()->routeIs('accounting.commissions') ? 'active' : '' }}"><i class="bi bi-award"></i> Hoa hong khach hang</a>
            <a href="{{ route('accounting.discounts') }}" class="{{ request()->routeIs('accounting.discounts') ? 'active' : '' }}"><i class="bi bi-percent"></i> Chiet khau khach hang</a>
            <a href="{{ route('accounting.daily-orders') }}" class="{{ request()->routeIs('accounting.daily-orders') ? 'active' : '' }}"><i class="bi bi-list-ul"></i> Don hang hang ngay</a>
            <a href="{{ route('accounting.financial-reports') }}" class="{{ request()->routeIs('accounting.financial-reports') ? 'active' : '' }}"><i class="bi bi-graph-up-arrow"></i> Bao cao tai chinh</a>
        </nav>

        <div class="acc-sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger w-100 btn-sm"><i class="bi bi-box-arrow-right"></i> Dang xuat</button>
            </form>
        </div>
    </aside>

    <main class="acc-main">
        <header class="acc-topbar">
            <div>
                <strong>@yield('title', 'Accounting')</strong>
                <div class="text-muted small">@yield('subtitle', 'Khu vuc nghiep vu ke toan')</div>
            </div>
            <div class="text-muted small">{{ auth()->user()->name ?? 'Accounting' }} | {{ now()->format('d/m/Y H:i') }}</div>
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
@stack('scripts')
</body>
</html>
