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

    @if($orders->count() > 0)
        <div class="orders-table-wrap">
            <div class="table-responsive">
                <table class="table orders-table align-middle">
                    <thead>
                        <tr>
                            <th>
                                <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => 'code', 'sort_dir' => $sortDirFor('code'), 'page' => 1]) }}" class="text-decoration-none text-reset d-inline-flex align-items-center gap-1">
                                    Mã đơn <i class="fa {{ $sortIconFor('code') }}"></i>
                                </a>
                            </th>
                            <th>
                                <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => 'customer_name', 'sort_dir' => $sortDirFor('customer_name'), 'page' => 1]) }}" class="text-decoration-none text-reset d-inline-flex align-items-center gap-1">
                                    Khách hàng <i class="fa {{ $sortIconFor('customer_name') }}"></i>
                                </a>
                            </th>
                            <th>
                                <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => 'total', 'sort_dir' => $sortDirFor('total'), 'page' => 1]) }}" class="text-decoration-none text-reset d-inline-flex align-items-center gap-1">
                                    Tổng tiền <i class="fa {{ $sortIconFor('total') }}"></i>
                                </a>
                            </th>
                            <th>
                                <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => 'status', 'sort_dir' => $sortDirFor('status'), 'page' => 1]) }}" class="text-decoration-none text-reset d-inline-flex align-items-center gap-1">
                                    Trạng thái <i class="fa {{ $sortIconFor('status') }}"></i>
                                </a>
                            </th>
                            <th>
                                <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_dir' => $sortDirFor('created_at'), 'page' => 1]) }}" class="text-decoration-none text-reset d-inline-flex align-items-center gap-1">
                                    Ngày tạo <i class="fa {{ $sortIconFor('created_at') }}"></i>
                                </a>
                            </th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
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
                            <tr>
                                <td>
                                    <div class="orders-code">{{ $order->code }}</div>
                                    @if(!empty($order->copied_from_order_id))
                                        <span class="badge bg-warning text-dark mt-1">Đơn copy mới</span>
                                    @endif
                                    <div class="orders-subtle">{{ $order->payment_status ?: 'pending payment' }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $order->customer->name ?? 'Không có khách hàng' }}</div>
                                    <div class="orders-subtle">Nhân viên: {{ $user->name }}</div>
                                </td>
                                <td>
                                    <div class="orders-total">{{ number_format($order->total, 0, ',', '.') }}đ</div>
                                    <div class="orders-subtle">{{ $order->payment_status_text }}</div>
                                </td>
                                <td>
                                    <span class="status-pill {{ $statusClass }}">
                                        <i class="fa fa-circle" style="font-size:8px;"></i>{{ $statusLabel }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $order->created_at->format('d/m/Y') }}</div>
                                    <div class="orders-subtle">{{ $order->created_at->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div class="orders-actions">
                                        <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fa fa-eye me-1"></i>
                                        </a>
                                        <a href="{{ route('site.orders.copy', $order->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa fa-copy me-1"></i>
                                        </a>
                                        @if($canEdit)
                                            <a href="{{ route('site.orders.edit', $order) }}" class="btn btn-success btn-sm">
                                                <i class="fa fa-pencil me-1"></i>
                                            </a>
                                        @endif
                                        @if($canReturn)
                                            <a href="{{ route('site.order-returns.create', $order) }}" class="btn btn-warning btn-sm text-dark">
                                                <i class="fa fa-undo me-1"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="orders-mobile-list">
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
                        <div class="orders-mobile-card">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <div class="orders-code">{{ $order->code }}</div>
                                    @if(!empty($order->copied_from_order_id))
                                        <span class="badge bg-warning text-dark mt-1">Đơn copy mới</span>
                                    @endif
                                    <div class="orders-subtle">{{ $order->customer->name ?? 'Không có khách hàng' }}</div>
                                </div>
                                <span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>

                            <div class="orders-mobile-grid mb-3">
                                <div class="orders-mobile-item">
                                    <span>Tổng tiền</span>
                                    <strong>{{ number_format($order->total, 0, ',', '.') }}đ</strong>
                                </div>
                                <div class="orders-mobile-item">
                                    <span>Thanh toán</span>
                                    <strong>{{ $order->payment_status_text }}</strong>
                                </div>
                                <div class="orders-mobile-item">
                                    <span>Ngày tạo</span>
                                    <strong>{{ $order->created_at->format('d/m/Y') }}</strong>
                                </div>
                                <div class="orders-mobile-item">
                                    <span>Giờ tạo</span>
                                    <strong>{{ $order->created_at->format('H:i') }}</strong>
                                </div>
                            </div>

                            <div class="orders-actions">
                                <a href="{{ route('site.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-eye me-1"></i>Chi tiết
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
