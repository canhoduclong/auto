@extends('layouts.warehouse')
@php
    if (!isset($orders)) {
        echo '<div class="alert alert-danger m-4">Lỗi: Không có biến $orders được truyền vào view. Hãy kiểm tra lại controller hoặc route.</div>';
        return;
    }
@endphp
@section('title', 'Điều chuyển đơn hàng')
@push('styles')
<style>
.order-transfer-col {
    min-height: 500px;
    background: #f8fafc;
    border-radius: 10px;
    padding: 1.5rem 1rem;
}
.order-transfer-list {
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
}
.order-transfer-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 10px;
    padding: 1rem;
    cursor: pointer;
    transition: box-shadow .15s;
}
.order-transfer-card.selected {
    border-color: #0f766e;
    box-shadow: 0 2px 8px rgba(20,184,166,.08);
}
.transfer-day-group {
    border: 1px solid #dbe5e3;
    border-radius: 10px;
    background: #fff;
    overflow: hidden;
    margin-bottom: 16px;
}
.transfer-day-head {
    padding: 9px 12px;
    background: #dff3ef;
    color: #115e59;
    font-weight: 800;
}
.transfer-slip {
    padding: 12px;
    border-top: 1px solid #e5e7eb;
}
.transfer-order-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 7px 0;
    border-top: 1px dashed #e2e8f0;
}
</style>
@endpush
@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
            <div><h5 class="fw-bold mb-1">Điều chuyển đơn đã đóng gói</h5><div class="small text-muted">Gom đơn theo tài xế và kho nhận như quy trình hiện tại.</div></div>
            <a href="{{ route('warehouse.dispatch-slips.index') }}" class="btn btn-warning fw-semibold"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Lập phiếu xuất kho tổng</a>
        </div>
        <form method="GET" action="{{ route('warehouse.order-transfers') }}" class="row g-2 align-items-end">
            <div class="col-lg-3 col-md-4">
                <label class="form-label small fw-semibold mb-1">Từ ngày</label>
                <input type="date" name="from_date" value="{{ $from }}" class="form-control">
            </div>
            <div class="col-lg-3 col-md-4">
                <label class="form-label small fw-semibold mb-1">Đến ngày</label>
                <input type="date" name="to_date" value="{{ $to }}" class="form-control">
            </div>
            <div class="col-lg-4 col-md-4">
                <label class="form-label small fw-semibold mb-1">Mã đơn / khách hàng</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="Nhập từ khóa...">
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit">Lọc</button>
                <a href="{{ route('warehouse.order-transfers') }}" class="btn btn-outline-secondary">Đặt lại</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="order-transfer-col">
            <form method="POST" action="{{ route('warehouse.order-transfers.store') }}" id="orderTransferForm" class="mb-3">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label mb-1">Chọn shipper</label>
                        <select name="shipper_id" class="form-select" required>
                            <option value="">-- Chọn shipper --</option>
                            @foreach($shippers as $shipper)
                                <option value="{{ $shipper->id }}">{{ $shipper->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label mb-1">Chọn kho nhận</label>
                        <select name="warehouse_id" class="form-select" required>
                            <option value="">-- Chọn kho --</option>
                            @php $currentWarehouseId = auth()->user()->warehouse_id; @endphp
                            @foreach($warehouses as $warehouse)
                                @if($warehouse->id != $currentWarehouseId)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-success">Tạo phiếu</button>
                    </div>
                </div>
                <input type="hidden" name="order_ids" id="orderIdsInput">
            </form>
            <div id="orderListRight" style="display:none"></div>
            <h5 class="mt-3">Đơn hàng chưa điều chuyển</h5>
            <div class="d-flex mb-2 gap-2">
                <input type="text" class="form-control" id="orderSearchInput" placeholder="Tìm kiếm đơn hàng...">
                <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllOrdersBtn">Chọn tất cả</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAllOrdersBtn">Bỏ chọn tất cả</button>
            </div>
            <div class="order-transfer-list" id="orderListLeft">
                @if(!is_iterable($orders))
                    <div class="alert alert-danger text-center my-4">Lỗi dữ liệu: Không thể lấy danh sách đơn hàng.</div>
                @else
                    @forelse($orders as $order)
                        @include('warehouse.order-transfers._order_transfer_card', ['order' => $order])
                    @empty
                        <div class="alert alert-warning text-center my-4">Chưa có đơn.</div>
                    @endforelse
                @endif
            </div>
            @if(is_iterable($orders))
                {{ $orders->links() }}
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="order-transfer-col">
            <h5 class="mb-3">Phiếu điều chuyển theo ngày</h5>
            @if($transferGroups->isEmpty())
                <div class="alert alert-info text-center my-4">
                    {{ !empty($search) ? 'Không tìm thấy phiếu điều chuyển phù hợp.' : 'Chưa có phiếu điều chuyển nào.' }}
                </div>
            @else
                @foreach($transferGroups as $date => $dayTransfers)
                    <div class="transfer-day-group">
                        <div class="transfer-day-head d-flex justify-content-between gap-2">
                            <span>{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</span>
                            <span>{{ $dayTransfers->count() }} phiếu · {{ $dayTransfers->sum(fn ($transfer) => $transfer->orders->count()) }} đơn</span>
                        </div>
                        @foreach($dayTransfers as $transfer)
                            <div class="transfer-slip">
                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                                    <div>
                                        <div class="fw-bold">Phiếu #{{ $transfer->id }}</div>
                                        <div class="small text-muted">
                                            Shipper: <strong>{{ $transfer->shipper?->name ?? '—' }}</strong>
                                            · Kho nhận: <strong>{{ $transfer->warehouse?->name ?? '—' }}</strong>
                                        </div>
                                    </div>
                                    <span class="badge {{ $transfer->is_completed ? 'bg-success' : ($transfer->can_delete ? 'bg-warning text-dark' : 'bg-info text-dark') }}">
                                        {{ $transfer->status_label }}
                                    </span>
                                </div>
                                <div class="small text-muted mb-2">
                                    Tạo lúc {{ $transfer->created_at->format('H:i') }} · {{ $transfer->orders->count() }} đơn
                                    @if($transfer->dispatchEntry?->slip)
                                        · <a class="text-decoration-none" href="{{ route('warehouse.dispatch-slips.show', $transfer->dispatchEntry->slip) }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i>{{ $transfer->dispatchEntry->slip->code }}</a>
                                    @endif
                                </div>

                                @foreach($transfer->orders as $order)
                                    @php
                                        $latestTransfer = $order->warehouseTransfers->first();
                                        $orderStatus = $latestTransfer?->status;
                                        $orderCompleted = $orderStatus === \App\Models\WarehouseTransfer::STATUS_RECEIVED_COMPLETED;
                                        $orderStatusLabel = match ($orderStatus) {
                                            \App\Models\WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP => 'Chờ shipper nhận',
                                            \App\Models\WarehouseTransfer::STATUS_IN_TRANSIT => 'Đang vận chuyển',
                                            \App\Models\WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE => 'Chờ kho tiếp nhận',
                                            \App\Models\WarehouseTransfer::STATUS_RECEIVED_COMPLETED => 'Đã tiếp nhận',
                                            \App\Models\WarehouseTransfer::STATUS_CANCELLED => 'Đã hủy / hoàn lại',
                                            default => 'Chưa tạo vận chuyển',
                                        };
                                    @endphp
                                    <div class="transfer-order-row">
                                        <div>
                                            <div class="fw-semibold">#{{ $order->code ?? $order->id }} - {{ $order->customer?->name ?? '—' }}</div>
                                            <div class="small text-muted">
                                                {{ optional($order->created_at)->format('d/m/Y H:i') }}
                                                · KL bàn giao: <strong>{{ $latestTransfer?->packed_total_weight !== null ? number_format((float) $latestTransfer->packed_total_weight, 3, ',', '.') . ' kg' : '—' }}</strong>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge {{ $orderCompleted ? 'bg-success' : 'bg-light text-dark border' }}">{{ $orderStatusLabel }}</span>
                                            @if(in_array($orderStatus, [
                                                \App\Models\WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                                                \App\Models\WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
                                            ], true))
                                                @php
                                                    $willRestoreStock = $orderStatus === \App\Models\WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE;
                                                @endphp
                                                <form method="POST"
                                                    action="{{ route('warehouse.order-transfers.orders.detach', [$transfer, $order]) }}"
                                                    class="mt-2"
                                                    onsubmit="return confirm('{{ $willRestoreStock
                                                        ? 'Gỡ phiếu điều chuyển hiện tại để chọn shipper khác? Đơn hàng được giữ nguyên và tồn kho nguồn sẽ được hoàn lại.'
                                                        : 'Gỡ phiếu điều chuyển hiện tại để chọn shipper khác? Đơn hàng được giữ nguyên.'
                                                    }}');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-warning btn-sm">
                                                        Gỡ phiếu điều chuyển
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @if($transfer->can_delete)
                                    <form method="POST" action="{{ route('warehouse.order-transfers.destroy', $transfer->id) }}"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa phiếu chưa được shipper nhận này?');" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Xóa phiếu</button>
                                    </form>
                                @elseif($transfer->is_completed)
                                    <div class="small text-success fw-semibold mt-2">
                                        Kho chuyển đến đã tiếp nhận hoàn tất. Phiếu chỉ còn hiển thị lịch sử.
                                    </div>
                                @else
                                    <div class="small text-muted mt-2">Phiếu đã bắt đầu vận chuyển nên không còn thao tác tại đây.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
<script>
const orderListLeft = document.getElementById('orderListLeft');
const orderListRight = document.getElementById('orderListRight');
const orderIdsInput = document.getElementById('orderIdsInput');
const selectAllOrdersBtn = document.getElementById('selectAllOrdersBtn');
const deselectAllOrdersBtn = document.getElementById('deselectAllOrdersBtn');
let selectedOrders = [];

function renderSelectedOrders() {
    orderListRight.innerHTML = '';
    selectedOrders.forEach(order => {
        const div = document.createElement('div');
        div.className = 'order-transfer-card selected';
        div.innerHTML = `<div class="fw-semibold">${order.code} - ${order.customer}</div><div class="small text-muted">${order.created_at}</div><button type='button' class='btn btn-link text-danger btn-sm float-end remove-order-btn' data-id='${order.id}'><i class='bi bi-x-circle'></i></button>`;
        orderListRight.appendChild(div);
    });
    orderIdsInput.value = selectedOrders.map(o => o.id).join(',');
    // Sync checkbox state
    document.querySelectorAll('.order-select-checkbox').forEach(cb => {
        cb.checked = selectedOrders.some(o => o.id == cb.value);
    });
}


orderListLeft.addEventListener('change', function(e) {
    if (e.target.classList.contains('order-select-checkbox')) {
        const card = e.target.closest('.order-transfer-card');
        const id = card.getAttribute('data-id');
        if (e.target.checked) {
            if (!selectedOrders.find(o => o.id == id)) {
                selectedOrders.push({
                    id: id,
                    code: card.querySelector('.fw-semibold').textContent.split(' ')[0].replace('#',''),
                    customer: card.querySelector('.fw-semibold').textContent.split('-')[1]?.trim() || '',
                    created_at: card.querySelector('.small.text-muted').textContent
                });
            }
        } else {
            selectedOrders = selectedOrders.filter(o => o.id != id);
        }
        renderSelectedOrders();
    }
});

selectAllOrdersBtn.addEventListener('click', function() {
    document.querySelectorAll('.order-select-checkbox').forEach(cb => {
        if (!cb.checked) cb.click();
    });
});

deselectAllOrdersBtn.addEventListener('click', function() {
    document.querySelectorAll('.order-select-checkbox').forEach(cb => {
        if (cb.checked) cb.click();
    });
});

orderListRight.addEventListener('click', function(e) {
    if (e.target.closest('.remove-order-btn')) {
        const id = e.target.closest('.remove-order-btn').getAttribute('data-id');
        selectedOrders = selectedOrders.filter(o => o.id != id);
        // Hiện lại bên trái
        const card = orderListLeft.querySelector(`.order-transfer-card[data-id='${id}']`);
        if (card) card.style.display = '';
        renderSelectedOrders();
    }
});

// Đảm bảo order_ids luôn cập nhật khi submit form
document.getElementById('orderTransferForm').addEventListener('submit', function(e) {
    // Lấy tất cả checkbox đã chọn trong orderListLeft
    const checked = Array.from(document.querySelectorAll('#orderListLeft .order-select-checkbox:checked'));
    const ids = checked.map(cb => cb.value);
    orderIdsInput.value = ids.join(',');
});
</script>
@endsection
