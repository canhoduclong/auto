@extends($prefix === 'ceo' ? 'layouts.ceo' : 'layouts.accounting')
@section('title','Quản lý thuế suất')
@section('subtitle','Cấu hình thuế suất VAT dùng chung cho sản phẩm và các biến thể')
@push('styles')
<style>
.tax-panel{background:#fff;border:1px solid #dde5ee;border-radius:14px;overflow:hidden}.tax-heading{padding:22px 24px;border-bottom:1px solid #e7edf3}.tax-heading h5{margin:0 0 6px;font-weight:700}.tax-heading p{margin:0;color:#64748b;font-size:13px}.tax-filters{display:grid;grid-template-columns:minmax(180px,2fr) repeat(3,minmax(140px,1fr)) 110px auto;gap:12px;align-items:end;padding:20px 24px;background:#f8fafc}.tax-filters label{display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px}.tax-filters .form-control,.tax-filters .form-select{min-height:40px}.tax-table{margin:0;width:100%}.tax-table th{background:#f1f5f9;font-size:11px;letter-spacing:.4px;text-transform:uppercase;color:#64748b;padding:14px 20px;white-space:nowrap}.tax-table td{padding:14px 20px;border-color:#edf1f5;vertical-align:middle}.tax-product-name{font-weight:600;color:#1e293b}.tax-status{display:inline-block;padding:4px 10px;border-radius:20px;font-size:12px;white-space:nowrap;background:#fff4dc;color:#8a6100}.tax-status.is-set{background:#e7f5ef;color:#166348}.tax-editor{display:flex;flex-wrap:wrap;align-items:center;gap:10px}.tax-input{position:relative;width:125px}.tax-input input{padding-right:28px;min-height:40px}.tax-input span{position:absolute;right:12px;top:10px;color:#64748b;pointer-events:none}.tax-save{background:#087f72;color:#fff;border:0;border-radius:8px;padding:9px 16px;min-height:40px}.tax-save:hover{background:#06665c}.tax-bottom{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:16px 24px;border-top:1px solid #e7edf3;font-size:13px;color:#64748b}.tax-bottom .pagination{margin:0}.tax-help{padding:14px 24px;background:#f8fafc;font-size:12px;color:#64748b}.tax-table-wrap{overflow-x:auto}
@media(max-width:900px){.tax-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.tax-filters>div:first-child{grid-column:1/-1}}
@media(max-width:575px){.tax-heading,.tax-filters,.tax-bottom,.tax-help{padding:16px}.tax-table thead{display:none}.tax-table,.tax-table tbody{display:block}.tax-table tr{display:grid;grid-template-columns:30px minmax(0,1fr);gap:8px;padding:16px;border-bottom:1px solid #e7edf3}.tax-table td{display:block;padding:0;border:0;min-width:0}.tax-table td:nth-child(n+3){grid-column:2}.tax-table td:nth-child(3)::before{content:'Biến thể: ';color:#64748b}.tax-table td:nth-child(7){margin-top:4px}.tax-table-wrap{overflow:visible}.tax-filters .btn{min-height:40px}.tax-editor{flex-wrap:wrap}}
.tax-priority-cell{text-align:center;width:70px}.tax-priority-star{display:inline-grid;place-items:center;width:40px;height:40px;border:0;border-radius:8px;background:transparent;color:#94a3b8;font-size:26px;cursor:pointer;line-height:1}.tax-priority-star.is-priority{color:#d99a00}.tax-priority-star:hover{background:#fff7df;color:#b78000}.tax-priority-star:focus-visible{outline:2px solid #087f72;outline-offset:2px}.tax-priority-cell form{margin:0}
@media(max-width:575px){.tax-table tr{position:relative}.tax-table td.tax-product-name{padding-right:42px}.tax-table td.tax-priority-cell{position:absolute;top:10px;right:10px;width:40px;margin:0}}
@media(max-width:575px){.tax-table td[data-tax-entity]::before{content:attr(data-tax-entity) ': ';font-size:12px;color:#64748b}}
.tax-entity-editor{min-width:190px}.tax-entity-form{display:grid;gap:7px;margin-top:12px}.tax-entity-form label{font-size:11px;font-weight:600;color:#64748b;margin:0}.tax-entity-form .tax-input{width:100%}.tax-entity-form .form-control{min-height:38px;font-size:13px}.tax-entity-draft{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;border-top:1px dashed #dce5ec;padding-top:10px;margin-top:12px;font-size:12px}.tax-entity-draft form{margin:0}
@media(max-width:575px){.tax-entity-editor{min-width:0;padding:12px!important;border:1px solid #e7edf3!important;border-radius:8px}.tax-table td[data-tax-entity]::before{display:block;margin-bottom:8px;font-weight:700;color:#164b50}}
.tax-table td.tax-entity-editor{min-width:120px}.tax-adjust-dialog{border:1px solid #dce5ed;border-radius:16px;padding:0;width:min(900px,calc(100vw - 24px));max-height:90dvh;overflow:auto;color:#1e293b;box-shadow:0 20px 70px #0003}.tax-adjust-dialog::backdrop{background:#0f172a80}.tax-dialog-heading{display:flex;justify-content:space-between;align-items:flex-start;padding:20px 24px;border-bottom:1px solid #e7edf3;gap:16px}.tax-dialog-heading h5{margin:0 0 6px;font-weight:700}.tax-dialog-heading p{margin:0;color:#64748b}.tax-dialog-close{border:0;background:#f1f5f9;border-radius:8px;font-size:26px;line-height:1;width:40px;height:40px}.tax-dialog-hint{padding:16px 24px 0;font-size:13px;color:#64748b}.tax-dialog-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;padding:16px 24px 24px}.tax-dialog-entity{padding:16px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc}.tax-dialog-entity h6{font-weight:700;margin:0 0 6px}.tax-dialog-footer{display:flex;justify-content:flex-end;padding:16px 24px;border-top:1px solid #e7edf3}.tax-dialog-entity .tax-entity-form input{width:100%}.tax-dialog-entity .tax-save{margin-top:6px}
@media(max-width:650px){.tax-dialog-grid{grid-template-columns:minmax(0,1fr);padding:16px}.tax-dialog-heading{padding:16px}.tax-dialog-hint{padding:16px 16px 0}}
</style>
@endpush
@section('accounting_content')
<div class="py-3">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<section class="tax-panel">
<div class="tax-heading"><h5>Thuế suất VAT sản phẩm</h5><p>Thuế suất VAT riêng cho từng nhóm pháp nhân, dùng chung cho toàn bộ biến thể. Nhóm Khác gồm cá nhân và các pháp nhân còn lại. Để trống nếu chưa thiết lập; nhập 0 để áp dụng thuế suất 0%.</p></div>
<form method="GET" class="tax-filters">
<div><label for="tax-search">Tên sản phẩm</label><input id="tax-search" name="search" value="{{ $search }}" class="form-control" placeholder="Nhập tên sản phẩm…"></div>
<div><label for="tax-status">Trạng thái</label><select id="tax-status" name="status" class="form-select">@foreach([''=>'Tất cả','configured'=>'Đã thiết lập','unconfigured'=>'Chưa thiết lập'] as $value=>$label)<option value="{{ $value }}" @selected($status===$value)>{{ $label }}</option>@endforeach</select></div>
<div><label for="tax-entity-filter">Nhóm thuế để lọc / sắp xếp</label><select id="tax-entity-filter" name="entity_type" class="form-select">@foreach(\App\Models\ProductTaxRate::ENTITY_TYPES as $entity=>$label)<option value="{{ $entity }}" @selected($entityType===$entity)>{{ $label }}</option>@endforeach</select></div>
<div><label for="tax-sort">Sắp xếp</label><select id="tax-sort" name="sort" class="form-select">@foreach(['name_asc'=>'Tên sản phẩm A → Z','name_desc'=>'Tên sản phẩm Z → A','tax_asc'=>'Thuế thấp → cao','tax_desc'=>'Thuế cao → thấp'] as $value=>$label)<option value="{{ $value }}" @selected($sort===$value)>{{ $label }}</option>@endforeach</select></div>
<div><label for="tax-per-page">Dòng / trang</label><select id="tax-per-page" name="per_page" class="form-select" onchange="this.form.submit()">@foreach([10,20,50,100] as $size)<option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>@endforeach</select></div>
<button class="btn btn-primary" style="min-height:40px">Áp dụng</button>
</form>
<div class="tax-table-wrap"><table class="table tax-table"><thead><tr><th>STT</th><th>Sản phẩm</th><th>Biến thể</th><th>Hộ kinh doanh</th><th>Công ty</th><th>Khác</th><th>Thao tác</th><th class="text-center">Ưu tiên</th></tr></thead><tbody>
@forelse($products as $product)
<tr><td class="text-muted">{{ $products->firstItem()+$loop->index }}</td><td class="tax-product-name"><a href="{{ route($prefix.'.tax-settings.products.show',$product) }}">{{ $product->name }}</a></td><td>{{ $product->variants_count }}</td>@foreach(\App\Models\ProductTaxRate::ENTITY_COLUMNS as $entity=>$column)
<td class="tax-entity-editor" data-tax-entity="{{ \App\Models\ProductTaxRate::ENTITY_TYPES[$entity] }}">
    <span class="tax-status {{ $product->$column !== null ? 'is-set' : '' }}">Hiện tại: {{ $product->$column !== null ? rtrim(rtrim(number_format((float)$product->$column,2,',','.'),'0'),',').'%' : 'Chưa thiết lập' }}</span>
    @foreach($product->taxRates->where('entity_type',$entity) as $rate)
    <div class="tax-entity-draft"><span>Nháp: {{ $rate->tax_percent === null ? 'Chưa thiết lập' : $rate->tax_percent.'%' }}<br>Từ {{ $rate->effective_from->format('d/m/Y') }}</span><form method="POST" action="{{ route($prefix.'.tax-settings.products.apply',[$product,$rate]) }}">@csrf<button class="btn btn-sm btn-success">Áp dụng</button></form></div>
    @endforeach
</td>
@endforeach
<td><button type="button" class="btn btn-sm btn-outline-primary" data-tax-dialog="tax-dialog-{{ $product->id }}">Điều chỉnh</button></td><td class="tax-priority-cell"><form method="POST" action="{{ route($prefix.'.tax-settings.products.priority',$product) }}">@csrf @method('PATCH')<input type="hidden" name="tax_priority" value="{{ $product->tax_priority ? 0 : 1 }}"><button type="submit" class="tax-priority-star {{ $product->tax_priority ? 'is-priority' : '' }}" aria-pressed="{{ $product->tax_priority ? 'true' : 'false' }}" aria-label="{{ $product->tax_priority ? 'Bỏ ưu tiên ' : 'Ưu tiên ' }}{{ $product->name }}" title="{{ $product->tax_priority ? 'Bỏ ưu tiên' : 'Đưa lên đầu' }}">{{ $product->tax_priority ? '★' : '☆' }}</button></form></td></tr>
@empty<tr><td colspan="8" class="text-center text-muted py-4">Không tìm thấy sản phẩm phù hợp.</td></tr>@endforelse
</tbody></table></div>
<div class="tax-bottom"><span>Hiển thị {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} / {{ $products->total() }} sản phẩm</span>{{ $products->links() }}</div>
<div class="tax-help">Sản phẩm ưu tiên luôn ở đầu. Lưu nháp chưa đổi mức thuế hiện tại; Áp dụng xác nhận hiệu lực từ ngày đã chọn. Ngày tương lai được kích hoạt tự động theo lịch hệ thống. Cập nhật tại đây không tự tính lại VAT của đơn hàng đã tạo.</div>
</section></div>

@foreach($products as $product)
<dialog id="tax-dialog-{{ $product->id }}" class="tax-adjust-dialog" aria-labelledby="tax-dialog-title-{{ $product->id }}">
    <div class="tax-dialog-heading"><div><h5 id="tax-dialog-title-{{ $product->id }}">Điều chỉnh thuế suất VAT</h5><p>{{ $product->name }}</p></div><button type="button" class="tax-dialog-close" data-tax-close aria-label="Đóng">×</button></div>
    <p class="tax-dialog-hint">Mỗi pháp nhân có thuế suất và ngày hiệu lực riêng. Lưu nháp, sau đó bấm Áp dụng trong danh sách để xác nhận.</p>
    <div class="tax-dialog-grid">
    @foreach(\App\Models\ProductTaxRate::ENTITY_COLUMNS as $entity=>$column)
    <section class="tax-dialog-entity"><h6>{{ \App\Models\ProductTaxRate::ENTITY_TYPES[$entity] }}</h6><small class="text-muted">Hiện tại: {{ $product->$column === null ? 'Chưa thiết lập' : $product->$column.'%' }}</small>
    <form action="{{ route($prefix.'.tax-settings.products.update',$product) }}" method="POST" class="tax-entity-form">
        @csrf @method('PATCH')
        <input type="hidden" name="entity_type" value="{{ $entity }}">
        <label for="tax-rate-{{ $product->id }}-{{ $entity }}">Thuế suất (%)</label>
        <div class="tax-input"><input id="tax-rate-{{ $product->id }}-{{ $entity }}" type="number" name="tax_percent" min="0" max="100" step="0.01" value="{{ $product->$column }}" placeholder="Chưa đặt" class="form-control"><span>%</span></div>
        <label for="tax-date-{{ $product->id }}-{{ $entity }}">Áp dụng từ ngày</label>
        <input id="tax-date-{{ $product->id }}-{{ $entity }}" type="date" name="effective_from" required min="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" class="form-control">
        <input name="note" placeholder="Ghi chú thay đổi" maxlength="1000" class="form-control" aria-label="Ghi chú thuế {{ \App\Models\ProductTaxRate::ENTITY_TYPES[$entity] }}">
        <button class="tax-save">Lưu nháp</button>
    </form>

    </section>
    @endforeach
    </div>
    <div class="tax-dialog-footer"><button type="button" class="btn btn-outline-secondary" data-tax-close>Đóng</button></div>
</dialog>
@endforeach

@endsection

@push('scripts')
<script>
document.addEventListener('click',event=>{
 const trigger=event.target.closest('[data-tax-dialog]');
 if(trigger){const dialog=document.getElementById(trigger.dataset.taxDialog);if(dialog)dialog.showModal();}
 const close=event.target.closest('[data-tax-close]');
 if(close)close.closest('dialog').close();
});
</script>
@endpush
