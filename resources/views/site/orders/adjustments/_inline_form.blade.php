@php
    $items = $order->items ?? collect();
    $uid = 'adjustment-' . $order->id;
@endphp

<form class="monitor-adjustment-form" data-monitor-adjustment-form
      data-variant-url="{{ route('site.orders.variants.ajax') }}"
      action="{{ route('site.order-adjustments.store', $order) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="action" value="submit">
    <div class="monitor-adjustment-heading">
        <div>
            <strong><i class="bi bi-arrow-left-right me-1"></i>Yêu cầu chỉnh sửa</strong>
            <span>{{ $order->code ?: ('#' . $order->id) }} · {{ $order->customer?->name ?? 'Khách hàng' }}</span>
        </div>
        <button type="button" class="btn-close monitor-adjustment-close" aria-label="Đóng"></button>
    </div>

    <div class="monitor-adjustment-fields">
        <div><label>Khách hàng</label><input class="form-control form-control-sm" value="{{ $order->customer?->name }}{{ $order->customer?->phone ? ' · '.$order->customer->phone : '' }}" readonly></div>
        <div><label>Giờ giao hàng</label><input name="delivery_time" class="form-control form-control-sm" value="{{ $order->delivery_time ?: ($order->customer?->delivery_time ?: '') }}" placeholder="Chưa cập nhật"></div>
        <div><label>Họ tên người nhận</label><input name="recipient_name" class="form-control form-control-sm" value="{{ $order->recipient_name ?: $order->customer?->name }}"></div>
        <div><label>Số điện thoại</label><input name="recipient_phone" class="form-control form-control-sm" value="{{ $order->recipient_phone ?: $order->customer?->phone }}"></div>
    </div>

    <div class="table-responsive mt-3 monitor-adjustment-items-wrap">
        <table class="table table-sm align-middle monitor-adjustment-items mb-0">
            <thead><tr><th>Sản phẩm</th><th>SL mới</th><th>Size</th><th>Khối lượng mới (kg)</th><th>Đơn giá mới</th><th class="text-end">Thành tiền</th><th></th></tr></thead>
            <tbody data-adjustment-items>
                @foreach($items as $index => $item)
                    @php
                        $variant = $item->variant;
                        $product = $item->product ?? $variant?->product;
                        $weight = (float) ($item->actual_weight ?? $item->total_weight ?? $item->display_total_value ?? 0);
                        $lineTotal = (float) ($item->total ?: ((float) $item->quantity * (float) $item->price));
                    @endphp
                    <tr data-adjustment-item data-variant-id="{{ $variant?->id }}" data-original-qty="{{ (int) $item->quantity }}" data-priced-by-kg="{{ $item->effective_priced_by_kg ? 1 : 0 }}">
                        <td><strong>{{ $product?->name ?? $variant?->name ?? 'Sản phẩm' }}</strong><small>{{ $variant?->sku }}</small>
                            <input type="hidden" name="items[{{ $index }}][order_item_id]" value="{{ $item->id }}">
                            <input type="hidden" name="items[{{ $index }}][note]" value="">
                        </td>
                        <td><input type="number" min="0" class="form-control form-control-sm adjustment-qty" name="items[{{ $index }}][adjusted_quantity]" value="{{ (int) $item->quantity }}" required></td>
                        <td>{{ $variant?->size ?: '—' }}</td>
                        <td><input type="number" min="0" step="0.001" class="form-control form-control-sm adjustment-weight" name="items[{{ $index }}][adjusted_weight]" value="{{ $weight }}" required></td>
                        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm adjustment-price" name="items[{{ $index }}][adjusted_price]" value="{{ (float) $item->price }}" required></td>
                        <td class="text-end fw-bold adjustment-line-total">{{ number_format($lineTotal, 0, ',', '.') }}đ</td><td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="monitor-adjustment-details">
        <div><label for="{{ $uid }}-note">Ghi chú điều chỉnh</label><textarea id="{{ $uid }}-note" name="adjustment_note" class="form-control" rows="3" placeholder="Mô tả lý do điều chỉnh, thay đổi..."></textarea></div>
        <div><label for="{{ $uid }}-images">Hình ảnh minh chứng</label><input id="{{ $uid }}-images" type="file" name="evidence_images[]" class="form-control" accept="image/*" multiple></div>
    </div>

    <div class="monitor-adjustment-return" hidden>
        <label>Kho trả hàng (bắt buộc khi giảm số lượng)</label>
        <select name="return_warehouse_id" class="form-select form-select-sm"><option value="">-- Chọn kho --</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select>
    </div>

    <div class="monitor-adjustment-add-actions">
        <button type="button" class="btn btn-sm btn-outline-success monitor-adjustment-products-toggle"><i class="bi bi-plus-circle me-1"></i>Bổ sung hàng thiếu</button>
        <button type="button" class="btn btn-sm btn-outline-primary monitor-adjustment-fees-toggle"><i class="bi bi-plus-circle me-1"></i>Thêm phí</button>
    </div>

    <div class="monitor-adjustment-picker monitor-adjustment-product-picker" hidden>
        <div class="input-group input-group-sm"><input type="search" class="form-control monitor-adjustment-product-search" placeholder="Tìm sản phẩm..."><button type="button" class="btn btn-primary monitor-adjustment-product-search-button">Tìm</button></div>
        <div class="monitor-adjustment-product-results mt-2"></div>
    </div>

    <div class="monitor-adjustment-picker monitor-adjustment-fee-picker" hidden>
        <div class="monitor-adjustment-picker-head">
            <div>
                <strong><i class="bi bi-receipt me-1"></i>Các phí và giảm trừ</strong>
                <span>Chọn khoản cần điều chỉnh và nhập giá trị mới.</span>
            </div>
            <span class="monitor-adjustment-picker-hint"><i class="bi bi-check2-square me-1"></i>Chọn để kích hoạt</span>
        </div>
        <div class="monitor-adjustment-fee-list">
            @forelse($feeTypes as $feeType)
                @php
                    $state = $feeStates[$feeType->id] ?? ['enabled' => false, 'value' => (float) $feeType->default_value];
                    $enabled = (bool) $state['enabled'];
                    $percent = $feeType->code !== 'vat' && $feeType->calculation_type === 'percent';
                    $currentValue = array_key_exists('value', $state) ? (float) $state['value'] : (float) $feeType->default_value;
                    $unit = $percent ? '%' : 'đ';
                    $formattedCurrentValue = $percent
                        ? number_format($currentValue, 2, ',', '.') . '%'
                        : number_format($currentValue, 0, ',', '.') . 'đ';
                @endphp
                <div class="monitor-adjustment-fee-row {{ $enabled ? 'is-enabled' : '' }}">
                    <input type="hidden" name="fees[{{ $feeType->id }}][type_id]" value="{{ $feeType->id }}">
                    <input type="hidden" name="fees[{{ $feeType->id }}][enabled]" value="0">
                    <div class="monitor-adjustment-fee-identity">
                        <input id="{{ $uid }}-fee-{{ $feeType->id }}" type="checkbox" class="form-check-input monitor-adjustment-fee-enabled" name="fees[{{ $feeType->id }}][enabled]" value="1" @checked($enabled)>
                        <label for="{{ $uid }}-fee-{{ $feeType->id }}" class="monitor-adjustment-fee-label">
                            <span class="monitor-adjustment-fee-name">
                                <strong>{{ $feeType->name }}</strong>
                                <small>Hiện tại: {{ $formattedCurrentValue }}</small>
                            </span>
                            <span class="monitor-adjustment-fee-direction {{ $feeType->direction === 'discount' ? 'is-discount' : 'is-charge' }}">
                                {{ $feeType->direction === 'discount' ? 'Giảm trừ' : 'Cộng thêm' }}
                            </span>
                        </label>
                    </div>
                    <div class="monitor-adjustment-fee-control">
                        <label for="{{ $uid }}-fee-value-{{ $feeType->id }}">Giá trị mới</label>
                        <div class="input-group input-group-sm monitor-adjustment-fee-value">
                            <input id="{{ $uid }}-fee-value-{{ $feeType->id }}" type="number" min="0" @if($percent) max="100" @endif step="{{ $percent ? '0.01' : '1' }}" name="fees[{{ $feeType->id }}][value]" value="{{ $currentValue }}" class="form-control" @disabled(!$enabled)>
                            <span class="input-group-text">{{ $unit }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted small">Chưa có loại phí đang hoạt động.</div>
            @endforelse
        </div>
    </div>

    <div class="monitor-adjustment-errors alert alert-danger py-2 mt-3 mb-0" hidden></div>
    <div class="monitor-adjustment-submit">
        <button type="submit" class="btn btn-warning fw-bold" data-adjustment-action="submit"><i class="bi bi-send me-1"></i>Gửi yêu cầu phê duyệt</button>
    </div>
</form>
