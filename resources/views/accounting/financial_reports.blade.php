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
@endsection
