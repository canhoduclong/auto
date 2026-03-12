@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0">Danh sách Người dùng</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('users.bulk-assign-team.form') }}" class="btn btn-outline-primary">Gán hàng loạt vào team</a>
            <a href="{{ route('users.create') }}" class="btn btn-primary">+ Thêm User</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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
