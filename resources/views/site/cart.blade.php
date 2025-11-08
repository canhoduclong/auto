@extends('layouts.site')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <h1>Shopping Cart</h1>
            @if(session('cart') && count(session('cart')) > 0)
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $total = 0 @endphp
                            @foreach(session('cart') as $id => $details)
                                @php $total += $details['price'] * $details['quantity'] @endphp
                                <tr data-id="{{ $id }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($details['image'])
                                                <img src="{{ asset('storage/' . $details['image']) }}" alt="{{ $details['name'] }}" width="50" class="me-3">
                                            @endif
                                            <div>
                                                <h6 class="mb-0">{{ $details['name'] }}</h6>
                                                <small>SKU: {{ $details['sku'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ number_format($details['price']) }}đ</td>
                                    <td>
                                        <input type="number" value="{{ $details['quantity'] }}" class="form-control quantity update-cart" min="1" style="width: 80px">
                                    </td>
                                    <td>{{ number_format($details['price'] * $details['quantity']) }}đ</td>
                                    <td>
                                        <button class="btn btn-danger btn-sm remove-from-cart">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td>{{ number_format($total) }}đ</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end">
                    <a href="{{ route('cart.checkout') }}" class="btn btn-success">Tạo đơn hàng</a>
                </div>
            @else
                <div class="alert alert-info">
                    Your cart is empty!
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update quantity
    document.querySelectorAll('.update-cart').forEach(function(element) {
        element.addEventListener('change', function(e) {
            let id = e.target.closest('tr').dataset.id;
            let quantity = e.target.value;
            
            fetch(`/cart/update/${id}`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    id: id,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                }
            });
        });
    });

    // Remove item
    document.querySelectorAll('.remove-from-cart').forEach(function(element) {
        element.addEventListener('click', function(e) {
            let id = e.target.closest('tr').dataset.id;
            
            fetch(`/cart/remove/${id}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                }
            });
        });
    });
});
</script>
@endpush