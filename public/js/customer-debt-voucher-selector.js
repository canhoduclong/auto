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
    let manualAmount = days.size ? '' : amountInput?.value;
    amountInput?.addEventListener('input', () => { manualAmount = amountInput.value; });

    function render() {
        if (!form) return;
        list.replaceChildren();
        fields.replaceChildren();
        let total = 0;
        for (const [date, amount] of [...days].sort()) {
            total += Number(amount);
            const label = date.split('-').reverse().join('/');
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
            row.append(remove);
            list.append(row);
        }
        cutoff.hidden = days.size > 0;
        dateInput.disabled = days.size > 0;
        amountInput.readOnly = days.size > 0;
        amountInput.value = days.size ? total : manualAmount;
        totalLabel.textContent = days.size ? `${days.size} ngày · Còn phải thu ${money.format(total)} đ` : 'Chưa chọn ngày mua.';
        for (const button of findButtons()) {
            const selected = days.has(button.dataset.voucherDate);
            button.textContent = selected ? 'Bỏ' : 'Thêm';
            button.setAttribute('aria-pressed', String(selected));
            button.disabled = !selected && days.size >= 100;
        }
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
