@extends(request()->boolean('popup') ? 'layouts.warehouse-popup' : 'layouts.warehouse')
@section('title', 'Đóng hàng bù')
@section('content')
<style>
.supplement-wrap{padding:16px;color:#123047;font-size:13px}.supplement-wrap th{font-size:12px;color:#687c8c;white-space:nowrap}.supplement-wrap td{vertical-align:middle}.supplement-wrap .form-control{min-width:68px}.supplement-wrap .btn-primary{background:#0e7974;border-color:#0e7974}.supplement-wrap .measure{display:flex;gap:6px;min-width:135px}.supplement-wrap .measure input{width:78px}.supplement-wrap .thumb{width:42px;height:42px;object-fit:cover;border:1px solid #dee2e6;border-radius:6px}.supplement-wrap .preview-grid{display:grid;grid-template-columns:1fr 2fr;gap:26px;margin-top:24px}.supplement-wrap .section-title{border-bottom:4px solid #111;padding-bottom:6px;margin-bottom:22px}.supplement-wrap .summary-weight{font-weight:700;color:#087d82;white-space:nowrap}.supplement-wrap .table td{padding:10px 8px}.supplement-wrap .saved{color:#087d82}.supplement-wrap .line-variant{min-width:110px;max-width:170px}@media(max-width:650px){.supplement-wrap .preview-grid{grid-template-columns:1fr;gap:10px}.supplement-wrap{padding:10px}}
</style>
<div class="supplement-wrap">
<div id="supplement-errors" class="alert alert-danger" role="alert" hidden></div>
<form method="POST" action="{{ route('warehouse.orders.supplemental-packing.store', $order) }}" id="supplement-form">
@csrf
<input type="hidden" name="packing_date" value="{{ request('packing_date', today()->toDateString()) }}">
<div class="table-responsive"><table class="table"><thead><tr><th>ẢNH</th><th>SẢN PHẨM</th><th>SL ĐÓNG</th><th>KHỐI LƯỢNG (KG)</th><th>SIZE</th><th>THÀNH TIỀN</th></tr></thead><tbody>
@foreach($lines as $id => $line)
@php
$item = $line['item'];
$imagePath = $item->variant?->avatar?->media?->file_path ?? $item->product?->avatar?->media?->file_path;
@endphp
<tr class="supplement-line" data-id="{{ $id }}" data-name="{{ $item->product->name }}" data-price="{{ $item->price }}" data-kg="{{ $item->effective_priced_by_kg ? 1 : 0 }}">
<td>@if($imagePath)<img class="thumb" src="{{ asset('storage/'.$imagePath) }}" alt="">@else<span class="text-muted">—</span>@endif</td>
<td><label class="fw-bold"><input class="line-enabled" type="checkbox" name="lines[{{ $id }}][enabled]" value="1" checked> {{ $item->product->name }}</label><small class="d-block text-muted">{{ $item->variant?->sku }}</small>
<select aria-label="Định mức pha lóc" class="form-select form-select-sm line-recipe mt-1" name="lines[{{ $id }}][recipe_id]">@foreach($line['recipes'] as $recipe)<option value="{{ $recipe->id }}">{{ $recipe->name }}</option>@endforeach</select></td>
<td><div class="measure"><input aria-label="Số lượng đóng" class="form-control form-control-sm line-quantity" name="lines[{{ $id }}][quantity]" type="number" min="1" max="100000" step="1" required value="{{ $item->packed_quantity ?? $item->quantity }}"><button class="btn btn-primary btn-sm save-line" type="button">Lưu</button></div></td>
<td><div class="measure"><input aria-label="Khối lượng kg" class="form-control form-control-sm line-weight" name="lines[{{ $id }}][weight]" type="number" min="0.001" max="1000000" step="0.001" required value="{{ $item->packed_weight ?? round($item->quantity * $item->effective_unit_weight, 3) }}"><button class="btn btn-primary btn-sm save-line" type="button">Lưu</button></div></td>
<td><select aria-label="Size xác nhận" class="form-select form-select-sm line-variant" name="lines[{{ $id }}][variant_id]">@foreach($line['variants'] as $variant)<option value="{{ $variant->id }}" data-size="{{ $variant->order_unit_weight }}">{{ $variant->size }}</option>@endforeach</select><small class="average d-block mt-1"></small><label class="d-block mt-1"><input type="checkbox" class="line-confirmed" name="lines[{{ $id }}][confirmed]" value="1"> Xác nhận size</label></td>
<td class="line-total text-nowrap">—</td>
</tr>
@endforeach
</tbody></table></div>
<div class="preview-grid"><div><div class="section-title">Phần Nhập Bù</div><p>Tương đương nguyên con</p><div id="equivalents"></div><small class="text-muted">Quy đổi theo định mức; không trừ tồn nguyên liệu.</small></div><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>STT</th><th>Sản phẩm / Biến thể</th><th>Số lượng</th><th>ĐVT</th><th>Khối lượng</th></tr></thead><tbody id="receipt-preview"></tbody></table></div></div>
<p class="text-muted small">Lưu để cập nhật phần nhập bù trong popup. Thành phẩm nhập ngay khi hoàn thành; phụ phẩm cộng dồn chờ nhập kho sau. Các dòng không chọn cần được đóng đủ ở màn hình đơn hàng.</p>
<div class="d-flex justify-content-end"><button class="btn btn-primary btn-sm" type="submit" @disabled(empty($lines))>Hoàn thành</button></div>
</form></div>
<script>
const recipes = @json($preview);
const format = value => Number(value).toLocaleString('vi-VN', {maximumFractionDigits:3});
const rows = Array.from(document.querySelectorAll('.supplement-line'));
const states = new Map();
function renderPreview() {
    const body = document.getElementById('receipt-preview'), equivalent = document.getElementById('equivalents');
    body.replaceChildren(); equivalent.replaceChildren();
    const components = new Map(); let index = 0;
    function add(name, quantity, unit, weight, pending) {
        const tr = document.createElement('tr');
        [++index, name + (pending ? ' · Chờ nhập sau' : ''), format(quantity), unit, format(weight) + ' Kg'].forEach((text, i) => { const td=document.createElement('td'); td.textContent=text; if(i===4)td.className='summary-weight'; tr.append(td); }); body.append(tr);
    }
    rows.forEach(row => {
        if (!row.querySelector('.line-enabled').checked) return;
        const state = states.get(row); if(!state) return;
        add(row.dataset.name + ' · Size ' + state.size, state.quantity, 'Con', state.weight, false);
        const plan = recipes[row.dataset.id]?.[state.recipe];
        if (!plan?.yield) return;
        const input = state.weight * 100 / plan.yield;
        const p = document.createElement('p'); p.textContent = format(state.quantity) + ' con · bình quân ' + format(input / state.quantity) + ' kg/con (' + format(input) + ' kg)'; equivalent.append(p);
        plan.components.forEach(component => components.set(component.name, (components.get(component.name)||0) + input * component.rate/100));
    });
    components.forEach((kg,name)=>add(name, Math.round(kg*1000)/1000, 'Kg', Math.round(kg*1000)/1000, true));
}
rows.forEach(row=>{
    const quantity=row.querySelector('.line-quantity'), weight=row.querySelector('.line-weight'), variant=row.querySelector('.line-variant'), confirmed=row.querySelector('.line-confirmed'), recipe=row.querySelector('.line-recipe');
    function dirty(suggest) {
        confirmed.checked=false; states.delete(row);
        row.querySelectorAll('.save-line').forEach(button=>button.textContent='Lưu');
        const average=Number(weight.value)/Number(quantity.value);
        if(suggest && Number.isFinite(average)) {
            const options=Array.from(variant.options).filter(o=>Number(o.dataset.size)>0).sort((a,b)=>Math.abs(Number(a.dataset.size)-average)-Math.abs(Number(b.dataset.size)-average));
            if(options.length)variant.value=options[0].value;
        }
        row.querySelector('.average').textContent=Number.isFinite(average)? 'TB '+format(average)+' kg':'';
        row.querySelector('.line-total').textContent=format((row.dataset.kg==='1'?Number(weight.value):Number(quantity.value))*Number(row.dataset.price))+' đ';
        renderPreview();
    }
    quantity.addEventListener('input',()=>dirty(true)); weight.addEventListener('input',()=>dirty(true)); variant.addEventListener('change',()=>dirty(false)); recipe.addEventListener('change',()=>dirty(false)); row.querySelector('.line-enabled').addEventListener('change',renderPreview);
    row.querySelectorAll('.save-line').forEach(button=>button.addEventListener('click',()=>{
        if(!quantity.reportValidity() || !weight.reportValidity())return;
        if(!variant.value || !recipes[row.dataset.id]?.[recipe.value]?.yield){alert('Thiếu size hoặc định mức pha lóc hợp lệ.');return;}
        states.set(row,{quantity:Number(quantity.value),weight:Number(weight.value),size:variant.selectedOptions[0].textContent,recipe:recipe.value});
        row.querySelectorAll('.save-line').forEach(b=>b.textContent='Đã lưu');renderPreview();
    })); dirty(true);
});
document.getElementById('supplement-form').addEventListener('submit',async function(event){
    event.preventDefault(); const error=document.getElementById('supplement-errors');error.hidden=true;
    const selected=rows.filter(row=>row.querySelector('.line-enabled').checked);
    if(!selected.length || selected.some(row=>!states.has(row)||!row.querySelector('.line-confirmed').checked)){error.textContent='Vui lòng Lưu số liệu và xác nhận size cho từng sản phẩm nhập bù.';error.hidden=false;error.scrollIntoView();return;}
    const button=this.querySelector('[type=submit]');button.disabled=true;
    try {
        const response=await fetch(this.action,{method:'POST',headers:{Accept:'application/json'},body:new FormData(this)});
        const result=await response.json(); if(!response.ok || !result.ok)throw new Error(Object.values(result.errors||{}).flat().join(' ')||result.message||'Không thể hoàn thành.');
        if(window.parent!==window)window.parent.postMessage({type:'supplement-packing-completed'},location.origin);
        else location.href=@json(route('warehouse.orders', ['date'=>request('packing_date',today()->toDateString())]));
    }catch(e){error.textContent=e.message;error.hidden=false;error.scrollIntoView();button.disabled=false;}
});
</script>
@endsection
