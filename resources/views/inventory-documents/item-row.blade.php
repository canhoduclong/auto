<tr>
    <td>
        <select name="items[{{ $key }}][product_variant_id]" class="form-control" required>
            @foreach($productVariants as $variant)
                <option value="{{ $variant->id }}" {{ (isset($item['product_variant_id']) && $item['product_variant_id'] == $variant->id) ? 'selected' : '' }}>
                    {{ $variant->product->name }} ({{ $variant->sku }})
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" name="items[{{ $key }}][quantity]" class="form-control" value="{{ $item['quantity'] ?? 1 }}" required min="1">
    </td>
    <td>
        <input type="number" step="0.01" name="items[{{ $key }}][unit_cost]" class="form-control" value="{{ $item['unit_cost'] ?? 0 }}" required min="0">
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-sm remove-item-btn">Remove</button>
    </td>
</tr>
