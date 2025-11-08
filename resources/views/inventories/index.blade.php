@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Inventories</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Variant ID</th>
                <th>Warehouse ID</th>
                <th>Quantity</th>
                <th>Low Stock Threshold</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inventories as $inventory)
            <tr>
                <td>{{ $inventory->id }}</td>
                <td>{{ $inventory->product_variant_id }}</td>
                <td>{{ $inventory->warehouse_id }}</td>
                <td>{{ $inventory->quantity }}</td>
                <td>{{ $inventory->low_stock_threshold }}</td>
                <td>{{ $inventory->created_at }}</td>
                <td>{{ $inventory->updated_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $inventories->links() }}
</div>
@endsection