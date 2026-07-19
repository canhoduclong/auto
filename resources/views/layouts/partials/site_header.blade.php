<style>
/* ========= Header: Account / Login ========= */
.hdr-login-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 18px;
    border-radius: 9px;
    border: 1px solid var(--theme-primary, #0f766e);
    background: transparent;
    color: var(--theme-primary, #0f766e);
    font-weight: 700;
    font-size: 13.5px;
    letter-spacing: 0.02em;
    text-decoration: none;
    transition: background 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    white-space: nowrap;
}
.hdr-login-btn:hover {
    background: var(--theme-primary, #0f766e);
    color: #fff !important;
    box-shadow: 0 6px 18px rgba(15,118,110,0.22);
}
.hdr-login-btn i { font-size: 16px; }

.hdr-account-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 4px 12px 4px 5px;
    border-radius: 9px;
    border: 1.5px solid rgba(15,118,110,0.35);
    background: rgba(15,118,110,0.06);
    color: #0f172a;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s ease;
    white-space: nowrap;
    max-width: 200px;
}
.hdr-account-btn:hover,
.hdr-account-btn.show {
    border-color: var(--theme-primary, #0f766e);
    background: rgba(15,118,110,0.12);
    color: #0f766e;
}
.hdr-account-btn::after { display: none; }
.hdr-account-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.9);
    box-shadow: 0 2px 8px rgba(15,118,110,0.18);
    flex-shrink: 0;
}
.hdr-account-name {
    max-width: 110px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.hdr-account-chevron {
    font-size: 11px;
    opacity: 0.65;
    transition: transform 0.2s ease;
}
.hdr-account-btn.show .hdr-account-chevron { transform: rotate(180deg); }

.hdr-account-menu {
    min-width: 230px;
    border-radius: 16px;
    padding: 0;
    border: 1px solid rgba(148,163,184,0.22);
    box-shadow: 0 16px 40px rgba(15,23,42,0.12);
    overflow: hidden;
    margin-top: 8px !important;
}
.hdr-account-menu__info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: linear-gradient(135deg, rgba(15,118,110,0.08), rgba(15,23,42,0.04));
}
.hdr-account-menu__avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.9);
    box-shadow: 0 4px 10px rgba(15,118,110,0.15);
    flex-shrink: 0;
}
.hdr-account-menu__name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.hdr-account-menu__email {
    font-size: 12px;
    color: #64748b;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.hdr-account-menu .dropdown-item {
    padding: 9px 16px;
    font-size: 13.5px;
    display: flex;
    align-items: center;
    gap: 9px;
    color: #334155;
    transition: background 0.15s, color 0.15s;
}
.hdr-account-menu .dropdown-item i {
    width: 18px;
    text-align: center;
    font-size: 15px;
    color: #64748b;
}
.hdr-account-menu .dropdown-item:hover {
    background: rgba(15,118,110,0.06);
    color: #0f766e;
}
.hdr-account-menu .dropdown-item:hover i { color: #0f766e; }
.hdr-account-logout { color: #ef4444 !important; }
.hdr-account-logout i { color: #ef4444 !important; }
.hdr-account-logout:hover { background: rgba(239,68,68,0.07) !important; color: #dc2626 !important; }

.hdr-notify-btn {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    border: 1px solid rgba(15,118,110,0.22);
    background: #fff;
    color: #0f766e;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.18s ease;
}
.hdr-notify-btn:hover,
.hdr-notify-btn.show {
    background: rgba(15,118,110,0.08);
    border-color: rgba(15,118,110,0.45);
    color: #0f766e;
}
.hdr-notify-btn i {
    font-size: 18px;
}
.hdr-notify-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 20px;
    height: 20px;
    border-radius: 999px;
    background: #dc2626;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    border: 2px solid #fff;
}
.hdr-notify-menu {
    width: 340px;
    max-width: calc(100vw - 24px);
    border-radius: 14px;
    border: 1px solid rgba(148,163,184,0.22);
    box-shadow: 0 16px 40px rgba(15,23,42,0.12);
    padding: 0;
    overflow: hidden;
}
.hdr-notify-header {
    padding: 10px 14px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #475569;
    background: #f8fafc;
}
.hdr-notify-list {
    max-height: 320px;
    overflow-y: auto;
}
.hdr-notify-item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 10px 14px;
    text-decoration: none;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.16s ease, transform 0.16s ease, border-color 0.16s ease;
}
.hdr-notify-item:hover {
    background: linear-gradient(90deg, rgba(15,118,110,0.09), rgba(15,118,110,0.03));
    color: #0f766e;
    border-bottom-color: rgba(15,118,110,0.12);
}
.hdr-notify-item:last-child {
    border-bottom: 0;
}
.hdr-notify-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.hdr-notify-content {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}
.hdr-notify-icon.warehouse {
    background: #fef3c7;
    color: #b45309;
}
.hdr-notify-icon.customer {
    background: #dcfce7;
    color: #15803d;
}
.hdr-notify-title {
    font-size: 12.5px;
    font-weight: 700;
    display: block;
    line-height: 1.25;
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.hdr-notify-meta {
    width: 100%;
    font-size: 12px;
    color: #64748b;
    line-height: 1.28;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.hdr-notify-empty {
    padding: 14px;
    color: #64748b;
    font-size: 13px;
}

.header__nav__widget__btn {
    gap: 10px;
}

.header__nav__widget__btn .js-cart-widget {
    margin-right: 0 !important;
}

.header__nav__widget__btn .js-cart-widget .btn {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    border-color: rgba(15,118,110,0.22);
    color: #0f766e;
    background: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.header__nav__widget__btn .js-cart-widget .btn:hover {
    background: rgba(15,118,110,0.08);
    border-color: rgba(15,118,110,0.45);
    color: #0f766e;
}

.header__nav__widget__btn .js-cart-widget .btn i {
    font-size: 18px;
}

.header__nav__widget__btn .dropdown {
    margin-right: 0 !important;
}
.dropdown-toggle::after {
    display: none !important;
}

@media (max-width: 991.98px) {
    .header__nav__widget__btn {
        gap: 8px;
    }

    .hdr-account-btn {
        padding-right: 8px;
        max-width: 150px;
    }

    .hdr-account-name {
        max-width: 72px;
    }
    
}
</style>

<div class="offcanvas-menu-overlay"></div>
<div class="offcanvas-menu-wrapper">
    <div class="offcanvas__widget">
        <a href="#"><i class="fa fa-cart-plus"></i></a>
        <a href="#" class="search-switch"><i class="fa fa-search"></i></a>
        <a href="#" class="primary-btn">{{ __('site.add') }}</a>
        @auth
            <div class="mt-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    @php
                        $avatar = Auth::user()->avatar ?? null;
                        $avatarUrl = $avatar ? asset($avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'U') . '&background=0f766e&color=fff&size=40&bold=true';
                    @endphp
                    <img src="{{ $avatarUrl }}" alt="avatar" class="rounded-circle" width="36" height="36">
                    <div>
                        <div class="fw-semibold">{{ Auth::user()->name }}</div>
                        <div class="text-muted small">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <ul class="list-unstyled mb-2">
                    @php
                        $offcanvasCanViewMonitoring = Auth::user()->isAdmin() || Auth::user()->hasPermission('orders.monitoring');
                        $offcanvasCanApproveTeamOrders = Auth::user()->hasRole('leader') || Auth::user()->hasRole('leader_sale') || Auth::user()->hasRole('sale_manager');
                        $offcanvasCanApproveDepartmentOrders = Auth::user()->hasRole('manager') || Auth::user()->hasRole('manager_sale') || Auth::user()->hasRole('director');
                        $offcanvasCanCreateDepartmentNotifications = Auth::user()->hasRole(['admin', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']);
                        $offcanvasCanManageAppointments = Auth::user()->isAdmin() || Auth::user()->isSalesFlowRole();
                        $offcanvasMyTasksRoute = Auth::user()->isSalesFlowRole() ? 'my-tasks' : 'tasks.my-tasks';
                        $offcanvasFinanceRequestRoute = null;
                        if ($offcanvasCanApproveDepartmentOrders || Auth::user()->isAdmin()) {
                            $offcanvasFinanceRequestRoute = 'manager.finance-requests.index';
                        } elseif ($offcanvasCanApproveTeamOrders) {
                            $offcanvasFinanceRequestRoute = 'leader.finance-requests.index';
                        }
                    @endphp
                    <li><a href="{{ route('pages.my_dashboard') }}" class="d-block py-1"><i class="bi bi-speedometer2 me-1"></i> My Dashboard</a></li>
                    @if(Auth::user()->isSalesFlowRole())
                        <li><a href="{{ route('pages.hoang_long_profile') }}" class="d-block py-1"><i class="bi bi-file-earmark-person me-1"></i> Hoàng Long TNT Profile</a></li>
                    @endif
                    @if($offcanvasCanCreateDepartmentNotifications)
                        <li><a href="{{ route('department-notifications.index', ['layout' => 'site']) }}" class="d-block py-1"><i class="bi bi-megaphone me-1"></i> Tạo thông báo</a></li>
                    @endif
                    <li><a href="{{ route('pages.my_profile') }}" class="d-block py-1"><i class="bi bi-person-circle me-1"></i> {{ __('site.profile') }}</a></li>
                    @if(Auth::user()->hasPermission('task.create') || Auth::user()->hasPermission('task.assign') || \App\Services\TaskMenuService::canAssignTasks(Auth::user()) || \App\Services\TaskMenuService::canCompleteTasks(Auth::user()))
                        <li><a href="{{ route($offcanvasMyTasksRoute) }}" class="d-block py-1"><i class="bi bi-list-task me-1"></i> Nhiệm vụ được giao</a></li>
                    @endif
                    @if(Auth::user()->isSalesFlowRole())
                        <li><a href="{{ $offcanvasCanViewMonitoring ? route('pages.my_orders.monitoring', ['tab' => 'drafts']) : route('pages.my_order_drafts') }}" class="d-block py-1"><i class="bi bi-file-earmark-text me-1"></i> Đơn nháp</a></li>
                    @endif
                    <li><a href="{{ $offcanvasCanViewMonitoring ? route('pages.my_orders.monitoring', ['tab' => 'my_orders']) : route('pages.my_orders') }}" class="d-block py-1"><i class="bi bi-bag-check me-1"></i> {{ __('site.my_orders') }}</a></li>
                    @if(Auth::user()->isSalesFlowRole())
                        <li><a href="{{ route('pages.my_products') }}" class="d-block py-1"><i class="bi bi-box-seam me-1"></i> Sản phẩm</a></li>
                    @endif
                    @if($offcanvasCanViewMonitoring)
                        <li><a href="{{ route('pages.my_orders.monitoring') }}" class="d-block py-1"><i class="bi bi-activity me-1"></i> Theo dõi đơn hàng</a></li>
                    @endif
                    @if($offcanvasCanApproveTeamOrders)
                        <li><a href="{{ route('pages.my_team_orders') }}" class="d-block py-1"><i class="bi bi-check-circle me-1"></i> Duyệt đơn của Team</a></li>
                    @endif
                    @if($offcanvasCanApproveDepartmentOrders)
                        <li><a href="{{ route('pages.all_team_orders') }}" class="d-block py-1"><i class="bi bi-check2-all me-1"></i> Duyệt đơn PKD</a></li>
                    @endif
                    @if($offcanvasFinanceRequestRoute)
                        <li><a href="{{ route($offcanvasFinanceRequestRoute) }}" class="d-block py-1"><i class="bi bi-file-earmark-text me-1"></i> Phiếu yêu cầu</a></li>
                    @endif
                    <li><a href="{{ route('pages.my_customer') }}" class="d-block py-1"><i class="bi bi-people me-1"></i> {{ __('site.my_customers') }}</a></li>
                    <li><a href="{{ $offcanvasCanViewMonitoring ? route('pages.my_orders.monitoring', ['tab' => 'schedules']) : route('my_customer.schedules.index') }}" class="d-block py-1"><i class="bi bi-calendar2-check me-1"></i> Lịch lên đơn</a></li>
                    @if(Auth::user()->isSalesFlowRole())
                        <li><a href="{{ route('pages.my_truck_stations') }}" class="d-block py-1"><i class="bi bi-truck me-1"></i> Danh sách nhà xe</a></li>
                    @endif
                    @if($offcanvasCanManageAppointments)
                        <li><a href="{{ route('pages.my_customer_appointments') }}" class="d-block py-1"><i class="bi bi-camera me-1"></i> Cuộc hẹn khách hàng</a></li>
                    @endif
                    <li><a href="{{ route('work-reports.index') }}" class="d-block py-1"><i class="bi bi-clipboard-data me-1"></i> Báo cáo công việc</a></li>
                    @if(Auth::user()->isSalesFlowRole())
                        <li><a href="{{ route('customer-tracking.index') }}" class="d-block py-1"><i class="bi bi-graph-up-arrow me-1"></i> Theo dõi khách hàng</a></li>
                    @endif
                    <li><a href="{{ url('/dashboard') }}" class="d-block py-1"><i class="bi bi-speedometer2 me-1"></i> {{ __('site.dashboard') }}</a></li>
                </ul>
                <form id="offcanvas-logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link text-danger p-0"><i class="bi bi-box-arrow-right me-1"></i> {{ __('site.logout') }}</button>
                </form>
            </div>
        @else
            <div class="mt-3">
                <a href="{{ route('login') }}" class="btn btn-primary w-100 mb-2"><i class="bi bi-person-fill me-1"></i> {{ __('site.login') }}</a>
            </div>
        @endauth
    </div>
    <div class="offcanvas__logo"> 
        <a href="{{ route('home') }}">
            @if(isset($settings['logo']) && $settings['logo']->value)
                @php
                    $media = App\Models\Media::find($settings['logo']->value);
                @endphp
                @if($media)
                    <img src="{{ asset('storage/' . $media->file_path) }}" alt="logo" height="50">
                @endif
            @else
                <h2>{{ $settings['brand_name']->value ?? __('site.logo_fallback') }}</h2>
            @endif
        </a>
    </div>
    <div id="mobile-menu-wrap"></div>
    <ul class="offcanvas__widget__add">
        <li><i class="fa fa-clock-o"></i> {{ __('site.weekday_hours') }}</li>
        <li><i class="fa fa-envelope-o"></i> email@gmail.com</li>
    </ul>
    <div class="offcanvas__phone__num">
        <i class="fa fa-phone"></i>
        <span>{{ $settings['HOTLINE']->value ?? '--' }}</span>
    </div>
    <div class="offcanvas__social">
        <a href="#"><i class="fa fa-facebook"></i></a>
        <a href="#"><i class="fa fa-twitter"></i></a>
        <a href="#"><i class="fa fa-google"></i></a>
        <a href="#"><i class="fa fa-instagram"></i></a>
    </div>
</div>
<header class="header ">
    <div class="header__top">
            <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <ul class="header__top__widget">
                        <li><i class="fa fa-clock-o"></i>{{ $settings['slogan']->value ?? __('site.slogan_fallback') }}</li>
                        <li><i class="fa fa-phone"></i>  {{ $settings['HOTLINE']->value ?? '--' }}</li>
                    </ul>
                </div>
                <div class="col-lg-5  d-flex justify-content-end align-items-center">
                    <ul class="header__top__widget me-3">
                        <li><a href="{{ route('locale.switch', 'vi') }}">{{ __('common.language.vi') }}</a></li>
                        <li><a href="{{ route('locale.switch', 'en') }}">{{ __('common.language.en') }}</a></li>
                    </ul>
                    <ul class="header__top__widget">
                        <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                        <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fa fa-google"></i></a></li>
                        <li><a href="#"><i class="fa fa-instagram"></i></a></li>
                    </ul> 
                   
                
                </div>
            </div>
        </div>
    </div> 
     <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="header__logo"> 
                    <a href="{{ route('home') }}">
                        @if(isset($settings['logo']) && $settings['logo']->value)
                            @php
                                $media = App\Models\Media::find($settings['logo']->value);
                            @endphp
                            @if($media)
                                <img src="{{ asset('storage/' . $media->file_path) }}" alt="logo" height="50">
                            @endif
                        @else
                            <h2>{{ $settings['brand_name']->value ?? __('site.logo_fallback') }}</h2>
                        @endif
                    </a>
                </div>
            </div>
            <div class="col-lg-9 d-flex justify-content-end align-items-center">
                <div class="header__nav ">
                    <nav class="header__menu "> 
                        <ul class="mb-0 ml-0 pb-0 pl-0">
                            <li><a href="{{ route('home') }}" class="nav-link px-2 link-secondary">{{ __('site.home') }}</a></li>
                            <li><a href="{{ route('pages.about') }}" class="nav-link px-2 link-dark">{{ __('site.about') }}</a></li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('pages.product_list') }}" >
                                    {{ __('site.products') }}
                                </a> 
                            </li>
                            <li><a href="{{ route('posts.list') }}" class="nav-link px-2 link-dark">{{ __('site.posts') }}</a></li>
                            <li><a href="{{ route('pages.contact') }}" class="nav-link px-2 link-dark">{{ __('site.contact') }}</a></li>
                        </ul> 
 
                    </nav>
                    <div class="header__nav__widget">
                        <div class="header__nav__widget__btn  d-flex justify-content-end align-items-center">

                            <x-cart-widget :cartCount="count(session('cart', []))" />
                            @auth
                                @php
                                    $user = Auth::user();
                                    $hdrSalesNotifications = $user ? getUserOrderNotifications($user, 7) : collect();
                                    $hdrSalesNotificationCount = $hdrSalesNotifications->whereNull('read_at')->count();
                                @endphp

                                @if(Auth::user()->isSalesFlowRole())
                                    <div class="dropdown">
                                        <button type="button"
                                            class="hdr-notify-btn dropdown-toggle"
                                            id="hdrNotifyBtn"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            aria-label="Thông báo">
                                            <i class="bi bi-bell"></i>
                                            @if($hdrSalesNotificationCount > 0)
                                                <span class="hdr-notify-badge">{{ $hdrSalesNotificationCount > 99 ? '99+' : $hdrSalesNotificationCount }}</span>
                                            @endif
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end hdr-notify-menu" aria-labelledby="hdrNotifyBtn">
                                            <div class="hdr-notify-header">Thông báo</div>
                                            <div class="hdr-notify-list">
                                                @forelse($hdrSalesNotifications as $notify)
                                                    <a href="{{ $notify['link'] }}" class="hdr-notify-item d-flex align-items-center {{ empty($notify['read_at']) ? 'bg-light' : '' }}">
                                                        <span class="hdr-notify-icon {{ $notify['type'] }}">
                                                            @php
                                                                $icon = match($notify['type']) {
                                                                    'new_order' => 'bi-receipt-cutoff',
                                                                    'warehouse' => 'bi-box-seam',
                                                                    'sale' => 'bi-person-badge',
                                                                    'shipper' => 'bi-truck',
                                                                    default => 'bi-info-circle',
                                                                };
                                                            @endphp
                                                            <i class="bi {{ $icon }}"></i>
                                                        </span>
                                                        <span class="hdr-notify-content d-flex flex-column align-items-start flex-grow-1">
                                                            <span class="hdr-notify-title">{!! $notify['title'] !!}</span>
                                                            <span class="hdr-notify-meta">{{ $notify['meta'] }}<span class="ms-2 text-muted small">{{ $notify['time'] ?? '' }}</span></span>
                                                            @if(!empty($notify['details']))
                                                                <span class="small text-start mt-1">
                                                                    @foreach($notify['details'] as $detail)
                                                                        <span class="d-block">{{ $detail['name'] }}: {{ rtrim(rtrim(number_format($detail['quantity'], 2, ',', '.'), '0'), ',') }} × {{ number_format($detail['price']) }}đ = {{ number_format($detail['line_total']) }}đ</span>
                                                                    @endforeach
                                                                    <span class="d-block fw-semibold">Tổng tiền: {{ number_format($notify['total'] ?? 0) }}đ</span>
                                                                    <span class="d-block">Ghi chú: {{ $notify['note'] ?: 'Không có' }}</span>
                                                                </span>
                                                            @endif
                                                        </span>
                                                    </a>
                                                @empty
                                                    <div class="hdr-notify-empty">Hiện chưa có thông báo mới.</div>
                                                @endforelse
                                            </div>
                                            <div class="text-center py-2 border-top">
                                                <a href="{{ route('pages.my_dashboard.notifications') }}" class="btn btn-link p-0 small">Xem tất cả thông báo</a>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @php
                                    $hdrAvatarUrl = Auth::user()->avatar
                                        ? asset(Auth::user()->avatar)
                                        : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'U') . '&background=0f766e&color=fff&size=80&bold=true';
                                @endphp
                                <div class="dropdown hdr-account-dropdown">
                                    <button type="button"
                                        class="hdr-account-btn dropdown-toggle"
                                        id="hdrAccountBtn"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <img src="{{ $hdrAvatarUrl }}" alt="avatar" class="hdr-account-avatar">
                                        <span class="hdr-account-name">{{ Str::limit(Auth::user()->name, 14) }}</span>
                                        <i class="bi bi-chevron-down hdr-account-chevron"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end hdr-account-menu" aria-labelledby="hdrAccountBtn">
                                        <div class="hdr-account-menu__info">
                                            <img src="{{ $hdrAvatarUrl }}" alt="avatar" class="hdr-account-menu__avatar">
                                            <div>
                                                <div class="hdr-account-menu__name">{{ Auth::user()->name }}</div>
                                                <div class="hdr-account-menu__email">{{ Auth::user()->email }}</div>
                                            </div>
                                        </div>
                                        <div class="dropdown-divider my-0"></div>
                                        <a class="dropdown-item" href="{{ route('pages.my_dashboard') }}">
                                            <i class="bi bi-speedometer2"></i> My Dashboard
                                        </a>
                                        @if(Auth::user()->isSalesFlowRole())
                                            <a class="dropdown-item" href="{{ route('pages.hoang_long_profile') }}">
                                                <i class="bi bi-file-earmark-person"></i> Hoàng Long TNT Profile
                                            </a>
                                        @endif
                                        @if(Auth::user()->hasRole(['admin', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']))
                                            <a class="dropdown-item" href="{{ route('department-notifications.index', ['layout' => 'site']) }}">
                                                <i class="bi bi-megaphone"></i> Tạo thông báo
                                            </a>
                                        @endif
                                        <a class="dropdown-item" href="{{ route('pages.my_profile') }}">
                                            <i class="bi bi-person-circle"></i> {{ __('site.profile') }}
                                        </a>
                                        @if(Auth::user()->hasPermission('task.create') || Auth::user()->hasPermission('task.assign') || \App\Services\TaskMenuService::canAssignTasks(Auth::user()) || \App\Services\TaskMenuService::canCompleteTasks(Auth::user()))
                                            @php
                                                $headerMyTasksRoute = Auth::user()->isSalesFlowRole() ? 'my-tasks' : 'tasks.my-tasks';
                                            @endphp
                                            <div class="dropdown-divider my-0"></div>
                                            <div class="px-3 py-2 text-muted small text-uppercase fw-semibold">Quản lý công việc</div>
                                            <a class="dropdown-item" href="{{ route($headerMyTasksRoute) }}">
                                                <i class="bi bi-list-task"></i> Nhiệm vụ
                                            </a>
                                            @if(Auth::user()->hasPermission('task.create') || Auth::user()->hasPermission('task.assign') || \App\Services\TaskMenuService::canAssignTasks(Auth::user()))
                                               
                                                <a class="dropdown-item" href="{{ route('tasks.assigned') }}">
                                                    <i class="bi bi-kanban"></i> Giao việc
                                                </a>
                                            @endif
                                        @endif
                                        @if(Auth::user()->isSalesFlowRole())
                                        <a class="dropdown-item" href="{{ (Auth::user()->isAdmin() || Auth::user()->hasPermission('orders.monitoring')) ? route('pages.my_orders.monitoring', ['tab' => 'drafts']) : route('pages.my_order_drafts') }}">
                                            <i class="bi bi-file-earmark-text"></i> Đơn nháp
                                        </a>
                                        <a class="dropdown-item" href="{{ (Auth::user()->isAdmin() || Auth::user()->hasPermission('orders.monitoring')) ? route('pages.my_orders.monitoring', ['tab' => 'my_orders']) : route('pages.my_orders') }}">
                                            <i class="bi bi-bag-check"></i> {{ __('site.my_orders') }}
                                        </a>
                                        <a class="dropdown-item" href="{{ route('pages.my_products') }}">
                                            <i class="bi bi-box-seam"></i> Sản phẩm
                                        </a>
                                        @endif
                                        @php
                                            $canViewMonitoring = Auth::user()->isAdmin() || Auth::user()->hasPermission('orders.monitoring');
                                        @endphp
                                        @if($canViewMonitoring)
                                            <a class="dropdown-item" href="{{ route('pages.my_orders.monitoring') }}">
                                                <i class="bi bi-activity"></i> Theo dõi đơn hàng
                                            </a>
                                        @endif
                                        @if(Auth::user()->isSalesFlowRole())
                                        <a class="dropdown-item" href="{{ route('pages.my_customer') }}">
                                            <i class="bi bi-people"></i> {{ __('site.my_customers') }}
                                        </a>
                                        <a class="dropdown-item" href="{{ (Auth::user()->isAdmin() || Auth::user()->hasPermission('orders.monitoring')) ? route('pages.my_orders.monitoring', ['tab' => 'schedules']) : route('my_customer.schedules.index') }}">
                                            <i class="bi bi-calendar2-check"></i> Lịch lên đơn
                                        </a>
                                        @endif
                                        @if(Auth::user()->isSalesFlowRole())
                                            <a class="dropdown-item" href="{{ route('pages.my_truck_stations') }}">
                                                <i class="bi bi-truck"></i> Danh sách nhà xe
                                            </a>
                                        @endif
                                        <a class="dropdown-item" href="{{ route('work-reports.index') }}">
                                            <i class="bi bi-clipboard-data"></i> Báo cáo công việc
                                        </a>
                                        @if(Auth::user()->isSalesFlowRole())
                                            <a class="dropdown-item" href="{{ route('customer-tracking.index') }}">
                                                <i class="bi bi-graph-up-arrow"></i> Theo dõi khách hàng
                                            </a>
                                        @endif
                                        @php
                                            $isSalesFlowRole = Auth::user()->isSalesFlowRole();
                                            $canManageCustomerAppointments = $isSalesFlowRole || Auth::user()->isAdmin();
                                            $canAccessSalesDailyPages = Auth::user()->canAccessSalesDailyFeatures();
                                            $canApproveTeamOrders = Auth::user()->hasRole('leader') || Auth::user()->hasRole('leader_sale') || Auth::user()->hasRole('sale_manager');
                                            $canApproveDepartmentOrders = Auth::user()->hasRole('manager') || Auth::user()->hasRole('manager_sale') || Auth::user()->hasRole('director');
                                            $financeRequestRoute = null;
                                            if ($canApproveDepartmentOrders || Auth::user()->isAdmin()) {
                                                $financeRequestRoute = 'manager.finance-requests.index';
                                            } elseif ($canApproveTeamOrders) {
                                                $financeRequestRoute = 'leader.finance-requests.index';
                                            }
                                        @endphp
                                        @if($canAccessSalesDailyPages)
                                            <a class="dropdown-item" href="{{ route('pages.my_orders.daily_prices') }}">
                                                <i class="bi bi-tags"></i> Bảng giá sản phẩm
                                            </a>
                                            <a class="dropdown-item" href="{{ route('pages.my_orders.daily_inventories') }}">
                                                <i class="bi bi-boxes"></i> Tồn kho hôm nay
                                            </a>
                                        @endif
                                        @if($canManageCustomerAppointments)
                                            <a class="dropdown-item" href="{{ route('pages.my_customer_appointments') }}">
                                                <i class="bi bi-camera"></i> Cuộc hẹn khách hàng
                                            </a>
                                        @endif
                                        @if($financeRequestRoute)
                                            <a class="dropdown-item" href="{{ route($financeRequestRoute) }}">
                                                <i class="bi bi-file-earmark-text"></i> Phiếu yêu cầu
                                            </a>
                                        @endif
                                        @if(!$isSalesFlowRole)
                                            <a class="dropdown-item" href="{{ url('/dashboard') }}">
                                                <i class="bi bi-speedometer2"></i> {{ __('site.dashboard') }}
                                            </a>
                                        @endif
                                        @include('layouts.partials.role_switcher', ['roleSwitcherVariant' => 'items'])
                                        @if($canApproveTeamOrders)
                                            <div class="dropdown-divider my-0"></div>
                                            <a class="dropdown-item" href="{{ route('pages.my_team_orders') }}">
                                                <i class="bi bi-check-circle"></i> Duyệt đơn của Team
                                            </a>
                                        @endif
                                        @if($canApproveDepartmentOrders)
                                            <div class="dropdown-divider my-0"></div>
                                            <a class="dropdown-item" href="{{ route('pages.all_team_orders') }}">
                                                <i class="bi bi-check-circle"></i> Duyệt Đơn PKD
                                            </a>
                                        @endif
                                        <div class="dropdown-divider my-0"></div>
                                        <a class="dropdown-item hdr-account-logout"
                                            href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('hdr-logout-form').submit();">
                                            <i class="bi bi-box-arrow-right"></i> {{ __('site.logout') }}
                                        </a>
                                        <form id="hdr-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('login') }}" class="hdr-login-btn">
                                    <i class="bi bi-person-fill"></i>
                                    <span>{{ __('site.login') }}</span>
                                </a>
                            @endauth

                             
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="canvas__open"><i class="fa fa-bars"></i></div>
    </div> 
</header>

 
