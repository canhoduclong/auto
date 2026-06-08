@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="mb-1">
                    <i class="bi bi-shield-lock me-2 text-primary"></i>Cập nhật Vai trò
                </h2>
                <div class="text-muted">Thiết lập quyền truy cập chức năng cho role <strong>{{ $role->name }}</strong>.</div>
            </div>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
            </a>
        </div>
    </div>

    <form action="{{ route('roles.update', $role->id) }}" method="POST" id="role-edit-form">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-info"></i>Thông tin role</h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Tên Role</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="description" class="form-label">Mô tả (tuỳ chọn)</label>
                        <input type="text" name="description" id="description" value="{{ old('description', $role->description) }}" class="form-control" placeholder="Ví dụ: Role quản lý kho">
                    </div>

                    @php
                        $websiteLayouts = collect($layoutCatalog)->filter(fn ($layout) => ($layout['platform'] ?? 'website') === 'website');
                        $mobileLayouts = collect($layoutCatalog)->filter(fn ($layout) => ($layout['platform'] ?? 'website') === 'my_app');
                    @endphp

                    <div class="col-md-6">
                        <label for="layout_web_slug" class="form-label">Website layout</label>
                        <select name="layout_web_slug" id="layout_web_slug" class="form-select @error('layout_web_slug') is-invalid @enderror" required>
                            <option value="">-- Chọn 1 layout website --</option>
                            @foreach($websiteLayouts as $slug => $layout)
                                <option
                                    value="{{ $slug }}"
                                    data-label="{{ $layout['label'] ?? $slug }}"
                                    {{ old('layout_web_slug', $role->layout_web_slug) === $slug ? 'selected' : '' }}
                                >
                                    {{ $layout['label'] ?? $slug }} | {{ $layout['route'] ?? '' }} | roles: {{ collect($layout['role_hints'] ?? [])->join(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('layout_web_slug')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Mỗi role có duy nhất 1 layout website để redirect sau đăng nhập.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="layout_web_name" class="form-label">Website layout name</label>
                        <input
                            type="text"
                            name="layout_web_name"
                            id="layout_web_name"
                            value="{{ old('layout_web_name', $role->layout_web_name) }}"
                            class="form-control @error('layout_web_name') is-invalid @enderror"
                            placeholder="Tự lấy theo layout nếu bỏ trống"
                        >
                        @error('layout_web_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="layout_mobile_slug" class="form-label">My_app mobile layout</label>
                        <select name="layout_mobile_slug" id="layout_mobile_slug" class="form-select @error('layout_mobile_slug') is-invalid @enderror" required>
                            <option value="">-- Chọn 1 layout mobile --</option>
                            @foreach($mobileLayouts as $slug => $layout)
                                <option
                                    value="{{ $slug }}"
                                    data-label="{{ $layout['label'] ?? $slug }}"
                                    {{ old('layout_mobile_slug', $role->layout_mobile_slug) === $slug ? 'selected' : '' }}
                                >
                                    {{ $layout['label'] ?? $slug }} | {{ $layout['route'] ?? '' }} | roles: {{ collect($layout['role_hints'] ?? [])->join(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('layout_mobile_slug')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">My_app là app Flutter viết lại các tính năng website trên mobile.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="layout_mobile_name" class="form-label">Mobile layout name</label>
                        <input
                            type="text"
                            name="layout_mobile_name"
                            id="layout_mobile_name"
                            value="{{ old('layout_mobile_name', $role->layout_mobile_name) }}"
                            class="form-control @error('layout_mobile_name') is-invalid @enderror"
                            placeholder="Tự lấy theo layout nếu bỏ trống"
                        >
                        @error('layout_mobile_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <label class="form-label mb-0 fw-bold">
                        <i class="bi bi-key me-2 text-warning"></i>Phân quyền chi tiết
                    </label>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="checkAllPermissions">
                            <i class="bi bi-check2-square me-1"></i>Check All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="resetPermissions">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <input type="text" class="form-control" id="permission-search" placeholder="Tìm nhanh theo tên quyền hoặc nhóm chức năng...">
                </div>

                @php
                    $groupedPermissions = $permissions->groupBy(function ($perm) {
                        return explode('.', $perm->name)[0];
                    });

                    $featureIcons = [
                        'users' => 'bi-people',
                        'roles' => 'bi-person-badge',
                        'permissions' => 'bi-shield-check',
                        'products' => 'bi-box-seam',
                        'orders' => 'bi-receipt',
                        'customers' => 'bi-person-lines-fill',
                        'warehouse' => 'bi-house-gear',
                        'inventory' => 'bi-stack',
                        'reports' => 'bi-bar-chart',
                        'media' => 'bi-image',
                        'admin' => 'bi-gear',
                    ];
                @endphp

                <div class="row" id="permission-groups">
                    @foreach($groupedPermissions as $feature => $perms)
                        @php
                            $featureIcon = $featureIcons[$feature] ?? 'bi-grid';
                        @endphp

                        <div class="col-lg-6 mb-3 permission-group" data-feature="{{ strtolower($feature) }}">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>
                                        <i class="bi {{ $featureIcon }} me-1 text-primary"></i>{{ ucfirst($feature) }}
                                    </strong>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-dark js-check-group" data-feature="{{ strtolower($feature) }}">
                                            <i class="bi bi-check2 me-1"></i>Chọn nhóm
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger js-uncheck-group" data-feature="{{ strtolower($feature) }}">
                                            <i class="bi bi-x-square me-1"></i>Bỏ chọn nhóm
                                        </button>
                                    </div>
                                </div>

                                @foreach($perms as $permission)
                                    <div class="form-check permission-item" data-permission-name="{{ strtolower($permission->name) }}">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permission->id }}"
                                               id="perm_{{ $permission->id }}"
                                               class="form-check-input"
                                               data-initial-checked="{{ $role->permissions->contains($permission->id) ? '1' : '0' }}"
                                               {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="perm_{{ $permission->id }}">
                                            {{ $permission->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save2 me-1"></i>Lưu thay đổi
            </button>
            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle me-1"></i>Hủy
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const permissionCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
    const checkAllButton = document.getElementById('checkAllPermissions');
    const resetButton = document.getElementById('resetPermissions');
    const searchInput = document.getElementById('permission-search');
    const syncLayoutName = function (selectId, inputId) {
        const select = document.getElementById(selectId);
        const input = document.getElementById(inputId);
        if (!select || !input) return;

        select.addEventListener('change', function () {
            if (input.value.trim() !== '') return;
            input.value = select.options[select.selectedIndex]?.dataset.label || '';
        });
    };

    syncLayoutName('layout_web_slug', 'layout_web_name');
    syncLayoutName('layout_mobile_slug', 'layout_mobile_name');

    if (checkAllButton) {
        checkAllButton.addEventListener('click', function () {
            permissionCheckboxes.forEach(function (checkbox) {
                checkbox.checked = true;
            });
        });
    }

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            permissionCheckboxes.forEach(function (checkbox) {
                checkbox.checked = checkbox.dataset.initialChecked === '1';
            });
        });
    }

    document.querySelectorAll('.js-check-group').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const feature = btn.getAttribute('data-feature');
            const group = document.querySelector(`.permission-group[data-feature="${feature}"]`);
            if (!group) return;
            group.querySelectorAll('input[name="permissions[]"]').forEach(function (checkbox) {
                checkbox.checked = true;
            });
        });
    });
    document.querySelectorAll('.js-uncheck-group').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const feature = btn.getAttribute('data-feature');
            const group = document.querySelector(`.permission-group[data-feature="${feature}"]`);
            if (!group) return;
            group.querySelectorAll('input[name="permissions[]"]').forEach(function (checkbox) {
                checkbox.checked = false;
            });
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const keyword = (searchInput.value || '').trim().toLowerCase();

            document.querySelectorAll('.permission-group').forEach(function (group) {
                let hasVisible = false;

                group.querySelectorAll('.permission-item').forEach(function (item) {
                    const label = item.textContent.toLowerCase();
                    const visible = keyword === '' || label.includes(keyword);
                    item.style.display = visible ? '' : 'none';
                    hasVisible = hasVisible || visible;
                });

                group.style.display = hasVisible ? '' : 'none';
            });
        });
    }
});
</script>
@endpush
