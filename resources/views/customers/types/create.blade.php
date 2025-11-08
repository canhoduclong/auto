@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Thêm loại khách hàng</h2>

    <form action="{{ route('customertype.store') }}" method="POST">
        @csrf

        @include('customers.types.form', ['type' => new \App\Models\CustomerType])

        <button type="submit" class="btn btn-success">Lưu</button>
        <a href="{{ route('customertype.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
