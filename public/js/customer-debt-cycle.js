(() => {
    const form = document.querySelector('[data-cycle-form]');
    const adjustment = form?.querySelector('[data-cycle-adjustment]');
    if (!adjustment) return;

    const picker = form.querySelector('[data-cycle-bill-picker]');

    const editor = form.closest('[data-cycle-editor]');
    const bills = [...(picker?.querySelectorAll('[data-cycle-bill]') ?? [])];
    const rows = [...(picker?.querySelectorAll('[data-cycle-row]') ?? [])];
    const status = picker?.querySelector('[data-cycle-filter]');
    const empty = picker?.querySelector('[data-cycle-empty]');
    const all = picker?.querySelector('[data-cycle-all]');
    const selected = form.querySelector('[data-cycle-selected]');
    const count = picker?.querySelector('[data-cycle-count]');
    const search = picker?.querySelector('[data-cycle-search]');
    const assignedRows = editor.querySelector('[data-cycle-assigned-rows]');
    const assignedList = editor.querySelector('[data-cycle-assigned-list]');
    const assignedEmpty = editor.querySelector('[data-cycle-assigned-empty]');
    const money = new Intl.NumberFormat('vi-VN');
    const renderAssigned = checked => {
        const fragment = document.createDocumentFragment();
        checked.forEach(bill => {
            const row = document.createElement('tr');
            row.insertCell().textContent = bill.dataset.date;
            row.insertCell().textContent = bill.dataset.label;
            const amount = row.insertCell();
            amount.className = 'text-end';
            amount.textContent = `${money.format(Number(bill.dataset.sales) - Number(bill.dataset.gifts))} đ`;
            fragment.append(row);
        });
        assignedRows.replaceChildren(fragment);
        assignedList.hidden = checked.length === 0;
        assignedEmpty.hidden = checked.length > 0;
    };
    const visibleBills = () => bills.filter(bill => !bill.closest('tr').hidden);
    const update = (selectionChanged = false) => {
        const visible = visibleBills();
        const selectedVisible = visible.filter(bill => bill.checked).length;
        const checked = bills.filter(bill => bill.checked);
        const sales = picker ? checked.reduce((sum, bill) => sum + Number(bill.dataset.sales), 0) : Number(form.dataset.sales);
        const gifts = picker ? checked.reduce((sum, bill) => sum + Number(bill.dataset.gifts), 0) : Number(form.dataset.gifts);
        const manualAdjustment = Number(adjustment.value) || 0;
        const netNew = sales - gifts + Number(form.dataset.adjustments) + manualAdjustment;
        const closing = Number(form.dataset.opening) + netNew - Number(form.dataset.payments);
        if (picker) {
            selected.value = JSON.stringify(checked.map(bill => bill.value));
            if (selectionChanged) renderAssigned(checked);
            count.textContent = `${checked.length} bill đã chọn`;
            all.checked = visible.length > 0 && selectedVisible === visible.length;
            all.disabled = visible.length === 0;
            all.indeterminate = selectedVisible > 0 && !all.checked;
        }
        const totals = {sales, gifts, manual_adjustment: manualAdjustment, net_new: netNew, closing: Math.abs(closing), closing_signed: closing};
        editor.querySelectorAll('[data-cycle-total]').forEach(field => {
            field.textContent = `${money.format(totals[field.dataset.cycleTotal])} đ`;
        });
        editor.querySelector('[data-cycle-closing-label]').textContent =
            closing < 0 ? 'Khách trả trước cuối kỳ' : 'Còn phải thu cuối kỳ';
    };

    adjustment.addEventListener('input', () => update());
    if (!picker) {
        update();
        return;
    }
    picker.addEventListener('change', event => {
        if (event.target === all) visibleBills().forEach(bill => { bill.checked = all.checked; });
        update(true);
    });
    const filter = () => {
        const key = search.value.trim().toLocaleLowerCase('vi');
        rows.forEach(row => {
            row.hidden = (status.value === 'available' && row.hasAttribute('data-cycle-unavailable'))
                || !row.textContent.toLocaleLowerCase('vi').includes(key);
        });
        empty.hidden = rows.some(row => !row.hidden);
        update();
    };
    search.addEventListener('input', filter);
    status.addEventListener('change', filter);
    picker.disabled = false;
    filter();
})();
