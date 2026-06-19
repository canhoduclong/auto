@extends(accounting_layout())

@section('title', 'Chi tiết đơn hàng')
@section('subtitle', $order->code ?: ('#' . $order->id))

@section('accounting_content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' đ';
    $reconciliation = $order->accountingReconciliation;
    $isConfirmed = $reconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED;
    $statusLabels = [
        'delivered' => 'Đã giao hàng',
        'completed' => 'Hoàn thành',
        'delivering' => 'Đang giao hàng',
        'in_delivery' => 'Đang giao hàng',
        'returned' => 'Đã hoàn trả',
        'returned_completed' => 'Hoàn trả hoàn tất',
        'cancelled' => 'Đã hủy',
    ];
    $paymentLabels = [
        'paid' => 'Đã thanh toán',
        'partially_paid' => 'Thanh toán một phần',
        'partial' => 'Thanh toán một phần',
        'unpaid' => 'Chưa thanh toán',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-1">Đơn hàng {{ $order->code ?: ('#' . $order->id) }}</h5>
        <div class="text-muted small">
            Tạo lúc {{ optional($order->created_at)->format('d/m/Y H:i') ?: '-' }}
            · Sale: {{ $order->user?->name ?? '-' }}
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($order->customer)
            <a href="{{ accounting_route('customer-debts.show', $order->customer) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-wallet2 me-1"></i>Công nợ khách hàng
            </a>
        @endif
        <a href="{{ $reconciliationUrl }}" class="btn btn-primary btn-sm">
            <i class="bi bi-check2-square me-1"></i>Xem đối soát đơn hàng
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="acc-card h-100"><div class="card-body">
            <div class="text-muted small">Tổng đơn hàng</div>
            <div class="fs-5 fw-bold">{{ $money($order->total) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="acc-card h-100"><div class="card-body">
            <div class="text-muted small">Đã thu</div>
            <div class="fs-5 fw-bold text-success">{{ $money($effectivePaid) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="acc-card h-100"><div class="card-body">
            <div class="text-muted small">Hàng trả/hoàn</div>
            <div class="fs-5 fw-bold text-warning">{{ $money($returnAmount) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="acc-card h-100"><div class="card-body">
            <div class="text-muted small">Còn công nợ</div>
            <div class="fs-5 fw-bold {{ $effectiveDue > 0 ? 'text-danger' : 'text-success' }}">{{ $money($effectiveDue) }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="acc-card h-100"><div class="card-body">
            <h6 class="fw-bold mb-3">Khách hàng</h6>
            <div class="row g-2 small">
                <div class="col-4 text-muted">Tên khách hàng</div><div class="col-8 fw-semibold">{{ $order->customer?->name ?? '-' }}</div>
                <div class="col-4 text-muted">Điện thoại</div><div class="col-8">{{ $order->customer?->phone ?? '-' }}</div>
                <div class="col-4 text-muted">Email</div><div class="col-8">{{ $order->customer?->email ?? '-' }}</div>
                <div class="col-4 text-muted">Địa chỉ</div><div class="col-8">{{ $order->recipient_address ?: ($order->customer?->address ?? '-') }}</div>
                <div class="col-4 text-muted">Công ty</div><div class="col-8">{{ $order->customer?->company_name ?? '-' }}</div>
                <div class="col-4 text-muted">Mã số thuế</div><div class="col-8">{{ $order->customer?->tax_code ?? '-' }}</div>
            </div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="acc-card h-100"><div class="card-body">
            <h6 class="fw-bold mb-3">Giao hàng và đối soát</h6>
            <div class="row g-2 small">
                <div class="col-4 text-muted">Trạng thái đơn</div><div class="col-8"><span class="badge text-bg-light border">{{ $statusLabels[$order->status] ?? $order->status ?? '-' }}</span></div>
                <div class="col-4 text-muted">Thanh toán</div><div class="col-8">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status ?? '-' }}</div>
                <div class="col-4 text-muted">Shipper</div><div class="col-8">{{ $order->shipper?->name ?? '-' }}</div>
                <div class="col-4 text-muted">Kho xuất</div><div class="col-8">{{ $order->warehouse?->name ?? '-' }}</div>
                <div class="col-4 text-muted">Ngày giao</div><div class="col-8">{{ optional($order->delivered_at)->format('d/m/Y H:i') ?: '-' }}</div>
                <div class="col-4 text-muted">Phí giao hàng</div><div class="col-8">{{ $money($order->shipping_fee) }}</div>
                <div class="col-4 text-muted">Đối soát</div>
                <div class="col-8">
                    <span class="badge {{ $isConfirmed ? 'text-bg-success' : 'text-bg-warning' }}">{{ $isConfirmed ? 'Đã xác nhận' : 'Chưa xác nhận' }}</span>
                    @if($isConfirmed)
                        <div class="text-muted mt-1">{{ $reconciliation->confirmer?->name ?? '-' }} · {{ optional($reconciliation->confirmed_at)->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>
        </div></div>
    </div>
</div>

<div class="acc-card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Chi tiết sản phẩm</h6>
            <span class="text-muted small">Doanh thu ghi nhận: <strong class="text-success">{{ $money($recognizedRevenue) }}</strong></span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Phân loại/Size</th>
                        <th>SKU</th>
                        <th class="text-end">Số lượng</th>
                        <th class="text-end">Khối lượng</th>
                        <th class="text-end">Đơn giá</th>
                        <th class="text-end">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->product?->name ?? $item->variant?->product?->name ?? '-' }}</td>
                            <td>{{ $item->variant?->size ?: ($item->variant?->name ?? '-') }}</td>
                            <td>{{ $item->variant?->sku ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float) $item->quantity, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) ($item->actual_weight ?? $item->total_weight ?? 0), 3, ',', '.') }} kg</td>
                            <td class="text-end">{{ $money($item->price) }}</td>
                            <td class="text-end fw-semibold">{{ $money($item->total ?? ((float) $item->quantity * (float) $item->price)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Đơn hàng chưa có sản phẩm.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="acc-card h-100"><div class="card-body">
            <h6 class="fw-bold mb-3">Giao dịch thanh toán</h6>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Ngày</th><th>Loại</th><th>Trạng thái</th><th class="text-end">Số tiền</th></tr></thead>
                    <tbody>
                        @forelse($order->transactions as $transaction)
                            <tr>
                                <td class="text-nowrap">{{ optional($transaction->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $transaction->type }}</td>
                                <td>{{ $transaction->status ?? '-' }}</td>
                                <td class="text-end fw-semibold">{{ $money($transaction->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Chưa có giao dịch.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="acc-card h-100"><div class="card-body">
            <h6 class="fw-bold mb-3">Lịch sử xử lý gần nhất</h6>
            <div class="list-group list-group-flush">
                @forelse($order->histories->sortByDesc('id')->take(10) as $history)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="fw-semibold">{{ $history->action ?: 'Cập nhật đơn hàng' }}</span>
                            <span class="text-muted small text-nowrap">{{ optional($history->created_at)->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="small text-muted">{{ $history->user?->name ?? 'Hệ thống' }} @if($history->note) · {{ $history->note }} @endif</div>
                    </div>
                @empty
                    <div class="text-muted">Chưa có lịch sử xử lý.</div>
                @endforelse
            </div>
        </div></div>
    </div>
</div>
@endsection
