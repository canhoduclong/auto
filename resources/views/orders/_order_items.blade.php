<h5>Danh sách sản phẩm trong đơn hàng</h5>
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
