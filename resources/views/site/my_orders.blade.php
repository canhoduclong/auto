@extends('layouts.site')

@section('content')
<div class="container">
    <h1>My Orders</h1>

    <div class="card mb-3">
        <div class="card-header">Filter Orders</div>
        <div class="card-body">
            <form action="{{ route('pages.my_orders') }}" method="GET" class="row">
                <div class="col-md-4">
                    <label for="customer_id" class="form-label">Customer</label>
                    <select name="customer_id" id="customer_id" class="form-select">
                        <option value="">All customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) request('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

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
                                @if(in_array($order->status, ['picked_up', 'shipping', 'completed'], true))
                                    <a href="{{ route('site.order-returns.create', $order) }}" class="btn btn-warning btn-sm">Tra hang</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">You have no orders.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $orders->appends(request()->input())->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
