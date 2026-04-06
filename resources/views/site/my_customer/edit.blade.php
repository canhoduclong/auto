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
    .mc-edit-card,
    .mc-info-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        background: #ffffff;
    }
    .mc-edit-card .card-header,
    .mc-info-card .card-header {
        border-bottom: 1px solid #eef2f7;
        background: #ffffff;
        font-weight: 700;
        color: #111827;
        padding: 1rem 1.25rem;
    }
    .mc-edit-card .card-body,
    .mc-info-card .card-body {
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
    .mc-meta-item {
        padding: 0.7rem 0;
        border-bottom: 1px dashed #e5e7eb;
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .mc-meta-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .mc-meta-label {
        color: #6b7280;
        font-size: 0.9rem;
    }
    .mc-meta-value {
        color: #111827;
        font-weight: 600;
        text-align: right;
    }
    .mc-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
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
                <div class="mc-badge"><i class="bi bi-person-lines-fill"></i> Chỉnh sửa hồ sơ khách hàng</div>
                <h1 class="mc-edit-title">{{ $customer->name }}</h1>
                <p class="mc-edit-subtitle">Cập nhật thông tin liên hệ và khung giờ giao hàng theo nhu cầu thực tế.</p>
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
                        <form action="{{ route('my_customer.update', $customer) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="name" class="form-label mc-form-label">Tên khách hàng</label>
                                <input type="text" class="form-control mc-form-control" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label mc-form-label">Email</label>
                                <input type="email" class="form-control mc-form-control" id="email" name="email" value="{{ old('email', $customer->email) }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label mc-form-label">Điện thoại</label>
                                <input type="text" class="form-control mc-form-control" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label mc-form-label">Địa chỉ khách hàng</label>
                                <textarea
                                    class="form-control mc-form-control"
                                    id="address"
                                    name="address"
                                    rows="3"
                                    placeholder="Nhập địa chỉ đầy đủ của khách hàng"
                                >{{ old('address', $customer->address) }}</textarea>
                                <div class="mc-help mt-1">Địa chỉ này dùng làm mặc định khi tạo đơn cho khách nếu chưa nhập địa chỉ giao riêng.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="province_id" class="form-label mc-form-label">Tỉnh / Thành phố</label>
                                    <select class="form-select mc-form-control" id="province_id" name="province_id">
                                        <option value="">-- Chọn tỉnh/thành phố --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="district_id" class="form-label mc-form-label">Quận / Huyện</label>
                                    <select class="form-select mc-form-control" id="district_id" name="district_id" disabled>
                                        <option value="">-- Chọn quận/huyện --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="ward_id" class="form-label mc-form-label">Phường / Xã</label>
                                    <select class="form-select mc-form-control" id="ward_id" name="ward_id" disabled>
                                        <option value="">-- Chọn phường/xã --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="delivery_time" class="form-label mc-form-label">Giờ giao hàng</label>
                                <input type="text" class="form-control mc-form-control" id="delivery_time" name="delivery_time" value="{{ old('delivery_time', $customer->delivery_time) }}" placeholder="Ví dụ: 8h-10h, 14h-16h, sau 17h">
                                <div class="mc-help mt-1">Thông tin này sẽ được dùng làm mặc định khi sale tạo đơn mới cho khách.</div>
                            </div>

                            <div class="mb-3">
                                <label for="size" class="form-label mc-form-label">Size</label>
                                <input type="text" class="form-control mc-form-control" id="size" name="size" value="{{ old('size', $customer->size) }}" placeholder="Nhập size (nếu có)">
                            </div>

                            <div class="mb-3">
                                <label for="production" class="form-label mc-form-label">Sản lượng</label>
                                <input type="number" step="any" class="form-control mc-form-control" id="production" name="production" value="{{ old('production', $customer->production) }}" placeholder="Nhập sản lượng (nếu có)">
                                <div class="mc-help mt-1">Điền sản lượng trung bình theo đơn vị bạn đang theo dõi (ví dụ: kg/tháng).</div>
                            </div>

                            <div class="mc-actions pt-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i> Lưu thay đổi
                                </button>
                                <a href="{{ route('pages.my_customer') }}" class="btn btn-light border">Hủy</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card mc-info-card">
                    <div class="card-header">Thông tin nhanh</div>
                    <div class="card-body">
                        <div class="mc-meta-item">
                            <span class="mc-meta-label">Mã khách hàng</span>
                            <span class="mc-meta-value">#{{ $customer->id }}</span>
                        </div>
                        <div class="mc-meta-item">
                            <span class="mc-meta-label">Giờ giao hiện tại</span>
                            <span class="mc-meta-value">{{ $customer->delivery_time ?: '-' }}</span>
                        </div>
                        <div class="mc-meta-item">
                            <span class="mc-meta-label">Địa chỉ hiện tại</span>
                            <span class="mc-meta-value">{{ $customer->address ?: '-' }}</span>
                        </div>
                        <div class="mc-meta-item">
                            <span class="mc-meta-label">Cập nhật gần nhất</span>
                            <span class="mc-meta-value">{{ optional($customer->updated_at)->format('d/m/Y H:i') ?: '-' }}</span>
                        </div>
                        <div class="mc-meta-item">
                            <span class="mc-meta-label">Tạo lúc</span>
                            <span class="mc-meta-value">{{ optional($customer->created_at)->format('d/m/Y H:i') ?: '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const provinceSelect = document.getElementById('province_id');
        const districtSelect = document.getElementById('district_id');
        const wardSelect = document.getElementById('ward_id');

        // Load provinces on page load
        loadProvinces();

        // Load districts when province changes
        provinceSelect.addEventListener('change', function() {
            if (this.value) {
                loadDistricts(this.value);
                districtSelect.disabled = false;
            } else {
                districtSelect.disabled = true;
                wardSelect.disabled = true;
                districtSelect.innerHTML = '<option value="">-- Chọn quận/huyện --</option>';
                wardSelect.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
            }
        });

        // Load wards when district changes
        districtSelect.addEventListener('change', function() {
            if (this.value) {
                loadWards(this.value);
                wardSelect.disabled = false;
            } else {
                wardSelect.disabled = true;
                wardSelect.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
            }
        });

        function loadProvinces() {
            fetch('{{ route("api.provinces") }}')
                .then(response => response.json())
                .then(data => {
                    let html = '<option value="">-- Chọn tỉnh/thành phố --</option>';
                    data.forEach(province => {
                        html += `<option value="${province.id}">${province.name}</option>`;
                    });
                    provinceSelect.innerHTML = html;
                })
                .catch(error => console.error('Error loading provinces:', error));
        }

        function loadDistricts(provinceId) {
            fetch(`{{ route("api.districts") }}?province_id=${provinceId}`)
                .then(response => response.json())
                .then(data => {
                    let html = '<option value="">-- Chọn quận/huyện --</option>';
                    data.forEach(district => {
                        html += `<option value="${district.id}">${district.name}</option>`;
                    });
                    districtSelect.innerHTML = html;
                })
                .catch(error => console.error('Error loading districts:', error));
        }

        function loadWards(districtId) {
            fetch(`{{ route("api.wards") }}?district_id=${districtId}`)
                .then(response => response.json())
                .then(data => {
                    let html = '<option value="">-- Chọn phường/xã --</option>';
                    data.forEach(ward => {
                        html += `<option value="${ward.id}">${ward.name}</option>`;
                    });
                    wardSelect.innerHTML = html;
                })
                .catch(error => console.error('Error loading wards:', error));
        }
    });
</script>
@endpush

@endsection

