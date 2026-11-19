<dialog id="accounting-policy-dialog" class="debt-balance-dialog" aria-labelledby="accounting-policy-title">
    <form method="POST" action="{{ ($accountingDebt ?? false) ? accounting_route('customer-debts.policy') : '' }}">
        @csrf
        <div class="d-flex justify-content-between gap-3 mb-3"><h5 id="accounting-policy-title">Chính sách thanh toán</h5><button type="button" class="btn btn-sm btn-light" data-policy-close>Đóng</button></div>
        <strong data-policy-name></strong>
        <input type="hidden" name="source_key"><input type="hidden" name="policy_version">
        <label class="form-label mt-3" for="acc-payment-mode">Phân loại thanh toán</label>
        <select class="form-select" id="acc-payment-mode" name="payment_mode"><option value="default">Mặc định</option><option value="cycle">Thanh toán theo chu kỳ</option></select>
        <div data-cycle-fields>
        <label class="form-label mt-3" for="acc-cycle-type">Lịch thanh toán</label><select class="form-select" id="acc-cycle-type" name="cycle_type"><option value="weekly">Hằng tuần</option><option value="monthly">Hằng tháng</option></select>
        <div data-weekly-fields>
        <label class="form-label mt-2" for="acc-close-weekday">Thứ chốt công nợ</label><select class="form-select" id="acc-close-weekday" name="cycle_close_weekday">@foreach([1=>'Thứ hai',2=>'Thứ ba',3=>'Thứ tư',4=>'Thứ năm',5=>'Thứ sáu',6=>'Thứ bảy',7=>'Chủ nhật'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        <label class="form-label mt-2" for="acc-weekday">Thứ thanh toán</label><select class="form-select" id="acc-weekday" name="cycle_weekday">@foreach([1=>'Thứ hai',2=>'Thứ ba',3=>'Thứ tư',4=>'Thứ năm',5=>'Thứ sáu',6=>'Thứ bảy',7=>'Chủ nhật'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        </div><div data-monthly-fields>
        <label class="form-label mt-2" for="acc-close-monthday">Ngày chốt công nợ trong tháng</label><input class="form-control" id="acc-close-monthday" name="cycle_close_monthday" type="number" min="1" max="31">
        <label class="form-label mt-2" for="acc-monthday">Ngày thanh toán trong tháng</label><input class="form-control" id="acc-monthday" name="cycle_monthday" type="number" min="1" max="31">
        </div>
        <p class="small text-muted mt-2">Công nợ phát sinh đến hết ngày chốt thuộc kỳ này; sau ngày chốt thuộc kỳ tiếp theo. Nếu ngày thanh toán đứng trước ngày chốt, thanh toán vào tuần/tháng kế tiếp. Tháng thiếu ngày dùng ngày cuối tháng.</p>
        </div>
        <p class="small text-muted mt-3" data-default-help>Để trống công nợ cố định để tiếp tục dùng tiêu chí số Bill hiện có.</p>
        <label class="form-label mt-3" for="acc-fixed-debt">Công nợ cố định (đ)</label><input class="form-control" id="acc-fixed-debt" name="fixed_debt" type="number" min="0" max="999999999999999">
        <label class="form-label mt-3" for="acc-limit">Trần công nợ (đ)</label><input class="form-control" id="acc-limit" name="credit_limit" type="number" min="0" max="999999999999999" required>
        <label class="form-label mt-3" for="acc-threshold-days">Số ngày cho nợ khi vượt ngưỡng</label><input class="form-control" id="acc-threshold-days" name="threshold_days" type="number" min="0" max="3650">
        <p class="small text-muted">Ngưỡng cảnh báo bằng công nợ cố định cộng trần công nợ. {{ ($accountingDebt ?? false) ? 'Kế toán có thể cập nhật lại; mọi thay đổi được lưu lịch sử.' : 'Sale được lưu một lần rồi khóa. Admin có thể mở khóa để sửa thêm một lần.' }}</p>
        <div data-policy-error class="text-danger small" role="alert"></div>
        <div class="text-end mt-3"><button class="btn btn-primary" type="submit">Lưu & cập nhật cảnh báo</button></div>
    </form>
</dialog>
@push('scripts')
<script>
(() => {
    const dialog=document.getElementById('accounting-policy-dialog'),form=dialog.querySelector('form');
    const updateMode=()=>{
        const cycle=form.elements.payment_mode.value==='cycle';
        const weekly=form.elements.cycle_type.value==='weekly';
        dialog.querySelector('[data-cycle-fields]').hidden=!cycle;
        dialog.querySelector('[data-default-help]').hidden=cycle;
        dialog.querySelector('[data-weekly-fields]').hidden=!weekly;
        dialog.querySelector('[data-monthly-fields]').hidden=weekly;
        form.elements.cycle_type.disabled=!cycle;
        ['cycle_weekday','cycle_close_weekday'].forEach(key=>{form.elements[key].disabled=!cycle||!weekly;form.elements[key].required=cycle&&weekly;});
        ['cycle_monthday','cycle_close_monthday'].forEach(key=>{form.elements[key].disabled=!cycle||weekly;form.elements[key].required=cycle&&!weekly;});
    };
    document.querySelectorAll('[data-accounting-policy]').forEach(button=>button.addEventListener('click',()=>{
        const policy=JSON.parse(button.dataset.policy);
        if(button.dataset.updateUrl) form.action=button.dataset.updateUrl;
        form.dataset.salePolicy=button.dataset.updateUrl ? '1' : '';
        dialog.querySelector('[data-policy-error]').textContent='';
        form.elements.source_key.value=button.dataset.key;
        form.elements.policy_version.value=policy.edit_version;
        ['payment_mode','cycle_type','cycle_weekday','cycle_monthday','credit_limit','fixed_debt','threshold_days'].forEach(key=>form.elements[key].value=policy[key] ?? '');
        form.elements.cycle_close_weekday.value=policy.cycle_close_weekday ?? policy.cycle_weekday;
        form.elements.cycle_close_monthday.value=policy.cycle_close_monthday ?? policy.cycle_monthday;
        dialog.querySelector('[data-policy-name]').textContent=button.dataset.name;
        updateMode();dialog.showModal();
    }));
    form.elements.cycle_type.addEventListener('change',updateMode);
    form.elements.payment_mode.addEventListener('change',()=>{
        updateMode();
    });
    form.addEventListener('submit',async event=>{
        if(form.dataset.salePolicy!=='1')return;
        event.preventDefault();
        const save=form.querySelector('[type=submit]');if(save.disabled)return;
        save.disabled=true;
        try{
            const data=new FormData(form);data.set('_method','PATCH');
            const response=await fetch(form.action,{method:'POST',body:data,headers:{Accept:'application/json'}});
            const result=await response.json();
            if(!response.ok)throw new Error(Object.values(result.errors||{}).flat()[0]||result.message||'Không lưu được chính sách.');
            window.location.reload();
        }catch(error){dialog.querySelector('[data-policy-error]').textContent=error.message;save.disabled=false;}
    });
    document.querySelectorAll('[data-unlock-sale-policy]').forEach(button=>button.addEventListener('click',async()=>{
        button.disabled=true;
        try{
            const response=await fetch(button.dataset.url,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':form.querySelector('[name=_token]').value},body:JSON.stringify({policy_version:Number(button.dataset.version)})});
            const result=await response.json();
            if(!response.ok)throw new Error(Object.values(result.errors||{}).flat()[0]||result.message||'Không mở khóa được.');
            window.location.reload();
        }catch(error){button.disabled=false;button.parentElement.querySelector('[role=status]').textContent=error.message;}
    }));
    dialog.querySelector('[data-policy-close]').addEventListener('click',()=>dialog.close());
})();
</script>
@endpush
