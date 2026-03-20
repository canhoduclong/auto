<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shipper – @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @stack('styles')
    <style>
        :root { --sidebar-width: 240px; --sidebar-bg: #064e3b; --sidebar-active: #10b981; }
        body { min-height: 100vh; background: #f0fdf4; font-family: 'Inter', system-ui, sans-serif; }
        .sp-sidebar {
            width: var(--sidebar-width); min-height: 100vh; background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 200; display: flex; flex-direction: column;
        }
        .sp-brand {
            padding: 1rem 1.25rem; background: #022c22; color: #fff; font-weight: 700;
            font-size: .95rem; display: flex; align-items: center; gap: .5rem; border-bottom: 1px solid #065f46;
        }
        .sp-brand .badge { font-size: .65rem; background: #10b981; }
        .sp-nav-section {
            padding: .75rem 1.25rem .25rem; font-size: .7rem; color: #6ee7b7;
            text-transform: uppercase; letter-spacing: .08em; font-weight: 600;
        }
        .sp-nav-link {
            display: flex; align-items: center; gap: .6rem; padding: .55rem 1.25rem;
            color: #a7f3d0; font-size: .875rem; text-decoration: none;
            border-left: 3px solid transparent; transition: all .15s;
        }
        .sp-nav-link:hover { color: #fff; background: rgba(255,255,255,.06); border-left-color: #34d399; }
        .sp-nav-link.active { color: #fff; background: rgba(16,185,129,.2); border-left-color: var(--sidebar-active); }
        .sp-nav-link .badge { margin-left: auto; font-size: .65rem; }
        .sp-main { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }
        .sp-topbar {
            background: #fff; border-bottom: 1px solid #d1fae5; padding: .8rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100;
        }
        .sp-content { padding: 1.5rem; flex: 1; }
        .stat-card { border: none; border-radius: .75rem; transition: transform .15s; }
        .stat-card:hover { transform: translateY(-2px); }
        @media (max-width: 768px) {
            .sp-sidebar { transform: translateX(-100%); }
            .sp-main { margin-left: 0; }
        }
    </style>
</head>
<body>
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

            <div class="sp-nav-section">Đang giao</div>
            <a href="{{ route('shipper.my-orders') }}" class="sp-nav-link {{ request()->routeIs('shipper.my-orders') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Đơn của tôi
            </a>

            <div class="sp-nav-section">Lịch sử</div>
            <a href="{{ route('shipper.history') }}" class="sp-nav-link {{ request()->routeIs('shipper.history') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Lịch sử giao
            </a>
        </nav>
        <div class="p-3 border-top border-success border-opacity-25">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-person-circle text-success-emphasis"></i>
                <div class="small">
                    <div class="text-white fw-semibold" style="font-size:.8rem;">{{ auth()->user()->name }}</div>
                    <div style="color:#6ee7b7;font-size:.7rem;">Shipper</div>
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

    <!-- Main content -->
    <div class="sp-main">
        <header class="sp-topbar">
            <div>
                <h6 class="mb-0 fw-semibold">@yield('title', 'Dashboard')</h6>
                @hasSection('subtitle')
                    <div class="text-muted" style="font-size:.8rem;">@yield('subtitle')</div>
                @endif
            </div>
            <div class="d-flex align-items-center gap-3">
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
    </script>
    @stack('scripts')
</body>
</html>
