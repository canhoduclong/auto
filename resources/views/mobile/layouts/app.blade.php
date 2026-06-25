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
            position: relative;
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
        .m-topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            max-width: 720px;
            margin: 0 auto;
            padding: 8px 12px;
            background: rgba(248, 250, 252, .96);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
        }
        .m-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .m-brand-logo {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            background: #111827;
            color: #facc15;
            font-weight: 900;
            flex: 0 0 auto;
        }
        .m-brand-title {
            font-size: .9rem;
            font-weight: 900;
            line-height: 1.05;
            white-space: nowrap;
        }
        .m-brand-subtitle {
            font-size: .68rem;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 190px;
        }
        .m-top-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }
        .m-icon-btn {
            position: relative;
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #fff;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .1);
            text-decoration: none;
        }
        .m-notification-btn {
            width: 40px;
            height: 40px;
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
            box-shadow: 0 8px 18px rgba(194, 65, 12, .18);
        }
        .m-notification-btn .m-bell {
            font-size: 1.24rem;
            line-height: 1;
        }
        .m-noti-dot {
            position: absolute;
            right: -7px;
            top: -7px;
            min-width: 21px;
            height: 21px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #ef4444;
            color: #fff;
            border: 2px solid #fff;
            font-size: .68rem;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(239, 68, 68, .42);
        }
        .m-noti-dot::after {
            content: "";
            position: absolute;
            inset: -5px;
            border-radius: inherit;
            border: 1px solid rgba(239, 68, 68, .35);
            animation: mNotiPulse 1.8s ease-out infinite;
        }
        @keyframes mNotiPulse {
            from { opacity: .65; transform: scale(.78); }
            to { opacity: 0; transform: scale(1.28); }
        }
        .m-profile-menu {
            position: relative;
        }
        .m-profile-menu > summary {
            list-style: none;
            cursor: pointer;
            display: grid;
            place-items: center;
        }
        .m-profile-menu > summary::-webkit-details-marker { display: none; }
        .m-top-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .18);
            background: #e5e7eb;
        }
        .m-profile-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            width: min(88vw, 304px);
            max-height: min(78vh, 620px);
            border-radius: 14px;
            overflow: hidden auto;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .22);
            border: 1px solid #e2e8f0;
            z-index: 100;
        }
        .m-profile-head {
            display: grid;
            grid-template-columns: 48px minmax(0, 1fr);
            gap: 10px;
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            align-items: center;
        }
        .m-profile-head img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }
        .m-profile-head strong {
            font-size: .95rem;
            line-height: 1.18;
        }
        .m-profile-head .m-profile-mail {
            color: #475569;
            font-size: .78rem;
            margin-top: 2px;
        }
        .m-profile-head strong,
        .m-profile-head span {
            display: block;
            overflow-wrap: anywhere;
        }
        .m-profile-meta {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: .78rem;
            color: #334155;
            display: grid;
            gap: 3px;
            line-height: 1.25;
        }
        .m-profile-current-role {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 4px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 800;
            color: #0f172a;
        }
        .m-role-pill {
            max-width: 128px;
            border-radius: 999px;
            padding: 4px 8px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: .72rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .m-menu-section {
            padding: 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .m-menu-section:last-child { border-bottom: 0; }
        .m-menu-section.m-menu-muted { background: #eef4f6; }
        .m-menu-title {
            padding: 7px 10px 6px;
            font-size: .86rem;
            color: #64748b;
            font-weight: 650;
        }
        .m-menu-item,
        .m-menu-section form button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #0f172a;
            padding: 10px 12px;
            text-decoration: none;
            font-size: .94rem;
            text-align: left;
            font-family: inherit;
            cursor: pointer;
        }
        .m-menu-icon {
            width: 22px;
            flex: 0 0 22px;
            display: grid;
            place-items: center;
            color: #64748b;
        }
        .m-menu-item.active,
        .m-menu-section form button.active {
            background: #1677ff;
            color: #fff;
        }
        .m-menu-item.active .m-menu-icon,
        .m-menu-section form button.active .m-menu-icon {
            color: #bfdbfe;
        }
        .m-menu-item.danger,
        .m-menu-section form button.danger {
            color: #dc2626;
        }
        .m-menu-copy {
            display: block;
            margin-top: 2px;
            color: #475569;
            font-size: .78rem;
            line-height: 1.15;
        }
        .m-mobile-order-card {
            position: relative;
            padding-top: 38px;
            padding-bottom: 50px;
        }
        .m-mobile-status-badge {
            position: absolute;
            top: 10px;
            right: 12px;
            max-width: calc(100% - 112px);
            border-radius: 6px;
            padding: 5px 10px;
            background: #dcfce7;
            color: #16a34a;
            font-size: .78rem;
            line-height: 1.15;
            font-weight: 900;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: none;
        }
        .m-card-action-bottom {
            position: absolute;
            left: 12px;
            bottom: 12px;
            margin: 0;
            z-index: 3;
        }
        .m-card-menu {
            position: relative;
            display: inline-block;
        }
        .m-card-menu > summary {
            list-style: none;
            width: 36px;
            height: 36px;
            border-radius: 2px;
            border: 1px solid #f97316;
            display: grid;
            place-items: center;
            background: #fff;
            color: #334155;
            cursor: pointer;
            font-size: 1.24rem;
            line-height: 1;
            font-weight: 900;
        }
        .m-card-menu > summary::-webkit-details-marker { display: none; }
        .m-card-menu-pop {
            position: absolute;
            left: 0;
            bottom: calc(100% + 6px);
            width: 166px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .18);
            padding: 6px;
            z-index: 10;
        }
        .m-card-menu-pop a,
        .m-card-menu-pop button {
            width: 100%;
            display: block;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #0f172a;
            padding: 9px 10px;
            text-align: left;
            text-decoration: none;
            font-size: .84rem;
            font-weight: 700;
        }
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
    @php
        $mobileUser = auth()->user();
        $rawAvatar = trim((string) ($mobileUser?->google_avatar ?: $mobileUser?->avatar));
        $avatarUrl = $rawAvatar !== ''
            ? (\Illuminate\Support\Str::startsWith($rawAvatar, ['http://', 'https://']) ? $rawAvatar : asset(ltrim($rawAvatar, '/')))
            : 'https://ui-avatars.com/api/?name=' . urlencode($mobileUser?->name ?? 'User') . '&background=0f766e&color=fff';
        $activeRole = strtolower((string) session('active_role', $mobileUser?->defaultRole?->name ?? ''));
        $topRoles = $mobileUser?->roles ?? collect();
        $roleLabels = [
            'sale' => 'Kinh doanh',
            'leader' => 'Trưởng nhóm kinh doanh',
            'leader_sale' => 'Trưởng nhóm kinh doanh',
            'sale_manager' => 'Trưởng phòng kinh doanh',
            'manager' => 'Quản lý',
            'manager_sale' => 'Quản lý kinh doanh',
            'account' => 'Kế toán',
            'accountant' => 'Kế toán',
            'accounting' => 'Kế toán',
            'shipper' => 'Shipper',
            'ship' => 'Shipper',
            'manager_shipper' => 'Quản lý shipper',
            'warehouse' => 'Kho',
            'package' => 'Đóng hàng',
            'ceo' => 'CEO',
            'procurement_manager' => 'Thu mua',
            'admin' => 'Admin',
        ];
        $roleIcons = [
            'sale' => '◉',
            'leader' => '◉',
            'leader_sale' => '◉',
            'sale_manager' => '◉',
            'manager' => '◉',
            'manager_sale' => '◉',
            'account' => '▣',
            'accountant' => '▣',
            'accounting' => '▣',
            'shipper' => '▱',
            'ship' => '▱',
            'manager_shipper' => '▱',
            'warehouse' => '◇',
            'package' => '▦',
            'ceo' => '□',
            'procurement_manager' => '▽',
            'admin' => '◆',
        ];
        $activeRoleModel = $activeRole
            ? $topRoles->first(fn ($role) => strcasecmp((string) $role->name, (string) $activeRole) === 0)
            : null;
        $activeRoleModel ??= $mobileUser?->defaultRole ?: $topRoles->first();
        $activeRoleName = $activeRoleModel?->name;
        $activeRoleKey = strtolower((string) $activeRoleName);
        $currentRoleLabel = $roleLabels[$activeRoleKey] ?? $activeRoleModel?->display_name ?? $activeRoleName ?? $mobileUser?->job_title ?? 'Nhân sự';
        $unreadCount = (\Illuminate\Support\Facades\Schema::hasTable('notifications') && $mobileUser) ? $mobileUser->unreadNotifications()->count() : 0;
    @endphp
    <header class="m-topbar">
        <div class="m-brand">
            <div class="m-brand-logo">HL</div>
            <div class="min-w-0">
                <div class="m-brand-title">HOÀNG LONG TNT</div>
                <div class="m-brand-subtitle">Uy tín - Chất lượng - Tận tâm</div>
            </div>
        </div>
        <div class="m-top-actions">
            <a class="m-icon-btn m-notification-btn" href="{{ route('pages.my_dashboard') }}" aria-label="Thông báo">
                <span class="m-bell" aria-hidden="true">🔔</span>
                @if($unreadCount > 0)<span class="m-noti-dot">{{ min($unreadCount, 99) }}</span>@endif
            </a>
            <details class="m-profile-menu">
                <summary><img src="{{ $avatarUrl }}" alt="{{ $mobileUser?->name }}" class="m-top-avatar"></summary>
                <div class="m-profile-dropdown">
                    <div class="m-profile-head">
                        <img src="{{ $avatarUrl }}" alt="{{ $mobileUser?->name }}">
                        <div>
                            <strong>{{ $mobileUser?->name }}</strong>
                            <span class="m-profile-mail">{{ $mobileUser?->email ?: 'Chưa cập nhật email' }}</span>
                        </div>
                    </div>
                    <div class="m-profile-meta">
                        <div class="m-profile-current-role">
                            <span>Trạng thái</span>
                            <span class="m-role-pill">{{ $currentRoleLabel }}</span>
                        </div>
                        <div><strong>Chức vụ:</strong> {{ $mobileUser?->job_title ?: $currentRoleLabel }}</div>
                        <div><strong>Email:</strong> {{ $mobileUser?->email ?: '—' }}</div>
                        <div><strong>Tel:</strong> {{ $mobileUser?->phone ?: '—' }}</div>
                        <div><strong>Ngày gia nhập:</strong> {{ optional($mobileUser?->created_at)->format('d/m/Y') }}</div>
                    </div>
                    <div class="m-menu-section">
                        <div class="m-menu-title">Chuyển vai trò</div>
                        @foreach($topRoles as $role)
                            @php($roleKey = strtolower((string) $role->name))
                            <form action="{{ route('role.switch', $role->name) }}" method="POST">
                                @csrf
                                <input type="hidden" name="surface" value="my_app">
                                <button class="{{ strcasecmp((string) ($activeRoleName ?? $activeRole), (string) $role->name) === 0 ? 'active' : '' }}" type="submit">
                                    <span class="m-menu-icon">{{ $roleIcons[$roleKey] ?? '▣' }}</span><span>{{ $roleLabels[$roleKey] ?? $role->display_name ?? $role->name }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                    <div class="m-menu-section m-menu-muted">
                        <a class="m-menu-item" href="{{ route('pages.my_profile') }}"><span class="m-menu-icon">♙</span><span>Chỉnh sửa profile</span></a>
                        <a class="m-menu-item" href="{{ route('mobile.home') }}"><span class="m-menu-icon">⇩</span><span>Cập nhật iOS</span></a>
                        <a class="m-menu-item" href="{{ route('mobile.home') }}"><span class="m-menu-icon">⇩</span><span>Cập nhật ứng dụng<span class="m-menu-copy">Kiểm tra và cập nhật bản mới</span></span></a>
                    </div>
                    <div class="m-menu-section">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="danger" type="submit"><span class="m-menu-icon">↪</span><span>Đăng xuất</span></button>
                        </form>
                    </div>
                </div>
            </details>
        </div>
    </header>
    <div class="m-wrap">
        @yield('content')
    </div>

    <nav class="m-bottom-nav" style="display:none">
        @if($mobileUser?->hasRole('sale') || $mobileUser?->hasRole('leader') || $mobileUser?->hasRole('leader_sale') || $mobileUser?->hasRole('sale_manager') || $mobileUser?->hasRole('manager') || $mobileUser?->hasRole('manager_sale') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.sale.home') }}" class="{{ request()->routeIs('mobile.sale.*') ? 'active' : '' }}">Sale</a>
        @endif
        @if($mobileUser?->hasRole('account') || $mobileUser?->hasRole('accountant') || $mobileUser?->hasRole('accounting') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.accounting.home') }}" class="{{ request()->routeIs('mobile.accounting.*') ? 'active' : '' }}">Account</a>
        @endif
        @if($mobileUser?->hasRole('ceo') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.ceo.home') }}" class="{{ request()->routeIs('mobile.ceo.*') ? 'active' : '' }}">CEO</a>
        @endif
        @if($mobileUser?->hasRole('warehouse') || $mobileUser?->hasRole('package') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.warehouse.home') }}" class="{{ request()->routeIs('mobile.warehouse.*') ? 'active' : '' }}">Warehouse</a>
        @endif
        @if($mobileUser?->hasRole('shipper') || $mobileUser?->hasRole('ship') || $mobileUser?->hasRole('manager_shipper') || $mobileUser?->hasRole('admin'))
            <a href="{{ route('mobile.shipper.home') }}" class="{{ request()->routeIs('mobile.shipper.*') ? 'active' : '' }}">Shipper</a>
        @endif
    </nav>

    @stack('scripts')
</body>
</html>
