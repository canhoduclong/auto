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
    .mo-timeline {
        min-width: 320px;
    }
    .mo-timeline-track {
        position: relative;
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        padding: 0 4px;
    }
    .mo-timeline-track::before {
        content: '';
        position: absolute;
        top: 8px;
        left: 12px;
        right: 12px;
        height: 3px;
        border-radius: 999px;
        background: #dbe4ef;
    }
    .mo-timeline-progress {
        position: absolute;
        top: 8px;
        left: 12px;
        height: 3px;
        border-radius: 999px;
        background: linear-gradient(90deg, #0e7490, #2563eb);
        transition: width .3s ease;
    }
    .mo-timeline-dot {
        position: relative;
        z-index: 1;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid #c5d3e5;
        background: #fff;
    }
    .mo-timeline-dot.done {
        border-color: #0e7490;
        background: #0e7490;
        box-shadow: 0 0 0 4px rgba(14, 116, 144, 0.14);
    }
    .mo-timeline-dot.current {
        border-color: #2563eb;
        background: #2563eb;
        box-shadow: 0 0 0 5px rgba(37, 99, 235, 0.16);
        animation: moPulse 1.6s infinite;
    }
    .mo-timeline.returning .mo-timeline-progress,
    .mo-timeline.returned .mo-timeline-progress {
        background: linear-gradient(90deg, #ef4444, #f59e0b);
    }
    .mo-timeline.cancelled .mo-timeline-progress {
        background: #ef4444;
    }
    .mo-timeline.cancelled .mo-timeline-dot.done,
    .mo-timeline.returning .mo-timeline-dot.done,
    .mo-timeline.returned .mo-timeline-dot.done {
        border-color: #ef4444;
        background: #ef4444;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
    }
    .mo-timeline-labels {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 6px;
    }
    .mo-timeline-label {
        text-align: center;
        font-size: .68rem;
        line-height: 1.2;
        color: #64748b;
        font-weight: 600;
    }
    .mo-timeline-label.active {
        color: #0f172a;
    }
    @keyframes moPulse {
        0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.3); }
        100% { box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); }
    }
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

    $timelineSteps = ['Đặt đơn', 'Duyệt', 'Kho', 'Vận chuyển', 'Hoàn tất'];
    $timelineMap = [
        \App\Models\Order::STATUS_ORDER_PLACED => 0,
        'pending_leader_approval' => 1,
        'pending_manager_approval' => 1,
        'pending_warehouse_approval' => 1,
        \App\Models\Order::STATUS_ORDER_CONFIRMED => 1,
        \App\Models\Order::STATUS_APPROVED => 1,
        \App\Models\Order::STATUS_READY_TO_PACK => 2,
        \App\Models\Order::STATUS_PACKING => 2,
        \App\Models\Order::STATUS_PACKED => 2,
        \App\Models\Order::STATUS_READY_TO_SHIP => 3,
        \App\Models\Order::STATUS_DELIVERING => 3,
        \App\Models\Order::STATUS_IN_DELIVERY => 3,
        'shipping' => 3,
        'picked_up' => 3,
        \App\Models\Order::STATUS_DELIVERED => 4,
        \App\Models\Order::STATUS_COMPLETED => 4,
        \App\Models\Order::STATUS_RETURNING => 3,
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 4,
        \App\Models\Order::STATUS_RETURNED => 4,
        \App\Models\Order::STATUS_CANCELLED => 1,
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
                                <th>Timeline xử lý</th>
                                <th class="text-end">Tổng tiền</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                @php
                                    $statusClass = $statusClasses[$order->status] ?? 'muted';
                                    $timelineIndex = $timelineMap[$order->status] ?? 0;
                                    $timelinePercent = ($timelineIndex / 4) * 100;
                                    $timelineFlowClass = 'normal';
                                    if (in_array($order->status, [\App\Models\Order::STATUS_RETURNING], true)) {
                                        $timelineFlowClass = 'returning';
                                    } elseif (in_array($order->status, [\App\Models\Order::STATUS_RETURNED_COMPLETED, \App\Models\Order::STATUS_RETURNED], true)) {
                                        $timelineFlowClass = 'returned';
                                    } elseif (in_array($order->status, [\App\Models\Order::STATUS_CANCELLED, \App\Models\Order::STATUS_REJECTED], true)) {
                                        $timelineFlowClass = 'cancelled';
                                    }
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
                                        <div class="mo-timeline {{ $timelineFlowClass }}">
                                            <div class="mo-timeline-track">
                                                <div class="mo-timeline-progress" style="width: {{ $timelinePercent }}%;"></div>
                                                @foreach($timelineSteps as $stepIndex => $stepName)
                                                    @php
                                                        $dotClass = '';
                                                        if ($stepIndex < $timelineIndex) {
                                                            $dotClass = 'done';
                                                        } elseif ($stepIndex === $timelineIndex) {
                                                            $dotClass = 'current';
                                                        }
                                                    @endphp
                                                    <span class="mo-timeline-dot {{ $dotClass }}" title="{{ $stepName }}"></span>
                                                @endforeach
                                            </div>
                                            <div class="mo-timeline-labels">
                                                @foreach($timelineSteps as $stepIndex => $stepName)
                                                    <span class="mo-timeline-label {{ $stepIndex <= $timelineIndex ? 'active' : '' }}">{{ $stepName }}</span>
                                                @endforeach
                                            </div>
                                            <div class="mt-2">
                                                <span class="mo-status {{ $statusClass }}">{{ $statusLabels[$order->status] ?? ucfirst((string) $order->status) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($order->total ?? 0) }}đ</td>
                                    
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
