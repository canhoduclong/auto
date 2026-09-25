@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('customers.create.title') }}</h2>

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customers.store') }}" method="POST">
        @csrf
        @include('customers._form', ['customer' => null, 'types' => $types])
        <div class="mt-3">
            <button class="btn btn-success">{{ __('common.actions.save') }}</button>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">{{ __('common.actions.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
