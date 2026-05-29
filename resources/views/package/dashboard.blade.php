@extends('layouts.package')
@section('title', 'Tổng quan đóng hàng')
@section('content')
<div class="container">
    <h2 class="mb-4">Tổng quan đóng hàng</h2>
    <div class="alert alert-info">Chào mừng bạn đến giao diện đóng hàng!</div>
    <ul>
        <li><a href="{{ route('package.orders') }}">Nhận đơn đóng hàng</a></li>
        <li><a href="{{ route('package.returns') }}">Nhận hàng trả về</a></li>
        <li><a href="{{ route('package.order-changes') }}">Yêu cầu thay đổi đơn</a></li>
        <li><a href="{{ route('package.inventory') }}">Thống kê tồn kho</a></li>
    </ul>
</div>
@endsection
