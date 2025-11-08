@extends('layouts.site')

@section('content')
<div class="container">
    <h1>My Orders</h1>
    <div class="card">
        <div class="card-header">Order List</div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order Code</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->code }}</td>
                            <td>{{ $order->customer->name ?? '' }}</td>
                            <td>{{ number_format($order->total, 2) }}</td>
                            <td>{{ $order->status }}</td>
                            <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                            <td>
                                <a href="{{ route('site.orders.show', $order) }}" class="btn btn-info btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">You have no orders.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
