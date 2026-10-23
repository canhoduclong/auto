<div class="monitor-panel monitor-customer-areas" aria-label="Danh sách khu vực khách hàng">
    <div class="monitor-filter-title">Danh sách khu vực</div>
    <a class="monitor-filter-link {{ !request()->filled('area') ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['tab' => 'customers', 'area' => null, 'ward' => null, 'page' => null, 'ajax' => null]) }}"><span>Tất cả khu vực</span><span class="monitor-filter-count">{{ $areaStats->flatten()->sum('customer_count') }}</span></a>
    <div class="monitor-customer-area-list">
        @forelse($areaStats as $area => $wards)
        @php
            $unknown = \App\Services\CustomerAreaService::UNKNOWN;
            $selectedArea = request('area') === $area;
        @endphp
        <details class="monitor-customer-area" @if($selectedArea) open @endif>
            <summary>
                <span class="monitor-area-arrow" aria-hidden="true">▸</span>
                <a class="{{ $selectedArea && !request()->filled('ward') ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['tab' => 'customers', 'area' => $area, 'ward' => null, 'page' => null, 'ajax' => null]) }}">{{ $area === $unknown ? 'Chưa cập nhật khu vực' : $area }}</a>
                <span class="monitor-filter-count">{{ $wards->sum('customer_count') }}</span>
            </summary>
            <div class="monitor-area-wards">
                @foreach($wards as $ward)
                <a class="monitor-filter-link {{ $selectedArea && request('ward') === $ward->ward ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['tab' => 'customers', 'area' => $area, 'ward' => $ward->ward, 'page' => null, 'ajax' => null]) }}" @if($selectedArea && request('ward') === $ward->ward) aria-current="page" @endif>
                    <span>{{ $ward->ward === $unknown ? 'Chưa cập nhật phường' : $ward->ward }}</span><span class="monitor-filter-count">{{ $ward->customer_count }}</span>
                </a>
                @endforeach
            </div>
        </details>
        @empty<div class="p-3 text-muted small">Chưa có khách hàng phù hợp.</div>@endforelse
    </div>
</div>
