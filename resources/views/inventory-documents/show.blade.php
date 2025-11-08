@extends('layouts.app')

@section('title', 'Inventory Document #' . $inventoryDocument->id)

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Inventory Document #{{ $inventoryDocument->id }}</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Document Details</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Date:</strong> {{ $inventoryDocument->document_date }}</p>
                    <p><strong>Type:</strong> {{ $inventoryDocument->type }}</p>
                    <p><strong>Warehouse:</strong> {{ $inventoryDocument->warehouse->name }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>User:</strong> {{ $inventoryDocument->user->name ?? 'N/A' }}</p>
                    <p><strong>Shipping Fee:</strong> {{ number_format($inventoryDocument->shipping_fee, 2) }}</p>
                    <p><strong>Notes:</strong> {{ $inventoryDocument->notes }}</p>
                </div>
            </div>

            <hr>

            <h4>Items</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product Variant</th>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventoryDocument->items as $item)
                        <tr>
                            <td>{{ $item->productVariant->product->name }} ({{ $item->productVariant->sku }})</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->unit_cost, 2) }}</td>
                            <td>{{ number_format($item->quantity * $item->unit_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <a href="{{ route('inventory-documents.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</div>
@endsection
