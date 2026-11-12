@csrf
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Tên nhà cung cấp *</label><input class="form-control" name="name" maxlength="200" value="{{ old('name', $supplier->name ?? '') }}" required></div>
<div class="col-md-6"><label class="form-label">Điện thoại</label><input class="form-control" name="phone" maxlength="30" value="{{ old('phone', $supplier->phone ?? '') }}"></div>
<div class="col-12"><label class="form-label">Địa chỉ</label><input class="form-control" name="address" maxlength="500" value="{{ old('address', $supplier->address ?? '') }}"></div>
<div class="col-12"><label class="form-label">Ghi chú / thông tin liên hệ</label><textarea class="form-control" name="notes" maxlength="2000" rows="3">{{ old('notes', $supplier->notes ?? '') }}</textarea></div>
<div class="col-md-6"><label class="form-label">Trạng thái hợp tác</label><select name="is_active" class="form-select"><option value="1" @selected((string) old('is_active', isset($supplier) ? (int) $supplier->is_active : 1) === '1')>Đang hợp tác</option><option value="0" @selected((string) old('is_active', isset($supplier) ? (int) $supplier->is_active : 1) === '0')>Ngừng hợp tác</option></select></div>
<div class="col-12"><button class="btn btn-primary">Lưu nhà cung cấp</button></div></div>
