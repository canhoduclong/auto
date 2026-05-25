@extends('layouts.warehouse')

@section('title', 'Dashboard Kho hàng')
@section('subtitle_clock', '1')

@push('styles')
<style>
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
    
    .inv-summary-table tr.product-row {
        border-bottom: 2px solid #000 !important;
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
        border: 1.5px solid #6b3f19; 
        background: #fcfcfd;
        margin-bottom: 2px;
    }
    .inv-summary-table thead th {
        background: #6b3f19 !important;
        color: #fff !important;
        font-weight: 700;
        font-size: 1rem;
        border-bottom: 3px solid #eab308 !important;
        letter-spacing: 0.04em;
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

<div class="row g-3 mb-4">
    <!-- Cột trái: Nhiệm vụ hàng ngày và Thống kê tồn kho -->
    <div class="col-md-6">
         
        <div class="underline">
            <span class="fs-5 fw-semibold progress-title-underline d-flex align-items-center text-uppercase">Tiến độ công việc hôm nay</span>
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
                    'label' => 'Đơn điều chuyển đến',
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
    <div class="col-md-6">
                <!-- Thống kê tồn kho -->
         <div class="underline">
            <span class="fs-5 fw-semibold progress-title-underline d-flex align-items-center text-uppercase">Thống kê tồn kho</span>
        </div>
        <div class="inv-summary-list mb-4">
            <div class="inv-summary-head my-2">Danh sách thống kê tồn kho (sản phẩm và biến thể cùng một cấu trúc cột)</div>
            <div class="table-responsive">
                <table class="inv-summary-table">
                    <thead>
                        <tr>
                            <th style="min-width: 280px;">Tên sản phẩm / biến thể</th>
                            <th style="min-width: 100px;">DVT</th>
                            <th class="num" style="min-width: 110px;">Tồn đầu</th>
                            <th class="num" style="min-width: 90px;">Nhập</th>
                            <th class="num" style="min-width: 100px;">Book</th>
                            <th class="num" style="min-width: 90px;">Xuất</th>
                            <th class="num" style="min-width: 120px;">Tồn cuối</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $filteredRows = $summaryRows->filter(function($row) {
                                return $row['closing'] > 0;
                            });
                        @endphp
                        @forelse($filteredRows as $row)
                                <tr class="product-row">
                                    <td style="background: none !important;">
                                        <button type="button" class="inv-toggle js-inv-toggle border-0" style="background: none !important;" data-target="inv-child-{{ $row['product_id'] }}">
                                            <span class="icon-plus" style="display:inline;">+</span>
                                            <span class="icon-minus" style="display:none;">&minus;</span>
                                            <span class="inv-product-name">{{ $row['name'] }}</span>
                                        </button>
                                    </td>
                                <td>{{ $row['unit'] }}</td>
                                <td class="num"><strong>{{ number_format($row['opening']) }}</strong></td>
                                <td class="num">{{ number_format($row['import']) }}</td>
                                <td class="num" style="color:#1d4ed8;">{{ number_format($row['reserved']) }}</td>
                                <td class="num">{{ number_format($row['export']) }}</td>
                                <td class="num">{{ number_format($row['closing']) }}</td>
                            </tr>
                            <tr id="inv-child-{{ $row['product_id'] }}" class="inv-child-row">
                                <td colspan="7" class="p-0">
                                    <table class="table table-sm mb-0 w-100">
                                        <tbody>
                                        @foreach($row['variants'] as $variantRow)
                                            @if($variantRow['closing'] > 0)
                                                <tr class="variant-row">
                                                    <td colspan="7" class="p-0">
                                                        <div class="inv-variant-block d-flex align-items-center px-2 py-1">
                                                            <div class="inv-indent flex-grow-1">{{ $variantRow['name'] }}</div>
                                                            <div style="min-width:80px">{{ $variantRow['unit'] }}</div>
                                                            <div class="num" style="min-width:90px">{{ number_format($variantRow['opening']) }}</div>
                                                            <div class="num" style="min-width:70px">{{ number_format($variantRow['import']) }}</div>
                                                            <div class="num" style="min-width:90px;color:#1d4ed8;">{{ number_format($variantRow['reserved']) }}</div>
                                                            <div class="num" style="min-width:70px">{{ number_format($variantRow['export']) }}</div>
                                                            <div class="num" style="min-width:100px">{{ number_format($variantRow['closing']) }}</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Không có dữ liệu sản phẩm trong danh sách hiện tại.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @push('scripts')
        <script>
        document.querySelectorAll('.js-inv-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-target');
                const headerRow = document.getElementById(targetId);
                if (!headerRow) return;
                const productId = targetId.replace('inv-child-', '');
                const childRows = document.querySelectorAll('.inv-child-of-' + productId);
                const isOpen = headerRow.style.display === 'table-row';
                const nextDisplay = isOpen ? 'none' : 'table-row';
                headerRow.style.display = nextDisplay;
                childRows.forEach(function (row) { row.style.display = nextDisplay; });
                // Toggle icons
                const plus = button.querySelector('.icon-plus');
                const minus = button.querySelector('.icon-minus');
                if (!isOpen) {
                    plus.style.display = 'none';
                    minus.style.display = 'inline';
                } else {
                    plus.style.display = 'inline';
                    minus.style.display = 'none';
                }
            });
        });
        // Mặc định ẩn hết các dòng biến thể
        document.querySelectorAll('.inv-child-row').forEach(function(row) {
            row.style.display = 'none';
        });
        </script>
        @endpush

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
