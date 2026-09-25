@extends('layouts.shipper')

@section('title', 'Lịch sử giao hàng')
@section('subtitle', 'Đơn đã giao, đã trả và hoàn thành')

@push('styles')
<style>
    .sp-history-stat {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        height: 100%;
    }
    .sp-history-stat .card-body {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .sp-history-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .sp-history-icon.success {
        background: #dcfce7;
        color: #166534;
    }
    .sp-history-icon.warning {
        background: #fef3c7;
        color: #92400e;
    }
    .sp-history-icon.secondary {
        background: #e2e8f0;
        color: #334155;
    }
    .sp-history-label {
        font-size: .76rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 700;
    }
    .sp-history-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    .sp-history-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .sp-history-card .card-header {
        border-bottom: 1px solid #e2e8f0;
    }
    .sp-history-table thead th {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        border-bottom-width: 1px;
        white-space: nowrap;
    }
    .sp-history-table tbody td {
        vertical-align: middle;
        border-color: #eef2f7;
    }
    .sp-history-code {
        font-weight: 800;
        color: #0f172a;
    }
    .sp-history-amount {
        font-weight: 700;
        color: #0f172a;
    }
    .sp-history-collected {
        font-weight: 700;
        color: #15803d;
    }
    .sp-history-action {
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
@php
$statusMap = [
    'delivered'          => ['label' => 'Đã giao',         'color' => 'success'],
    'returning'          => ['label' => 'Đang trả',        'color' => 'warning'],
    'returned_completed' => ['label' => 'Đã nhập kho trả', 'color' => 'secondary'],
    'completed'          => ['label' => 'Hoàn thành',      'color' => 'success'],
];
@endphp

<div class="card sp-history-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Khoảng thời gian nhanh</label>
                <select name="period" class="form-select">
                    <option value="today" {{ ($filters['period'] ?? '') === 'today' ? 'selected' : '' }}>Hôm nay</option>
                    <option value="7" {{ ($filters['period'] ?? '') === '7' ? 'selected' : '' }}>7 ngày gần nhất</option>
                    <option value="15" {{ ($filters['period'] ?? '') === '15' ? 'selected' : '' }}>15 ngày gần nhất</option>
                    <option value="30" {{ ($filters['period'] ?? '') === '30' ? 'selected' : '' }}>1 tháng gần nhất</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Ngày cụ thể</label>
                <input type="date" name="date" class="form-control" value="{{ $filters['date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Từ ngày</label>
                <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Đến ngày</label>
                <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
                <a href="{{ route('shipper.history') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
            <div class="col-12">
                <div class="small text-muted">
                    Mặc định hiển thị <strong>đơn trong ngày hôm nay</strong>. Nếu chọn <strong>Ngày cụ thể</strong> thì sẽ ưu tiên lọc theo ngày đó.
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card sp-history-stat">
            <div class="card-body">
                <span class="sp-history-icon secondary"><i class="bi bi-clock-history"></i></span>
                <div>
                    <div class="sp-history-label">Tổng lịch sử</div>
                    <div class="sp-history-value">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card sp-history-stat">
            <div class="card-body">
                <span class="sp-history-icon success"><i class="bi bi-truck"></i></span>
                <div>
                    <div class="sp-history-label">Đã giao</div>
                    <div class="sp-history-value">{{ $stats['delivered'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card sp-history-stat">
            <div class="card-body">
                <span class="sp-history-icon warning"><i class="bi bi-arrow-return-left"></i></span>
                <div>
                    <div class="sp-history-label">Đang trả</div>
                    <div class="sp-history-value">{{ $stats['returning'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card sp-history-stat">
            <div class="card-body">
                <span class="sp-history-icon success"><i class="bi bi-cash-stack"></i></span>
                <div>
                    <div class="sp-history-label">Tổng tiền ship</div>
                    <div class="sp-history-value">{{ number_format($stats['total_ship_fee']) }}đ</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card sp-history-stat">
            <div class="card-body">
                <span class="sp-history-icon success"><i class="bi bi-check2-all"></i></span>
                <div>
                    <div class="sp-history-label">Hoàn thành</div>
                    <div class="sp-history-value">{{ $stats['completed'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card sp-history-card">
    <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
        <div><i class="bi bi-clock-history me-1 text-secondary"></i>Lịch sử giao hàng</div>
        <span class="badge bg-light text-dark border">{{ $orders->total() }} đơn</span>
    </div>

    @if($orders->isEmpty())
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-2 mb-0">Chưa có lịch sử giao hàng.</p>
        </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sp-history-table">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Tổng tiền</th>
                    <th>Đã thu</th>
                    <th>Trạng thái</th>
                    <th>Lý do trả</th>
                    <th>Thời gian</th>
                    <th class="text-end">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $i => $order)
                @php $st = $statusMap[$order->status] ?? ['label' => $order->status, 'color' => 'secondary']; @endphp
                <tr>
                    <td class="text-muted">{{ $orders->firstItem() + $i }}</td>
                    <td>
                        <div class="sp-history-code">{{ $order->code }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $order->customer?->name ?? '—' }}</div>
                        <div class="text-muted small">{{ $order->customer?->phone }}</div>
                    </td>
                    <td><span class="sp-history-amount">{{ number_format($order->total) }}đ</span></td>
                    <td>
                        @if($order->collected_amount !== null)
                            <span class="sp-history-collected">{{ number_format($order->collected_amount) }}đ</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $st['color'] }}">{{ $st['label'] }}</span>
                    </td>
                    <td class="small text-muted">
                        @php
                            $reasons = [
                                'customer_refused' => 'Khách từ chối',
                                'no_contact'       => 'Không liên lạc được',
                                'wrong_address'    => 'Sai địa chỉ',
                                'damaged'          => 'Hàng hỏng',
                            ];
                        @endphp
                        {{ $reasons[$order->return_reason] ?? ($order->return_reason ? $order->return_reason : '—') }}
                    </td>
                    <td class="text-muted small">{{ $order->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="text-end sp-history-action">
                        <a href="{{ route('shipper.history-detail', $order) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Xem chi tiết
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        {{ $orders->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
