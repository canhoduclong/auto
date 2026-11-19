@extends(($accountingDebt ?? false) ? accounting_layout() : 'layouts.site')
@section('title', ($accountingDebt ?? false) ? 'Công nợ toàn bộ khách hàng' : 'Công nợ khách hàng của tôi')
@section(($accountingDebt ?? false) ? 'accounting_content' : 'content')
@php
    $debtListUrl = ($accountingDebt ?? false) ? accounting_route('customer-debts') : route('pages.my_customer_debt');
@endphp
<div class="debt-page sale-debt-page {{ ($accountingDebt ?? false) ? 'accounting-debt-page' : '' }}">
    <header class="debt-page-header">
        <div><h1>Công nợ khách hàng</h1><div class="debt-updated">@if($snapshot)Cập nhật {{ \Carbon\Carbon::parse($snapshot['loaded_at'])->format('d/m/Y H:i') }}@else Chưa có dữ liệu công nợ @endif</div></div>
        @if($accountingDebt ?? false)
        <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="{{ accounting_route('customer-debts', ['ledger'=>1]) }}">Sổ kế toán & điều chỉnh</a><a class="btn btn-outline-primary" href="{{ route('customer-debt.index', ['tab'=>'all', 'recovery'=>'all']) }}">Quản lý nguồn & cảnh báo</a></div>
        @else
        <a href="{{ route('pages.my_dashboard') }}" class="btn btn-light">Về bảng điều khiển</a>
        @endif
    </header>
    @foreach(['success'=>'success','error'=>'danger'] as $key=>$color)@if(session($key))<div class="alert alert-{{ $color }} py-2">{{ session($key) }}</div>@endif @endforeach
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if(!$snapshot)<div class="alert alert-info">Chưa có dữ liệu công nợ. Vui lòng liên hệ kế toán cập nhật dữ liệu.</div>@endif
    <form method="GET" action="{{ $debtListUrl }}" class="debt-filters sale-debt-filters">
        <div class="debt-filter-field"><label class="form-label" for="sale-debt-from">Thống kê từ ngày</label><input class="form-control" type="date" id="sale-debt-from" name="from" value="{{ request('from') }}"></div>
        <div class="debt-filter-field"><label class="form-label" for="sale-debt-to">Đến ngày</label><input class="form-control" type="date" id="sale-debt-to" name="to" value="{{ request('to') }}"></div>
        <div class="debt-filter-field debt-filter-search"><label class="form-label" for="sale-debt-search">Khách hàng</label><input class="form-control" type="search" id="sale-debt-search" name="search" value="{{ request('search') }}" placeholder="Tên, mã khách hoặc số điện thoại" maxlength="150"></div>
        @if($accountingDebt ?? false)
        <div class="debt-filter-field"><label class="form-label" for="accounting-debt-sale">Sale phụ trách</label><select class="form-select" id="accounting-debt-sale" name="sale"><option value="">Tất cả sale</option>@foreach($saleOptions as $saleOption)<option value="{{ $saleOption['key'] }}" @selected(request('sale') === $saleOption['key'])>{{ $saleOption['label'] }}</option>@endforeach</select></div>
        <input type="hidden" name="payment_class" value="{{ request('payment_class','all') }}">
        <div class="debt-filter-field"><label class="form-label" for="acc-reminder-filter">Cảnh báo</label><select class="form-select" id="acc-reminder-filter" name="reminder"><option value="">Tất cả</option><option value="remind" @selected(request('reminder') === 'remind')>Nhắc thu nợ</option><option value="overdue" @selected(request('reminder') === 'overdue')>Quá hạn</option></select></div>
        @endif
        <div class="debt-filter-field"><label class="form-label" for="sale-debt-scope">Phạm vi khách</label><select class="form-select" id="sale-debt-scope" name="scope"><option value="all" @selected(request('scope','all') === 'all')>{{ ($accountingDebt ?? false) ? 'Toàn bộ khách hàng' : 'Tất cả khách phụ trách' }}</option><option value="orders" @selected(request('scope') === 'orders')>Có mua hàng trong kỳ</option></select></div>
        <div class="debt-filter-field"><label class="form-label" for="sale-debt-balance">Công nợ</label><select class="form-select" id="sale-debt-balance" name="balance">@foreach(['debt'=>'Đang có nợ','all'=>'Tất cả số dư','settled'=>'Đã cân đối','credit'=>'Khách trả trước'] as $value=>$label)<option value="{{ $value }}" @selected(request('balance','debt') === $value)>{{ $label }}</option>@endforeach</select></div>
        @include('customer-debt.min-debt-filter')
        <div class="debt-filter-field sale-debt-page-size"><label class="form-label" for="sale-debt-per-page">Số dòng / trang</label><select class="form-select" id="sale-debt-per-page" name="per_page">@foreach([20,30,50,100,200,300] as $size)<option value="{{ $size }}" @selected((int) request('per_page',30) === $size)>{{ $size }}</option>@endforeach</select></div>
        @if(request('range') === 'all')<input type="hidden" name="range" value="all">@endif
        <input type="hidden" name="recovery" value="{{ request('recovery','all') }}">
        @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="direction" value="{{ request('direction','desc') }}">@endif
        <div class="debt-filter-actions"><button class="btn btn-primary">Lọc</button><a class="btn btn-light" href="{{ $debtListUrl }}">Xóa lọc</a></div>
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
        @if($accountingDebt ?? false)
        <form method="POST" action="{{ route('customer-debt.load') }}" class="sale-debt-refresh" onsubmit="this.querySelector('button').disabled=true;this.querySelector('span').textContent='Đang cập nhật…'">
            @csrf<input type="hidden" name="source" value="google"><button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat"></i> <span>Cập nhật bán hàng & thanh toán Google Sheet</span></button>
        </form>
        @else
        <form method="POST" action="{{ route('pages.my_customer_debt.refresh') }}" class="sale-debt-refresh" data-sale-debt-refresh>
            @csrf<button type="submit" class="btn btn-sm btn-outline-primary" title="Cập nhật dữ liệu mới từ Google Sheet"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> <span>Cập nhật Google Sheet</span></button>
            <span role="status" class="small text-danger"></span>
        </form>
        @endif
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
    @if($accountingDebt ?? false)
    <nav class="sale-debt-shortcuts accounting-debt-classification" aria-label="Phân loại thanh toán">
        <span class="small text-muted align-self-center me-1">Phân loại</span>
        @foreach(['all'=>'Tất cả','default'=>'1 Mặc định','multi'=>'2 bill trở lên','cycle'=>'Định kỳ'] as $value=>$label)
        <a class="btn btn-sm {{ request('payment_class','all') === $value ? 'btn-primary' : 'btn-light' }}" href="{{ request()->fullUrlWithQuery(['payment_class'=>$value,'payment_mode'=>null,'page'=>1,'customer'=>null,'system_customer'=>null]) }}" @if(request('payment_class','all') === $value) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @endif
    <nav class="sale-debt-shortcuts" aria-label="Khả năng thu hồi">@foreach(['all'=>'Tất cả','normal'=>'Đang theo dõi','difficult'=>'Khó thu hồi','unrecoverable'=>'Không có khả năng thu hồi'] as $value=>$label)<a class="btn btn-sm {{ request('recovery','all') === $value ? 'btn-primary' : 'btn-light' }}" href="{{ request()->fullUrlWithQuery(['recovery'=>$value,'page'=>1,'customer'=>null,'system_customer'=>null]) }}">{{ $label }}</a>@endforeach</nav>
    <div class="sale-debt-pagination"><span class="small text-muted">Hiển thị {{ $debts->firstItem() ?? 0 }}–{{ $debts->lastItem() ?? 0 }} / {{ $debts->total() }} khách</span>{{ $debts->onEachSide(1)->links() }}</div>
    </div>
    @if(!($accountingDebt ?? false))<div class="sale-debt-priority-hint"><i class="bi bi-star-fill" aria-hidden="true"></i> Ưu tiên 5 sao → 1 sao → chưa ghim · Cùng mức sao, sắp xếp theo cột đã chọn</div>@endif
    <div class="card sale-debt-table-card"><div class="table-responsive sale-debt-scroll"><table class="table table-hover align-middle sale-debt-table mb-0">
        <colgroup><col style="width:3%"><col style="width:20%">@foreach(range(1,6) as $unused)<col style="width:8%">@endforeach<col style="width:8%"><col style="width:9%">@if($accountingDebt ?? false)<col style="width:8%"><col style="width:4%">@else<col style="width:12%">@endif</colgroup>
        <thead>
            <tr class="sale-debt-group-head"><th rowspan="2" scope="col">STT</th><th rowspan="2" scope="col">Khách hàng</th><th colspan="3" scope="colgroup" class="text-center">Tổng toàn bộ</th><th colspan="3" scope="colgroup" class="text-center sale-debt-period-group">Trong kỳ <span>{{ \Carbon\Carbon::parse($periodFrom)->format('d/m') }} – {{ \Carbon\Carbon::parse($periodTo)->format('d/m') }}</span></th>
                @foreach((($accountingDebt ?? false) ? ['overdue_days'=>'Thanh toán','last_purchase_date'=>'Đơn mới nhất'] : ['days_without_payment'=>'Chưa thanh toán','last_purchase_date'=>'Đơn mới nhất']) as $column=>$label)
                <th rowspan="2" scope="col"><a href="{{ request()->fullUrlWithQuery(['sort'=>$column,'direction'=>request('sort','balance') === $column && request('direction','desc') === 'desc' ? 'asc' : 'desc','page'=>1]) }}">{{ $label }}@if(request('sort','balance') === $column) {{ request('direction','desc') === 'desc' ? '↓' : '↑' }}@endif</a></th>
                @endforeach
                @if($accountingDebt ?? false)<th rowspan="2" scope="col">Chi tiết cảnh báo</th>@endif
                <th rowspan="2" scope="col">{{ ($accountingDebt ?? false) ? 'Thao tác' : 'Chi tiết' }}</th>
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
                <td><strong title="{{ $debt['name'] }}{{ $debt['phone'] ? ' · '.$debt['phone'] : '' }}">{{ $debt['name'] }}</strong>@if(!($accountingDebt ?? false))<form method="POST" action="{{ route('pages.my_customer_debt.priority') }}" class="sale-debt-stars" data-sale-debt-priority aria-label="Ghim {{ $debt['name'] }}">
                    @csrf<input type="hidden" name="customer_id" value="{{ $debt['customer_id'] }}"><input type="hidden" name="source_key" value="{{ $debt['key'] }}"><input type="hidden" name="priority_version" value="{{ $debt['priority']['version'] }}">
                    <span class="sale-debt-customer-code" title="Mã khách hàng{{ $debt['phone'] ? ' · SĐT: '.$debt['phone'] : '' }}">{{ $debt['code'] ?: 'Chưa có mã' }}</span>
                    @if(($debt['recovery']['status'] ?? 'normal') !== 'normal')<span class="sale-debt-recovery" title="{{ $debt['recovery']['label'] }}" aria-label="{{ $debt['recovery']['label'] }}">●</span>@endif
                    @foreach(range(1,5) as $star)<button type="submit" name="stars" value="{{ $star }}" class="sale-debt-star {{ $star <= $debt['priority']['stars'] ? 'is-active' : '' }}" title="Ghim {{ $star }} sao" aria-label="Ghim {{ $star }} sao" aria-pressed="{{ $star === $debt['priority']['stars'] ? 'true' : 'false' }}">★</button>@endforeach
                    @if($debt['priority']['stars'] > 0)<button type="submit" name="stars" value="0" class="sale-debt-unpin" title="Bỏ ghim" aria-label="Bỏ ghim">×</button>@endif
                    <span class="small text-danger" role="status"></span>
                </form>
                    <div class="small mt-1">{{ $debt['policy']['payment_mode'] === 'cycle' ? 'Thanh toán theo chu kỳ '.($debt['policy']['cycle_type'] === 'weekly' ? 'hằng tuần' : 'hằng tháng') : 'Mặc định — gối đầu 1 đơn' }}</div>
                    @include('customer-debt.alerts', ['alert'=>$debt['alerts'], 'compact'=>true])
                @else
                    <div class="accounting-customer-meta" title="{{ $debt['code'] }} · {{ $debt['phone'] }} · {{ $debt['sale_label'] ?? '' }}">
                        <span>{{ $debt['code'] ?: 'Chưa có mã' }}</span>
                        @if($debt['phone'])<span>{{ $debt['phone'] }}</span>@endif
                        <span>{{ $debt['sale_label'] ?? 'Chưa có sale' }}</span>
                        @if($debt['recovery']['status'] !== 'normal')<span class="text-warning-emphasis">{{ $debt['recovery']['label'] }}</span>@endif
                    </div>



                @endif</td>
                @foreach(['sales','payments','balance','period_sales','period_payments','period_increase'] as $column)<td class="text-end text-nowrap {{ in_array($column,['payments','period_payments']) ? 'text-success' : (in_array($column,['balance','period_increase']) && $debt[$column] > 0 ? 'text-danger fw-bold' : '') }}">{{ number_format($debt[$column],0,',','.') }} đ</td>@endforeach
                <td>
                    @if($accountingDebt ?? false)
                    <div class="accounting-primary-alert">
                        @if($debt['balance'] <= 0)<span class="small text-success">{{ $debt['balance'] < 0 ? 'Khách trả trước' : 'Đã cân đối' }}</span>
                        @elseif($debt['alerts']['overdue'])<span class="badge bg-danger">Trễ {{ $debt['alerts']['overdue_days'] }} ngày</span>
                        @elseif($debt['alerts']['risk'])<span class="badge bg-danger">Rủi ro {{ ['medium'=>'trung bình','high'=>'cao','very_high'=>'rất cao'][$debt['alerts']['risk']] }}</span>
                        @elseif($debt['alerts']['remind'])<span class="badge bg-warning text-dark">Nhắc thu nợ</span>
                        @else<span class="small text-muted">{{ $debt['alerts']['due_date'] ? 'Chưa đến hạn' : 'Trong hạn gối đầu' }}</span>@endif
                    </div>
                    @if($debt['balance'] > 0 && $debt['alerts']['due_date'])<div class="small text-muted">Hạn {{ \Carbon\Carbon::parse($debt['alerts']['due_date'])->format('d/m/Y') }}</div>@endif
                    @else
                        {{ $debt['days_without_payment'] !== null ? $debt['days_without_payment'].' ngày' : 'Chưa thanh toán' }}
                    @endif
                </td>
                <td>@if($debt['last_purchase_date'])
                    {{ \Carbon\Carbon::parse($debt['last_purchase_date'])->format('d/m/Y') }}
                    @php
                        $days = (int) \Carbon\CarbonImmutable::parse($debt['last_purchase_date'])->diffInDays(\Carbon\CarbonImmutable::today(), false);
                    @endphp
                    <div class="small text-muted">{{ $days === 0 ? 'Hôm nay' : ($days === 1 ? 'Hôm qua' : ($days > 1 ? $days.' ngày trước' : 'Sau '.abs($days).' ngày')) }}</div>
                @else — @endif</td>
                @if($accountingDebt ?? false)
                <td class="accounting-alert-column">
                    <button type="button" class="btn btn-sm btn-light" popovertarget="debt-alert-{{ $loop->index }}" data-debt-actions-toggle aria-label="Chi tiết cảnh báo của {{ $debt['name'] }}">Xem</button>
                    <div id="debt-alert-{{ $loop->index }}" class="debt-alert-popover" popover>
                        <strong>{{ $debt['name'] }}</strong>
                    <div class="small mt-1">{{ ($debt['policy']['payment_mode'] ?? 'default') === 'cycle' ? (($debt['policy']['cycle_type'] ?? 'weekly') === 'monthly' ? 'Chốt ngày '.($debt['policy']['cycle_close_monthday'] ?? $debt['policy']['cycle_monthday']).', trả ngày '.$debt['policy']['cycle_monthday'].' hằng tháng' : 'Chốt thứ '.(($debt['policy']['cycle_close_weekday'] ?? $debt['policy']['cycle_weekday']) === 7 ? 'CN' : ($debt['policy']['cycle_close_weekday'] ?? $debt['policy']['cycle_weekday'])+1).', trả '.([1=>'thứ hai',2=>'thứ ba',3=>'thứ tư',4=>'thứ năm',5=>'thứ sáu',6=>'thứ bảy',7=>'chủ nhật'][$debt['policy']['cycle_weekday']]).' hằng tuần') : 'Mặc định — gối đầu 1 đơn' }} · Trần {{ number_format($debt['policy']['credit_limit'],0,',','.') }} đ</div>
                        <div class="small mt-2">{{ $debt['alerts']['unpaid_orders'] }} Bill còn nợ · Tuổi nợ {{ $debt['alerts']['age_days'] }} ngày</div>
                        @if($debt['alerts']['due_date'])<div class="small text-muted">Hạn: {{ \Carbon\Carbon::parse($debt['alerts']['due_date'])->format('d/m/Y') }}</div>@endif
                        <div class="small text-muted">Thanh toán gần nhất: {{ $debt['last_payment_date'] ? \Carbon\Carbon::parse($debt['last_payment_date'])->format('d/m/Y').' ('.$debt['days_without_payment'].' ngày trước)' : 'Chưa ghi nhận thanh toán' }}</div>
                        @foreach($debt['alerts']['reasons'] as $reason)<div class="small text-muted mt-1">{{ $reason }}</div>@endforeach
                        @if(!$debt['alerts']['balance'])<div class="small text-success mt-1">Không còn công nợ phải thu</div>@endif
                    </div>
                </td>
                @endif
                <td>
                    @if($accountingDebt ?? false)
                        @include('accounting.partials.customer-debt-actions')
                    @else
<a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ request()->fullUrlWithQuery(['customer'=>$debt['key'],'system_customer'=>null,'window'=>request('from') ? 'custom' : 'all','report_from'=>request('from') ? $periodFrom : null,'report_to'=>request('to') ? $periodTo : null,'purchase_page'=>null,'payment_page'=>null,'detail_page'=>null]) }}#debt-detail">Đối chiếu</a>
                    @if(!($accountingDebt ?? false) && $debt['customer_id'])
                    <div class="d-flex flex-column gap-1 mt-2">
                        <button class="btn btn-sm btn-light" type="button" data-accounting-policy data-key="{{ $debt['key'] }}" data-name="{{ $debt['name'] }}" data-policy='@json($debt["policy"])' data-update-url="{{ route('customers.debt-policy.update', $debt['customer_id']) }}" @disabled($debt['policy']['staff_edit_locked'] && !auth()->user()->isAdmin())>Chính sách thanh toán</button>
                        <span class="small text-muted">{{ $debt['policy']['staff_edit_locked'] ? 'Đã khóa' : 'Được lưu 1 lần' }}</span>
                    </div>
                    @endif
                    @if(auth()->user()->isAdmin() && $debt['customer_id'] && $debt['policy']['staff_edit_locked'])
                    <div class="mt-1"><button type="button" class="btn btn-sm btn-light" data-unlock-sale-policy data-url="{{ route('customers.debt-policy.unlock', $debt['customer_id']) }}" data-version="{{ $debt['policy']['edit_version'] }}">Mở khóa cho sale sửa 1 lần</button><span class="small text-danger" role="status"></span></div>
                    @endif
                    @if($accountingDebt ?? false)
                    <div class="d-flex flex-column gap-1 mt-2">
                        <button class="btn btn-sm btn-light" type="button" data-accounting-policy data-key="{{ $debt['key'] }}" data-name="{{ $debt['name'] }}" data-policy='@json($debt["policy"])'>Chính sách thanh toán</button>
                        @if($canAdjust) @include('customer-debt.balance-button') @endif
                        <button type="button" class="btn btn-sm btn-light debt-recovery-button" data-key="{{ $debt['key'] }}" data-name="{{ $debt['name'] }}" data-status="{{ $debt['recovery']['status'] }}" data-note="{{ $debt['recovery']['note'] }}" data-version="{{ $debt['recovery']['version'] }}">Phân loại thu hồi</button>
                    </div>
                    @endif
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="{{ ($accountingDebt ?? false) ? 12 : 11 }}" class="sale-debt-empty"><div><i class="bi bi-wallet2" aria-hidden="true"></i><strong>Chưa có khách hàng phù hợp</strong><span>Bạn có thể điều chỉnh bộ lọc để tìm khách cần theo dõi.</span></div></td></tr>@endforelse</tbody>
        <tfoot><tr><th colspan="2">Tổng tất cả trang</th>@foreach(['sales','payments','balance','period_sales','period_payments','period_increase'] as $column)<th class="text-end text-nowrap">{{ number_format($summary[$column === 'balance' ? 'balance_net' : $column],0,',','.') }} đ</th>@endforeach<th colspan="{{ ($accountingDebt ?? false) ? 4 : 3 }}"></th></tr></tfoot>
    </table></div></div>
    <div class="sale-debt-pagination"><span class="small text-muted">{{ $debts->total() }} khách · {{ $debts->perPage() }} dòng / trang</span>{{ $debts->onEachSide(1)->links() }}</div>
    @include('accounting.partials.customer-debt-tools')
    @if($accountingDebt ?? false)
        @include('customer-debt.recovery-dialog')
        @if($canAdjust) @include('customer-debt.balance-adjustment-dialog') @endif
    @endif
    @if($selected)
        @include('customer-debt.detail-report', ['debtDetailAction'=>$debtListUrl])
    @endif
</div>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer-debt.css') }}?v={{ filemtime(public_path('css/customer-debt.css')) }}">
<style>
.sale-debt-page { --debt-accent:#0f766e; --debt-border:#e2e8f0; width:100%; max-width:1400px; min-width:0; box-sizing:border-box; margin:auto; padding:28px 20px; color:#24364b; overflow-wrap:break-word; }
.sale-debt-page.accounting-debt-page { max-width:none; padding:20px 8px; }
.accounting-debt-page .sale-debt-table { min-width:1450px; }
.accounting-debt-page .sale-debt-table col:nth-child(2) { width:20%!important; }
.accounting-debt-page .sale-debt-table col:last-child { width:4%!important; }
.accounting-debt-classification { flex-basis:100%; border-bottom:1px solid #e2e8f0; padding-bottom:10px; }
.debt-actions-menu { position:fixed; inset:auto; margin:0; padding:6px; border:1px solid #dce3ec; border-radius:8px; background:#fff; box-shadow:0 8px 25px #0f172a22; width:230px; max-height:70vh; overflow:auto; }
.debt-actions-menu::backdrop { background:transparent; }
.debt-actions-menu a,.debt-actions-menu button { display:block; width:100%; text-align:left; border:0; background:transparent; padding:8px 10px; border-radius:5px; color:#334155; font-size:13px; text-decoration:none; }
.debt-actions-menu a:hover,.debt-actions-menu button:hover { background:#f1f5f9; }
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
.sale-debt-refresh { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin:0 0 0 6px; }
.sale-debt-refresh .btn { display:inline-flex; align-items:center; gap:6px; }
.sale-debt-refresh .bi { font-size:15px; }
.sale-debt-refresh.is-loading .bi { animation:sale-debt-spin 1s linear infinite; }
@keyframes sale-debt-spin { to { transform:rotate(360deg); } }
@media(prefers-reduced-motion:reduce) { .sale-debt-refresh.is-loading .bi { animation:none; } }
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
.sale-debt-scroll { width:100%; max-width:100%; overflow-x:auto; }
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
/* Keep the accounting list at three compact lines until details are opened. */
.accounting-debt-page .sale-debt-table tbody td { padding:6px 8px; line-height:1.3; }
.accounting-debt-page .sale-debt-table tbody td:nth-child(2)>strong { font-size:13px; line-height:1.35; }
.accounting-customer-meta { display:flex; gap:0 7px; color:#7a8899; font-size:11px; line-height:1.4; white-space:nowrap; overflow:hidden; margin-top:1px; }
.accounting-customer-meta span+span::before { content:'·'; margin-right:7px; color:#b0bac7; }
.accounting-primary-alert { margin-top:2px; line-height:1.25; }
.accounting-primary-alert .badge { font-size:10px; font-weight:600; padding:2px 5px; }
.debt-alert-popover { position:fixed; inset:auto; margin:0; width:300px; max-width:calc(100vw - 16px); max-height:65vh; overflow:auto; border:1px solid #dce3ec; border-radius:8px; padding:12px; box-shadow:0 8px 25px #0f172a22; background:#fff; color:#334155; }
.debt-alert-popover::backdrop { background:transparent; }
.accounting-debt-page .sale-debt-table td .btn { padding:3px 7px; }
.sale-debt-page select { display:block!important; }
.sale-debt-page select+.nice-select { display:none!important; }
@media(max-width:900px) { .sale-debt-summary { grid-template-columns:1fr; } .sale-debt-page { padding:20px 12px; } }
@media(max-width:480px) { .sale-debt-metrics { gap:6px; } .sale-debt-metrics>div+div { padding-left:8px; } .sale-debt-metrics strong { font-size:15px; } .sale-debt-summary-panel { padding:14px 12px; } .sale-debt-metrics span { font-size:10px; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    document.querySelectorAll('[data-debt-actions-toggle]').forEach(button=>button.addEventListener('click',()=>{
        const menu=document.getElementById(button.getAttribute('popovertarget')),rect=button.getBoundingClientRect();
        const width=menu.classList.contains('debt-alert-popover') ? 300 : 230;
        menu.style.left=Math.max(8,Math.min(window.innerWidth-width-8,rect.right-width))+'px';
        menu.style.top=Math.max(8,Math.min(window.innerHeight-300,rect.bottom+4))+'px';
    }));
    document.querySelectorAll('.debt-actions-menu').forEach(menu=>menu.addEventListener('click',event=>{
        if(event.target.closest('button,a'))menu.hidePopover();
    }));
    const refresh=document.querySelector('[data-sale-debt-refresh]');
    if(refresh)refresh.addEventListener('submit',async event=>{
        event.preventDefault();
        const button=refresh.querySelector('button'),label=button.querySelector('span'),status=refresh.querySelector('[role=status]');
        if(button.disabled)return;
        button.disabled=true;label.textContent='Đang cập nhật…';status.textContent='';refresh.classList.add('is-loading');
        try{
            const response=await fetch(refresh.action,{method:'POST',body:new FormData(refresh),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
            if(response.status===429)throw new Error('Vui lòng chờ một phút trước khi cập nhật lại.');
            const result=await response.json();
            if(!response.ok)throw new Error(result.message||'Không cập nhật được dữ liệu. Vui lòng thử lại.');
            window.location.reload();
        }catch(error){status.textContent=error.message;button.disabled=false;label.textContent='Cập nhật Google Sheet';refresh.classList.remove('is-loading');}
    });
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
