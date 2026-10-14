@extends('layouts.ceo')
@section('title', 'Quy định nghiệp vụ nội bộ')
@section('subtitle', 'Cập nhật nội dung và quản lý xác nhận của người dùng')
@push('styles')
<style>
.policy-admin-card{border:1px solid #e2e8f0;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.05)}
.policy-status{display:flex;gap:12px;align-items:flex-start;padding:16px;border:1px solid #bae6fd;border-radius:12px;background:#f0f9ff}.policy-status .form-check-input{width:2.7rem;height:1.4rem}.policy-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.policy-kpi{padding:16px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc}.policy-kpi span{display:block;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase}.policy-kpi strong{display:block;margin-top:6px;font-size:24px}.policy-version{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}@media(max-width:767px){.policy-kpis{grid-template-columns:1fr}.policy-admin-card .card-body{padding:16px!important}}
</style>
@endpush
@section('content')
<div class="policy-kpis mb-3">
    <div class="policy-kpi"><span>Phiên bản hiện tại</span><strong class="policy-version fs-6">{{ $version }}</strong></div>
    <div class="policy-kpi"><span>Đã xác nhận</span><strong class="text-success">{{ $acceptedUsers }}</strong></div>
    <div class="policy-kpi"><span>Chưa xác nhận</span><strong class="text-warning">{{ max(0, $eligibleUsers - $acceptedUsers) }}</strong></div>
</div>

<form method="POST" action="{{ route('ceo.sales-tax-policy.update') }}" class="policy-admin-card mb-4">@csrf @method('PUT')
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-3"><div><h2 class="h5 mb-1">Nội dung áp dụng cho Sale, Leader và Manager</h2><p class="text-muted mb-0">Khi nội dung thay đổi, hệ thống tạo phiên bản mới và yêu cầu các tài khoản xác nhận lại.</p></div><a class="btn btn-outline-secondary align-self-start" href="{{ route('sales-tax-policy.show') }}" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>Xem trang người dùng</a></div>
        <div class="policy-status mb-4">
            <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1" id="policyEnabled" {{ old('enabled', $policyEnabled) ? 'checked' : '' }}></div>
            <label for="policyEnabled" class="flex-grow-1"><strong class="d-block">Yêu cầu người dùng xác nhận</strong><span class="text-muted small">Khi bật, tài khoản thuộc nhóm áp dụng chưa xác nhận phiên bản hiện tại sẽ thấy popup và bị chặn thao tác nghiệp vụ.</span></label>
            <span class="badge {{ $policyEnabled ? 'bg-success' : 'bg-secondary' }}">{{ $policyEnabled ? 'Đang bật' : 'Đang tắt' }}</span>
        </div>
        <div class="mb-3"><label for="policyTitle" class="form-label fw-bold">Tiêu đề quy định</label><input id="policyTitle" name="title" class="form-control form-control-lg fw-bold" maxlength="180" value="{{ old('title', $policyTitle) }}" required><div class="form-text">Tiêu đề này hiển thị trên popup xác nhận và trang nội dung dành cho người dùng.</div>@error('title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
        <label for="policyContent" class="form-label fw-bold">Nội dung quy định</label>
        <textarea id="policyContent" name="content" class="form-control task-description-editor" data-editor-height="560">{{ old('content', $policyContent) }}</textarea>
        @error('content')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        <div class="alert alert-warning mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Việc bấm lưu sau khi sửa nội dung sẽ làm phiên bản cũ hết hiệu lực xác nhận. Lịch sử xác nhận cũ vẫn được lưu để đối soát.</div>
    </div>
    <div class="card-footer bg-white border-top p-3 d-flex justify-content-end"><button class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Lưu nội dung và cấu hình</button></div>
</form>

<section class="policy-admin-card">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3"><div><h2 class="h5 mb-1">Người dùng đã xác nhận</h2><p class="text-muted mb-0">Danh sách của phiên bản <span class="policy-version">{{ $version }}</span></p></div><span class="badge bg-success-subtle text-success border border-success-subtle">{{ $acceptedUsers }} xác nhận</span></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>STT</th><th>Người dùng</th><th>Vai trò</th><th>Thời điểm xác nhận</th><th>Địa chỉ IP</th></tr></thead><tbody>
        @forelse($acceptances as $acceptance)<tr><td>{{ $acceptances->firstItem() + $loop->index }}</td><td><strong>{{ $acceptance->user?->name ?? 'Tài khoản đã xóa' }}</strong><br><small class="text-muted">{{ $acceptance->user?->email }}</small></td><td>{{ $acceptance->user?->roles->pluck('name')->join(', ') ?: '—' }}</td><td>{{ $acceptance->accepted_at?->format('d/m/Y H:i:s') }}</td><td><code>{{ $acceptance->ip_address ?: '—' }}</code></td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-person-check fs-3 d-block mb-2"></i>Chưa có người dùng xác nhận phiên bản này.</td></tr>@endforelse
        </tbody></table></div>
        @if($acceptances->hasPages())<div class="mt-3">{{ $acceptances->links() }}</div>@endif
    </div>
</section>
@include('task_assignments.partials.description-editor')
@endsection
