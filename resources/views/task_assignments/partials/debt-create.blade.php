<div class="mb-3">
<label class="form-label" for="task-type">Loại công việc</label>
<select class="form-select" id="task-type" name="task_type"><option value="default">Mặc định</option><option value="debt_collection" @selected(old('task_type') === 'debt_collection')>Thu nợ</option></select>
<div id="debt-editor" class="border rounded p-3 mt-2" hidden>
<p class="small text-muted">Thêm khách hàng và mục tiêu thu. Sale phụ trách phải được chọn trong danh sách thành viên nhận việc bên dưới. Nhập số tiền bằng đồng.</p>
<div id="debt-rows"></div><button type="button" class="btn btn-sm btn-outline-primary" id="add-debt">+ Thêm khách hàng</button>
</div>
</div>
<template id="debt-template"><div class="row g-2 mb-3 debt-row">
<div class="col-md-4"><label class="small">Khách hàng</label><select data-field="customer_id" class="form-select" required><option value="">Chọn khách hàng</option>@foreach($debtCustomers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="small">Công nợ cần thu (đ)</label><input data-field="target" type="number" min="1" max="999999999999" step="0.01" class="form-control" required></div>
<div class="col-md-4"><label class="small">Sale phụ trách</label><select data-field="sale_id" class="form-select" required><option value="">Chọn sale</option>@foreach($allowedAssignees as $sale)<option value="{{ $sale->id }}">{{ $sale->name }}</option>@endforeach</select></div>
<div class="col-md-1"><button type="button" class="btn btn-outline-danger mt-3" data-remove aria-label="Xóa dòng">×</button></div>
</div></template>
@push('scripts')
<script>
(() => {
 const type = document.getElementById('task-type'), editor = document.getElementById('debt-editor'), rows = document.getElementById('debt-rows');
 let index = 0;
 function add(values = {}) { const row = document.getElementById('debt-template').content.cloneNode(true); row.querySelectorAll('[data-field]').forEach(input => { input.name = `debt_items[${index}][${input.dataset.field}]`; input.value = values[input.dataset.field] ?? ''; }); row.querySelector('[data-remove]').onclick = event => event.target.closest('.debt-row').remove(); rows.append(row); index++; sync(); }
 function sync() { editor.hidden = type.value !== 'debt_collection'; rows.querySelectorAll('input,select').forEach(input => input.disabled = editor.hidden); }
 const oldRows = @json(array_values((array) old('debt_items', [])));
 oldRows.forEach(add);
 document.getElementById('add-debt').onclick = () => add();
 type.onchange = () => { if (type.value === 'debt_collection' && !rows.children.length) add(); sync(); };
 if (type.value === 'debt_collection' && !rows.children.length) add(); sync();
})();
</script>
@endpush
