@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('inventory.titles.movements') }}</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('inventory.labels.inventory_id') }}</th>
                <th>{{ __('inventory.labels.quantity') }}</th>
                <th>{{ __('inventory.labels.type') }}</th>
                <th>{{ __('inventory.labels.reference') }}</th>
                <th>{{ __('inventory.labels.user') }}</th>
                <th>{{ __('inventory.labels.created_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $movement)
            <tr>
                <td>{{ $movement->id }}</td>
                <td>{{ $movement->inventory_id }}</td>
                <td>{{ $movement->quantity }}</td>
                <td>{{ __('inventory.types.' . $movement->type) }}</td>
                <td>{{ $movement->reference_type }} - {{ $movement->reference_id }}</td>
                <td>{{ $movement->user->name ?? __('inventory.default.na') }}</td>
                <td>{{ $movement->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $movements->links() }}
</div>
@endsection