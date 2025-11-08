@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Danh sách giao dịch</h2>
    <a href="{{ route('transactions.create') }}" class="btn btn-success mb-3">+ Thêm giao dịch</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Đơn hàng</th>
                <th>Khách hàng</th>
                <th>Số tiền</th>
                <th>Loại</th>
                <th>Phương thức</th>
                <th>Ghi chú</th>
                <th>Thời gian</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>@if($t->order_id)<a href="{{ route('orders.show', $t->order_id) }}">#{{ $t->order_id }}</a>@endif</td>
                    <td>@if($t->customer_id){{ $t->customer->name }}@endif</td>
                    <td>{{ number_format($t->amount,0,',','.') }}</td>
                    <td>{{ $t->type }}</td>
                    <td>{{ $t->method }}</td>
                    <td>{{ $t->note }}</td>
                    <td>{{ $t->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $transactions->links() }}
</div>
@endsection
