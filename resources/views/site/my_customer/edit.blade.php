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

    /* ── grid-dropdown widget ────────────────────── */
    .mc-gd-wrap { position: relative; }
    .mc-gd-trigger {
        display: flex; align-items: center; justify-content: space-between;
        border: 1px solid #dbe3ef; border-radius: 10px;
        padding: .65rem .85rem; cursor: pointer; background: #fff;
        font-size: .95rem; color: #374151; user-select: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .mc-gd-trigger:hover { border-color: #2563eb; }
    .mc-gd-trigger.open {
        border-color: #2563eb;
        box-shadow: 0 0 0 .2rem rgba(37,99,235,.15);
    }
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
    .mc-gd-grid {
        display: grid; gap: 4px;
        max-height: 280px; overflow-y: auto;
    }
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
                                >{{ old('address', $defaultAddress->note ?? $customer->address) }}</textarea>
                                <div class="mc-help mt-1">Địa chỉ này dùng làm mặc định khi tạo đơn cho khách nếu chưa nhập địa chỉ giao riêng.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label mc-form-label">Tỉnh / Thành phố</label>
                                    <div class="mc-gd-wrap" id="province-gd-wrap">
                                        <div class="mc-gd-trigger" id="province-gd-trigger" tabindex="0">
                                            <span class="mc-gd-val text-muted">-- Chọn tỉnh/thành phố --</span>
                                            <span class="mc-gd-clear" title="Xoá">×</span>
                                            <i class="bi bi-chevron-down ms-1" style="font-size:.75rem;"></i>
                                        </div>
                                        <div class="mc-gd-panel" id="province-gd-panel">
                                            <input type="text" class="mc-gd-search" placeholder="Tìm tỉnh/thành...">
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
                                <div class="col-md-6">
                                    <label class="form-label mc-form-label">Phường / Xã</label>
                                    <div class="mc-gd-wrap" id="ward-gd-wrap">
                                        <div class="mc-gd-trigger mc-gd-disabled" id="ward-gd-trigger" tabindex="0">
                                            <span class="mc-gd-val text-muted">-- Chọn phường/xã --</span>
                                            <span class="mc-gd-clear" title="Xoá">×</span>
                                            <i class="bi bi-chevron-down ms-1" style="font-size:.75rem;"></i>
                                        </div>
                                        <div class="mc-gd-panel" id="ward-gd-panel">
                                            <input type="text" class="mc-gd-search" placeholder="Tìm phường/xã...">
                                            <div class="mc-gd-grid cols-3" id="ward-gd-grid"></div>
                                        </div>
                                        <input type="hidden" id="ward_id" name="ward_id" value="{{ $selectedWardId }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="company_name" class="form-label mc-form-label">Tên công ty</label>
                                    <input type="text" class="form-control mc-form-control" id="company_name" name="company_name" value="{{ old('company_name', $customer->company_name) }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="tax_code" class="form-label mc-form-label">Mã số thuế</label>
                                    <input type="text" class="form-control mc-form-control" id="tax_code" name="tax_code" value="{{ old('tax_code', $customer->tax_code) }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="company_email" class="form-label mc-form-label">Email công ty</label>
                                    <input type="email" class="form-control mc-form-control" id="company_email" name="company_email" value="{{ old('company_email', $customer->company_email) }}">
                                </div>
                                <div class="col-12 mt-3">
                                    <label for="company_address" class="form-label mc-form-label">Địa chỉ công ty</label>
                                    <input type="text" class="form-control mc-form-control" id="company_address" name="company_address" value="{{ old('company_address', $customer->company_address) }}">
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

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="use_truck_station" class="form-label mc-form-label">Gửi qua nhà xe</label>
                                    <select class="form-select mc-form-control" id="use_truck_station" name="use_truck_station">
                                        <option value="0" {{ old('use_truck_station', (string) ($customer->use_truck_station ? 1 : 0)) === '0' ? 'selected' : '' }}>Không</option>
                                        <option value="1" {{ old('use_truck_station', (string) ($customer->use_truck_station ? 1 : 0)) === '1' ? 'selected' : '' }}>Có</option>
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
                                                <option value="{{ $station->id }}" {{ (string) old('truck_station_id', $customer->truck_station_id) === (string) $station->id ? 'selected' : '' }}>
                                                    {{ $station->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="truck_station_phone" class="form-label mc-form-label">Số điện thoại nhà xe</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_station_phone" name="truck_station_phone" value="{{ old('truck_station_phone', $customer->truck_station_phone) }}">
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label for="truck_station_address" class="form-label mc-form-label">Địa chỉ giao nhà xe</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_station_address" name="truck_station_address" value="{{ old('truck_station_address', $customer->truck_station_address) }}">
                                    </div>
                                    <div class="col-md-3 mt-3">
                                        <label for="truck_receive_time" class="form-label mc-form-label">Giờ nhận</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_receive_time" name="truck_receive_time" value="{{ old('truck_receive_time', $customer->truck_receive_time) }}">
                                    </div>
                                    <div class="col-md-3 mt-3">
                                        <label for="truck_return_time" class="form-label mc-form-label">Giờ trả</label>
                                        <input type="text" class="form-control mc-form-control" id="truck_return_time" name="truck_return_time" value="{{ old('truck_return_time', $customer->truck_return_time) }}">
                                    </div>
                                </div>
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
                            <span class="mc-meta-value">{{ $defaultAddress->note ?? $customer->address ?: '-' }}</span>
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
        const wardsApiUrl   = '{{ route("api.wards") }}';
        const useTruckSelect = document.getElementById('use_truck_station');
        const truckSection   = document.getElementById('truck_section');

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

            // if hidden already has a value on page load, show label
            if (hidden?.value) {
                const pre = grid?.querySelector(`.mc-gd-item[data-value="${hidden.value}"]`);
                if (pre) { valSpan.textContent = pre.dataset.label; valSpan.classList.remove('text-muted'); trigger.classList.add('has-value'); pre.classList.add('selected'); }
            }

            function close() {
                trigger.classList.remove('open');
                panel?.classList.remove('open');
                if (search) search.value = '';
                filterItems('');
            }
            function open() {
                if (disabled) return;
                document.querySelectorAll('.mc-gd-panel.open').forEach(p => {
                    if (p !== panel) { p.classList.remove('open'); p.closest('.mc-gd-wrap')?.querySelector('.mc-gd-trigger')?.classList.remove('open'); }
                });
                trigger.classList.add('open');
                panel?.classList.add('open');
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
            if (grid) {
                grid.addEventListener('click', e => { const item = e.target.closest('.mc-gd-item'); if (item) selectItem(item.dataset.value, item.dataset.label); });
            }
            document.addEventListener('click', e => { if (!wrap.contains(e.target)) close(); });

            return {
                getValue()         { return hidden?.value || ''; },
                setValue(v, l)     { selectItem(v, l); },
                clearValue()       { selectItem('', ''); },
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

        /* ── init province & ward grid dropdowns ──────────── */
        const provGd = makeGridDropdown(document.getElementById('province-gd-wrap'));
        const wardGd = makeGridDropdown(document.getElementById('ward-gd-wrap'));
        wardGd.disable();

        const selectedProvince = '{{ $selectedProvinceId }}';
        const selectedWard     = '{{ $selectedWardId }}';

        document.getElementById('province-gd-wrap').addEventListener('gd:change', e => {
            wardGd.clearValue();
            wardGd.setItems([]);
            if (e.detail.value) {
                wardGd.disable();
                fetch(wardsApiUrl + '?province_id=' + e.detail.value)
                    .then(r => r.json())
                    .then(wards => { wardGd.setItems(wards); wardGd.enable(); })
                    .catch(() => {});
            } else {
                wardGd.disable();
            }
        });

        // load wards for pre-selected province on page load
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

        /* ── truck section toggle ────────────────────────── */
        function toggleTruckSection() {
            truckSection.style.display = useTruckSelect.value === '1' ? '' : 'none';
        }
        useTruckSelect.addEventListener('change', toggleTruckSection);
        toggleTruckSection();
    });
</script>
@endpush

@endsection

