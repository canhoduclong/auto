                            
@extends('layouts.site')

@section('content')
<style>
    .mc-edit-shell {
        background: linear-gradient(180deg, #f7fafc 0%, #eef2f7 100%);
        min-height: calc(100vh - 120px);
        padding: 2rem 0 2.5rem;
    }
    .mc-edit-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .mc-edit-title {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 700;
        color: #1f2937;
    }
    .mc-edit-subtitle {
        margin: 0.25rem 0 0;
        color: #6b7280;
        font-size: 0.95rem;
    }
    .mc-edit-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        background: #ffffff;
    }
    .mc-edit-card .card-header {
        border-bottom: 1px solid #eef2f7;
        background: #ffffff;
        font-weight: 700;
        color: #111827;
        padding: 1rem 1.25rem;
    }
    .mc-edit-card .card-body {
        padding: 1.25rem;
    }
    .mc-form-label {
        font-weight: 600;
        color: #374151;
    }
    .mc-form-control {
        border-radius: 10px;
        border-color: #dbe3ef;
        padding: 0.65rem 0.85rem;
    }
    .mc-form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
    }
    .mc-help {
        color: #6b7280;
        font-size: 0.82rem;
    }
    .mc-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: #e8f4ff;
        color: #0f4c81;
        font-size: 0.8rem;
        font-weight: 600;
    }
    @media (max-width: 991.98px) {
        .mc-edit-head {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="mc-edit-shell">
    <div class="container">
        <div class="mc-edit-head">
            <div>
                <div class="mc-badge"><i class="bi bi-person-plus"></i> Thêm khách hàng mới</div>
                <h1 class="mc-edit-title">Tạo khách hàng</h1>
                <p class="mc-edit-subtitle">Điền đầy đủ thông tin để quản lý khách hàng hiệu quả hơn.</p>
            </div>
            <a href="{{ route('pages.my_customer') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại danh sách
            </a>
        </div>

        @if(session('error'))
            <div class="alert alert-danger mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                <div class="fw-semibold mb-1">Không thể lưu thông tin:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="card mc-edit-card">
                    <div class="card-header">Thông tin khách hàng</div>
                    <div class="card-body">
                        <form action="{{ route('my_customer.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label mc-form-label">Tên khách hàng <span class="text-danger">*</span></label>
                                <input type="text" class="form-control mc-form-control" id="name" name="name" value="{{ old('name') }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label mc-form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control mc-form-control" id="email" name="email" value="{{ old('email') }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label mc-form-label">Điện thoại</label>
                                <input type="text" class="form-control mc-form-control" id="phone" name="phone" value="{{ old('phone') }}">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label mc-form-label">Địa chỉ khách hàng</label>
                                <textarea
                                    class="form-control mc-form-control"
                                    id="address"
                                    name="address"
                                    rows="3"
                                    placeholder="Nhập địa chỉ đầy đủ của khách hàng"
                                >{{ old('address') }}</textarea>
                                <div class="mc-help mt-1">Địa chỉ này dùng làm mặc định khi tạo đơn cho khách nếu chưa nhập địa chỉ giao riêng.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="province_id" class="form-label mc-form-label">Tỉnh / Thành phố</label>
                                    <select class="form-select mc-form-control" id="province_id" name="province_id">
                                        <option value="">-- Chọn tỉnh/thành phố --</option>
                                        @foreach(($provinces ?? []) as $province)
                                            <option value="{{ $province->id }}" {{ (string) old('province_id') === (string) $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="ward_id" class="form-label mc-form-label">Phường / Xã</label>
                                    <select class="form-select mc-form-control" id="ward_id" name="ward_id" data-selected-ward="{{ old('ward_id', '') }}" disabled>
                                        <option value="">-- Chọn phường/xã --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="company_name" class="form-label mc-form-label">Tên công ty</label>
                                    <input type="text" class="form-control mc-form-control" id="company_name" name="company_name" value="{{ old('company_name') }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="tax_code" class="form-label mc-form-label">Mã số thuế</label>
                                    <input type="text" class="form-control mc-form-control" id="tax_code" name="tax_code" value="{{ old('tax_code') }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="company_email" class="form-label mc-form-label">Email công ty</label>
                                    <input type="email" class="form-control mc-form-control" id="company_email" name="company_email" value="{{ old('company_email') }}">
                                </div>
                                <div class="col-12 mt-3">
                                    <label for="company_address" class="form-label mc-form-label">Địa chỉ công ty</label>
                                    <input type="text" class="form-control mc-form-control" id="company_address" name="company_address" value="{{ old('company_address') }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="size" class="form-label mc-form-label">Size</label>
                                <input type="text" class="form-control mc-form-control" id="size" name="size" value="{{ old('size') }}" placeholder="Nhập size (nếu có)">
                            </div>

                            <div class="mb-3">
                                <label for="production" class="form-label mc-form-label">Sản lượng</label>
                                <input type="number" step="any" class="form-control mc-form-control" id="production" name="production" value="{{ old('production') }}" placeholder="Nhập sản lượng (nếu có)">
                            </div>
                            <div class="mb-3">
                                <label for="delivery_time" class="form-label mc-form-label">Giờ giao hàng</label>
                                <input type="text" class="form-control mc-form-control" id="delivery_time" name="delivery_time" value="{{ old('delivery_time') }}" placeholder="Ví dụ: 8h-10h, 14h-16h, sau 17h">
                                <div class="mc-help mt-1">Thông tin này sẽ được dùng làm mặc định khi sale tạo đơn mới cho khách.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="use_truck_station" class="form-label mc-form-label">Gửi qua nhà xe</label>
                                    <select class="form-select mc-form-control" id="use_truck_station" name="use_truck_station">
                                        <option value="0" {{ old('use_truck_station', '0') === '0' ? 'selected' : '' }}>Không</option>
                                        <option value="1" {{ old('use_truck_station') === '1' ? 'selected' : '' }}>Có</option>
                                    </select>
                                </div>
                            </div>

                            <div id="truck_section" style="display:none;">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="truck_station_id" class="form-label mc-form-label">Nhà xe</label>
                                        <select class="form-select mc-form-control" id="truck_station_id" name="truck_station_id">
                                            <option value="">-- Chọn nhà xe --</option>
                                            @foreach(($truckStations ?? []) as $station)
                                                <option value="{{ $station->id }}" {{ (string) old('truck_station_id') === (string) $station->id ? 'selected' : '' }}>
                                                    {{ $station->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="truck_station_phone" class="form-label mc-form-label">Số điện thoại nhà xe</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_station_phone" name="truck_station_phone" value="{{ old('truck_station_phone') }}">
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label for="truck_station_address" class="form-label mc-form-label">Địa chỉ giao nhà xe</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_station_address" name="truck_station_address" value="{{ old('truck_station_address') }}">
                                    </div>
                                    <div class="col-md-3 mt-3">
                                        <label for="truck_receive_time" class="form-label mc-form-label">Giờ nhận</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_receive_time" name="truck_receive_time" value="{{ old('truck_receive_time') }}">
                                    </div>
                                    <div class="col-md-3 mt-3">
                                        <label for="truck_return_time" class="form-label mc-form-label">Giờ trả</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_return_time" name="truck_return_time" value="{{ old('truck_return_time') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="avatar" class="form-label mc-form-label">Ảnh khách hàng</label>
                                <input class="form-control mc-form-control" type="file" id="avatar" name="avatar" accept="image/*">
                                <div class="mt-3 d-flex justify-content-start">
                                    <img id="avatarPreview" src="https://ui-avatars.com/api/?name=Khach+Hang" alt="Avatar preview" class="rounded-circle border border-2" style="width: 100px; height: 100px; object-fit: cover; border-color: #0f766e !important; background: #f8fafc;">
                                </div>
                                <div class="mc-help mt-1">Chọn ảnh đại diện (tùy chọn)</div>
                            </div>

                            <div class="mc-actions pt-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i> Lưu khách hàng
                                </button>
                                <a href="{{ route('pages.my_customer') }}" class="btn btn-light border">Hủy</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const provinceSelect = document.getElementById('province_id');
        const wardSelect = document.getElementById('ward_id');
        const truckSection = document.getElementById('truck_section');
        const useTruckSelect = document.getElementById('use_truck_station');

        function loadWardsByProvince(provinceId, selectedWardId = '') {
            if (!provinceId) {
                wardSelect.disabled = true;
                wardSelect.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
                return;
            }

            fetch(`{{ route('api.wards') }}?province_id=${provinceId}`)
                .then(response => response.json())
                .then(data => {
                    let html = '<option value="">-- Chọn phường/xã --</option>';
                    data.forEach(ward => {
                        const selected = String(selectedWardId || '') === String(ward.id) ? 'selected' : '';
                        html += `<option value="${ward.id}" ${selected}>${ward.name}</option>`;
                    });
                    wardSelect.innerHTML = html;
                    wardSelect.disabled = false;
                })
                .catch(() => {
                    wardSelect.disabled = true;
                    wardSelect.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
                });
        }

        function toggleTruckSection() {
            truckSection.style.display = useTruckSelect.value === '1' ? '' : 'none';
        }

        provinceSelect.addEventListener('change', function() {
            loadWardsByProvince(this.value, '');
        });

        useTruckSelect.addEventListener('change', toggleTruckSection);

        if (provinceSelect.value) {
            loadWardsByProvince(provinceSelect.value, wardSelect.dataset.selectedWard || '');
        }
        toggleTruckSection();
    });

    document.getElementById('avatar').addEventListener('change', function(e) {
        const [file] = e.target.files;
        if (file) {
            document.getElementById('avatarPreview').src = URL.createObjectURL(file);
        }
    });
</script>
@endsection
