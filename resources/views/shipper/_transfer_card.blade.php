@php
    $order = $transfer->order;
    $sequenceNumber = $transfer->sequence_number ?? $order->daily_sequence ?? '—';
    $deliveryTime = $order?->delivery_time ?: $order?->customer?->delivery_time ?: '—';
    $deliveryDate = optional($order?->delivery_date)->format('d/m/Y') ?: '—';
    $transferDate = !empty($transfer->dispatch_business_date)
        ? \Carbon\Carbon::parse($transfer->dispatch_business_date)->format('d/m/Y')
        : optional($transfer->created_at)->format('d/m/Y');
    $dispatchItems = collect($transfer->dispatch_items ?? []);
    $dispatchQuantity = (int) ($transfer->dispatch_total_quantity ?? $dispatchItems->sum('quantity'));
    $dispatchWeight = (float) ($transfer->dispatch_total_weight ?? $transfer->packed_total_weight ?? 0);
    $saleName = $order?->user?->name ?: $order?->customer?->currentOwner?->name ?: '—';
    $dispatchSlip = $transfer->dispatchEntry?->slip ?? $order?->orderTransfer?->dispatchEntry?->slip;
    $transferCode = $transfer->dispatch_slip_code ?: $dispatchSlip?->code ?: ('ĐC-' . str_pad((string) $transfer->id, 6, '0', STR_PAD_LEFT));
    $status = $status ?? match($transfer->status) {
        'pending_shipper_pickup' => 'pending',
        'in_transit' => 'transit',
        'delivered_waiting_receive' => 'waiting',
        'received_completed' => 'completed',
        'cancelled' => 'cancelled',
        default => 'other',
    };
@endphp
<div class="card border border-2 shadow-sm js-transfer-card wh-transfer-{{ $status }}" id="transfer-card-{{ $transfer->id }}">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center transfer-card-summary">
            <div class="transfer-nav-pill wh-transfer-{{ $status }} me-2" style="width:2rem;height:2rem;font-size:1rem;">{{ $sequenceNumber }}</div>
            <div class="text-truncate">
                <div class="fw-semibold">{{ $transferCode }} · {{ $order?->customer?->name ?? 'Khách hàng' }}</div>
                <div class="small text-muted text-truncate">
                    {{ $transfer->sourceWarehouse?->name ?? '—' }} → {{ $transfer->targetWarehouse?->name ?? '—' }}
                    · Sale: <strong class="text-dark">{{ $saleName }}</strong>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 text-nowrap transfer-card-actions">
            <span class="wh-transfer-status-badge wh-transfer-{{ $status }}">
                {{
                    $status === 'pending' ? 'Cần nhận' :
                    ($status === 'transit' ? 'Đang vận chuyển' :
                    ($status === 'waiting' ? 'Giao kho' :
                    ($status === 'cancelled' ? 'Đã hoàn lại' : 'Kho đã nhận')))
                }}
            </span>
            <span class="fw-bold text-primary"><i class="bi bi-clock me-1"></i>{{ $deliveryTime }}</span>
            @if($status === 'pending')
                <form method="POST" action="{{ route('shipper.warehouse-transfers.pickup', $transfer) }}" class="m-0 js-pickup-form">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check2-circle me-1"></i>Chấp nhận
                    </button>
                </form>
            @endif
            <button type="button"
                    class="btn btn-outline-primary btn-sm transfer-card-toggle"
                    data-bs-toggle="collapse"
                    data-bs-target="#transfer-details-{{ $transfer->id }}"
                    aria-expanded="false"
                    aria-controls="transfer-details-{{ $transfer->id }}">
                <i class="bi bi-eye me-1"></i>Chi tiết
                <i class="bi bi-chevron-down ms-1 transfer-card-chevron"></i>
            </button>
        </div>
    </div>
    <div class="collapse js-transfer-details" id="transfer-details-{{ $transfer->id }}">
    <div class="card-body">
        <div class="small text-muted mb-1">Kho gửi: <strong class="text-dark">{{ $transfer->sourceWarehouse?->name ?? '—' }}</strong></div>
        <div class="small text-muted mb-1">Kho nhận: <strong class="text-dark">{{ $transfer->targetWarehouse?->name ?? '—' }}</strong></div>
        <div class="small text-muted mb-1">Shipper phụ trách: <strong class="text-dark">{{ $transfer->shipper?->name ?? '—' }}</strong></div>
        <div class="small text-muted mb-1">Tổng số lượng chuyển: <strong class="text-primary fs-6">{{ number_format($dispatchQuantity) }}</strong></div>
        <div class="small text-muted mb-2">KL bàn giao: <strong class="text-dark">{{ $dispatchWeight > 0 ? number_format($dispatchWeight, 3, ',', '.') . ' kg' : '—' }}</strong></div>
        <div class="small text-muted mb-2">Ngày chuyển: <strong class="text-primary">{{ $transferDate ?: '—' }}</strong> · Ngày giao đơn: <strong class="text-dark">{{ $deliveryDate }}</strong></div>
        <div class="border-top pt-2 mt-2">
            <div class="small fw-semibold mb-1">Sản phẩm</div>
            @forelse($dispatchItems as $item)
                <div class="small text-muted d-flex justify-content-between gap-2">
                    <span>- {{ $item['product_name'] ?? 'Sản phẩm' }}{{ !empty($item['size']) ? ' · '.$item['size'] : '' }}{{ !empty($item['sku']) ? ' · '.$item['sku'] : '' }}</span>
                    <strong class="text-dark text-nowrap">SL: {{ number_format((int) ($item['quantity'] ?? 0)) }}</strong>
                </div>
            @empty
                <div class="small text-muted">Chưa có chi tiết hàng hóa.</div>
            @endforelse
        </div>
        <div class="small text-muted border-top pt-2 mt-2">
            Mã đơn: <strong class="text-dark">{{ $order?->code ?? ('#' . $transfer->order_id) }}</strong>
        </div>
        @if($transfer->delivery_proof_image)
            <div class="border-top pt-2 mt-2">
                <div class="small fw-semibold mb-1">Ảnh giao hàng</div>
                <a href="{{ asset('storage/' . $transfer->delivery_proof_image) }}" target="_blank" class="small">Xem ảnh bằng chứng</a>
            </div>
        @endif
    </div>
    @if($status !== 'pending')
    <div class="card-footer bg-white border-top">
        <div class="mb-2">
            <span class="wh-transfer-status-badge wh-transfer-{{ $status }}">
                {{
                    $status === 'pending' ? 'Cần nhận' :
                    ($status === 'transit' ? 'Đang vận chuyển' :
                    ($status === 'waiting' ? 'Giao kho' :
                    ($status === 'cancelled' ? 'Đã hoàn lại' : 'Kho đã nhận')))
                }}
            </span>
        </div>
        @if($status === 'transit')
            <form method="POST" action="{{ route('shipper.warehouse-transfers.deliver', $transfer) }}" enctype="multipart/form-data" class="d-grid gap-2 js-deliver-form">
                @csrf
                <textarea name="delivery_note" rows="2" class="form-control form-control-sm" placeholder="Ghi chú giao hàng cho kho nhận"></textarea>
                <input type="file" class="form-control form-control-sm" name="delivery_proof_image" accept="image/*">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-truck me-1"></i>Giao hàng cho kho nhận
                </button>
            </form>
        @elseif($status === 'waiting')
            <div class="d-flex flex-column flex-md-row gap-2 align-items-start">
                <span class="badge bg-info text-dark flex-grow-1">Đã giao, chờ kho nhận xác nhận</span>
                <form method="POST" action="{{ route('shipper.warehouse-transfers.rollback', $transfer) }}" class="js-rollback-transfer-form">
                    @csrf
                    <input type="hidden" name="rollback_note" value="">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Hoàn lại
                    </button>
                </form>
            </div>
        @elseif($status === 'completed')
            <span class="badge bg-success">Phiếu điều chuyển đã hoàn tất</span>
        @else
            <span class="badge bg-danger">Phiếu điều chuyển đã hoàn lại</span>
        @endif
    </div>
    @endif
    </div>
</div>
