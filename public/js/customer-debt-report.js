(() => {
    const scope=document.getElementById('collection-scope');
    const toggleDates=()=>document.querySelectorAll('#collection-from,#collection-to').forEach(input=>{input.disabled=false;input.required=scope.value==='orders';});
    scope.addEventListener('change',toggleDates);toggleDates();
    document.querySelectorAll('[data-report-sheet-refresh]').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button');button.disabled=true;button.textContent='Đang cập nhật…';}));
    document.addEventListener('click',event=>document.querySelectorAll('.collection-more[open]').forEach(menu=>{if(!menu.contains(event.target)||(event.target.closest('button,a')&&!event.target.closest('[data-collection-priority]')))menu.open=false;}));
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
    const bulk=document.querySelector('[data-collection-bulk]');
    if(bulk){
        const boxes=[...document.querySelectorAll('[data-collection-select]:not(:disabled)')];
        const all=document.querySelector('[data-collection-select-all]'),copy=bulk.querySelector('[data-bulk-copy]'),remindSelected=bulk.querySelector('[data-bulk-remind]'),status=bulk.querySelector('[data-bulk-status]');
        let busy=false;
        const selected=()=>boxes.filter(box=>box.checked);
        const update=()=>{
            const count=selected().length;
            bulk.querySelector('[data-selection-count]').textContent=`Đã chọn ${count} khách`;
            copy.disabled=busy||!count;remindSelected.disabled=busy||!count;
            all.checked=boxes.length>0&&count===boxes.length;all.indeterminate=count>0&&count<boxes.length;all.disabled=busy||!boxes.length;
            boxes.forEach(box=>box.disabled=busy);
        };
        const daysLabel=value=>value===''?'Chưa rõ':Number(value)===0?'Hôm nay':Number(value)===1?'Hôm qua':`${value} ngày`;
        const line=box=>{
            const d=box.dataset;
            const purchase=d.purchase?`${d.purchase} (${daysLabel(d.purchaseDays)})`:'Chưa có ngày mua';
            return `${d.name}: ${money(Number(d.balance))}đ | Nợ ${daysLabel(d.paymentDays)} | Đơn mới: ${purchase}`;
        };
        boxes.forEach(box=>box.addEventListener('change',update));
        all.addEventListener('change',()=>{boxes.forEach(box=>box.checked=all.checked);update();});
        copy.addEventListener('click',async()=>{
            const targets=selected();
            const range=bulk.dataset.from&&bulk.dataset.to?`${bulk.dataset.from} – ${bulk.dataset.to}`:'';
            const title=`NHẮC CÔNG NỢ | ${range||bulk.dataset.date}`;
            const escape=value=>String(value).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            const buildContent=rich=>{
                const text=value=>rich?escape(value):String(value);
                const bold=value=>rich?`<strong>${escape(value)}</strong>`:String(value);
                const total=targets.reduce((sum,box)=>sum+Number(box.dataset.balance),0);
                const lines=[`💣💣💣 ${bold(title)} 💣💣💣`,
                    `💬 ${bold('Doanh số là thành tích – Thu tiền là kết quả!')}`,
                    'Anh em cùng chủ động theo sát công nợ để hoa hồng về đúng hẹn nhé!',
                    `📌 ${bold('Danh sách khách hàng cần theo dõi và thu hồi công nợ:')}`, ''];
                targets.forEach((box,index)=>{
                    const d=box.dataset;
                    const purchase=d.purchase?`${d.purchase} (${daysLabel(d.purchaseDays)})`:'Chưa có ngày mua';
                    lines.push(`${index+1}. ${bold(d.name)}: ${bold(money(Number(d.balance))+'đ')} | Nợ ${bold(daysLabel(d.paymentDays))} | Đơn mới: ${text(purchase)}`);
                });
                lines.push('',`💰 TỔNG CÔNG NỢ: ${bold(money(total)+'đ')}`);
                if(range){
                    const available=targets.filter(box=>box.dataset.periodIncrease!=='');
                    const increase=available.reduce((sum,box)=>sum+Number(box.dataset.periodIncrease),0);
                    lines.push(`💰 CÔNG NỢ PHÁT SINH (${text(range)}): ${bold(available.length?money(increase)+'đ':'Chưa có dữ liệu')}`);
                    const top=available.filter(box=>Number(box.dataset.periodIncrease)>0).sort((a,b)=>Number(b.dataset.periodIncrease)-Number(a.dataset.periodIncrease)).slice(0,3);
                    if(top.length)lines.push('    • Khách phát sinh nhiều:',...top.map(box=>`    🔥 ${bold(box.dataset.name)}: ${bold(money(Number(box.dataset.periodIncrease))+' đ')}`));
                }
                lines.push('','🎯 CÔNG NỢ CHƯA VỀ – HOA HỒNG CÒN XA! 🦆💸');
                return lines.join(rich?'<br>':'\n');
            };
            const content=buildContent(false);
            try{
                if(navigator.clipboard&&window.isSecureContext){
                    if(typeof ClipboardItem!=='undefined'&&navigator.clipboard.write){
                        try{await navigator.clipboard.write([new ClipboardItem({
                            'text/plain':new Blob([content],{type:'text/plain'}),
                            'text/html':new Blob([buildContent(true)],{type:'text/html'})
                        })]);}catch(_){await navigator.clipboard.writeText(content);}
                    }else await navigator.clipboard.writeText(content);
                }
                else {
                    const area=document.createElement('textarea');area.value=content;area.style.position='fixed';area.style.opacity='0';document.body.appendChild(area);area.select();
                    const copied=document.execCommand('copy');area.remove();if(!copied)throw new Error();
                }
                status.textContent='Đã chép nội dung của các khách đã chọn.';
            }catch(_){status.textContent='Không chép được. Vui lòng cho phép trình duyệt truy cập bộ nhớ tạm và thử lại.';}
        });
        remindSelected.addEventListener('click',async()=>{
            const targets=selected();if(busy||!targets.length)return;
            busy=true;update();let success=0;const failures=[];
            for(const box of targets){
                status.textContent=`Đang ghi nhận ${success+failures.length+1}/${targets.length} khách…`;
                const data=new FormData();
                data.set('_token',document.querySelector('meta[name="csrf-token"]').content);
                data.set('report_source',bulk.dataset.source);data.set('source_key',box.dataset.key);data.set('customer_id',box.dataset.customer);
                data.set('request_id',box.dataset.requestId);data.set('target_amount',box.dataset.balance);data.set('channel','zalo');
                data.set('note',`Nhắc công nợ ngày ${bulk.dataset.date}: ${line(box)}`);
                try{
                    await send(bulk.dataset.url,data);success++;
                    box.closest('tr').classList.add('collection-reminded-today');
                    const cell=box.closest('tr').querySelector('.collection-status');
                    let badge=cell.querySelector('[data-bulk-reminded-date]');
                    if(!badge){badge=document.createElement('div');badge.dataset.bulkRemindedDate='';cell.appendChild(badge);}
                    badge.textContent=`Đã nhắc ngày ${bulk.dataset.date}`;
                }catch(error){failures.push(`${box.dataset.name}: ${error.message}`);}
            }
            busy=false;update();
            status.textContent=`Đã ghi nhận ${success}/${targets.length} khách.${failures.length?' Chưa ghi nhận: '+failures.join('; '):''}`;
        });
        update();
    }
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
