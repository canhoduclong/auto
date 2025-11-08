@extends('layouts.site')

@section('content')
<div class="container">
    <h1>Thêm khách hàng mới</h1>

    <div class="card">
        <div class="card-header">Thông tin khách hàng</div>
        <div class="card-body">
            <form action="{{ route('my_customer.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Tên</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Điện thoại</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
                <button type="submit" class="btn btn-primary">Lưu</button>
                <a href="{{ route('pages.my_customer') }}" class="btn btn-secondary">Hủy</a>
            </form>
        </div>
    </div>
</div>
@endsection
