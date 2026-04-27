                            
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
        margin-bottom: 1.5rem;
    }
    .mc-edit-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #1f2937; }
    .mc-edit-subtitle { margin: 0.25rem 0 0; color: #6b7280; font-size: 0.95rem; }
    .mc-edit-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.07);
        background: #ffffff;
        margin-bottom: 1.25rem;
    }
    .mc-edit-card .card-header {
        border-bottom: 1px solid #eef2f7;
        background: #ffffff;
        border-radius: 14px 14px 0 0;
        font-weight: 700;
        color: #111827;
        padding: 0.9rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
    }
    .mc-edit-card .card-header .card-num {
        width: 26px; height: 26px;
        border-radius: 50%;
        background: #2563eb;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .mc-edit-card .card-body { padding: 1.25rem; }
    .mc-form-label { font-weight: 600; color: #374151; }
    .mc-form-control {
        border-radius: 10px;
        border-color: #dbe3ef;
        padding: 0.65rem 0.85rem;
    }
    .mc-form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
    }
    .mc-help { color: #6b7280; font-size: 0.82rem; }
    .mc-badge {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.35rem 0.75rem; border-radius: 999px;
        background: #e8f4ff; color: #0f4c81; font-size: 0.8rem; font-weight: 600;
    }
    /* Custom multi-column dropdown */
    .mc-dropdown { position: relative; }
    .mc-dropdown-btn {
        width: 100%; text-align: left; background: #fff; cursor: pointer;
        display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;
        border: 1px solid #dbe3ef; border-radius: 10px;
        padding: 0.65rem 0.85rem; font-size: 1rem; color: #212529; line-height: 1.5;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    .mc-dropdown-btn:hover { border-color: #b0bec5; }
    .mc-dropdown-btn:focus, .mc-dropdown-btn.open {
        border-color: #2563eb; box-shadow: 0 0 0 0.2rem rgba(37,99,235,.15); outline: none;
    }
    .mc-dropdown-btn .mc-dropdown-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; }
    .mc-dropdown-btn .mc-chevron {
        flex-shrink: 0; width: 10px; height: 10px;
        border: solid #6b7280; border-width: 0 2px 2px 0; display: inline-block;
        transform: rotate(45deg); margin-bottom: 3px; transition: transform .15s;
    }
    .mc-dropdown-btn.open .mc-chevron { transform: rotate(-135deg); margin-bottom: -3px; }
    .mc-dropdown-btn:disabled { background: #f3f4f6; cursor: not-allowed; color: #9ca3af; border-color: #e5e7eb; }
    .mc-dropdown-panel {
        position: absolute; z-index: 1055; background: #fff;
        border: 1px solid #dbe3ef; border-radius: 12px;
        box-shadow: 0 10px 28px rgba(15,23,42,0.13);
        min-width: 100%; width: max-content; max-width: min(700px, 96vw);
        padding: 0.6rem; top: calc(100% + 4px); left: 0; display: none;
    }
    .mc-dropdown-panel.open { display: block; }
    .mc-dropdown-search { margin-bottom: 0.5rem; }
    .mc-dropdown-search input {
        width: 100%; border: 1px solid #dbe3ef; border-radius: 8px;
        padding: 0.4rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;
    }
    .mc-dropdown-search input:focus { border-color: #2563eb; }
    .mc-dropdown-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; max-height: 260px; overflow-y: auto; }
    .mc-dropdown-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }
    .mc-dropdown-item {
        background: none; border: none; border-radius: 6px;
        padding: 0.3rem 0.55rem; text-align: left; font-size: 0.82rem; color: #374151;
        cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        width: 100%; display: block;
    }
    .mc-dropdown-item:hover { background: #eff6ff; color: #2563eb; }
    .mc-dropdown-item.selected { background: #2563eb; color: #fff; }
    .mc-dropdown-empty { color: #9ca3af; font-size: 0.85rem; padding: 0.5rem 0.25rem; text-align: center; grid-column: 1/-1; }
    @media (max-width: 575.98px) {
        .mc-dropdown-grid { grid-template-columns: repeat(2, 1fr); }
        .mc-dropdown-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
    }
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
    @media (max-width: 991.98px) {
        .mc-edit-head { flex-direction: column; align-items: flex-start; }
    }

    /* ── Card collapsible ── */
    .mc-edit-card .card-header {
        cursor: pointer;
        user-select: none;
    }
    .mc-edit-card .card-header:hover {
        background: #f8fafc;
    }
    .mc-card-toggle-icon {
        margin-left: auto;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #eef2f7;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background .15s;
        flex-shrink: 0;
    }
    .mc-edit-card .card-header:hover .mc-card-toggle-icon {
        background: #dce5f5;
    }
    .mc-card-toggle-icon svg {
        width: 13px; height: 13px; color: #374151;
        transition: transform .25s ease;
    }
    .mc-edit-card.collapsed .mc-card-toggle-icon svg {
        transform: rotate(-90deg);
    }
    .mc-card-body-wrap { 
        transition: max-height .3s ease, opacity .3s ease;
        max-height: 2000px;
        opacity: 1;
    }
    .mc-edit-card.collapsed .mc-card-body-wrap {
        max-height: 0;
        opacity: 0;
    }
    /* Badge lỗi trên header khi card thu gọn */
    .mc-card-err-badge {
        display: none;
        font-size: 0.72rem;
        font-weight: 700;
        background: #fee2e2;
        color: #b91c1c;
        border-radius: 999px;
        padding: 1px 9px;
        margin-left: 6px;
        white-space: nowrap;
    }
    .mc-edit-card.has-error .mc-card-err-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .mc-edit-card.has-error .card-header {
        border-left: 3px solid #ef4444;
    }

    /* ── Inline field errors ── */
    .field-error-msg {
        font-size: 0.8rem;
        color: #dc2626;
        margin-top: 4px;
        display: flex;
        align-items: flex-start;
        gap: 4px;
    }
    .field-error-msg svg {
        width: 13px; height: 13px; flex-shrink: 0; margin-top: 1px;
    }
    .mc-form-control.is-invalid {
        border-color: #ef4444;
        box-shadow: 0 0 0 0.2rem rgba(239,68,68,.14);
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

        <form action="{{ route('my_customer.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
            @csrf

            {{-- ===================== CARD 1: Thông tin khách hàng ===================== --}}
            <div class="card mc-edit-card{{ $errors->hasAny(['name','phone','email','address','province_id','ward_id']) ? ' has-error' : '' }}" id="card-1">
                <div class="card-header" onclick="toggleCard('card-1')">
                    <span class="card-num">1</span>
                    <i class="bi bi-person-fill text-primary"></i> Thông tin khách hàng
                    <span class="mc-card-err-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.345 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg> Có lỗi</span>
                    <span class="mc-card-toggle-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg></span>
                </div>
                <div class="mc-card-body-wrap"><div class="card-body">
                    
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="row g-3">

                                <div class="col-12 col-md-12">
                                    <label for="name" class="form-label mc-form-label">Tên khách hàng <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control mc-form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')<div class="field-error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>{{ $message }}</div>@enderror
                                    <div id="name-duplicate-alert" class="alert alert-danger py-2 px-3 mt-2 d-none" role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="phone" class="form-label mc-form-label">Số điện thoại</label>
                                    <input type="text" class="form-control mc-form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                    @error('phone')<div class="field-error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>{{ $message }}</div>@enderror
                                    <div id="phone-duplicate-alert" class="alert alert-danger py-2 px-3 mt-2 d-none" role="alert"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="email" class="form-label mc-form-label">Email</label>
                                    <input type="email" class="form-control mc-form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                                    @error('email')<div class="field-error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>{{ $message }}</div>@enderror
                                    <div id="email-duplicate-alert" class="alert alert-danger py-2 px-3 mt-2 d-none" role="alert"></div>
                                </div>
                                <div class="col-12 col-md-12">
                                    <label for="address" class="form-label mc-form-label">Địa chỉ</label>
                                    <input type="text" class="form-control mc-form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}" placeholder="Số nhà, tên đường...">
                                    @error('address')<div class="field-error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label mc-form-label">Tỉnh / Thành phố</label>
                                    <input type="hidden" name="province_id" id="province_id" value="{{ old('province_id') }}">
                                    <div class="mc-dropdown">
                                        @php
                                            $_selProv = ($provinces ?? collect())->firstWhere('id', old('province_id'));
                                            $_selProvLabel = $_selProv ? $_selProv->name : '-- Chọn tỉnh/thành --';
                                        @endphp
                                        <button type="button" class="mc-dropdown-btn" id="province-btn">
                                            <span class="mc-dropdown-label">{{ $_selProvLabel }}</span>
                                            <span class="mc-chevron"></span>
                                        </button>
                                        <div class="mc-dropdown-panel" id="province-panel">
                                            <div class="mc-dropdown-search">
                                                <input type="text" id="province-search" placeholder="🔍 Tìm tỉnh/thành..." autocomplete="off">
                                            </div>
                                            <div class="mc-dropdown-grid" id="province-grid">
                                                @foreach(($provinces ?? []) as $province)
                                                    <button type="button"
                                                        class="mc-dropdown-item{{ (string) old('province_id') === (string) $province->id ? ' selected' : '' }}"
                                                        data-value="{{ $province->id }}"
                                                        data-label="{{ $province->name }}">{{ $province->name }}</button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label mc-form-label">Phường / Xã</label>
                                    <input type="hidden" name="ward_id" id="ward_id" value="{{ old('ward_id', '') }}">
                                    <div class="mc-dropdown">
                                        <button type="button" class="mc-dropdown-btn" id="ward-btn" disabled>
                                            <span class="mc-dropdown-label">-- Chọn phường/xã --</span>
                                            <span class="mc-chevron"></span>
                                        </button>
                                        <div class="mc-dropdown-panel" id="ward-panel">
                                            <div class="mc-dropdown-search">
                                                <input type="text" id="ward-search" placeholder="🔍 Tìm phường/xã..." autocomplete="off">
                                            </div>
                                            <div class="mc-dropdown-grid cols-4" id="ward-grid"></div>
                                        </div>
                                    </div>
                                </div>                                

                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="avatar" class="form-label mc-form-label">Ảnh đại diện</label>
                            <input class="form-control mc-form-control" type="file" id="avatar" name="avatar" accept="image/*">
                            <div class="mt-2">
                                <img id="avatarPreview" src="https://ui-avatars.com/api/?name=Khach+Hang" alt="Avatar" class="" style="width:210px;height:210px;object-fit:cover;border-color:#0f766e!important;">
                            </div>                                
                        </div>
                    </div>
                </div></div>{{-- /.mc-card-body-wrap --}}
            </div>

            {{-- ===================== CARD 2: Nhu cầu khách hàng ===================== --}}
            <div class="card mc-edit-card{{ $errors->hasAny(['size','production','delivery_time','product_name','product_note']) ? ' has-error' : '' }}" id="card-2">
                <div class="card-header" onclick="toggleCard('card-2')">
                    <span class="card-num">2</span>
                    <i class="bi bi-clipboard-check text-primary"></i> Nhu cầu khách hàng
                    <span class="mc-card-err-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.345 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg> Có lỗi</span>
                    <span class="mc-card-toggle-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg></span>
                </div>
                <div class="mc-card-body-wrap"><div class="card-body">
                    <p class="mc-help mb-3">Thông tin nhu cầu đặt hàng mặc định. Sale sẽ thấy dữ liệu này khi tạo đơn cho khách.</p>

                    {{-- Dòng sản phẩm mặc định --}}
                    <div class="product-row" id="product-row-default">
                        <div class="fw-600 mb-2 text-primary" style="font-size:0.85rem;font-weight:600;">
                            <i class="bi bi-box-seam me-1"></i> Sản phẩm chính
                        </div>
                        <div class="row g-2">
                            <div class="col-12 col-md-4">
                                <label class="form-label mc-form-label" style="font-size:0.82rem;">Tên sản phẩm</label>
                                <input type="text" class="form-control mc-form-control" name="product_name" value="{{ old('product_name') }}" placeholder="VD: Gà Tam Hoàng">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label mc-form-label" style="font-size:0.82rem;">Size</label>
                                <input type="text" class="form-control mc-form-control" name="size" value="{{ old('size') }}" placeholder="VD: 1.2kg">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label mc-form-label" style="font-size:0.82rem;">Sản lượng</label>
                                <input type="text" class="form-control mc-form-control" name="production" value="{{ old('production') }}" placeholder="VD: 120 con">
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label mc-form-label" style="font-size:0.82rem;">Giờ giao hàng</label>
                                <input type="text" class="form-control mc-form-control" name="delivery_time" id="delivery_time" value="{{ old('delivery_time') }}" placeholder="VD: 8h-10h">
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label mc-form-label" style="font-size:0.82rem;">Ghi chú</label>
                                <input type="text" class="form-control mc-form-control" name="product_note" value="{{ old('product_note') }}" placeholder="Yêu cầu thêm...">
                            </div>
                        </div>
                    </div>

                    {{-- Dynamic product rows --}}
                    <div id="extra-product-rows"></div>

                    <button type="button" class="btn btn-outline-success btn-sm mt-1" id="btn-add-product">
                        <i class="bi bi-plus-circle me-1"></i> Sản phẩm khác
                    </button>
                </div></div>{{-- /.mc-card-body-wrap --}}
            </div>

            {{-- ===================== CARD 3: Thông tin công ty ===================== --}}
            <div class="card mc-edit-card{{ $errors->hasAny(['company_name','tax_code','company_email','company_address','company_representative']) ? ' has-error' : ' collapsed' }}" id="card-3">
                <div class="card-header" onclick="toggleCard('card-3')">
                    <span class="card-num">3</span>
                    <i class="bi bi-building text-primary"></i> Thông tin công ty (xuất hóa đơn)
                    <span class="mc-card-err-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.345 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg> Có lỗi</span>
                    <span class="mc-card-toggle-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg></span>
                </div>
                <div class="mc-card-body-wrap"><div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="company_name" class="form-label mc-form-label">Tên công ty</label>
                            <input type="text" class="form-control mc-form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name') }}">
                            @error('company_name')<div class="field-error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="tax_code" class="form-label mc-form-label">Mã số thuế</label>
                            <input type="text" class="form-control mc-form-control @error('tax_code') is-invalid @enderror" id="tax_code" name="tax_code" value="{{ old('tax_code') }}">
                            @error('tax_code')<div class="field-error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="company_email" class="form-label mc-form-label">Email công ty</label>
                            <input type="email" class="form-control mc-form-control @error('company_email') is-invalid @enderror" id="company_email" name="company_email" value="{{ old('company_email') }}">
                            @error('company_email')<div class="field-error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="company_address" class="form-label mc-form-label">Địa chỉ công ty</label>
                            <input type="text" class="form-control mc-form-control @error('company_address') is-invalid @enderror" id="company_address" name="company_address" value="{{ old('company_address') }}">
                            @error('company_address')<div class="field-error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="company_representative" class="form-label mc-form-label">Người đại diện</label>
                            <input type="text" class="form-control mc-form-control" id="company_representative" name="company_representative" value="{{ old('company_representative') }}" placeholder="Họ tên người đại diện">
                        </div>
                    </div>
                </div></div>{{-- /.mc-card-body-wrap --}}
            </div>

            {{-- ===================== CARD 4: Thông tin nhà xe ===================== --}}
            <div class="card mc-edit-card{{ $errors->hasAny(['truck_station_id','truck_station_address','truck_station_phone','truck_receive_time','truck_return_time','truck_fee']) ? ' has-error' : ' collapsed' }}" id="card-4">
                <div class="card-header" onclick="toggleCard('card-4')">
                    <span class="card-num">4</span>
                    <i class="bi bi-truck text-primary"></i> Thông tin nhà xe
                    <span class="mc-card-err-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.345 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg> Có lỗi</span>
                    <span class="mc-card-toggle-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg></span>
                </div>
                <div class="mc-card-body-wrap"><div class="card-body">
                    <input type="hidden" name="use_truck_station" id="use_truck_station_hidden" value="{{ old('use_truck_station', '0') }}">
                    <input type="hidden" name="truck_station_id" id="truck_station_id" value="{{ old('truck_station_id') }}">

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
                                <input type="text" class="form-control mc-form-control" id="truck_station_address" name="truck_station_address" value="{{ old('truck_station_address') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="truck_station_phone" class="form-label mc-form-label">Số điện thoại nhà xe</label>
                                <input type="text" class="form-control mc-form-control" id="truck_station_phone" name="truck_station_phone" value="{{ old('truck_station_phone') }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="truck_receive_time" class="form-label mc-form-label">Giờ nhận hàng</label>
                                <input type="text" class="form-control mc-form-control" id="truck_receive_time" name="truck_receive_time" value="{{ old('truck_receive_time') }}" placeholder="VD: 7h-9h">
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="truck_return_time" class="form-label mc-form-label">Giờ trả hàng</label>
                                <input type="text" class="form-control mc-form-control" id="truck_return_time" name="truck_return_time" value="{{ old('truck_return_time') }}" placeholder="VD: 17h-19h">
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="truck_fee" class="form-label mc-form-label">Phí nhà xe (₫)</label>
                                <input type="number" class="form-control mc-form-control" id="truck_fee" name="truck_fee" value="{{ old('truck_fee') }}" placeholder="0">
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="truck_fee" class="form-label mc-form-label">Phí nhà xe (₫)</label>
                                <input type="number" class="form-control mc-form-control @error('truck_fee') is-invalid @enderror" id="truck_fee" name="truck_fee" value="{{ old('truck_fee') }}" placeholder="0">
                                @error('truck_fee')<div class="field-error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd"/></svg>{{ $message }}</div>@enderror
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
                    <i class="bi bi-check-circle me-1"></i> Lưu khách hàng
                </button>
                <a href="{{ route('pages.my_customer') }}" class="btn btn-light border">Hủy</a>
            </div>

        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ===================== CUSTOM DROPDOWN (Province / Ward) =====================
    const provinceHidden = document.getElementById('province_id');
    const wardHidden     = document.getElementById('ward_id');
    const provinceBtn    = document.getElementById('province-btn');
    const provincePanel  = document.getElementById('province-panel');
    const provinceSearch = document.getElementById('province-search');
    const provinceGrid   = document.getElementById('province-grid');
    const wardBtn        = document.getElementById('ward-btn');
    const wardPanel      = document.getElementById('ward-panel');
    const wardSearch     = document.getElementById('ward-search');
    const wardGrid       = document.getElementById('ward-grid');

    function openPanel(panel, btn) {
        closeAllPanels();
        panel.classList.add('open');
        btn.classList.add('open');
        const si = panel.querySelector('input[type=text]');
        if (si) { si.value = ''; filterGrid(panel.querySelector('.mc-dropdown-grid'), ''); si.focus(); }
    }
    function closeAllPanels() {
        document.querySelectorAll('.mc-dropdown-panel.open').forEach(p => p.classList.remove('open'));
        document.querySelectorAll('.mc-dropdown-btn.open').forEach(b => b.classList.remove('open'));
    }
    function filterGrid(grid, q) {
        let visible = 0;
        grid.querySelectorAll('.mc-dropdown-item').forEach(item => {
            const match = item.dataset.label.toLowerCase().includes(q.toLowerCase());
            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        let empty = grid.querySelector('.mc-dropdown-empty');
        if (visible === 0) {
            if (!empty) { empty = document.createElement('div'); empty.className = 'mc-dropdown-empty'; grid.appendChild(empty); }
            empty.textContent = 'Không tìm thấy kết quả';
        } else if (empty) { empty.remove(); }
    }
    function selectItem(hiddenInput, btn, grid, value, label) {
        hiddenInput.value = value;
        btn.querySelector('.mc-dropdown-label').textContent = label || '-- Chọn --';
        grid.querySelectorAll('.mc-dropdown-item').forEach(i => i.classList.toggle('selected', i.dataset.value === String(value)));
    }

    provinceBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (provinceBtn.disabled) return;
        provincePanel.classList.contains('open') ? closeAllPanels() : openPanel(provincePanel, provinceBtn);
    });
    provinceSearch.addEventListener('input', function () { filterGrid(provinceGrid, this.value); });
    provinceGrid.addEventListener('click', function (e) {
        const item = e.target.closest('.mc-dropdown-item');
        if (!item) return;
        selectItem(provinceHidden, provinceBtn, provinceGrid, item.dataset.value, item.dataset.label);
        closeAllPanels();
        loadWardsByProvince(item.dataset.value, '');
    });

    wardBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (wardBtn.disabled) return;
        wardPanel.classList.contains('open') ? closeAllPanels() : openPanel(wardPanel, wardBtn);
    });
    wardSearch.addEventListener('input', function () { filterGrid(wardGrid, this.value); });
    wardGrid.addEventListener('click', function (e) {
        const item = e.target.closest('.mc-dropdown-item');
        if (!item) return;
        selectItem(wardHidden, wardBtn, wardGrid, item.dataset.value, item.dataset.label);
        closeAllPanels();
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.mc-dropdown')) closeAllPanels();
    });

    function loadWardsByProvince(provinceId, selectedWardId = '') {
        wardGrid.innerHTML = '<div class="mc-dropdown-empty">Đang tải...</div>';
        wardBtn.disabled = true;
        wardBtn.querySelector('.mc-dropdown-label').textContent = '-- Chọn phường/xã --';
        wardHidden.value = '';
        if (!provinceId) return;
        fetch(`{{ route('api.wards') }}?province_id=${provinceId}`)
            .then(r => r.json())
            .then(data => {
                wardGrid.innerHTML = '';
                data.forEach(ward => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'mc-dropdown-item' + (String(selectedWardId) === String(ward.id) ? ' selected' : '');
                    btn.dataset.value = ward.id;
                    btn.dataset.label = ward.name;
                    btn.textContent = ward.name;
                    wardGrid.appendChild(btn);
                    if (String(selectedWardId) === String(ward.id)) {
                        wardHidden.value = ward.id;
                        wardBtn.querySelector('.mc-dropdown-label').textContent = ward.name;
                    }
                });
                wardBtn.disabled = false;
            })
            .catch(() => { wardGrid.innerHTML = '<div class="mc-dropdown-empty">Lỗi tải dữ liệu</div>'; });
    }

    if (provinceHidden.value) {
        loadWardsByProvince(provinceHidden.value, wardHidden.value);
    }

    // ===================== DUPLICATE CHECK =====================
    const nameInput  = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const nameAlert  = document.getElementById('name-duplicate-alert');
    const emailAlert = document.getElementById('email-duplicate-alert');
    const phoneAlert = document.getElementById('phone-duplicate-alert');
    const checkDuplicateUrl = "{{ route('my_customer.check_duplicate') }}";
    let dupTimer = null;

    function buildAlertHtml(data) {
        return `<i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Khách hàng đã tồn tại trong hệ thống!</strong><br>
            Mã KH: <strong>#${data.id}</strong> – Tên: <strong>${data.name}</strong> – SĐT: <strong>${data.phone}</strong>${data.email ? ` – Email: <strong>${data.email}</strong>` : ''}<br>
            Thuộc về sale: <strong>${data.sale}</strong>${data.created_at ? ` – Ngày tạo: ${data.created_at}` : ''}`;
    }
    function clearDuplicateAlerts() {
        [nameAlert, emailAlert, phoneAlert].forEach(el => { el.classList.add('d-none'); el.innerHTML = ''; });
    }
    function checkDuplicate() {
        const name  = nameInput.value.trim();
        const email = emailInput.value.trim();
        const phone = phoneInput.value.trim();
        if (!((name && phone) || email)) { clearDuplicateAlerts(); return; }
        const params = new URLSearchParams();
        if (name)  params.set('name',  name);
        if (email) params.set('email', email);
        if (phone) params.set('phone', phone);
        fetch(`${checkDuplicateUrl}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.duplicate) {
                    const html = buildAlertHtml(data);
                    clearDuplicateAlerts();
                    if (data.match_reason === 'email' || data.match_reason === 'both') {
                        emailAlert.innerHTML = html; emailAlert.classList.remove('d-none');
                    }
                    if (data.match_reason === 'name_phone' || data.match_reason === 'both') {
                        nameAlert.innerHTML = html; phoneAlert.innerHTML = html;
                        nameAlert.classList.remove('d-none'); phoneAlert.classList.remove('d-none');
                    }
                } else { clearDuplicateAlerts(); }
            })
            .catch(() => clearDuplicateAlerts());
    }
    function scheduleCheck() { clearTimeout(dupTimer); dupTimer = setTimeout(checkDuplicate, 500); }
    nameInput.addEventListener('input', scheduleCheck);  nameInput.addEventListener('blur', checkDuplicate);
    emailInput.addEventListener('input', scheduleCheck); emailInput.addEventListener('blur', checkDuplicate);
    phoneInput.addEventListener('input', scheduleCheck); phoneInput.addEventListener('blur', checkDuplicate);

    // ===================== TRUCK STATION AJAX =====================
    const btnLoadTrucks     = document.getElementById('btn-load-trucks');
    const truckLoadStatus   = document.getElementById('truck-load-status');
    const truckSearchArea   = document.getElementById('truck-search-area');
    const truckDetailSection= document.getElementById('truck-detail-section');
    const truckTbody        = document.getElementById('truck-station-tbody');
    const truckSearchName   = document.getElementById('truck-search-name');
    const truckSearchDest   = document.getElementById('truck-search-dest');
    const truckStationIdInput = document.getElementById('truck_station_id');
    const useTruckHidden    = document.getElementById('use_truck_station_hidden');
    const btnClearTruck     = document.getElementById('btn-clear-truck');
    const truckSelectedLabel= document.getElementById('truck-selected-label');

    let allTruckStations = @json($truckStations ?? []);
    let trucksLoaded = false;

    function renderTrucks(stations) {
        if (!stations.length) {
            truckTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Không có nhà xe phù hợp.</td></tr>';
            return;
        }
        const selectedId = truckStationIdInput.value;
        truckTbody.innerHTML = stations.map(s => {
            const dest = s.province ? s.province.name : (s.province_id || '');
            const selected = String(s.id) === String(selectedId) ? 'selected-station' : '';
            return `<tr class="${selected}" data-id="${s.id}" data-name="${s.name}" data-dest="${dest}">
                <td class="text-center"><i class="bi bi-${selected ? 'check-circle-fill text-primary' : 'circle text-muted'}"></i></td>
                <td>${s.name}</td>
                <td>${dest}</td>
                <td class="text-muted" style="font-size:0.8rem;">${s.ward ? s.ward.name : ''}</td>
            </tr>`;
        }).join('');
    }

    function filterTrucks() {
        const q1 = truckSearchName.value.toLowerCase();
        const q2 = truckSearchDest.value.toLowerCase();
        const filtered = allTruckStations.filter(s => {
            const dest = s.province ? s.province.name.toLowerCase() : '';
            return s.name.toLowerCase().includes(q1) && dest.includes(q2);
        });
        renderTrucks(filtered);
    }

    btnLoadTrucks.addEventListener('click', function () {
        if (trucksLoaded) {
            truckSearchArea.style.display = truckSearchArea.style.display === 'none' ? '' : 'none';
            return;
        }
        truckLoadStatus.textContent = 'Đang tải...';
        btnLoadTrucks.disabled = true;
        // Use pre-loaded data (already in allTruckStations), or re-fetch via AJAX
        fetch(`{{ route('pages.my_truck_stations.ajax') }}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                // Support both array response and {data: [...]} response
                allTruckStations = Array.isArray(data) ? data : (data.data || data.stations || allTruckStations);
                trucksLoaded = true;
                btnLoadTrucks.disabled = false;
                truckLoadStatus.textContent = `Đã tải ${allTruckStations.length} nhà xe.`;
                renderTrucks(allTruckStations);
                truckSearchArea.style.display = '';
            })
            .catch(() => {
                // Fallback to pre-loaded data
                trucksLoaded = true;
                btnLoadTrucks.disabled = false;
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
        const id   = row.dataset.id;
        const name = row.dataset.name;
        truckTbody.querySelectorAll('tr').forEach(r => {
            r.classList.remove('selected-station');
            const ico = r.querySelector('i');
            if (ico) ico.className = 'bi bi-circle text-muted';
        });
        row.classList.add('selected-station');
        const ico = row.querySelector('i');
        if (ico) ico.className = 'bi bi-check-circle-fill text-primary';
        truckStationIdInput.value = id;
        useTruckHidden.value = '1';
        truckSelectedLabel.innerHTML = `<i class="bi bi-check-circle-fill text-primary me-1"></i>Đã chọn: <strong>${name}</strong>`;
        truckDetailSection.style.display = '';
    });

    btnClearTruck.addEventListener('click', function () {
        truckStationIdInput.value = '';
        useTruckHidden.value = '0';
        truckSelectedLabel.textContent = 'Chưa chọn nhà xe.';
        truckDetailSection.style.display = 'none';
        truckTbody.querySelectorAll('tr').forEach(r => {
            r.classList.remove('selected-station');
            const ico = r.querySelector('i');
            if (ico) ico.className = 'bi bi-circle text-muted';
        });
    });

    // Pre-select if old value exists
    if (truckStationIdInput.value) {
        useTruckHidden.value = '1';
        truckDetailSection.style.display = '';
        const found = allTruckStations.find(s => String(s.id) === String(truckStationIdInput.value));
        if (found) truckSelectedLabel.innerHTML = `<i class="bi bi-check-circle-fill text-primary me-1"></i>Đã chọn: <strong>${found.name}</strong>`;
    }

    // ===================== DYNAMIC PRODUCT ROWS =====================
    const extraRows   = document.getElementById('extra-product-rows');
    const btnAddProd  = document.getElementById('btn-add-product');
    let productIndex  = 0;

    btnAddProd.addEventListener('click', function () {
        const idx = productIndex++;
        const row = document.createElement('div');
        row.className = 'product-row';
        row.innerHTML = `
            <button type="button" class="remove-product-row" title="Xóa dòng"><i class="bi bi-x-lg"></i></button>
            <div class="fw-600 mb-2 text-secondary" style="font-size:0.85rem;font-weight:600;">
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

    // ===================== AVATAR PREVIEW =====================
    document.getElementById('avatar').addEventListener('change', function (e) {
        const [file] = e.target.files;
        if (file) document.getElementById('avatarPreview').src = URL.createObjectURL(file);
    });

    // ===================== CARD TOGGLE =====================
    window.toggleCard = function (cardId) {
        const card = document.getElementById(cardId);
        if (!card) return;
        card.classList.toggle('collapsed');
        const key = 'mc_card_' + cardId;
        try { localStorage.setItem(key, card.classList.contains('collapsed') ? '1' : '0'); } catch(e) {}
    };

    // Restore collapse state from localStorage
    ['card-1','card-2','card-3','card-4'].forEach(function (id) {
        try {
            const stored = localStorage.getItem('mc_card_' + id);
            const card = document.getElementById(id);
            if (!card) return;
            if (stored === '1') {
                // Don't collapse cards that have errors on page reload
                if (!card.classList.contains('has-error')) {
                    card.classList.add('collapsed');
                }
            } else if (stored === '0') {
                // User previously expanded this card — keep it open
                card.classList.remove('collapsed');
            }
            // stored === null: no preference saved, keep HTML default
        } catch(e) {}
    });

});
</script>
@endsection
