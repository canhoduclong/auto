@extends('layouts.site')

@php
    $orderCode = $order->code ?: ('#' . $order->id);

    $statusClass = match((string) $order->status) {
        'approved', 'completed', 'delivered' => 'success',
        'rejected', 'cancelled' => 'danger',
        'pending_leader_approval', 'pending_manager_approval', 'pending_warehouse_approval' => 'warning',
        default => 'secondary',
    };

    $creatorRoleText = $order->user?->roles
        ?->pluck('name')
        ->map(fn ($role) => ucfirst((string) $role))
        ->implode(', ');

    $parseSizeToKg = static function ($size): float {
        $normalized = strtolower(trim((string) $size));
        if ($normalized === '') {
            return 0.0;
        }

        $normalized = str_replace(',', '.', $normalized);

        if (!preg_match('/([0-9]*\.?[0-9]+)/', $normalized, $matches)) {
            return 0.0;
        }

        $value = (float) ($matches[1] ?? 0);
        if ($value <= 0) {
            return 0.0;
        }

        // Support common inputs such as "500g", "0.5kg", "1 kg".
        if (str_contains($normalized, 'g') && !str_contains($normalized, 'kg')) {
            $value = $value / 1000;
        }

        return round($value, 3);
    };

    $subtotal = (float) $order->items->sum(function ($item) use ($parseSizeToKg) {
        if ($item->total !== null) {
            return (float) $item->total;
        }

        $qty = (int) ($item->quantity ?? 0);
        $price = (float) ($item->price ?? $item->base_price ?? 0);
        $unitWeight = (float) ($item->unit_weight ?? 0);
        if ($unitWeight <= 0) {
            $unitWeight = $parseSizeToKg($item->variant?->size);
        }

        $isPricedByKg = $item->is_priced_by_kg;
        if ($isPricedByKg === null) {
            $isPricedByKg = $item->variant?->is_priced_by_kg ?? $item->variant?->product?->is_priced_by_kg ?? true;
        }
        $factor = $isPricedByKg ? max($unitWeight, 0) : 1;

        return (float) ($qty * $factor * $price);
    });

    $itemDiscount = (float) ($order->item_discount_total ?? $order->items->sum('discount_total'));
    $extraDiscount = (float) ($order->extra_discount_total ?? $order->order_discount ?? 0);
    $totalDiscount = (float) ($order->total_discount ?? ($itemDiscount + $extraDiscount));
    $formatSignedMoney = static function (float $amount): string {
        $prefix = $amount < 0 ? '+' : '-';

        return $prefix . number_format(abs($amount), 0, ',', '.') . 'đ';
    };
    $canProcessToday = optional($order->created_at)?->isToday();

    $recipientName = $order->recipient_name ?: ($order->customer?->name ?? '-');
    $recipientPhone = $order->recipient_phone ?: ($order->customer?->phone ?? '-');
    $deliveryAddress = $order->recipient_address ?: ($order->customer?->address ?? '-');
    $deliveryTime = $order->delivery_time ?: ($order->customer?->delivery_time ?? 'Chưa cập nhật');
    $createdAt = optional($order->created_at)->format('d/m/Y H:i') ?: '-';
    $updatedAt = optional($order->updated_at)->format('d/m/Y H:i') ?: '-';

    $statusText = ucfirst(str_replace('_', ' ', (string) $order->status));
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

    $currentStepText = $currentStep?->step
        ? ('B' . $currentStep->step->step_order . ' - ' . $currentStep->step->role_slug)
        : 'Không có bước chờ';

    $itemCount = (int) $order->items->sum('quantity');
    $shippingFee = (float) ($order->shipping_fee ?? 0);
    $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
    $estimatedTotalWeight = (float) ($order->total_weight ?? $order->items->sum(function ($item) {
        $qty = (int) ($item->quantity ?? 0);
        $unitWeight = (float) ($item->unit_weight ?? 0);

        return (float) ($item->total_weight ?? ($qty * $unitWeight));
    }));
@endphp

@push('styles')
<style>
    .team-order-wrap {
        background:
            radial-gradient(circle at top left, rgba(20, 184, 166, 0.08), transparent 24%),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 24%),
            linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        min-height: calc(100vh - 140px);
        padding: 30px 0 44px;
    }
    .team-order-hero {
        border: 0;
        border-radius: 18px;
        background: linear-gradient(135deg, #0f172a 0%, #0f766e 55%, #0369a1 100%);
        color: #fff;
        padding: 24px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
        margin-bottom: 16px;
    }
    .team-order-hero h1 {
        margin: 8px 0 0;
        font-size: clamp(1.35rem, 2.1vw, 1.9rem);
        font-weight: 900;
    }
    .team-order-hero .sub {
        color: rgba(255, 255, 255, 0.82);
        font-size: .9rem;
        margin-top: 4px;
    }
    .team-order-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 14px;
    }
    .team-order-kpi {
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.1);
        padding: 12px;
    }
    .team-order-kpi .label {
        display: block;
        font-size: .72rem;
        opacity: .82;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 4px;
    }
    .team-order-kpi .value {
        font-size: 1rem;
        font-weight: 800;
    }
    .team-status-pills {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .team-order-panel {
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        height: 100%;
    }
    .team-order-panel .card-body {
        padding: 16px;
    }
    .team-order-heading {
        margin: 0 0 12px;
        font-size: .97rem;
        font-weight: 800;
        color: #0f172a;
    }
    .team-order-label {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .team-order-value {
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
    }
    .team-order-table th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #475569;
        background: #f8fafc;
    }
    .team-order-table td {
        font-size: .9rem;
    }
    .team-order-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }
    .team-order-meta-box {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px;
        background: #f8fafc;
    }
    .team-order-note {
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
        padding: 10px;
        font-size: .88rem;
        color: #334155;
    }
    .team-order-section {
        padding: 12px 0;
        border-top: 1px dashed #e2e8f0;
    }
    .team-order-section:first-child {
        border-top: 0;
        padding-top: 0;
    }
    .team-order-section-title {
        font-size: .78rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .team-delivery-line {
        font-size: .88rem;
        color: #475569;
        margin-bottom: 6px;
    }
    .team-delivery-line:last-child {
        margin-bottom: 0;
    }
    .team-item-table-head,
    .team-item-table-row {
        display: grid;
        grid-template-columns: minmax(0, 2fr) 64px 58px 72px 88px 92px 92px;
        gap: 8px;
        align-items: center;
    }
    .team-item-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 4px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .team-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .team-item-row {
        display: grid;
        gap: 4px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .team-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .team-item-name {
        font-size: .88rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .team-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: right;
    }
    .team-item-cell strong {
        color: #0f172a;
    }
    .team-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-size: .88rem;
        color: #475569;
        padding: 4px 0;
    }
    .team-summary-row.total {
        margin-top: 4px;
        padding-top: 8px;
        border-top: 1px dashed #cbd5e1;
        font-weight: 800;
        color: #0f172a;
    }
    .team-action-lock {
        width: 100%;
        margin-top: 6px;
        font-size: .83rem;
    }
    @media (max-width: 991.98px) {
        .team-order-kpis,
        .team-order-meta-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 575px) {
        .team-item-table-head,
        .team-item-table-row {
            grid-template-columns: minmax(0, 1.25fr) 48px 50px 62px 82px 82px 82px;
            gap: 6px;
        }
    }
</style>
@endpush

@section('content')
<section class="team-order-wrap">
    <div class="container">
        <div class="team-order-hero">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="small text-uppercase" style="letter-spacing:.08em;opacity:.9;">Chi tiết đơn hàng nội bộ</div>
                    <h1>Đơn {{ $orderCode }}</h1>
                    <div class="sub">Trang chi tiết chuẩn cho sale, leader và manager với đầy đủ dữ liệu vận hành.</div>
                </div>
                <div class="team-status-pills">
                    <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                    <span class="badge bg-dark-subtle text-dark border">Thanh toán: {{ $paymentStatusText }}</span>
                    <span class="badge bg-secondary-subtle text-secondary border">Giao hàng: {{ $deliveryStatusText }}</span>
                    @if($currentStep?->step)
                        <span class="badge bg-info text-dark">B{{ $currentStep->step->step_order }} - {{ $currentStep->step->role_slug }}</span>
                    @endif
                </div>
            </div>

            <div class="team-order-kpis">
                <div class="team-order-kpi">
                    <span class="label">Ngày tạo</span>
                    <span class="value">{{ $createdAt }}</span>
                </div>
                <div class="team-order-kpi">
                    <span class="label">Giờ giao hàng</span>
                    <span class="value">{{ $deliveryTime }}</span>
                </div>
                <div class="team-order-kpi">
                    <span class="label">Tổng thanh toán</span>
                    <span class="value">{{ number_format((float) $order->total, 0, ',', '.') }} đ</span>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
            </a>
            <div class="small text-muted">Cập nhật lần cuối: {{ $updatedAt }}</div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-5">
                <div class="card team-order-panel">
                    <div class="card-body">
                        <h6 class="team-order-heading">Thông tin khách hàng & giao hàng</h6>
                        <div class="team-order-section">
                            <div class="mb-2">
                                <div class="team-order-label">Người nhận</div>
                                <div class="team-order-value">{{ $recipientName }}</div>
                            </div>
                            <div class="mb-2">
                                <div class="team-order-label">Số điện thoại</div>
                                <div class="team-order-value">{{ $recipientPhone }}</div>
                            </div>
                        </div>

                        <div class="team-order-section">
                            <div class="team-order-section-title">Thông tin giao hàng</div>
                            <div class="team-delivery-line">
                                <i class="bi bi-geo-alt me-1"></i> {{ $deliveryAddress }}
                            </div>
                            <div class="team-delivery-line">
                                <i class="bi bi-clock me-1"></i> Giờ giao: {{ $deliveryTime }}
                            </div>
                        </div>
                        <div class="team-order-section">
                            <div class="team-delivery-line">
                                <i class="bi bi-receipt me-1"></i> Trạng thái đơn: {{ $statusText }}
                            </div>
                            <div class="team-delivery-line">
                                <i class="bi bi-truck me-1"></i>  Trạng thái giao: {{ $deliveryStatusText }}
                            </div>
                            <div class="team-delivery-line">
                                <i class="bi bi-credit-card me-1"></i> Thanh toán: {{ $paymentStatusText }}
                            </div>
                        </div>

                        <div class="team-order-section">
                            <div class="team-order-meta-grid mt-0">
                                <div class="team-order-label">Người tạo: <strong>{{ $order->user?->name ?? '-' }}</strong></div>
                            </div>
                        </div>
                        <div class="team-order-section">
                            @if(!empty($order->shipper_note))
                                <div class="team-order-note mt-3">
                                    <div class="team-order-label mb-1">Ghi chú vận chuyển</div>
                                    {{ $order->shipper_note }}
                                </div>
                            @endif
                        </div>
                        
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card team-order-panel mb-3">
                    <div class="card-body p-0">
                        <div class="p-3 border-bottom">
                            <h6 class="team-order-heading">Sản phẩm trong đơn</h6>
                        </div>
                        <div class="p-3">
                            <div class="team-item-table-head">
                                <div>Sản phẩm</div>                                
                                <div class="text-end">Số Lượng</div>
                                <div class="text-center">ĐVT</div>
                                <div class="text-center">Size</div>
                                <div class="text-end">KL tạm tính</div>
                                <div class="text-center">Loại tính</div>
                                <div class="text-end">Đơn giá</div>
                                <div class="text-end">Tạm tính</div>
                            </div>
                            <ul class="team-item-list">
                                @forelse($order->items as $item)
                                    @php
                                        $qty = (int) ($item->quantity ?? 0);
                                        $price = (float) ($item->price ?? $item->base_price ?? 0);
                                        $unitWeight = (float) ($item->unit_weight ?? 0);
                                        if ($unitWeight <= 0) {
                                            $unitWeight = $parseSizeToKg($item->variant?->size);
                                        }

                                        $lineWeight = (float) ($item->total_weight ?? ($qty * $unitWeight));
                                        $isPricedByKg = $item->is_priced_by_kg;
                                        if ($isPricedByKg === null) {
                                            $isPricedByKg = $item->variant?->is_priced_by_kg ?? $item->variant?->product?->is_priced_by_kg ?? true;
                                        }
                                        $factor = $isPricedByKg ? max($unitWeight, 0) : 1;
                                        $lineTotal = (float) ($item->total ?? ($qty * $factor * $price));
                                        $unitLabel = $item->variant?->product?->unit_label ?? 'Cái';
                                        $weightUnitLabel = $unitLabel;
                                        $sizeText = trim((string) ($item->variant?->size ?? ''));
                                        if ($sizeText === '') {
                                            $sizeText = $unitWeight > 0
                                                ? number_format($unitWeight, 2, ',', '.') . 'kg'
                                                : '-';
                                        }
                                    @endphp
                                    <li class="team-item-row">
                                        <div class="team-item-table-row">
                                            <div class="team-item-name">
                                                {{ $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}
                                                @if($item->variant?->sku)
                                                    <span class="text-muted small">({{ $item->variant->sku }})</span>
                                                @endif
                                            </div>
                                            
                                            <div class="team-item-cell"><strong>{{ number_format($qty, 0, ',', '.') }}</strong></div>
                                            <div class="text-center text-muted small">{{ $unitLabel }}</div>
                                            <div class="text-center text-muted small">{{ $sizeText }}</div>
                                            <div class="team-item-cell">{{ number_format($lineWeight, 2, ',', '.') }} {{ $weightUnitLabel }}</div>
                                            <div class="text-center text-muted small">{{ $isPricedByKg ? 'Theo kg' : 'Theo đơn vị' }}</div>
                                            <div class="team-item-cell">{{ number_format($price, 0, ',', '.') }} đ</div>
                                            <div class="team-item-cell"><strong>{{ number_format($lineTotal, 0, ',', '.') }} đ</strong></div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-center text-muted py-4">Không có dòng sản phẩm.</li>
                                @endforelse
                            </ul>

                            <div class="team-order-section mt-3">
                                <div class="team-summary-row">
                                    <span>Tạm tính</span>
                                    <strong>{{ number_format($subtotal, 0, ',', '.') }} đ</strong>
                                </div>
                                <div class="team-summary-row">
                                    <span>Khối lượng tạm tính</span>
                                    <strong>{{ number_format($estimatedTotalWeight, 2, ',', '.') }} kg</strong>
                                </div>
                                <div class="team-summary-row">
                                    <span>Điều chỉnh</span>
                                    <strong>{{ $formatSignedMoney($totalDiscount) }}</strong>
                                </div>
                                <div class="team-summary-row">
                                    <span>Phí ship</span>
                                    <strong>{{ number_format($shippingFee, 0, ',', '.') }} đ</strong>
                                </div>
                                <div class="team-summary-row">
                                    <span>Thùng xốp</span>
                                    <strong>{{ number_format($foamBoxFee, 0, ',', '.') }} đ</strong>
                                </div>
                                <div class="team-summary-row total">
                                    <span>Tổng thanh toán</span>
                                    <span class="text-success">{{ number_format((float) $order->total, 0, ',', '.') }} đ</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($canApprove)
                    <div class="my-3 px-3">
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            @if($canProcessToday)
                                <form method="POST" action="{{ route('orders.approve', $order) }}">
                                    @csrf
                                    <input type="hidden" name="note" value="Duyệt từ trang chi tiết đơn team">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check2-circle me-1"></i>Duyệt đơn
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('orders.reject', $order) }}">
                                    @csrf
                                    <input type="hidden" name="note" value="Từ chối từ trang chi tiết đơn team">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Xác nhận từ chối đơn này?')">
                                        <i class="bi bi-x-circle me-1"></i>Từ chối
                                    </button>
                                </form>
                            @else
                                <button type="button" class="btn btn-outline-success" disabled title="Chỉ duyệt đơn tạo trong ngày hôm nay">
                                    <i class="bi bi-check2-circle me-1"></i>Duyệt đơn (không khả dụng)
                                </button>
                                <button type="button" class="btn btn-outline-danger" disabled title="Chỉ từ chối đơn tạo trong ngày hôm nay">
                                    <i class="bi bi-x-circle me-1"></i>Từ chối (không khả dụng)
                                </button>
                            @endif
                        </div>
                    </div>
                    @endif

                </div> 
                
            
            </div>
           
        </div>

       </div>

        
    </div>
</section>
@endsection
