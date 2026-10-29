@extends('layouts.site')

@push('styles')
<style>
    .ts-page {
        background:
            radial-gradient(circle at top right, rgba(15,118,110,0.10), transparent 32%),
            linear-gradient(180deg, #f0f4f8 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .ts-hero {
        border: 1px solid rgba(41,52,98,0.08);
        border-radius: 28px;
        background: linear-gradient(135deg, #0a3a2a 0%, #0f5c42 55%, #1a8060 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(10,58,42,0.18);
        position: relative;
        overflow: hidden;
    }
    .ts-hero::after {
        content: '';
        position: absolute;
        width: 220px; height: 220px;
        right: -60px; top: -60px;
        border-radius: 50%;
        background: rgba(255,255,255,0.07);
    }
    .ts-kpi {
        background: rgba(255,255,255,0.09);
        border: 1px solid rgba(255,255,255,0.09);
        border-radius: 18px;
        padding: 16px;
        backdrop-filter: blur(6px);
    }
    .ts-kpi-label {
        font-size: .75rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255,255,255,.65);
        margin-bottom: 6px;
    }
    .ts-kpi-value {
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1;
    }
    .ts-panel {
        border: 1px solid rgba(15,23,42,0.08);
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(15,23,42,0.06);
    }
    .ts-filter { padding: 22px; }
    .ts-section-head { padding: 15px 24px; }
    .ts-province-list {
        display: flex;
        flex-direction: column;
        gap: .35rem;
        max-height: 360px;
        overflow-y: auto;
    }
    .ts-province-btn {
        width: 100%;
        text-align: left;
        border: 1px solid #dbe4ef;
        background: #fff;
        border-radius: 10px;
        padding: .42rem .65rem;
        font-size: .83rem;
        color: #334155;
        cursor: pointer;
        transition: all .14s;
    }
    .ts-province-btn:hover, .ts-province-btn.active {
        border-color: #0f766e;
        background: #ecfdf5;
        color: #0f766e;
        font-weight: 600;
    }
    .ts-table-wrap { overflow-x: auto; }
    .ts-table { min-width: 980px; margin-bottom: 0; }
    .ts-table th { white-space: nowrap; background: #f8fafc; color: #475569; font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; }
    .ts-sort-btn { border: 0; background: transparent; color: inherit; font: inherit; font-weight: 700; padding: 0; }
    .ts-sort-btn:hover { color: #0f766e; }
    .ts-table td { vertical-align: middle; }
    .ts-badge-on  { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:999px; font-size:.75rem; font-weight:700; background:#ecfdf5; color:#047857; }
    .ts-badge-off { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:999px; font-size:.75rem; font-weight:700; background:#fee2e2; color:#991b1b; }
    .ts-actions .btn { border-radius: 10px; padding: 6px 9px; font-size: .8rem; }
    .ts-inline-form {
        border: 1px dashed #94a3b8;
        border-radius: 14px;
        padding: 1rem;
        background: #f8fafc;
        display: none;
    }
    .ts-inline-form.open { display: block; }
    .ts-edit-wrap {
        border-top: 1px dashed #94a3b8;
        margin-top: .75rem;
        padding-top: .8rem;
        display: none;
    }
    .ts-edit-wrap.open { display: block; }
    .ts-empty {
        padding: 48px 24px 56px;
        text-align: center;
        color: #64748b;
    }
    .ts-route-card {
        border: 1px solid #dbe4ef;
        border-radius: 16px;
        padding: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }
    .ts-route-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }
    .ts-route-name {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .ts-route-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }
    .ts-route-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 700;
        background: #eef2ff;
        color: #3730a3;
    }
    .ts-route-line {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        padding: 10px 12px;
    }
    .ts-route-path {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        color: #334155;
        font-weight: 700;
    }
    .ts-route-arrow {
        color: #0f766e;
        font-size: .8rem;
    }
    .ts-route-time {
        color: #475569;
        font-size: .85rem;
    }
    .ts-route-code {
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
    }

    /* ── grid-dropdown ─────────────────────────────── */
    .ts-gd-wrap { position: relative; }
    .ts-gd-trigger {
        display: flex; align-items: center; justify-content: space-between;
        border: 1px solid #dee2e6; border-radius: .375rem;
        padding: .25rem .5rem; cursor: pointer; background: #fff;
        font-size: .875rem; color: #212529; user-select: none;
        transition: border-color .15s;
    }
    .ts-gd-trigger:hover { border-color: #86b7fe; }
    .ts-gd-trigger.open { border-color: #86b7fe; box-shadow: 0 0 0 .2rem rgba(13,110,253,.15); }
    .ts-gd-trigger .ts-gd-val { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ts-gd-trigger .ts-gd-clear {
        display: none; line-height: 1; color: #6c757d;
        padding: 0 2px 0 4px; font-size: 1rem;
    }
    .ts-gd-trigger.has-value .ts-gd-clear { display: inline; }
    .ts-gd-panel {
        position: absolute; z-index: 1060; left: 0; top: calc(100% + 4px);
        min-width: 100%; width: max-content; max-width: 700px;
        background: #fff; border: 1px solid #dee2e6;
        border-radius: 12px; box-shadow: 0 12px 40px rgba(15,23,42,.14);
        padding: 10px; display: none;
    }
    .ts-gd-panel.open { display: block; }
    .ts-gd-search {
        width: 100%; border: 1px solid #dee2e6; border-radius: 8px;
        padding: .3rem .6rem; font-size: .82rem; margin-bottom: 8px;
        outline: none;
    }
    .ts-gd-search:focus { border-color: #86b7fe; }
    .ts-gd-grid {
        display: grid;
        gap: 4px;
        max-height: 280px; overflow-y: auto;
    }
    .ts-gd-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
    .ts-gd-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }
    .ts-gd-item {
        padding: 5px 8px; border-radius: 6px; font-size: .78rem;
        cursor: pointer; transition: background .1s; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis; border: 1px solid transparent;
    }
    .ts-gd-item:hover { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
    .ts-gd-item.selected { background: #d1fae5; border-color: #6ee7b7; color: #065f46; font-weight: 600; }
    .ts-gd-empty { font-size: .82rem; color: #94a3b8; text-align: center; padding: 12px; grid-column: 1/-1; }
    @media (max-width: 576px) {
        .ts-gd-panel { max-width: calc(100vw - 40px); }
        .ts-gd-grid.cols-3 { grid-template-columns: repeat(2, 1fr); }
        .ts-gd-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
    }

    /* ── region tree ───────────────────────────── */
    .ts-region-tree { max-height: 420px; overflow-y: auto; }
    .ts-rt-province {
        border: 1px solid #dbe4ef;
        border-radius: 10px;
        margin-bottom: .35rem;
        overflow: hidden;
    }
    .ts-rt-prov-btn {
        width: 100%; text-align: left; border: none; background: #fff;
        padding: .42rem .65rem; font-size: .83rem; color: #334155;
        cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        transition: background .12s;
    }
    .ts-rt-prov-btn:hover { background: #f1f5f9; }
    .ts-rt-prov-btn.active { background: #ecfdf5; color: #0f766e; font-weight: 700; }
    .ts-rt-prov-btn .ts-rt-arrow { font-size: .65rem; transition: transform .2s; }
    .ts-rt-prov-btn.open .ts-rt-arrow { transform: rotate(90deg); }
    .ts-rt-wards {
        display: none;
        padding: 4px 6px 6px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    .ts-rt-wards.open { display: block; }
    .ts-rt-ward-btn {
        display: block; width: 100%; text-align: left; border: none;
        background: transparent; padding: .28rem .55rem;
        font-size: .78rem; color: #475569; cursor: pointer; border-radius: 6px;
        transition: background .1s;
    }
    .ts-rt-ward-btn:hover { background: #e0f2fe; color: #0369a1; }
    .ts-rt-ward-btn.active { background: #d1fae5; color: #065f46; font-weight: 600; }
    .ts-rt-badge {
        display: inline-block; padding: 1px 6px; border-radius: 99px;
        background: #e2e8f0; color: #64748b; font-size: .68rem; margin-left: 4px;
    }
</style>
@endpush

@section('content')
<section class="ts-page">
<div class="container">

    {{-- Hero --}}
    <div class="ts-hero mb-4">
        <div class="row g-4 align-items-end position-relative">
            <div class="col-lg-5">
                <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.6);">Logistics</div>
                <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Danh sách trạm xe</h1>
                <p class="mb-0" style="color:rgba(255,255,255,.8);max-width:520px;">
                    Quản lý thông tin trạm xe và các tuyến vận chuyển. Tra cứu nhanh, lọc theo tuyến và cập nhật ngay trên một màn hình.
                </p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="ts-kpi">
                            <div class="ts-kpi-label" id="kpi-total-label">Tổng trạm xe</div>
                            <div class="ts-kpi-value" id="kpi-total">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="ts-kpi">
                            <div class="ts-kpi-label">Đang HĐ</div>
                            <div class="ts-kpi-value" id="kpi-active">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="ts-kpi">
                            <div class="ts-kpi-label">Trên trang</div>
                            <div class="ts-kpi-value" id="kpi-page">—</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- LEFT PANEL --}}
        <div class="col-xl-4">

            <div class="ts-panel mb-4">
                <div class="ts-filter">
                    <div class="fw-bold mb-3" style="font-size:.95rem;color:#0f172a;"><i class="bi bi-search me-1"></i>Tìm kiếm</div>
                    <input type="text" id="ts-search" class="form-control mb-3" placeholder="Tên, địa chỉ, số điện thoại...">

                    <div class="fw-bold mb-2" style="font-size:.95rem;color:#0f172a;"><i class="bi bi-signpost-2 me-1"></i>Tuyến xe</div>
                    <input type="text" id="ts-route-filter" class="form-control mb-3" placeholder="Lọc theo tên tuyến...">

                    <div class="fw-bold mb-2" style="font-size:.95rem;color:#0f172a;"><i class="bi bi-geo me-1"></i>Điểm đến</div>
                    <input type="text" id="ts-destination-filter" class="form-control mb-3" placeholder="Lọc theo điểm đến...">

                    <div class="fw-bold mb-2" style="font-size:.95rem;color:#0f172a;"><i class="bi bi-truck me-1"></i>Brand</div>
                    <select id="ts-brand-filter" class="form-select mb-3">
                        <option value="">Tất cả brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>

                    <div class="fw-bold mb-2" style="font-size:.95rem;color:#0f172a;"><i class="bi bi-toggles me-1"></i>Trạng thái</div>
                    <select id="ts-status-filter" class="form-select mb-3">
                        <option value="">Tất cả</option>
                        <option value="1">Đang hoạt động</option>
                        <option value="0">Ngừng hoạt động</option>
                    </select>
                </div>
            </div>

            <div class="ts-panel" id="ts-region-panel">
                <div class="ts-filter pt-3 pb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold" style="font-size:.95rem;color:#0f172a;"><i class="bi bi-geo-alt me-1"></i>Khu vực</span>
                        <button class="btn btn-link btn-sm p-0 text-secondary" id="ts-clear-region">Tất cả</button>
                    </div>
                    <div class="ts-region-tree" id="ts-region-tree">
                        <div class="text-muted small text-center py-3"><i class="bi bi-hourglass-split me-1"></i>Đang tải khu vực...</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT PANEL --}}
        <div class="col-xl-8">

            <div class="ts-panel mb-3">
                <div class="ts-section-head d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0" id="ts-list-title">Trạm xe vận chuyển</h4>
                        <div class="text-muted small" id="ts-count-label">Đang tải...</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <select id="ts-per-page" class="form-select form-select-sm" style="width:auto;">
                            <option value="10">10 / trang</option>
                            <option value="20" selected>20 / trang</option>
                            <option value="50">50 / trang</option>
                            <option value="100">100 / trang</option>
                        </select>
                        <button class="btn btn-primary" id="ts-show-create-btn">
                            <i class="bi bi-plus-circle me-1"></i>Thêm trạm xe
                        </button>
                    </div>
                </div>
            </div>

            {{-- Create form --}}
            <div class="ts-inline-form mb-3" id="ts-create-form">
                <div class="fw-semibold mb-2 text-primary"><i class="bi bi-plus-circle me-1"></i>Thêm trạm xe</div>
                <div id="ts-create-errors" class="alert alert-danger py-2 small d-none"></div>
                <div class="row g-2">
                    <div class="col-12 col-sm-6">
                        <label class="form-label form-label-sm mb-1">Tên trạm xe <span class="text-danger">*</span></label>
                        <input type="text" id="cf-name" class="form-control form-control-sm" placeholder="Nhập tên trạm xe">
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label form-label-sm mb-1">Nhà xe (Brand)</label>
                        <input type="text" id="cf-brand-name" class="form-control form-control-sm" list="cf-brand-options" placeholder="Chọn hoặc nhập tên nhà xe">
                        <datalist id="cf-brand-options">
                            @foreach($brands as $brand)
                                <option value="{{ $brand->name }}" data-headquarters-address="{{ $brand->headquarters_address }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm mb-1">Trụ sở chính của nhà xe</label>
                        <input type="text" id="cf-brand-headquarters-address" class="form-control form-control-sm" placeholder="Nhập địa chỉ trụ sở chính">
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label form-label-sm mb-1">Số điện thoại</label>
                        <input type="text" id="cf-phone" class="form-control form-control-sm" placeholder="0xxx xxx xxx">
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label form-label-sm mb-1">Tỉnh/Thành</label>
                        <div class="ts-gd-wrap" id="cf-province-wrap">
                            <div class="ts-gd-trigger" id="cf-province-trigger" tabindex="0">
                                <span class="ts-gd-val text-muted">-- Chọn tỉnh --</span>
                                <span class="ts-gd-clear" title="Xoá">×</span>
                                <i class="bi bi-chevron-down ms-1" style="font-size:.75rem;"></i>
                            </div>
                            <div class="ts-gd-panel" id="cf-province-panel">
                                <input type="text" class="ts-gd-search" placeholder="Tìm tỉnh/thành...">
                                <div class="ts-gd-grid cols-4" id="cf-province-grid">
                                    @foreach($provinces as $province)
                                        <div class="ts-gd-item" data-value="{{ $province->id }}" data-label="{{ $province->name }}">{{ $province->name }}</div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" id="cf-province" name="province_id">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label form-label-sm mb-1">Phường/Xã</label>
                        <div class="ts-gd-wrap" id="cf-ward-wrap">
                            <div class="ts-gd-trigger ts-gd-disabled" id="cf-ward-trigger" tabindex="0">
                                <span class="ts-gd-val text-muted">-- Chọn phường/xã --</span>
                                <span class="ts-gd-clear" title="Xoá">×</span>
                                <i class="bi bi-chevron-down ms-1" style="font-size:.75rem;"></i>
                            </div>
                            <div class="ts-gd-panel" id="cf-ward-panel">
                                <input type="text" class="ts-gd-search" placeholder="Tìm phường/xã...">
                                <div class="ts-gd-grid cols-3" id="cf-ward-grid"></div>
                            </div>
                            <input type="hidden" id="cf-ward" name="ward_id">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm mb-1">Địa chỉ</label>
                        <input type="text" id="cf-address" class="form-control form-control-sm" placeholder="Số nhà, đường...">
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label form-label-sm mb-1">Phí vào bãi xe (₫)</label>
                        <input type="number" id="cf-parking-fee" class="form-control form-control-sm" placeholder="0" min="0">
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label form-label-sm mb-1">Thông tin phòng / chi nhánh</label>
                        <input type="text" id="cf-branch-info" class="form-control form-control-sm" placeholder="VD: Phòng hàng hóa tầng 1">
                    </div>
                    <div class="col-12">
                        <div class="card border-0 bg-light rounded-3 p-2">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="cf-has-home-delivery">
                                <label class="form-check-label" for="cf-has-home-delivery">
                                    <i class="bi bi-house me-1 text-primary"></i>Có dịch vụ giao hàng tận nhà
                                </label>
                            </div>
                            <div id="cf-home-delivery-fee-wrap" class="d-none">
                                <label class="form-label form-label-sm mb-1">Phí giao hàng tận nhà mặc định (₫)</label>
                                <input type="number" id="cf-home-delivery-fee" class="form-control form-control-sm" placeholder="0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label form-label-sm mb-1">Ghi chú</label>
                        <textarea id="cf-note" class="form-control form-control-sm" rows="2" placeholder="Giờ mở cửa, lưu ý..."></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="cf-is-active" checked>
                            <label class="form-check-label" for="cf-is-active">Đang hoạt động</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-primary btn-sm" id="ts-create-save-btn"><i class="bi bi-check-lg me-1"></i>Lưu</button>
                    <button class="btn btn-outline-secondary btn-sm" id="ts-cancel-create-btn">Huỷ</button>
                </div>
            </div>

            {{-- Station list --}}
            <div id="ts-list-wrap">
                <div class="ts-empty"><i class="bi bi-hourglass-split" style="font-size:2.2rem;"></i><h5 class="mt-3">Đang tải dữ liệu...</h5></div>
            </div>

            <div id="ts-pagination" class="d-flex justify-content-center gap-2 mt-3 flex-wrap"></div>

        </div>
    </div>

</div>
</section>

{{-- Edit template --}}
<template id="ts-edit-tpl">
    <div class="ts-edit-wrap">
        <div class="text-danger small mb-2 d-none" data-edit-errors></div>
        <div class="row g-2">
            <div class="col-12 col-sm-6">
                <label class="form-label form-label-sm mb-1">Tên trạm xe <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" data-field="name">
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label form-label-sm mb-1">Nhà xe</label>
                <select class="form-select form-select-sm" data-field="brand_id">
                    <option value="">-- Chưa gắn nhà xe --</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label form-label-sm mb-1">Số điện thoại</label>
                <input type="text" class="form-control form-control-sm" data-field="phone">
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label form-label-sm mb-1">Phí vào bãi xe (₫)</label>
                <input type="number" min="0" class="form-control form-control-sm" data-field="parking_fee">
            </div>
            <div class="col-12">
                <label class="form-label form-label-sm mb-1">Thông tin phòng / chi nhánh</label>
                <input type="text" class="form-control form-control-sm" data-field="branch_info">
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label form-label-sm mb-1">Tỉnh/Thành</label>
                <div class="ts-gd-wrap">
                    <div class="ts-gd-trigger" data-gd-trigger="province" tabindex="0">
                        <span class="ts-gd-val text-muted">-- Chọn tỉnh --</span>
                        <span class="ts-gd-clear" title="Xoá">×</span>
                        <i class="bi bi-chevron-down ms-1" style="font-size:.75rem;"></i>
                    </div>
                    <div class="ts-gd-panel" data-gd-panel="province">
                        <input type="text" class="ts-gd-search" placeholder="Tìm tỉnh/thành...">
                        <div class="ts-gd-grid cols-4" data-gd-grid="province">
                            @foreach($provinces as $province)
                                <div class="ts-gd-item" data-value="{{ $province->id }}" data-label="{{ $province->name }}">{{ $province->name }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" data-field="province_id">
                </div>
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label form-label-sm mb-1">Phường/Xã</label>
                <div class="ts-gd-wrap">
                    <div class="ts-gd-trigger ts-gd-disabled" data-gd-trigger="ward" tabindex="0">
                        <span class="ts-gd-val text-muted">-- Chọn phường/xã --</span>
                        <span class="ts-gd-clear" title="Xoá">×</span>
                        <i class="bi bi-chevron-down ms-1" style="font-size:.75rem;"></i>
                    </div>
                    <div class="ts-gd-panel" data-gd-panel="ward">
                        <input type="text" class="ts-gd-search" placeholder="Tìm phường/xã...">
                        <div class="ts-gd-grid cols-3" data-gd-grid="ward"></div>
                    </div>
                    <input type="hidden" data-field="ward_id">
                </div>
            </div>
            <div class="col-12">
                <label class="form-label form-label-sm mb-1">Địa chỉ</label>
                <input type="text" class="form-control form-control-sm" data-field="address">
            </div>
            <div class="col-12">
                <label class="form-label form-label-sm mb-1">Ghi chú</label>
                <textarea class="form-control form-control-sm" rows="2" data-field="note"></textarea>
            </div>
            <div class="col-12 col-sm-6">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" data-field="has_home_delivery">
                    <label class="form-check-label">Có giao hàng tận nhà</label>
                </div>
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label form-label-sm mb-1">Phí giao tận nhà mặc định (₫)</label>
                <input type="number" min="0" class="form-control form-control-sm" data-field="home_delivery_fee">
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" data-field="is_active">
                    <label class="form-check-label">Đang hoạt động</label>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-success btn-sm" data-action="save-edit"><i class="bi bi-check-lg me-1"></i>Lưu thay đổi</button>
            <button class="btn btn-outline-secondary btn-sm" data-action="cancel-edit">Huỷ</button>
        </div>
    </div>
</template>

@push('scripts')
<script>
(function () {
    const ajaxUrl    = '{{ route("pages.my_truck_stations.ajax") }}';
    const storeUrl   = '{{ route("pages.my_truck_stations.store") }}';
    const updateBase = '{{ url("/my-truck-stations") }}';
    const wardsUrl   = '{{ route("api.wards") }}';
    const csrf       = '{{ csrf_token() }}';

    /* ══════════════════════════════════════════════════════
       Grid-dropdown widget
       makeGridDropdown(wrap) — binds behaviour to a .ts-gd-wrap element.
       Returns { getValue, setValue, setItems, disable, enable }
    ══════════════════════════════════════════════════════ */
    function makeGridDropdown(wrap) {
        const trigger = wrap.querySelector('.ts-gd-trigger');
        const panel   = wrap.querySelector('.ts-gd-panel');
        const valSpan = trigger.querySelector('.ts-gd-val');
        const clearBtn= trigger.querySelector('.ts-gd-clear');
        const search  = panel ? panel.querySelector('.ts-gd-search') : null;
        const grid    = panel ? panel.querySelector('.ts-gd-grid')   : null;
        const hidden  = wrap.querySelector('input[type="hidden"]');

        let disabled = trigger.classList.contains('ts-gd-disabled');

        function close() {
            trigger.classList.remove('open');
            if (panel) panel.classList.remove('open');
            if (search) search.value = '';
            if (grid) filterItems('');
        }

        function open() {
            if (disabled) return;
            // close all other panels
            document.querySelectorAll('.ts-gd-panel.open').forEach(p => {
                if (p !== panel) {
                    p.classList.remove('open');
                    const t = p.closest('.ts-gd-wrap')?.querySelector('.ts-gd-trigger');
                    if (t) t.classList.remove('open');
                }
            });
            trigger.classList.add('open');
            if (panel) {
                panel.classList.add('open');
                if (search) { setTimeout(() => search.focus(), 60); }
            }
        }

        function filterItems(q) {
            if (!grid) return;
            const lq = q.toLowerCase();
            grid.querySelectorAll('.ts-gd-item').forEach(el => {
                const match = el.dataset.label.toLowerCase().includes(lq);
                el.style.display = match ? '' : 'none';
            });
            let empty = grid.querySelector('.ts-gd-empty');
            const anyVisible = [...grid.querySelectorAll('.ts-gd-item')].some(e => e.style.display !== 'none');
            if (!anyVisible) {
                if (!empty) { empty = document.createElement('div'); empty.className = 'ts-gd-empty'; empty.textContent = 'Không tìm thấy'; grid.appendChild(empty); }
                empty.style.display = '';
            } else if (empty) {
                empty.style.display = 'none';
            }
        }

        function selectItem(value, label) {
            if (grid) {
                grid.querySelectorAll('.ts-gd-item').forEach(el => el.classList.toggle('selected', el.dataset.value === String(value)));
            }
            if (hidden) hidden.value = value || '';
            if (value) {
                valSpan.textContent = label;
                valSpan.classList.remove('text-muted');
                trigger.classList.add('has-value');
            } else {
                valSpan.textContent = valSpan.dataset.placeholder || '-- Chọn --';
                valSpan.classList.add('text-muted');
                trigger.classList.remove('has-value');
            }
            close();
            wrap.dispatchEvent(new CustomEvent('gd:change', { detail: { value, label }, bubbles: true }));
        }

        // store placeholder
        valSpan.dataset.placeholder = valSpan.textContent.trim();

        // events
        trigger.addEventListener('click', e => {
            if (e.target.closest('.ts-gd-clear')) { e.stopPropagation(); selectItem('', ''); return; }
            if (trigger.classList.contains('open')) close(); else open();
        });
        trigger.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); trigger.classList.contains('open') ? close() : open(); }
            if (e.key === 'Escape') close();
        });
        if (search) {
            search.addEventListener('input', () => filterItems(search.value));
            search.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
        }
        if (grid) {
            grid.addEventListener('click', e => {
                const item = e.target.closest('.ts-gd-item');
                if (!item) return;
                selectItem(item.dataset.value, item.dataset.label);
            });
        }
        // close on outside click
        document.addEventListener('click', e => {
            if (!wrap.contains(e.target)) close();
        });

        return {
            getValue()         { return hidden ? hidden.value : ''; },
            getLabel()         { return trigger.classList.contains('has-value') ? valSpan.textContent : ''; },
            setValue(val, lbl) { selectItem(val, lbl); },
            clearValue()       { selectItem('', ''); },
            setItems(items /* [{id,name}] */) {
                if (!grid) return;
                grid.querySelectorAll('.ts-gd-item').forEach(el => el.remove());
                grid.querySelectorAll('.ts-gd-empty').forEach(el => el.remove());
                items.forEach(item => {
                    const el = document.createElement('div');
                    el.className = 'ts-gd-item';
                    el.dataset.value = item.id;
                    el.dataset.label = item.name;
                    el.textContent   = item.name;
                    grid.appendChild(el);
                });
            },
            disable() {
                disabled = true;
                trigger.classList.add('ts-gd-disabled');
                trigger.style.opacity = '.6'; trigger.style.pointerEvents = 'none';
                close();
            },
            enable() {
                disabled = false;
                trigger.classList.remove('ts-gd-disabled');
                trigger.style.opacity = ''; trigger.style.pointerEvents = '';
            },
        };
    }

    /* ══════════════════════════════════════════════════════
       Create form grid dropdowns
    ══════════════════════════════════════════════════════ */
    const cfProvinceGd = makeGridDropdown(document.getElementById('cf-province-wrap'));
    const cfWardGd     = makeGridDropdown(document.getElementById('cf-ward-wrap'));
    cfWardGd.disable();

    document.getElementById('cf-province-wrap').addEventListener('gd:change', e => {
        cfWardGd.clearValue();
        cfWardGd.setItems([]);
        if (e.detail.value) {
            cfWardGd.disable();
            fetchWards(e.detail.value).then(wards => {
                cfWardGd.setItems(wards);
                cfWardGd.enable();
            });
        } else {
            cfWardGd.disable();
        }
    });

    function fetchWards(provinceId) {
        return fetch(wardsUrl + '?province_id=' + provinceId)
            .then(r => r.json())
            .catch(() => []);
    }

    const createFormEl = document.getElementById('ts-create-form');
    const createErrorsEl = document.getElementById('ts-create-errors');
    const createShowBtn = document.getElementById('ts-show-create-btn');
    const createCancelBtn = document.getElementById('ts-cancel-create-btn');
    const createSaveBtn = document.getElementById('ts-create-save-btn');
    const createBrandNameEl = document.getElementById('cf-brand-name');
    const createBrandHeadquartersEl = document.getElementById('cf-brand-headquarters-address');
    const homeDeliveryToggleEl = document.getElementById('cf-has-home-delivery');
    const homeDeliveryFeeWrapEl = document.getElementById('cf-home-delivery-fee-wrap');

    function toggleHomeDeliveryFee() {
        if (!homeDeliveryToggleEl || !homeDeliveryFeeWrapEl) {
            return;
        }

        homeDeliveryFeeWrapEl.classList.toggle('d-none', !homeDeliveryToggleEl.checked);
    }

    function resetCreateForm() {
        document.getElementById('cf-name').value = '';
        createBrandNameEl.value = '';
        createBrandHeadquartersEl.value = '';
        document.getElementById('cf-phone').value = '';
        document.getElementById('cf-address').value = '';
        document.getElementById('cf-parking-fee').value = '';
        document.getElementById('cf-branch-info').value = '';
        document.getElementById('cf-note').value = '';
        document.getElementById('cf-home-delivery-fee').value = '';
        document.getElementById('cf-is-active').checked = true;
        document.getElementById('cf-has-home-delivery').checked = false;
        cfProvinceGd.clearValue();
        cfWardGd.clearValue();
        cfWardGd.setItems([]);
        cfWardGd.disable();
        toggleHomeDeliveryFee();
    }

    function showCreateErrors(messages) {
        if (!createErrorsEl) {
            return;
        }

        createErrorsEl.innerHTML = messages.map(msg => `<div>${esc(msg)}</div>`).join('');
        createErrorsEl.classList.remove('d-none');
    }

    function clearCreateErrors() {
        if (!createErrorsEl) {
            return;
        }

        createErrorsEl.innerHTML = '';
        createErrorsEl.classList.add('d-none');
    }

    createShowBtn?.addEventListener('click', () => {
        clearCreateErrors();
        createFormEl.classList.add('open');
    });

    createCancelBtn?.addEventListener('click', () => {
        createFormEl.classList.remove('open');
        clearCreateErrors();
        resetCreateForm();
    });

    homeDeliveryToggleEl?.addEventListener('change', toggleHomeDeliveryFee);
    toggleHomeDeliveryFee();

    createBrandNameEl?.addEventListener('change', function () {
        const brandOption = [...document.querySelectorAll('#cf-brand-options option')]
            .find(option => option.value.trim().toLocaleLowerCase() === this.value.trim().toLocaleLowerCase());
        createBrandHeadquartersEl.value = brandOption?.dataset.headquartersAddress || '';
    });

    createSaveBtn?.addEventListener('click', function () {
        clearCreateErrors();

        const payload = {
            name: document.getElementById('cf-name').value.trim(),
            brand_name: createBrandNameEl.value.trim(),
            brand_headquarters_address: createBrandHeadquartersEl.value.trim(),
            phone: document.getElementById('cf-phone').value.trim(),
            province_id: cfProvinceGd.getValue(),
            ward_id: cfWardGd.getValue(),
            address: document.getElementById('cf-address').value.trim(),
            parking_fee: document.getElementById('cf-parking-fee').value,
            branch_info: document.getElementById('cf-branch-info').value.trim(),
            has_home_delivery: document.getElementById('cf-has-home-delivery').checked ? 1 : 0,
            home_delivery_fee: document.getElementById('cf-home-delivery-fee').value,
            note: document.getElementById('cf-note').value.trim(),
            is_active: document.getElementById('cf-is-active').checked ? 1 : 0,
        };

        this.disabled = true;
        fetch(storeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                if (!ok) {
                    const messages = Object.values(d.errors || {}).flat();
                    showCreateErrors(messages.length ? messages : [d.message || 'Không thể tạo trạm xe.']);
                    return;
                }

                createFormEl.classList.remove('open');
                resetCreateForm();
                currentPage = 1;
                loadRegionTree();
                loadList();
            })
            .catch(() => {
                showCreateErrors(['Lỗi kết nối, vui lòng thử lại.']);
            })
            .finally(() => {
                this.disabled = false;
            });
    });

    let currentProvince = '';
    let currentWard     = '';
    let currentPage     = 1;
    let currentPerPage  = 20;
    let currentSortBy = 'personal';
    let currentSortDirection = 'asc';
    let searchTimer     = null;

    function esc(s) {
        if (!s) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function qs(params) {
        return Object.entries(params)
            .filter(([,v]) => v !== '' && v !== null && v !== undefined)
            .map(([k,v]) => encodeURIComponent(k)+'='+encodeURIComponent(v))
            .join('&');
    }

    function getFilters() {
        return {
            mode:        'stations',
            q:           document.getElementById('ts-search').value.trim(),
            route:       document.getElementById('ts-route-filter').value.trim(),
            destination: document.getElementById('ts-destination-filter').value.trim(),
            brand_id:    document.getElementById('ts-brand-filter').value,
            province_id: currentWard ? '' : currentProvince,
            ward_id:     currentWard,
            is_active:   document.getElementById('ts-status-filter').value,
            per_page:    currentPerPage,
            sort_by: currentSortBy,
            sort_direction: currentSortDirection,
            page:        currentPage,
        };
    }

    /* ── region tree ──────────────────────────────────────── */
    const regionsUrl = '{{ route("pages.my_truck_stations.regions") }}';

    function loadRegionTree() {
        fetch(regionsUrl)
            .then(r => r.json())
            .then(renderRegionTree)
            .catch(() => {
                document.getElementById('ts-region-tree').innerHTML =
                    '<div class="text-danger small text-center py-2">Lỗi tải khu vực</div>';
            });
    }

    function renderRegionTree(provinces) {
        const tree = document.getElementById('ts-region-tree');
        if (!provinces.length) {
            tree.innerHTML = '<div class="text-muted small text-center py-2">Chưa có dữ liệu khu vực</div>';
            return;
        }
        tree.innerHTML = '';
        provinces.forEach(prov => {
            const div = document.createElement('div');
            div.className = 'ts-rt-province';

            const wardCount = prov.wards.length;
            const wardBadge = wardCount
                ? `<span class="ts-rt-badge">${wardCount}</span>` : '';
            const arrow = wardCount
                ? `<i class="bi bi-chevron-right ts-rt-arrow"></i>` : '';

            div.innerHTML = `
                <button class="ts-rt-prov-btn" data-province="${prov.id}">
                    <span>${esc(prov.name)}${wardBadge}</span>${arrow}
                </button>
                ${wardCount ? `<div class="ts-rt-wards" data-wards-province="${prov.id}">
                    ${prov.wards.map(w =>
                        `<button class="ts-rt-ward-btn" data-ward="${w.id}" data-province="${prov.id}">${esc(w.name)}</button>`
                    ).join('')}
                </div>` : ''}
            `;
            tree.appendChild(div);
        });

        // province click: toggle wards open + filter
        tree.querySelectorAll('.ts-rt-prov-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const pid     = btn.dataset.province;
                const wardsEl = tree.querySelector(`[data-wards-province="${pid}"]`);
                const isOpen  = btn.classList.contains('open');

                // collapse all
                tree.querySelectorAll('.ts-rt-prov-btn').forEach(b => { b.classList.remove('open','active'); });
                tree.querySelectorAll('.ts-rt-wards').forEach(w => w.classList.remove('open'));
                tree.querySelectorAll('.ts-rt-ward-btn').forEach(b => b.classList.remove('active'));

                if (!isOpen) {
                    btn.classList.add('open', 'active');
                    if (wardsEl) wardsEl.classList.add('open');
                    currentProvince = pid;
                    currentWard     = '';
                } else {
                    currentProvince = '';
                    currentWard     = '';
                }
                currentPage = 1; loadList();
            });
        });

        // ward click: filter by ward
        tree.querySelectorAll('.ts-rt-ward-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                const wid = btn.dataset.ward;
                const pid = btn.dataset.province;
                const isActive = btn.classList.contains('active');

                tree.querySelectorAll('.ts-rt-ward-btn').forEach(b => b.classList.remove('active'));
                tree.querySelectorAll('.ts-rt-prov-btn').forEach(b => b.classList.remove('active'));

                if (!isActive) {
                    btn.classList.add('active');
                    // keep parent province open+active highlight
                    const pb = tree.querySelector(`.ts-rt-prov-btn[data-province="${pid}"]`);
                    if (pb) pb.classList.add('active');
                    currentWard     = wid;
                    currentProvince = pid;
                } else {
                    currentWard     = '';
                    currentProvince = pid;
                    // restore province active
                    const pb = tree.querySelector(`.ts-rt-prov-btn[data-province="${pid}"]`);
                    if (pb) pb.classList.add('active');
                }
                currentPage = 1; loadList();
            });
        });
    }

    document.getElementById('ts-clear-region').addEventListener('click', () => {
        currentProvince = '';
        currentWard     = '';
        document.querySelectorAll('.ts-rt-prov-btn').forEach(b => b.classList.remove('open','active'));
        document.querySelectorAll('.ts-rt-wards').forEach(w => w.classList.remove('open'));
        document.querySelectorAll('.ts-rt-ward-btn').forEach(b => b.classList.remove('active'));
        currentPage = 1; loadList();
    });

    function loadList() {
        const wrap = document.getElementById('ts-list-wrap');
        wrap.innerHTML = '<div class="ts-empty"><i class="bi bi-hourglass-split" style="font-size:2rem;"></i><h5 class="mt-3">Đang tải...</h5></div>';
        document.getElementById('ts-pagination').innerHTML = '';
        fetch(ajaxUrl + '?' + qs(getFilters()))
            .then(r => r.json())
            .then(renderList)
            .catch(() => { wrap.innerHTML = '<div class="ts-empty text-danger"><i class="bi bi-exclamation-circle" style="font-size:2rem;"></i><h5 class="mt-3">Lỗi tải dữ liệu.</h5></div>'; });
    }

    function renderList(res) {
        const wrap  = document.getElementById('ts-list-wrap');
        const links = res.links || {};
        const total = links.total || 0;
        const rows = res.data || [];
        const perPage = Number(links.per_page || currentPerPage);
        const first = total ? ((Number(links.current_page || 1) - 1) * perPage) + 1 : 0;
        const last = total ? first + rows.length - 1 : 0;

        document.getElementById('ts-count-label').textContent =
            `Hiển thị ${first}–${last} trong tổng số ${total} trạm xe`;
        document.getElementById('kpi-total').textContent  = total;
        document.getElementById('kpi-active').textContent = res.active_total ?? 0;
        document.getElementById('kpi-page').textContent   = rows.length;

        if (!rows.length) {
            wrap.innerHTML = '<div class="ts-empty"><i class="bi bi-truck" style="font-size:2.4rem;"></i><h5 class="mt-3 mb-2">Không có trạm xe nào</h5><p>Thử đổi điều kiện lọc hoặc thêm trạm xe mới.</p></div>';
            return;
        }

        const columns = [
            ['name', 'Tên trạm'], ['brand', 'Nhà xe / Trụ sở chính'], ['phone', 'Điện thoại'],
            ['province', 'Tỉnh/Thành'], ['ward', 'Phường/Xã'], ['address', 'Địa chỉ trạm'], ['branch_info', 'Phòng / chi nhánh'], ['parking_fee', 'Phí bãi'],
            ['is_active', 'Trạng thái'], ['has_home_delivery', 'Giao tận nhà'],
        ];
        const sortMark = key => currentSortBy === key ? (currentSortDirection === 'asc' ? ' ↑' : ' ↓') : '';
        wrap.innerHTML = `<div class="ts-table-wrap"><table class="table table-hover ts-table">
            <thead><tr><th><button type="button" class="ts-sort-btn" data-sort="id">Mã${sortMark('id')}</button></th>${columns.map(([key, label]) => `<th><button type="button" class="ts-sort-btn" data-sort="${key}">${label}${sortMark(key)}</button></th>`).join('')}<th>Ghim</th><th>Thứ tự riêng</th><th>Thao tác</th></tr></thead>
            <tbody>${rows.map(item => buildStationRow(item)).join('')}</tbody>
        </table></div>`;
        wrap.querySelectorAll('[data-sort]').forEach(button => button.addEventListener('click', () => {
            const key = button.dataset.sort;
            currentSortDirection = currentSortBy === key && currentSortDirection === 'asc' ? 'desc' : 'asc';
            currentSortBy = key;
            currentPage = 1;
            loadList();
        }));
        rows.forEach(item => {
            const row = wrap.querySelector(`[data-station-row="${item.id}"]`);
            if (row) {
                row._tsData = item;
                attachEditHandlers(row, item);
            }
        });

        renderPagination(links.current_page, links.last_page);
    }

    function buildStationRow(ts) {
        const status = ts.is_active
            ? '<span class="ts-badge-on"><i class="bi bi-check-circle-fill"></i> Đang HĐ</span>'
            : '<span class="ts-badge-off"><i class="bi bi-x-circle-fill"></i> Ngừng HĐ</span>';
        const homeDelivery = ts.has_home_delivery
            ? `Có${Number(ts.home_delivery_fee || 0) ? ` · ${Number(ts.home_delivery_fee).toLocaleString('vi-VN')}₫` : ''}`
            : 'Không';
        const editBtn = ts.can_edit
            ? '<button class="btn btn-outline-primary btn-sm" data-action="open-edit" title="Sửa trạm"><i class="bi bi-pencil me-1"></i>Sửa</button>'
            : '<span class="text-muted small">—</span>';
        const pinButton = `<button type="button" class="btn btn-sm ${ts.user_is_pinned ? 'btn-warning' : 'btn-outline-secondary'}" data-action="toggle-pin" data-pinned="${ts.user_is_pinned ? '1' : '0'}" title="${ts.user_is_pinned ? 'Bỏ ghim trạm' : 'Ghim trạm'}"><i class="bi ${ts.user_is_pinned ? 'bi-star-fill' : 'bi-star'}"></i></button>`;
        return `<tr data-station-row="${ts.id}">
            <td class="text-muted">#${ts.id}</td><td class="fw-semibold">${esc(ts.name || '')}</td>
            <td>${esc(ts.brand || '—')}${ts.brand_headquarters_address ? `<small class="d-block text-muted">${esc(ts.brand_headquarters_address)}</small>` : ''}</td>
            <td>${ts.phone ? `<a href="tel:${esc(ts.phone)}">${esc(ts.phone)}</a>` : '—'}</td>
            <td>${esc(ts.province || '—')}</td><td>${esc(ts.ward || '—')}</td><td>${esc(ts.address || '—')}</td><td>${esc(ts.branch_info || '—')}</td>
            <td class="text-end text-nowrap">${Number(ts.parking_fee || 0).toLocaleString('vi-VN')}₫</td>
            <td>${status}</td><td>${homeDelivery}</td><td>${pinButton}</td>
            <td><input type="number" min="0" max="999999" class="form-control form-control-sm ts-personal-order" style="width:90px" value="${Number(ts.user_sort_order || 0)}" data-action="sort-order" aria-label="Thứ tự riêng"></td>
            <td class="text-nowrap">${editBtn}</td>
        </tr><tr class="d-none" data-edit-row="${ts.id}"><td colspan="14" data-edit-container></td></tr>`;
    }

    /* ── edit ─────────────────────────────────────────────── */
    function attachEditHandlers(card, ts) {
        const pinBtn = card.querySelector('[data-action="toggle-pin"]');
        pinBtn?.addEventListener('click', async () => {
            pinBtn.disabled = true;
            try {
                await saveStationPreference(ts.id, { is_pinned: pinBtn.dataset.pinned !== '1' });
                loadList();
            } catch (error) {
                alert(error.message || 'Không lưu được trạng thái ghim.');
                pinBtn.disabled = false;
            }
        });

        const orderInput = card.querySelector('[data-action="sort-order"]');
        orderInput?.addEventListener('change', async () => {
            orderInput.disabled = true;
            try {
                await saveStationPreference(ts.id, { sort_order: Number(orderInput.value || 0) });
                loadList();
            } catch (error) {
                alert(error.message || 'Không lưu được thứ tự trạm.');
                orderInput.disabled = false;
            }
        });

        const openBtn = card.querySelector('[data-action="open-edit"]');
        if (!openBtn) return;
        const editRow = card.nextElementSibling;
        const editContainer = editRow?.querySelector('[data-edit-container]');
        if (!editContainer) return;

        openBtn.addEventListener('click', () => {
            document.querySelectorAll('.ts-edit-wrap.open').forEach(w => {
                w.classList.remove('open');
                const oldRow = w.closest('tr[data-edit-row]');
                if (oldRow) oldRow.classList.add('d-none');
                const c = oldRow?.previousElementSibling;
                if (c) { const b = c.querySelector('[data-action="open-edit"]'); if (b) b.style.display = ''; }
            });

            let wrap = editContainer.querySelector('.ts-edit-wrap');
            if (!wrap) {
                editContainer.appendChild(document.getElementById('ts-edit-tpl').content.cloneNode(true));
                wrap = editContainer.querySelector('.ts-edit-wrap');
                initEditGridDropdowns(wrap);
                setupEditSave(card, editRow, wrap, ts.id);
                wrap.querySelector('[data-action="cancel-edit"]').addEventListener('click', () => {
                    wrap.classList.remove('open');
                    editRow.classList.add('d-none');
                    openBtn.style.display = '';
                });
            }

            fillEditForm(wrap, card._tsData || ts);
            wrap.classList.add('open');
            editRow.classList.remove('d-none');
            openBtn.style.display = 'none';
        });
    }

    async function saveStationPreference(stationId, payload) {
        const response = await fetch(`${updateBase}/${stationId}/sort-settings`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Không thể lưu tùy chọn trạm xe.');
        }
        return data;
    }

    function initEditGridDropdowns(wrap) {
        const provWrap = wrap.querySelector('[data-gd-trigger="province"]')?.closest('.ts-gd-wrap');
        const wardWrap = wrap.querySelector('[data-gd-trigger="ward"]')?.closest('.ts-gd-wrap');
        if (!provWrap || !wardWrap) return;

        const provGd = makeGridDropdown(provWrap);
        const wardGd = makeGridDropdown(wardWrap);
        wardGd.disable();

        provWrap.addEventListener('gd:change', e => {
            wardGd.clearValue();
            wardGd.setItems([]);
            if (e.detail.value) {
                wardGd.disable();
                fetchWards(e.detail.value).then(wards => {
                    wardGd.setItems(wards);
                    wardGd.enable();
                });
            } else {
                wardGd.disable();
            }
        });

        wrap._provGd = provGd;
        wrap._wardGd = wardGd;
    }

    function fillEditForm(wrap, ts) {
        wrap.querySelector('[data-field="name"]').value    = ts.name    || '';
        wrap.querySelector('[data-field="brand_id"]').value = ts.brand_id || '';
        wrap.querySelector('[data-field="phone"]').value   = ts.phone   || '';
        wrap.querySelector('[data-field="parking_fee"]').value = ts.parking_fee ?? 0;
        wrap.querySelector('[data-field="branch_info"]').value = ts.branch_info || '';
        wrap.querySelector('[data-field="address"]').value = ts.address || '';
        wrap.querySelector('[data-field="note"]').value    = ts.note    || '';
        wrap.querySelector('[data-field="is_active"]').checked = !!ts.is_active;
        wrap.querySelector('[data-field="has_home_delivery"]').checked = !!ts.has_home_delivery;
        wrap.querySelector('[data-field="home_delivery_fee"]').value = ts.home_delivery_fee ?? 0;

        const provGd = wrap._provGd;
        const wardGd = wrap._wardGd;
        if (provGd && wardGd) {
            if (ts.province_id && ts.province) {
                provGd.setValue(String(ts.province_id), ts.province);
                wardGd.disable();
                fetchWards(ts.province_id).then(wards => {
                    wardGd.setItems(wards);
                    wardGd.enable();
                    if (ts.ward_id && ts.ward) wardGd.setValue(String(ts.ward_id), ts.ward);
                });
            } else {
                provGd.clearValue();
                wardGd.clearValue();
                wardGd.setItems([]);
                wardGd.disable();
            }
        }
    }

    function setupEditSave(card, editRow, wrap, stationId) {
        wrap.querySelector('[data-action="save-edit"]').addEventListener('click', function () {
            const errDiv = wrap.querySelector('[data-edit-errors]');
            errDiv.classList.add('d-none'); errDiv.innerHTML = '';
            const body = {
                name:        wrap.querySelector('[data-field="name"]').value.trim(),
                brand_id:    wrap.querySelector('[data-field="brand_id"]').value,
                phone:       wrap.querySelector('[data-field="phone"]').value.trim(),
                parking_fee: wrap.querySelector('[data-field="parking_fee"]').value,
                branch_info: wrap.querySelector('[data-field="branch_info"]').value.trim(),
                province_id: wrap._provGd ? wrap._provGd.getValue() : '',
                ward_id:     wrap._wardGd ? wrap._wardGd.getValue() : '',
                address:     wrap.querySelector('[data-field="address"]').value.trim(),
                note:        wrap.querySelector('[data-field="note"]').value.trim(),
                is_active:   wrap.querySelector('[data-field="is_active"]').checked ? 1 : 0,
                has_home_delivery: wrap.querySelector('[data-field="has_home_delivery"]').checked ? 1 : 0,
                home_delivery_fee: wrap.querySelector('[data-field="home_delivery_fee"]').value,
                _method:     'PUT',
            };
            this.disabled = true;
            fetch(updateBase + '/' + stationId, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                if (!ok) {
                    const msgs = Object.values(d.errors || {}).flat();
                    errDiv.innerHTML = msgs.length ? msgs.map(m => `<div>${esc(m)}</div>`).join('') : esc(d.message || 'Lỗi không xác định.');
                    errDiv.classList.remove('d-none');
                    return;
                }
                wrap.classList.remove('open');
                editRow.classList.add('d-none');
                loadList();
            })
            .catch(() => { errDiv.innerHTML = '<div>Lỗi kết nối, vui lòng thử lại.</div>'; errDiv.classList.remove('d-none'); })
            .finally(() => { this.disabled = false; });
        });
    }

    /* ── pagination ────────────────────────────────────────── */
    function renderPagination(current, last) {
        const container = document.getElementById('ts-pagination');
        if (last <= 1) { container.innerHTML = ''; return; }
        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);
        let html = `<button class="btn btn-sm btn-outline-secondary" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}>‹ Trước</button>`;
        if (start > 1) html += `<button class="btn btn-sm btn-outline-secondary" data-page="1">1</button>${start > 2 ? '<span class="px-1">…</span>' : ''}`;
        for (let i = start; i <= end; i++)
            html += `<button class="btn btn-sm ${i===current?'btn-primary':'btn-outline-secondary'}" data-page="${i}">${i}</button>`;
        if (end < last) html += `${end < last - 1 ? '<span class="px-1">…</span>' : ''}<button class="btn btn-sm btn-outline-secondary" data-page="${last}">${last}</button>`;
        html += `<button class="btn btn-sm btn-outline-secondary" data-page="${current + 1}" ${current >= last ? 'disabled' : ''}>Sau ›</button>`;
        container.innerHTML = html;
        container.querySelectorAll('button').forEach(btn =>
            btn.addEventListener('click', () => { currentPage = +btn.dataset.page; loadList(); }));
    }

    /* ── text/status filters ──────────────────────────────── */
    document.getElementById('ts-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 1; loadList(); }, 400);
    });

    document.getElementById('ts-route-filter').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 1; loadList(); }, 400);
    });

    document.getElementById('ts-destination-filter').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 1; loadList(); }, 400);
    });

    document.getElementById('ts-brand-filter').addEventListener('change', () => {
        currentPage = 1;
        loadList();
    });

    document.getElementById('ts-status-filter').addEventListener('change', () => { currentPage = 1; loadList(); });

    document.getElementById('ts-per-page').addEventListener('change', (e) => {
        currentPerPage = parseInt(e.target.value, 10) || 20;
        currentPage = 1;
        loadList();
    });

    loadRegionTree();
    loadList();
})();
</script>
@endpush
@endsection
