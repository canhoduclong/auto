<div class="row gy-2">
    <div class="col-md-6">
        <label class="form-label">Họ & tên</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}">
        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email ?? '') }}">
        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Website</label>
        <input type="url" name="website" class="form-control" value="{{ old('website', $customer->website ?? '') }}">
        @error('website') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Địa chỉ</label>
        <textarea name="address" class="form-control" rows="3">{{ old('address', $customer->addresses->where('is_default', 1)->first()->note ?? '') }}</textarea>
        @error('address') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Giới tính</label>
        <select name="gender" class="form-select">
            <option value="">-- Chọn --</option>
            <option value="male" {{ old('gender', $customer->gender ?? '') === 'male' ? 'selected' : '' }}>Nam</option>
            <option value="female" {{ old('gender', $customer->gender ?? '') === 'female' ? 'selected' : '' }}>Nữ</option>
            <option value="other" {{ old('gender', $customer->gender ?? '') === 'other' ? 'selected' : '' }}>Khác</option>
        </select>
        @error('gender') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Ngày sinh</label>
        <input type="date" name="dob" class="form-control" value="{{ old('dob', isset($customer) && $customer->dob ? $customer->dob->format('Y-m-d') : '') }}">
        @error('dob') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Loại khách hàng</label>
        <select name="customer_type_id" class="form-select">
            <option value="">-- Chọn loại --</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}" {{ (string)$t->id === (string) old('customer_type_id', $customer->customer_type_id ?? '') ? 'selected' : '' }}>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>
        @error('customer_type_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Ghi chú</label>
        <textarea name="note" class="form-control" rows="3">{{ old('note', $customer->note ?? '') }}</textarea>
        @error('note') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Giờ giao hàng</label>
        <input type="text" name="delivery_time" class="form-control" value="{{ old('delivery_time', $customer->delivery_time ?? '') }}" placeholder="VD: 8h-10h sáng">
        @error('delivery_time') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Đóng gói thùng xốp</label>
        <select name="foam_box_required" class="form-select">
            <option value="0" {{ old('foam_box_required', $customer->foam_box_required ?? 0)==0?'selected':'' }}>Không</option>
            <option value="1" {{ old('foam_box_required', $customer->foam_box_required ?? 0)==1?'selected':'' }}>Có (+70.000đ)</option>
        </select>
        @error('foam_box_required') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Có giao chành xe?</label>
        <select name="use_truck_station" class="form-select" id="use_truck_station">
            <option value="0" {{ old('use_truck_station', $customer->use_truck_station ?? 0)==0?'selected':'' }}>Không</option>
            <option value="1" {{ old('use_truck_station', $customer->use_truck_station ?? 0)==1?'selected':'' }}>Có</option>
        </select>
        @error('use_truck_station') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div id="truck_fields" style="display: none;">
        <div class="col-md-6">
            <label class="form-label">Địa chỉ chành xe</label>
            <input type="text" name="truck_station_address" class="form-control" value="{{ old('truck_station_address', $customer->truck_station_address ?? '') }}">
            @error('truck_station_address') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Giờ nhận hàng tại chành</label>
            <input type="text" name="truck_receive_time" class="form-control" value="{{ old('truck_receive_time', $customer->truck_receive_time ?? '') }}">
            @error('truck_receive_time') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Giờ trả hàng tại chành</label>
            <input type="text" name="truck_return_time" class="form-control" value="{{ old('truck_return_time', $customer->truck_return_time ?? '') }}">
            @error('truck_return_time') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Địa chỉ trả hàng</label>
            <input type="text" name="truck_return_address" class="form-control" value="{{ old('truck_return_address', $customer->truck_return_address ?? '') }}">
            @error('truck_return_address') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Số điện thoại chành xe</label>
            <input type="text" name="truck_station_phone" class="form-control" value="{{ old('truck_station_phone', $customer->truck_station_phone ?? '') }}">
            @error('truck_station_phone') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Phí chành xe (VNĐ)</label>
            <input type="number" name="truck_fee" class="form-control" value="{{ old('truck_fee', $customer->truck_fee ?? '') }}">
            @error('truck_fee') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Hóa đơn chứng từ (link/ảnh)</label>
            <input type="text" name="truck_invoice_image" class="form-control" value="{{ old('truck_invoice_image', $customer->truck_invoice_image ?? '') }}">
            @error('truck_invoice_image') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Ảnh giao chành xe (link/ảnh)</label>
            <input type="text" name="truck_delivery_image" class="form-control" value="{{ old('truck_delivery_image', $customer->truck_delivery_image ?? '') }}">
            @error('truck_delivery_image') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleTruckFields() {
        var val = document.getElementById('use_truck_station').value;
        document.getElementById('truck_fields').style.display = val == '1' ? '' : 'none';
    }
    document.getElementById('use_truck_station').addEventListener('change', toggleTruckFields);
    toggleTruckFields();
});
</script>
