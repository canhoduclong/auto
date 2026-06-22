@extends(accounting_layout())

@section('title', 'Sổ luân chuyển dòng tiền')
@section('subtitle', 'Theo dõi tiền di chuyển giữa các tài khoản và thanh toán ra bên ngoài')

@section('accounting_content')
<style>
    .balance-account-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px; }
    .balance-account-card { display:block; padding:14px; border:1px solid #e2e8f0; border-radius:10px; color:inherit; text-decoration:none; background:#fff; transition:.15s ease; }
    .balance-account-card:hover,.balance-account-card.active { border-color:var(--bs-primary); box-shadow:0 5px 18px rgba(15,23,42,.08); transform:translateY(-1px); }
    .balance-account-card.active { background:#eff6ff; }
    .flow-route { display:flex; align-items:center; gap:9px; min-width:290px; }
    .flow-node { min-width:105px; max-width:170px; }
    .flow-node .node-name { font-weight:700; color:#0f172a; line-height:1.25; }
    .flow-arrow { display:flex; flex-direction:column; align-items:center; color:#64748b; flex:0 0 auto; }
    .flow-arrow i { font-size:20px; line-height:1; }
    .movement-row-in { box-shadow:inset 3px 0 #22c55e; }
    .movement-row-out { box-shadow:inset 3px 0 #ef4444; }
    .balance-value { font-variant-numeric:tabular-nums; white-space:nowrap; }
    @media(max-width:767.98px) { .balance-account-grid { grid-template-columns:1fr 1fr; } .flow-route { min-width:245px; } }
</style>

@php
    $clearAccountQuery = request()->except(['account_id', 'page']);
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h5 class="fw-bold mb-1"><i class="bi bi-arrow-left-right text-primary me-2"></i>Số dư các tài khoản</h5>
        <div class="text-muted small">Bấm vào một tài khoản để xem riêng toàn bộ dòng tiền vào và ra.</div>
    </div>
    @if($accountId)
        <a href="{{ route(request()->route()->getName(), $clearAccountQuery) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-grid me-1"></i>Xem tất cả</a>
    @endif
</div>

<div class="balance-account-grid mb-3">
    @foreach($accounts as $account)
        <a class="balance-account-card {{ $accountId === (int)$account->id ? 'active' : '' }}" href="{{ route(request()->route()->getName(), array_merge(request()->except('page'), ['account_id' => $account->id])) }}">
            <div class="d-flex justify-content-between gap-2 mb-2">
                <div class="fw-semibold text-truncate">{{ $account->name }}</div>
                <i class="bi {{ $account->type === 'cash' ? 'bi-cash-stack text-success' : 'bi-bank text-primary' }}"></i>
            </div>
            <div class="fs-5 fw-bold balance-value {{ (float)$account->balance < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float)$account->balance) }}đ</div>
            <div class="small text-muted mt-1">{{ $account->type === 'cash' ? 'Tiền mặt' : 'Tài khoản ngân hàng' }}{{ !$account->is_active ? ' · Ngừng hoạt động' : '' }}</div>
        </a>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Tổng số dư hệ thống</div><div class="fs-4 fw-bold">{{ number_format($totalAccountBalance) }}đ</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Tiền vào từ bên ngoài</div><div class="fs-4 fw-bold text-success">+{{ number_format($externalIn) }}đ</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Đã trả ra bên ngoài</div><div class="fs-4 fw-bold text-danger">-{{ number_format($externalOut) }}đ</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Luân chuyển nội bộ</div><div class="fs-4 fw-bold text-primary">{{ number_format($internalTransferTotal) }}đ</div><div class="small text-muted">Không làm đổi tổng tiền hệ thống</div></div></div></div>
</div>

<div class="acc-card mb-3"><div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Tài khoản biến động</label><select name="account_id" class="form-select"><option value="">Tất cả tài khoản</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected($accountId === (int)$account->id)>{{ $account->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Loại dòng tiền</label><select name="movement_scope" class="form-select"><option value="">Tất cả</option><option value="internal" @selected($movementScope === 'internal')>Luân chuyển nội bộ</option><option value="external" @selected($movementScope === 'external')>Ra/vào bên ngoài</option><option value="adjustment" @selected($movementScope === 'adjustment')>Điều chỉnh thủ công</option></select></div>
        <div class="col-md-2"><label class="form-label">Chiều biến động</label><select name="direction" class="form-select"><option value="">Tiền vào và ra</option><option value="in" @selected($direction === 'in')>Tiền vào</option><option value="out" @selected($direction === 'out')>Tiền ra</option></select></div>
        <div class="col-md-2"><label class="form-label">Từ ngày</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
        <div class="col-md-1 d-grid"><button class="btn btn-primary" title="Lọc"><i class="bi bi-funnel"></i></button></div>
    </form>
</div></div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div><span class="fw-bold">Chi tiết biến động</span><span class="text-muted small ms-2">{{ number_format($movements->total()) }} dòng</span></div>
    <div class="small text-muted"><span class="text-success me-3"><i class="bi bi-arrow-down-left"></i> Tiền vào</span><span class="text-danger"><i class="bi bi-arrow-up-right"></i> Tiền ra</span></div>
</div>

<div class="acc-card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Phiếu / Thời gian</th><th>Luồng tiền</th><th>Tài khoản ghi nhận</th><th class="text-end">Biến động</th><th class="text-end">Số dư trước</th><th class="text-end">Số dư sau</th><th>Người thực hiện</th><th></th></tr></thead>
        <tbody>
        @forelse($movements as $movement)
            <tr class="{{ $movement['direction'] === 'in' ? 'movement-row-in' : 'movement-row-out' }}">
                <td class="text-nowrap">
                    <div class="fw-semibold">{{ $movement['source'] === 'transaction' ? 'Phiếu #' . $movement['source_id'] : 'Điều chỉnh #' . $movement['source_id'] }}</div>
                    <div class="small text-muted">{{ optional($movement['occurred_at'])->format('d/m/Y H:i') }}</div>
                </td>
                <td>
                    <div class="flow-route">
                        <div class="flow-node"><div class="node-name">{{ $movement['from_name'] }}</div><div class="small text-muted">{{ $movement['movement_scope'] === 'internal' ? 'Tài khoản nguồn' : ($movement['direction'] === 'in' ? 'Nguồn tiền' : 'Tài khoản nguồn') }}</div></div>
                        <div class="flow-arrow"><i class="bi bi-arrow-right"></i><span class="badge {{ $movement['movement_scope'] === 'internal' ? 'text-bg-primary' : ($movement['movement_scope'] === 'adjustment' ? 'text-bg-secondary' : 'text-bg-light border text-dark') }}">{{ $movement['movement_scope'] === 'internal' ? 'Nội bộ' : ($movement['movement_scope'] === 'adjustment' ? 'Thủ công' : 'Bên ngoài') }}</span></div>
                        <div class="flow-node"><div class="node-name">{{ $movement['to_name'] }}</div><div class="small text-muted">Nơi nhận tiền</div></div>
                    </div>
                    <div class="small mt-1"><span class="text-muted">{{ $movement['type_label'] }}</span>@if($movement['request_title']) · {{ $movement['request_title'] }}@endif</div>
                </td>
                <td><div class="fw-semibold">{{ $movement['account']?->name ?? 'Tài khoản đã xóa' }}</div><span class="badge {{ $movement['direction'] === 'in' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $movement['direction'] === 'in' ? 'Ghi tăng' : 'Ghi giảm' }}</span></td>
                <td class="text-end fw-bold balance-value {{ $movement['direction'] === 'in' ? 'text-success' : 'text-danger' }}">{{ $movement['direction'] === 'in' ? '+' : '-' }}{{ number_format($movement['amount']) }}đ</td>
                <td class="text-end text-muted balance-value">{{ number_format($movement['balance_before']) }}đ</td>
                <td class="text-end fw-bold balance-value">{{ number_format($movement['balance_after']) }}đ</td>
                <td><div>{{ $movement['performed_by'] }}</div>@if($movement['note'])<div class="small text-muted text-truncate" style="max-width:190px" title="{{ $movement['note'] }}">{{ $movement['note'] }}</div>@endif</td>
                <td>@if($movement['detail_url'])<a href="{{ $movement['detail_url'] }}" class="btn btn-sm btn-outline-primary" title="Xem phiếu"><i class="bi bi-eye"></i></a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Chưa có biến động phù hợp bộ lọc.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div></div>

<div class="mt-3">{{ $movements->links() }}</div>
@endsection
