<h5>Địa chỉ nhà phố</h5>
<div class="row">
    <div class="col-md-6">
        <label>Số nhà</label>
        <input type="text" name="house_number" value="{{ old('house_number', $address->house_number ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>Số nhà tạm</label>
        <input type="text" name="temporary_number" value="{{ old('temporary_number', $address->temporary_number ?? '') }}" class="form-control">
    </div>
</div> 

<hr>

<h5>Địa chỉ căn hộ / chung cư</h5>
<div class="row">
    <div class="col-md-6">
        <label>Tên dự án</label>
        <input type="text" name="project_name" value="{{ old('project_name', $address->project_name ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>Block</label>
        <input type="text" name="block" value="{{ old('block', $address->block ?? '') }}" class="form-control">
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <label>Tầng</label>
        <input type="text" name="floor" value="{{ old('floor', $address->floor ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>Số căn</label>
        <input type="text" name="unit_number" value="{{ old('unit_number', $address->unit_number ?? '') }}" class="form-control">
    </div>
</div>

<hr>

<h5>Thông tin chung</h5>
<div class="row">
    <div class="col-md-6">
        <label>Tên đường</label>
        <input type="text" name="street" value="{{ old('street', $address->street ?? '') }}" class="form-control">
    </div> 
    <div class="col-md-6">
        <label>Phường/Xã</label>
        <input type="text" name="ward" value="{{ old('ward', $address->ward ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>Quận/Huyện</label>
        <input type="text" name="district" value="{{ old('district', $address->district ?? '') }}" class="form-control">
    </div> 
    <div class="col-md-6">
        <label>Tỉnh/Thành phố</label>
        <input type="text" name="city" value="{{ old('city', $address->city ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label>Địa chỉ mặc định</label><br>
        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $address->is_default ?? false) ? 'checked' : '' }}>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <label>Ghi chú</label>
        <textarea name="note" class="form-control">{{ old('note', $address->note ?? '') }}</textarea>
    </div>
</div>
