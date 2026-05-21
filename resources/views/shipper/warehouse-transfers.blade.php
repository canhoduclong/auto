@extends('layouts.shipper')

@section('title', 'Điều chuyển kho')
@section('subtitle', 'Nhận - giao hàng điều chuyển giữa các kho')

@section('content')
@php
    $pendingPickup = $transfers->where('status', 'pending_shipper_pickup')->count();
    $inTransit = $transfers->where('status', 'in_transit')->count();
    $waitingReceive = $transfers->where('status', 'delivered_waiting_receive')->count();
    $isManagerShipper = auth()->user()?->hasRole('manager_shipper') || auth()->user()?->hasRole('admin');
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-secondary rounded-pill">Chờ nhận: {{ $pendingPickup }}</span>
        <span class="badge bg-warning text-dark rounded-pill">Đang vận chuyển: {{ $inTransit }}</span>
        <span class="badge bg-info text-dark rounded-pill">Đã giao kho nhận: {{ $waitingReceive }}</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($isManagerShipper && ($pendingPickup > 0 || $inTransit > 0))
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnToggleBulkMode">
                <i class="bi bi-check2-square me-1"></i>Chọn nhiều
            </button>
        @endif
        <a href="{{ route('shipper.my-orders') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Quay lại đơn giao
        </a>
    </div>
</div>

{{-- Bulk action bar (chỉ hiện khi ở chế độ chọn nhiều) --}}
@if($isManagerShipper)
<div id="bulkActionBar" class="card border-primary shadow-sm mb-3 d-none">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="fw-semibold small text-primary">
                <span id="bulkSelectedCount">0</span> phiếu đã chọn
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSelectAllPending">
                    <i class="bi bi-check-all me-1"></i>Chọn tất cả "Chờ nhận"
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm" id="btnSelectAllTransit">
                    <i class="bi bi-check-all me-1"></i>Chọn tất cả "Đang vận chuyển"
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-sm" id="btnDeselectAll">
                    <i class="bi bi-x-circle me-1"></i>Bỏ chọn tất cả
                </button>
            </div>
            <div class="ms-auto d-flex gap-2 flex-wrap">
                {{-- Bulk Pickup form --}}
                <form id="bulkPickupForm" method="POST" action="{{ route('shipper.warehouse-transfers.bulk-pickup') }}" class="d-inline-flex gap-2 align-items-center">
                    @csrf
                    <div id="bulkPickupIds"></div>
                    <input type="text" name="pickup_note" class="form-control form-control-sm" style="width:200px" placeholder="Ghi chú nhận hàng (tùy chọn)">
                    <button type="submit" class="btn btn-primary btn-sm" id="btnBulkPickup" disabled>
                        <i class="bi bi-box-arrow-in-down me-1"></i>Nhận hàng tất cả đã chọn
                    </button>
                </form>
                {{-- Bulk Deliver form --}}
                <form id="bulkDeliverForm" method="POST" action="{{ route('shipper.warehouse-transfers.bulk-deliver') }}" class="d-inline-flex gap-2 align-items-center">
                    @csrf
                    <div id="bulkDeliverIds"></div>
                    <input type="text" name="delivery_note" class="form-control form-control-sm" style="width:200px" placeholder="Ghi chú giao hàng (tùy chọn)">
                    <button type="submit" class="btn btn-success btn-sm" id="btnBulkDeliver" disabled>
                        <i class="bi bi-truck me-1"></i>Giao hàng tất cả đã chọn
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@if($transfers->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-truck fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Không có phiếu điều chuyển nào.</p>
    </div>
@else
    <div class="row g-3" id="transferList">
        @foreach($transfers as $transfer)
            @php
                $order = $transfer->order;
                $statusMeta = match($transfer->status) {
                    'pending_shipper_pickup' => ['Chờ nhận hàng', 'bg-secondary'],
                    'in_transit' => ['Đang vận chuyển', 'bg-warning text-dark'],
                    'delivered_waiting_receive' => ['Đã giao kho nhận', 'bg-info text-dark'],
                    'received_completed' => ['Đã tiếp nhận', 'bg-success'],
                    default => [strtoupper($transfer->status), 'bg-light text-dark'],
                };
                $isBulkable = in_array($transfer->status, ['pending_shipper_pickup', 'in_transit']);
            @endphp
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100 js-transfer-card {{ $isBulkable ? '' : 'opacity-75' }}"
                     data-transfer-id="{{ $transfer->id }}"
                     data-status="{{ $transfer->status }}">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
                        @if($isManagerShipper && $isBulkable)
                            <div class="bulk-checkbox-wrap d-none me-1">
                                <input type="checkbox" class="form-check-input fs-5 js-transfer-checkbox"
                                       value="{{ $transfer->id }}"
                                       data-status="{{ $transfer->status }}"
                                       style="cursor:pointer">
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $order?->code ?? ('#' . $transfer->order_id) }}</div>
                            <div class="small text-muted">{{ $order?->customer?->name ?? 'Khách hàng' }}</div>
                        </div>
                        <span class="badge {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
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
                                    - {{ $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}
                                    (SL: {{ (int) ($item->quantity ?? 0) }})
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
                        @if($transfer->status === 'pending_shipper_pickup')
                            <form method="POST" action="{{ route('shipper.warehouse-transfers.pickup', $transfer) }}" class="d-grid gap-2">
                                @csrf
                                <textarea name="pickup_note" rows="2" class="form-control form-control-sm" placeholder="Ghi chú khi nhận hàng (nếu có)"></textarea>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-box-arrow-in-down me-1"></i>Xác nhận nhận hàng
                                </button>
                            </form>
                        @elseif($transfer->status === 'in_transit')
                            <form method="POST" action="{{ route('shipper.warehouse-transfers.deliver', $transfer) }}" enctype="multipart/form-data" class="d-grid gap-2">
                                @csrf
                                <textarea name="delivery_note" rows="2" class="form-control form-control-sm" placeholder="Ghi chú giao hàng cho kho nhận"></textarea>
                                <input type="file" class="form-control form-control-sm" name="delivery_proof_image" accept="image/*">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-truck me-1"></i>Giao hàng cho kho nhận
                                </button>
                            </form>
                        @elseif($transfer->status === 'delivered_waiting_receive')
                            <span class="badge bg-info text-dark">Đã giao, chờ kho nhận xác nhận tiếp nhận</span>
                        @else
                            <span class="badge bg-success">Phiếu điều chuyển đã hoàn tất</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if($isManagerShipper)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnToggle = document.getElementById('btnToggleBulkMode');
    const bulkBar = document.getElementById('bulkActionBar');
    const checkboxWraps = document.querySelectorAll('.bulk-checkbox-wrap');
    const checkboxes = document.querySelectorAll('.js-transfer-checkbox');
    const selectedCount = document.getElementById('bulkSelectedCount');
    const btnBulkPickup = document.getElementById('btnBulkPickup');
    const btnBulkDeliver = document.getElementById('btnBulkDeliver');
    const bulkPickupIds = document.getElementById('bulkPickupIds');
    const bulkDeliverIds = document.getElementById('bulkDeliverIds');
    const bulkPickupForm = document.getElementById('bulkPickupForm');
    const bulkDeliverForm = document.getElementById('bulkDeliverForm');

    let bulkMode = false;

    function updateBulkState() {
        const checked = document.querySelectorAll('.js-transfer-checkbox:checked');
        selectedCount.textContent = checked.length;

        const pendingChecked = [...checked].filter(c => c.dataset.status === 'pending_shipper_pickup');
        const transitChecked = [...checked].filter(c => c.dataset.status === 'in_transit');

        // Update submit buttons
        btnBulkPickup.disabled = pendingChecked.length === 0;
        btnBulkDeliver.disabled = transitChecked.length === 0;

        // Sync hidden inputs for pickup form
        bulkPickupIds.innerHTML = '';
        pendingChecked.forEach(c => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'transfer_ids[]';
            inp.value = c.value;
            bulkPickupIds.appendChild(inp);
        });

        // Sync hidden inputs for deliver form
        bulkDeliverIds.innerHTML = '';
        transitChecked.forEach(c => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'transfer_ids[]';
            inp.value = c.value;
            bulkDeliverIds.appendChild(inp);
        });
    }

    if (btnToggle) {
        btnToggle.addEventListener('click', function () {
            bulkMode = !bulkMode;
            checkboxWraps.forEach(w => w.classList.toggle('d-none', !bulkMode));
            bulkBar.classList.toggle('d-none', !bulkMode);
            btnToggle.classList.toggle('btn-outline-primary', !bulkMode);
            btnToggle.classList.toggle('btn-primary', bulkMode);
            if (!bulkMode) {
                checkboxes.forEach(c => c.checked = false);
                updateBulkState();
            }
        });
    }

    checkboxes.forEach(c => c.addEventListener('change', updateBulkState));

    document.getElementById('btnSelectAllPending')?.addEventListener('click', function () {
        document.querySelectorAll('.js-transfer-checkbox[data-status="pending_shipper_pickup"]').forEach(c => c.checked = true);
        updateBulkState();
    });

    document.getElementById('btnSelectAllTransit')?.addEventListener('click', function () {
        document.querySelectorAll('.js-transfer-checkbox[data-status="in_transit"]').forEach(c => c.checked = true);
        updateBulkState();
    });

    document.getElementById('btnDeselectAll')?.addEventListener('click', function () {
        checkboxes.forEach(c => c.checked = false);
        updateBulkState();
    });

    bulkPickupForm?.addEventListener('submit', function (e) {
        const ids = bulkPickupIds.querySelectorAll('input');
        if (ids.length === 0) { e.preventDefault(); alert('Vui lòng chọn ít nhất một phiếu ở trạng thái Chờ nhận.'); }
    });

    bulkDeliverForm?.addEventListener('submit', function (e) {
        const ids = bulkDeliverIds.querySelectorAll('input');
        if (ids.length === 0) { e.preventDefault(); alert('Vui lòng chọn ít nhất một phiếu ở trạng thái Đang vận chuyển.'); }
    });
});
</script>
@endif
@endsection
