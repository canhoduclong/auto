@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0">Danh sách Người dùng</h2>
        <div class="d-flex gap-2">
            <button type="submit" form="bulk-delete-form" class="btn btn-outline-danger" id="bulk-delete-button">Xóa đã chọn</button>
            <a href="{{ route('users.bulk-assign-team.form') }}" class="btn btn-outline-primary">Gán hàng loạt vào team</a>
            <a href="{{ route('users.create') }}" class="btn btn-primary">+ Thêm User</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form id="bulk-delete-form" action="{{ route('users.bulk-delete') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
        <div id="bulk-delete-inputs"></div>
    </form>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Tên hoặc email">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Team</label>
                    <select name="team_id" class="form-select">
                        <option value="">Tất cả team</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select">
                        <option value="">Tất cả role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ (string) request('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">Lọc</button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="width: 1%; white-space: nowrap;">
                    <input type="checkbox" id="select-all-users" class="form-check-input">
                </th>
                <th>ID</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Team</th>
                <th>Kho được assign</th>
                <th>Quyền</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>
                    <input type="checkbox" class="form-check-input user-checkbox" value="{{ $user->id }}">
                </td>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->team->name ?? 'Chưa gán' }}</td>
                <td>{{ $user->warehouse->name ?? 'Chưa gán' }}</td>
                <td>
                    @foreach($user->roles as $role)
                        <span class="badge bg-info">{{ $role->name }}</span>
                    @endforeach
                </td>
                <td>
                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">Sửa</a>
                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline-block">
                        @csrf @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                        <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('Bạn có chắc chắn muốn xóa không?')">
                            Xóa
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $users->links() }}
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('select-all-users');
    var bulkDeleteForm = document.getElementById('bulk-delete-form');
    var bulkDeleteInputs = document.getElementById('bulk-delete-inputs');
    var bulkDeleteButton = document.getElementById('bulk-delete-button');

    function getUserCheckboxes() {
        return Array.from(document.querySelectorAll('.user-checkbox'));
    }

    function syncBulkDeleteButton() {
        var hasSelection = getUserCheckboxes().some(function (checkbox) {
            return checkbox.checked;
        });

        bulkDeleteButton.disabled = !hasSelection;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            getUserCheckboxes().forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });

            syncBulkDeleteButton();
        });
    }

    getUserCheckboxes().forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var checkboxes = getUserCheckboxes();
            var checkedCount = checkboxes.filter(function (item) {
                return item.checked;
            }).length;

            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            }

            syncBulkDeleteButton();
        });
    });

    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function (event) {
            var selectedIds = getUserCheckboxes()
                .filter(function (checkbox) {
                    return checkbox.checked;
                })
                .map(function (checkbox) {
                    return checkbox.value;
                });

            if (selectedIds.length === 0) {
                event.preventDefault();
                alert('Vui lòng chọn ít nhất một user để xóa.');
                return;
            }

            if (!window.confirm('Bạn có chắc chắn muốn xóa các user đã chọn không?')) {
                event.preventDefault();
                return;
            }

            bulkDeleteInputs.innerHTML = '';

            selectedIds.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_ids[]';
                input.value = id;
                bulkDeleteInputs.appendChild(input);
            });
        });
    }

    syncBulkDeleteButton();
});
</script>
@endpush
