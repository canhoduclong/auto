<dialog id="debt-create-customer-dialog" class="debt-balance-dialog" aria-labelledby="debt-create-customer-title">
    <form method="POST" action="{{ route('customer-debt.customers.store') }}">@csrf
        <div class="d-flex justify-content-between align-items-center mb-3"><h3 id="debt-create-customer-title">Tạo khách hàng nhanh</h3><button type="button" class="btn btn-sm btn-light" data-create-customer-close aria-label="Đóng">×</button></div>
        <p class="small text-muted">Tạo khách trong hệ thống và tự ghép với dữ liệu công nợ đang chọn.</p>
        <input type="hidden" name="source_key">
        <label class="form-label" for="debt-create-name">Tên khách hàng *</label><input id="debt-create-name" name="name" class="form-control mb-3" maxlength="255" required>
        <div class="row g-3 mb-3"><div class="col-sm-6"><label class="form-label" for="debt-create-code">Mã khách hàng</label><input id="debt-create-code" name="customer_code" class="form-control" maxlength="255"></div><div class="col-sm-6"><label class="form-label" for="debt-create-phone">Số điện thoại</label><input id="debt-create-phone" name="phone" class="form-control" maxlength="30" type="tel"></div></div>
        <label class="form-label" for="debt-create-address">Địa chỉ</label><input id="debt-create-address" name="address" class="form-control mb-3" maxlength="255">
        <div class="row g-3 mb-3"><div class="col-sm-7"><label class="form-label" for="debt-create-sale">Sale phụ trách</label><select id="debt-create-sale" name="sale_id" class="form-select"><option value="">Chưa phân công</option>@foreach($quickCustomerSales as $sale)<option value="{{ $sale->id }}">{{ $sale->short_name ?: $sale->name }}</option>@endforeach</select></div><div class="col-sm-5"><label class="form-label" for="debt-create-time">Giờ giao hàng *</label><input id="debt-create-time" name="delivery_time" type="time" class="form-control" required></div></div>
        <div class="alert alert-danger py-2" data-create-customer-error role="alert" hidden></div>
        <div class="text-end"><button type="submit" class="btn btn-primary">Tạo và ghép khách</button></div>
    </form>
</dialog>
@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('debt-create-customer-dialog'), form = dialog.querySelector('form');
    const save = form.querySelector('[type=submit]'), close = dialog.querySelector('[data-create-customer-close]'), error = dialog.querySelector('[role=alert]');
    document.querySelectorAll('.debt-create-customer-button').forEach(button => button.addEventListener('click', () => {
        form.reset(); error.hidden = true;
        form.elements.source_key.value = button.dataset.key;
        form.elements.name.value = button.dataset.name;
        form.elements.customer_code.value = button.dataset.code;
        form.elements.sale_id.value = [...form.elements.sale_id.options].some(option => option.value === button.dataset.sale) ? button.dataset.sale : '';
        dialog.showModal();
    }));
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('cancel', event => { if (save.disabled) event.preventDefault(); });
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (!form.reportValidity() || save.disabled) return;
        save.disabled = true; close.disabled = true; error.hidden = true;
        try {
            const response = await fetch(form.action, {method:'POST', body:new FormData(form), headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Không tạo được khách hàng.');
            window.location.reload();
        } catch (failure) { error.textContent = failure.message; error.hidden = false; save.disabled = false; close.disabled = false; }
    });
})();
</script>
@endpush
