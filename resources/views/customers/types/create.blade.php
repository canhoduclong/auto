@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('customers.type.create_title') }}</h2>

    <form action="{{ route('customertype.store') }}" method="POST">
        @csrf

        @include('customers.types.form', ['type' => new \App\Models\CustomerType])

        <button type="submit" class="btn btn-success">{{ __('common.actions.save') }}</button>
        <a href="{{ route('customertype.index') }}" class="btn btn-secondary">{{ __('common.actions.cancel') }}</a>
    </form>
</div>
@endsection
