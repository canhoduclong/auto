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
</style>
@endpush
@section('content')
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
            <h5>Đơn đã được tạo phiếu điều chuyển</h5>
            @if($recentTransfers->isEmpty())
                <div class="alert alert-info text-center my-4">Chưa có phiếu điều chuyển nào.</div>
            @else
                @foreach($recentTransfers as $transfer)
                    <div class="mb-3 border rounded p-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <span class="fw-semibold">Phiếu #{{ $transfer->id }}</span>
                                <span class="badge bg-info ms-2">Shipper: {{ $transfer->shipper?->name ?? '—' }}</span>
                                <span class="badge bg-secondary ms-2">Kho: {{ $transfer->warehouse?->name ?? '—' }}</span>
                            </div>
                            <div class="small text-muted">{{ $transfer->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="small text-muted mb-1">Người tạo: {{ $transfer->created_by ? (\App\Models\User::find($transfer->created_by)?->name ?? $transfer->created_by) : '—' }}</div>
                        <ul class="mb-0 ps-3">
                            @foreach($transfer->orders as $order)
                                <li>#{{ $order->code ?? $order->id }} - {{ $order->customer?->name ?? '—' }} ({{ optional($order->created_at)->format('d/m/Y H:i') }})</li>
                            @endforeach
                        </ul>
                        <form method="POST" action="{{ route('warehouse.order-transfers.destroy', $transfer->id) }}" onsubmit="return confirm('Bạn có chắc muốn xóa phiếu này?');" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Xóa phiếu</button>
                        </form>
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
