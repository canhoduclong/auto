<dialog id="debt-recovery-dialog" class="debt-balance-dialog" aria-labelledby="debt-recovery-title">
    <form method="POST" action="{{ $recoveryAction ?? route('customer-debt.recovery') }}">@csrf @method('PATCH')
        <div class="d-flex justify-content-between align-items-center mb-3"><h3 id="debt-recovery-title">Thu hồi công nợ</h3><button type="button" class="btn btn-sm btn-light" data-recovery-close aria-label="Đóng">×</button></div>
        <strong data-recovery-name></strong>
        <p class="small text-muted mt-2">Áp dụng cho khách này và các nguồn cùng ghép vào khách hệ thống. Không thay đổi số công nợ.</p>
        <input type="hidden" name="source_key">@isset($recoveryAction)<input type="hidden" name="customer_id"><input type="hidden" name="report_source" value="{{ $reportSource }}">@endisset<input type="hidden" name="recovery_version">
        <label class="form-label" for="debt-recovery-status">Trạng thái thu hồi</label>
        <select id="debt-recovery-status" class="form-select mb-3" name="recovery_status" required>@foreach(\App\Services\CustomerDebtRecoveryService::LABELS as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        <label class="form-label" for="debt-recovery-note">Ghi chú</label><textarea id="debt-recovery-note" name="recovery_note" class="form-control" rows="3" maxlength="1000" placeholder="Lý do / tình hình thu hồi"></textarea>
        <p class="small text-muted mt-2">Hai nhóm khó thu hồi và không có khả năng thu hồi mặc định được ẩn. Chọn “Theo dõi thu hồi” để đưa khách trở lại danh sách chính.</p>
        <div class="text-end mt-3"><button type="submit" class="btn btn-primary">Lưu trạng thái</button></div>
    </form>
</dialog>
@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('debt-recovery-dialog');
    const form = dialog.querySelector('form');
    document.querySelectorAll('.debt-recovery-button').forEach(button => button.addEventListener('click', () => {
        form.elements.source_key.value = button.dataset.key;
        if (form.elements.customer_id) form.elements.customer_id.value = button.dataset.customer || '';
        if (form.elements.report_source) form.elements.report_source.value = button.dataset.reportSource || 'sheet';
        form.elements.recovery_version.value = button.dataset.version;
        form.elements.recovery_status.value = button.dataset.status;
        form.elements.recovery_note.value = button.dataset.note;
        dialog.querySelector('[data-recovery-name]').textContent = button.dataset.name;
        dialog.showModal();
    }));
    dialog.querySelector('[data-recovery-close]').addEventListener('click', () => dialog.close());
    form.addEventListener('submit', () => { form.querySelector('[type=submit]').disabled = true; });
})();
</script>
@endpush
