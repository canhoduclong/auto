@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Order Returns</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($returns as $return)
            <tr>
                <td>{{ $return->id }}</td>
                <td>{{ $return->order->code ?? 'N/A' }}</td>
                <td>{{ $return->customer->name ?? 'N/A' }}</td>
                <td>{{ $return->status }}</td>
                <td>{{ $return->reason }}</td>
                <td>{{ $return->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $returns->links() }}
</div>
@endsection