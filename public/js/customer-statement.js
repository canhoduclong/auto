(() => {
    const selector = document.getElementById('statement-limit');
    const selectedLimit = () => selector.value === 'all' ? Infinity : Number(selector.value);
    const formatDate = value => value.split('-').reverse().join('/');
    const dayNumber = value => {
        const [year, month, day] = value.split('-').map(Number);
        return Date.UTC(year, month - 1, day) / 86400000;
    };
    const updateSummaries = limit => {
        document.querySelectorAll('[data-statement-summary]').forEach(label => {
            const dates = [...label.closest('section').querySelectorAll('tbody tr[data-date]')]
                .slice(0, limit).map(row => row.dataset.date).sort();
            if (!dates.length) {
                label.textContent = 'Không có giao dịch trong kỳ đã chọn.';
                return;
            }
            const from = dates[0], to = dates[dates.length - 1];
            const days = Math.round(dayNumber(to) - dayNumber(from)) + 1;
            label.textContent = `${dates.length} ${label.dataset.unit} gần nhất · ${days} ngày (từ ${formatDate(from)} đến ${formatDate(to)})`;
        });
    };
    selector.addEventListener('change', () => {
        const limit = selectedLimit();
        document.querySelectorAll('[data-statement-rows]').forEach(body => {
            [...body.querySelectorAll('tr[data-date]')].forEach((row, index) => { row.hidden = index >= limit; });
        });
        document.querySelectorAll('[data-statement-range]').forEach(label => {
            const total = Number(label.dataset.total);
            label.textContent = `${Math.min(limit, total)} / ${total} giao dịch · Mới nhất trước`;
        });
        updateSummaries(limit);
    });
    window.addEventListener('beforeprint', () => updateSummaries(Infinity));
    window.addEventListener('afterprint', () => updateSummaries(selectedLimit()));
    document.getElementById('statement-print').addEventListener('click', () => window.print());
})();
