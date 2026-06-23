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
    @else
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-badge"></i> Chuyển vai trò
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
