@extends('layouts.site')

@push('styles')
<style>
    .orders-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .orders-shell {
        max-width: 1180px;
        margin: 0 auto;
    }
    .orders-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 28px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(21, 34, 56, 0.18);
        overflow: hidden;
        position: relative;
    }
    .orders-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        top: -60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .orders-kpi {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 18px;
        min-height: 100%;
        backdrop-filter: blur(6px);
    }
    .orders-kpi-label {
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.68);
        margin-bottom: 8px;
    }
    .orders-kpi-value {
        font-size: 1.7rem;
        font-weight: 800;
        line-height: 1;
    }
    .orders-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }
    .orders-filter {
        padding: 24px;
    }
    .orders-filter .form-control,
    .orders-filter .form-select {
        height: 48px;
        border-radius: 14px;
        border-color: #d8deea;
    }
    .orders-filter .btn {
        height: 48px;
        border-radius: 14px;
        font-weight: 700;
    }
    .orders-section-head {
        padding: 22px 24px 0;
    }
    .orders-table-wrap {
        padding: 0 18px 18px;
    }
    .orders-table {
        margin-bottom: 0;
        min-width: 920px;
    }
    .orders-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        border-bottom: 1px solid #e8edf5;
        padding: 16px 14px;
        white-space: nowrap;
    }
    .orders-table tbody td {
        padding: 18px 14px;
        border-color: #edf2f7;
        vertical-align: middle;
    }
    .orders-code {
        font-weight: 800;
        color: #1e293b;
    }
    .orders-subtle {
        font-size: .82rem;
        color: #64748b;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
    }
    .status-pending { background: #fff7ed; color: #c2410c; }
    .status-progress { background: #eff6ff; color: #1d4ed8; }
    .status-success { background: #ecfdf5; color: #047857; }
    .status-danger { background: #fef2f2; color: #b91c1c; }
    .status-muted { background: #f1f5f9; color: #475569; }
    .orders-total {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
    }
    .orders-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .orders-actions .btn {
        border-radius: 12px;
        font-weight: 700;
        padding: 9px 14px;
    }
    .orders-mobile-list {
        display: none;
        padding: 0 16px 16px;
    }
    .orders-mobile-card {
        border: 1px solid #e7ecf3;
        border-radius: 22px;
        padding: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    }
    .orders-mobile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .orders-mobile-item span {
        display: block;
        font-size: .75rem;
        color: #64748b;
        margin-bottom: 4px;
    }
    .orders-empty {
        padding: 42px 24px 52px;
        text-align: center;
        color: #64748b;
    }
    @media (max-width: 991.98px) {
        .orders-hero {
            padding: 22px;
            border-radius: 24px;
        }
        .orders-filter {
            padding: 20px;
        }
    }
    @media (max-width: 767.98px) {
        .orders-page {
            padding: 20px 0 48px;
        }
        .orders-shell {
            padding: 0 12px;
        }
        .orders-hero {
            padding: 18px;
        }
        .orders-kpi-value {
            font-size: 1.35rem;
        }
        .orders-table-wrap {
            display: none;
        }
        .orders-mobile-list {
            display: block;
        }
        .orders-mobile-grid {
            grid-template-columns: 1fr;
        }
        .orders-actions {
            justify-content: stretch;
        }
        .orders-actions .btn {
            flex: 1 1 auto;
            text-align: center;
        }
    }
</style>
@endpush

@section('content')
@php
    $statusLabels = \App\Models\Order::statusOptions() + [
        \App\Models\Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
        \App\Models\Order::STATUS_PACKING => 'Đang đóng gói',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'Chờ giao đơn vị vận chuyển',
        \App\Models\Order::STATUS_DELIVERING => 'Đang giao hàng',
        \App\Models\Order::STATUS_RETURNING => 'Đang trả hàng',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'Đã nhập kho trả hàng',
        'shipping' => 'Đang vận chuyển',
        'picked_up' => 'Đã lấy hàng',
    ];

    $statusClasses = [
        \App\Models\Order::STATUS_COMPLETED => 'status-success',
        \App\Models\Order::STATUS_DELIVERED => 'status-success',
        \App\Models\Order::STATUS_ORDER_PLACED => 'status-pending',
        \App\Models\Order::STATUS_ORDER_CONFIRMED => 'status-progress',
        \App\Models\Order::STATUS_PACKED => 'status-progress',
        \App\Models\Order::STATUS_IN_DELIVERY => 'status-progress',
        \App\Models\Order::STATUS_READY_TO_PACK => 'status-pending',
        \App\Models\Order::STATUS_PACKING => 'status-progress',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'status-progress',
        \App\Models\Order::STATUS_DELIVERING => 'status-progress',
        \App\Models\Order::STATUS_RETURNING => 'status-danger',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'status-muted',
        \App\Models\Order::STATUS_RETURNED => 'status-danger',
        \App\Models\Order::STATUS_CANCELLED => 'status-danger',
        'shipping' => 'status-progress',
        'picked_up' => 'status-progress',
    ];

    $pageOrders = $orders->getCollection();
    $totalValue = $pageOrders->sum('total');
    $completedCount = $pageOrders->whereIn('status', [\App\Models\Order::STATUS_COMPLETED, \App\Models\Order::STATUS_DELIVERED])->count();
    $returnableCount = $pageOrders->filter(function ($order) {
        return in_array($order->status, ['picked_up', 'shipping', 'completed'], true);
    })->count();
@endphp

<section class="orders-page">
    <div class="container orders-shell">
        <div class="orders-hero mb-4">
            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-5">
                    <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.65);">Order Center</div>
                    <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Đơn hàng của {{ $user->name }}</h1>
                    <p class="mb-0" style="color:rgba(255,255,255,.8);max-width:520px;">
                        Theo dõi toàn bộ đơn hàng, lọc theo khách hàng và thời gian, đồng thời thao tác nhanh với các đơn đủ điều kiện trả hàng.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="orders-kpi">
                                <div class="orders-kpi-label">Đơn trên trang</div>
                                <div class="orders-kpi-value">{{ $orders->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="orders-kpi">
                                <div class="orders-kpi-label">Giá trị đơn</div>
                                <div class="orders-kpi-value">{{ number_format($totalValue, 0, ',', '.') }}đ</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="orders-kpi">
                                <div class="orders-kpi-label">Đã hoàn tất</div>
                                <div class="orders-kpi-value">{{ $completedCount }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="orders-panel mb-4">
            <div class="orders-filter">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1 fw-bold">Bộ lọc đơn hàng</h2>
                        <p class="mb-0 text-muted">Rút gọn danh sách theo khách hàng và khoảng thời gian.</p>
                    </div>
                    @if(request()->filled('customer_id') || request()->filled('from_date') || request()->filled('to_date'))
                        <a href="{{ route('pages.my_orders') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-refresh me-2"></i>Xóa bộ lọc
                        </a>
                    @endif
                </div>

                <form action="{{ route('pages.my_orders') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="customer_id" class="form-label fw-bold">Khách hàng</label>
                        <select name="customer_id" id="customer_id" class="form-select">
                            <option value="">Tất cả khách hàng</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ (string) request('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="from_date" class="form-label fw-bold">Từ ngày</label>
                        <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="to_date" class="form-label fw-bold">Đến ngày</label>
                        <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="orders-panel">
            <div class="orders-section-head d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h5 mb-1 fw-bold">Danh sách đơn hàng</h2>
                    <p class="mb-0 text-muted">Hiển thị {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} trên tổng {{ $orders->total() }} đơn.</p>
                </div>
                <div class="text-muted small">
                    Có thể trả hàng: <strong class="text-dark">{{ $returnableCount }}</strong>
                </div>
            </div>

            @if($orders->count() > 0)
                <div class="orders-table-wrap">
                    <div class="table-responsive">
                        <table class="table orders-table align-middle">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                    <th class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    @php
                                        $statusLabel = $statusLabels[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status));
                                        $statusClass = $statusClasses[$order->status] ?? 'status-muted';
                                        $canReturn = in_array($order->status, ['picked_up', 'shipping', 'completed'], true);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="orders-code">{{ $order->code }}</div>
                                            <div class="orders-subtle">{{ $order->payment_status ?: 'pending payment' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $order->customer->name ?? 'Không có khách hàng' }}</div>
                                            <div class="orders-subtle">Nhân viên: {{ $user->name }}</div>
                                        </td>
                                        <td>
                                            <div class="orders-total">{{ number_format($order->total, 0, ',', '.') }}đ</div>
                                            <div class="orders-subtle">{{ $order->payment_status_text }}</div>
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $statusClass }}">
                                                <i class="fa fa-circle" style="font-size:8px;"></i>{{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $order->created_at->format('d/m/Y') }}</div>
                                            <div class="orders-subtle">{{ $order->created_at->format('H:i') }}</div>
                                        </td>
                                        <td>
                                            <div class="orders-actions">
                                                <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="fa fa-eye me-1"></i>Chi tiết
                                                </a>
                                                @if($canReturn)
                                                    <a href="{{ route('site.order-returns.create', $order) }}" class="btn btn-warning btn-sm text-dark">
                                                        <i class="fa fa-undo me-1"></i>Trả hàng
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="orders-mobile-list">
                    <div class="row g-3">
                        @foreach($orders as $order)
                            @php
                                $statusLabel = $statusLabels[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status));
                                $statusClass = $statusClasses[$order->status] ?? 'status-muted';
                                $canReturn = in_array($order->status, ['picked_up', 'shipping', 'completed'], true);
                            @endphp
                            <div class="col-12">
                                <div class="orders-mobile-card">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                        <div>
                                            <div class="orders-code">{{ $order->code }}</div>
                                            <div class="orders-subtle">{{ $order->customer->name ?? 'Không có khách hàng' }}</div>
                                        </div>
                                        <span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </div>

                                    <div class="orders-mobile-grid mb-3">
                                        <div class="orders-mobile-item">
                                            <span>Tổng tiền</span>
                                            <strong>{{ number_format($order->total, 0, ',', '.') }}đ</strong>
                                        </div>
                                        <div class="orders-mobile-item">
                                            <span>Thanh toán</span>
                                            <strong>{{ $order->payment_status_text }}</strong>
                                        </div>
                                        <div class="orders-mobile-item">
                                            <span>Ngày tạo</span>
                                            <strong>{{ $order->created_at->format('d/m/Y') }}</strong>
                                        </div>
                                        <div class="orders-mobile-item">
                                            <span>Giờ tạo</span>
                                            <strong>{{ $order->created_at->format('H:i') }}</strong>
                                        </div>
                                    </div>

                                    <div class="orders-actions">
                                        <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fa fa-eye me-1"></i>Chi tiết
                                        </a>
                                        @if($canReturn)
                                            <a href="{{ route('site.order-returns.create', $order) }}" class="btn btn-warning btn-sm text-dark">
                                                <i class="fa fa-undo me-1"></i>Trả hàng
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="px-4 pb-4">
                    {{ $orders->appends(request()->input())->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="orders-empty">
                    <div class="mb-3" style="font-size:3rem;color:#cbd5e1;">
                        <i class="fa fa-inbox"></i>
                    </div>
                    <h3 class="h5 fw-bold text-dark mb-2">Chưa có đơn hàng phù hợp</h3>
                    <p class="mb-3">Hãy thử thay đổi bộ lọc hoặc quay lại sau khi có đơn mới được tạo.</p>
                    <a href="{{ route('pages.my_orders') }}" class="btn btn-outline-primary rounded-pill px-4">
                        Xem tất cả đơn hàng
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
