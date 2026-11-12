<dialog id="debt-balance-dialog" class="debt-balance-dialog" aria-labelledby="debt-balance-title">
    <form id="debt-balance-form" action="{{ route('customer-debt.balance-adjustments') }}" method="POST">
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-3"><h3 id="debt-balance-title">Cập nhật công nợ</h3><button type="button" class="btn btn-light btn-sm" id="debt-balance-close" aria-label="Đóng">×</button></div>
        <strong id="debt-balance-name"></strong><p id="debt-balance-scope" class="small text-muted mt-1"></p>
        <input type="hidden" name="source_key"><input type="hidden" name="revision"><input type="hidden" name="request_id">
        <div class="debt-balance-current">Công nợ hiện tại <strong id="debt-balance-current"></strong></div>
        <label for="debt-balance-actual" class="form-label mt-3">Công nợ thực tế (đ)</label>
        <input id="debt-balance-actual" name="new_balance" class="form-control" inputmode="text" autocomplete="off" required aria-describedby="debt-balance-help">
        <div id="debt-balance-help" class="small text-muted mt-1">Nhập 0 nếu hết nợ; số âm nếu khách trả trước.</div>
        <div class="small mt-2">Chênh lệch điều chỉnh: <strong id="debt-balance-delta">—</strong></div>
        <label for="debt-balance-reason" class="form-label mt-3">Lý do điều chỉnh <span class="text-danger">*</span></label>
        <textarea id="debt-balance-reason" name="reason" class="form-control" rows="3" minlength="3" maxlength="1000" required placeholder="Ví dụ: Đối chiếu và chốt công nợ với khách ngày ..."></textarea>
        <p class="small text-muted mt-2">Chênh lệch được lưu riêng và giữ khi cập nhật Sheet. Giao dịch mới vẫn tăng / giảm công nợ.</p>
        <div id="debt-balance-error" class="alert alert-danger py-2" role="alert" hidden></div>
        <div class="text-end mt-3"><button type="submit" class="btn btn-primary" id="debt-balance-save">Lưu công nợ thực tế</button></div>
    </form>
</dialog>
@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('debt-balance-dialog');
    const form = document.getElementById('debt-balance-form');
    const input = form.elements.new_balance;
    const error = document.getElementById('debt-balance-error');
    const save = document.getElementById('debt-balance-save');
    const close = document.getElementById('debt-balance-close');
    const money = value => new Intl.NumberFormat('vi-VN').format(value) + ' đ';
    const parse = value => {
        const text = value.trim().replace(/\s/g, '');
        if (!/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)$/.test(text)) return NaN;
        const number = Number(text.replace(/\./g, ''));
        return Number.isSafeInteger(number) && Math.abs(number) <= 999999999999999 ? number : NaN;
    };
    let current = 0;
    const preview = () => {
        const value = parse(input.value);
        input.setCustomValidity(Number.isNaN(value) ? 'Nhập số tiền nguyên hợp lệ, ví dụ 10.000.000.' : '');
        document.getElementById('debt-balance-delta').textContent = Number.isNaN(value) ? '—' : money(value - current);
    };
    document.querySelectorAll('.debt-balance-button').forEach(button => button.addEventListener('click', () => {
        form.reset(); error.hidden = true;
        current = Number(button.dataset.balance);
        form.elements.source_key.value = button.dataset.key;
        form.elements.revision.value = button.dataset.revision;
        form.elements.request_id.value = button.dataset.requestId;
        document.getElementById('debt-balance-name').textContent = button.dataset.name;
        document.getElementById('debt-balance-scope').textContent = button.dataset.linked === '1' ? 'Áp dụng cho tổng công nợ của khách hệ thống, gồm mọi nguồn đã ghép.' : 'Áp dụng cho khách nguồn này; chưa ghép với khách hệ thống.';
        document.getElementById('debt-balance-current').textContent = money(current);
        input.value = new Intl.NumberFormat('vi-VN').format(current);
        preview(); dialog.showModal(); input.focus(); input.select();
    }));
    input.addEventListener('input', preview);
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('cancel', event => { if (save.disabled) event.preventDefault(); });
    form.addEventListener('submit', async event => {
        event.preventDefault(); preview();
        if (!form.reportValidity() || save.disabled) return;
        save.disabled = true; close.disabled = true; error.hidden = true;
        const data = new FormData(form); data.set('new_balance', String(parse(input.value)));
        try {
            const response = await fetch(form.action, {method: 'POST', body: data, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Không thể lưu công nợ.');
            window.location.reload();
        } catch (failure) {
            error.textContent = failure.message || 'Không thể kết nối. Vui lòng thử lại.'; error.hidden = false;
            save.disabled = false; close.disabled = false;
        }
    });
})();
</script>
@endpush
