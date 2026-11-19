@extends(accounting_layout())

@section('title', 'Thu chi Google Sheet')
@section('accounting_content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    @if($loadedAt)
        <span class="text-muted">Cập nhật {{ \Carbon\Carbon::parse($loadedAt)->format('d/m/Y H:i') }}</span>
    @endif
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ request()->fullUrlWithQuery(['cashflow_source' => 'system', 'page' => null]) }}" class="btn btn-outline-secondary btn-sm">Phiếu thu chi hệ thống</a>
        <form method="POST" action="{{ route('customer-debt.load') }}">
            @csrf
            <input type="hidden" name="source" value="google">
            <button class="btn btn-primary btn-sm">Cập nhật Google Sheet</button>
        </form>
    </div>
</div>
@foreach(['success' => 'success', 'error' => 'danger'] as $key => $color)
    @if(session($key))<div class="alert alert-{{ $color }}">{{ session($key) }}</div>@endif
@endforeach

<form class="d-flex flex-wrap gap-2 align-items-end mb-3" method="GET">
    <input type="hidden" name="cashflow_source" value="sheet">
    <input type="hidden" name="range" value="custom">
    <label>Tài khoản
        <select class="form-select" name="sheet_tab">
            <option value="">Tất cả tài khoản</option>
            @foreach($ledgers as $name => $ledger)
                <option value="{{ $name }}" @selected($tab === $name)>{{ $ledger['label'] }} · {{ $ledger['number'] }}</option>
            @endforeach
        </select>
    </label>
    <label>Từ ngày <input class="form-control" type="date" name="from_date" value="{{ $from->toDateString() }}"></label>
    <label>Đến ngày <input class="form-control" type="date" name="to_date" value="{{ $to->toDateString() }}"></label>
    <button class="btn btn-primary">Lọc</button>
</form>

<div class="acc-kpi mb-3">
    <div class="item"><div class="label">Thu trong kỳ</div><div class="value text-success">{{ number_format($incomeTotal, 0, ',', '.') }} đ</div></div>
    <div class="item"><div class="label">Chi trong kỳ</div><div class="value text-danger">{{ number_format($expenseTotal, 0, ',', '.') }} đ</div></div>
</div>

@if($ledgers->isNotEmpty())
<div class="acc-card mb-3"><div class="card-body table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Tài khoản</th><th>Tab nguồn</th><th class="text-end">Số dư đầu nguồn</th><th class="text-end">Số dư cuối nguồn</th></tr></thead>
        <tbody>
        @foreach($tab ? $ledgers->only([$tab]) : $ledgers as $name => $ledger)
            <tr><td>{{ $ledger['label'] }} · {{ $ledger['number'] }}</td><td>{{ $name }}</td>
                <td class="text-end">{{ number_format($ledger['opening'], 0, ',', '.') }} đ</td>
                <td class="text-end">{{ number_format($ledger['closing'], 0, ',', '.') }} đ</td></tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endif

<div class="acc-card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Ngày</th><th>Tài khoản</th><th>Loại</th><th>Khách hàng / NCC</th><th>Nội dung</th><th class="text-end">Thu</th><th class="text-end">Chi</th><th class="text-end">Còn lại</th></tr></thead>
        <tbody>
        @forelse($transactions as $row)
            <tr>
                <td class="text-nowrap">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                <td>{{ $ledgers[$row['tab']]['label'] }}<div class="small text-muted">{{ $row['tab'] }} · Dòng {{ $row['row'] }}</div></td>
                <td>{{ $row['category'] ?: '—' }}</td><td>{{ $row['counterparty'] ?: '—' }}</td><td>{{ $row['note'] }}</td>
                <td class="text-end text-nowrap">{{ $row['in'] ? number_format($row['in'], 0, ',', '.') : '—' }}</td>
                <td class="text-end text-nowrap">{{ $row['out'] ? number_format($row['out'], 0, ',', '.') : '—' }}</td>
                <td class="text-end text-nowrap">{{ number_format($row['balance'], 0, ',', '.') }}
                    @if($row['difference'] !== 0)<div class="small text-danger">Lệch {{ number_format($row['difference'], 0, ',', '.') }} đ so với Thu − Chi</div>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted">{{ $loadedAt ? 'Không có giao dịch trong kỳ.' : 'Bấm Cập nhật Google Sheet để lấy dữ liệu thu chi.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $transactions->links() }}
</div></div>
@endsection
