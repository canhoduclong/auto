@php
    $defaultAddress = isset($customer)
        ? ($customer->addresses->where('is_default', 1)->first() ?? $customer->addresses->first())
        : null;

    $selectedProvinceId = old('province_id', $defaultAddress->province_id ?? '');
    $selectedWardId = old('ward_id', $defaultAddress->ward_id ?? '');
@endphp

<div class="row gy-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('customers.form.name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name ?? '') }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('customers.form.phone') }}</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}">
        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('customers.form.email') }}</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email ?? '') }}">
        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('customers.form.website') }}</label>
        <input type="url" name="website" class="form-control" value="{{ old('website', $customer->website ?? '') }}">
        @error('website') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('customers.form.address') }}</label>
        <textarea name="address" class="form-control" rows="3">{{ old('address', $defaultAddress->note ?? '') }}</textarea>
        @error('address') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Tỉnh/Thành</label>
        <select name="province_id" id="province_id" class="form-select" data-selected-province="{{ $selectedProvinceId }}">
            <option value="">-- Chọn Tỉnh/Thành --</option>
            @foreach(($provinces ?? []) as $province)
                <option value="{{ $province->id }}" {{ (string) $selectedProvinceId === (string) $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
            @endforeach
        </select>
        @error('province_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Phường/Xã</label>
        <select name="ward_id" id="ward_id" class="form-select" data-selected-ward="{{ $selectedWardId }}">
            <option value="">-- Chọn Phường/Xã --</option>
        </select>
        @error('ward_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">{{ __('customers.form.gender') }}</label>
        <select name="gender" class="form-select">
            <option value="">{{ __('customers.form.gender_placeholder') }}</option>
            <option value="male" {{ old('gender', $customer->gender ?? '') === 'male' ? 'selected' : '' }}>{{ __('customers.form.gender_male') }}</option>
            <option value="female" {{ old('gender', $customer->gender ?? '') === 'female' ? 'selected' : '' }}>{{ __('customers.form.gender_female') }}</option>
            <option value="other" {{ old('gender', $customer->gender ?? '') === 'other' ? 'selected' : '' }}>{{ __('customers.form.gender_other') }}</option>
        </select>
        @error('gender') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">{{ __('customers.form.dob') }}</label>
        <input type="date" name="dob" class="form-control" value="{{ old('dob', isset($customer) && $customer->dob ? $customer->dob->format('Y-m-d') : '') }}">
        @error('dob') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('customers.form.customer_type') }}</label>
        <select name="customer_type_id" class="form-select">
            <option value="">{{ __('customers.form.customer_type_placeholder') }}</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}" {{ (string)$t->id === (string) old('customer_type_id', $customer->customer_type_id ?? '') ? 'selected' : '' }}>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>
        @error('customer_type_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">% hoa hồng sale</label>
        <input
            type="number"
            name="commission_percent"
            class="form-control"
            min="0"
            max="100"
            step="0.01"
            value="{{ old('commission_percent', $customer->commission_percent ?? 0) }}"
            placeholder="Ví dụ: 3.50"
        >
        @error('commission_percent') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('customers.form.note') }}</label>
        <textarea name="note" class="form-control" rows="3">{{ old('note', $customer->note ?? '') }}</textarea>
        @error('note') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 mt-1">
        <h6 class="fw-bold mb-2">Thông tin xuất hóa đơn</h6>
    </div>
    <div class="col-md-4">
        <label class="form-label">Tên công ty</label>
        <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $customer->company_name ?? '') }}">
        @error('company_name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Mã số thuế</label>
        <input type="text" name="tax_code" class="form-control" value="{{ old('tax_code', $customer->tax_code ?? '') }}">
        @error('tax_code') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Email công ty</label>
        <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $customer->company_email ?? '') }}">
        @error('company_email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Địa chỉ công ty</label>
        <input type="text" name="company_address" class="form-control" value="{{ old('company_address', $customer->company_address ?? '') }}">
        @error('company_address') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('customers.form.delivery_time') }}</label>
        <input type="text" name="delivery_time" class="form-control" value="{{ old('delivery_time', $customer->delivery_time ?? '') }}" placeholder="{{ __('customers.form.delivery_time_placeholder') }}">
        @error('delivery_time') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('customers.form.foam_box_required') }}</label>
        <select name="foam_box_required" class="form-select">
            <option value="0" {{ old('foam_box_required', $customer->foam_box_required ?? 0)==0?'selected':'' }}>{{ __('customers.form.no') }}</option>
            <option value="1" {{ old('foam_box_required', $customer->foam_box_required ?? 0)==1?'selected':'' }}>{{ __('customers.form.yes_with_fee') }}</option>
        </select>
        @error('foam_box_required') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('customers.form.truck_station_enabled') }}</label>
        <select name="use_truck_station" class="form-select" id="use_truck_station">
            <option value="0" {{ old('use_truck_station', $customer->use_truck_station ?? 0)==0?'selected':'' }}>{{ __('customers.form.no') }}</option>
            <option value="1" {{ old('use_truck_station', $customer->use_truck_station ?? 0)==1?'selected':'' }}>{{ __('customers.form.yes') }}</option>
        </select>
        @error('use_truck_station') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div id="truck_fields" style="display: none;">
        <div class="col-md-6">
            <label class="form-label">Nhà xe</label>
            <select name="truck_station_id" class="form-select">
                <option value="">-- Chọn nhà xe --</option>
                @foreach(($truckStations ?? []) as $station)
                    <option value="{{ $station->id }}" {{ (string) old('truck_station_id', $customer->truck_station_id ?? '') === (string) $station->id ? 'selected' : '' }}>
                        {{ $station->name }}
                    </option>
                @endforeach
            </select>
            @error('truck_station_id') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('customers.form.truck_station_address') }}</label>
            <input type="text" name="truck_station_address" class="form-control" value="{{ old('truck_station_address', $customer->truck_station_address ?? '') }}">
            @error('truck_station_address') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('customers.form.truck_receive_time') }}</label>
            <input type="text" name="truck_receive_time" class="form-control" value="{{ old('truck_receive_time', $customer->truck_receive_time ?? '') }}">
            @error('truck_receive_time') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('customers.form.truck_return_time') }}</label>
            <input type="text" name="truck_return_time" class="form-control" value="{{ old('truck_return_time', $customer->truck_return_time ?? '') }}">
            @error('truck_return_time') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('customers.form.truck_return_address') }}</label>
            <input type="text" name="truck_return_address" class="form-control" value="{{ old('truck_return_address', $customer->truck_return_address ?? '') }}">
            @error('truck_return_address') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('customers.form.truck_station_phone') }}</label>
            <input type="text" name="truck_station_phone" class="form-control" value="{{ old('truck_station_phone', $customer->truck_station_phone ?? '') }}">
            @error('truck_station_phone') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('customers.form.truck_fee') }}</label>
            <input type="number" name="truck_fee" class="form-control" value="{{ old('truck_fee', $customer->truck_fee ?? '') }}">
            @error('truck_fee') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('customers.form.truck_invoice_image') }}</label>
            <input type="text" name="truck_invoice_image" class="form-control" value="{{ old('truck_invoice_image', $customer->truck_invoice_image ?? '') }}">
            @error('truck_invoice_image') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('customers.form.truck_delivery_image') }}</label>
            <input type="text" name="truck_delivery_image" class="form-control" value="{{ old('truck_delivery_image', $customer->truck_delivery_image ?? '') }}">
            @error('truck_delivery_image') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('province_id');
    const wardSelect = document.getElementById('ward_id');

    function loadWards(provinceId, selectedWardId) {
        if (!provinceId) {
            wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            return;
        }

        fetch(`{{ route('api.wards') }}?province_id=${provinceId}`)
            .then((response) => response.json())
            .then((wards) => {
                let html = '<option value="">-- Chọn Phường/Xã --</option>';
                wards.forEach((ward) => {
                    const selected = String(selectedWardId || '') === String(ward.id) ? 'selected' : '';
                    html += `<option value="${ward.id}" ${selected}>${ward.name}</option>`;
                });
                wardSelect.innerHTML = html;
            })
            .catch(() => {
                wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            });
    }

    if (provinceSelect && wardSelect) {
        const selectedWard = wardSelect.dataset.selectedWard || '';

        if (provinceSelect.value) {
            loadWards(provinceSelect.value, selectedWard);
        }

        provinceSelect.addEventListener('change', function () {
            loadWards(this.value, '');
        });
    }

    function toggleTruckFields() {
        var val = document.getElementById('use_truck_station').value;
        document.getElementById('truck_fields').style.display = val == '1' ? '' : 'none';
    }
    document.getElementById('use_truck_station').addEventListener('change', toggleTruckFields);
    toggleTruckFields();
});
</script>
