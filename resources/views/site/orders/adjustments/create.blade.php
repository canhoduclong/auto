@extends('layouts.site')

@php
    $items = $order->items ?? collect();
    $totalQty = (float) $items->sum('quantity');
    $deliveryAddress = $order->recipient_address
        ?: ($order->customer?->addresses?->firstWhere('is_default', 1)?->note
            ?: ($order->customer?->address ?: 'Chưa có địa chỉ'));
    $wardLine = $order->customer?->addresses?->firstWhere('is_default', 1)?->ward;
    $cityLine = $order->customer?->addresses?->firstWhere('is_default', 1)?->city;
    $deliveryTime = $order->delivery_time ?: ($order->customer?->delivery_time ?: 'Chưa cập nhật');
@endphp

@push('styles')
<style>
    .adjustment-order-card { border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; box-shadow: 0 2px 10px rgba(15,23,42,.08); }
    .adjustment-order-head { display: flex; justify-content: space-between; gap: 14px; padding: 16px; border-bottom: 1px solid #eef2f7; }
    .orders-code { font-weight: 800; color: #1e293b; font-size: 1.05rem; }
    .wh-meta-label { font-size: .72rem; color: #64748b; text-transform: uppercase; letter-spacing: .03em; }
    .wh-meta-value { font-size: .92rem; font-weight: 800; color: #0f172a; }
    .order-total-table-head { display: grid; grid-template-columns: repeat(3, minmax(90px, 1fr)); gap: 12px; text-align: right; }
    .wh-section { padding: 14px 16px; border-bottom: 1px dashed #e2e8f0; }
    .wh-logistics-title { font-size: .78rem; font-weight: 800; color: #334155; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .03em; }
    .wh-item-table-wrap { overflow-x: auto; }
    .wh-item-table-head, .wh-item-table-row { display: grid; grid-template-columns: 48px minmax(180px, 1.5fr) 60px 70px 90px 90px 110px; gap: 8px; align-items: center; min-width: 760px; }
    .wh-item-table-head { font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; color: #64748b; font-weight: 800; border-bottom: 1px solid #e2e8f0; padding-bottom: 7px; }
    .wh-item-list { list-style: none; padding: 0; margin: 8px 0 0; display: grid; gap: 6px; }
    .wh-item-row { border-bottom: 1px solid #f1f5f9; padding-bottom: 6px; }
    .wh-item-thumb, .wh-item-thumb-placeholder { width: 40px; height: 40px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; }
    .wh-item-thumb { object-fit: cover; display: block; }
    .wh-item-thumb-placeholder { display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; border-style: dashed; }
    .wh-item-name { font-size: .86rem; font-weight: 800; color: #0f172a; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wh-item-cell { font-size: .82rem; color: #334155; text-align: center; white-space: nowrap; }
    .wh-item-cell strong { color: #0f172a; }
    .status-pill { border-radius: 999px; padding: 8px 12px; font-weight: 800; font-size: .82rem; background: #fef2f2; color: #b91c1c; }
    .adjustment-panel { margin: 0 16px 16px; border: 1px solid #f59e0b; border-radius: 8px; background: #fffbeb; padding: 14px; }
    .adjustment-table-head, .adjustment-table-row { display: grid; grid-template-columns: 48px minmax(180px,1.5fr) 90px 70px 120px 90px 110px; gap: 8px; align-items: center; min-width: 830px; }
    .adjustment-table-head { font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; color: #64748b; font-weight: 800; border-bottom: 1px solid #fde68a; padding-bottom: 8px; }
    .adjustment-table-row { padding: 10px 0; border-bottom: 1px solid #fde68a; }
    .adjustment-table-row:last-child { border-bottom: 0; }
    .adjustment-actions { border-top: 1px solid #fde68a; padding-top: 12px; }
    .adjustment-fees { margin-bottom: 16px; padding: 14px; border: 1px solid #fed7aa; border-radius: 9px; background: #fff; }
    .adjustment-fee-picker { display: flex; gap: 8px; max-width: 620px; margin-bottom: 12px; }
    .adjustment-fee-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px; }
    .adjustment-fee-card { padding: 12px; border: 1px solid #e2e8f0; border-radius: 9px; background: #f8fafc; transition: .15s ease; }
    .adjustment-fee-card.is-enabled { border-color: #f59e0b; background: #fffbeb; }
    .adjustment-fee-card .input-group { margin-top: 9px; }
    .adjustment-fee-current { margin-top: 5px; color: #64748b; font-size: .75rem; }
    .missing-item-picker { margin-top: 16px; padding: 14px; border: 1px dashed #f59e0b; border-radius: 8px; background: #fff; }
    .variant-picker-toolbar, .variant-picker-item, .variant-picker-main, .variant-picker-actions, .variant-picker-stats { display: flex; align-items: center; gap: 10px; }
    .variant-picker-toolbar { justify-content: space-between; margin-bottom: 10px; }
    .variant-picker-list { display: grid; gap: 8px; }
    .variant-picker-item { justify-content: space-between; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    .variant-picker-main { min-width: 220px; flex: 1; }
    .variant-picker-thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 7px; border: 1px solid #e2e8f0; }
    .variant-picker-name { font-weight: 700; color: #0f172a; }
    .variant-picker-meta { display: flex; flex-wrap: wrap; gap: 6px; color: #64748b; font-size: .75rem; }
    .variant-picker-stats { font-size: .8rem; }
    .variant-picker-stats > div { display: grid; min-width: 75px; }
    .variant-picker-label { color: #64748b; font-size: .7rem; }
    .monitor-product-list { display: grid; gap: 9px; }
    .monitor-product-card { overflow: hidden; border: 1px solid #dbe4ee; border-radius: 9px; background: #fff; }
    .monitor-product-choice { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border: 0; background: #fff; text-align: left; }
    .monitor-product-choice:hover { background: #f8fafc; }
    .monitor-product-main { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .monitor-product-thumb { width: 48px; height: 48px; flex: 0 0 48px; object-fit: cover; border: 1px solid #e2e8f0; border-radius: 8px; }
    .monitor-product-name { display: block; color: #0f172a; }
    .monitor-product-meta { display: block; color: #64748b; font-size: .76rem; }
    .monitor-product-choice-label { flex-shrink: 0; color: #0f766e; font-size: .8rem; font-weight: 700; }
    .monitor-product-variants { padding: 10px 12px 12px; border-top: 1px solid #e2e8f0; background: #f8fafc; }
    .monitor-variant-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; }
    .monitor-variant-option { display: grid; gap: 3px; padding: 9px 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #334155; text-align: left; }
    .monitor-variant-option:hover { border-color: #0f766e; background: #ecfdf5; }
    .monitor-variant-size { color: #0f172a; font-weight: 800; }
    .monitor-variant-option small { display: block; color: #64748b; font-size: .7rem; }
    .monitor-variant-availability.is-available { color: #15803d; }
    .monitor-variant-availability.is-unavailable { color: #b91c1c; }
    .monitor-product-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px; }
    .new-adjustment-item { background: #f0fdf4; }
    .new-item-badge { display: inline-block; margin-top: 3px; padding: 2px 6px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: .68rem; font-weight: 700; }
    @media (max-width: 767.98px) {
        .adjustment-order-head { flex-direction: column; }
        .order-total-table-head { text-align: left; grid-template-columns: 1fr; }
        .variant-picker-item { align-items: flex-start; flex-direction: column; }
        .variant-picker-actions { width: 100%; justify-content: flex-end; }
    }
</style>
@endpush

@section('content')
<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h3 class="mb-1 fw-bold">Yêu cầu chỉnh sửa</h3>
                <div class="text-muted">{{ $order->code ?: ('#' . $order->id) }}</div>
            </div>
            <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">Quay lại chi tiết đơn</a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('site.order-adjustments.store', $order) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="adjustment-order-card">
                <div class="adjustment-order-head">
                    <div>
                        <div class="orders-code">{{ $order->customer?->name ?? '—' }}</div>
                        <small class="text-muted">
                            {{ optional($order->created_at)->format('d/m/Y H:i') ?: '—' }}
                            @if($order->customer?->phone)
                                <i class="bi bi-telephone ms-1 me-1"></i>{{ $order->customer->phone }}
                            @endif
                        </small>
                    </div>
                    <div class="order-total-table-head">
                        <div>
                            <div class="wh-meta-label">Mã KH</div>
                            <div class="wh-meta-value">{{ $order->customer?->customer_code ?? ('#' . ($order->customer?->id ?? '')) }}</div>
                        </div>
                        <div>
                            <div class="wh-meta-label">Tổng số lượng</div>
                            <div class="wh-meta-value">{{ rtrim(rtrim(number_format($totalQty, 3, '.', ''), '0'), '.') }}</div>
                        </div>
                        <div>
                            <div class="wh-meta-label">Thành tiền</div>
                            <div class="wh-meta-value text-primary">{{ number_format((float) $order->total, 0, ',', '.') }}đ</div>
                        </div>
                    </div>
                </div>

                <div class="wh-section">
                    <div class="wh-logistics-title">Giao hàng</div>
                    <div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>Địa chỉ nhận hàng: {{ $deliveryAddress }}</div>
                    @if($wardLine || $cityLine)
                        <div class="small text-muted mb-1"><i class="bi bi-pin-map me-1"></i>Khu vực: {{ collect([$wardLine, $cityLine])->filter()->implode(', ') }}</div>
                    @endif
                    <div class="small text-muted"><i class="bi bi-clock me-1"></i>Giờ giao: {{ $deliveryTime }}</div>
                </div>

                <div class="wh-section">
                    <div class="wh-logistics-title">Danh sách sản phẩm</div>
                    <div class="wh-item-table-wrap">
                        <div class="wh-item-table-head">
                            <div>Ảnh</div><div>Sản phẩm</div><div class="text-center">SL</div><div class="text-center">Size</div><div class="text-center">Tổng</div><div class="text-center">Đơn giá</div><div class="text-end">Thành tiền</div>
                        </div>
                        <ul class="wh-item-list">
                            @foreach($items as $item)
                                @php
                                    $variant = $item->variant;
                                    $product = $item->product ?? $variant?->product;
                                    $productName = $variant?->name ?? $product?->name ?? 'Sản phẩm';
                                    $qty = (float) ($item->quantity ?? 0);
                                    $unitPrice = (float) ($item->price ?? 0);
                                    $lineTotal = (float) ($item->total ?? 0);
                                    if ($lineTotal <= 0) $lineTotal = $qty * $unitPrice;
                                    $variantSize = $variant?->size;
                                    $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '') ? rtrim(rtrim(number_format((float) $variantSize, 2, '.', ''), '0'), '.') : '-';
                                    $imagePath = $variant?->avatar?->media?->file_path ?? $product?->avatar?->media?->file_path ?? null;
                                @endphp
                                <li class="wh-item-row">
                                    <div class="wh-item-table-row">
                                        <div>
                                            @if($imagePath)
                                                <img class="wh-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $productName }}">
                                            @else
                                                <span class="wh-item-thumb-placeholder"><i class="bi bi-image"></i></span>
                                            @endif
                                        </div>
                                        <div class="wh-item-name">
                                            {{ $productName }}
                                            @if($variant?->sku)<span class="text-muted small">({{ $variant->sku }})</span>@endif
                                        </div>
                                        <div class="wh-item-cell"><strong>{{ rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') }}</strong></div>
                                        <div class="wh-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                        <div class="wh-item-cell"><strong>{{ $item->display_total_label }}</strong></div>
                                        <div class="wh-item-cell">{{ number_format($unitPrice, 0, ',', '.') }}đ</div>
                                        <div class="wh-item-cell text-end"><strong>{{ number_format($lineTotal, 0, ',', '.') }}đ</strong></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap px-3 py-3">
                    <div class="small">{{ $order->code }}</div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-pill"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i>Chờ Kho Ráp Hàng</span>
                        <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>Chi tiết</a>
                        <a href="{{ route('site.orders.copy', $order->id) }}"
                           class="btn btn-outline-secondary btn-sm"
                           onclick="return confirm('Copy đơn #{{ $order->code }} để tạo đơn mới?')">
                            <i class="bi bi-files me-1"></i>Copy Đơn
                        </a>
                    </div>
                </div>

                <div class="adjustment-panel">
                    <div class="text-warning fw-semibold mb-1">Yêu cầu chỉnh sửa</div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Ghi chú điều chỉnh</label>
                            <textarea name="adjustment_note" class="form-control" rows="2" placeholder="Mô tả lý do điều chỉnh, thay đổi giá/số lượng/cân ký...">{{ old('adjustment_note') }}</textarea>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Hình ảnh minh chứng</label>
                            <input type="file" name="evidence_images[]" class="form-control" accept="image/*" multiple>
                        </div>
                        <div class="col-12" id="returnWarehouseWrap" style="display:none;">
                            <label class="form-label small fw-semibold">Kho trả hàng (bắt buộc khi giảm số lượng)</label>
                            <select name="return_warehouse_id" id="return_warehouse_id" class="form-select">
                                <option value="">-- Chọn kho --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected((string) old('return_warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="adjustment-fees">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <div class="fw-bold"><i class="bi bi-receipt me-1"></i>Phí và chiết khấu áp dụng cho đơn</div>
                                <div class="text-muted small">Chọn phí từ danh mục do admin cấu hình để gắn vào đơn.</div>
                            </div>
                            <span class="badge text-bg-light border">Giá trị sau điều chỉnh</span>
                        </div>
                        <div class="adjustment-fee-picker">
                            <select id="order-fee-picker" class="form-select form-select-sm" @disabled($feeTypes->where('is_active', true)->isEmpty())>
                                <option value="">-- Chọn phí cần thêm --</option>
                                @foreach($feeTypes->where('is_active', true) as $feeType)
                                    @php
                                        $pickerState = $feeStates[$feeType->id] ?? ['enabled' => false];
                                        $pickerEnabled = (bool) old('fees.'.$feeType->id.'.enabled', $pickerState['enabled']);
                                    @endphp
                                    <option value="{{ $feeType->id }}" @disabled($pickerEnabled)>{{ $feeType->name }} · {{ $feeType->direction === 'discount' ? 'Giảm trừ' : 'Cộng thêm' }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" id="add-order-fee" @disabled($feeTypes->where('is_active', true)->isEmpty())><i class="bi bi-plus-circle me-1"></i>Thêm phí</button>
                        </div>
                        <div class="adjustment-fee-grid" id="selected-order-fees">
                            @forelse($feeTypes as $feeType)
                                @php
                                    $feeState = $feeStates[$feeType->id] ?? ['enabled' => false, 'value' => (float) $feeType->default_value];
                                    $feeEnabled = (bool) old('fees.'.$feeType->id.'.enabled', $feeState['enabled']);
                                    $feeValue = old('fees.'.$feeType->id.'.value', $feeState['value'] ?: $feeType->default_value);
                                    $isPercent = $feeType->calculation_type === 'percent';
                                    $currentFeeText = $feeState['enabled']
                                        ? ($isPercent ? rtrim(rtrim(number_format((float) $feeState['value'], 2, '.', ''), '0'), '.').'%' : number_format((float) $feeState['value'], 0, ',', '.').'đ')
                                        : 'Không áp dụng';
                                @endphp
                                <div class="adjustment-fee-card {{ $feeEnabled ? 'is-enabled' : '' }}" data-fee-card data-fee-id="{{ $feeType->id }}" @if(!$feeEnabled) hidden @endif>
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <input type="hidden" name="fees[{{ $feeType->id }}][type_id]" value="{{ $feeType->id }}">
                                            <input type="hidden" class="adjustment-fee-enabled" name="fees[{{ $feeType->id }}][enabled]" value="{{ $feeEnabled ? 1 : 0 }}">
                                            <div class="fw-semibold">{{ $feeType->name }}</div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge {{ $feeType->direction === 'discount' ? 'text-bg-danger' : 'text-bg-success' }}">{{ $feeType->direction === 'discount' ? 'Giảm trừ' : 'Cộng thêm' }}</span>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-order-fee" title="Loại phí khỏi đơn"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="number" min="0" @if($isPercent) max="100" @endif step="0.01" class="form-control adjustment-fee-value" name="fees[{{ $feeType->id }}][value]" value="{{ $feeValue }}" aria-label="Giá trị {{ $feeType->name }}">
                                        <span class="input-group-text">{{ $isPercent ? '%' : 'đ' }}</span>
                                    </div>
                                    <div class="adjustment-fee-current">Hiện tại: {{ $currentFeeText }}@if(!$feeType->is_active) · <span class="text-danger">đã ngừng dùng</span>@endif</div>
                                    @if($feeType->description)<div class="adjustment-fee-current">{{ $feeType->description }}</div>@endif
                                </div>
                            @empty
                                <div class="text-muted small">Admin chưa cấu hình loại phí nào đang hoạt động.</div>
                            @endforelse
                        </div>
                        <div id="selected-order-fees-empty" class="text-muted small py-2" @if($feeTypes->contains(fn ($type) => (bool) old('fees.'.$type->id.'.enabled', ($feeStates[$type->id]['enabled'] ?? false)))) hidden @endif>Chưa có phí nào được gắn vào đơn.</div>
                    </div>

                    <div class="wh-item-table-wrap">
                        <div class="adjustment-table-head">
                            <div>Ảnh</div><div>Sản phẩm</div><div class="text-center">SL</div><div class="text-center">Size</div><div class="text-center">Khối lượng mới (kg)</div><div class="text-center">Đơn giá</div><div class="text-end">Thành tiền</div>
                        </div>
                        <div id="adjustmentItemsContainer">
                        @foreach($items as $idx => $item)
                            @php
                                $variant = $item->variant;
                                $product = $item->product ?? $variant?->product;
                                $productName = $variant?->name ?? $product?->name ?? 'Sản phẩm';
                                $originalWeight = (float) ($item->actual_weight ?? $item->total_weight ?? $item->display_total_value ?? 0);
                                $imagePath = $variant?->avatar?->media?->file_path ?? $product?->avatar?->media?->file_path ?? null;
                                $variantSize = $variant?->size;
                                $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '') ? rtrim(rtrim(number_format((float) $variantSize, 2, '.', ''), '0'), '.') : '-';
                            @endphp
                            <div class="adjustment-table-row" data-adjustment-variant-id="{{ $variant?->id }}">
                                <div>
                                    @if($imagePath)
                                        <img class="wh-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $productName }}">
                                    @else
                                        <span class="wh-item-thumb-placeholder"><i class="bi bi-image"></i></span>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $productName }}</div>
                                    <div class="text-muted small">{{ $variant?->sku ?: '' }}</div>
                                    <input type="hidden" name="items[{{ $idx }}][order_item_id]" value="{{ $item->id }}">
                                    <input type="hidden" name="items[{{ $idx }}][note]" value="{{ old('items.' . $idx . '.note') }}">
                                </div>
                                <div>
                                    <input type="number" min="0" name="items[{{ $idx }}][adjusted_quantity]" class="form-control form-control-sm adjustment-qty text-center" data-original-qty="{{ (int) ($item->quantity ?? 0) }}" value="{{ old('items.' . $idx . '.adjusted_quantity', (int) ($item->quantity ?? 0)) }}" required>
                                </div>
                                <div class="wh-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                <div>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.001"
                                        name="items[{{ $idx }}][adjusted_weight]"
                                        class="form-control form-control-sm text-center"
                                        value="{{ old('items.' . $idx . '.adjusted_weight', $originalWeight) }}"
                                        aria-label="Khối lượng điều chỉnh của {{ $productName }}"
                                        required
                                    >
                                    <div class="text-muted text-center mt-1" style="font-size:.7rem;">Gốc: {{ rtrim(rtrim(number_format($originalWeight, 3, '.', ''), '0'), '.') }} kg</div>
                                </div>
                                <div>
                                    <input type="number" min="0" step="0.01" name="items[{{ $idx }}][adjusted_price]" class="form-control form-control-sm text-end" value="{{ old('items.' . $idx . '.adjusted_price', (float) ($item->price ?? 0)) }}" required>
                                </div>
                                <div class="wh-item-cell text-end"><strong>{{ number_format((float) ($item->total ?? ((float) $item->quantity * (float) $item->price)), 0, ',', '.') }}đ</strong></div>
                            </div>
                        @endforeach
                        </div>
                    </div>

                    <div class="missing-item-picker">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="fw-bold"><i class="bi bi-plus-circle me-1"></i>Bổ sung hàng thiếu</div>
                                <div class="text-muted small">Chọn loại hàng chưa có trong đơn. Hàng thêm mới sẽ được chuyển Kho xác nhận cuối.</div>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" id="missing-item-show-all">Hiện tất cả sản phẩm</button>
                        </div>
                        <div class="input-group">
                            <input type="text" class="form-control" id="missing-item-search" placeholder="Tìm tên sản phẩm, SKU hoặc size...">
                            <button type="button" class="btn btn-outline-primary" id="missing-item-search-button">Tìm hàng</button>
                        </div>
                        <div id="missing-item-search-results" class="mt-3"></div>
                    </div>

                    <div class="adjustment-actions d-flex justify-content-end gap-2 mt-3">
                        <button type="submit" class="btn btn-outline-secondary" name="action" value="draft">Lưu nháp</button>
                        <button type="submit" class="btn btn-danger" name="action" value="submit">Gửi yêu cầu phê duyệt</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
(function () {
    const qtyInputs = () => Array.from(document.querySelectorAll('.adjustment-qty'));
    const wrap = document.getElementById('returnWarehouseWrap');
    const itemsContainer = document.getElementById('adjustmentItemsContainer');
    const searchInput = document.getElementById('missing-item-search');
    const searchButton = document.getElementById('missing-item-search-button');
    const showAllButton = document.getElementById('missing-item-show-all');
    const searchResults = document.getElementById('missing-item-search-results');
    const feePicker = document.getElementById('order-fee-picker');
    const addFeeButton = document.getElementById('add-order-fee');
    const selectedFees = document.getElementById('selected-order-fees');
    const selectedFeesEmpty = document.getElementById('selected-order-fees-empty');
    const searchUrl = @json(route('site.orders.variants.ajax'));
    let nextItemIndex = {{ $items->count() }};

    const refresh = () => {
        const requiresReturn = qtyInputs().some((input) => Number(input.value || 0) < Number(input.dataset.originalQty || 0));
        if (wrap) wrap.style.display = requiresReturn ? 'block' : 'none';
    };

    const refreshFeePicker = () => {
        const visibleIds = new Set(Array.from(document.querySelectorAll('[data-fee-card]:not([hidden])')).map((card) => card.dataset.feeId));
        Array.from(feePicker?.options || []).forEach((option) => {
            if (!option.value) return;
            option.disabled = visibleIds.has(option.value);
        });
        if (feePicker?.selectedOptions[0]?.disabled) feePicker.value = '';
        if (selectedFeesEmpty) selectedFeesEmpty.hidden = visibleIds.size > 0;
    };

    const setFeeEnabled = (card, enabled) => {
        if (!card) return;
        card.hidden = !enabled;
        card.classList.toggle('is-enabled', enabled);
        const enabledInput = card.querySelector('.adjustment-fee-enabled');
        if (enabledInput) enabledInput.value = enabled ? '1' : '0';
        refreshFeePicker();
    };

    const escapeHtml = (value) => {
        const node = document.createElement('div');
        node.textContent = String(value ?? '');
        return node.innerHTML;
    };

    const selectedVariantIds = () => Array.from(document.querySelectorAll('[data-adjustment-variant-id]'))
        .map((element) => String(element.dataset.adjustmentVariantId || ''))
        .filter(Boolean);

    const updateNewItem = (row) => {
        const quantity = Math.max(1, Number(row.querySelector('.adjustment-qty')?.value || 1));
        const weightInput = row.querySelector('.new-item-weight');
        const price = Math.max(0, Number(row.querySelector('.new-item-price')?.value || 0));
        const pricedByKg = row.dataset.pricedByKg === '1';
        if (weightInput?.dataset.autoWeight === '1') {
            weightInput.value = (quantity * Number(weightInput.dataset.unitWeight || 0)).toFixed(3);
        }
        const totalWeight = Math.max(0, Number(weightInput?.value || 0));
        const total = price * (pricedByKg ? totalWeight : quantity);
        const totalElement = row.querySelector('.new-item-total');
        if (totalElement) totalElement.textContent = new Intl.NumberFormat('vi-VN').format(Math.round(total)) + 'đ';
    };

    const addMissingItem = (button) => {
        const variantId = String(button.dataset.variantId || '');
        if (!variantId || selectedVariantIds().includes(variantId)) {
            window.alert('Loại hàng này đã có trong đơn. Hãy điều chỉnh số lượng ở dòng hiện có.');
            return;
        }

        const name = button.dataset.variantName || 'Sản phẩm';
        const sku = button.dataset.variantSku || '';
        const size = button.dataset.variantSize || '-';
        const price = Math.max(0, Number(button.dataset.variantPrice || 0));
        const unitWeight = Math.max(0, Number(button.dataset.variantWeight || 0));
        const image = button.dataset.variantImage || '';
        const pricedByKg = button.dataset.variantIsPricedByKg === '1';
        const index = nextItemIndex++;
        const row = document.createElement('div');
        row.className = 'adjustment-table-row new-adjustment-item';
        row.dataset.adjustmentVariantId = variantId;
        row.dataset.pricedByKg = pricedByKg ? '1' : '0';
        row.innerHTML = `
            <div>${image ? `<img class="wh-item-thumb" src="${escapeHtml(image)}" alt="${escapeHtml(name)}">` : '<span class="wh-item-thumb-placeholder"><i class="bi bi-image"></i></span>'}</div>
            <div>
                <div class="fw-semibold">${escapeHtml(name)}</div>
                <div class="text-muted small">${escapeHtml(sku)}</div>
                <span class="new-item-badge">Hàng bổ sung</span>
                <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-2 remove-new-adjustment-item">Xóa</button>
                <input type="hidden" name="items[${index}][order_item_id]" value="">
                <input type="hidden" name="items[${index}][product_variant_id]" value="${escapeHtml(variantId)}">
                <input type="hidden" name="items[${index}][note]" value="Bổ sung hàng thiếu trong đơn">
            </div>
            <div><input type="number" min="1" name="items[${index}][adjusted_quantity]" class="form-control form-control-sm adjustment-qty text-center" data-original-qty="0" value="1" required></div>
            <div class="wh-item-cell"><strong>${escapeHtml(size)}</strong></div>
            <div><input type="number" min="0" step="0.001" name="items[${index}][adjusted_weight]" class="form-control form-control-sm text-center new-item-weight" data-unit-weight="${unitWeight}" data-auto-weight="1" value="${unitWeight.toFixed(3)}" required></div>
            <div><input type="number" min="0" step="0.01" name="items[${index}][adjusted_price]" class="form-control form-control-sm text-end new-item-price" value="${price}" required></div>
            <div class="wh-item-cell text-end"><strong class="new-item-total">0đ</strong></div>
        `;
        itemsContainer.appendChild(row);
        updateNewItem(row);
        button.closest('.monitor-variant-option, .variant-picker-item')?.remove();
        refresh();
    };

    const loadVariants = async (pageUrl = null, showAll = false) => {
        const url = new URL(pageUrl || searchUrl, window.location.origin);
        if (!pageUrl) {
            url.searchParams.set('search', showAll ? '' : (searchInput?.value.trim() || ''));
            url.searchParams.set('per_page', '10');
            url.searchParams.set('exclude_ids', selectedVariantIds().join(','));
            url.searchParams.set('view', 'products');
        }
        searchResults.innerHTML = '<div class="text-muted small py-3">Đang tải danh sách sản phẩm...</div>';
        try {
            const response = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error('Không tải được sản phẩm');
            searchResults.innerHTML = payload.html;
        } catch (error) {
            searchResults.innerHTML = '<div class="alert alert-danger py-2 mb-0">Không tải được danh sách sản phẩm. Vui lòng thử lại.</div>';
        }
    };

    document.addEventListener('input', (event) => {
        if (event.target.matches('.adjustment-qty')) refresh();
        const row = event.target.closest('.new-adjustment-item');
        if (!row) return;
        if (event.target.matches('.new-item-weight')) event.target.dataset.autoWeight = '0';
        updateNewItem(row);
    });
    searchResults?.addEventListener('click', (event) => {
        const productChoice = event.target.closest('.monitor-product-choice');
        if (productChoice) {
            const variants = productChoice.closest('.monitor-product-card')?.querySelector('.monitor-product-variants');
            if (variants) {
                variants.hidden = !variants.hidden;
                productChoice.setAttribute('aria-expanded', variants.hidden ? 'false' : 'true');
                const icon = productChoice.querySelector('.bi-chevron-down, .bi-chevron-up');
                icon?.classList.toggle('bi-chevron-down', variants.hidden);
                icon?.classList.toggle('bi-chevron-up', !variants.hidden);
            }
            return;
        }
        const addButton = event.target.closest('.monitor-variant-option, .add-variant-to-cart');
        if (addButton) {
            event.preventDefault();
            addMissingItem(addButton);
            return;
        }
        const paginationLink = event.target.closest('.pagination a');
        if (paginationLink) {
            event.preventDefault();
            loadVariants(paginationLink.href);
        }
    });
    searchResults?.addEventListener('change', (event) => {
        if (event.target.matches('#per-page-select')) {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('search', searchInput?.value.trim() || '');
            url.searchParams.set('per_page', event.target.value || '10');
            url.searchParams.set('exclude_ids', selectedVariantIds().join(','));
            url.searchParams.set('view', 'products');
            loadVariants(url.toString());
        }
    });
    itemsContainer?.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-new-adjustment-item');
        if (removeButton) {
            removeButton.closest('.new-adjustment-item')?.remove();
            refresh();
        }
    });
    searchButton?.addEventListener('click', () => loadVariants());
    showAllButton?.addEventListener('click', () => loadVariants(null, true));
    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadVariants();
        }
    });
    addFeeButton?.addEventListener('click', () => {
        if (!feePicker?.value) return;
        setFeeEnabled(document.querySelector(`[data-fee-card][data-fee-id="${feePicker.value}"]`), true);
        feePicker.value = '';
    });
    feePicker?.addEventListener('change', () => {
        if (feePicker.value) addFeeButton?.focus();
    });
    selectedFees?.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-order-fee');
        if (removeButton) setFeeEnabled(removeButton.closest('[data-fee-card]'), false);
    });
    refreshFeePicker();

    refresh();
})();
</script>
@endpush
@endsection
