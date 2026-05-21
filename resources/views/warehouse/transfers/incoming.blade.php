@extends('layouts.warehouse')

@section('title', 'Tiếp nhận hàng điều chuyển')
@section('subtitle', 'Đơn hàng điều chuyển từ kho khác qua shipper')

@section('content')
@php
    $pendingCount = $transfers->where('status', 'delivered_waiting_receive')->count();
    $doneCount = $transfers->where('status', 'received_completed')->count();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
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
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Mã đơn</th>
                        <th>Kho gửi</th>
                        <th>Shipper</th>
                        <th>Đã giao lúc</th>
                        <th>KL đóng gói</th>
                        <th>KL tiếp nhận</th>
                        <th>Hao hụt</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $idx => $transfer)
                        @php
                            $order = $transfer->order;
                            $canConfirm = $transfer->status === 'delivered_waiting_receive';
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $order?->code ?? ('#' . $transfer->order_id) }}</div>
                                <div class="small text-muted">{{ $order?->customer?->name ?? 'Khách hàng' }}</div>
                            </td>
                            <td>{{ $transfer->sourceWarehouse?->name ?? '—' }}</td>
                            <td>{{ $transfer->shipper?->name ?? '—' }}</td>
                            <td class="small text-muted">{{ optional($transfer->delivered_at)->format('d/m/Y H:i') ?: '—' }}</td>
                            <td>{{ $transfer->packed_total_weight !== null ? number_format((float) $transfer->packed_total_weight, 3, ',', '.') . ' kg' : '—' }}</td>
                            <td>{{ $transfer->received_total_weight !== null ? number_format((float) $transfer->received_total_weight, 3, ',', '.') . ' kg' : '—' }}</td>
                            <td>
                                @if($transfer->weight_loss !== null)
                                    <span class="{{ (float) $transfer->weight_loss > 0 ? 'text-danger' : 'text-success' }} fw-semibold">
                                        {{ number_format((float) $transfer->weight_loss, 3, ',', '.') }} kg
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($canConfirm)
                                    <span class="badge bg-warning text-dark">Chờ tiếp nhận</span>
                                @else
                                    <span class="badge bg-success">Đã tiếp nhận</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($canConfirm)
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#receive-form-{{ $transfer->id }}">
                                        <i class="bi bi-check2-circle me-1"></i>Xác nhận
                                    </button>
                                @else
                                    <span class="text-muted small">Đã xử lý</span>
                                @endif
                            </td>
                        </tr>
                        @if($canConfirm)
                            <tr class="collapse" id="receive-form-{{ $transfer->id }}">
                                <td colspan="10" class="bg-light">
                                    <form method="POST" action="{{ route('warehouse.transfers.confirm-receipt', $transfer) }}" class="p-2">
                                        @csrf
                                        <div class="fw-semibold mb-2">Nhập khối lượng tiếp nhận theo từng sản phẩm</div>
                                        <div class="row g-2">
                                            @foreach($order?->items ?? [] as $item)
                                                @php
                                                    $defaultWeight = (float) ($item->packed_weight ?? $item->total_weight ?? 0);
                                                @endphp
                                                <input type="hidden" name="item_weights[{{ $loop->index }}][order_item_id]" value="{{ $item->id }}">
                                                <div class="col-12 col-md-6 col-xl-4">
                                                    <label class="form-label small mb-1">
                                                        {{ $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}
                                                        <span class="text-muted">(SL: {{ (int) ($item->quantity ?? 0) }})</span>
                                                    </label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number"
                                                               step="0.001"
                                                               min="0"
                                                               name="item_weights[{{ $loop->index }}][received_weight]"
                                                               class="form-control"
                                                               value="{{ number_format($defaultWeight, 3, '.', '') }}"
                                                               required>
                                                        <span class="input-group-text">kg</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <div class="col-12">
                                                <label class="form-label small mb-1">Ghi chú tiếp nhận</label>
                                                <textarea class="form-control form-control-sm" name="receive_note" rows="2" placeholder="Ghi chú tình trạng hàng khi nhận"></textarea>
                                            </div>
                                            <div class="col-12 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-save2 me-1"></i>Lưu và xác nhận tiếp nhận
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
