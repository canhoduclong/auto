@extends('layouts.app')
@section('content')
<div class="container">
    <h4>{{ __('orders.titles.edit', ['code' => $order->code]) }}</h4>
    <form action="{{ route('orders.update', $order->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="customer_id" class="form-label">{{ __('orders.labels.customer') }}</label>
            <select name="customer_id" id="customer_id" class="form-control" required>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ $order->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="user_id" class="form-label">{{ __('orders.labels.employee') }}</label>
            <select name="user_id" id="user_id" class="form-control" required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $order->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">{{ __('orders.labels.status') }}</label>
            <select name="status" id="status" class="form-control">
                @foreach($statusOptions as $key => $label)
                    <option value="{{ $key }}" {{ $order->status == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    <div class="mb-3">
        <label>{{ __('orders.labels.variant') }}:</label>
        <select id="edit-variant-select" class="form-control" style="width:300px;display:inline-block">
            <option value="">-- {{ __('orders.labels.variant') }} --</option>
            @foreach(\App\Models\ProductVariant::with('product')->get() as $variant)
                <option value="{{ $variant->id }}">{{ $variant->product->name ?? '' }} - {{ $variant->variant_name ?? ($variant->sku ?? $variant->id) }}</option>
            @endforeach
        </select>
        <button type="button" id="edit-add-variant" class="btn btn-success btn-sm">{{ __('inventory.buttons.add_item') }}</button>
    </div>
    <div id="edit-variant-list"></div>
    <div class="mt-3 text-end">
        <strong>{{ __('orders.labels.total') }}: <span id="edit-order-total">0</span> đ</strong>
    </div>
    <button type="submit" class="btn btn-primary">{{ __('orders.buttons.update') }}</button>
    <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('orders.buttons.cancel') }}</a>
</form>
</div>
@push('scripts')
<script>
let orderId = {{ $order->id }};
function loadEditVariants() {
    $.get(`/orders/${orderId}/list-variant`, function(html) {
        $('#edit-variant-list').html(html);
        $('#edit-order-total').text($('#edit-list-total').text() || 0);
    });
}
$(function() {
    loadEditVariants();
    $('#edit-variant-list').on('click', '.remove-variant-btn', function() {
        let vid = $(this).data('variant-id');
        $.post(`/orders/${orderId}/remove-variant`, {variant_id: vid, _token: '{{ csrf_token() }}'}, function() {
            loadEditVariants();
        });
    });
    $('#edit-add-variant').on('click', function() {
        let vid = $('#edit-variant-select').val();
        if (vid) {
            $.post(`/orders/${orderId}/add-variant`, {variant_id: vid, _token: '{{ csrf_token() }}'}, function() {
                loadEditVariants();
            });
        }
    });
});
</script>
@endpush
@endsection
