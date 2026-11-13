(() => {
    document.getElementById('statement-limit').addEventListener('change', event => {
        const limit = event.target.value === 'all' ? Infinity : Number(event.target.value);
        document.querySelectorAll('[data-statement-rows]').forEach(body => {
            const rows = [...body.rows];
            if (rows[0]?.querySelector('.empty')) return;
            rows.forEach((row, index) => { row.hidden = index >= limit; });
        });
        document.querySelectorAll('[data-statement-range]').forEach(label => {
            const total = Number(label.dataset.total);
            label.textContent = `${Math.min(limit, total)} / ${total} giao dịch · Mới nhất trước`;
        });
    });
    document.getElementById('statement-print').addEventListener('click', () => window.print());
})();
