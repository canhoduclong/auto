<style>
.mcl-debt-policy {display:flex;flex-wrap:wrap;align-items:center;gap:7px 14px;margin-top:10px;padding-top:9px;border-top:1px dashed #dbe5ef;font-size:12px;color:#64748b}
.mcl-debt-policy strong {color:#123b78;font-weight:650}
.mcl-debt-policy .btn {padding:3px 8px;font-size:11px}
.mcl-debt-policy [hidden] {display:none!important}
.mcl-debt-lock {font-size:11px;border-radius:4px;padding:2px 6px;background:#f1f5f9;color:#64748b}
.mcl-debt-dialog {width:min(520px,94vw);max-height:90vh;overflow:auto;border:1px solid #dbe5ef;border-radius:12px;padding:22px;color:#1e293b}
.mcl-debt-dialog::backdrop {background:#0f172a77}
.mcl-debt-dialog .form-label {font-size:13px;margin-top:12px}
.mcl-debt-dialog .form-control {width:100%;font-size:14px}
@media(max-width:575px){.mcl-debt-policy {gap:7px 10px;font-size:11px}.mcl-debt-dialog {padding:16px}}
</style>
<dialog class="mcl-debt-dialog" id="mclDebtPolicyDialog" aria-labelledby="mclDebtPolicyTitle">
    <div class="d-flex align-items-center justify-content-between gap-2"><h5 id="mclDebtPolicyTitle" class="mb-0">Sửa nhanh tiêu chí công nợ</h5><button class="btn btn-sm btn-light" type="button" data-close-debt-policy>Đóng</button></div>
    <p class="fw-bold mt-3 mb-1" id="mclDebtPolicyCustomer"></p>
    <form id="mclDebtPolicyForm">
        <label for="mclDebtOrders" class="form-label">Cho nợ (số Bill)</label><input id="mclDebtOrders" name="allowed_orders" type="number" min="0" max="100000" step="1" class="form-control" required>
        <label for="mclDebtLimit" class="form-label">Trần công nợ (đ)</label><input id="mclDebtLimit" name="credit_limit" inputmode="numeric" placeholder="Ví dụ: 10.000.000" class="form-control" required>
        <label for="mclDebtDays" class="form-label">Ngày nợ (từ ngày giao hàng gần nhất)</label><input id="mclDebtDays" name="due_days" type="number" min="0" max="3650" step="1" class="form-control" required>
        <p class="small text-muted mt-3">Sale, leader và manager được lưu một lần rồi tự khóa. Chỉ admin được mở khóa; mỗi lần mở khóa chỉ cho phép thêm một lần lưu.</p>
        <div id="mclDebtPolicyError" class="small text-danger mb-2" role="alert"></div>
        <button id="mclDebtPolicySave" class="btn btn-primary" type="submit">Lưu và khóa</button>
    </form>
</dialog>
<script>
(() => {
    const root = document.querySelector('.mcl-list');
    const dialog = document.getElementById('mclDebtPolicyDialog');
    if (!root || !dialog) return;
    const csrf = @json(csrf_token());
    const isAdmin = @json(auth()->user()->isAdmin());
    let active;
    let version;
    const request = async (url, method, data) => {
        const response = await fetch(url, {method, credentials:'same-origin', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify(data)});
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : (result.message || 'Không cập nhật được. Vui lòng tải lại trang và thử lại.'));
        return result;
    };
    const paint = (card, policy, message) => {
        card.dataset.policy = JSON.stringify(policy);
        card.querySelector('[data-debt-orders]').textContent = `${policy.allowed_orders} Bill`;
        card.querySelector('[data-debt-limit]').textContent = `${Number(policy.credit_limit).toLocaleString('vi-VN')} đ`;
        card.querySelector('[data-debt-days]').textContent = `${policy.due_days} ngày`;
        card.querySelector('[data-debt-lock]').textContent = policy.staff_edit_locked ? 'Đã khóa' : 'Được lưu 1 lần';
        const edit = card.querySelector('[data-edit-debt-policy]');
        if (edit) edit.hidden = policy.staff_edit_locked && !isAdmin;
        const unlock = card.querySelector('[data-unlock-debt-policy]');
        if (unlock) unlock.hidden = !policy.staff_edit_locked;
        const status = card.querySelector('[data-debt-policy-message]');
        status.className = 'small text-success';status.textContent = message;
    };
    root.addEventListener('click', async event => {
        const button = event.target.closest('[data-edit-debt-policy],[data-unlock-debt-policy]');
        if (!button) return;
        const card = button.closest('[data-debt-policy-card]');
        if (button.hasAttribute('data-edit-debt-policy')) {
            const policy = JSON.parse(card.dataset.policy);
            active = card;version = policy.edit_version;
            document.getElementById('mclDebtPolicyCustomer').textContent = card.dataset.customerName;
            document.getElementById('mclDebtOrders').value = policy.allowed_orders;
            document.getElementById('mclDebtLimit').value = Number(policy.credit_limit).toLocaleString('vi-VN');
            document.getElementById('mclDebtDays').value = policy.due_days;
            document.getElementById('mclDebtPolicyError').textContent = '';
            dialog.showModal();
            return;
        }
        button.disabled = true;
        try {const result = await request(card.dataset.unlockUrl,'POST',{});paint(card,result.policy,result.message);}
        catch(error) {const status=card.querySelector('[data-debt-policy-message]');status.className='small text-danger';status.textContent=error.message;}
        finally {button.disabled=false;}
    });
    document.getElementById('mclDebtPolicyForm').addEventListener('submit', async event => {
        event.preventDefault();if (!active) return;
        const save=document.getElementById('mclDebtPolicySave');save.disabled=true;
        try {
            const data=Object.fromEntries(new FormData(event.target));data.policy_version=version;
            const result=await request(active.dataset.updateUrl,'PATCH',data);
            paint(active,result.policy,result.message);dialog.close();
        } catch(error) {document.getElementById('mclDebtPolicyError').textContent=error.message;}
        finally {save.disabled=false;}
    });
    dialog.querySelector('[data-close-debt-policy]').addEventListener('click',()=>dialog.close());
})();
</script>
