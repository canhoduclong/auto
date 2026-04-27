@extends('layouts.app')

@push('styles')
<style>
    :root {
        --osr-border: #e2e8f0;
        --osr-soft: #f8fafc;
        --osr-ink: #0f172a;
        --osr-muted: #64748b;
        --osr-green: #16a34a;
        --osr-orange: #d97706;
        --osr-red: #dc2626;
        --osr-blue: #2563eb;
    }
    .osr-page { padding: 1.5rem 0 2.5rem; }
    .osr-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
    }
    .osr-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: .75rem;
        margin-top: 1.25rem;
    }
    .osr-kpi {
        background: rgba(255,255,255,.07);
        border-radius: 10px;
        padding: .7rem 1rem;
        text-align: center;
    }
    .osr-kpi-label { font-size: .73rem; opacity: .65; text-transform: uppercase; letter-spacing: .05em; }
    .osr-kpi-value { font-size: 1.5rem; font-weight: 800; margin-top: .15rem; }
    .osr-card {
        background: #fff;
        border: 1px solid var(--osr-border);
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(17,24,39,.05);
    }
    .osr-card-header {
        padding: .8rem 1.1rem;
        border-bottom: 1px solid var(--osr-border);
        font-weight: 700;
        font-size: .87rem;
        background: var(--osr-soft);
        border-radius: 12px 12px 0 0;
        display: flex;
        align-items: center;
        gap: .6rem;
        justify-content: space-between;
    }
    .osr-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .osr-table th {
        background: var(--osr-soft);
        color: var(--osr-muted);
        font-size: .73rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
        padding: .6rem .9rem;
        border-bottom: 1px solid var(--osr-border);
        white-space: nowrap;
    }
    .osr-table td {
        padding: .6rem .9rem;
        border-bottom: 1px solid var(--osr-border);
        vertical-align: middle;
    }
    .osr-table tr:last-child td { border-bottom: 0; }
    .osr-table tr:hover td { background: var(--osr-soft); }
    .osr-badge {
        display: inline-block;
        padding: .2rem .6rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .osr-badge-success  { background: #dcfce7; color: #166534; }
    .osr-badge-failed   { background: #fee2e2; color: #991b1b; }
    .osr-badge-cron     { background: #dbeafe; color: #1e40af; }
    .osr-badge-manual   { background: #fef3c7; color: #92400e; }
    .osr-num { font-weight: 700; }
    .osr-num-green  { color: var(--osr-green); }
    .osr-num-orange { color: var(--osr-orange); }
    .osr-num-blue   { color: var(--osr-blue); }
    .osr-cron-box {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        padding: .65rem 1rem;
        font-size: .84rem;
        color: #166534;
    }
    .osr-error-text {
        font-size: .78rem;
        color: var(--osr-red);
        max-width: 280px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: default;
    }
    @media (max-width: 767.98px) {
        .osr-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .osr-table td:nth-child(6), .osr-table th:nth-child(6) { display: none; }
    }
</style>
@endpush

@section('content')
<div class="osr-page">
    <div class="container-fluid">

        {{-- Hero --}}
        <div class="osr-hero">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1 fw-bold"><i class="bi bi-calendar-check me-2"></i>Đơn tự động — Lịch sử chạy lệnh</h4>
                    <div class="opacity-70 small">Giám sát và kiểm soát lệnh <code class="text-warning">order-schedules:evaluate-today</code></div>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <div class="osr-cron-box">
                        <i class="bi bi-clock me-1"></i>
                        Cron tự động: <strong>00:15 mỗi ngày</strong>
                    </div>
                    <form method="POST" action="{{ route('admin.order-schedule-runs.run-now') }}" class="m-0"
                          onsubmit="return confirm('Chạy lệnh ngay bây giờ?\n\nLệnh sẽ kiểm tra giá/tồn kho và tạo đơn cho tất cả lịch hôm nay có status = pending.')">
                        @csrf
                        <button type="submit" class="btn btn-warning fw-bold">
                            <i class="bi bi-play-fill me-1"></i>Chạy ngay bây giờ
                        </button>
                    </form>
                </div>
            </div>
            <div class="osr-kpi-grid">
                <div class="osr-kpi">
                    <div class="osr-kpi-label">Tổng lần chạy</div>
                    <div class="osr-kpi-value">{{ number_format($stats['total_runs']) }}</div>
                </div>
                <div class="osr-kpi">
                    <div class="osr-kpi-label">Tổng đã xử lý</div>
                    <div class="osr-kpi-value">{{ number_format($stats['total_evaluated']) }}</div>
                </div>
                <div class="osr-kpi">
                    <div class="osr-kpi-label">Đơn đã tạo</div>
                    <div class="osr-kpi-value" style="color:#86efac;">{{ number_format($stats['total_generated']) }}</div>
                </div>
                <div class="osr-kpi">
                    <div class="osr-kpi-label">Cần review</div>
                    <div class="osr-kpi-value" style="color:#fcd34d;">{{ number_format($stats['total_need_review']) }}</div>
                </div>
                <div class="osr-kpi">
                    <div class="osr-kpi-label">Lần cuối chạy</div>
                    <div class="osr-kpi-value" style="font-size:.95rem;">
                        {{ $stats['last_run'] ? $stats['last_run']->created_at->format('d/m H:i') : '—' }}
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 rounded-3 mb-3">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 rounded-3 mb-3">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            </div>
        @endif

        {{-- How it works info box --}}
        <div class="alert alert-info border-0 rounded-3 mb-3 d-flex gap-3 align-items-start">
            <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
            <div>
                <div class="fw-bold mb-1">Lệnh này hoạt động thế nào?</div>
                <ul class="mb-0 small ps-3">
                    <li>Mỗi ngày lúc <strong>00:15</strong>, hệ thống tự chạy <code>php artisan order-schedules:evaluate-today</code></li>
                    <li>Lệnh tìm tất cả lịch có <strong>schedule_date = hôm nay</strong>, <strong>status = pending</strong>, chưa có đơn hàng</li>
                    <li>Kiểm tra <strong>giá hiện tại</strong> và <strong>tồn kho</strong> của từng mặt hàng trong lịch</li>
                    <li>Nếu giá &amp; tồn kho OK → tự động <strong>tạo đơn hàng</strong> (status: <span class="badge bg-success">generated</span>)</li>
                    <li>Nếu có thay đổi giá / thiếu tồn kho → đánh dấu <strong>cần review</strong> (status: <span class="badge bg-warning text-dark">need_review</span>)</li>
                    <li>Bạn cũng có thể nhấn <strong>"Chạy ngay bây giờ"</strong> để kích hoạt thủ công bất kỳ lúc nào</li>
                </ul>
            </div>
        </div>

        {{-- Run history table --}}
        <div class="osr-card">
            <div class="osr-card-header">
                <span><i class="bi bi-clock-history me-2 text-primary"></i>Lịch sử chạy lệnh</span>
                <span class="text-muted small fw-normal">{{ $runs->total() }} lần</span>
            </div>
            <div class="table-responsive">
                <table class="osr-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Thời điểm</th>
                            <th>Kích hoạt bởi</th>
                            <th>Loại</th>
                            <th>Trạng thái</th>
                            <th>Đã xử lý</th>
                            <th>Tạo đơn</th>
                            <th>Cần review</th>
                            <th>Thời gian</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($runs as $run)
                        <tr>
                            <td class="text-muted small">{{ $run->id }}</td>
                            <td>
                                <div class="fw-semibold" style="font-size:.84rem;">{{ $run->created_at->format('d/m/Y H:i:s') }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ $run->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($run->triggeredBy)
                                    <div class="fw-semibold" style="font-size:.83rem;">{{ $run->triggeredBy->name }}</div>
                                @else
                                    <span class="text-muted small">Cron hệ thống</span>
                                @endif
                            </td>
                            <td>
                                <span class="osr-badge {{ $run->trigger_type === 'manual' ? 'osr-badge-manual' : 'osr-badge-cron' }}">
                                    {{ $run->trigger_type === 'manual' ? 'Thủ công' : 'Cron' }}
                                </span>
                            </td>
                            <td>
                                <span class="osr-badge {{ $run->status === 'success' ? 'osr-badge-success' : 'osr-badge-failed' }}">
                                    {{ $run->status === 'success' ? 'Thành công' : 'Lỗi' }}
                                </span>
                            </td>
                            <td>
                                <span class="osr-num osr-num-blue">{{ number_format($run->evaluated) }}</span>
                            </td>
                            <td>
                                <span class="osr-num osr-num-green">{{ number_format($run->generated) }}</span>
                            </td>
                            <td>
                                <span class="osr-num osr-num-orange">{{ number_format($run->need_review) }}</span>
                            </td>
                            <td class="text-muted small">
                                @if($run->duration_ms !== null)
                                    {{ $run->duration_ms < 1000
                                        ? $run->duration_ms . 'ms'
                                        : number_format($run->duration_ms / 1000, 1) . 's' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($run->error)
                                    <span class="osr-error-text" title="{{ $run->error }}">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $run->error }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                Chưa có lần chạy nào được ghi lại.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($runs->hasPages())
            <div class="px-3 py-2 border-top">
                {{ $runs->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
