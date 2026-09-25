<div class="mb-3">
    <label class="form-label fw-semibold">Tài khoản được giao quản lý</label>
    <div class="border rounded p-3">
        @forelse($accounts as $account)
            <div class="d-flex align-items-center justify-content-between gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <label class="d-flex align-items-center gap-2 mb-0 flex-grow-1">
                    <input type="checkbox" name="account_ids[]" value="{{ $account->id }}" class="form-check-input mt-0 js-managed-account" @checked($selectedAccountIds->contains((int)$account->id))>
                    <span>
                        <strong>{{ $account->name }}</strong>
                        <small class="text-muted d-block">{{ $account->type === 'cash' ? 'Tiền mặt' : 'Ngân hàng' }}{{ $account->account_number ? ' · ' . $account->account_number : '' }}</small>
                    </span>
                </label>
                <label class="small text-nowrap mb-0">
                    <input type="radio" name="default_account_id" value="{{ $account->id }}" class="form-check-input mt-0 js-default-account" @checked($defaultAccountId === (int)$account->id)>
                    Mặc định
                </label>
            </div>
        @empty
            <div class="text-muted">Chưa có tài khoản hoạt động.</div>
        @endforelse
    </div>
    <small class="text-muted">User có thể được giao nhiều tài khoản; tài khoản mặc định sẽ được chọn sẵn khi lập phiếu chi.</small>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-default-account').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const checkbox = document.querySelector('.js-managed-account[value="' + radio.value + '"]');
            if (checkbox) checkbox.checked = true;
        });
    });
    document.querySelectorAll('.js-managed-account').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const radio = document.querySelector('.js-default-account[value="' + checkbox.value + '"]');
            if (!checkbox.checked && radio?.checked) radio.checked = false;
        });
    });
});
</script>
