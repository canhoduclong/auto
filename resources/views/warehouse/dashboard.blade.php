@extends('layouts.warehouse')

@section('title', 'Dashboard Kho hàng')
@section('subtitle_clock', '1')

@push('styles')
<style>
/* Responsive cho thống kê tồn kho layout div */
.inv-summary-list {
    overflow-x: auto;
}
.inv-summary-table-div {
    min-width: 550px;
}
@media (max-width: 767.98px) {
    .inv-summary-header,
    .product-row-div > .d-flex,
    .variant-row-div > .d-flex {
        font-size: 0.92rem;
        padding-left: 0.2rem;
        padding-right: 0.2rem;
    }
    .inv-summary-header > div,
    .product-row-div > .d-flex > div,
    .variant-row-div > .d-flex > div {
        padding-left: 0.2rem !important;
        padding-right: 0.2rem !important;
    }
    .inv-col-name { min-width: 120px; }
    .inv-col-unit, .inv-col-opening, .inv-col-import, .inv-col-reserved, .inv-col-export, .inv-col-closing {
        min-width: 60px !important;
        font-size: 0.92rem;
    }
    .inv-product-name, .inv-indent { font-size: 0.95rem; }
    .inv-variant-block { flex-wrap: wrap; }
}
    .task-status-badge {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        color: #fff;
        font-weight: 700;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
    }
    .task-status-todo { background: #dc3545; }
    .task-status-inprogress { background: #f59e42; }
    .task-status-none { background: #b0b0b0; }
    .task-status-done { background: #198754; }
    .task-desc-legend {
        display: flex;
        gap: 1.5rem;
        margin-top: 1.2rem;
        margin-bottom: 0.5rem;
        font-size: .98rem;
        align-items: center;
    }
    .task-desc-legend span {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .task-desc-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: inline-block;
    }
    .task-dot-todo { background: #dc3545; }
    .task-dot-inprogress { background: #f59e42; }
    .task-dot-none { background: #b0b0b0; }
    .task-dot-done { background: #198754; }

    .progress-title-underline {
        display: inline-block;
        position: relative;
        font-size: 1.35rem;
        font-weight: 800;
        color: #6b3f19;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    .progress-title-underline:after {
        content: '';
        display: block;
        width: 53%;
        height: 5px;
        background: #6b3f19;
        border-radius: 3px;
        margin-top: 6px;
        margin-left: 12px;
    }
    

    .wh-filter-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .07);
    }
    .wh-stat-soft {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .07);
    }
    .wh-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        display: inline-block;
    }
    .wh-status-chip {
        font-size: .75rem;
        font-weight: 700;
        border-radius: 999px;
        padding: 5px 10px;
        display: inline-block;
    }
    .wh-table-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .07);
        overflow: hidden;
    }
    .wh-table-card table th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .wh-btn-sync {
        min-height: 42px;
        padding: .48rem .95rem;
        border-radius: 10px;
        font-size: .9rem;
        font-weight: 600;
        line-height: 1.2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
    }
    .wh-btn-sync i {
        line-height: 1;
    }
    .wh-btn-sync .badge {
        line-height: 1;
        margin-left: .1rem;
    }
    .inv-summary-table{
        width: 100%;
        /*
        border-collapse: separate;
        border-spacing: 0;
        */
    }
    .inv-summary-table tr.product-row {
        border-bottom: 1px solid #d9c3a8 !important;
        background: #fffbe7 !important;
        transition: background 0.2s;
    }
    .inv-summary-table tr.product-row:hover {
        background: #fde68a !important;
    }
    .inv-summary-table tr.variant-row {
        /*border-bottom: 1.5px solid #6b3f19 !important;*/
    }
    .inv-variant-block { 
        background: #fcfcfd;
        margin-bottom: 1px;
    }
    .inv-summary-table thead th {
        background: #6b3f19 !important;
        color: #fff !important;
        font-weight: 700;
        font-size: 1rem;
        border-bottom: 3px solid #eab308 !important;
        letter-spacing: 0.04em;
    }
    .subname{
        padding-left: 20px;
    }
    .production-hero { border: 0; border-radius: 16px; background: linear-gradient(135deg, #084c47, #0f766e); color: #fff; box-shadow: 0 12px 28px rgba(8,76,71,.18); }
    .production-date-form { display: flex; gap: .5rem; align-items: center; }
    .production-date-form input { min-width: 150px; }
    .production-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; }
    .production-kpi { padding: 14px; border: 1px solid #dbe9e7; border-radius: 14px; background: #fff; box-shadow: 0 6px 16px rgba(15,23,42,.05); }
    .production-kpi-label { min-height: 34px; color: #64748b; font-size: .76rem; font-weight: 700; text-transform: uppercase; }
    .production-kpi-value { margin-top: 5px; color: #153f3b; font-size: 1.25rem; font-weight: 850; }
    .production-kpi-note { color: #7c8b99; font-size: .73rem; }
    .production-panel { height: 100%; border: 0; border-radius: 14px; box-shadow: 0 7px 20px rgba(15,23,42,.06); overflow: hidden; }
    .production-panel .card-header { padding: .8rem 1rem; background: #fff; border-bottom-color: #e5efed; font-weight: 800; }
    .production-table { font-size: .83rem; }
    .production-table th { color: #64748b; font-size: .7rem; text-transform: uppercase; white-space: nowrap; }
    .production-trend { display: flex; min-height: 165px; align-items: end; gap: 10px; padding: 14px 8px 4px; }
    .production-trend-day { display: flex; flex: 1; min-width: 40px; height: 145px; flex-direction: column; justify-content: end; align-items: center; gap: 4px; }
    .production-trend-bar { width: min(34px, 70%); min-height: 2px; border-radius: 7px 7px 2px 2px; background: linear-gradient(180deg, #24a99d, #0f766e); }
    .production-trend-value { color: #334155; font-size: .66rem; font-weight: 750; white-space: nowrap; }
    .production-trend-label { color: #64748b; font-size: .68rem; }
    @media (max-width: 1199.98px) { .production-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 575.98px) {
        .production-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .production-hero .card-body { display: block !important; }
        .production-date-form { margin-top: 12px; }
        .production-date-form input { min-width: 0; width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $statusMap = [
        'pending_leader_approval' => ['label' => 'Chờ leader duyệt', 'class' => 'bg-warning text-dark'],
        'pending_manager_approval' => ['label' => 'Chờ manager duyệt', 'class' => 'bg-warning text-dark'],
        'pending_warehouse_approval' => ['label' => 'Chờ kho duyệt', 'class' => 'bg-warning text-dark'],
        'approved' => ['label' => 'Đã duyệt', 'class' => 'bg-primary'],
        'ready_to_pack' => ['label' => 'Chờ đóng gói', 'class' => 'bg-primary'],
        'packing' => ['label' => 'Đang đóng gói', 'class' => 'bg-info text-dark'],
        'packed' => ['label' => 'Đã đóng gói', 'class' => 'bg-success'],
        'packed_waiting_pickup' => ['label' => 'Chờ shipper nhận', 'class' => 'bg-success'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'bg-danger'],
    ];
@endphp

@include('layouts.partials.department_broadcasts')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

@php
    $productionSummary = $productionDashboard['summary'] ?? [];
@endphp
<section class="mb-4" aria-labelledby="production-dashboard-title">
    <div class="card production-hero mb-3">
        <div class="card-body d-flex align-items-center justify-content-between gap-3">
            <div>
                <h1 id="production-dashboard-title" class="h4 fw-bold mb-1">Bảng điều khiển sản xuất nhà máy</h1>
                <div class="small text-white-50">Theo dõi nhập vịt, phân loại, sản lượng, hao hụt và chi phí theo kho.</div>
            </div>
            <form method="GET" action="{{ route('warehouse.dashboard') }}" class="production-date-form">
                <label for="production-date" class="visually-hidden">Ngày vận hành</label>
                <input id="production-date" type="date" name="date" value="{{ $productionDashboard['date'] ?? $selectedDate->toDateString() }}" class="form-control form-control-sm">
                <button class="btn btn-warning btn-sm fw-bold" type="submit"><i class="bi bi-funnel me-1"></i>Xem ngày</button>
            </form>
        </div>
    </div>

    <div class="production-kpis mb-3">
        <div class="production-kpi">
            <div class="production-kpi-label">Vịt/hàng nhập</div>
            <div class="production-kpi-value">{{ number_format((int) ($productionSummary['input_quantity'] ?? 0), 0, ',', '.') }} con</div>
            <div class="production-kpi-note">{{ format_kg($productionSummary['input_weight'] ?? 0) }} · {{ $productionSummary['receipt_count'] ?? 0 }} phiếu</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Thành phẩm sản xuất</div>
            <div class="production-kpi-value">{{ format_kg($productionSummary['finished_weight'] ?? 0) }}</div>
            <div class="production-kpi-note">{{ $productionSummary['production_batch_count'] ?? 0 }} mẻ · Hiệu suất {{ number_format($productionSummary['yield_percent'] ?? 0, 2, ',', '.') }}%</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Phụ phẩm thu hồi</div>
            <div class="production-kpi-value">{{ format_kg($productionSummary['component_weight'] ?? 0) }}</div>
            <div class="production-kpi-note">Đã ghi nhận từ các mẻ hoàn tất</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Hao hụt sản xuất</div>
            <div class="production-kpi-value text-danger">{{ format_kg($productionSummary['loss_weight'] ?? 0) }}</div>
            <div class="production-kpi-note">{{ number_format($productionSummary['loss_percent'] ?? 0, 2, ',', '.') }}% đầu vào</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Hàng loại khi nhập</div>
            <div class="production-kpi-value text-warning-emphasis">{{ format_kg($productionSummary['reject_weight'] ?? 0) }}</div>
            <div class="production-kpi-note">Theo phân loại thực nhận</div>
        </div>
        <div class="production-kpi">
            <div class="production-kpi-label">Chi phí nhập bình quân</div>
            <div class="production-kpi-value">{{ number_format($productionSummary['average_input_cost'] ?? 0, 0, ',', '.') }}đ/kg</div>
            <div class="production-kpi-note">Hao hụt ước tính {{ number_format($productionSummary['estimated_loss_cost'] ?? 0, 0, ',', '.') }}đ</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-success"></i>Sản lượng theo loại và size</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle production-table mb-0">
                        <thead><tr><th>Sản phẩm</th><th>Size</th><th class="text-end">Số mẻ</th><th class="text-end">Đầu vào</th><th class="text-end">Thành phẩm</th><th class="text-end">Hao hụt</th></tr></thead>
                        <tbody>
                            @forelse(($productionDashboard['production_by_variant'] ?? []) as $row)
                                <tr>
                                    <td class="fw-semibold">{{ trim($row['product_name'].' '.$row['variant_name']) }}</td>
                                    <td>{{ $row['size'] !== null ? number_format($row['size'], 1, ',', '.').' kg' : '—' }}</td>
                                    <td class="text-end">{{ $row['batch_count'] }}</td>
                                    <td class="text-end">{{ format_kg($row['input_weight']) }}</td>
                                    <td class="text-end fw-bold text-success">{{ format_kg($row['finished_weight']) }}</td>
                                    <td class="text-end text-danger">{{ format_kg($row['loss_weight']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có mẻ sản xuất hoàn tất trong ngày.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Thành phẩm 7 ngày gần nhất</div>
                <div class="card-body pt-1">
                    <div class="production-trend" aria-label="Biểu đồ sản lượng thành phẩm 7 ngày">
                        @foreach(($productionDashboard['trend'] ?? []) as $day)
                            @php
                                $barHeight = max(2, round(($day['finished_weight'] / ($productionDashboard['trend_max'] ?? 1)) * 105));
                            @endphp
                            <div class="production-trend-day" title="Thành phẩm {{ format_kg($day['finished_weight']) }}; hao hụt {{ format_kg($day['loss_weight']) }}">
                                <span class="production-trend-value">{{ number_format($day['finished_weight'], 0, ',', '.') }}</span>
                                <span class="production-trend-bar" style="height: {{ $barHeight }}px"></span>
                                <span class="production-trend-label">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-truck me-2 text-success"></i>Đầu vào theo loại</div>
                <div class="table-responsive"><table class="table table-sm production-table mb-0">
                    <thead><tr><th>Loại vịt/hàng</th><th class="text-end">Số lượng</th><th class="text-end">Khối lượng</th></tr></thead>
                    <tbody>
                    @forelse(($productionDashboard['input_types'] ?? []) as $row)
                        <tr><td class="fw-semibold">{{ $row['label'] }}</td><td class="text-end">{{ number_format($row['quantity'], 0, ',', '.') }}</td><td class="text-end">{{ format_kg($row['weight']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Chưa có phiếu nhập trong ngày.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-ui-checks-grid me-2 text-success"></i>Phân loại thực nhận</div>
                <div class="table-responsive"><table class="table table-sm production-table mb-0">
                    <thead><tr><th>Loại</th><th>Size</th><th class="text-end">Số lượng</th><th class="text-end">Kg</th></tr></thead>
                    <tbody>
                    @forelse(($productionDashboard['classifications'] ?? []) as $row)
                        <tr class="{{ $row['type'] === 'reject' ? 'table-warning' : '' }}"><td class="fw-semibold">{{ $row['type_label'] }}</td><td>{{ $row['size'] !== null ? number_format($row['size'], 1, ',', '.') : '—' }}</td><td class="text-end">{{ number_format($row['quantity'], 0, ',', '.') }}</td><td class="text-end">{{ number_format($row['weight'], 1, ',', '.') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu phân loại.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card production-panel">
                <div class="card-header"><i class="bi bi-cash-stack me-2 text-success"></i>Cơ cấu chi phí</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span>Giá trị hàng</span><strong>{{ number_format($productionSummary['purchase_cost'] ?? 0, 0, ',', '.') }}đ</strong></div>
                    @foreach(($productionDashboard['operating_costs'] ?? []) as $cost)
                        <div class="d-flex justify-content-between small py-1 border-top"><span>{{ $cost['label'] }}</span><strong>{{ number_format($cost['amount'], 0, ',', '.') }}đ</strong></div>
                    @endforeach
                    <div class="d-flex justify-content-between mt-2 pt-2 border-top border-2"><span class="fw-bold">Tổng chi phí nhập</span><strong class="text-success">{{ number_format($productionSummary['total_cost'] ?? 0, 0, ',', '.') }}đ</strong></div>
                    <div class="alert alert-warning small mt-3 mb-0 py-2"><i class="bi bi-exclamation-triangle me-1"></i>Giá trị hao hụt ước tính theo giá nhập bình quân: <strong>{{ number_format($productionSummary['estimated_loss_cost'] ?? 0, 0, ',', '.') }}đ</strong></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="row g-3 mb-4">
    <!-- Cột trái: Nhiệm vụ hàng ngày và Thống kê tồn kho -->
    <div class="col-md-12 col-lg-6"> 
        <div class="underline mb-4">
            <span class="fw-semibold progress-title-underline d-flex align-items-center text-uppercase">Tiến độ công việc</span>
        </div>
        <div class="mb-4">
            @php
            $tasks = [
                [
                    'label' => 'Đơn cần đóng gói',
                    'total' => ($stats['ready_to_pack'] ?? 0) + ($stats['packing'] ?? 0) + ($stats['packed'] ?? 0),
                    'done' => $stats['packed'] ?? 0,
                    'route' => route('warehouse.orders'),
                ],
                [
                    'label' => 'Tiếp nhận Đơn',
                    'total' => ($stats['transfers_incoming'] ?? 0) + ($stats['transfers_completed'] ?? 0),
                    'done' => $stats['transfers_completed'] ?? 0,
                    'route' => route('warehouse.transfers.incoming'),
                ],
                [
                    'label' => 'Tiếp nhận hàng',
                    'total' => ($stats['receiving'] ?? 0) + ($stats['received'] ?? 0),
                    'done' => $stats['received'] ?? 0,
                    'route' => route('warehouse.inventory-transfers.incoming'),
                ],
                [
                    'label' => 'Tiếp nhận đơn hoàn trả',
                    'total' => ($stats['returning'] ?? 0) + ($stats['returned'] ?? 0),
                    'done' => $stats['returned'] ?? 0,
                    'route' => route('warehouse.returns'),
                ],
                [
                    'label' => 'Nhiệm vụ được giao',
                    'total' => ($stats['assigned_tasks'] ?? 0) + ($stats['completed_tasks'] ?? 0),
                    'done' => $stats['completed_tasks'] ?? 0,
                    'route' => route('tasks.my-tasks'),
                ],
                [
                    'label' => 'Kiểm kê kho',
                    'total' => 1,
                    'done' => min(1, (int) ($stats['stocktakes_completed'] ?? 0)),
                    'route' => route('warehouse.stocktakes.index'),
                ],
            ];
            $colorMap = [
                'todo' => 'task-status-todo',
                'inprogress' => 'task-status-inprogress',
                'none' => 'task-status-none',
                'done' => 'task-status-done',
            ];
            function getTaskStatus($total, $done) {
                if ($total == 0) return 'none';
                if ($done == 0 && $total > 0) return 'todo';
                if ($done > 0 && $done < $total) return 'inprogress';
                return 'done';
            }
            @endphp
            <ul class="list-unstyled mb-0">
                @foreach($tasks as $i => $task)
                    @php
                        $percent = ($task['total'] > 0) ? round($task['done'] / $task['total'] * 100) : 0;
                        $status = getTaskStatus($task['total'], $task['done']);
                        $badgeClass = $colorMap[$status];
                        $isDone = $status === 'done';
                    @endphp
                    <li class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <span class="task-status-badge {{ $badgeClass }}">{{ $i+1 }}</span>
                            <span class="fw-semibold flex-grow-1">{{ $task['label'] }}</span>
                            <span class="ms-2 text-muted small">{{ $task['done'] }}/{{ $task['total'] }}</span>
                            <span class="ms-2 text-primary small">{{ $percent }}%</span>
                            @if($isDone)
                                <span class="ms-2 text-success fs-4"><i class="bi bi-check-circle-fill"></i></span>
                            @endif
                            <a href="{{ $task['route'] }}" class="btn btn-outline-primary btn-sm ms-3">Chi tiết</a>
                        </div>
                        <div class="progress" style="height: 18px;">
                            <div class="progress-bar {{ $isDone ? 'bg-success' : ($status === 'inprogress' ? 'bg-warning' : ($status === 'todo' ? 'bg-danger' : 'bg-secondary')) }}" role="progressbar" style="width: {{ $percent }}%; font-size:.98rem;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">{{ $percent }}%</div>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if(($stats['receiving'] ?? 0) > 0)
                <div class="alert alert-danger mt-3" style="font-size:1.05rem;">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <b>Cần tiếp nhận hàng:</b> Hiện có <b>{{ $stats['receiving'] }}</b> phiếu điều chuyển chờ tiếp nhận!
                    <a href="{{ route('warehouse.inventory-transfers.incoming') }}" class="ms-2 text-danger text-decoration-underline">Xem chi tiết</a>
                </div>
            @endif
            <div class="task-desc-legend">
                <span><span class="task-desc-dot task-dot-todo"></span> Cần làm</span>
                <span><span class="task-desc-dot task-dot-inprogress"></span> Đang làm</span>
                <span><span class="task-desc-dot task-dot-done"></span> Đã hoàn thành</span>
                <span><span class="task-desc-dot task-dot-none"></span> Chưa có nhiệm vụ</span>
            </div>
        </div>
        
        <!--Thống kê  -->
        <div class="report-work mb-4"> 
            <div class="report-body">
                <ul class="mb-2 ps-3">
                @php $hasWork = false; @endphp
                @foreach($tasks as $task)
                    @php
                        $percent = ($task['total'] > 0) ? round($task['done'] / $task['total'] * 100) : 0;
                        if ($task['total'] == 0 || $task['done'] >= $task['total']) continue;
                        $hasWork = true;
                        $remind = '';
                        if ($task['done'] == 0) {
                            $remind = 'Hãy bắt đầu ngay!';
                        } else {
                            $remind = 'Hãy tiếp tục...';
                        }
                    @endphp
                    <li class="mb-1">
                        <span class="fw-semibold">{{ $task['label'] }}</span>
                        <span class="text-primary">hoàn thiện {{ $percent }}%</span>
                        <span class="text-muted">- {{ $remind }}</span>
                    </li>
                @endforeach
                @if(!$hasWork)
                    <li class="text-success">Chưa có việc được giao</li>
                @endif
                </ul>
            </div>
        </div>

        <!-- Listchanges chuyển sang cột trái phía dưới -->
        <div class="listchanges mb-4">
            <ul class="list-unstyled mb-0">
                <li class="mb-3"><i class="bi bi-pencil-square text-primary me-2"></i>Yêu cầu thay đổi đơn hàng từ sale <span class="badge bg-info text-dark">Mới</span></li>
                <li class="mb-3"><i class="bi bi-chat-dots text-success me-2"></i>Sale trả lời khách hàng <span class="badge bg-success">Đã trả lời</span></li>
                <li class="mb-3"><i class="bi bi-truck text-warning me-2"></i>Phiếu điều chuyển kho chờ ship nhận <span class="badge bg-warning text-dark">Chờ ship</span></li>
                <!-- Thêm các nghiệp vụ thực tế tại đây -->
            </ul>
        </div>

    </div>
    <!-- Cột phải: Nghiệp vụ mới nhất -->
    <div class="col-md-12 col-lg-6">
        <div class="mb-3 toolsdaily d-flex align-items-center gap-3 flex-wrap">
            <a href="{{ route('warehouse.stock-in.create') }}" class="btn btn-success fw-700" >
                <i class="bi bi-plus-circle me-1"></i> Tạo phiếu nhập kho
            </a>
            <a href="{{ route('warehouse.stocktakes.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-clipboard2-check me-1"></i>Kiểm kê kho
            </a>
        </div>
        @if(($deferredComponentImportRequests ?? collect())->isNotEmpty())
            <div class="card border-warning shadow-sm mb-4">
                <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div>
                        <div class="fw-semibold">
                            <i class="bi bi-hourglass-split me-1"></i>Yêu cầu nhập kho thành phần pha lóc
                        </div>
                        <div class="small text-muted">Các thành phần được chọn "nhập sau" trong ngày sẽ gom vào phiếu đang mở.</div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @foreach($deferredComponentImportRequests as $request)
                        @php
                            $groupedItems = $request->items
                                ->groupBy('product_variant_id')
                                ->map(function ($items) {
                                    $first = $items->first();
                                    return [
                                        'variant' => $first?->productVariant,
                                        'quantity' => (float) $items->sum('quantity'),
                                        'orders' => $items->pluck('source_order_code')->filter()->unique()->values(),
                                    ];
                                })
                                ->values();
                        @endphp
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                                <div>
                                    <div class="fw-semibold">Phiếu yêu cầu #{{ $request->id }} - {{ $request->warehouse?->name ?? 'Kho' }}</div>
                                    <div class="small text-muted">
                                        Ngày {{ optional($request->request_date)->format('d/m/Y') }} · {{ $request->items->count() }} dòng từ pha lóc
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('warehouse.cutting-component-import-requests.receive', $request) }}" onsubmit="return confirm('Xác nhận nhập kho các thành phần còn lại trong phiếu này?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-check2-circle me-1"></i>Xác nhận nhập kho
                                    </button>
                                </form>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Thành phần</th>
                                            <th class="text-end" style="width:130px;">Khối lượng</th>
                                            <th style="width:180px;">Đơn liên quan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($groupedItems as $row)
                                            <tr>
                                                <td class="fw-semibold">
                                                    {{ trim(($row['variant']?->product?->name ?? 'Sản phẩm') . ' ' . ($row['variant']?->name ?: '')) }}
                                                </td>
                                                <td class="text-end">{{ format_kg($row['quantity']) }}</td>
                                                <td class="small text-muted">{{ $row['orders']->isNotEmpty() ? $row['orders']->implode(', ') : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        <!-- Thống kê tồn kho -->
        <div class="underline mb-4 d-flex align-items-center gap-2">
            <span class="fw-semibold progress-title-underline d-flex align-items-center text-uppercase">Thống kê tồn kho</span>
        </div>
        @include('warehouse._inventory_summary', [
            'title' => 'Tồn kho hôm nay - kho đang quản lý',
            'rows' => $inventorySummary['rows'],
            'targetPrefix' => 'managed'
        ])

        <div class="inv-summary-list mb-4">
            <div class="inv-summary-head my-2 d-flex align-items-center justify-content-between gap-2">
                <span class="fw-semibold text-uppercase">Hàng pha lóc</span> 
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th class="text-end">Tồn kho hiện tại</th>
                            <th class="text-end">Nhu cầu</th>
                            <th class="text-end">Thiếu</th>
                            <th style="width:120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cuttingShortages as $shortage)
                            <tr>
                                <td class="fw-semibold">{{ $shortage['name'] }}</td>
                                <td class="text-end">{{ format_kg($shortage['current_stock']) }}</td>
                                <td class="text-end">{{ format_kg($shortage['demand']) }}</td>
                                <td class="text-end text-danger fw-bold">{{ format_kg($shortage['shortage']) }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-primary" href="{{ route('warehouse.cutting.form', ['variant' => $shortage['variant_id'], 'demand' => $shortage['demand']]) }}">
                                        Pha lóc
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Chưa có hàng pha lóc còn thiếu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div> 
</div>
                    
{{-- Recent packed orders --}}
@if($recentPacked->isNotEmpty())
<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history me-1 text-secondary"></i> Đơn đóng xong gần đây theo ngày đã chọn
    </div>
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Tổng tiền</th>
                <th>Cập nhật lúc</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentPacked as $i => $order)
            <tr>
                <td class="text-muted">{{ $i + 1 }}</td>
                <td class="fw-semibold">{{ $order->code }}</td>
                <td>{{ $order->customer?->name ?? '—' }}</td>
                <td>{{ number_format($order->total) }}đ</td>
                <td class="text-muted small">{{ $order->updated_at->format('H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-inv-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const target = document.getElementById(button.dataset.target);
        if (!target) return;
        const open = target.style.display !== 'none';
        target.style.display = open ? 'none' : 'block';
        const plus = button.querySelector('.icon-plus');
        const minus = button.querySelector('.icon-minus');
        if (plus) plus.style.display = open ? 'inline' : 'none';
        if (minus) minus.style.display = open ? 'none' : 'inline';
    });
});
</script>
@endpush
