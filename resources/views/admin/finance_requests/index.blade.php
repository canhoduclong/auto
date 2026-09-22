@extends('layouts.app')

@section('title', 'Quản trị phiếu yêu cầu')

@section('content')
@php
    $statusLabels = [
        \App\Models\Transaction::STATUS_PENDING_APPROVAL => ['Chờ duyệt', 'warning text-dark'],
        \App\Models\Transaction::STATUS_APPROVED_PENDING_COMPLETION => ['Đã duyệt - chờ hoàn thành', 'info text-dark'],
        \App\Models\Transaction::STATUS_APPROVED => ['Đã hoàn thành', 'success'],
        \App\Models\Transaction::STATUS_REJECTED => ['Từ chối', 'danger'],
    ];
@endphp
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div><h1 class="h3 mb-1">Quản trị phiếu yêu cầu</h1><div class="text-muted">Kiểm tra và hiệu chỉnh phiếu của tất cả bộ phận.</div></div>
        <span class="badge bg-light text-dark border">{{ number_format($requests->total()) }} phiếu</span>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4"><label class="form-label">Tìm kiếm</label><input class="form-control" name="search" value="{{ $search }}" placeholder="Mã phiếu, tiêu đề, người lập, bộ phận"></div>
            <div class="col-lg-3"><label class="form-label">Nguồn / vai trò</label><select class="form-select" name="source"><option value="all">Tất cả</option>@foreach($sources as $key => $label)<option value="{{ $key }}" @selected($source === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-lg-3"><label class="form-label">Trạng thái</label><select class="form-select" name="status"><option value="all">Tất cả</option>@foreach($statusLabels as $key => [$label])<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Lọc</button><a class="btn btn-outline-secondary" href="{{ route('admin.accounting.finance-requests.index') }}">Xóa lọc</a></div>
        </form>
    </div></div>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>#</th><th>Phiếu yêu cầu</th><th>Người lập</th><th>Vai trò / Bộ phận</th><th class="text-end">Số tiền</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr></thead>
        <tbody>@forelse($requests as $item)
            @php([$statusText, $statusClass] = $statusLabels[$item->status] ?? [$item->status, 'secondary'])
            <tr>
                <td class="fw-bold">#{{ $item->id }}</td>
                <td><div class="fw-semibold">{{ $item->request_title ?: 'Phiếu yêu cầu' }}</div><div class="small text-muted">{{ $item->request_form_type === \App\Models\Transaction::REQUEST_FORM_PAYMENT ? 'Đề nghị thanh toán' : 'Yêu cầu thu/chi' }}</div></td>
                <td>{{ $item->submitter?->name ?: '—' }}</td>
                <td><span class="badge bg-light text-dark border">{{ $sources[$item->request_source] ?? $item->request_source }}</span><div class="small mt-1">{{ $item->request_department ?: '—' }}</div></td>
                <td class="text-end fw-bold">{{ number_format((float) $item->amount, 0, ',', '.') }}đ</td>
                <td><span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span></td>
                <td>{{ $item->created_at?->copy()->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</td>
                <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('admin.accounting.finance-requests.print', $item) }}" title="In"><i class="bi bi-printer"></i></a> <a class="btn btn-sm btn-primary" href="{{ route('admin.accounting.finance-requests.edit', $item) }}"><i class="bi bi-pencil me-1"></i>Sửa</a></td>
            </tr>
        @empty<tr><td colspan="8" class="text-center text-muted py-5">Chưa có phiếu yêu cầu.</td></tr>@endforelse</tbody>
    </table></div><div class="card-footer bg-white">{{ $requests->links() }}</div></div>
</div>
@endsection
