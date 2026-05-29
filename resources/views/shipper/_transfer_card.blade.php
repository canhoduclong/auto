@php
    $order = $transfer->order;
    $sequenceNumber = $order->daily_sequence ?? '—';
    $status = $status ?? match($transfer->status) {
        'pending_shipper_pickup' => 'pending',
        'in_transit' => 'transit',
        'delivered_waiting_receive' => 'waiting',
        'received_completed' => 'completed',
        default => 'other',
    };
@endphp
<div class="card border-0 shadow-sm h-100 js-transfer-card" id="transfer-card-{{ $transfer->id }}">
    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center">
            <div class="transfer-nav-pill {{
                $status === 'pending' ? 'status-pending' :
                ($status === 'transit' ? 'status-transit' :
                ($status === 'waiting' ? 'status-waiting' :
                ($status === 'completed' ? 'status-completed' : 'status-other')))
            }} me-2" style="width:2rem;height:2rem;font-size:1rem;">{{ $sequenceNumber }}</div>
            <div>
                <div class="fw-semibold">{{ $order?->code ?? ('#' . $transfer->order_id) }}</div>
                <div class="small text-muted">{{ $order?->customer?->name ?? 'Khách hàng' }}</div>
            </div>
        </div>
        <span class="badge {{
            $status === 'pending' ? 'bg-success' :
            ($status === 'transit' ? 'bg-warning text-dark' :
            ($status === 'waiting' ? 'bg-info text-dark' : 'bg-light text-dark'))
        }}">
            {{
                $status === 'pending' ? 'Cần nhận' :
                ($status === 'transit' ? 'Đang vận chuyển' :
                ($status === 'waiting' ? 'Đã giao, chờ xác nhận' : ''))
            }}
        </span>
    </div>
    <div class="card-body">
        <div class="small text-muted mb-1">Kho gửi: <strong class="text-dark">{{ $transfer->sourceWarehouse?->name ?? '—' }}</strong></div>
        <div class="small text-muted mb-1">Kho nhận: <strong class="text-dark">{{ $transfer->targetWarehouse?->name ?? '—' }}</strong></div>
        <div class="small text-muted mb-1">Shipper phụ trách: <strong class="text-dark">{{ $transfer->shipper?->name ?? '—' }}</strong></div>
        <div class="small text-muted mb-2">KL đóng gói: <strong class="text-dark">{{ $transfer->packed_total_weight !== null ? number_format((float) $transfer->packed_total_weight, 3, ',', '.') . ' kg' : '—' }}</strong></div>
        <div class="border-top pt-2 mt-2">
            <div class="small fw-semibold mb-1">Sản phẩm</div>
            @foreach($order?->items ?? [] as $item)
                <div class="small text-muted">
                    - {{ $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm' }} (SL: {{ (int) ($item->quantity ?? 0) }})
                </div>
            @endforeach
        </div>
        @if($transfer->delivery_proof_image)
            <div class="border-top pt-2 mt-2">
                <div class="small fw-semibold mb-1">Ảnh giao hàng</div>
                <a href="{{ asset('storage/' . $transfer->delivery_proof_image) }}" target="_blank" class="small">Xem ảnh bằng chứng</a>
            </div>
        @endif
    </div>
    <div class="card-footer bg-white border-top">
        @if($status === 'pending')
            <form method="POST" action="{{ route('shipper.warehouse-transfers.pickup', $transfer) }}" class="d-grid gap-2 js-pickup-form">
                @csrf
                <textarea name="pickup_note" rows="2" class="form-control form-control-sm" placeholder="Ghi chú khi nhận hàng (nếu có)"></textarea>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-box-arrow-in-down me-1"></i>Xác nhận nhận hàng
                </button>
            </form>
        @elseif($status === 'transit')
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
        @else
            <span class="badge bg-success">Phiếu điều chuyển đã hoàn tất</span>
        @endif
    </div>
</div>