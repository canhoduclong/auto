(() => {
    'use strict';
    const data = window.transferWorkbenchData;
    const el = id => document.getElementById(id);
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const kg = value => `${Number(Number(value || 0).toFixed(3))} kg`;
    const products = new Map(data.products.map(p => [Number(p.variant_id), p]));
    const orders = new Map(data.orders.map(o => [Number(o.id), {...o, target: null, selected: false}]));
    const groups = new Map();
    let rows = [], nextId = 1, dirty = false, submitting = false;
    const warehouseOptions = '<option value="">Chọn kho</option>' + data.warehouses.map(w => `<option value="${w.id}">${escape(w.name)}</option>`).join('');
    const error = message => { el('tw-client-error').textContent = message; el('tw-client-error').hidden = false; el('tw-client-error').scrollIntoView({block:'center',behavior:'smooth'}); };
    const ensureGroup = target => {
        target = Number(target);
        if (!data.warehouses.some(w => Number(w.id) === target)) throw new Error('Vui lòng chọn kho nhận.');
        if (!groups.has(target)) groups.set(target, {shipper: '', note: ''});
        return target;
    };
    for (const group of Object.values(data.oldGroups || {})) {
        const target = Number(group.target_warehouse_id);
        if (!data.warehouses.some(w => Number(w.id) === target)) continue;
        groups.set(target, {shipper: group.shipper_id || '', note: group.note || ''});
        for (const id of Object.values(group.order_ids || {})) {
            if (orders.has(Number(id))) orders.get(Number(id)).target = target;
            else orders.set(Number(id), {id: Number(id), code: `#${id}`, customer:'Đơn không còn trong danh sách hiện tại', items:[], target, selected:false, unavailable:true});
        }
        for (const item of Object.values(group.items || {})) rows.push({id:nextId++, variant:Number(item.product_variant_id), quantity:Number(item.quantity), weight:Number(item.weight_kg), target, selected:false});
        dirty = true;
    }
    function orderCard(order, destination = false) {
        return `<article class="tw-order"><div class="tw-order-head">${destination ? '' : `<input type="checkbox" data-order-select="${order.id}" aria-label="Chọn đơn ${escape(order.code)}" ${order.selected ? 'checked' : ''}>`}<span class="tw-sequence">${escape(order.sequence || '•')}</span><div class="tw-order-info"><strong>${escape(order.customer)}</strong><div><small>${escape(order.code)} · ${escape(order.date || '')}</small></div></div>${destination ? `<button type="button" class="tw-remove" data-return-order="${order.id}" aria-label="Bỏ đơn ${escape(order.code)} khỏi kho nhận">×</button>` : `<select class="form-select form-select-sm w-auto" aria-label="Chọn kho cho đơn ${escape(order.code)}" data-order-target="${order.id}">${warehouseOptions}</select>`}</div>${order.phone ? `<div class="small mt-2">SĐT khách: ${escape(order.phone)}</div>` : ''}${order.station ? `<div class="small mt-1">Trạm xe: ${escape(order.station)}</div>` : ''}<details class="mt-2 small"><summary>Sản phẩm (${order.items.length})</summary>${order.items.map(i => `<div class="py-1">${escape(i.name)} · ${escape(i.size)} · SL ${escape(i.quantity)} · ${kg(i.weight)}</div>`).join('')}</details>${order.unavailable ? '<div class="text-danger small">Hãy bỏ đơn này và tải lại danh sách trước khi thực hiện.</div>' : ''}</article>`;
    }
    function render() {
        const freeOrders = [...orders.values()].filter(o => !o.target);
        el('tw-order-count').textContent = freeOrders.length;
        el('tw-orders').innerHTML = freeOrders.map(o => orderCard(o)).join('') || '<div class="tw-empty">Không có đơn đủ điều kiện trong ngày đã chọn.</div>';
        const freeRows = rows.filter(r => !r.target);
        el('tw-products').innerHTML = freeRows.map(row => {
            const product = products.get(row.variant);
            return `<div class="tw-product"><input type="checkbox" data-row-select="${row.id}" aria-label="Chọn ${escape(product?.label)}" ${row.selected ? 'checked' : ''}><div class="tw-product-name"><strong>${escape(product?.product_name || 'Sản phẩm không còn khả dụng')}</strong><div class="small">${escape(product?.variant_name)} · Khả dụng: ${product?.available ?? 0}</div></div><label class="tw-product-field tw-qty">SL<input type="number" min="1" step="1" value="${row.quantity}" data-row-qty="${row.id}" class="form-control form-control-sm" aria-label="Số lượng"></label><div class="tw-product-field tw-size"><label>Size</label><div>${kg(product?.weight_per_unit)}</div></div><label class="tw-product-field tw-weight">Khối lượng (kg)<input type="number" min="0.001" step="0.001" value="${row.weight}" data-row-weight="${row.id}" class="form-control form-control-sm" aria-label="Khối lượng kg"></label><button type="button" class="tw-remove" data-delete-row="${row.id}" aria-label="Xóa sản phẩm">×</button></div>`;
        }).join('') || '<div class="tw-empty">Thêm sản phẩm tồn kho để điều chuyển.</div>';
        const targets = [...new Set([...rows.map(r => r.target), ...[...orders.values()].map(o => o.target)].filter(Boolean))];
        el('tw-destinations').innerHTML = targets.map(target => {
            const group = groups.get(target), warehouse = data.warehouses.find(w => Number(w.id) === target);
            const selectedOrders = [...orders.values()].filter(o => o.target === target);
            const selectedRows = rows.filter(r => r.target === target);
            return `<div class="tw-panel tw-destination"><div class="tw-toolbar justify-content-between"><strong>${escape(warehouse?.name)}</strong><button type="button" class="tw-remove" data-remove-group="${target}" aria-label="Bỏ kho ${escape(warehouse?.name)}">×</button></div><label class="d-block mb-2">Tài xế <select required class="form-select" data-group-shipper="${target}"><option value="">Chọn tài xế</option>${data.shippers.map(s=>`<option value="${s.id}" ${Number(group.shipper)===Number(s.id)?'selected':''}>${escape(s.name)}</option>`).join('')}</select></label>${selectedOrders.map(o=>orderCard(o,true)).join('')}${selectedRows.map(r=>{const p=products.get(r.variant);return `<div class="tw-order d-flex align-items-center gap-2"><div class="flex-grow-1"><strong>${escape(p?.label || 'Sản phẩm không còn khả dụng')}</strong><div>SL: ${r.quantity} · Size: ${kg(p?.weight_per_unit)} · <strong>${kg(r.weight)}</strong></div></div><button type="button" class="tw-remove" data-return-row="${r.id}" aria-label="Trả sản phẩm về để chỉnh sửa">×</button></div>`;}).join('')}<div class="small mb-2">${selectedOrders.length} đơn · ${selectedRows.reduce((n,r)=>n+r.quantity,0)} SP tồn kho · Hàng tồn: <strong>${kg(selectedRows.reduce((n,r)=>n+r.weight,0))}</strong></div><label class="d-block">Ghi chú<textarea class="form-control" rows="2" maxlength="1000" data-group-note="${target}">${escape(group.note)}</textarea></label></div>`;
        }).join('') || '<div class="tw-empty">Chọn đơn hoặc sản phẩm bên trái rồi chọn kho nhận.</div>';
        el('tw-execute').disabled = !targets.length;
        el('tw-all-orders').checked = freeOrders.length > 0 && freeOrders.every(o => o.selected);
        el('tw-all-products').checked = freeRows.length > 0 && freeRows.every(r => r.selected);
    }
    function renderPicker() {
        const search = el('tw-product-search').value.toLocaleLowerCase('vi');
        const matches = data.products.filter(p => `${p.label} ${p.variant_sku} ${p.attributes}`.toLocaleLowerCase('vi').includes(search));
        const header = '<div class="tw-stock-head" aria-hidden="true"><span>Sản phẩm / mã hàng</span><span>Size</span><span>Tồn khả dụng</span><span></span></div>';
        el('tw-product-results').innerHTML = matches.length ? header + matches.map(p => `
            <button type="button" class="tw-stock-row" data-add-variant="${p.variant_id}" aria-label="Thêm ${escape(p.label)}, tồn khả dụng ${escape(p.available)}" title="${escape(p.label)} · ${escape(p.variant_sku)}">
                <span class="tw-stock-name"><strong>${escape(p.product_name)}</strong><small>${escape(p.variant_sku || p.variant_name)}</small></span>
                <span class="tw-stock-size">${kg(p.weight_per_unit)}</span>
                <span class="tw-stock-available" title="Tồn khả dụng">${escape(p.available)}</span>
                <span class="tw-stock-add" aria-hidden="true">+</span>
            </button>`).join('') : '<div class="tw-empty">Không tìm thấy hàng tồn khả dụng.</div>';
    }
    function validRows(selectedRows) {
        const totals = new Map();
        for (const row of rows) totals.set(row.variant, (totals.get(row.variant) || 0) + row.quantity);
        for (const row of selectedRows) {
            const product = products.get(row.variant);
            if (!Number.isInteger(row.quantity) || row.quantity < 1 || !Number.isFinite(row.weight) || row.weight <= 0) throw new Error('Số lượng phải là số nguyên dương và khối lượng phải lớn hơn 0.');
            if (!product || totals.get(row.variant) > Number(product.available)) throw new Error(`Tổng số lượng ${product?.label || 'sản phẩm'} vượt tồn khả dụng (${product?.available ?? 0}).`);
        }
    }
    el('tw-add-product').onclick = () => {el('tw-picker').hidden = !el('tw-picker').hidden;renderPicker();if (!el('tw-picker').hidden) el('tw-product-search').focus();};
    el('tw-product-search').oninput = renderPicker;
    el('tw-all-orders').onchange = event => {orders.forEach(o=>{if (!o.target) o.selected=event.target.checked;});render();};
    el('tw-all-products').onchange = event => {rows.forEach(r=>{if (!r.target) r.selected=event.target.checked;});render();};
    el('tw-assign-orders').onclick = () => {
        try {const target=ensureGroup(el('tw-order-target').value);const selected=[...orders.values()].filter(o=>!o.target&&o.selected);if(!selected.length) throw new Error('Hãy tích chọn đơn cần chuyển.');selected.forEach(o=>{o.target=target;o.selected=false;});dirty=true;render();}catch(e){error(e.message);}
    };
    el('tw-assign-products').onclick = () => {
        try {const target=ensureGroup(el('tw-product-target').value);const selected=rows.filter(r=>!r.target&&r.selected);if(!selected.length) throw new Error('Hãy tích chọn sản phẩm cần chuyển.');validRows(selected);selected.forEach(r=>{r.target=target;r.selected=false;});dirty=true;render();}catch(e){error(e.message);}
    };
    el('transfer-workbench').addEventListener('change', event => {
        const d=event.target.dataset;
        if (d.orderSelect) orders.get(Number(d.orderSelect)).selected=event.target.checked;
        if (d.rowSelect) rows.find(r=>r.id===Number(d.rowSelect)).selected=event.target.checked;
        if (d.orderTarget && event.target.value) {try{orders.get(Number(d.orderTarget)).target=ensureGroup(event.target.value);dirty=true;render();}catch(e){error(e.message);}}
        if (d.rowQty) {const row=rows.find(r=>r.id===Number(d.rowQty));row.quantity=Number(event.target.value);row.weight=Number((row.quantity*Number(products.get(row.variant)?.weight_per_unit || 0)).toFixed(3));dirty=true;render();}
        if (d.rowWeight) {rows.find(r=>r.id===Number(d.rowWeight)).weight=Number(event.target.value);dirty=true;}
        if (d.groupShipper) {groups.get(Number(d.groupShipper)).shipper=event.target.value;dirty=true;}
    });
    el('transfer-workbench').addEventListener('input',event=>{if(event.target.dataset.groupNote){groups.get(Number(event.target.dataset.groupNote)).note=event.target.value;dirty=true;}});
    el('transfer-workbench').addEventListener('click', event => {
        const button=event.target.closest('button');if(!button)return;const d=button.dataset;
        if(d.addVariant){const p=products.get(Number(d.addVariant));rows.push({id:nextId++,variant:Number(p.variant_id),quantity:1,weight:Number(p.weight_per_unit),target:null,selected:true});dirty=true;render();}
        if(d.deleteRow){rows=rows.filter(r=>r.id!==Number(d.deleteRow));dirty=true;render();}
        if(d.returnRow){rows.find(r=>r.id===Number(d.returnRow)).target=null;dirty=true;render();}
        if(d.returnOrder){const o=orders.get(Number(d.returnOrder));if(o.unavailable)orders.delete(o.id);else o.target=null;dirty=true;render();}
        if(d.removeGroup){const target=Number(d.removeGroup);rows.forEach(r=>{if(r.target===target)r.target=null;});orders.forEach(o=>{if(o.target===target){if(o.unavailable)orders.delete(o.id);else o.target=null;}});groups.delete(target);dirty=true;render();}
    });
    el('tw-submit-form').addEventListener('submit',event=>{
        event.preventDefault();if(submitting)return;
        try{
            el('tw-client-error').hidden=true;
            const assigned=rows.filter(r=>r.target);validRows(assigned);
            if(rows.some(r=>!r.target)) throw new Error('Còn sản phẩm chưa chọn kho nhận. Hãy chuyển qua kho hoặc xóa dòng sản phẩm trước khi thực hiện.');
            const selectedOrders=[...orders.values()].filter(o=>o.target);
            if(selectedOrders.some(o=>o.unavailable))throw new Error('Có đơn không còn đủ điều kiện. Hãy bỏ đơn đó và tải lại danh sách.');
            const targets=[...new Set([...assigned.map(r=>r.target),...selectedOrders.map(o=>o.target)])];
            if(!targets.length)throw new Error('Hãy chọn ít nhất một đơn hoặc sản phẩm để điều chuyển.');
            const payload=el('tw-payload');payload.replaceChildren();
            const input=(name,value)=>{const field=document.createElement('input');field.type='hidden';field.name=name;field.value=value;payload.append(field);};
            targets.forEach((target,index)=>{const group=groups.get(target),prefix=`groups[${index}]`;if(!group.shipper)throw new Error('Hãy chọn tài xế cho từng kho nhận.');input(`${prefix}[target_warehouse_id]`,target);input(`${prefix}[shipper_id]`,group.shipper);input(`${prefix}[note]`,group.note);selectedOrders.filter(o=>o.target===target).forEach((o,i)=>input(`${prefix}[order_ids][${i}]`,o.id));assigned.filter(r=>r.target===target).forEach((r,i)=>{input(`${prefix}[items][${i}][product_variant_id]`,r.variant);input(`${prefix}[items][${i}][quantity]`,r.quantity);input(`${prefix}[items][${i}][weight_kg]`,r.weight);});});
            submitting=true;el('tw-execute').disabled=true;el('tw-execute').textContent='Đang tạo điều chuyển…';event.target.submit();
        }catch(e){error(e.message);}
    });
    window.addEventListener('beforeunload', event => {if(dirty&&!submitting){event.preventDefault();event.returnValue='';}});
    render();
})();
