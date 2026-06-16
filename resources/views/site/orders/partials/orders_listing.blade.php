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

    $stockWarnings = $stockWarnings ?? [];


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
    $isTodayOrdersView = !(bool) ($isTrashView ?? (request('trash') === '1'))
        && request()->filled('from_date')
        && request()->filled('to_date')
        && request('from_date') === now()->toDateString()
        && request('to_date') === now()->toDateString();
    $sequencePackedStatuses = [
        \App\Models\Order::STATUS_PACKED,
        \App\Models\Order::STATUS_READY_TO_SHIP,
        \App\Models\Order::STATUS_DELIVERING,
        \App\Models\Order::STATUS_DELIVERED,
        \App\Models\Order::STATUS_COMPLETED,
    ];
    $returnableCount = $pageOrders->filter(function ($order) {
        return in_array($order->status, ['picked_up', 'shipping', 'completed'], true);
    })->count();

    $currentSortBy = $sortBy ?? request('sort_by', 'created_at');
    $currentSortDir = strtolower($sortDir ?? request('sort_dir', 'desc'));
    $isTrashView = (bool) ($isTrashView ?? (request('trash') === '1'));

    $quickTrashUrl = request()->fullUrlWithQuery([
        'trash' => $isTrashView ? 0 : 1,
        'page' => 1,
    ]);

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
 @if($isTodayOrdersView && $pageOrders->isNotEmpty())
    <div class="my-orders-sequence-nav">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-bold text-muted me-1">
                <i class="bi bi-list-ol me-1"></i>Điều hướng nhanh:
            </span>
            @foreach($pageOrders->sortBy(fn ($order) => $order->daily_sequence ?? PHP_INT_MAX) as $navOrder)
                @php
                    $navStatus = (string) $navOrder->status;
                    $navStateClass = $navStatus === \App\Models\Order::STATUS_PACKING
                        ? 'is-packing'
                        : (in_array($navStatus, $sequencePackedStatuses, true) ? 'is-packed' : 'is-unpacked');
                @endphp
                <a
                    href="#my-order-card-{{ $navOrder->id }}"
                    class="my-orders-sequence-pill {{ $navStateClass }}"
                    onclick="event.preventDefault(); document.getElementById('my-order-card-{{ $navOrder->id }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' });"
                    title="{{ $navOrder->customer?->name ?? 'Đơn hàng' }} - {{ $statusLabels[$navStatus] ?? $navStatus }}"
                >
                    {{ $navOrder->daily_sequence ?? '—' }}
                </a>
            @endforeach
        </div>
        <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
            <span><i class="bi bi-circle-fill text-secondary me-1"></i>Chưa đóng hàng</span>
            <span><i class="bi bi-circle-fill text-warning me-1"></i>Đang đóng hàng</span>
            <span><i class="bi bi-circle-fill text-success me-1"></i>Đã đóng hàng</span>
        </div>
    </div>
 @endif
  <div class="list-orders mt-4">
    @if($orders->count() > 0)
        <div class="pt-3 pb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ $quickTrashUrl }}" class="btn btn-sm {{ $isTrashView ? 'btn-danger' : 'btn-outline-danger' }}" title="{{ $isTrashView ? 'Xem đơn đang hoạt động' : 'Xem thùng rác' }}">
                        <i class="bi bi-trash"></i>
                    </a>
                    <div class="small text-muted">Sắp xếp nhanh:</div>
                </div>
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
                        $isWaitingWarehouseAssemble = (int) ($order->stock_sufficient ?? 1) === 0
                            && $order->created_at?->isToday();
                        $statusLabel = $isWaitingWarehouseAssemble
                            ? 'Chờ Kho Ráp Hàng'
                            : ($statusLabels[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status)));
                        $statusClass = $statusClasses[$order->status] ?? 'status-muted';
                        if ($isWaitingWarehouseAssemble) {
                            $statusClass = 'status-danger';
                        }
                        $isReturnOrder = (bool) ($order->is_return_order ?? false)
                            || (string) ($order->order_type ?? '') === 'order_return'
                            || (string) ($order->workflow_code ?? '') === 'order_return';
                        $canReturn = !$isReturnOrder && $order->status === \App\Models\Order::STATUS_DELIVERED;
                        $canMoveToTrash = in_array($order->status, [\App\Models\Order::STATUS_REJECTED, \App\Models\Order::STATUS_CANCELLED], true);
                        $isCopiedOrder = !empty($order->copied_from_order_id);
                        $canEdit = $isCopiedOrder
                            || ($order->status === \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL
                                && $order->created_at?->isToday());
                        $canSaveCustomerFeedback = !$isTrashView && method_exists($order, 'canReceiveCustomerFeedback') && $order->canReceiveCustomerFeedback();
                        $customerFeedbackOptions = \App\Models\Order::customerFeedbackOptions();
                        $customerFeedbackMeta = \App\Models\Order::customerFeedbackMeta($order->customer_feedback_status ?? null);
                    @endphp
                    <div class="col-12">
                        <div
                            class="mc-customer-card border rounded p-3 bg-white"
                            id="my-order-card-{{ $order->id }}"
                            data-order-id="{{ $order->id }}"
                            data-order-sequence="{{ $order->daily_sequence ?? '' }}"
                        >
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
                                <div class="d-flex align-items-start gap-3">
                                    @if($isTodayOrdersView)
                                        <div class="my-orders-sequence-badge" title="Số thứ tự đơn trong ngày">
                                            <div class="text-center"> 
                                                {{ $order->daily_sequence ?? '—' }}
                                            </div>
                                        </div>
                                    @endif
                                    <div>
                                    <div class="orders-code">{{ $order->customer?->name ?? '—' }}</div>
                                    <small class="text-muted"> 
                                        {{ $order->created_at->format('d/m/Y H:i') }},  
                                       @if($order->customer?->phone)
                                           <i class="bi bi-telephone me-1"></i>{{ $order->customer->phone }}
                                            @endif
                                    </small>
                                    @if(!empty($order->copied_from_order_id))
                                        <div><span class="badge bg-warning text-dark mt-2">Đơn copy mới</span></div>
                                    @endif
                                    </div>
                                </div>
                                <div class="text-end">

                           
                                    <div class="wh-section">
                                        <div class="order-total-table-wrap">
                                            <div class="order-total-table-head">
                                                <div class="order-total=items">
                                                    <div class="wh-meta-label">Mã KH</div>
                                                    <div class="wh-meta-value">{{ $order->customer->customer_code ?? ('#' . ($order->customer->id ?? '')) }} </div>
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

                            <div class="wh-section border-top-0 border-bottom-0">
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
                                    <div class="customer-info-logistics">
                                        <a class="w-100 d-flex justify-content-between align-items-center customer-collapse-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $deliveryCollapseId }}" aria-expanded="false" aria-controls="{{ $deliveryCollapseId }}">
                                            <span class="logistics-title mb-0">Giao hàng</span>
                                            <span class="customer-collapse-action" data-collapse-label="1">Hide</span>
                                        </a>
                                        <div id="{{ $deliveryCollapseId }}" class="collapse show logistics-body">
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

                            @if($canSaveCustomerFeedback)
                                <div class="mt-3 p-3 rounded border bg-light">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div class="fw-bold">
                                            <i class="bi bi-chat-square-text me-1"></i>Phản hồi khách hàng cho bộ phận đóng hàng
                                        </div>
                                        <span class="badge border {{ $customerFeedbackMeta['class'] }}">
                                            {{ $customerFeedbackMeta['label'] }}
                                        </span>
                                    </div>
                                    <form method="POST" action="{{ route('site.orders.customer-feedback', $order) }}" class="d-grid gap-2">
                                        @csrf
                                        <div>
                                            <label class="form-label small fw-semibold mb-1">Tình trạng khách</label>
                                            <select name="customer_feedback_status" class="form-select form-select-sm" required>
                                                @foreach($customerFeedbackOptions as $value => $label)
                                                    <option value="{{ $value }}" {{ (string) ($order->customer_feedback_status ?? '') === (string) $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-semibold mb-1">Phản hồi từ khách hàng</label>
                                            <textarea name="customer_feedback_note" class="form-control form-control-sm" rows="2" required placeholder="Ví dụ: khách yêu cầu đóng kỹ đáy thùng, dễ trả nếu móp méo...">{{ old('customer_feedback_note', $order->customer_feedback_note ?? '') }}</textarea>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-semibold mb-1">Đánh giá đơn hàng từ sale</label>
                                            <textarea name="customer_feedback_sale_review" class="form-control form-control-sm" rows="2" placeholder="Ví dụ: đơn giao tốt, khách hài lòng, lần sau đóng thêm lớp chống móp...">{{ old('customer_feedback_sale_review', $order->customer_feedback_sale_review ?? '') }}</textarea>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button class="btn btn-sm btn-primary" type="submit">
                                                <i class="bi bi-save2 me-1"></i>Lưu phản hồi
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" type="submit" name="reset_feedback" value="1" onclick="return confirm('Reset phản hồi khách hàng cho đơn này?')">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                            </button>
                                        </div>
                                    </form>
                                    @if($order->customer_feedback_at)
                                        <div class="small text-muted mt-2">
                                            Cập nhật lúc {{ $order->customer_feedback_at->format('d/m/Y H:i') }}
                                            @if($order->customerFeedbackUser?->name)
                                                bởi {{ $order->customerFeedbackUser->name }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="order-tootls d-md-flex justify-content-between align-items-center mt-4">
                                <div class="code small mt-3">
                                    {{ $order->code }}
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <span class="status-pill me-2 {{ $statusClass }}">
                                            <i class="fa fa-circle" style="font-size:8px;"></i>{{ $statusLabel }}
                                    </span>
                                    @if($isReturnOrder)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-2">
                                            <i class="fa fa-undo me-1"></i>Đơn hoàn trả
                                        </span>
                                    @endif
                                    <div class="orders-actions">
                                        <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fa fa-eye me-1"></i>Chi tiết
                                        </a>
                                        @if(!$isTrashView && !$isCopiedOrder)
                                            <a href="{{ route('site.orders.copy', $order->id) }}"
                                               class="btn btn-outline-secondary btn-sm"
                                               onclick="return confirm('Copy đơn #{{ $order->code }} để tạo đơn mới?')">
                                                <i class="fa fa-copy me-1"></i>Copy Đơn
                                            </a>
                                        @endif
                                        @if(!$isTrashView && $canReturn)
                                            <a href="{{ route('site.orders.return', $order->id) }}"
                                               class="btn btn-outline-danger btn-sm"
                                               onclick="return confirm('Tạo đơn hoàn trả từ đơn #{{ $order->code }}?')">
                                                <i class="fa fa-undo me-1"></i>Trả hàng
                                            </a>
                                        @endif
                                                                                @if(!$isTrashView && $canMoveToTrash)
                                            <form action="{{ route('site.orders.trash', $order) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Đưa đơn #{{ $order->code }} vào thùng rác?')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Đưa vào thùng rác">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($isCopiedOrder)
                                            <form action="{{ route('site.orders.confirm-copy', $order) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Xác nhận {{ $isReturnOrder ? 'đơn hoàn trả' : 'đơn copy' }} #{{ $order->code }}? Hệ thống sẽ cập nhật giá và chuyển đơn sang quy trình duyệt.')">
                                                @csrf
                                                <button type="submit" class="btn {{ $isReturnOrder ? 'btn-danger' : 'btn-warning text-dark' }} btn-sm">
                                                    <i class="fa fa-check me-1"></i>Xác nhận
                                                </button>
                                            </form>
                                        @endif
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

                            @if(!empty($stockWarnings[$order->id]))
                                <div class="stock-warning-alert mt-2 p-2 rounded border border-warning bg-warning bg-opacity-10">
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                        <strong class="small text-warning-emphasis">Cảnh báo thiếu hàng đóng đơn</strong>
                                    </div>
                                    <ul class="mb-0 ps-3" style="font-size:.8rem;">
                                        @foreach($stockWarnings[$order->id] as $shortage)
                                            <li>
                                                <span class="fw-semibold">{{ $shortage['name'] }}</span>:
                                                cần {{ rtrim(rtrim(number_format($shortage['needed'], 3, '.', ''), '0'), '.') }},
                                                còn lại {{ rtrim(rtrim(number_format($shortage['available'], 3, '.', ''), '0'), '.') }}
                                                <span class="text-danger fw-bold">(thiếu {{ rtrim(rtrim(number_format($shortage['needed'] - $shortage['available'], 3, '.', ''), '0'), '.') }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
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
