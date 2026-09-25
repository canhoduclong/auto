@extends('layouts.app')
@section('content')
<div class="content">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">{{ __('transactions.titles.index') }}</h4>
            <p class="text-muted mb-0">Báo cáo theo kỳ: {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('transactions.create') }}" class="btn btn-success">+ {{ __('transactions.buttons.add') }}</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">Bộ lọc báo cáo</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('transactions.index') }}" class="row g-2 align-items-end" id="transactionReportFilterForm">
                <div class="col-md-3">
                    <label class="form-label">Chu kỳ</label>
                    <select name="period" id="period" class="form-select">
                        <option value="day" {{ $period === 'day' ? 'selected' : '' }}>Theo ngày</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Theo tuần</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Theo tháng</option>
                        <option value="range" {{ $period === 'range' ? 'selected' : '' }}>Từ ngày - đến ngày</option>
                    </select>
                </div>

                <div class="col-md-3" data-period="day">
                    <label class="form-label">Ngày</label>
                    <input type="date" name="day" value="{{ $dayInput }}" class="form-control">
                </div>

                <div class="col-md-3" data-period="week">
                    <label class="form-label">Tuần</label>
                    <input type="week" name="week" value="{{ $weekInput }}" class="form-control">
                </div>

                <div class="col-md-3" data-period="month">
                    <label class="form-label">Tháng</label>
                    <input type="month" name="month" value="{{ $monthInput }}" class="form-control">
                </div>

                <div class="col-md-3" data-period="range">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ $fromDateInput }}" class="form-control">
                </div>

                <div class="col-md-3" data-period="range">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $toDateInput }}" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Loại giao dịch</label>
                    <select name="type" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach(__('transactions.types') as $typeKey => $typeName)
                            <option value="{{ $typeKey }}" {{ request('type') === $typeKey ? 'selected' : '' }}>{{ $typeName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Xem báo cáo</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('transactions.index') }}" class="btn btn-light">Đặt lại</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Tổng giao dịch</div>
                    <h4 class="mb-0">{{ number_format($totalTransactions) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Tổng thu</div>
                    <h4 class="mb-0 text-success">{{ number_format($totalIncome, 0, ',', '.') }} đ</h4>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Tổng chi/hoàn</div>
                    <h4 class="mb-0 text-danger">{{ number_format($totalExpense, 0, ',', '.') }} đ</h4>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Doanh thu thuần</div>
                    <h4 class="mb-0 {{ $netAmount >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format($netAmount, 0, ',', '.') }} đ</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Báo cáo theo ngày</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Tổng thu</th>
                                    <th>Tổng chi/hoàn</th>
                                    <th>Thuần</th>
                                    <th>Số giao dịch</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summaryByDay as $row)
                                    @php $dailyNet = (float) $row->income - (float) $row->expense; @endphp
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row->day)->format('d/m/Y') }}</td>
                                        <td>{{ number_format((float) $row->income, 0, ',', '.') }} đ</td>
                                        <td>{{ number_format((float) $row->expense, 0, ',', '.') }} đ</td>
                                        <td class="{{ $dailyNet >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format($dailyNet, 0, ',', '.') }} đ</td>
                                        <td>{{ number_format((int) $row->total_rows) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Không có dữ liệu theo ngày trong kỳ đã chọn.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Báo cáo theo loại giao dịch</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Loại</th>
                                    <th>Số lượng</th>
                                    <th>Tổng tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summaryByType as $row)
                                    <tr>
                                        <td>{{ __('transactions.types.' . $row->type) }}</td>
                                        <td>{{ number_format((int) $row->total_rows) }}</td>
                                        <td>{{ number_format((float) $row->total_amount, 0, ',', '.') }} đ</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Không có dữ liệu theo loại giao dịch.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Chi tiết giao dịch</h6>
            <span class="text-muted small">{{ number_format($transactions->total()) }} bản ghi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('transactions.labels.order') }}</th>
                            <th>{{ __('transactions.labels.customer') }}</th>
                            <th>{{ __('transactions.labels.amount') }}</th>
                            <th>{{ __('transactions.labels.type') }}</th>
                            <th>{{ __('transactions.labels.method') }}</th>
                            <th>{{ __('transactions.labels.note') }}</th>
                            <th>{{ __('transactions.labels.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                            <tr>
                                <td>{{ $t->id }}</td>
                                <td>@if($t->order_id)<a href="{{ route('orders.show', $t->order_id) }}">#{{ $t->order_id }}</a>@endif</td>
                                <td>@if($t->customer_id){{ $t->customer->name }}@endif</td>
                                <td>{{ number_format((float) $t->amount, 0, ',', '.') }}</td>
                                <td>{{ __('transactions.types.' . $t->type) }}</td>
                                <td>{{ $t->method }}</td>
                                <td>{{ $t->note }}</td>
                                <td>{{ optional($t->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Không có giao dịch trong kỳ đã chọn.</td>
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
    const periodSelect = document.getElementById('period');
    const periodBlocks = document.querySelectorAll('[data-period]');

    function togglePeriodInputs() {
        const currentPeriod = periodSelect ? periodSelect.value : 'day';

        periodBlocks.forEach(function (block) {
            const showBlock = block.dataset.period === currentPeriod;
            block.style.display = showBlock ? '' : 'none';
        });
    }

    if (periodSelect) {
        periodSelect.addEventListener('change', togglePeriodInputs);
        togglePeriodInputs();
    }
});
</script>
@endpush
