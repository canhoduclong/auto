@extends('layouts.ceo')

@section('title', 'Báo cáo tài chính')
@section('subtitle', 'Doanh thu, thu thực nhận, chi phí và lợi nhuận tạm tính')

@section('content')
{{-- Filter --}}
<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">Chu kỳ</label>
                <select class="form-select form-select-sm" name="range">
                    <option value="day"    {{ request('range') === 'day'    ? 'selected' : '' }}>Ngày</option>
                    <option value="week"   {{ request('range') === 'week'   ? 'selected' : '' }}>Tuần</option>
                    <option value="month"  {{ request('range', 'month') === 'month' ? 'selected' : '' }}>Tháng</option>
                    <option value="year"   {{ request('range') === 'year'   ? 'selected' : '' }}>Năm</option>
                    <option value="custom" {{ request('range') === 'custom' ? 'selected' : '' }}>Khoảng</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Từ ngày</label>
                <input class="form-control form-control-sm" type="date" name="from_date" value="{{ request('from_date', $from->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Đến ngày</label>
                <input class="form-control form-control-sm" type="date" name="to_date" value="{{ request('to_date', $to->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Tài khoản</label>
                <select class="form-select form-select-sm" name="account_id">
                    <option value="">Tất cả tài khoản</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ (int)$accountFilterId === $acc->id ? 'selected' : '' }}>
                            {{ $acc->name }} ({{ $acc->type === 'cash' ? 'Tiền mặt' : 'Ngân hàng' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary btn-sm w-100">Lọc</button>
            </div>
            <div class="col-md-2">
                <span class="badge text-bg-light border w-100 text-start p-2">{{ $rangeLabel }}</span>
            </div>
        </form>
    </div>
</div>

{{-- KPI cards --}}
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Tổng doanh thu</div>
                <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($revenue) }}đ</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Tổng thu thực nhận</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ number_format($received) }}đ</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Tổng chi phí</div>
                <div class="fs-4 fw-bold text-danger mt-1">{{ number_format($cost) }}đ</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Lợi nhuận tạm tính</div>
                <div class="fs-4 fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }} mt-1">{{ number_format($profit) }}đ</div>
            </div>
        </div>
    </div>
</div>

{{-- Daily income/expense table --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-table me-2 text-primary"></i>Chi tiết thu / chi theo ngày
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ngày</th>
                    <th class="text-end text-success">Thu</th>
                    <th class="text-end text-danger">Chi</th>
                    <th class="text-end">Chênh lệch</th>
                </tr>
            </thead>
            <tbody>
            @forelse($series as $row)
                @php $delta = (float)$row->income - (float)$row->expense; @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->day_key)->format('d/m/Y') }}</td>
                    <td class="text-end text-success">{{ number_format((float)$row->income) }}đ</td>
                    <td class="text-end text-danger">{{ number_format((float)$row->expense) }}đ</td>
                    <td class="text-end fw-semibold {{ $delta >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($delta) }}đ</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-3">Không có dữ liệu báo cáo.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Account balances --}}
@php $accountList = \App\Models\Account::active()->orderBy('name')->get(); @endphp
@if($accountList->isNotEmpty())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-wallet2 me-2 text-primary"></i>Số dư tài khoản hiện tại
    </div>
    <div class="card-body">
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
@php $catStatsList = $catStats ?? collect(); @endphp
@if($catStatsList->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-grid-3x3-gap me-2 text-info"></i>Thống kê theo danh mục giao dịch
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã</th>
                        <th>Danh mục</th>
                        <th class="text-center">Số GD</th>
                        <th>Khách hàng</th>
                        <th>Tài khoản</th>
                        <th class="text-end">Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($catStatsList as $cs)
                    <tr>
                        <td><span class="badge bg-primary">{{ $cs->transactionCategory?->code ?? '?' }}</span></td>
                        <td>{{ $cs->transactionCategory?->name ?? 'Không rõ' }}</td>
                        <td class="text-center">{{ number_format($cs->total_count) }}</td>
                        <td>
                            @if($cs->customers->isNotEmpty())
                                @foreach($cs->customers as $cust)
                                    <span class="badge bg-info text-dark">{{ $cust['name'] }}</span>
                                @endforeach
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            @if($cs->accounts->isNotEmpty())
                                @foreach($cs->accounts as $acc)
                                    <span class="badge bg-warning text-dark">{{ $acc['name'] }} ({{ $acc['type'] === 'cash' ? 'TM' : 'NH' }})</span>
                                @endforeach
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">{{ number_format((float)$cs->total_amount) }}đ</td>
                    </tr>
                @endforeach
                    <tr class="table-light fw-bold">
                        <td colspan="2">Tổng</td>
                        <td class="text-center">{{ number_format($catStatsList->sum('total_count')) }}</td>
                        <td colspan="2"></td>
                        <td class="text-end">{{ number_format((float)$catStatsList->sum('total_amount')) }}đ</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
