@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Chỉnh sửa loại khách hàng</h2>

    <form action="{{ route('customertype.update', $customerType) }}" method="POST">
        @csrf
        @method('PUT')

        @include('customers.types.form', ['type' => $customerType])

        <button type="submit" class="btn btn-success">Cập nhật</button>
        <a href="{{ route('customertype.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
