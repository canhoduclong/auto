@extends('layouts.accounting')

@section('title', 'Bao Cao Tai Chinh Noi Bo')
@section('subtitle', 'Doanh thu, thu thuc nhan, chi phi va loi nhuan tam tinh')

@section('accounting_content')
<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="acc-filter">
            <div>
                <label class="form-label">Chu ky</label>
                <select class="form-select" name="range">
                    <option value="day" {{ request('range') === 'day' ? 'selected' : '' }}>Ngay</option>
                    <option value="week" {{ request('range') === 'week' ? 'selected' : '' }}>Tuan</option>
                    <option value="month" {{ request('range', 'month') === 'month' ? 'selected' : '' }}>Thang</option>
                    <option value="year" {{ request('range') === 'year' ? 'selected' : '' }}>Nam</option>
                    <option value="custom" {{ request('range') === 'custom' ? 'selected' : '' }}>Khoang</option>
                </select>
            </div>
            <div><label class="form-label">Tu ngay</label><input class="form-control" type="date" name="from_date" value="{{ request('from_date', $from->format('Y-m-d')) }}"></div>
            <div><label class="form-label">Den ngay</label><input class="form-control" type="date" name="to_date" value="{{ request('to_date', $to->format('Y-m-d')) }}"></div>
            <div class="d-flex align-items-end"><button class="btn btn-primary w-100">Loc</button></div>
            <div class="d-flex align-items-end"><span class="badge text-bg-light border w-100 text-start p-2">{{ $rangeLabel }}</span></div>
        </form>
    </div>
</div>

<div class="acc-kpi mb-3">
    <div class="item"><div class="label">Tong doanh thu</div><div class="value text-primary">{{ number_format($revenue) }} d</div></div>
    <div class="item"><div class="label">Tong thu thuc nhan</div><div class="value text-success">{{ number_format($received) }} d</div></div>
    <div class="item"><div class="label">Tong chi phi</div><div class="value text-danger">{{ number_format($cost) }} d</div></div>
    <div class="item"><div class="label">Loi nhuan tam tinh</div><div class="value">{{ number_format($profit) }} d</div></div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Ngay</th><th>Thu</th><th>Chi</th><th>Chenh lech</th></tr></thead>
            <tbody>
            @forelse($series as $row)
                @php $delta = (float) $row->income - (float) $row->expense; @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->day_key)->format('d/m/Y') }}</td>
                    <td class="text-success">{{ number_format((float)$row->income) }} d</td>
                    <td class="text-danger">{{ number_format((float)$row->expense) }} d</td>
                    <td class="fw-semibold">{{ number_format($delta) }} d</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Khong co du lieu bao cao.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{-- Account balances --}}
@php
    $accountList = \App\Models\Account::active()->orderBy('name')->get();
@endphp
@if($accountList->isNotEmpty())
<div class="acc-card mt-3">
    <div class="card-body">
        <div class="fw-bold mb-3 fs-6"><i class="bi bi-wallet2 me-2 text-primary"></i>Số dư tài khoản hiện tại</div>
        <div class="row g-2">
            @foreach($accountList as $acc)
                @php $low = $acc->isLowBalance(); @endphp
                <div class="col-sm-6 col-md-4">
                    <div class="border rounded p-3 {{ $low ? 'border-danger bg-danger bg-opacity-10' : 'bg-light' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold small">{{ $acc->name }}</div>
                                <div class="text-muted" style="font-size:11px">{{ $acc->type === 'cash' ? 'Tiền mặt' : 'Ngân hàng' }}</div>
                            </div>
                            @if($low)<i class="bi bi-exclamation-triangle-fill text-danger"></i>@endif
                        </div>
                        <div class="fw-bold fs-6 mt-1 {{ $low ? 'text-danger' : 'text-success' }}">
                            {{ number_format((float)$acc->balance) }}đ
                        </div>
                        @if($low)
                            <div class="text-danger" style="font-size:10px">Dưới ngưỡng {{ number_format((float)$acc->warning_threshold) }}đ</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 text-end text-muted small">
            Tổng số dư: <strong class="text-dark">{{ number_format($accountList->sum('balance')) }}đ</strong>
        </div>
    </div>
</div>
@endif

{{-- Transaction by category --}}
@php
    $catStats = \App\Models\Transaction::query()
        ->with('transactionCategory:id,code,name')
        ->whereBetween('created_at', [$from, $to])
        ->whereNotNull('transaction_category_id')
        ->selectRaw('transaction_category_id, COUNT(*) as total_count, SUM(amount) as total_amount')
        ->groupBy('transaction_category_id')
        ->orderByDesc('total_amount')
        ->get();
@endphp
@if($catStats->isNotEmpty())
<div class="acc-card mt-3">
    <div class="card-body">
        <div class="fw-bold mb-3 fs-6"><i class="bi bi-grid-3x3-gap me-2 text-info"></i>Thống kê theo danh mục giao dịch</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã</th><th>Danh mục</th>
                        <th class="text-center">Số GD</th>
                        <th class="text-end">Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($catStats as $cs)
                    <tr>
                        <td><span class="badge bg-primary">{{ $cs->transactionCategory?->code ?? '?' }}</span></td>
                        <td>{{ $cs->transactionCategory?->name ?? 'Không rõ' }}</td>
                        <td class="text-center">{{ number_format($cs->total_count) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float)$cs->total_amount) }}đ</td>
                    </tr>
                @endforeach
                    <tr class="table-light fw-bold">
                        <td colspan="2">Tổng</td>
                        <td class="text-center">{{ number_format($catStats->sum('total_count')) }}</td>
                        <td class="text-end">{{ number_format((float)$catStats->sum('total_amount')) }}đ</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
