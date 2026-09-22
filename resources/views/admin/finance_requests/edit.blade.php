@extends('layouts.app')

@section('title', 'Sửa phiếu yêu cầu #'.$transaction->id)

@section('content')
@php
    $items = old('items', $transaction->request_items ?: [['content' => '', 'unit' => '', 'quantity' => 1, 'unit_price' => 0]]);
    $assignedRoles = $transaction->submitter?->roles?->pluck('name')->map(fn ($name) => strtolower($name))->all() ?? [];
    $flow = old('flow_direction', $transaction->type === 'extra_income' ? 'in' : 'out');
@endphp
<div class="container-fluid py-3" style="max-width:1200px">
    <div class="d-flex justify-content-between align-items-start mb-3"><div><h1 class="h3 mb-1">Sửa phiếu yêu cầu #{{ $transaction->id }}</h1><div class="text-muted">Trạng thái và lịch sử duyệt sẽ được giữ nguyên.</div></div><a class="btn btn-outline-secondary" href="{{ route('admin.accounting.finance-requests.index') }}">Quay lại</a></div>
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('admin.accounting.finance-requests.update', $transaction) }}">@csrf @method('PUT')
        <div class="card mb-3"><div class="card-header fw-bold">Người lập và bộ phận</div><div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">Người lập phiếu</label><input class="form-control" value="{{ $transaction->submitter?->name ?: 'Không xác định' }}" disabled><div class="form-text">Vai trò được gán: {{ $transaction->submitter?->roles?->pluck('name')->implode(', ') ?: 'Không có' }}</div></div>
            <div class="col-md-4"><label class="form-label">Vai trò / nguồn tạo phiếu <span class="text-danger">*</span></label><select class="form-select" name="request_source" id="adminRequestSource" required>@foreach($sourceConfigs as $key => $config)@php($matchesRole = collect(explode(',', $config['role']))->map(fn($role) => strtolower(trim($role)))->reject(fn($role) => $role === 'admin')->intersect($assignedRoles)->isNotEmpty())<option value="{{ $key }}" data-label="{{ $config['label'] }}" @selected(old('request_source', $transaction->request_source) === $key)>{{ $config['label'] }}{{ $matchesRole ? ' ✓ vai trò của người dùng' : '' }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Tên bộ phận hiển thị <span class="text-danger">*</span></label><div class="input-group"><input class="form-control" name="request_department" id="adminRequestDepartment" maxlength="150" value="{{ old('request_department', $transaction->request_department) }}" required><button class="btn btn-outline-secondary" type="button" id="useRoleLabel">Dùng tên vai trò</button></div><div class="form-text">Có thể nhập tự do nếu tên bộ phận thực tế khác tên vai trò.</div></div>
        </div></div>
        <div class="card mb-3"><div class="card-header fw-bold">Thông tin phiếu</div><div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">Loại phiếu</label><select class="form-select" name="request_form_type" required><option value="{{ \App\Models\Transaction::REQUEST_FORM_CASH }}" @selected(old('request_form_type', $transaction->request_form_type) === \App\Models\Transaction::REQUEST_FORM_CASH)>Phiếu yêu cầu thu/chi</option><option value="{{ \App\Models\Transaction::REQUEST_FORM_PAYMENT }}" @selected(old('request_form_type', $transaction->request_form_type) === \App\Models\Transaction::REQUEST_FORM_PAYMENT)>Phiếu đề nghị thanh toán</option></select></div>
            <div class="col-md-4"><label class="form-label">Dòng tiền</label><select class="form-select" name="flow_direction"><option value="out" @selected($flow === 'out')>Chi</option><option value="in" @selected($flow === 'in')>Thu</option></select></div>
            <div class="col-md-4"><label class="form-label">Hình thức</label><select class="form-select" name="method" id="adminRequestMethod"><option value="cash" @selected(old('method', $transaction->method) === 'cash')>Tiền mặt</option><option value="managed_transfer" @selected(old('method', $transaction->method) === 'managed_transfer')>Chuyển khoản nội bộ</option><option value="bank_transfer" @selected(old('method', $transaction->method) === 'bank_transfer')>Chuyển khoản bên ngoài</option></select></div>
            <div class="col-12"><label class="form-label">Tiêu đề</label><input class="form-control" name="request_title" value="{{ old('request_title', $transaction->request_title) }}" required></div>
            <div class="col-12"><label class="form-label">Nội dung / lý do</label><textarea class="form-control" rows="3" name="note" required>{{ old('note', $transaction->note) }}</textarea></div>
            <div class="col-md-4"><label class="form-label">Tài khoản nội bộ</label><select class="form-select" name="destination_account_id"><option value="">— Chọn tài khoản —</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected((string)old('destination_account_id', $transaction->destination_account_id) === (string)$account->id)>{{ $account->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Người nhận bên ngoài</label><input class="form-control" name="external_recipient" value="{{ old('external_recipient', $transaction->external_recipient) }}"></div>
            <div class="col-md-4"><label class="form-label">Số tài khoản</label><input class="form-control" name="external_account_number" value="{{ old('external_account_number', $transaction->external_account_number) }}"></div>
            <div class="col-md-6"><label class="form-label">Ngân hàng</label><input class="form-control" name="external_bank_name" value="{{ old('external_bank_name', $transaction->external_bank_name) }}"></div>
            <div class="col-md-6"><label class="form-label">Chi nhánh</label><input class="form-control" name="external_bank_branch" value="{{ old('external_bank_branch', $transaction->external_bank_branch) }}"></div>
        </div></div>
        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center"><strong>Danh sách nội dung</strong><button class="btn btn-sm btn-outline-primary" type="button" id="adminAddRequestItem"><i class="bi bi-plus-lg me-1"></i>Thêm dòng</button></div><div class="table-responsive"><table class="table align-middle mb-0" id="adminRequestItems"><thead><tr><th>Nội dung</th><th style="width:120px">ĐVT</th><th style="width:140px">Số lượng</th><th style="width:180px">Đơn giá</th><th style="width:150px" class="text-end">Thành tiền</th><th style="width:50px"></th></tr></thead><tbody>@foreach($items as $index => $item)<tr><td><input class="form-control item-content" name="items[{{ $index }}][content]" value="{{ $item['content'] ?? '' }}" required></td><td><input class="form-control" name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? '' }}"></td><td><input type="number" min="0.01" step="any" class="form-control item-qty" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required></td><td><input type="number" min="0" step="any" class="form-control item-price" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? 0 }}" required></td><td class="item-total text-end fw-bold"></td><td><button type="button" class="btn btn-outline-danger remove-item"><i class="bi bi-x"></i></button></td></tr>@endforeach</tbody><tfoot><tr><th colspan="4" class="text-end">VAT</th><th><input type="number" min="0" step="any" class="form-control text-end" id="adminRequestVat" name="request_vat" value="{{ old('request_vat', $transaction->request_vat ?? 0) }}"></th><th></th></tr><tr><th colspan="4" class="text-end">Tổng cộng</th><th class="text-end fs-5 text-primary" id="adminRequestTotal"></th><th></th></tr></tfoot></table></div></div>
        <div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('admin.accounting.finance-requests.index') }}">Hủy</a><button class="btn btn-primary px-4">Lưu thay đổi</button></div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('adminRequestItems');
    const body = table.querySelector('tbody');
    const money = value => Math.round(Number(value) || 0).toLocaleString('vi-VN') + 'đ';
    const rows = () => [...body.querySelectorAll('tr')];
    function recalc() { let sum = 0; rows().forEach((row, index) => { row.querySelectorAll('input').forEach(input => input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`)); const total = Number(row.querySelector('.item-qty').value || 0) * Number(row.querySelector('.item-price').value || 0); sum += total; row.querySelector('.item-total').textContent = money(total); }); document.getElementById('adminRequestTotal').textContent = money(sum + Number(document.getElementById('adminRequestVat').value || 0)); }
    function bind(row) { row.querySelectorAll('.item-qty,.item-price').forEach(input => input.addEventListener('input', recalc)); row.querySelector('.remove-item').addEventListener('click', () => { if (rows().length > 1) row.remove(); recalc(); }); }
    rows().forEach(bind); document.getElementById('adminRequestVat').addEventListener('input', recalc);
    document.getElementById('adminAddRequestItem').addEventListener('click', () => { const row = rows().at(-1).cloneNode(true); row.querySelectorAll('input').forEach(input => input.value = input.classList.contains('item-qty') ? 1 : (input.classList.contains('item-price') ? 0 : '')); body.appendChild(row); bind(row); recalc(); });
    document.getElementById('useRoleLabel').addEventListener('click', () => { const select = document.getElementById('adminRequestSource'); document.getElementById('adminRequestDepartment').value = select.selectedOptions[0].dataset.label; });
    recalc();
});
</script>
@endsection
