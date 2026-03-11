@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Tạo quy trình xét duyệt</h4>

    <form action="{{ route('approval-workflows.store') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Mã quy trình</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="order_default">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tên quy trình</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Quy trình duyệt Sale -> Manager -> Director">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', 1) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Kích hoạt</label>
                    </div>
                </div>
            </div>

            <h6 class="mt-2">Các bước duyệt</h6>
            <p class="text-muted mb-2">Chọn role theo thứ tự duyệt. Ví dụ: sale -> manager -> director.</p>

            @php
                $oldSteps = old('steps', [
                    ['role_slug' => 'manager', 'can_skip' => 0],
                    ['role_slug' => 'director', 'can_skip' => 0],
                ]);
            @endphp

            <div id="steps-wrapper">
            @foreach($oldSteps as $idx => $step)
                <div class="row mb-2 step-row">
                    <div class="col-md-8">
                        <label class="form-label">Bước {{ $idx + 1 }} - Role</label>
                        <select name="steps[{{ $idx }}][role_slug]" class="form-select" required>
                            <option value="">-- Chọn Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ ($step['role_slug'] ?? '') === $role ? 'selected' : '' }}>{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="d-flex align-items-center gap-3 mb-2 w-100">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="steps[{{ $idx }}][can_skip]" value="1" id="can_skip_{{ $idx }}" {{ !empty($step['can_skip']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="can_skip_{{ $idx }}">Cho phép bỏ qua bước</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger ms-auto remove-step">Xóa</button>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>

            <button type="button" id="add-step" class="btn btn-outline-secondary btn-sm">+ Thêm bước</button>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Lưu quy trình</button>
            <a href="{{ route('approval-workflows.index') }}" class="btn btn-secondary">Quay lại</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('steps-wrapper');
    const addBtn = document.getElementById('add-step');

    const reindex = () => {
        const rows = wrapper.querySelectorAll('.step-row');
        rows.forEach((row, idx) => {
            const label = row.querySelector('label.form-label');
            if (label) {
                label.textContent = `Buoc ${idx + 1} - Role`;
            }

            const roleInput = row.querySelector('select[name*="[role_slug]"]');
            const skipInput = row.querySelector('input.form-check-input');
            const skipLabel = row.querySelector('.form-check-label');

            if (roleInput) {
                roleInput.name = `steps[${idx}][role_slug]`;
            }

            if (skipInput) {
                skipInput.name = `steps[${idx}][can_skip]`;
                skipInput.id = `can_skip_${idx}`;
            }

            if (skipLabel) {
                skipLabel.setAttribute('for', `can_skip_${idx}`);
            }
        });
    };

    addBtn.addEventListener('click', function () {
        const idx = wrapper.querySelectorAll('.step-row').length;
        const html = `
            <div class="row mb-2 step-row">
                <div class="col-md-8">
                    <label class="form-label">Buoc ${idx + 1} - Role</label>
                    <select name="steps[${idx}][role_slug]" class="form-select" required>
                        <option value="">-- Chon Role --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="d-flex align-items-center gap-3 mb-2 w-100">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="steps[${idx}][can_skip]" value="1" id="can_skip_${idx}">
                            <label class="form-check-label" for="can_skip_${idx}">Cho phep bo qua buoc</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-auto remove-step">Xoa</button>
                    </div>
                </div>
            </div>`;
        wrapper.insertAdjacentHTML('beforeend', html);
        reindex();
    });

    wrapper.addEventListener('click', function (event) {
        if (!event.target.classList.contains('remove-step')) {
            return;
        }

        const rows = wrapper.querySelectorAll('.step-row');
        if (rows.length <= 1) {
            return;
        }

        event.target.closest('.step-row').remove();
        reindex();
    });

    reindex();
});
</script>
@endpush
