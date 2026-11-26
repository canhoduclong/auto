const customerExport = document.querySelector('#customer-export-selection');
if (customerExport) {
    const key = customerExport.dataset.exportKey;
    const form = customerExport.querySelector('[data-export-form]');
    const list = customerExport.querySelector('[data-export-list]');
    const count = customerExport.querySelector('[data-export-count]');
    const clear = customerExport.querySelector('[data-export-clear]');
    const all = customerExport.querySelector('[data-export-all]');
    const status = customerExport.querySelector('[data-export-status]');
    let selected = new Map();

    try {
        selected = new Map(JSON.parse(sessionStorage.getItem(key) || '[]'));
    } catch {
        // The current page can still export when browser storage is unavailable.
    }

    function renderSelection() {
        try {
            sessionStorage.setItem(key, JSON.stringify([...selected]));
        } catch {
            // Keep the in-memory selection for this page.
        }
        count.textContent = `${selected.size} khách trong danh sách xuất`;
        form.elements.selection.value = JSON.stringify([...selected.keys()]);
        form.querySelector('button').disabled = !selected.size;
        clear.disabled = !selected.size;
        list.replaceChildren();
        for (const [id, name] of selected) {
            const row = document.createElement('li');
            row.className = 'list-group-item d-flex align-items-center justify-content-between gap-2 py-1';
            const label = document.createElement('span');
            label.textContent = name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-link';
            remove.textContent = 'Bỏ';
            remove.setAttribute('aria-label', `Bỏ ${name} khỏi danh sách xuất`);
            remove.addEventListener('click', () => {
                selected.delete(id);
                renderSelection();
                count.focus();
            });
            row.append(label, remove);
            list.append(row);
        }
    }

    customerExport.querySelector('[data-export-add]').addEventListener('click', () => {
        const checked = document.querySelectorAll('[data-customer-name]:checked');
        checked.forEach(box => selected.set(Number(box.value), box.dataset.customerName));
        renderSelection();
        status.textContent = checked.length ? 'Đã thêm khách đã chọn vào danh sách xuất.' : 'Hãy tích ít nhất một khách trong bảng.';
    });

    all.addEventListener('click', async () => {
        all.disabled = true;
        status.textContent = 'Đang lấy danh sách khách theo bộ lọc…';
        try {
            const response = await fetch(customerExport.dataset.exportChoices, {headers: {Accept: 'application/json'}});
            if (!response.ok) throw new Error();
            const customers = await response.json();
            customers.forEach(customer => selected.set(Number(customer.id), customer.name));
            renderSelection();
            status.textContent = customers.length ? `Đã chọn ${customers.length} khách theo bộ lọc.` : 'Không có khách phù hợp bộ lọc.';
        } catch {
            status.textContent = 'Chưa lấy được danh sách khách. Vui lòng thử lại.';
        } finally {
            all.disabled = false;
        }
    });

    clear.addEventListener('click', () => {
        selected.clear();
        renderSelection();
        status.textContent = 'Đã bỏ chọn tất cả khách trong danh sách xuất.';
    });

    renderSelection();
}
