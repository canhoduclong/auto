<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $warehouse->name ?? '') }}" required>
</div>
<div class="card border-primary-subtle mb-3">
    <div class="card-body d-flex align-items-start gap-3">
        <div class="fs-3 text-primary"><i class="bi bi-arrows-expand"></i></div>
        <div class="flex-grow-1">
            <input type="hidden" name="expand_packing_size_bounds" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="expand_packing_size_bounds" name="expand_packing_size_bounds" value="1"
                       @checked(old('expand_packing_size_bounds', $warehouse->expand_packing_size_bounds ?? false))>
                <label class="form-check-label fw-bold" for="expand_packing_size_bounds">
                    Thêm cơ cấu đóng hàng chặn 2 đầu
                </label>
            </div>
            <div class="small text-muted mt-1">
                Khi Sale cho phép một dải size, Kho được dùng thêm một biến thể đứng ngay trước và một biến thể đứng ngay sau dải đó.
                Thứ tự liền kề lấy theo trường <strong>Thứ tự</strong> của biến thể trong Admin.
            </div>
        </div>
    </div>
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
