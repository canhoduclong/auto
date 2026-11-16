@extends('layouts.site')
@section('title', 'Công nợ khách hàng của tôi')
@section('content')
<div class="debt-page sale-debt-page">
    <header class="debt-page-header">
        <div><h1>Công nợ khách hàng</h1><div class="debt-updated">@if($snapshot)Cập nhật {{ \Carbon\Carbon::parse($snapshot['loaded_at'])->format('d/m/Y H:i') }}@else Chưa có dữ liệu công nợ @endif</div></div>
        <a href="{{ route('pages.my_dashboard') }}" class="btn btn-light">Về bảng điều khiển</a>
    </header>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if(!$snapshot)<div class="alert alert-info">Chưa có dữ liệu công nợ. Vui lòng liên hệ kế toán cập nhật dữ liệu.</div>@endif
    <form method="GET" action="{{ route('pages.my_customer_debt') }}" class="debt-filters sale-debt-filters">
        <div class="debt-filter-field"><label class="form-label" for="sale-debt-from">Thống kê từ ngày</label><input class="form-control" type="date" id="sale-debt-from" name="from" value="{{ request('from') }}"></div>
        <div class="debt-filter-field"><label class="form-label" for="sale-debt-to">Đến ngày</label><input class="form-control" type="date" id="sale-debt-to" name="to" value="{{ request('to') }}"></div>
        <div class="debt-filter-field debt-filter-search"><label class="form-label" for="sale-debt-search">Khách hàng</label><input class="form-control" type="search" id="sale-debt-search" name="search" value="{{ request('search') }}" placeholder="Tên, mã khách hoặc số điện thoại" maxlength="150"></div>
        <div class="debt-filter-field"><label class="form-label" for="sale-debt-scope">Phạm vi khách</label><select class="form-select" id="sale-debt-scope" name="scope"><option value="all" @selected(request('scope','all') === 'all')>Tất cả khách phụ trách</option><option value="orders" @selected(request('scope') === 'orders')>Có mua hàng trong kỳ</option></select></div>
        <div class="debt-filter-field"><label class="form-label" for="sale-debt-balance">Công nợ</label><select class="form-select" id="sale-debt-balance" name="balance">@foreach(['debt'=>'Đang có nợ','all'=>'Tất cả số dư','settled'=>'Đã cân đối','credit'=>'Khách trả trước'] as $value=>$label)<option value="{{ $value }}" @selected(request('balance','debt') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="debt-filter-field sale-debt-page-size"><label class="form-label" for="sale-debt-per-page">Số dòng / trang</label><select class="form-select" id="sale-debt-per-page" name="per_page">@foreach([20,30,50,100] as $size)<option value="{{ $size }}" @selected((int) request('per_page',30) === $size)>{{ $size }}</option>@endforeach</select></div>
        @if(request('range') === 'all')<input type="hidden" name="range" value="all">@endif
        <input type="hidden" name="recovery" value="{{ request('recovery','all') }}">
        @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="direction" value="{{ request('direction','desc') }}">@endif
        <div class="debt-filter-actions"><button class="btn btn-primary">Lọc</button><a class="btn btn-light" href="{{ route('pages.my_customer_debt') }}">Xóa lọc</a></div>
    </form>
    <nav class="sale-debt-shortcuts" aria-label="Chọn nhanh khoảng ngày">
        @foreach(['Hôm nay'=>0,'Hôm qua'=>-1,'2 ngày'=>2,'3 ngày'=>3,'5 ngày'=>5,'7 ngày'=>7,'14 ngày'=>14,'30 ngày'=>30] as $label=>$days)
            @php
                $quickTo = $days === -1 ? now()->subDay()->toDateString() : now()->toDateString();
                $quickFrom = $days === -1 ? $quickTo : now()->subDays(max(0,$days-1))->toDateString();
                $quickActive = request('from') === $quickFrom && request('to') === $quickTo;
            @endphp
            <a class="btn btn-sm {{ $quickActive ? 'btn-primary' : 'btn-light' }}" href="{{ request()->fullUrlWithQuery(['range'=>null,'from'=>$quickFrom,'to'=>$quickTo,'page'=>1,'customer'=>null,'system_customer'=>null,'report_from'=>null,'report_to'=>null,'purchase_page'=>null,'payment_page'=>null]) }}" @if($quickActive) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
        <a class="btn btn-sm {{ !request('from') && !request('to') ? 'btn-primary' : 'btn-light' }}" href="{{ request()->fullUrlWithQuery(['range'=>'all','from'=>null,'to'=>null,'page'=>1,'customer'=>null,'system_customer'=>null,'report_from'=>null,'report_to'=>null,'purchase_page'=>null,'payment_page'=>null]) }}">Toàn bộ</a>
    </nav>
    <section class="sale-debt-summary" aria-label="Tổng hợp công nợ">
        <div class="sale-debt-summary-caption"><strong>Tổng hợp công nợ</strong><span>{{ $summary['customers'] }} khách · Tất cả các trang</span></div>
        <article class="sale-debt-summary-panel">
            <header><h2>Toàn bộ lịch sử</h2><span>Số liệu lũy kế</span></header>
            <div class="sale-debt-metrics">
                <div><span>Doanh thu</span><strong>{{ number_format($summary['sales'],0,',','.') }} <small>đ</small></strong></div>
                <div><span>Thanh toán</span><strong class="text-success">{{ number_format($summary['payments'],0,',','.') }} <small>đ</small></strong></div>
                <div><span>Công nợ hiện tại</span><strong class="text-danger">{{ number_format($summary['balance'],0,',','.') }} <small>đ</small></strong></div>
            </div>
        </article>
        <article class="sale-debt-summary-panel sale-debt-summary-period">
            <header><h2>Phát sinh trong kỳ</h2><span>{{ \Carbon\Carbon::parse($periodFrom)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($periodTo)->format('d/m/Y') }}</span></header>
            <div class="sale-debt-metrics">
                <div><span>Doanh thu</span><strong>{{ number_format($summary['period_sales'],0,',','.') }} <small>đ</small></strong></div>
                <div><span>Thanh toán</span><strong class="text-success">{{ number_format($summary['period_payments'],0,',','.') }} <small>đ</small></strong></div>
                <div><span>Công nợ phát sinh</span><strong class="{{ $summary['period_increase'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($summary['period_increase'],0,',','.') }} <small>đ</small></strong></div>
            </div>
        </article>
    </section>
    <details class="sale-debt-explanation"><summary>Cách đọc và đối chiếu số liệu</summary><div>Các số tổng và công nợ hiện tại tính toàn bộ lịch sử. Số phát sinh trong kỳ tính theo khoảng ngày đang lọc, gồm tất cả các trang.<br>Công nợ phát sinh = Doanh thu − Tặng − Thanh toán + Điều chỉnh; số âm là giảm nợ. Tặng trong kỳ: {{ number_format($summary['period_gifts'],0,',','.') }} đ.@if($summary['period_adjustments'] != 0) Điều chỉnh: {{ number_format($summary['period_adjustments'],0,',','.') }} đ.@endif</div></details>
    <div class="sale-debt-table-toolbar">
    <nav class="sale-debt-shortcuts" aria-label="Khả năng thu hồi">@foreach(['all'=>'Tất cả','normal'=>'Đang theo dõi','difficult'=>'Khó thu hồi','unrecoverable'=>'Không có khả năng thu hồi'] as $value=>$label)<a class="btn btn-sm {{ request('recovery','all') === $value ? 'btn-primary' : 'btn-light' }}" href="{{ request()->fullUrlWithQuery(['recovery'=>$value,'page'=>1,'customer'=>null,'system_customer'=>null]) }}">{{ $label }}</a>@endforeach</nav>
    <div class="sale-debt-pagination"><span class="small text-muted">Hiển thị {{ $debts->firstItem() ?? 0 }}–{{ $debts->lastItem() ?? 0 }} / {{ $debts->total() }} khách</span>{{ $debts->onEachSide(1)->links() }}</div>
    </div>
    <div class="sale-debt-priority-hint"><i class="bi bi-star-fill" aria-hidden="true"></i> Ưu tiên 5 sao → 1 sao → chưa ghim · Cùng mức sao, sắp xếp theo cột đã chọn</div>
    <div class="card sale-debt-table-card"><div class="table-responsive sale-debt-scroll"><table class="table table-hover align-middle sale-debt-table mb-0">
        <colgroup><col style="width:3%"><col style="width:20%">@foreach(range(1,6) as $unused)<col style="width:8%">@endforeach<col style="width:9%"><col style="width:10%"><col style="width:10%"></colgroup>
        <thead>
            <tr class="sale-debt-group-head"><th rowspan="2" scope="col">STT</th><th rowspan="2" scope="col">Khách hàng</th><th colspan="3" scope="colgroup" class="text-center">Tổng toàn bộ</th><th colspan="3" scope="colgroup" class="text-center sale-debt-period-group">Trong kỳ <span>{{ \Carbon\Carbon::parse($periodFrom)->format('d/m') }} – {{ \Carbon\Carbon::parse($periodTo)->format('d/m') }}</span></th>
                @foreach(['days_without_payment'=>'Chưa thanh toán','last_purchase_date'=>'Đơn mới nhất'] as $column=>$label)
                <th rowspan="2" scope="col"><a href="{{ request()->fullUrlWithQuery(['sort'=>$column,'direction'=>request('sort','balance') === $column && request('direction','desc') === 'desc' ? 'asc' : 'desc','page'=>1]) }}">{{ $label }}@if(request('sort','balance') === $column) {{ request('direction','desc') === 'desc' ? '↓' : '↑' }}@endif</a></th>
                @endforeach<th rowspan="2" scope="col">Chi tiết</th>
            </tr>
            <tr class="sale-debt-column-head">
                @foreach(['sales'=>'Doanh thu','payments'=>'Thanh toán','balance'=>'Công nợ','period_sales'=>'Doanh thu','period_payments'=>'Thanh toán','period_increase'=>'Công nợ'] as $column=>$label)
                <th scope="col" class="text-end {{ str_starts_with($column,'period_') ? 'sale-debt-period-column' : '' }}"><a href="{{ request()->fullUrlWithQuery(['sort'=>$column,'direction'=>request('sort','balance') === $column && request('direction','desc') === 'desc' ? 'asc' : 'desc','page'=>1]) }}">{{ $label }}@if(request('sort','balance') === $column) {{ request('direction','desc') === 'desc' ? '↓' : '↑' }}@endif</a></th>
                @endforeach
            </tr>
        </thead>
        <tbody>@forelse($debts as $debt)
            <tr class="{{ request('customer') === $debt['key'] ? 'table-primary' : '' }}">
                <td>{{ $debts->firstItem()+$loop->index }}</td>
                <td><strong title="{{ $debt['name'] }}{{ $debt['phone'] ? ' · '.$debt['phone'] : '' }}">{{ $debt['name'] }}</strong><form method="POST" action="{{ route('pages.my_customer_debt.priority') }}" class="sale-debt-stars" data-sale-debt-priority aria-label="Ghim {{ $debt['name'] }}">
                    @csrf<input type="hidden" name="customer_id" value="{{ $debt['customer_id'] }}"><input type="hidden" name="source_key" value="{{ $debt['key'] }}"><input type="hidden" name="priority_version" value="{{ $debt['priority']['version'] }}">
                    <span class="sale-debt-customer-code" title="Mã khách hàng{{ $debt['phone'] ? ' · SĐT: '.$debt['phone'] : '' }}">{{ $debt['code'] ?: 'Chưa có mã' }}</span>
                    @if(($debt['recovery']['status'] ?? 'normal') !== 'normal')<span class="sale-debt-recovery" title="{{ $debt['recovery']['label'] }}" aria-label="{{ $debt['recovery']['label'] }}">●</span>@endif
                    @foreach(range(1,5) as $star)<button type="submit" name="stars" value="{{ $star }}" class="sale-debt-star {{ $star <= $debt['priority']['stars'] ? 'is-active' : '' }}" title="Ghim {{ $star }} sao" aria-label="Ghim {{ $star }} sao" aria-pressed="{{ $star === $debt['priority']['stars'] ? 'true' : 'false' }}">★</button>@endforeach
                    @if($debt['priority']['stars'] > 0)<button type="submit" name="stars" value="0" class="sale-debt-unpin" title="Bỏ ghim" aria-label="Bỏ ghim">×</button>@endif
                    <span class="small text-danger" role="status"></span>
                </form></td>
                @foreach(['sales','payments','balance','period_sales','period_payments','period_increase'] as $column)<td class="text-end text-nowrap {{ in_array($column,['payments','period_payments']) ? 'text-success' : (in_array($column,['balance','period_increase']) && $debt[$column] > 0 ? 'text-danger fw-bold' : '') }}">{{ number_format($debt[$column],0,',','.') }} đ</td>@endforeach
                <td>{{ $debt['days_without_payment'] !== null ? $debt['days_without_payment'].' ngày' : 'Chưa thanh toán' }}</td>
                <td>@if($debt['last_purchase_date'])
                    {{ \Carbon\Carbon::parse($debt['last_purchase_date'])->format('d/m/Y') }}
                    @php
                        $days = (int) \Carbon\CarbonImmutable::parse($debt['last_purchase_date'])->diffInDays(\Carbon\CarbonImmutable::today(), false);
                    @endphp
                    <div class="small text-muted">{{ $days === 0 ? 'Hôm nay' : ($days === 1 ? 'Hôm qua' : ($days > 1 ? $days.' ngày trước' : 'Sau '.abs($days).' ngày')) }}</div>
                @else — @endif</td>
                <td><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ request()->fullUrlWithQuery(['customer'=>$debt['key'],'system_customer'=>null,'window'=>request('from') ? 'custom' : 'all','report_from'=>request('from') ? $periodFrom : null,'report_to'=>request('to') ? $periodTo : null,'purchase_page'=>null,'payment_page'=>null,'detail_page'=>null]) }}#debt-detail">Đối chiếu</a></td>
            </tr>
        @empty<tr><td colspan="11" class="sale-debt-empty"><div><i class="bi bi-wallet2" aria-hidden="true"></i><strong>Chưa có khách hàng phù hợp</strong><span>Bạn có thể điều chỉnh bộ lọc để tìm khách cần theo dõi.</span></div></td></tr>@endforelse</tbody>
        <tfoot><tr><th colspan="2">Tổng tất cả trang</th>@foreach(['sales','payments','balance','period_sales','period_payments','period_increase'] as $column)<th class="text-end text-nowrap">{{ number_format($summary[$column === 'balance' ? 'balance_net' : $column],0,',','.') }} đ</th>@endforeach<th colspan="3"></th></tr></tfoot>
    </table></div></div>
    <div class="sale-debt-pagination"><span class="small text-muted">{{ $debts->total() }} khách · {{ $debts->perPage() }} dòng / trang</span>{{ $debts->onEachSide(1)->links() }}</div>
    @if($selected)
        @include('customer-debt.detail-report', ['debtDetailAction'=>route('pages.my_customer_debt')])
    @endif
</div>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer-debt.css') }}?v={{ filemtime(public_path('css/customer-debt.css')) }}">
<style>
.sale-debt-page { --debt-accent:#0f766e; --debt-border:#e2e8f0; width:100%; max-width:1400px; box-sizing:border-box; margin:auto; padding:28px 20px; color:#24364b; overflow-wrap:break-word; }
.sale-debt-page .debt-page-header { margin-bottom:20px; }
.sale-debt-page .card { min-width:0; max-width:100%; }
.sale-debt-filters { gap:12px; border-color:var(--debt-border); padding:16px; border-radius:10px; background:#fff; box-shadow:0 2px 5px rgba(15,23,42,.025); }
.sale-debt-filters .debt-filter-field { flex:1 1 145px; min-width:130px; }
.sale-debt-filters .debt-filter-search { flex:2 1 235px; }
.sale-debt-filters .sale-debt-page-size { flex:0 1 110px; }
.sale-debt-filters .form-label { font-size:12px; font-weight:600; color:#64748b; margin-bottom:6px; }
.sale-debt-filters .form-control,.sale-debt-filters .form-select { min-height:38px; border-color:#dce3ec; border-radius:6px; font-size:12px; }
.sale-debt-shortcuts { display:flex; flex-wrap:wrap; gap:6px; margin:0 0 18px; }
.sale-debt-shortcuts .btn { padding:7px 11px; font-weight:500; border:1px solid transparent; }
.sale-debt-shortcuts .btn-light { background:#f4f6f9; color:#526278; }
.sale-debt-shortcuts .btn-light:hover { border-color:#cbd5e1; background:#eef2f6; }
.sale-debt-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin:0 0 12px; }
.sale-debt-summary-caption { grid-column:1/-1; display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:2px; }
.sale-debt-summary-caption strong { font-size:14px; }
.sale-debt-summary-caption>span { color:#64748b; font-size:12px; }
.sale-debt-summary-panel { padding:16px 18px; border:1px solid var(--debt-border); border-radius:10px; background:#fff; }
.sale-debt-summary-panel header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:6px; margin-bottom:15px; }
.sale-debt-summary-panel h2 { margin:0; font-size:12px; font-weight:700; color:#475569; }
.sale-debt-summary-panel header>span { color:#8090a3; font-size:11px; }
.sale-debt-summary-period { background:#f6fbfa; border-color:#d5e9e4; }
.sale-debt-metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
.sale-debt-metrics>div { display:flex; flex-direction:column; gap:6px; min-width:0; }
.sale-debt-metrics>div+div { border-left:1px solid var(--debt-border); padding-left:14px; }
.sale-debt-metrics span { font-size:11px; color:#718096; }
.sale-debt-metrics strong { font-size:18px; font-weight:700; letter-spacing:-.3px; font-variant-numeric:tabular-nums; }
.sale-debt-metrics strong small { font-size:11px; font-weight:500; color:#8090a3; }
.sale-debt-explanation { font-size:11px; color:#718096; margin:0 0 20px; }
.sale-debt-explanation summary { cursor:pointer; width:fit-content; }
.sale-debt-explanation>div { padding:9px 12px; margin-top:8px; border-left:2px solid #b9d5cf; line-height:1.7; background:#f8fafc; }
.sale-debt-table-toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; }
.sale-debt-table-toolbar .sale-debt-shortcuts,.sale-debt-table-toolbar .sale-debt-pagination { margin:0; }
.sale-debt-priority-hint { color:#8793a3; font-size:11px; margin:0 0 12px; }
.sale-debt-priority-hint i { color:#d5a445; font-size:10px; margin-right:4px; }
.sale-debt-table-card { border:1px solid #dce3ec!important; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(15,23,42,.03); }
.sale-debt-scroll { width:100%; max-width:100%; max-height:65vh; overflow:auto; }
.sale-debt-table { width:100%; min-width:1280px; table-layout:fixed; border-collapse:separate; border-spacing:0; margin:0; font-size:13px; }
.sale-debt-table> :not(caption)>*>* { padding:9px 6px; border-color:#e9edf3; background:#fff; box-shadow:none; }
.sale-debt-table thead th { vertical-align:middle!important; text-align:center!important; color:#526278; font-size:12px; font-weight:600; white-space:normal; line-height:1.5; border-bottom:1px solid #e0e6ee; }
.sale-debt-table .sale-debt-group-head th { position:sticky; top:0; z-index:2; height:44px; background:#f1f4f8; }
.sale-debt-table .sale-debt-column-head th { position:sticky; top:44px; z-index:2; height:40px; background:#f7f9fc; padding-top:10px; padding-bottom:10px; }
.sale-debt-table .sale-debt-group-head th:nth-child(2) { text-align:left!important; }
.sale-debt-table .sale-debt-group-head th[colspan] { color:#334155; font-size:12px; font-weight:700; }
.sale-debt-table .sale-debt-group-head .sale-debt-period-group,.sale-debt-table .sale-debt-column-head .sale-debt-period-column { background:#edf7f4; }
.sale-debt-period-group span { display:inline-block; font-size:10px; font-weight:500; color:#668b81; margin-left:6px; }
.sale-debt-table thead a { display:block; color:inherit; text-decoration:none!important; border:0; }
.sale-debt-table thead a:hover { color:var(--debt-accent); }
.sale-debt-table thead a:focus-visible { outline:2px solid var(--debt-accent); outline-offset:3px; border-radius:2px; }
.sale-debt-table tbody td { vertical-align:middle!important; overflow-wrap:break-word; line-height:1.5; }
.sale-debt-table tbody td:nth-child(1) { text-align:center; color:#94a3b8; font-size:12px; }
.sale-debt-table tbody td:nth-child(2) { text-align:left; white-space:normal; }
.sale-debt-table tbody td:nth-child(2)>strong { display:block; color:#253b50; font-size:14px; line-height:1.5; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sale-debt-table tbody td:nth-child(3),.sale-debt-table tbody td:nth-child(6) { border-left:1px solid #e2e8f0; }
.sale-debt-table tbody td:nth-child(9),.sale-debt-table tbody td:nth-child(10),.sale-debt-table tbody td:nth-child(11) { text-align:center; font-size:12px; }
.sale-debt-table tbody td.text-nowrap,.sale-debt-table tfoot .text-nowrap { white-space:nowrap!important; font-variant-numeric:tabular-nums; font-size:13px; letter-spacing:-.2px; }
.sale-debt-table tbody tr:hover>td { background:#f7fafb; }
.sale-debt-table tbody tr.table-primary>td { background:#eff8f5; }
.sale-debt-table td .btn { white-space:nowrap!important; font-size:12px; padding:5px 8px; border-color:#c9ded9; color:var(--debt-accent); border-radius:5px; }
.sale-debt-table td .btn:hover { background:var(--debt-accent); color:#fff; }
.sale-debt-table tfoot th { padding:14px 9px; background:#f3f6fa; border-top:1px solid #dce3ec; border-bottom:0; color:#334155; font-weight:700; }
.sale-debt-table .sale-debt-empty { padding:0; }
.sale-debt-empty>div { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; min-height:160px; padding:24px; text-align:center; }
.sale-debt-empty i { font-size:28px; color:#becbd6; margin-bottom:4px; }
.sale-debt-empty strong { color:#607286; font-size:13px; font-weight:600; }
.sale-debt-empty span { color:#93a0af; font-size:11px; }
.sale-debt-pagination { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px; margin:14px 0; }
.sale-debt-pagination .pagination { margin:0; flex-wrap:wrap; }
.sale-debt-stars { position:relative; display:flex; flex-wrap:nowrap; align-items:center; gap:2px; margin:3px 0 0; min-height:21px; }
.sale-debt-customer-code { flex:0 0 auto; margin-right:7px; color:#64748b; font-size:12px; font-variant-numeric:tabular-nums; }
.sale-debt-recovery { color:#d49a27; font-size:10px; margin-right:4px; }
.sale-debt-star,.sale-debt-unpin { border:0; background:transparent; color:#d2dae4; padding:1px 2px; font-size:17px; line-height:1; cursor:pointer; }
.sale-debt-star.is-active,.sale-debt-star:hover { color:#d49a27; }
.sale-debt-unpin { font-size:17px; color:#94a3b8; margin-left:4px; }
.sale-debt-stars button:disabled { opacity:.5; cursor:wait; }
.sale-debt-stars [role=status]:empty { display:none; }
.sale-debt-stars [role=status]:not(:empty) { position:absolute; top:100%; left:0; z-index:5; width:260px; max-width:80vw; padding:7px 9px; background:#fff7f7; border:1px solid #fecaca; border-radius:5px; white-space:normal; box-shadow:0 2px 6px #0001; }
.sale-debt-page select { display:block!important; }
.sale-debt-page select+.nice-select { display:none!important; }
@media(max-width:900px) { .sale-debt-summary { grid-template-columns:1fr; } .sale-debt-page { padding:20px 12px; } }
@media(max-width:480px) { .sale-debt-metrics { gap:6px; } .sale-debt-metrics>div+div { padding-left:8px; } .sale-debt-metrics strong { font-size:15px; } .sale-debt-summary-panel { padding:14px 12px; } .sale-debt-metrics span { font-size:10px; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const forms=[...document.querySelectorAll('[data-sale-debt-priority]')];
    let saving=false;
    forms.forEach(form=>form.addEventListener('submit',async event=>{
        event.preventDefault();
        if(saving||!event.submitter)return;
        saving=true;
        const status=form.querySelector('[role=status]');status.textContent='';
        const data=new FormData(form);data.set('stars',event.submitter.value);
        forms.forEach(item=>item.querySelectorAll('button').forEach(button=>button.disabled=true));
        try{
            const response=await fetch(form.action,{method:'POST',body:data,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
            const result=await response.json();
            if(!response.ok)throw new Error(Object.values(result.errors||{}).flat()[0]||result.message||'Không lưu được mức ghim.');
            const url=new URL(window.location.href);url.searchParams.set('page','1');window.location.assign(url);
        }catch(error){
            status.textContent=error.message;saving=false;
            forms.forEach(item=>item.querySelectorAll('button').forEach(button=>button.disabled=false));
        }
    }));
})();
</script>
@endpush
