<div class="form-group mb-3">
    <label for="inventory_id" class="form-label">Inventory</label>
    <select name="inventory_id" id="inventory_id" class="form-control @error('inventory_id') is-invalid @enderror" required>
        <option value="">Select an Inventory</option>
        @foreach($inventories as $inventory)
            <option value="{{ $inventory->id }}" {{ (old('inventory_id', $inventoryMovement->inventory_id ?? '') == $inventory->id) ? 'selected' : '' }}>
                {{ $inventory->productVariant->product->name }} ({{ $inventory->productVariant->sku }}) @ {{ $inventory->warehouse->name }}
            </option>
        @endforeach
    </select>
    @error('inventory_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="quantity" class="form-label">Quantity</label>
    <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $inventoryMovement->quantity ?? 0) }}" required>
    @error('quantity')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="type" class="form-label">Type</label>
    <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
        <option value="">Select a Type</option>
        <option value="purchase" {{ (old('type', $inventoryMovement->type ?? '') == 'purchase') ? 'selected' : '' }}>Purchase</option>
        <option value="sale" {{ (old('type', $inventoryMovement->type ?? '') == 'sale') ? 'selected' : '' }}>Sale</option>
        <option value="adjustment" {{ (old('type', $inventoryMovement->type ?? '') == 'adjustment') ? 'selected' : '' }}>Adjustment</option>
        <option value="transfer" {{ (old('type', $inventoryMovement->type ?? '') == 'transfer') ? 'selected' : '' }}>Transfer</option>
    </select>
    @error('type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="reference_id" class="form-label">Reference ID</label>
    <input type="text" name="reference_id" id="reference_id" class="form-control @error('reference_id') is-invalid @enderror" value="{{ old('reference_id', $inventoryMovement->reference_id ?? '') }}">
    @error('reference_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label for="reference_type" class="form-label">Reference Type</label>
    <input type="text" name="reference_type" id="reference_type" class="form-control @error('reference_type') is-invalid @enderror" value="{{ old('reference_type', $inventoryMovement->reference_type ?? '') }}">
    @error('reference_type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary">Submit</button>
<a href="{{ route('inventory-movements.index') }}" class="btn btn-secondary">Cancel</a>
