<div class="mb-3">
    <label>Tên loại *</label>
    <input type="text" name="name" value="{{ old('name', $type->name ?? '') }}" class="form-control" required>
</div>

<div class="mb-3">
    <label>Mô tả</label>
    <textarea name="description" class="form-control">{{ old('description', $type->description ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>Số đơn tối thiểu</label>
        <input type="number" name="min_orders" value="{{ old('min_orders', $type->min_orders ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label>Tổng chi tiêu tối thiểu</label>
        <input type="number" name="min_total_spent" value="{{ old('min_total_spent', $type->min_total_spent ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label>Thời hạn (ngày)</label>
        <input type="number" name="valid_days" value="{{ old('valid_days', $type->valid_days ?? '') }}" class="form-control">
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>Chiết khấu (%)</label>
        <input type="number" step="0.01" name="discount_rate" value="{{ old('discount_rate', $type->discount_rate ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label>Freeship</label>
        <select name="free_shipping" class="form-control">
            <option value="0" {{ old('free_shipping', $type->free_shipping ?? 0) == 0 ? 'selected' : '' }}>Không</option>
            <option value="1" {{ old('free_shipping', $type->free_shipping ?? 0) == 1 ? 'selected' : '' }}>Có</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label>Mức ưu tiên</label>
        <input type="number" name="priority_level" value="{{ old('priority_level', $type->priority_level ?? 0) }}" class="form-control">
    </div>
</div>
