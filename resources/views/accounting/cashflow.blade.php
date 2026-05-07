@extends('layouts.accounting')

@section('title', 'Quan Ly Thu Chi')
@section('subtitle', 'Phieu thu, phieu chi va luong tien phat sinh')

@section('accounting_content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <a href="{{ route('accounting.transactions.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Tao giao dich
    </a>
</div>
<div class="acc-kpi mb-3">
    <div class="item"><div class="label">Tong thu</div><div class="value text-success">{{ number_format($incomeTotal) }} d</div></div>
    <div class="item"><div class="label">Tong chi</div><div class="value text-danger">{{ number_format($expenseTotal) }} d</div></div>
    <div class="item"><div class="label">Can doi</div><div class="value">{{ number_format($incomeTotal - $expenseTotal) }} d</div></div>
</div>

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
            <div>
                <label class="form-label">Loai</label>
                <select class="form-select" name="type">
                    <option value="">Tat ca</option>
                    <option value="payment" {{ $type === 'payment' ? 'selected' : '' }}>Thu (payment)</option>
                    <option value="refund" {{ $type === 'refund' ? 'selected' : '' }}>Chi (refund)</option>
                    <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Chi (expense)</option>
                </select>
            </div>
            <div class="d-flex align-items-end"><button class="btn btn-primary w-100">Loc</button></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Loai</th><th>So tien</th><th>Noi dung</th><th>Don hang</th><th>Khach hang</th><th>Ngay tao</th></tr></thead>
            <tbody>
            @forelse($transactions as $tx)
                <tr>
                    <td><span class="badge text-bg-light border">{{ $tx->type }}</span></td>
                    <td class="fw-semibold">{{ number_format($tx->amount) }} d</td>
                    <td>{{ $tx->note ?? '-' }}</td>
                    <td>{{ $tx->order?->code ?? '-' }}</td>
                    <td>{{ $tx->customer?->name ?? '-' }}</td>
                    <td>{{ optional($tx->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">Khong co giao dich.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $transactions->links() }}
    </div>
</div>
@endsection
