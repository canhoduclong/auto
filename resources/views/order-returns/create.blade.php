@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-3">Tao don tra hang</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $submitRoute ?? route('order-returns.store') }}" method="POST" id="return-form">
        @csrf

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Don hang</label>
                <select name="order_id" id="order_id" class="form-select" required>
                    <option value="">Chon don hang</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" @selected((old('order_id') ?: ($selectedOrderId ?? null)) == $order->id)>
                            {{ $order->code }} - {{ optional($order->customer)->name ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Kho nhap hang tra ve</label>
                <select name="warehouse_id" class="form-select" required>
                    <option value="">Chon kho</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Ly do tra hang</label>
                <textarea name="reason" class="form-control" rows="2">{{ old('reason') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Ghi chu</label>
                <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
            </div>
        </div>

        <h5>Danh sach san pham tra</h5>
        <div id="items-container" class="border rounded p-3 bg-light text-muted">
            Chon don hang de hien thi san pham.
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Luu don tra hang</button>
            <a href="{{ route('order-returns.index') }}" class="btn btn-secondary">Quay lai</a>
        </div>
    </form>
</div>

<script>
    const orders = @json($orderPayload);

    const selectOrder = document.getElementById('order_id');
    const itemsContainer = document.getElementById('items-container');

    function renderItems(orderId) {
        const order = orders.find(o => String(o.id) === String(orderId));
        if (!order || !order.items.length) {
            itemsContainer.className = 'border rounded p-3 bg-light text-muted';
            itemsContainer.innerHTML = 'Don hang khong co item hop le de tra.';
            return;
        }

        itemsContainer.className = 'border rounded p-3';
        const rows = order.items.map((item, idx) => {
            return `
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-6">
                        <label class="form-label">San pham</label>
                        <input type="hidden" name="items[${idx}][product_variant_id]" value="${item.variant_id}">
                        <input type="text" class="form-control" value="${item.name}" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">So luong</label>
                        <input type="number" min="1" max="${item.max_qty}" name="items[${idx}][quantity]" class="form-control" value="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tinh trang</label>
                        <input type="text" name="items[${idx}][condition]" class="form-control" placeholder="Vi du: moi, tray xuoc nhe...">
                    </div>
                </div>
            `;
        }).join('');

        itemsContainer.innerHTML = rows;
    }

    selectOrder.addEventListener('change', function () {
        renderItems(this.value);
    });

    const initialOrderId = selectOrder.value || @json($selectedOrderId ?? null);
    if (initialOrderId) {
        selectOrder.value = String(initialOrderId);
        renderItems(initialOrderId);
    }
</script>
@endsection
