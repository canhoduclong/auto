@php
    $statusLabels = \App\Models\Order::statusOptions() + [
        \App\Models\Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
        \App\Models\Order::STATUS_PACKING => 'Đang đóng gói',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'Chờ giao đơn vị vận chuyển',
        \App\Models\Order::STATUS_DELIVERING => 'Đang giao hàng',
        \App\Models\Order::STATUS_RETURNING => 'Đang trả hàng',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'Đã nhập kho trả hàng',
        'shipping' => 'Đang vận chuyển',
        'picked_up' => 'Đã lấy hàng',
    ];

    $statusClasses = [
        \App\Models\Order::STATUS_COMPLETED => 'status-success',
        \App\Models\Order::STATUS_DELIVERED => 'status-success',
        \App\Models\Order::STATUS_ORDER_PLACED => 'status-pending',
        \App\Models\Order::STATUS_ORDER_CONFIRMED => 'status-progress',
        \App\Models\Order::STATUS_PACKED => 'status-progress',
        \App\Models\Order::STATUS_IN_DELIVERY => 'status-progress',
        \App\Models\Order::STATUS_READY_TO_PACK => 'status-pending',
        \App\Models\Order::STATUS_PACKING => 'status-progress',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'status-progress',
        \App\Models\Order::STATUS_DELIVERING => 'status-progress',
        \App\Models\Order::STATUS_RETURNING => 'status-danger',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'status-muted',
        \App\Models\Order::STATUS_RETURNED => 'status-danger',
        \App\Models\Order::STATUS_CANCELLED => 'status-danger',
        'shipping' => 'status-progress',
        'picked_up' => 'status-progress',
    ];

    $pageOrders = $orders->getCollection();
    $returnableCount = $pageOrders->filter(function ($order) {
        return in_array($order->status, ['picked_up', 'shipping', 'completed'], true);
    })->count();

    $currentSortBy = $sortBy ?? request('sort_by', 'created_at');
    $currentSortDir = strtolower($sortDir ?? request('sort_dir', 'desc'));

    $sortDirFor = function (string $field) use ($currentSortBy, $currentSortDir): string {
        if ($currentSortBy === $field && $currentSortDir === 'asc') {
            return 'desc';
        }

        return 'asc';
    };

    $sortIconFor = function (string $field) use ($currentSortBy, $currentSortDir): string {
        if ($currentSortBy !== $field) {
            return 'fa-sort text-muted';
        }

        return $currentSortDir === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc';
    };
@endphp

<div class="orders-panel">
    <div class="orders-section-head d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h5 mb-1 fw-bold">Danh sách đơn hàng</h2>
            <p class="mb-0 text-muted">Hiển thị {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} trên tổng {{ $orders->total() }} đơn.</p>
        </div>
        <div class="text-muted small">
            Có thể trả hàng: <strong class="text-dark">{{ $returnableCount }}</strong>
        </div>
    </div>
 </div>
  <div class="list-orders mt-4">
    @if($orders->count() > 0)
        <div class="pt-3 pb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="small text-muted">Sắp xếp nhanh:</div>
                <div class="d-flex flex-wrap gap-2">
                    <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_dir' => $sortDirFor('created_at'), 'page' => 1]) }}" class="btn btn-sm btn-outline-secondary">Ngày tạo <i class="fa {{ $sortIconFor('created_at') }}"></i></a>
                    <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => 'total', 'sort_dir' => $sortDirFor('total'), 'page' => 1]) }}" class="btn btn-sm btn-outline-secondary">Tổng tiền <i class="fa {{ $sortIconFor('total') }}"></i></a>
                    <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => 'customer_name', 'sort_dir' => $sortDirFor('customer_name'), 'page' => 1]) }}" class="btn btn-sm btn-outline-secondary">Khách hàng <i class="fa {{ $sortIconFor('customer_name') }}"></i></a>
                    <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => 'status', 'sort_dir' => $sortDirFor('status'), 'page' => 1]) }}" class="btn btn-sm btn-outline-secondary">Trạng thái <i class="fa {{ $sortIconFor('status') }}"></i></a>
                </div>
            </div>

            <div class="row g-3">
                @foreach($orders as $order)
                    @php
                        $statusLabel = $statusLabels[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status));
                        $statusClass = $statusClasses[$order->status] ?? 'status-muted';
                        $canReturn = in_array($order->status, ['picked_up', 'shipping', 'completed'], true);
                        $isCopiedOrder = !empty($order->copied_from_order_id);
                        $canEdit = $isCopiedOrder
                            || ($order->status === \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL
                                && $order->created_at?->isToday());
                    @endphp
                    <div class="col-12">
                        <div class="mc-customer-card border rounded p-3 bg-white">
                            @php
                                $formatSignedMoney = static function (float $amount): string {
                                    $prefix = $amount < 0 ? '+' : '-';

                                    return $prefix . number_format(abs($amount), 0, ',', '.') . 'đ';
                                };
                                $itemCount = (int) (($order->items ?? collect())->count());
                                $totalQty = (float) (($order->items ?? collect())->sum('quantity'));
                                $orderDiscount = (float) ($order->total_discount
                                    ?? (($order->item_discount_total ?? 0) + ($order->extra_discount_total ?? 0)));
                                $deliveryAddress = $order->recipient_address ?: ($order->customer?->address ?: 'Chưa có địa chỉ');
                                $deliveryTime = $order->delivery_time ?: ($order->customer?->delivery_time ?: 'Chưa cập nhật');
                            @endphp

                            <div class="wh-order-head">
                                <div>
                                    <div class="orders-code">{{ $order->customer?->name ?? '—' }}</div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i>
                                        {{ $order->created_at->format('d/m/Y H:i') }},
                                        Mã KH: {{ $order->customer->customer_code ?? ('#' . ($order->customer->id ?? '')) }}, 
                                       @if($order->customer?->phone)
                                           <i class="bi bi-telephone me-1"></i>{{ $order->customer->phone }}
                                            @endif
                                    </small>
                                    @if(!empty($order->copied_from_order_id))
                                        <div><span class="badge bg-warning text-dark mt-2">Đơn copy mới</span></div>
                                    @endif
                                </div>
                                <div class="text-end">

                           
                                    <div class="wh-section">
                                        <div class="order-total-table-wrap">
                                            <div class="order-total-table-head">
                                                <div class="order-total=items">
                                                    <div class="wh-meta-label">Số dòng SP</div>
                                                    <div class="wh-meta-value">{{ $itemCount }}</div>
                                                </div>
                                                <div class="order-total=items">
                                                    <div class="wh-meta-label">Tổng số lượng</div>
                                                    <div class="wh-meta-value">{{ rtrim(rtrim(number_format($totalQty, 3, '.', ''), '0'), '.') }}</div>
                                                </div> 
                                                <div class="order-total=items">
                                                    <div class="wh-meta-label">Thành tiền</div>
                                                    <div class="wh-meta-value text-primary">{{ number_format((float) $order->total, 0, ',', '.') }}đ</div>
                                                </div> 
                                            </div>
                                        </div>
                                    </div> 
                                </div>
                            </div>

                            <div class="wh-section border-top-0">
                                @php
                                    $defaultAddress = $order->customer?->addresses?->firstWhere('is_default', 1) ?? $order->customer?->addresses?->first();
                                    $addressLine = $defaultAddress?->note ?: ($order->customer?->address ?: 'Chưa có địa chỉ');
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

                                <div class="customer-info g-3">
                                    <div class="customer-info-logistics mt-2">
                                        <a class="w-100 d-flex justify-content-between align-items-center customer-collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $deliveryCollapseId }}" aria-expanded="true" aria-controls="{{ $deliveryCollapseId }}">
                                            <span class="logistics-title mb-0">Giao hàng</span>
                                            <span class="customer-collapse-action" data-collapse-label="1">Hide</span>
                                        </a>
                                        <div id="{{ $deliveryCollapseId }}" class="collapse show logistics-body pt-2">
                                            <div class="small text-muted mb-1">
                                                <i class="bi bi-geo-alt me-1"></i>
                                                Địa chỉ nhận hàng: {{ $addressLine }}
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
                                            <div class="text-center">Đơn giá</div>
                                            <!--div class="text-center">Chiết khấu</div-->
                                            <div class="text-end">Thành tiền</div>
                                        </div>
                                        <ul class="wh-item-list">
                                            @foreach($order->items as $item)
                                                @php
                                                    $variant = $item->variant;
                                                    $product = $item->product;
                                                    $productName = $variant?->name ?? $product?->name ?? 'Sản phẩm';
                                                    $qty = (float) ($item->quantity ?? 0);
                                                    $unitPrice = (float) ($item->price ?? 0);
                                                    $lineDiscount = $item->discount_total;
                                                    if ($lineDiscount === null) {
                                                        $rawDiscount = (float) ($item->unit_discount ?? 0) * $qty;
                                                        $discountType = strtolower((string) ($item->discount_type ?? 'decrease'));
                                                        $lineDiscount = $discountType === 'increase' ? -1 * $rawDiscount : $rawDiscount;
                                                    }
                                                    $lineDiscount = (float) $lineDiscount;
                                                    $lineTotal = (float) ($item->total ?? 0);
                                                    if ($lineTotal <= 0) {
                                                        $lineTotal = ($qty * $unitPrice) - $lineDiscount;
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
                                                        <div class="wh-item-cell">{{ number_format($unitPrice, 0, ',', '.') }}đ</div>
                                                        <!--div class="wh-item-cell">{{ number_format($lineDiscount, 0, ',', '.') }}đ</div-->
                                                        <div class="wh-item-cell text-end"><strong>{{ number_format($lineTotal, 0, ',', '.') }}đ</strong></div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <div class="orders-subtle">Không có sản phẩm</div>
                                @endif
                            </div>
                            
                            <div class="order-tootls d-flex justify-content-between align-items-center mt-4">
                                <div class="code small mt-3">
                                    {{ $order->code }}
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <span class="status-pill me-2 {{ $statusClass }}">
                                            <i class="fa fa-circle" style="font-size:8px;"></i>{{ $statusLabel }}
                                    </span>
                                    <div class="orders-actions">
                                        <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fa fa-eye me-1"></i>Chi tiết
                                        </a>
                                        <a href="{{ route('site.orders.copy', $order->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa fa-copy me-1"></i>Copy
                                        </a>
                                        @if($canEdit)
                                            <a href="{{ route('site.orders.edit', $order) }}" class="btn btn-success btn-sm">
                                                <i class="fa fa-pencil me-1"></i>Sửa
                                            </a>
                                        @endif
                                        @if($canReturn)
                                            <a href="{{ route('site.order-returns.create', $order) }}" class="btn btn-warning btn-sm text-dark">
                                                <i class="fa fa-undo me-1"></i>Trả hàng
                                            </a>
                                        @endif
                                    </div>
                                </div>
                             </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="px-4 pb-4" id="ordersPaginationContainer">
            {{ $orders->appends(request()->input())->links('pagination::bootstrap-5') }}
        </div>
    @else
        <div class="orders-empty">
            <div class="mb-3" style="font-size:3rem;color:#cbd5e1;">
                <i class="fa fa-inbox"></i>
            </div>
            <h3 class="h5 fw-bold text-dark mb-2">Chưa có đơn hàng phù hợp</h3>
            <p class="mb-3">Hãy thử thay đổi bộ lọc hoặc quay lại sau khi có đơn mới được tạo.</p>
            <a href="{{ route('pages.my_orders') }}" class="btn btn-outline-primary rounded-pill px-4">
                Xem tất cả đơn hàng
            </a>
        </div>
    @endif
</div>
