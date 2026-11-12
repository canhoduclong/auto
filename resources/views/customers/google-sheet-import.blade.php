@extends('layouts.app')
@section('title', 'Import & đồng bộ khách hàng từ Google Sheet')
@section('content')
<div class="sheet-customer-page">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><div><h3 class="mb-1">Import & đồng bộ khách hàng từ Google Sheet</h3><div class="text-muted small">Nguồn gốc dùng chung với Import Đơn · nhatkybanhang</div></div><a class="btn btn-light" href="{{ route('customers.index') }}">Danh sách khách hàng</a></header>
    @foreach(['success'=>'success','error'=>'danger'] as $key=>$color)
        @if(session($key))<div class="alert alert-{{ $color }}">{{ session($key) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card mb-3"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div><a href="{{ \App\Services\GoogleSheetOrderImportService::URL }}" target="_blank" rel="noopener">Mở Google Sheet gốc</a>
            @if($snapshot)<div class="text-muted small mt-1">Đã đọc lúc {{ \Carbon\Carbon::parse($snapshot['loaded_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · Bản xem trước lưu trong 2 giờ.</div>@endif
            <div class="small text-muted mt-1">Chế độ import chỉ ghép nguồn / tạo khách mới. Chế độ đồng bộ cho xem thay đổi và cập nhật thông tin khách hiện có.</div>
            <details class="small mt-2"><summary>Tài khoản kết nối & cách ghép</summary><div class="pt-2"><div>Tài khoản: <strong style="overflow-wrap:anywhere">{{ $connectionEmail ?: 'Chưa xác định được tài khoản dịch vụ' }}</strong> · cần quyền Người xem.</div><div>Ưu tiên ghép đã lưu, sau đó đối chiếu mã và tên. Trường hợp mã / tên trỏ đến nhiều khách cần chọn thủ công. Sale không xác định sẽ để khách ở trạng thái chưa phân công.</div></div></details>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-start">
        <form method="POST" action="{{ route('customers.sheet-import.load') }}" data-sheet-import-loading>
            @csrf<input type="hidden" name="mode" value="sync">
            <details class="small mb-2"><summary>Trường đồng bộ</summary><div class="d-flex flex-column gap-1 pt-2">@foreach(\App\Services\GoogleSheetCustomerImportService::SYNC_FIELDS as $field=>$label)<label><input type="checkbox" name="sync_fields[]" value="{{ $field }}" @checked(in_array($field, $snapshot['sync_fields'] ?? ['name','phone','address'])) @disabled($field === 'sale_id' && !$canReassign)> {{ $label }}</label>@endforeach</div></details>
            <button class="btn btn-primary">Đồng bộ thay đổi từ Google Sheet</button>
        </form>
        <form method="POST" action="{{ route('customers.sheet-import.load') }}" data-sheet-import-loading>@csrf<input type="hidden" name="mode" value="import"><button class="btn btn-outline-primary">Chỉ import / ghép khách</button></form>
        </div>
    </div></div>
    @if($snapshot)
    @if($syncMode)<div class="alert alert-info py-2 small">Chế độ đồng bộ · Chỉ cập nhật trường đã chọn và có giá trị trên Sheet; ô trống giữ nguyên dữ liệu hiện tại. Xem thay đổi cũ → mới bên dưới trước khi áp dụng.</div>@endif
    <div class="sheet-customer-stats mb-3">
        @foreach(['all'=>['Tổng khách nguồn',$total],'link'=>['Ghép khách hiện có',$counts['link'] ?? 0],'create'=>['Khách mới',$counts['create'] ?? 0],'update'=>['Có thay đổi',$counts['update'] ?? 0],'unchanged'=>['Không thay đổi',$counts['unchanged'] ?? 0],'conflict'=>['Cần xử lý',$counts['conflict'] ?? 0],'imported'=>['Đã import',$counts['imported'] ?? 0],'skip'=>['Bỏ qua',$counts['skip'] ?? 0]] as $state=>$stat)
        <a href="{{ request()->fullUrlWithQuery(['status'=>$state,'page'=>1]) }}" class="sheet-customer-stat {{ request('status','all') === $state ? 'active' : '' }}"><strong>{{ number_format($stat[1],0,',','.') }}</strong><span>{{ $stat[0] }}</span></a>
        @endforeach
    </div>
    <div class="card"><div class="card-body d-flex flex-wrap justify-content-between gap-3 align-items-end">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end"><input type="hidden" name="preview" value="{{ request('preview') }}"><div><label class="form-label" for="sheet-import-search">Tên / mã khách</label><input id="sheet-import-search" name="search" value="{{ request('search') }}" maxlength="150" class="form-control"></div><div><label class="form-label" for="sheet-import-status">Phân loại</label><select id="sheet-import-status" name="status" class="form-select">@foreach(['all'=>'Tất cả','link'=>'Ghép khách hiện có','create'=>'Khách mới','update'=>'Có thay đổi','unchanged'=>'Không thay đổi','conflict'=>'Cần xử lý','imported'=>'Đã import','skip'=>'Bỏ qua','error'=>'Import lỗi'] as $value=>$label)<option value="{{ $value }}" @selected(request('status','all') === $value)>{{ $label }}</option>@endforeach</select></div><button class="btn btn-primary">Lọc</button></form>
        <form method="POST" action="{{ route('customers.sheet-import.commit') }}" data-sheet-import-commit>@csrf<input type="hidden" name="preview" value="{{ request('preview') }}"><button class="btn btn-primary" @disabled($readyCount === 0)>{{ $syncMode ? 'Áp dụng đồng bộ' : 'Import' }} {{ $readyCount }} khách đã sẵn sàng</button></form>
    </div>
        <div class="small text-muted px-3 pb-3">Nút import xử lý toàn bộ khách đã sẵn sàng trong bản xem trước, gồm cả các trang khác. Các dòng “Cần xử lý” và “Bỏ qua” được giữ lại. @if(!$canCreate)Tài khoản chưa có quyền tạo khách mới; chỉ thực hiện ghép khách hiện có. @endif</div>
        <div class="table-responsive sheet-customer-scroll"><table class="table table-hover mb-0"><thead><tr><th>Khách trong Sheet</th><th>NVKD nguồn</th><th>Khách hệ thống / Khách mới</th><th>Trạng thái</th><th></th></tr></thead><tbody>
        @forelse($rows as $row)
        <tr><td><strong>{{ $row['name'] }}</strong><div class="small text-muted">{{ $row['code'] ?: 'Chưa có mã KH' }}</div></td><td>{{ $row['sale_name'] ?: 'Chưa xác định' }}</td><td>
            @if($row['target'])<strong>{{ $row['target']->name }}</strong><div class="small text-muted">#{{ $row['target']->id }} · {{ $row['target']->customer_code ?: 'Chưa có mã' }}</div>
            @elseif($row['status'] === 'create')<strong>{{ $row['new_name'] }}</strong><div class="small text-muted">{{ $row['new_code'] ?: 'Chưa có mã' }} · {{ $sales->firstWhere('id',$row['sale_id'])?->short_name ?: ($sales->firstWhere('id',$row['sale_id'])?->name ?? 'Chưa phân công sale') }}</div>
            @else<span class="text-muted">Chưa chọn khách</span>@endif
        </td><td><span class="badge {{ ['link'=>'bg-info text-dark','create'=>'bg-success','update'=>'bg-warning text-dark','unchanged'=>'bg-secondary','conflict'=>'bg-warning text-dark','imported'=>'bg-primary','skip'=>'bg-secondary'][$row['status']] }}">{{ ['link'=>'Ghép khách hiện có','create'=>'Tạo khách mới','update'=>'Cập nhật thông tin','unchanged'=>'Không thay đổi','conflict'=>'Cần xử lý','imported'=>'Đã import','skip'=>'Bỏ qua'][$row['status']] }}</span><div class="small text-muted mt-1">{{ $row['reason'] }}</div>
            @if($row['changes'])<details class="small mt-2"><summary>{{ count($row['changes']) }} trường thay đổi</summary><dl class="mb-0 mt-1">@foreach($row['changes'] as $field=>$change)<dt>{{ \App\Services\GoogleSheetCustomerImportService::SYNC_FIELDS[$field] }}</dt><dd class="mb-1"><span class="text-muted">{{ $field === 'sale_id' ? ($sales->firstWhere('id',$change['before'])?->name ?? 'Chưa phân công') : ($change['before'] ?: 'Trống') }}</span> → <strong>{{ $field === 'sale_id' ? ($sales->firstWhere('id',$change['after'])?->name ?? $change['after']) : $change['after'] }}</strong></dd>@endforeach</dl></details>@endif
            @if($row['status'] === 'update' && !$row['can_update'])<div class="small text-danger">Chưa có quyền sửa khách này.</div>@endif
            @if(!empty($row['result']) && !$row['result']['ok'])<div class="small text-danger mt-1">{{ $row['result']['message'] }}</div>@endif</td>
        <td>@if($row['status'] !== 'imported')<button type="button" class="btn btn-sm btn-outline-primary sheet-customer-choice" data-key="{{ $row['key'] }}" data-source-name="{{ $row['name'] }}" data-name="{{ $row['new_name'] }}" data-code="{{ $row['new_code'] }}" data-sale="{{ $row['sale_id'] }}" data-customer="{{ $row['target']?->id }}" data-target="{{ $row['target']?->name }}" data-status="{{ $row['status'] }}" data-phone="{{ $row['phone'] }}" data-address="{{ $row['address'] }}" data-revision="{{ $row['target_revision'] }}" data-sync-fields="{{ json_encode($row['sync_preferences']) }}" data-can-update="{{ $row['can_update'] ? '1' : '0' }}">Chọn ghép / tạo</button>@endif</td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-4">Không có khách phù hợp.</td></tr>@endforelse
        </tbody></table></div><div class="card-body">{{ $rows->links() }}</div>
    </div>
    <dialog id="sheet-customer-dialog" aria-labelledby="sheet-customer-dialog-title"><form action="{{ route('customers.sheet-import.decide') }}" method="POST">@csrf<input type="hidden" name="preview" value="{{ request('preview') }}"><input type="hidden" name="source_key"><input type="hidden" name="customer_id"><input type="hidden" name="customer_revision">
        <div class="d-flex justify-content-between align-items-center mb-3"><h4 id="sheet-customer-dialog-title" class="mb-0">Ghép / tạo khách hàng</h4><button class="btn btn-sm btn-light" type="button" data-choice-close aria-label="Đóng">×</button></div>
        <strong data-choice-source></strong>
        <label class="form-label d-block mt-3" for="sheet-customer-action">Cách xử lý</label><select id="sheet-customer-action" name="action" class="form-select mb-3"><option value="link">Chỉ ghép / giữ nguyên thông tin khách</option>@if($syncMode)<option value="update">Cập nhật khách từ Sheet</option>@endif
            @if($canCreate)<option value="create">Tạo khách mới</option>@endif<option value="skip">Bỏ qua khách này</option><option value="auto">Đối chiếu tự động</option></select>
        <section data-choice-link><div class="small mb-2">Đã chọn: <strong data-choice-selected>Chưa chọn</strong></div><label for="sheet-customer-find" class="form-label">Tìm khách hệ thống</label><input id="sheet-customer-find" class="form-control" maxlength="150" placeholder="Tên, mã hoặc điện thoại"><div data-choice-customers class="my-2"></div><div class="d-flex justify-content-between align-items-center mb-3"><button class="btn btn-sm btn-light" data-choice-prev type="button">Trước</button><span class="small" data-choice-page></span><button class="btn btn-sm btn-light" data-choice-next type="button">Sau</button></div></section>
        @if($syncMode)<section data-choice-sync hidden><div class="small text-muted mb-2">Chọn trường cập nhật từ nguồn. Sau khi lưu lựa chọn, xem giá trị cũ → mới trên danh sách trước khi áp dụng.</div><div class="d-flex flex-wrap gap-3 mb-3">@foreach(\App\Services\GoogleSheetCustomerImportService::SYNC_FIELDS as $field=>$label)<label class="small"><input type="checkbox" name="sync_fields[]" value="{{ $field }}" @disabled($field === 'sale_id' && !$canReassign)> {{ $label }}</label>@endforeach</div></section>@endif
        <section data-choice-create hidden><label class="form-label" for="sheet-customer-new-name">Tên khách mới *</label><input id="sheet-customer-new-name" name="name" class="form-control mb-2" maxlength="255"><label class="form-label" for="sheet-customer-new-code">Mã khách</label><input id="sheet-customer-new-code" name="customer_code" class="form-control mb-2" maxlength="255"><label class="form-label" for="sheet-customer-new-phone">Điện thoại</label><input id="sheet-customer-new-phone" name="phone" class="form-control mb-2" maxlength="30"><label class="form-label" for="sheet-customer-new-address">Địa chỉ</label><input id="sheet-customer-new-address" name="address" class="form-control mb-2" maxlength="255"><label class="form-label" for="sheet-customer-sale">Sale phụ trách</label><select id="sheet-customer-sale" name="sale_id" class="form-select mb-3"><option value="">Chưa phân công</option>@foreach($sales as $sale)<option value="{{ $sale->id }}">{{ $sale->short_name ?: $sale->name }}</option>@endforeach</select></section>
        <div class="alert alert-danger py-2" role="alert" data-choice-error hidden></div><div class="text-end mt-3"><button class="btn btn-primary" type="submit">Lưu lựa chọn</button></div>
    </form></dialog>
    @else
        <div class="card"><div class="card-body py-4 text-muted">Bấm “Đồng bộ thay đổi từ Google Sheet” để xem khách mới và thông tin cần cập nhật. Hệ thống chưa tạo hoặc sửa khách hàng ở bước đọc dữ liệu.</div></div>
    @endif
</div>
@endsection
@push('styles')
<style>
.sheet-customer-page {padding:24px;max-width:1600px;margin:auto;color:#19324d;min-width:0;}
.sheet-customer-page h3 {font-size:23px;font-weight:700;}
.sheet-customer-stats {display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;}
.sheet-customer-stat {background:#fff;border:1px solid #dbe3ee;border-radius:8px;padding:12px;color:#19324d;display:flex;flex-direction:column;gap:4px;font-size:12px;}
.sheet-customer-stat strong {font-size:23px;}.sheet-customer-stat.active {border-color:#1688ff;background:#f0f7ff;}
.sheet-customer-scroll {max-height:65vh;}.sheet-customer-scroll th {position:sticky;top:0;background:#f4f7fb;z-index:1;font-size:12px;}.sheet-customer-scroll td {vertical-align:top;min-width:125px;font-size:13px;}
#sheet-customer-dialog {margin:auto;width:min(640px,95vw);max-height:90vh;overflow:auto;border:1px solid #dbe3ee;border-radius:12px;padding:24px;color:#19324d;}
#sheet-customer-dialog::backdrop {background:rgba(15,23,42,.45);}
.sheet-customer-result {display:flex;justify-content:space-between;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid #e2e8f0;}
.sheet-customer-page select {display:block!important;}.sheet-customer-page select + .nice-select {display:none!important;}
@media(max-width:767px){.sheet-customer-page {padding:12px;}.sheet-customer-stats {grid-template-columns:repeat(2,minmax(0,1fr));}}
</style>
@endpush
@push('scripts')
<script>
(() => {
    document.querySelectorAll('[data-sheet-import-loading]').forEach(form => form.addEventListener('submit', () => { const button=form.querySelector('button'); button.disabled=true;button.textContent='Đang xử lý…'; }));
    const commitForm=document.querySelector('[data-sheet-import-commit]');
    if(commitForm)commitForm.addEventListener('submit',async event=>{
        event.preventDefault();const button=commitForm.querySelector('button');if(button.disabled)return;
        button.disabled=true;let completed=0;
        try{
            while(true){
                button.textContent='Đang import · đã xử lý '+completed+' khách…';
                const response=await fetch(commitForm.action,{method:'POST',body:new FormData(commitForm),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
                const result=await response.json();
                if(!response.ok)throw new Error(result.message||'Không import được. Tải lại trang để xem kết quả các khách đã xử lý.');
                completed+=result.counts.created+result.counts.linked+(result.counts.updated||0);
                if(result.remaining===0||result.counts.errors>0){window.location.assign(result.url);break;}
                if(result.counts.created+result.counts.linked+(result.counts.updated||0)===0)throw new Error('Không có khách nào được xử lý. Vui lòng tải lại danh sách.');
            }
        }catch(failure){button.disabled=false;button.textContent='Thử tiếp các khách chưa import';let message=document.querySelector('[data-import-batch-error]');if(!message){message=document.createElement('div');message.className='alert alert-danger';message.dataset.importBatchError='';commitForm.closest('.card').before(message);}message.textContent=failure.message;}
    });
    const dialog=document.getElementById('sheet-customer-dialog'); if(!dialog)return;
    const form=dialog.querySelector('form'), action=form.elements.action, save=form.querySelector('[type=submit]'), close=dialog.querySelector('[data-choice-close]'), error=dialog.querySelector('[data-choice-error]'), search=dialog.querySelector('#sheet-customer-find');
    let page=1,lastPage=1,abort,sequence=0,timer;
    const refreshFields=()=>{
        const creating=action.value==='create',updating=action.value==='update',linking=action.value==='link'||updating;
        dialog.querySelector('[data-choice-create]').hidden=!creating;dialog.querySelector('[data-choice-link]').hidden=!linking;
        dialog.querySelectorAll('[data-choice-create] input,[data-choice-create] select').forEach(input=>input.disabled=!creating);
        form.elements.name.required=creating;form.elements.customer_id.disabled=!linking;form.elements.customer_revision.disabled=!updating;
        const sync=dialog.querySelector('[data-choice-sync]');if(sync){sync.hidden=!updating;sync.querySelectorAll('input').forEach(input=>input.disabled=!updating||(input.value==='sale_id'&&!@json($canReassign)));}
    };
    const lookup=async()=>{
        if(abort)abort.abort(); abort=new AbortController(); const current=++sequence;
        const results=dialog.querySelector('[data-choice-customers]');results.textContent='Đang tìm…';
        try {
            const url=new URL(@json(route('customers.sheet-import.customers')),window.location.origin);url.search=new URLSearchParams({search:search.value.trim(),page});
            const response=await fetch(url,{signal:abort.signal,headers:{Accept:'application/json'}});const data=await response.json();
            if(!response.ok)throw new Error(data.message || 'Không tải được danh sách khách.');
            if(current!==sequence || !dialog.open)return;
            results.replaceChildren();lastPage=data.last_page;
            data.customers.forEach(customer=>{
                const row=document.createElement('div');row.className='sheet-customer-result';
                const name=document.createElement('span');name.textContent=customer.name+' · '+(customer.customer_code || '#'+customer.id)+(customer.phone ? ' · '+customer.phone : '');
                const button=document.createElement('button');button.type='button';button.className='btn btn-sm btn-outline-primary';button.textContent='Chọn';button.addEventListener('click',()=>{form.elements.customer_id.value=customer.id;form.elements.customer_revision.value=customer.revision||'';dialog.querySelector('[data-choice-selected]').textContent=customer.name;});
                row.append(name,button);results.append(row);
            });
            if(!data.customers.length)results.textContent='Không tìm thấy khách.';
            dialog.querySelector('[data-choice-page]').textContent='Trang '+data.page+'/'+data.last_page+' · '+data.total+' khách';
            dialog.querySelector('[data-choice-prev]').disabled=page<=1;dialog.querySelector('[data-choice-next]').disabled=page>=lastPage;
        }catch(failure){if(failure.name!=='AbortError'&&current===sequence)results.textContent=failure.message;}
    };
    document.querySelectorAll('.sheet-customer-choice').forEach(button=>button.addEventListener('click',()=>{
        form.reset();error.hidden=true;form.elements.source_key.value=button.dataset.key;form.elements.customer_id.value=button.dataset.customer;form.elements.customer_revision.value=button.dataset.revision;
        dialog.querySelectorAll('[data-choice-sync] input').forEach(input=>input.checked=JSON.parse(button.dataset.syncFields||'[]').includes(input.value));
        dialog.querySelector('[data-choice-source]').textContent=button.dataset.sourceName;
        dialog.querySelector('[data-choice-selected]').textContent=button.dataset.target || 'Chưa chọn';
        form.elements.name.value=button.dataset.name;form.elements.customer_code.value=button.dataset.code;form.elements.phone.value=button.dataset.phone;form.elements.address.value=button.dataset.address;form.elements.sale_id.value=button.dataset.sale;
        action.value=button.dataset.status==='update'&&button.dataset.canUpdate==='1' ? 'update' : button.dataset.status==='create'&&@json($canCreate) ? 'create' : (button.dataset.status==='skip' ? 'skip' : 'link');
        refreshFields();page=1;search.value='';dialog.showModal();lookup();
    }));
    action.addEventListener('change',refreshFields);close.addEventListener('click',()=>dialog.close());
    dialog.addEventListener('close',()=>{if(abort)abort.abort();sequence++;});
    dialog.addEventListener('cancel',event=>{if(save.disabled)event.preventDefault();});
    search.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(()=>{page=1;lookup();},300);});
    dialog.querySelector('[data-choice-prev]').addEventListener('click',()=>{if(page>1){page--;lookup();}});
    dialog.querySelector('[data-choice-next]').addEventListener('click',()=>{if(page<lastPage){page++;lookup();}});
    form.addEventListener('submit',async event=>{
        event.preventDefault();if(save.disabled||!form.reportValidity())return;
        if(['link','update'].includes(action.value)&&!form.elements.customer_id.value){error.textContent='Hãy chọn một khách trong danh sách.';error.hidden=false;return;}
        save.disabled=true;close.disabled=true;error.hidden=true;
        try {const response=await fetch(form.getAttribute('action'),{method:'POST',body:new FormData(form),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});const result=await response.json();if(!response.ok)throw new Error(Object.values(result.errors||{}).flat()[0]||result.message||'Không lưu được lựa chọn.');window.location.reload();}
        catch(failure){error.textContent=failure.message;error.hidden=false;save.disabled=false;close.disabled=false;}
    });
})();
</script>
@endpush
