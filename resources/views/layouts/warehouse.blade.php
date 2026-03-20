<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kho hàng – @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @stack('styles')
    <style>
        :root { --sidebar-width: 240px; --sidebar-bg: #1e293b; --sidebar-active: #3b82f6; }
        body {
            min-height: 100vh;
            background: #f1f5f9;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 1.04rem;
        }
        .wh-sidebar {
            width: var(--sidebar-width); min-height: 100vh; background: var(--sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 200; display: flex; flex-direction: column;
        }
        .wh-brand {
            padding: 1rem 1.25rem; background: #0f172a; color: #fff; font-weight: 700;
            font-size: 1.02rem; display: flex; align-items: center; gap: .5rem; border-bottom: 1px solid #334155;
        }
        .wh-brand .badge { font-size: .65rem; background: #3b82f6; }
        .wh-nav-section {
            padding: .75rem 1.25rem .25rem; font-size: .78rem; color: #64748b;
            text-transform: uppercase; letter-spacing: .08em; font-weight: 600;
        }
        .wh-nav-link {
            display: flex; align-items: center; gap: .6rem; padding: .55rem 1.25rem;
            color: #94a3b8; font-size: .95rem; text-decoration: none;
            border-left: 3px solid transparent; transition: all .15s;
        }
        .wh-nav-link:hover { color: #e2e8f0; background: rgba(255,255,255,.04); border-left-color: #475569; }
        .wh-nav-link.active { color: #fff; background: rgba(59,130,246,.15); border-left-color: var(--sidebar-active); }
        .wh-nav-link .badge { margin-left: auto; font-size: .65rem; }
        .wh-main { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }
        .wh-topbar {
            background: #fff; border-bottom: 1px solid #e2e8f0; padding: .8rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100;
        }
        .wh-topbar .breadcrumb { margin-bottom: 0; font-size: .95rem; }
        .wh-topbar h6 { font-size: 1.08rem; }
        .wh-topbar .small { font-size: .93rem !important; }
        .wh-content { padding: 1.5rem; flex: 1; }
        .stat-card { border: none; border-radius: .75rem; transition: transform .15s; }
        .stat-card:hover { transform: translateY(-2px); }
        @media (max-width: 768px) {
            .wh-sidebar { transform: translateX(-100%); }
            .wh-main { margin-left: 0; }
        }
    </style>
</head>
<body>
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

            <div class="wh-nav-section">Trả hàng</div>
            <a href="{{ route('warehouse.returns') }}" class="wh-nav-link {{ request()->routeIs('warehouse.returns') ? 'active' : '' }}">
                <i class="bi bi-arrow-return-left"></i> Đơn trả về
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
                    <div class="text-muted" style="font-size:.82rem;">Warehouse</div>
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

    <!-- Main content -->
    <div class="wh-main">
        <header class="wh-topbar">
            <div>
                <h6 class="mb-0 fw-semibold">@yield('title', 'Dashboard')</h6>
                @hasSection('subtitle')
                    <div class="text-muted" style="font-size:.9rem;">@yield('subtitle')</div>
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
            const el = document.getElementById('current-time');
            if (el) el.textContent = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit', minute:'2-digit'});
            setTimeout(tick, 60000);
        })();
    </script>
    @stack('scripts')
</body>
</html>
