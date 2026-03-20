@extends('layouts.site')

@section('content')
<div class="container">
    <h1>Thêm khách hàng mới</h1>

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

    <div class="card">
        <div class="card-header">Thông tin khách hàng</div>
        <div class="card-body">
            <form action="{{ route('my_customer.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Tên</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Điện thoại</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="mb-3">
                    <label for="delivery_time" class="form-label">Giờ giao hàng</label>
                    <input type="text" class="form-control" id="delivery_time" name="delivery_time" value="{{ old('delivery_time') }}" placeholder="Ví dụ: 8h-10h, sau 17h">
                </div>
                <button type="submit" class="btn btn-primary">Lưu</button>
                <a href="{{ route('pages.my_customer') }}" class="btn btn-secondary">Hủy</a>
            </form>
        </div>
    </div>
</div>
@endsection
