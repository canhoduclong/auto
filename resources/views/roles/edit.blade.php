@extends('layouts.app')

@push('styles')
<style>
    .role-edit-page {
        --role-edit-ink: #111827;
        --role-edit-muted: #6b7280;
        --role-edit-line: #e5e7eb;
        --role-edit-soft: #f8fafc;
        --role-edit-primary: #2563eb;
        --role-edit-primary-dark: #1d4ed8;
        padding-bottom: 32px;
    }

    .role-edit-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding: 22px 0 18px;
        border-bottom: 1px solid var(--role-edit-line);
        margin-bottom: 14px;
    }

    .role-edit-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0 0 6px;
        color: var(--role-edit-ink);
        font-size: 1.45rem;
        font-weight: 800;
    }

    .role-edit-title-icon,
    .role-edit-section-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: #dbeafe;
        color: var(--role-edit-primary);
    }

    .role-edit-subtitle {
        color: var(--role-edit-muted);
        margin: 0;
    }

    .role-edit-role-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e40af;
        border-radius: 999px;
        padding: 7px 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .role-edit-sticky-actions {
        position: sticky;
        top: 0;
        z-index: 50;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        margin-bottom: 18px;
        border: 1px solid rgba(37, 99, 235, 0.18);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(10px);
    }

    .role-edit-sticky-title {
        color: var(--role-edit-ink);
        font-weight: 800;
        margin: 0;
    }

    .role-edit-sticky-meta {
        color: var(--role-edit-muted);
        font-size: .83rem;
        margin: 2px 0 0;
    }

    .role-edit-save {
        min-width: 132px;
        background: var(--role-edit-primary);
        border-color: var(--role-edit-primary);
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22);
    }

    .role-edit-save:hover,
    .role-edit-save:focus {
        background: var(--role-edit-primary-dark);
        border-color: var(--role-edit-primary-dark);
    }

    .role-edit-panel {
        border: 1px solid var(--role-edit-line);
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
        margin-bottom: 18px;
    }

    .role-edit-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--role-edit-line);
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .role-edit-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: var(--role-edit-ink);
        font-size: 1.02rem;
        font-weight: 800;
    }

    .role-edit-section-icon {
        width: 34px;
        height: 34px;
    }

    .role-edit-section-desc {
        color: var(--role-edit-muted);
        margin: 6px 0 0 44px;
        font-size: .88rem;
    }

    .role-edit-panel-body {
        padding: 20px;
    }

    .role-edit-field-card {
        height: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: var(--role-edit-soft);
        padding: 14px;
    }

    .role-edit-field-card .form-label {
        color: #374151;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .role-edit-field-card .form-control,
    .role-edit-field-card .form-select {
        background-color: #fff;
        border-color: #d1d5db;
    }

    .role-edit-helper {
        display: block;
        color: var(--role-edit-muted);
        font-size: .8rem;
        margin-top: 7px;
    }

    .role-edit-permission-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .role-edit-permission-search {
        max-width: 460px;
        min-width: min(100%, 260px);
    }

    .role-edit-permission-group {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        padding: 14px;
        height: 100%;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
    }

    .role-edit-permission-group-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .role-edit-permission-title {
        color: var(--role-edit-ink);
        font-weight: 800;
    }

    .role-edit-bottom-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 2px;
    }

    @media (max-width: 767.98px) {
        .role-edit-header,
        .role-edit-sticky-actions,
        .role-edit-panel-head,
        .role-edit-permission-toolbar,
        .role-edit-bottom-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .role-edit-sticky-actions {
            top: 0;
        }

        .role-edit-sticky-actions .btn,
        .role-edit-bottom-actions .btn {
            width: 100%;
        }

        .role-edit-section-desc {
            margin-left: 0;
        }
    }
</style>
@endpush

@section('content')
<div class="container role-edit-page">
    <div class="role-edit-header">
        <div>
            <h2 class="role-edit-title">
                <span class="role-edit-title-icon"><i class="bi bi-shield-lock"></i></span>
                Cập nhật vai trò
            </h2>
            <p class="role-edit-subtitle">Thiết lập layout và quyền truy cập cho role trong hệ thống.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="role-edit-role-pill">
                <i class="bi bi-person-badge"></i>
                {{ $role->name }}
            </span>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Danh sách role
            </a>
        </div>
    </div>

    <form action="{{ route('roles.update', $role->id) }}" method="POST" id="role-edit-form">
        @csrf
        @method('PUT')

        <div class="role-edit-sticky-actions">
            <div>
                <p class="role-edit-sticky-title mb-0">Đang chỉnh sửa: {{ $role->name }}</p>
                <p class="role-edit-sticky-meta">Lưu để áp dụng layout website, my_app và phân quyền mới.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('roles.index') }}" class="btn btn-light border">
                    <i class="bi bi-x-circle me-1"></i>Hủy
                </a>
                <button type="submit" class="btn btn-primary role-edit-save">
                    <i class="bi bi-save2 me-1"></i>Lưu thay đổi
                </button>
            </div>
        </div>

        <div class="role-edit-panel">
            <div class="role-edit-panel-head">
                <div>
                    <h3 class="role-edit-section-title">
                        <span class="role-edit-section-icon"><i class="bi bi-info-circle"></i></span>
                        Thông tin và layout
                    </h3>
                    <p class="role-edit-section-desc">Mỗi role cần đúng một layout website và một layout my_app.</p>
                </div>
            </div>
            <div class="role-edit-panel-body">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="role-edit-field-card">
                            <label for="name" class="form-label">Tên Role</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="role-edit-field-card">
                            <label for="description" class="form-label">Mô tả</label>
                            <input type="text" name="description" id="description" value="{{ old('description', $role->description) }}" class="form-control" placeholder="Ví dụ: Role quản lý kho">
                        </div>
                    </div>

                    @php
                        $websiteLayouts = collect($layoutCatalog)->filter(fn ($layout) => ($layout['platform'] ?? 'website') === 'website');
                        $mobileLayouts = collect($layoutCatalog)->filter(fn ($layout) => ($layout['platform'] ?? 'website') === 'my_app');
                    @endphp

                    <div class="col-md-6">
                        <div class="role-edit-field-card">
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
                            <span class="role-edit-helper">Redirect website sau đăng nhập.</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="role-edit-field-card">
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
                    </div>

                    <div class="col-md-6">
                        <div class="role-edit-field-card">
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
                            <span class="role-edit-helper">Layout cho app Flutter my_app.</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="role-edit-field-card">
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
        </div>

        <div class="role-edit-panel">
            <div class="role-edit-panel-head">
                <div>
                    <h3 class="role-edit-section-title">
                        <span class="role-edit-section-icon"><i class="bi bi-key"></i></span>
                        Phân quyền chi tiết
                    </h3>
                    <p class="role-edit-section-desc">Chọn quyền theo từng nhóm chức năng cho role này.</p>
                </div>
            </div>
            <div class="role-edit-panel-body">
                <div class="role-edit-permission-toolbar">
                    <input type="text" class="form-control role-edit-permission-search" id="permission-search" placeholder="Tìm nhanh theo tên quyền hoặc nhóm chức năng...">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="checkAllPermissions">
                            <i class="bi bi-check2-square me-1"></i>Check All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="resetPermissions">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
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
                            <div class="role-edit-permission-group">
                                <div class="role-edit-permission-group-head">
                                    <strong class="role-edit-permission-title">
                                        <i class="bi {{ $featureIcon }} me-1 text-primary"></i>{{ ucfirst($feature) }}
                                    </strong>
                                    <div class="d-flex gap-2 flex-wrap">
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

        <div class="role-edit-bottom-actions">
            <a href="{{ route('roles.index') }}" class="btn btn-light border">
                <i class="bi bi-x-circle me-1"></i>Hủy
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save2 me-1"></i>Lưu thay đổi
            </button>
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
