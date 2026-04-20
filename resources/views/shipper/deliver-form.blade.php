@extends('layouts.shipper')

@section('title', 'Xác nhận đã giao hàng')
@section('subtitle', 'Đơn #{{ $order->code }}')

@section('content')
@php
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
                                            <span class="packed-weight-badge" title="KL kho đã cân">{{ format_kg($packedWeight) }}</span>
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

                <form action="{{ route('shipper.mark-delivered', $order) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- ── Khách trả 1 phần hàng ── --}}
                    <div class="mb-3 p-3 rounded" style="background:#fff7ed;border:1px solid #fed7aa;">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="hasPartialReturn"
                                   {{ old('has_partial_return') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="hasPartialReturn" style="color:#9a3412;">
                                <i class="bi bi-arrow-return-left me-1"></i>Khách trả lại 1 phần hàng
                            </label>
                        </div>
                        <div class="partial-return-section mt-3 {{ old('has_partial_return') ? 'open' : '' }}" id="partialReturnSection">
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
                                @endphp
                                <div class="partial-row">
                                    <div class="fw-semibold" style="line-height:1.25;">
                                        {{ $item->variant?->name ?? $item->variant?->sku ?? 'Sản phẩm' }}
                                        <div class="text-muted" style="font-weight:400;font-size:.75rem;">tổng: {{ $qty }}{{ $pricedByKg ? ' – '.format_kg($defaultTotalWeight) : '' }}</div>
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
                                               data-default-weight="{{ $defaultTotalWeight }}">
                                    </div>
                                    {{-- Tổng KL giao --}}
                                    <div class="partial-field">
                                        @if($pricedByKg)
                                            <input type="number"
                                                   name="partial_weight[{{ $item->id }}]"
                                                   id="pw-{{ $item->id }}"
                                                   class="partial-weight-input"
                                                   value="{{ old('partial_weight.'.$item->id, $defaultTotalWeight) }}"
                                                   step="0.001" min="0" max="{{ $defaultTotalWeight }}"
                                                   data-item-id="{{ $item->id }}"
                                                   data-default-weight="{{ $defaultTotalWeight }}">
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
                    </div>

                    {{-- ── Số tiền đã thu ── --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số tiền đã thu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₫</span>
                            <input type="number" name="collected_amount" id="collected_amount_input" class="form-control"
                                   value="{{ old('collected_amount', $order->total) }}"
                                   step="1000" min="0" required>
                        </div>
                        <div class="form-text text-muted">COD gốc: {{ number_format($order->total) }}đ – Số tiền sẽ tự cập nhật khi điều chỉnh</div>
                    </div>

                    {{-- ── Phương thức thanh toán ── --}}
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

                    {{-- ── Ảnh cân hàng (chỉ hiện khi có sản phẩm tính theo kg) ── --}}
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

                    {{-- ── Ảnh xác nhận giao hàng ── --}}
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

                    <div class="d-flex gap-2">
                        <a href="{{ route('shipper.my-orders') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Quay lại
                        </a>
                        <button type="submit" class="btn btn-success flex-fill">
                            <i class="bi bi-check-circle me-1"></i>Xác nhận đã giao hàng
                        </button>
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
            defaultWeight: {{ round($item->effective_unit_weight * $item->quantity, 3) }},
        },
        @endforeach
    };

    function formatVnd(n) {
        return Math.round(n).toLocaleString('vi-VN') + 'đ';
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
        if (!inp) return itemData[id].defaultWeight;
        const v = parseFloat(inp.value);
        return isNaN(v) ? 0 : Math.max(0, v);
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

    // Partial return: total weight inputs — validate & warn if over max
    document.querySelectorAll('.partial-weight-input').forEach(function (inp) {
        inp.addEventListener('input', function () {
            const maxW  = parseFloat(this.dataset.defaultWeight ?? 0);
            const v     = parseFloat(this.value);
            const errId = 'pw-err-' + this.dataset.itemId;
            let errEl   = document.getElementById(errId);
            if (!errEl) {
                errEl = document.createElement('small');
                errEl.id = errId;
                errEl.style.cssText = 'color:#dc2626;display:block;font-size:0.75rem;margin-top:2px';
                this.parentNode.appendChild(errEl);
            }
            if (!isNaN(v) && v > maxW && maxW > 0) {
                errEl.textContent = 'Tối đa ' + maxW + ' kg';
                this.style.borderColor = '#dc2626';
            } else {
                errEl.textContent = '';
                this.style.borderColor = '';
            }
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
                const defaultW = parseFloat(this.dataset.defaultWeight ?? pwInp.dataset.defaultWeight ?? 0);
                const scaled = max > 0 ? Math.round(defaultW * (v / max) * 1000) / 1000 : 0;
                pwInp.value = scaled;
            }

            // Return badge
            const badge = document.getElementById('return-badge-' + id);
            if (badge) {
                if (v < max) {
                    badge.classList.remove('d-none');
                    const returnedLabel = this.dataset.pricedByKg === '1'
                        ? 'trả ' + (max - v) + ' con'
                        : 'trả ' + (max - v);
                    badge.textContent = returnedLabel;
                } else {
                    badge.classList.add('d-none');
                }
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

    // Init return badges from old() values
    document.querySelectorAll('.delivered-qty-input').forEach(function (inp) {
        const id  = parseInt(inp.dataset.itemId, 10);
        const max = parseInt(inp.dataset.maxQty, 10);
        const v   = parseInt(inp.value, 10);
        const badge = document.getElementById('return-badge-' + id);
        if (badge && !isNaN(v) && v < max) {
            badge.classList.remove('d-none');
            badge.textContent = 'trả ' + (max - v);
        }
    });

    recalc();
})();
</script>
@endpush
@endsection
