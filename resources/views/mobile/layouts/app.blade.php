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
        .m-profile-card {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: 104px minmax(0, 1fr);
            gap: 14px;
            align-items: center;
            min-height: 162px;
            border-radius: 28px;
            border: 1px solid rgba(247, 214, 143, .34);
            background:
                radial-gradient(circle at 18% 50%, rgba(247, 214, 143, .18) 0 30%, transparent 31%),
                linear-gradient(135deg, #031b32 0%, #052846 52%, #061f38 100%);
            color: #fff;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 18px 38px rgba(3, 27, 50, .2);
        }
        .m-profile-card::before,
        .m-profile-card::after {
            content: "";
            position: absolute;
            inset: -18px -26px auto auto;
            width: 210px;
            height: 210px;
            border: 1px solid rgba(247, 214, 143, .16);
            border-radius: 50%;
            background:
                repeating-conic-gradient(from 12deg, rgba(247, 214, 143, .2) 0deg 2deg, transparent 2deg 11deg);
            opacity: .62;
            pointer-events: none;
        }
        .m-profile-card::after {
            inset: auto auto -54px 18px;
            width: 132px;
            height: 132px;
            opacity: .38;
        }
        .m-profile-photo-wrap {
            position: relative;
            z-index: 1;
            width: 104px;
            aspect-ratio: 1;
            border-radius: 50%;
            padding: 5px;
            background: linear-gradient(135deg, #fff8df, #d8aa54 54%, #08233d 55%);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .28);
        }
        .m-profile-photo {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #08233d;
            background: #e5e7eb;
            display: block;
        }
        .m-profile-info {
            position: relative;
            z-index: 1;
            min-width: 0;
            text-shadow: 0 2px 3px rgba(0, 0, 0, .45);
        }
        .m-profile-name {
            margin: 0 0 8px;
            font-size: clamp(1.34rem, 6vw, 2rem);
            line-height: 1.05;
            font-weight: 900;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }
        .m-profile-role {
            margin: 0 0 8px;
            font-size: 1.02rem;
            line-height: 1.2;
            font-weight: 800;
            overflow-wrap: anywhere;
        }
        .m-profile-line {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 6px;
            font-size: .94rem;
            line-height: 1.25;
            font-weight: 750;
            overflow-wrap: anywhere;
        }
        .m-profile-icon {
            flex: 0 0 auto;
            color: #f5cf7a;
            text-shadow: none;
        }
        .m-profile-brand {
            position: absolute;
            left: 18px;
            bottom: 14px;
            z-index: 2;
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            background: #061f38;
            border: 1px solid rgba(247, 214, 143, .45);
            color: #f5cf7a;
            font-weight: 950;
            line-height: .8;
            box-shadow: 0 7px 14px rgba(0, 0, 0, .22);
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
        @media (max-width: 390px) {
            .m-profile-card {
                grid-template-columns: 86px minmax(0, 1fr);
                gap: 12px;
                min-height: 146px;
                border-radius: 22px;
                padding: 14px;
            }
            .m-profile-photo-wrap { width: 86px; }
            .m-profile-brand {
                width: 40px;
                height: 40px;
                left: 16px;
                bottom: 12px;
                font-size: .82rem;
            }
            .m-profile-name { font-size: 1.2rem; }
            .m-profile-role { font-size: .94rem; }
            .m-profile-line { font-size: .82rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="m-wrap">
        @yield('content')
    </div>

    @php($mobileUser = auth()->user())
    <nav class="m-bottom-nav">
        @if($mobileUser?->hasRole('sale') || $mobileUser?->hasRole('leader') || $mobileUser?->hasRole('leader_sale') || $mobileUser?->hasRole('sale_manager') || $mobileUser?->hasRole('manager') || $mobileUser?->hasRole('manager_sale') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.sale.home') }}" class="{{ request()->routeIs('mobile.sale.*') ? 'active' : '' }}">Sale</a>
        @endif
        @if($mobileUser?->hasRole('account') || $mobileUser?->hasRole('accountant') || $mobileUser?->hasRole('accounting') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.accounting.home') }}" class="{{ request()->routeIs('mobile.accounting.*') ? 'active' : '' }}">Account</a>
        @endif
        @if($mobileUser?->hasRole('ceo') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.ceo.home') }}" class="{{ request()->routeIs('mobile.ceo.*') ? 'active' : '' }}">CEO</a>
        @endif
        @if($mobileUser?->hasRole('warehouse') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.warehouse.home') }}" class="{{ request()->routeIs('mobile.warehouse.*') ? 'active' : '' }}">Warehouse</a>
        @endif
        @if($mobileUser?->hasRole('shipper') || $mobileUser?->hasRole('ship') || $mobileUser?->hasRole('manager_shipper') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.shipper.home') }}" class="{{ request()->routeIs('mobile.shipper.*') ? 'active' : '' }}">Shipper</a>
        @endif
    </nav>

    @stack('scripts')
</body>
</html>
