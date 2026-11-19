<form method="GET" class="debt-filters collection-filters mb-3">
    @if($includeSearch ?? false)
        @foreach(request()->only(['per_page', 'sort', 'direction']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <div class="debt-filter-field"><label for="supplier-search" class="form-label">Nhà cung cấp</label><input class="form-control" id="supplier-search" name="search" value="{{ request('search') }}"></div>
    @endif
    <div class="debt-filter-field"><label for="supplier-from" class="form-label">Từ ngày</label><input class="form-control" id="supplier-from" type="date" name="from" value="{{ $from }}"></div>
    <div class="debt-filter-field"><label for="supplier-to" class="form-label">Đến ngày</label><input class="form-control" id="supplier-to" type="date" name="to" value="{{ $to }}"></div>
    <div class="debt-filter-actions">
        <button class="btn btn-primary" name="range" value="custom">Lọc</button>
        @php($preservedFilters = ($includeSearch ?? false) ? request()->only(['search', 'per_page', 'sort', 'direction']) : [])
        <a class="btn btn-light" href="{{ request()->url() }}?{{ http_build_query(['range' => 'week'] + $preservedFilters) }}">7 ngày qua</a>
        <a class="btn btn-light" href="{{ request()->url() }}?{{ http_build_query(['range' => 'all'] + $preservedFilters) }}">Tất cả</a>
    </div>
</form>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
