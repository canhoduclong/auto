@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Thêm User mới</h2>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Tên</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Xác nhận mật khẩu</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Vai trò</label>
            <div>
                @foreach($roles as $role)
                    <label>
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"> {{ $role->name }}
                    </label><br>
                @endforeach
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Team</label>
            <select name="team_id" class="form-control">
                <option value="">-- Chưa gán team --</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">Leader/Manager sẽ xem đơn theo team được gán.</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Kho duoc assign</label>
            <select name="warehouse_id" class="form-control">
                <option value="">-- Chua gan kho --</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">User role warehouse se chi thao tac tren kho duoc gan.</small>
        </div>

        <button type="submit" class="btn btn-success">Tạo</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
