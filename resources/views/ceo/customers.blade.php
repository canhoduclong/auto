@extends('layouts.ceo')
@section('title','Khách hàng lớn / Tiềm năng')
@section('subtitle','Theo dõi doanh số, công nợ và khách hàng cần ưu tiên chăm sóc')
@push('styles')
<style>
.ceo-customers{font-size:14px;color:#243449}.ceo-customers .customer-panel{background:#fff;border:1px solid #dfe6ef;border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(30,50,80,.04)}
.ceo-customers .customer-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.ceo-customers .customer-kpi{padding:18px;border:1px solid #dfe6ef;border-radius:12px;background:#fff}.ceo-customers .customer-kpi small{color:#68788d}.ceo-customers .customer-kpi strong{display:block;font-size:22px;margin-top:8px}
.ceo-customers .customer-filters{padding:18px;background:#f8fafc;border-bottom:1px solid #e5eaf1}.ceo-customers label{font-size:12px;color:#52647a;font-weight:600;display:block;margin-bottom:6px}.ceo-customers .form-control,.ceo-customers .form-select,.ceo-customers .btn{font-size:13px}
.ceo-customers .customer-heading,.ceo-customers .customer-footer{padding:16px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.ceo-customers .customer-heading h2{font-size:17px;font-weight:700;margin:0}.ceo-customers .table{margin:0;font-size:14px}.ceo-customers .table th{padding:13px 16px;background:#f2f5f9;white-space:nowrap;text-transform:none;letter-spacing:0}.ceo-customers .table td{padding:15px 16px;border-bottom:1px solid #edf0f5;vertical-align:middle}.ceo-customers .table tbody tr:hover{background:#f7fbff}.ceo-customers th a{text-decoration:none;color:#52647a;display:block}.ceo-customers th a:hover{color:#1266bf}.ceo-customers .customer-name{font-weight:600;color:#245b9b;text-decoration:none}.ceo-customers .customer-code{font-size:12px;color:#718198;margin-top:4px}.ceo-customers .pinned-row{background:#fffdf4}.ceo-customers .pagination{margin-bottom:0}.ceo-customers .customer-note{color:#718198;font-size:12px}
@media(max-width:900px){.ceo-customers .customer-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.ceo-customers .customer-kpi strong{font-size:19px}}
</style>
@endpush
@section('content')
<div class="ceo-customers">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="customer-kpis">
        @foreach(['count'=>'Khách hàng','sales'=>'Doanh số trong kỳ','orders'=>'Số đơn trong kỳ','debt'=>'Công nợ hiện tại'] as $key=>$label)<div class="customer-kpi"><small>{{ $label }}</small><strong class="{{ $key==='debt'?'text-danger':'' }}">{{ number_format($summary[$key],0,',','.') }}{{ in_array($key,['sales','debt'])?' đ':'' }}</strong></div>@endforeach
    </div>
    <section class="customer-panel">
        <div class="customer-heading"><h2>Danh sách khách hàng</h2><span class="customer-note">{{ $rangeLabel }} · {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</span></div>
        <form method="GET" action="{{ route('ceo.customers') }}" class="customer-filters">
            <input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="direction" value="{{ $direction }}">
            <div class="row g-2">
                <div class="col-lg-4"><label for="customer-search">Tìm khách hàng</label><input id="customer-search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Tên khách hàng, mã hoặc số điện thoại…" maxlength="200"></div>
                <div class="col-6 col-lg-2"><label for="customer-range">Kỳ doanh số</label><select id="customer-range" name="range" class="form-select">@foreach(['day'=>'Hôm nay','week'=>'Tuần này','month'=>'Tháng này','year'=>'Năm nay','custom'=>'Tùy chọn ngày'] as $key=>$label)<option value="{{ $key }}" @selected(request('range','month')===$key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-2"><label for="customer-from">Từ ngày</label><input id="customer-from" type="date" name="from_date" value="{{ $from->format('Y-m-d') }}" class="form-control" onchange="document.getElementById('customer-range').value='custom'"></div>
                <div class="col-6 col-lg-2"><label for="customer-to">Đến ngày</label><input id="customer-to" type="date" name="to_date" value="{{ $to->format('Y-m-d') }}" class="form-control" onchange="document.getElementById('customer-range').value='custom'"></div>
                <div class="col-6 col-lg-2"><label for="customer-per-page">Số dòng / trang</label><select id="customer-per-page" name="per_page" class="form-select">@foreach([10,20,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',20)===$size)>{{ $size }} dòng</option>@endforeach</select></div>
                <div class="col-12 d-flex flex-wrap gap-2 mt-3"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Tìm kiếm / Lọc</button><a href="{{ route('ceo.customers') }}" class="btn btn-outline-secondary">Đặt lại</a><a class="btn {{ $sort==='priority'?'btn-primary':'btn-outline-primary' }}" href="{{ route('ceo.customers',array_merge(request()->except('page'),['sort'=>'priority','direction'=>'desc'])) }}"><i class="bi bi-pin-angle me-1"></i>Ưu tiên đã ghim</a><a class="btn {{ $sort==='debt_total' && $direction==='desc'?'btn-danger':'btn-outline-danger' }}" href="{{ route('ceo.customers',array_merge(request()->except('page'),['sort'=>'debt_total','direction'=>'desc'])) }}">Ưu tiên công nợ</a></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th scope="col">STT</th>
            @foreach(['priority'=>'Ưu tiên','name'=>'Khách hàng','phone'=>'Điện thoại','total_orders'=>'Số đơn','total_amount'=>'Doanh số','debt_total'=>'Công nợ hiện tại'] as $key=>$label)
                @php $nextDirection=$sort===$key && $direction==='asc'?'desc':($sort===$key?'asc':(in_array($key,['name','phone'])?'asc':'desc')); @endphp
                <th scope="col" class="{{ in_array($key,['total_orders','total_amount','debt_total'])?'text-end':'' }}" aria-sort="{{ $sort===$key ? ($direction==='asc'?'ascending':'descending') : 'none' }}"><a href="{{ route('ceo.customers',array_merge(request()->except('page'),['sort'=>$key,'direction'=>$nextDirection])) }}">{{ $label }} <i class="bi bi-{{ $sort===$key ? ($direction==='asc'?'arrow-up':'arrow-down') : 'arrow-down-up' }} ms-1"></i></a></th>
            @endforeach
        </tr></thead><tbody>
            @forelse($customers as $customer)<tr class="{{ $customer->is_pinned?'pinned-row':'' }}"><td class="text-muted">{{ $customers->firstItem()+$loop->index }}</td><td><form method="POST" action="{{ route('ceo.customers.priority',$customer) }}">@csrf<input type="hidden" name="is_pinned" value="{{ $customer->is_pinned?0:1 }}"><button type="submit" class="btn btn-sm {{ $customer->is_pinned?'btn-warning':'btn-outline-secondary' }} text-nowrap" title="{{ $customer->is_pinned?'Bỏ ưu tiên':'Ghim khách hàng lên đầu' }}"><i class="bi bi-pin-angle{{ $customer->is_pinned?'-fill':'' }} me-1"></i>{{ $customer->is_pinned?'Đã ưu tiên':'Ưu tiên' }}</button></form></td><td><a class="customer-name" href="{{ route('ceo.customer-revenue-report',$customer) }}">{{ $customer->name }}</a><div class="customer-code">{{ $customer->customer_code ?: '#'.$customer->id }}</div></td><td class="text-nowrap">@if($customer->phone)<a class="text-decoration-none text-reset" href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>@else — @endif</td><td class="text-end">{{ number_format($customer->total_orders,0,',','.') }}</td><td class="text-end fw-semibold text-nowrap">{{ number_format($customer->total_amount,0,',','.') }} đ</td><td class="text-end fw-semibold text-nowrap {{ $customer->debt_total>0?'text-danger':'text-muted' }}">{{ number_format($customer->debt_total,0,',','.') }} đ</td></tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-5">Không có khách hàng phù hợp với bộ lọc.</td></tr>@endforelse
        </tbody></table></div>
        <div class="customer-footer"><span class="customer-note">Hiển thị {{ $customers->firstItem()??0 }}–{{ $customers->lastItem()??0 }} / {{ number_format($customers->total()) }} khách hàng</span>{{ $customers->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    </section>
    <p class="customer-note mt-3">Khách có đơn trong kỳ, còn công nợ hoặc đã ghim được hiển thị. Công nợ hiện tại lấy từ các đơn còn phải thu, không gồm đơn nháp / hủy. Mặc định khách đã ghim đứng đầu, sau đó theo doanh số giảm dần. Trạng thái ghim dùng chung với danh sách khách hàng của hệ thống.</p>
</div>
@endsection
