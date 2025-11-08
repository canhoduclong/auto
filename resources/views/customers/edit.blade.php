@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Sửa thông tin: {{ $customer->name }}</h2>

    <form action="{{ route('customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')
        @include('customers._form', ['customer' => $customer, 'types' => $types])
        <div class="mt-3">
            <button class="btn btn-primary">Cập nhật</button>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
