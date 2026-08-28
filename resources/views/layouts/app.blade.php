<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('auth.login_title'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">



 


    
	<!-- Global stylesheets -->
	<link href="{{ asset('assets/fonts/inter/inter.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ asset('assets/icons/phosphor/styles.min.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ asset('assets/css/ltr/all.min.css') }}" id="stylesheet" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/mobile-responsive.css') }}" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
	<!-- Core JS files -->
	<script src="{{ asset('assets/demo/demo_configurator.js') }}"></script>
	<script src="{{ asset('assets/js/jquery/jquery.min.js') }}"></script> 
	<script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
    <script src="{{ asset('assets/js/app.js') }}"></script>

   
    <script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js']) 

    <style>
        @media (max-width: 991.98px) {
            .sidebar.sidebar-main {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: min(86vw, 320px);
                transform: translateX(-100%);
                transition: transform 0.22s ease;
                z-index: 220;
            }

            .sidebar.sidebar-main.mobile-sidebar-open {
                transform: translateX(0);
            }

            .content-wrapper {
                margin-left: 0 !important;
                width: 100%;
            }
        }

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
    </style>

    @stack('styles')
</head>
<body class="{{ !empty($isMobileClient) ? 'is-mobile-client' : '' }}">

    <div class="page-content"> 
		<div class="sidebar sidebar-dark sidebar-main sidebar-expand-lg">
             @include('layouts.sidebar')
        </div>
        <div class="mobile-drawer-overlay d-lg-none js-mobile-drawer-overlay"></div>
        <div class="content-wrapper">
            <div class="navbar navbar-expand-lg navbar-static shadow">
				<div class="container-fluid">
                    @php
                        $currentUser = auth()->user();
                        $isAdmin = $currentUser?->hasRole('admin') ?? false;
                        $hasNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
                        $hasUserLastSeenColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'last_seen_at');
                        $visibleUnreadNotifications = ($isAdmin && $hasNotificationsTable)
                            ? ($currentUser?->unreadNotifications()->get() ?? collect())
                                ->filter(fn ($item) => !departmentBroadcastIsExpired($item->data ?? []) && !departmentBroadcastIsScheduled($item->data ?? []))
                            : collect();
                        $latestNotifications = ($isAdmin && $hasNotificationsTable)
                            ? ($currentUser?->notifications()->latest()->take(50)->get() ?? collect())
                                ->filter(fn ($item) => !departmentBroadcastIsExpired($item->data ?? []) && !departmentBroadcastIsScheduled($item->data ?? []))
                                ->take(5)
                            : collect();
                        $unreadNotificationsCount = $visibleUnreadNotifications->count();
                        $onlineWindowMinutes = 5;
                        $presenceUsers = ($isAdmin && $hasUserLastSeenColumn)
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
                        $currentLocale = app()->getLocale();
                    @endphp

                    <button type="button" class="btn btn-light btn-sm d-lg-none me-2 js-global-mobile-menu-toggle" aria-label="Open menu">
                        <i class="ph ph-list"></i>
                    </button>

                    <div class="ms-auto d-flex align-items-center gap-2 py-2">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ __('common.language.label') }}: {{ strtoupper($currentLocale) }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item {{ $currentLocale === 'vi' ? 'active' : '' }}" href="{{ route('locale.switch', 'vi') }}">{{ __('common.language.vi') }}</a></li>
                                <li><a class="dropdown-item {{ $currentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">{{ __('common.language.en') }}</a></li>
                            </ul>
                        </div>
                    </div>
                        <div class="ms-auto d-flex align-items-center gap-2 py-2">
                            @if($currentUser)
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ph ph-user me-1"></i>{{ $currentUser->name }}
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @php
                                            $userRoles = $currentUser->roles()->pluck('name')->toArray();
                                            $dashboardUrl = null;
                                            if (in_array('account', $userRoles) || in_array('accountant', $userRoles) || in_array('accounting', $userRoles)) {
                                                $dashboardUrl = route('accounting.dashboard');
                                            } elseif (array_intersect($userRoles, ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale'])) {
                                                $dashboardUrl = route('pages.my_dashboard');
                                            } else {
                                                $dashboardUrl = route('dashboard');
                                            }
                                        @endphp
                                        <li>
                                            <a class="dropdown-item" href="{{ $dashboardUrl }}">
                                                <i class="ph ph-house me-2"></i>Dashboard
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                                <i class="ph ph-gear me-2"></i>Profile
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="ph ph-sign-out me-2"></i>Logout
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            @endif

                            <div class="dropdown">
                                <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ __('common.language.label') }}: {{ strtoupper($currentLocale) }}
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item {{ $currentLocale === 'vi' ? 'active' : '' }}" href="{{ route('locale.switch', 'vi') }}">{{ __('common.language.vi') }}</a></li>
                                    <li><a class="dropdown-item {{ $currentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">{{ __('common.language.en') }}</a></li>
                                </ul>
                            </div>
                        </div>

                    @if($isAdmin)
                        <div class="ms-auto d-flex align-items-center gap-2 py-2">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="User Online/Offline">
                                    <i class="ph ph-users-three"></i>
                                    @if($onlineUsersCount > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $onlineUsersCount > 99 ? '99+' : $onlineUsersCount }}
                                        </span>
                                    @endif
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 340px; max-height: calc(100vh - 80px); overflow-y: auto;">
                                    {{-- Header --}}
                                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                                        <div class="fw-semibold" style="font-size:.82rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Trạng thái người dùng</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill d-flex align-items-center gap-1" style="background:rgba(34,197,94,.12);color:#16a34a;font-size:.75rem;">
                                                <span class="user-presence-dot online"></span> Online {{ $onlineUsersCount }}
                                            </span>
                                            <span class="badge rounded-pill d-flex align-items-center gap-1" style="background:rgba(148,163,184,.15);color:#64748b;font-size:.75rem;">
                                                <span class="user-presence-dot offline"></span> Offline {{ $offlineUsersCount }}
                                            </span>
                                        </div>
                                    </div>
                                    {{-- User rows --}}
                                    @forelse($presenceUsers as $presenceUser)
                                        @php
                                            $puAvatar = $presenceUser->avatar
                                                ? asset($presenceUser->avatar)
                                                : ($presenceUser->google_avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($presenceUser->name ?? 'U') . '&background=0F172A&color=F8FAFC&size=80&bold=true');
                                        @endphp
                                        <a href="{{ route('users.show', $presenceUser->id) }}" class="presence-row text-decoration-none" style="color:inherit;">
                                            {{-- Avatar + status dot --}}
                                            <div class="presence-avatar-wrap">
                                                <img src="{{ $puAvatar }}" alt="{{ $presenceUser->name }}" class="presence-avatar">
                                                <span class="presence-status-dot {{ $presenceUser->is_online ? 'online' : 'offline' }}"></span>
                                            </div>
                                            {{-- Info --}}
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-semibold text-truncate" style="font-size:.875rem;color:#1e293b;">{{ $presenceUser->name }}</div>
                                                <div class="text-truncate" style="font-size:.75rem;color:#64748b;">{{ $presenceUser->email }}</div>
                                                <div style="font-size:.72rem;color:#94a3b8;margin-top:1px;">
                                                    @if($presenceUser->is_online)
                                                        <span style="color:#16a34a;">● Đang online</span>
                                                    @else
                                                        {{ $presenceUser->last_seen_at ? 'Login ' . $presenceUser->last_seen_at->format('d/m H:i') . ' (' . $presenceUser->last_seen_at->diffForHumans() . ')' : 'Chưa đăng nhập' }}
                                                    @endif
                                                </div>
                                            </div>
                                            {{-- Badge --}}
                                            <span class="badge rounded-pill flex-shrink-0 d-flex align-items-center gap-1"
                                                  style="font-size:.72rem; {{ $presenceUser->is_online ? 'background:rgba(34,197,94,.12);color:#16a34a;' : 'background:rgba(148,163,184,.15);color:#94a3b8;' }}">
                                                <span class="user-presence-dot {{ $presenceUser->is_online ? 'online' : 'offline' }}"></span>
                                                {{ $presenceUser->is_online ? 'Online' : 'Offline' }}
                                            </span>
                                        </a>
                                    @empty
                                        <div class="p-4 text-muted text-center" style="font-size:.85rem;">Chưa có dữ liệu trạng thái user</div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-light btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ph ph-bell"></i>
                                    @if($unreadNotificationsCount > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                                        </span>
                                    @endif
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-0" style="width: 360px; max-height: 420px; overflow-y: auto;">
                                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                        <strong>{{ __('common.notifications.title') }}</strong>
                                        <a href="{{ route('admin.notifications.index') }}" class="small">{{ __('common.actions.view_all') }}</a>
                                    </div>
                                    @forelse($latestNotifications as $notification)
                                        <form action="{{ route('admin.notifications.read', $notification->id) }}" method="POST" class="border-bottom">
                                            @csrf
                                            <button type="submit" class="dropdown-item py-2 {{ is_null($notification->read_at) ? 'bg-light' : '' }}">
                                                <div class="fw-semibold">{{ $notification->data['title'] ?? __('common.notifications.title') }}</div>
                                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($notification->data['message'] ?? '-', 80) }}</div>
                                                <div class="small text-muted">{{ optional($notification->created_at)->diffForHumans() }}</div>
                                            </button>
                                        </form>
                                    @empty
                                        <div class="p-3 text-muted text-center">{{ __('common.notifications.empty') }}</div>
                                    @endforelse
                                    <div class="p-2 border-top text-center">
                                        <a href="{{ route('admin.events.index') }}" class="small">{{ __('common.notifications.event_log') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    </div>
			</div>

            <div class="content pt-3">
                {{-- Hiển thị lỗi --}}
                @if ($errors->any())
                    <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-300 rounded">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                
               
 </div>


            <div class="content-inner"> 
                @yield('content')
            </div>  

            <div class="navbar navbar-sm navbar-footer border-top bg-white">
                <div class="container-fluid">
                    <span class="text-muted small">&copy; {{ date('Y') }} {{ config('app.name', 'Auto') }}</span>
                    <span class="text-muted small">Admin Panel</span>
                </div>
            </div>
        </div>
       
        

        {{-- Plugins thường dùng của Limitless (tùy gói bạn có) --}}
        {{-- <script src="{{ asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script> --}}
        {{-- <script> $('.form-input-styled').uniform(); </script> --}}

        @include('layouts.partials.session_expiry_redirect')
        @stack('scripts')
        
    </div>

@include('layouts.notifications')

<script>
    // Function to dynamically show a toast
    function showToast(message, type = 'success') {
        const container = document.getElementById('notification-container');
        if (!container) return;

        const toastEl = document.createElement('div');
        toastEl.classList.add('toast');
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');

        let headerClass = '';
        if (type === 'error') {
            headerClass = 'bg-danger text-white';
        }

        const toastLabels = @json(__('common.toast_types'));
        const closeLabel = @json(__('common.actions.close'));
        const typeLabel = toastLabels[type] || type;

        toastEl.innerHTML = `
            <div class="toast-header ${headerClass}">
                <strong class="me-auto">${typeLabel}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="${closeLabel}"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;

        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
    }

    // Show session-based toasts on page load
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('notification-container');
        if (container) {
            const toastElements = container.querySelectorAll('.toast');
            toastElements.forEach(toastEl => {
                const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
                toast.show();
            });
        }

        const sidebar = document.querySelector('.sidebar.sidebar-main');
        const toggleBtn = document.querySelector('.js-global-mobile-menu-toggle');
        const overlay = document.querySelector('.js-mobile-drawer-overlay');

        if (sidebar && toggleBtn && overlay) {
            const closeSidebar = function () {
                sidebar.classList.remove('mobile-sidebar-open');
                document.body.classList.remove('mobile-menu-open');
            };

            toggleBtn.addEventListener('click', function () {
                sidebar.classList.add('mobile-sidebar-open');
                document.body.classList.add('mobile-menu-open');
            });

            overlay.addEventListener('click', closeSidebar);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeSidebar();
                }
            });
        }
    });
</script>
</body>
</html>
