@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Thêm khách hàng mới</h2>

    <form action="{{ route('customers.store') }}" method="POST">
        @csrf
        @include('customers._form', ['customer' => null, 'types' => $types])
        <div class="mt-3">
            <button class="btn btn-success">Lưu</button>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
