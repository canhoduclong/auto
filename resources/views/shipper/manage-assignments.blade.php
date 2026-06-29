@extends('layouts.shipper')

@section('title', 'Gán đơn cho ship')
@section('subtitle', 'Quản lý gán đơn hàng đến từng người giao')

@push('styles')
<style>
    .ma-order-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: all 0.15s;
    }
    .ma-order-card:hover {
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.1);
        border-color: var(--theme-primary);
    }
    .ma-order-code {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }
    .ma-customer-info {
        font-size: 0.85rem;
        color: #475569;
        line-height: 1.4;
    }
    .ma-address-badge {
        display: inline-block;
        background: #f0fdfa;
        color: var(--theme-primary);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 4px;
    }
    .ma-shipper-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        margin-top: .35rem;
    }
    .ma-filter-group {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        background: white;
        padding: 1rem;
        border-radius: 0.75rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
    }
    .ma-stats {
        display: none;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .ma-stat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
    }
    .ma-stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--theme-primary);
    }
    .ma-stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 4px;
    }
    .ma-preview-table {
        font-size: .82rem;
        table-layout: fixed;
    }
    .ma-preview-table th {
        text-align: center;
        vertical-align: middle;
        background: #f8fafc;
        color: #334155;
        font-size: .72rem;
        text-transform: uppercase;
    }
    .ma-preview-table td {
        vertical-align: middle;
        overflow-wrap: anywhere;
    }
    .ma-preview-title {
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: .03em;
        color: #334155;
        text-transform: uppercase;
    }
    .trip-planner {
        border: 0;
        background: transparent;
        padding: 0;
        margin-bottom: 8px;
    }
    .js-trip-definitions {
        display: none;
    }
    .priority-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        align-items: center;
        border: 1px solid #dbe4ea;
        border-radius: 8px;
        background: #fff;
        padding: 6px 18px;
        margin-bottom: 18px;
    }
    .priority-dot {
        width: 31px;
        height: 31px;
        border: 1px solid #0f766e;
        border-radius: 999px;
        background: #fff;
        color: #064e4a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .86rem;
        text-decoration: none;
    }
    .priority-dot.is-routed {
        background: #ff8a1c;
        color: #fff;
        border-color: #0f766e;
    }
    .assignment-workspace {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .assignment-routes {
        order: 1;
    }
    .assignment-unassigned {
        order: 2;
    }
    .route-zone {
        border: 1px solid #d8dee6;
        border-radius: 8px;
        background: #f1f3f5;
        padding: 12px;
    }
    .route-zone-card {
        border: 0;
        border-bottom: 1px solid #d9e0e7;
        border-radius: 0;
        background: transparent;
        padding: 10px 14px;
    }
    .route-zone-card:last-child {
        border-bottom: 0;
    }
    .route-line-title {
        color: #0f766e;
        font-weight: 800;
    }
    .unassigned-table {
        font-size: .86rem;
    }
    .unassigned-table th {
        color: #ef4444;
        font-size: .78rem;
        white-space: nowrap;
    }
    .unassigned-table th button {
        border: 0;
        background: transparent;
        color: inherit;
        font-weight: 800;
        padding: 0;
    }
    .unassigned-table td {
        vertical-align: middle;
    }
    .unassigned-dot {
        width: 29px;
        height: 29px;
        border: 1px solid #0f766e;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #0f766e;
        font-weight: 700;
        background: #fff;
    }
    .btn-assign-shipper {
        background: #0f766e;
        border-color: #0f766e;
        color: #fff;
        min-width: 146px;
    }
    .btn-assign-shipper:hover {
        background: #115e59;
        border-color: #115e59;
        color: #fff;
    }
    .trip-definition-row {
        display: grid;
        grid-template-columns: minmax(185px, 1fr) 28px 64px minmax(130px, 180px) 34px 34px 110px;
        gap: 8px;
        align-items: center;
        padding: 5px 8px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        margin-bottom: 4px;
    }
    .trip-definition-row .form-label,
    .trip-order-table .form-label {
        display: none;
    }
    .trip-title-pill {
        color: #0f766e;
        font-weight: 800;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .trip-status-badge {
        background: #64748b;
        color: #fff;
        font-size: .68rem;
        border-radius: 4px;
        padding: 2px 5px;
        white-space: nowrap;
    }
    .trip-mini-select {
        height: 28px;
        font-size: .78rem;
        padding-top: 2px;
        padding-bottom: 2px;
    }
    .trip-plus-btn,
    .trip-arrow-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-weight: 800;
    }
    .trip-plus-btn {
        border: 1px solid #ff7a1a;
        color: #ff4d00;
        background: #fff7ed;
    }
    .trip-arrow-btn {
        border: 1px solid #f59e0b;
        color: #b45309;
        background: #fffbeb;
    }
    .trip-compact-total {
        color: #ff5a00;
        font-weight: 800;
        text-align: right;
        white-space: nowrap;
        font-size: .82rem;
    }
    .trip-order-table {
        font-size: .82rem;
        border-collapse: separate;
        border-spacing: 0 7px;
    }
    .trip-order-table thead {
        display: none;
    }
    .trip-order-table th {
        background: #eef2f7;
        color: #334155;
        border-color: #dbe4ea;
        font-size: .72rem;
        text-transform: uppercase;
        vertical-align: middle;
    }
    .trip-order-table th.trip-head-strong {
        background: #0b5f59;
        color: #fff;
    }
    .trip-order-table td {
        vertical-align: middle;
        background: #fff;
        border-top: 1px solid #dbe4ea;
        border-bottom: 1px solid #dbe4ea;
        padding: 7px 8px;
    }
    .trip-order-table td:first-child {
        border-left: 1px solid #dbe4ea;
        border-radius: 7px 0 0 7px;
    }
    .trip-order-table td:last-child {
        border-right: 1px solid #dbe4ea;
        border-radius: 0 7px 7px 0;
    }
    .trip-group-row td {
        background: transparent !important;
        border: 0 !important;
        padding: 14px 4px 2px !important;
    }
    .trip-group-line {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) 28px 64px minmax(140px, 180px) 34px 34px;
        gap: 8px;
        align-items: center;
    }
    .trip-group-title {
        color: #0f766e;
        font-weight: 800;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .trip-group-meta {
        color: #ff5a00;
        font-weight: 800;
        margin-left: 8px;
        white-space: nowrap;
    }
    .trip-order-customer,
    .trip-order-address {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .trip-order-customer {
        font-weight: 800;
        color: #111827;
    }
    .trip-order-time {
        color: #0f766e;
        font-weight: 900;
        margin-right: 6px;
        white-space: nowrap;
    }
    .order-meta-line {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 10px;
        margin-top: 3px;
        color: #64748b;
        font-size: .73rem;
        font-weight: 600;
    }
    .quick-products-btn {
        white-space: nowrap;
    }
    .trip-order-address {
        color: #475569;
    }
    .trip-order-table input,
    .trip-order-table select {
        min-width: 86px;
    }
    @media (max-width: 992px) {
        .trip-definition-row {
            grid-template-columns: 1fr 28px 64px;
        }
        .trip-definition-row .trip-mobile-break {
            grid-column: 1 / -1;
        }
    }
</style>
@endpush

@section('content')
<div id="manageAssignmentsApp" data-refresh-url="{{ route('shipper.manage-assignments', ['date' => $selectedDate]) }}">
<div class="row ">
    <div class="col col-md-6">
        <form method="GET" action="{{ route('shipper.manage-assignments') }}" class="d-flex gap-2 align-items-center flex-grow-1">
            <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm" style="max-width: 150px">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-search me-1"></i>Lọc
            </button>
            <a href="{{ route('shipper.manage-assignments', ['date' => now()->toDateString()]) }}" class="btn btn-sm {{ $selectedDate === now()->toDateString() ? 'btn-success' : 'btn-outline-success' }}">
                Hôm nay
            </a>
            <a href="{{ route('shipper.manage-assignments', ['date' => now()->subDay()->toDateString()]) }}" class="btn btn-sm {{ $selectedDate === now()->subDay()->toDateString() ? 'btn-success' : 'btn-outline-success' }}">
                Hôm qua
            </a>
            <a href="{{ route('shipper.manage-assignments') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>Đặt lại
            </a>
        </form>
        <div class="small text-muted mt-2">
            Đang hiển thị đơn lên ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}; ngày giao của từng đơn có thể là hôm nay hoặc ngày mai.
        </div>
    </div>
    <div class="col col-md-6 d-flex justify-content-end align-items-center">
        <form method="POST" action="{{ route('shipper.manage-assignments.review') }}" class="d-flex gap-2 align-items-center ms-auto js-route-review-form">
            @csrf
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <input type="hidden" name="route_plan" id="routePlanInput">
            <input type="text" name="notes" id="scheduleNotesInput" class="form-control form-control-sm" maxlength="500" placeholder="Ghi chú (tùy chọn)" style="width: 100%">
            <button type="submit"
                class="btn btn-sm {{ $assignedOrdersCount > 0 ? 'btn-success' : 'btn-secondary' }}"
                style="min-width: 220px"
                title="{{ $assignedOrdersCount > 0 ? 'Mở trang xem lại bảng kê trước khi gửi' : 'Chưa có đơn đã gán shipper' }}"
                @disabled($assignedOrdersCount === 0)>
                <i class="bi bi-eye me-1"></i>Xem lại & Gửi xác nhận
            </button>
        </form>
    </div>
</div> 

@php
    $priorityOrders = collect($assignedOrders)
        ->flatten(1)
        ->merge($unassignedOrders->getCollection())
        ->sortBy([
            fn ($order) => $order->daily_sequence ?? PHP_INT_MAX,
            fn ($order) => $order->delivery_time ?: '23:59:59',
            fn ($order) => $order->created_at?->timestamp ?? 0,
        ])
        ->values();
@endphp
<div class="priority-nav" aria-label="Số thứ tự ưu tiên đơn hàng">
    @foreach($priorityOrders as $priorityOrder)
        <a href="#order-{{ $priorityOrder->id }}"
            class="priority-dot {{ $priorityOrder->shipper_id ? 'is-routed' : '' }}"
            data-priority-order-id="{{ $priorityOrder->id }}"
            title="Đơn {{ $priorityOrder->code ?: $priorityOrder->id }}">
            {{ $priorityOrder->daily_sequence ?: $loop->iteration }}
        </a>
    @endforeach
</div>

<div class="ma-stats">
    <div class="ma-stat-card">
        <div class="ma-stat-value">{{ $totalOrdersCount }}</div>
        <div class="ma-stat-label">Tổng đơn trong luồng</div>
    </div>
    <div class="ma-stat-card">
        <div class="ma-stat-value">{{ $assignedOrdersCount }}</div>
        <div class="ma-stat-label">Đã gán shipper</div>
    </div>
    <div class="ma-stat-card">
        <div class="ma-stat-value">{{ $unassignedOrdersCount }}</div>
        <div class="ma-stat-label">Chưa gán shipper</div>
    </div>
    <div class="ma-stat-card">
        <div class="ma-stat-value">{{ $shippers->count() }}</div>
        <div class="ma-stat-label">Shipper sẵn sàng</div>
    </div>
</div>

<div class="assignment-workspace">
    <div class="assignment-unassigned">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold text-dark">Đơn hàng chưa gán</div>
                        <div class="text-muted small">Danh sách đơn chưa thuộc lộ trình; bấm tiêu đề cột để sắp xếp nhanh.</div>
                    </div>
                    <span class="badge bg-primary rounded-pill">{{ $unassignedOrders->total() }}</span>
                </div>

                @if($unassignedOrders->isEmpty())
                    <div class="text-center py-5 border rounded-3 bg-light">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0 text-muted">Không có đơn chưa gán trong ngày này.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table unassigned-table align-middle mb-0 js-unassigned-table">
                            <thead>
                                <tr>
                                    <th style="width: 44px;"></th>
                                    <th style="width: 68px;"><button type="button" data-sort="sequence">STT</button></th>
                                    <th style="width: 86px;"><button type="button" data-sort="time">Giờ giao</button></th>
                                    <th>Khách hàng</th>
                                    <th><button type="button" data-sort="address">Địa chỉ</button></th>
                                    <th style="width: 110px;"><button type="button" data-sort="fee">Giá ship</button></th>
                                    <th style="width: 170px;">Shipper</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unassignedOrders as $order)
                                    @php
                                        $customer = $order->customer;
                                        $address = $order->recipient_address ?: $customer?->address ?: 'Chưa cập nhật';
                                        $customerName = $customer?->name ?? $order->recipient_name ?? 'Khách hàng';
                                        $shippingFee = (float) ($order->shipping_fee ?? $customer?->shipping_fee ?? 0);
                                        $deliveryTime = $order->delivery_time ?: $customer?->delivery_time ?: '';
                                        $saleName = $order->user?->name ?: 'Chưa có sale';
                                        $originName = $order->warehouse?->name ?: 'Chưa chọn kho';
                                        $productPayload = $order->items->map(function ($item) {
                                            $variant = $item->variant;
                                            $productName = $item->product?->name ?: $variant?->product?->name ?: 'Sản phẩm';
                                            $variantName = $variant?->name ?: $variant?->variant_name ?: $variant?->sku;

                                            return [
                                                'name' => trim($productName . ($variantName ? ' - ' . $variantName : '')),
                                                'quantity' => (float) ($item->quantity ?? 0),
                                                'weight' => (float) ($item->total_weight ?? $item->actual_weight ?? 0),
                                                'price' => (float) ($item->price ?? 0),
                                                'total' => (float) ($item->total ?? 0),
                                            ];
                                        })->values();
                                    @endphp
                                    <tr id="order-{{ $order->id }}"
                                        class="js-unassigned-order"
                                        data-sequence="{{ $order->daily_sequence ?? 999999 }}"
                                        data-time="{{ $deliveryTime ?: '99:99' }}"
                                        data-address="{{ e($address) }}"
                                        data-fee="{{ $shippingFee }}">
                                        <td class="text-center"><span class="unassigned-dot">{{ $order->daily_sequence ?: $loop->iteration }}</span></td>
                                        <td class="fw-bold">{{ $order->daily_sequence ?: '-' }}</td>
                                        <td class="fw-bold text-success">{{ $deliveryTime ?: '-' }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $customerName }}</div>
                                            <div class="text-muted small">#{{ $order->code ?: $order->id }}</div>
                                            <div class="order-meta-line">
                                                <span><i class="bi bi-person-badge me-1"></i>{{ $saleName }}</span>
                                                <span><i class="bi bi-box-arrow-up-right me-1"></i>{{ $originName }}</span>
                                            </div>
                                        </td>
                                        <td class="text-muted">{{ \Illuminate\Support\Str::limit($address, 48) }}</td>
                                        <td class="text-end fw-bold text-muted">{{ number_format($shippingFee, 0, ',', '.') }} đ</td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-assign-shipper js-open-shipper-picker"
                                                data-bs-toggle="modal"
                                                data-bs-target="#shipperPickerModal"
                                                data-action="{{ route('shipper.assign-order.selected', $order) }}"
                                                data-order-id="{{ $order->id }}"
                                                data-order-code="{{ $order->code ?: $order->id }}"
                                                data-customer-name="{{ $customerName }}"
                                                data-set-default="{{ $customer?->default_shipper_id ? '0' : '1' }}">
                                                <i class="bi bi-person-plus me-1"></i>Chọn shipper
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary mt-1 quick-products-btn js-open-products-preview"
                                                data-bs-toggle="modal"
                                                data-bs-target="#productsPreviewModal"
                                                data-order-code="{{ $order->code ?: $order->id }}"
                                                data-customer-name="{{ $customerName }}"
                                                data-products="{{ e($productPayload->toJson(JSON_UNESCAPED_UNICODE)) }}">
                                                <i class="bi bi-list-ul me-1"></i>Sản phẩm
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $unassignedOrders->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="assignment-routes">
        <div class="border-0 h-100">
            <div class="body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold text-dark">Shipper và đơn đã gán</div>
                        <div class="text-muted small">Các đơn đã gán được gom trong vùng lộ trình màu xám theo từng shipper.</div>
                    </div>
                    <span class="badge bg-success rounded-pill">{{ $assignedOrdersCount }}</span>
                </div>

                @if($assignedOrdersCount === 0)
                    <div class="text-center py-5 border rounded-3 bg-light">
                        <i class="bi bi-truck fs-1 text-muted"></i>
                        <p class="mt-2 mb-0 text-muted">Chưa có đơn nào được gán shipper.</p>
                    </div>
                @else
                    <div class="route-zone">
                        @foreach($assignedOrders as $shipperId => $shipperOrders)
                            @if($shipperOrders->isNotEmpty())
                                @php $shipper = $shippers->firstWhere('id', $shipperId); @endphp
                                @php
                                    $scheduleStatus = $shipperScheduleStatuses[$shipperId] ?? 'waiting';
                                    $scheduleBadgeClass = match ($scheduleStatus) {
                                        'confirmed' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        'draft' => 'bg-secondary',
                                        default => 'bg-warning text-dark',
                                    };
                                    $scheduleLabel = match ($scheduleStatus) {
                                        'confirmed' => 'Đã Xác nhận',
                                        'rejected' => 'Từ chối',
                                        'draft' => 'Chưa gửi',
                                        default => 'Chờ xác nhận',
                                    };
                                @endphp
                                <div class="route-zone-card">
                                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $shipper?->name ?? 'Shipper #' . $shipperId }}</div>
                                            <div class="text-muted small">{{ $shipper?->phone ?? $shipper?->email ?? 'Không có liên hệ' }}</div>
                                            
                                        </div>
                                        <div class="d-flex align-items-center gap-2">                                            
                                            <div class="d-flex">
                                                <span class="badge bg-primary rounded-pill  me-2" style="white-space: nowrap;">{{ $shipperOrders->count() }}</span>
                                                <span class="fw-bold text-danger me-2 js-shipper-trip-total" style="white-space: nowrap;">0 đ</span>
                                                <div class="ma-shipper-meta">
                                                    <span class="badge {{ $scheduleBadgeClass }}">{{ $scheduleLabel }}</span>
                                                </div>
                                                
                                            </div>
                                            <form method="POST" action="{{ route('shipper.bulk-transfer-assignments') }}" class="d-flex gap-1" style="width: 220px;">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ $selectedDate }}">
                                                <input type="hidden" name="from_shipper_id" value="{{ $shipperId }}">
                                                <select name="to_shipper_id" class="form-select form-select-sm" required style="flex: 1; font-size: 0.8rem;">
                                                    <option value="">-- Chuyển --</option>
                                                    @foreach($shippers as $s)
                                                        @if($s->id != $shipperId)
                                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                                        @endif
                                                    @endforeach
                                                </select> 
                                                <button type="submit" class="btn btn-sm btn-outline-warning px-2" title="Chuyển tất cả {{ $shipperOrders->count() }} đơn">
                                                    <i class="bi bi-arrow-right"></i>
                                                </button>                                                 
                                            </form>
                                        </div>
                                    </div>
                                    @php $defaultTripCode = 'T' . $shipperId . '-1'; @endphp
                                    <div class="trip-planner js-trip-shipper"
                                        data-shipper-id="{{ $shipperId }}"
                                        data-shipper-name="{{ e($shipper?->name ?? 'Shipper #' . $shipperId) }}">
                                        <div class="js-trip-definitions">
                                            <div class="trip-definition-row js-trip-definition" data-trip-code="{{ $defaultTripCode }}">
                                                <div class="trip-title-pill">
                                                    Lộ trình - {{ $shipper?->name ?? 'Shipper #' . $shipperId }}
                                                    <input type="hidden" class="js-trip-name" value="Lộ trình 1 - {{ $shipper?->name ?? 'Shipper #' . $shipperId }}">
                                                    <input type="hidden" class="js-trip-km" value="">
                                                    <input type="hidden" class="js-trip-fee" value="">
                                                    <input type="hidden" class="js-trip-note" value="">
                                                </div>
                                                <span class="badge bg-primary rounded-pill js-trip-order-count">0</span>
                                                <span class="trip-status-badge">{{ $scheduleLabel }}</span>
                                                <select class="form-select form-select-sm trip-mini-select js-trip-jump">
                                                    <option value="{{ $defaultTripCode }}">-- Chuyến --</option>
                                                </select>
                                                <button type="button" class="trip-arrow-btn js-focus-trip" title="Chọn chuyến"><i class="bi bi-arrow-right"></i></button>
                                                <button type="button" class="trip-plus-btn js-add-trip" title="Thêm mới lộ trình">+</button>
                                                <span class="trip-compact-total js-trip-final-total">0 đ</span>
                                            </div>
                                        </div>
                                        <div class="table-responsive mt-2">
                                            <table class="table table-bordered trip-order-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 58px;" class="trip-head-strong">STT</th>
                                                        <th style="width: 220px;" class="trip-head-strong">Khách hàng</th>
                                                        <th>Địa chỉ</th>
                                                        <th style="width: 104px;">Điều chỉnh</th>
                                                        <th style="width: 104px;">Phí cuối</th>
                                                        <th style="width: 150px;">Thao tác</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($shipperOrders as $order)
                                                        @php
                                                            $customer = $order->customer;
                                                            $selectedRoute = $customer?->truckRoute;
                                                            if (!$selectedRoute && $customer?->truck_station_id) {
                                                                $selectedRoute = $customer?->truckRouteByStation;
                                                            }
                                                            $truckStation = $customer?->truckStation ?: ($selectedRoute?->stops?->last()?->station);
                                                            $destination = $order->recipient_address
                                                                ?: $customer?->truck_station_address
                                                                ?: $truckStation?->address
                                                                ?: $truckStation?->name
                                                                ?: $customer?->address
                                                                ?: 'Chưa cập nhật';
                                                            $quantity = (float) $order->items->sum('quantity');
                                                            $baseFee = (bool) ($order->charge_shipping_fee ?? true)
                                                                ? (float) ($order->shipping_fee ?? 0)
                                                                : 0;
                                                            $customerName = $customer?->name ?? $order->recipient_name ?? 'Khách hàng';
                                                            $deliveryTime = $order->delivery_time ?: $customer?->delivery_time ?: '';
                                                            $saleName = $order->user?->name ?: 'Chưa có sale';
                                                            $originName = $order->warehouse?->name ?: 'Chưa chọn kho';
                                                            $productPayload = $order->items->map(function ($item) {
                                                                $variant = $item->variant;
                                                                $productName = $item->product?->name ?: $variant?->product?->name ?: 'Sản phẩm';
                                                                $variantName = $variant?->name ?: $variant?->variant_name ?: $variant?->sku;

                                                                return [
                                                                    'name' => trim($productName . ($variantName ? ' - ' . $variantName : '')),
                                                                    'quantity' => (float) ($item->quantity ?? 0),
                                                                    'weight' => (float) ($item->total_weight ?? $item->actual_weight ?? 0),
                                                                    'price' => (float) ($item->price ?? 0),
                                                                    'total' => (float) ($item->total ?? 0),
                                                                ];
                                                            })->values();
                                                        @endphp
                                                        <tr id="order-{{ $order->id }}"
                                                            class="js-trip-order"
                                                            data-order-id="{{ $order->id }}"
                                                            data-order-code="{{ $order->code ?: $order->id }}"
                                                            data-customer-name="{{ e($customerName) }}"
                                                            data-destination="{{ e($destination) }}"
                                                            data-origin="{{ e($order->warehouse?->name ?: 'Kho') }}"
                                                            data-quantity="{{ $quantity }}"
                                                            data-base-fee="{{ $baseFee }}">
                                                            <td class="text-center">
                                                                <span class="priority-dot is-routed" style="width:28px;height:28px;font-size:.78rem;">{{ $order->daily_sequence ?: $loop->iteration }}</span>
                                                            </td>
                                                            <td class="trip-order-customer">
                                                                @if($deliveryTime)
                                                                    <span class="trip-order-time">{{ $deliveryTime }}</span>
                                                                @endif
                                                                {{ $customerName }}
                                                                <div class="order-meta-line">
                                                                    <span><i class="bi bi-person-badge me-1"></i>{{ $saleName }}</span>
                                                                    <span><i class="bi bi-box-arrow-up-right me-1"></i>{{ $originName }}</span>
                                                                </div>
                                                                <input type="hidden" class="js-order-trip" value="{{ $defaultTripCode }}">
                                                            </td>
                                                            <td class="trip-order-address" title="{{ $destination }}">{{ $destination }}</td>
                                                            <td>
                                                                <input type="number" step="1000" class="form-control form-control-sm js-order-extra-fee" value="0">
                                                            </td>
                                                            <td class="text-end fw-bold js-order-final-fee">{{ number_format($baseFee, 0, ',', '.') }}</td>
                                                            <td>
                                                                <input type="hidden" class="js-order-trip-note" value="">
                                                                <div class="d-flex gap-1 flex-wrap">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-secondary quick-products-btn js-open-products-preview"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#productsPreviewModal"
                                                                        data-order-code="{{ $order->code ?: $order->id }}"
                                                                        data-customer-name="{{ $customerName }}"
                                                                        data-products="{{ e($productPayload->toJson(JSON_UNESCAPED_UNICODE)) }}">
                                                                        <i class="bi bi-list-ul me-1"></i>SP
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-success js-open-shipper-picker"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#shipperPickerModal"
                                                                        data-action="{{ route('shipper.assign-order.selected', $order) }}"
                                                                        data-order-id="{{ $order->id }}"
                                                                        data-order-code="{{ $order->code ?: $order->id }}"
                                                                        data-customer-name="{{ $customerName }}"
                                                                        data-set-default="0">
                                                                        <i class="bi bi-arrow-left-right me-1"></i>Chuyển
                                                                    </button>
                                                                    <form action="{{ route('shipper.unassign-order', [$order->id]) }}" method="POST">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn gỡ đơn này ra?')">
                                                                            <i class="bi bi-x-circle me-1"></i>Gỡ
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="shipperPickerModal" tabindex="-1" aria-labelledby="shipperPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="shipperPickerModalLabel">Chọn shipper</h5>
                    <div class="small text-muted" id="shipperPickerOrderInfo"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <form id="shipperPickerForm" method="POST">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="shipper_id" id="shipperPickerShipperId">
                    <input type="hidden" name="set_default_shipper" id="shipperPickerSetDefault" value="0">
                    <input type="hidden" id="shipperPickerOrderId">
                    <input type="hidden" id="shipperPickerTripCode">
                    <div id="shipperRoutePicker" class="d-grid gap-2"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="defaultShipperPickerModal" tabindex="-1" aria-labelledby="defaultShipperPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="defaultShipperPickerModalLabel">Đổi shipper cố định</h5>
                    <div class="small text-muted" id="defaultShipperPickerCustomerInfo"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <form id="defaultShipperPickerForm" method="POST">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="shipper_id" id="defaultShipperPickerShipperId">
                    <input type="hidden" name="transfer_pending_orders" value="0">
                    <label class="form-check mb-3">
                        <input type="checkbox" name="transfer_pending_orders" value="1" class="form-check-input" checked>
                        <span class="form-check-label">Chuyển các đơn đang chờ sang shipper mới</span>
                    </label>
                    <div class="d-grid gap-2">
                        @foreach($shippers as $fixedPickerShipper)
                            <button type="submit"
                                class="btn btn-outline-primary text-start js-pick-default-shipper"
                                data-shipper-id="{{ $fixedPickerShipper->id }}">
                                <i class="bi bi-person me-2"></i>{{ $fixedPickerShipper->name }}
                                @if($fixedPickerShipper->phone)
                                    <span class="text-muted small ms-1">{{ $fixedPickerShipper->phone }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productsPreviewModal" tabindex="-1" aria-labelledby="productsPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="productsPreviewModalLabel">Sản phẩm trong đơn</h5>
                    <div class="small text-muted" id="productsPreviewOrderInfo"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-end" style="width:90px;">SL</th>
                                <th class="text-end" style="width:100px;">Kg</th>
                                <th class="text-end" style="width:120px;">Đơn giá</th>
                                <th class="text-end" style="width:130px;">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody id="productsPreviewBody">
                            <tr>
                                <td colspan="5" class="text-muted text-center py-3">Chưa có sản phẩm.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
@php
    $availableShippersForPicker = $shippers->map(function ($shipper) {
        return [
            'id' => (int) $shipper->id,
            'name' => $shipper->name,
            'phone' => $shipper->phone,
        ];
    })->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const appSelector = '#manageAssignmentsApp';
    const currency = new Intl.NumberFormat('vi-VN');
    const availableShippers = @json($availableShippersForPicker);
    const tripStorageKey = 'shipperTripPlan:' + @json($selectedDate);
    let isRestoringTrips = false;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char];
        });
    }

    function loadTripState() {
        try {
            return JSON.parse(localStorage.getItem(tripStorageKey) || '{}');
        } catch (error) {
            return {};
        }
    }

    function saveTripState() {
        if (isRestoringTrips) return;

        const state = {shippers: {}, orders: {}};
        document.querySelectorAll('.js-trip-shipper').forEach(function (shipperBlock) {
            const shipperId = shipperBlock.dataset.shipperId;
            state.shippers[shipperId] = tripDefinitionsFor(shipperBlock).map(function (trip) {
                return {
                    code: trip.code,
                    name: trip.name,
                    km: trip.km,
                    combined_fee: trip.combined_fee,
                    note: trip.note,
                };
            });

            shipperBlock.querySelectorAll('.js-trip-order').forEach(function (row) {
                state.orders[row.dataset.orderId] = {
                    shipper_id: Number(shipperId),
                    trip_code: row.querySelector('.js-order-trip')?.value || '',
                    extra_fee: row.querySelector('.js-order-extra-fee')?.value || '0',
                    note: row.querySelector('.js-order-trip-note')?.value || '',
                };
            });
        });

        localStorage.setItem(tripStorageKey, JSON.stringify(state));
    }

    function numberValue(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function tripDefinitionsFor(shipperBlock) {
        return Array.from(shipperBlock.querySelectorAll('.js-trip-definition')).map(function (definition, index) {
            if (!definition.dataset.tripCode) {
                definition.dataset.tripCode = 'T' + shipperBlock.dataset.shipperId + '-' + (index + 1);
            }

            return {
                code: definition.dataset.tripCode,
                name: definition.querySelector('.js-trip-name')?.value?.trim() || ('Chuyến ' + (index + 1)),
                km: numberValue(definition.querySelector('.js-trip-km')?.value),
                combined_fee: numberValue(definition.querySelector('.js-trip-fee')?.value),
                note: definition.querySelector('.js-trip-note')?.value?.trim() || '',
                element: definition,
            };
        });
    }

    function syncTripOptions(shipperBlock) {
        const trips = tripDefinitionsFor(shipperBlock);
        const optionsHtml = trips.map(trip => `<option value="${trip.code}">${trip.name}</option>`).join('');

        shipperBlock.querySelectorAll('.js-order-trip').forEach(function (select) {
            const current = select.value || trips[0]?.code || '';
            select.value = trips.some(trip => trip.code === current) ? current : (trips[0]?.code || '');
        });

        shipperBlock.querySelectorAll('.js-trip-jump').forEach(function (select) {
            const current = select.closest('.js-trip-definition')?.dataset.tripCode || select.value || trips[0]?.code || '';
            select.innerHTML = '<option value="">-- Chuyến --</option>' + optionsHtml;
            select.value = trips.some(trip => trip.code === current) ? current : '';
        });
    }

    function collectTripPlan() {
        const plan = [];
        document.querySelectorAll('.js-trip-shipper').forEach(function (shipperBlock) {
            syncTripOptions(shipperBlock);

            const trips = tripDefinitionsFor(shipperBlock).map(function (trip) {
                return {...trip, original_total: 0, additional_total: 0, final_total: 0, orders: []};
            });
            const tripByCode = new Map(trips.map(trip => [trip.code, trip]));
            let shipperFinalTotal = 0;

            shipperBlock.querySelectorAll('.js-trip-order').forEach(function (row) {
                const enabled = row.querySelector('.js-trip-order-enabled')?.checked ?? true;
                if (!enabled) return;

                const tripCode = row.querySelector('.js-order-trip')?.value || trips[0]?.code;
                const trip = tripByCode.get(tripCode);
                if (!trip) return;

                const baseFee = numberValue(row.dataset.baseFee);
                const extraFee = numberValue(row.querySelector('.js-order-extra-fee')?.value);
                const finalFee = Math.max(0, baseFee + extraFee);
                const finalFeeText = row.querySelector('.js-order-final-fee');
                if (finalFeeText) {
                    finalFeeText.textContent = currency.format(finalFee);
                }

                const order = {
                    order_id: Number(row.dataset.orderId),
                    order_code: row.dataset.orderCode,
                    customer_name: row.dataset.customerName,
                    destination: row.dataset.destination,
                    origin: row.dataset.origin,
                    quantity: numberValue(row.dataset.quantity),
                    base_fee: baseFee,
                    extra_fee: extraFee,
                    final_fee: finalFee,
                    note: row.querySelector('.js-order-trip-note')?.value?.trim() || '',
                };

                row.dataset.tripCode = trip.code;
                trip.orders.push(order);
                trip.original_total += baseFee;
                trip.additional_total += extraFee;
                trip.final_total += finalFee;
                shipperFinalTotal += finalFee;
            });

            trips.forEach(function (trip) {
                const countEl = trip.element?.querySelector('.js-trip-order-count');
                const totalEl = trip.element?.querySelector('.js-trip-final-total');
                const feeInput = trip.element?.querySelector('.js-trip-fee');
                if (countEl) {
                    countEl.textContent = trip.orders.length;
                }
                if (totalEl) {
                    totalEl.textContent = currency.format(trip.final_total) + ' đ';
                }
                if (feeInput) {
                    feeInput.value = trip.final_total || '';
                }
            });

            const totalEl = shipperBlock.closest('.route-zone-card')?.querySelector('.js-shipper-trip-total');
            if (totalEl) {
                totalEl.textContent = currency.format(shipperFinalTotal) + ' đ';
            }

            renderTripOrderGroups(shipperBlock, trips);

            plan.push({
                shipper_id: Number(shipperBlock.dataset.shipperId),
                shipper_name: shipperBlock.dataset.shipperName,
                routes: trips
                    .filter(trip => trip.orders.length > 0)
                    .map(function (trip) {
                        const {element, ...payload} = trip;
                        return payload;
                    }),
            });
        });

        return plan.filter(shipper => shipper.routes.length > 0);
    }

    function renderTripOrderGroups(shipperBlock, trips) {
        const tbody = shipperBlock.querySelector('.trip-order-table tbody');
        if (!tbody) return;

        tbody.querySelectorAll('.trip-group-row').forEach(row => row.remove());

        const allRows = Array.from(tbody.querySelectorAll('.js-trip-order'));
        const fallbackCode = trips[0]?.code || '';
        trips.forEach(function (trip) {
            const tripRows = allRows.filter(row => (row.dataset.tripCode || fallbackCode) === trip.code);

            const groupRow = document.createElement('tr');
            groupRow.className = 'trip-group-row';
            groupRow.innerHTML = `
                <td colspan="6">
                    <div class="trip-group-line">
                        <div class="trip-group-title">
                            ${trip.name}
                            <span class="trip-group-meta">${tripRows.length} đơn · ${currency.format(trip.final_total)} đ</span>
                        </div>
                        <span class="badge bg-primary rounded-pill text-center">${tripRows.length}</span>
                        <span class="trip-status-badge">Chưa gửi</span>
                        <select class="form-select form-select-sm trip-mini-select js-trip-jump" data-trip-code="${trip.code}">
                            ${trips.map(optionTrip => `<option value="${optionTrip.code}" ${optionTrip.code === trip.code ? 'selected' : ''}>${optionTrip.name}</option>`).join('')}
                        </select>
                        <button type="button" class="trip-arrow-btn js-focus-trip" data-trip-code="${trip.code}" title="Chọn chuyến"><i class="bi bi-arrow-right"></i></button>
                        <button type="button" class="trip-plus-btn js-add-trip" title="Thêm mới lộ trình">+</button>
                    </div>
                </td>
            `;
            tbody.appendChild(groupRow);
            tripRows.forEach(row => tbody.appendChild(row));
        });
    }

    function refreshTripBlocks() {
        document.querySelectorAll('.js-trip-shipper').forEach(syncTripOptions);
        collectTripPlan();
        saveTripState();
    }

    function ensureTripDefinition(shipperBlock, savedTrip) {
        if (!shipperBlock || !savedTrip?.code) return;
        if (shipperBlock.querySelector(`.js-trip-definition[data-trip-code="${savedTrip.code}"]`)) return;

        const definitions = shipperBlock.querySelector('.js-trip-definitions');
        const name = savedTrip.name || ('Lộ trình ' + (definitions.querySelectorAll('.js-trip-definition').length + 1) + ' - ' + (shipperBlock.dataset.shipperName || ''));
        definitions.insertAdjacentHTML('beforeend', `
            <div class="trip-definition-row js-trip-definition" data-trip-code="${savedTrip.code}">
                <div class="trip-title-pill">
                    ${name}
                    <input type="hidden" class="js-trip-name" value="${name}">
                    <input type="hidden" class="js-trip-km" value="${savedTrip.km || ''}">
                    <input type="hidden" class="js-trip-fee" value="${savedTrip.combined_fee || ''}">
                    <input type="hidden" class="js-trip-note" value="${savedTrip.note || ''}">
                </div>
                <span class="badge bg-primary rounded-pill js-trip-order-count">0</span>
                <span class="trip-status-badge">Chưa gửi</span>
                <select class="form-select form-select-sm trip-mini-select js-trip-jump">
                    <option value="${savedTrip.code}">-- Chuyến --</option>
                </select>
                <button type="button" class="trip-arrow-btn js-focus-trip" title="Chọn chuyến"><i class="bi bi-arrow-right"></i></button>
                <button type="button" class="trip-plus-btn js-add-trip" title="Thêm mới lộ trình">+</button>
                <span class="trip-compact-total js-trip-final-total">0 đ</span>
            </div>
        `);
    }

    function restoreTripState() {
        const state = loadTripState();
        if (!state.shippers && !state.orders) return;

        isRestoringTrips = true;
        Object.entries(state.shippers || {}).forEach(function ([shipperId, trips]) {
            const shipperBlock = document.querySelector(`.js-trip-shipper[data-shipper-id="${shipperId}"]`);
            if (!shipperBlock) return;
            (trips || []).forEach(function (trip) {
                ensureTripDefinition(shipperBlock, trip);
            });
        });

        document.querySelectorAll('.js-trip-shipper').forEach(syncTripOptions);

        Object.entries(state.orders || {}).forEach(function ([orderId, savedOrder]) {
            const row = document.querySelector(`.js-trip-order[data-order-id="${orderId}"]`);
            if (!row) return;
            const shipperBlock = row.closest('.js-trip-shipper');
            if (!shipperBlock) return;
            if (savedOrder.trip_code) {
                const select = row.querySelector('.js-order-trip');
                const availableTripCodes = tripDefinitionsFor(shipperBlock).map(trip => trip.code);
                if (select && availableTripCodes.includes(savedOrder.trip_code)) {
                    select.value = savedOrder.trip_code;
                }
            }
            const extraInput = row.querySelector('.js-order-extra-fee');
            if (extraInput) extraInput.value = savedOrder.extra_fee ?? '0';
            const noteInput = row.querySelector('.js-order-trip-note');
            if (noteInput) noteInput.value = savedOrder.note ?? '';
        });

        isRestoringTrips = false;
        refreshTripBlocks();
    }

    function sortUnassignedTable(key) {
        const table = document.querySelector('.js-unassigned-table tbody');
        if (!table) return;

        const rows = Array.from(table.querySelectorAll('.js-unassigned-order'));
        const currentKey = table.dataset.sortKey;
        const currentDirection = table.dataset.sortDirection || 'asc';
        const direction = currentKey === key && currentDirection === 'asc' ? 'desc' : 'asc';
        table.dataset.sortKey = key;
        table.dataset.sortDirection = direction;

        rows.sort(function (a, b) {
            let av = a.dataset[key] || '';
            let bv = b.dataset[key] || '';
            if (['sequence', 'fee'].includes(key)) {
                av = numberValue(av);
                bv = numberValue(bv);
            }
            if (av < bv) return direction === 'asc' ? -1 : 1;
            if (av > bv) return direction === 'asc' ? 1 : -1;
            return 0;
        });

        rows.forEach(row => table.appendChild(row));
    }

    function addTripToBlock(shipperBlock) {
        const definitions = shipperBlock.querySelector('.js-trip-definitions');
        const count = definitions.querySelectorAll('.js-trip-definition').length + 1;
        const tripCode = 'T' + shipperBlock.dataset.shipperId + '-' + count + '-' + Date.now();
        definitions.insertAdjacentHTML('beforeend', `
            <div class="trip-definition-row js-trip-definition" data-trip-code="${tripCode}">
                <div class="trip-title-pill">
                    Lộ trình ${count} - ${shipperBlock.dataset.shipperName || ''}
                    <input type="hidden" class="js-trip-name" value="Lộ trình ${count} - ${shipperBlock.dataset.shipperName || ''}">
                    <input type="hidden" class="js-trip-km" value="">
                    <input type="hidden" class="js-trip-fee" value="">
                    <input type="hidden" class="js-trip-note" value="">
                </div>
                <span class="badge bg-primary rounded-pill js-trip-order-count">0</span>
                <span class="trip-status-badge">Chưa gửi</span>
                <select class="form-select form-select-sm trip-mini-select js-trip-jump">
                    <option value="${tripCode}">-- Chuyến --</option>
                </select>
                <button type="button" class="trip-arrow-btn js-focus-trip" title="Chọn chuyến"><i class="bi bi-arrow-right"></i></button>
                <button type="button" class="trip-plus-btn js-add-trip" title="Thêm mới lộ trình">+</button>
                <span class="trip-compact-total js-trip-final-total">0 đ</span>
            </div>
        `);
        refreshTripBlocks();
    }

    function renderShipperRoutePicker() {
        const picker = document.getElementById('shipperRoutePicker');
        if (!picker) return;

        picker.innerHTML = availableShippers.map(function (shipper) {
            const shipperBlock = document.querySelector(`.js-trip-shipper[data-shipper-id="${shipper.id}"]`);
            const trips = shipperBlock ? tripDefinitionsFor(shipperBlock) : [{
                code: 'T' + shipper.id + '-1',
                name: 'Lộ trình 1 - ' + shipper.name,
            }];
            const routeButtons = trips.map(function (trip) {
                return `
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-outline-success text-start flex-grow-1 js-pick-shipper" data-shipper-id="${shipper.id}" data-trip-code="${trip.code}">
                            <i class="bi bi-person me-2"></i>${trip.name}
                        </button>
                        <button type="button" class="btn btn-outline-danger js-popup-add-trip" data-shipper-id="${shipper.id}" title="Thêm mới lộ trình">+</button>
                    </div>
                `;
            }).join('');

            return `
                <div class="border rounded-2 p-2">
                    <button type="submit" class="btn btn-success text-start w-100 js-pick-shipper" data-shipper-id="${shipper.id}">
                        <i class="bi bi-person-check me-2"></i>${shipper.name}${shipper.phone ? `<span class="small ms-1">${shipper.phone}</span>` : ''}
                    </button>
                    <div class="d-grid gap-2 mt-2">
                        ${routeButtons}
                    </div>
                </div>
            `;
        }).join('');
    }

    restoreTripState();

    function notify(message, isError = false) {
        const alert = document.createElement('div');
        alert.className = `alert ${isError ? 'alert-danger' : 'alert-success'} shadow position-fixed top-0 end-0 m-3`;
        alert.style.zIndex = '2000';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3500);
    }

    async function refreshAssignments() {
        const app = document.querySelector(appSelector);
        const response = await fetch(app.dataset.refreshUrl, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        if (!response.ok) throw new Error('Không thể tải lại danh sách điều phối.');
        const html = await response.text();
        const documentFragment = new DOMParser().parseFromString(html, 'text/html');
        const refreshedApp = documentFragment.querySelector(appSelector);
        if (!refreshedApp) throw new Error('Dữ liệu điều phối trả về không hợp lệ.');
        app.replaceWith(refreshedApp);
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        restoreTripState();
    }

    document.addEventListener('click', function (event) {
        const addTripButton = event.target.closest('.js-add-trip');
        if (addTripButton) {
            const shipperBlock = addTripButton.closest('.js-trip-shipper');
            addTripToBlock(shipperBlock);
            return;
        }

        const sortButton = event.target.closest('.js-unassigned-table [data-sort]');
        if (sortButton) {
            sortUnassignedTable(sortButton.dataset.sort);
            return;
        }

        const removeTripButton = event.target.closest('.js-remove-trip');
        if (removeTripButton && !removeTripButton.disabled) {
            const shipperBlock = removeTripButton.closest('.js-trip-shipper');
            removeTripButton.closest('.js-trip-definition')?.remove();
            refreshTripBlocks();
            return;
        }

        const focusTripButton = event.target.closest('.js-focus-trip');
        if (focusTripButton) {
            const definition = focusTripButton.closest('.js-trip-definition');
            const shipperBlock = focusTripButton.closest('.js-trip-shipper');
            const selectedTrip = focusTripButton.closest('.trip-group-line, .trip-definition-row')?.querySelector('.js-trip-jump')?.value;
            const tripCode = selectedTrip || focusTripButton.dataset.tripCode || definition?.dataset.tripCode;
            if (shipperBlock && tripCode) {
                shipperBlock.querySelectorAll('.js-order-trip').forEach(function (input) {
                    input.value = tripCode;
                });
                refreshTripBlocks();
            }
            return;
        }

        const popupAddTripButton = event.target.closest('.js-popup-add-trip');
        if (popupAddTripButton) {
            const shipperBlock = document.querySelector(`.js-trip-shipper[data-shipper-id="${popupAddTripButton.dataset.shipperId}"]`);
            if (shipperBlock) {
                addTripToBlock(shipperBlock);
                renderShipperRoutePicker();
                notify('Đã tạo lộ trình mới cho shipper.');
            } else {
                notify('Shipper này chưa có đơn trong ngày. Hãy chọn shipper để gán đơn trước.', true);
            }
            return;
        }

        const productsPreviewButton = event.target.closest('.js-open-products-preview');
        if (productsPreviewButton) {
            const orderInfo = document.getElementById('productsPreviewOrderInfo');
            const body = document.getElementById('productsPreviewBody');
            const money = new Intl.NumberFormat('vi-VN');
            let products = [];

            try {
                products = JSON.parse(productsPreviewButton.dataset.products || '[]');
            } catch (error) {
                products = [];
            }

            if (orderInfo) {
                orderInfo.textContent = 'Đơn ' + (productsPreviewButton.dataset.orderCode || '') + ' - ' + (productsPreviewButton.dataset.customerName || '');
            }

            if (body) {
                if (!products.length) {
                    body.innerHTML = '<tr><td colspan="5" class="text-muted text-center py-3">Chưa có sản phẩm.</td></tr>';
                } else {
                    body.innerHTML = products.map(function (item) {
                        const quantity = Number(item.quantity || 0);
                        const weight = Number(item.weight || 0);
                        const price = Number(item.price || 0);
                        const total = Number(item.total || 0);

                        return `
                            <tr>
                                <td class="fw-semibold">${escapeHtml(item.name || 'Sản phẩm')}</td>
                                <td class="text-end">${money.format(quantity)}</td>
                                <td class="text-end">${weight ? money.format(weight) : '-'}</td>
                                <td class="text-end">${price ? money.format(price) + ' đ' : '-'}</td>
                                <td class="text-end fw-bold">${money.format(total)} đ</td>
                            </tr>
                        `;
                    }).join('');
                }
            }
            return;
        }

        const button = event.target.closest('.js-open-shipper-picker, .js-open-default-shipper-picker, .js-pick-shipper, .js-pick-default-shipper');
        if (!button) return;

        if (button.classList.contains('js-open-shipper-picker')) {
            const form = document.getElementById('shipperPickerForm');
            form.action = button.dataset.action;
            document.getElementById('shipperPickerShipperId').value = '';
            document.getElementById('shipperPickerOrderId').value = button.dataset.orderId || '';
            document.getElementById('shipperPickerTripCode').value = '';
            document.getElementById('shipperPickerSetDefault').value = button.dataset.setDefault;
            document.getElementById('shipperPickerOrderInfo').textContent = 'Đơn ' + button.dataset.orderCode + ' - ' + button.dataset.customerName;
            renderShipperRoutePicker();
        }
        if (button.classList.contains('js-pick-shipper')) {
            document.getElementById('shipperPickerShipperId').value = button.dataset.shipperId;
            document.getElementById('shipperPickerTripCode').value = button.dataset.tripCode || '';
        }
        if (button.classList.contains('js-open-default-shipper-picker')) {
            const form = document.getElementById('defaultShipperPickerForm');
            form.action = button.dataset.action;
            document.getElementById('defaultShipperPickerShipperId').value = '';
            document.getElementById('defaultShipperPickerCustomerInfo').textContent = 'Khách hàng: ' + button.dataset.customerName;
            document.querySelectorAll('.js-pick-default-shipper').forEach(function (shipperButton) {
                shipperButton.classList.toggle('active', shipperButton.dataset.shipperId === button.dataset.currentShipperId);
            });
        }
        if (button.classList.contains('js-pick-default-shipper')) {
            document.getElementById('defaultShipperPickerShipperId').value = button.dataset.shipperId;
        }
    });

    document.addEventListener('input', function (event) {
        if (!event.target.closest('.js-trip-shipper')) return;
        refreshTripBlocks();
    });

    document.addEventListener('change', function (event) {
        if (!event.target.closest('.js-trip-shipper')) return;
        refreshTripBlocks();
    });

    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!form.closest(appSelector) || form.method.toLowerCase() === 'get') return;
        if (form.classList.contains('js-route-review-form')) {
            const routePlanInput = document.getElementById('routePlanInput');
            const plan = collectTripPlan();
            if (routePlanInput) {
                routePlanInput.value = JSON.stringify(plan);
            }
            if (!plan.length) {
                event.preventDefault();
                notify('Vui lòng chọn ít nhất một đơn để tạo chuyến.', true);
            }
            return;
        }
        if (form.id === 'shipperPickerForm') {
            const orderId = document.getElementById('shipperPickerOrderId')?.value;
            const shipperId = document.getElementById('shipperPickerShipperId')?.value;
            const tripCode = document.getElementById('shipperPickerTripCode')?.value;
            if (orderId && shipperId) {
                const state = loadTripState();
                state.shippers = state.shippers || {};
                state.orders = state.orders || {};
                const shipperBlock = document.querySelector(`.js-trip-shipper[data-shipper-id="${shipperId}"]`);
                if (shipperBlock) {
                    state.shippers[shipperId] = tripDefinitionsFor(shipperBlock).map(function (trip) {
                        return {
                            code: trip.code,
                            name: trip.name,
                            km: trip.km,
                            combined_fee: trip.combined_fee,
                            note: trip.note,
                        };
                    });
                }
                const resolvedTripCode = tripCode || state.shippers?.[shipperId]?.[0]?.code || ('T' + shipperId + '-1');
                state.orders[orderId] = {
                    shipper_id: Number(shipperId),
                    trip_code: resolvedTripCode,
                    extra_fee: '0',
                    note: '',
                };
                localStorage.setItem(tripStorageKey, JSON.stringify(state));
            }
        }
        event.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: new FormData(form),
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Không thể cập nhật điều phối.');
            }
            document.querySelectorAll('.modal.show').forEach(function (modal) {
                bootstrap.Modal.getInstance(modal)?.hide();
            });
            await refreshAssignments();
            notify(payload.message || 'Đã cập nhật điều phối.');
        } catch (error) {
            if (submitButton) submitButton.disabled = false;
            notify(error.message, true);
        }
    });
});
</script>
@endpush
@endsection
