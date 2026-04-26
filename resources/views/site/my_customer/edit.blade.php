@extends('layouts.site')

@section('content')
@php
    $defaultAddress = $customer->addresses->where('is_default', 1)->first() ?? $customer->addresses->first();
    $selectedProvinceId = old('province_id', $defaultAddress->province_id ?? '');
    $selectedWardId = old('ward_id', $defaultAddress->ward_id ?? '');
@endphp
<style>
    .mc-edit-shell {
        background: linear-gradient(180deg, #f7fafc 0%, #eef2f7 100%);
        min-height: calc(100vh - 120px);
        padding: 2rem 0 2.5rem;
    }
    .mc-edit-head {
        display: flex; justify-content: space-between; align-items: center;
        gap: 1rem; margin-bottom: 1.5rem;
    }
    .mc-edit-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #1f2937; }
    .mc-edit-subtitle { margin: 0.25rem 0 0; color: #6b7280; font-size: 0.95rem; }
    .mc-edit-card {
        border: 0; border-radius: 14px;
        box-shadow: 0 4px 20px rgba(15,23,42,0.07);
        background: #ffffff; margin-bottom: 1.25rem;
    }
    .mc-edit-card .card-header {
        border-bottom: 1px solid #eef2f7; background: #ffffff;
        border-radius: 14px 14px 0 0; font-weight: 700; color: #111827;
        padding: 0.9rem 1.25rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1rem;
        cursor: pointer; user-select: none;
    }
    .mc-edit-card .card-header .card-num {
        width: 26px; height: 26px; border-radius: 50%; background: #2563eb; color: #fff;
        font-size: 0.78rem; font-weight: 700; display: inline-flex;
        align-items: center; justify-content: center; flex-shrink: 0;
    }
    .mc-edit-card .card-body { padding: 1.25rem; }
    .mc-edit-card .card-header:hover { background: #f7fafc; }
    .mc-card-toggle-icon {
        margin-left: auto; display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; border-radius: 50%; background: #f1f5f9;
        color: #6b7280; font-size: 1rem; flex-shrink: 0;
        transition: transform .25s, background .2s;
    }
    .mc-edit-card .card-header:hover .mc-card-toggle-icon { background: #e2e8f0; color: #374151; }
    .mc-edit-card.collapsed .mc-card-toggle-icon { transform: rotate(-90deg); }
    .mc-card-body-wrap { overflow: hidden; transition: max-height .3s ease; max-height: 2000px; }
    .mc-edit-card.collapsed .mc-card-body-wrap { max-height: 0; }
    .mc-card-err-badge { display:none; align-items:center; gap:0.3rem; font-size:0.78rem; font-weight:600; color:#dc2626; margin-left:0.5rem; }
    .mc-edit-card.has-error .mc-card-err-badge { display:inline-flex; }
    .mc-edit-card.has-error .card-header { border-left: 3px solid #dc2626; }
    .field-error-msg { color:#dc2626; font-size:0.78rem; margin-top:0.25rem; display:flex; align-items:center; gap:0.25rem; }
    .field-error-msg svg { width:14px; height:14px; flex-shrink:0; }
    .mc-info-card {
        border: 0; border-radius: 14px;
        box-shadow: 0 4px 20px rgba(15,23,42,0.07);
        background: #ffffff; margin-bottom: 1.25rem;
    }
    .mc-info-card .card-header {
        border-bottom: 1px solid #eef2f7; background: #ffffff;
        border-radius: 14px 14px 0 0; font-weight: 700; color: #111827; padding: 0.9rem 1.25rem;
    }
    .mc-info-card .card-body { padding: 1.25rem; }
    .mc-form-label { font-weight: 600; color: #374151; }
    .mc-form-control { border-radius: 10px; border-color: #dbe3ef; padding: 0.65rem 0.85rem; }
    .mc-form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 0.2rem rgba(37,99,235,0.15); }
    .mc-help { color: #6b7280; font-size: 0.82rem; }
    .mc-badge {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.35rem 0.75rem; border-radius: 999px;
        background: #e8f4ff; color: #0f4c81; font-size: 0.8rem; font-weight: 600;
    }
    .mc-meta-item {
        padding: 0.7rem 0; border-bottom: 1px dashed #e5e7eb;
        display: flex; justify-content: space-between; gap: 0.75rem;
    }
    .mc-meta-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .mc-meta-label { color: #6b7280; font-size: 0.9rem; }
    .mc-meta-value { color: #111827; font-weight: 600; text-align: right; }
    /* Truck station list */
    .truck-station-table { font-size: 0.875rem; }
    .truck-station-table thead th { background: #f8fafc; font-weight: 600; color: #374151; border-color: #eef2f7; }
    .truck-station-table tbody tr { cursor: pointer; transition: background .12s; }
    .truck-station-table tbody tr:hover { background: #eff6ff; }
    .truck-station-table tbody tr.selected-station { background: #dbeafe; }
    .truck-station-table td { vertical-align: middle; border-color: #eef2f7; }
    .truck-search-bar { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; flex-wrap: wrap; }
    .truck-search-bar input { flex: 1; min-width: 140px; }
    /* Product rows */
    .product-row {
        background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;
        padding: 0.9rem 1rem; margin-bottom: 0.75rem; position: relative;
    }
    .product-row .remove-product-row {
        position: absolute; top: 0.6rem; right: 0.75rem;
        background: none; border: none; color: #ef4444; font-size: 1.1rem; cursor: pointer; padding: 0;
    }
    .product-row .remove-product-row:hover { color: #b91c1c; }
    /* grid-dropdown widget */
    .mc-gd-wrap { position: relative; }
    .mc-gd-trigger {
        display: flex; align-items: center; justify-content: space-between;
        border: 1px solid #dbe3ef; border-radius: 10px;
        padding: .65rem .85rem; cursor: pointer; background: #fff;
        font-size: .95rem; color: #374151; user-select: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .mc-gd-trigger:hover { border-color: #2563eb; }
    .mc-gd-trigger.open { border-color: #2563eb; box-shadow: 0 0 0 .2rem rgba(37,99,235,.15); }
    .mc-gd-trigger .mc-gd-val { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .mc-gd-trigger .mc-gd-clear { display:none; line-height:1; color:#9ca3af; padding:0 2px 0 4px; font-size:1.1rem; }
    .mc-gd-trigger.has-value .mc-gd-clear { display:inline; }
    .mc-gd-panel {
        position: absolute; z-index: 1060; left: 0; top: calc(100% + 4px);
        min-width: 100%; width: max-content; max-width: 720px;
        background: #fff; border: 1px solid #dbe3ef;
        border-radius: 12px; box-shadow: 0 12px 40px rgba(15,23,42,.14);
        padding: 10px; display: none;
    }
    .mc-gd-panel.open { display: block; }
    .mc-gd-search {
        width: 100%; border: 1px solid #dbe3ef; border-radius: 8px;
        padding: .3rem .6rem; font-size: .82rem; margin-bottom: 8px; outline: none;
    }
    .mc-gd-search:focus { border-color: #2563eb; }
    .mc-gd-grid { display: grid; gap: 4px; max-height: 280px; overflow-y: auto; }
    .mc-gd-grid.cols-3 { grid-template-columns: repeat(3,1fr); }
    .mc-gd-grid.cols-4 { grid-template-columns: repeat(4,1fr); }
    .mc-gd-item {
        padding: 5px 8px; border-radius: 6px; font-size: .78rem;
        cursor: pointer; transition: background .1s; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
        border: 1px solid transparent;
    }
    .mc-gd-item:hover { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    .mc-gd-item.selected { background: #dbeafe; border-color: #93c5fd; color: #1e40af; font-weight: 600; }
    .mc-gd-empty { font-size:.82rem; color:#94a3b8; text-align:center; padding:12px; grid-column:1/-1; }
    @media (max-width:576px) {
        .mc-gd-panel { max-width: calc(100vw - 32px); }
        .mc-gd-grid.cols-3, .mc-gd-grid.cols-4 { grid-template-columns: repeat(2,1fr); }
    }
    @media (max-width: 991.98px) {
        .mc-edit-head { flex-direction: column; align-items: flex-start; }
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
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="row g-3">
            {{-- ── LEFT COLUMN: 4 cards ── --}}
            <div class="col-12 col-lg-8">

                <form action="{{ route('my_customer.update', $customer) }}" method="POST" autocomplete="off">
                    @csrf
                    @method('PUT')

                    {{-- CARD 1: Thông tin khách hàng --}}
                    <div class="card mc-edit-card" id="card-1">
                        <div class="card-header" onclick="toggleCard('card-1')">
                            <span class="card-num">1</span>
                            <i class="bi bi-person-fill text-primary"></i> Thông tin khách hàng
                            <span class="mc-card-toggle-icon"><i class="bi bi-chevron-down"></i></span>
                        </div>
                        <div class="mc-card-body-wrap"><div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="name" class="form-label mc-form-label">Tên khách hàng <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control mc-form-control" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="phone" class="form-label mc-form-label">Số điện thoại</label>
                                    <input type="text" class="form-control mc-form-control" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="email" class="form-label mc-form-label">Email</label>
                                    <input type="email" class="form-control mc-form-control" id="email" name="email" value="{{ old('email', $customer->email) }}">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="address" class="form-label mc-form-label">Địa chỉ</label>
                                    <input type="text" class="form-control mc-form-control" id="address" name="address"
                                        value="{{ old('address', $defaultAddress->note ?? $customer->address) }}"
                                        placeholder="Số nhà, tên đường...">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label mc-form-label">Tỉnh / Thành phố</label>
                                    <div class="mc-gd-wrap" id="province-gd-wrap">
                                        <div class="mc-gd-trigger" id="province-gd-trigger" tabindex="0">
                                            <span class="mc-gd-val text-muted">-- Chọn tỉnh/thành --</span>
                                            <span class="mc-gd-clear" title="Xoá">×</span>
                                            <i class="bi bi-chevron-down ms-1" style="font-size:.75rem;"></i>
                                        </div>
                                        <div class="mc-gd-panel" id="province-gd-panel">
                                            <input type="text" class="mc-gd-search" placeholder="🔍 Tìm tỉnh/thành...">
                                            <div class="mc-gd-grid cols-4" id="province-gd-grid">
                                                @foreach(($provinces ?? []) as $province)
                                                    <div class="mc-gd-item{{ (string)$selectedProvinceId === (string)$province->id ? ' selected' : '' }}"
                                                         data-value="{{ $province->id }}" data-label="{{ $province->name }}">{{ $province->name }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <input type="hidden" id="province_id" name="province_id" value="{{ $selectedProvinceId }}">
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label mc-form-label">Phường / Xã</label>
                                    <div class="mc-gd-wrap" id="ward-gd-wrap">
                                        <div class="mc-gd-trigger mc-gd-disabled" id="ward-gd-trigger" tabindex="0">
                                            <span class="mc-gd-val text-muted">-- Chọn phường/xã --</span>
                                            <span class="mc-gd-clear" title="Xoá">×</span>
                                            <i class="bi bi-chevron-down ms-1" style="font-size:.75rem;"></i>
                                        </div>
                                        <div class="mc-gd-panel" id="ward-gd-panel">
                                            <input type="text" class="mc-gd-search" placeholder="🔍 Tìm phường/xã...">
                                            <div class="mc-gd-grid cols-3" id="ward-gd-grid"></div>
                                        </div>
                                        <input type="hidden" id="ward_id" name="ward_id" value="{{ $selectedWardId }}">
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="delivery_time" class="form-label mc-form-label">Giờ giao hàng</label>
                                    <input type="text" class="form-control mc-form-control" id="delivery_time" name="delivery_time"
                                        value="{{ old('delivery_time', $customer->delivery_time) }}" placeholder="VD: 8h-10h, sau 17h">
                                    <div class="mc-help mt-1">Dùng mặc định khi tạo đơn.</div>
                                </div>
                            </div>
                        </div></div>{{-- /.mc-card-body-wrap --}}
                    </div>

                    {{-- CARD 2: Nhu cầu khách hàng --}}
                    <div class="card mc-edit-card" id="card-2">
                        <div class="card-header" onclick="toggleCard('card-2')">
                            <span class="card-num">2</span>
                            <i class="bi bi-clipboard-check text-primary"></i> Nhu cầu khách hàng
                            <span class="mc-card-toggle-icon"><i class="bi bi-chevron-down"></i></span>
                        </div>
                        <div class="mc-card-body-wrap"><div class="card-body">
                            <p class="mc-help mb-3">Thông tin nhu cầu đặt hàng mặc định khi sale tạo đơn.</p>

                            <div class="product-row" id="product-row-default">
                                <div class="fw-600 mb-2 text-primary" style="font-size:0.85rem;font-weight:600;">
                                    <i class="bi bi-box-seam me-1"></i> Sản phẩm chính
                                </div>
                                <div class="row g-2">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label mc-form-label" style="font-size:0.82rem;">Tên sản phẩm</label>
                                        <input type="text" class="form-control mc-form-control" name="product_name"
                                            value="{{ old('product_name', $customer->product_name ?? '') }}" placeholder="VD: Gà Tam Hoàng">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label mc-form-label" style="font-size:0.82rem;">Size</label>
                                        <input type="text" class="form-control mc-form-control" name="size"
                                            value="{{ old('size', $customer->size) }}" placeholder="VD: 1.2kg">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label mc-form-label" style="font-size:0.82rem;">Sản lượng</label>
                                        <input type="text" class="form-control mc-form-control" name="production"
                                            value="{{ old('production', $customer->production) }}" placeholder="VD: 120 con">
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label class="form-label mc-form-label" style="font-size:0.82rem;">Ghi chú</label>
                                        <input type="text" class="form-control mc-form-control" name="product_note"
                                            value="{{ old('product_note', $customer->product_note ?? '') }}" placeholder="Yêu cầu thêm...">
                                    </div>
                                </div>
                            </div>

                            <div id="extra-product-rows"></div>

                            <button type="button" class="btn btn-outline-success btn-sm mt-1" id="btn-add-product">
                                <i class="bi bi-plus-circle me-1"></i> Sản phẩm khác
                            </button>
                        </div></div>{{-- /.mc-card-body-wrap --}}
                    </div>

                    {{-- CARD 3: Thông tin công ty --}}
                    <div class="card mc-edit-card collapsed" id="card-3">
                        <div class="card-header" onclick="toggleCard('card-3')">
                            <span class="card-num">3</span>
                            <i class="bi bi-building text-primary"></i> Thông tin công ty (xuất hóa đơn)
                            <span class="mc-card-toggle-icon"><i class="bi bi-chevron-down"></i></span>
                        </div>
                        <div class="mc-card-body-wrap"><div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="company_name" class="form-label mc-form-label">Tên công ty</label>
                                    <input type="text" class="form-control mc-form-control" id="company_name" name="company_name" value="{{ old('company_name', $customer->company_name) }}">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label for="tax_code" class="form-label mc-form-label">Mã số thuế</label>
                                    <input type="text" class="form-control mc-form-control" id="tax_code" name="tax_code" value="{{ old('tax_code', $customer->tax_code) }}">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label for="company_email" class="form-label mc-form-label">Email công ty</label>
                                    <input type="email" class="form-control mc-form-control" id="company_email" name="company_email" value="{{ old('company_email', $customer->company_email) }}">
                                </div>
                                <div class="col-12 col-md-8">
                                    <label for="company_address" class="form-label mc-form-label">Địa chỉ công ty</label>
                                    <input type="text" class="form-control mc-form-control" id="company_address" name="company_address" value="{{ old('company_address', $customer->company_address) }}">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="company_representative" class="form-label mc-form-label">Người đại diện</label>
                                    <input type="text" class="form-control mc-form-control" id="company_representative" name="company_representative"
                                        value="{{ old('company_representative', $customer->company_representative ?? '') }}" placeholder="Họ tên người đại diện">
                                </div>
                            </div>
                        </div></div>{{-- /.mc-card-body-wrap --}}
                    </div>

                    {{-- CARD 4: Thông tin nhà xe --}}
                    <div class="card mc-edit-card collapsed" id="card-4">
                        <div class="card-header" onclick="toggleCard('card-4')">
                            <span class="card-num">4</span>
                            <i class="bi bi-truck text-primary"></i> Thông tin nhà xe
                            <span class="mc-card-toggle-icon"><i class="bi bi-chevron-down"></i></span>
                        </div>
                        <div class="mc-card-body-wrap"><div class="card-body">
                            <input type="hidden" name="use_truck_station" id="use_truck_station_hidden"
                                value="{{ old('use_truck_station', $customer->use_truck_station ? '1' : '0') }}">
                            <input type="hidden" name="truck_station_id" id="truck_station_id"
                                value="{{ old('truck_station_id', $customer->truck_station_id) }}">

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <button type="button" class="btn btn-outline-primary" id="btn-load-trucks">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Load nhà xe
                                </button>
                                <span class="text-muted mc-help" id="truck-load-status">Bấm để tải danh sách nhà xe.</span>
                            </div>

                            <div id="truck-search-area" style="display:none;">
                                <div class="truck-search-bar">
                                    <input type="text" class="form-control mc-form-control" id="truck-search-name" placeholder="🔍 Tìm theo tên nhà xe...">
                                    <input type="text" class="form-control mc-form-control" id="truck-search-dest" placeholder="🔍 Tìm theo điểm đến (tỉnh/thành)...">
                                </div>
                                <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
                                    <table class="table table-bordered table-hover truck-station-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:36px"></th>
                                                <th>Tên nhà xe</th>
                                                <th>Điểm đến</th>
                                                <th>Địa chỉ</th>
                                            </tr>
                                        </thead>
                                        <tbody id="truck-station-tbody">
                                            <tr><td colspan="4" class="text-center text-muted">Đang tải...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-1 mc-help" id="truck-selected-label">Chưa chọn nhà xe.</div>
                            </div>

                            <div id="truck-detail-section" class="mt-3" style="display:none;">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="truck_station_address" class="form-label mc-form-label">Địa chỉ giao nhà xe</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_station_address" name="truck_station_address"
                                            value="{{ old('truck_station_address', $customer->truck_station_address) }}">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="truck_station_phone" class="form-label mc-form-label">Số điện thoại nhà xe</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_station_phone" name="truck_station_phone"
                                            value="{{ old('truck_station_phone', $customer->truck_station_phone) }}">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label for="truck_receive_time" class="form-label mc-form-label">Giờ nhận hàng</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_receive_time" name="truck_receive_time"
                                            value="{{ old('truck_receive_time', $customer->truck_receive_time) }}" placeholder="VD: 7h-9h">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label for="truck_return_time" class="form-label mc-form-label">Giờ trả hàng</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_return_time" name="truck_return_time"
                                            value="{{ old('truck_return_time', $customer->truck_return_time) }}" placeholder="VD: 17h-19h">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label for="truck_fee" class="form-label mc-form-label">Phí nhà xe (₫)</label>
                                        <input type="number" class="form-control mc-form-control" id="truck_fee" name="truck_fee"
                                            value="{{ old('truck_fee', $customer->truck_fee ?? '') }}" placeholder="0">
                                    </div>
                                    <div class="col-12 col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger w-100" id="btn-clear-truck">
                                            <i class="bi bi-x-circle me-1"></i> Bỏ chọn nhà xe
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div></div>{{-- /.mc-card-body-wrap --}}
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 pb-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle me-1"></i> Lưu thay đổi
                        </button>
                        <a href="{{ route('pages.my_customer') }}" class="btn btn-light border">Hủy</a>
                    </div>

                </form>
            </div>

            {{-- ── RIGHT COLUMN: info sidebar ── --}}
            <div class="col-12 col-lg-4">
                <div class="card mc-info-card">
                    <div class="card-header"><i class="bi bi-info-circle me-1 text-primary"></i> Thông tin nhanh</div>
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
                            <span class="mc-meta-value" style="font-size:0.875rem;">{{ $defaultAddress->note ?? $customer->address ?: '-' }}</span>
                        </div>
                        <div class="mc-meta-item">
                            <span class="mc-meta-label">Nhà xe</span>
                            <span class="mc-meta-value">{{ $customer->truckStation->name ?? '-' }}</span>
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
document.addEventListener('DOMContentLoaded', function () {
    const wardsApiUrl = '{{ route("api.wards") }}';

    /* ── makeGridDropdown ─────────────────────────── */
    function makeGridDropdown(wrap) {
        const trigger = wrap.querySelector('.mc-gd-trigger');
        const panel   = wrap.querySelector('.mc-gd-panel');
        const valSpan = trigger.querySelector('.mc-gd-val');
        const clearBtn= trigger.querySelector('.mc-gd-clear');
        const search  = panel?.querySelector('.mc-gd-search');
        const grid    = panel?.querySelector('.mc-gd-grid');
        const hidden  = wrap.querySelector('input[type="hidden"]');
        let disabled  = trigger.classList.contains('mc-gd-disabled');

        valSpan.dataset.placeholder = valSpan.textContent.trim();

        if (hidden?.value) {
            const pre = grid?.querySelector(`.mc-gd-item[data-value="${hidden.value}"]`);
            if (pre) { valSpan.textContent = pre.dataset.label; valSpan.classList.remove('text-muted'); trigger.classList.add('has-value'); pre.classList.add('selected'); }
        }

        function close() {
            trigger.classList.remove('open'); panel?.classList.remove('open');
            if (search) { search.value = ''; filterItems(''); }
        }
        function open() {
            if (disabled) return;
            document.querySelectorAll('.mc-gd-panel.open').forEach(p => {
                if (p !== panel) { p.classList.remove('open'); p.closest('.mc-gd-wrap')?.querySelector('.mc-gd-trigger')?.classList.remove('open'); }
            });
            trigger.classList.add('open'); panel?.classList.add('open');
            if (search) setTimeout(() => search.focus(), 60);
        }
        function filterItems(q) {
            if (!grid) return;
            const lq = q.toLowerCase();
            grid.querySelectorAll('.mc-gd-item').forEach(el => { el.style.display = el.dataset.label.toLowerCase().includes(lq) ? '' : 'none'; });
            const any = [...grid.querySelectorAll('.mc-gd-item')].some(e => e.style.display !== 'none');
            let empty = grid.querySelector('.mc-gd-empty');
            if (!any) {
                if (!empty) { empty = document.createElement('div'); empty.className = 'mc-gd-empty'; empty.textContent = 'Không tìm thấy'; grid.appendChild(empty); }
                empty.style.display = '';
            } else if (empty) { empty.style.display = 'none'; }
        }
        function selectItem(value, label) {
            grid?.querySelectorAll('.mc-gd-item').forEach(el => el.classList.toggle('selected', el.dataset.value === String(value)));
            if (hidden) hidden.value = value || '';
            if (value) { valSpan.textContent = label; valSpan.classList.remove('text-muted'); trigger.classList.add('has-value'); }
            else { valSpan.textContent = valSpan.dataset.placeholder; valSpan.classList.add('text-muted'); trigger.classList.remove('has-value'); }
            close();
            wrap.dispatchEvent(new CustomEvent('gd:change', { detail: { value, label }, bubbles: true }));
        }

        trigger.addEventListener('click', e => {
            if (e.target.closest('.mc-gd-clear')) { e.stopPropagation(); selectItem('', ''); return; }
            trigger.classList.contains('open') ? close() : open();
        });
        trigger.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); trigger.classList.contains('open') ? close() : open(); }
            if (e.key === 'Escape') close();
        });
        if (search) {
            search.addEventListener('input', () => filterItems(search.value));
            search.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
        }
        if (grid) grid.addEventListener('click', e => { const item = e.target.closest('.mc-gd-item'); if (item) selectItem(item.dataset.value, item.dataset.label); });
        document.addEventListener('click', e => { if (!wrap.contains(e.target)) close(); });

        return {
            getValue()      { return hidden?.value || ''; },
            setValue(v, l)  { selectItem(v, l); },
            clearValue()    { selectItem('', ''); },
            setItems(items) {
                if (!grid) return;
                grid.querySelectorAll('.mc-gd-item,.mc-gd-empty').forEach(el => el.remove());
                items.forEach(item => {
                    const el = document.createElement('div');
                    el.className = 'mc-gd-item'; el.dataset.value = item.id; el.dataset.label = item.name; el.textContent = item.name;
                    grid.appendChild(el);
                });
            },
            disable() { disabled=true; trigger.classList.add('mc-gd-disabled'); trigger.style.opacity='.5'; trigger.style.pointerEvents='none'; close(); },
            enable()  { disabled=false; trigger.classList.remove('mc-gd-disabled'); trigger.style.opacity=''; trigger.style.pointerEvents=''; },
        };
    }

    /* ── Province / Ward ── */
    const provGd = makeGridDropdown(document.getElementById('province-gd-wrap'));
    const wardGd = makeGridDropdown(document.getElementById('ward-gd-wrap'));
    wardGd.disable();

    const selectedProvince = '{{ $selectedProvinceId }}';
    const selectedWard     = '{{ $selectedWardId }}';

    document.getElementById('province-gd-wrap').addEventListener('gd:change', e => {
        wardGd.clearValue(); wardGd.setItems([]);
        if (e.detail.value) {
            wardGd.disable();
            fetch(wardsApiUrl + '?province_id=' + e.detail.value)
                .then(r => r.json())
                .then(wards => { wardGd.setItems(wards); wardGd.enable(); })
                .catch(() => {});
        } else { wardGd.disable(); }
    });

    if (selectedProvince) {
        wardGd.disable();
        fetch(wardsApiUrl + '?province_id=' + selectedProvince)
            .then(r => r.json())
            .then(wards => {
                wardGd.setItems(wards);
                wardGd.enable();
                if (selectedWard) {
                    const item = document.querySelector(`#ward-gd-grid .mc-gd-item[data-value="${selectedWard}"]`);
                    if (item) wardGd.setValue(item.dataset.value, item.dataset.label);
                }
            })
            .catch(() => {});
    }

    /* ── Truck station AJAX ── */
    const btnLoadTrucks      = document.getElementById('btn-load-trucks');
    const truckLoadStatus    = document.getElementById('truck-load-status');
    const truckSearchArea    = document.getElementById('truck-search-area');
    const truckDetailSection = document.getElementById('truck-detail-section');
    const truckTbody         = document.getElementById('truck-station-tbody');
    const truckSearchName    = document.getElementById('truck-search-name');
    const truckSearchDest    = document.getElementById('truck-search-dest');
    const truckStationIdInput= document.getElementById('truck_station_id');
    const useTruckHidden     = document.getElementById('use_truck_station_hidden');
    const btnClearTruck      = document.getElementById('btn-clear-truck');
    const truckSelectedLabel = document.getElementById('truck-selected-label');

    let allTruckStations = @json($truckStations ?? []);
    let trucksLoaded = false;

    function renderTrucks(stations) {
        if (!stations.length) {
            truckTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Không có nhà xe phù hợp.</td></tr>';
            return;
        }
        const selectedId = truckStationIdInput.value;
        truckTbody.innerHTML = stations.map(s => {
            const dest     = s.province ? s.province.name : (s.province_id || '');
            const selected = String(s.id) === String(selectedId) ? 'selected-station' : '';
            const icon     = selected ? 'check-circle-fill text-primary' : 'circle text-muted';
            return `<tr class="${selected}" data-id="${s.id}" data-name="${s.name}" data-dest="${dest}">
                <td class="text-center"><i class="bi bi-${icon}"></i></td>
                <td>${s.name}</td>
                <td>${dest}</td>
                <td class="text-muted" style="font-size:0.8rem;">${s.ward ? s.ward.name : ''}</td>
            </tr>`;
        }).join('');
    }

    function filterTrucks() {
        const q1 = truckSearchName.value.toLowerCase();
        const q2 = truckSearchDest.value.toLowerCase();
        renderTrucks(allTruckStations.filter(s => {
            const dest = s.province ? s.province.name.toLowerCase() : '';
            return s.name.toLowerCase().includes(q1) && dest.includes(q2);
        }));
    }

    btnLoadTrucks.addEventListener('click', function () {
        if (trucksLoaded) { truckSearchArea.style.display = truckSearchArea.style.display === 'none' ? '' : 'none'; return; }
        truckLoadStatus.textContent = 'Đang tải...';
        btnLoadTrucks.disabled = true;
        fetch(`{{ route('pages.my_truck_stations.ajax') }}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                allTruckStations = Array.isArray(data) ? data : (data.data || data.stations || allTruckStations);
                trucksLoaded = true; btnLoadTrucks.disabled = false;
                truckLoadStatus.textContent = `Đã tải ${allTruckStations.length} nhà xe.`;
                renderTrucks(allTruckStations);
                truckSearchArea.style.display = '';
            })
            .catch(() => {
                trucksLoaded = true; btnLoadTrucks.disabled = false;
                truckLoadStatus.textContent = `Hiển thị ${allTruckStations.length} nhà xe.`;
                renderTrucks(allTruckStations);
                truckSearchArea.style.display = '';
            });
    });

    truckSearchName.addEventListener('input', filterTrucks);
    truckSearchDest.addEventListener('input', filterTrucks);

    truckTbody.addEventListener('click', function (e) {
        const row = e.target.closest('tr[data-id]');
        if (!row) return;
        truckTbody.querySelectorAll('tr').forEach(r => {
            r.classList.remove('selected-station');
            const ico = r.querySelector('i'); if (ico) ico.className = 'bi bi-circle text-muted';
        });
        row.classList.add('selected-station');
        const ico = row.querySelector('i'); if (ico) ico.className = 'bi bi-check-circle-fill text-primary';
        truckStationIdInput.value = row.dataset.id;
        useTruckHidden.value = '1';
        truckSelectedLabel.innerHTML = `<i class="bi bi-check-circle-fill text-primary me-1"></i>Đã chọn: <strong>${row.dataset.name}</strong>`;
        truckDetailSection.style.display = '';
    });

    btnClearTruck.addEventListener('click', function () {
        truckStationIdInput.value = '';
        useTruckHidden.value = '0';
        truckSelectedLabel.textContent = 'Chưa chọn nhà xe.';
        truckDetailSection.style.display = 'none';
        truckTbody.querySelectorAll('tr').forEach(r => {
            r.classList.remove('selected-station');
            const ico = r.querySelector('i'); if (ico) ico.className = 'bi bi-circle text-muted';
        });
    });

    // Pre-select truck if customer already has one
    if (truckStationIdInput.value) {
        useTruckHidden.value = '1';
        truckDetailSection.style.display = '';
        const found = allTruckStations.find(s => String(s.id) === String(truckStationIdInput.value));
        if (found) truckSelectedLabel.innerHTML = `<i class="bi bi-check-circle-fill text-primary me-1"></i>Đã chọn: <strong>${found.name}</strong>`;
    }

    /* ── Dynamic product rows ── */
    const extraRows  = document.getElementById('extra-product-rows');
    const btnAddProd = document.getElementById('btn-add-product');
    let productIndex = 0;

    btnAddProd.addEventListener('click', function () {
        const idx = productIndex++;
        const row = document.createElement('div');
        row.className = 'product-row';
        row.innerHTML = `
            <button type="button" class="remove-product-row" title="Xóa dòng"><i class="bi bi-x-lg"></i></button>
            <div class="mb-2 text-secondary" style="font-size:0.85rem;font-weight:600;">
                <i class="bi bi-box-seam me-1"></i> Sản phẩm ${idx + 2}
            </div>
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label mc-form-label" style="font-size:0.82rem;">Tên sản phẩm</label>
                    <input type="text" class="form-control mc-form-control" name="products[${idx}][name]" placeholder="VD: Gà Tam Hoàng">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mc-form-label" style="font-size:0.82rem;">Size</label>
                    <input type="text" class="form-control mc-form-control" name="products[${idx}][size]" placeholder="VD: 1.2kg">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mc-form-label" style="font-size:0.82rem;">Sản lượng</label>
                    <input type="text" class="form-control mc-form-control" name="products[${idx}][production]" placeholder="VD: 120 con">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label mc-form-label" style="font-size:0.82rem;">Giờ giao</label>
                    <input type="text" class="form-control mc-form-control" name="products[${idx}][delivery_time]" placeholder="VD: 8h-10h">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label mc-form-label" style="font-size:0.82rem;">Ghi chú</label>
                    <input type="text" class="form-control mc-form-control" name="products[${idx}][note]" placeholder="Yêu cầu thêm...">
                </div>
            </div>`;
        row.querySelector('.remove-product-row').addEventListener('click', () => row.remove());
        extraRows.appendChild(row);
    });

    // ===================== CARD TOGGLE =====================
    window.toggleCard = function (cardId) {
        const card = document.getElementById(cardId);
        if (!card) return;
        card.classList.toggle('collapsed');
        const key = 'mc_edit_card_' + cardId;
        try { localStorage.setItem(key, card.classList.contains('collapsed') ? '1' : '0'); } catch(e) {}
    };

    // Restore collapse state from localStorage
    ['card-1','card-2','card-3','card-4'].forEach(function (id) {
        try {
            const stored = localStorage.getItem('mc_edit_card_' + id);
            const card = document.getElementById(id);
            if (!card) return;
            if (stored === '1') {
                card.classList.add('collapsed');
            } else if (stored === '0') {
                card.classList.remove('collapsed');
            }
            // stored === null: keep HTML default
        } catch(e) {}
    });
});
</script>
@endpush

@endsection
