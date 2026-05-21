@php
    $customer = $order->customer;
    $address = $order->recipient_address ?: $customer?->address;
    $totalItems = $order->items->sum('quantity');
    $deliveryTime = $order->delivery_time ?: $customer?->delivery_time ?: 'Chưa cập nhật';
    $customerName = $customer?->name ?? $order->recipient_name;
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
@endphp
<div class="col-12">
    <div class="card ma-order-card p-2" style="min-height:unset; position: relative;">
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
            @media (max-width: 575px) {
                .ma-product-table-head,
                .ma-product-table-row {
                    grid-template-columns: minmax(0, 1.3fr) 40px 58px 74px 86px;
                    gap: 6px;
                }
            }
        </style>
        @if(($showAssignmentButtons ?? false) === false && $order->shipper_id)
            <form action="{{ route('shipper.unassign-order', [$order->id]) }}" method="POST" class="d-inline-block" style="position: absolute; top: 0.5rem; right: 0.5rem; z-index: 10;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Gỡ ra khỏi danh sách" onclick="return confirm('Bạn có chắc chắn muốn gỡ đơn này ra?')" style="font-size: 1.3rem; text-decoration: none;">
                    <i class="bi bi-x-circle"></i>
                </button>
            </form>
        @endif
        <div class="row align-items-stretch g-0">
            <div class="col-3 d-flex flex-column justify-content-center align-items-center border-end" style="min-width:78px; max-width:78px;">
                <div class="text-muted small fw-semibold text-uppercase mb-1" style="font-size: .68rem; letter-spacing: .03em; line-height: 1;">Giờ giao</div>
                <div class="fw-bold text-primary text-center" style="font-size:0.95rem; line-height: 1.15;">{{ $deliveryTime }}</div>
            </div>
            <div class="col-9 ps-3">
                <div class="fw-semibold text-dark">{{ $customerName }}</div>
                <div class="text-muted small mb-1">{{ $address ? mb_substr($address, 0, 60) . (mb_strlen($address) > 60 ? '...' : '') : 'Chưa cập nhật' }}</div>
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
                                    $itemActualWeight = null;
                                    if ($item->actual_weight) {
                                        $itemActualWeight = rtrim(rtrim(number_format((float) $item->actual_weight, 2, '.', ''), '0'), '.');
                                    } else {
                                        $itemActualWeight = '-';
                                    }
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
                @endif
            </div>
        </div>
        @if(($showAssignmentButtons ?? false) === true)
            <div class="mt-2">
                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.03em;">Gán cho:</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($shippers as $shipper)
                        <form action="{{ route('shipper.assign-order', [$order->id, $shipper->id]) }}" method="POST" class="d-inline-block">
                            @csrf
                            <button type="submit" class="btn btn-sm ma-shipper-btn btn-outline-primary" title="Gán cho {{ $shipper->name }}">
                                {{ mb_substr($shipper->name, 0, 12) }}
                            </button>
                        </form>
                    @endforeach
                    @php $user = auth()->user(); @endphp
                    @if($user && ($user->hasRole('manager_shipper') || $user->hasRole('admin')))
                        <form action="{{ route('shipper.assign-order', [$order->id, $user->id]) }}" method="POST" class="d-inline-block">
                            @csrf
                            <button type="submit" class="btn btn-sm ma-shipper-btn btn-outline-danger" title="Gán cho tôi ({{ $user->name }})">
                                Gán cho tôi
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @else
            @if(($showAssignmentButtons ?? false) === false && $order->shipper_id)
                {{-- Chuyển tới kho khác --}}
                <form action="{{ route('shipper.transfer-to-warehouse', [$order->id]) }}" method="POST" class="d-flex align-items-center gap-2 mt-2">
                    @csrf
                    <label for="warehouse_id_{{ $order->id }}" class="form-label mb-0" style="font-size:0.85em;">Chuyển tới kho:</label>
                    <select name="warehouse_id" id="warehouse_id_{{ $order->id }}" class="form-select form-select-sm" style="width:auto; min-width:160px;" required>
                        <option value="">-- Chọn kho --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @if($order->warehouse_id == $wh->id) selected @endif>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-info">Chuyển tới kho</button>
                </form>
            @endif
        @endif
    </div>
</div>