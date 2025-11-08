@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Sửa địa chỉ của {{ $customer->name }}</h2> 

    <div class="mb-3">
        <strong>Mã KH:</strong> {{ $customer->id }} <br>
        <strong>Tên:</strong> {{ $customer->name }} <br>
        <strong>Email:</strong> {{ $customer->email ?? '—' }} <br>
        <strong>Số điện thoại:</strong> {{ $customer->phone ?? '—' }}
    </div>


    <form action="{{ route('customers.addresses.update', [$customer->id, $address->id]) }}" method="POST">
        @csrf @method('PUT')
        @include('customers.addresses.form', ['address' => $address])
        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
