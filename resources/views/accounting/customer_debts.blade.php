@extends(accounting_layout())

@section('title', 'Công Nợ Khách Hàng')
@section('subtitle', 'Theo dõi công nợ đầu kỳ, bổ sung, đơn hàng và thanh toán')

@section('accounting_content')
@php
    $sortLink = function (string $key, string $label, string $class = '') use ($sortBy) {
        $isActiveAsc = $sortBy === $key . '_asc';
        $isActiveDesc = $sortBy === $key . '_desc';
        $next = $isActiveDesc ? $key . '_asc' : $key . '_desc';
        $icon = $isActiveDesc ? '↓' : ($isActiveAsc ? '↑' : '↕');
        $url = request()->fullUrlWithQuery(['sort_by' => $next, 'page' => null]);

        return '<a href="' . e($url) . '" class="text-decoration-none text-dark d-inline-flex gap-1 align-items-center ' . e($class) . '"><span>' . e($label) . '</span><span class="small text-muted">' . e($icon) . '</span></a>';
    };
@endphp

<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Tìm khách hàng</label>
                <input type="text" class="form-control" name="keyword" value="{{ $keyword }}" placeholder="Tên, SĐT, Email...">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Từ ngày</label>
                <input type="date" class="form-control" name="from_date" value="{{ $fromDate?->toDateString() }}">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Đến ngày</label>
                <input type="date" class="form-control" name="to_date" value="{{ $toDate?->toDateString() }}">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Nợ từ ngày</label>
                <input type="number" class="form-control" name="debt_days_min" value="{{ $debtDaysMin }}" min="0" placeholder="VD: 7">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Nợ đến ngày</label>
                <input type="number" class="form-control" name="debt_days_max" value="{{ $debtDaysMax }}" min="0" placeholder="VD: 30">
            </div>
            <div class="col-lg-1 col-md-6"><button class="btn btn-primary w-100">Lọc</button></div>
            <div class="col-12 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <a href="{{ accounting_route('customer-debts') }}" class="btn btn-outline-secondary btn-sm">Xóa lọc</a>
                <span class="badge text-bg-light border">Tổng nợ trang này: {{ number_format($totalDebt, 0, ',', '.') }} đ</span>
            </div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>{!! $sortLink('customer', 'Khách hàng') !!}</th>
                    <th>{!! $sortLink('debt_type', 'Loại công nợ') !!}</th>
                    <th class="text-end">{!! $sortLink('debt_increase', 'Tăng công nợ') !!}</th>
                    <th class="text-end">{!! $sortLink('payments', 'Thanh toán') !!}</th>
                    <th class="text-end">{!! $sortLink('debt', 'Nợ hiện tại') !!}</th>
                    <th>{!! $sortLink('status', 'Trạng thái') !!}</th>
                    <th>{!! $sortLink('latest_debt', 'Thời gian') !!}</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $row['customer']->name }}</div>
                        <div class="text-muted small">{{ $row['customer']->phone ?? '-' }}</div>
                    </td>
                    <td>
                        @if($row['debt_type']['value'] !== 'normal')
                            <span class="badge {{ $row['debt_type']['class'] }}">{{ $row['debt_type']['label'] }}</span>
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($row['debt_increase'], 0, ',', '.') }} đ</td>
                    <td class="text-end text-success">{{ number_format($row['payments'], 0, ',', '.') }} đ</td>
                    <td class="text-end fw-semibold {{ $row['debt'] > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($row['debt'], 0, ',', '.') }} đ</td>
                    <td><span class="badge {{ $row['debt'] > 0 ? 'text-bg-warning' : 'text-bg-success' }}">{{ $row['status'] }}</span></td>
                    <td>
                        @if($row['debt'] > 0)
                            <div class="fw-semibold text-danger">Chưa thanh toán {{ number_format($row['unpaid_days'], 0, ',', '.') }} ngày</div>
                            <div class="text-muted small">Phát sinh từ: {{ $row['first_debt_at'] ? $row['first_debt_at']->format('d/m/Y') : '-' }}</div>
                            <div class="text-muted small">Mới nhất: {{ $row['latest_debt_at'] ? $row['latest_debt_at']->format('d/m/Y') : '-' }}</div>
                        @else
                            <div class="text-muted small">Không còn công nợ</div>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ accounting_route('customer-debts.show', $row['customer']) }}" class="btn btn-sm btn-outline-primary">
                            Chi tiết
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">Không có dữ liệu công nợ.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $customers->links() }}
    </div>
</div>
@endsection
