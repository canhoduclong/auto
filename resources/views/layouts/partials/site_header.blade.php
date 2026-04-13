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
    gap: 8px;
    padding: 4px 12px 4px 4px;
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
                        $offcanvasCanApproveTeamOrders = Auth::user()->hasRole('leader')
                            || Auth::user()->hasRole('leader_sale')
                            || Auth::user()->hasRole('manager')
                            || Auth::user()->hasRole('manager_sale');
                        $offcanvasCanApproveDepartmentOrders = Auth::user()->hasRole('manager')
                            || Auth::user()->hasRole('manager_sale');
                        $offcanvasCanManageAppointments = Auth::user()->isAdmin() || Auth::user()->isSalesFlowRole();
                    @endphp
                    <li><a href="{{ route('pages.my_dashboard') }}" class="d-block py-1"><i class="bi bi-person-circle me-1"></i> {{ __('site.profile') }}</a></li>
                    <li><a href="{{ route('pages.my_orders') }}" class="d-block py-1"><i class="bi bi-bag-check me-1"></i> {{ __('site.my_orders') }}</a></li>
                    @if($offcanvasCanViewMonitoring)
                        <li><a href="{{ route('pages.my_orders.monitoring') }}" class="d-block py-1"><i class="bi bi-activity me-1"></i> Theo dõi đơn hàng</a></li>
                    @endif
                    @if($offcanvasCanApproveTeamOrders)
                        <li><a href="{{ route('pages.my_team_orders') }}" class="d-block py-1"><i class="bi bi-check-circle me-1"></i> Duyệt đơn của Team</a></li>
                    @endif
                    @if($offcanvasCanApproveDepartmentOrders)
                        <li><a href="{{ route('pages.all_team_orders') }}" class="d-block py-1"><i class="bi bi-check2-all me-1"></i> Duyệt đơn PKD</a></li>
                    @endif
                    <li><a href="{{ route('pages.my_customer') }}" class="d-block py-1"><i class="bi bi-people me-1"></i> {{ __('site.my_customers') }}</a></li>
                    @if($offcanvasCanManageAppointments)
                        <li><a href="{{ route('pages.my_customer_appointments') }}" class="d-block py-1"><i class="bi bi-camera me-1"></i> Cuộc hẹn khách hàng</a></li>
                    @endif
                    <li><a href="{{ route('work-reports.index') }}" class="d-block py-1"><i class="bi bi-clipboard-data me-1"></i> Báo cáo công việc</a></li>
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
        <span>0909 990 909</span>
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
                        <li><i class="fa fa-phone"></i>  {{ $settings['HOTLINE']->value ?? '0909 990 909' }}</li>
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
            <div class="col-lg-4">
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
            <div class="col-lg-8 d-flex justify-content-end align-items-center">
                <div class="header__nav ">
                    <nav class="header__menu "> 
                        <ul class="mb-0 ml-0 pb-0 pl-0">
                            <li><a href="{{ route('home') }}" class="nav-link px-2 link-secondary">{{ __('site.home') }}</a></li>
                            <li><a href="{{ route('pages.about') }}" class="nav-link px-2 link-dark">{{ __('site.about') }}</a></li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('pages.products_by_category') }}" >
                                    {{ __('site.products') }}
                                </a> 
                            </li>
                            <li><a href="{{ route('posts.list') }}" class="nav-link px-2 link-dark">{{ __('site.posts') }}</a></li>
                            <li><a href="{{ route('pages.contact') }}" class="nav-link px-2 link-dark">{{ __('site.contact') }}</a></li>
                        </ul> 
 
                    </nav>
                    <div class="header__nav__widget">
                        <div class="header__nav__widget__btn  d-flex justify-content-end align-items-center">

                            <x-cart-widget :cartCount="count(session('cart', []))" class="me-3" />
                            @auth
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
                                            <i class="bi bi-person-circle"></i> {{ __('site.profile') }}
                                        </a>
                                        <a class="dropdown-item" href="{{ route('pages.my_orders') }}">
                                            <i class="bi bi-bag-check"></i> {{ __('site.my_orders') }}
                                        </a>
                                        @php
                                            $canViewMonitoring = Auth::user()->isAdmin() || Auth::user()->hasPermission('orders.monitoring');
                                        @endphp
                                        @if($canViewMonitoring)
                                            <a class="dropdown-item" href="{{ route('pages.my_orders.monitoring') }}">
                                                <i class="bi bi-activity"></i> Theo dõi đơn hàng
                                            </a>
                                        @endif
                                        <a class="dropdown-item" href="{{ route('pages.my_customer') }}">
                                            <i class="bi bi-people"></i> {{ __('site.my_customers') }}
                                        </a>
                                        <a class="dropdown-item" href="{{ route('work-reports.index') }}">
                                            <i class="bi bi-clipboard-data"></i> Báo cáo công việc
                                        </a>
                                        @php
                                            $isSalesFlowRole = Auth::user()->isSalesFlowRole();
                                            $canManageCustomerAppointments = $isSalesFlowRole || Auth::user()->isAdmin();
                                            $canAccessSalesDailyPages = Auth::user()->canAccessSalesDailyFeatures();
                                            $canApproveTeamOrders = Auth::user()->hasRole('leader')
                                                || Auth::user()->hasRole('leader_sale')
                                                || Auth::user()->hasRole('manager')
                                                || Auth::user()->hasRole('manager_sale');
                                            $canApproveDepartmentOrders = Auth::user()->hasRole('manager')
                                                || Auth::user()->hasRole('manager_sale');
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
                                        @if(!$isSalesFlowRole)
                                            <a class="dropdown-item" href="{{ url('/dashboard') }}">
                                                <i class="bi bi-speedometer2"></i> {{ __('site.dashboard') }}
                                            </a>
                                        @endif
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

 