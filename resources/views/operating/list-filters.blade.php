@php
    $prefix = $listType === 'task' ? 'task_' : 'proposal_';
    $resetParams = collect(request()->query())->reject(fn($value, $key) => str_starts_with($key, $prefix) || $key === ($listType === 'task' ? 'page' : 'proposal_page'))->all();
@endphp
<form method="GET" action="{{ route('operating.index') }}{{ $listType === 'proposal' ? '#bieu-quyet' : '#cong-viec' }}" class="op-filters">
    <input type="hidden" name="filter" value="{{ $filter }}">
    @foreach($resetParams as $key => $value)
        @if($key !== 'filter' && is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    <div class="row g-2">
        <div class="col-12 col-lg-4"><label for="{{ $prefix }}q">Tìm kiếm</label><input id="{{ $prefix }}q" class="form-control" name="{{ $prefix }}q" value="{{ request($prefix.'q') }}" placeholder="{{ $listType === 'task' ? 'Tên, mã công việc, người giao hoặc người nhận…' : 'Tên đề xuất hoặc người mở biểu quyết…' }}" maxlength="200"></div>
        <div class="col-6 col-lg-2"><label for="{{ $prefix }}status">Trạng thái</label><select id="{{ $prefix }}status" class="form-select" name="{{ $prefix }}status"><option value="">Tất cả trạng thái</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request($prefix.'status')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-lg-2"><label for="{{ $prefix }}extra">{{ $listType === 'task' ? 'Loại công việc' : 'Phiếu của tôi' }}</label><select id="{{ $prefix }}extra" class="form-select" name="{{ $listType === 'task' ? 'task_kind' : 'proposal_vote' }}"><option value="">Tất cả</option>@foreach(($listType === 'task' ? ['execution'=>'Giao thực hiện','coordination'=>'Phối hợp'] : ['pending'=>'Chưa bỏ phiếu','voted'=>'Đã bỏ phiếu']) as $value=>$label)<option value="{{ $value }}" @selected(request($listType === 'task' ? 'task_kind' : 'proposal_vote')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-lg-2"><label for="{{ $prefix }}from">Ngày tạo từ</label><input type="date" id="{{ $prefix }}from" class="form-control" name="{{ $prefix }}from" value="{{ request($prefix.'from') }}"></div>
        <div class="col-6 col-lg-2"><label for="{{ $prefix }}to">Ngày tạo đến</label><input type="date" id="{{ $prefix }}to" class="form-control" name="{{ $prefix }}to" value="{{ request($prefix.'to') }}"></div>
        <div class="col-6 col-lg-3"><label for="{{ $prefix }}sort">Sắp xếp</label><select id="{{ $prefix }}sort" name="{{ $prefix }}sort" class="form-select">@foreach(['newest'=>'Ngày tạo: mới nhất','oldest'=>'Ngày tạo: cũ nhất','due'=>'Hạn: gần nhất'] as $value=>$label)<option value="{{ $value }}" @selected(request($prefix.'sort','newest')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-lg-2"><label for="{{ $prefix }}per_page">Số dòng / trang</label><select id="{{ $prefix }}per_page" name="{{ $prefix }}per_page" class="form-select">@foreach([10,20,50,100] as $size)<option value="{{ $size }}" @selected((int)request($prefix.'per_page',$listType === 'task' ? 20 : 10)===$size)>{{ $size }} dòng</option>@endforeach</select></div>
        <div class="col-12 col-lg-7 d-flex align-items-end gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Tìm kiếm / Lọc</button><a class="btn btn-outline-secondary" href="{{ route('operating.index', $resetParams + ['filter'=>$filter]) }}{{ $listType === 'proposal' ? '#bieu-quyet' : '#cong-viec' }}">Đặt lại</a></div>
    </div>
</form>
