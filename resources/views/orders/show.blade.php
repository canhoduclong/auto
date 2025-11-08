@extends('layouts.app')
@section('content')
<div class="container">
    <h4>Chi tiết đơn hàng #{{ $order->code }}</h4>
    <a href="{{ route('orders.index') }}" class="btn btn-secondary mb-3">Quay lại danh sách</a>
    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Khách hàng:</strong> {{ $order->customer->name ?? '' }}</p>
            <p><strong>Nhân viên:</strong> {{ $order->user->name ?? '' }}</p>
            <p><strong>Tổng tiền:</strong> {{ number_format($order->total, 0, ',', '.') }} đ</p> 
            <p><strong>Ngày tạo:</strong> {{ $order->created_at }}</p>
            <p><strong>Trạng thái hiện tại:</strong> {{ $order->status }}</p>
        </div>
        
        <div class="card-footer">
            @if($order->status === 'draft')
                <form action="{{ route('orders.confirm', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="status" value="pending">
                    <button type="submit" class="btn btn-primary">Lên đơn</button>
                </form>
            @endif
            @if($order->status === 'pending')
                <form action="{{ route('orders.confirm', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="btn btn-primary">Xác nhận đơn hàng</button>
                </form>
            @endif
            @if($order->status === 'confirmed')
                <form action="{{ route('orders.ship', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="btn btn-warning">Đánh dấu đã giao hàng</button>
                </form>
            @endif
            @if($order->status === 'shipped')
                <form action="{{ route('orders.complete', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="status" value="shipped">
                    <button type="submit" class="btn btn-success">Hoàn tất đơn hàng</button>
                </form>
            @endif
    </div>
    
    <h5>Danh sách sản phẩm</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Biến thể</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_variant->product->name ?? '' }}</td>
                <td>{{ $item->product_variant->variant_name ?? '' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
                <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if(!$order->isPaid())
        <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success mb-3">+ Thêm giao dịch/Thanh toán</a>
    @endif
    <h5>Giao dịch liên quan</h5>
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Số tiền</th>
                <th>Loại</th>
                <th>Phương thức</th>
                <th>Ghi chú</th>
                <th>Thời gian</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->transactions as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ number_format($t->amount,0,',','.') }}</td>
                    <td>{{ $t->type }}</td>
                    <td>{{ $t->method }}</td>
                    <td>{{ $t->note }}</td>
                    <td>{{ $t->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
