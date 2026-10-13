@php
    $roleSwitcherUser = auth()->user();
    $roleSwitcherWorkspaces = $roleSwitcherUser
        ? app(\App\Services\UserWorkspaceService::class)->availableForUser($roleSwitcherUser)
        : [];
    $roleSwitcherMode = $roleSwitcherVariant ?? 'dropdown';
    $roleSwitcherActiveWorkspace = strtolower((string) session('active_workspace', $roleSwitcherUser?->default_workspace ?? ''));
    $roleSwitcherActiveRole = strtolower((string) session('active_role', $roleSwitcherUser?->defaultRole?->name ?? ''));
    $roleSwitcherPresentation = [
        'website_admin' => ['Quản trị', 'bi-shield-lock'],
        'website_ceo' => ['CEO', 'bi-briefcase'],
        'website_accounting' => ['Kế toán', 'bi-cash-stack'],
        'website_warehouse' => ['Kho', 'bi-boxes'],
        'website_package' => ['Đóng hàng', 'bi-box-seam'],
        'website_shipper' => ['Shipper', 'bi-truck'],
        'website_sales' => ['Kinh doanh', 'bi-graph-up-arrow'],
        'website_procurement' => ['Thu mua', 'bi-basket2-fill'],
    ];
@endphp

@if(count($roleSwitcherWorkspaces) > 1)
    @if($roleSwitcherMode === 'mobile' || $roleSwitcherMode === 'dropdown')
        <style>
            .mobile-role-switcher{display:none}
            @media(max-width:991.98px){
                .mobile-role-switcher{display:block;position:relative;color:#134e4a;min-width:0}
                .mobile-role-switcher summary{list-style:none;cursor:pointer;border:1px solid #b8d7d1;border-radius:10px;padding:10px 12px;background:#edf7f4;font-size:14px;font-weight:600;line-height:1.4;min-height:44px;white-space:nowrap}
                .mobile-role-switcher summary::-webkit-details-marker{display:none}
                .mobile-role-options{position:absolute;right:0;top:calc(100% + 6px);width:min(280px,calc(100vw - 32px));max-height:55vh;overflow:auto;background:#fff;border:1px solid #d9e7e4;border-radius:12px;padding:8px;box-shadow:0 10px 28px #0002;z-index:1060}
                .mobile-role-options form{margin:0}
                .mobile-role-options button{display:block;width:100%;text-align:left;border:0;border-radius:8px;background:white;padding:13px 12px;min-height:48px;font-size:16px;color:#134e4a;line-height:1.4;white-space:normal}
                .mobile-role-options button[aria-current=true]{background:#dff1ec;font-weight:700}
                .shared-role-desktop{display:none!important}
                .offcanvas-menu-wrapper .mobile-role-switcher{margin-bottom:16px}
                .offcanvas-menu-wrapper .mobile-role-options{position:static;width:100%;margin-top:8px;box-shadow:none}
            }
        </style>
        @php
            $mobileRoleLabel = 'Chuyển vai trò';
            foreach($roleSwitcherWorkspaces as $workspace) {
                if ($roleSwitcherActiveWorkspace === $workspace['key'] || ($roleSwitcherActiveWorkspace === '' && in_array($roleSwitcherActiveRole,$workspace['matched_roles'],true))) {
                    $mobileRoleLabel = $roleSwitcherPresentation[$workspace['key']][0] ?? $workspace['label']; break;
                }
            }
        @endphp
        <details class="mobile-role-switcher"><summary aria-label="Chuyển vai trò">{{ $mobileRoleLabel }} ▾</summary><div class="mobile-role-options">
            @foreach($roleSwitcherWorkspaces as $roleSwitcherWorkspace)
                @php
                    $roleSwitcherLabel = $roleSwitcherPresentation[$roleSwitcherWorkspace['key']][0] ?? $roleSwitcherWorkspace['label'];
                    $roleSwitcherIsActive = $roleSwitcherActiveWorkspace === $roleSwitcherWorkspace['key'] || ($roleSwitcherActiveWorkspace === '' && in_array($roleSwitcherActiveRole, $roleSwitcherWorkspace['matched_roles'], true));
                @endphp
                <form action="{{ route('role.switch', $roleSwitcherWorkspace['active_role']) }}" method="POST">@csrf<button type="submit" aria-current="{{ $roleSwitcherIsActive ? 'true' : 'false' }}">{{ $roleSwitcherIsActive ? '✓ ' : '' }}{{ $roleSwitcherLabel }}</button></form>
            @endforeach
        </div></details>
    @endif
    @if($roleSwitcherMode === 'items')
        <div class="dropdown-divider my-0"></div>
        <h6 class="dropdown-header">Chuyển vai trò</h6>
        @foreach($roleSwitcherWorkspaces as $roleSwitcherWorkspace)
            @php
                [$roleSwitcherLabel, $roleSwitcherIcon] = $roleSwitcherPresentation[$roleSwitcherWorkspace['key']]
                    ?? [$roleSwitcherWorkspace['label'], 'bi-person-badge'];
                $roleSwitcherIsActive = $roleSwitcherActiveWorkspace === $roleSwitcherWorkspace['key']
                    || ($roleSwitcherActiveWorkspace === '' && in_array($roleSwitcherActiveRole, $roleSwitcherWorkspace['matched_roles'], true));
            @endphp
            <form action="{{ route('role.switch', $roleSwitcherWorkspace['active_role']) }}" method="POST">
                @csrf
                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 {{ $roleSwitcherIsActive ? 'active' : '' }}">
                    <i class="bi {{ $roleSwitcherIsActive ? 'bi-check-circle-fill' : $roleSwitcherIcon }}"></i>
                    <span>{{ $roleSwitcherLabel }}</span>
                </button>
            </form>
        @endforeach
    @elseif($roleSwitcherMode !== 'mobile')
        <div class="dropdown shared-role-desktop">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-badge"></i><span class="role-switcher-label"> Chuyển vai trò</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><h6 class="dropdown-header">Chọn khu vực làm việc</h6></li>
                <li><hr class="dropdown-divider"></li>
                @foreach($roleSwitcherWorkspaces as $roleSwitcherWorkspace)
                    @php
                        [$roleSwitcherLabel, $roleSwitcherIcon] = $roleSwitcherPresentation[$roleSwitcherWorkspace['key']]
                            ?? [$roleSwitcherWorkspace['label'], 'bi-person-badge'];
                        $roleSwitcherIsActive = $roleSwitcherActiveWorkspace === $roleSwitcherWorkspace['key']
                            || ($roleSwitcherActiveWorkspace === '' && in_array($roleSwitcherActiveRole, $roleSwitcherWorkspace['matched_roles'], true));
                    @endphp
                    <li>
                        <form action="{{ route('role.switch', $roleSwitcherWorkspace['active_role']) }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 {{ $roleSwitcherIsActive ? 'active' : '' }}">
                                <i class="bi {{ $roleSwitcherIsActive ? 'bi-check-circle-fill' : $roleSwitcherIcon }}"></i>
                                <span>{{ $roleSwitcherLabel }}</span>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
