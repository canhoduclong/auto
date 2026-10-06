@if(session('mobile_accounting'))
<style media="screen">
    .acc-sidebar, .acc-topbar, .acc-sidebar-overlay { display: none !important; }
    .acc-shell, body.acc-sidebar-collapsed .acc-shell { display: block !important; }
    .acc-main { width: 100%; min-width: 0; }
    .acc-content { padding: 10px !important; }
    body { background: #f4f6fb; }
    .container, .container-fluid { max-width: 100% !important; padding-left: 10px; padding-right: 10px; }
    input, select, textarea { font-size: 16px !important; }
    .card, .acc-card { border-radius: 14px; }
    .table-responsive { -webkit-overflow-scrolling: touch; }
    @media(max-width:600px) {
        .mobile-accounting-table { min-width: 0 !important; width: 100%; }
        .mobile-accounting-table > thead { display: none; }
        .mobile-accounting-table > tbody { display: block; }
        .mobile-accounting-table > tbody > tr { display: block; margin-bottom: 12px; padding: 8px; background: #fff; border: 1px solid #dbe4ef; border-radius: 12px; }
        .mobile-accounting-table > tbody > tr > td { display: flex; justify-content: space-between; gap: 12px; padding: 6px !important; text-align: right !important; white-space: normal !important; }
        .mobile-accounting-table > tbody > tr > td[data-mobile-label]::before { content: attr(data-mobile-label); text-align: left; color: #64748b; font-size: 12px; flex: 0 0 38%; }
        .mobile-accounting-table > tfoot { display: table; width: 100%; }
        .adjustment-layout { grid-template-columns: minmax(0, 1fr) !important; }
        .adjustment-side { position: static !important; }
        .adjustment-summary { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        .adjustment-card-body { padding: 12px !important; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('a[target="_blank"]').forEach(link => link.removeAttribute('target'));
    document.querySelectorAll('table').forEach(function (table) {
        const headers = Array.from(table.querySelectorAll('thead tr:last-child th'));
        if (!headers.length) return;
        const rows = Array.from(table.querySelectorAll('tbody > tr'));
        if (!rows.some(row => row.cells.length === headers.length)) return;
        table.classList.add('mobile-accounting-table');
        rows.forEach(function (row) {
            if (row.cells.length !== headers.length) return;
            Array.from(row.cells).forEach(function (cell, index) {
                cell.dataset.mobileLabel = headers[index].textContent.trim();
            });
        });
    });
});
</script>
@endif
