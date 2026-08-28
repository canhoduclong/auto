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
    .inv-col-unit, .inv-col-opening, .inv-col-import, .inv-col-reserved, .inv-col-available, .inv-col-export, .inv-col-closing {
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

<div class="row g-3 mb-4">
    <!-- Cột trái: Nhiệm vụ hàng ngày và Thống kê tồn kho -->
    <div class="col-md-12 col-lg-4"> 
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
    <div class="col-md-12 col-lg-8">
        <div class="mb-3 toolsdaily align-items-center ">
            <a href="{{ route('warehouse.stock-in.create') }}" class="btn btn-sm btn-success fw-700" >
                <i class="bi bi-plus-circle me-1"></i> Tạo phiếu nhập kho
            </a>
            <a href="{{ route('warehouse.stocktakes.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-clipboard2-check me-1"></i>Kiểm kê kho
            </a>
        </div>
        @if(($deferredComponentImportRequests ?? collect())->isNotEmpty())
            <div class="card border-warning shadow-sm mb-4">
                <div class="card-header bg-warning-subtle justify-content-between align-items-center gap-2 flex-wrap">
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
