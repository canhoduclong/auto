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
    .ts-station-card {
        border: 1px solid #e7ecf3;
        border-radius: 16px;
        padding: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
        box-shadow: 0 4px 14px rgba(15,23,42,0.05);
        transition: box-shadow .2s ease;
    }
    .ts-station-card:hover { box-shadow: 0 8px 24px rgba(15,23,42,0.10); }
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
                <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Danh sách nhà xe</h1>
                <p class="mb-0" style="color:rgba(255,255,255,.8);max-width:520px;">
                    Quản lý thông tin nhà xe vận chuyển hàng hoá. Tra cứu nhanh và tạo mới nhà xe chỉ trong một màn hình.
                </p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="ts-kpi">
                            <div class="ts-kpi-label">Tổng nhà xe</div>
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

                    <div class="fw-bold mb-2" style="font-size:.95rem;color:#0f172a;"><i class="bi bi-toggles me-1"></i>Trạng thái</div>
                    <select id="ts-status-filter" class="form-select mb-3">
                        <option value="">Tất cả</option>
                        <option value="1">Đang hoạt động</option>
                        <option value="0">Ngừng hoạt động</option>
                    </select>
                </div>
            </div>

            <div class="ts-panel">
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
                        <h4 class="mb-0">Nhà xe vận chuyển</h4>
                        <div class="text-muted small" id="ts-count-label">Đang tải...</div>
                    </div>
                    <div>
                        <button class="btn btn-primary" id="ts-show-create-btn">
                            <i class="bi bi-plus-circle me-1"></i>Thêm nhà xe mới
                        </button>
                    </div>
                </div>
            </div>

            {{-- Create form --}}
            <div class="ts-inline-form mb-3" id="ts-create-form">
                <div class="fw-semibold mb-2 text-primary"><i class="bi bi-plus-circle me-1"></i>Thêm nhà xe mới</div>
                <div id="ts-create-errors" class="alert alert-danger py-2 small d-none"></div>
                <div class="row g-2">
                    <div class="col-12 col-sm-6">
                        <label class="form-label form-label-sm mb-1">Tên nhà xe <span class="text-danger">*</span></label>
                        <input type="text" id="cf-name" class="form-control form-control-sm" placeholder="Nhập tên nhà xe">
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
                <label class="form-label form-label-sm mb-1">Tên nhà xe <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" data-field="name">
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label form-label-sm mb-1">Số điện thoại</label>
                <input type="text" class="form-control form-control-sm" data-field="phone">
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

    let currentProvince = '';
    let currentWard     = '';
    let currentPage     = 1;
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
            q:           document.getElementById('ts-search').value.trim(),
            province_id: currentWard ? '' : currentProvince,
            ward_id:     currentWard,
            is_active:   document.getElementById('ts-status-filter').value,
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
        const active = (res.data || []).filter(d => d.is_active).length;

        document.getElementById('ts-count-label').textContent =
            'Hiển thị ' + (res.data ? res.data.length : 0) + ' trong tổng số ' + total + ' nhà xe';
        document.getElementById('kpi-total').textContent  = total;
        document.getElementById('kpi-active').textContent = active;
        document.getElementById('kpi-page').textContent   = res.data ? res.data.length : 0;

        if (!res.data || !res.data.length) {
            wrap.innerHTML = '<div class="ts-empty"><i class="bi bi-truck" style="font-size:2.4rem;"></i><h5 class="mt-3 mb-2">Không có nhà xe nào</h5><p>Hãy thêm nhà xe mới để bắt đầu quản lý.</p></div>';
            return;
        }

        wrap.innerHTML = '<div class="row g-3" id="ts-card-grid"></div>';
        const grid = document.getElementById('ts-card-grid');
        res.data.forEach(ts => {
            const col = document.createElement('div');
            col.className = 'col-12';
            col.innerHTML = buildCard(ts);
            const card = col.querySelector('.ts-station-card');
            card._tsData = ts;
            attachEditHandlers(card, ts);
            grid.appendChild(col);
        });

        renderPagination(links.current_page, links.last_page);
    }

    function buildCard(ts) {
        const badge    = ts.is_active
            ? '<span class="ts-badge-on"><i class="bi bi-check-circle-fill"></i> Đang HĐ</span>'
            : '<span class="ts-badge-off"><i class="bi bi-x-circle-fill"></i> Ngừng HĐ</span>';
        const location = [ts.ward, ts.province].filter(Boolean).join(', ');
        const editBtn  = ts.can_edit
            ? `<button class="btn btn-outline-warning btn-sm" data-action="open-edit" title="Chỉnh sửa"><i class="bi bi-pencil"></i></button>`
            : '';

        return `<div class="ts-station-card">
            <div class="row justify-content-between">
                <div class="col-md-7">
                    <div class="d-flex align-items-start gap-2 flex-wrap mb-1">
                        <h6 class="mb-0 fw-bold fs-6 ts-name">${esc(ts.name)}</h6>
                        <span class="ts-badge-wrap">${badge}</span>
                    </div>
                    ${ts.phone ? `<div class="fw-bold"><i class="bi bi-telephone me-1"></i><a href="tel:${esc(ts.phone)}" class="text-decoration-none ts-phone">${esc(ts.phone)}</a></div>` : `<span class="ts-phone d-none"></span>`}
                    <div class="ts-meta">
                        ${location ? `<small class="text-muted d-block mt-1 ts-location"><i class="bi bi-geo-alt me-1"></i>${esc(location)}</small>` : ''}
                        ${ts.address ? `<small class="text-muted d-block ts-address"><i class="bi bi-house me-1"></i>${esc(ts.address)}</small>` : ''}
                        ${ts.note ? `<small class="text-secondary d-block mt-1 ts-note"><i class="bi bi-sticky me-1"></i>${esc(ts.note)}</small>` : ''}
                    </div>
                </div>
                <div class="col-md-5 d-flex flex-column justify-content-between align-items-end mt-2 mt-md-0">
                    <div class="ts-actions d-flex gap-2">
                        ${editBtn}
                    </div>
                </div>
            </div>
        </div>`;
    }

    /* ── edit ─────────────────────────────────────────────── */
    function attachEditHandlers(card, ts) {
        const openBtn = card.querySelector('[data-action="open-edit"]');
        if (!openBtn) return;

        openBtn.addEventListener('click', () => {
            document.querySelectorAll('.ts-edit-wrap.open').forEach(w => {
                w.classList.remove('open');
                const c = w.closest('.ts-station-card');
                if (c) { const b = c.querySelector('[data-action="open-edit"]'); if (b) b.style.display = ''; }
            });

            let wrap = card.querySelector('.ts-edit-wrap');
            if (!wrap) {
                card.appendChild(document.getElementById('ts-edit-tpl').content.cloneNode(true));
                wrap = card.querySelector('.ts-edit-wrap');
                initEditGridDropdowns(wrap);
                setupEditSave(card, wrap, ts.id);
                wrap.querySelector('[data-action="cancel-edit"]').addEventListener('click', () => {
                    wrap.classList.remove('open');
                    openBtn.style.display = '';
                });
            }

            fillEditForm(wrap, card._tsData || ts);
            wrap.classList.add('open');
            openBtn.style.display = 'none';
        });
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
        wrap.querySelector('[data-field="phone"]').value   = ts.phone   || '';
        wrap.querySelector('[data-field="address"]').value = ts.address || '';
        wrap.querySelector('[data-field="note"]').value    = ts.note    || '';
        wrap.querySelector('[data-field="is_active"]').checked = !!ts.is_active;

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

    function setupEditSave(card, wrap, stationId) {
        wrap.querySelector('[data-action="save-edit"]').addEventListener('click', function () {
            const errDiv = wrap.querySelector('[data-edit-errors]');
            errDiv.classList.add('d-none'); errDiv.innerHTML = '';
            const body = {
                name:        wrap.querySelector('[data-field="name"]').value.trim(),
                phone:       wrap.querySelector('[data-field="phone"]').value.trim(),
                province_id: wrap._provGd ? wrap._provGd.getValue() : '',
                ward_id:     wrap._wardGd ? wrap._wardGd.getValue() : '',
                address:     wrap.querySelector('[data-field="address"]').value.trim(),
                note:        wrap.querySelector('[data-field="note"]').value.trim(),
                is_active:   wrap.querySelector('[data-field="is_active"]').checked ? 1 : 0,
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
                card._tsData = d.data;
                updateCardDisplay(card, d.data);
                wrap.classList.remove('open');
                const ob = card.querySelector('[data-action="open-edit"]');
                if (ob) ob.style.display = '';
            })
            .catch(() => { errDiv.innerHTML = '<div>Lỗi kết nối, vui lòng thử lại.</div>'; errDiv.classList.remove('d-none'); })
            .finally(() => { this.disabled = false; });
        });
    }

    function updateCardDisplay(card, ts) {
        const nameEl = card.querySelector('.ts-name');
        if (nameEl) nameEl.textContent = ts.name;

        const badgeWrap = card.querySelector('.ts-badge-wrap');
        if (badgeWrap) badgeWrap.innerHTML = ts.is_active
            ? '<span class="ts-badge-on"><i class="bi bi-check-circle-fill"></i> Đang HĐ</span>'
            : '<span class="ts-badge-off"><i class="bi bi-x-circle-fill"></i> Ngừng HĐ</span>';

        const phoneWrap = card.querySelector('.ts-phone');
        if (phoneWrap) {
            const parent = phoneWrap.closest('div') || phoneWrap.parentElement;
            if (ts.phone) {
                parent.outerHTML = `<div class="fw-bold"><i class="bi bi-telephone me-1"></i><a href="tel:${esc(ts.phone)}" class="text-decoration-none ts-phone">${esc(ts.phone)}</a></div>`;
            }
        }

        const meta = card.querySelector('.ts-meta');
        if (meta) {
            const location = [ts.ward, ts.province].filter(Boolean).join(', ');
            meta.innerHTML =
                (location ? `<small class="text-muted d-block mt-1 ts-location"><i class="bi bi-geo-alt me-1"></i>${esc(location)}</small>` : '') +
                (ts.address ? `<small class="text-muted d-block ts-address"><i class="bi bi-house me-1"></i>${esc(ts.address)}</small>` : '') +
                (ts.note ? `<small class="text-secondary d-block mt-1 ts-note"><i class="bi bi-sticky me-1"></i>${esc(ts.note)}</small>` : '');
        }
    }

    /* ── pagination ────────────────────────────────────────── */
    function renderPagination(current, last) {
        const container = document.getElementById('ts-pagination');
        if (last <= 1) { container.innerHTML = ''; return; }
        let html = '';
        for (let i = 1; i <= last; i++)
            html += `<button class="btn btn-sm ${i===current?'btn-primary':'btn-outline-secondary'}" data-page="${i}">${i}</button>`;
        container.innerHTML = html;
        container.querySelectorAll('button').forEach(btn =>
            btn.addEventListener('click', () => { currentPage = +btn.dataset.page; loadList(); }));
    }

    /* ── text/status filters ──────────────────────────────── */
    document.getElementById('ts-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 1; loadList(); }, 400);
    });

    document.getElementById('ts-status-filter').addEventListener('change', () => { currentPage = 1; loadList(); });


    loadRegionTree();
    loadList();
})();
</script>
@endpush
@endsection
