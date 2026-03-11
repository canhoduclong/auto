@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Cập nhật Vai trò</h2>

    <form action="{{ route('roles.update', $role->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Tên Role</label>
            <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" class="form-control">
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">Phân quyền chi tiết (Permissions)</label>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="checkAllPermissions">Check All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="resetPermissions">Reset</button>
                </div>
            </div>
            <div class="row">
                @php
                    // Nhóm permissions theo tính năng
                    $groupedPermissions = $permissions->groupBy(function($perm){
                        return explode('.', $perm->name)[0]; // ví dụ 'users', 'products'
                    });
                @endphp

                @foreach($groupedPermissions as $feature => $perms)
                    <div class="col-md-6 mb-3">
                        <strong>{{ ucfirst($feature) }}</strong>
                        @foreach($perms as $permission)
                            <div class="form-check">
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
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const permissionCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
    const checkAllButton = document.getElementById('checkAllPermissions');
    const resetButton = document.getElementById('resetPermissions');

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
});
</script>
@endpush
