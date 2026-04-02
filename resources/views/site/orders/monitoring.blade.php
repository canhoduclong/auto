@extends('layouts.site')

@push('styles')
<style>
    .mo-page {
        background:
            radial-gradient(circle at top left, rgba(14, 116, 144, 0.12), transparent 34%),
            radial-gradient(circle at 90% 10%, rgba(15, 23, 42, 0.08), transparent 26%),
            linear-gradient(180deg, #f8fbff 0%, #ffffff 42%, #f6f7fb 100%);
        padding: 40px 0 72px;
    }
    .mo-shell {
        max-width: 1200px;
        margin: 0 auto;
    }
    .mo-hero {
        border-radius: 24px;
        background: linear-gradient(135deg, #0f172a 0%, #12324f 58%, #0e7490 100%);
        color: #fff;
        padding: 26px;
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.22);
    }
    .mo-hero .mo-title {
        font-size: 1.85rem;
        font-weight: 900;
        line-height: 1.15;
        margin-bottom: 10px;
    }
    .mo-hero .mo-subtitle {
        color: rgba(255, 255, 255, 0.82);
        margin-bottom: 0;
    }
    .mo-kpi-card {
        border-radius: 16px;
        padding: 14px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.08);
        min-height: 100%;
    }
    .mo-kpi-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: rgba(255, 255, 255, 0.72);
        margin-bottom: 8px;
    }
    .mo-kpi-value {
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1;
    }
    .mo-panel {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 22px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
    }
    .mo-filter {
        padding: 22px;
    }
    .mo-filter .form-control,
    .mo-filter .form-select {
        border-radius: 12px;
        border-color: #d8deea;
        height: 44px;
    }
    .mo-filter .btn {
        height: 44px;
        border-radius: 12px;
        font-weight: 700;
    }
    .mo-table-wrap {
        padding: 0 16px 16px;
    }
    .mo-table {
        margin-bottom: 0;
        min-width: 980px;
    }
    .mo-table thead th {
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
        border-bottom: 1px solid #e7ecf3;
        white-space: nowrap;
        padding: 14px 12px;
    }
    .mo-table tbody td {
        border-color: #eef2f7;
        padding: 14px 12px;
        vertical-align: middle;
    }
    .mo-code {
        font-weight: 800;
        color: #0f172a;
    }
    .mo-subtle {
        color: #64748b;
        font-size: .82rem;
    }
    .mo-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: .75rem;
        font-weight: 700;
    }
    .mo-status.pending { background: #fff7ed; color: #c2410c; }
    .mo-status.progress { background: #eff6ff; color: #1d4ed8; }
    .mo-status.success { background: #ecfdf5; color: #047857; }
    .mo-status.danger { background: #fef2f2; color: #b91c1c; }
    .mo-status.muted { background: #f1f5f9; color: #475569; }
    .mo-empty {
        padding: 44px 24px 52px;
        text-align: center;
        color: #64748b;
    }
    @media (max-width: 991.98px) {
        .mo-page {
            padding-top: 22px;
        }
        .mo-hero {
            padding: 20px;
        }
        .mo-hero .mo-title {
            font-size: 1.45rem;
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
        \App\Models\Order::STATUS_COMPLETED => 'success',
        \App\Models\Order::STATUS_DELIVERED => 'success',
        \App\Models\Order::STATUS_ORDER_PLACED => 'pending',
        \App\Models\Order::STATUS_ORDER_CONFIRMED => 'progress',
        \App\Models\Order::STATUS_PACKED => 'progress',
        \App\Models\Order::STATUS_IN_DELIVERY => 'progress',
        \App\Models\Order::STATUS_READY_TO_PACK => 'pending',
        \App\Models\Order::STATUS_PACKING => 'progress',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'progress',
        \App\Models\Order::STATUS_DELIVERING => 'progress',
        \App\Models\Order::STATUS_RETURNING => 'danger',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'muted',
        \App\Models\Order::STATUS_RETURNED => 'danger',
        \App\Models\Order::STATUS_CANCELLED => 'danger',
        'shipping' => 'progress',
        'picked_up' => 'progress',
    ];
@endphp

<section class="mo-page">
    <div class="container mo-shell">
        <div class="mo-hero mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.65);">Monitoring</div>
                    <h1 class="mo-title">Theo dõi đơn hàng toàn hệ thống</h1>
                    <p class="mo-subtitle">Theo dõi tiến độ xử lý đơn theo thời gian thực, tìm nhanh theo mã đơn, khách hàng, sale và shipper.</p>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2">
                        <div class="col-6 col-md-4">
                            <div class="mo-kpi-card">
                                <div class="mo-kpi-label">Tổng đơn</div>
                                <div class="mo-kpi-value">{{ number_format($stats['total_orders']) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="mo-kpi-card">
                                <div class="mo-kpi-label">Đang giao</div>
                                <div class="mo-kpi-value">{{ number_format($stats['delivering_orders']) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="mo-kpi-card">
                                <div class="mo-kpi-label">Đang trả</div>
                                <div class="mo-kpi-value">{{ number_format($stats['returning_orders']) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="mo-kpi-card">
                                <div class="mo-kpi-label">Hoàn thành</div>
                                <div class="mo-kpi-value">{{ number_format($stats['completed_orders']) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="mo-kpi-card">
                                <div class="mo-kpi-label">Tạo hôm nay</div>
                                <div class="mo-kpi-value">{{ number_format($stats['today_orders']) }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="mo-kpi-card">
                                <div class="mo-kpi-label">Tổng giá trị</div>
                                <div class="mo-kpi-value" style="font-size:1.1rem;">{{ number_format($stats['total_value']) }}đ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mo-panel mb-4">
            <div class="mo-filter">
                <form method="GET" action="{{ route('pages.my_orders.monitoring') }}" class="row g-2 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label small text-uppercase fw-bold text-muted mb-1">Từ khóa</label>
                        <input type="text" name="keyword" class="form-control" value="{{ $keyword }}" placeholder="Mã đơn, khách hàng, sale, shipper...">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small text-uppercase fw-bold text-muted mb-1">Từ ngày</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small text-uppercase fw-bold text-muted mb-1">Đến ngày</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label small text-uppercase fw-bold text-muted mb-1">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            @foreach($statusLabels as $statusKey => $statusName)
                                <option value="{{ $statusKey }}" {{ $selectedStatus === $statusKey ? 'selected' : '' }}>{{ $statusName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-6">
                        <label class="form-label small text-uppercase fw-bold text-muted mb-1">Hiển thị</label>
                        <select name="per_page" class="form-select">
                            @foreach([10, 20, 50, 100] as $perPageOption)
                                <option value="{{ $perPageOption }}" {{ (int) $perPage === $perPageOption ? 'selected' : '' }}>{{ $perPageOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-6 d-grid">
                        <button type="submit" class="btn btn-primary">Lọc</button>
                    </div>
                    <div class="col-12 mt-2">
                        <a href="{{ route('pages.my_orders.monitoring') }}" class="btn btn-outline-secondary btn-sm">Đặt lại bộ lọc</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="mo-panel">
            @if($orders->count() > 0)
                <div class="mo-table-wrap table-responsive">
                    <table class="table mo-table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Sale</th>
                                <th>Shipper</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Tổng tiền</th>
                                <th>Ngày tạo</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                @php
                                    $statusClass = $statusClasses[$order->status] ?? 'muted';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="mo-code">{{ $order->code ?: ('#' . $order->id) }}</div>
                                        <div class="mo-subtle">ID: {{ $order->id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->customer?->name ?? '—' }}</div>
                                        <div class="mo-subtle">{{ $order->customer?->phone ?? 'Không có SĐT' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->user?->name ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->shipper?->name ?? 'Chưa gán' }}</div>
                                    </td>
                                    <td>
                                        <span class="mo-status {{ $statusClass }}">{{ $statusLabels[$order->status] ?? ucfirst((string) $order->status) }}</span>
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($order->total ?? 0) }}đ</td>
                                    <td>
                                        <div>{{ $order->created_at?->format('d/m/Y') }}</div>
                                        <div class="mo-subtle">{{ $order->created_at?->format('H:i') }}</div>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">Chi tiết</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 pb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="mo-subtle">
                        Hiển thị {{ $orders->firstItem() }} - {{ $orders->lastItem() }} / {{ $orders->total() }} đơn
                    </div>
                    <div>
                        {{ $orders->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @else
                <div class="mo-empty">
                    <i class="bi bi-inbox" style="font-size:2.4rem;"></i>
                    <h5 class="mt-3 mb-2">Không có đơn hàng phù hợp</h5>
                    <p class="mb-0">Hãy thay đổi bộ lọc để xem dữ liệu khác.</p>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
