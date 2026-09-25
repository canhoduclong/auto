<style>
    .route-builder { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1rem; }
    .stop-item {
        background: #fff; border: 1px solid #dbeafe; border-radius: 10px;
        padding: 0.75rem 1rem; margin-bottom: 0.6rem; display: flex; gap: 0.75rem;
        align-items: flex-start; position: relative;
    }
    .stop-order {
        width: 28px; height: 28px; border-radius: 50%; background: #2563eb; color: #fff;
        font-size: .75rem; font-weight: 700; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; margin-top: 2px;
    }
    .stop-content { flex: 1; min-width: 0; }
    .stop-station-name { font-weight: 600; color: #1e293b; font-size: .9rem; }
    .stop-meta { color: #64748b; font-size: .78rem; }
    .stop-actions { display: flex; gap: 0.3rem; flex-shrink: 0; }
    .stop-connector { text-align: center; color: #93c3fd; font-size: .7rem; margin: -0.1rem 0; }
    .station-search-panel {
        border: 1px solid #e5e7eb; border-radius: 10px; background: #fff;
        padding: 0.75rem;
    }
    .station-result-item {
        padding: 0.5rem 0.75rem; border-radius: 7px; cursor: pointer;
        border: 1px solid transparent; font-size: .85rem; display: flex; justify-content: space-between; align-items: center;
        transition: background .1s;
    }
    .station-result-item:hover { background: #eff6ff; border-color: #bfdbfe; }
    .station-result-list { max-height: 300px; overflow-y: auto; }
</style>

@if($errors->any())
    <div class="alert alert-danger mb-3">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" id="route-form">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div class="row g-3">
        {{-- Left: route info --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header fw-semibold">Thông tin tuyến</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên tuyến <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $route?->name) }}" required
                               placeholder="VD: HCM → Cà Mau">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nhà xe</label>
                        <select name="truck_brand_id" id="filter-brand" class="form-select">
                            <option value="">-- Chọn nhà xe --</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ old('truck_brand_id', $route?->truck_brand_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Biểu giá hiện tại (₫)</label>
                        <input type="number" name="current_price" class="form-control" min="0"
                               value="{{ old('current_price', $route?->current_price) }}" placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $route?->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quy định vận chuyển</label>
                        <textarea name="regulations" class="form-control" rows="3" placeholder="Quy định hàng hóa, trọng lượng, đóng gói...">{{ old('regulations', $route?->regulations) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea name="note" class="form-control" rows="2">{{ old('note', $route?->note) }}</textarea>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                               {{ old('is_active', $route?->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Đang hoạt động</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: route builder --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span class="fw-semibold">Xây dựng tuyến (Timeline chặng)</span>
                    <span class="badge bg-primary" id="stop-count-badge">{{ count($existingStops) }} chặng</span>
                </div>
                <div class="card-body">
                    {{-- Stop timeline --}}
                    <div id="stop-timeline" class="mb-3">
                        <div id="stop-empty" class="text-center text-muted py-3" style="{{ count($existingStops) > 0 ? 'display:none' : '' }}">
                            <i class="ph-path" style="font-size:2rem;"></i>
                            <div class="mt-1">Chưa có chặng nào. Tìm và thêm trạm bên dưới.</div>
                        </div>
                        {{-- Rendered stops go here --}}
                    </div>

                    {{-- Station search --}}
                    <div class="station-search-panel mt-3">
                        <div class="fw-semibold mb-2" style="font-size:.85rem;"><i class="ph-magnifying-glass me-1"></i>Tìm và thêm trạm vào tuyến</div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <input type="text" id="search-station-q" class="form-control form-control-sm" placeholder="Tên, địa chỉ, SĐT...">
                            </div>
                            <div class="col-md-4">
                                <select id="search-brand-id" class="form-select form-select-sm">
                                    <option value="">-- Nhà xe --</option>
                                    @foreach($brands as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select id="search-province-id" class="form-select form-select-sm">
                                    <option value="">-- Tỉnh/thành --</option>
                                    @foreach($provinces as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="station-result-list" id="station-result-list">
                            <div class="text-center text-muted py-3" style="font-size:.85rem;">Nhập từ khóa để tìm trạm...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary"><i class="ph-floppy-disk me-1"></i> Lưu tuyến</button>
        <a href="{{ route('admin.truck-routes.index') }}" class="btn btn-light border">Hủy</a>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchApiUrl   = '{{ route("admin.truck-routes.stations.search") }}';
    const timeline       = document.getElementById('stop-timeline');
    const emptyMsg       = document.getElementById('stop-empty');
    const resultList     = document.getElementById('station-result-list');
    const countBadge     = document.getElementById('stop-count-badge');
    const searchQ        = document.getElementById('search-station-q');
    const searchBrand    = document.getElementById('search-brand-id');
    const searchProvince = document.getElementById('search-province-id');
    const filterBrand    = document.getElementById('filter-brand');

    // Pre-populate stops from existing data
    @php
        $mappedStops = $existingStops->map(fn($s) => [
            'station_id'        => $s->truck_station_id,
            'station_name'      => $s->station?->name ?? '',
            'brand_name'        => $s->station?->brand?->name ?? '',
            'province_name'     => $s->station?->province?->name ?? '',
            'address'           => $s->station?->address ?? '',
            'phone'             => $s->station?->phone ?? '',
            'has_home_delivery' => $s->station?->has_home_delivery ?? false,
            'home_delivery_fee' => (string)($s->station?->home_delivery_fee ?? 0),
            'arrival_time'      => $s->arrival_time ?? '',
            'travel_duration'   => $s->travel_duration ?? '',
            'note'              => $s->note ?? '',
        ])->values();
    @endphp
    const existingStops = @json($mappedStops);

    let stops = [...existingStops];

    function renderTimeline() {
        // Remove all stop-item and connector elements
        timeline.querySelectorAll('.stop-item, .stop-connector').forEach(el => el.remove());

        if (stops.length === 0) {
            emptyMsg.style.display = '';
            countBadge.textContent = '0 chặng';
            return;
        }
        emptyMsg.style.display = 'none';
        countBadge.textContent = stops.length + ' chặng';

        stops.forEach((stop, idx) => {
            // Connector
            if (idx > 0) {
                const conn = document.createElement('div');
                conn.className = 'stop-connector';
                conn.innerHTML = '<i class="ph-arrow-down"></i>';
                timeline.appendChild(conn);
            }

            const item = document.createElement('div');
            item.className = 'stop-item';
            item.dataset.idx = idx;

            const homeDeliveryBadge = stop.has_home_delivery
                ? `<span class="badge bg-success bg-opacity-15 text-success ms-2" title="Có giao hàng tận nhà"><i class="ph-house"></i> Giao tận nhà${ Number(stop.home_delivery_fee) > 0 ? ' · ' + Number(stop.home_delivery_fee).toLocaleString('vi-VN') + '₫' : ' (miễn phí)' }</span>`
                : '';

            const travelDurationField = idx > 0
                ? `<div>
                    <label style="font-size:.75rem;color:#64748b;"><i class="ph-clock me-1"></i>T/gian từ chặng trước</label>
                    <input type="text" class="form-control form-control-sm" style="width:140px;"
                           name="stops[${idx}][travel_duration]" value="${stop.travel_duration ?? ''}"
                           placeholder="VD: 2 tiếng, 30ph"
                           data-field="travel_duration" data-idx="${idx}">
                   </div>`
                : `<input type="hidden" name="stops[0][travel_duration]" value="">`;

            item.innerHTML = `
                <div class="stop-order">${idx + 1}</div>
                <div class="stop-content">
                    <div class="stop-station-name">${stop.station_name}${homeDeliveryBadge}</div>
                    <div class="stop-meta">
                        ${stop.brand_name ? '<span class="me-2"><i class="ph-truck"></i> ' + stop.brand_name + '</span>' : ''}
                        ${stop.province_name ? '<span class="me-2"><i class="ph-map-pin"></i> ' + stop.province_name + '</span>' : ''}
                        ${stop.address ? '<span><i class="ph-house-simple"></i> ' + stop.address + '</span>' : ''}
                    </div>
                    <div class="d-flex gap-2 mt-2 flex-wrap">
                        ${travelDurationField}
                        <div>
                            <label style="font-size:.75rem;color:#64748b;">Giờ đến/đi</label>
                            <input type="time" class="form-control form-control-sm" style="width:120px;"
                                   name="stops[${idx}][arrival_time]" value="${stop.arrival_time}"
                                   data-field="arrival_time" data-idx="${idx}">
                        </div>
                        <div class="flex-grow-1">
                            <label style="font-size:.75rem;color:#64748b;">Ghi chú chặng</label>
                            <input type="text" class="form-control form-control-sm"
                                   name="stops[${idx}][note]" value="${stop.note ?? ''}" placeholder="..."
                                   data-field="note" data-idx="${idx}">
                        </div>
                    </div>
                    <input type="hidden" name="stops[${idx}][truck_station_id]" value="${stop.station_id}">
                </div>
                <div class="stop-actions">
                    ${idx > 0 ? '<button type="button" class="btn btn-sm btn-light border" onclick="moveStop(' + idx + ',-1)" title="Lên"><i class="ph-arrow-up"></i></button>' : '<span style="width:31px;"></span>'}
                    ${idx < stops.length - 1 ? '<button type="button" class="btn btn-sm btn-light border" onclick="moveStop(' + idx + ',1)" title="Xuống"><i class="ph-arrow-down"></i></button>' : '<span style="width:31px;"></span>'}
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeStop(${idx})" title="Xóa chặng"><i class="ph-x"></i></button>
                </div>`;

            // Live-save arrival_time and note to stops array
            item.querySelectorAll('input[data-field]').forEach(input => {
                input.addEventListener('input', e => {
                    stops[e.target.dataset.idx][e.target.dataset.field] = e.target.value;
                });
            });

            timeline.appendChild(item);
        });
    }

    window.moveStop = function (idx, dir) {
        const newIdx = idx + dir;
        if (newIdx < 0 || newIdx >= stops.length) return;
        [stops[idx], stops[newIdx]] = [stops[newIdx], stops[idx]];
        renderTimeline();
    };

    window.removeStop = function (idx) {
        stops.splice(idx, 1);
        renderTimeline();
    };

    function addStop(station) {
        stops.push({
            station_id:        station.id,
            station_name:      station.name,
            brand_name:        station.brand?.name ?? '',
            province_name:     station.province?.name ?? '',
            address:           station.address ?? '',
            phone:             station.phone ?? '',
            has_home_delivery: station.has_home_delivery ?? false,
            home_delivery_fee: station.home_delivery_fee ?? '0',
            arrival_time:      '',
            travel_duration:   '',
            note:              '',
        });
        renderTimeline();
        // scroll timeline into view
        timeline.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ── Station search ──
    let searchTimeout;
    function doSearch() {
        const q          = searchQ.value.trim();
        const brandId    = searchBrand.value;
        const provinceId = searchProvince.value;

        if (!q && !brandId && !provinceId) {
            resultList.innerHTML = '<div class="text-center text-muted py-3" style="font-size:.85rem;">Nhập từ khóa để tìm trạm...</div>';
            return;
        }

        resultList.innerHTML = '<div class="text-center text-muted py-2">Đang tìm...</div>';

        const params = new URLSearchParams();
        if (q) params.set('q', q);
        if (brandId) params.set('brand_id', brandId);
        if (provinceId) params.set('province_id', provinceId);

        fetch(searchApiUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    resultList.innerHTML = '<div class="text-center text-muted py-2" style="font-size:.85rem;">Không tìm thấy trạm nào.</div>';
                    return;
                }
                resultList.innerHTML = data.map(s => `
                    <div class="station-result-item" data-station='${JSON.stringify(s).replace(/'/g, "&#39;")}'>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:.85rem;">${s.name}
                                ${s.has_home_delivery ? '<span class="badge bg-success bg-opacity-15 text-success ms-1" style="font-size:.7rem;"><i class="ph-house"></i> Giao tận nhà</span>' : ''}
                            </div>
                            <div class="text-muted" style="font-size:.75rem;">
                                ${s.brand ? '<i class="ph-truck me-1"></i>' + s.brand.name + ' · ' : ''}
                                ${s.province ? '<i class="ph-map-pin me-1"></i>' + s.province.name : ''}
                                ${s.address ? ' · ' + s.address : ''}
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0">
                            <i class="ph-plus"></i> Thêm
                        </button>
                    </div>`).join('');

                resultList.querySelectorAll('.station-result-item').forEach(el => {
                    el.querySelector('button').addEventListener('click', () => {
                        const station = JSON.parse(el.dataset.station);
                        addStop(station);
                    });
                });
            })
            .catch(() => {
                resultList.innerHTML = '<div class="text-center text-danger py-2" style="font-size:.85rem;">Lỗi tải dữ liệu.</div>';
            });
    }

    [searchQ, searchBrand, searchProvince].forEach(el => {
        el.addEventListener('input', () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(doSearch, 350); });
    });

    // When brand is chosen in route info, sync to search filter
    filterBrand.addEventListener('change', () => { searchBrand.value = filterBrand.value; doSearch(); });

    // Initial render
    renderTimeline();
});
</script>
@endpush
