@extends('layouts.app')
@section('content')
<div class="container">
    <h4>{{ __('orders.titles.create') }}</h4>
    <form action="{{ route('orders.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="customer_id" class="form-label">{{ __('orders.labels.customer') }}</label>
            <select name="customer_id" id="customer_id" class="form-control" required>
                <option value="">-- {{ __('orders.labels.customer') }} --</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="user_id" class="form-label">{{ __('orders.labels.employee') }}</label>
            <select name="user_id" id="user_id" class="form-control" required>
                <option value="">-- {{ __('orders.labels.employee') }} --</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">{{ __('orders.labels.status') }}</label>
            <select name="status" id="status" class="form-control">
                @foreach($statusOptions as $key => $label)
                    <option value="{{ $key }}" {{ old('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('orders.buttons.create') }}</button>
        <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('orders.buttons.cancel') }}</a>
    </form>
</div>
@endsection
