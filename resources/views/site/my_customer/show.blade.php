@extends('layouts.site')

@section('content')
<div class="container">
    <h1>Customer Details</h1>

    <div class="card">
        <div class="card-header">
            {{ $customer->name }}
        </div>
        <div class="card-body">
            <p><strong>Email:</strong> {{ $customer->email }}</p>
            <p><strong>Phone:</strong> {{ $customer->phone }}</p>
            <a href="{{ route('pages.my_customer') }}" class="btn btn-primary">Back to List</a>
        </div>
    </div>
</div>
@endsection