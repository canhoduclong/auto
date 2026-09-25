@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('customers.edit.title', ['name' => $customer->name]) }}</h2>

    <form action="{{ route('customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')
        @include('customers._form', ['customer' => $customer, 'types' => $types])
        <div class="mt-3">
            <button class="btn btn-primary">{{ __('common.actions.update') }}</button>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">{{ __('common.actions.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
