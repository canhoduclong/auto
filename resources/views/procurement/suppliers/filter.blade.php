<form class="card card-body border-0 shadow-sm mb-3" method="GET">
<div class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label">Thời gian</label><select class="form-select" name="period"><option value="today" @selected($period === 'today')>Hôm nay</option><option value="latest" @selected($period === 'latest')>Gần nhất (30 ngày)</option><option value="week" @selected($period === 'week')>Tuần này</option><option value="custom" @selected($period === 'custom')>Từ ngày đến ngày</option><option value="all" @selected($period === 'all')>Tất cả</option></select></div>
<div class="col-md-3"><label class="form-label">Từ ngày</label><input type="date" name="from_date" value="{{ $from }}" class="form-control" onchange="this.form.elements.period.value='custom'"></div>
<div class="col-md-3"><label class="form-label">Đến ngày</label><input type="date" name="to_date" value="{{ $to }}" class="form-control" onchange="this.form.elements.period.value='custom'"></div>
@if(request()->routeIs('procurement.suppliers.index'))
<div class="col-md-4"><label class="form-label">Tên / điện thoại</label><input class="form-control" name="search" value="{{ request('search') }}"></div>
<div class="col-md-3"><label class="form-label">Trạng thái</label><select class="form-select" name="status"><option value="">Tất cả</option><option value="active" @selected(request('status') === 'active')>Đang hợp tác</option><option value="inactive" @selected(request('status') === 'inactive')>Ngừng hợp tác</option></select></div>
@endif
<div class="col-auto"><button class="btn btn-primary">Lọc</button></div></div>
</form>
