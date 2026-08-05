@extends('layouts.site')

@push('styles')
<style>
    .fix-data-page {
        --monitor-border: #dce6f1;
        background: #f8fafc;
        min-height: 75vh;
        padding: 28px 0 64px;
    }
    .fix-data-shell { width: calc(100% - 32px); max-width: 1290px; margin: 0 auto; }
    .fix-data-layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 20px; }
    .fix-data-sidebar { display: grid; align-content: start; }
    .fix-data-content { min-width: 0; }
    .monitor-tab-nav { display: grid; gap: 8px; }
    .monitor-tab-link {
        display: flex;
        min-height: 50px;
        align-items: center;
        gap: 11px;
        padding: 10px 14px;
        border: 1px solid var(--monitor-border);
        border-radius: 4px;
        background: #fff;
        color: #075985;
        font-size: .82rem;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .04);
    }
    .monitor-tab-link i { width: 18px; color: #527394; font-size: 1rem; text-align: center; }
    .monitor-tab-link:hover,
    .monitor-tab-link.active { border-color: #cfe3e8; background: #eaf5f6; color: #075985; }
    @media (max-width: 1199.98px) {
        .fix-data-layout { grid-template-columns: 220px minmax(0, 1fr); gap: 14px; }
    }
    @media (max-width: 991.98px) {
        .fix-data-layout { grid-template-columns: 1fr; }
        .monitor-tab-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .monitor-tab-nav { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
@php
    $statusLabels = [
        'draft' => 'Bản nháp',
        'pending_approval' => 'Đang chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Đã từ chối',
        'completed' => 'Đã hoàn tất',
    ];
    $roleLabels = [
        'leader' => 'Leader', 'leader_sale' => 'Leader', 'sale_manager' => 'Leader',
        'manager' => 'Manager', 'manager_sale' => 'Manager', 'director' => 'Manager',
        'account' => 'Kế toán', 'accountant' => 'Kế toán', 'accounting' => 'Kế toán',
        'warehouse' => 'Kho',
    ];
@endphp

<section class="fix-data-page">
    <div class="fix-data-shell">
        <div class="fix-data-layout">
            <aside class="fix-data-sidebar">
                @include('site.orders.partials.monitor_sidebar_nav', ['activeTab' => 'fix_data'])
            </aside>
            <main class="fix-data-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h3 class="mb-1"><i class="bi bi-arrow-left-right me-2"></i>Fix số liệu</h3>
                <div class="text-muted">Yêu cầu điều chỉnh đơn đã hoàn thành theo luồng Leader → Manager → Kế toán → Kho.</div>
            </div>
            <a href="{{ route('pages.my_orders.monitoring', ['tab' => 'my_orders']) }}" class="btn btn-outline-secondary">Đơn của tôi</a>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Tìm yêu cầu</label>
                        <input name="keyword" value="{{ $keyword }}" class="form-control" placeholder="Mã đơn, khách hàng hoặc mã yêu cầu">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            @foreach($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1">Lọc</button>
                        <a href="{{ route('site.order-adjustments.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-grid gap-3">
            @forelse($adjustments as $adjustment)
                @php
                    $order = $adjustment->order;
                    $currentStep = $currentSteps[$adjustment->id] ?? null;
                    $currentRole = strtolower((string) ($currentStep?->step?->role_slug ?? ''));
                    $canApprove = $canApproveByAdjustment[$adjustment->id] ?? false;
                @endphp
                <article class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between gap-2 align-items-start">
                        <div>
                            <div class="fw-bold">Yêu cầu #{{ $adjustment->id }} · {{ $order?->code ?: ('#'.$adjustment->order_id) }}</div>
                            <div class="small text-muted">
                                {{ $order?->customer?->name ?? 'Khách hàng' }} · Sale: {{ $order?->user?->name ?? '—' }} · Gửi bởi {{ $adjustment->requester?->name ?? '—' }}
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge text-bg-{{ $adjustment->status === 'rejected' ? 'danger' : ($adjustment->status === 'pending_approval' ? 'warning' : 'success') }}">{{ $statusLabels[$adjustment->status] ?? $adjustment->status }}</span>
                            @if($currentStep)
                                <div class="small text-primary fw-semibold mt-1">Đang chờ: {{ $roleLabels[$currentRole] ?? $currentRole }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if($adjustment->adjustment_note)
                            <div class="alert alert-light border py-2"><strong>Lý do:</strong> {{ $adjustment->adjustment_note }}</div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Sản phẩm</th><th class="text-end">SL cũ</th><th class="text-end">SL yêu cầu</th><th class="text-end">Giá cũ</th><th class="text-end">Giá yêu cầu</th></tr></thead>
                                <tbody>
                                    @foreach($adjustment->items as $item)
                                        <tr>
                                            <td>{{ $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm' }}</td>
                                            <td class="text-end">{{ number_format((float) $item->original_quantity, 0, ',', '.') }}</td>
                                            <td class="text-end fw-bold">{{ number_format((float) $item->adjusted_quantity, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ number_format((float) $item->original_price, 0, ',', '.') }}đ</td>
                                            <td class="text-end fw-bold">{{ number_format((float) $item->adjusted_price, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex flex-wrap justify-content-between gap-2">
                        <div class="small text-muted">{{ optional($adjustment->submitted_at)->format('d/m/Y H:i') }}</div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('site.order-adjustments.show', $adjustment) }}" class="btn btn-outline-info btn-sm">Chi tiết</a>
                            @if($canApprove)
                                <form method="POST" action="{{ route('site.order-adjustments.approve', $adjustment) }}">
                                    @csrf
                                    <button class="btn btn-success btn-sm" onclick="return confirm('Duyệt yêu cầu điều chỉnh #{{ $adjustment->id }}?')">Duyệt bước {{ $roleLabels[$currentRole] ?? $currentRole }}</button>
                                </form>
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#rejectFix{{ $adjustment->id }}">Từ chối</button>
                            @endif
                        </div>
                    </div>
                    @if($canApprove)
                        <div class="collapse" id="rejectFix{{ $adjustment->id }}">
                            <form method="POST" action="{{ route('site.order-adjustments.reject', $adjustment) }}" class="card-body border-top">
                                @csrf
                                <label class="form-label">Lý do từ chối</label>
                                <textarea name="reason" class="form-control mb-2" rows="2" required></textarea>
                                <button class="btn btn-danger btn-sm">Xác nhận từ chối</button>
                            </form>
                        </div>
                    @endif
                </article>
            @empty
                <div class="card"><div class="card-body text-center text-muted py-5">Không có yêu cầu điều chỉnh phù hợp.</div></div>
            @endforelse
        </div>

                <div class="mt-3">{{ $adjustments->links('pagination::bootstrap-5') }}</div>
            </main>
        </div>
    </div>
</section>
@endsection
