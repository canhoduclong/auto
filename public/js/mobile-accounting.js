(function () {
    if (window.__accountingMobileLayout) return;
    window.__accountingMobileLayout = true;
    document.documentElement.classList.add('accounting-mobile-view');
    let viewport = document.querySelector('meta[name="viewport"]');
    if (!viewport) { viewport = document.createElement('meta'); viewport.name = 'viewport'; document.head.appendChild(viewport); }
    viewport.content = 'width=device-width, initial-scale=1, viewport-fit=cover';
    const style = document.createElement('style');
    style.textContent = `
    @media screen {
        .accounting-mobile-view .acc-sidebar,.accounting-mobile-view .acc-topbar,.accounting-mobile-view .acc-sidebar-overlay{display:none!important}
        .accounting-mobile-view .acc-shell,.accounting-mobile-view body.acc-sidebar-collapsed .acc-shell{display:block!important}
        .accounting-mobile-view .acc-main{width:100%;min-width:0}
        .accounting-mobile-view .acc-content{padding:10px!important;min-width:0}
        .accounting-mobile-view body{background:#f4f6fb}
        .accounting-mobile-view .container,.accounting-mobile-view .container-fluid{max-width:100%!important;padding-left:0;padding-right:0}
        .accounting-mobile-view input,.accounting-mobile-view select,.accounting-mobile-view textarea{font-size:16px!important;max-width:100%;min-width:0}
        .accounting-mobile-view .table-responsive{-webkit-overflow-scrolling:touch;max-width:100%}
    }
    @media screen and (max-width:992px){
        .accounting-mobile-view .acc-content{font-size:14px;overflow-wrap:anywhere}
        .accounting-mobile-view .acc-card,.accounting-mobile-view .card,.accounting-mobile-view .card-body,.accounting-mobile-view .recon-panel,.accounting-mobile-view .recon-detail{min-width:0;max-width:100%}
        .accounting-mobile-view .card-body{padding:12px}
        .accounting-mobile-view .ds-filter{grid-template-columns:minmax(0,1fr)!important}
        .accounting-mobile-view .ds-filter>*{min-width:0}
        .accounting-mobile-view .ds-kpi,.accounting-mobile-view .ds-kpi.ds-kpi-journal{grid-template-columns:repeat(2,minmax(0,1fr))!important}
        .accounting-mobile-view .ds-kpi-item .lbl,.accounting-mobile-view .ds-kpi-item .val,.accounting-mobile-view .ds-kpi-item .sub{white-space:normal;overflow-wrap:anywhere;text-overflow:clip;line-height:1.4}
        .accounting-mobile-view .ds-kpi-item .val{font-size:17px}
        .accounting-mobile-view .ds-toolbar{align-items:flex-start}
        .accounting-mobile-view .ds-prod-row{grid-template-columns:20px minmax(0,1fr) 58px 86px;min-width:0}
        .accounting-mobile-view .ds-prod-row>*{min-width:0;overflow-wrap:anywhere}
        .accounting-mobile-view .ds-prod-row .hide-sm{display:none}
        .accounting-mobile-view .nav-tabs{display:flex;flex-wrap:wrap}
        .accounting-mobile-view .nav-tabs .nav-item{flex:1 1 140px;min-width:0}
        .accounting-mobile-view .nav-tabs .nav-link{font-size:13px;white-space:normal;padding:10px}
        .accounting-mobile-view .pagination{flex-wrap:wrap;gap:4px}
        .accounting-mobile-view .btn{min-height:40px;white-space:normal}
        .accounting-mobile-view .recon-grid,.accounting-mobile-view .recon-grid.filter-collapsed{grid-template-columns:minmax(0,1fr)!important}
        .accounting-mobile-view .recon-filter-panel,.accounting-mobile-view .recon-filter-panel.is-collapsed{position:static;width:100%;min-width:0}
        .accounting-mobile-view .recon-filter-actions{flex-shrink:0}
        .accounting-mobile-view .recon-panel .panel-head{flex-wrap:wrap}
        .accounting-mobile-view .recon-orders-table{min-width:0!important;width:100%;display:block}
        .accounting-mobile-view .recon-orders-table>thead{display:none}
        .accounting-mobile-view .recon-orders-table>tbody{display:block;width:100%}
        .accounting-mobile-view .recon-orders-table>tbody>tr:not(.d-none){display:grid;grid-template-columns:repeat(2,minmax(0,1fr));margin-bottom:12px;border:1px solid #dbe4ef;border-radius:10px;background:#fff}
        .accounting-mobile-view .recon-orders-table>tbody>tr>td{display:block;min-width:0;padding:10px!important;text-align:left!important;white-space:normal!important;overflow-wrap:anywhere}
        .accounting-mobile-view .recon-orders-table>tbody>tr>td[data-label]::before{content:attr(data-label);display:block;font-size:12px;color:#64748b;margin-bottom:4px}
        .accounting-mobile-view .recon-orders-table>tbody>tr>td:nth-child(3),.accounting-mobile-view .recon-orders-table>tbody>tr>td:last-child,.accounting-mobile-view .recon-orders-table>tbody>tr>td[colspan]{grid-column:1/-1}
        .accounting-mobile-view .recon-orders-table>tbody>.recon-inline-detail-row:not(.d-none){display:block;border:0;background:transparent}
        .accounting-mobile-view .recon-orders-table>tbody>.recon-inline-detail-row>td{width:100%;padding:0!important}
        .accounting-mobile-view .recon-inline-detail-row .recon-detail{padding:0!important}
        .accounting-mobile-view .recon-detail-layout,.accounting-mobile-view .recon-info-grid{display:grid;grid-template-columns:minmax(0,1fr)!important}
        .accounting-mobile-view .recon-detail-flow{position:static}
        .accounting-mobile-view .recon-kv{grid-template-columns:minmax(0,1fr);gap:3px}
        .accounting-mobile-view .recon-kv .v{margin-bottom:9px;overflow-wrap:anywhere}
        .accounting-mobile-view .recon-mini-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:8px;padding:6px 0}
        .accounting-mobile-view .recon-mini-row>*{min-width:0;overflow-wrap:anywhere;white-space:normal}
        .accounting-mobile-view .recon-detail-totals{width:100%!important}
        .accounting-mobile-view .recon-detail-toolbar{flex-wrap:wrap}
        .accounting-mobile-view .recon-bulk-toolbar{flex-direction:column;align-items:stretch}
        .accounting-mobile-view .recon-orders-table td:last-child>.d-flex{flex-wrap:wrap;gap:8px!important}
        .accounting-mobile-view .mobile-accounting-table,.accounting-mobile-view .mobile-accounting-table>tbody{display:block!important;width:100%!important;min-width:0!important}
        .accounting-mobile-view .mobile-accounting-table>thead{display:none!important}
        .accounting-mobile-view .mobile-accounting-table>tbody>tr{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:0;margin-bottom:12px;padding:8px;background:#fff;border:1px solid #dbe4ef;border-radius:10px}
        .accounting-mobile-view .mobile-accounting-table>tbody>tr>td{display:block!important;min-width:0;padding:8px!important;text-align:left!important;white-space:normal!important;border:0;grid-column:auto;overflow-wrap:anywhere}
        .accounting-mobile-view .mobile-accounting-table>tbody>tr>td[data-mobile-label]::before{content:attr(data-mobile-label);display:block;font-size:12px;line-height:1.4;color:#64748b;font-weight:500;margin-bottom:4px;max-width:100%}
        .accounting-mobile-view .mobile-accounting-table>tbody>tr>td.mobile-cell-wide,.accounting-mobile-view .mobile-accounting-table>tbody>tr>td[colspan]{grid-column:1/-1}
        .accounting-mobile-view .mobile-cell-value{display:block;min-width:0;max-width:100%;white-space:normal;overflow-wrap:anywhere;font-size:14px;line-height:1.5}
        .accounting-mobile-view .mobile-cell-value>*{max-width:100%!important;white-space:normal!important}
        .accounting-mobile-view .mobile-accounting-table>tfoot{display:block;width:100%}
        .accounting-mobile-view .mobile-accounting-table>tfoot>tr{display:block}
        .accounting-mobile-view .mobile-accounting-table>tfoot>tr>td{display:block;white-space:normal;max-width:100%;overflow-wrap:anywhere}
        .accounting-mobile-view .adjustment-layout{grid-template-columns:minmax(0,1fr)!important}
        .accounting-mobile-view .adjustment-side{position:static!important}
        .accounting-mobile-view .adjustment-summary{grid-template-columns:repeat(2,minmax(0,1fr))!important}
        .accounting-mobile-view .adjustment-card-body{padding:12px!important}
    }
    @media screen and (max-width:360px){
        .accounting-mobile-view .ds-kpi,.accounting-mobile-view .ds-kpi.ds-kpi-journal{grid-template-columns:minmax(0,1fr)!important}
        .accounting-mobile-view .ds-prod-row{grid-template-columns:18px minmax(0,1fr) 48px 75px}
    }`;
    document.head.appendChild(style);
    function adapt() {
        // Older server templates also converted the outer reconciliation table.
        // Keep its own order-card layout and adapt only its nested detail tables.
        document.querySelectorAll('.recon-orders-table').forEach(table => {
            table.classList.remove('mobile-accounting-table');
            Array.from(table.tBodies).forEach(body => Array.from(body.rows).forEach(row => {
                Array.from(row.cells).forEach(cell => { delete cell.dataset.mobileLabel; });
            }));
        });
        document.querySelectorAll('a[target="_blank"]').forEach(link => link.removeAttribute('target'));
        document.querySelectorAll('.ds-table, .recon-detail table').forEach(table => {
            const headers = Array.from(table.tHead?.rows[table.tHead.rows.length - 1]?.cells || []);
            if (!headers.length) return;
            table.classList.add('mobile-accounting-table');
            Array.from(table.tBodies).forEach(body => Array.from(body.rows).forEach(row => {
                Array.from(row.cells).forEach((cell, index) => {
                    if (cell.dataset.mobileAdapted) return;
                    const label = row.cells.length === headers.length ? headers[index].textContent.trim() : '';
                    if (label) cell.dataset.mobileLabel = label;
                    if (/khách hàng|hàng hóa|sản phẩm|biến thể|nội dung/i.test(label)) cell.classList.add('mobile-cell-wide');
                    const value = document.createElement('div'); value.className = 'mobile-cell-value';
                    while (cell.firstChild) value.appendChild(cell.firstChild);
                    cell.appendChild(value); cell.dataset.mobileAdapted = '1';
                });
            }));
        });
    }
    let pending = false;
    const observer = new MutationObserver(() => {
        if (pending) return;
        pending = true;
        requestAnimationFrame(() => { observer.disconnect(); adapt(); observer.observe(document.body,{childList:true,subtree:true}); pending=false; });
    });
    function start() { adapt(); observer.observe(document.body,{childList:true,subtree:true}); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',start,{once:true}); else start();
})();
