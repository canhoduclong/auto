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
    .adjustment-table-head, .adjustment-table-row { display: grid; grid-template-columns: 48px minmax(180px,1.5fr) 90px 70px 90px 90px 110px; gap: 8px; align-items: center; min-width: 800px; }
    .adjustment-table-head { font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; color: #64748b; font-weight: 800; border-bottom: 1px solid #fde68a; padding-bottom: 8px; }
    .adjustment-table-row { padding: 10px 0; border-bottom: 1px solid #fde68a; }
    .adjustment-table-row:last-child { border-bottom: 0; }
    .adjustment-actions { border-top: 1px solid #fde68a; padding-top: 12px; }
    @media (max-width: 767.98px) {
        .adjustment-order-head { flex-direction: column; }
        .order-total-table-head { text-align: left; grid-template-columns: 1fr; }
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

                    <div class="wh-item-table-wrap">
                        <div class="adjustment-table-head">
                            <div>Ảnh</div><div>Sản phẩm</div><div class="text-center">SL</div><div class="text-center">Size</div><div class="text-center">Tổng</div><div class="text-center">Đơn giá</div><div class="text-end">Thành tiền</div>
                        </div>
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
                            <div class="adjustment-table-row">
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
                                    <input type="hidden" name="items[{{ $idx }}][adjusted_weight]" value="{{ old('items.' . $idx . '.adjusted_weight', $originalWeight) }}">
                                    <input type="hidden" name="items[{{ $idx }}][note]" value="{{ old('items.' . $idx . '.note') }}">
                                </div>
                                <div>
                                    <input type="number" min="0" name="items[{{ $idx }}][adjusted_quantity]" class="form-control form-control-sm adjustment-qty text-center" data-original-qty="{{ (int) ($item->quantity ?? 0) }}" value="{{ old('items.' . $idx . '.adjusted_quantity', (int) ($item->quantity ?? 0)) }}" required>
                                </div>
                                <div class="wh-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                <div class="wh-item-cell"><strong>{{ $item->display_total_label }}</strong></div>
                                <div>
                                    <input type="number" min="0" step="0.01" name="items[{{ $idx }}][adjusted_price]" class="form-control form-control-sm text-end" value="{{ old('items.' . $idx . '.adjusted_price', (float) ($item->price ?? 0)) }}" required>
                                </div>
                                <div class="wh-item-cell text-end"><strong>{{ number_format((float) ($item->total ?? ((float) $item->quantity * (float) $item->price)), 0, ',', '.') }}đ</strong></div>
                            </div>
                        @endforeach
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
    const qtyInputs = Array.from(document.querySelectorAll('.adjustment-qty'));
    const wrap = document.getElementById('returnWarehouseWrap');

    const refresh = () => {
        const requiresReturn = qtyInputs.some((input) => Number(input.value || 0) < Number(input.dataset.originalQty || 0));
        if (wrap) wrap.style.display = requiresReturn ? 'block' : 'none';
    };

    qtyInputs.forEach((input) => input.addEventListener('input', refresh));
    refresh();
})();
</script>
@endpush
@endsection
