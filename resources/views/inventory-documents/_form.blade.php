<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="document_date" class="form-label">Date</label>
            <input type="date" name="document_date" id="document_date" class="form-control @error('document_date') is-invalid @enderror" value="{{ old('document_date', $inventoryDocument->document_date ?? date('Y-m-d')) }}" required>
            @error('document_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="type" class="form-label">Type</label>
            <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                <option value="import" {{ (old('type', $inventoryDocument->type ?? '') == 'import') ? 'selected' : '' }}>Import</option>
                <option value="export" {{ (old('type', $inventoryDocument->type ?? '') == 'export') ? 'selected' : '' }}>Export</option>
                <option value="adjustment" {{ (old('type', $inventoryDocument->type ?? '') == 'adjustment') ? 'selected' : '' }}>Adjustment</option>
            </select>
            @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="warehouse_id" class="form-label">Warehouse</label>
            <select name="warehouse_id" id="warehouse_id" class="form-control @error('warehouse_id') is-invalid @enderror" required>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ (old('warehouse_id', $inventoryDocument->warehouse_id ?? '') == $warehouse->id) ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            @error('warehouse_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="shipping_fee" class="form-label">Shipping Fee</label>
            <input type="number" step="0.01" name="shipping_fee" id="shipping_fee" class="form-control @error('shipping_fee') is-invalid @enderror" value="{{ old('shipping_fee', $inventoryDocument->shipping_fee ?? 0) }}">
            @error('shipping_fee')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $inventoryDocument->notes ?? '') }}</textarea>
    @error('notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<hr>

<h4>Items</h4>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Product Variant</th>
            <th>Quantity</th>
            <th>Unit Cost</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="items-tbody">
        @if(old('items'))
            @foreach(old('items') as $key => $item)
                @include('inventory-documents.item-row', ['key' => $key, 'item' => $item])
            @endforeach
        @elseif(isset($inventoryDocument))
            @foreach($inventoryDocument->items as $key => $item)
                @include('inventory-documents.item-row', ['key' => $key, 'item' => $item])
            @endforeach
        @endif
    </tbody>
</table>

<button type="button" id="add-item-btn" class="btn btn-success btn-sm">Add Item</button>

<hr>

<button type="submit" class="btn btn-primary">Save Document</button>
<a href="{{ route('inventory-documents.index') }}" class="btn btn-secondary">Cancel</a>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let tbody = document.getElementById('items-tbody');
        let addItemBtn = document.getElementById('add-item-btn');

        addItemBtn.addEventListener('click', function () {
            let key = new Date().getTime();
            let newRow = document.createElement('tr');
            newRow.innerHTML = `@include('inventory-documents.item-row', ['key' => '${key}'])`;
            tbody.appendChild(newRow);
        });

        tbody.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-item-btn')) {
                e.target.closest('tr').remove();
            }
        });
    });
</script>
@endpush
