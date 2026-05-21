@extends('layouts.shipper')

@section('title', 'Báo cáo chi phí ship')
@section('subtitle', 'Theo dõi phí ship trong ngày và lịch sử thay đổi theo khách hàng')

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
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Từ ngày</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Đến ngày</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-6 d-flex gap-2">
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
    <div class="col-md-4">
        <div class="card sfr-card">
            <div class="card-body">
                <div class="sfr-stat-value">{{ $dailySummary['total_orders'] }}</div>
                <div class="sfr-stat-label">Đơn đã giao trong khoảng</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card sfr-card">
            <div class="card-body">
                <div class="sfr-stat-value">{{ number_format($dailySummary['total_ship_fee'], 0, ',', '.') }}</div>
                <div class="sfr-stat-label">Tổng chi phí ship</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card sfr-card">
            <div class="card-body">
                <div class="sfr-stat-value">{{ $dailySummary['customers'] }}</div>
                <div class="sfr-stat-label">Khách phát sinh phí ship</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="sfr-section-title">Báo cáo chi phí ship trong ngày</div>
                <div class="text-muted small">Các đơn đã giao / hoàn thành trong khoảng ngày đã lọc.</div>
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
                            <th>Thời gian</th>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Shipper</th>
                            <th class="text-end">Phí ship</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailyOrders as $order)
                            <tr>
                                <td>{{ optional($order->updated_at)->format('d/m/Y H:i') }}</td>
                                <td class="fw-semibold">#{{ $order->code }}</td>
                                <td>{{ $order->customer?->name ?? $order->recipient_name ?? 'N/A' }}</td>
                                <td>{{ $order->shipper?->name ?? 'Chưa gán' }}</td>
                                <td class="text-end fw-semibold">{{ number_format(($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0, 0, ',', '.') }} đ</td>
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