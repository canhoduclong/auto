@extends('layouts.site')

@php
    $order = $adjustment->order;
    $statusMeta = [
        'draft' => ['label' => 'Bản nháp', 'class' => 'secondary', 'icon' => 'bi-pencil-square'],
        'pending_approval' => ['label' => 'Đang chờ duyệt', 'class' => 'warning', 'icon' => 'bi-hourglass-split'],
        'approved' => ['label' => 'Đã duyệt', 'class' => 'primary', 'icon' => 'bi-check2-circle'],
        'rejected' => ['label' => 'Đã từ chối', 'class' => 'danger', 'icon' => 'bi-x-circle'],
        'completed' => ['label' => 'Đã hoàn tất', 'class' => 'success', 'icon' => 'bi-check-circle-fill'],
    ][$adjustment->status] ?? ['label' => $adjustment->status, 'class' => 'secondary', 'icon' => 'bi-info-circle'];
    $warehouseStatusLabels = [
        'not_required' => 'Không cần Kho xác nhận',
        'pending' => 'Đang chờ Kho xác nhận',
        'confirmed_full' => 'Kho đã xác nhận đủ',
        'confirmed_partial' => 'Kho xác nhận một phần',
        'rejected' => 'Kho từ chối',
    ];
    $roleLabels = [
        'leader' => 'Leader', 'leader_sale' => 'Leader', 'sale_manager' => 'Leader',
        'manager' => 'Manager', 'manager_sale' => 'Manager', 'director' => 'Manager',
        'account' => 'Kế toán', 'accountant' => 'Kế toán', 'accounting' => 'Kế toán',
        'warehouse' => 'Kho',
    ];
    $approvalSteps = $adjustment->approvalSteps->sortBy(fn ($approval) => (int) ($approval->step?->step_order ?? PHP_INT_MAX));
    $changedItems = $adjustment->items->filter(fn ($item) =>
        (int) $item->original_quantity !== (int) $item->adjusted_quantity
        || abs((float) $item->original_price - (float) $item->adjusted_price) > 0.001
        || abs((float) $item->original_weight - (float) $item->adjusted_weight) > 0.001
        || ! $item->order_item_id
    )->count();
    $compactNumber = static fn ($value, int $precision = 3) => rtrim(rtrim(number_format((float) $value, $precision, '.', ''), '0'), '.');
    $money = static fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
@endphp

@push('styles')
<style>
    .adjustment-detail-page { min-height: 75vh; padding: 28px 0 64px; background: #f5f7fb; }
    .adjustment-detail-shell { width: calc(100% - 32px); max-width: 1320px; margin: 0 auto; }
    .adjustment-breadcrumb { display: flex; align-items: center; gap: 7px; margin-bottom: 14px; color: #64748b; font-size: .82rem; }
    .adjustment-breadcrumb a { color: #2563eb; text-decoration: none; }
    .adjustment-hero { overflow: hidden; margin-bottom: 18px; border: 1px solid #dbe4ef; border-radius: 16px; background: #fff; box-shadow: 0 12px 30px rgba(15, 23, 42, .06); }
    .adjustment-hero-main { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; padding: 22px 24px; }
    .adjustment-kicker { margin-bottom: 5px; color: #2563eb; font-size: .74rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .adjustment-title { margin: 0; color: #0f172a; font-size: clamp(1.35rem, 2.5vw, 1.8rem); font-weight: 850; }
    .adjustment-subtitle { margin-top: 7px; color: #64748b; }
    .adjustment-status { display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; padding: 8px 12px; border-radius: 999px; font-size: .82rem; font-weight: 800; }
    .adjustment-status.status-warning { color: #92400e; background: #fef3c7; }
    .adjustment-status.status-success { color: #166534; background: #dcfce7; }
    .adjustment-status.status-primary { color: #1d4ed8; background: #dbeafe; }
    .adjustment-status.status-danger { color: #b91c1c; background: #fee2e2; }
    .adjustment-status.status-secondary { color: #475569; background: #e2e8f0; }
    .adjustment-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border-top: 1px solid #e8eef5; background: #f8fafc; }
    .adjustment-summary-item { min-width: 0; padding: 14px 20px; border-right: 1px solid #e8eef5; }
    .adjustment-summary-item:last-child { border-right: 0; }
    .adjustment-label { margin-bottom: 4px; color: #64748b; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .adjustment-value { overflow: hidden; color: #0f172a; font-size: .9rem; font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
    .adjustment-layout { display: grid; grid-template-columns: minmax(0, 1fr) 330px; gap: 18px; align-items: start; }
    .adjustment-main, .adjustment-side { display: grid; gap: 18px; min-width: 0; }
    .adjustment-side { position: sticky; top: 18px; }
    .adjustment-card { overflow: hidden; border: 1px solid #dbe4ef; border-radius: 14px; background: #fff; box-shadow: 0 7px 20px rgba(15, 23, 42, .045); }
    .adjustment-card-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 15px 18px; border-bottom: 1px solid #e8eef5; }
    .adjustment-card-title { margin: 0; color: #0f172a; font-size: .95rem; font-weight: 850; }
    .adjustment-card-title i { margin-right: 7px; color: #2563eb; }
    .adjustment-card-body { padding: 18px; }
    .adjustment-note { padding: 14px 16px; border-left: 4px solid #f59e0b; border-radius: 8px; background: #fffbeb; color: #713f12; line-height: 1.55; }
    .adjustment-table { min-width: 920px; margin: 0; }
    .adjustment-table thead th { padding: 10px 12px; border-bottom-width: 1px; background: #f8fafc; color: #64748b; font-size: .68rem; letter-spacing: .04em; text-transform: uppercase; }
    .adjustment-table tbody td { padding: 12px; vertical-align: middle; }
    .adjustment-product { color: #0f172a; font-weight: 800; }
    .adjustment-change { display: inline-flex; align-items: center; gap: 5px; color: #b45309; font-weight: 800; }
    .adjustment-change i { color: #94a3b8; font-size: .72rem; }
    .adjustment-new-badge { display: inline-flex; margin-top: 4px; padding: 2px 7px; border-radius: 999px; color: #166534; background: #dcfce7; font-size: .67rem; font-weight: 800; }
    .adjustment-timeline { display: grid; }
    .timeline-item { position: relative; display: grid; grid-template-columns: 28px minmax(0, 1fr); gap: 10px; padding-bottom: 18px; }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-item:not(:last-child)::before { position: absolute; top: 27px; bottom: 0; left: 13px; width: 2px; background: #e2e8f0; content: ''; }
    .timeline-dot { position: relative; z-index: 1; display: grid; width: 28px; height: 28px; place-items: center; border: 2px solid #cbd5e1; border-radius: 50%; color: #64748b; background: #fff; font-size: .72rem; }
    .timeline-item.is-approved .timeline-dot { border-color: #22c55e; color: #15803d; background: #dcfce7; }
    .timeline-item.is-rejected .timeline-dot { border-color: #ef4444; color: #b91c1c; background: #fee2e2; }
    .timeline-item.is-pending .timeline-dot { border-color: #f59e0b; color: #92400e; background: #fef3c7; }
    .timeline-title { color: #0f172a; font-size: .84rem; font-weight: 800; }
    .timeline-meta { margin-top: 2px; color: #64748b; font-size: .74rem; line-height: 1.4; }
    .adjustment-meta-list { display: grid; gap: 11px; }
    .adjustment-meta-row { display: flex; justify-content: space-between; gap: 14px; padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0; font-size: .82rem; }
    .adjustment-meta-row:last-child { padding-bottom: 0; border-bottom: 0; }
    .adjustment-meta-row span:first-child { color: #64748b; }
    .adjustment-meta-row span:last-child { color: #0f172a; font-weight: 750; text-align: right; }
    .adjustment-action-card { border-color: #bbf7d0; }
    .adjustment-action-card .adjustment-card-head { background: #f0fdf4; }
    .adjustment-reject-form { padding-top: 14px; margin-top: 14px; border-top: 1px solid #e2e8f0; }
    .adjustment-evidence { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
    .adjustment-evidence a { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 9px; background: #f8fafc; }
    .adjustment-evidence img { display: block; width: 100%; height: 120px; object-fit: cover; transition: transform .2s ease; }
    .adjustment-evidence a:hover img { transform: scale(1.04); }
    .warehouse-action-table { min-width: 760px; }
    @media (max-width: 991.98px) {
        .adjustment-layout { grid-template-columns: 1fr; }
        .adjustment-side { position: static; }
        .adjustment-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .adjustment-summary-item:nth-child(2) { border-right: 0; }
        .adjustment-summary-item:nth-child(-n+2) { border-bottom: 1px solid #e8eef5; }
    }
    @media (max-width: 575.98px) {
        .adjustment-detail-shell { width: calc(100% - 20px); }
        .adjustment-hero-main { flex-direction: column; padding: 18px; }
        .adjustment-summary { grid-template-columns: 1fr; }
        .adjustment-summary-item { border-right: 0; border-bottom: 1px solid #e8eef5; }
        .adjustment-summary-item:last-child { border-bottom: 0; }
        .adjustment-card-head, .adjustment-card-body { padding: 14px; }
    }
</style>
@endpush

@section('content')
<section class="adjustment-detail-page">
    <div class="adjustment-detail-shell">
        <nav class="adjustment-breadcrumb" aria-label="Điều hướng">
            <a href="{{ route('site.order-adjustments.index') }}">Fix số liệu</a><i class="bi bi-chevron-right"></i><span>Yêu cầu #{{ $adjustment->id }}</span>
        </nav>

        @if(session('success'))<div class="alert alert-success shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>@endif

        <header class="adjustment-hero">
            <div class="adjustment-hero-main">
                <div>
                    <div class="adjustment-kicker">Hồ sơ điều chỉnh đơn hàng</div>
                    <h1 class="adjustment-title">Yêu cầu #{{ $adjustment->id }}</h1>
                    <div class="adjustment-subtitle">Đơn hàng <a class="fw-semibold text-decoration-none" href="{{ route('site.orders.show', $adjustment->order_id) }}">{{ $order?->code ?: ('#'.$adjustment->order_id) }}</a> · tạo lúc {{ optional($adjustment->created_at)->format('d/m/Y H:i') ?: '—' }}</div>
                </div>
                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                    <span class="adjustment-status status-{{ $statusMeta['class'] }}"><i class="bi {{ $statusMeta['icon'] }}"></i>{{ $statusMeta['label'] }}</span>
                    <a href="{{ route('site.orders.show', $adjustment->order_id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Xem đơn gốc</a>
                </div>
            </div>
            <div class="adjustment-summary">
                <div class="adjustment-summary-item"><div class="adjustment-label">Khách hàng</div><div class="adjustment-value">{{ $order?->customer?->name ?? '—' }}</div></div>
                <div class="adjustment-summary-item"><div class="adjustment-label">Người yêu cầu</div><div class="adjustment-value">{{ $adjustment->requester?->name ?? '—' }}</div></div>
                <div class="adjustment-summary-item"><div class="adjustment-label">Sản phẩm thay đổi</div><div class="adjustment-value">{{ $changedItems }} / {{ $adjustment->items->count() }} dòng</div></div>
                <div class="adjustment-summary-item"><div class="adjustment-label">Thời gian gửi duyệt</div><div class="adjustment-value">{{ optional($adjustment->submitted_at)->format('d/m/Y H:i') ?: 'Chưa gửi' }}</div></div>
            </div>
        </header>

        <div class="adjustment-layout">
            <main class="adjustment-main">
                <section class="adjustment-card">
                    <div class="adjustment-card-head"><h2 class="adjustment-card-title"><i class="bi bi-chat-left-text"></i>Nội dung yêu cầu</h2></div>
                    <div class="adjustment-card-body"><div class="adjustment-note">{{ $adjustment->adjustment_note ?: 'Không có ghi chú bổ sung.' }}</div></div>
                </section>

                @include('site.orders.adjustments._fee_changes', ['adjustment' => $adjustment])

                <section class="adjustment-card">
                    <div class="adjustment-card-head"><h2 class="adjustment-card-title"><i class="bi bi-box-seam"></i>Chi tiết sản phẩm điều chỉnh</h2><span class="badge text-bg-light border">{{ $adjustment->items->count() }} dòng sản phẩm</span></div>
                    <div class="table-responsive">
                        <table class="table adjustment-table align-middle">
                            <thead><tr><th>Sản phẩm</th><th class="text-center">Số lượng</th><th class="text-end">Đơn giá</th><th class="text-end">Khối lượng</th><th class="text-center">Kho xác nhận</th><th>Tình trạng hàng</th></tr></thead>
                            <tbody>
                            @foreach($adjustment->items as $item)
                                @php
                                    $quantityChanged = (int) $item->original_quantity !== (int) $item->adjusted_quantity;
                                    $priceChanged = abs((float) $item->original_price - (float) $item->adjusted_price) > 0.001;
                                    $weightChanged = abs((float) $item->original_weight - (float) $item->adjusted_weight) > 0.001;
                                    $isNewItem = ! $item->order_item_id;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="adjustment-product">{{ $item->variant?->product?->name ?? 'Sản phẩm' }}</div>
                                        <div class="small text-muted">{{ $item->variant?->name ?? '—' }} @if($item->variant?->sku) · {{ $item->variant->sku }} @endif</div>
                                        @if($isNewItem)<span class="adjustment-new-badge">Sản phẩm bổ sung</span>@endif
                                        @if($item->note)<div class="small text-muted mt-1"><i class="bi bi-chat-dots me-1"></i>{{ $item->note }}</div>@endif
                                    </td>
                                    <td class="text-center">@if($quantityChanged || $isNewItem)<span class="adjustment-change">{{ (int) $item->original_quantity }} <i class="bi bi-arrow-right"></i> {{ (int) $item->adjusted_quantity }}</span>@else{{ (int) $item->adjusted_quantity }}@endif</td>
                                    <td class="text-end">@if($priceChanged || $isNewItem)<div class="small text-muted text-decoration-line-through">{{ $money($item->original_price) }}</div><div class="adjustment-change">{{ $money($item->adjusted_price) }}</div>@else{{ $money($item->adjusted_price) }}@endif</td>
                                    <td class="text-end">
                                        @if($weightChanged || $isNewItem)
                                            <span class="adjustment-change">{{ $compactNumber($item->original_weight) }} <i class="bi bi-arrow-right"></i> {{ $compactNumber($item->adjusted_weight) }} kg</span>
                                        @else
                                            {{ $compactNumber($item->adjusted_weight) }} kg
                                        @endif
                                    </td>
                                    <td class="text-center">@if(is_null($item->warehouse_received_quantity) && is_null($item->warehouse_received_weight))<span class="text-muted">—</span>@else<div class="fw-semibold">{{ is_null($item->warehouse_received_quantity) ? '—' : (int) $item->warehouse_received_quantity }}</div>@if(!is_null($item->warehouse_received_weight))<div class="small text-muted">{{ $compactNumber($item->warehouse_received_weight) }} kg</div>@endif @endif</td>
                                    <td>{{ $item->warehouse_condition ?: '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                @if(!empty($adjustment->evidence_images))
                    <section class="adjustment-card">
                        <div class="adjustment-card-head"><h2 class="adjustment-card-title"><i class="bi bi-images"></i>Hình ảnh minh chứng</h2><span class="badge text-bg-light border">{{ count((array) $adjustment->evidence_images) }} ảnh</span></div>
                        <div class="adjustment-card-body"><div class="adjustment-evidence">
                            @foreach((array) $adjustment->evidence_images as $image)<a href="{{ asset('storage/'.$image) }}" target="_blank" rel="noopener" title="Mở ảnh gốc"><img src="{{ asset('storage/'.$image) }}" alt="Minh chứng yêu cầu #{{ $adjustment->id }}"></a>@endforeach
                        </div></div>
                    </section>
                @endif

                @if($canWarehouseConfirm)
                    <section class="adjustment-card">
                        <div class="adjustment-card-head"><h2 class="adjustment-card-title"><i class="bi bi-building-check"></i>Kho xác nhận hàng hóa</h2><span class="badge text-bg-warning">Cần xử lý</span></div>
                        <div class="adjustment-card-body">
                            <form method="POST" action="{{ route('site.order-adjustments.warehouse-confirm', $adjustment) }}">
                                @csrf
                                <div class="table-responsive mb-3"><table class="table table-bordered align-middle warehouse-action-table">
                                    <thead class="table-light"><tr><th>Sản phẩm</th><th class="text-center">SL giảm</th><th>SL Kho nhận</th><th>Cân Kho nhận (kg)</th><th>Tình trạng</th></tr></thead>
                                    <tbody>@foreach($adjustment->items as $idx => $item)
                                        @php $decrease = max((int) $item->original_quantity - (int) $item->adjusted_quantity, 0); @endphp
                                        <tr>
                                            <td><strong>{{ $item->variant?->product?->name ?? 'Sản phẩm' }}</strong><div class="small text-muted">{{ $item->variant?->name ?? '—' }}</div></td><td class="text-center fw-semibold">{{ $decrease }}</td>
                                            <td><input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}"><input type="number" min="0" max="{{ $decrease }}" name="items[{{ $idx }}][warehouse_received_quantity]" class="form-control" value="{{ old('items.'.$idx.'.warehouse_received_quantity', $decrease) }}"></td>
                                            <td><input type="number" min="0" step="0.001" name="items[{{ $idx }}][warehouse_received_weight]" class="form-control" value="{{ old('items.'.$idx.'.warehouse_received_weight') }}"></td>
                                            <td><input type="text" name="items[{{ $idx }}][warehouse_condition]" class="form-control" value="{{ old('items.'.$idx.'.warehouse_condition') }}" placeholder="Đủ chất lượng / hư hỏng..."></td>
                                        </tr>
                                    @endforeach</tbody>
                                </table></div>
                                <div class="mb-3"><label class="form-label fw-semibold">Ghi chú của Kho</label><textarea name="note" class="form-control" rows="3" placeholder="Ghi nhận tình trạng thực tế khi tiếp nhận...">{{ old('note') }}</textarea></div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" name="mode" value="confirm_full" class="btn btn-success"><i class="bi bi-check2-all me-1"></i>Xác nhận đủ</button>
                                    <button type="submit" name="mode" value="confirm_partial" class="btn btn-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Xác nhận một phần</button>
                                    <button type="submit" name="mode" value="reject" class="btn btn-outline-danger" onclick="return confirm('Xác nhận từ chối tiếp nhận hàng?');"><i class="bi bi-x-circle me-1"></i>Từ chối tiếp nhận</button>
                                </div>
                            </form>
                        </div>
                    </section>
                @endif
            </main>

            <aside class="adjustment-side">
                <section class="adjustment-card">
                    <div class="adjustment-card-head"><h2 class="adjustment-card-title"><i class="bi bi-diagram-3"></i>Tiến trình phê duyệt</h2></div>
                    <div class="adjustment-card-body"><div class="adjustment-timeline">
                        <div class="timeline-item is-approved"><div class="timeline-dot"><i class="bi bi-send-check"></i></div><div><div class="timeline-title">Gửi yêu cầu</div><div class="timeline-meta">{{ $adjustment->requester?->name ?? '—' }} · {{ optional($adjustment->submitted_at ?? $adjustment->created_at)->format('d/m/Y H:i') }}</div></div></div>
                        @foreach($approvalSteps as $approval)
                            @php
                                $approvalStatus = strtolower((string) $approval->status);
                                $timelineClass = $approvalStatus === 'approved' ? 'is-approved' : ($approvalStatus === 'rejected' ? 'is-rejected' : 'is-pending');
                                $role = strtolower((string) ($approval->step?->role_slug ?? ''));
                            @endphp
                            <div class="timeline-item {{ $timelineClass }}"><div class="timeline-dot"><i class="bi {{ $approvalStatus === 'approved' ? 'bi-check-lg' : ($approvalStatus === 'rejected' ? 'bi-x-lg' : 'bi-hourglass') }}"></i></div><div>
                                <div class="timeline-title">{{ $roleLabels[$role] ?? ($approval->step?->name ?? 'Bước phê duyệt') }}</div>
                                <div class="timeline-meta">@if($approvalStatus === 'approved'){{ $approval->approver?->name ?? 'Đã duyệt' }} · {{ optional($approval->approved_at)->format('d/m/Y H:i') }}@elseif($approvalStatus === 'rejected')Đã từ chối{{ $approval->approver?->name ? ' bởi '.$approval->approver->name : '' }}@else Đang chờ xử lý @endif @if($approval->note)<div class="mt-1">{{ $approval->note }}</div>@endif</div>
                            </div></div>
                        @endforeach
                        @if($adjustment->requiresWarehouseConfirmation())
                            @php $warehouseDone = in_array($adjustment->warehouse_confirmation_status, ['confirmed_full', 'confirmed_partial'], true); $warehouseRejected = $adjustment->warehouse_confirmation_status === 'rejected'; @endphp
                            <div class="timeline-item {{ $warehouseDone ? 'is-approved' : ($warehouseRejected ? 'is-rejected' : 'is-pending') }}"><div class="timeline-dot"><i class="bi bi-box-seam"></i></div><div><div class="timeline-title">Kho xác nhận</div><div class="timeline-meta">{{ $warehouseStatusLabels[$adjustment->warehouse_confirmation_status] ?? $adjustment->warehouse_confirmation_status }}@if($adjustment->warehouseConfirmer) · {{ $adjustment->warehouseConfirmer->name }}@endif</div></div></div>
                        @endif
                    </div></div>
                </section>

                <section class="adjustment-card">
                    <div class="adjustment-card-head"><h2 class="adjustment-card-title"><i class="bi bi-info-circle"></i>Thông tin xử lý</h2></div>
                    <div class="adjustment-card-body">
                        <div class="adjustment-meta-list">
                            <div class="adjustment-meta-row"><span>Quy trình</span><span>{{ $adjustment->workflow_code ?: '—' }}</span></div>
                            <div class="adjustment-meta-row"><span>Kho trả hàng</span><span>{{ $adjustment->returnWarehouse?->name ?? 'Không áp dụng' }}</span></div>
                            <div class="adjustment-meta-row"><span>Trạng thái Kho</span><span>{{ $warehouseStatusLabels[$adjustment->warehouse_confirmation_status] ?? $adjustment->warehouse_confirmation_status }}</span></div>
                            @if($adjustment->approved_at)<div class="adjustment-meta-row"><span>Duyệt hoàn tất</span><span>{{ optional($adjustment->approved_at)->format('d/m/Y H:i') }}</span></div>@endif
                            @if($adjustment->completed_at)<div class="adjustment-meta-row"><span>Hoàn tất</span><span>{{ optional($adjustment->completed_at)->format('d/m/Y H:i') }}</span></div>@endif
                        </div>
                        @if($adjustment->approval_note)<div class="alert alert-light border small mt-3 mb-0"><strong>Ghi chú duyệt:</strong><br>{{ $adjustment->approval_note }}</div>@endif
                        @if($adjustment->reject_reason)<div class="alert alert-danger small mt-3 mb-0"><strong>Lý do từ chối:</strong><br>{{ $adjustment->reject_reason }}</div>@endif
                        @if($adjustment->warehouse_confirmation_note)<div class="alert alert-light border small mt-3 mb-0"><strong>Ghi chú Kho:</strong><br>{{ $adjustment->warehouse_confirmation_note }}</div>@endif
                    </div>
                </section>

                @if($canApprove)
                    <section class="adjustment-card adjustment-action-card">
                        <div class="adjustment-card-head"><h2 class="adjustment-card-title"><i class="bi bi-shield-check"></i>Thao tác phê duyệt</h2></div>
                        <div class="adjustment-card-body">
                            <p class="small text-muted">Kiểm tra kỹ các thay đổi về sản phẩm, giá và khoản phí trước khi duyệt.</p>
                            <form method="POST" action="{{ route('site.order-adjustments.approve', $adjustment) }}">@csrf<input type="hidden" name="note" value="Duyệt yêu cầu điều chỉnh từ trang chi tiết"><button type="submit" class="btn btn-success w-100" onclick="return confirm('Xác nhận duyệt yêu cầu điều chỉnh #{{ $adjustment->id }}?')"><i class="bi bi-check2-circle me-1"></i>Xác nhận duyệt</button></form>
                            <form method="POST" action="{{ route('site.order-adjustments.reject', $adjustment) }}" class="adjustment-reject-form">@csrf<label class="form-label small fw-semibold">Lý do từ chối</label><textarea name="reason" class="form-control mb-2" rows="3" placeholder="Nhập lý do cụ thể..." required>{{ old('reason') }}</textarea><button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Xác nhận từ chối yêu cầu này?')"><i class="bi bi-x-circle me-1"></i>Từ chối yêu cầu</button></form>
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
