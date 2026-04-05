@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-3">{{ __('order_returns.titles.create') }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $submitRoute ?? route('order-returns.store') }}" method="POST" enctype="multipart/form-data" id="return-form">
        @csrf

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('order_returns.labels.order') }}</label>
                <select name="order_id" id="order_id" class="form-select" required>
                    <option value="">{{ __('order_returns.placeholders.select_order') }}</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" @selected((old('order_id') ?: ($selectedOrderId ?? null)) == $order->id)>
                            {{ $order->code }} - {{ optional($order->customer)->name ?? __('order_returns.default.na') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('order_returns.labels.warehouse') }}</label>
                <select name="warehouse_id" class="form-select" required>
                    <option value="">{{ __('order_returns.placeholders.select_warehouse') }}</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('order_returns.labels.return_reason') }}</label>
                <textarea name="reason" class="form-control" rows="2">{{ old('reason') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('order_returns.labels.evidence_image') }}</label>
                <input type="file" name="evidence_image" class="form-control" accept="image/*" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('order_returns.labels.note') }}</label>
                <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
            </div>
        </div>

        <h5>{{ __('order_returns.labels.return_items') }}</h5>
        <div id="items-container" class="border rounded p-3 bg-light text-muted">
            {{ __('order_returns.placeholders.select_order_to_load_items') }}
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('order_returns.buttons.save') }}</button>
            <a href="{{ route('order-returns.index') }}" class="btn btn-secondary">{{ __('order_returns.buttons.back') }}</a>
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
            itemsContainer.innerHTML = @json(__('order_returns.placeholders.no_valid_items'));
            return;
        }

        itemsContainer.className = 'border rounded p-3';
        const rows = order.items.map((item, idx) => {
            return `
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('order_returns.labels.order') }}</label>
                        <input type="hidden" name="items[${idx}][product_variant_id]" value="${item.variant_id}">
                        <input type="text" class="form-control" value="${item.name}" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('order_returns.labels.quantity') }}</label>
                        <input type="number" min="1" max="${item.max_qty}" name="items[${idx}][quantity]" class="form-control" value="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('order_returns.labels.condition') }}</label>
                        <input type="text" name="items[${idx}][condition]" class="form-control" placeholder="{{ __('order_returns.placeholders.condition_example') }}">
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
