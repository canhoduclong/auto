@php
    $order = $transfer->order;
    $canConfirm = $transfer->status === 'delivered_waiting_receive';
    $statusMeta = $canConfirm
        ? ['Chờ tiếp nhận', 'bg-warning text-dark']
        : ['Đã tiếp nhận', 'bg-success'];
    $sequenceNumber = $transfer->sequence_number ?? ($loop->iteration ?? 1);
@endphp
<div class="card border-0 shadow-sm h-100" id="transfer-card-{{ $transfer->id }}">
    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center">
            @if(isset($isReceived) && !$isReceived)
                <div class="wh-order-index text-center me-2" style="background:#38bdf8 !important; color:#fff !important; border: 2px solid #38bdf8;">{{ $sequenceNumber }}</div>
            @else
                <div class="wh-order-index text-center me-2">{{ $sequenceNumber }}</div>
            @endif
            <div>
                <div class="fw-semibold">{{ $order?->customer?->name ?? 'Khách hàng' }}</div>
                <div class="small text-muted">{{ $order?->code ?? ('#' . $transfer->order_id) }} · Lên đơn {{ optional($order?->created_at)->format('d/m/Y') ?: '—' }} · Giao {{ optional($order?->delivery_date)->format('d/m/Y') ?: '—' }}</div>
            </div>
        </div>
        <span class="badge {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3 small"> 
            <div class="col-12 col-md-6">
                <div class="text-muted">KL tiếp nhận</div>
                <div class="fw-semibold">{{ $transfer->received_total_weight !== null ? format_kg((float) $transfer->received_total_weight) : '—' }}</div>
            </div>
            <div class="col-12 col-md-6">
                <div class="text-muted">Hao hụt</div>
                <div class="fw-semibold {{ (float) ($transfer->weight_loss ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $transfer->weight_loss !== null ? format_kg((float) $transfer->weight_loss) : '—' }}
                </div>
            </div>
        </div>
        <div class="border-top pt-3 mt-3">
            <div class="wh-item-table-wrap">
                <div class="wh-item-table-head">
                    <div>Ảnh</div>
                    <div>Sản phẩm</div>
                    <div class="text-center">Size</div>
                    <div class="text-center">SL</div>
                    <div class="text-center">Tổng</div>
                    <div class="text-end">Khối lượng</div>
                </div>
                <ul class="wh-item-list">
                    @foreach($order?->items ?? [] as $item)
                        @php
                            $variant = $item->variant;
                            $orderedQty = (int) ($item->quantity ?? 0);
                            $variantSize = $variant?->size;
                            $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                ? (string) $variantSize
                                : '-';
                            $itemWeight = (float) ($item->packed_weight ?? $item->total_weight ?? 0);
                            $imagePath = $variant?->avatar?->media?->file_path
                                ?? $item->product?->avatar?->media?->file_path
                                ?? null;
                        @endphp
                        <li class="wh-item-row">
                            <div class="wh-item-table-row">
                                <div>
                                    @if($imagePath)
                                        <img class="wh-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}">
                                    @else
                                        <span class="wh-item-thumb-placeholder">
                                            <i class="bi bi-image"></i>
                                        </span>
                                    @endif
                                </div>
                                <div class="wh-item-name">
                                    {{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}
                                    @if($variant?->sku)
                                        <span class="text-muted small">({{ $variant->sku }})</span>
                                    @endif
                                </div>
                                <div class="wh-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                <div class="wh-item-cell"><strong>{{ number_format($orderedQty) }}</strong></div>
                                <div class="wh-item-cell"><strong>{{ $item->display_total_label ?? '—' }}</strong></div>
                                <div class="wh-item-cell text-end"><strong>{{ format_kg($itemWeight) }}</strong></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center gap-2">
        <div class="byshipper" style="font-size:.8rem;">
            Từ kho: <strong>{{ $transfer->sourceWarehouse?->name ?? '—' }}</strong>, bởi :<strong>{{ $transfer->shipper?->name ?? '—' }}</strong>
        </div>
        <div class="actions">
        @if($canConfirm)
            <div class="d-flex justify-content-end gap-2">
                <form method="POST" action="{{ route('warehouse.transfers.rollback', $transfer) }}" class="js-rollback-transfer-form">
                    @csrf
                    <input type="hidden" name="rollback_note" value="">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Từ chối
                    </button>
                </form>
                <form method="POST" action="{{ route('warehouse.transfers.confirm-receipt', $transfer) }}" class="d-flex justify-content-end" onsubmit="return confirm('Xác nhận nhập hàng điều chuyển vào kho? Hệ thống sẽ tạo phiếu nhập và cập nhật tồn kho.');">
                    @csrf
                    <input type="hidden" name="receive_note" value="Đã nhận kho qua trang tiếp nhận nhanh">
                    @foreach($order?->items ?? [] as $item)
                        @php
                            $defaultWeight = (float) ($item->packed_weight ?? $item->total_weight ?? 0);
                        @endphp
                        <input type="hidden" name="item_weights[{{ $loop->index }}][order_item_id]" value="{{ $item->id }}">
                        <input type="hidden" name="item_weights[{{ $loop->index }}][received_weight]" value="{{ number_format($defaultWeight, 3, '.', '') }}">
                    @endforeach
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check2-circle me-1"></i>Xác nhận nhập vào kho
                    </button>
                </form>
            </div>
        @else
            <div class="text-end text-muted small">Đã xử lý</div>
        @endif
        </div>
    </div>
</div>
