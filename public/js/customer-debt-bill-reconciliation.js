document.querySelectorAll('[data-bill-match]').forEach(form => {
    const money = new Intl.NumberFormat('vi-VN');
    const output = form.querySelector('[data-bill-totals]');
    const button = form.querySelector('button[type="submit"]');
    const confirmEmpty = form.querySelector('[data-bill-confirm-empty]');
    const exact = form.querySelector('[data-bill-match-exact]');
    const selectExact = form.querySelector('[data-bill-select-exact]');
    const selection = form.querySelectorAll('input[data-amount]');
    const billList = form.querySelector('[data-bill-staged]');
    const paymentList = form.querySelector('[data-payment-staged]');

    function stagedTotal(name, list) {
        const selected = Array.from(form.querySelectorAll(`input[name="${name}"]:checked`));
        list.replaceChildren();
        if (selected.length === 0) {
            const empty = document.createElement('li');
            empty.textContent = 'Chưa chọn';
            list.append(empty);
        }
        return selected.reduce((sum, input) => {
            const amount = BigInt(input.dataset.amount);
            const row = document.createElement('li');
            row.textContent = `${input.dataset.label} · ${money.format(amount)} đ`;
            if (!input.disabled) {
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-sm btn-link py-0';
                remove.textContent = 'Bỏ chọn';
                remove.setAttribute('aria-label', `Bỏ chọn ${input.dataset.label}`);
                remove.addEventListener('click', () => {
                    input.checked = false;
                    update();
                    input.focus();
                });
                row.append(' ', remove);
            }
            list.append(row);
            return sum + amount;
        }, 0n);
    }

    function update() {
        const bills = stagedTotal('bill_keys[]', billList);
        const payments = stagedTotal('payment_keys[]', paymentList);
        const applied = bills < payments ? bills : payments;
        output.textContent = `Sẽ phân bổ ${money.format(applied)} đ · Thu thiếu ${money.format(bills - applied)} đ · Thu dư ${money.format(payments - applied)} đ`;
        button.disabled = applied === 0n;
        if (confirmEmpty) {
            const empty = bills === 0n && payments === 0n;
            confirmEmpty.hidden = !empty;
            button.hidden = empty;
        }
    }

    const selectExactLabel = selectExact?.textContent.trim();
    selectExact?.addEventListener('click', () => {
        const selecting = exact.value !== '1';
        selection.forEach(input => {
            input.checked = selecting && input.hasAttribute('data-exact-pair');
            input.disabled = selecting;
        });
        exact.value = selecting ? '1' : '0';
        selectExact.textContent = selecting ? 'Bỏ chọn tất cả cặp' : selectExactLabel;
        update();
    });
    form.addEventListener('change', update);
    update();
});
