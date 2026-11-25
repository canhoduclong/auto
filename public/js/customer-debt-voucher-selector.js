document.querySelectorAll('[data-voucher-workspace]').forEach(workspace => {
    const form = workspace.querySelector('[data-voucher-create]');
    const initial = workspace.parentElement.querySelector('[data-voucher-selected-data]');
    const days = new Map(Object.entries(JSON.parse(initial?.textContent || '{}')));

    function purchaseUrl(href) {
        const url = new URL(href, window.location.href);
        for (const name of new Set(url.searchParams.keys())) {
            if (name.startsWith('voucher_selected[')) url.searchParams.delete(name);
        }
        Array.from(days.keys()).forEach((date, index) => url.searchParams.set(`voucher_selected[${index}]`, date));
        return url.href;
    }

    const money = new Intl.NumberFormat('vi-VN');
    const findButtons = () => [...workspace.querySelectorAll('[data-voucher-date]')];
    const list = form?.querySelector('[data-voucher-selected-list]');
    const fields = form?.querySelector('[data-voucher-purchase-fields]');
    const totalLabel = form?.querySelector('[data-voucher-purchase-total]');
    const amountInput = form?.querySelector('[data-voucher-amount]');
    const cutoff = form?.querySelector('[data-voucher-cutoff]');
    const dateInput = cutoff?.querySelector('input');
    const picker = workspace.querySelector('[data-voucher-picker]');
    const choices = JSON.parse(workspace.querySelector('[data-voucher-picker-data]')?.textContent || '[]');
    const search = picker?.querySelector('[data-voucher-picker-search]');
    const results = picker?.querySelector('[data-voucher-picker-results]');
    const formatDate = date => date.split('-').reverse().join('/');
    let swapDate;
    let pickerSource;
    let transferLimit = null;
    const transferForm = picker?.querySelector('[data-voucher-transfer-form]');

    function renderChoices() {
        const query = search.value.trim();
        results.replaceChildren();
        for (const {date, count, remaining} of choices) {
            const label = formatDate(date);
            if ((!label.includes(query) && !date.includes(query)) || date === pickerSource) continue;
            if (transferLimit === null && days.has(date)) continue;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action d-flex justify-content-between gap-2';
            const description = document.createElement('span');
            description.textContent = `${label} · ${count} Bill`;
            const amount = document.createElement('strong');
            amount.textContent = `${money.format(remaining)} đ`;
            button.append(description, amount);
            button.addEventListener('click', () => {
                if (transferLimit !== null) {
                    const moved = Math.min(remaining, transferLimit);
                    const shortfall = remaining - moved;
                    if (window.confirm(`Chuyển ${money.format(moved)} đ từ ngày ${formatDate(pickerSource)} sang ngày ${label}? Ngày nhận còn phải thu ${money.format(shortfall)} đ.`)) {
                        transferForm.elements.paid_date.value = pickerSource;
                        transferForm.elements.open_date.value = date;
                        transferForm.requestSubmit();
                    }
                    return;
                }
                if (swapDate) days.delete(swapDate);
                days.set(date, Number(remaining));
                picker.close();
                render();
                list.querySelector(`[data-voucher-swap-date="${date}"]`)?.focus();
            });
            results.append(button);
        }
        if (!results.childElementCount) results.textContent = 'Không tìm thấy ngày mua còn phải thu để chọn.';
    }

    function openPicker(date, paid = null) {
        transferLimit = paid;
        swapDate = days.has(date) ? date : null;
        if (paid === null && !swapDate && days.size >= 100) return;
        pickerSource = date;
        picker.querySelector('[data-voucher-picker-context]').textContent = paid !== null
            ? `Chọn ngày nhận khoản thu đã phân bổ cho ngày ${formatDate(date)}.`
            : swapDate
            ? `Chọn ngày thay cho ${formatDate(swapDate)}. Các ngày đã chọn khác vẫn được giữ.`
            : 'Chọn ngày mua còn phải thu để thêm vào phiếu.';
        search.value = '';
        renderChoices();
        picker.showModal();
        search.focus();
    }

    search?.addEventListener('input', renderChoices);
    let manualAmount = days.size ? '' : amountInput?.value;
    amountInput?.addEventListener('input', () => { manualAmount = amountInput.value; });

    function render() {
        if (!form) return;
        list.replaceChildren();
        fields.replaceChildren();
        let total = 0;
        for (const [date, amount] of [...days].sort()) {
            total += Number(amount);
            const label = formatDate(date);
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'purchase_dates[]';
            hidden.value = date;
            fields.append(hidden);

            const row = document.createElement('li');
            row.textContent = `${label} · ${money.format(amount)} đ `;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-link py-0';
            remove.textContent = 'Bỏ';
            remove.setAttribute('aria-label', `Bỏ ngày ${label} khỏi phiếu`);
            remove.addEventListener('click', () => {
                days.delete(date);
                render();
                (findButtons().find(button => button.dataset.voucherDate === date) ?? amountInput).focus();
            });
            const change = document.createElement('button');
            change.type = 'button';
            change.className = 'btn btn-sm btn-link py-0';
            change.textContent = 'Chọn Bill khác';
            change.dataset.voucherSwapDate = date;
            change.setAttribute('aria-label', `Đổi ngày mua ${label}`);
            change.addEventListener('click', () => openPicker(date));
            row.append(change, remove);
            list.append(row);
        }
        if (cutoff) {
            cutoff.hidden = days.size > 0;
            dateInput.disabled = days.size > 0;
        }
        amountInput.readOnly = !cutoff || days.size > 0;
        amountInput.value = !cutoff || days.size ? total : manualAmount;
        totalLabel.textContent = days.size ? `${days.size} ngày · Còn phải thu ${money.format(total)} đ` : 'Chưa chọn ngày mua.';
        for (const button of findButtons()) {
            const date = button.dataset.voucherDate;
            const selected = days.has(date);
            button.textContent = selected ? 'Bỏ' : 'Thêm';
            button.setAttribute('aria-label', `${button.textContent} ngày ${formatDate(date)}`);
            button.setAttribute('aria-pressed', String(selected));
            button.disabled = !selected && days.size >= 100;
        }
        workspace.querySelectorAll('[data-voucher-open-picker]').forEach(button => {
            button.disabled = days.size >= 100 && !days.has(button.dataset.voucherOpenPicker);
        });
        workspace.querySelectorAll('[data-voucher-purchase-pagination] a').forEach(link => {
            link.href = purchaseUrl(link.href);
        });
    }

    let loading;
    async function loadPurchases(href) {
        const url = purchaseUrl(href);
        const panel = workspace.querySelector('#debt-voucher-purchases');
        loading?.abort();
        const request = new AbortController();
        loading = request;
        panel.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {signal: request.signal});
            if (!response.ok) throw new Error('Could not load purchases');
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = html.querySelector('#debt-voucher-purchases');
            if (!next) throw new Error('Purchase list missing');
            if (request.signal.aborted) return;
            panel.replaceWith(next);
            render();
        } catch {
            if (!request.signal.aborted) window.location.assign(url);
        } finally {
            if (loading === request) {
                loading = null;
                panel.removeAttribute('aria-busy');
            }
        }
    }

    workspace.addEventListener('change', event => {
        if (event.target.matches('[data-voucher-purchase-size]')) loadPurchases(event.target.value);
    });
    workspace.addEventListener('click', event => {
        const transferTrigger = event.target.closest('[data-voucher-transfer]');
        if (transferTrigger) {
            openPicker(transferTrigger.dataset.voucherTransfer, Number(transferTrigger.dataset.paid));
            return;
        }
        const pickerTrigger = event.target.closest('[data-voucher-open-picker]');
        if (pickerTrigger) {
            openPicker(pickerTrigger.dataset.voucherOpenPicker);
            return;
        }
        const button = event.target.closest('[data-voucher-date]');
        if (button) {
            const date = button.dataset.voucherDate;
            if (days.has(date)) days.delete(date);
            else if (days.size < 100) days.set(date, Number(button.dataset.amount));
            render();
            return;
        }
        const link = event.target.closest('[data-voucher-purchase-pagination] a');
        if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && event.button === 0) {
            event.preventDefault();
            loadPurchases(link.href);
        }
    });
    render();
});
