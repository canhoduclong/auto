@extends('layouts.shipper')

@section('title', 'Thống kê giao hàng')
@section('subtitle', 'Số đơn đã giao theo khách hàng và ngày')

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control">
            </div>
            <div class="col-md-5">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="form-control">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Lọc</button>
            </div>
        </form>
        <div class="small text-muted mt-2">Khoảng lọc tối đa 32 ngày.</div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 text-center">
            <thead class="table-light">
                <tr>
                    <th class="text-start text-nowrap">Tên khách hàng</th>
                    @foreach($dates as $date)
                        <th class="text-nowrap">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</th>
                    @endforeach
                    <th>Tổng</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="text-start fw-semibold text-nowrap">{{ $row['customer'] }}</td>
                        @foreach($dates as $date)
                            <td>{{ $row['days'][$date] ?: '—' }}</td>
                        @endforeach
                        <td class="fw-bold">{{ $row['total'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $dates->count() + 2 }}" class="text-muted py-4">Chưa có đơn giao thành công trong khoảng này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
