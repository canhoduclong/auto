@extends('layouts.shipper')

@section('title', 'Xác nhận đã giao hàng')
@section('subtitle', 'Đơn #{{ $order->code }}')

@section('content')
@php
    $formatKg = static function (float|int|string $value): string {
        $num = (float) $value;
        $str = rtrim(rtrim(number_format($num, 3, '.', ''), '0'), '.');
        return $str . 'kg';
    };
    $customerAddress = $order->customer?->address
        ?? $order->customer?->addresses?->first()?->address
        ?? 'Chưa có địa chỉ';
    $deliveryTime = $order->delivery_time
        ?? $order->customer?->delivery_time
        ?? 'Chưa có khung giờ giao';
    $itemsSubtotal = (float) $order->items->sum(function ($item) {
        return (float) $item->price * (int) $item->quantity;
    });
    $shippingFee = (float) ($order->shipping_fee ?? 0);
    $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
    $codAmount = (float) ($order->total ?? ($itemsSubtotal + $shippingFee + $foamBoxFee));
    $hasKgItem = $order->items->contains(fn($item) => (bool) $item->effective_priced_by_kg);
    $isTruckStationDelivery = (bool) ($order->customer?->use_truck_station ?? false)
        && !empty($order->customer?->truck_station_id);
    $truckStationName = $order->customer?->truckStation?->name;
    $truckStationAddress = $order->customer?->truckStation?->address;
@endphp
<style>
    .shipper-deliver-shell .card {
        border-radius: 14px;
    }
    .shipper-meta-grid {
        display: grid;
        grid-template-columns: 132px 1fr;
        gap: 8px 10px;
        font-size: .92rem;
    }
    .shipper-meta-key {
        color: #64748b;
        font-weight: 600;
    }
    .shipper-customer-title {
        font-size: .82rem;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .shipper-quick-note {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e3a8a;
        border-radius: 10px;
        padding: 8px 10px;
        font-size: .83rem;
        margin-bottom: 12px;
    }
    /* 6-column grid: Name | SL | KL đóng gói | KL thực giao | Giá | Tiền */
    .sp-my-table-head,
    .sp-my-table-row {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) 46px 78px 82px 76px 94px;
        gap: 6px;
        align-items: center;
    }
    .sp-my-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 4px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .sp-my-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .sp-my-item-row {
        display: grid;
        gap: 4px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .sp-my-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .sp-my-item-name {
        font-size: .88rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sp-my-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: right;
    }
    .sp-my-item-cell strong {
        color: #0f172a;
    }
    .sp-my-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-size: .82rem;
        padding: 2px 0;
        color: #475569;
    }
    .sp-my-summary-row.total {
        margin-top: 4px;
        padding-top: 6px;
        border-top: 1px dashed #cbd5e1;
        font-weight: 800;
        color: #0f172a;
        font-size: .95rem;
    }
    .weight-input-wrap {
        display: flex;
        align-items: center;
        gap: 2px;
        justify-content: flex-end;
    }
    .weight-input-wrap input[type="number"] {
        width: 56px;
        text-align: right;
        font-size: .78rem;
        padding: 2px 4px;
        border: 1px solid #94a3b8;
        border-radius: 4px;
        -moz-appearance: textfield;
    }
    .weight-input-wrap input[type="number"]::-webkit-inner-spin-button,
    .weight-input-wrap input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; }
    .weight-input-wrap .unit-label { font-size: .72rem; color: #64748b; }
    .partial-return-section { display: none; }
    .partial-return-section.open { display: block; }
    /* partial return table */
    .partial-table-head,
    .partial-row {
        display: grid;
        grid-template-columns: minmax(0,1.4fr) 90px 110px 80px;
        gap: 6px;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: .82rem;
    }
    .partial-table-head {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 2px;
    }
    .partial-row:last-child { border-bottom: 0; }
    .partial-field {
        display: flex;
        align-items: center;
        gap: 3px;
        justify-content: flex-end;
    }
    .partial-field input[type="number"] {
        width: 54px;
        text-align: center;
        font-size: .82rem;
        padding: 3px 5px;
        border: 1px solid #94a3b8;
        border-radius: 6px;
        -moz-appearance: textfield;
    }
    .partial-field input[type="number"]::-webkit-inner-spin-button,
    .partial-field input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; }
    .partial-field .unit-label { font-size: .7rem; color: #64748b; }
    .partial-return-badge {
        font-size: .7rem;
        padding: 1px 5px;
        border-radius: 10px;
    }
    @media (max-width: 480px) {
        .partial-table-head,
        .partial-row { grid-template-columns: minmax(0,1.2fr) 76px 96px 70px; gap: 4px; }
    }
    .packed-weight-badge {
        display: inline-block;
        font-size: .75rem;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 1px 5px;
        white-space: nowrap;
    }
    @media (max-width: 575px) {
        .sp-my-table-head,
        .sp-my-table-row {
            grid-template-columns: minmax(0, 1.2fr) 38px 64px 68px 60px 78px;
            gap: 3px;
        }
        .weight-input-wrap input[type="number"] { width: 44px; }
    }
</style>
<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8 shipper-deliver-shell">
        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <div class="fw-semibold mb-1">Không thể xác nhận giao hàng</div>
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-info-circle me-1 text-primary"></i>Thông tin đơn hàng
            </div>
            <div class="card-body">
                <div class="shipper-meta-grid">
                    <div class="shipper-meta-key">Mã đơn:</div>
                    <div class="fw-semibold">{{ $order->code }}</div>
                    <div class="shipper-meta-key">Khách hàng:</div>
                    <div>{{ $order->customer?->name ?? '—' }}</div>
                    <div class="shipper-meta-key">Số điện thoại:</div>
                    <div>{{ $order->customer?->phone ?? '—' }}</div>
                    
                </div>

                <div class="shipper-customer my-4">
                    <div class="shipper-customer-title">Thông tin giao hàng</div>
                    <div class="small mb-2"><i class="bi bi-geo-alt me-1"></i><strong>Địa chỉ:</strong> {{ $customerAddress }}</div>
                    <div class="small"><i class="bi bi-clock me-1"></i><strong>Giờ giao:</strong> {{ $deliveryTime }}</div>
                </div>
            
                <div class="sp-my-table-head">
                    <div>Sản phẩm</div>
                    <div class="text-end">SL</div>
                    <div class="text-end">KL đóng gói</div>
                    <div class="text-end">KL thực giao</div>
                    <div class="text-end">Đơn giá</div>
                    <div class="text-end">Thành tiền</div>
                </div>

                <ul class="sp-my-item-list">
                    @foreach($order->items as $item)
                        @php
                            $qty = (int) $item->quantity;
                            $unitPrice = (float) ($item->price ?? 0);
                            $pricedByKg = (bool) $item->effective_priced_by_kg;
                            $defaultWeight = round($item->effective_unit_weight * $qty, 3);
                            // packed_weight = KL kho cân để tính tiền; fallback về actual_weight cho đơn cũ
                            $packedWeight = $item->packed_weight !== null
                                ? (float) $item->packed_weight
                                : ($item->actual_weight !== null ? (float) $item->actual_weight : null);
                            $lineTotal = $pricedByKg ? $defaultWeight * $unitPrice : $qty * $unitPrice;
                        @endphp
                        <li class="sp-my-item-row">
                            <div class="sp-my-table-row">
                                <div class="sp-my-item-name">
                                    {{ $item->variant?->name ?? $item->variant?->sku ?? 'Sản phẩm' }}
                                    @if($item->variant?->sku)
                                        <span class="text-muted small">({{ $item->variant->sku }})</span>
                                    @endif
                                </div>
                                <div class="sp-my-item-cell">
                                    <strong id="disp-qty-{{ $item->id }}">{{ $qty }}</strong>
                                </div>
                                {{-- KL đóng gói (kho cân) – read-only --}}
                                <div class="sp-my-item-cell">
                                    @if($pricedByKg)
                                        @if($packedWeight !== null)
                                            <span class="packed-weight-badge" title="KL kho đã cân">{{ $formatKg($packedWeight) }}</span>
                                        @else
                                            <span class="text-muted" style="font-size:.75rem;">Chưa cân</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                                {{-- KL thực giao (shipper nhập) --}}
                                <div class="sp-my-item-cell">
                                    @if($pricedByKg)
                                        <div class="weight-input-wrap">
                                            <input type="number"
                                                   name="actual_weight[{{ $item->id }}]"
                                                   id="wt-{{ $item->id }}"
                                                   value="{{ old('actual_weight.'.$item->id, $packedWeight ?? $defaultWeight) }}"
                                                   step="0.001" min="0"
                                                   data-item-id="{{ $item->id }}">
                                            <span class="unit-label">kg</span>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                                <div class="sp-my-item-cell">{{ number_format($unitPrice) }}đ</div>
                                <div class="sp-my-item-cell">
                                    <strong id="line-total-{{ $item->id }}">{{ number_format($lineTotal) }}đ</strong>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-2">
                    <div class="sp-my-summary-row">
                        <span>Tiền hàng</span>
                        <strong id="subtotal-display">{{ number_format($itemsSubtotal) }}đ</strong>
                    </div>
                    <div class="sp-my-summary-row">
                        <span>Phí ship</span>
                        <strong>{{ number_format($shippingFee) }}đ</strong>
                    </div>
                    <div class="sp-my-summary-row">
                        <span>Thùng xốp</span>
                        <strong>{{ number_format($foamBoxFee) }}đ</strong>
                    </div>
                    <div class="sp-my-summary-row total">
                        <span>COD cần thu</span>
                        <span class="text-success" id="cod-display">{{ number_format($codAmount) }}đ</span>
                    </div>
                </div>
            
                <div class="shipper-quick-note mt-4">
                    <i class="bi bi-shield-check me-1"></i>Vui lòng kiểm tra đúng số tiền thu và ảnh bằng chứng trước khi xác nhận.
                </div>

                @if($isTruckStationDelivery)
                    <div class="alert alert-warning mt-3 mb-3" style="border:1px solid #fcd34d;background:#fffbeb;">
                        <div class="fw-semibold mb-1"><i class="bi bi-truck me-1"></i>Đơn giao nhà xe</div>
                        <div class="small mb-1">Bắt buộc upload chứng từ bàn giao cho nhà xe trước khi xác nhận hoàn thành đơn.</div>
                        <div class="small text-muted">
                            Nhà xe: {{ $truckStationName ?: 'Chưa cấu hình' }}
                            @if($truckStationAddress)
                                | Địa chỉ: {{ $truckStationAddress }}
                            @endif
                        </div>
                    </div>
                @endif

                <form action="{{ route('shipper.mark-delivered', $order) }}" method="POST" enctype="multipart/form-data" id="deliveryForm">
                    @csrf

                    {{-- ── STEP 0: CHỌN HÀNH ĐỘNG ── --}}
                    <div id="step-0-content" style="display:block;">
                        <div style="text-align:center;padding:2rem 1rem;">
                            <div style="font-size:1.25rem;font-weight:700;color:#0f172a;margin-bottom:1rem;">
                                <i class="bi bi-question-circle me-2 text-primary"></i>Bạn muốn làm gì?
                            </div>
                            <div style="display:flex;gap:1rem;max-width:500px;margin:0 auto;">
                                <button type="button" id="btnPaymentOnly" class="btn btn-lg btn-success flex-fill" style="padding:1rem;">
                                    <i class="bi bi-credit-card me-2"></i>Thanh toán
                                </button>
                                <button type="button" id="btnPartialReturn" class="btn btn-lg btn-warning flex-fill" style="padding:1rem;">
                                    <i class="bi bi-arrow-return-left me-2"></i>Trả hàng
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ── STEPPER ── --}}
                    <div class="mb-4" id="stepperContainer" style="display:none;">
                        <div style="display:flex;align-items:center;">
                            {{-- Step 1: Trả hàng (chỉ hiện khi path=return) --}}
                            <div id="step-1-indicator" style="flex:0 0 auto;text-align:center;display:none;">
                                <div style="display:flex;align-items:center;gap:0.4rem;">
                                    <div id="step-1-circle" style="width:32px;height:32px;border-radius:50%;background:#cbd5e1;color:#475569;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0;">1</div>
                                    <span id="step-1-label" style="font-size:0.82rem;font-weight:600;color:#64748b;white-space:nowrap;">Trả hàng</span>
                                </div>
                            </div>
                            <div id="step-divider-12" style="flex:1;height:2px;background:#e2e8f0;margin:0 0.5rem;display:none;"></div>

                            {{-- Step 2: Xem lại (chỉ hiện khi path=return) --}}
                            <div id="step-2-indicator" style="flex:0 0 auto;text-align:center;display:none;">
                                <div style="display:flex;align-items:center;gap:0.4rem;">
                                    <div id="step-2-circle" style="width:32px;height:32px;border-radius:50%;background:#cbd5e1;color:#475569;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0;">2</div>
                                    <span id="step-2-label" style="font-size:0.82rem;font-weight:600;color:#64748b;white-space:nowrap;">Xem lại</span>
                                </div>
                            </div>
                            <div id="step-divider-23" style="flex:1;height:2px;background:#e2e8f0;margin:0 0.5rem;display:none;"></div>

                            {{-- Step 3: Thanh toán (luôn hiện) --}}
                            <div id="step-3-indicator" style="flex:0 0 auto;text-align:center;">
                                <div style="display:flex;align-items:center;gap:0.4rem;">
                                    <div id="step-3-circle" style="width:32px;height:32px;border-radius:50%;background:#cbd5e1;color:#475569;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0;">3</div>
                                    <span id="step-3-label" style="font-size:0.82rem;font-weight:600;color:#64748b;white-space:nowrap;">Thanh toán</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    {{-- ── STEP 1: TRẢ HÀNG 1 PHẦN ── --}}
                    <div id="step-1-content" style="display:none;">
                    {{-- ── Khách trả 1 phần hàng ── --}}
                    <div class="mb-3 p-3 rounded" style="background:#fff7ed;border:1px solid #fed7aa;">
                        <div style="margin-bottom:.75rem;">
                            <input type="checkbox" id="hasPartialReturn" style="display:none;"
                                   {{ old('has_partial_return') ? 'checked' : '' }}>
                            <div class="fw-semibold" style="color:#9a3412;">
                                <i class="bi bi-arrow-return-left me-1"></i>Điền thông tin hàng trả lại
                            </div>
                        </div>
                        <div class="partial-return-section open" id="partialReturnSection">
                            <input type="hidden" name="has_partial_return" id="hasPartialReturnHidden" value="{{ old('has_partial_return', '0') }}">
                            <div class="small text-muted mb-2">
                                <i class="bi bi-info-circle me-1"></i>Nhập số lượng và khối lượng <strong>thực giao</strong>. Phần chưa giao sẽ tạo phiếu hoàn trả về kho.
                            </div>

                            {{-- Kho trả về --}}
                            <div class="mb-2">
                                <label class="form-label fw-semibold mb-1" style="font-size:.85rem;">Kho trả hàng về <span class="text-danger">*</span></label>
                                <select name="return_warehouse_id" class="form-select form-select-sm" id="returnWarehouseSelect" required>
                                    <option value="">-- Chọn kho --</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ old('return_warehouse_id') == $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Lý do trả --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-1" style="font-size:.85rem;">Lý do trả hàng <span class="text-danger">*</span></label>
                                <select name="partial_return_reason" class="form-select form-select-sm" required>
                                    <option value="">-- Chọn lý do --</option>
                                    <option value="customer_refused" {{ old('partial_return_reason') === 'customer_refused' ? 'selected' : '' }}>Khách từ chối nhận</option>
                                    <option value="overstock" {{ old('partial_return_reason') === 'overstock' ? 'selected' : '' }}>Khách nhận dư / đặt nhầm</option>
                                    <option value="quality" {{ old('partial_return_reason') === 'quality' ? 'selected' : '' }}>Hàng không đủ chất lượng</option>
                                    <option value="other" {{ old('partial_return_reason') === 'other' ? 'selected' : '' }}>Lý do khác</option>
                                </select>
                            </div>

                            {{-- header --}}
                            <div class="partial-table-head">
                                <div>Sản phẩm</div>
                                <div class="text-end">SL giao</div>
                                <div class="text-end">Tổng KL giao</div>
                                <div class="text-end">Trả lại</div>
                            </div>
                            @foreach($order->items as $item)
                                @php
                                    $qty = (int) $item->quantity;
                                    $pricedByKg = (bool) $item->effective_priced_by_kg;
                                    $defaultTotalWeight = round($item->effective_unit_weight * $qty, 3);
                                    $packedWeight = $item->packed_weight !== null
                                        ? (float) $item->packed_weight
                                        : ($item->actual_weight !== null ? (float) $item->actual_weight : null);
                                    $partialBaseWeight = $pricedByKg
                                        ? round(($packedWeight ?? $defaultTotalWeight), 3)
                                        : null;
                                @endphp
                                <div class="partial-row">
                                    <div class="fw-semibold" style="line-height:1.25;">
                                        {{ $item->variant?->name ?? $item->variant?->sku ?? 'Sản phẩm' }}
                                        <div class="text-muted" style="font-weight:400;font-size:.75rem;">tổng: {{ $qty }}{{ $pricedByKg ? ' – '.$formatKg($partialBaseWeight) : '' }}</div>
                                    </div>
                                    {{-- SL giao --}}
                                    <div class="partial-field">
                                        <input type="number"
                                               name="delivered_qty[{{ $item->id }}]"
                                               id="dq-{{ $item->id }}"
                                               class="delivered-qty-input"
                                               value="{{ old('delivered_qty.'.$item->id, $qty) }}"
                                               min="0" max="{{ $qty }}"
                                               data-item-id="{{ $item->id }}"
                                               data-max-qty="{{ $qty }}"
                                               data-priced-by-kg="{{ $pricedByKg ? '1' : '0' }}"
                                               data-unit-weight="{{ $item->effective_unit_weight }}"
                                               data-default-weight="{{ $partialBaseWeight ?? $defaultTotalWeight }}">
                                    </div>
                                    {{-- Tổng KL giao --}}
                                    <div class="partial-field">
                                        @if($pricedByKg)
                                            <input type="number"
                                                   name="partial_weight[{{ $item->id }}]"
                                                   id="pw-{{ $item->id }}"
                                                   class="partial-weight-input"
                                                   value="{{ old('partial_weight.'.$item->id, $partialBaseWeight) }}"
                                                   step="0.001" min="0" max="{{ $partialBaseWeight }}"
                                                   data-item-id="{{ $item->id }}"
                                                   data-default-weight="{{ $partialBaseWeight }}">
                                            <span class="unit-label">kg</span>
                                        @else
                                            <span class="text-muted" id="pw-display-{{ $item->id }}">—</span>
                                        @endif
                                    </div>
                                    {{-- Trả lại badge --}}
                                    <div class="text-end">
                                        <span class="partial-return-badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-none"
                                              id="return-badge-{{ $item->id }}">—</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Nút hành động Step 1 --}}
                        <div class="mt-3 d-flex gap-2">
                            <button type="button" id="step1BackBtn" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Quay lại
                            </button>
                            <button type="button" id="step1ContinueBtn" class="btn btn-primary flex-fill">
                                <i class="bi bi-check2 me-1"></i>Lưu & Tiếp tục
                            </button>
                        </div>
                    </div>
                    </div>

                    {{-- ── STEP 2: XEM LẠI & TÍNH TOÁN ── --}}
                    <div id="step-2-content" style="display:none;">
                        <div class="mb-3 p-3 rounded" style="background:#f0f9ff;border:1px solid #bfdbfe;">
                            <div class="fw-semibold mb-3" style="color:#1e40af;font-size:1rem;">
                                <i class="bi bi-info-circle me-1"></i>Xem lại chi tiết đơn hàng
                            </div>

                            {{-- Chi tiết đơn hàng --}}
                            <div class="order-detail-table-head" style="display:grid;grid-template-columns:2fr 60px 60px 70px 100px 100px;gap:8px;font-size:.8rem;font-weight:700;text-transform:uppercase;color:#64748b;padding:0 0 8px;border-bottom:1px solid #dee2e6;margin-bottom:8px;">
                                <div>Sản phẩm</div>
                                <div class="text-end">Giao</div>
                                <div class="text-end">Trả</div>
                                <div class="text-end">KL/Kg</div>
                                <div class="text-end">Đơn giá</div>
                                <div class="text-end">Tiền</div>
                            </div>
                            <div id="orderDetailItems" style="display:grid;gap:6px;">
                                {{-- JS sẽ populate nội dung ở đây --}}
                            </div>
                            <div style="border-top:1px solid #dee2e6;margin-top:12px;padding-top:12px;">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:1rem;">
                                    <div>
                                        <div style="font-size:.8rem;color:#64748b;margin-bottom:4px;">Tiền hàng gốc</div>
                                        <div style="font-size:.95rem;color:#0f172a;"><strong id="orig-subtotal-display">{{ number_format($itemsSubtotal) }}đ</strong></div>
                                    </div>
                                    <div>
                                        <div style="font-size:.8rem;color:#64748b;margin-bottom:4px;">Tiền hàng sau điều chỉnh</div>
                                        <div style="font-size:.95rem;color:#0f172a;"><strong id="adjusted-subtotal-display">{{ number_format($itemsSubtotal) }}đ</strong></div>
                                    </div>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
                                    <div>
                                        <div style="font-size:.8rem;color:#64748b;margin-bottom:4px;">Phí ship</div>
                                        <div style="font-size:.9rem;color:#0f172a;">{{ number_format($shippingFee) }}đ</div>
                                    </div>
                                    <div>
                                        <div style="font-size:.8rem;color:#64748b;margin-bottom:4px;">Thùng xốp</div>
                                        <div style="font-size:.9rem;color:#0f172a;">{{ number_format($foamBoxFee) }}đ</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="button" id="step2BackBtn" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Quay lại chỉnh sửa
                            </button>
                            <button type="button" id="step2ContinueBtn" class="btn btn-success flex-fill">
                                <i class="bi bi-arrow-right me-1"></i>Tiếp tục thanh toán
                            </button>
                        </div>
                    </div>

                    {{-- ── STEP 3: THANH TOÁN ── --}}
                    <div id="step-3-content" style="display:none;">
                        <div class="mb-3 p-3 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                            <div class="fw-semibold mb-3" style="color:#15803d;font-size:1rem;">
                                <i class="bi bi-credit-card me-1"></i>Hoàn thành thanh toán
                            </div>

                            {{-- Tóm tắt thanh toán --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:1.5rem;padding:1rem;background:white;border-radius:6px;border:1px solid #dcfce7;">
                                <div>
                                    <div style="font-size:.8rem;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.03em;">Tổng tiền cần thu</div>
                                    <div style="font-size:1.25rem;color:#15803d;font-weight:700;" id="cod-display-step3">{{ number_format($codAmount) }}đ</div>
                                </div>
                                <div>
                                    <div style="font-size:.8rem;color:#64748b;margin-bottom:4px;text-transform:uppercase;letter-spacing:.03em;">Số tiền đã thu</div>
                                    <div style="font-size:1.25rem;color:#0f172a;font-weight:700;">
                                        <input type="number" name="collected_amount" id="collected_amount_input" class="form-control form-control-lg" 
                                               value="{{ old('collected_amount', $order->total) }}"
                                               step="1000" min="0" required style="max-width:200px;">
                                    </div>
                                </div>
                            </div>

                            {{-- Phương thức thanh toán --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Phương thức thanh toán <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method"
                                               id="pm_cash" value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="pm_cash">
                                            <i class="bi bi-cash me-1"></i>Tiền mặt
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method"
                                               id="pm_transfer" value="transfer" {{ old('payment_method') === 'transfer' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="pm_transfer">
                                            <i class="bi bi-bank me-1"></i>Chuyển khoản
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Ảnh cân hàng --}}
                            @if($hasKgItem)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-camera me-1 text-secondary"></i>Ảnh cân hàng
                                    <span class="text-muted fw-normal small">(tuỳ chọn – nếu cân lại)</span>
                                </label>
                                <input type="file" name="weight_image" class="form-control" accept="image/*" id="weightImageInput">
                                <div class="form-text text-muted">Ảnh chụp cân khi giao cho khách (tối đa 5MB)</div>
                                <div id="weightImagePreview" class="mt-2 d-none">
                                    <img id="weightImagePreviewImg" src="" class="img-fluid rounded" style="max-height:160px;">
                                </div>
                            </div>
                            @endif

                            {{-- Ảnh xác nhận giao hàng --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-camera-fill me-1 text-success"></i>Ảnh xác nhận giao hàng <span class="text-danger">*</span>
                                </label>
                                <input type="file" name="proof_image" class="form-control" accept="image/*" required id="proofPreviewInput">
                                <div class="form-text text-muted">Ảnh chụp khi giao hàng (tối đa 5MB)</div>
                                <div id="proofPreview" class="mt-2 d-none">
                                    <img id="proofPreviewImg" src="" class="img-fluid rounded" style="max-height:200px;">
                                </div>
                            </div>

                            @if($isTruckStationDelivery)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-file-earmark-check me-1 text-warning"></i>Chứng từ giao nhà xe <span class="text-danger">*</span>
                                </label>
                                <input type="file" name="truck_station_receipt_image" class="form-control" accept="image/*" required id="truckStationReceiptInput">
                                <div class="form-text text-muted">Ảnh biên nhận/phiếu gửi tại trạm xe (tối đa 5MB)</div>
                                <div id="truckStationReceiptPreview" class="mt-2 d-none">
                                    <img id="truckStationReceiptPreviewImg" src="" class="img-fluid rounded" style="max-height:200px;">
                                </div>
                            </div>
                            @endif

                            <div class="mt-4 d-flex gap-2">
                                <button type="button" id="step3BackBtn" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                                </button>
                                <button type="submit" class="btn btn-success flex-fill btn-lg">
                                    <i class="bi bi-check-circle me-1"></i>Xác nhận đã giao hàng
                                </button>
                            </div>
                        </div>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const shippingFee = {{ (int) $shippingFee }};
    const foamBoxFee  = {{ (int) $foamBoxFee }};
    const itemData    = {
        @foreach($order->items as $item)
        {{ $item->id }}: {
            id:            {{ $item->id }},
            pricedByKg:    {{ $item->effective_priced_by_kg ? 'true' : 'false' }},
            price:         {{ (float) ($item->price ?? 0) }},
            originalQty:   {{ (int) $item->quantity }},
            unitWeight:    {{ round((float) $item->effective_unit_weight, 3) }},
            defaultWeight: {{ round($item->effective_unit_weight * $item->quantity, 3) }},
            baseWeight:    {{ round(($item->packed_weight ?? $item->actual_weight ?? ($item->effective_unit_weight * $item->quantity)), 3) }},
            name:          '{{ $item->variant?->name ?? $item->variant?->sku ?? 'Sản phẩm' }}',
            sku:           '{{ $item->variant?->sku ?? '' }}',
        },
        @endforeach
    };

    function formatVnd(n) {
        return Math.round(n).toLocaleString('vi-VN') + 'đ';
    }

    function formatNumber(n) {
        return Math.round(n).toLocaleString('vi-VN');
    }

    function getDeliveredQty(id) {
        const inp = document.getElementById('dq-' + id);
        if (!inp) return itemData[id].originalQty;
        const v = parseInt(inp.value, 10);
        return isNaN(v) ? itemData[id].originalQty : Math.max(0, Math.min(v, itemData[id].originalQty));
    }

    function getActualWeight(id) {
        // Top-table actual_weight (full batch re-weigh)
        const inp = document.getElementById('wt-' + id);
        if (!inp) return itemData[id].baseWeight;
        const v = parseFloat(inp.value);
        return isNaN(v) ? itemData[id].baseWeight : Math.max(0, v);
    }

    function getPartialWeight(id) {
        // Partial-return section total delivered weight input
        const inp = document.getElementById('pw-' + id);
        if (!inp) return null; // not a kg item
        const v = parseFloat(inp.value);
        return isNaN(v) ? 0 : Math.max(0, v);
    }

    function calcItemTotal(id) {
        const d = itemData[id];
        const deliveredQty = getDeliveredQty(id);
        if (d.pricedByKg) {
            // Use partial_weight if partial return section is open, else use actual_weight
            const partialCheck = document.getElementById('hasPartialReturn');
            if (partialCheck && partialCheck.checked) {
                const pw = getPartialWeight(id);
                return (pw !== null ? pw : 0) * d.price;
            }
            const weight = getActualWeight(id);
            return weight * d.price;
        } else {
            return deliveredQty * d.price;
        }
    }

    function getCurrentBaseWeight(id) {
        const d = itemData[id];
        if (!d.pricedByKg) return d.defaultWeight;

        const actualWeight = getActualWeight(id);
        if (actualWeight > 0) return actualWeight;

        return d.baseWeight > 0 ? d.baseWeight : d.defaultWeight;
    }

    function updateReturnBadge(id) {
        const d = itemData[id];
        const badge = document.getElementById('return-badge-' + id);
        const qtyInp = document.getElementById('dq-' + id);
        if (!badge || !qtyInp) return;

        const max = d.originalQty;
        const deliveredQty = getDeliveredQty(id);
        if (deliveredQty >= max) {
            badge.classList.add('d-none');
            return;
        }

        badge.classList.remove('d-none');
        if (d.pricedByKg) {
            const baseWeight = getCurrentBaseWeight(id);
            const deliveredWeight = getPartialWeight(id);
            const safeDeliveredWeight = deliveredWeight !== null ? deliveredWeight : 0;
            const returnedWeight = Math.max(0, baseWeight - safeDeliveredWeight);
            badge.textContent = 'trả ' + returnedWeight.toFixed(3).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1') + ' kg';
            return;
        }

        badge.textContent = 'trả ' + (max - deliveredQty);
    }

    function recalc() {
        let subtotal = 0;
        for (const id of Object.keys(itemData)) {
            const intId = parseInt(id, 10);
            const total = calcItemTotal(intId);
            subtotal += total;
            const lineEl = document.getElementById('line-total-' + id);
            if (lineEl) lineEl.textContent = formatVnd(total);

            const qtyEl = document.getElementById('disp-qty-' + id);
            if (qtyEl) {
                const dq = getDeliveredQty(intId);
                qtyEl.textContent = dq;
                qtyEl.style.color = dq < itemData[intId].originalQty ? '#dc2626' : '';
            }

            updateReturnBadge(intId);
        }
        const newCod = subtotal + shippingFee + foamBoxFee;
        const codEl = document.getElementById('cod-display');
        if (codEl) codEl.textContent = formatVnd(newCod);
        const subtotalEl = document.getElementById('subtotal-display');
        if (subtotalEl) subtotalEl.textContent = formatVnd(subtotal);
        const amtInput = document.getElementById('collected_amount_input');
        if (amtInput) amtInput.value = Math.round(newCod);
    }

    // Top-table actual_weight inputs (re-weigh)
    document.querySelectorAll('input[name^="actual_weight["]').forEach(function (inp) {
        inp.addEventListener('input', recalc);
    });

    // Partial return: validate weight input — range check like warehouse packing
    function validatePartialWeight(inp) {
        const id       = parseInt(inp.dataset.itemId, 10);
        const d        = itemData[id];
        const v        = parseFloat(inp.value);
        const delivQty = getDeliveredQty(id);
        const errId    = 'pw-err-' + id;
        let errEl      = document.getElementById(errId);
        if (!errEl) {
            errEl = document.createElement('small');
            errEl.id = errId;
            errEl.style.cssText = 'color:#dc2626;display:block;font-size:0.75rem;margin-top:4px;font-weight:500;';
            inp.parentNode.appendChild(errEl);
        }

        function setInvalid(msg) {
            errEl.textContent = msg;
            inp.style.borderColor = '#dc2626';
            inp.style.background  = '#fff5f5';
            return false;
        }
        function setValid() {
            errEl.textContent = '';
            inp.style.borderColor = '';
            inp.style.background  = '';
            return true;
        }

        if (inp.value === '' || isNaN(v)) return setInvalid('Vui lòng nhập số kg hợp lệ.');
        if (v < 0) return setInvalid('Kg không được âm.');

        const unitW  = d.unitWeight || 0;
        const baseW  = d.baseWeight || 0;
        const maxW   = baseW; // cannot exceed total packed weight

        if (v > maxW && maxW > 0) {
            return setInvalid('Tối đa ' + maxW + ' kg (tổng KL đóng gói).');
        }

        // Range check: delivered_qty × (unitWeight ± 0.25) — same logic as warehouse packing
        if (unitW > 0 && delivQty > 0) {
            const minKg = Math.max(0, delivQty * (unitW - 0.25));
            const maxKg = Math.min(maxW > 0 ? maxW : Infinity, delivQty * (unitW + 0.25));
            if (v < minKg || v > maxKg) {
                return setInvalid(
                    'Kg giao dự kiến ' + minKg.toFixed(3) + ' – ' + maxKg.toFixed(3)
                    + ' (SL ' + delivQty + ' × ' + unitW + ' kg ± 0.25)'
                );
            }
        }

        return setValid();
    }

    function updateContinueBtnState() {
        const btn = document.getElementById('step1ContinueBtn');
        if (!btn) return;
        const hasError = document.querySelector('.partial-weight-input[style*="border-color: rgb(220"]') ||
                         [...document.querySelectorAll('.partial-weight-input')].some(inp => {
                             const errEl = document.getElementById('pw-err-' + inp.dataset.itemId);
                             return errEl && errEl.textContent.trim() !== '';
                         });
        btn.disabled = !!hasError;
    }

    document.querySelectorAll('.partial-weight-input').forEach(function (inp) {
        inp.addEventListener('input', function () {
            validatePartialWeight(this);
            updateContinueBtnState();
            recalc();
        });
    });

    // Partial return toggle
    const partialCheck   = document.getElementById('hasPartialReturn');
    const partialSection = document.getElementById('partialReturnSection');
    const partialHidden  = document.getElementById('hasPartialReturnHidden');

    partialCheck.addEventListener('change', function () {
        if (this.checked) {
            partialSection.classList.add('open');
            if (partialHidden) partialHidden.value = '1';
        } else {
            partialSection.classList.remove('open');
            if (partialHidden) partialHidden.value = '0';
            // Reset all fields to max/default
            document.querySelectorAll('.delivered-qty-input').forEach(function (inp) {
                inp.value = inp.dataset.maxQty;
            });
            document.querySelectorAll('.partial-weight-input').forEach(function (inp) {
                inp.value = inp.dataset.defaultWeight;
            });
            recalc();
        }
    });

    // Delivered qty input: auto-scale partial_weight proportionally
    document.querySelectorAll('.delivered-qty-input').forEach(function (inp) {
        inp.addEventListener('input', function () {
            const id  = parseInt(this.dataset.itemId, 10);
            const max = parseInt(this.dataset.maxQty, 10);
            let v = parseInt(this.value, 10);
            if (isNaN(v) || v < 0) v = 0;
            if (v > max) v = max;
            this.value = v;

            // Auto-scale partial_weight if kg item
            const pwInp = document.getElementById('pw-' + id);
            if (pwInp) {
                const defaultW = getCurrentBaseWeight(id);
                const scaled = max > 0 ? Math.round(defaultW * (v / max) * 1000) / 1000 : 0;
                pwInp.value = scaled;
            }
            recalc();
        });
    });

    // Image previews
    function bindPreview(inputId, previewId, previewImgId) {
        const inp = document.getElementById(inputId);
        if (!inp) return;
        inp.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                document.getElementById(previewImgId).src = ev.target.result;
                document.getElementById(previewId).classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    }
    bindPreview('proofPreviewInput', 'proofPreview', 'proofPreviewImg');
    bindPreview('weightImageInput', 'weightImagePreview', 'weightImagePreviewImg');
    bindPreview('truckStationReceiptInput', 'truckStationReceiptPreview', 'truckStationReceiptPreviewImg');

    // Init return badges from old() values
    document.querySelectorAll('.delivered-qty-input').forEach(function (inp) {
        const id  = parseInt(inp.dataset.itemId, 10);
        const max = parseInt(inp.dataset.maxQty, 10);
        const v   = parseInt(inp.value, 10);
        const badge = document.getElementById('return-badge-' + id);
        if (badge && !isNaN(v) && v < max) {
            badge.classList.remove('d-none');
            updateReturnBadge(id);
        }
    });

    // Render order details (chi tiết đơn hàng)
    function renderOrderDetails() {
        const container = document.getElementById('orderDetailItems');
        if (!container) return;
        container.innerHTML = '';

        let totalAdjustedPrice = 0;
        for (const id of Object.keys(itemData)) {
            const intId = parseInt(id, 10);
            const d = itemData[intId];
            const deliveredQty = getDeliveredQty(intId);
            const returnedQty = d.originalQty - deliveredQty;
            const itemTotal = calcItemTotal(intId);
            totalAdjustedPrice += itemTotal;

            const row = document.createElement('div');
            row.style.cssText = 'display:grid;grid-template-columns:2fr 60px 60px 70px 100px 100px;gap:8px;padding:8px;border-bottom:1px solid #e9ecef;font-size:.8rem;';

            // Sản phẩm
            const nameCol = document.createElement('div');
            nameCol.style.cssText = 'color:#0f172a;';
            nameCol.innerHTML = '<div style="font-weight:600;line-height:1.25;">' + d.name + '</div>'
                + (d.sku ? '<div style="font-size:0.7rem;color:#64748b;">' + d.sku + '</div>' : '');
            row.appendChild(nameCol);

            // Giao
            const deliveredCol = document.createElement('div');
            deliveredCol.style.cssText = 'text-align:right;color:#0f172a;font-weight:600;';
            deliveredCol.textContent = deliveredQty;
            row.appendChild(deliveredCol);

            // Trả
            const returnCol = document.createElement('div');
            returnCol.style.cssText = 'text-align:right;' + (returnedQty > 0 ? 'color:#dc2626;font-weight:600;' : 'color:#64748b;');
            returnCol.textContent = returnedQty > 0 ? returnedQty : '—';
            row.appendChild(returnCol);

            // KL/Kg
            const weightCol = document.createElement('div');
            weightCol.style.cssText = 'text-align:right;color:#64748b;';
            if (d.pricedByKg) {
                const weight = getPartialWeight(intId) !== null ? getPartialWeight(intId) : 0;
                weightCol.textContent = weight.toFixed(3).replace(/\.?0+$/, '') + ' kg';
            } else {
                weightCol.textContent = '—';
            }
            row.appendChild(weightCol);

            // Đơn giá
            const priceCol = document.createElement('div');
            priceCol.style.cssText = 'text-align:right;color:#64748b;';
            priceCol.textContent = formatNumber(d.price) + 'đ';
            row.appendChild(priceCol);

            // Tiền
            const totalCol = document.createElement('div');
            totalCol.style.cssText = 'text-align:right;color:#0f172a;font-weight:600;';
            totalCol.textContent = formatVnd(itemTotal);
            row.appendChild(totalCol);

            container.appendChild(row);
        }

        // Cập nhật tổng tiền điều chỉnh
        const adjustedSubtotalEl = document.getElementById('adjusted-subtotal-display');
        if (adjustedSubtotalEl) {
            adjustedSubtotalEl.textContent = formatVnd(totalAdjustedPrice);
        }
    }

    // ── Step Navigation ──
    let currentPath = null; // 'payment' or 'return'

    function updateStepperIndicator(activeStep) {
        for (let i = 1; i <= 3; i++) {
            const circle = document.getElementById('step-' + i + '-circle');
            const label  = document.getElementById('step-' + i + '-label');
            if (!circle) continue;

            if (i < activeStep) {
                // Done step
                circle.style.background = '#0f766e';
                circle.style.color      = 'white';
                circle.innerHTML        = '<i class="bi bi-check-lg" style="font-size:0.85rem;"></i>';
                if (label) { label.style.color = '#0f766e'; label.style.fontWeight = '600'; }
            } else if (i === activeStep) {
                // Active step
                circle.style.background = '#0369a1';
                circle.style.color      = 'white';
                circle.textContent      = i;
                if (label) { label.style.color = '#0f172a'; label.style.fontWeight = '700'; }
            } else {
                // Future step
                circle.style.background = '#cbd5e1';
                circle.style.color      = '#475569';
                circle.textContent      = i;
                if (label) { label.style.color = '#94a3b8'; label.style.fontWeight = '600'; }
            }
        }
        // Divider colors
        const d12 = document.getElementById('step-divider-12');
        const d23 = document.getElementById('step-divider-23');
        if (d12) d12.style.background = activeStep > 1 ? '#0f766e' : '#e2e8f0';
        if (d23) d23.style.background = activeStep > 2 ? '#0f766e' : '#e2e8f0';
    }

    function updateStepperLayout(path) {
        const showReturn = path === 'return';
        const s1 = document.getElementById('step-1-indicator');
        const s2 = document.getElementById('step-2-indicator');
        const d12 = document.getElementById('step-divider-12');
        const d23 = document.getElementById('step-divider-23');
        const s3  = document.getElementById('step-3-indicator');
        // Số thứ tự lại khi chỉ có 1 bước
        const circle3 = document.getElementById('step-3-circle');
        if (showReturn) {
            if (s1) s1.style.display = '';
            if (s2) s2.style.display = '';
            if (d12) d12.style.display = '';
            if (d23) d23.style.display = '';
            if (circle3) circle3.textContent = '3';
        } else {
            // Payment only: chỉ hiện step 3, đặt số 1
            if (s1) s1.style.display = 'none';
            if (s2) s2.style.display = 'none';
            if (d12) d12.style.display = 'none';
            if (d23) d23.style.display = 'none';
            if (circle3) circle3.textContent = '1';
        }
    }

    function goToStep(stepNum) {
        const stepperContainer = document.getElementById('stepperContainer');
        
        if (stepNum === 0) {
            // Show step 0, hide stepper & all other steps
            document.getElementById('step-0-content').style.display = 'block';
            document.getElementById('step-1-content').style.display = 'none';
            document.getElementById('step-2-content').style.display = 'none';
            document.getElementById('step-3-content').style.display = 'none';
            stepperContainer.style.display = 'none';
        } else {
            // Hide step 0, show stepper & target step
            document.getElementById('step-0-content').style.display = 'none';
            document.getElementById('step-1-content').style.display = 'none';
            document.getElementById('step-2-content').style.display = 'none';
            document.getElementById('step-3-content').style.display = 'none';
            document.getElementById('step-' + stepNum + '-content').style.display = 'block';
            stepperContainer.style.display = 'block';
            
            // Update layout theo path, rồi update trạng thái active
            updateStepperLayout(currentPath);
            updateStepperIndicator(stepNum);
        }

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ── Step 0: Button Handlers ──
    
    // "Thanh toán" button - go directly to step 3
    document.getElementById('btnPaymentOnly').addEventListener('click', function (e) {
        e.preventDefault();
        currentPath = 'payment';
        goToStep(3);
    });

    // "Trả hàng" button - go to step 1 and auto-open return section
    document.getElementById('btnPartialReturn').addEventListener('click', function (e) {
        e.preventDefault();
        currentPath = 'return';
        // Auto-check checkbox and activate hidden value
        const cb = document.getElementById('hasPartialReturn');
        const hiddenInput = document.getElementById('hasPartialReturnHidden');
        if (cb && !cb.checked) {
            cb.checked = true;
            if (hiddenInput) hiddenInput.value = '1';
            const section = document.getElementById('partialReturnSection');
            if (section) section.classList.add('open');
        }
        goToStep(1);
    });

    // Step 1 → Step 2
    document.getElementById('step1BackBtn').addEventListener('click', function (e) {
        e.preventDefault();
        goToStep(0);
    });

    // Step 1 → Step 2
    document.getElementById('step1ContinueBtn').addEventListener('click', function (e) {
        e.preventDefault();
        
        // Validate partial weight if partial return enabled
        if (document.getElementById('hasPartialReturn').checked) {
            let valid = true;
            document.querySelectorAll('.partial-weight-input').forEach(inp => {
                if (!inp.value || isNaN(parseFloat(inp.value))) {
                    valid = false;
                }
            });
            if (!valid) {
                alert('Vui lòng nhập số kg trả về cho tất cả sản phẩm');
                return;
            }
        }

        // Render order details
        renderOrderDetails();
        
        // Update cod display in step 3
        const codDisplay = document.getElementById('cod-display');
        const codDisplayStep3 = document.getElementById('cod-display-step3');
        if (codDisplayStep3 && codDisplay) {
            codDisplayStep3.textContent = codDisplay.textContent;
        }

        // Pre-fill collected amount in step 3
        const collectedInput = document.getElementById('collected_amount_input');
        const codValue = parseInt(codDisplay.textContent.replace(/[^0-9]/g, ''));
        if (collectedInput) {
            collectedInput.value = codValue;
        }

        goToStep(2);
    });

    // Step 2 → Step 3
    document.getElementById('step2ContinueBtn').addEventListener('click', function (e) {
        e.preventDefault();
        goToStep(3);
    });

    // Step 2 → Step 1
    document.getElementById('step2BackBtn').addEventListener('click', function (e) {
        e.preventDefault();
        goToStep(1);
    });

    // Step 3 → Back (to step 2 if return path, or step 0 if payment path)
    document.getElementById('step3BackBtn').addEventListener('click', function (e) {
        e.preventDefault();
        if (currentPath === 'payment') {
            goToStep(0);
        } else {
            goToStep(2);
        }
    });

    recalc();
})();
</script>
@endpush
@endsection
