<h5>Sản phẩm trong đơn hàng</h5>
<table class="table table-bordered" id="order-items-table">
    <thead>
        <tr>
            <th>Sản phẩm</th>
            <th>Biến thể</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Thành tiền</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $idx => $item)
        <tr>
            <td>
                <select name="items[{{ $idx }}][product_id]" class="form-control product-select" required>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="items[{{ $idx }}][product_variant_id]" class="form-control variant-select">
                    <option value="">-- Không chọn --</option>
                    @php
                        $product = $products->where('id', $item->product_id)->first();
                    @endphp
                    @if($product && $product->variants)
                        @foreach($product->variants as $variant)
                            <option value="{{ $variant->id }}" {{ $item->product_variant_id == $variant->id ? 'selected' : '' }}>{{ $variant->variant_name }}</option>
                        @endforeach
                    @endif
                </select>
            </td>
            <td><input type="number" name="items[{{ $idx }}][quantity]" class="form-control" value="{{ $item->quantity }}" min="1" required></td>
            <td><input type="number" name="items[{{ $idx }}][price]" class="form-control" value="{{ $item->price }}" min="0" required></td>
            <td class="item-total">{{ number_format($item->quantity * $item->price, 0, ',', '.') }}</td>
            <td><button type="button" class="btn btn-danger btn-sm remove-item">X</button></td>
        </tr>
        @endforeach
    </tbody>
</table>
<button type="button" class="btn btn-success btn-sm" id="add-item">+ Thêm sản phẩm</button>

@push('scripts')
<script>
    const products = @json($products);
    let itemIndex = {{ count($order->items) }};
    document.getElementById('add-item').onclick = function() {
        let row = `<tr>
            <td><select name="items[${itemIndex}][product_id]" class="form-control product-select" required>`;
        products.forEach(p => {
            row += `<option value="${p.id}">${p.name}</option>`;
        });
        row += `</select></td>`;
        row += `<td><select name="items[${itemIndex}][product_variant_id]" class="form-control variant-select"><option value="">-- Không chọn --</option></select></td>`;
        row += `<td><input type="number" name="items[${itemIndex}][quantity]" class="form-control" value="1" min="1" required></td>`;
        row += `<td><input type="number" name="items[${itemIndex}][price]" class="form-control" value="0" min="0" required></td>`;
        row += `<td class="item-total">0</td>`;
        row += `<td><button type="button" class="btn btn-danger btn-sm remove-item">X</button></td>`;
        row += `</tr>`;
        document.querySelector('#order-items-table tbody').insertAdjacentHTML('beforeend', row);
        itemIndex++;
    };
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            e.target.closest('tr').remove();
        }
        if (e.target.classList.contains('product-select')) {
            let tr = e.target.closest('tr');
            let productId = e.target.value;
            let variantSelect = tr.querySelector('.variant-select');
            let product = products.find(p => p.id == parseInt(productId));
            let html = '<option value="">-- Không chọn --</option>';
            if (product && product.variants) {
                product.variants.forEach(v => {
                    html += `<option value="${v.id}">${v.variant_name}</option>`;
                });
            }
            variantSelect.innerHTML = html;
        }
    });
</script>
@endpush
