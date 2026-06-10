@php
    $formatCompactDecimal = static function (float|int|string $value, int $decimals = 2): string {
        $num = (float) $value;
        $str = number_format($num, $decimals, ',', '.');
        return rtrim(rtrim($str, '0'), ',');
    };
    $formatKg = static fn (float|int|string $value): string => $formatCompactDecimal($value) . ' kg';
    $statusMeta = [
        'approved' => ['label' => 'Chờ đóng gói', 'class' => 'bg-primary'],
        'ready_to_pack' => ['label' => 'Chờ đóng gói', 'class' => 'bg-primary'],
        'packing' => ['label' => 'Đang đóng gói', 'class' => 'bg-warning text-dark'],
        'packed' => ['label' => 'Đã đóng gói', 'class' => 'bg-info text-dark'],
        'packed_waiting_pickup' => ['label' => 'Chờ shipper nhận', 'class' => 'bg-info text-dark'],
        'delivering' => ['label' => 'Đang giao', 'class' => 'bg-secondary'],
        'delivered' => ['label' => 'Đã giao', 'class' => 'bg-success'],
        'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-success'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'bg-danger'],
    ];
    $confirmedOrders = $orders->where('warehouse_adjustment_status', \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_CONFIRMED);
    $rejectedOrders = $orders->where('warehouse_adjustment_status', \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED);
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('package.order-changes') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-3">
                <label class="form-label small text-muted mb-1">Mã đơn / khách hàng</label>
                <input type="search" name="search" class="form-control" value="{{ $search }}" placeholder="Tìm mã đơn hoặc tên khách">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small text-muted mb-1">Từ ngày phản hồi</label>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small text-muted mb-1">Đến ngày phản hồi</label>
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label small text-muted mb-1">Phản hồi Sale</label>
                <select name="response_status" class="form-select">
                    <option value="">Tất cả phản hồi</option>
                    <option value="sale_confirmed" {{ $responseStatus === 'sale_confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    <option value="sale_rejected" {{ $responseStatus === 'sale_rejected' ? 'selected' : '' }}>Đã từ chối</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Lọc</button>
                <a href="{{ route('package.order-changes') }}" class="btn btn-outline-secondary">Xóa lọc</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex gap-2 flex-wrap mb-3">
    <span class="badge bg-dark wh-summary-pill">Tổng phản hồi: {{ $orders->count() }}</span>
    <span class="badge bg-success wh-summary-pill">Sale xác nhận: {{ $confirmedOrders->count() }}</span>
    <span class="badge bg-danger wh-summary-pill">Sale từ chối: {{ $rejectedOrders->count() }}</span>
</div>

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-inbox fs-1 text-secondary"></i>
        <p class="mt-2 mb-0 text-muted">Không có yêu cầu thay đổi đã được Sale phản hồi trong bộ lọc này.</p>
    </div>
@else
    <div class="wh-order-nav-area mb-4">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-bold text-muted me-1"><i class="bi bi-list-ol me-1"></i>Điều hướng nhanh:</span>
            @foreach($orders as $navOrder)
                <a href="javascript:void(0);"
                   onclick="document.getElementById('order-card-{{ $navOrder->id }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' });"
                   class="wh-order-nav-pill {{ $navOrder->warehouse_adjustment_status === 'sale_confirmed' ? 'is-packed' : 'bg-danger' }}"
                   title="{{ $navOrder->code }}">
                    {{ $navOrder->daily_sequence ?? $loop->iteration }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold text-success"><i class="bi bi-check-circle me-2"></i>Sale đã xác nhận</h5>
                <span class="badge bg-success rounded-pill">{{ $confirmedOrders->count() }} đơn</span>
            </div>
            <div class="row g-3">
                @foreach($confirmedOrders as $order)
                    @include('warehouse.orders._order_card', ['selectedDate' => $order->created_at->toDateString()])
                @endforeach
            </div>
        </div>
        <div class="col-12 col-xl-6 border-start">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Sale đã từ chối</h5>
                <span class="badge bg-danger rounded-pill">{{ $rejectedOrders->count() }} đơn</span>
            </div>
            <div class="row g-3">
                @foreach($rejectedOrders as $order)
                    @include('warehouse.orders._order_card', ['selectedDate' => $order->created_at->toDateString()])
                @endforeach
            </div>
        </div>
    </div>
@endif
