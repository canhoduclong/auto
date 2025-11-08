<table class="table table-bordered">
    <thead>
        <tr>
            <th>Sản phẩm</th>
            <th>Biến thể</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Thành tiền</th>
            <th>Xóa</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->variant->product->name ?? '' }}</td>
            <td>{{ $item->variant->variant_name ?? ($item->variant->sku ?? $item->variant->id) }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
            <td>{{ number_format($item->quantity * $item->price, 0, ',', '.') }} đ</td>
            <td><button type="button" class="btn btn-danger btn-sm remove-variant-btn" data-variant-id="{{ $item->variant_id }}">X</button></td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="text-end fw-bold">Tổng tiền: <span id="edit-list-total">{{ number_format($total, 0, ',', '.') }}</span> đ</div>
