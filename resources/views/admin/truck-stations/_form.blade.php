<div class="card" style="max-width:800px;">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Tên trạm xe <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $station?->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nhà xe (Brand)</label>
                    <select name="brand_id" class="form-select">
                        <option value="">-- Chọn nhà xe --</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" {{ old('brand_id', $station?->brand_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tỉnh / Thành phố</label>
                    <select name="province_id" id="province_id" class="form-select">
                        <option value="">-- Chọn tỉnh/thành --</option>
                        @foreach($provinces as $p)
                            <option value="{{ $p->id }}" {{ old('province_id', $station?->province_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Phường / Xã</label>
                    <select name="ward_id" id="ward_id" class="form-select">
                        <option value="">-- Chọn phường/xã --</option>
                        @foreach($wards as $w)
                            <option value="{{ $w->id }}" {{ old('ward_id', $station?->ward_id) == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $station?->phone) }}">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Địa chỉ</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address', $station?->address) }}" placeholder="Số nhà, tên đường, khu vực...">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Phí vào bãi xe (₫)</label>
                    <input type="number" name="parking_fee" class="form-control" value="{{ old('parking_fee', $station?->parking_fee) }}" placeholder="0" min="0">
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Thông tin phòng / chi nhánh</label>
                    <input type="text" name="branch_info" class="form-control" value="{{ old('branch_info', $station?->branch_info) }}" placeholder="VD: Phòng hàng hóa tầng 1">
                </div>

                {{-- Home delivery --}}
                <div class="col-12">
                    <div class="card border-0 bg-light rounded-3 p-3">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="has_home_delivery" value="1"
                                       id="has_home_delivery"
                                       {{ old('has_home_delivery', $station?->has_home_delivery ?? false) ? 'checked' : '' }}
                                       onchange="document.getElementById('home-delivery-fee-wrap').style.display = this.checked ? '' : 'none'">
                                <label class="form-check-label fw-semibold" for="has_home_delivery">
                                    <i class="ph-house me-1 text-primary"></i> Có dịch vụ giao hàng tận nhà
                                </label>
                            </div>
                        </div>
                        <div id="home-delivery-fee-wrap"
                             style="{{ old('has_home_delivery', $station?->has_home_delivery ?? false) ? '' : 'display:none' }}">
                            <label class="form-label fw-semibold mb-1" style="font-size:.85rem;">Phí giao hàng tận nhà mặc định (₫)</label>
                            <div class="input-group" style="max-width:260px;">
                                <input type="number" name="home_delivery_fee" class="form-control"
                                       value="{{ old('home_delivery_fee', $station?->home_delivery_fee ?? 0) }}"
                                       placeholder="0" min="0">
                                <span class="input-group-text">₫</span>
                            </div>
                            <div class="form-text">Nhập <strong>0</strong> nếu miễn phí giao hàng tận nhà.</div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Ghi chú</label>
                    <textarea name="note" class="form-control" rows="2">{{ old('note', $station?->note) }}</textarea>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                            {{ old('is_active', $station?->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Đang hoạt động</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="ph-floppy-disk me-1"></i> Lưu</button>
                <a href="{{ route('admin.truck-stations.index') }}" class="btn btn-light border">Hủy</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('province_id');
    const wardSelect     = document.getElementById('ward_id');
    const wardsApiUrl    = '{{ route("api.wards") }}';
    const selectedWard   = '{{ old("ward_id", $station?->ward_id ?? "") }}';

    async function loadWards(provinceId, selectValue = '') {
        wardSelect.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
        if (!provinceId) return;
        try {
            const res   = await fetch(wardsApiUrl + '?province_id=' + provinceId);
            const wards = await res.json();
            wards.forEach(w => {
                const opt = document.createElement('option');
                opt.value = w.id; opt.textContent = w.name;
                if (String(w.id) === String(selectValue)) opt.selected = true;
                wardSelect.appendChild(opt);
            });
        } catch (e) {}
    }

    provinceSelect.addEventListener('change', () => loadWards(provinceSelect.value));

    // On page load, load wards for selected province
    if (provinceSelect.value) loadWards(provinceSelect.value, selectedWard);
});
</script>
@endpush
