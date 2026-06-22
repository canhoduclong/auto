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
            <label class="form-label">Tên Zalo</label>
            <input type="text" name="zalo_name" class="form-control" value="{{ old('zalo_name') }}" placeholder="Ví dụ: Ba Sơn Hoàng Long Tnt">
            <small class="text-muted">Dùng để nhận diện sale khi nhập đơn từ nội dung Zalo.</small>
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
                <option value="">-- --- team --</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">Leader/Manager sẽ xem đơn theo team được gán.</small>
        </div>

        @include('users.partials.managed_accounts', [
            'selectedAccountIds' => collect(old('account_ids', []))->map(fn($id) => (int)$id),
            'defaultAccountId' => (int) old('default_account_id', 0),
        ])

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

        {{-- Khối / Phòng --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Khối</label>
                <select name="block_id" id="block_id_create" class="form-control">
                    <option value="">-- Chọn khối --</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id') == $block->id ? 'selected' : '' }}>{{ $block->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phòng / Ban</label>
                <select name="department_id" id="dept_id_create" class="form-control">
                    <option value="">-- Chọn phòng --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" data-block="{{ $dept->block_id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Tạo</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
<script>
(function() {
    const blockSel = document.getElementById('block_id_create');
    const deptSel  = document.getElementById('dept_id_create');
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
@endsection
