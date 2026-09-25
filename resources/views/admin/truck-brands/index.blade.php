@extends('layouts.admin')

@section('content')
<div class="content-inner">

    <div class="page-header page-header-light">
        <div class="page-header-content d-flex">
            <div class="page-title">
                <h4><i class="ph-truck me-2 text-primary"></i> Quản lý Nhà xe</h4>
                <span class="text-muted">Danh sách thương hiệu / đơn vị nhà xe</span>
            </div>
            <div class="my-auto ms-auto">
                <a href="{{ route('admin.truck-brands.create') }}" class="btn btn-primary">
                    <i class="ph-plus me-1"></i> Thêm nhà xe
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
            <div class="card-header">
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:280px;" placeholder="Tìm theo tên...">
                    <button class="btn btn-sm btn-outline-primary"><i class="ph-magnifying-glass"></i> Tìm</button>
                    @if($q)<a href="{{ route('admin.truck-brands.index') }}" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>@endif
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-muted" style="width:50px">#</th>
                            <th>Tên nhà xe</th>
                            <th>Số điện thoại</th>
                            <th>Email</th>
                            <th class="text-center" style="width:65px">Trạm</th>
                            <th class="text-center" style="width:80px">Tuyến</th>
                            <th class="text-center" style="width:110px">Trạng thái</th>
                            <th style="width:100px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brands as $brand)
                        {{-- Brand main row --}}
                        <tr class="brand-row" data-brand-id="{{ $brand->id }}">
                            <td class="align-middle text-muted">{{ $brand->id }}</td>
                            <td class="align-middle">
                                <div class="fw-semibold">{{ $brand->name }}</div>
                                @if($brand->description)
                                    <div class="text-muted" style="font-size:.8rem;">{{ Str::limit($brand->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="align-middle">{{ $brand->phone ?: '–' }}</td>
                            <td class="align-middle">{{ $brand->email ?: '–' }}</td>
                            <td class="text-center align-middle">
                                <span class="badge bg-light text-dark border">{{ $brand->stations_count }}</span>
                            </td>
                            <td class="text-center align-middle">
                                @if($brand->routes_count > 0)
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary py-0 px-2 js-expand-routes"
                                        data-brand-id="{{ $brand->id }}"
                                        data-url="{{ route('admin.truck-brands.routes', $brand) }}"
                                        title="Xem / quản lý tuyến đi">
                                    <i class="ph-caret-right expand-icon me-1" style="font-size:.75rem;transition:transform .2s;"></i>{{ $brand->routes_count }}
                                </button>
                                @else
                                <span class="badge bg-light text-muted border">0</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($brand->is_active)
                                    <span class="badge bg-success bg-opacity-15 text-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-15 text-secondary">Tạm dừng</span>
                                @endif
                            </td>
                            <td class="text-end align-middle">
                                <a href="{{ route('admin.truck-brands.edit', $brand) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ph-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.truck-brands.destroy', $brand) }}" class="d-inline"
                                      onsubmit="return confirm('Xóa nhà xe {{ addslashes($brand->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        {{-- Routes sub-row (hidden initially) --}}
                        <tr class="routes-sub-row d-none" id="routes-sub-{{ $brand->id }}">
                            <td colspan="8" class="p-0" style="background:#f8f9fa;">
                                <div class="routes-panel px-3 py-2"
                                     data-url="{{ route('admin.truck-brands.routes', $brand) }}"
                                     data-loaded="false">
                                    <div class="text-center text-muted py-3">
                                        <span class="spinner-border spinner-border-sm"></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có nhà xe nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($brands->hasPages())
            <div class="card-footer">{{ $brands->links() }}</div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const CSRF            = document.querySelector('meta[name="csrf-token"]').content;
    const ROUTES_BASE     = '{{ url("admin/truck-routes") }}';
    const STATION_SEARCH  = '{{ route("admin.truck-routes.stations.search") }}';

    // ── Expand / collapse ──────────────────────────────────────────────────────
    document.querySelectorAll('.js-expand-routes').forEach(btn => {
        btn.addEventListener('click', function () {
            const brandId = this.dataset.brandId;
            const subRow  = document.getElementById('routes-sub-' + brandId);
            const panel   = subRow.querySelector('.routes-panel');
            const icon    = this.querySelector('.expand-icon');
            const isOpen  = !subRow.classList.contains('d-none');

            if (isOpen) {
                subRow.classList.add('d-none');
                icon.style.transform = '';
                return;
            }

            subRow.classList.remove('d-none');
            icon.style.transform = 'rotate(90deg)';

            if (panel.dataset.loaded === 'false') {
                panel.dataset.loaded = 'loading';
                loadRoutes(panel);
            }
        });
    });

    // ── Load routes ────────────────────────────────────────────────────────────
    async function loadRoutes(panel) {
        try {
            const data = await apiFetch(panel.dataset.url, 'GET');
            panel.dataset.loaded = 'true';
            panel.innerHTML = renderRoutesList(data.routes);
            bindPanelEvents(panel);
        } catch (e) {
            panel.dataset.loaded = 'false';
            panel.innerHTML = '<div class="text-danger py-2">Lỗi tải dữ liệu.</div>';
        }
    }

    function renderRoutesList(routes) {
        if (!routes.length) {
            return `<div class="text-center text-muted py-3">Nhà xe chưa có tuyến đi nào.
                        <a href="{{ route('admin.truck-routes.create') }}" class="ms-2">Tạo tuyến mới</a>
                    </div>`;
        }
        return `<div class="py-2 d-flex flex-column gap-2">${routes.map(renderRouteCard).join('')}</div>`;
    }

    function renderRouteCard(r) {
        const price = r.current_price > 0
            ? `<span class="badge bg-success bg-opacity-15 text-success ms-1">${Number(r.current_price).toLocaleString('vi-VN')}₫</span>`
            : '';
        const status = r.is_active
            ? `<span class="badge bg-success bg-opacity-15 text-success">Hoạt động</span>`
            : `<span class="badge bg-secondary bg-opacity-15 text-secondary">Tạm dừng</span>`;

        const stopsHtml = r.stops.length
            ? r.stops.map((s, i) => renderStopItem(s, i)).join('')
            : `<div class="text-muted py-1" style="font-size:.82rem;">Chưa có trạm dừng nào.</div>`;

        return `
<div class="border rounded bg-white" data-route-id="${r.id}">
    <div class="d-flex align-items-center px-3 py-2 border-bottom gap-2 flex-wrap" style="background:#fafafa;">
        <span class="fw-semibold" style="font-size:.9rem;">${esc(r.name)}</span>
        ${price} ${status}
        <div class="ms-auto">
            <a href="${r.edit_url}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2">
                <i class="ph-pencil me-1"></i>Sửa tuyến
            </a>
        </div>
    </div>
    <div class="stops-section px-3 py-2">
        <div class="stops-list" data-route-id="${r.id}">${stopsHtml}</div>
        ${renderAddStopForm(r.id)}
    </div>
</div>`;
    }

    function renderStopItem(s, idx) {
        const time  = s.arrival_time
            ? `<span class="badge bg-light text-muted border ms-1" style="font-size:.72rem;"><i class="ph-clock me-1"></i>${esc(s.arrival_time)}</span>`
            : '';
        const dur   = s.travel_duration
            ? `<span class="badge bg-info bg-opacity-15 text-info border ms-1" style="font-size:.72rem;">+${esc(s.travel_duration)}</span>`
            : '';

        return `
<div class="stop-item d-flex align-items-start gap-2 py-2 border-bottom flex-wrap" data-stop-id="${s.id}">
    <span class="stop-num mt-1 badge bg-secondary bg-opacity-15 text-secondary"
          style="min-width:22px;height:22px;padding:0;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;border-radius:50%;">${idx + 1}</span>
    <div class="stop-display d-flex align-items-center gap-1 flex-grow-1 flex-wrap">
        <span class="stop-name fw-semibold" style="font-size:.86rem;">${esc(s.name)}</span>${time}${dur}
    </div>
    <div class="stop-actions d-flex gap-1 ms-auto">
        <button class="btn btn-xs btn-outline-warning py-0 px-1 btn-edit-stop" title="Sửa trạm"><i class="ph-pencil"></i></button>
        <button class="btn btn-xs btn-outline-danger  py-0 px-1 btn-del-stop"  title="Xóa khỏi tuyến"><i class="ph-trash"></i></button>
    </div>
    <div class="stop-edit-form d-none w-100 mt-1">
        <div class="d-flex gap-2 align-items-start flex-wrap p-2 rounded" style="background:#f0f4ff;border:1px solid #ccd;">
            <div class="position-relative" style="min-width:200px;flex:1;">
                <input type="text" class="form-control form-control-sm station-text-input"
                       value="${esc(s.name)}" placeholder="Tìm trạm..." autocomplete="off">
                <input type="hidden" class="station-id-input" value="${s.truck_station_id}">
                <div class="station-dropdown list-group position-absolute w-100"
                     style="z-index:1050;top:100%;display:none;max-height:180px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.15);"></div>
            </div>
            <input type="text" class="form-control form-control-sm arrival-input" style="width:88px;"
                   placeholder="Giờ đến" value="${esc(s.arrival_time || '')}">
            <input type="text" class="form-control form-control-sm duration-input" style="width:96px;"
                   placeholder="Thời gian đi" value="${esc(s.travel_duration || '')}">
            <div class="d-flex gap-1">
                <button class="btn btn-xs btn-success px-2 py-1 btn-save-stop" title="Lưu"><i class="ph-floppy-disk"></i></button>
                <button class="btn btn-xs btn-outline-secondary px-2 py-1 btn-cancel-stop" title="Hủy"><i class="ph-x"></i></button>
            </div>
        </div>
    </div>
</div>`;
    }

    function renderAddStopForm(routeId) {
        return `
<div class="add-stop-area pt-2 mt-1">
    <div class="d-flex gap-2 align-items-start flex-wrap">
        <div class="position-relative" style="min-width:180px;flex:1;">
            <input type="text" class="form-control form-control-sm new-station-input"
                   placeholder="Thêm trạm vào cuối tuyến..." autocomplete="off">
            <input type="hidden" class="new-station-id">
            <div class="new-station-dropdown list-group position-absolute w-100"
                 style="z-index:1050;top:100%;display:none;max-height:160px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.15);"></div>
        </div>
        <input type="text" class="form-control form-control-sm new-arrival" style="width:88px;" placeholder="Giờ đến">
        <input type="text" class="form-control form-control-sm new-duration" style="width:96px;" placeholder="Thời gian đi">
        <button class="btn btn-xs btn-primary px-2 py-1 btn-add-stop" data-route-id="${routeId}">
            <i class="ph-plus me-1"></i>Thêm
        </button>
    </div>
</div>`;
    }

    // ── Bind events via delegation ─────────────────────────────────────────────
    function bindPanelEvents(panel) {
        // Bind station search on add-stop inputs
        panel.querySelectorAll('.add-stop-area').forEach(area => {
            bindStationSearch(
                area.querySelector('.new-station-input'),
                area.querySelector('.new-station-id'),
                area.querySelector('.new-station-dropdown')
            );
        });

        // Delegate all stop interactions
        panel.addEventListener('click', async function (e) {

            // ── Edit stop ──
            const editBtn = e.target.closest('.btn-edit-stop');
            if (editBtn) {
                const item = editBtn.closest('.stop-item');
                item.querySelector('.stop-display').classList.add('d-none');
                item.querySelector('.stop-actions').classList.add('d-none');
                item.querySelector('.stop-edit-form').classList.remove('d-none');
                const textIn = item.querySelector('.station-text-input');
                const hidIn  = item.querySelector('.station-id-input');
                const drop   = item.querySelector('.station-dropdown');
                bindStationSearch(textIn, hidIn, drop);
                textIn.focus();
                return;
            }

            // ── Cancel edit ──
            const cancelBtn = e.target.closest('.btn-cancel-stop');
            if (cancelBtn) {
                closeStopEdit(cancelBtn.closest('.stop-item'));
                return;
            }

            // ── Save stop ──
            const saveBtn = e.target.closest('.btn-save-stop');
            if (saveBtn) {
                const item      = saveBtn.closest('.stop-item');
                const stopId    = item.dataset.stopId;
                const routeId   = item.closest('.stops-list').dataset.routeId;
                const stationId = item.querySelector('.station-id-input').value;
                const arrival   = item.querySelector('.arrival-input').value.trim();
                const duration  = item.querySelector('.duration-input').value.trim();
                if (!stationId) { item.querySelector('.station-text-input').focus(); return; }
                try {
                    const res = await apiFetch(`${ROUTES_BASE}/${routeId}/stops/${stopId}`, 'PUT', {
                        truck_station_id: stationId,
                        arrival_time:     arrival   || null,
                        travel_duration:  duration  || null,
                    });
                    if (res.success) {
                        const s = res.stop;
                        item.querySelector('.stop-name').textContent = s.name;
                        item.querySelector('.station-text-input').value  = s.name;
                        item.querySelector('.station-id-input').value    = s.truck_station_id;
                        item.querySelector('.arrival-input').value       = s.arrival_time   || '';
                        item.querySelector('.duration-input').value      = s.travel_duration || '';
                        rebuildStopBadges(item, s.arrival_time, s.travel_duration);
                        closeStopEdit(item);
                        flashEl(item, 'bg-success');
                    }
                } catch (err) { flashEl(item, 'bg-danger'); }
                return;
            }

            // ── Delete stop ──
            const delBtn = e.target.closest('.btn-del-stop');
            if (delBtn) {
                const item    = delBtn.closest('.stop-item');
                const stopId  = item.dataset.stopId;
                const routeId = item.closest('.stops-list').dataset.routeId;
                const name    = item.querySelector('.stop-name').textContent.trim();
                if (!confirm(`Xóa trạm "${name}" khỏi tuyến này?`)) return;
                try {
                    const res = await apiFetch(`${ROUTES_BASE}/${routeId}/stops/${stopId}`, 'DELETE', {});
                    if (res.success) {
                        item.style.transition = 'opacity .2s';
                        item.style.opacity = '0';
                        setTimeout(() => { item.remove(); renumber(routeId, panel); }, 210);
                    }
                } catch (err) { flashEl(item, 'bg-danger'); }
                return;
            }

            // ── Add stop ──
            const addBtn = e.target.closest('.btn-add-stop');
            if (addBtn) {
                const area      = addBtn.closest('.add-stop-area');
                const routeId   = addBtn.dataset.routeId;
                const stationId = area.querySelector('.new-station-id').value;
                const arrival   = area.querySelector('.new-arrival').value.trim();
                const duration  = area.querySelector('.new-duration').value.trim();
                if (!stationId) { area.querySelector('.new-station-input').focus(); return; }
                try {
                    const res = await apiFetch(`${ROUTES_BASE}/${routeId}/stops`, 'POST', {
                        truck_station_id: stationId,
                        arrival_time:     arrival  || null,
                        travel_duration:  duration || null,
                    });
                    if (res.success) {
                        const stopsList = panel.querySelector(`.stops-list[data-route-id="${routeId}"]`);
                        const idx       = stopsList.querySelectorAll('.stop-item').length;
                        stopsList.insertAdjacentHTML('beforeend', renderStopItem(res.stop, idx));
                        renumber(routeId, panel);
                        // Clear form
                        area.querySelector('.new-station-input').value = '';
                        area.querySelector('.new-station-id').value    = '';
                        area.querySelector('.new-arrival').value       = '';
                        area.querySelector('.new-duration').value      = '';
                        flashEl(stopsList.lastElementChild, 'bg-success');
                    }
                } catch (err) { console.error(err); }
                return;
            }
        });
    }

    // ── Station autocomplete ──────────────────────────────────────────────────
    function bindStationSearch(input, hiddenInput, dropdown) {
        if (!input || input._searchBound) return;
        input._searchBound = true;
        let timer;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < 1) { hideDropdown(dropdown); return; }
            timer = setTimeout(async () => {
                try {
                    const list = await apiFetch(STATION_SEARCH + '?q=' + encodeURIComponent(q), 'GET');
                    renderDropdown(list, input, hiddenInput, dropdown);
                } catch (e) {}
            }, 280);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideDropdown(dropdown);
        });
        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) hideDropdown(dropdown);
        });
    }

    function renderDropdown(list, input, hiddenInput, dropdown) {
        if (!list.length) { hideDropdown(dropdown); return; }
        dropdown.innerHTML = list.slice(0, 15).map(s => {
            const loc = s.province?.name ? ` <small class="text-muted">— ${esc(s.province.name)}</small>` : '';
            return `<button type="button" class="list-group-item list-group-item-action py-1 px-2"
                            data-id="${s.id}" data-name="${esc(s.name)}" style="font-size:.84rem;">
                        <span class="fw-semibold">${esc(s.name)}</span>${loc}
                    </button>`;
        }).join('');
        dropdown.style.display = '';
        dropdown.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                input.value       = this.dataset.name;
                hiddenInput.value = this.dataset.id;
                hideDropdown(dropdown);
            });
        });
    }

    function hideDropdown(dropdown) {
        dropdown.style.display = 'none';
        dropdown.innerHTML     = '';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function closeStopEdit(item) {
        item.querySelector('.stop-display').classList.remove('d-none');
        item.querySelector('.stop-actions').classList.remove('d-none');
        item.querySelector('.stop-edit-form').classList.add('d-none');
    }

    function rebuildStopBadges(item, arrival, duration) {
        const disp = item.querySelector('.stop-display');
        disp.querySelectorAll('.badge').forEach(b => b.remove());
        if (arrival) {
            disp.insertAdjacentHTML('beforeend',
                `<span class="badge bg-light text-muted border ms-1" style="font-size:.72rem;"><i class="ph-clock me-1"></i>${esc(arrival)}</span>`);
        }
        if (duration) {
            disp.insertAdjacentHTML('beforeend',
                `<span class="badge bg-info bg-opacity-15 text-info border ms-1" style="font-size:.72rem;">+${esc(duration)}</span>`);
        }
    }

    function renumber(routeId, panel) {
        const list = panel.querySelector(`.stops-list[data-route-id="${routeId}"]`);
        if (!list) return;
        list.querySelectorAll('.stop-item .stop-num').forEach((el, i) => el.textContent = i + 1);
    }

    function flashEl(el, cls) {
        el.classList.add(cls, 'bg-opacity-10');
        setTimeout(() => el.classList.remove(cls, 'bg-opacity-10'), 900);
    }

    async function apiFetch(url, method, body) {
        const opts = {
            method,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
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

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
@endpush
@endsection
