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

    for (const trigger of document.querySelectorAll('[data-open-customer-match]')) {
        trigger.addEventListener('click', () => {
            dialog.querySelector('[data-match-key]').value = trigger.dataset.sourceKey;
            dialog.querySelector('[data-match-source]').textContent = trigger.dataset.sourceName;
            search.value = '';
            filter.value = 'all';
            filterCustomers();
            dialog.showModal();
        });
    }
    search.addEventListener('input', filterCustomers);
    filter.addEventListener('change', filterCustomers);
})();
