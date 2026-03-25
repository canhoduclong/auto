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
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                        {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                        {{ $role->name }}
                    </label><br>
                @endforeach
            </div>
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
    </form>
</div>
@endsection
