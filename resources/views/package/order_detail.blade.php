@extends('layouts.package')
@section('title', 'Chi tiết đơn đóng hàng')
@section('content')
<div class="container">
    <h2 class="mb-4">Chi tiết đơn #{{ $orderId }}</h2>
    <div class="alert alert-info">Thông tin chi tiết đơn hàng sẽ hiển thị ở đây.</div>
    <!-- TODO: Hiển thị chi tiết từng đơn, thao tác đóng hàng, cập nhật trạng thái -->
</div>
@endsection
