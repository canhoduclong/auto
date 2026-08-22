@extends('layouts.shipper')

@section('title', 'Đơn có thể nhận')
@section('subtitle', 'Danh sách đơn đã được quản lý gán và sẵn sàng giao')

@push('styles')
<style>
      /* Cấu trúc thẻ đơn hàng (Card) */
        .order-card {
            position: relative;
            border: none; 
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            background-color: #ffffff;
            scroll-margin-top: 150px;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, background-color .2s ease;
        }
        .order-card.is-highlighted {
            transform: translateY(-2px);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, .22), 0 16px 32px rgba(15, 23, 42, .14);
        }
        .order-card.is-moving {
            animation: sp-av-card-move .45s ease;
        }
        .order-card.is-new {
            animation: sp-av-card-new .7s ease;
        }

        /* Khối hiển thị số thứ tự và thời gian bên trái */
        .time-block {
            min-width: 90px;
            background-color: #e2eee6; /* Màu xanh pastel nhẹ */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 15px 10px;
            transition: background-color .2s ease;
        }
        .time-block.is-available {
            background-color: color-mix(in srgb, var(--theme-primary) 14%, #ffffff);
        }
        .time-block.is-accepted {
            background-color: #f59e0b;
        }
        
        .time-block .order-number {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1;
            color: #1a1a1a;
        }
        
        .time-block .order-time {
            font-size: 0.85rem;
            font-weight: 700;
            color: #2b2b2b;
            margin-top: 6px;
        }

        /* Định dạng bảng chi tiết danh sách sản phẩm */
        .table-detail {
            margin-bottom: 0;
        }
        .table-detail th {
            color: #7a869a;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #edf0f4;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .table-detail td {
            font-size: 0.85rem;
            vertical-align: middle;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        /* Đường gạch đứt đoạn phân tách phần thanh toán tổng */
        .border-dashed {
            border-top: 1px dashed #cbd5e1;
        }

        /* Nút Nhận đơn màu xanh ngọc đồng bộ thương hiệu */
        .btn-teal {
            background-color: #ffffff;
            color: #0d7a70;
            border: 1px solid #0d7a70;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 5px 16px;
            border-radius: 6px;
            transition: all 0.2s ease-in-out;
        }
        .btn-teal:hover {
            background-color: #0d7a70;
            color: #ffffff;
        }

        /* Link thu gọn / xem chi tiết */
        .toggle-link {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1a1a1a;
            text-decoration: none;
        }

        /* Nút nhận đơn màu xanh ngọc giống hình mẫu */
        .btn-accept {
            background-color: #0d7a70;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 10px;
            transition: background 0.2s;
        }
        .btn-accept:hover {
            background-color: #0a5f57;
            color: white;
        }

        /* Định dạng phần text gạch đứt đoạn */
        .border-dashed {
            border-top: 1px dashed #dee2e6;
        }
    .sp-av-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        height: 100%;
    }
    .sp-av-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
    }
    .sp-av-order-code {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
    }
    .sp-av-order-time {
        color: #64748b;
        font-size: .78rem;
    }
    .sp-av-meta-label {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .sp-av-meta-value {
        font-weight: 700;
        color: #0f172a;
        font-size: .92rem;
    }
    .sp-av-section {
        border-top: 1px dashed #e2e8f0;
        padding-top: 8px;
        margin-top: 8px;
    }
    .sp-av-table-head,
    .sp-av-table-row {
        display: grid;
        grid-template-columns: minmax(0, 2fr) 36px 44px 34px 68px 100px;
        gap: 8px;
        align-items: center;
    }
    .sp-av-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 4px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .sp-av-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .sp-av-item-row {
        display: grid;
        gap: 4px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .sp-av-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .sp-av-item-top {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .sp-av-item-name {
        font-size: .88rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sp-av-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: right;
    }
    .sp-av-item-cell strong {
        color: #0f172a;
    }
    .sp-av-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-size: .82rem;
        padding: 2px 0;
        color: #475569;
    }
    .sp-av-summary-row.total {
        margin-top: 4px;
        padding-top: 6px;
        border-top: 1px dashed #cbd5e1;
        font-weight: 800;
        color: #0f172a;
        font-size: .95rem;
    }
    .sp-av-quick-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .sp-av-quick-pill {
        border-radius: 999px;
        padding: 6px 10px;
        font-size: .78rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
    }
    .sp-av-quick-pill.active {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .sp-av-quick-pill.disabled {
        opacity: .55;
        background: #f8fafc;
        color: #64748b;
        border-style: dashed;
        pointer-events: none;
    }
    .sp-av-quick-count {
        min-width: 20px;
        height: 20px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
        background: #e2e8f0;
        color: #334155;
        padding: 0 6px;
    }
    .sp-av-quick-pill.active .sp-av-quick-count {
        background: #2563eb;
        color: #fff;
    }
    .sp-av-summary-pill {
        border-radius: 999px;
        padding: 7px 12px;
        font-size: .82rem;
        font-weight: 700;
    }
    .sp-av-order-nav-area {
        position: sticky;
        top: 75px;
        z-index: 95;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .sp-av-order-nav-scroll {
        display: flex;
        gap: 8px;
        align-items: center;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }
    .sp-av-order-nav-label {
        flex: 0 0 auto;
    }
    .sp-av-order-nav-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        min-width: 40px;
        height: 40px;
        padding: 0 10px;
        border-radius: 50px;
        font-weight: 800;
        font-size: .9rem;
        text-decoration: none;
        color: #fff !important;
        border: 2px solid transparent;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, background-color .2s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .10);
    }
    .sp-av-order-nav-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, .15);
    }
    .sp-av-order-nav-pill.is-available {
        background-color: var(--theme-primary);
    }
    .sp-av-order-nav-pill.is-accepted {
        background-color: #f59e0b;
    }
    .sp-av-order-nav-pill.active {
        border-color: #0f172a;
        box-shadow: 0 0 0 3px rgba(15, 23, 42, .16), 0 6px 14px rgba(15, 23, 42, .18);
        transform: translateY(-2px) scale(1.04);
    }
    .sp-av-legend {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .78rem;
        color: #64748b;
        white-space: nowrap;
    }
    .sp-av-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        display: inline-block;
    }
    .sp-av-legend-dot.available {
        background: var(--theme-primary);
    }
    .sp-av-legend-dot.accepted {
        background: #f59e0b;
    }
    .sp-av-mobile-tabs {
        display: none;
    }
    .sp-av-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        color: #64748b;
        background: #f8fafc;
        padding: 18px;
        text-align: center;
    }
    .sp-av-delivery-btn {
        min-height: 40px;
        font-weight: 800;
        border-radius: 8px;
    }
    @keyframes sp-av-card-move {
        0% { opacity: .55; transform: translateY(8px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes sp-av-card-new {
        0% { box-shadow: 0 0 0 0 rgba(13, 122, 112, .35); }
        70% { box-shadow: 0 0 0 8px rgba(13, 122, 112, 0); }
        100% { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); }
    }
    @media (max-width: 767px) {
        .sp-av-order-nav-area {
            top: 64px;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .sp-av-mobile-tabs {
            display: flex;
            gap: 8px;
            position: sticky;
            top: 128px;
            z-index: 90;
            background: #fff;
            padding: 8px 0;
        }
        .sp-av-mobile-tab {
            flex: 1 1 0;
            min-height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #fff;
            color: #334155;
            font-weight: 800;
            font-size: .84rem;
        }
        .sp-av-mobile-tab.active {
            background: var(--theme-primary);
            border-color: var(--theme-primary);
            color: #fff;
        }
        .sp-av-mobile-pane:not(.active) {
            display: none;
        }
        .sp-av-delivery-btn {
            width: 100%;
            justify-content: center;
        }
    }
    @media (max-width: 575px) {
        .sp-av-table-head,
        .sp-av-table-row {
            grid-template-columns: minmax(0, 1.3fr) 40px 58px 74px 86px;
            gap: 6px;
        }
    }
</style>
@endpush

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                 <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">                
            </div>
            <div class="col-md-10 d-flex gap-2 justify-content-md-start justify-content-end">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
                 
                <div class="sp-av-quick-wrap ml-4">
                    @foreach($quickDates as $quickDate)
                        @if($quickDate['available'])
                            <a href="{{ route('shipper.available', ['date' => $quickDate['date']]) }}"
                            class="sp-av-quick-pill {{ $quickDate['active'] ? 'active' : '' }}">
                                {{ $quickDate['label'] }}
                                <span class="sp-av-quick-count">{{ $quickDate['count'] }}</span>
                            </a>
                        @else
                            <span class="sp-av-quick-pill disabled">
                                {{ $quickDate['label'] }}
                                <span class="sp-av-quick-count">0</span>
                            </span>
                        @endif
                    @endforeach
                </div> 
            </div>
        </form>

         
    </div>
</div>

@php
    $readyStatus = \App\Models\Order::STATUS_READY_TO_SHIP;
    $acceptedStatus = \App\Models\Order::STATUS_DELIVERING;
    $availableOrders = $orders
        ->where('status', $readyStatus)
        ->sortBy('daily_sequence')
        ->values();
    $acceptedOrders = $orders
        ->where('status', $acceptedStatus)
        ->sortBy('daily_sequence')
        ->values();
    $orderGroups = [
        [
            'key' => 'available',
            'title' => 'Đơn có thể nhận',
            'icon' => 'bi-box-seam',
            'orders' => $availableOrders,
            'badgeClass' => 'bg-primary',
        ],
        [
            'key' => 'accepted',
            'title' => 'Đơn đã nhận',
            'icon' => 'bi-check-circle',
            'orders' => $acceptedOrders,
            'badgeClass' => 'bg-warning text-dark',
        ],
    ];
@endphp

<div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-dark sp-av-summary-pill">Tổng đơn: <span data-total-count>{{ $orders->count() }}</span></span>
        <span class="badge bg-primary sp-av-summary-pill">Có thể nhận: <span data-available-count>{{ $availableOrders->count() }}</span></span>
        <span class="badge bg-warning text-dark sp-av-summary-pill">Đã nhận: <span data-accepted-count>{{ $acceptedOrders->count() }}</span></span>
    </div>
    <span class="badge bg-info sp-av-summary-pill">{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
</div>

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5" data-page-empty-state>
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Không có đơn sẵn sàng giao trong ngày {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}.</p>
    </div>
@endif
    <div class="sp-av-order-nav-area mb-4 {{ $orders->isEmpty() ? 'd-none' : '' }}" id="shipper-order-nav">
        <div class="sp-av-order-nav-scroll">
            <span class="fw-bold text-muted me-1 sp-av-order-nav-label"><i class="bi bi-list-ol me-1"></i>Điều hướng nhanh:</span>
            @foreach($orders->sortBy('daily_sequence') as $navOrder)
                @php
                    $isAcceptedNav = (string) $navOrder->status === $acceptedStatus;
                    $sequenceNumber = $navOrder->daily_sequence ?? $loop->iteration;
                @endphp
                <a href="javascript:void(0);"
                   class="sp-av-order-nav-pill {{ $isAcceptedNav ? 'is-accepted' : 'is-available' }}"
                   data-order-nav="{{ $navOrder->id }}"
                   data-order-group="{{ $isAcceptedNav ? 'accepted' : 'available' }}"
                   title="{{ $navOrder->customer?->name ?? 'Đơn hàng' }}">
                    {{ $sequenceNumber }}
                </a>
            @endforeach
            <span class="sp-av-legend ms-2"><span class="sp-av-legend-dot available"></span>Xanh = Chưa nhận</span>
            <span class="sp-av-legend"><span class="sp-av-legend-dot accepted"></span>Cam vàng đậm = Đã nhận</span>
        </div>
    </div>

    <div class="sp-av-mobile-tabs mb-3 {{ $orders->isEmpty() ? 'd-none' : '' }}" role="tablist" aria-label="Nhóm đơn hàng" data-mobile-tabs-wrap>
        <button class="sp-av-mobile-tab active" type="button" data-mobile-tab="available">
            Đơn có thể nhận <span data-available-count>{{ $availableOrders->count() }}</span>
        </button>
        <button class="sp-av-mobile-tab" type="button" data-mobile-tab="accepted">
            Đơn đã nhận <span data-accepted-count>{{ $acceptedOrders->count() }}</span>
        </button>
    </div>

    <div class="row g-4 {{ $orders->isEmpty() ? 'd-none' : '' }}" data-order-board>
        @foreach($orderGroups as $group)
            <div class="col-12 col-md-6 sp-av-mobile-pane {{ $group['key'] === 'available' ? 'active' : '' }}"
                 data-order-pane="{{ $group['key'] }}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold {{ $group['key'] === 'accepted' ? 'text-warning' : '' }}" style="{{ $group['key'] === 'available' ? 'color:var(--theme-primary);' : '' }}">
                        <i class="bi {{ $group['icon'] }} me-2"></i>{{ $group['title'] }}
                    </h5>
                    <span class="badge {{ $group['badgeClass'] }} rounded-pill">
                        <span data-{{ $group['key'] }}-count>{{ $group['orders']->count() }}</span> đơn
                    </span>
                </div>

                <div class="d-flex flex-column gap-3" data-order-list="{{ $group['key'] }}">
                    <div class="sp-av-empty {{ $group['orders']->isEmpty() ? '' : 'd-none' }}" data-empty-state="{{ $group['key'] }}">
                        Chưa có đơn trong nhóm này.
                    </div>

                    @foreach($group['orders'] as $order)
                        @php
                            $isAccepted = (string) $order->status === $acceptedStatus;
                            $canAcceptToday = !$isAccepted && (
                                \Illuminate\Support\Carbon::parse($selectedDate)->isToday()
                                || (bool) $order->skip_auto_cancel
                            );
                            $recipientName = $order->recipient_name ?: ($order->customer?->name ?? '—');
                            $deliveryAddress = $order->recipient_address ?: ($order->customer?->address ?? null);
                            $customerDeliveryTime = $order->delivery_time ?: $order->customer?->delivery_time;
                            $sourceWarehouseName = $order->resolved_pickup_warehouse_name ?: $order->warehouse?->name;

                            if (!$sourceWarehouseName) {
                                $packingHistory = $order->histories
                                    ->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
                                    ->sortByDesc('id')
                                    ->first();
                                $sourceWarehouseName = $packingHistory?->user?->warehouse?->name;
                            }

                            $itemsSubtotal = (float) $order->items->sum(function ($item) {
                                return (float) $item->price * (int) $item->quantity;
                            });
                            $shippingFee = (float) ($order->shipping_fee ?? 0);
                            $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
                            $codAmount = (float) ($order->total ?? ($itemsSubtotal + $shippingFee + $foamBoxFee));
                            $isReturnOrder = (bool) ($order->is_return_order ?? false)
                                || (string) ($order->order_type ?? '') === 'order_return'
                                || (string) ($order->workflow_code ?? '') === 'order_return';
                        @endphp

                        <div class="card order-card bg-white js-shipper-order-card {{ $isReturnOrder ? 'border border-danger border-2' : '' }}"
                             id="order-card-{{ $order->id }}"
                             data-order-id="{{ $order->id }}"
                             data-order-code="{{ $order->code }}"
                             data-delivery-url="{{ $isReturnOrder ? route('shipper.return-form', $order) : route('shipper.delivered-form', $order) }}"
                             data-is-return-order="{{ $isReturnOrder ? '1' : '0' }}"
                             data-order-group="{{ $isAccepted ? 'accepted' : 'available' }}"
                             data-order-sequence="{{ $order->daily_sequence ?? $loop->iteration }}">
                            <div class="d-flex">
                                <div class="time-block p-3 {{ $isAccepted ? 'is-accepted' : 'is-available' }}">
                                    <h2 class="fw-bold mb-0 text-dark" style="font-size: 2.2rem;">{{ $order->daily_sequence }}</h2>
                                    <div class="order-time">{{ $customerDeliveryTime ?: '—' }}</div>
                                </div>

                                <div class="p-3 flex-grow-1 d-flex flex-column justify-content-between pb-0">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-bold text-dark"><i class="bi bi-person text-muted me-1"></i>{{ $recipientName }}</div>
                                            @if($isReturnOrder)
                                                <div class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1">
                                                    <i class="bi bi-arrow-return-left me-1"></i>Đơn hoàn trả
                                                </div>
                                            @endif
                                            <div class="text-muted mt-1"><i class="bi bi-geo-alt text-muted me-1"></i>{{ $deliveryAddress ?: 'Chưa có địa chỉ' }}</div>
                                            <div class="text-muted"><i class="bi bi-box-seam me-1"></i>Từ kho: {{ $sourceWarehouseName ?: 'Chưa xác định' }}</div>
                                            <div class="text-muted mt-1"># {{ $order->code }}, {{ $order->created_at->format('d/m/Y H:i') }}</div>
                                        </div>

                                        <div class="text-end">
                                            <div class="fw-bold text-dark fs-5">{{ number_format($codAmount, 0, ',', '.') }}đ</div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center gap-2 py-2 mt-2 border-0 flex-wrap">
                                        <div data-accept-action>
                                            @if($isAccepted)
                                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                                    <span class="badge rounded-pill bg-warning text-dark border px-2 py-1" style="font-size: 0.72rem;">
                                                        <i class="bi bi-check2-circle me-1"></i>Đã nhận
                                                    </span>
                                                    <a href="{{ $isReturnOrder ? route('shipper.return-form', $order) : route('shipper.delivered-form', $order) }}" class="btn {{ $isReturnOrder ? 'btn-outline-danger' : 'btn-success' }} btn-sm sp-av-delivery-btn d-inline-flex align-items-center gap-1">
                                                        <i class="bi {{ $isReturnOrder ? 'bi-arrow-return-left' : 'bi-truck' }}"></i> {{ $isReturnOrder ? 'Nhập kho trả' : 'Giao hàng' }}
                                                    </a>
                                                </div>
                                            @elseif($canAcceptToday && ($order->updated_at->isToday() || $order->created_at->isToday() || (bool) $order->skip_auto_cancel))
                                                <form action="{{ route('shipper.accept', $order) }}" method="POST" class="js-shipper-accept-form">
                                                    @csrf
                                                    <button class="btn btn-teal shadow-sm d-inline-flex align-items-center gap-1" type="submit">
                                                        <i class="bi bi-hand-index-thumb"></i> Nhận đơn này
                                                    </button>
                                                </form>
                                            @else
                                                <button class="btn btn-outline-secondary" disabled>
                                                    <i class="bi bi-calendar-x me-1"></i>Chỉ nhận đơn có ngày hôm nay
                                                </button>
                                            @endif
                                        </div>

                                        <span class="badge rounded-pill {{ $isAccepted ? 'bg-warning text-dark' : 'bg-secondary bg-opacity-10 text-secondary' }} border px-2 py-1 js-order-state-badge" style="font-size: 0.72rem;">
                                            <i class="bi {{ $isAccepted ? 'bi-check2-circle' : 'bi-clock' }} me-1"></i>{{ $isAccepted ? 'Đã nhận' : 'Đang chờ' }}
                                        </span>
                                        <a href="#collapseOrder{{ $order->id }}" class="toggle-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapseOrder{{ $order->id }}">
                                            + Xem chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="collapse" id="collapseOrder{{ $order->id }}">
                                <div class="card-body pt-0 px-3 pb-3 border-top">
                                    <div class="sp-av-section">
                                        <div class="sp-av-table-head">
                                            <div>Sản phẩm</div>
                                            <div class="text-end">SL</div>
                                            <div class="text-end">Size</div>
                                            <div class="text-end">Kg</div>
                                            <div class="text-end">Đơn giá</div>
                                            <div class="text-end">Thành tiền</div>
                                        </div>
                                        <ul class="sp-av-item-list">
                                            @foreach($order->items as $item)
                                                @php
                                                    $qty = (int) $item->quantity;
                                                    $unitPrice = (float) ($item->price ?? 0);
                                                    $lineTotal = $qty * $unitPrice;
                                                    $variantSize = $item->variant?->size;
                                                    $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                                        ? rtrim(rtrim(number_format((float) $variantSize, 2, '.', ''), '0'), '.')
                                                        : '-';
                                                    $itemActualWeight = $item->actual_weight
                                                        ? rtrim(rtrim(number_format((float) $item->actual_weight, 2, '.', ''), '0'), '.')
                                                        : '-';
                                                @endphp
                                                <li class="sp-av-item-row">
                                                    <div class="sp-av-table-row">
                                                        <div class="sp-av-item-name">
                                                            {{ $item->variant?->name ?? $item->variant?->sku ?? 'Sản phẩm' }}
                                                            @if($item->variant?->sku)
                                                                <span class="text-muted small">({{ $item->variant->sku }})</span>
                                                            @endif
                                                        </div>
                                                        <div class="sp-av-item-cell"><strong>{{ $qty }}</strong></div>
                                                        <div class="sp-av-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                                        <div class="sp-av-item-cell"><strong>{{ $itemActualWeight }}</strong></div>
                                                        <div class="sp-av-item-cell">{{ number_format($unitPrice) }}đ</div>
                                                        <div class="sp-av-item-cell"><strong>{{ number_format($lineTotal) }}đ</strong></div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <div class="sp-av-section">
                                        <div class="sp-av-summary-row">
                                            <span>Tiền hàng</span>
                                            <strong>{{ number_format($itemsSubtotal) }}đ</strong>
                                        </div>
                                        <div class="sp-av-summary-row">
                                            <span>Phí ship</span>
                                            <strong>{{ number_format($shippingFee) }}đ</strong>
                                        </div>
                                        <div class="sp-av-summary-row">
                                            <span>Thùng xốp</span>
                                            <strong>{{ number_format($foamBoxFee) }}đ</strong>
                                        </div>
                                        <div class="sp-av-summary-row total">
                                            <span>COD cần thu</span>
                                            <span class="text-success">{{ number_format($codAmount) }}đ</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nav = document.getElementById('shipper-order-nav');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function setMobileTab(group) {
        document.querySelectorAll('[data-mobile-tab]').forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.mobileTab === group);
        });
        document.querySelectorAll('[data-order-pane]').forEach((pane) => {
            pane.classList.toggle('active', pane.dataset.orderPane === group);
        });
    }

    function stickyOffset() {
        const navHeight = nav ? nav.getBoundingClientRect().height : 0;
        return navHeight + (window.innerWidth < 768 ? 92 : 92);
    }

    function setActiveOrder(orderId) {
        document.querySelectorAll('[data-order-nav]').forEach((pill) => {
            pill.classList.toggle('active', pill.dataset.orderNav === String(orderId));
        });
        const activePill = document.querySelector(`[data-order-nav="${orderId}"]`);
        activePill?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }

    function highlightCard(card) {
        card.classList.add('is-highlighted');
        window.setTimeout(() => card.classList.remove('is-highlighted'), 1800);
    }

    function scrollToOrder(orderId) {
        const card = document.getElementById(`order-card-${orderId}`);
        if (!card) return;

        const group = card.dataset.orderGroup || 'available';
        if (window.innerWidth < 768) {
            setMobileTab(group);
        }

        window.setTimeout(() => {
            const top = card.getBoundingClientRect().top + window.scrollY - stickyOffset();
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
            setActiveOrder(orderId);
            highlightCard(card);
        }, 80);
    }

    function updateCounts() {
        const availableCount = document.querySelectorAll('.js-shipper-order-card[data-order-group="available"]').length;
        const acceptedCount = document.querySelectorAll('.js-shipper-order-card[data-order-group="accepted"]').length;
        const totalCount = availableCount + acceptedCount;

        document.querySelectorAll('[data-available-count]').forEach((el) => el.textContent = availableCount);
        document.querySelectorAll('[data-accepted-count]').forEach((el) => el.textContent = acceptedCount);
        document.querySelectorAll('[data-total-count]').forEach((el) => el.textContent = totalCount);
        document.querySelector('[data-page-empty-state]')?.classList.toggle('d-none', totalCount > 0);
        document.getElementById('shipper-order-nav')?.classList.toggle('d-none', totalCount === 0);
        document.querySelector('[data-mobile-tabs-wrap]')?.classList.toggle('d-none', totalCount === 0);
        document.querySelector('[data-order-board]')?.classList.toggle('d-none', totalCount === 0);

        document.querySelectorAll('[data-empty-state]').forEach((empty) => {
            const list = document.querySelector(`[data-order-list="${empty.dataset.emptyState}"]`);
            const count = list ? list.querySelectorAll('.js-shipper-order-card').length : 0;
            empty.classList.toggle('d-none', count > 0);
        });
    }

    function sortList(group) {
        const list = document.querySelector(`[data-order-list="${group}"]`);
        if (!list) return;

        Array.from(list.querySelectorAll('.js-shipper-order-card'))
            .sort((a, b) => Number(a.dataset.orderSequence || 0) - Number(b.dataset.orderSequence || 0))
            .forEach((card) => list.appendChild(card));
    }

    function markCardAccepted(card, shouldFocus = true) {
        card.dataset.orderGroup = 'accepted';
        card.classList.add('is-moving');

        const timeBlock = card.querySelector('.time-block');
        timeBlock?.classList.remove('is-available');
        timeBlock?.classList.add('is-accepted');

        const action = card.querySelector('[data-accept-action]');
        if (action) {
            const deliveryUrl = card.dataset.deliveryUrl || '#';
            const isReturnOrder = card.dataset.isReturnOrder === '1';
            action.innerHTML = `<div class="d-flex gap-2 flex-wrap align-items-center">
                <span class="badge rounded-pill bg-warning text-dark border px-2 py-1" style="font-size: 0.72rem;"><i class="bi bi-check2-circle me-1"></i>Đã nhận</span>
                <a href="${deliveryUrl}" class="btn ${isReturnOrder ? 'btn-outline-danger' : 'btn-success'} btn-sm sp-av-delivery-btn d-inline-flex align-items-center gap-1">
                    <i class="bi ${isReturnOrder ? 'bi-arrow-return-left' : 'bi-truck'}"></i> ${isReturnOrder ? 'Nhập kho trả' : 'Giao hàng'}
                </a>
            </div>`;
        }

        const stateBadge = card.querySelector('.js-order-state-badge');
        if (stateBadge) {
            stateBadge.className = 'badge rounded-pill bg-warning text-dark border px-2 py-1 js-order-state-badge';
            stateBadge.style.fontSize = '0.72rem';
            stateBadge.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Đã nhận';
        }

        const acceptedList = document.querySelector('[data-order-list="accepted"]');
        acceptedList?.appendChild(card);
        sortList('accepted');

        const navPill = document.querySelector(`[data-order-nav="${card.dataset.orderId}"]`);
        if (navPill) {
            navPill.dataset.orderGroup = 'accepted';
            navPill.classList.remove('is-available');
            navPill.classList.add('is-accepted');
        }

        updateCounts();
        window.setTimeout(() => card.classList.remove('is-moving'), 500);
        if (shouldFocus) {
            setMobileTab('accepted');
            scrollToOrder(card.dataset.orderId);
        }
    }

    document.addEventListener('click', function (event) {
        const tab = event.target.closest('[data-mobile-tab]');
        if (tab) {
            setMobileTab(tab.dataset.mobileTab);
            return;
        }

        const pill = event.target.closest('[data-order-nav]');
        if (pill) {
            scrollToOrder(pill.dataset.orderNav);
        }
    });

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('.js-shipper-accept-form');
        if (!form) return;

        event.preventDefault();

        const card = form.closest('.js-shipper-order-card');
        if (!card || !window.confirm(`Xác nhận nhận đơn #${card.dataset.orderCode || card.dataset.orderSequence}?`)) {
            return;
        }

        const button = form.querySelector('button[type="submit"]');
        const originalHtml = button?.innerHTML;
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Đang nhận';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || 'Không thể nhận đơn. Vui lòng thử lại.');
            }

            markCardAccepted(card);
        } catch (error) {
            alert(error.message);
            if (button) {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        }
    });

    function syncFromFreshDocument(doc) {
        const freshCards = Array.from(doc.querySelectorAll('.js-shipper-order-card'));
        const freshIds = new Set(freshCards.map((card) => card.dataset.orderId));
        const currentActive = document.querySelector('[data-order-nav].active')?.dataset.orderNav;

        freshCards.forEach((freshCard) => {
            const orderId = freshCard.dataset.orderId;
            const currentCard = document.getElementById(`order-card-${orderId}`);
            const targetGroup = freshCard.dataset.orderGroup || 'available';
            const targetList = document.querySelector(`[data-order-list="${targetGroup}"]`);

            if (!targetList) return;

            if (!currentCard) {
                const importedCard = document.importNode(freshCard, true);
                importedCard.classList.add('is-new');
                targetList.appendChild(importedCard);
                window.setTimeout(() => importedCard.classList.remove('is-new'), 800);
            } else if ((currentCard.dataset.orderGroup || 'available') !== targetGroup) {
                if (targetGroup === 'accepted') {
                    markCardAccepted(currentCard, false);
                } else {
                    currentCard.replaceWith(document.importNode(freshCard, true));
                }
            }

            sortList(targetGroup);
        });

        document.querySelectorAll('.js-shipper-order-card').forEach((card) => {
            if (!freshIds.has(card.dataset.orderId)) {
                card.remove();
            }
        });

        const freshPills = Array.from(doc.querySelectorAll('[data-order-nav]'));
        const freshPillIds = new Set(freshPills.map((pill) => pill.dataset.orderNav));
        const navScroll = document.querySelector('.sp-av-order-nav-scroll');

        freshPills.forEach((freshPill) => {
            const currentPill = document.querySelector(`[data-order-nav="${freshPill.dataset.orderNav}"]`);
            if (!currentPill && navScroll) {
                const firstLegend = navScroll.querySelector('.sp-av-legend');
                navScroll.insertBefore(document.importNode(freshPill, true), firstLegend || null);
            } else if (currentPill) {
                currentPill.dataset.orderGroup = freshPill.dataset.orderGroup;
                currentPill.className = freshPill.className;
                if (currentActive === currentPill.dataset.orderNav) {
                    currentPill.classList.add('active');
                }
            }
        });

        document.querySelectorAll('[data-order-nav]').forEach((pill) => {
            if (!freshPillIds.has(pill.dataset.orderNav)) {
                pill.remove();
            }
        });

        updateCounts();
    }

    async function refreshOrderData() {
        if (document.hidden) return;

        try {
            const response = await fetch(window.location.href, {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });

            if (!response.ok) return;

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            syncFromFreshDocument(doc);
        } catch (error) {
            // Giữ yên giao diện hiện tại nếu đồng bộ nền tạm thời lỗi.
        }
    }

    updateCounts();
    window.setInterval(refreshOrderData, 30000);
});
</script>
@endpush
