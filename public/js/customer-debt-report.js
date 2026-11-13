(() => {
    const scope=document.getElementById('collection-scope');
    const toggleDates=()=>document.querySelectorAll('#collection-from,#collection-to').forEach(input=>{input.disabled=false;input.required=scope.value==='orders';});
    scope.addEventListener('change',toggleDates);toggleDates();
    document.querySelectorAll('[data-report-sheet-refresh]').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button');button.disabled=true;button.textContent='Đang cập nhật…';}));
    document.addEventListener('click',event=>document.querySelectorAll('.collection-more[open]').forEach(menu=>{if(!menu.contains(event.target)||event.target.closest('button,a'))menu.open=false;}));
    document.querySelectorAll('[data-collection-priority]').forEach(form=>form.addEventListener('submit',async event=>{
        event.preventDefault();
        if(!event.submitter || form.dataset.saving==='1')return;
        const data=new FormData(form);data.set('stars',event.submitter.value);
        const status=form.querySelector('[role=status]');status.textContent='Đang lưu…';form.dataset.saving='1';
        const buttons=[...form.querySelectorAll('button')];buttons.forEach(button=>button.disabled=true);
        try{
            const response=await fetch(form.action,{method:'POST',body:data,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
            let result;try{result=await response.json();}catch(_){throw new Error('Không lưu được. Vui lòng tải lại trang và thử lại.');}
            if(!response.ok)throw new Error(Object.values(result.errors||{}).flat()[0]||result.message||'Không lưu được mức ưu tiên.');
            const url=new URL(window.location.href);url.searchParams.set('page','1');url.hash='';window.location.assign(url);
        }catch(error){status.textContent=error.message;form.dataset.saving='0';buttons.forEach(button=>button.disabled=false);}
    }));
    const money=value=>new Intl.NumberFormat('vi-VN').format(value);
    const parse=value=>{const text=value.trim().replace(/\s/g,'');if(!/^(?:\d+|\d{1,3}(?:\.\d{3})+)$/.test(text))return NaN;const number=Number(text.replace(/\./g,''));return Number.isSafeInteger(number)&&number>0&&number<=999999999999999?number:NaN;};
    const send=async(url,data)=>{
        const response=await fetch(url,{method:'POST',body:data,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
        let result;try{result=await response.json();}catch(_){throw new Error('Không nhận được phản hồi. Vui lòng kiểm tra kết nối hoặc đăng nhập lại.');}
        if(!response.ok)throw new Error(Object.values(result.errors||{}).flat()[0]||result.message||'Không lưu được dữ liệu.');
        return result;
    };
    const remind=document.getElementById('collection-remind-dialog'),remindForm=remind.querySelector('form'),remindSave=remindForm.querySelector('[type=submit]'),remindClose=remind.querySelector('[data-collection-close]'),remindError=remind.querySelector('[role=alert]');
    document.querySelectorAll('.collection-remind').forEach(button=>button.addEventListener('click',()=>{
        remindForm.reset();remindError.hidden=true;
        remindForm.elements.report_source.value=button.dataset.reportSource||'sheet';remindForm.elements.source_key.value=button.dataset.key;remindForm.elements.customer_id.value=button.dataset.customer;remindForm.elements.request_id.value=button.dataset.requestId;
        remindForm.elements.target_amount.value=money(Number(button.dataset.balance));remindForm.elements.target_amount.setCustomValidity('');
        remind.querySelector('[data-collection-name]').textContent=button.dataset.name;remind.showModal();
    }));
    remindClose.addEventListener('click',()=>remind.close());remind.addEventListener('cancel',event=>{if(remindSave.disabled)event.preventDefault();});
    remindForm.elements.target_amount.addEventListener('input',()=>remindForm.elements.target_amount.setCustomValidity(''));
    remindForm.addEventListener('submit',async event=>{
        event.preventDefault();const amount=parse(remindForm.elements.target_amount.value);
        remindForm.elements.target_amount.setCustomValidity(Number.isNaN(amount)?'Nhập số tiền nguyên lớn hơn 0.':'');
        if(remindSave.disabled||!remindForm.reportValidity())return;
        remindSave.disabled=true;remindClose.disabled=true;remindError.hidden=true;
        try{const data=new FormData(remindForm);data.set('target_amount',String(amount));await send(remindForm.action,data);window.location.reload();}
        catch(failure){remindError.textContent=failure.message;remindError.hidden=false;remindSave.disabled=false;remindClose.disabled=false;}
    });
    const history=document.getElementById('collection-history-dialog'),historyContent=history.querySelector('[data-history-content]'),historyError=history.querySelector('[data-history-error]'),historyClose=history.querySelector('[data-history-close]');
    let historyUrl='',abort,sequence=0,historyDirty=false,historyBusy=false;
    const loadHistory=async url=>{
        if(abort)abort.abort();abort=new AbortController();const current=++sequence;historyUrl=url;
        historyError.hidden=true;historyContent.textContent='Đang tải lịch sử…';
        try{const response=await fetch(url,{signal:abort.signal,headers:{Accept:'application/json'}});const result=await response.json();if(!response.ok)throw new Error(result.message||'Không tải được lịch sử.');if(current!==sequence||!history.open)return;historyContent.innerHTML=result.html;}
        catch(failure){if(failure.name!=='AbortError'&&current===sequence){historyContent.textContent='';historyError.textContent=failure.message;historyError.hidden=false;}}
    };
    document.querySelectorAll('.collection-history').forEach(button=>button.addEventListener('click',()=>{
        const url=new URL(history.dataset.url,window.location.origin);url.searchParams.set('report_source',button.dataset.reportSource||history.dataset.reportSource||'sheet');if(button.dataset.key)url.searchParams.set('source_key',button.dataset.key);if(button.dataset.customer)url.searchParams.set('customer_id',button.dataset.customer);
        historyDirty=false;history.querySelector('[data-history-name]').textContent=button.dataset.name;history.showModal();loadHistory(url.toString());
    }));
    historyClose.addEventListener('click',()=>history.close());history.addEventListener('cancel',event=>{if(historyBusy)event.preventDefault();});
    history.addEventListener('close',()=>{if(abort)abort.abort();sequence++;if(historyDirty)window.location.reload();});
    historyContent.addEventListener('click',event=>{const link=event.target.closest('[data-history-pagination] a');if(!link)return;event.preventDefault();if(!historyBusy)loadHistory(link.href);});
    historyContent.addEventListener('submit',async event=>{
        const form=event.target.closest('[data-collection-event]');if(!form)return;event.preventDefault();if(historyBusy||!form.reportValidity())return;
        const data=new FormData(form);const promised=form.elements.promised_amount;
        if(promised&&promised.value.trim()){
            const amount=parse(promised.value);if(Number.isNaN(amount)){promised.setCustomValidity('Nhập số tiền nguyên lớn hơn 0.');promised.reportValidity();promised.addEventListener('input',()=>promised.setCustomValidity(''),{once:true});return;}data.set('promised_amount',String(amount));
        }
        historyBusy=true;historyClose.disabled=true;historyError.hidden=true;historyContent.querySelectorAll('[type=submit]').forEach(button=>button.disabled=true);
        try{await send(form.action,data);historyDirty=true;await loadHistory(historyUrl);}
        catch(failure){historyError.textContent=failure.message;historyError.hidden=false;}
        finally{historyBusy=false;historyClose.disabled=false;historyContent.querySelectorAll('[type=submit]').forEach(button=>button.disabled=false);}
    });
})();
