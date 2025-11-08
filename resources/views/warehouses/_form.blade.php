<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $warehouse->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label for="address" class="form-label">Address</label>
    <textarea class="form-control" id="address" name="address">{{ old('address', $warehouse->address ?? '') }}</textarea>
</div>
<div class="mb-3">
    <label for="phone" class="form-label">Phone</label>
    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $warehouse->phone ?? '') }}">
</div>
<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select class="form-select" id="status" name="status" required>
        <option value="1" {{ old('status', $warehouse->status ?? '') == 1 ? 'selected' : '' }}>Active</option>
        <option value="0" {{ old('status', $warehouse->status ?? '') == 0 ? 'selected' : '' }}>Inactive</option>
    </select>
</div>