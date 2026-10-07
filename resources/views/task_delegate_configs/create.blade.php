@extends('layouts.admin')

@section('title', 'Thêm phân quyền giao việc')

@push('styles')
<style>
.user-checkbox-list { max-height: 320px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; }
.user-check-item { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 6px; cursor: pointer; }
.user-check-item:hover { background: #f1f5f9; }
.user-check-item input { cursor: pointer; }
.role-badge { font-size: 10px; padding: 1px 6px; border-radius: 10px; background: #e2e8f0; color: #475569; }
</style>
@endpush

@section('content')
<div class="content-wrapper">
<div class="content-header d-flex align-items-center py-3 px-4">
    <a href="{{ route('task-delegate-configs.index') }}" class="btn btn-sm btn-outline-secondary me-3">
        <i class="ph ph-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0">Thêm phân quyền giao việc</h4>
        <small class="text-muted">Chọn người được phép giao việc và danh sách người nhận</small>
    </div>
</div>

<div class="content-body px-4 pb-4" style="max-width: 780px">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('task-delegate-configs.store') }}" method="POST">
        @csrf

        <div class="card shadow-sm mb-3">
            <div class="card-header py-2 fw-semibold small text-uppercase text-muted">
                <i class="ph-user-gear me-1"></i>Người được phép giao việc
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Người này sẽ thấy danh sách người nhận khi tạo công việc mới.</p>
                <select name="assigner_id" class="form-select @error('assigner_id') is-invalid @enderror" required id="assignerSelect">
                    <option value="">-- Chọn người giao việc --</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ old('assigner_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }}
                        </option>
                    @endforeach
                </select>
                @error('assigner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header py-2 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <span class="fw-semibold small text-uppercase text-muted">
                    <i class="ph-users me-1"></i>Người nhận việc
                </span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="checkAll(true)">Chọn hết</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="checkAll(false)">Bỏ chọn</button>
                    <input type="text" id="userSearch" class="form-control form-control-sm" style="width:160px" placeholder="Tìm kiếm…">
                </div>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Chọn một hoặc nhiều người. Người được chọn sẽ xuất hiện trong danh sách khi người giao việc tạo công việc mới.</p>
                @error('assignee_ids')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

                <div class="user-checkbox-list" id="userList">
                    @foreach($users as $u)
                        <label class="user-check-item" data-name="{{ strtolower($u->name) }}">
                            <input type="checkbox" name="assignee_ids[]" value="{{ $u->id }}"
                                   {{ in_array($u->id, old('assignee_ids', [])) ? 'checked' : '' }}>
                            <span class="flex-grow-1">{{ $u->name }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="text-muted small mt-2" id="selectedCount">0 người được chọn</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Ghi chú (tùy chọn)</label>
            <textarea name="note" class="form-control" rows="2" maxlength="500"
                      placeholder="Ví dụ: Trưởng nhóm kinh doanh giao việc cho các thành viên…">{{ old('note') }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="ph-floppy-disk me-1"></i>Lưu phân quyền
            </button>
            <a href="{{ route('task-delegate-configs.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
</div>

<script>
// Live search
document.getElementById('userSearch').addEventListener('input', function () {
    const kw = this.value.toLowerCase();
    document.querySelectorAll('#userList .user-check-item').forEach(item => {
        item.style.display = item.dataset.name.includes(kw) ? '' : 'none';
    });
});

// Select all / none
function checkAll(val) {
    document.querySelectorAll('#userList input[type=checkbox]').forEach(cb => { if (!cb.disabled) cb.checked = val; });
    updateCount();
}

// Counter
function updateCount() {
    const cnt = document.querySelectorAll('#userList input:checked').length;
    document.getElementById('selectedCount').textContent = cnt + ' người được chọn';
}
document.getElementById('userList').addEventListener('change', updateCount);
updateCount();

// Prevent checking assigner as assignee
document.getElementById('assignerSelect').addEventListener('change', function () {
    const assignerId = parseInt(this.value);
    document.querySelectorAll('#userList input[type=checkbox]').forEach(cb => {
        if (parseInt(cb.value) === assignerId) {
            cb.checked = false;
            cb.disabled = true;
            cb.closest('.user-check-item').style.opacity = '.4';
            cb.closest('.user-check-item').title = 'Không thể chọn chính người giao việc';
        } else {
            cb.disabled = false;
            cb.closest('.user-check-item').style.opacity = '';
            cb.closest('.user-check-item').title = '';
        }
    });
});
</script>
@endsection
