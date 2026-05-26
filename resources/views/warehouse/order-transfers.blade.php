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
            <h5>Đơn hàng chưa điều chuyển</h5>
            <input type="text" class="form-control mb-2" id="orderSearchInput" placeholder="Tìm kiếm đơn hàng...">
            <div class="order-transfer-list" id="orderListLeft">
                @if(!is_iterable($orders))
                    <div class="alert alert-danger text-center my-4">Lỗi dữ liệu: Không thể lấy danh sách đơn hàng.</div>
                @else
                    @forelse($orders as $order)
                        <div class="order-transfer-card" data-id="{{ $order->id }}">
                            <div class="fw-semibold">#{{ $order->code ?? $order->id }} - {{ $order->customer?->name ?? '—' }}</div>
                            <div class="small text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                        </div>
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
            <h5>Đơn đã chọn để điều chuyển</h5>
            <form method="POST" action="{{ route('warehouse.order-transfers.store') }}" id="orderTransferForm">
                @csrf
                <div class="mb-2">
                    <select name="shipper_id" class="form-select mb-2" required>
                        <option value="">-- Chọn shipper --</option>
                        @foreach($shippers as $shipper)
                            <option value="{{ $shipper->id }}">{{ $shipper->name }}</option>
                        @endforeach
                    </select>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">-- Chọn kho --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="order-transfer-list" id="orderListRight"></div>
                <input type="hidden" name="order_ids" id="orderIdsInput">
                <button type="submit" class="btn btn-success mt-3">Tạo phiếu điều chuyển</button>
            </form>
        </div>
    </div>
</div>
<script>
const orderListLeft = document.getElementById('orderListLeft');
const orderListRight = document.getElementById('orderListRight');
const orderIdsInput = document.getElementById('orderIdsInput');
let selectedOrders = [];

function renderSelectedOrders() {
    orderListRight.innerHTML = '';
    selectedOrders.forEach(order => {
        const div = document.createElement('div');
        div.className = 'order-transfer-card selected';
        div.innerHTML = `<div class=\"fw-semibold\">#${order.code} - ${order.customer}</div><div class=\"small text-muted\">${order.created_at}</div><button type='button' class='btn btn-link text-danger btn-sm float-end remove-order-btn' data-id='${order.id}'><i class='bi bi-x-circle'></i></button>`;
        orderListRight.appendChild(div);
    });
    orderIdsInput.value = selectedOrders.map(o => o.id).join(',');
}

orderListLeft.addEventListener('click', function(e) {
    const card = e.target.closest('.order-transfer-card');
    if (!card) return;
    const id = card.getAttribute('data-id');
    if (selectedOrders.find(o => o.id == id)) return;
    selectedOrders.push({
        id: id,
        code: card.querySelector('.fw-semibold').textContent.split(' ')[0].replace('#',''),
        customer: card.querySelector('.fw-semibold').textContent.split('-')[1]?.trim() || '',
        created_at: card.querySelector('.small.text-muted').textContent
    });
    card.style.display = 'none';
    renderSelectedOrders();
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
</script>
@endsection
