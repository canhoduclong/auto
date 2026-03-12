@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0">Gán hàng loạt user vào team</h2>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Quay lại danh sách user</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('users.bulk-assign-team.form') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Tìm user</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Tên hoặc email">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lọc theo role</label>
                    <select name="role_id" class="form-select">
                        <option value="">Tất cả role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ (string) request('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lọc theo team</label>
                    <select name="team_id" class="form-select">
                        <option value="">Tất cả team</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('users.bulk-assign-team') }}">
        @csrf

        <div class="card mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Team cần gán</label>
                    <select name="team_id" class="form-select">
                        <option value="">-- Bỏ gán team --</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 text-md-end">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Xác nhận cập nhật team cho các user đã chọn?')">Cập nhật team cho user đã chọn</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Team hiện tại</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td><input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="row-check"></td>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="badge bg-info">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>{{ $user->team->name ?? 'Chưa gán' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Không có user phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    const rowChecks = document.querySelectorAll('.row-check');

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rowChecks.forEach(cb => cb.checked = checkAll.checked);
        });
    }
});
</script>
@endsection
