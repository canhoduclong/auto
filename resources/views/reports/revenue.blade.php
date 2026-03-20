@extends('layouts.app')

@section('content')
<div class="content">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">Báo cáo doanh thu</h4>
            <p class="text-muted mb-0">
                Kỳ báo cáo: {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-light">Quay lại Dashboard</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.revenue') }}" class="row g-2 align-items-end" id="revenueFilterForm">
                <div class="col-md-3">
                    <label class="form-label">Kiểu báo cáo</label>
                    <select name="type" id="reportType" class="form-select">
                        <option value="day" {{ $type === 'day' ? 'selected' : '' }}>Theo ngày</option>
                        <option value="month" {{ $type === 'month' ? 'selected' : '' }}>Theo tháng</option>
                        <option value="range" {{ $type === 'range' ? 'selected' : '' }}>Từ ngày - đến ngày</option>
                    </select>
                </div>

                <div class="col-md-3" data-filter="day">
                    <label class="form-label">Ngày</label>
                    <input type="date" name="day" value="{{ $dayInput }}" class="form-control">
                </div>

                <div class="col-md-3" data-filter="month">
                    <label class="form-label">Tháng</label>
                    <input type="month" name="month" value="{{ $monthInput }}" class="form-control">
                </div>

                <div class="col-md-3" data-filter="range">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ $fromInput }}" class="form-control">
                </div>

                <div class="col-md-3" data-filter="range">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $toInput }}" class="form-control">
                </div>

                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Xem báo cáo</button>
                </div>

                <div class="col-auto">
                    <a href="{{ route('reports.revenue') }}" class="btn btn-light">Đặt lại</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-muted mb-1">Tổng thu</div>
                    <h4 class="mb-0 text-success">{{ number_format($income, 0, ',', '.') }} đ</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-muted mb-1">Tổng hoàn</div>
                    <h4 class="mb-0 text-danger">{{ number_format($refund, 0, ',', '.') }} đ</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-muted mb-1">Doanh thu thuần</div>
                    <h4 class="mb-0 {{ $netRevenue >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format($netRevenue, 0, ',', '.') }} đ</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">Tổng hợp theo ngày</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Thu</th>
                            <th>Hoàn</th>
                            <th>Doanh thu thuần</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailySummary as $row)
                            @php
                                $dailyNet = (float) $row->income - (float) $row->refund;
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->day)->format('d/m/Y') }}</td>
                                <td>{{ number_format((float) $row->income, 0, ',', '.') }} đ</td>
                                <td>{{ number_format((float) $row->refund, 0, ',', '.') }} đ</td>
                                <td class="{{ $dailyNet >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format($dailyNet, 0, ',', '.') }} đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Không có dữ liệu doanh thu trong kỳ đã chọn.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Chi tiết giao dịch</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Loại</th>
                            <th>Số tiền</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ optional($transaction->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $transaction->order?->code ?? ('#' . ($transaction->order_id ?? '-')) }}</td>
                                <td>{{ $transaction->customer?->name ?? '-' }}</td>
                                <td>
                                    @if($transaction->type === 'payment')
                                        <span class="badge bg-success">Thu</span>
                                    @elseif($transaction->type === 'refund')
                                        <span class="badge bg-danger">Hoàn</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $transaction->type }}</span>
                                    @endif
                                </td>
                                <td>{{ number_format((float) $transaction->amount, 0, ',', '.') }} đ</td>
                                <td>{{ $transaction->note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Không có giao dịch trong kỳ đã chọn.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reportType = document.getElementById('reportType');
    const filterBlocks = document.querySelectorAll('[data-filter]');

    function toggleFilters() {
        const currentType = reportType ? reportType.value : 'day';

        filterBlocks.forEach(function (block) {
            const isVisible = block.dataset.filter === currentType || (currentType === 'range' && block.dataset.filter === 'range');
            block.style.display = isVisible ? '' : 'none';
        });
    }

    if (reportType) {
        reportType.addEventListener('change', toggleFilters);
        toggleFilters();
    }
});
</script>
@endpush
