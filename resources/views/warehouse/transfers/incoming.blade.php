@extends('layouts.warehouse')

@section('title', 'Tiếp nhận hàng điều chuyển')
@section('subtitle', 'Đơn hàng điều chuyển từ kho khác qua shipper')

@push('styles')
<style>
    .wh-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .wh-item-table-wrap {
        overflow-x: auto;
    }
    .wh-item-table-head,
    .wh-item-table-row {
        display: grid;
        grid-template-columns: 48px minmax(50px, 1fr) 42px 52px 86px 86px;
        gap: 8px;
        align-items: center;
    }
    .wh-item-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 6px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .wh-item-row {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .wh-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .wh-item-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #fff;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }
    .wh-item-thumb-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        margin-left: auto;
        margin-right: auto;
    }
    .wh-item-name {
        font-size: .86rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .wh-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: center;
    }
    .wh-item-cell strong {
        color: #0f172a;
    }
    @media (max-width: 767.98px) {
        .wh-item-table-head,
        .wh-item-table-row {
            grid-template-columns: 44px minmax(120px, 1fr) 42px 52px 70px 70px;
        }
    }
</style>
@endpush

@section('content')
@php
    $pendingCount = $transfers->where('status', 'delivered_waiting_receive')->count();
    $doneCount = $transfers->where('status', 'received_completed')->count();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-warning text-dark rounded-pill">Chờ tiếp nhận: {{ $pendingCount }}</span>
        <span class="badge bg-success rounded-pill">Đã tiếp nhận: {{ $doneCount }}</span>
    </div>
    <a href="{{ route('warehouse.orders') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Quay lại đơn kho
    </a>
</div>

@if($transfers->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Không có đơn điều chuyển nào cần tiếp nhận.</p>
    </div>
@else
    <div class="row g-3">
        @foreach($transfers as $transfer)
            @php
                $order = $transfer->order;
                $canConfirm = $transfer->status === 'delivered_waiting_receive';
                $statusMeta = $canConfirm
                    ? ['Chờ tiếp nhận', 'bg-warning text-dark']
                    : ['Đã tiếp nhận', 'bg-success'];
            @endphp
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <div class="fw-semibold">{{ $order?->customer?->name ?? 'Khách hàng' }}</div>
                            <div class="small text-muted">{{ $order?->code ?? ('#' . $transfer->order_id) }}, {{ optional($transfer->delivered_at)->format('d/m/Y H:i') ?: '—' }}, {{ $transfer->sourceWarehouse?->name ?? '—' }}</div>
                            
                        </div>
                        <span class="badge {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
                    </div>

                    <div class="card-body">
                        <div class="row g-2 mb-3 small"> 
                            <div class="col-12 col-md-6">
                                <div class="text-muted">KL tiếp nhận</div>
                                <div class="fw-semibold">{{ $transfer->received_total_weight !== null ? number_format((float) $transfer->received_total_weight, 3, ',', '.') . ' kg' : '—' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="text-muted">Hao hụt</div>
                                <div class="fw-semibold {{ (float) ($transfer->weight_loss ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $transfer->weight_loss !== null ? number_format((float) $transfer->weight_loss, 3, ',', '.') . ' kg' : '—' }}
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
                                                <div class="wh-item-cell text-end"><strong>{{ number_format($itemWeight, 3, ',', '.') }} kg</strong></div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center gap-2">
                        <div class="byshipper fw-semibold">
                            {{ $transfer->shipper?->name ?? '—' }}
                        </div>
                        <div class="actions">
                        @if($canConfirm)
                            <div class="d-flex justify-content-end gap-2">
                                <form method="POST" action="{{ route('warehouse.transfers.rollback', $transfer) }}" class="js-rollback-transfer-form">
                                    @csrf
                                    <input type="hidden" name="rollback_note" value="">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Hoàn lại
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('warehouse.transfers.confirm-receipt', $transfer) }}" class="d-flex justify-content-end" onsubmit="return confirm('Xác nhận nhập kho cho đơn này? Hệ thống sẽ tạo phiếu nhập và cập nhật tồn kho.');">
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
                                        <i class="bi bi-check2-circle me-1"></i>Xác nhận nhận vào kho
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="text-end text-muted small">Đã xử lý</div>
                        @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-rollback-transfer-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const accepted = window.confirm('Xác nhận hoàn lại phiếu điều chuyển này trước khi nhập kho?');
            if (!accepted) {
                return;
            }

            const reason = window.prompt('Nhập lý do hoàn lại (không bắt buộc):', '');
            if (reason === null) {
                return;
            }

            const noteInput = form.querySelector('input[name="rollback_note"]');
            if (noteInput) {
                noteInput.value = reason.trim();
            }

            form.submit();
        });
    });
});
</script>
@endpush
@endsection
