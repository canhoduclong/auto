@extends(accounting_layout())

@section('title', 'Dashboard Ke Toan')
@section('subtitle', 'Tong quan cong no, thu chi va thanh toan')

@section('accounting_content')
@include('layouts.partials.department_broadcasts')

<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="acc-filter">
            <div>
                <label class="form-label">Chu ky</label>
                <select class="form-select" name="range">
                    <option value="day" {{ request('range') === 'day' ? 'selected' : '' }}>Theo ngay</option>
                    <option value="week" {{ request('range') === 'week' ? 'selected' : '' }}>Theo tuan</option>
                    <option value="month" {{ request('range', 'month') === 'month' ? 'selected' : '' }}>Theo thang</option>
                    <option value="year" {{ request('range') === 'year' ? 'selected' : '' }}>Theo nam</option>
                    <option value="custom" {{ request('range') === 'custom' ? 'selected' : '' }}>Khoang thoi gian</option>
                </select>
            </div>
            <div>
                <label class="form-label">Tu ngay</label>
                <input type="date" class="form-control" name="from_date" value="{{ request('from_date', $from->format('Y-m-d')) }}">
            </div>
            <div>
                <label class="form-label">Den ngay</label>
                <input type="date" class="form-control" name="to_date" value="{{ request('to_date', $to->format('Y-m-d')) }}">
            </div>
            <div class="d-flex align-items-end">
                <button class="btn btn-primary w-100">Loc du lieu</button>
            </div>
            <div class="d-flex align-items-end">
                <span class="badge text-bg-light border w-100 text-start p-2">{{ $rangeLabel }}: {{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</span>
            </div>
        </form>
    </div>
</div>

<div class="acc-kpi mb-3">
    <div class="item"><div class="label">Tong cong no phai thu</div><div class="value text-danger">{{ number_format($cards['receivable_total'] ?? 0) }} d</div></div>
    <div class="item"><div class="label">Tong cong no phai tra</div><div class="value text-warning">{{ number_format($cards['payable_total'] ?? 0) }} d</div></div>
    <div class="item"><div class="label">Tong thu hom nay</div><div class="value text-success">{{ number_format($cards['today_income'] ?? 0) }} d</div></div>
    <div class="item"><div class="label">Tong chi hom nay</div><div class="value text-primary">{{ number_format($cards['today_expense'] ?? 0) }} d</div></div>
    <div class="item"><div class="label">Don chua thanh toan</div><div class="value">{{ number_format($cards['unpaid_orders'] ?? 0) }}</div></div>
    <div class="item"><div class="label">Don qua han thanh toan</div><div class="value text-danger">{{ number_format($cards['overdue_orders'] ?? 0) }}</div></div>
</div>
@endsection
