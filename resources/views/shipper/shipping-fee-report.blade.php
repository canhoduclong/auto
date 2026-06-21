@extends('layouts.shipper')

@section('title', 'Báo cáo chi phí ship')
@section('subtitle', 'Báo cáo theo ngày hoặc khoảng thời gian, đối chiếu phí đã và chưa lập phiếu')

@push('styles')
<style>
    .sfr-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        height: 100%;
    }
    .sfr-stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--theme-primary);
        line-height: 1.1;
    }
    .sfr-stat-label {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin-top: 4px;
    }
    .sfr-section-title {
        font-weight: 800;
        color: #0f172a;
    }
    .sfr-table td,
    .sfr-table th {
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Loại báo cáo</label>
                <select name="mode" id="reportMode" class="form-select form-select-sm">
                    <option value="day" @selected($reportMode === 'day')>Theo ngày</option>
                    <option value="range" @selected($reportMode === 'range')>Từ ngày đến ngày</option>
                </select>
            </div>
            <div class="col-md-3 js-day-filter">
                <label class="form-label small text-muted mb-1">Ngày báo cáo</label>
                <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 js-range-filter">
                <label class="form-label small text-muted mb-1">Từ ngày</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 js-range-filter">
                <label class="form-label small text-muted mb-1">Đến ngày</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
                <a href="{{ route('shipper.shipping-fee-report') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-1"></i>Đặt lại
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-2">
        <div class="card sfr-card">
            <div class="card-body">
                <div class="sfr-stat-value">{{ $dailySummary['total_orders'] }}</div>
                <div class="sfr-stat-label">Đơn đã giao trong khoảng</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card sfr-card">
            <div class="card-body">
                <div class="sfr-stat-value">{{ number_format($dailySummary['total_ship_fee'], 0, ',', '.') }}</div>
                <div class="sfr-stat-label">Tổng chi phí ship</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card sfr-card">
            <div class="card-body">
                <div class="sfr-stat-value">{{ $dailySummary['customers'] }}</div>
                <div class="sfr-stat-label">Khách phát sinh phí ship</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card sfr-card"><div class="card-body">
            <div class="sfr-stat-value text-success">{{ number_format($dailySummary['requested_fee'], 0, ',', '.') }}</div>
            <div class="sfr-stat-label">Đã lập phiếu ({{ $dailySummary['requested_orders'] }} đơn)</div>
        </div></div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card sfr-card"><div class="card-body">
            <div class="sfr-stat-value text-danger">{{ number_format($dailySummary['pending_fee'], 0, ',', '.') }}</div>
            <div class="sfr-stat-label">Chưa lập phiếu ({{ $dailySummary['pending_orders'] }} đơn)</div>
        </div></div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card sfr-card"><div class="card-body">
            <div class="sfr-stat-value">{{ $dailySummary['shippers'] }}</div>
            <div class="sfr-stat-label">Shipper phát sinh phí</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="sfr-section-title mb-3">Tổng hợp theo ngày</div>
                <div class="table-responsive">
                    <table class="table table-sm sfr-table mb-0">
                        <thead class="table-light"><tr><th>Ngày giao</th><th class="text-center">Đơn</th><th class="text-center">Shipper</th><th class="text-end">Tổng phí</th><th class="text-end">Đã lập phiếu</th><th class="text-end">Còn lại</th></tr></thead>
                        <tbody>
                        @forelse($dailyBreakdown as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['date']->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $row['orders'] }}</td>
                                <td class="text-center">{{ $row['shippers'] }}</td>
                                <td class="text-end">{{ number_format($row['total_fee'], 0, ',', '.') }}đ</td>
                                <td class="text-end text-success">{{ number_format($row['requested_fee'], 0, ',', '.') }}đ</td>
                                <td class="text-end text-danger">{{ number_format($row['pending_fee'], 0, ',', '.') }}đ</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Không có dữ liệu.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="sfr-section-title mb-3">Tổng hợp theo shipper</div>
                <div class="table-responsive">
                    <table class="table table-sm sfr-table mb-0">
                        <thead class="table-light"><tr><th>Shipper</th><th class="text-center">Đơn</th><th class="text-end">Tổng phí</th><th class="text-end">Đã lập phiếu</th><th class="text-end">Chưa lập phiếu</th></tr></thead>
                        <tbody>
                        @forelse($shipperBreakdown as $row)
                            <tr>
                                <td><div class="fw-semibold">{{ $row['shipper']?->name ?? 'Chưa gán shipper' }}</div><div class="small text-muted">{{ $row['requested_orders'] }} đã gửi · {{ $row['pending_orders'] }} chưa gửi</div></td>
                                <td class="text-center">{{ $row['orders'] }}</td>
                                <td class="text-end fw-semibold">{{ number_format($row['total_fee'], 0, ',', '.') }}đ</td>
                                <td class="text-end text-success">{{ number_format($row['requested_fee'], 0, ',', '.') }}đ</td>
                                <td class="text-end text-danger">{{ number_format($row['pending_fee'], 0, ',', '.') }}đ</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Không có dữ liệu.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="sfr-section-title">Chi tiết đơn giao</div>
                <div class="text-muted small">{{ $reportMode === 'day' ? 'Ngày ' . \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') : 'Từ ' . \Carbon\Carbon::parse($fromDate)->format('d/m/Y') . ' đến ' . \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}.</div>
            </div>
            <span class="badge bg-light text-dark border">{{ $dailyOrders->count() }} đơn</span>
        </div>

        @if($dailyOrders->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Không có dữ liệu đơn đã giao trong khoảng ngày này.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle sfr-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ngày giao</th>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Shipper</th>
                            <th class="text-end">Phí ship</th>
                            <th>Phiếu yêu cầu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyOrders as $order)
                            <tr>
                                <td>{{ optional($order->delivery_date)->format('d/m/Y') }}</td>
                                <td class="fw-semibold">#{{ $order->code }}</td>
                                <td>{{ $order->customer?->name ?? $order->recipient_name ?? 'N/A' }}</td>
                                <td>{{ $order->shipper?->name ?? 'Chưa gán' }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) ($order->shipping_fee ?? 0), 0, ',', '.') }} đ</td>
                                <td>
                                    @if($order->shipping_fee_transaction_id)
                                        <span class="badge bg-success">#{{ $order->shipping_fee_transaction_id }} · {{ $order->shippingFeeRequest?->status }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Chưa lập phiếu</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="sfr-section-title">Báo cáo thay đổi chi phí ship</div>
                <div class="text-muted small">Ngày giờ thay đổi cho các khách hàng đã có đơn đã ship.</div>
            </div>
            <span class="badge bg-light text-dark border">{{ $feeChanges->count() }} lần thay đổi</span>
        </div>

        @if($feeChanges->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
                Không có lịch sử thay đổi phí ship trong khoảng ngày này.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle sfr-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ngày giờ</th>
                            <th>Khách hàng</th>
                            <th>Đơn liên quan</th>
                            <th class="text-end">Phí cũ</th>
                            <th class="text-end">Phí mới</th>
                            <th>Người thay đổi</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($feeChanges as $change)
                            <tr>
                                <td>{{ optional($change->changed_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $change->customer?->name ?? 'N/A' }}</div>
                                    <div class="small text-muted">{{ $change->customer?->phone }}</div>
                                </td>
                                <td>
                                    @if($change->order)
                                        #{{ $change->order->code }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) ($change->old_fee ?? 0), 0, ',', '.') }} đ</td>
                                <td class="text-end fw-semibold">{{ number_format((float) ($change->new_fee ?? 0), 0, ',', '.') }} đ</td>
                                <td>{{ $change->user?->name ?? 'Hệ thống' }}</td>
                                <td>{{ $change->note ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mode = document.getElementById('reportMode');
    const syncFilters = function () {
        const isDay = mode?.value === 'day';
        document.querySelectorAll('.js-day-filter').forEach(el => el.classList.toggle('d-none', !isDay));
        document.querySelectorAll('.js-range-filter').forEach(el => el.classList.toggle('d-none', isDay));
    };
    mode?.addEventListener('change', syncFilters);
    syncFilters();
});
</script>
@endpush
