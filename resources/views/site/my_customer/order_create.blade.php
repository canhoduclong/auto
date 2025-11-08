@extends('layouts.site')

@section('content')
<div class="container">
    <h1>Create Order for {{ $customer->name }}</h1>

    <div class="card mb-4">
        <div class="card-header">
            Customer Details
        </div>
        <div class="card-body">
            <p><strong>Email:</strong> {{ $customer->email }}</p>
            <p><strong>Phone:</strong> {{ $customer->phone }}</p>
        </div>
    </div>

    <form action="{{ route('my_customer.order.store', $customer) }}" method="POST">
        @csrf
        <div class="card">
            <div class="card-header">
                Product List
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Variant</th>
                            <th>Price</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @foreach($product->variants as $variant)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $variant->name }}</td>
                                    <td>{{ number_format($variant->latestPriceRule->price ?? $variant->price) }}</td>
                                    <td>
                                        <input type="hidden" name="variants[{{ $variant->id }}][id]" value="{{ $variant->id }}">
                                        <input type="number" name="variants[{{ $variant->id }}][quantity]" class="form-control" min="0">
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Create Order</button>
                <a href="{{ route('pages.my_customer') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection