(() => {
    const dialog = document.querySelector('#sheet-customer-match');
    if (!dialog) return;

    const search = dialog.querySelector('[data-match-search]');
    const filter = dialog.querySelector('[data-match-filter]');
    const count = dialog.querySelector('[data-match-count]');
    const normalize = text => text.toLowerCase().normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').replaceAll('đ', 'd').trim();
    const customers = Array.from(dialog.querySelectorAll('[data-match-customer]'), row => ({
        row,
        text: normalize(row.dataset.search),
    }));

    function filterCustomers() {
        const terms = normalize(search.value).split(/\s+/);
        let visible = 0;
        for (const {row, text} of customers) {
            const show = (filter.value === 'all' || filter.value === row.dataset.coded)
                && terms.every(term => text.includes(term));
            row.classList.toggle('d-none', !show);
            row.classList.toggle('d-flex', show);
            if (show) visible++;
        }
        count.textContent = visible ? `${visible} khách phù hợp` : 'Không tìm thấy khách phù hợp.';
    }

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-open-customer-match]');
        if (!trigger) return;
        dialog.querySelector('[data-match-key]').value = trigger.dataset.sourceKey;
        dialog.querySelector('[data-match-source]').textContent = trigger.dataset.sourceName;
        search.value = '';
        filter.value = 'all';
        filterCustomers();
        dialog.showModal();
    });
    search.addEventListener('input', filterCustomers);
    filter.addEventListener('change', filterCustomers);
})();

(() => {
    for (const panel of document.querySelectorAll('[data-sheet-panel]')) {
        const side = panel.dataset.sheetPanel;
        const searchKey = `${side}_search`;
        const pageKey = side === 'system' ? 'page' : 'source_page';
        let pending;

        async function loadPanel(parameters) {
            pending?.abort();
            const controller = new AbortController();
            pending = controller;
            const url = new URL(window.location.href);
            const keys = side === 'source' ? [searchKey, pageKey, 'show_same_name'] : [searchKey, pageKey];
            for (const key of keys) {
                const value = parameters.get(key);
                if (value) url.searchParams.set(key, value);
                else url.searchParams.delete(key);
            }
            url.searchParams.set('panel', side);
            panel.setAttribute('aria-busy', 'true');
            panel.querySelector('[data-sheet-error]').hidden = true;
            try {
                const response = await fetch(url, {
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    signal: controller.signal,
                });
                if (!response.ok || response.redirected) throw new Error('Cannot load customer list');
                const markup = await response.text();
                if (controller.signal.aborted) return;

                panel.innerHTML = markup;
                const location = new URL(window.location.href);
                for (const key of keys) {
                    const value = url.searchParams.get(key);
                    if (value) location.searchParams.set(key, value);
                    else location.searchParams.delete(key);
                }
                history.replaceState(history.state, '', location);
            } catch {
                if (controller.signal.aborted) return;
                const error = panel.querySelector('[data-sheet-error]');
                error.textContent = 'Không tải được danh sách khách. Vui lòng thử lại.';
                error.hidden = false;
            } finally {
                if (pending === controller) {
                    pending = null;
                    panel.removeAttribute('aria-busy');
                }
            }
        }

        panel.addEventListener('submit', event => {
            if (!event.target.matches('[data-sheet-search]')) return;
            event.preventDefault();
            const parameters = new URLSearchParams(new FormData(event.target));
            parameters.delete(pageKey);
            loadPanel(parameters);
        });

        panel.addEventListener('click', event => {
            const link = event.target.closest('[data-sheet-pages] a[href]');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            const parameters = new URL(link.href).searchParams;
            loadPanel(parameters);
        });
    }
})();

document.addEventListener('submit', event => {
    if (event.target.matches('[data-unlink-sheet-customer]')
        && !window.confirm('Gỡ khách Google Sheet khỏi báo cáo của khách hệ thống này?')) {
        event.preventDefault();
    }
});
