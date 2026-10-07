@php
    $statusOptions=['Công việc'=>['task_pending'=>'Chờ thực hiện / Tiếp nhận','task_processing'=>'Đang thực hiện','task_completed'=>'Chờ nghiệm thu','task_done'=>'Đã hoàn thành','task_rejected'=>'Yêu cầu làm lại','task_cancelled'=>'Đã hủy','task_draft'=>'Nháp'], 'Biểu quyết'=>['vote_open'=>'Đang mở','vote_expired'=>'Hết hạn / Chờ chốt','vote_approved'=>'Thông qua','vote_rejected'=>'Không thông qua','vote_no_quorum'=>'Không đủ số phiếu']];
    $quickFilters=['mine'=>'Liên quan đến tôi','received'=>'Tôi nhận','unaccepted'=>'Chưa tiếp nhận','working'=>'Đang thực hiện','reported'=>'Báo cáo đã gửi','history'=>'Lịch sử','votes'=>'Biểu quyết','assigned'=>'Tôi giao','execution'=>'Giao thực hiện','coordination'=>'Phối hợp','overdue'=>'Quá hạn','verification'=>'Chờ nghiệm thu'];
    $receiptOnly=\App\Services\TaskMenuService::isReceiptOnlyShipper(auth()->user());
    if($receiptOnly){unset($quickFilters['votes'],$quickFilters['assigned'],$quickFilters['verification']);unset($statusOptions['Biểu quyết']);}
    $quickBase=request()->only(['q','from','to','sort','per_page']);
    $quickActive=$filter;
    if($filter==='mine' && count($selectedKinds)===1){
        $quickActive=['vote'=>'votes','execution'=>'execution','coordination'=>'coordination'][$selectedKinds[0]];
    }
@endphp
<form method="GET" action="{{ route('operating.index') }}" class="op-filters" id="operatingFilters">
    <input type="hidden" name="kinds_present" value="1">
    <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Lọc nhanh công việc">
        @foreach($quickFilters as $key=>$label)
            @php
                $quickKinds=match($key){'votes'=>['vote'],'execution'=>['execution'],'coordination'=>['coordination'],default=>$selectedKinds};
                if($key==='mine'){$quickKinds=['execution','coordination','vote'];}
                $quickQuery=array_merge($quickBase,['filter'=>in_array($key,['votes','execution','coordination'],true)?'mine':$key,'kinds_present'=>1,'kinds'=>$quickKinds]);
            @endphp
            <a href="{{ route('operating.index',$quickQuery) }}" class="btn btn-sm rounded-pill {{ $quickActive===$key?'btn-primary':'btn-outline-secondary' }}" @if($quickActive===$key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <fieldset class="mb-3"><legend class="fs-6 fw-semibold mb-2">Loại nghiệp vụ <span class="op-muted fw-normal">· Có thể chọn nhiều loại</span></legend><div class="d-flex flex-wrap gap-2">
        @foreach(['execution'=>'Giao thực hiện','coordination'=>'Yêu cầu phối hợp'] + ($receiptOnly?[]:['vote'=>'Biểu quyết']) as $key=>$label)
        <input type="checkbox" class="btn-check" name="kinds[]" id="kind-{{ $key }}" value="{{ $key }}" @checked(in_array($key,$selectedKinds,true)) onchange="this.form.requestSubmit()"><label class="btn btn-outline-primary mb-0" for="kind-{{ $key }}">{{ $label }}</label>
        @endforeach
    </div></fieldset>
    <div class="row g-2">
        <div class="col-12 col-lg-4"><label for="operating-q">Tìm kiếm</label><input id="operating-q" name="q" class="form-control" value="{{ request('q',request('task_q',request('proposal_q'))) }}" placeholder="Tên, mã công việc, người giao hoặc người nhận…" maxlength="200"></div>
        <div class="col-6 col-lg-4"><label for="operating-scope">Phạm vi</label><select id="operating-scope" name="filter" class="form-select">@foreach(['mine'=>'Liên quan đến tôi','received'=>'Tôi nhận','assigned'=>'Tôi giao','unaccepted'=>'Chưa tiếp nhận','working'=>'Đang thực hiện','reported'=>'Báo cáo đã gửi','verification'=>'Chờ nghiệm thu','overdue'=>'Quá hạn','history'=>'Lịch sử'] + (auth()->user()->hasRole('admin')?['all'=>'Tất cả (quản trị)','deleted'=>'Công việc đã xóa']:[]) as $key=>$label) @continue($receiptOnly && in_array($key,['assigned','verification','all','deleted'],true)) <option value="{{ $key }}" @selected(($filter==='votes'||in_array($filter,['execution','coordination'],true)?'mine':$filter)===$key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-lg-4"><label for="operating-status">Trạng thái</label><select id="operating-status" name="status" class="form-select"><option value="">Tất cả trạng thái</option>@foreach($statusOptions as $group=>$options)<optgroup label="{{ $group }}">@foreach($options as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</optgroup>@endforeach</select></div>
        <div class="col-6 col-lg-2"><label for="operating-from">Ngày tạo từ</label><input id="operating-from" type="date" name="from" class="form-control" value="{{ request('from',request('task_from',request('proposal_from'))) }}"></div>
        <div class="col-6 col-lg-2"><label for="operating-to">Ngày tạo đến</label><input id="operating-to" type="date" name="to" class="form-control" value="{{ request('to',request('task_to',request('proposal_to'))) }}"></div>
        <div class="col-6 col-lg-3"><label for="operating-sort">Sắp xếp</label><select id="operating-sort" name="sort" class="form-select">@foreach(['newest'=>'Ngày tạo: mới nhất','oldest'=>'Ngày tạo: cũ nhất','due'=>'Hạn: gần nhất'] as $value=>$label)<option value="{{ $value }}" @selected(request('sort','newest')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-lg-2"><label for="operating-per-page">Số dòng / trang</label><select id="operating-per-page" name="per_page" class="form-select">@foreach([10,20,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',20)===$size)>{{ $size }} dòng</option>@endforeach</select></div>
        <div class="col-12 col-lg-3 d-flex align-items-end gap-2"><button type="submit" class="btn btn-primary">Tìm kiếm / Lọc</button><a class="btn btn-outline-secondary" href="{{ route('operating.index',['filter'=>'mine']) }}">Đặt lại</a></div>
    </div>
</form>
