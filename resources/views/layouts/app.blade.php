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
            margin-right: 6px;
            flex-shrink: 0;
        }

        .user-presence-dot.online {
            background: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
        }

        .user-presence-dot.offline {
            background: #94a3b8;
            box-shadow: 0 0 0 2px rgba(148, 163, 184, 0.16);
        }
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
                        $unreadNotificationsCount = ($isAdmin && $hasNotificationsTable)
                            ? ($currentUser?->unreadNotifications()->count() ?? 0)
                            : 0;
                        $latestNotifications = ($isAdmin && $hasNotificationsTable)
                            ? ($currentUser?->notifications()->latest()->take(5)->get() ?? collect())
                            : collect();
                        $onlineWindowMinutes = 5;
                        $presenceUsers = ($isAdmin && $hasUserLastSeenColumn)
                            ? (\App\Models\User::query()
                                ->select(['id', 'name', 'email', 'last_seen_at'])
                                ->orderByDesc('last_seen_at')
                                ->take(12)
                                ->get())
                            : collect();
                        $presenceUsers = $presenceUsers->map(function ($user) use ($onlineWindowMinutes) {
                            $isOnline = !empty($user->last_seen_at) && $user->last_seen_at->gte(now()->subMinutes($onlineWindowMinutes));
                            $user->is_online = $isOnline;
                            return $user;
                        });
                        $onlineUsersCount = $presenceUsers->where('is_online', true)->count();
                        $offlineUsersCount = $presenceUsers->where('is_online', false)->count();
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
                                <div class="dropdown-menu dropdown-menu-end p-0" style="width: 320px; max-height: 420px; overflow-y: auto;">
                                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                        <strong>User Online/Offline</strong>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success">Online {{ $onlineUsersCount }}</span>
                                            <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary">Offline {{ $offlineUsersCount }}</span>
                                        </div>
                                    </div>
                                    @forelse($presenceUsers as $presenceUser)
                                        <div class="dropdown-item py-2 border-bottom">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div class="min-w-0">
                                                    <div class="fw-semibold text-truncate">{{ $presenceUser->name }}</div>
                                                    <div class="small text-muted text-truncate">{{ $presenceUser->email }}</div>
                                                </div>
                                                <span class="badge rounded-pill {{ $presenceUser->is_online ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }}">
                                                    <span class="user-presence-dot {{ $presenceUser->is_online ? 'online' : 'offline' }}"></span>
                                                    {{ $presenceUser->is_online ? 'Online' : 'Offline' }}
                                                </span>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                {{ $presenceUser->is_online ? 'Đang hoạt động' : 'Truy cập gần nhất' }}:
                                                {{ optional($presenceUser->last_seen_at)->diffForHumans() ?? 'Chưa ghi nhận' }}
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-3 text-muted text-center">Chưa có dữ liệu trạng thái user</div>
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
        </div>
       
        

        {{-- Plugins thường dùng của Limitless (tùy gói bạn có) --}}
        {{-- <script src="{{ asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script> --}}
        {{-- <script> $('.form-input-styled').uniform(); </script> --}}

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



