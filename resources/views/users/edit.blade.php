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
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" data-role-name="{{ strtolower($role->name) }}"
                        {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                        {{ $role->name }}
                    </label><br>
                @endforeach
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Layout mặc định</label>
            @php
                $selectedWorkspace = old('default_workspace', $user->default_workspace);
            @endphp

            <div class="mb-2">
                <label class="d-block border rounded p-2">
                    <input type="radio" name="default_workspace" value="" {{ empty($selectedWorkspace) ? 'checked' : '' }}>
                    <span class="ms-1">Không đặt mặc định (hệ thống sẽ hỏi chọn khi đăng nhập nếu có nhiều layout)</span>
                </label>
            </div>

            @if(count($availableWorkspaces) > 0)
                @foreach($availableWorkspaces as $workspace)
                    <label class="d-block border rounded p-2 mb-2">
                        <input type="radio" name="default_workspace" value="{{ $workspace['key'] }}" {{ $selectedWorkspace === $workspace['key'] ? 'checked' : '' }}>
                        <span class="ms-1 fw-semibold">{{ $workspace['label'] }}</span>
                        @if($user->default_workspace === $workspace['key'])
                            <span class="badge bg-success ms-2">Đang là mặc định</span>
                        @endif
                        @if(!empty($workspace['description']))
                            <span class="d-block text-muted small mt-1">{{ $workspace['description'] }}</span>
                        @endif
                    </label>
                @endforeach
            @else
                <div class="alert alert-warning mb-0">User hiện chưa có layout hợp lệ theo vai trò đang gán.</div>
            @endif

            <small class="text-muted">Danh sách này lấy theo vai trò hiện có của user.</small>
            @error('default_workspace') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
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
