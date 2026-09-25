@extends('layouts.admin')

@section('content')
<div class="content-inner">
    <div class="page-header page-header-light">
        <div class="page-header-content d-flex">
            <div class="page-title">
                <h4><i class="ph-map-pin me-2 text-primary"></i> Quản lý Tỉnh / Thành phố</h4>
                <span class="text-muted">Danh sách tỉnh/thành và quản lý phường/xã</span>
            </div>
            <div class="my-auto ms-auto">
                <a href="{{ route('provinces.create') }}" class="btn btn-primary">
                    <i class="ph-plus me-1"></i> Thêm tỉnh/thành
                </a>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Tỉnh / Thành phố</th>
                            <th class="text-center" style="width:130px">Số phường/xã</th>
                            <th style="width:220px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($provinces as $province)
                        <tr>
                            <td class="text-muted">{{ $province->id }}</td>
                            <td class="fw-semibold">{{ $province->name }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-15 text-primary">{{ $province->wards_count }}</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary js-wards-btn"
                                        data-province-id="{{ $province->id }}"
                                        data-province-name="{{ $province->name }}"
                                        data-wards-url="{{ route('provinces.wards.index', $province) }}"
                                        data-store-url="{{ route('provinces.wards.store', $province) }}"
                                        title="Xem & quản lý phường/xã">
                                    <i class="ph-list-bullets me-1"></i> Phường/xã
                                </button>
                                <a href="{{ route('provinces.edit', $province) }}" class="btn btn-sm btn-outline-warning" title="Sửa tên">
                                    <i class="ph-pencil"></i>
                                </a>
                                <form action="{{ route('provinces.destroy', $province) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Xóa {{ addslashes($province->name) }} và toàn bộ phường/xã?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($provinces->hasPages())
            <div class="card-footer">{{ $provinces->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- Ward management offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="wardsOffcanvas" style="width:520px;">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title mb-0" id="wardsOffcanvasTitle">Phường / Xã</h5>
            <div class="text-muted" id="wardsOffcanvasSubtitle" style="font-size:.8rem;"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column" style="overflow:hidden;">
        {{-- Add form --}}
        <div class="p-3 border-bottom bg-light">
            <div class="fw-semibold mb-2" style="font-size:.85rem;"><i class="ph-plus-circle me-1 text-primary"></i>Thêm phường/xã mới</div>
            <div class="input-group mb-2">
                <input type="text" id="newWardName" class="form-control form-control-sm"
                       placeholder="Nhập tên 1 phường/xã rồi nhấn Thêm">
                <button class="btn btn-sm btn-primary" id="btnAddWard" type="button">
                    <i class="ph-plus"></i> Thêm
                </button>
            </div>
            <textarea id="newWardsBulk" class="form-control form-control-sm mb-1" rows="3"
                      placeholder="Hoặc nhập nhiều dòng (mỗi dòng = 1 phường/xã)..."></textarea>
            <button class="btn btn-sm btn-outline-primary w-100" id="btnAddWardsBulk" type="button">
                <i class="ph-rows me-1"></i> Thêm nhiều cùng lúc
            </button>
            <div id="addWardMsg" class="mt-2" style="display:none;font-size:.82rem;"></div>
        </div>

        {{-- Search --}}
        <div class="p-2 border-bottom">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="ph-magnifying-glass"></i></span>
                <input type="text" id="wardSearchInput" class="form-control" placeholder="Tìm phường/xã...">
                <span class="input-group-text text-muted" id="wardCountBadge">–</span>
            </div>
        </div>

        {{-- List --}}
        <div id="wardListContainer" class="flex-grow-1 overflow-auto p-2">
            <div class="text-center text-muted py-5" id="wardListLoading">
                <div class="spinner-border spinner-border-sm me-2"></div> Đang tải...
            </div>
            <div id="wardListBody"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const baseUrl   = '{{ url("provinces") }}';

    let currentProvinceId = null;
    let currentWardsUrl   = null;
    let currentStoreUrl   = null;
    let allWards          = [];

    // Open offcanvas
    document.querySelectorAll('.js-wards-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentProvinceId = this.dataset.provinceId;
            currentWardsUrl   = this.dataset.wardsUrl;
            currentStoreUrl   = this.dataset.storeUrl;

            document.getElementById('wardsOffcanvasTitle').textContent    = 'Phường/Xã — ' + this.dataset.provinceName;
            document.getElementById('wardsOffcanvasSubtitle').textContent  = '';
            document.getElementById('wardListBody').innerHTML              = '';
            document.getElementById('wardListLoading').style.display       = '';
            document.getElementById('wardSearchInput').value               = '';
            document.getElementById('newWardName').value                   = '';
            document.getElementById('newWardsBulk').value                  = '';
            hideMsg();

            bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('wardsOffcanvas')).show();
            loadWards();
        });
    });

    // Load wards
    async function loadWards(q = '') {
        document.getElementById('wardListLoading').style.display = '';
        document.getElementById('wardListBody').innerHTML        = '';
        try {
            const url = currentWardsUrl + (q ? '?q=' + encodeURIComponent(q) : '');
            const res = await apiFetch(url, 'GET');
            allWards  = res.wards;
            renderList(allWards, res.total);
            document.getElementById('wardsOffcanvasSubtitle').textContent = res.total + ' phường/xã';
        } catch (e) {
            document.getElementById('wardListBody').innerHTML = '<div class="text-danger p-3">Lỗi tải dữ liệu.</div>';
        }
        document.getElementById('wardListLoading').style.display = 'none';
    }

    function renderList(wards, total) {
        document.getElementById('wardCountBadge').textContent = total ?? wards.length;
        const body = document.getElementById('wardListBody');
        if (!wards.length) {
            body.innerHTML = '<div class="text-center text-muted py-4">Không có phường/xã nào.</div>';
            return;
        }
        body.innerHTML = wards.map(w => `
            <div class="ward-item d-flex align-items-center gap-2 p-2 rounded mb-1 border bg-white" data-ward-id="${w.id}" style="font-size:.88rem;">
                <span class="text-muted" style="font-size:.73rem;min-width:28px;">#${w.id}</span>
                <span class="ward-display flex-grow-1 fw-semibold">${esc(w.name)}</span>
                <input type="text" class="form-control form-control-sm ward-input flex-grow-1" value="${esc(w.name)}" style="display:none;">
                <div class="ward-view-btns d-flex gap-1">
                    <button class="btn btn-xs btn-outline-warning py-0 px-2 btn-edit" title="Sửa"><i class="ph-pencil"></i></button>
                    <button class="btn btn-xs btn-outline-danger  py-0 px-2 btn-del"  title="Xóa"><i class="ph-trash"></i></button>
                </div>
                <div class="ward-edit-btns d-flex gap-1" style="display:none;">
                    <button class="btn btn-xs btn-success        py-0 px-2 btn-save"   title="Lưu"><i class="ph-floppy-disk"></i></button>
                    <button class="btn btn-xs btn-outline-secondary py-0 px-2 btn-cancel" title="Hủy"><i class="ph-x"></i></button>
                </div>
            </div>`).join('');
        bindActions();
    }

    function bindActions() {
        document.querySelectorAll('.ward-item').forEach(item => {
            const wid    = item.dataset.wardId;
            const disp   = item.querySelector('.ward-display');
            const input  = item.querySelector('.ward-input');
            const vbtns  = item.querySelector('.ward-view-btns');
            const ebtns  = item.querySelector('.ward-edit-btns');

            item.querySelector('.btn-edit').onclick = () => {
                disp.style.display  = 'none';
                input.style.display = '';
                vbtns.style.display = 'none';
                ebtns.style.display = '';
                input.focus();
                input.select();
            };
            item.querySelector('.btn-cancel').onclick = () => closeEdit(disp, input, vbtns, ebtns);

            item.querySelector('.btn-save').onclick = async () => {
                const name = input.value.trim();
                if (!name) { input.focus(); return; }
                try {
                    const res = await apiFetch(baseUrl + '/' + currentProvinceId + '/wards/' + wid, 'PUT', { name, ward_id: wid });
                    if (res.success) {
                        disp.textContent = res.ward.name;
                        input.value      = res.ward.name;
                        const w = allWards.find(x => String(x.id) === wid);
                        if (w) w.name = res.ward.name;
                        closeEdit(disp, input, vbtns, ebtns);
                        flash(item, 'border-success');
                    }
                } catch { flash(item, 'border-danger'); }
            };

            input.onkeydown = e => {
                if (e.key === 'Enter')  item.querySelector('.btn-save').click();
                if (e.key === 'Escape') item.querySelector('.btn-cancel').click();
            };

            item.querySelector('.btn-del').onclick = async () => {
                if (!confirm('Xóa "' + disp.textContent.trim() + '"?')) return;
                try {
                    const res = await apiFetch(baseUrl + '/' + currentProvinceId + '/wards/' + wid, 'DELETE', {});
                    if (res.success) {
                        item.style.transition = 'opacity .2s';
                        item.style.opacity    = '0';
                        setTimeout(() => {
                            item.remove();
                            allWards = allWards.filter(x => String(x.id) !== wid);
                            const cnt = document.querySelectorAll('.ward-item').length;
                            document.getElementById('wardCountBadge').textContent        = cnt;
                            document.getElementById('wardsOffcanvasSubtitle').textContent = cnt + ' phường/xã';
                            if (!cnt) document.getElementById('wardListBody').innerHTML =
                                '<div class="text-center text-muted py-4">Không có phường/xã nào.</div>';
                        }, 220);
                    }
                } catch { flash(item, 'border-danger'); }
            };
        });
    }

    function closeEdit(disp, input, vbtns, ebtns) {
        disp.style.display  = '';
        input.style.display = 'none';
        vbtns.style.display = '';
        ebtns.style.display = 'none';
    }
    function flash(item, cls) {
        item.classList.add(cls);
        setTimeout(() => item.classList.remove(cls), 900);
    }

    // Add single
    document.getElementById('btnAddWard').onclick = async () => {
        const name = document.getElementById('newWardName').value.trim();
        if (!name) { document.getElementById('newWardName').focus(); return; }
        await doAdd(name, '');
    };
    document.getElementById('newWardName').onkeydown = e => {
        if (e.key === 'Enter') document.getElementById('btnAddWard').click();
    };

    // Add bulk
    document.getElementById('btnAddWardsBulk').onclick = async () => {
        const bulk = document.getElementById('newWardsBulk').value.trim();
        if (!bulk) { document.getElementById('newWardsBulk').focus(); return; }
        await doAdd('', bulk);
    };

    async function doAdd(name, bulk) {
        hideMsg();
        try {
            const res = await apiFetch(currentStoreUrl, 'POST', { name, wards: bulk });
            if (res.success) {
                document.getElementById('newWardName').value  = '';
                document.getElementById('newWardsBulk').value = '';
                showMsg(res.message, 'success');
                await loadWards(document.getElementById('wardSearchInput').value);
            } else {
                showMsg(res.error ?? 'Lỗi.', 'danger');
            }
        } catch (err) {
            showMsg(err?.error ?? 'Lỗi kết nối.', 'danger');
        }
    }

    // Search
    let st;
    document.getElementById('wardSearchInput').oninput = function () {
        clearTimeout(st);
        st = setTimeout(() => loadWards(this.value.trim()), 300);
    };

    // API helper
    async function apiFetch(url, method, body) {
        const opts = {
            method,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        };
        if (body && method !== 'GET') {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const res  = await fetch(url, opts);
        const data = await res.json();
        if (!res.ok) throw data;
        return data;
    }

    function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function showMsg(m, t) { const el = document.getElementById('addWardMsg'); el.className = 'mt-2 text-' + t; el.style.display = ''; el.textContent = m; }
    function hideMsg() { document.getElementById('addWardMsg').style.display = 'none'; }
})();
</script>
@endpush
@endsection
