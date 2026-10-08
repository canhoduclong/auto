@push('styles')
<style>
.process-page{padding:20px;color:#24364b;font-size:14px}.process-page h1{font-size:24px;font-weight:700}.process-page h2{font-size:17px;font-weight:700}.process-card{border:1px solid #dce4ec;background:white;padding:18px;margin-bottom:16px;border-radius:8px}.process-table{width:100%;font-size:13px}.process-table th{background:#f1f5f9;font-weight:600;white-space:nowrap}.process-table th,.process-table td{padding:10px 12px;border-bottom:1px solid #e2e8f0;vertical-align:middle}.process-status{display:inline-block;padding:5px 9px;background:#eaf2ff;color:#235ea5;border-radius:5px;font-size:12px}.process-page .btn{font-size:13px}.process-steps{display:flex;flex-wrap:wrap;gap:8px}.process-event{border-left:3px solid #dce9e7;padding:8px 12px;margin-bottom:12px}.process-page input,.process-page select,.process-page textarea{max-width:100%}@media(max-width:767px){.process-page{padding:12px 6px}.process-card{padding:12px}.process-table{min-width:650px}.process-page .form-control,.process-page .form-select{font-size:16px}}
.shipping-timeline-heading{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;margin-bottom:18px;color:#235ea5}.shipping-timeline-nodes{list-style:none;display:flex;margin:0;padding:0;gap:0}.shipping-timeline-node{position:relative;flex:1;min-width:0;padding:0 12px 0 0}.shipping-timeline-node::before{content:'';position:absolute;top:11px;left:24px;right:0;height:2px;background:#dce4ed}.shipping-timeline-node:last-child::before{display:none}.shipping-timeline-dot{position:relative;display:flex;align-items:center;justify-content:center;background:#fff;border:2px solid #cad5e2;border-radius:50%;width:24px;height:24px;margin-bottom:10px;font-size:12px;font-weight:700}.shipping-timeline-label{font-weight:600;font-size:13px;margin-bottom:4px}.shipping-timeline-description{font-size:12px;line-height:1.5;color:#64758b}.shipping-timeline-node.is-done .shipping-timeline-dot{background:#e9f6ee;color:#278054;border-color:#278054}.shipping-timeline-node.is-done::before{background:#8ac5a3}.shipping-timeline-node.is-current .shipping-timeline-dot{border-color:#2563eb;box-shadow:0 0 0 4px #eaf1ff}.shipping-timeline-node.is-current .shipping-timeline-label{color:#2563eb}.shipping-timeline-node.is-revision .shipping-timeline-dot{border-color:#d39114;background:#fff5da}.shipping-timeline-node.is-rejected .shipping-timeline-dot{border-color:#d03939;color:#d03939;background:#fff0f0}.shipping-timeline-node.is-stopped{opacity:.6}.shipping-timeline-compact{min-width:260px;max-width:350px}.shipping-timeline-compact .shipping-timeline-heading{margin-bottom:10px}.shipping-timeline-compact .shipping-timeline-nodes{display:block}.shipping-timeline-compact .shipping-timeline-node{display:flex;gap:10px;padding-bottom:12px}.shipping-timeline-compact .shipping-timeline-node::before{left:11px;top:24px;bottom:0;right:auto;height:auto;width:2px}.shipping-timeline-compact .shipping-timeline-dot{flex-shrink:0;margin-bottom:0}.shipping-timeline-compact .shipping-timeline-label{margin:0}.shipping-timeline-compact .shipping-timeline-description{font-size:11px}@media(max-width:767px){.shipping-timeline-nodes{display:block}.shipping-timeline-node{display:flex;gap:12px;padding-bottom:18px}.shipping-timeline-dot{flex-shrink:0}.shipping-timeline-node::before{left:11px;top:24px;bottom:0;right:auto;width:2px;height:auto}}
.shipping-timeline-person{color:#526b85;font-size:12px;line-height:1.5;min-height:36px;padding-bottom:8px}.shipping-timeline-node::before{top:47px}.shipping-timeline-node.is-requested .shipping-timeline-dot{border-color:#d39114;background:#fff5da}.shipping-timeline-node.is-requested .shipping-timeline-description{color:#956006;font-weight:600}.shipping-timeline-node.is-revision .shipping-timeline-description{color:#235ea5;font-weight:700}.shipping-timeline-note{font-size:12px;line-height:1.5;color:#6c7d91;margin-top:5px;white-space:pre-wrap;overflow-wrap:anywhere}.shipping-timeline-compact .shipping-timeline-node{display:grid;grid-template-columns:24px minmax(0,1fr);column-gap:10px;row-gap:0}.shipping-timeline-compact .shipping-timeline-person{grid-column:2;min-height:0;padding-bottom:3px}.shipping-timeline-compact .shipping-timeline-node::before{top:46px}.shipping-timeline-compact .shipping-timeline-dot{grid-column:1;grid-row:1 / span 2}.shipping-timeline-compact .shipping-timeline-node>div:last-child{grid-column:2}@media(max-width:767px){.shipping-timeline-node{display:grid;grid-template-columns:24px minmax(0,1fr);column-gap:12px;row-gap:0}.shipping-timeline-person{grid-column:2;min-height:0;padding-bottom:4px}.shipping-timeline-dot{grid-column:1;grid-row:1 / span 2}.shipping-timeline-node>div:last-child{grid-column:2}.shipping-timeline-node::before{top:24px}.shipping-timeline-compact .shipping-timeline-node::before{top:24px}}
.shipping-timeline-entry{border-top:1px solid #e5ebf3;padding-top:7px;margin-top:8px;font-size:12px;color:#526b85;line-height:1.5}
.shipping-timeline-heading{justify-content:flex-end;margin-top:-26px;margin-bottom:24px}.shipping-timeline-compact .shipping-timeline-heading{margin-top:0;margin-bottom:12px}.shipping-timeline-entry{border-top:0;padding-top:0;margin-top:0;margin-bottom:7px}.shipping-timeline-entry-row{display:flex;align-items:baseline;flex-wrap:wrap;gap:5px 12px}.shipping-timeline-entry-row time{font-size:10px;white-space:nowrap}.shipping-timeline-entry-action{font-size:12px;color:#526b85}.shipping-timeline-entry-action.is-highlighted{font-weight:600;color:#a56b0b}.shipping-timeline-node.is-revision .shipping-timeline-description,.shipping-timeline-node.is-current .shipping-timeline-description{font-size:13px;font-weight:700;margin-top:8px;color:#1762b0}.shipping-timeline-person{min-height:40px}.shipping-timeline-node::before{top:51px}.shipping-timeline-compact .shipping-timeline-person{min-height:0}.shipping-timeline-compact .shipping-timeline-node::before{top:24px}@media(max-width:767px){.shipping-timeline-heading{margin-top:0;margin-bottom:16px}.shipping-timeline-person{min-height:0}.shipping-timeline-node::before{top:24px}}
.shipping-order-customer{font-size:16px;font-weight:700;line-height:1.45;color:#24364b;margin-bottom:5px;min-width:180px}.shipping-order-meta{font-size:12px;color:#6c7d91;line-height:1.5}.shipping-order-meta span{margin:0 4px}
.shipping-expense-toolbar .pagination{margin-bottom:0}.shipping-sort-link{color:inherit;text-decoration:none}.shipping-sort-link:hover{color:#0d9488}.shipping-sort-link span{margin-left:5px;color:#718096}#openExpenseRequest:disabled{background:#858b91;border-color:#858b91;color:white;opacity:1;cursor:not-allowed}#expenseRequestModal .modal-dialog{max-width:1240px;width:calc(100% - 48px);margin-left:auto;margin-right:auto}
#expenseRequestModal .modal-content{border:0;border-radius:12px;color:#24364b;font-size:14px;line-height:1.5}
#expenseRequestModal .modal-header{padding:20px 24px}
#expenseRequestModal .modal-title{font-size:18px;margin:0;font-weight:700}
#expenseRequestModal .modal-body{padding:24px}
#expenseRequestModal h2:not(.modal-title){font-size:15px;font-weight:600;margin-bottom:14px}
#expenseRequestModal .process-table{font-size:14px;min-width:580px}
#expenseRequestModal .process-table th{font-size:13px;font-weight:600;padding:12px 10px}
#expenseRequestModal .process-table td{padding:14px 10px}
#expenseRequestModal .shipping-order-customer{font-size:14px;font-weight:600;min-width:180px;margin-bottom:5px}
#expenseRequestModal .shipping-order-meta{font-size:12px;line-height:1.6}
#expenseRequestModal .expense-modal-date{font-size:13px;white-space:nowrap}
#expenseRequestModal .form-label{font-size:14px;font-weight:500;margin-bottom:8px}
#expenseRequestModal .form-control{font-size:14px;min-height:42px}
#expenseRequestModal .process-table input{min-width:110px!important;width:110px}
#expenseRequestModal textarea{min-height:130px}
#expenseRequestModal .small{font-size:12px;line-height:1.6}
#expenseRequestModal .btn{font-size:14px;font-weight:500;min-height:42px;padding:9px 16px}
#expenseRequestModal tfoot td{font-size:14px;background:#f8fafc}
@media(max-width:767px){#expenseRequestModal .modal-dialog{width:calc(100% - 24px)}#expenseRequestModal .modal-header,#expenseRequestModal .modal-body{padding:16px}#expenseRequestModal .form-control{font-size:16px}}

.shipping-expenses-page{min-width:0;max-width:100%}
.shipping-expense-filters{margin-left:0;margin-right:0}
.shipping-expenses-page .table-responsive{max-width:100%;overscroll-behavior-x:contain}
@media(max-width:767px){
.shipping-expenses-page{padding:16px 10px;font-size:14px}
.shipping-expenses-page h1{font-size:22px;line-height:1.35}
.shipping-expenses-page .nav{gap:6px!important}
.shipping-expenses-page .nav-link{font-size:14px;padding:10px 12px}
.shipping-expense-filters{--bs-gutter-x:12px;--bs-gutter-y:12px;padding:12px 6px}
.shipping-expense-filters>div{width:100%}
.shipping-expense-filters>div:nth-of-type(-n+2){width:50%}
.shipping-expense-filters .form-label{font-size:13px}
.shipping-expense-filters .btn{width:100%;min-height:42px}
.shipping-expense-toolbar>div{width:100%;gap:10px!important}
.shipping-expense-toolbar nav{width:100%;overflow-x:auto}
.shipping-expense-toolbar .pagination{flex-wrap:wrap}
.shipping-expenses-page .shipping-routes-table{min-width:0;display:block;width:100%}
.shipping-routes-table>thead{display:none}
.shipping-routes-table>tbody{display:block}
.shipping-routes-table>tbody>tr:not([data-route-orders]){display:grid;grid-template-columns:1fr 1fr;padding:12px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:12px;gap:8px}
.shipping-routes-table>tbody>tr>td{border:0;padding:0;min-width:0}
.shipping-routes-table>tbody>tr:not([data-route-orders])>td:first-child{grid-column:1/-1;font-size:16px}
.shipping-routes-table>tbody>tr:not([data-route-orders])>td[data-label]::before{content:attr(data-label);display:block;color:#64748b;font-size:12px;margin-bottom:3px}
.shipping-routes-table>tbody>tr:not([data-route-orders])>td:last-child{grid-column:1/-1;margin-top:6px}
.shipping-routes-table [data-route-send],.shipping-routes-table [data-route-toggle]{width:100%;min-height:42px;font-size:14px;white-space:normal}
.shipping-routes-table>tbody>[data-route-orders]:not([hidden]){display:block;margin-bottom:16px}
.shipping-routes-table>tbody>[data-route-orders]>td{display:block;width:100%}
#expenseRequestModal .modal-dialog{width:calc(100% - 16px);margin:8px auto;height:calc(100% - 16px)}
#expenseRequestModal .modal-title{font-size:17px;line-height:1.4;padding-right:10px}
#expenseRequestModal .modal-body,#expenseRequestModal .modal-header{padding:14px}
#expenseRequestModal .modal-body>.row{--bs-gutter-x:16px;--bs-gutter-y:20px}
#expenseRouteLabel{font-size:14px;padding:10px;overflow-wrap:anywhere}
#expenseRequestModal .process-table{min-width:0;width:100%}
#expenseRequestModal .process-table thead{display:none}
#expenseSelectedOrders{display:block}
#expenseSelectedOrders>tr{display:grid;grid-template-columns:28px minmax(0,1fr);gap:8px;padding:12px 0;border-bottom:1px solid #e2e8f0}
#expenseSelectedOrders>tr>td{padding:0;border:0;min-width:0}
#expenseSelectedOrders>tr>td:nth-child(n+3){grid-column:2;white-space:normal}
#expenseSelectedOrders>tr>td[data-label]::before{content:attr(data-label);display:block;font-size:12px;color:#64748b;margin-bottom:4px}
#expenseRequestModal .shipping-order-customer{min-width:0;font-size:15px}
#expenseRequestModal .shipping-order-meta{overflow-wrap:anywhere}
#expenseRequestModal .process-table input{width:100%;min-width:0!important;font-size:16px}
#expenseRequestModal tfoot{display:table;width:100%}
#expenseRequestModal tfoot td{padding:12px 6px}
#expenseRequestModal .btn{width:100%;min-height:44px}
#expenseRequestModal .btn-close{flex-shrink:0}
#expenseRequestModal .modal-body{padding-bottom:calc(16px + env(safe-area-inset-bottom))}
}
</style>
@endpush
