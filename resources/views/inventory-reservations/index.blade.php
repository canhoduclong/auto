@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Inventory Reservations</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Order Item ID</th>
                <th>Inventory ID</th>
                <th>Quantity</th>
                <th>Reserved At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservations as $reservation)
            <tr>
                <td>{{ $reservation->id }}</td>
                <td>{{ $reservation->order_item_id }}</td>
                <td>{{ $reservation->inventory_id }}</td>
                <td>{{ $reservation->quantity }}</td>
                <td>{{ $reservation->reserved_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $reservations->links() }}
</div>
@endsection