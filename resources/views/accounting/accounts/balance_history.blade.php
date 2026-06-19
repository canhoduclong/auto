@extends(accounting_layout())

@section('title', 'Lịch sử thay đổi số dư')
@section('subtitle', 'Biến động tài khoản từ thu chi, nạp tiền và rút tiền')

@section('accounting_content')
@php $netMovement = $totalIn - $totalOut; @endphp

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Tổng tiền vào</div><div class="fs-4 fw-bold text-success">+{{ number_format($totalIn) }}đ</div></div></div></div>
    <div class="col-md-4"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Tổng tiền ra</div><div class="fs-4 fw-bold text-danger">-{{ number_format($totalOut) }}đ</div></div></div></div>
    <div class="col-md-4"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Biến động ròng</div><div class="fs-4 fw-bold {{ $netMovement >= 0 ? 'text-success' : 'text-danger' }}">{{ $netMovement >= 0 ? '+' : '' }}{{ number_format($netMovement) }}đ</div></div></div></div>
</div>

<div class="acc-card mb-3"><div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Tài khoản</label><select name="account_id" class="form-select"><option value="">Tất cả tài khoản</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected($accountId === (int) $account->id)>{{ $account->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Chiều biến động</label><select name="direction" class="form-select"><option value="">Tất cả</option><option value="in" @selected($direction === 'in')>Tiền vào</option><option value="out" @selected($direction === 'out')>Tiền ra</option></select></div>
        <div class="col-md-2"><label class="form-label">Nguồn</label><select name="source" class="form-select"><option value="">Tất cả</option><option value="transaction" @selected($source === 'transaction')>Thu/chi</option><option value="adjustment" @selected($source === 'adjustment')>Nạp/rút thủ công</option></select></div>
        <div class="col-md-2"><label class="form-label">Từ ngày</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100" title="Lọc"><i class="bi bi-funnel"></i></button></div>
    </form>
</div></div>

<div class="acc-card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Thời gian</th><th>Tài khoản</th><th>Nguồn phát sinh</th><th>Người thực hiện</th><th class="text-end">Biến động</th><th class="text-end">Số dư trước</th><th class="text-end">Số dư sau</th><th>Ghi chú</th><th></th></tr></thead>
        <tbody>
        @forelse($movements as $movement)
            <tr>
                <td class="text-nowrap"><div class="fw-semibold">{{ optional($movement['occurred_at'])->format('d/m/Y') }}</div><div class="small text-muted">{{ optional($movement['occurred_at'])->format('H:i:s') }}</div></td>
                <td><div class="fw-semibold">{{ $movement['account']?->name ?? 'Tài khoản đã xóa' }}</div><div class="small text-muted">{{ $movement['account']?->type === 'cash' ? 'Tiền mặt' : 'Ngân hàng' }}</div></td>
                <td><span class="badge {{ $movement['source'] === 'adjustment' ? 'text-bg-secondary' : 'text-bg-primary' }}">{{ $movement['type_label'] }}</span></td>
                <td>{{ $movement['performed_by'] }}</td>
                <td class="text-end fw-bold {{ $movement['direction'] === 'in' ? 'text-success' : 'text-danger' }}">{{ $movement['direction'] === 'in' ? '+' : '-' }}{{ number_format($movement['amount']) }}đ</td>
                <td class="text-end text-muted">{{ number_format($movement['balance_before']) }}đ</td>
                <td class="text-end fw-semibold">{{ number_format($movement['balance_after']) }}đ</td>
                <td class="small text-muted">{{ $movement['note'] ?: '—' }}</td>
                <td>@if($movement['detail_url'])<a href="{{ $movement['detail_url'] }}" class="btn btn-sm btn-outline-primary" title="Xem giao dịch"><i class="bi bi-eye"></i></a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Chưa có biến động tài khoản phù hợp.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div></div>

<div class="mt-3">{{ $movements->links() }}</div>
@endsection
