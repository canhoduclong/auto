@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('customers.type.edit_title') }}</h2>

    <form action="{{ route('customertype.update', $customerType) }}" method="POST">
        @csrf
        @method('PUT')

        @include('customers.types.form', ['type' => $customerType])

        <button type="submit" class="btn btn-success">{{ __('common.actions.update') }}</button>
        <a href="{{ route('customertype.index') }}" class="btn btn-secondary">{{ __('common.actions.cancel') }}</a>
    </form>
</div>
@endsection
