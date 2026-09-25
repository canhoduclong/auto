@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('customers.address.edit_title', ['name' => $customer->name]) }}</h2>

    <div class="mb-3">
        <strong>{{ __('customers.address.customer_code') }}:</strong> {{ $customer->id }} <br>
        <strong>{{ __('customers.address.customer_name') }}:</strong> {{ $customer->name }} <br>
        <strong>Email:</strong> {{ $customer->email ?? '—' }} <br>
        <strong>{{ __('customers.address.customer_phone') }}:</strong> {{ $customer->phone ?? '—' }}
    </div>


    <form action="{{ route('customers.addresses.update', [$customer->id, $address->id]) }}" method="POST">
        @csrf @method('PUT')
        @include('customers.addresses.form', ['address' => $address])
        <button type="submit" class="btn btn-primary">{{ __('common.actions.update') }}</button>
        <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-secondary">{{ __('common.actions.cancel') }}</a>
    </form>
</div>
@endsection
