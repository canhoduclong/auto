@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('inventory.titles.reservations') }}</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('inventory.labels.order_item_id') }}</th>
                <th>{{ __('inventory.labels.inventory_id') }}</th>
                <th>{{ __('inventory.labels.quantity') }}</th>
                <th>{{ __('inventory.labels.reserved_at') }}</th>
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