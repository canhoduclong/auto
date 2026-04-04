<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mobile Web')</title>
    <style>
        :root {
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #0f766e;
            --danger: #b91c1c;
            --warn: #b45309;
            --radius: 14px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", sans-serif;
            background: linear-gradient(180deg, #f8fafc 0%, var(--bg) 100%);
            color: var(--text);
        }
        .m-wrap {
            max-width: 720px;
            margin: 0 auto;
            padding: 14px 12px 90px;
        }
        .m-header {
            border-radius: var(--radius);
            background: linear-gradient(140deg, #0f172a, #0f766e);
            color: #fff;
            padding: 14px;
            margin-bottom: 12px;
        }
        .m-title { margin: 0; font-size: 1.05rem; font-weight: 800; }
        .m-subtitle { margin: 4px 0 0; font-size: 0.83rem; opacity: .88; }
        .m-card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid #dbe3ef;
            padding: 12px;
            margin-bottom: 10px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
        }
        .m-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 8px;
        }
        .m-label { font-size: .76rem; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
        .m-value { font-size: .95rem; font-weight: 700; }
        .m-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .m-btn {
            border: 0;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: .95rem;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            cursor: pointer;
        }
        .m-btn-primary { background: var(--primary); color: #fff; }
        .m-btn-outline { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .m-btn-danger { background: var(--danger); color: #fff; }
        .m-btn-warn { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .m-btn:disabled { opacity: .5; cursor: not-allowed; }
        .m-input, .m-select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 11px 12px;
            font-size: .94rem;
            background: #fff;
        }
        .m-alert {
            border-radius: 12px;
            padding: 10px 12px;
            font-size: .88rem;
            margin-bottom: 8px;
        }
        .m-alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .m-alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .m-bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #dbe3ef;
            padding: 8px 10px calc(8px + env(safe-area-inset-bottom));
            display: flex;
            gap: 8px;
        }
        .m-bottom-nav a {
            flex: 1;
            text-align: center;
            padding: 10px 8px;
            border-radius: 10px;
            color: #334155;
            text-decoration: none;
            font-size: .8rem;
            font-weight: 700;
            background: #f8fafc;
        }
        .m-bottom-nav a.active { background: #e6fffb; color: #0f766e; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="m-wrap">
        @yield('content')
    </div>

    <nav class="m-bottom-nav">
        <a href="{{ route('mobile.sale.home') }}" class="{{ request()->routeIs('mobile.sale.*') ? 'active' : '' }}">Sale</a>
        <a href="{{ route('mobile.warehouse.home') }}" class="{{ request()->routeIs('mobile.warehouse.*') ? 'active' : '' }}">Warehouse</a>
        <a href="{{ route('mobile.shipper.home') }}" class="{{ request()->routeIs('mobile.shipper.*') ? 'active' : '' }}">Shipper</a>
    </nav>

    @stack('scripts')
</body>
</html>
