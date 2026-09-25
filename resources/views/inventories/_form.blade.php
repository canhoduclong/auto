<div class="form-group mb-3">
    <label for="product_variant_id" class="form-label">Product Variant</label>
    <select name="product_variant_id" id="product_variant_id" class="form-control @error('product_variant_id') is-invalid @enderror" required>
        <option value="">Select a Variant</option>
        @foreach($productVariants as $variant)
            <option value="{{ $variant->id }}" {{ (old('product_variant_id', $inventory->product_variant_id ?? '') == $variant->id) ? 'selected' : '' }}>
                {{ $variant->product->name }} ({{ $variant->sku }})
            </option>
        @endforeach
    </select>
    @error('product_variant_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="warehouse_id" class="form-label">Warehouse</label>
    <select name="warehouse_id" id="warehouse_id" class="form-control @error('warehouse_id') is-invalid @enderror" required>
        <option value="">Select a Warehouse</option>
        @foreach($warehouses as $warehouse)
            <option value="{{ $warehouse->id }}" {{ (old('warehouse_id', $inventory->warehouse_id ?? '') == $warehouse->id) ? 'selected' : '' }}>
                {{ $warehouse->name }}
            </option>
        @endforeach
    </select>
    @error('warehouse_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="quantity" class="form-label">Quantity</label>
    <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $inventory->quantity ?? 0) }}" required min="0">
    @error('quantity')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
    <input type="number" name="low_stock_threshold" id="low_stock_threshold" class="form-control @error('low_stock_threshold') is-invalid @enderror" value="{{ old('low_stock_threshold', $inventory->low_stock_threshold ?? 10) }}" min="0">
    @error('low_stock_threshold')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary">Submit</button>
<a href="{{ route('inventories.index') }}" class="btn btn-secondary">Cancel</a>
