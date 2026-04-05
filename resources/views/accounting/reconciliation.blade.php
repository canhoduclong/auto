@extends('layouts.accounting')

@section('title', 'Doi Soat Don Hang')
@section('subtitle', 'Theo doi thanh toan du, thieu va can xac minh')

@section('accounting_content')
<div class="acc-kpi mb-3">
    <div class="item"><div class="label">Da thanh toan</div><div class="value text-success">{{ number_format($stats['paid']) }}</div></div>
    <div class="item"><div class="label">Chua thanh toan</div><div class="value text-danger">{{ number_format($stats['unpaid']) }}</div></div>
    <div class="item"><div class="label">Thanh toan thieu</div><div class="value text-warning">{{ number_format($stats['partial']) }}</div></div>
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
            <div class="d-flex align-items-end"><button class="btn btn-primary w-100">Loc</button></div>
            <div class="d-flex align-items-end"><span class="badge text-bg-light border w-100 text-start p-2">{{ $rangeLabel }}</span></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Ma don</th><th>Khach hang</th><th>Tong tien</th><th>Trang thai thanh toan</th><th>Trang thai giao hang</th><th>Kho</th><th>Ngay tao</th></tr></thead>
            <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->code }}</td>
                    <td>{{ $order->customer?->name ?? '-' }}</td>
                    <td class="fw-semibold">{{ number_format($order->total) }} d</td>
                    <td><span class="badge text-bg-light border">{{ $order->payment_status ?? 'can_xac_minh' }}</span></td>
                    <td>{{ $order->status }}</td>
                    <td>{{ $order->warehouse?->name ?? '-' }}</td>
                    <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Khong co du lieu doi soat.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $orders->links() }}
    </div>
</div>
@endsection
