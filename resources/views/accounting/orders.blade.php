@extends(accounting_layout())

@section('title', 'Đơn hàng')
@section('subtitle', 'Danh sách đơn hàng của tất cả sale trong ngày')

@section('accounting_content')
@php
    $dailyQtyText = rtrim(rtrim(number_format((float) $dailyTotalItemQuantity, 3, '.', ''), '0'), '.');
    $filteredQtyText = rtrim(rtrim(number_format((float) $filteredItemQuantity, 3, '.', ''), '0'), '.');
    $compactNumber = fn ($value, $decimals = 3) => rtrim(rtrim(number_format((float) $value, $decimals, ',', '.'), '0'), ',');
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $statusMeta = [
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'text-bg-secondary'],
        'approved' => ['label' => 'Đã duyệt', 'class' => 'text-bg-primary'],
        'ready_to_pack' => ['label' => 'Chờ đóng hàng', 'class' => 'text-bg-info'],
        'packing' => ['label' => 'Đang đóng hàng', 'class' => 'text-bg-warning'],
        'packed' => ['label' => 'Đã đóng hàng', 'class' => 'text-bg-primary'],
        'packed_waiting_pickup' => ['label' => 'Chờ lấy hàng', 'class' => 'text-bg-info'],
        'delivering' => ['label' => 'Đang giao', 'class' => 'text-bg-warning'],
        'delivered' => ['label' => 'Đã giao', 'class' => 'text-bg-success'],
        'completed' => ['label' => 'Hoàn thành', 'class' => 'text-bg-success'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'text-bg-danger'],
        'returned' => ['label' => 'Đã hoàn trả', 'class' => 'text-bg-danger'],
        'returned_completed' => ['label' => 'Hoàn trả hoàn tất', 'class' => 'text-bg-dark'],
    ];
@endphp

@push('styles')
<style>
    .acc-order-card { border: 0; border-radius: 12px; box-shadow: 0 10px 24px rgba(15,23,42,.07); overflow: hidden; }
    .acc-order-sequence { width: 42px; min-width: 42px; height: 36px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: #64748b; color: #fff; font-weight: 800; }
    .acc-order-sequence.is-done { background: #198754; }
    .acc-order-sequence.is-processing { background: #ffc107; color: #212529; }
    .acc-order-description { color: #64748b; font-size: .76rem; }
    .acc-order-info { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 10px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; }
    .acc-order-info-label { color: #64748b; font-size: .7rem; text-transform: uppercase; letter-spacing: .03em; }
    .acc-order-info-value { color: #0f172a; font-size: .87rem; font-weight: 700; overflow-wrap: anywhere; }
    .acc-item-table { min-width: 760px; }
    .acc-item-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; }
    .acc-item-thumb-empty { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px dashed #cbd5e1; color: #94a3b8; background: #f8fafc; }
    .acc-adjustment-panel { border-left: 1px solid #e2e8f0; background: #f8fafc; width: 340px; min-width: 300px; }
    .acc-order-nav { position: sticky; top: 74px; z-index: 15; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; background: rgba(255,255,255,.96); box-shadow: 0 4px 15px rgba(15,23,42,.08); backdrop-filter: blur(8px); }
    .acc-order-nav-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 9px; border: 0; border-radius: 999px; color: #fff; background: #64748b; font-size: .9rem; font-weight: 800; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,.12); transition: transform .15s, box-shadow .15s, outline .15s; }
    .acc-order-nav-pill:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,.18); }
    .acc-order-nav-pill.is-processing { background: #ffc107; color: #212529; }
    .acc-order-nav-pill.is-done { background: #198754; }
    .acc-order-nav-pill.active { outline: 3px solid rgba(14,165,233,.35); outline-offset: 2px; }
    [id^="order-card-"] { scroll-margin-top: 145px; }
    @media (max-width: 991.98px) {
        .acc-order-info { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .acc-adjustment-panel { width: 100%; border-left: 0; border-top: 1px solid #e2e8f0; }
    }
</style>
@endpush

<div class="acc-kpi mb-3">
    <div class="acc-card p-3"><div class="text-muted small">Tổng đơn hàng trong ngày</div><div class="h4 mb-0">{{ number_format((int) $dailyTotalOrders) }}</div></div>
    <div class="acc-card p-3"><div class="text-muted small">Tổng số lượng hàng hóa</div><div class="h4 mb-0">{{ $dailyQtyText }}</div></div>
    <div class="acc-card p-3"><div class="text-muted small">Số lượng trên trang hiện tại</div><div class="h4 mb-0">{{ $filteredQtyText }}</div></div>
</div>

<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label">Ngày</label><input type="date" name="date" class="form-control" value="{{ $date }}"></div>
            <div class="col-md-3"><label class="form-label">Khách hàng</label><select class="form-select" name="customer_id"><option value="0">Tất cả</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected($customerId === (int) $customer->id)>{{ $customer->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Sale</label><select class="form-select" name="sale_id"><option value="0">Tất cả sale</option>@foreach($sales as $sale)<option value="{{ $sale->id }}" @selected($saleId === (int) $sale->id)>{{ $sale->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Trạng thái đơn</label><input class="form-control" name="status" value="{{ $status }}" placeholder="delivered..."></div>
            <div class="col-md-2"><label class="form-label">Thanh toán</label><input class="form-control" name="payment_status" value="{{ $paymentStatus }}" placeholder="paid/unpaid..."></div>
            <div class="col-md-4"><label class="form-label">Tìm nhanh</label><input class="form-control" name="keyword" value="{{ $keyword }}" placeholder="Mã đơn, khách hàng, sale"></div>
            <div class="col-md-2"><label class="form-label">Số đơn/trang</label><select class="form-select" name="per_page">@foreach([10,20,50,100] as $size)<option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Sắp xếp</label><select class="form-select" name="sort_by"><option value="created_at" @selected($sortBy === 'created_at')>Ngày tạo</option><option value="code" @selected($sortBy === 'code')>Mã đơn</option><option value="total" @selected($sortBy === 'total')>Tổng tiền</option><option value="customer_name" @selected($sortBy === 'customer_name')>Khách hàng</option><option value="sale_name" @selected($sortBy === 'sale_name')>Sale</option></select></div>
            <div class="col-md-2"><label class="form-label">Chiều sắp xếp</label><select class="form-select" name="sort_dir"><option value="desc" @selected($sortDir === 'desc')>Giảm dần</option><option value="asc" @selected($sortDir === 'asc')>Tăng dần</option></select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Lọc danh sách</button></div>
        </form>
    </div>
</div>

<div class="mb-3 small text-muted">Hiển thị {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} / {{ $orders->total() }} đơn.</div>

@if($orders->isNotEmpty())
    <div class="acc-order-nav mb-3" aria-label="Điều hướng nhanh giữa các đơn hàng">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @foreach($orders->getCollection()->sortBy('daily_sequence') as $navOrder)
                    @php
                        $navDone = in_array($navOrder->status, ['delivered','completed','returned_completed'], true);
                        $navProcessing = in_array($navOrder->status, ['packing','delivering'], true);
                    @endphp
                    <a href="#order-card-{{ $navOrder->id }}"
                       class="acc-order-nav-pill {{ $navDone ? 'is-done' : ($navProcessing ? 'is-processing' : '') }}"
                       data-order-id="{{ $navOrder->id }}"
                       title="{{ $navOrder->customer?->name ?? 'Đơn hàng' }} · {{ $navOrder->code }}">
                        {{ $navOrder->daily_sequence ?? $loop->iteration }}
                    </a>
                @endforeach
            </div>
            <div class="d-flex flex-wrap gap-3 small text-muted">
                <span><i class="bi bi-circle-fill text-secondary me-1"></i>Chờ xử lý</span>
                <span><i class="bi bi-circle-fill text-warning me-1"></i>Đang xử lý</span>
                <span><i class="bi bi-circle-fill text-success me-1"></i>Hoàn tất</span>
            </div>
        </div>
    </div>
@endif

<div class="row g-3">
@forelse($orders as $order)
    @php
        $meta = $statusMeta[$order->status] ?? ['label' => $order->status ?: '-', 'class' => 'text-bg-secondary'];
        $isDone = in_array($order->status, ['delivered','completed','returned_completed'], true);
        $isProcessing = in_array($order->status, ['packing','delivering'], true);
        $isReturnOrder = (bool) ($order->is_return_order ?? false) || in_array((string) ($order->order_type ?? ''), ['order_return','return'], true);
        $reconciliation = $order->accountingReconciliation;
        $isReconciled = $reconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED;
    @endphp
    <div class="col-12" id="order-card-{{ $order->id }}">
        <div class="card acc-order-card {{ $isReturnOrder ? 'border border-danger' : '' }}">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-3">
                <div class="acc-order-sequence {{ $isDone ? 'is-done' : ($isProcessing ? 'is-processing' : '') }}">{{ $order->daily_sequence ?? '—' }}</div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold fs-5">{{ $order->customer?->name ?? '—' }}</div>
                            <div class="acc-order-description">#{{ $order->daily_sequence ?? '—' }}, lên đơn {{ optional($order->created_at)->format('d/m/Y H:i') }}, giao {{ optional($order->delivery_date)->format('d/m/Y') ?: 'chưa cập nhật' }}, {{ $order->code }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @if($isReturnOrder)<span class="badge text-bg-danger">Đơn hoàn trả</span>@endif
                            <span class="badge {{ $isReconciled ? 'text-bg-success' : 'text-bg-warning' }}">{{ $isReconciled ? 'Đã đối soát' : 'Chưa đối soát' }}</span>
                            <span class="badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap">
                <div class="card-body flex-grow-1" style="min-width:0">
                    <div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $order->customer?->address ?: 'Chưa có địa chỉ' }}</div>
                    <div class="small text-muted mb-3"><i class="bi bi-clock me-1"></i>Giờ giao: {{ $order->delivery_time ?: ($order->customer?->delivery_time ?: 'Chưa cập nhật') }}</div>

                    <div class="acc-order-info mb-3">
                        <div><div class="acc-order-info-label">Sale</div><div class="acc-order-info-value">{{ $order->user?->name ?? '-' }}</div></div>
                        <div><div class="acc-order-info-label">Shipper</div><div class="acc-order-info-value">{{ $order->shipper?->name ?? '-' }}</div></div>
                        <div><div class="acc-order-info-label">Kho xuất</div><div class="acc-order-info-value">{{ $order->warehouse?->name ?? '-' }}</div></div>
                        <div><div class="acc-order-info-label">Thanh toán</div><div class="acc-order-info-value">{{ $order->payment_status ?? '-' }}</div></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 acc-item-table">
                            <thead><tr><th>Ảnh</th><th>Sản phẩm</th><th class="text-center">Size</th><th class="text-center">SL</th><th class="text-center">Tổng</th><th class="text-center">Khối lượng</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
                            <tbody>
                            @foreach($order->items as $item)
                                @php
                                    $variant = $item->variant;
                                    $imagePath = $variant?->avatar?->media?->file_path ?? $item->product?->avatar?->media?->file_path;
                                    $weight = $item->actual_weight ?? $item->total_weight;
                                @endphp
                                <tr>
                                    <td>@if($imagePath)<img class="acc-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="">@else<span class="acc-item-thumb-empty"><i class="bi bi-image"></i></span>@endif</td>
                                    <td><div class="fw-semibold">{{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}</div><div class="small text-muted">{{ $variant?->sku ?: '---' }}</div></td>
                                    <td class="text-center fw-semibold">{{ filled($variant?->size) ? $compactNumber($variant->size, 2) : '-' }}</td>
                                    <td class="text-center fw-semibold">{{ $compactNumber($item->quantity) }}</td>
                                    <td class="text-center">{{ $item->display_total_label }}</td>
                                    <td class="text-center">{{ $weight !== null ? $compactNumber($weight) . ' kg' : '---' }}</td>
                                    <td class="text-end">{{ $money($item->price) }}</td>
                                    <td class="text-end fw-semibold">{{ $money($item->total ?? ((float) $item->quantity * (float) $item->price)) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($order->adjustments->isNotEmpty())
                    <aside class="acc-adjustment-panel p-3">
                        <div class="fw-bold small text-uppercase text-secondary mb-2"><i class="bi bi-arrow-left-right me-1"></i>Yêu cầu điều chỉnh</div>
                        @foreach($order->adjustments as $adjustment)
                            @php
                                $adjustmentClass = match($adjustment->status) {'approved','completed' => 'success', 'rejected' => 'danger', 'pending_approval' => 'warning', default => 'secondary'};
                                $canApproveAdjustment = $adjustment->status === \App\Models\OrderAdjustment::STATUS_PENDING_APPROVAL && (
                                    $authUser?->hasRole('admin') ||
                                    $authUser?->hasRole('accountant') ||
                                    app(\App\Services\ApprovalService::class)->canApproveAdjustmentStep($adjustment, $authUser)
                                );
                            @endphp
                            <div class="bg-white border rounded p-2 mb-2 small">
                                <div class="d-flex justify-content-between gap-2"><strong>#{{ $adjustment->id }}</strong><span class="badge text-bg-{{ $adjustmentClass }}">{{ $adjustment->status }}</span></div>
                                <div class="text-muted mt-1">{{ $adjustment->requester?->name ?? '-' }} · {{ optional($adjustment->submitted_at)->format('d/m H:i') }}</div>
                                @if($adjustment->adjustment_note)<div class="mt-1">{{ $adjustment->adjustment_note }}</div>@endif
                                @if($canApproveAdjustment)
                                    <div class="d-flex gap-2 mt-2">
                                        <form action="{{ route('site.order-adjustments.approve', $adjustment) }}" method="POST" class="flex-grow-1">
                                            @csrf
                                            <button class="btn btn-success btn-sm w-100" onclick="return confirm('Duyệt yêu cầu điều chỉnh #{{ $adjustment->id }}?')">Duyệt</button>
                                        </form>
                                        <button class="btn btn-outline-danger btn-sm flex-grow-1" data-bs-toggle="modal" data-bs-target="#rejectAdjustment{{ $adjustment->id }}">Từ chối</button>
                                    </div>
                                @endif
                            </div>

                            @if($canApproveAdjustment)
                                <div class="modal fade" id="rejectAdjustment{{ $adjustment->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog"><div class="modal-content">
                                        <form action="{{ route('site.order-adjustments.reject', $adjustment) }}" method="POST">
                                            @csrf
                                            <div class="modal-header"><h6 class="modal-title">Từ chối yêu cầu #{{ $adjustment->id }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body"><label class="form-label">Lý do từ chối</label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
                                            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button><button class="btn btn-danger btn-sm">Xác nhận từ chối</button></div>
                                        </form>
                                    </div></div>
                                </div>
                            @endif
                        @endforeach
                    </aside>
                @endif
            </div>

            <div class="card-footer bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><span class="text-muted small me-2">Tổng đơn hàng</span><strong class="text-primary fs-5">{{ $money($order->total) }}</strong></div>
                <div class="d-flex gap-2">
                    @if($order->delivered_at)<a href="{{ accounting_route('reconciliation', ['date' => $order->delivered_at->toDateString(), 'order_id' => $order->id]) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-check2-square me-1"></i>Đối soát</a>@endif
                    <a href="{{ accounting_route('orders.detail', $order) }}" class="btn btn-primary btn-sm"><i class="bi bi-eye me-1"></i>Xem chi tiết</a>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12"><div class="text-center text-muted py-5 acc-card">Không có đơn hàng nào trong bộ lọc hiện tại.</div></div>
@endforelse
</div>

<div class="mt-3">{{ $orders->links() }}</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.acc-order-nav-pill').forEach((pill) => {
    pill.addEventListener('click', (event) => {
        event.preventDefault();
        document.querySelectorAll('.acc-order-nav-pill').forEach((item) => item.classList.remove('active'));
        pill.classList.add('active');
        document.getElementById(`order-card-${pill.dataset.orderId}`)?.scrollIntoView({behavior: 'smooth', block: 'start'});
    });
});
</script>
@endpush
