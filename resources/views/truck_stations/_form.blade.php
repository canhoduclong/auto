@php
    $selectedProvince = old('province_id', $truckStation->province_id ?? '');
    $selectedWard = old('ward_id', $truckStation->ward_id ?? '');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Tên nhà xe <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $truckStation->name ?? '') }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Số điện thoại</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $truckStation->phone ?? '') }}">
        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Tỉnh/Thành</label>
        <select name="province_id" id="province_id" class="form-select">
            <option value="">-- Chọn Tỉnh/Thành --</option>
            @foreach($provinces as $province)
                <option value="{{ $province->id }}" {{ (string) $selectedProvince === (string) $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
            @endforeach
        </select>
        @error('province_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Phường/Xã</label>
        <select name="ward_id" id="ward_id" class="form-select" data-selected-ward="{{ $selectedWard }}">
            <option value="">-- Chọn Phường/Xã --</option>
        </select>
        @error('ward_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Địa chỉ chi tiết</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $truckStation->address ?? '') }}">
        @error('address') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Ghi chú</label>
        <textarea name="note" class="form-control" rows="3">{{ old('note', $truckStation->note ?? '') }}</textarea>
        @error('note') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $truckStation->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Đang hoạt động</label>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('province_id');
    const wardSelect = document.getElementById('ward_id');

    if (!provinceSelect || !wardSelect) {
        return;
    }

    const selectedWard = wardSelect.dataset.selectedWard || '';

    const loadWards = function (provinceId, keepSelection = true) {
        if (!provinceId) {
            wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            return;
        }

        fetch(`{{ route('api.wards') }}?province_id=${provinceId}`)
            .then((response) => response.json())
            .then((wards) => {
                let html = '<option value="">-- Chọn Phường/Xã --</option>';
                wards.forEach((ward) => {
                    const isSelected = keepSelection && String(selectedWard) === String(ward.id);
                    html += `<option value="${ward.id}" ${isSelected ? 'selected' : ''}>${ward.name}</option>`;
                });
                wardSelect.innerHTML = html;
            })
            .catch(() => {
                wardSelect.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            });
    };

    provinceSelect.addEventListener('change', function () {
        wardSelect.dataset.selectedWard = '';
        loadWards(this.value, false);
    });

    if (provinceSelect.value) {
        loadWards(provinceSelect.value, true);
    }
});
</script>
@endpush
