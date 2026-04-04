<tr>
    <td>
        <select name="items[{{ $key }}][product_variant_id]" class="form-control" required>
            @foreach($productVariants as $variant)
                <option
                    value="{{ $variant->id }}"
                    data-unit-label="{{ $variant->product->unit_label ?? 'Cái' }}"
                    data-default-weight="{{ number_format((float) ($variant->size ?? 0), 3, '.', '') }}"
                    data-weight-unit-label="{{ in_array((string) ($variant->product->unit ?? 'cai'), ['con', 'cai'], true) ? 'Kg' : ($variant->product->unit_label ?? 'Cái') }}"
                    {{ (isset($item['product_variant_id']) && $item['product_variant_id'] == $variant->id) ? 'selected' : '' }}
                >
                    {{ $variant->product->name }} ({{ $variant->sku }}) - {{ $variant->product->unit_label ?? 'Cai' }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" name="items[{{ $key }}][quantity]" class="form-control" value="{{ $item['quantity'] ?? 1 }}" required min="1">
    </td>
    <td>
        <span class="text-muted small unit-label-display" style="white-space:nowrap;">
            {{ optional(optional($productVariants->firstWhere('id', $item['product_variant_id'] ?? null))->product)->unit_label ?? 'Cái' }}
        </span>
    </td>
    <td>
        <div class="d-flex align-items-center gap-2">
            <input type="number" class="form-control form-control-sm weight-display" value="{{ number_format((float) ($item['weight'] ?? 0), 3, '.', '') }}" step="0.001" min="0" readonly>
            <span class="text-muted small weight-unit-display" style="white-space:nowrap;">Kg</span>
        </div>
    </td>
    <td>
        <input type="number" step="0.01" name="items[{{ $key }}][unit_cost]" class="form-control" value="{{ $item['unit_cost'] ?? 0 }}" required min="0">
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-sm remove-item-btn">{{ __('inventory.buttons.delete') }}</button>
    </td>
</tr>
