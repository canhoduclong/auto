(() => {
    document.querySelectorAll('[data-payment-refresh]').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button');button.disabled=true;button.textContent='Đang tải và đối chiếu thanh toán…';}));
    const dialog=document.getElementById('payment-updates-dialog'),content=dialog.querySelector('[data-updates-content]');let abort,sequence=0;
    const load=async url=>{
        if(abort)abort.abort();abort=new AbortController();const current=++sequence;content.textContent='Đang tải lịch sử…';
        try{const response=await fetch(url,{signal:abort.signal,headers:{Accept:'application/json'}});const data=await response.json();if(!response.ok)throw new Error(data.message||'Không tải được lịch sử cập nhật.');if(current!==sequence||!dialog.open)return;content.innerHTML=data.html;}
        catch(error){if(error.name!=='AbortError'&&current===sequence)content.textContent=error.message||'Không tải được lịch sử cập nhật.';}
    };
    document.getElementById('payment-updates-open').addEventListener('click',()=>{dialog.showModal();load(dialog.dataset.url);});
    document.getElementById('payment-updates-close').addEventListener('click',()=>dialog.close());
    dialog.addEventListener('close',()=>{if(abort)abort.abort();sequence++;});
    content.addEventListener('click',event=>{const link=event.target.closest('[data-updates-pagination] a');if(link){event.preventDefault();load(link.href);}});
})();
