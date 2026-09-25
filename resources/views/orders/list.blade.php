<table class="table table-bordered">
    <thead>
        <tr>
            <th>Sản phẩm</th>
            <th>Biến thể</th>
            <th>Giá</th>
            <th>Xóa</th>
        </tr>
    </thead>
    <tbody>
        @foreach($variants as $variant)
        <tr>
            <td>{{ $variant->product->name ?? '' }}</td>
            <td>{{ $variant->variant_name ?? ($variant->sku ?? $variant->id) }}</td>
            <td>{{ number_format($variant->price ?? 0, 0, ',', '.') }} đ</td>
            <td><button type="button" class="btn btn-danger btn-sm remove-variant-btn" data-variant-id="{{ $variant->id }}">X</button></td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="text-end fw-bold">Tổng tiền: <span id="list-total">{{ number_format($total, 0, ',', '.') }}</span> đ</div>
