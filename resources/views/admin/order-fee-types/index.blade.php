@extends('layouts.admin')

@section('title', 'Quản trị phí bổ sung cho đơn')

@push('styles')
<style>
    .fee-admin-page { padding: 8px 0 40px; }
    .fee-admin-hero { padding: 22px 24px; border: 0; border-radius: 14px; color: #fff; background: linear-gradient(135deg, #172554, #1d4ed8); box-shadow: 0 12px 28px rgba(30, 64, 175, .18); }
    .fee-admin-layout { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 18px; align-items: start; }
    .fee-admin-card { overflow: hidden; border: 1px solid #dbe4ef; border-radius: 12px; background: #fff; box-shadow: 0 6px 18px rgba(15, 23, 42, .05); }
    .fee-admin-card-head { padding: 15px 18px; border-bottom: 1px solid #e8eef5; font-weight: 800; }
    .fee-admin-card-body { padding: 18px; }
    .fee-code { padding: 3px 7px; border-radius: 6px; color: #475569; background: #f1f5f9; font-family: monospace; font-size: .76rem; }
    .fee-kind { display: inline-flex; padding: 4px 8px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
    .fee-kind.charge { color: #166534; background: #dcfce7; }
    .fee-kind.discount { color: #9f1239; background: #ffe4e6; }
    @media (max-width: 991.98px) { .fee-admin-layout { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="container-fluid fee-admin-page">
    <div class="fee-admin-hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="text-uppercase small opacity-75 fw-semibold mb-1">Cấu hình đơn hàng</div>
            <h2 class="mb-1 text-white">Quản trị phí bổ sung</h2>
            <div class="opacity-75">Tạo các khoản cộng thêm hoặc giảm trừ để sử dụng trong yêu cầu điều chỉnh đơn.</div>
        </div>
        <div class="text-end"><div class="fs-2 fw-bold">{{ $feeTypes->count() }}</div><div class="small opacity-75">loại phí</div></div>
    </div>

    @if(session('success'))<div class="alert alert-success"><i class="ph-check-circle me-2"></i>{{ session('success') }}</div>@endif

    <div class="fee-admin-layout">
        <section class="fee-admin-card">
            <div class="fee-admin-card-head"><i class="ph-plus-circle me-2 text-primary"></i>Thêm loại phí mới</div>
            <div class="fee-admin-card-body">
                <form method="POST" action="{{ route('admin.order-fee-types.store') }}">
                    @csrf
                    <div class="mb-3"><label class="form-label fw-semibold">Tên khoản phí</label><input name="name" class="form-control" value="{{ old('name') }}" placeholder="Ví dụ: Phí đóng gói đặc biệt" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Mã phí</label><input name="code" class="form-control" value="{{ old('code') }}" placeholder="Tự tạo từ tên nếu để trống"><div class="form-text">Không dấu, dùng để nhận diện trong hệ thống.</div></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label fw-semibold">Cách tính</label><select name="calculation_type" class="form-select"><option value="fixed" @selected(old('calculation_type') === 'fixed')>Số tiền</option><option value="percent" @selected(old('calculation_type') === 'percent')>Phần trăm</option></select></div>
                        <div class="col-6"><label class="form-label fw-semibold">Tác động</label><select name="direction" class="form-select"><option value="charge" @selected(old('direction') === 'charge')>Cộng thêm</option><option value="discount" @selected(old('direction') === 'discount')>Giảm trừ</option></select></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7"><label class="form-label fw-semibold">Giá trị mặc định</label><input type="number" min="0" step="0.01" name="default_value" class="form-control" value="{{ old('default_value', 0) }}" required></div>
                        <div class="col-5"><label class="form-label fw-semibold">Thứ tự</label><input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', 100) }}" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Mô tả</label><textarea name="description" class="form-control" rows="3" placeholder="Mục đích và cách sử dụng...">{{ old('description') }}</textarea></div>
                    <div class="form-check form-switch mb-3"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="newFeeActive" checked><label class="form-check-label" for="newFeeActive">Cho phép sử dụng ngay</label></div>
                    <button class="btn btn-primary w-100"><i class="ph-plus me-1"></i>Thêm loại phí</button>
                </form>
            </div>
        </section>

        <section class="fee-admin-card">
            <div class="fee-admin-card-head d-flex justify-content-between align-items-center"><span><i class="ph-receipt me-2 text-primary"></i>Danh sách loại phí</span><span class="badge bg-light text-body border">{{ $feeTypes->where('is_active', true)->count() }} đang hoạt động</span></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Loại phí</th><th>Cách tính</th><th>Tác động</th><th class="text-end">Mặc định</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                    <tbody>
                    @forelse($feeTypes as $type)
                        <tr>
                            <td><div class="fw-bold">{{ $type->name }} @if($type->is_system)<span class="badge bg-light text-primary border ms-1">Hệ thống</span>@endif</div><div class="mt-1"><span class="fee-code">{{ $type->code }}</span></div>@if($type->description)<div class="small text-muted mt-1">{{ $type->description }}</div>@endif</td>
                            <td>{{ $type->calculation_type === 'percent' ? 'Phần trăm' : 'Số tiền' }}</td>
                            <td><span class="fee-kind {{ $type->direction }}">{{ $type->direction === 'discount' ? 'Giảm trừ' : 'Cộng thêm' }}</span></td>
                            <td class="text-end fw-semibold">{{ $type->calculation_type === 'percent' ? rtrim(rtrim(number_format((float) $type->default_value, 2, '.', ''), '0'), '.').'%' : number_format((float) $type->default_value, 0, ',', '.').'đ' }}</td>
                            <td><span class="badge {{ $type->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $type->is_active ? 'Đang dùng' : 'Ngừng dùng' }}</span>@if($type->order_fees_count)<div class="small text-muted mt-1">{{ $type->order_fees_count }} đơn đã áp dụng</div>@endif</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editFeeType{{ $type->id }}"><i class="ph-pencil"></i></button>
                                    <form method="POST" action="{{ route('admin.order-fee-types.toggle', $type) }}">@csrf @method('PATCH')<button class="btn btn-sm {{ $type->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $type->is_active ? 'Ngừng sử dụng' : 'Bật sử dụng' }}"><i class="ph {{ $type->is_active ? 'ph-pause' : 'ph-play' }}"></i></button></form>
                                    <form method="POST" action="{{ route('admin.order-fee-types.destroy', $type) }}" onsubmit="return confirm('Xóa loại phí {{ addslashes($type->name) }}? Loại phí đã có lịch sử sẽ chỉ được ngừng sử dụng.');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm"><i class="ph-trash"></i></button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Chưa có loại phí nào.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

@foreach($feeTypes as $type)
<div class="modal fade" id="editFeeType{{ $type->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="POST" action="{{ route('admin.order-fee-types.update', $type) }}">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Chỉnh sửa {{ $type->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-7"><label class="form-label fw-semibold">Tên khoản phí</label><input name="name" class="form-control" value="{{ $type->name }}" required></div>
                <div class="col-md-5"><label class="form-label fw-semibold">Mã phí</label><input name="code" class="form-control" value="{{ $type->code }}" {{ ($type->is_system || $type->order_fees_count > 0) ? 'readonly' : '' }} required></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Cách tính</label><select name="calculation_type" class="form-select"><option value="fixed" @selected($type->calculation_type === 'fixed')>Số tiền</option><option value="percent" @selected($type->calculation_type === 'percent')>Phần trăm</option></select></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Tác động</label><select name="direction" class="form-select"><option value="charge" @selected($type->direction === 'charge')>Cộng thêm</option><option value="discount" @selected($type->direction === 'discount')>Giảm trừ</option></select></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Giá trị mặc định</label><input type="number" min="0" step="0.01" name="default_value" class="form-control" value="{{ (float) $type->default_value }}" required></div>
                <div class="col-md-4"><label class="form-label fw-semibold">Thứ tự</label><input type="number" min="0" name="sort_order" class="form-control" value="{{ $type->sort_order }}" required></div>
                <div class="col-md-8"><label class="form-label fw-semibold">Mô tả</label><input name="description" class="form-control" value="{{ $type->description }}"></div>
                <div class="col-12"><div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="feeActive{{ $type->id }}" @checked($type->is_active)><label class="form-check-label" for="feeActive{{ $type->id }}">Đang hoạt động</label></div></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu thay đổi</button></div>
        </form>
    </div></div>
</div>
@endforeach
@endsection
