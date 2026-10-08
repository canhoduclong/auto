@extends('layouts.admin')
@section('title', 'Quản lý quy trình & luồng xử lý')

@push('styles')
<style>
.process-designer{width:100%;padding:24px;color:#24364b;font-size:14px}.designer-header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px}.designer-header h1{font-size:26px;font-weight:700;margin:0 0 8px}.designer-muted{color:#738298;font-size:13px;line-height:1.6}.designer-header p{margin:0}.designer-panel{background:#fff;border:1px solid #dfe6ef;border-radius:8px;box-shadow:0 3px 14px rgba(28,45,69,.035);overflow:hidden;margin-bottom:24px}.designer-panel-heading{padding:20px 24px;border-bottom:1px solid #e6ecf3;display:flex;align-items:center;justify-content:space-between;gap:16px}.designer-panel-heading h2{font-size:18px;font-weight:650;margin:0 0 6px}.designer-tag{display:inline-block;font-size:12px;font-weight:600;padding:5px 9px;border-radius:4px;background:#edf3fa;color:#42678c;white-space:nowrap}.designer-tag.active{background:#eaf6ef;color:#27734c}.designer-tag.inactive{background:#f1f3f6;color:#6d7786}.designer-layout{display:grid;grid-template-columns:minmax(0,1fr) 280px}.designer-main{padding:24px;min-width:0}.designer-aside{background:#f8fafc;border-left:1px solid #e6ecf3;padding:24px}.designer-section-title{display:flex;align-items:center;gap:10px;font-size:16px;font-weight:650;margin:0 0 18px}.designer-section-title span{background:#eaf1fb;color:#3267a0;width:26px;height:26px;display:inline-flex;justify-content:center;align-items:center;border-radius:5px;font-size:12px}.designer-fields{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:28px}.process-designer label{font-size:13px;font-weight:600;margin-bottom:8px;color:#45566b}.process-designer .form-control,.process-designer .form-select{min-height:42px;font-size:14px;border:1px solid #d8e1ed;max-width:100%}.designer-section-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px}.designer-section-heading .designer-section-title{margin-bottom:0}.designer-step{border:1px solid #dde6ef;border-radius:6px;padding:16px;background:#fff;margin-bottom:12px}.designer-step-heading{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}.designer-step-index{font-size:13px;font-weight:700;color:#3267a0}.designer-step-fields{display:grid;grid-template-columns:1.1fr .85fr 1.1fr;gap:16px}.designer-remove{border:0;background:transparent;color:#a45454;font-size:12px;padding:3px 6px}.designer-remove:hover{background:#fff1f1;color:#a52424}.designer-remove:disabled{opacity:.35;cursor:not-allowed}.designer-actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.designer-action{background:#f8fafc;border:1px solid #e3eaf2;padding:12px;border-radius:5px}.designer-start,.designer-end{padding:12px 16px;background:#f2f7fc;border:1px dashed #cddce9;border-radius:6px;color:#526b85;font-size:13px}.designer-start{margin-bottom:12px}.designer-end{margin-top:12px}.designer-aside h3{font-size:14px;font-weight:650;margin:0 0 14px}.designer-aside section+section{border-top:1px solid #e0e7ef;margin-top:22px;padding-top:22px}.designer-policy{margin:0;padding-left:17px;color:#64758b;font-size:13px;line-height:1.7}.designer-policy li+li{margin-top:10px}.designer-aside .form-check-label{font-size:14px}.designer-footer{border-top:1px solid #e6ecf3;padding:18px 24px;display:flex;justify-content:space-between;align-items:center;gap:20px;background:#fcfdff}.process-designer .btn{font-size:13px;font-weight:600;white-space:nowrap}.process-designer .btn-outline-primary{color:#2267a5;border-color:#bdcede}.process-designer .btn-outline-primary:hover{color:#fff;background:#2267a5}.designer-empty{padding:32px;text-align:center;color:#738298}@media(max-width:1199px){.designer-layout{grid-template-columns:1fr}.designer-aside{border-left:0;border-top:1px solid #e6ecf3;display:grid;grid-template-columns:1fr 1fr;gap:24px}.designer-aside section+section{margin:0;padding:0;border:0}}@media(max-width:767px){.process-designer{padding:16px 10px}.designer-header{flex-wrap:wrap}.designer-header h1{font-size:22px}.designer-panel-heading,.designer-main,.designer-aside,.designer-footer{padding:16px}.designer-actions,.designer-fields,.designer-step-fields,.designer-aside{grid-template-columns:1fr}.designer-footer{align-items:stretch;flex-direction:column}.designer-panel-heading{align-items:flex-start;flex-wrap:wrap}.process-designer .form-control,.process-designer .form-select{font-size:16px}.designer-section-heading{flex-wrap:wrap}}
</style>
@endpush

@section('content')
@php
    $roleLabels = ['manager_shipper'=>'Điều phối', 'shipper'=>'Shipper', 'accountant'=>'Kế toán', 'account'=>'Kế toán', 'accounting'=>'Kế toán', 'leader'=>'Trưởng nhóm', 'manager'=>'Quản lý', 'warehouse'=>'Kho', 'director'=>'Giám đốc', 'sale'=>'Sale', 'admin'=>'Quản trị'];
@endphp
<div class="container-fluid process-designer">
    <header class="designer-header">
        <div><h1>Quản lý quy trình & luồng xử lý</h1><p class="designer-muted">Thiết lập hoạt động, thứ tự các bước và người chịu trách nhiệm xử lý.</p></div>
        <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="{{ route('process-management.create') }}">+ Tạo quy trình</a><a class="btn btn-outline-primary" href="{{ route('process-management.runs') }}">Theo dõi luồng xử lý</a></div>
    </header>
    @include('processes.messages')
    @forelse($definitions as $definition)
        @php
            $formOld=fn($key,$fallback=null)=>(($creating??false)||(string)old('_definition_id')===(string)$definition->id)?old($key,$fallback):$fallback;
        @endphp
        <form method="POST" action="{{ ($creating??false)?route('process-management.store'):route('process-management.save', $definition) }}" class="designer-panel" id="process-{{ $definition->id }}">
            @csrf
            <input type="hidden" name="_definition_id" value="{{ $definition->id }}">
            @if(!($creating??false))@method('PUT')<input type="hidden" name="version" value="{{ $definition->version }}">@endif
            <header class="designer-panel-heading">
                <div><h2>{{ $definition->name }}</h2><div class="designer-muted">Mã quy trình: {{ $definition->code }}</div></div>
                <div class="d-flex flex-wrap gap-2"><span class="designer-tag">Phiên bản {{ $definition->version }}</span><span class="designer-tag {{ $definition->is_active?'active':'inactive' }}">{{ $definition->is_active?'Đang áp dụng':'Chưa kích hoạt' }}</span></div>
            </header>
            <div class="designer-layout">
                <main class="designer-main">
                    <h3 class="designer-section-title"><span>1</span> Thông tin quy trình</h3>
                    <div class="designer-fields">
                        @if($creating??false)<div><label>Mã quy trình</label><input name="code" class="form-control" value="{{ $formOld('code') }}" maxlength="100" required placeholder="Ví dụ: duyet_ho_so_don"></div>@endif
                        <div><label for="process-name-{{ $definition->id }}">Tên quy trình</label><input id="process-name-{{ $definition->id }}" class="form-control" name="name" value="{{ $formOld('name', $definition->name) }}" maxlength="255" required></div>
                        <div><label>Hoạt động áp dụng</label><select name="activity" class="form-select" data-activity @disabled(!($creating??false))>@foreach(\App\Support\ProcessActivities::all() as $key=>$activity)<option value="{{ $key }}" @selected($formOld('activity',$definition->activity)===$key)>{{ $activity['label'] }}</option>@endforeach</select></div>
                        <div><label>Vai trò khởi tạo</label><select name="initiator_role" class="form-select" required>@foreach($roles as $role)<option value="{{ $role->name }}" @selected(strtolower($formOld('initiator_role',$definition->configuration['initiator_role']))===strtolower($role->name))>{{ $roleLabels[strtolower($role->name)] ?? $role->name }}</option>@endforeach</select></div>
                    </div>
                    <div class="designer-section-heading"><h3 class="designer-section-title"><span>2</span> Các bước xử lý</h3><button type="button" class="btn btn-sm btn-outline-primary" onclick="addProcessStep(this)">+ Thêm bước</button></div>
                    <div class="designer-start">Khởi tạo yêu cầu · <strong>Người có vai trò khởi tạo được chọn</strong></div>
                    <div data-process-steps>
                        @foreach($formOld('steps', $definition->configuration['steps']) as $step)
                            <section class="designer-step process-step">
                                <div class="designer-step-heading"><span class="designer-step-index" data-step-number>Bước {{ $loop->iteration }}</span><button type="button" class="designer-remove" onclick="removeProcessStep(this)" @disabled(count($formOld('steps', $definition->configuration['steps']))<=1)>Xóa bước</button></div>
                                @php
                                    $mode=$step['assignment_mode']??'user_role';
                                    $actionOptions=$step['actions']??['approve'=>['label'=>$loop->last?'Hoàn tất':'Xác nhận'],'revise'=>['label'=>'Yêu cầu bổ sung','note_required'=>true],'reject'=>['label'=>'Từ chối','note_required'=>true]];
                                @endphp
                                <div class="designer-step-fields">
                                    <div><label>Tên bước</label><input name="steps[{{ $loop->index }}][name]" class="form-control" value="{{ $step['name'] }}" maxlength="100" required></div>
                                    <div><label>Cách giao người xử lý</label><select name="steps[{{ $loop->index }}][assignment_mode]" class="form-select" data-assignment-mode>@foreach(['user'=>'User cụ thể','role'=>'Theo vai trò','user_role'=>'User có vai trò chỉ định'] as $key=>$label)<option value="{{ $key }}" @selected($mode===$key)>{{ $label }}</option>@endforeach</select></div>
                                    <div><label>Vai trò xử lý</label><select name="steps[{{ $loop->index }}][role]" class="form-select" data-assignment-role><option value="">Chọn vai trò</option>@foreach($roles as $role)<option value="{{ $role->name }}" @selected(($step['role']??null)===$role->name)>{{ $roleLabels[strtolower($role->name)] ?? $role->name }}</option>@endforeach</select></div>
                                    <div><label>Người thực hiện</label><select name="steps[{{ $loop->index }}][user_id]" class="form-select" data-assignment-user><option value="">Chọn người thực hiện</option>@foreach($users as $user)<option value="{{ $user->id }}" data-roles="{{ $user->roles->pluck('name')->map(fn($r)=>strtolower($r))->toJson() }}" @selected((int)($step['user_id']??0)===(int)$user->id)>{{ $user->name }}</option>@endforeach</select></div>
                                    <div><label>Sau khi bổ sung</label><select name="steps[{{ $loop->index }}][revision_resume]" class="form-select"><option value="current" @selected(($step['revision_resume']??'current')==='current')>Quay lại bước yêu cầu sửa</option><option value="first" @selected(($step['revision_resume']??'current')==='first')>Duyệt lại từ bước đầu</option></select></div>
                                </div>
                                <div class="mt-3"><label>Thao tác được phép</label><div class="designer-actions">
                                @foreach(['approve'=>'Xác nhận / Hoàn tất','revise'=>'Yêu cầu bổ sung','reject'=>'Từ chối'] as $key=>$label)
                                    @php $option=$actionOptions[$key]??[]; @endphp
                                    <div class="designer-action">
                                        <input type="hidden" name="steps[{{ $loop->parent->index }}][actions][{{ $key }}][enabled]" value="0"><label><input type="checkbox" name="steps[{{ $loop->parent->index }}][actions][{{ $key }}][enabled]" value="1" @checked($option['enabled']??isset($actionOptions[$key]))> {{ $label }}</label>
                                        <input class="form-control mb-2" name="steps[{{ $loop->parent->index }}][actions][{{ $key }}][label]" value="{{ $option['label']??$label }}" maxlength="60" aria-label="Nhãn nút {{ $label }}">
                                        <label class="d-block small"><input type="checkbox" name="steps[{{ $loop->parent->index }}][actions][{{ $key }}][note_required]" value="1" @checked($option['note_required']??in_array($key,['revise','reject']))> Bắt buộc diễn giải</label>
                                        <label class="d-block small"><input type="checkbox" name="steps[{{ $loop->parent->index }}][actions][{{ $key }}][document_required]" value="1" @checked($option['document_required']??false)> Bắt buộc tài liệu</label>
                                    </div>
                                @endforeach
                                </div><div class="designer-muted mt-2">Xác nhận chuyển bước tiếp theo; ở bước cuối sẽ hoàn tất. Từ chối kết thúc hồ sơ. Yêu cầu bổ sung luôn cần lý do.</div></div>
                            </section>
                        @endforeach
                    </div>
                    <div class="designer-end">Kết thúc quy trình · <strong>Người xử lý cuối xác nhận hoàn tất</strong></div>
                </main>
                <aside class="designer-aside">
                    <section>
                        <h3>Trạng thái áp dụng</h3>
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="process-active-{{ $definition->id }}" name="is_active" value="1" @checked($formOld('is_active', $definition->is_active))><label class="form-check-label" for="process-active-{{ $definition->id }}">Kích hoạt quy trình</label></div>
                        <p class="designer-muted mb-0">Cho phép tạo yêu cầu mới theo quy trình này. Hồ sơ đã khởi tạo tiếp tục xử lý theo cấu hình đã lưu.</p>
                    </section>
                    <section><h3>Hiển thị thao tác</h3>
                        @foreach(\App\Support\ProcessActivities::positions() as $key=>$label)
                        <label class="d-block mb-2" data-position="{{ $key }}"><input type="checkbox" name="positions[]" value="{{ $key }}" @checked(in_array($key,$formOld('positions',$definition->configuration['positions']??['inbox','order_list','order_detail','shipping_list'])))> {{ $label }}</label>
                        @endforeach
                        <p class="designer-muted">Các vị trí cùng mở một hồ sơ; quyền xử lý được kiểm tra theo bước hiện tại.</p>
                    </section>
                    <section>
                        <h3>Quy tắc xử lý</h3>
                        <ul class="designer-policy"><li>Chọn user cụ thể để giao cho bất kỳ tài khoản nào.</li><li>Các bước thực hiện tuần tự, không bỏ qua.</li><li>Chế độ user và vai trò yêu cầu cả hai điều kiện cùng phù hợp.</li><li>Yêu cầu bổ sung trả về người khởi tạo; gửi lại theo bước được cấu hình.</li></ul>
                    </section>
                </aside>
            </div>
            <footer class="designer-footer"><div class="designer-muted">Lưu thay đổi tạo phiên bản mới.<br>Hồ sơ đang xử lý giữ nguyên cấu hình lúc khởi tạo.</div><button class="btn btn-primary" type="submit">Lưu & công bố phiên bản mới</button></footer>
        </form>
    @empty
        <div class="designer-panel designer-empty">Chưa có quy trình được cấu hình.</div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
function renumberSteps(form) {
    const rows = form.querySelectorAll('.process-step');
    rows.forEach((row, index) => {
        row.querySelector('[data-step-number]').textContent = 'Bước ' + (index + 1);
        row.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/steps\[\d+\]/, 'steps[' + index + ']');

        });
        row.querySelector('.designer-remove').disabled = rows.length <= 1;
    });
    form.querySelector('[onclick="addProcessStep(this)"]').disabled = rows.length >= 20;
}
function removeProcessStep(button) {
    const form = button.form;
    if (form.querySelectorAll('.process-step').length <= 2) return;
    button.closest('.process-step').remove();
    renumberSteps(form);
}
function addProcessStep(button) {
    const form = button.form;
    const rows = form.querySelectorAll('.process-step');
    if (rows.length >= 20) return;
    const row = rows[rows.length - 1]?.cloneNode(true);
    if (!row) return;
    row.querySelector('input').value = '';
    form.querySelector('[data-process-steps]').appendChild(row);
    renumberSteps(form);
    syncAssignment(row);
    row.querySelector('input').focus();
}
function syncAssignment(row){const mode=row.querySelector('[data-assignment-mode]').value,role=row.querySelector('[data-assignment-role]'),user=row.querySelector('[data-assignment-user]');role.disabled=mode==='user';role.required=mode!=='user';user.disabled=mode==='role';user.required=mode!=='role';[...user.options].forEach(option=>{const allowed=mode!=='user_role'||!option.value||JSON.parse(option.dataset.roles||'[]').includes(role.value.toLowerCase());option.hidden=!allowed;option.disabled=!allowed;if(option.selected&&!allowed)user.value='';});}
function syncPositions(form){const activity=form.querySelector('[data-activity]').value;const allowed=@json(array_map(fn($a)=>$a['positions'],\App\Support\ProcessActivities::all()));form.querySelectorAll('[data-position]').forEach(label=>{const visible=allowed[activity].includes(label.dataset.position);label.hidden=!visible;label.querySelector('input').disabled=!visible;});}
document.querySelectorAll('.designer-panel').forEach(form=>{form.querySelectorAll('.process-step').forEach(syncAssignment);syncPositions(form);form.addEventListener('change',event=>{if(event.target.matches('[data-assignment-mode],[data-assignment-role]'))syncAssignment(event.target.closest('.process-step'));if(event.target.matches('[data-activity]'))syncPositions(form);});});
</script>
@endpush
