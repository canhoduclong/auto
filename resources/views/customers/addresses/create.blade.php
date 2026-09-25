@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('customers.address.create_title', ['name' => $customer->name]) }}</h2>

    <form action="{{ route('customers.addresses.store', $customer->id) }}" method="POST">
        @csrf
        @include('customers.addresses.form')
        <button type="submit" class="btn btn-success">{{ __('common.actions.save') }}</button>
        <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-secondary">{{ __('common.actions.cancel') }}</a>
    </form>
</div>
@endsection
