@php
    $customer = $order->customer;
    $address = $order->recipient_address ?: $customer?->address;
    $deliveryTime = $order->delivery_time ?: $customer?->delivery_time ?: 'Chưa cập nhật';
    $customerName = $customer?->name ?? $order->recipient_name;
    $priorityNumber = $order->daily_sequence ?: '—';
    $orderTotal = (float) ($order->total ?? 0);
    $selectedRoute = $customer?->truckRoute;
    if (!$selectedRoute && $customer?->truck_station_id) {
        $selectedRoute = $customer?->truckRouteByStation;
    }
    $truckStationName = $customer?->truckStation?->name
        ?: ($selectedRoute?->stops?->first()?->station?->name);
    $truckStationPhone = $customer?->truckStation?->phone
        ?: ($selectedRoute?->stops?->first()?->station?->phone);
    $truckStationAddress = $customer?->truckStation?->address
        ?: ($selectedRoute?->stops?->first()?->station?->address);
    $selectedRouteName = $selectedRoute?->name;
    $isReturnOrder = (bool) ($order->is_return_order ?? false)
        || (string) ($order->order_type ?? '') === 'order_return'
        || (string) ($order->workflow_code ?? '') === 'order_return';
@endphp
<div class="col-12">
    <div class="card ma-order-card p-2 {{ $isReturnOrder ? 'border border-danger border-2' : '' }}" style="min-height:unset; position: relative;">
        <style>
            .ma-product-section {
                border-top: 1px dashed #e2e8f0;
                padding-top: 8px;
                margin-top: 8px;
            }
            .ma-product-table-head,
            .ma-product-table-row {
                display: grid;
                grid-template-columns: minmax(0, 2fr) 36px 44px 34px 92px 110px;
                gap: 8px;
                align-items: center;
            }
            .ma-product-table-head {
                font-size: .72rem;
                text-transform: uppercase;
                letter-spacing: .03em;
                color: #64748b;
                font-weight: 700;
                padding: 0 0 4px;
                border-bottom: 1px solid #e2e8f0;
                margin-bottom: 6px;
            }
            .ma-product-list {
                list-style: none;
                margin: 0;
                padding: 0;
                display: grid;
                gap: 6px;
            }
            .ma-product-row {
                display: grid;
                gap: 4px;
                border-bottom: 1px solid #f1f5f9;
                padding-bottom: 6px;
            }
            .ma-product-row:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }
            .ma-product-name {
                font-size: .88rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.25;
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .ma-product-cell {
                font-size: .8rem;
                color: #475569;
                text-align: right;
            }
            .ma-product-cell strong {
                color: #0f172a;
            }
            .ma-priority-circle {
                width: 34px;
                height: 34px;
                border: 2px solid var(--theme-primary);
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 34px;
                font-weight: 800;
                color: var(--theme-primary);
                background: #fff;
            }
            .ma-delivery-time {
                min-width: 62px;
                color: var(--theme-primary);
                font-size: .95rem;
                font-weight: 700;
                line-height: 1.15;
                text-align: center;
            }
            .ma-shipper-actions {
                border-top: 1px dashed #cbd5e1;
                margin-top: 8px;
                padding-top: 8px;
            }
            .ma-assignment-picker-actions {
                min-width: 160px;
            }
            .ma-order-summary {
                min-width: 120px;
                text-align: right;
            }
            .ma-product-details > summary {
                list-style: none;
            }
            .ma-product-details > summary::-webkit-details-marker {
                display: none;
            }
            .ma-product-details .ma-hide-icon {
                display: none;
            }
            .ma-product-details[open] .ma-show-icon {
                display: none;
            }
            .ma-product-details[open] .ma-hide-icon {
                display: inline-block;
            }
            @media (max-width: 575px) {
                .ma-product-table-head,
                .ma-product-table-row {
                    grid-template-columns: minmax(0, 1.3fr) 40px 58px 74px 86px;
                    gap: 6px;
                }
            }
        </style>
        <div class="d-flex align-items-start justify-content-between gap-2">
            <div class="d-flex align-items-start gap-2 min-w-0 flex-grow-1">
                <span class="ma-delivery-time">{{ $deliveryTime }}</span>
                <span class="ma-priority-circle" title="Số thứ tự ưu tiên">{{ $priorityNumber }}</span>
                <div class="min-w-0">
                    <div class="fw-semibold text-dark">{{ $customerName }}</div>
                    <div class="text-muted small">{{ $address ? mb_substr($address, 0, 60) . (mb_strlen($address) > 60 ? '...' : '') : 'Chưa cập nhật' }}</div>
                </div>
            </div>
            <div class="ma-order-summary">
                <div class="fw-bold text-danger">{{ number_format($orderTotal) }}đ</div>
                @if($order->items->isNotEmpty())
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none"
                        onclick="const details = document.getElementById('products-{{ $order->id }}'); details.open = !details.open; this.querySelector('.bi-eye').classList.toggle('d-none', details.open); this.querySelector('.bi-eye-slash').classList.toggle('d-none', !details.open);"
                        title="Hiện/ẩn sản phẩm">
                        <i class="bi bi-eye"></i>
                        <i class="bi bi-eye-slash d-none"></i>
                    </button>
                @endif
            </div>
            @if(($showAssignmentButtons ?? false) === true)
                <div class="ma-assignment-picker-actions d-flex flex-column gap-1">
                    @if($customer?->defaultShipper)
                        <button type="button"
                            class="btn btn-sm btn-outline-success js-open-default-shipper-picker"
                            data-bs-toggle="modal"
                            data-bs-target="#defaultShipperPickerModal"
                            data-action="{{ route('shipper.customers.default-shipper.update', $customer) }}"
                            data-customer-name="{{ $customerName }}"
                            data-current-shipper-id="{{ $customer->default_shipper_id }}"
                            title="Chọn shipper cố định khác">
                            <i class="bi bi-person-check me-1"></i>{{ $customer->defaultShipper->name }}
                        </button>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                            <i class="bi bi-pin-angle me-1"></i>Gán lần đầu sẽ lưu cố định
                        </span>
                    @endif
                    <button type="button"
                        class="btn btn-sm btn-primary js-open-shipper-picker"
                        data-bs-toggle="modal"
                        data-bs-target="#shipperPickerModal"
                        data-action="{{ route('shipper.assign-order.selected', $order) }}"
                        data-order-code="{{ $order->code ?: $order->id }}"
                        data-customer-name="{{ $customerName }}"
                        data-set-default="{{ $customer?->default_shipper_id ? '0' : '1' }}">
                        <i class="bi bi-person-check me-1"></i>Chọn shipper
                    </button>
                </div>
            @endif
        </div>
        <div>
                @if($isReturnOrder)
                    <div class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1">
                        <i class="bi bi-arrow-return-left me-1"></i>Đơn hoàn trả
                    </div>
                @endif
                @if(!empty($customer?->truck_route_id) || !empty($customer?->truck_station_id))
                    <div class="text-muted small mb-2 d-flex flex-wrap gap-3">
                        <span><i class="bi bi-building me-1"></i>Trạm nhận: {{ $truckStationName ?: 'Chưa cập nhật' }}</span>
                        <span><i class="bi bi-truck me-1"></i>Tuyến xe: {{ $selectedRouteName ?: 'Chưa cập nhật' }}</span>
                    </div>
                    <div class="d-flex">
                        <div class="text-muted small mb-2">
                            <i class="bi bi-telephone me-1"></i>Tel : {{ $truckStationPhone ?: 'Chưa cập nhật' }} &nbsp;
                        </div>
                        <div class="text-muted small mb-2">
                            <i class="bi bi-geo-alt me-1"></i>Địa chỉ: {{ $truckStationAddress ?: 'Chưa cập nhật' }}
                        </div>
                    </div>
                @endif
                @if($order->items->isNotEmpty())
                    <details id="products-{{ $order->id }}" class="ma-product-details">
                        <summary class="d-none">Sản phẩm</summary>
                        <div class="pt-2 border-top ma-product-section">
                            <div class="ma-product-table-head">
                                <div>Sản phẩm</div>
                                <div class="text-end">SL</div>
                                <div class="text-end">Size</div>
                                <div class="text-end">Kg</div>
                                <div class="text-end">Đơn giá</div>
                                <div class="text-end">Thành tiền</div>
                            </div>
                            <ul class="ma-product-list">
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
                                    <li class="ma-product-row">
                                        <div class="ma-product-table-row">
                                            <div class="ma-product-name">
                                                {{ $item->variant?->name ?? $item->variant?->sku ?? $item->product_name ?? 'Sản phẩm' }}
                                                @if($item->variant?->sku)
                                                    <span class="text-muted small">({{ $item->variant->sku }})</span>
                                                @endif
                                            </div>
                                            <div class="ma-product-cell"><strong>{{ $qty }}</strong></div>
                                            <div class="ma-product-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                            <div class="ma-product-cell"><strong>{{ $itemActualWeight }}</strong></div>
                                            <div class="ma-product-cell">{{ number_format($unitPrice) }}đ</div>
                                            <div class="ma-product-cell"><strong>{{ number_format($lineTotal) }}đ</strong></div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </details>
                @endif
            </div>
        @if(($showAssignmentButtons ?? false) === false)
            <div class="ma-shipper-actions d-flex flex-wrap gap-2 align-items-center">
                @if($customer?->defaultShipper)
                    <button type="button"
                        class="btn btn-sm btn-outline-success js-open-default-shipper-picker"
                        data-bs-toggle="modal"
                        data-bs-target="#defaultShipperPickerModal"
                        data-action="{{ route('shipper.customers.default-shipper.update', $customer) }}"
                        data-customer-name="{{ $customerName }}"
                        data-current-shipper-id="{{ $customer->default_shipper_id }}"
                        title="Chọn shipper cố định khác">
                        <i class="bi bi-person-check me-1"></i>{{ $customer->defaultShipper->name }}
                    </button>
                @endif

                @if($order->shipper_id)
                    <form action="{{ route('shipper.unassign-order', [$order->id]) }}" method="POST" class="ms-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn gỡ đơn này ra?')">
                            <i class="bi bi-x-circle me-1"></i>Gỡ đơn
                        </button>
                    </form>
                    <form action="{{ route('shipper.move-order-up', [$order->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate ?? now()->toDateString() }}">
                        <button type="submit" class="btn btn-light btn-sm" title="Lên trên" @disabled(!($canMoveUp ?? false))><i class="bi bi-arrow-up"></i></button>
                    </form>
                    <form action="{{ route('shipper.move-order-down', [$order->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate ?? now()->toDateString() }}">
                        <button type="submit" class="btn btn-light btn-sm" title="Xuống dưới" @disabled(!($canMoveDown ?? false))><i class="bi bi-arrow-down"></i></button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
