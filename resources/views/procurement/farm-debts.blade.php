@extends('layouts.procurement')
@section('title', 'Công nợ trang trại')
@section('subtitle', 'Theo dõi số tiền đã trả, còn nợ và thời hạn thanh toán theo từng trang trại')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Tổng công nợ</div><div class="fs-3 fw-bold text-danger">{{ number_format($totalDebt) }}đ</div><div class="small">{{ $farmDebts->count() }} trang trại còn nợ</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Nợ quá hạn</div><div class="fs-3 fw-bold text-warning">{{ number_format($totalOverdue) }}đ</div><div class="small">Có ngày phải trả trước hôm nay</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Đã thanh toán trên các khoản còn nợ</div><div class="fs-3 fw-bold text-success">{{ number_format($totalPaid) }}đ</div><div class="small">Không bao gồm các khoản đã tất toán</div></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <form class="row g-2">
        <div class="col-lg-3"><label class="form-label">Tìm trang trại</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tên trại hoặc số điện thoại"></div>
        <div class="col-lg-2"><label class="form-label">Tình trạng hạn</label><select class="form-select" name="due_status"><option value="">Tất cả khoản nợ</option><option value="overdue" @selected(request('due_status') === 'overdue')>Đã quá hạn</option><option value="due_soon" @selected(request('due_status') === 'due_soon')>Đến hạn trong 7 ngày</option><option value="no_due" @selected(request('due_status') === 'no_due')>Chưa có ngày trả</option></select></div>
        <div class="col-lg-2"><label class="form-label">Từ ngày mua</label><input type="date" class="form-control" name="from_date" value="{{ request('from_date') }}"></div>
        <div class="col-lg-2"><label class="form-label">Đến ngày mua</label><input type="date" class="form-control" name="to_date" value="{{ request('to_date') }}"></div>
        <div class="col-lg-3 d-flex align-items-end gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Lọc</button><a href="{{ route('procurement.farm-debts.index') }}" class="btn btn-outline-secondary">Đặt lại</a></div>
    </form>
</div></div>

<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Trang trại</th><th class="text-center">Khoản còn nợ</th><th class="text-end">Tổng tiền mua</th><th class="text-end">Đã thanh toán</th><th class="text-end">Còn nợ</th><th class="text-end">Quá hạn</th><th>Hạn gần nhất</th><th></th></tr></thead>
    <tbody>
    @forelse($farmDebts as $debt)
        @php($farm = $debt['farm'])
        <tr>
            <td><div class="fw-semibold">{{ $farm?->name ?? 'Trang trại đã xóa' }}</div><div class="small text-muted">{{ $farm?->phone ?: '—' }} · {{ $farm?->address ?: 'Chưa có địa chỉ' }}</div></td>
            <td class="text-center"><span class="badge bg-secondary">{{ $debt['purchase_count'] }}</span></td>
            <td class="text-end">{{ number_format($debt['total_amount']) }}đ</td>
            <td class="text-end text-success">{{ number_format($debt['paid_amount']) }}đ</td>
            <td class="text-end fw-bold text-danger">{{ number_format($debt['remaining_amount']) }}đ</td>
            <td class="text-end {{ $debt['overdue_amount'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">{{ number_format($debt['overdue_amount']) }}đ</td>
            <td>@if($debt['nearest_due_date']){{ \Carbon\Carbon::parse($debt['nearest_due_date'])->format('d/m/Y') }}@else <span class="text-muted">Chưa đặt hạn</span>@endif</td>
            <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#farmDebt{{ $farm?->id ?? $loop->index }}"><i class="bi bi-eye me-1"></i>Chi tiết</button></td>
        </tr>
        <tr class="collapse" id="farmDebt{{ $farm?->id ?? $loop->index }}"><td colspan="8" class="bg-light p-3">
            <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Ngày/Mã</th><th>Số lượng</th><th class="text-end">Tổng tiền</th><th class="text-end">Đã trả</th><th class="text-end">Còn lại</th><th>Ngày phải trả</th><th>Phiếu yêu cầu</th></tr></thead><tbody>
            @foreach($debt['purchases'] as $purchase)<tr><td>{{ $purchase->purchased_at->format('d/m/Y') }}<div class="small fw-semibold">{{ $purchase->code }}</div></td><td>{{ number_format($purchase->quantity) }} con / {{ number_format($purchase->total_weight, 1) }}kg</td><td class="text-end">{{ number_format($purchase->total_amount) }}đ</td><td class="text-end text-success">{{ number_format($purchase->paid_amount) }}đ</td><td class="text-end fw-semibold text-danger">{{ number_format($purchase->remaining_amount) }}đ</td><td>@if($purchase->payment_due_date)<span class="{{ $purchase->payment_due_date->isBefore(today()) ? 'text-danger fw-semibold' : '' }}">{{ $purchase->payment_due_date->format('d/m/Y') }}</span>@else — @endif</td><td>@if($purchase->payment_transaction_id)<span class="badge bg-info text-dark">Phiếu #{{ $purchase->payment_transaction_id }}</span>@else<form method="POST" action="{{ route('procurement.purchases.request-payment', $purchase) }}">@csrf<button class="btn btn-sm btn-outline-danger">Tạo yêu cầu trả</button></form>@endif</td></tr>@endforeach
            </tbody></table></div>
        </td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-5">Không có công nợ trang trại phù hợp.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
