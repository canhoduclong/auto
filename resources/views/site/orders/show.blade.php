@extends('layouts.site')

@php
    $orderCode = $order->code ?: ('#' . $order->id);

    $statusClass = match((string) $order->status) {
        'completed', 'delivered' => 'success',
        'shipping', 'packed', 'packing' => 'info',
        'cancelled', 'rejected' => 'danger',
        default => 'warning',
    };

    $paymentClass = match((string) $order->payment_status) {
        'paid' => 'success',
        'partial' => 'warning',
        default => 'danger',
    };

    $deliveryClass = match((string) $order->delivery_status) {
        'delivered' => 'success',
        'shipping', 'shipped' => 'info',
        default => 'secondary',
    };

    $createdAt = optional($order->created_at)->format('d/m/Y H:i');
    $itemCount = (int) $order->items->sum('quantity');
    $lineCount = (int) $order->items->count();
    $totalQty = (float) $order->items->sum('quantity');
    $orderSubtotalAmount = (float) ($order->subtotal_amount ?? $order->items->sum(function ($item) {
        return (float) (($item->base_price ?? $item->price ?? 0) * ($item->quantity ?? 0));
    }));
    $orderItemDiscount = (float) ($order->item_discount_total ?? $order->items->sum('discount_total'));
    $orderExtraDiscount = (float) ($order->extra_discount_total ?? $order->order_discount ?? 0);
    $orderTotalDiscount = (float) ($order->total_discount ?? ($orderItemDiscount + $orderExtraDiscount));
    $orderTotalWeight = (float) $order->items->sum(function ($item) {
        if (!($item->effective_priced_by_kg ?? false)) {
            return 0;
        }

        return (float) ($item->display_total_value ?? 0);
    });
    $formatSignedMoney = static function (float $amount): string {
        $prefix = $amount < 0 ? '+' : '-';

        return $prefix . number_format(abs($amount), 0, ',', '.') . 'đ';
    };
    $isCopiedOrder = !empty($order->copied_from_order_id);
    $canEdit = $isCopiedOrder
        || ($order->status === \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL
            && $order->created_at?->isToday());

    $statusText = \App\Models\Order::statusOptions()[$order->status] ?? ucfirst(str_replace('_', ' ', (string) $order->status));
    $paymentStatusText = match((string) $order->payment_status) {
        'paid' => 'Đã thanh toán',
        'partial' => 'Thanh toán một phần',
        'unpaid' => 'Chưa thanh toán',
        default => ucfirst(str_replace('_', ' ', (string) ($order->payment_status ?: 'unknown'))),
    };
    $deliveryStatusText = match((string) $order->delivery_status) {
        'delivered' => 'Đã giao',
        'shipping', 'shipped' => 'Đang giao',
        'pending' => 'Chờ giao',
        default => ucfirst(str_replace('_', ' ', (string) ($order->delivery_status ?: 'pending'))),
    };

    $recipientName = $order->recipient_name ?: ($order->customer?->name ?: 'Chưa cập nhật');
    $recipientPhone = $order->recipient_phone ?: ($order->customer?->phone ?: 'Chưa cập nhật');
    $deliveryTime = $order->delivery_time ?: ($order->customer?->delivery_time ?: 'Chưa cập nhật');
    $defaultAddress = $order->customer?->addresses?->firstWhere('is_default', 1) ?? $order->customer?->addresses?->first();
    $deliveryAddress = $order->recipient_address
        ?: ($defaultAddress?->note ?: ($order->customer?->address ?: 'Chưa cập nhật'));
    $wardLine = $defaultAddress?->ward;
    $cityLine = $defaultAddress?->city;
    $hasInvoiceInfo = filled($order->customer?->company_name)
        || filled($order->customer?->tax_code)
        || filled($order->customer?->company_address);
    $showTruckStation = (bool) ($order->customer?->use_truck_station ?? false);
    $station = $order->customer?->truckStation;
    $deliveryCollapseId = 'delivery-info-' . $order->id;
    $invoiceCollapseId = 'invoice-info-' . $order->id;
    $truckCollapseId = 'truck-info-' . $order->id;
@endphp

@push('styles')
<style>
    .order-detail-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }

    .order-shell {
        max-width: 1180px;
        margin: 0 auto;
    }

    .order-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 28px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(21, 34, 56, 0.18);
        overflow: hidden;
        position: relative;
        margin-bottom: 20px;
    }

    .order-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        top: -60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .order-hero h1 {
        margin: 12px 0;
        font-size: 2rem;
        font-weight: 900;
    }

    .order-hero p {
        margin: 0;
        color: rgba(255, 255, 255, 0.8);
        font-size: .95rem;
    }

    .order-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: rgba(255,255,255,.68);
    }

    .order-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .order-kpi {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 18px;
        min-height: 100%;
        backdrop-filter: blur(6px);
    }

    .order-kpi-label {
        font-size: .78rem;
        color: rgba(255, 255, 255, 0.68);
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 8px;
    }

    .order-kpi-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: #fff;
        line-height: 1.05;
    }

    .order-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }

    .order-side-panel {
        position: sticky;
        top: 84px;
    }

    .order-panel-body {
        padding: 22px;
    }

    .order-title {
        margin: 0 0 6px;
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }

    .order-subtitle {
        margin: 0;
        color: #64748b;
        font-size: .84rem;
    }

    .order-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 14px;
    }

    .order-meta-item { 
        padding: 7px; 
        background: #f8fafc;
    }

    .order-meta-label {
        display: block;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
        margin-bottom: 6px;
    }

    .order-meta-value {
        font-size: .94rem;
        font-weight: 700;
        color: #0f172a;
    }

    .order-status-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 14px;
    }

    .order-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
    }

    .status-success { background: #ecfdf5; color: #047857; }
    .status-warning { background: #fff7ed; color: #c2410c; }
    .status-info { background: #eff6ff; color: #1d4ed8; }
    .status-danger { background: #fef2f2; color: #b91c1c; }
    .status-secondary { background: #f1f5f9; color: #475569; }

    .order-stack {
        display: grid;
        gap: 16px;
    }

    .order-info-list {
        display: grid;
        gap: 12px;
        margin-top: 16px;
    }

    .order-info-card {
        border: 1px solid #e5eaf3;
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
    }

    .order-info-head {
        padding: 14px 16px 10px;
        border-bottom: 1px solid #eef2f7;
        font-size: .8rem;
        font-weight: 800;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .order-info-body {
        padding: 14px 16px;
    }

    .order-info-row {
        display: flex;
        gap: 10px;
        margin-bottom: 8px;
        color: #475569;
        font-size: .9rem;
    }

    .order-info-row:last-child {
        margin-bottom: 0;
    }

    .order-info-key {
        width: 112px;
        flex: 0 0 112px;
        color: #64748b;
    }

    .order-items-wrap {
        margin-top: 18px;
        overflow-x: auto;
    }

    .order-items-head,
    .order-items-row {
        display: grid;
        grid-template-columns: minmax(240px, 2fr) 84px 120px 90px 120px 130px;
        gap: 10px;
        align-items: center;
    }

    .order-items-head {
        border: 1px solid #dbe4ef;
        border-radius: 12px;
        background: #eef3f9;
        padding: 10px 12px;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #475569;
        font-weight: 700;
    }

    .order-items-list {
        list-style: none;
        margin: 10px 0 0;
        padding: 0;
        display: grid;
        gap: 8px;
    }

    .order-items-row {
        border: 1px solid #e5eaf3;
        border-radius: 12px;
        padding: 10px 12px;
        background: #f8fafc;
        font-size: .84rem;
    }

    .order-item-product {
        display: flex;
        gap: 12px;
        align-items: center;
        min-width: 0;
    }

    .order-item-thumb {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #fff;
        flex: 0 0 48px;
    }

    .order-item-thumb-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        border: 1px dashed #cbd5e1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        background: #fff;
        flex: 0 0 48px;
    }

    .order-item-name {
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
    }

    .order-item-sub {
        color: #64748b;
        font-size: .78rem;
        margin-top: 2px;
    }

    .order-item-cell {
        text-align: right;
        color: #334155;
        white-space: nowrap;
        font-weight: 600;
    }

    .order-item-total {
        color: #0f172a;
        font-weight: 800;
    }

    .order-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
        margin-top: 18px;
    }

    .order-actions .btn {
        border-radius: 12px;
        font-weight: 700;
        padding: 10px 16px;
    }

    .mc-customer-card {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: box-shadow 0.2s ease;
    }

    .mc-customer-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    }

    .orders-code {
        font-weight: 800;
        color: #1e293b;
    }

    .wh-order-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding-bottom: 7px;
        border-bottom: 1px solid #eef2f7;
    }

    .wh-meta-label {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .wh-meta-value {
        font-size: .92rem;
        font-weight: 700;
        color: #0f172a;
    }

    .wh-section {
        padding: 12px 0;
        border-top: 1px dashed #e2e8f0;
    }

    .wh-logistics-title,
    .logistics-title,
    .customer-tax-title,
    .transport-title {
        font-size: .78rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .customer-collapse-toggle {
        min-height: 40px;
        border-radius: 12px;
        font-weight: 700;
        border: 1px solid #dbe4ef;
        background: #fff;
        color: #334155;
        padding: 8px 12px;
        text-decoration: none;
    }

    .customer-collapse-action {
        font-size: .78rem;
        font-weight: 700;
        color: #1d4ed8;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .customer-tax-body,
    .logistics-body,
    .transport-body {
        padding: 0 0 0 14px;
    }

    .row-title {
        width: 96px;
        flex: 0 0 96px;
        color: #64748b;
        padding-right: 5px;
    }

    .wh-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }

    .wh-item-table-wrap {
        overflow-x: auto;
    }

    .wh-item-table-head,
    .wh-item-table-row {
        display: grid;
        grid-template-columns: 48px minmax(180px, 1.4fr) 54px 55px 84px 84px 98px;
        gap: 8px;
        align-items: center;
    }

    .wh-item-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 6px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }

    .wh-item-row {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }

    .wh-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .wh-item-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #fff;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    .wh-item-thumb-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        margin-left: auto;
        margin-right: auto;
    }

    .wh-item-name {
        font-size: .86rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
    }

    .wh-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: center;
    }

    .wh-item-cell strong {
        color: #0f172a;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
    }

    .orders-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .orders-actions .btn {
        border-radius: 12px;
        font-weight: 700;
        padding: 9px 14px;
    }

    @media (max-width: 991.98px) {
        .order-hero {
            padding: 22px;
            border-radius: 24px;
        }
        .order-side-panel {
            position: static;
        }
        .order-kpis,
        .order-meta-grid {
            grid-template-columns: 1fr;
        }
        .wh-order-head {
            flex-direction: column;
        }
    }

    @media (max-width: 767.98px) {
        .order-detail-page {
            padding: 20px 0 48px;
        }
        .order-shell {
            padding: 0 12px;
        }
        .order-hero {
            padding: 18px;
        }
        .order-kpi-value {
            font-size: 1.25rem;
        }
        .order-items-head,
        .order-items-row {
            grid-template-columns: 1fr;
        }
        .wh-item-table-head,
        .wh-item-table-row {
            grid-template-columns: 1fr;
            gap: 4px;
        }
        .order-item-cell {
            text-align: left;
        }
        .order-info-row {
            flex-direction: column;
            gap: 2px;
        }
        .order-info-key {
            width: auto;
            flex-basis: auto;
        }
    }
</style>
@endpush

@section('content')
<section class="order-detail-page">
    <div class="container order-shell">
        <div class="order-hero">
            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-5">
                    <div class="order-eyebrow"><i class="bi bi-receipt"></i> Order Center</div>
                    <h1>Đơn {{ $orderCode }}</h1>
                    <p>Theo dõi chi tiết đơn hàng, thông tin khách mua, xuất hóa đơn và nhà xe trên cùng một màn hình hai cột.</p>
                </div>
                <div class="col-lg-7">
                    <div class="order-kpis">
                        <div class="order-kpi">
                            <div class="order-kpi-label">Ngày tạo</div>
                            <div class="order-kpi-value">{{ $createdAt ?: '-' }}</div>
                        </div>
                        <div class="order-kpi">
                            <div class="order-kpi-label">Số dòng sản phẩm</div>
                            <div class="order-kpi-value">{{ number_format($lineCount, 0, ',', '.') }}</div>
                        </div>
                        <div class="order-kpi">
                            <div class="order-kpi-label">Tổng thanh toán</div>
                            <div class="order-kpi-value">{{ number_format((float) $order->total, 0, ',', '.') }}đ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="order-panel order-side-panel">
                    <div class="order-panel-body">
                        <h2 class="order-title">Khách hàng có đơn</h2>
                        <p class="order-subtitle">Thông tin khách mua gắn với đơn hàng hiện tại.</p>

                        <div class="order-meta-grid">
                            <div class="order-meta-item">
                                <span class="order-meta-label">Khách hàng</span>
                                <div class="order-meta-value">{{ $order->customer?->name ?: 'Chưa cập nhật' }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Mã khách hàng</span>
                                <div class="order-meta-value">{{ $order->customer?->customer_code ?: ('#' . ($order->customer?->id ?? '')) }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Người nhận</span>
                                <div class="order-meta-value">{{ $recipientName }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Điện thoại</span>
                                <div class="order-meta-value">{{ $recipientPhone }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Trạng thái</span>
                                <div class="order-meta-value">{{ $statusText }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Thanh toán</span>
                                <div class="order-meta-value">{{ $paymentStatusText }}</div>
                            </div>
                            <div class="order-meta-item" style="grid-column: 1 / -1;">
                                <span class="order-meta-label">Địa chỉ khách</span>
                                <div class="order-meta-value">{{ $order->customer?->address ?: 'Chưa cập nhật' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="mc-customer-card border rounded p-3 bg-white">
                    <div class="wh-order-head">
                        <div>
                            <div class="orders-code">{{ $order->customer?->name ?? '—' }}</div>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i>
                                {{ $createdAt ?: '-' }},
                                Mã KH: {{ $order->customer?->customer_code ?? ('#' . ($order->customer?->id ?? '')) }}
                                @if($order->customer?->phone)
                                    , <i class="bi bi-telephone me-1"></i>{{ $order->customer->phone }}
                                @endif
                            </small>
                            @if($isCopiedOrder)
                                <div><span class="badge bg-warning text-dark mt-2">Đơn copy mới</span></div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="wh-section">
                                <div class="order-meta-grid mt-0">
                                    <div class="order-meta-item">
                                        <div class="wh-meta-label">Số dòng SP</div>
                                        <div class="wh-meta-value">{{ $lineCount }}</div>
                                    </div>
                                    <div class="order-meta-item">
                                        <div class="wh-meta-label">Tổng số lượng</div>
                                        <div class="wh-meta-value">{{ rtrim(rtrim(number_format($totalQty, 3, '.', ''), '0'), '.') }}</div>
                                    </div>
                                    <div class="order-meta-item">
                                        <div class="wh-meta-label">Thành tiền</div>
                                        <div class="wh-meta-value text-primary">{{ number_format((float) $order->total, 0, ',', '.') }}đ</div>
                                    </div>
                                    <div class="order-meta-item">
                                        <div class="wh-meta-label">Tổng điều chỉnh</div>
                                        <div class="wh-meta-value">{{ $formatSignedMoney($orderTotalDiscount) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wh-section border-top-0">
                        <div class="order-status-row">
                            <span class="status-pill status-{{ $statusClass }}">{{ $statusText }}</span>
                            <span class="status-pill status-{{ $paymentClass }}">{{ $paymentStatusText }}</span>
                            <span class="status-pill status-{{ $deliveryClass }}">{{ $deliveryStatusText }}</span>
                        </div>

                        <div class="order-meta-grid">
                            <div class="order-meta-item">
                                <span class="order-meta-label">Mã đơn</span>
                                <div class="order-meta-value">{{ $orderCode }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tiền hàng</span>
                                <div class="order-meta-value">{{ number_format($orderSubtotalAmount, 0, ',', '.') }}đ</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Giảm giá SP</span>
                                <div class="order-meta-value">{{ $formatSignedMoney($orderItemDiscount) }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Điều chỉnh đơn</span>
                                <div class="order-meta-value">{{ $formatSignedMoney($orderExtraDiscount) }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tổng khối lượng</span>
                                <div class="order-meta-value">{{ number_format($orderTotalWeight, 3, ',', '.') }} kg</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tổng thanh toán</span>
                                <div class="order-meta-value">{{ number_format((float) $order->total, 0, ',', '.') }}đ</div>
                            </div>
                        </div>
                    </div>

                    <div class="wh-section border-top-0">
                        <div class="customer-info g-3">
                            <div class="customer-info-logistics mt-2">
                                <a class="w-100 d-flex justify-content-between align-items-center customer-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $deliveryCollapseId }}" aria-expanded="true" aria-controls="{{ $deliveryCollapseId }}">
                                    <span class="logistics-title mb-0">Giao hàng</span>
                                    <span class="customer-collapse-action" data-collapse-label="1">Hide</span>
                                </a>
                                <div id="{{ $deliveryCollapseId }}" class="collapse show logistics-body pt-2">
                                    <div class="small text-muted mb-1">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        Địa chỉ nhận hàng: {{ $deliveryAddress }}
                                    </div>
                                    @if($wardLine || $cityLine)
                                        <div class="small text-muted mb-1">
                                            <i class="bi bi-pin-map me-1"></i>
                                            Khu vực: {{ collect([$wardLine, $cityLine])->filter()->implode(', ') }}
                                        </div>
                                    @endif
                                    <div class="small text-muted mb-1">
                                        <i class="bi bi-clock me-1"></i>
                                        Giờ giao: {{ $deliveryTime }}
                                    </div>
                                    @if($order->note)
                                        <div class="small text-muted mb-1">
                                            <i class="bi bi-sticky me-1"></i>
                                            Ghi chú: {{ $order->note }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($hasInvoiceInfo)
                                <div class="customer-info-tax mt-3 mt-md-2">
                                    <button class="btn btn-sm btn-outline-secondary w-100 d-flex justify-content-between align-items-center customer-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $invoiceCollapseId }}" aria-expanded="false" aria-controls="{{ $invoiceCollapseId }}">
                                        <span class="customer-tax-title mb-0">Thuế / xuất hóa đơn</span>
                                        <span class="customer-collapse-action" data-collapse-label="1">Show</span>
                                    </button>
                                    <div id="{{ $invoiceCollapseId }}" class="collapse customer-tax-body pt-2">
                                        <div class="text-muted small mb-1 d-flex">
                                            <div class="row-title">Tên công ty:</div>
                                            <div class="row-value">{{ $order->customer?->company_name ?: 'Chưa cập nhật' }}</div>
                                        </div>
                                        <div class="text-muted small mb-1 d-flex">
                                            <div class="row-title">Mã số thuế:</div>
                                            <div class="row-value">{{ $order->customer?->tax_code ?: 'Chưa cập nhật' }}</div>
                                        </div>
                                        <div class="text-muted small d-flex">
                                            <div class="row-title">Địa chỉ Cty:</div>
                                            <div class="row-value">{{ $order->customer?->company_address ?: 'Chưa cập nhật' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($showTruckStation)
                                <div class="transport-info mt-3 mt-md-2">
                                    <button class="btn btn-sm btn-outline-secondary w-100 d-flex justify-content-between align-items-center customer-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $truckCollapseId }}" aria-expanded="false" aria-controls="{{ $truckCollapseId }}">
                                        <span class="transport-title mb-0">Thông tin nhà xe</span>
                                        <span class="customer-collapse-action" data-collapse-label="1">Show</span>
                                    </button>
                                    <div id="{{ $truckCollapseId }}" class="collapse transport-body pt-2">
                                        <div class="text-muted small mb-1">Nhà xe: {{ $station?->name ?: 'Chưa chọn nhà xe' }}</div>
                                        @if($station)
                                            <div class="text-muted small mb-1">
                                                Khu vực: {{ collect([$station->ward?->name, $station->province?->name])->filter()->implode(', ') ?: 'Chưa cập nhật' }}
                                            </div>
                                        @endif
                                        <div class="text-muted small mb-1">Địa chỉ gửi: {{ $order->customer?->truck_station_address ?: ($station?->address ?: 'Chưa cập nhật') }}</div>
                                        <div class="small text-muted mb-1">
                                            <i class="bi bi-clock me-1"></i>
                                            Giờ nhận: {{ $order->customer?->truck_receive_time ?: 'Chưa cập nhật' }}
                                        </div>
                                        <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $order->customer?->truck_station_phone ?: ($station?->phone ?: 'Chưa cập nhật') }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="wh-section pb-0">
                        <div class="wh-logistics-title">Danh sách sản phẩm</div>
                        @if(($order->items ?? collect())->isNotEmpty())
                            <div class="wh-item-table-wrap">
                                <div class="wh-item-table-head">
                                    <div>Ảnh</div>
                                    <div>Sản phẩm</div>
                                    <div class="text-center">SL</div>
                                    <div class="text-center">Size</div>
                                    <div class="text-center">Tổng</div>
                                    <div class="text-center">Giá bán</div>
                                    <div class="text-end">Thành tiền</div>
                                </div>
                                <ul class="wh-item-list">
                                    @foreach($order->items as $item)
                                        @php
                                            $variant = $item->variant;
                                            $product = $item->product ?? $variant?->product;
                                            $productName = $product?->name ?? $variant?->name ?? 'Sản phẩm';
                                            $qty = (float) ($item->quantity ?? 0);
                                            $basePrice = (float) ($item->base_price ?? $item->price ?? 0);
                                            $unitDiscount = (float) ($item->unit_discount ?? 0);
                                            $discountType = $item->discount_type === 'increase' ? 'increase' : 'decrease';
                                            $sellingPrice = $discountType === 'increase'
                                                ? ($basePrice + $unitDiscount)
                                                : ($basePrice - $unitDiscount);
                                            $lineTotal = (float) ($item->total ?? 0);
                                            if ($lineTotal <= 0) {
                                                $lineTotal = $sellingPrice * (float) ($item->display_total_value ?? $qty);
                                            }
                                            $variantSize = $variant?->size;
                                            $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                                ? rtrim(rtrim(number_format((float) $variantSize, 2, '.', ''), '0'), '.')
                                                : '-';
                                            $imagePath = $variant?->avatar?->media?->file_path
                                                ?? $product?->avatar?->media?->file_path
                                                ?? null;
                                        @endphp
                                        <li class="wh-item-row">
                                            <div class="wh-item-table-row">
                                                <div>
                                                    @if($imagePath)
                                                        <img class="wh-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $productName }}">
                                                    @else
                                                        <span class="wh-item-thumb-placeholder">
                                                            <i class="bi bi-image"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="wh-item-name">
                                                    {{ $productName }}
                                                    @if($variant?->sku)
                                                        <span class="text-muted small">({{ $variant->sku }})</span>
                                                    @endif
                                                </div>
                                                <div class="wh-item-cell"><strong>{{ rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') }}</strong></div>
                                                <div class="wh-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                                <div class="wh-item-cell"><strong>{{ $item->display_total_label }}</strong></div>
                                                <div class="wh-item-cell">{{ number_format($sellingPrice, 0, ',', '.') }}đ</div>
                                                <div class="wh-item-cell text-end"><strong>{{ number_format($lineTotal, 0, ',', '.') }}đ</strong></div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="order-subtitle mt-3">Không có sản phẩm</div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
                        <div class="code small">
                            {{ $order->code }}
                        </div>
                        <div class="d-flex justify-content-end align-items-center flex-wrap gap-2">
                            <span class="status-pill status-{{ $statusClass }}">
                                <i class="fa fa-circle" style="font-size:8px;"></i>{{ $statusText }}
                            </span>
                            <div class="orders-actions">
                                @if($isCopiedOrder)
                                    <form action="{{ route('site.orders.confirm-copy', $order) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Xác nhận đơn copy #{{ $order->code }}? Hệ thống sẽ cập nhật giá và chuyển đơn sang Chờ leader duyệt.')">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm text-dark">
                                            <i class="fa fa-check me-1"></i>Xác nhận
                                        </button>
                                    </form>
                                @endif
                                @if($canEdit)
                                    <a href="{{ route('site.orders.edit', $order) }}" class="btn btn-success btn-sm">
                                        <i class="fa fa-pencil me-1"></i>Sửa
                                    </a>
                                @endif
                                <a href="{{ route('pages.my_orders') }}" class="btn btn-outline-primary btn-sm">
                                    Quay lại danh sách đơn
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
