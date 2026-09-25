@extends('layouts.warehouse')

@section('title', 'Tiếp nhận')
@section('subtitle', 'Tiếp nhận đơn và hàng điều chuyển vào kho')

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
    /* --- Order Sequence Navigation --- */
    .wh-order-nav-area {
        position: sticky;
        top: 75px;
        z-index: 95;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        overflow-x: auto;
    }
    .wh-order-nav-track {
        display: flex;
        align-items: center;
        gap: 20px;
        width: max-content;
        min-width: 100%;
    }
    .wh-order-nav-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 0 0 auto;
    }
    .wh-order-nav-time {
        width: 64px;
        min-width: 64px;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #6366f1;
        font-weight: 700;
        color: #4338ca;
        background: #fff;
    }
    .wh-order-nav-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 8px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        color: #fff !important;
        background-color: #6c757d;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 2px solid transparent;
    }
    .wh-order-nav-pill.is-unpacked {
        background-color: #38bdf8 !important; /* xanh da trời */
        color: #fff !important;
    }
    .wh-order-nav-pill.is-packed {
        background-color: var(--theme-primary) !important;
        color: #fff !important;
    }
    .wh-order-index {
        border-radius: 50px;
        width: 35px;
        z-index: 2;
        font-weight: 700;
        padding: 3px 8px;
        background: var(--theme-primary) !important;
        color: #fff;
        margin-right: 12px;
    }
    .wh-order-nav-pill.active, .wh-order-nav-pill:focus {
        border: 2px solid #2563eb !important;
        color: #2563eb !important;
        background: #e0e7ff !important;
    }
    .wh-order-nav-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .wh-order-index {
        border-radius: 50px;
        width: 40px;
        z-index: 2;
        font-weight: 700;
        padding: 8px 8px;
        background: #0f172a;
        color: #fff;
        margin-right: 12px;
    }
</style>
@endpush

@section('content')

@php
    $receivedTransfers = $transfers->where('status', 'received_completed');
    $pendingTransfers = $transfers->where('status', 'delivered_waiting_receive');
    $extractDeliveryHour = static function ($deliveryTime): ?int {
        if (!preg_match('/^\s*(\d{1,2})(?=\D|$)/u', trim((string) $deliveryTime), $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        return $hour >= 0 && $hour <= 23 ? $hour : null;
    };
    $timelineTransfers = $transfers->groupBy(function ($transfer) use ($extractDeliveryHour) {
        $deliveryTime = $transfer->order?->delivery_time ?: $transfer->order?->customer?->delivery_time;
        return $extractDeliveryHour($deliveryTime);
    })->forget(null)->sortKeys();
    $timelineHours = $timelineTransfers->keys();
@endphp

<div class="d-flex gap-2 mb-3">
    <a href="#incoming-orders" class="btn btn-sm btn-primary"><i class="bi bi-receipt me-1"></i>Tiếp nhận đơn</a>
    <a href="#incoming-goods" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-in-down me-1"></i>Tiếp nhận hàng</a>
    <a href="#pull-from-shipper" class="btn btn-sm btn-outline-warning"><i class="bi bi-truck-flatbed me-1"></i>Kéo đơn về kho <span class="badge bg-warning text-dark ms-1">{{ $shipperTransfers->count() }}</span></a>
</div>

@php
    $firstOrder = $transfers->first()?->order;
    $orderCreationDate = optional($firstOrder?->created_at)->format('d/m/Y') ?: '—';
    $orderDeliveryDate = optional($firstOrder?->delivery_date)->format('d/m/Y') ?: '—';
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 alert alert-info py-2 mb-3">
    <span>
        <i class="bi bi-calendar-event me-1"></i>
        Ngày xem <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</strong>
        · Lọc theo ngày giao; ngày lên đơn được hiển thị riêng ở từng đơn.
    </span>
    <form method="GET" class="d-flex gap-2">
        <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm">
        <button class="btn btn-sm btn-primary" type="submit">Xem</button>
    </form>
</div>

<div class="wh-order-nav-area mb-4">
    <div class="fw-bold text-muted mb-2"><i class="bi bi-clock-history me-1"></i>Điều hướng theo giờ giao</div>
    <div class="wh-order-nav-track">
        @forelse($timelineHours as $hour)
            <div class="wh-order-nav-group">
                <span class="wh-order-nav-time">{{ $hour }}:00</span>
                <div class="d-flex flex-nowrap gap-2">
                    @foreach($timelineTransfers->get($hour, collect()) as $navTransfer)
                        @php
                            $sequenceNumber = $navTransfer->sequence_number ?? $loop->iteration;
                            $isReceived = $navTransfer->status === 'received_completed';
                        @endphp
                        <a href="javascript:void(0);"
                           onclick="scrollToTransferCard({{ $navTransfer->id }}, this)"
                           class="wh-order-nav-pill {{ $isReceived ? 'is-packed' : 'is-unpacked' }}"
                           id="nav-pill-{{ $navTransfer->id }}"
                           title="{{ $navTransfer->order?->customer?->name ?? 'Đơn hàng' }}">
                            {{ $sequenceNumber }}
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <span class="text-muted small">Không có đơn có giờ giao.</span>
        @endforelse
    </div>
</div>

<div class="row g-4" id="incoming-orders">
    <div class="col-12 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold" style="color: var(--theme-primary) !important;">
                <i class="bi bi-check-circle me-2"></i>Đã nhận hàng
            </h5>
            <span class="badge rounded-pill" style="background: var(--theme-primary) !important; color: #fff;">{{ $receivedTransfers->count() }} đơn</span>
        </div>
        <div class="row g-3">
            @forelse($receivedTransfers as $transfer)
                @include('warehouse.transfers._transfer_card', ['transfer' => $transfer, 'isReceived' => true])
            @empty
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Không có đơn đã nhận.</p>
                </div>
            @endforelse
        </div>
    </div>
    <div class="col-12 col-lg-6 border-start">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold" style="color:#38bdf8">
                <i class="bi bi-truck me-2"></i>Hàng được điều chuyển tới - Cần tiếp nhận
            </h5>
            <span class="badge rounded-pill" style="background:#38bdf8; color:#fff;">{{ $pendingTransfers->count() }} đơn</span>
        </div>
        <div class="row g-3">
            @forelse($pendingTransfers as $transfer)
                @include('warehouse.transfers._transfer_card', ['transfer' => $transfer, 'isReceived' => false])
            @empty
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">Không có đơn cần tiếp nhận.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<section id="pull-from-shipper" class="mt-5 pt-3 border-top">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="mb-1 fw-bold"><i class="bi bi-truck-flatbed me-2"></i>Kéo đơn về kho</h4><div class="small text-muted">Dùng khi shipper đã mang hàng tới nhưng quên bấm Giao. Sau khi kéo, đơn chuyển sang danh sách chờ kiểm cân và xác nhận.</div></div>
        <span class="badge bg-warning text-dark rounded-pill">Đang ở Shipper: {{ $shipperTransfers->count() }}</span>
    </div>
    <div class="row g-3">
        @forelse($shipperTransfers as $transfer)
            <div class="col-12 col-xl-6"><div class="card border-warning shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between gap-2"><div><strong>{{ $transfer->order?->code ?? '#'.$transfer->order_id }}</strong><div class="small text-muted">{{ $transfer->order?->customer?->name ?? 'Khách hàng' }} · {{ $transfer->sourceWarehouse?->name ?? '—' }} → {{ $transfer->targetWarehouse?->name ?? '—' }}</div></div><span class="badge bg-warning text-dark align-self-start"><i class="bi bi-person-fill me-1"></i>{{ $transfer->shipper?->short_name ?: ($transfer->shipper?->name ?? 'Chưa rõ shipper') }} đang giữ</span></div>
                <div class="card-body small"><div class="alert alert-warning py-2 mb-2"><span class="text-muted">Shipper đang giữ đơn:</span> <strong class="fs-6">{{ $transfer->shipper?->name ?? 'Chưa xác định' }}</strong>@if($transfer->shipper?->phone)<span class="ms-2">· {{ $transfer->shipper->phone }}</span>@endif</div><div>Nhận lúc: <strong>{{ optional($transfer->picked_up_at)->format('d/m/Y H:i') ?: '—' }}</strong></div><div class="mt-2 text-muted">{{ $transfer->order?->items?->count() ?? 0 }} mặt hàng · {{ format_kg((float) ($transfer->packed_total_weight ?? 0)) }}</div></div>
                <div class="card-footer bg-white text-end"><form method="POST" action="{{ route('warehouse.transfers.pull-to-warehouse', $transfer) }}" onsubmit="return confirm('Kéo đơn này từ Shipper về danh sách chờ kho xác nhận?')">@csrf<input type="hidden" name="note" value="Kho chủ động kéo đơn về do shipper chưa bấm giao"><button class="btn btn-warning btn-sm"><i class="bi bi-arrow-down-square me-1"></i>Kéo về kho</button></form></div>
            </div></div>
        @empty
            <div class="col-12 text-center text-muted py-4"><i class="bi bi-check2-circle fs-1 d-block"></i>Không có đơn nào đang ở Shipper cần kéo về.</div>
        @endforelse
    </div>
</section>

<section id="incoming-goods" class="mt-5 pt-3 border-top">
    @php($pendingInventoryCount = $inventoryTransfers->where('status', 'pending_receive')->count())
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h4 class="mb-1 fw-bold"><i class="bi bi-box-arrow-in-down me-2"></i>Tiếp nhận hàng điều chuyển</h4><div class="text-muted small">Các phiếu hàng được chuyển tới kho đang quản lý.</div></div>
        <span class="badge bg-warning text-dark rounded-pill">Chờ tiếp nhận: {{ number_format($pendingInventoryCount) }}</span>
    </div>
    <div class="row g-3">
    @forelse($inventoryTransfers as $inventoryTransfer)
        @php($isPendingInventory = $inventoryTransfer->status === 'pending_receive')
        <div class="col-12 col-xl-6"><div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><div><strong>{{ $inventoryTransfer->transfer_code ?? '#'.$inventoryTransfer->id }}</strong><div class="small text-muted">Từ {{ $inventoryTransfer->sourceWarehouse?->name ?? '—' }} · {{ optional($inventoryTransfer->requested_at ?? $inventoryTransfer->created_at)->format('d/m/Y H:i') }}</div></div><span class="badge {{ $isPendingInventory ? 'bg-warning text-dark' : 'bg-success' }}">{{ $isPendingInventory ? 'Chờ tiếp nhận' : 'Đã tiếp nhận' }}</span></div>
            <div class="card-body">
                @if($inventoryTransfer->order)<div class="alert alert-info py-2 small">Hàng cho đơn <strong>{{ $inventoryTransfer->order->code ?: '#'.$inventoryTransfer->order->id }}</strong> · {{ $inventoryTransfer->order->customer?->name }}</div>@endif
                @foreach($inventoryTransfer->items as $item)<div class="d-flex justify-content-between gap-2 border-bottom py-2"><span>{{ $item->variant?->product?->name ?? 'Sản phẩm' }} · Size {{ $item->variant?->size ?? '—' }}</span><strong class="text-nowrap">{{ number_format($item->quantity) }} · {{ number_format((float)$item->weight_kg,3,',','.') }} kg</strong></div>@endforeach
                <div class="text-end fw-bold text-primary pt-2">Tổng KL: {{ number_format((float)$inventoryTransfer->items->sum('weight_kg'),3,',','.') }} kg</div>
            </div>
            @if($isPendingInventory)<div class="card-footer bg-white text-end"><form method="POST" action="{{ route('warehouse.inventory-transfers.confirm', $inventoryTransfer) }}" onsubmit="return confirm('Xác nhận tiếp nhận và nhập kho phiếu này?')">@csrf<button class="btn btn-success btn-sm"><i class="bi bi-check2-circle me-1"></i>Xác nhận nhập kho</button></form></div>@endif
        </div></div>
    @empty
        <div class="col-12 text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block"></i>Không có phiếu hàng điều chuyển.</div>
    @endforelse
    </div>
</section>

@push('scripts')
<script>
function scrollToTransferCard(id, el) {
    const card = document.getElementById('transfer-card-' + id);
    if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => {
            document.querySelectorAll('.wh-order-nav-pill').forEach(pill => pill.classList.remove('active'));
            if (el) el.classList.add('active');
        }, 100);
    }
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-rollback-transfer-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const accepted = window.confirm('Từ chối tiếp nhận phiếu điều chuyển này? Phiếu sẽ bị hủy và không nhập hàng vào kho.');
            if (!accepted) {
                return;
            }

            const reason = window.prompt('Nhập lý do từ chối (không bắt buộc):', '');
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

    document.querySelectorAll('.js-undo-transfer-receipt-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!window.confirm('Gỡ tiếp nhận đơn này? Phiếu nhập và tồn kho đã tiếp nhận sẽ được hoàn tác.')) {
                return;
            }

            const reason = window.prompt('Nhập lý do gỡ tiếp nhận:', 'Nhận nhầm đơn');
            if (reason === null || reason.trim() === '') {
                return;
            }

            form.querySelector('input[name="undo_note"]').value = reason.trim();
            form.submit();
        });
    });
});
</script>
@endpush
@endsection
