@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Inventory Movements</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Inventory ID</th>
                <th>Quantity</th>
                <th>Type</th>
                <th>Reference</th>
                <th>User</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $movement)
            <tr>
                <td>{{ $movement->id }}</td>
                <td>{{ $movement->inventory_id }}</td>
                <td>{{ $movement->quantity }}</td>
                <td>{{ $movement->type }}</td>
                <td>{{ $movement->reference_type }} - {{ $movement->reference_id }}</td>
                <td>{{ $movement->user->name ?? 'N/A' }}</td>
                <td>{{ $movement->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $movements->links() }}
</div>
@endsection