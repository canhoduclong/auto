@php
    $statusLabels = \App\Models\Order::statusOptions() + [
        \App\Models\Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
        \App\Models\Order::STATUS_PACKING => 'Đang đóng gói',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'Chờ giao vận chuyển',
        \App\Models\Order::STATUS_DELIVERING => 'Đang giao hàng',
        \App\Models\Order::STATUS_RETURNING => 'Đang trả hàng',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'Đã nhập kho trả hàng',
        'shipping' => 'Đang vận chuyển',
        'picked_up' => 'Đã lấy hàng',
    ];
    $currentSortBy = $sortBy ?? request('sort_by', 'created_at');
    $currentSortDir = strtolower($sortDir ?? request('sort_dir', 'desc'));
    $isTrashView = (bool) ($isTrashView ?? false);
    $returnableCount = $orders->getCollection()->filter(
        fn ($order) => in_array($order->status, ['picked_up', 'shipping', 'completed'], true)
    )->count();
    $sortDirFor = fn (string $field): string => $currentSortBy === $field && $currentSortDir === 'asc' ? 'desc' : 'asc';
    $sortIconFor = fn (string $field): string => $currentSortBy !== $field
        ? 'fa-sort'
        : ($currentSortDir === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc');
@endphp

<style>
    .monitor-my-orders { display: grid; gap: 18px; }
    .monitor-my-orders-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 16px; border-left: 7px solid #f1f5f9; background: #fff; }
    .monitor-my-orders-head h2 { margin: 0; font-size: 1rem; font-weight: 900; }
    .monitor-my-orders-head p { margin: 2px 0 0; color: #64748b; font-size: .75rem; }
    .monitor-my-orders-sort { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 0 4px; }
    .monitor-my-orders-sort-actions { display: flex; flex-wrap: wrap; gap: 6px; }
    .monitor-my-orders-sort .btn { border-radius: 4px; font-size: .72rem; }
    .monitor-my-order { display: grid; grid-template-columns: minmax(0, 1fr) 132px; gap: 14px; align-items: start; }
    .monitor-my-order-card { min-width: 0; padding: 14px 16px; border: 1px solid #dce6f1; border-radius: 7px; background: #fff; box-shadow: 0 5px 16px rgba(15, 23, 42, .06); }
    .monitor-my-order-card.is-cancelled { border-color: #fecaca; background: #fffafa; }
    .monitor-my-order-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
    .monitor-my-order-name { color: #0f172a; font-size: .82rem; font-weight: 900; text-transform: uppercase; }
    .monitor-my-order-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 3px; color: #64748b; font-size: .68rem; }
    .monitor-my-order-status { display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border-radius: 999px; background: #fff1f2; color: #be123c; font-size: .68rem; font-weight: 800; white-space: nowrap; }
    .monitor-my-order-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .monitor-my-order-delivery { padding: 9px 0; border-bottom: 1px dashed #dce6f1; }
    .monitor-my-order-section-title { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; color: #334155; font-size: .67rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
    .monitor-my-order-section-title button { border: 0; background: transparent; color: #1d4ed8; font-size: .65rem; font-weight: 900; text-transform: uppercase; }
    .monitor-my-order-delivery-lines { display: grid; gap: 4px; color: #64748b; font-size: .7rem; }
    .monitor-my-order-products { width: 100%; margin: 0; font-size: .7rem; }
    .monitor-my-order-products th { padding: 6px 4px; border-color: #dce6f1; color: #64748b; font-size: .61rem; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
    .monitor-my-order-products td { padding: 7px 4px; border-color: #edf2f7; vertical-align: middle; }
    .monitor-my-order-thumb { width: 34px; height: 34px; border-radius: 5px; object-fit: cover; border: 1px solid #e2e8f0; }
    .monitor-my-order-product-name { color: #0f172a; font-weight: 800; }
    .monitor-my-order-totals { display: grid; justify-content: end; margin-top: 5px; font-size: .72rem; }
    .monitor-my-order-total-line { display: grid; grid-template-columns: 95px 100px; gap: 8px; padding: 4px 0; text-align: right; }
    .monitor-my-order-total-line.is-total { border-top: 1px solid #dce6f1; font-size: .8rem; font-weight: 900; }
    .monitor-my-order-actions { display: grid; gap: 8px; }
    .monitor-my-order-actions .btn { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; border-radius: 6px; font-size: .72rem; font-weight: 800; }
    .monitor-my-order-actions form { margin: 0; }
    .monitor-my-order-actions form .btn { width: 100%; }
    .monitor-my-order-cancel { margin-top: 8px !important; padding-top: 8px; border-top: 1px solid #e2e8f0; }
    .monitor-my-orders-empty { padding: 44px 20px; border: 1px solid #dce6f1; border-radius: 8px; background: #fff; color: #64748b; text-align: center; }
    @media (max-width: 767.98px) {
        .monitor-my-order { grid-template-columns: 1fr; }
        .monitor-my-order-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .monitor-my-orders-sort { align-items: flex-start; flex-direction: column; }
        .monitor-my-order-products { min-width: 650px; }
    }
</style>

<div class="monitor-my-orders">
    <div class="monitor-my-orders-head">
        <div>
            <h2>Danh sách đơn hàng</h2>
            <p>Hiển thị {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} trên tổng {{ $orders->total() }} đơn.</p>
        </div>
        <span class="small text-muted">Có thể trả hàng: <strong class="text-dark">{{ $returnableCount }}</strong></span>
    </div>

    @if($orders->count())
        <div class="monitor-my-orders-sort">
            <div class="d-flex align-items-center gap-2 small text-muted">
                <a href="{{ request()->fullUrlWithQuery(['trash' => $isTrashView ? 0 : 1, 'page' => 1]) }}" class="btn btn-sm {{ $isTrashView ? 'btn-danger' : 'btn-outline-danger' }}" title="{{ $isTrashView ? 'Xem đơn đang hoạt động' : 'Xem thùng rác' }}"><i class="bi bi-trash"></i></a>
                <span>Sắp xếp nhanh:</span>
            </div>
            <div class="monitor-my-orders-sort-actions">
                @foreach(['created_at' => 'Ngày tạo', 'total' => 'Tổng tiền', 'customer_name' => 'Khách hàng', 'status' => 'Trạng thái'] as $field => $label)
                    <a data-order-sort-link="1" href="{{ request()->fullUrlWithQuery(['sort_by' => $field, 'sort_dir' => $sortDirFor($field), 'page' => 1]) }}" class="btn btn-sm btn-outline-secondary">{{ $label }} <i class="fa {{ $sortIconFor($field) }}"></i></a>
                @endforeach
            </div>
        </div>

        @foreach($orders as $order)
            @php
                $isCancelled = $order->status === \App\Models\Order::STATUS_CANCELLED;
                $isWaitingWarehouse = (int) ($order->stock_sufficient ?? 1) === 0 && $order->created_at?->isToday();
                $statusLabel = $isWaitingWarehouse ? 'Chờ Kho Ráp Hàng' : ($statusLabels[$order->status] ?? str_replace('_', ' ', $order->status));
                $canEdit = !$isTrashView && (int) $order->user_id === (int) $user->id && $order->canBeDirectlyEditedByOwner();
                $canCancel = !$isTrashView && (int) $order->user_id === (int) $user->id && $order->canBeCancelled();
                $canApprove = !$isTrashView && !$isCancelled && $user->hasRole(['admin', 'manager', 'manager_sale', 'director']);
                $defaultAddress = $order->customer?->addresses?->firstWhere('is_default', 1) ?? $order->customer?->addresses?->first();
                $address = $order->recipient_address ?: ($defaultAddress?->note ?: ($order->customer?->address ?: 'Chưa cập nhật'));
                $area = collect([$defaultAddress?->ward, $defaultAddress?->city])->filter()->implode(', ');
                $deliveryTime = $order->delivery_time ?: ($order->customer?->delivery_time ?: 'Chưa cập nhật');
                $itemsTotal = (float) $order->items->sum('total');
                $orderAdjustment = $itemsTotal - (float) $order->total;
            @endphp
            <article class="monitor-my-order" id="my-order-card-{{ $order->id }}">
                <div class="monitor-my-order-card {{ $isCancelled ? 'is-cancelled' : '' }}">
                    <div class="monitor-my-order-head">
                        <div>
                            <div class="monitor-my-order-name">{{ $order->customer?->name ?? 'Khách hàng' }}</div>
                            <div class="monitor-my-order-meta">
                                <span>{{ $order->created_at?->format('d/m/Y H:i') }}</span>
                                @if($order->customer?->phone)<span><i class="bi bi-telephone me-1"></i>{{ $order->customer->phone }}</span>@endif
                                <span>{{ $order->code ?: ('#' . $order->id) }}</span>
                            </div>
                        </div>
                        <span class="monitor-my-order-status">{{ $statusLabel }}</span>
                    </div>

                    <div class="monitor-my-order-delivery">
                        <div class="monitor-my-order-section-title">
                            <span>Giao hàng</span>
                            <button type="button" data-bs-toggle="collapse" data-bs-target="#myOrderDelivery{{ $order->id }}" aria-expanded="true">Ẩn/Hiện</button>
                        </div>
                        <div class="collapse show monitor-my-order-delivery-lines" id="myOrderDelivery{{ $order->id }}">
                            <span><i class="bi bi-geo-alt me-1"></i>Địa chỉ nhận hàng: {{ $address }}</span>
                            @if($area !== '')<span><i class="bi bi-pin-map me-1"></i>Khu vực: {{ $area }}</span>@endif
                            <span><i class="bi bi-clock me-1"></i>Giờ giao: {{ $deliveryTime }}</span>
                        </div>
                    </div>

                    <div class="monitor-my-order-section-title mt-2"><span>Danh sách sản phẩm</span></div>
                    <div class="table-responsive">
                        <table class="table table-sm monitor-my-order-products">
                            <thead><tr><th>Ảnh</th><th>Sản phẩm</th><th class="text-end">SL</th><th class="text-end">Size</th><th class="text-end">Tổng</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
                            <tbody>
                                @forelse($order->items as $item)
                                    @php
                                        $variant = $item->variant;
                                        $product = $item->product ?: $variant?->product;
                                        $productName = $product?->name ?? $variant?->name ?? 'Sản phẩm';
                                        $imagePath = $variant?->avatar?->media?->file_path ?? $product?->avatar?->media?->file_path;
                                        $lineTotal = (float) ($item->total ?? 0);
                                    @endphp
                                    <tr>
                                        <td>@if($imagePath)<img class="monitor-my-order-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $productName }}">@else<span class="text-muted"><i class="bi bi-image"></i></span>@endif</td>
                                        <td><span class="monitor-my-order-product-name">{{ $productName }}</span>@if($variant?->sku)<span class="d-block text-muted">{{ $variant->sku }}</span>@endif</td>
                                        <td class="text-end fw-bold">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, ',', '.'), '0'), ',') }}</td>
                                        <td class="text-end">{{ $variant?->size ?: '—' }}</td>
                                        <td class="text-end fw-bold">{{ $item->display_total_label }}</td>
                                        <td class="text-end">{{ number_format((float) $item->price, 0, ',', '.') }}đ</td>
                                        <td class="text-end fw-bold">{{ number_format($lineTotal, 0, ',', '.') }}đ</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-3">Đơn chưa có sản phẩm.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="monitor-my-order-totals">
                        @if(abs($orderAdjustment) > .01)<div class="monitor-my-order-total-line"><span>Điều chỉnh:</span><strong>{{ $orderAdjustment > 0 ? '-' : '+' }}{{ number_format(abs($orderAdjustment), 0, ',', '.') }}đ</strong></div>@endif
                        <div class="monitor-my-order-total-line is-total"><span>Tổng cộng:</span><strong>{{ number_format((float) $order->total, 0, ',', '.') }}đ</strong></div>
                    </div>
                </div>

                <div class="monitor-my-order-actions">
                    @if($canApprove)
                        <form method="POST" action="{{ route('site.orders.approve', $order) }}" class="js-monitor-approval-form">@csrf<input type="hidden" name="note" value="Duyệt từ danh sách đơn của tôi"><button class="btn btn-sm btn-success"><i class="bi bi-check2"></i>Duyệt</button></form>
                    @endif
                    @if($canEdit)<a href="{{ route('site.orders.edit', $order) }}" class="btn btn-sm btn-success"><i class="bi bi-pencil"></i>Sửa</a>@endif
                    <a href="{{ route('site.orders.show', $order) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i>Chi tiết</a>
                    @if(!$isTrashView)<a href="{{ route('site.orders.copy', $order->id) }}" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Sao chép đơn {{ $order->code }}?')"><i class="bi bi-files"></i>Sao chép đơn</a>@endif
                    @if($canCancel)<form class="monitor-my-order-cancel" method="POST" action="{{ route('site.orders.cancel', $order) }}" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?');">@csrf<button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i>Hủy đơn hàng</button></form>@endif
                </div>
            </article>
        @endforeach

        <div>{{ $orders->appends(request()->input())->links('pagination::bootstrap-5') }}</div>
    @else
        <div class="monitor-my-orders-empty"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Chưa có đơn hàng phù hợp.</div>
    @endif
</div>
