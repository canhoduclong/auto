@extends('layouts.package')

@section('title', 'Tổng quan đóng hàng')

@push('styles')
<style>
.pkg-dashboard-title {
    display:inline-block; color:#6b3f19; font-size:1.35rem; font-weight:800;
    letter-spacing:.04em; text-transform:uppercase;
}
.pkg-dashboard-title::after {
    content:''; display:block; width:53%; height:5px; margin:6px 0 0 12px;
    border-radius:3px; background:#6b3f19;
}
.pkg-task-badge {
    width:36px; height:36px; flex:0 0 36px; display:inline-flex;
    align-items:center; justify-content:center; border-radius:50%;
    color:#fff; font-size:1.05rem; font-weight:800;
}
.pkg-task-todo { background:#dc3545; }
.pkg-task-progress { background:#f59e42; }
.pkg-task-none { background:#b0b0b0; }
.pkg-task-done { background:#198754; }
.pkg-task-card, .pkg-inventory-card {
    border:0; border-radius:14px; background:#fff;
    box-shadow:0 8px 20px rgba(15,23,42,.07);
}
.pkg-legend { display:flex; flex-wrap:wrap; gap:14px; color:#64748b; font-size:.84rem; }
.pkg-legend-dot { width:14px; height:14px; display:inline-block; border-radius:50%; margin-right:4px; }
.pkg-inventory-scroll { overflow-x:auto; }
.pkg-inventory-table { min-width:720px; }
.pkg-inventory-head, .pkg-inventory-row {
    display:grid; grid-template-columns:minmax(190px,1fr) 75px repeat(5,80px);
    align-items:center;
}
.pkg-inventory-head { background:#6b3f19; color:#fff; font-weight:700; }
.pkg-inventory-head > div, .pkg-inventory-row > div { padding:9px 8px; }
.pkg-product-row { background:#fffbe7; border-bottom:1px solid #ead8bd; }
.pkg-variant-row { background:#fcfcfd; border-bottom:1px solid #edf0f3; }
.pkg-num { text-align:right; }
.pkg-toggle { border:0; background:transparent; padding:0; font-weight:700; color:#334155; text-align:left; }
@media (max-width:767.98px) {
    .pkg-dashboard-title { font-size:1.08rem; }
    .pkg-task-line { align-items:flex-start !important; }
    .pkg-task-line .btn { margin-left:auto !important; }
}
</style>
@endpush

@section('content')
@php
    $tasks = [
        [
            'label' => 'Đơn cần đóng gói',
            'total' => ($stats['ready_to_pack'] ?? 0) + ($stats['packing'] ?? 0) + ($stats['packed'] ?? 0),
            'done' => $stats['packed'] ?? 0,
            'route' => route('package.orders'),
        ],
        [
            'label' => 'Đơn đang đóng gói',
            'total' => ($stats['packing'] ?? 0) + ($stats['packed'] ?? 0),
            'done' => $stats['packed'] ?? 0,
            'route' => route('package.orders'),
        ],
        [
            'label' => 'Tiếp nhận hàng trả về',
            'total' => ($stats['returning'] ?? 0) + ($stats['returned'] ?? 0),
            'done' => $stats['returned'] ?? 0,
            'route' => route('package.returns'),
        ],
        [
            'label' => 'Yêu cầu thay đổi đơn',
            'total' => $stats['change_requests'] ?? 0,
            'done' => 0,
            'route' => route('package.order-changes'),
        ],
    ];
@endphp

<form method="GET" class="card pkg-task-card mb-4">
    <div class="card-body d-flex flex-wrap align-items-end gap-2">
        <div>
            <label class="form-label small text-muted mb-1">Ngày theo dõi</label>
            <input type="date" name="date" class="form-control" value="{{ $date }}">
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Lọc</button>
        <a href="{{ route('package.dashboard') }}" class="btn btn-outline-secondary">Hôm nay</a>
    </div>
</form>

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="mb-4"><span class="pkg-dashboard-title">Tiến độ công việc</span></div>
        <div class="card pkg-task-card">
            <div class="card-body">
                @foreach($tasks as $index => $task)
                    @php
                        $percent = $task['total'] > 0 ? min(100, (int) round($task['done'] / $task['total'] * 100)) : 0;
                        $state = $task['total'] === 0 ? 'none' : ($task['done'] === 0 ? 'todo' : ($task['done'] < $task['total'] ? 'progress' : 'done'));
                    @endphp
                    <div class="{{ !$loop->last ? 'mb-4' : '' }}">
                        <div class="d-flex gap-2 align-items-center mb-2 pkg-task-line">
                            <span class="pkg-task-badge pkg-task-{{ $state }}">{{ $index + 1 }}</span>
                            <span class="fw-semibold flex-grow-1">{{ $task['label'] }}</span>
                            <span class="small text-muted">{{ $task['done'] }}/{{ $task['total'] }}</span>
                            <span class="small text-primary fw-semibold">{{ $percent }}%</span>
                            @if($state === 'done')<i class="bi bi-check-circle-fill text-success fs-5"></i>@endif
                            <a href="{{ $task['route'] }}" class="btn btn-outline-primary btn-sm">Chi tiết</a>
                        </div>
                        <div class="progress" style="height:18px;">
                            <div class="progress-bar {{ $state === 'done' ? 'bg-success' : ($state === 'progress' ? 'bg-warning' : ($state === 'todo' ? 'bg-danger' : 'bg-secondary')) }}"
                                 style="width:{{ $percent }}%">{{ $percent }}%</div>
                        </div>
                    </div>
                @endforeach
                <div class="pkg-legend mt-4">
                    <span><i class="pkg-legend-dot pkg-task-todo"></i>Cần làm</span>
                    <span><i class="pkg-legend-dot pkg-task-progress"></i>Đang làm</span>
                    <span><i class="pkg-legend-dot pkg-task-done"></i>Đã hoàn thành</span>
                    <span><i class="pkg-legend-dot pkg-task-none"></i>Chưa có nhiệm vụ</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="mb-4"><span class="pkg-dashboard-title">Thống kê tồn kho</span></div>
        <div class="card pkg-inventory-card">
            <div class="card-body">
                <div class="text-muted small mb-2">Danh sách sản phẩm và biến thể còn tồn trong kho được gán.</div>
                <div class="pkg-inventory-scroll">
                    <div class="pkg-inventory-table">
                        <div class="pkg-inventory-head">
                            <div>Tên sản phẩm / biến thể</div><div>DVT</div>
                            <div class="pkg-num">Tồn đầu</div><div class="pkg-num">Nhập</div>
                            <div class="pkg-num">Book</div><div class="pkg-num">Xuất</div><div class="pkg-num">Tồn cuối</div>
                        </div>
                        @forelse($summaryRows as $row)
                            <div class="pkg-inventory-row pkg-product-row">
                                <div>
                                    <button type="button" class="pkg-toggle js-pkg-inventory-toggle" data-target="pkg-stock-{{ $row['product_id'] }}">
                                        <span class="js-icon">+</span> {{ $row['name'] }}
                                    </button>
                                </div>
                                <div>{{ $row['unit'] }}</div>
                                <div class="pkg-num">{{ number_format($row['opening']) }}</div>
                                <div class="pkg-num">{{ number_format($row['import']) }}</div>
                                <div class="pkg-num text-primary">{{ number_format($row['reserved']) }}</div>
                                <div class="pkg-num">{{ number_format($row['export']) }}</div>
                                <div class="pkg-num fw-bold">{{ number_format($row['closing']) }}</div>
                            </div>
                            <div id="pkg-stock-{{ $row['product_id'] }}" class="d-none">
                                @foreach($row['variants'] as $variant)
                                    @if($variant['closing'] > 0)
                                        <div class="pkg-inventory-row pkg-variant-row">
                                            <div class="ps-4">{{ $variant['name'] }}</div>
                                            <div>{{ $variant['unit'] }}</div>
                                            <div class="pkg-num">{{ number_format($variant['opening']) }}</div>
                                            <div class="pkg-num">{{ number_format($variant['import']) }}</div>
                                            <div class="pkg-num text-primary">{{ number_format($variant['reserved']) }}</div>
                                            <div class="pkg-num">{{ number_format($variant['export']) }}</div>
                                            <div class="pkg-num fw-semibold">{{ number_format($variant['closing']) }}</div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">Không có sản phẩm tồn kho.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-pkg-inventory-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const target = document.getElementById(button.dataset.target);
        if (!target) return;
        target.classList.toggle('d-none');
        button.querySelector('.js-icon').textContent = target.classList.contains('d-none') ? '+' : '−';
    });
});
</script>
@endpush
