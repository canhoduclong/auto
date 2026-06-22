@extends(accounting_layout())

@section('title', 'Sổ luân chuyển dòng tiền')
@section('subtitle', 'Mỗi dòng là một giao dịch, mỗi cột là một tài khoản bị tác động')

@section('accounting_content')
<style>
    .balance-value { font-variant-numeric:tabular-nums; white-space:nowrap; }
    .ledger-wrap { max-height:68vh; overflow:auto; }
    .ledger-table { font-size:13px; }
    .ledger-table th,.ledger-table td { padding:7px 9px; }
    .ledger-table thead th { position:sticky; top:0; z-index:3; }
    .history-head,.history-cell { position:sticky; left:0; min-width:240px; max-width:240px; }
    .history-head { z-index:5 !important; background:#f1f5f9 !important; }
    .history-cell { z-index:2; background:#fff; border-right:2px solid #e2e8f0; }
    .ledger-table tbody tr:hover .history-cell { background:#f8fafc; }
    .account-impact-head { min-width:132px; max-width:150px; text-align:right; background:#f8fafc !important; border-left:1px solid #e2e8f0; }
    .account-impact-head.selected { background:#dbeafe !important; color:#1d4ed8; }
    .account-impact-cell { min-width:132px; max-width:150px; text-align:right; border-left:1px solid #eef2f7; }
    .account-impact-cell.selected { background:#eff6ff; }
    .impact-empty { color:#cbd5e1; }
    .impact-balance { font-size:11px; color:#64748b; margin-top:2px; }
    .summary-strip { display:flex; flex-wrap:wrap; gap:8px 22px; align-items:center; padding:10px 14px; }
    @media(max-width:767.98px) { .history-head,.history-cell { min-width:190px; max-width:190px; } .account-impact-head,.account-impact-cell { min-width:118px; } }
</style>

@php
    $clearAccountQuery = request()->except(['account_id', 'page']);
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
    <div class="fw-bold"><i class="bi bi-table text-primary me-2"></i>Bảng biến động tài khoản</div>
    @if($accountId)
        <a href="{{ route(request()->route()->getName(), $clearAccountQuery) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-grid me-1"></i>Xem tất cả</a>
    @endif
</div>

<div class="acc-card mb-3"><div class="summary-strip">
    <span><span class="text-muted small">Tổng số dư:</span> <strong>{{ number_format($totalAccountBalance) }}đ</strong></span>
    <span><span class="text-muted small">Số tài khoản:</span> <strong>{{ $accounts->count() }}</strong></span>
    <span><span class="text-muted small">Luân chuyển nội bộ:</span> <strong class="text-primary">{{ number_format($internalTransferTotal) }}đ</strong></span>
    <span class="ms-auto small text-muted"><span class="text-success fw-bold">+ Tăng</span><span class="mx-2">·</span><span class="text-danger fw-bold">− Giảm</span></span>
</div></div>

<div class="acc-card mb-3"><div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Tài khoản biến động</label><select name="account_id" class="form-select"><option value="">Tất cả tài khoản</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected($accountId === (int)$account->id)>{{ $account->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Loại dòng tiền</label><select name="movement_scope" class="form-select"><option value="">Tất cả</option><option value="internal" @selected($movementScope === 'internal')>Luân chuyển nội bộ</option><option value="external" @selected($movementScope === 'external')>Thu/chi khác</option><option value="adjustment" @selected($movementScope === 'adjustment')>Điều chỉnh thủ công</option></select></div>
        <div class="col-md-2"><label class="form-label">Chiều biến động</label><select name="direction" class="form-select"><option value="">Tiền vào và ra</option><option value="in" @selected($direction === 'in')>Tiền vào</option><option value="out" @selected($direction === 'out')>Tiền ra</option></select></div>
        <div class="col-md-2"><label class="form-label">Từ ngày</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
        <div class="col-md-1 d-grid"><button class="btn btn-primary" title="Lọc"><i class="bi bi-funnel"></i></button></div>
    </form>
</div></div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div><span class="fw-bold">Nhật ký theo phiếu</span><span class="text-muted small ms-2">{{ number_format($ledgerRows->total()) }} phiếu / lần điều chỉnh</span></div>
</div>

<div class="acc-card"><div class="card-body p-0"><div class="ledger-wrap">
    <table class="table table-hover align-middle mb-0 ledger-table">
        <thead class="table-light">
            <tr>
                <th class="history-head">Lịch sử thay đổi</th>
                @foreach($accounts as $account)
                    <th class="account-impact-head {{ $accountId === (int)$account->id ? 'selected' : '' }}">
                        <div>{{ $account->name }}</div>
                        <div class="small fw-normal text-muted">{{ number_format((float)$account->balance) }}đ hiện tại</div>
                    </th>
                @endforeach
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($ledgerRows as $entry)
            <tr>
                <td class="history-cell">
                    <div class="d-flex justify-content-between gap-2"><strong>{{ $entry['source'] === 'transaction' ? 'Phiếu #' . $entry['source_id'] : 'Điều chỉnh #' . $entry['source_id'] }}</strong><span class="small text-muted text-nowrap">{{ optional($entry['occurred_at'])->format('d/m H:i') }}</span></div>
                    <div class="text-truncate mt-1" title="{{ $entry['request_title'] ?: $entry['type_label'] }}">{{ $entry['request_title'] ?: $entry['type_label'] }}</div>
                    <div class="small text-muted text-truncate">{{ $entry['performed_by'] }}@if($entry['note']) · {{ $entry['note'] }}@endif</div>
                </td>
                @foreach($accounts as $account)
                    @php $impact = $entry['movements_by_account']->get($account->id); @endphp
                    <td class="account-impact-cell {{ $accountId === (int)$account->id ? 'selected' : '' }}">
                        @if($impact)
                            <div class="fw-bold balance-value {{ $impact['direction'] === 'in' ? 'text-success' : 'text-danger' }}">
                                {{ $impact['direction'] === 'in' ? '+' : '-' }}{{ number_format($impact['amount']) }}đ
                            </div>
                            <div class="impact-balance">{{ number_format($impact['balance_before']) }} → {{ number_format($impact['balance_after']) }}đ</div>
                        @else
                            <span class="impact-empty">—</span>
                        @endif
                    </td>
                @endforeach
                <td>@if($entry['detail_url'])<a href="{{ $entry['detail_url'] }}" class="btn btn-sm btn-outline-primary" title="Xem phiếu"><i class="bi bi-eye"></i></a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="{{ $accounts->count() + 2 }}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Chưa có biến động phù hợp bộ lọc.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div></div>

<div class="mt-3">{{ $ledgerRows->links() }}</div>
@endsection
