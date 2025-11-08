@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Thêm địa chỉ cho {{ $customer->name }}</h2>

    <form action="{{ route('customers.addresses.store', $customer->id) }}" method="POST">
        @csrf
        @include('customers.addresses.form')
        <button type="submit" class="btn btn-success">Lưu</button>
        <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
