@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Sửa User</h2>

    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Tên</label>
            <input type="text" name="name" class="form-control" value="{{ old('name',$user->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email',$user->email) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Tên Zalo</label>
            <input type="text" name="zalo_name" class="form-control" value="{{ old('zalo_name', $user->zalo_name) }}" placeholder="Ví dụ: Ba Sơn Hoàng Long Tnt">
            <small class="text-muted">Tên phải khớp với tên người gửi trong nội dung copy từ Zalo.</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Mật khẩu (để trống nếu không đổi)</label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Xác nhận mật khẩu</label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>


        <div class="mb-4">
            <label class="form-label">Ảnh đại diện</label>
            <div class="card p-3 d-flex flex-row align-items-center" style="max-width: 400px;">
                <div class="me-3">
                    <img id="avatarPreview" src="{{ $user->avatar ? asset($user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'User') . '&background=0F172A&color=F8FAFC&size=150' }}" alt="Avatar" class="rounded-circle border" style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <div class="flex-grow-1">
                    <input type="file" name="avatar" id="avatar" class="form-control mb-2" accept="image/*">
                    <small class="text-muted">Ảnh vuông, tối đa 2MB.</small>
                    @error('avatar') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Vai trò</label>
            <div>
                @foreach($roles as $role)
                    <label>
                        <input
                            type="checkbox"
                            name="roles[]"
                            value="{{ $role->id }}"
                            class="js-role-checkbox"
                            data-role-name="{{ strtolower($role->name) }}"
                        {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                        {{ $role->name }}
                    </label><br>
                @endforeach
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Layout website mặc định</label>
            @php
                $selectedWorkspace = old('default_workspace', $user->default_workspace);
                $selectedMobileRoleId = old('default_mobile_role_id', $user->default_role_id);
                $layoutCatalog = collect(config('workspaces.catalog', []));
                $roleLayoutPayload = $roles->mapWithKeys(function ($role) use ($layoutCatalog) {
                    $webLayout = $layoutCatalog->get($role->layout_web_slug, []);
                    $mobileLayout = $layoutCatalog->get($role->layout_mobile_slug, []);

                    return [
                        (string) $role->id => [
                            'id' => (int) $role->id,
                            'name' => (string) $role->name,
                            'web_slug' => (string) ($role->layout_web_slug ?? ''),
                            'web_label' => (string) ($role->layout_web_name ?: ($webLayout['label'] ?? $role->layout_web_slug ?? '')),
                            'web_route' => (string) ($webLayout['route'] ?? ''),
                            'web_description' => (string) ($webLayout['description'] ?? ''),
                            'mobile_slug' => (string) ($role->layout_mobile_slug ?? ''),
                            'mobile_label' => (string) ($role->layout_mobile_name ?: ($mobileLayout['label'] ?? $role->layout_mobile_slug ?? '')),
                            'mobile_route' => (string) ($mobileLayout['route'] ?? ''),
                            'mobile_description' => (string) ($mobileLayout['description'] ?? ''),
                        ],
                    ];
                });
            @endphp

            <div id="default-workspace-options"></div>

            <small class="text-muted">Danh sách này lấy theo các vai trò đang được tick ở trên.</small>
            @error('default_workspace') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Layout Mobile mặc định</label>
            <div id="default-mobile-layout-options"></div>
            <small class="text-muted">Danh sách Mobile layout lấy theo các vai trò đang được tick ở trên.</small>
            @error('default_mobile_role_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Layout theo vai trò</label>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Website layout</th>
                            <th>My_app mobile layout</th>
                        </tr>
                    </thead>
                    <tbody id="role-layout-summary"></tbody>
                </table>
            </div>
            <small class="text-muted">Mỗi role cần có đúng 1 website layout và 1 my_app layout. Nếu thiếu, cập nhật tại trang sửa role.</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Team</label>
            <select name="team_id" class="form-control">
                <option value="">-- --- team --</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}" {{ (string) old('team_id', $user->team_id) === (string) $team->id ? 'selected' : '' }}>
                        {{ $team->name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Leader/Manager sẽ xem đơn theo team được gán.</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Kho duoc assign</label>
            <select name="warehouse_id" class="form-control">
                <option value="">-- Chua gan kho --</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ (string) old('warehouse_id', $user->warehouse_id) === (string) $warehouse->id ? 'selected' : '' }}>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">User role warehouse se chi thao tac tren kho duoc gan.</small>
        </div>

        {{-- Khối / Phòng --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Khối</label>
                <select name="block_id" id="block_id_edit" class="form-control">
                    <option value="">-- Chọn khối --</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ (string) old('block_id', $user->block_id) === (string) $block->id ? 'selected' : '' }}>{{ $block->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phòng / Ban</label>
                <select name="department_id" id="dept_id_edit" class="form-control">
                    <option value="">-- Chọn phòng --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}"
                            data-block="{{ $dept->block_id }}"
                            {{ (string) old('department_id', $user->department_id) === (string) $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Hủy</a>

    </form>

    <script>
        // Hiển thị preview ảnh khi chọn file mới
        document.addEventListener('DOMContentLoaded', function () {
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('avatarPreview');
            if (avatarInput && avatarPreview) {
                avatarInput.addEventListener('change', function (event) {
                    const [file] = event.target.files || [];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (typeof e.target.result === 'string') {
                            avatarPreview.src = e.target.result;
                        }
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
    <script>
    // Cascade: filter departments by selected block
    (function() {
        const blockSel = document.getElementById('block_id_edit');
        const deptSel  = document.getElementById('dept_id_edit');
        const allOpts  = Array.from(deptSel.options);
        function filterDepts() {
            const blockId = blockSel.value;
            Array.from(deptSel.options).forEach(opt => {
                if (!opt.value) return;
                opt.hidden = blockId && opt.dataset.block !== blockId;
            });
            if (blockId && deptSel.value && deptSel.options[deptSel.selectedIndex]?.dataset?.block !== blockId) {
                deptSel.value = '';
            }
        }
        blockSel.addEventListener('change', filterDepts);
        filterDepts();
    })();
    </script>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleLayouts = @json($roleLayoutPayload);
    const initialSelectedWorkspace = @json($selectedWorkspace);
    const initialSelectedMobileRoleId = @json((string) $selectedMobileRoleId);
    const savedWorkspace = @json($user->default_workspace);
    const savedMobileRoleId = @json((string) $user->default_role_id);
    const checkboxes = Array.from(document.querySelectorAll('.js-role-checkbox'));
    const workspaceContainer = document.getElementById('default-workspace-options');
    const mobileLayoutContainer = document.getElementById('default-mobile-layout-options');
    const summaryBody = document.getElementById('role-layout-summary');

    function checkedRoleLayouts() {
        return checkboxes
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => roleLayouts[checkbox.value])
            .filter(Boolean);
    }

    function renderDefaultWorkspaceOptions() {
        const roles = checkedRoleLayouts();
        const workspaces = new Map();
        const currentSelection = document.querySelector('input[name="default_workspace"]:checked')?.value;
        const selectedWorkspace = currentSelection !== undefined ? currentSelection : initialSelectedWorkspace;

        roles.forEach((role) => {
            if (!role.web_slug) return;
            if (!workspaces.has(role.web_slug)) {
                workspaces.set(role.web_slug, {
                    slug: role.web_slug,
                    label: role.web_label || role.web_slug,
                    route: role.web_route,
                    description: role.web_description,
                    roles: [],
                });
            }
            workspaces.get(role.web_slug).roles.push(role.name);
        });

        const selectedStillAvailable = selectedWorkspace && workspaces.has(selectedWorkspace);
        const noneChecked = !selectedWorkspace || !selectedStillAvailable;
        let html = `
            <div class="mb-2">
                <label class="d-block border rounded p-2">
                    <input type="radio" name="default_workspace" value="" ${noneChecked ? 'checked' : ''}>
                    <span class="ms-1">Không đặt mặc định (hệ thống sẽ hỏi chọn khi đăng nhập nếu có nhiều layout)</span>
                </label>
            </div>
        `;

        if (workspaces.size === 0) {
            html += '<div class="alert alert-warning mb-0">Các role đang chọn chưa có layout website hợp lệ.</div>';
            workspaceContainer.innerHTML = html;
            return;
        }

        workspaces.forEach((workspace) => {
            const checked = selectedWorkspace === workspace.slug ? 'checked' : '';
            const badge = savedWorkspace === workspace.slug ? '<span class="badge bg-success ms-2">Đang là mặc định</span>' : '';
            const description = workspace.description ? `<span class="d-block text-muted small mt-1">${workspace.description}</span>` : '';
            const rolesText = workspace.roles.length ? `<span class="d-block text-muted small">Roles: ${workspace.roles.join(', ')}</span>` : '';
            html += `
                <label class="d-block border rounded p-2 mb-2">
                    <input type="radio" name="default_workspace" value="${workspace.slug}" ${checked}>
                    <span class="ms-1 fw-semibold">${workspace.label}</span>
                    ${badge}
                    <span class="d-block text-muted small mt-1">${workspace.route || workspace.slug}</span>
                    ${description}
                    ${rolesText}
                </label>
            `;
        });

        workspaceContainer.innerHTML = html;
    }

    function renderDefaultMobileLayoutOptions() {
        const roles = checkedRoleLayouts();
        const currentSelection = document.querySelector('input[name="default_mobile_role_id"]:checked')?.value;
        const selectedRoleId = currentSelection !== undefined ? currentSelection : initialSelectedMobileRoleId;
        const selectedStillAvailable = selectedRoleId && roles.some((role) => String(role.id) === String(selectedRoleId) && role.mobile_slug);
        const noneChecked = !selectedRoleId || !selectedStillAvailable;
        let html = `
            <div class="mb-2">
                <label class="d-block border rounded p-2">
                    <input type="radio" name="default_mobile_role_id" value="" ${noneChecked ? 'checked' : ''}>
                    <span class="ms-1">Không đặt Mobile layout mặc định</span>
                </label>
            </div>
        `;

        const mobileRoles = roles.filter((role) => role.mobile_slug);
        if (mobileRoles.length === 0) {
            html += '<div class="alert alert-warning mb-0">Các role đang chọn chưa có Mobile layout hợp lệ.</div>';
            mobileLayoutContainer.innerHTML = html;
            return;
        }

        mobileRoles.forEach((role) => {
            const checked = String(selectedRoleId) === String(role.id) ? 'checked' : '';
            const badge = String(savedMobileRoleId) === String(role.id) ? '<span class="badge bg-success ms-2">Đang là mặc định</span>' : '';
            const description = role.mobile_description ? `<span class="d-block text-muted small mt-1">${role.mobile_description}</span>` : '';
            html += `
                <label class="d-block border rounded p-2 mb-2">
                    <input type="radio" name="default_mobile_role_id" value="${role.id}" ${checked}>
                    <span class="ms-1 fw-semibold">${role.mobile_label || role.mobile_slug}</span>
                    ${badge}
                    <span class="badge bg-info text-dark ms-2">Role: ${role.name}</span>
                    <span class="d-block text-muted small mt-1">${role.mobile_slug}${role.mobile_route ? ' | ' + role.mobile_route : ''}</span>
                    ${description}
                </label>
            `;
        });

        mobileLayoutContainer.innerHTML = html;
    }

    function renderRoleLayoutSummary() {
        const roles = checkedRoleLayouts();

        if (roles.length === 0) {
            summaryBody.innerHTML = '<tr><td colspan="3" class="text-muted">Chưa chọn role nào.</td></tr>';
            return;
        }

        summaryBody.innerHTML = roles.map((role) => {
            const web = role.web_slug
                ? `<strong>${role.web_label || role.web_slug}</strong><div class="text-muted small">${role.web_slug}${role.web_route ? ' | ' + role.web_route : ''}</div>`
                : '<span class="text-danger">Thiếu website layout</span>';
            const mobile = role.mobile_slug
                ? `<strong>${role.mobile_label || role.mobile_slug}</strong><div class="text-muted small">${role.mobile_slug}${role.mobile_route ? ' | ' + role.mobile_route : ''}</div>`
                : '<span class="text-danger">Thiếu my_app layout</span>';

            return `<tr><td>${role.name}</td><td>${web}</td><td>${mobile}</td></tr>`;
        }).join('');
    }

    function refreshLayoutUi() {
        renderDefaultWorkspaceOptions();
        renderDefaultMobileLayoutOptions();
        renderRoleLayoutSummary();
    }

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshLayoutUi));
    refreshLayoutUi();
});
</script>
@endpush
