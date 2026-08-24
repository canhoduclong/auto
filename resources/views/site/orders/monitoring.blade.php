@extends('layouts.site')

@push('styles')
<style>
    .monitor-page {
        --monitor-blue: #0b5d87;
        --monitor-teal: #087f78;
        --monitor-border: #dce6f1;
        --monitor-soft: #f5f8fc;
        background: #f8fafc;
        min-height: 75vh;
        padding: 28px 0 64px;
    }
    .monitor-shell { width: calc(100% - 32px); max-width: 1290px; margin: 0 auto; }
    .monitor-toolbar {
        display: grid;
        grid-template-columns: max-content minmax(0, 1fr);
        align-items: center;
        position: relative;
        z-index: 30;
        overflow: visible;
        column-gap: 14px;
        row-gap: 16px;
        padding: 5px 3px 16px;
    }
    .monitor-title {
        margin: 0;
        padding-bottom: 5px;
        border-bottom: 3px solid #f59e0b;
        color: #111827;
        font-size: 1.08rem;
        font-weight: 900;
        text-transform: uppercase;
        justify-self: start;
        grid-column: 1 / -1;
    }
    .monitor-date-actions,.monitor-date-form { display: flex; align-items: center; gap: 8px; position: relative; z-index: 31; overflow: visible; }
    .monitor-date-form .form-select { position: relative; z-index: 32; }
    .monitor-date-form .form-control { width: 160px; height: 36px; border-radius: 4px; }
    .monitor-date-form .form-select { width: 180px; height: 36px; border-radius: 4px; }
    .monitor-date-actions .btn { height: 36px; border-radius: 3px; font-weight: 700; }
    .monitor-panel {
        border: 1px solid var(--monitor-border);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .05);
    }
    .monitor-sequences {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 12px;
    }
    .monitor-sequence {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--monitor-state-color, #64748b);
        color: #fff;
        font-size: .78rem;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(15, 23, 42, .18);
    }
    .monitor-sequence:hover { background: var(--monitor-state-color, #64748b); color: #fff; filter: brightness(.88); transform: translateY(-1px); }
    .monitor-sequence.is-empty { cursor: default; }
    .monitor-sequence.status-pending { --monitor-state-color: #6b7280; }
    .monitor-sequence.status-approved { --monitor-state-color: #16a34a; }
    .monitor-sequence.status-packed { --monitor-state-color: #174a8b; }
    .monitor-sequence.status-transit { --monitor-state-color: #d97706; }
    .monitor-sequence.status-delivered { --monitor-state-color: #8b5e3c; }
    .monitor-sequence.status-accounted { --monitor-state-color: #581c87; }
    .monitor-sequence.status-cancelled { --monitor-state-color: #dc2626; }
    .monitor-summary-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px 8px;
    }
    .monitor-summary-toggle {
        border: 0;
        background: transparent;
        color: #64748b;
        font-size: .82rem;
    }
    .monitor-sort-group { display: flex; flex-wrap: wrap; gap: 6px; }
    .monitor-sort-group .btn { border-radius: 4px; font-size: .78rem; }
    .monitor-summary-table { min-height: 124px; padding: 12px; }
    .monitor-summary-table table { margin: 0; font-size: .8rem; }
    .monitor-summary-table thead th {
        border: 0;
        background: #eaf1f8;
        color: #334155;
        font-size: .68rem;
        letter-spacing: .05em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .monitor-summary-table th:first-child { border-radius: 9px 0 0 9px; }
    .monitor-summary-table th:last-child { border-radius: 0 9px 9px 0; }
    .monitor-summary-table td { border-color: #e8eef5; vertical-align: middle; }
    .monitor-summary-table .monitor-product-group td {
        border-top: 2px solid #cbdbe7;
        background: #eef5fa;
        color: #0f172a;
        font-weight: 800;
    }
    .monitor-summary-table .monitor-product-group:first-child td { border-top: 0; }
    .monitor-summary-table .monitor-variant-row td { background: #fff; }
    .monitor-summary-table .monitor-variant-name { padding-left: 26px; }
    .monitor-summary-table .monitor-variant-tree { color: #f97316; font-weight: 900; }
    .monitor-summary-table .monitor-variant-sku { display: block; margin-top: 2px; color: #94a3b8; font-size: .66rem; }
    .monitor-summary-table .monitor-stock-head { min-width: 112px; text-align: center; }
    .monitor-summary-table .monitor-stock-head span { display: block; color: #0f766e; font-size: .7rem; }
    .monitor-summary-table .monitor-stock-head small { display: block; margin-top: 2px; color: #64748b; font-size: .62rem; text-transform: none; }
    .monitor-summary-table .monitor-stock-cell { min-width: 112px; text-align: center; }
    .monitor-stock-value { color: #0f766e; font-size: .85rem; font-weight: 900; }
    .monitor-stock-value.is-low { color: #dc2626; }
    .monitor-stock-available { display: block; margin-top: 2px; color: #64748b; font-size: .64rem; white-space: nowrap; }
    .monitor-layout {
        display: grid;
        grid-template-columns: 260px minmax(0, 1fr);
        gap: 20px;
        margin-top: 0;
    }
    .monitor-content { min-width: 0; }
    .monitor-sidebar { display: grid; gap: 14px; align-content: start; }
    .monitor-tab-nav { display: grid; gap: 8px; }
    .monitor-tab-link {
        display: flex;
        min-height: 50px;
        align-items: center;
        gap: 11px;
        padding: 10px 14px;
        border: 1px solid var(--monitor-border);
        border-radius: 4px;
        background: #fff;
        color: #075985;
        font-size: .82rem;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .04);
    }
    .monitor-tab-link i { width: 18px; color: #527394; font-size: 1rem; text-align: center; }
    .monitor-tab-link:hover,
    .monitor-tab-link.active { border-color: #cfe3e8; background: #eaf5f6; color: #075985; }
    .monitor-tab-content { min-width: 0; }
    .monitor-tab-content .orders-page,
    .monitor-tab-content .drafts-page,
    .monitor-tab-content .schidx-page { padding: 0 0 30px; background: transparent; }
    .monitor-tab-content .orders-shell,
    .monitor-tab-content .drafts-shell { max-width: none; }
    .monitor-tab-content .container { max-width: 100%; padding-inline: 0; }
    .monitor-tab-schedules .schidx-daily-box { display: none; }
    .monitor-tab-automatic .schidx-page > .container > .row > .col-lg-3,
    .monitor-tab-automatic #schedTabToolbar,
    .monitor-tab-automatic #schedSummaryPanel,
    .monitor-tab-automatic #schedResultsWrap { display: none; }
    .monitor-tab-automatic .schidx-page > .container > .row > .col-lg-9 { width: 100%; }
    .monitor-tab-automatic .schidx-hero .schidx-kpi-grid { display: none; }
    .monitor-filter-block { overflow: hidden; }
    .monitor-filter-title {
        padding: 10px 14px;
        border-bottom: 1px solid var(--monitor-border);
        color: var(--monitor-blue);
        font-size: .78rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .monitor-filter-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 9px 13px;
        border-bottom: 1px solid #eef2f7;
        color: #075985;
        font-size: .82rem;
        font-weight: 700;
        text-decoration: none;
    }
    .monitor-filter-link:last-child { border-bottom: 0; }
    .monitor-filter-link:hover,
    .monitor-filter-link.active { background: #eaf5f6; color: #075985; }
    .monitor-filter-count {
        min-width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #64748b;
        color: #fff;
        font-size: .68rem;
    }
    .monitor-orders { display: grid; gap: 18px; margin-top: 8px; }
    .monitor-order {
        --monitor-state-color: #6b7280;
        --monitor-state-soft: #f3f4f6;
        scroll-margin-top: 100px;
        overflow: visible;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 200px;
        gap: 14px;
        width: 100%;
        border: 0;
        background: transparent;
        box-shadow: none;
    }
    .monitor-order-main {
        min-width: 0;
        padding: 18px 20px 16px;
        border: 1px solid var(--monitor-border);
        border-left: 6px solid var(--monitor-state-color);
        border-radius: 10px;
        background: linear-gradient(90deg, var(--monitor-state-soft) 0, #fff 150px);
        box-shadow: 0 5px 20px rgba(15, 23, 42, .05);
    }
    .monitor-order.status-pending { --monitor-state-color: #6b7280; --monitor-state-soft: #f3f4f6; }
    .monitor-order.status-approved { --monitor-state-color: #16a34a; --monitor-state-soft: #f0fdf4; }
    .monitor-order.status-packed { --monitor-state-color: #174a8b; --monitor-state-soft: #eff6ff; }
    .monitor-order.status-transit { --monitor-state-color: #d97706; --monitor-state-soft: #fffbeb; }
    .monitor-order.status-delivered { --monitor-state-color: #8b5e3c; --monitor-state-soft: #faf5f0; }
    .monitor-order.status-accounted { --monitor-state-color: #581c87; --monitor-state-soft: #faf5ff; }
    .monitor-order.is-cancelled .monitor-order-main {
        border-color: #ef4444;
        background: #fef2f2;
        box-shadow: 0 5px 20px rgba(220, 38, 38, .12);
    }
    .monitor-order.is-cancelled .monitor-order-number { background: #dc2626; }
    .monitor-order.is-cancelled .monitor-order-name { color: #b91c1c; }
    .monitor-order.is-cancelled .monitor-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #ef4444;
        background: #fee2e2;
        color: #b91c1c;
    }
    .monitor-order-head {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) minmax(250px, 280px);
        align-items: start;
        gap: 18px;
        padding-bottom: 10px;
        border-bottom: 1px solid #edf2f7;
    }
    .monitor-order-person { display: flex; align-items: center; gap: 12px; }
    .monitor-order-number {
        flex: 0 0 auto;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--monitor-state-color, #64748b);
        color: #fff;
        font-weight: 900;
    }
    .monitor-order-name { color: #075985; font-weight: 800; text-transform: uppercase; }
    .monitor-order-code { color: #64748b; font-size: .72rem; }
    .monitor-timeline { padding-top: 2px; }
    .monitor-timeline-track {
        position: relative;
        display: flex;
        justify-content: space-between;
        padding: 0 7px;
    }
    .monitor-timeline-track::before {
        content: "";
        position: absolute;
        top: 8px;
        left: 14px;
        right: 14px;
        height: 3px;
        background: #d9e4ef;
    }
    .monitor-timeline-progress {
        position: absolute;
        top: 8px;
        left: 14px;
        height: 3px;
        background: linear-gradient(90deg, #0e7490, #2563eb);
    }
    .monitor-timeline-dot {
        position: relative;
        z-index: 1;
        width: 18px;
        height: 18px;
        border: 2px solid #cbd9e8;
        border-radius: 50%;
        background: #fff;
    }
    .monitor-timeline-dot.done { border-color: #0e7490; background: #0e7490; }
    .monitor-timeline-dot.current {
        border-color: #2563eb;
        background: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .13);
    }
    .monitor-timeline-labels {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        margin-top: 5px;
        color: #64748b;
        font-size: .62rem;
        text-align: center;
    }
    .monitor-meta {
        display: grid;
        gap: 4px;
        padding: 10px 2px;
        color: #526071;
        font-size: .78rem;
    }
    .monitor-items { width: 100%; margin: 0; font-size: .76rem; }
    .monitor-items th {
        border-bottom: 1px solid #dfe8f2;
        color: #64748b;
        font-size: .64rem;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .monitor-items td { border-color: #edf2f7; vertical-align: middle; }
    .monitor-order-total { display: none; }
    .monitor-order-footer {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: flex-start;
        gap: 10px;
        padding: 4px 0;
        border: 0;
        background: transparent;
    }
    .monitor-status {
        display: none;
        padding: 5px 9px;
        border-radius: 999px;
        background: #eaf1f8;
        color: #334155;
        font-size: .7rem;
        font-weight: 800;
    }
    .monitor-actions { display: grid; align-content: start; gap: 8px; }
    .monitor-actions .btn {
        display: inline-flex;
        width: 100%;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 7px;
        font-size: .78rem;
        font-weight: 700;
        line-height: 1.25;
        text-align: center;
        white-space: normal;
    }
    .monitor-actions .btn i { flex: 0 0 auto; margin: 0 !important; font-size: .9rem; line-height: 1; }
    .monitor-actions form { width: 100%; margin: 0; }
    .monitor-actions .monitor-action-note { margin-top: -2px; font-size: .68rem; line-height: 1.25; text-align: center; }
    .monitor-actions .monitor-cancel-form { margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0; }
    .monitor-inline-edit { margin-top: 14px; border-top: 1px solid #dce6f1; }
    .monitor-inline-edit-form { padding: 14px 0 2px; }
    .monitor-inline-edit-title { margin-bottom: 10px; color: #075985; font-size: .82rem; font-weight: 900; text-transform: uppercase; }
    .monitor-edit-picker { margin-bottom: 12px; padding: 12px; border: 1px solid #dce6f1; border-radius: 9px; background: #f8fafc; }
    .monitor-edit-picker-label { color: #334155; font-size: .78rem; font-weight: 900; }
    .monitor-edit-selected-customer { color: #075985; font-size: .82rem; font-weight: 800; }
    .monitor-edit-picker-results { max-height: 280px; margin-top: 8px; overflow: auto; background: #fff; }
    .monitor-edit-product-search {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 88px;
        align-items: stretch;
        gap: 8px;
        margin-top: 10px;
    }
    .monitor-edit-product-search .form-control {
        width: 100%;
        min-width: 0;
        height: 38px;
        padding-inline: 11px;
        border-radius: 6px;
    }
    .monitor-edit-product-search-button {
        display: inline-flex;
        width: 88px;
        height: 38px;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 6px 10px;
        white-space: nowrap;
    }
    .monitor-edit-product-search-button i { margin: 0 !important; }
    .monitor-edit-product-results {
        max-height: 430px;
        margin-top: 12px;
        padding: 0 5px 0 0;
        overflow-x: hidden;
        overflow-y: auto;
        background: transparent;
        scrollbar-gutter: stable;
    }
    .monitor-edit-product-results .monitor-product-toolbar {
        display: grid;
        grid-template-columns: minmax(0, 1fr) max-content;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        padding: 0 2px;
    }
    .monitor-edit-product-results .monitor-product-toolbar p { min-width: 0; }
    .monitor-edit-product-results .monitor-product-toolbar > div { flex-wrap: nowrap; white-space: nowrap; }
    .monitor-edit-product-results .monitor-product-toolbar label { white-space: nowrap; }
    .monitor-edit-product-results .monitor-product-toolbar #per-page-select { width: 82px; min-width: 82px; }
    .monitor-edit-product-results .monitor-product-list { max-height: none; gap: 9px; overflow: visible; }
    .monitor-edit-product-results .monitor-product-card { border-color: #dce5ef; border-radius: 9px; box-shadow: 0 2px 7px rgba(15, 23, 42, .025); }
    .monitor-edit-product-results .monitor-product-choice { min-height: 72px; padding: 9px 11px; }
    .monitor-edit-product-results .monitor-product-main { overflow: hidden; }
    .monitor-edit-product-results .monitor-product-main > span:last-child { min-width: 0; }
    .monitor-edit-product-results .monitor-product-thumb { width: 50px; height: 50px; }
    .monitor-edit-product-results .monitor-product-name {
        overflow: hidden;
        color: #0f172a;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .monitor-edit-product-results .monitor-product-choice-label { min-width: 112px; text-align: right; white-space: nowrap; }
    .monitor-edit-product-results .monitor-variant-option:disabled { cursor: not-allowed; opacity: .65; }
    .monitor-inline-edit-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
    .monitor-inline-edit-fields .is-wide { grid-column: 1 / -1; }
    .monitor-inline-edit-fields label { margin-bottom: 3px; color: #475569; font-size: .68rem; font-weight: 800; }
    .monitor-edit-items { margin-bottom: 10px; font-size: .74rem; }
    .monitor-edit-items th { color: #64748b; font-size: .64rem; text-transform: uppercase; }
    .monitor-edit-quantity { width: 76px; min-width: 76px; }
    .monitor-edit-price-stepper { display: inline-flex; align-items: stretch; }
    .monitor-edit-price-stepper .btn { width: 32px; border-color: #cbd5e1; border-radius: 0; color: #0f766e; font-weight: 900; }
    .monitor-edit-price-stepper .btn:first-child { border-radius: 5px 0 0 5px; }
    .monitor-edit-price-stepper .btn:last-child { border-radius: 0 5px 5px 0; }
    .monitor-edit-price-stepper .btn:disabled { color: #94a3b8; background: #f1f5f9; opacity: 1; }
    .monitor-edit-price-value { min-width: 90px; display: inline-flex; align-items: center; justify-content: center; padding: 4px 7px; border-block: 1px solid #cbd5e1; background: #fff; color: #047857; font-weight: 900; white-space: nowrap; }
    .monitor-inline-edit-total { color: #047857; font-size: .9rem; font-weight: 900; text-align: right; }
    .monitor-inline-edit-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; }
    .monitor-order > .collapse { grid-column: 1; margin-top: -18px; border: 1px solid var(--monitor-border); border-radius: 0 0 10px 10px; background: #fff; }
    .monitor-empty { padding: 44px 20px; text-align: center; color: #64748b; }
    .monitor-pagination { padding: 14px 0 0; }
    .monitor-day-footer {
        margin-top: 18px;
        overflow: hidden;
        border: 1px solid #dce6f1;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .05);
    }
    .monitor-day-footer-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 14px;
        border-bottom: 1px solid #e5edf5;
        background: #f8fafc;
    }
    .monitor-day-footer-title {
        display: flex;
        align-items: center;
        gap: 7px;
        margin: 0;
        color: #075985;
        font-size: .82rem;
        font-weight: 900;
        text-transform: uppercase;
    }
    .monitor-day-footer-count {
        display: inline-flex;
        min-width: 22px;
        height: 22px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #e0f2fe;
        color: #0369a1;
        font-size: .7rem;
        font-weight: 800;
    }
    .monitor-day-note {
        display: grid;
        grid-template-columns: minmax(190px, .7fr) minmax(0, 1.3fr);
        gap: 14px;
        padding: 10px 14px;
        border-bottom: 1px solid #eef2f7;
    }
    .monitor-day-note:last-child { border-bottom: 0; }
    .monitor-day-note-order { min-width: 0; color: #0f172a; font-size: .76rem; }
    .monitor-day-note-order strong {
        display: block;
        overflow: hidden;
        color: #075985;
        text-overflow: ellipsis;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .monitor-day-note-meta { color: #64748b; font-size: .68rem; }
    .monitor-day-note-content { color: #334155; font-size: .78rem; line-height: 1.45; white-space: pre-line; }
    .monitor-day-note-content div + div { margin-top: 3px; }
    .monitor-day-note-label { color: #64748b; font-weight: 700; }
    .monitor-day-notes-empty { padding: 16px 14px; color: #64748b; font-size: .78rem; text-align: center; }
    .monitor-priority-legend {
        padding: 13px 14px 15px;
        border-top: 1px solid #dce6f1;
        background: #fbfdff;
    }
    .monitor-priority-legend-head {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 6px 14px;
        margin-bottom: 10px;
    }
    .monitor-priority-legend-title {
        margin: 0;
        color: #0f172a;
        font-size: .78rem;
        font-weight: 900;
        text-transform: uppercase;
    }
    .monitor-priority-legend-help { color: #64748b; font-size: .7rem; }
    .monitor-priority-legend-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 8px;
    }
    .monitor-priority-legend-item {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 9px;
        padding: 8px 10px;
        border: 1px solid #e5edf5;
        border-radius: 8px;
        background: #fff;
    }
    .monitor-priority-legend-number {
        width: 30px;
        height: 30px;
        flex: 0 0 30px;
        font-size: .72rem;
        pointer-events: none;
    }
    .monitor-priority-legend-copy { min-width: 0; line-height: 1.3; }
    .monitor-priority-legend-copy strong { display: block; color: #1e293b; font-size: .73rem; }
    .monitor-priority-legend-copy span { display: block; color: #64748b; font-size: .67rem; }
    .monitor-bulk-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        min-height: 54px;
        padding: 8px 0;
    }
    .monitor-bulk-actions > div { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .monitor-bulk-actions form { margin: 0; }
    .monitor-bulk-actions .form-select { width: 158px; min-height: 38px; }
    .monitor-bulk-actions .btn { min-height: 38px; padding-inline: 14px; }
    .monitor-view-switch { display: inline-flex; gap: 4px; }
    .monitor-view-switch .btn,
    .monitor-icon-action { width: 42px; padding-inline: 0 !important; }
    .monitor-source-panel { margin: 10px 0 14px; padding: 10px 12px; }
    .monitor-source-tabs { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
    .monitor-source-label { margin-right: 4px; color: #334155; font-size: .78rem; font-weight: 900; text-transform: uppercase; }
    .monitor-source-tab {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border: 1px solid #dbe4ee;
        border-radius: 5px;
        background: #fff;
        color: #475569;
        font-size: .76rem;
        font-weight: 700;
        text-decoration: none;
    }
    .monitor-source-tab:hover,
    .monitor-source-tab.active { border-color: #0f766e; background: #ecfdf5; color: #0f766e; }
    .monitor-source-tab-count { color: #64748b; font-size: .68rem; }
    .monitor-profit-summary { margin: 0 0 14px; padding: 13px; }
    .monitor-profit-summary-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 10px; }
    .monitor-profit-summary-title { margin: 0; color: #0f172a; font-size: .86rem; font-weight: 900; text-transform: uppercase; }
    .monitor-profit-summary-note { color: #64748b; font-size: .7rem; }
    .monitor-profit-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
    .monitor-profit-card { padding: 11px 12px; border: 1px solid #dbe4ee; border-radius: 7px; background: #f8fafc; }
    .monitor-profit-card-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
    .monitor-profit-card-name { color: #075985; font-size: .82rem; font-weight: 900; text-decoration: none; }
    .monitor-profit-card-count { color: #64748b; font-size: .68rem; }
    .monitor-profit-values { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    .monitor-profit-value small { display: block; color: #64748b; font-size: .62rem; text-transform: uppercase; }
    .monitor-profit-value strong { display: block; margin-top: 2px; color: #0f172a; font-size: .82rem; }
    .monitor-profit-value.is-profit strong { color: #15803d; }
    .monitor-profit-value.is-loss strong { color: #dc2626; }
    .monitor-profit-warning { margin-top: 7px; color: #b45309; font-size: .67rem; }
    /* Native select menus must be allowed to escape the table panel. */
    .monitor-simple-list { overflow: visible !important; }
    .monitor-simple-list table { margin: 0; font-size: .79rem; }
    .monitor-simple-list thead th { border-bottom: 2px solid #cbd5e1; background: #f8fafc; color: #1e293b; white-space: nowrap; }
    .monitor-simple-list td { border-color: #e2e8f0; vertical-align: middle; }
    .monitor-simple-list .monitor-list-customer { min-width: 150px; color: #075985; font-weight: 800; text-transform: uppercase; }
    .monitor-simple-list .monitor-list-products { min-width: 160px; }
    .monitor-simple-list .monitor-list-supplier { min-width: 205px; }
    .monitor-supplier-actions { display: flex; align-items: center; gap: 5px; }
    .monitor-supplier-actions form:first-child { flex: 1; }
    .monitor-supplier-actions .form-select { min-width: 150px; font-size: .75rem; }
    .monitor-supplier-remove { width: 32px; padding-inline: 0 !important; font-size: 1rem; line-height: 1; }
    .monitor-profit-incomplete { color: #b45309; font-size: .67rem; font-weight: 700; white-space: nowrap; }
    .monitor-auto-approval { margin: 0 0 18px; overflow: hidden; border: 1px solid #d3e0ed; border-radius: 10px; background: #fff; }
    .monitor-auto-approval-head { display: flex; min-height: 40px; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 14px; border-bottom: 1px solid #dce6f1; color: #075985; font-size: .76rem; font-weight: 900; text-transform: uppercase; }
    .monitor-auto-approval-head-meta { display: flex; align-items: center; justify-content: flex-end; gap: 18px; }
    .monitor-auto-approval-head .monitor-auto-owner { color: #64748b; font-size: .66rem; font-weight: 400; text-transform: lowercase; }
    .monitor-auto-close { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border: 0; background: transparent; color: #0f172a; font-size: 1.25rem; font-weight: 300; line-height: 1; }
    .monitor-auto-close:hover { color: #9a3412; }
    .monitor-auto-approval-body { padding: 15px 18px 14px; }
    .monitor-auto-approval-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 27px; }
    .monitor-auto-rule-title { margin: 0 0 10px; padding: 0 0 7px; border-bottom: 1px solid #334155; color: #0f172a; font-size: .75rem; font-weight: 900; text-transform: uppercase; }
    .monitor-auto-rule-toggle { display: flex; min-height: 40px; align-items: center; gap: 8px; margin: 0 0 10px; padding: 8px 14px; border-radius: 0 6px 6px 0; background: #effcf4; }
    .monitor-auto-rule-toggle .form-check-input { flex: 0 0 auto; margin: 0; }
    .monitor-auto-rule-toggle .form-check-label { color: #0f172a; font-size: .88rem; font-weight: 900; }
    .monitor-auto-rule-options { display: grid; gap: 9px; padding: 2px 14px 0; }
    .monitor-auto-rule-options > .form-check { min-height: 22px; margin: 0; }
    .monitor-auto-rule-options .form-check-label { color: #334155; font-size: .72rem; }
    .monitor-auto-bulk-fields { display: grid; grid-template-columns: 175px 145px; gap: 10px; margin: 1px 0 0 24px; }
    .monitor-auto-bulk-fields label { display: block; margin-bottom: 4px; color: #52617a; font-size: .65rem; font-weight: 900; }
    .monitor-auto-bulk-fields .form-control { height: 32px; font-size: .76rem; }
    .monitor-auto-bottom { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-top: 13px; padding-top: 10px; border-top: 1px dashed #dce6f1; }
    .monitor-auto-help { display: grid; gap: 4px; color: #64748b; font-size: .68rem; line-height: 1.4; }
    .monitor-auto-footer { flex: 0 0 auto; }
    .monitor-auto-footer .btn { min-height: 32px; padding: 6px 12px; border-color: #98572f; background: #98572f; color: #fff; font-size: .72rem; font-weight: 700; }
    .monitor-auto-footer .btn:hover, .monitor-auto-footer .btn:focus { border-color: #7f4525; background: #7f4525; color: #fff; }
    .monitor-auto-status { display: none; margin-top: 10px; padding: 8px 12px; border-radius: 6px; font-size: .76rem; font-weight: 600; }
    .monitor-auto-status.is-visible { display: block; }
    .monitor-auto-status.is-success { background: #dcfce7; color: #166534; }
    .monitor-auto-status.is-error { background: #fee2e2; color: #991b1b; }
    .monitor-summary-toggle { border: 1px solid var(--monitor-teal); color: var(--monitor-teal); }
    .monitor-sequence-panel {
        position: sticky;
        top: 12px;
        z-index: 20;
        min-height: 68px;
        background: #fff;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .12);
    }
    .monitor-summary-panel {
        overflow: hidden;
        margin-bottom: 0 !important;
        border: 0;
        background: transparent;
        box-shadow: none;
    }
    .monitor-summary-panel .collapse.show,
    .monitor-summary-panel .collapsing {
        margin-bottom: 16px;
        border: 1px solid var(--monitor-border);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .05);
    }
    .monitor-create { margin-bottom: 18px; overflow: hidden; }
    .monitor-create[hidden] { display: none !important; }
    .monitor-create-head { padding: 16px 18px 0; }
    .monitor-create-head h2 { margin: 0; color: #9a3412; font-size: 1rem; font-weight: 900; text-transform: uppercase; }
    .monitor-create-steps {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        padding: 18px 24px 14px;
    }
    .monitor-create-step { position: relative; color: #64748b; text-align: center; font-size: .75rem; font-weight: 700; }
    .monitor-create-step:not(:last-child)::after {
        content: "";
        position: absolute;
        z-index: 0;
        top: 17px;
        left: calc(50% + 24px);
        width: calc(100% - 48px);
        height: 2px;
        background: #fed7aa;
    }
    .monitor-create-step-number {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        margin: 0 auto 6px;
        border-radius: 50%;
        background: #64748b;
        color: #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, .2);
    }
    .monitor-create-step.is-active { color: #9a3412; }
    .monitor-create-step.is-active .monitor-create-step-number,
    .monitor-create-step.is-done .monitor-create-step-number { background: #c2410c; }
    .monitor-create-body { padding: 8px 18px 18px; }
    .monitor-create-pane[hidden] { display: none !important; }
    .monitor-create-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; }
    .monitor-create-search { display: flex; gap: 8px; margin-bottom: 12px; }
    .monitor-create-search .form-control { min-width: 0; }
    .monitor-selected-table { border: 1px solid #fed7aa; border-radius: 9px; overflow: hidden; background: #fffaf3; }
    .monitor-selected-table table { margin: 0; font-size: .78rem; }
    .monitor-selected-table .monitor-item-quantity { width: 78px; }
    .monitor-sale-price { min-width: 170px; }
    .monitor-price-stepper { display: inline-flex; align-items: stretch; }
    .monitor-price-stepper .btn {
        width: 34px;
        border-color: #cbd5e1;
        border-radius: 0;
        color: #0f766e;
        font-size: 1rem;
        font-weight: 900;
    }
    .monitor-price-stepper .btn:first-child { border-radius: 5px 0 0 5px; }
    .monitor-price-stepper .btn:last-child { border-radius: 0 5px 5px 0; }
    .monitor-price-stepper .btn:disabled { color: #94a3b8; background: #f1f5f9; opacity: 1; }
    .monitor-sale-price-value {
        min-width: 92px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 8px;
        border-block: 1px solid #cbd5e1;
        background: #fff;
        color: #047857;
        font-size: .84rem;
        font-weight: 900;
        white-space: nowrap;
    }
    .monitor-create-total { padding: 10px 12px; border-top: 1px solid #fed7aa; text-align: right; color: #b45309; font-weight: 900; }
    .monitor-customer-selected { padding: 12px 14px; border: 1px solid #99f6e4; border-radius: 8px; background: #f0fdfa; }
    .monitor-confirm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .monitor-confirm-card { padding: 14px; border: 1px solid var(--monitor-border); border-radius: 9px; background: #f8fafc; }
    .monitor-confirm-card h3 { font-size: .82rem; font-weight: 900; text-transform: uppercase; }
    .monitor-finish { padding: 24px; text-align: center; }
    .monitor-finish-icon { color: #059669; font-size: 2.8rem; }
    .monitor-product-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .monitor-product-list { display: grid; gap: 8px; max-height: 400px; overflow-y: auto; }
    .monitor-product-card { border: 1px solid #e5e7eb; border-radius: 9px; background: #fff; overflow: hidden; }
    .monitor-product-card.is-open { border-color: #0f766e; box-shadow: 0 0 0 2px rgba(15, 118, 110, .08); }
    .monitor-product-choice { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 12px; padding: 10px; border: 0; background: #fff; color: #0f172a; text-align: left; }
    .monitor-product-main { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .monitor-product-thumb { width: 52px; height: 52px; flex: 0 0 auto; border-radius: 7px; object-fit: cover; }
    .monitor-product-name, .monitor-product-meta { display: block; }
    .monitor-product-name { font-size: .84rem; }
    .monitor-product-meta { margin-top: 3px; color: #64748b; font-size: .7rem; }
    .monitor-product-choice-label { flex: 0 0 auto; color: #0f766e; font-size: .75rem; font-weight: 800; }
    .monitor-product-card.is-open .monitor-product-choice-label i { transform: rotate(180deg); }
    .monitor-product-variants { padding: 10px; border-top: 1px solid #e5e7eb; background: #f8fafc; }
    .monitor-variant-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    .monitor-variant-option { display: grid; gap: 2px; min-height: 98px; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #334155; text-align: left; font-size: .72rem; }
    .monitor-variant-option:hover, .monitor-variant-option.is-selected { border-color: #0f766e; background: #ecfdf5; }
    .monitor-variant-option.is-selected { box-shadow: inset 0 0 0 1px #0f766e; }
    .monitor-variant-size { color: #0f172a; font-size: .84rem; font-weight: 900; }
    .monitor-variant-option small { color: #64748b; }
    .monitor-variant-option .monitor-variant-availability { font-weight: 800; }
    .monitor-variant-option .monitor-variant-availability.is-available { color: #047857; }
    .monitor-variant-option .monitor-variant-availability.is-unavailable { color: #b45309; }
    .monitor-variant-option .monitor-variant-inventory,
    .monitor-variant-option .monitor-variant-production { color: #64748b; }
    .monitor-create .pagination { margin-bottom: 0; }
    .monitor-adjustment-host { display: none; grid-column: 1 / -1; }
    .monitor-adjustment-host.is-open { display: block; }
    .monitor-adjustment-loading { padding: 28px; border: 1px solid #fde68a; border-radius: 9px; background: #fffbeb; color: #92400e; text-align: center; }
    .monitor-adjustment-form { padding: 16px; border: 1px solid #f59e0b; border-radius: 9px; background: #fffbeb; box-shadow: 0 5px 18px rgba(146, 64, 14, .08); }
    .monitor-adjustment-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid #fde68a; color: #7c2d12; }
    .monitor-adjustment-heading strong { display: block; font-size: 1rem; text-transform: uppercase; }
    .monitor-adjustment-heading span { display: block; margin-top: 2px; color: #64748b; font-size: .75rem; }
    .monitor-adjustment-fields, .monitor-adjustment-details { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 24px; margin-top: 14px; }
    .monitor-adjustment-form label { display: block; margin-bottom: 4px; color: #334155; font-size: .75rem; font-weight: 800; }
    .monitor-adjustment-items { min-width: 800px; font-size: .75rem; }
    .monitor-adjustment-items th { color: #64748b; font-size: .65rem; text-transform: uppercase; }
    .monitor-adjustment-items td:first-child { min-width: 180px; }
    .monitor-adjustment-items td:first-child small, .monitor-adjustment-fee-row small { display: block; color: #64748b; }
    .monitor-adjustment-items input { min-width: 82px; }
    .monitor-adjustment-return { max-width: 420px; margin-top: 12px; }
    .monitor-adjustment-add-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
    .monitor-adjustment-picker { margin-top: 10px; padding: 12px; border: 1px solid #fed7aa; border-radius: 8px; background: #fff; }
    .monitor-adjustment-product-results { max-height: 460px; overflow-y: auto; }
    .monitor-adjustment-fee-row { display: grid !important; grid-template-columns: 24px minmax(140px, 1fr) minmax(150px, 230px); align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid #eef2f7; }
    .monitor-adjustment-submit { display: flex; justify-content: flex-end; margin-top: 12px; padding-top: 12px; border-top: 1px solid #fde68a; }
    @media (max-width: 1199.98px) {
        .monitor-shell { max-width: 960px; }
        .monitor-layout { grid-template-columns: 220px minmax(0, 1fr); gap: 14px; }
        .monitor-order { grid-template-columns: minmax(0, 1fr) 180px; }
    }
    @media (max-width: 991.98px) {
        .monitor-layout { grid-template-columns: 1fr; }
        .monitor-sidebar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .monitor-sequence-panel { top: 0; }
        .monitor-order { width: 100%; grid-template-columns: minmax(0, 1fr) 180px; }
    }
    @media (max-width: 767.98px) {
        .monitor-simple-list { overflow-x: auto !important; overflow-y: visible !important; }
        .monitor-page { padding-top: 18px; }
        .monitor-toolbar { grid-template-columns: 1fr; align-items: flex-start; row-gap: 10px; padding-top: 0; }
        .monitor-toolbar > * { grid-column: 1; }
        .monitor-date-actions,.monitor-date-form { width: 100%; }
        .monitor-date-actions { flex-wrap: wrap; }
        .monitor-date-form .form-control,.monitor-date-form .form-select { flex: 1; width: auto; }
        .monitor-sidebar { grid-template-columns: 1fr; }
        .monitor-order-head { grid-template-columns: 1fr; }
        .monitor-order { display: block; }
        .monitor-order-footer {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            border-top: 0;
            border-left: 0;
            padding-top: 10px;
        }
        .monitor-actions { width: 100%; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .monitor-actions .monitor-cancel-form { margin-top: 0; padding-top: 0; border-top: 0; }
        .monitor-timeline { min-width: 0; }
        .monitor-order-main { padding: 12px; }
        .monitor-create-steps { padding-inline: 8px; }
        .monitor-create-step { font-size: .66rem; }
        .monitor-confirm-grid { grid-template-columns: 1fr; }
        .monitor-inline-edit-fields { grid-template-columns: 1fr; }
        .monitor-inline-edit-fields .is-wide { grid-column: auto; }
        .monitor-simple-list { border-radius: 7px; }
        .monitor-auto-approval-grid { grid-template-columns: 1fr; gap: 18px; }
        .monitor-profit-grid { grid-template-columns: 1fr; }
        .monitor-auto-bulk-fields { grid-template-columns: 1fr; }
        .monitor-auto-bottom { align-items: stretch; flex-direction: column; }
        .monitor-auto-footer .btn { width: 100%; }
        .monitor-variant-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .monitor-adjustment-fields, .monitor-adjustment-details { grid-template-columns: 1fr; }
        .monitor-adjustment-fee-row { grid-template-columns: 24px 1fr; }
        .monitor-adjustment-fee-row .input-group { grid-column: 2; }
        .monitor-product-choice-label { font-size: 0; }
        .monitor-product-choice-label i { font-size: .8rem; }
        .monitor-priority-legend-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 575.98px) {
        .monitor-actions { grid-template-columns: 1fr; }
        .monitor-edit-product-search { grid-template-columns: minmax(0, 1fr) 82px; }
        .monitor-edit-product-search-button { width: 82px; }
        .monitor-edit-product-results .monitor-product-toolbar { grid-template-columns: 1fr; gap: 7px; }
        .monitor-edit-product-results .monitor-product-toolbar > div { justify-content: flex-end; }
        .monitor-edit-product-results .monitor-product-choice-label { min-width: auto; }
        .monitor-day-note { grid-template-columns: 1fr; gap: 5px; }
    }
</style>
@endpush

@section('content')
@php
    $statusLabels = \App\Models\Order::statusOptions() + [
        \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL => 'Chờ Leader duyệt',
        \App\Models\Order::STATUS_PENDING_MANAGER_APPROVAL => 'Chờ Manager duyệt',
        \App\Models\Order::STATUS_APPROVED => 'Đã duyệt',
        \App\Models\Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
        \App\Models\Order::STATUS_PACKING => 'Đang đóng gói',
        \App\Models\Order::STATUS_READY_TO_SHIP => 'Chờ vận chuyển',
        \App\Models\Order::STATUS_DELIVERING => 'Đang giao hàng',
        \App\Models\Order::STATUS_RETURNING => 'Đang trả hàng',
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 'Đã nhập kho trả hàng',
        'pending_warehouse_approval' => 'Chờ Kho duyệt',
        'shipping' => 'Đang vận chuyển',
        'picked_up' => 'Đã lấy hàng',
    ];
    $timelineSteps = ['Đặt đơn', 'Duyệt', 'Kho', 'Vận chuyển', 'Hoàn tất'];
    $timelineMap = [
        \App\Models\Order::STATUS_ORDER_PLACED => 0,
        \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL => 1,
        \App\Models\Order::STATUS_PENDING_MANAGER_APPROVAL => 1,
        'pending_warehouse_approval' => 1,
        \App\Models\Order::STATUS_ORDER_CONFIRMED => 1,
        \App\Models\Order::STATUS_APPROVED => 1,
        \App\Models\Order::STATUS_READY_TO_PACK => 2,
        \App\Models\Order::STATUS_PACKING => 2,
        \App\Models\Order::STATUS_PACKED => 2,
        \App\Models\Order::STATUS_READY_TO_SHIP => 3,
        \App\Models\Order::STATUS_DELIVERING => 3,
        \App\Models\Order::STATUS_IN_DELIVERY => 3,
        'shipping' => 3,
        'picked_up' => 3,
        \App\Models\Order::STATUS_DELIVERED => 4,
        \App\Models\Order::STATUS_COMPLETED => 4,
        \App\Models\Order::STATUS_RETURNING => 3,
        \App\Models\Order::STATUS_RETURNED => 4,
        \App\Models\Order::STATUS_RETURNED_COMPLETED => 4,
    ];
    $monitorStateLabels = [
        'pending' => 'Đã lên đơn, chờ duyệt',
        'approved' => 'Đã duyệt, chờ đóng gói',
        'packed' => 'Đã đóng gói, chờ giao',
        'transit' => 'Đang vận chuyển',
        'delivered' => 'Đã giao hàng',
        'accounted' => 'Kế toán đã xác nhận và tính doanh số',
        'cancelled' => 'Đã hủy',
    ];
    $monitorStateLegendDescriptions = [
        'pending' => 'Đơn mới tạo, đang chờ bước duyệt.',
        'approved' => 'Đơn đã duyệt, đang chờ kho hoặc đóng gói.',
        'packed' => 'Đã đóng gói, chờ bàn giao vận chuyển.',
        'transit' => 'Đang vận chuyển hoặc đang xử lý trả hàng.',
        'delivered' => 'Đã giao hàng hoặc đã hoàn tất đơn.',
        'accounted' => 'Kế toán đã xác nhận và ghi nhận doanh số.',
        'cancelled' => 'Đơn đã hủy, không tiếp tục xử lý.',
    ];
    $monitorStateForOrder = static function ($order): string {
        if ($order->status === \App\Models\Order::STATUS_CANCELLED) {
            return 'cancelled';
        }

        if ($order->accountingReconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED) {
            return 'accounted';
        }

        return match ((string) $order->status) {
            \App\Models\Order::STATUS_ORDER_CONFIRMED,
            \App\Models\Order::STATUS_APPROVED,
            \App\Models\Order::STATUS_READY_TO_PACK,
            \App\Models\Order::STATUS_PACKING,
            'confirmed',
            'picking' => 'approved',

            \App\Models\Order::STATUS_PACKED,
            \App\Models\Order::STATUS_READY_TO_SHIP => 'packed',

            \App\Models\Order::STATUS_SHIPPING,
            \App\Models\Order::STATUS_DELIVERING,
            \App\Models\Order::STATUS_IN_DELIVERY,
            \App\Models\Order::STATUS_RETURNING,
            'picked_up' => 'transit',

            \App\Models\Order::STATUS_DELIVERED,
            \App\Models\Order::STATUS_COMPLETED => 'delivered',

            default => 'pending',
        };
    };
    $formatQuantity = static fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    $sortUrl = static function (string $field) use ($sortBy, $sortDir): string {
        $nextDirection = $sortBy === $field && $sortDir === 'asc' ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort_by' => $field, 'sort_dir' => $nextDirection, 'page' => 1]);
    };
    $saleFilterQuery = request()->except(['sale_id', 'page']);
    $customerFilterQuery = request()->except(['customer_id', 'page']);
    $monitorUser = auth()->user();
    $hasAutoApprovalErrors = isset($errors) && $errors->any();
    $activeMonitorRole = strtolower(trim((string) (session('active_role') ?: $monitorUser?->defaultRole?->name)));
    $isSaleViewingRole = $activeMonitorRole === 'sale';
    if ($activeMonitorRole === '' && $monitorUser?->hasRole('sale')) {
        $isSaleViewingRole = !$monitorUser->hasRole(['admin', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']);
    }
    $canAssignPackingWarehouse = $monitorUser?->hasRole(['manager', 'manager_sale', 'director', 'admin']) ?? false;
    $visibleDailyOrderNotes = $dailyOrderNotes
        ->filter(fn ($noteOrder) => !$isSaleViewingRole || (int) $noteOrder->user_id === (int) $monitorUser?->id)
        ->values();
@endphp

<section class="monitor-page">
    <div class="container monitor-shell">
        <div class="monitor-layout">
            <aside class="monitor-sidebar">
                @include('site.orders.partials.monitor_sidebar_nav')

                @if($activeTab === 'today')
                <div class="monitor-panel monitor-filter-block">
                    <div class="monitor-filter-title">Sale</div>
                    <div class="monitor-filter-list">
                        <a class="monitor-filter-link {{ $selectedSaleId === 0 ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', $saleFilterQuery) }}">
                            <span>Tất cả Sale</span><span class="monitor-filter-count">{{ $saleFilters->sum('count') }}</span>
                        </a>
                        @foreach($saleFilters as $sale)
                            <a class="monitor-filter-link {{ $selectedSaleId === $sale['id'] ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', array_merge($saleFilterQuery, ['sale_id' => $sale['id']])) }}">
                                <span>{{ $sale['name'] }}</span><span class="monitor-filter-count">{{ $sale['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="monitor-panel monitor-filter-block">
                    <div class="monitor-filter-title">Khách hàng</div>
                    <div class="monitor-filter-list">
                        <a class="monitor-filter-link {{ $selectedCustomerId === 0 ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', $customerFilterQuery) }}">
                            <span>Tất cả khách hàng</span>
                        </a>
                        @foreach($customerFilters as $customer)
                            <a class="monitor-filter-link {{ $selectedCustomerId === $customer['id'] ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', array_merge($customerFilterQuery, ['customer_id' => $customer['id']])) }}">
                                <span>{{ $customer['name'] }}</span>
                                <span class="monitor-filter-count" title="Số thứ tự ưu tiên" aria-label="Số thứ tự ưu tiên {{ $customer['priority_sequence'] ?? 'chưa có' }}">
                                    {{ $customer['priority_sequence'] ?? '—' }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($activeTab === 'customers' && ($customerTabSales ?? collect())->isNotEmpty())
                @php $customerSaleQuery = request()->except(['sale_id', 'page']); @endphp
                <div class="monitor-panel monitor-filter-block">
                    <div class="monitor-filter-title">Sale</div>
                    <div class="monitor-filter-list">
                        <a class="monitor-filter-link {{ (int) request('sale_id', 0) === 0 ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', $customerSaleQuery) }}">
                            <span>Tất cả Sale</span><span class="monitor-filter-count">{{ $customerTabSales->sum('count') }}</span>
                        </a>
                        @foreach($customerTabSales as $sale)
                            <a class="monitor-filter-link {{ (int) request('sale_id', 0) === $sale['id'] ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', array_merge($customerSaleQuery, ['sale_id' => $sale['id']])) }}">
                                <span>{{ $sale['name'] }}</span><span class="monitor-filter-count">{{ $sale['count'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </aside>

            <main class="monitor-content">
        <div class="monitor-toolbar {{ in_array($activeTab, ['my_orders', 'drafts', 'customers'], true) ? 'd-none' : '' }}">
            @php
                $monitorTabLabels = [
                    'today' => 'Theo dõi đơn hàng ngày',
                    'drafts' => 'Đơn hàng mẫu',
                    'my_orders' => 'Đơn hàng của tôi',
                    'customers' => 'Danh sách khách hàng',
                    'schedules' => 'Đơn hàng theo lịch',
                    'automatic' => 'Đơn hàng tự động',
                ];
            @endphp
            <h1 class="monitor-title">{{ $monitorTabLabels[$activeTab] ?? $monitorTabLabels['today'] }}</h1>
            @if($activeTab === 'today')
            <div class="monitor-date-actions">
                <form class="monitor-date-form" method="GET" action="{{ route('pages.my_orders.monitoring') }}">
                    <input type="hidden" name="tab" value="today">
                    <input type="hidden" name="view" value="{{ $viewMode }}">
                    @if($supplierFilter !== '')<input type="hidden" name="supplier_id" value="{{ $supplierFilter }}">@endif
                    <select name="date_field" class="form-select form-select-sm" aria-label="Tiêu chí ngày">
                        <option value="business_date" @selected($selectedDateField === 'business_date')>Ngày nghiệp vụ</option>
                        <option value="created_at" @selected($selectedDateField === 'created_at')>Ngày tạo đơn</option>
                        <option value="delivery_date" @selected($selectedDateField === 'delivery_date')>Ngày giao hàng</option>
                    </select>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}">
                    @if($keyword !== '')<input type="hidden" name="keyword" value="{{ $keyword }}">@endif
                    @if($selectedStatus !== '')<input type="hidden" name="status" value="{{ $selectedStatus }}">@endif
                    <button type="submit" class="btn btn-sm btn-success">Lọc</button>
                </form>
                @if(auth()->user()?->isAdmin())
                    <form method="POST" action="{{ route('pages.my_orders.monitoring.restore_all') }}"
                          onsubmit="return confirm('Phục hồi tất cả {{ $restorableCancelledOrdersCount }} đơn đã hủy trong ngày {{ $selectedDate }}? Các đơn sẽ trở về trạng thái trước khi hủy và được dựng lại booking tồn kho.');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="date_field" value="{{ $selectedDateField }}">
                        <button type="submit" class="btn btn-sm btn-outline-success" @disabled($restorableCancelledOrdersCount === 0)
                                title="Phục hồi toàn bộ đơn đã hủy trong ngày đang chọn">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Phục hồi tất cả
                            @if($restorableCancelledOrdersCount > 0)<span class="badge text-bg-success ms-1">{{ $restorableCancelledOrdersCount }}</span>@endif
                        </button>
                    </form>
                @endif
            </div>
            <span class="small text-muted">
                {{ number_format($stats['total_orders']) }} đơn ·
                {{ $formatQuantity($stats['total_quantity']) }} sản phẩm ·
                {{ number_format($stats['total_value'], 0, ',', '.') }}đ
            </span>
            @endif
        </div>
        @if($activeTab === 'today')
        @php
            $sequenceOrders = $orders->getCollection()->sortBy(fn ($order) => $order->daily_sequence ?? PHP_INT_MAX)->values();
        @endphp
        @if($viewMode === 'cards' && $sequenceOrders->isNotEmpty())
        <div class="monitor-panel monitor-sequence-panel mb-3">
            <div class="monitor-sequences" aria-label="Điều hướng nhanh theo số thứ tự đơn">
                @foreach($sequenceOrders as $sequenceOrder)
                    @php
                        $sequenceState = $monitorStateForOrder($sequenceOrder);
                    @endphp
                    <a class="monitor-sequence status-{{ $sequenceState }}" href="#monitor-order-{{ $sequenceOrder->id }}" title="{{ $sequenceOrder->customer?->name ?? $sequenceOrder->code }} · {{ $monitorStateLabels[$sequenceState] }}">
                        {{ $sequenceOrder->daily_sequence ?? $loop->iteration }}
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <div class="monitor-bulk-actions mb-2">
            <div class="monitor-bulk-left">
                <form method="GET" action="{{ route('pages.my_orders.monitoring') }}">
                    @foreach(request()->except(['per_page', 'page']) as $queryKey => $queryValue)
                        @if(!is_array($queryValue))<input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">@endif
                    @endforeach
                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Số đơn trên trang">
                        @foreach([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }} đơn / trang</option>
                        @endforeach
                    </select>
                </form>
                <div class="monitor-view-switch" role="group" aria-label="Kiểu hiển thị đơn hàng">
                    <a class="btn btn-sm {{ $viewMode === 'cards' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ request()->fullUrlWithQuery(['view' => 'cards', 'page' => 1]) }}" title="Xem dạng thẻ" aria-label="Xem dạng thẻ">
                        <i class="bi bi-grid"></i>
                    </a>
                    <a class="btn btn-sm {{ $viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ request()->fullUrlWithQuery(['view' => 'list', 'page' => 1]) }}" title="Xem dạng danh sách" aria-label="Xem dạng danh sách">
                        <i class="bi bi-list-ul"></i>
                    </a>
                </div>
                @if($canConfigureAutoApproval)
                    <button type="button" class="btn btn-sm btn-outline-primary monitor-icon-action" data-bs-toggle="collapse" data-bs-target="#monitorAutoApproval" aria-expanded="{{ $hasAutoApprovalErrors ? 'true' : 'false' }}" title="Cài đặt duyệt đơn tự động" aria-label="Cài đặt duyệt đơn tự động">
                        <i class="bi bi-gear"></i>
                    </button>
                @endif
                <form method="POST" action="{{ route('pages.my_orders.monitoring.refresh_sequence') }}" onsubmit="return confirm('Quét duyệt tự động các đơn đủ điều kiện và cập nhật lại số thứ tự ưu tiên?');">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="date_field" value="{{ $selectedDateField }}">
                    <input type="hidden" name="keyword" value="{{ $keyword }}">
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                    <input type="hidden" name="sale_id" value="{{ $selectedSaleId }}">
                    <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">
                    <button type="submit" class="btn btn-sm btn-primary monitor-icon-action" title="Làm mới danh sách và số thứ tự" aria-label="Làm mới danh sách và số thứ tự">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </form>
                @if($canApproveManagedSales)
                    <form method="POST" action="{{ route('pages.my_orders.monitoring.approve_sales') }}" onsubmit="return confirm('Duyệt các đơn của sale thuộc phạm vi bạn quản lý?');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="date_field" value="{{ $selectedDateField }}">
                        <input type="hidden" name="keyword" value="{{ $keyword }}">
                        <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        <input type="hidden" name="sale_id" value="{{ $selectedSaleId }}">
                        <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">
                        <button type="submit"
                            class="btn btn-sm {{ $canApproveManagedSalesAny ? 'btn-success' : 'btn-secondary' }}"
                            @disabled(!$canApproveManagedSalesAny)
                            @if(!$canApproveManagedSalesAny) title="Không còn đơn PKD chờ duyệt" @endif>
                            <i class="bi bi-check2-all me-1"></i>Duyệt đơn PKD
                        </button>
                    </form>
                @endif
                @if($canApproveAllOrders)
                    <form method="POST" action="{{ route('pages.my_orders.monitoring.approve_all') }}" onsubmit="return confirm('Manager duyệt tất cả đơn đang tới lượt theo bộ lọc hiện tại?');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="date_field" value="{{ $selectedDateField }}">
                        <input type="hidden" name="keyword" value="{{ $keyword }}">
                        <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        <input type="hidden" name="sale_id" value="{{ $selectedSaleId }}">
                        <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">
                        <button type="submit"
                            class="btn btn-sm {{ $canApproveAllAny ? 'btn-success' : 'btn-secondary' }}"
                            @disabled(!$canApproveAllAny)
                            @if(!$canApproveAllAny)
                                title="{{ $hasPendingLeaderApprovals ? 'Chờ các Trưởng phòng KD duyệt hết đơn PKD' : 'Không còn đơn chờ duyệt' }}"
                            @endif>
                            <i class="bi bi-check2-all me-1"></i>Duyệt tất cả
                        </button>
                    </form>
                @endif
                <button class="btn btn-sm btn-outline-primary monitor-summary-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#monitorProductSummary" aria-expanded="false">
                    <i class="bi bi-inbox me-1"></i>Hàng - Số lượng
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-success" id="monitorOpenCreate">
                <i class="bi bi-plus-circle me-1"></i>Thêm đơn
            </button>
        </div>

        @if($viewMode === 'list')
            @php $supplierFilterQuery = request()->except(['supplier_id', 'page']); @endphp
            <div class="monitor-panel monitor-source-panel">
                <nav class="monitor-source-tabs" aria-label="Lọc đơn theo nhà cung cấp">
                    <span class="monitor-source-label">Nguồn</span>
                    <a class="monitor-source-tab {{ $supplierFilter === '' ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', $supplierFilterQuery) }}">
                        Tất cả <span class="monitor-source-tab-count">{{ $supplierCounts->sum() }}</span>
                    </a>
                    <a class="monitor-source-tab {{ $supplierFilter === 'unassigned' ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', array_merge($supplierFilterQuery, ['supplier_id' => 'unassigned'])) }}">
                        Chưa gắn <span class="monitor-source-tab-count">{{ $supplierCounts->get('', 0) }}</span>
                    </a>
                    @foreach($suppliers as $supplier)
                        <a class="monitor-source-tab {{ $supplierFilter === (string) $supplier->id ? 'active' : '' }}" href="{{ route('pages.my_orders.monitoring', array_merge($supplierFilterQuery, ['supplier_id' => $supplier->id])) }}">
                            {{ $supplier->name }} <span class="monitor-source-tab-count">{{ $supplierCounts->get($supplier->id, 0) }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            @if($supplierProfitSummaries->isNotEmpty())
                <section class="monitor-panel monitor-profit-summary" aria-label="Lợi nhuận theo nhà cung cấp">
                    <div class="monitor-profit-summary-head">
                        <h2 class="monitor-profit-summary-title">Lợi nhuận theo nhà cung cấp</h2>
                        <span class="monitor-profit-summary-note">Mỗi đơn dùng đúng một giá nhập có hiệu lực tại ngày nghiệp vụ của đơn</span>
                    </div>
                    <div class="monitor-profit-grid">
                        @foreach($supplierProfitSummaries as $summary)
                            @php $summaryProfit = $summary['profit']; @endphp
                            <article class="monitor-profit-card">
                                <div class="monitor-profit-card-head">
                                    <a class="monitor-profit-card-name" href="{{ route('pages.my_orders.monitoring', array_merge($supplierFilterQuery, ['supplier_id' => $summary['supplier_id']])) }}">{{ $summary['supplier_name'] }}</a>
                                    <span class="monitor-profit-card-count">{{ $summary['order_count'] }} đơn</span>
                                </div>
                                <div class="monitor-profit-values">
                                    <div class="monitor-profit-value"><small>Tiền bán</small><strong>{{ number_format($summary['sale_total'], 0, ',', '.') }}đ</strong></div>
                                    <div class="monitor-profit-value"><small>Tiền nhập</small><strong>{{ number_format($summary['purchase_total'], 0, ',', '.') }}đ</strong></div>
                                    <div class="monitor-profit-value {{ $summaryProfit !== null && $summaryProfit < 0 ? 'is-loss' : 'is-profit' }}">
                                        <small>Lợi nhuận</small>
                                        <strong>{{ $summaryProfit === null ? 'Chưa đủ giá' : number_format($summaryProfit, 0, ',', '.') . 'đ' }}</strong>
                                        @if($summary['margin_percent'] !== null)<small>{{ number_format($summary['margin_percent'], 1, ',', '.') }}% doanh thu</small>@endif
                                    </div>
                                </div>
                                @if(!$summary['is_complete'])
                                    <div class="monitor-profit-warning"><i class="bi bi-exclamation-triangle me-1"></i>Thiếu giá nhập: {{ $summary['missing_items']->implode(', ') }}</div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif

        @if($canConfigureAutoApproval)
            @php
                $newOrderRule = $autoApprovalRules->get(\App\Models\OrderAutoApprovalRule::TYPE_NEW_ORDER);
                $adjustmentRule = $autoApprovalRules->get(\App\Models\OrderAutoApprovalRule::TYPE_ORDER_ADJUSTMENT);
            @endphp
            <div class="collapse {{ $hasAutoApprovalErrors ? 'show' : '' }}" id="monitorAutoApproval">
                <form method="POST" action="{{ route('pages.my_orders.monitoring.auto_approval') }}" class="monitor-auto-approval" data-auto-approval-form novalidate>
                    @csrf
                    @method('PUT')
                    <div class="monitor-auto-approval-head">
                        <span><i class="bi bi-shield-check me-1"></i>Duyệt đơn tự động</span>
                        <div class="monitor-auto-approval-head-meta">
                            <span class="monitor-auto-owner">Cấu hình riêng cho {{ $user->name }}</span>
                            <button type="button" class="monitor-auto-close" data-bs-toggle="collapse" data-bs-target="#monitorAutoApproval" aria-controls="monitorAutoApproval" aria-label="Đóng cấu hình duyệt tự động">×</button>
                        </div>
                    </div>
                    <div class="monitor-auto-approval-body">
                        <div class="monitor-auto-approval-grid">
                            @foreach([
                                'new_order' => ['title' => 'Đơn mới', 'rule' => $newOrderRule, 'quantity_label' => 'Sản lượng từ (con)'],
                                'order_adjustment' => ['title' => 'Điều chỉnh đơn', 'rule' => $adjustmentRule, 'quantity_label' => 'Sản lượng từ'],
                            ] as $prefix => $config)
                                @php
                                    $rule = $config['rule'];
                                @endphp
                                <section data-auto-rule>
                                    <div class="monitor-auto-rule-title">{{ $config['title'] }}</div>
                                    <div class="form-check form-switch monitor-auto-rule-toggle">
                                        <input type="hidden" name="{{ $prefix }}_enabled" value="0">
                                        <input type="checkbox" class="form-check-input js-auto-rule-enabled" id="{{ $prefix }}Enabled" name="{{ $prefix }}_enabled" value="1" @checked(old("{$prefix}_enabled", $rule?->enabled ?? false))>
                                        <label class="form-check-label fw-bold" for="{{ $prefix }}Enabled">Bật tự động duyệt {{ mb_strtolower($config['title']) }}</label>
                                    </div>
                                    <div class="monitor-auto-rule-options">
                                        <div class="form-check">
                                            <input type="hidden" name="{{ $prefix }}_require_min_price" value="0">
                                            <input type="checkbox" class="form-check-input" id="{{ $prefix }}MinPrice" name="{{ $prefix }}_require_min_price" value="1" @checked(old("{$prefix}_require_min_price", $rule?->require_min_price ?? true))>
                                            <label class="form-check-label" for="{{ $prefix }}MinPrice">Giá bán của tất cả sản phẩm ≥ giá Min</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="hidden" name="{{ $prefix }}_allow_bulk_below_min" value="0">
                                            <input type="checkbox" class="form-check-input js-auto-bulk-enabled" id="{{ $prefix }}Bulk" name="{{ $prefix }}_allow_bulk_below_min" value="1" @checked(old("{$prefix}_allow_bulk_below_min", $rule?->allow_bulk_below_min ?? false))>
                                            <label class="form-check-label" for="{{ $prefix }}Bulk">Cho phép sản lượng lớn được bán thấp hơn giá Min</label>
                                        </div>
                                        <div class="monitor-auto-bulk-fields">
                                            <div>
                                                <label for="{{ $prefix }}Quantity">{{ $config['quantity_label'] }}</label>
                                                <input type="number" class="form-control form-control-sm" id="{{ $prefix }}Quantity" name="{{ $prefix }}_bulk_min_quantity" min="1" max="1000000" value="{{ old("{$prefix}_bulk_min_quantity", $rule?->bulk_min_quantity ?? 100) }}" required>
                                            </div>
                                            <div>
                                                <label for="{{ $prefix }}BelowMin">Chiết khấu thêm</label>
                                                <input type="number" class="form-control form-control-sm" id="{{ $prefix }}BelowMin" name="{{ $prefix }}_bulk_below_min_amount" min="0" max="1000000000" step="1" value="{{ old("{$prefix}_bulk_below_min_amount", (float) ($rule?->bulk_below_min_amount ?? 2000)) }}" required>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            @endforeach
                        </div>
                        <div class="monitor-auto-bottom">
                            <div class="monitor-auto-help">
                                <span>Trưởng phòng chỉ duyệt đơn của sale cùng team. Giám đốc chỉ duyệt khi các bước trước đã hoàn tất.</span>
                                <span>Đơn không đạt điều kiện vẫn giữ trạng thái chờ duyệt thủ công.</span>
                            </div>
                            <div class="monitor-auto-footer">
                                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Lưu cấu hình và duyệt đơn phù hợp</button>
                            </div>
                        </div>
                        <div class="monitor-auto-status" data-auto-approval-status role="status" aria-live="polite"></div>
                    </div>
                </form>
            </div>
        @endif

        <div class="monitor-panel monitor-summary-panel mb-4">
            <div class="collapse" id="monitorProductSummary">
                <div class="monitor-summary-table table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Sản phẩm / Biến thể</th>
                                <th>Số lượng</th>
                                <th>Tổng</th>
                                <th>Đơn giá</th>
                                <th class="text-end">Tạm tính</th>
                                @foreach($monitoringWarehouses as $warehouse)
                                    <th class="monitor-stock-head">
                                        <span>{{ $warehouse->name }}</span>
                                        <small>Tồn kho</small>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productRows as $row)
                                <tr class="monitor-product-group">
                                    <td>{{ $loop->iteration }}</td>
                                    <td><i class="bi bi-box-seam me-1 text-primary"></i>{{ $row['name'] }}</td>
                                    <td>{{ $formatQuantity($row['quantity']) }}</td>
                                    <td>{{ $formatQuantity($row['total']) }} {{ $row['unit'] }}</td>
                                    <td>—</td>
                                    <td class="text-end fw-semibold">{{ number_format($row['subtotal'], 0, ',', '.') }}đ</td>
                                    @foreach($monitoringWarehouses as $warehouse)
                                        @php $productStock = $row['warehouse_stocks']->get($warehouse->id); @endphp
                                        <td class="monitor-stock-cell">
                                            <span class="monitor-stock-value">{{ $formatQuantity($productStock['on_hand'] ?? 0) }}</span>
                                            @if(($productStock['available'] ?? 0) < ($productStock['on_hand'] ?? 0))
                                                <span class="monitor-stock-available">Khả dụng: {{ $formatQuantity($productStock['available']) }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @foreach($row['variants'] as $variant)
                                    <tr class="monitor-variant-row">
                                        <td></td>
                                        <td class="monitor-variant-name">
                                            <span class="monitor-variant-tree">└─</span> {{ $variant['name'] }}
                                            @if($variant['sku'])<span class="monitor-variant-sku">SKU: {{ $variant['sku'] }}</span>@endif
                                        </td>
                                        <td>{{ $formatQuantity($variant['quantity']) }}</td>
                                        <td>{{ $formatQuantity($variant['total']) }} {{ $variant['unit'] }}</td>
                                        <td>{{ $variant['price_label'] }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($variant['subtotal'], 0, ',', '.') }}đ</td>
                                        @foreach($monitoringWarehouses as $warehouse)
                                            @php $variantStock = $variant['warehouse_stocks']->get($warehouse->id); @endphp
                                            <td class="monitor-stock-cell">
                                                <span class="monitor-stock-value {{ ($variantStock['available'] ?? 0) < $variant['quantity'] ? 'is-low' : '' }}">
                                                    {{ $formatQuantity($variantStock['on_hand'] ?? 0) }}
                                                </span>
                                                @if(($variantStock['available'] ?? 0) < ($variantStock['on_hand'] ?? 0))
                                                    <span class="monitor-stock-available">Khả dụng: {{ $formatQuantity($variantStock['available']) }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="{{ 6 + $monitoringWarehouses->count() }}" class="text-center text-muted py-4">Không có hàng hóa phù hợp.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <section class="monitor-panel monitor-create" id="monitorCreateOrder" hidden aria-label="Tạo đơn hàng mới">
            <div class="monitor-create-head d-flex align-items-center justify-content-between gap-2">
                <h2>Tạo đơn hàng mới</h2>
                <button type="button" class="btn-close" id="monitorCloseCreate" aria-label="Đóng"></button>
            </div>
            <div class="monitor-create-steps" aria-label="Các bước tạo đơn">
                @foreach(['Chọn sản phẩm', 'Chọn khách hàng', 'Xác nhận', 'Hoàn thành'] as $createStep)
                    <div class="monitor-create-step {{ $loop->first ? 'is-active' : '' }}" data-create-step-indicator="{{ $loop->iteration }}">
                        <span class="monitor-create-step-number">{{ $loop->iteration }}</span>
                        <span>{{ $createStep }}</span>
                    </div>
                @endforeach
            </div>
            <div class="monitor-create-body">
                <div class="monitor-create-pane" data-create-pane="1">
                    <div class="monitor-create-search">
                        <input type="search" class="form-control form-control-sm" id="monitorVariantSearch" placeholder="Tìm sản phẩm, SKU hoặc size...">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="monitorVariantSearchButton"><i class="bi bi-search me-1"></i>Tìm</button>
                    </div>
                    <div id="monitorVariantResults" class="mb-3">
                        <div class="text-center text-muted py-4">Đang tải danh sách sản phẩm...</div>
                    </div>
                    <div class="monitor-selected-table">
                        <div class="px-3 pt-3 fw-bold">Sản phẩm đã chọn</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Sản phẩm</th><th>Biến thể</th><th>Giá bán</th><th>Số lượng</th><th>Thành tiền</th><th></th></tr></thead>
                                <tbody id="monitorSelectedItems"><tr><td colspan="6" class="text-center text-muted py-3">Chưa chọn sản phẩm.</td></tr></tbody>
                            </table>
                        </div>
                        <div class="monitor-create-total">Tạm tính: <span id="monitorCreateTotal">0đ</span></div>
                    </div>
                    <div class="monitor-create-actions">
                        <button type="button" class="btn btn-sm btn-success" data-create-next="2">Chọn khách hàng <i class="bi bi-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <div class="monitor-create-pane" data-create-pane="2" hidden>
                    <div class="monitor-create-search">
                        <input type="search" class="form-control form-control-sm" id="monitorCustomerSearch" placeholder="Tìm theo tên, số điện thoại hoặc email...">
                        <button type="button" class="btn btn-sm btn-primary" id="monitorCustomerSearchButton"><i class="bi bi-search me-1"></i>Lọc</button>
                    </div>
                    <div id="monitorSelectedCustomer" class="monitor-customer-selected mb-3" hidden></div>
                    <div id="monitorCustomerResults"><div class="text-center text-muted py-4">Đang tải danh sách khách hàng...</div></div>
                    <div class="monitor-create-actions justify-content-between">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-create-back="1"><i class="bi bi-arrow-left me-1"></i>Sản phẩm</button>
                        <button type="button" class="btn btn-sm btn-success" data-create-next="3">Xác nhận <i class="bi bi-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <div class="monitor-create-pane" data-create-pane="3" hidden>
                    <div class="monitor-confirm-grid">
                        <div class="monitor-confirm-card">
                            <h3>Khách hàng</h3>
                            <div id="monitorConfirmCustomer"></div>
                            <div class="mt-3">
                                <label class="form-label small fw-bold" for="monitorRecipientAddress">Địa chỉ nhận hàng</label>
                                <textarea class="form-control form-control-sm" id="monitorRecipientAddress" rows="2"></textarea>
                            </div>
                            <div class="mt-2">
                                <label class="form-label small fw-bold" for="monitorDeliveryTime">Giờ giao hàng</label>
                                <input class="form-control form-control-sm" id="monitorDeliveryTime" placeholder="Ví dụ: 9h-11h hoặc sau 17h">
                            </div>
                            <div class="mt-3 border-top pt-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="monitorUseTruckStation">
                                    <label class="form-check-label small fw-bold" for="monitorUseTruckStation">Gửi hàng qua trạm xe / nhà xe</label>
                                </div>
                                <div id="monitorTruckStationFields" hidden>
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold" for="monitorTruckStationId">Chọn trạm xe</label>
                                        <select class="form-select form-select-sm" id="monitorTruckStationId">
                                            <option value="">-- Nhập thông tin trạm xe thủ công --</option>
                                            @foreach($truckStations as $truckStation)
                                                <option value="{{ $truckStation->id }}"
                                                    data-name="{{ $truckStation->name }}"
                                                    data-address="{{ $truckStation->address ?? '' }}"
                                                    data-phone="{{ $truckStation->phone ?? '' }}">
                                                    {{ $truckStation->name }}{{ $truckStation->brand?->name ? ' · ' . $truckStation->brand->name : '' }}{{ $truckStation->province?->name ? ' · ' . $truckStation->province->name : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label small fw-bold" for="monitorTruckStationName">Tên trạm / nhà xe</label>
                                            <input class="form-control form-control-sm" id="monitorTruckStationName" maxlength="255">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-bold" for="monitorTruckStationAddress">Địa chỉ trạm xe</label>
                                            <input class="form-control form-control-sm" id="monitorTruckStationAddress" maxlength="255">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold" for="monitorTruckStationPhone">Số điện thoại</label>
                                            <input class="form-control form-control-sm" id="monitorTruckStationPhone" maxlength="30">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold" for="monitorTruckReceiveTime">Giờ nhà xe nhận hàng</label>
                                            <input class="form-control form-control-sm" id="monitorTruckReceiveTime" maxlength="255" placeholder="Ví dụ: trước 17h">
                                        </div>
                                    </div>
                                    <div class="form-text">Thông tin này được lưu riêng theo đơn và không làm thay đổi hồ sơ khách hàng.</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label small fw-bold" for="monitorOrderNote">Ghi chú</label>
                                <textarea class="form-control form-control-sm" id="monitorOrderNote" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="monitor-confirm-card">
                            <h3>Sản phẩm</h3>
                            <div id="monitorConfirmItems"></div>
                            <div class="border-top mt-3 pt-3">
                                <h3 class="mb-2">Chi phí khác <span class="fw-normal text-muted">(nếu có)</span></h3>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="monitorChargeVat">
                                    <label class="form-check-label small fw-bold" for="monitorChargeVat">Tính chi phí VAT</label>
                                </div>
                                <div id="monitorVatFields" class="mb-3" hidden>
                                    <label class="form-label small" for="monitorVatPercent">Thuế VAT (%)</label>
                                    <div class="input-group input-group-sm">
                                        <input class="form-control" type="number" id="monitorVatPercent" min="0.01" max="100" step="0.01" placeholder="Ví dụ: 8 hoặc 10">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Tiền VAT được tự động tính trên tổng tiền sản phẩm.</div>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="monitorCollectCustomerShippingFee">
                                    <label class="form-check-label small fw-bold" for="monitorCollectCustomerShippingFee">Thu tiền ship của khách hàng</label>
                                </div>
                                <div id="monitorCustomerShippingFeeFields" class="mb-2" hidden>
                                    <label class="form-label small" for="monitorCustomerShippingFee">Số tiền thu thêm</label>
                                    <div class="input-group input-group-sm">
                                        <input class="form-control" type="number" id="monitorCustomerShippingFee" min="1" step="1000" placeholder="Nhập số tiền">
                                        <span class="input-group-text">đ</span>
                                    </div>
                                    <div class="form-text">Khoản này độc lập với phí ship do shipper manager ấn định.</div>
                                </div>
                            </div>
                            <div class="border-top mt-3 pt-2 small">
                                <div class="d-flex justify-content-between"><span>Tạm tính sản phẩm:</span><strong id="monitorConfirmSubtotal">0đ</strong></div>
                                <div class="d-flex justify-content-between mt-1" id="monitorConfirmVatLine" hidden><span>VAT:</span><strong id="monitorConfirmVatAmount">0đ</strong></div>
                                <div class="d-flex justify-content-between mt-1" id="monitorConfirmCustomerShippingLine" hidden><span>Thu tiền ship:</span><strong id="monitorConfirmCustomerShippingAmount">0đ</strong></div>
                                <div class="d-flex justify-content-between border-top mt-2 pt-2 fs-6 fw-bold text-success"><span>Tổng cộng:</span><span id="monitorConfirmTotal">0đ</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="monitor-create-actions justify-content-between">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-create-back="2"><i class="bi bi-arrow-left me-1"></i>Khách hàng</button>
                        <button type="button" class="btn btn-sm btn-warning fw-bold" id="monitorSubmitOrder"><i class="bi bi-check2 me-1"></i>Tạo đơn</button>
                    </div>
                </div>

                <div class="monitor-create-pane monitor-finish" data-create-pane="4" hidden>
                    <i class="bi bi-check-circle-fill monitor-finish-icon"></i>
                    <h3 class="mt-2">Tạo đơn hàng thành công</h3>
                    <p class="text-muted" id="monitorFinishMessage"></p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="#" class="btn btn-sm btn-outline-primary" id="monitorCreatedOrderLink">Xem chi tiết</a>
                        <a href="#" class="btn btn-sm btn-success" id="monitorBackToOrders">Về danh sách đơn</a>
                    </div>
                </div>
            </div>
        </section>

                @if($viewMode === 'list')
                    <div class="monitor-panel monitor-simple-list table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center">STT</th>
                                    <th>Khách hàng</th>
                                    <th>Sale</th>
                                    <th>Sản phẩm</th>
                                    <th>Size</th>
                                    <th class="text-end">Số lượng</th>
                                    <th class="text-end">Số liệu thực tế</th>
                                    <th class="text-end">Giá bán</th>
                                    <th class="text-end">Tiền bán</th>
                                    <th>Nhà cung cấp</th>
                                    <th>Kho đóng hàng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    @php
                                        $listItems = $order->items->isNotEmpty() ? $order->items->values() : collect([null]);
                                        $listRowspan = $listItems->count();
                                        $listOrderSaleTotal = (float) $order->items->sum(
                                            fn ($item) => $item->lineTotalForStage((string) $order->status)
                                        );
                                        $canAssignSupplier = !$isSaleViewingRole || (int) $order->user_id === (int) $monitorUser?->id;
                                    @endphp
                                    @foreach($listItems as $listItem)
                                        <tr class="{{ $order->status === \App\Models\Order::STATUS_CANCELLED ? 'table-danger' : '' }}">
                                            @if($loop->first)
                                                <td rowspan="{{ $listRowspan }}" class="text-center fw-bold">{{ $order->daily_sequence ?? (($orders->firstItem() ?? 1) + $loop->parent->index) }}</td>
                                                <td rowspan="{{ $listRowspan }}" class="monitor-list-customer">
                                                    {{ $order->customer?->name ?? 'Khách hàng' }}
                                                    <small class="d-block text-muted text-lowercase">{{ $order->code ?: ('#' . $order->id) }}</small>
                                                </td>
                                                <td rowspan="{{ $listRowspan }}">{{ $order->user?->short_name ?: ($order->user?->name ?? '—') }}</td>
                                            @endif
                                            <td class="monitor-list-products">{{ $listItem?->display_name ?? '—' }}</td>
                                            <td>{{ $listItem?->variant?->size ?: '—' }}</td>
                                            <td class="text-end fw-semibold">{{ $listItem ? $formatQuantity($listItem->quantity) : '—' }}</td>
                                            <td class="text-end">
                                                @if($listItem)
                                                    <span class="fw-semibold">{{ $listItem->displayLabelForStage((string) $order->status) }}</span>
                                                    <small class="d-block text-muted">{{ $listItem->displaySourceForStage((string) $order->status) }}</small>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap">{{ $listItem && (float) $listItem->price > 0 ? number_format((float) $listItem->price, 0, ',', '.') . 'đ' : '—' }}</td>
                                            @if($loop->first)
                                                <td rowspan="{{ $listRowspan }}" class="text-end fw-bold text-nowrap" title="Tổng tiền bán của đơn">{{ number_format($listOrderSaleTotal, 0, ',', '.') }}đ</td>
                                                <td rowspan="{{ $listRowspan }}" class="monitor-list-supplier">
                                                    @if($canAssignSupplier)
                                                    <div class="monitor-supplier-actions">
                                                        <form method="POST" action="{{ route('pages.my_orders.monitoring.supplier', $order) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <select class="form-select form-select-sm" name="supplier_id" onchange="this.form.submit()" aria-label="Gắn nhà cung cấp cho đơn {{ $order->code ?: $order->id }}">
                                                                <option value="">-- Chọn nhà cung cấp --</option>
                                                                @foreach($suppliers as $supplier)
                                                                    <option value="{{ $supplier->id }}" @selected((int) $order->supplier_id === (int) $supplier->id)>{{ $supplier->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                        @if($order->supplier_id)
                                                            <form method="POST" action="{{ route('pages.my_orders.monitoring.supplier', $order) }}" onsubmit="return confirm('Gỡ đơn này khỏi nhà cung cấp {{ addslashes($order->supplier?->name ?? '') }}?');">
                                                                @csrf
                                                                @method('PUT')
                                                                <button class="btn btn-sm btn-outline-danger monitor-supplier-remove" type="submit" title="Gỡ khỏi nhà cung cấp" aria-label="Gỡ đơn khỏi nhà cung cấp">×</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                    @else
                                                        <span class="text-muted">{{ $order->supplier?->name ?? 'Chưa gắn' }}</span>
                                                    @endif
                                                </td>
                                                <td rowspan="{{ $listRowspan }}" class="monitor-list-warehouse">
                                                    @if($canAssignPackingWarehouse && in_array((string) $order->status, \App\Models\Order::WAREHOUSE_ASSIGNABLE_STATUSES, true))
                                                        <form method="POST" action="{{ route('pages.my_orders.monitoring.warehouse', $order) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <select class="form-select form-select-sm" name="warehouse_id" required onchange="this.form.submit()" aria-label="Chọn kho đóng hàng cho đơn {{ $order->code ?: $order->id }}">
                                                                <option value="">-- Chọn kho --</option>
                                                                @foreach($monitoringWarehouses as $warehouse)
                                                                    <option value="{{ $warehouse->id }}" @selected((int) $order->warehouse_id === (int) $warehouse->id)>{{ $warehouse->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                    @else
                                                        <span class="text-muted">{{ $order->warehouse?->name ?? 'Chưa chọn' }}</span>
                                                        @if($canAssignPackingWarehouse && $order->warehouse_id)
                                                            <small class="d-block text-muted">Đơn đã bắt đầu xử lý</small>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr><td colspan="11" class="py-4 text-center text-muted">Không có đơn hàng phù hợp.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                <div class="monitor-orders">
                    @forelse($orders as $order)
                        @php
                            $isCancelled = $order->status === \App\Models\Order::STATUS_CANCELLED;
                            $monitorState = $monitorStateForOrder($order);
                            $canApprove = !$isCancelled && ($canApproveByOrder[$order->id] ?? false);
                            $hasInvalidSizeItems = $order->items->contains(
                                fn ($item) => (float) ($item->effective_unit_weight ?? 0) <= 0
                            );
                            $timelineIndex = $timelineMap[$order->status] ?? 0;
                            $timelinePercent = ($timelineIndex / 4) * 100;
                            $defaultAddress = $order->customer?->addresses?->firstWhere('is_default', 1)
                                ?? $order->customer?->addresses?->first();
                            $deliveryAddress = $order->recipient_address
                                ?: ($defaultAddress?->note ?: ($order->customer?->address ?: 'Chưa cập nhật địa chỉ'));
                            $deliveryArea = collect([$defaultAddress?->ward, $defaultAddress?->city])->filter()->implode(', ');
                            $deliveryTime = $order->delivery_time ?: ($order->customer?->delivery_time ?: 'Chưa cập nhật');
                            $canManageOrder = (int) $order->user_id === (int) auth()->id();
                            $canViewOrderDetail = !$isSaleViewingRole || $canManageOrder;
                            $isAdminUser = auth()->user()?->isAdmin() ?? false;
                            $canDeleteCancelledOrder = !$isAdminUser
                                && $isCancelled
                                && empty($order->trash_at)
                                && $canManageOrder;
                            $canAdminDeleteOrder = $isAdminUser;
                            $canAdminRestoreOrder = $isAdminUser
                                && $isCancelled
                                && empty($order->trash_at);
                            $isEditable = $canManageOrder && $order->canBeDirectlyEditedByOwner();
                            $canCancel = in_array($order->status, \App\Models\Order::CANCELLABLE_STATUSES, true)
                                && ($isAdminUser || ($canManageOrder && $order->created_at?->isToday()));
                            $canRequestAdjustment = $canManageOrder && $order->canRequestAdjustment();
                        @endphp
                        <article class="monitor-panel monitor-order status-{{ $monitorState }} {{ $canManageOrder ? 'is-mine' : '' }} {{ $isCancelled ? 'is-cancelled' : '' }}" id="monitor-order-{{ $order->id }}" title="{{ $monitorStateLabels[$monitorState] }}">
                            <div class="monitor-order-main">
                                <div class="monitor-order-head">
                                    <div class="monitor-order-person">
                                        <div class="monitor-order-number">{{ $order->daily_sequence ?? $loop->iteration }}</div>
                                        <div>
                                            <div class="monitor-order-name">{{ $order->customer?->name ?? 'Khách hàng' }}</div>
                                            <div class="monitor-order-code">
                                                {{ $order->code ?: ('#' . $order->id) }}
                                                · Sale: {{ $order->user?->short_name ?: ($order->user?->name ?? '—') }}
                                                · {{ $order->created_at?->format('H:i d/m/Y') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="monitor-timeline">
                                        <div class="monitor-timeline-track">
                                            <div class="monitor-timeline-progress" style="width: {{ $timelinePercent }}%"></div>
                                            @foreach($timelineSteps as $stepIndex => $stepName)
                                                <span class="monitor-timeline-dot {{ $stepIndex < $timelineIndex ? 'done' : ($stepIndex === $timelineIndex ? 'current' : '') }}" title="{{ $stepName }}"></span>
                                            @endforeach
                                        </div>
                                        <div class="monitor-timeline-labels">
                                            @foreach($timelineSteps as $stepName)<span>{{ $stepName }}</span>@endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="monitor-meta">
                                    <span><i class="bi bi-geo-alt me-1"></i>Địa chỉ nhận hàng: {{ $deliveryAddress }}</span>
                                    @if($deliveryArea !== '')
                                        <span><i class="bi bi-pin-map me-1"></i>Khu vực: {{ $deliveryArea }}</span>
                                    @endif
                                    <span><i class="bi bi-clock me-1"></i>Giờ giao: {{ $deliveryTime }}</span>
                                    @if($order->shipper)
                                        <span><i class="bi bi-truck me-1"></i>Shipper: {{ $order->shipper->name }}</span>
                                    @endif
                                    @if($order->use_truck_station)
                                        <span><i class="bi bi-building me-1"></i>Nhà xe: {{ $order->truck_station_name ?: ($order->truckStation?->name ?: 'Chưa cập nhật') }}</span>
                                    @endif
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm monitor-items">
                                        <thead>
                                            <tr>
                                                <th>Sản phẩm</th>
                                                <th class="text-end">SL</th>
                                                <th class="text-end">Size</th>
                                                <th class="text-end">Số liệu thực tế</th>
                                                <th class="text-end">Đơn giá</th>
                                                <th class="text-end">Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($order->items as $item)
                                                @php
                                                    $itemName = $item->display_name;
                                                    $lineTotal = $item->lineTotalForStage((string) $order->status);
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <span class="fw-semibold">{{ $itemName }}</span>
                                                        @if($item->variant?->sku)<span class="text-muted">({{ $item->variant->sku }})</span>@endif
                                                    </td>
                                                    <td class="text-end">{{ $formatQuantity($item->quantity) }}</td>
                                                    <td class="text-end">{{ $item->variant?->size ?? '-' }}</td>
                                                    <td class="text-end fw-semibold">
                                                        {{ $item->displayLabelForStage((string) $order->status) }}
                                                        <small class="d-block text-muted fw-normal">{{ $item->displaySourceForStage((string) $order->status) }}</small>
                                                    </td>
                                                    <td class="text-end">{{ number_format((float) $item->price, 0, ',', '.') }}đ</td>
                                                    <td class="text-end fw-semibold">{{ number_format($lineTotal, 0, ',', '.') }}đ</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="text-center text-muted">Đơn chưa có sản phẩm.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="monitor-order-total">{{ number_format((float) $order->total, 0, ',', '.') }}đ</div>

                                @if($isEditable)
                                    <div class="collapse monitor-inline-edit" id="monitorEdit{{ $order->id }}">
                                        <form method="POST" action="{{ route('site.orders.update', $order) }}" class="monitor-inline-edit-form" data-monitor-edit-form>
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="return_to" value="monitoring">
                                            <input type="hidden" name="customer_id" value="{{ $order->customer_id }}">
                                            <input type="hidden" name="recipient_email" value="{{ $order->recipient_email }}">
                                            <input type="hidden" name="shipper_note" value="{{ $order->shipper_note }}">
                                            <input type="hidden" name="order_discount" value="{{ (float) ($order->order_discount ?? 0) }}">
                                            <input type="hidden" name="order_discount_type" value="{{ ($order->order_discount_type ?? 'decrease') === 'increase' ? 'increase' : 'decrease' }}">
                                            <input type="hidden" name="warehouse_can_adjust" value="{{ $order->warehouse_can_adjust ? 1 : 0 }}">

                                            <div class="monitor-inline-edit-title"><i class="bi bi-pencil-square me-1"></i>Sửa đơn {{ $order->code ?: ('#' . $order->id) }}</div>
                                            <div class="monitor-edit-picker">
                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                    <div>
                                                        <div class="monitor-edit-picker-label">Khách hàng</div>
                                                        <div class="monitor-edit-selected-customer">{{ $order->customer?->name ?? 'Chưa chọn' }}{{ $order->customer?->phone ? ' · ' . $order->customer->phone : '' }}</div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary monitor-edit-customer-toggle">Chọn khách khác</button>
                                                </div>
                                                <div class="monitor-edit-customer-picker mt-2" hidden>
                                                    <div class="input-group input-group-sm">
                                                        <input type="search" class="form-control monitor-edit-customer-search" placeholder="Tìm tên, số điện thoại hoặc email...">
                                                        <button type="button" class="btn btn-primary monitor-edit-customer-search-button"><i class="bi bi-search"></i> Tìm</button>
                                                    </div>
                                                    <div class="monitor-edit-picker-results monitor-edit-customer-results"></div>
                                                </div>
                                            </div>
                                            <div class="monitor-inline-edit-fields">
                                                <div>
                                                    <label for="monitorEditName{{ $order->id }}">Người nhận</label>
                                                    <input class="form-control form-control-sm" id="monitorEditName{{ $order->id }}" name="recipient_name" value="{{ $order->recipient_name ?: ($order->customer?->name ?? '') }}" required>
                                                </div>
                                                <div>
                                                    <label for="monitorEditPhone{{ $order->id }}">Số điện thoại</label>
                                                    <input class="form-control form-control-sm" id="monitorEditPhone{{ $order->id }}" name="recipient_phone" value="{{ $order->recipient_phone ?: ($order->customer?->phone ?? '') }}" required>
                                                </div>
                                                <div class="is-wide">
                                                    <label for="monitorEditAddress{{ $order->id }}">Địa chỉ nhận hàng</label>
                                                    <input class="form-control form-control-sm" id="monitorEditAddress{{ $order->id }}" name="recipient_address" value="{{ $order->recipient_address ?: ($order->customer?->address ?? '') }}" required>
                                                </div>
                                                <div>
                                                    <label for="monitorEditDelivery{{ $order->id }}">Giờ giao hàng</label>
                                                    <input class="form-control form-control-sm" id="monitorEditDelivery{{ $order->id }}" name="delivery_time" value="{{ $order->delivery_time }}">
                                                </div>
                                                <div>
                                                    <label for="monitorEditNote{{ $order->id }}">Ghi chú</label>
                                                    <input class="form-control form-control-sm" id="monitorEditNote{{ $order->id }}" name="note" value="{{ $order->note }}">
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle monitor-edit-items">
                                                    <thead><tr><th>Sản phẩm</th><th>Giá bán</th><th>Số lượng</th><th class="text-end">Thành tiền</th><th></th></tr></thead>
                                                    <tbody>
                                                        @foreach($order->items as $editIndex => $item)
                                                            @php
                                                                $editVariant = $item->variant;
                                                                $editBasePrice = (float) ($editVariant?->latestPriceRule?->price ?? $editVariant?->final_price ?? $item->base_price ?? $item->price ?? 0);
                                                                $editMinPrice = max(0, (float) ($editVariant?->latestPriceRule?->min_price ?? 0));
                                                                $editSellingPrice = (float) ($item->price ?? $editBasePrice);
                                                                $editDiscountType = $editSellingPrice > $editBasePrice ? 'increase' : 'decrease';
                                                                $editDiscount = abs($editSellingPrice - $editBasePrice);
                                                                $editPricingFactor = $item->effective_priced_by_kg ? max((float) $item->effective_unit_weight, 0) : 1;
                                                                $editLineTotal = $editSellingPrice * (float) $item->quantity * $editPricingFactor;
                                                            @endphp
                                                            <tr data-monitor-edit-item data-variant-id="{{ $editVariant?->id }}" data-base-price="{{ $editBasePrice }}" data-min-price="{{ $editMinPrice }}" data-pricing-factor="{{ $editPricingFactor }}">
                                                                <td>
                                                                    <strong>{{ $item->product?->name ?? $editVariant?->product?->name ?? 'Sản phẩm' }}</strong>
                                                                    <span class="d-block text-muted">{{ $editVariant?->size ?: ($editVariant?->sku ?: '') }}</span>
                                                                    <input type="hidden" name="items[{{ $editIndex }}][variant_id]" value="{{ $editVariant?->id }}">
                                                                    <input type="hidden" class="monitor-edit-discount-type" name="item_discount_type[{{ $editVariant?->id }}]" value="{{ $editDiscountType }}">
                                                                    <input type="hidden" class="monitor-edit-discount" name="item_discount[{{ $editVariant?->id }}]" value="{{ $editDiscount }}">
                                                                </td>
                                                                <td>
                                                                    <div class="monitor-edit-price-stepper">
                                                                        <button type="button" class="btn btn-sm monitor-edit-price-decrease" aria-label="Giảm đơn giá 1.000 đồng" {{ $editSellingPrice <= $editMinPrice ? 'disabled' : '' }}>−</button>
                                                                        <span class="monitor-edit-price-value">{{ number_format($editSellingPrice, 0, ',', '.') }}đ</span>
                                                                        <button type="button" class="btn btn-sm monitor-edit-price-increase" aria-label="Tăng đơn giá 1.000 đồng">+</button>
                                                                    </div>
                                                                </td>
                                                                <td><input type="number" class="form-control form-control-sm monitor-edit-quantity" name="items[{{ $editIndex }}][quantity]" min="1" max="100000" value="{{ (int) $item->quantity }}" required></td>
                                                                <td class="text-end fw-semibold monitor-edit-line-total">{{ number_format($editLineTotal, 0, ',', '.') }}đ</td>
                                                                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger monitor-edit-remove-item" aria-label="Xóa sản phẩm"><i class="bi bi-x"></i></button></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="monitor-edit-picker">
                                                <div class="monitor-edit-picker-label">Thêm sản phẩm vào đơn</div>
                                                <div class="monitor-edit-product-search">
                                                    <input type="search" class="form-control form-control-sm monitor-edit-product-search-input" placeholder="Tìm sản phẩm, SKU hoặc size...">
                                                    <button type="button" class="btn btn-sm btn-outline-primary monitor-edit-product-search-button"><i class="bi bi-search me-1"></i>Tìm</button>
                                                </div>
                                                <div class="monitor-edit-picker-results monitor-edit-product-results"></div>
                                            </div>
                                            <div class="monitor-inline-edit-total">Tổng sản phẩm: <span>{{ number_format((float) $order->items->sum('total'), 0, ',', '.') }}đ</span></div>
                                            <div class="monitor-inline-edit-actions">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#monitorEdit{{ $order->id }}">Đóng</button>
                                                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i>Lưu thay đổi</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </div>
                            <div class="monitor-order-footer">
                                <span class="monitor-status">{{ $statusLabels[$order->status] ?? str_replace('_', ' ', $order->status) }}</span>
                                <div class="monitor-actions">
                                    @if($canApprove)
                                        <form method="POST" action="{{ route('site.orders.approve', $order) }}" class="js-monitor-approval-form">
                                            @csrf
                                            <input type="hidden" name="note" value="Duyệt từ trang theo dõi đơn hàng">
                                            <button type="submit" class="btn btn-sm btn-success" @disabled($hasInvalidSizeItems)
                                                @if($hasInvalidSizeItems) title="Có sản phẩm chưa có size hoặc khối lượng quy đổi bằng 0." @endif>
                                                <i class="bi bi-check2 me-1"></i>Duyệt
                                            </button>
                                        </form>
                                        @if($hasInvalidSizeItems)
                                            <div class="monitor-action-note text-danger">Size/KL = 0</div>
                                        @endif
                                    @endif
                                    @if($isEditable)
                                        <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#monitorEdit{{ $order->id }}" aria-controls="monitorEdit{{ $order->id }}"><i class="bi bi-pencil me-1"></i>Sửa trực tiếp</button>
                                    @endif
                                    @if($canViewOrderDetail)
                                        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#monitorExtra{{ $order->id }}">
                                            <i class="bi bi-eye me-1"></i>Chi tiết
                                        </button>
                                    @endif
                                    @if($canManageOrder)
                                        @if($isCancelled)
                                            <form method="POST" action="{{ route('site.orders.resend', $order) }}"
                                                  onsubmit="return confirm('Gửi lại đơn {{ addslashes($order->code ?: ('#' . $order->id)) }}? Hệ thống sẽ tạo đơn mới, cập nhật giá hiện tại và chuyển vào quy trình duyệt. Đơn đã hủy vẫn được giữ nguyên.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning text-dark">
                                                    <i class="bi bi-send me-1"></i>Gửi lại đơn
                                                </button>
                                            </form>
                                        @else
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('site.orders.copy', $order->id) }}"><i class="bi bi-files"></i><span>Sao chép đơn</span></a>
                                        @endif
                                        @if($order->customer_id && !in_array((int) $order->customer_id, $sampleDraftCustomerIds ?? [], true))
                                            <button class="btn btn-sm btn-outline-primary monitor-add-to-sample" type="button" data-sample-customer-id="{{ $order->customer_id }}" data-sample-url="{{ route('pages.my_order_drafts.add_from_order', $order) }}"><i class="bi bi-bookmark-plus"></i><span>Cho vào đơn mẫu</span></button>
                                        @endif
                                        @if($canRequestAdjustment)
                                            <button class="btn btn-sm btn-warning monitor-adjustment-open" type="button" data-adjustment-url="{{ route('site.order-adjustments.create', $order) }}" data-adjustment-target="monitorAdjustment{{ $order->id }}"><i class="bi bi-arrow-left-right"></i><span>Gửi yêu cầu điều chỉnh</span></button>
                                        @endif
                                    @endif
                                    @if($canCancel)
                                        <form method="POST" class="monitor-cancel-form" action="{{ route('site.orders.cancel', $order) }}"
                                              onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn {{ addslashes($order->code ?: ('#' . $order->id)) }}? Booking tồn kho của đơn sẽ được giải phóng.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Hủy đơn hàng</button>
                                        </form>
                                    @endif
                                    @if($canDeleteCancelledOrder)
                                        <form method="POST" class="monitor-cancel-form" action="{{ route('site.orders.trash', $order) }}"
                                              onsubmit="return confirm('Xóa đơn đã hủy {{ $order->code ?: ('#' . $order->id) }}? Đơn sẽ được chuyển vào thùng rác.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash me-1"></i>Xóa đơn
                                            </button>
                                        </form>
                                    @endif
                                    @if($canAdminRestoreOrder)
                                        <form method="POST" class="monitor-cancel-form" action="{{ route('site.orders.restore-cancelled', $order) }}"
                                              onsubmit="return confirm('Phục hồi đơn {{ addslashes($order->code ?: ('#' . $order->id)) }} về trạng thái trước khi hủy? Nếu thiếu tồn kho, đơn vẫn được phục hồi để chờ bổ sung hàng.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Phục hồi đơn
                                            </button>
                                        </form>
                                    @endif
                                    @if($canAdminDeleteOrder)
                                        <form method="POST" class="monitor-cancel-form" action="{{ route('site.orders.admin-delete', $order) }}"
                                              onsubmit="return monitorAdminDeleteOrder(this, @js($order->code ?: ('#'.$order->id)), @js($order->user?->name ?? 'sale'), @js((float)$order->total));">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="reason" value="">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Xóa vĩnh viễn và loại khỏi doanh số sale">
                                                <i class="bi bi-trash3 me-1"></i>Xóa &amp; loại doanh số
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            @if($canViewOrderDetail)
                                <div class="collapse" id="monitorExtra{{ $order->id }}">
                                    <div class="px-3 py-2 border-top small text-muted">
                                        <div><strong>Điện thoại:</strong> {{ $order->recipient_phone ?: ($order->customer?->phone ?: 'Chưa cập nhật') }}</div>
                                        <div><strong>Ghi chú:</strong> {{ $order->note ?: 'Không có ghi chú' }}</div>
                                        <div><strong>Thanh toán:</strong> {{ ucfirst((string) ($order->payment_status ?: 'unpaid')) }}</div>
                                        @if($order->use_truck_station)
                                            <div><strong>Trạm xe:</strong> {{ $order->truck_station_name ?: ($order->truckStation?->name ?: 'Chưa cập nhật') }}</div>
                                            <div><strong>Địa chỉ trạm:</strong> {{ $order->truck_station_address ?: ($order->truckStation?->address ?: 'Chưa cập nhật') }}</div>
                                            <div><strong>Điện thoại / giờ nhận:</strong> {{ $order->truck_station_phone ?: ($order->truckStation?->phone ?: '—') }} · {{ $order->truck_receive_time ?: 'Chưa cập nhật' }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @if($canRequestAdjustment)
                                <div class="monitor-adjustment-host" id="monitorAdjustment{{ $order->id }}" aria-live="polite"></div>
                            @endif
                        </article>
                    @empty
                        <div class="monitor-panel monitor-empty">
                            <i class="bi bi-inbox fs-1"></i>
                            <h5 class="mt-2">Không có đơn hàng phù hợp</h5>
                            <p class="mb-0">Hãy chọn ngày hoặc thay đổi bộ lọc.</p>
                        </div>
                    @endforelse
                </div>
                @endif

                @if($orders->hasPages())
                    <div class="monitor-pagination">{{ $orders->links('pagination::bootstrap-5') }}</div>
                @endif

                <footer class="monitor-day-footer" aria-label="Ghi chú đơn hàng trong ngày">
                    <div class="monitor-day-footer-head">
                        <h2 class="monitor-day-footer-title">
                            <i class="bi bi-journal-text"></i>Ghi chú đơn hàng ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                        </h2>
                        <span class="monitor-day-footer-count" title="Số đơn có ghi chú">{{ $visibleDailyOrderNotes->count() }}</span>
                    </div>
                    <div class="monitor-day-notes">
                        @forelse($visibleDailyOrderNotes as $noteOrder)
                            <div class="monitor-day-note">
                                <div class="monitor-day-note-order">
                                    <strong>#{{ $noteOrder->daily_sequence ?? '—' }} · {{ $noteOrder->customer?->name ?? 'Khách hàng' }}</strong>
                                    <span class="monitor-day-note-meta">{{ $noteOrder->code ?: ('Đơn #' . $noteOrder->id) }} · Sale: {{ $noteOrder->user?->short_name ?: ($noteOrder->user?->name ?? '—') }}</span>
                                </div>
                                <div class="monitor-day-note-content">
                                    @if(trim((string) $noteOrder->note) !== '')
                                        <div><span class="monitor-day-note-label">Ghi chú:</span> {{ $noteOrder->note }}</div>
                                    @endif
                                    @if(trim((string) $noteOrder->shipper_note) !== '')
                                        <div><span class="monitor-day-note-label">Giao hàng:</span> {{ $noteOrder->shipper_note }}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="monitor-day-notes-empty"><i class="bi bi-check-circle me-1"></i>Chưa có ghi chú cho các đơn trong ngày.</div>
                        @endforelse
                    </div>
                    <section class="monitor-priority-legend" aria-labelledby="monitorPriorityLegendTitle">
                        <div class="monitor-priority-legend-head">
                            <h3 class="monitor-priority-legend-title" id="monitorPriorityLegendTitle">
                                <i class="bi bi-palette me-1"></i>Bảng màu số thứ tự ưu tiên
                            </h3>
                            <div class="monitor-priority-legend-help">Số trong vòng tròn là thứ tự xử lý trong ngày; màu nền thể hiện trạng thái hiện tại.</div>
                        </div>
                        <div class="monitor-priority-legend-grid">
                            @foreach($monitorStateLabels as $state => $label)
                                <div class="monitor-priority-legend-item">
                                    <span class="monitor-sequence monitor-priority-legend-number status-{{ $state }}" aria-hidden="true">#</span>
                                    <div class="monitor-priority-legend-copy">
                                        <strong>{{ $label }}</strong>
                                        <span>{{ $monitorStateLegendDescriptions[$state] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </footer>
        @else
            <div class="monitor-tab-content monitor-tab-{{ $activeTab }}">
                {!! $tabContentHtml !!}
            </div>
        @endif
            </main>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
window.monitorAdminDeleteOrder = function (form, orderCode, saleName, orderTotal) {
    const amount = new Intl.NumberFormat('vi-VN').format(Number(orderTotal) || 0) + 'đ';
    const confirmed = window.confirm(
        'XÓA VĨNH VIỄN đơn ' + orderCode + '?\n\n'
        + 'Đơn sẽ bị loại khỏi doanh số và hoa hồng của ' + saleName + '.\n'
        + 'Giá trị bị loại: ' + amount + '.\n'
        + 'Thao tác này không thể hoàn tác từ giao diện.'
    );
    if (!confirmed) return false;

    const reason = window.prompt('Nhập lý do xóa đơn (ít nhất 5 ký tự):', 'Xóa đơn nhập sai');
    if (reason === null) return false;
    if (reason.trim().length < 5) {
        window.alert('Lý do xóa đơn phải có ít nhất 5 ký tự.');
        return false;
    }
    form.querySelector('input[name="reason"]').value = reason.trim();
    form.querySelector('button[type="submit"]').disabled = true;
    return true;
};

(() => {
    const createPanel = document.getElementById('monitorCreateOrder');
    const openButton = document.getElementById('monitorOpenCreate');
    if (!createPanel || !openButton) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const variantEndpoint = @json(route('site.orders.variants.ajax'));
    const customerEndpoint = @json(route('site.orders.customers.ajax'));
    const storeEndpoint = @json(route('pages.my_orders.monitoring.store'));
    const selectedItems = new Map();
    let selectedCustomer = null;
    let variantsLoaded = false;
    let customersLoaded = false;

    const money = value => new Intl.NumberFormat('vi-VN').format(Math.round(Number(value) || 0)) + 'đ';
    const itemAdjustedPrice = item => item.discountType === 'increase'
        ? item.price + item.discount
        : Math.max(0, item.price - item.discount);
    const itemLineTotal = item => itemAdjustedPrice(item) * item.quantity * (item.isPricedByKg ? item.weight : 1);
    const itemsSubtotal = () => Array.from(selectedItems.values()).reduce((sum, item) => sum + itemLineTotal(item), 0);
    const selectedVatPercent = () => document.getElementById('monitorChargeVat').checked
        ? Math.min(100, Math.max(0, Number(document.getElementById('monitorVatPercent').value) || 0))
        : 0;
    const calculatedVatAmount = () => itemsSubtotal() * selectedVatPercent() / 100;
    const selectedCustomerShippingFee = () => document.getElementById('monitorCollectCustomerShippingFee').checked
        ? Math.max(0, Number(document.getElementById('monitorCustomerShippingFee').value) || 0)
        : 0;
    const orderTotal = () => itemsSubtotal() + calculatedVatAmount() + selectedCustomerShippingFee();
    const notify = (message, type = 'error') => {
        if (typeof window.showToast === 'function') window.showToast(message, type);
        else window.alert(message);
    };

    function setStep(step) {
        createPanel.querySelectorAll('[data-create-pane]').forEach(pane => {
            pane.hidden = Number(pane.dataset.createPane) !== step;
        });
        createPanel.querySelectorAll('[data-create-step-indicator]').forEach(indicator => {
            const value = Number(indicator.dataset.createStepIndicator);
            indicator.classList.toggle('is-active', value === step);
            indicator.classList.toggle('is-done', value < step);
        });
        if (step === 3) renderConfirmation();
        createPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderItems() {
        const body = document.getElementById('monitorSelectedItems');
        if (!selectedItems.size) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Chưa chọn sản phẩm.</td></tr>';
        } else {
            body.innerHTML = Array.from(selectedItems.values()).map(item => `
                <tr data-selected-variant="${item.id}">
                    <td><strong>${escapeHtml(item.name)}</strong><div class="small text-muted">${escapeHtml(item.sku || '')}</div></td>
                    <td>${escapeHtml(item.size || '—')}</td>
                    <td class="monitor-sale-price">
                        <div class="monitor-price-stepper">
                            <button type="button" class="btn btn-sm monitor-price-decrease" aria-label="Giảm đơn giá 1.000 đồng" title="Giảm 1.000đ" ${itemAdjustedPrice(item) <= item.minPrice ? 'disabled' : ''}>−</button>
                            <span class="monitor-sale-price-value">${money(itemAdjustedPrice(item))}</span>
                            <button type="button" class="btn btn-sm monitor-price-increase" aria-label="Tăng đơn giá 1.000 đồng" title="Tăng 1.000đ">+</button>
                        </div>
                    </td>
                    <td><input type="number" class="form-control form-control-sm monitor-item-quantity" min="1" max="100000" value="${item.quantity}"></td>
                    <td class="fw-semibold monitor-item-line-total">${money(itemLineTotal(item))}</td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger monitor-remove-item" aria-label="Xóa"><i class="bi bi-x"></i></button></td>
                </tr>`).join('');
        }
        document.getElementById('monitorCreateTotal').textContent = money(itemsSubtotal());
    }

    function escapeHtml(value) {
        const node = document.createElement('div');
        node.textContent = String(value ?? '');
        return node.innerHTML;
    }

    async function loadVariants(url = variantEndpoint, search = null) {
        const results = document.getElementById('monitorVariantResults');
        const target = new URL(url, window.location.origin);
        target.searchParams.set('view', 'products');
        target.searchParams.set('per_page', target.searchParams.get('per_page') || '10');
        if (search !== null) {
            target.searchParams.set('search', search);
            target.searchParams.set('page', '1');
        }
        results.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Đang tải sản phẩm...</div>';
        try {
            const response = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error('Không thể tải danh sách sản phẩm.');
            results.innerHTML = data.html;
            results.querySelectorAll('.monitor-variant-option').forEach(button => {
                button.classList.toggle('is-selected', selectedItems.has(Number(button.dataset.variantId)));
            });
            variantsLoaded = true;
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error.message)}</div>`;
        }
    }

    async function loadCustomers(page = 1) {
        const results = document.getElementById('monitorCustomerResults');
        const target = new URL(customerEndpoint, window.location.origin);
        target.searchParams.set('mode', 'single');
        target.searchParams.set('scope', 'my_customers');
        target.searchParams.set('q', document.getElementById('monitorCustomerSearch').value.trim());
        target.searchParams.set('per_page', '15');
        target.searchParams.set('page', String(page));
        results.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Đang tải khách hàng...</div>';
        try {
            const response = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.html) throw new Error('Không thể tải danh sách khách hàng.');
            results.innerHTML = data.html;
            customersLoaded = true;
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error.message)}</div>`;
        }
    }

    function renderSelectedCustomer() {
        const preview = document.getElementById('monitorSelectedCustomer');
        preview.hidden = !selectedCustomer;
        if (!selectedCustomer) return;
        preview.innerHTML = `<strong>${escapeHtml(selectedCustomer.name)}</strong>
            <div class="small text-muted"><i class="bi bi-telephone me-1"></i>${escapeHtml(selectedCustomer.phone || 'Chưa có SĐT')}</div>
            <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>${escapeHtml(selectedCustomer.address || 'Chưa có địa chỉ')}</div>`;
    }

    function applyCustomerTruckStation() {
        const enabled = Boolean(selectedCustomer?.useTruckStation);
        document.getElementById('monitorUseTruckStation').checked = enabled;
        document.getElementById('monitorTruckStationFields').hidden = !enabled;
        document.getElementById('monitorTruckStationId').value = selectedCustomer?.truckStationId || '';
        document.getElementById('monitorTruckStationName').value = selectedCustomer?.truckStationName || '';
        document.getElementById('monitorTruckStationAddress').value = selectedCustomer?.truckStationAddress || '';
        document.getElementById('monitorTruckStationPhone').value = selectedCustomer?.truckStationPhone || '';
        document.getElementById('monitorTruckReceiveTime').value = selectedCustomer?.truckReceiveTime || '';
    }

    function renderConfirmation() {
        if (!selectedCustomer) return;
        document.getElementById('monitorConfirmCustomer').innerHTML = `<strong>${escapeHtml(selectedCustomer.name)}</strong>
            <div>${escapeHtml(selectedCustomer.phone || 'Chưa có SĐT')}</div>`;
        document.getElementById('monitorRecipientAddress').value = selectedCustomer.address || '';
        document.getElementById('monitorConfirmItems').innerHTML = Array.from(selectedItems.values()).map(item => `
            <div class="d-flex justify-content-between gap-2 border-bottom py-2 small">
                <span><strong>${escapeHtml(item.name)}</strong> · ${escapeHtml(item.size || '—')} × ${item.quantity}
                    <span class="d-block text-muted">Giá bán: ${money(itemAdjustedPrice(item))}/đơn vị${item.discount > 0 ? ` · ${item.discountType === 'increase' ? 'Tăng' : 'Giảm'} ${money(item.discount)}` : ''}</span>
                </span>
                <strong>${money(itemLineTotal(item))}</strong>
            </div>`).join('');
        updateConfirmationTotals();
    }

    function updateConfirmationTotals() {
        const chargeVat = document.getElementById('monitorChargeVat').checked;
        const collectShipping = document.getElementById('monitorCollectCustomerShippingFee').checked;
        document.getElementById('monitorConfirmSubtotal').textContent = money(itemsSubtotal());
        document.getElementById('monitorConfirmVatLine').hidden = !chargeVat;
        document.getElementById('monitorConfirmVatAmount').textContent = money(calculatedVatAmount());
        document.getElementById('monitorConfirmCustomerShippingLine').hidden = !collectShipping;
        document.getElementById('monitorConfirmCustomerShippingAmount').textContent = money(selectedCustomerShippingFee());
        document.getElementById('monitorConfirmTotal').textContent = money(orderTotal());
    }

    openButton.addEventListener('click', () => {
        createPanel.hidden = false;
        setStep(1);
        if (!variantsLoaded) loadVariants();
    });
    document.getElementById('monitorCloseCreate').addEventListener('click', () => { createPanel.hidden = true; });
    document.getElementById('monitorVariantSearchButton').addEventListener('click', () => loadVariants(variantEndpoint, document.getElementById('monitorVariantSearch').value.trim()));
    document.getElementById('monitorVariantSearch').addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); document.getElementById('monitorVariantSearchButton').click(); }
    });
    document.getElementById('monitorCustomerSearchButton').addEventListener('click', () => loadCustomers());
    document.getElementById('monitorCustomerSearch').addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); loadCustomers(); }
    });

    createPanel.addEventListener('click', event => {
        const productChoice = event.target.closest('#monitorVariantResults .monitor-product-choice');
        if (productChoice) {
            event.preventDefault();
            const card = productChoice.closest('.monitor-product-card');
            const variants = card.querySelector('.monitor-product-variants');
            const willOpen = variants.hidden;
            document.querySelectorAll('#monitorVariantResults .monitor-product-card.is-open').forEach(openCard => {
                if (openCard !== card) {
                    openCard.classList.remove('is-open');
                    openCard.querySelector('.monitor-product-choice')?.setAttribute('aria-expanded', 'false');
                    openCard.querySelector('.monitor-product-variants').hidden = true;
                }
            });
            card.classList.toggle('is-open', willOpen);
            variants.hidden = !willOpen;
            productChoice.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            return;
        }

        const variantOption = event.target.closest('#monitorVariantResults .monitor-variant-option');
        if (variantOption) {
            event.preventDefault();
            const id = Number(variantOption.dataset.variantId);
            if (!selectedItems.has(id)) {
                selectedItems.set(id, {
                    id,
                    name: variantOption.dataset.variantName || 'Sản phẩm',
                    sku: variantOption.dataset.variantSku || '',
                    size: variantOption.dataset.variantSize || '',
                    price: Number(variantOption.dataset.variantPrice) || 0,
                    minPrice: Number(variantOption.dataset.variantMinPrice) || 0,
                    weight: Number(variantOption.dataset.variantWeight) || 1,
                    isPricedByKg: variantOption.dataset.variantIsPricedByKg === '1',
                    quantity: 1,
                    discount: 0,
                    discountType: 'decrease'
                });
                renderItems();
                variantOption.classList.add('is-selected');
            } else {
                selectedItems.delete(id);
                renderItems();
                variantOption.classList.remove('is-selected');
            }
            return;
        }

        const variantPage = event.target.closest('#monitorVariantResults .pagination a');
        if (variantPage) { event.preventDefault(); loadVariants(variantPage.href); return; }

        const remove = event.target.closest('.monitor-remove-item');
        if (remove) {
            const id = Number(remove.closest('[data-selected-variant]').dataset.selectedVariant);
            selectedItems.delete(id);
            document.querySelector(`#monitorVariantResults .monitor-variant-option[data-variant-id="${id}"]`)?.classList.remove('is-selected');
            renderItems();
            return;
        }

        const priceButton = event.target.closest('.monitor-price-decrease, .monitor-price-increase');
        if (priceButton) {
            const row = priceButton.closest('[data-selected-variant]');
            const item = selectedItems.get(Number(row.dataset.selectedVariant));
            if (!item) return;

            const step = priceButton.classList.contains('monitor-price-increase') ? 1000 : -1000;
            const adjustedPrice = Math.max(item.minPrice, itemAdjustedPrice(item) + step);
            item.discountType = adjustedPrice >= item.price ? 'increase' : 'decrease';
            item.discount = Math.abs(adjustedPrice - item.price);
            if (item.discount === 0) item.discountType = 'decrease';

            row.querySelector('.monitor-sale-price-value').textContent = money(adjustedPrice);
            row.querySelector('.monitor-price-decrease').disabled = adjustedPrice <= item.minPrice;
            row.querySelector('.monitor-item-line-total').textContent = money(itemLineTotal(item));
            document.getElementById('monitorCreateTotal').textContent = money(itemsSubtotal());
            return;
        }

        const customerButton = event.target.closest('#monitorCustomerResults .select-customer-btn');
        if (customerButton) {
            selectedCustomer = {
                id: Number(customerButton.dataset.customerId),
                name: customerButton.dataset.customerName || 'Khách hàng',
                phone: customerButton.dataset.customerPhone || '',
                email: customerButton.dataset.customerEmail || '',
                address: customerButton.dataset.customerAddress || '',
                useTruckStation: customerButton.dataset.customerUseTruckStation === '1',
                truckStationId: customerButton.dataset.customerTruckStationId || '',
                truckStationName: customerButton.dataset.customerTruckStationName || '',
                truckStationAddress: customerButton.dataset.customerTruckStationAddress || '',
                truckStationPhone: customerButton.dataset.customerTruckStationPhone || '',
                truckReceiveTime: customerButton.dataset.customerTruckReceiveTime || ''
            };
            applyCustomerTruckStation();
            renderSelectedCustomer();
            return;
        }

        const customerPage = event.target.closest('#monitorCustomerResults .customer-page-btn');
        if (customerPage && !customerPage.disabled) { loadCustomers(Number(customerPage.dataset.page) || 1); return; }

        const next = event.target.closest('[data-create-next]');
        if (next) {
            const step = Number(next.dataset.createNext);
            if (step === 2 && !selectedItems.size) { notify('Vui lòng chọn ít nhất một sản phẩm.'); return; }
            if (step === 2 && !customersLoaded) loadCustomers();
            if (step === 3 && !selectedCustomer) { notify('Vui lòng chọn khách hàng.'); return; }
            setStep(step);
            return;
        }

        const back = event.target.closest('[data-create-back]');
        if (back) setStep(Number(back.dataset.createBack));
    });

    createPanel.addEventListener('input', event => {
        if (event.target.matches('#monitorVatPercent, #monitorCustomerShippingFee')) {
            updateConfirmationTotals();
            return;
        }

        const row = event.target.closest('[data-selected-variant]');
        if (!row) return;
        const item = selectedItems.get(Number(row.dataset.selectedVariant));
        if (!item) return;

        if (event.target.matches('.monitor-item-quantity')) {
            item.quantity = Math.max(1, Number.parseInt(event.target.value || '1', 10));
        }
        row.querySelector('.monitor-item-line-total').textContent = money(itemLineTotal(item));
        document.getElementById('monitorCreateTotal').textContent = money(itemsSubtotal());
        updateConfirmationTotals();
    });

    createPanel.addEventListener('change', event => {
        if (event.target.matches('#monitorChargeVat')) {
            document.getElementById('monitorVatFields').hidden = !event.target.checked;
            if (event.target.checked) document.getElementById('monitorVatPercent').focus();
            updateConfirmationTotals();
            return;
        }
        if (event.target.matches('#monitorCollectCustomerShippingFee')) {
            document.getElementById('monitorCustomerShippingFeeFields').hidden = !event.target.checked;
            if (event.target.checked) document.getElementById('monitorCustomerShippingFee').focus();
            updateConfirmationTotals();
            return;
        }
        if (event.target.matches('#monitorUseTruckStation')) {
            document.getElementById('monitorTruckStationFields').hidden = !event.target.checked;
            return;
        }
        if (event.target.matches('#monitorTruckStationId')) {
            const option = event.target.selectedOptions[0];
            if (option?.value) {
                document.getElementById('monitorTruckStationName').value = option.dataset.name || '';
                document.getElementById('monitorTruckStationAddress').value = option.dataset.address || '';
                document.getElementById('monitorTruckStationPhone').value = option.dataset.phone || '';
            }
            return;
        }
        if (event.target.matches('#monitorVariantResults #per-page-select')) {
            const target = new URL(variantEndpoint, window.location.origin);
            target.searchParams.set('search', document.getElementById('monitorVariantSearch').value.trim());
            target.searchParams.set('per_page', event.target.value);
            loadVariants(target);
        }
    });

    document.getElementById('monitorSubmitOrder').addEventListener('click', async event => {
        const button = event.currentTarget;
        const chargeVat = document.getElementById('monitorChargeVat').checked;
        const vatPercent = selectedVatPercent();
        const collectCustomerShippingFee = document.getElementById('monitorCollectCustomerShippingFee').checked;
        const customerShippingFee = selectedCustomerShippingFee();
        if (chargeVat && vatPercent <= 0) {
            notify('Vui lòng nhập phần trăm VAT lớn hơn 0.');
            document.getElementById('monitorVatPercent').focus();
            return;
        }
        if (collectCustomerShippingFee && customerShippingFee <= 0) {
            notify('Vui lòng nhập số tiền ship thu của khách hàng.');
            document.getElementById('monitorCustomerShippingFee').focus();
            return;
        }
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang tạo...';
        try {
            const response = await fetch(storeEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    customer_id: selectedCustomer.id,
                    items: Array.from(selectedItems.values()).map(item => ({
                        variant_id: item.id,
                        quantity: item.quantity,
                        unit_discount: item.discount,
                        unit_discount_type: item.discountType
                    })),
                    recipient_address: document.getElementById('monitorRecipientAddress').value.trim(),
                    delivery_time: document.getElementById('monitorDeliveryTime').value.trim(),
                    note: document.getElementById('monitorOrderNote').value.trim(),
                    use_truck_station: document.getElementById('monitorUseTruckStation').checked,
                    truck_station_id: document.getElementById('monitorTruckStationId').value || null,
                    truck_station_name: document.getElementById('monitorTruckStationName').value.trim(),
                    truck_station_address: document.getElementById('monitorTruckStationAddress').value.trim(),
                    truck_station_phone: document.getElementById('monitorTruckStationPhone').value.trim(),
                    truck_receive_time: document.getElementById('monitorTruckReceiveTime').value.trim(),
                    charge_vat: chargeVat,
                    vat_percent: chargeVat ? vatPercent : null,
                    collect_customer_shipping_fee: collectCustomerShippingFee,
                    customer_shipping_fee: collectCustomerShippingFee ? customerShippingFee : null
                })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(validationMessage || data.message || 'Không thể tạo đơn hàng.');
            }
            document.getElementById('monitorFinishMessage').textContent = data.message;
            document.getElementById('monitorCreatedOrderLink').href = data.order.url;
            document.getElementById('monitorBackToOrders').href = data.monitoring_url;
            setStep(4);
            notify(data.message, 'success');
        } catch (error) {
            notify(error.message || 'Không thể kết nối máy chủ.');
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-check2 me-1"></i>Tạo đơn';
        }
    });

    function updateInlineEditTotals(form) {
        let total = 0;
        form.querySelectorAll('[data-monitor-edit-item]').forEach(row => {
            const basePrice = Number(row.dataset.basePrice) || 0;
            const discount = Math.max(0, Number(row.querySelector('.monitor-edit-discount')?.value) || 0);
            const discountType = row.querySelector('.monitor-edit-discount-type')?.value === 'increase' ? 'increase' : 'decrease';
            const sellingPrice = discountType === 'increase' ? basePrice + discount : basePrice - discount;
            const quantityInput = row.querySelector('.monitor-edit-quantity');
            const quantity = Math.max(1, Number.parseInt(quantityInput?.value || '1', 10));
            const pricingFactor = Math.max(0, Number(row.dataset.pricingFactor) || 0);
            const lineTotal = sellingPrice * quantity * pricingFactor;

            if (quantityInput && Number(quantityInput.value) !== quantity) quantityInput.value = quantity;
            row.querySelector('.monitor-edit-price-value').textContent = money(sellingPrice);
            row.querySelector('.monitor-edit-line-total').textContent = money(lineTotal);
            row.querySelector('.monitor-edit-price-decrease').disabled = sellingPrice <= (Number(row.dataset.minPrice) || 0);
            total += lineTotal;
        });
        form.querySelector('.monitor-inline-edit-total span').textContent = money(total);
    }

    async function loadInlineCustomers(form, page = 1) {
        const results = form.querySelector('.monitor-edit-customer-results');
        const target = new URL(customerEndpoint, window.location.origin);
        target.searchParams.set('mode', 'single');
        target.searchParams.set('scope', 'my_customers');
        target.searchParams.set('q', form.querySelector('.monitor-edit-customer-search').value.trim());
        target.searchParams.set('per_page', '10');
        target.searchParams.set('page', String(page));
        target.searchParams.set('sort_by', form.dataset.customerSortBy || 'manual');
        target.searchParams.set('sort_dir', form.dataset.customerSortDir || 'asc');
        results.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span>Đang tải khách hàng...</div>';
        try {
            const response = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.html) throw new Error('Không thể tải khách hàng.');
            results.innerHTML = data.html;
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger py-2 mb-0">${escapeHtml(error.message)}</div>`;
        }
    }

    async function loadInlineProducts(form, url = variantEndpoint) {
        const results = form.querySelector('.monitor-edit-product-results');
        const target = new URL(url, window.location.origin);
        const variantIds = Array.from(form.querySelectorAll('[data-monitor-edit-item]'))
            .map(row => row.dataset.variantId)
            .filter(Boolean);
        target.searchParams.set('view', 'products');
        target.searchParams.set('search', form.querySelector('.monitor-edit-product-search-input').value.trim());
        target.searchParams.set('per_page', form.dataset.productPerPage || '10');
        target.searchParams.set('page', target.searchParams.get('page') || '1');
        results.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span>Đang tải sản phẩm...</div>';
        try {
            const response = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.html) throw new Error('Không thể tải sản phẩm.');
            results.innerHTML = data.html;
            const selectedIds = new Set(variantIds);
            results.querySelectorAll('.monitor-variant-option').forEach(button => {
                const isSelected = selectedIds.has(String(button.dataset.variantId || ''));
                button.classList.toggle('is-selected', isSelected);
                button.disabled = isSelected;
                if (isSelected) button.title = 'Biến thể đã có trong đơn';
            });
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger py-2 mb-0">${escapeHtml(error.message)}</div>`;
        }
    }

    function addInlineEditItem(form, button) {
        const variantId = String(button.dataset.variantId || '');
        if (!variantId || form.querySelector(`[data-monitor-edit-item][data-variant-id="${CSS.escape(variantId)}"]`)) {
            notify('Sản phẩm này đã có trong đơn.');
            return;
        }

        const rows = form.querySelectorAll('[data-monitor-edit-item]');
        const nextIndex = Number(form.dataset.nextItemIndex || rows.length);
        form.dataset.nextItemIndex = String(nextIndex + 1);
        const name = button.dataset.variantName || 'Sản phẩm';
        const sku = button.dataset.variantSku || '';
        const size = button.dataset.variantSize || '';
        const price = Math.max(0, Number(button.dataset.variantPrice) || 0);
        const minPrice = Math.max(0, Number(button.dataset.variantMinPrice) || 0);
        const weight = Math.max(0.01, Number(button.dataset.variantWeight) || 1);
        const pricingFactor = button.dataset.variantIsPricedByKg === '1' ? weight : 1;

        form.querySelector('.monitor-edit-items tbody').insertAdjacentHTML('beforeend', `
            <tr data-monitor-edit-item data-variant-id="${escapeHtml(variantId)}" data-base-price="${price}" data-min-price="${minPrice}" data-pricing-factor="${pricingFactor}">
                <td>
                    <strong>${escapeHtml(name)}</strong>
                    <span class="d-block text-muted">${escapeHtml(size || sku)}</span>
                    <input type="hidden" name="items[${nextIndex}][variant_id]" value="${escapeHtml(variantId)}">
                    <input type="hidden" class="monitor-edit-discount-type" name="item_discount_type[${escapeHtml(variantId)}]" value="decrease">
                    <input type="hidden" class="monitor-edit-discount" name="item_discount[${escapeHtml(variantId)}]" value="0">
                </td>
                <td><div class="monitor-edit-price-stepper">
                    <button type="button" class="btn btn-sm monitor-edit-price-decrease" aria-label="Giảm đơn giá 1.000 đồng" ${price <= minPrice ? 'disabled' : ''}>−</button>
                    <span class="monitor-edit-price-value">${money(price)}</span>
                    <button type="button" class="btn btn-sm monitor-edit-price-increase" aria-label="Tăng đơn giá 1.000 đồng">+</button>
                </div></td>
                <td><input type="number" class="form-control form-control-sm monitor-edit-quantity" name="items[${nextIndex}][quantity]" min="1" max="100000" value="1" required></td>
                <td class="text-end fw-semibold monitor-edit-line-total">${money(price * pricingFactor)}</td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger monitor-edit-remove-item" aria-label="Xóa sản phẩm"><i class="bi bi-x"></i></button></td>
            </tr>`);
        button.classList.add('is-selected');
        button.disabled = true;
        button.title = 'Biến thể đã có trong đơn';
        updateInlineEditTotals(form);
    }

    document.addEventListener('click', event => {
        const priceButton = event.target.closest('.monitor-edit-price-decrease, .monitor-edit-price-increase');
        if (!priceButton) return;

        const row = priceButton.closest('[data-monitor-edit-item]');
        const form = priceButton.closest('[data-monitor-edit-form]');
        if (!row || !form) return;

        const basePrice = Number(row.dataset.basePrice) || 0;
        const minPrice = Number(row.dataset.minPrice) || 0;
        const discountInput = row.querySelector('.monitor-edit-discount');
        const discountTypeInput = row.querySelector('.monitor-edit-discount-type');
        const adjustment = Math.max(0, Number(discountInput.value) || 0);
        const currentPrice = discountTypeInput.value === 'increase' ? basePrice + adjustment : basePrice - adjustment;
        const step = priceButton.classList.contains('monitor-edit-price-increase') ? 1000 : -1000;
        const sellingPrice = Math.max(minPrice, currentPrice + step);

        discountTypeInput.value = sellingPrice > basePrice ? 'increase' : 'decrease';
        discountInput.value = Math.abs(sellingPrice - basePrice);
        updateInlineEditTotals(form);
    });

    document.addEventListener('click', event => {
        const form = event.target.closest('[data-monitor-edit-form]');
        if (!form) return;

        const customerToggle = event.target.closest('.monitor-edit-customer-toggle');
        if (customerToggle) {
            const picker = form.querySelector('.monitor-edit-customer-picker');
            picker.hidden = !picker.hidden;
            if (!picker.hidden && !form.querySelector('.monitor-edit-customer-results').innerHTML.trim()) loadInlineCustomers(form);
            return;
        }

        if (event.target.closest('.monitor-edit-customer-search-button')) {
            loadInlineCustomers(form);
            return;
        }

        const customerPage = event.target.closest('.monitor-edit-customer-results .customer-page-btn');
        if (customerPage && !customerPage.disabled) {
            loadInlineCustomers(form, Number(customerPage.dataset.page) || 1);
            return;
        }

        const customerSort = event.target.closest('.monitor-edit-customer-results .customer-sort-link');
        if (customerSort) {
            event.preventDefault();
            form.dataset.customerSortBy = customerSort.dataset.sortBy || 'manual';
            form.dataset.customerSortDir = customerSort.dataset.sortDir || 'asc';
            loadInlineCustomers(form);
            return;
        }

        const customerButton = event.target.closest('.monitor-edit-customer-results .select-customer-btn');
        if (customerButton) {
            form.elements.namedItem('customer_id').value = customerButton.dataset.customerId || '';
            form.elements.namedItem('recipient_name').value = customerButton.dataset.customerName || '';
            form.elements.namedItem('recipient_phone').value = customerButton.dataset.customerPhone || '';
            form.elements.namedItem('recipient_email').value = customerButton.dataset.customerEmail || '';
            form.elements.namedItem('recipient_address').value = customerButton.dataset.customerAddress || '';
            form.querySelector('.monitor-edit-selected-customer').textContent = [customerButton.dataset.customerName, customerButton.dataset.customerPhone].filter(Boolean).join(' · ');
            form.querySelector('.monitor-edit-customer-picker').hidden = true;
            return;
        }

        if (event.target.closest('.monitor-edit-product-search-button')) {
            loadInlineProducts(form);
            return;
        }

        const productChoice = event.target.closest('.monitor-edit-product-results .monitor-product-choice');
        if (productChoice) {
            const card = productChoice.closest('.monitor-product-card');
            const variants = card.querySelector('.monitor-product-variants');
            const willOpen = variants.hidden;
            form.querySelectorAll('.monitor-edit-product-results .monitor-product-card.is-open').forEach(openCard => {
                if (openCard !== card) {
                    openCard.classList.remove('is-open');
                    openCard.querySelector('.monitor-product-choice')?.setAttribute('aria-expanded', 'false');
                    openCard.querySelector('.monitor-product-variants').hidden = true;
                }
            });
            card.classList.toggle('is-open', willOpen);
            variants.hidden = !willOpen;
            productChoice.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            return;
        }

        const productPage = event.target.closest('.monitor-edit-product-results .pagination a');
        if (productPage) {
            event.preventDefault();
            loadInlineProducts(form, productPage.href);
            return;
        }

        const addProduct = event.target.closest('.monitor-edit-product-results .monitor-variant-option');
        if (addProduct && !addProduct.disabled) {
            event.preventDefault();
            addInlineEditItem(form, addProduct);
            return;
        }

        const removeProduct = event.target.closest('.monitor-edit-remove-item');
        if (removeProduct) {
            if (form.querySelectorAll('[data-monitor-edit-item]').length <= 1) {
                notify('Đơn hàng phải có ít nhất một sản phẩm.');
                return;
            }
            removeProduct.closest('[data-monitor-edit-item]')?.remove();
            updateInlineEditTotals(form);
            loadInlineProducts(form);
        }
    });

    document.addEventListener('keydown', event => {
        const form = event.target.closest('[data-monitor-edit-form]');
        if (!form || event.key !== 'Enter') return;
        if (event.target.matches('.monitor-edit-customer-search')) {
            event.preventDefault();
            loadInlineCustomers(form);
        } else if (event.target.matches('.monitor-edit-product-search-input')) {
            event.preventDefault();
            loadInlineProducts(form);
        }
    });

    document.addEventListener('input', event => {
        if (!event.target.matches('[data-monitor-edit-form] .monitor-edit-quantity')) return;
        const form = event.target.closest('[data-monitor-edit-form]');
        if (form) updateInlineEditTotals(form);
    });

    document.addEventListener('change', event => {
        if (!event.target.matches('[data-monitor-edit-form] .monitor-edit-product-results #per-page-select')) return;
        const form = event.target.closest('[data-monitor-edit-form]');
        if (!form) return;
        form.dataset.productPerPage = event.target.value || '10';
        loadInlineProducts(form);
    });

    document.querySelectorAll('[data-monitor-edit-form]').forEach(form => {
        form.dataset.nextItemIndex = String(form.querySelectorAll('[data-monitor-edit-item]').length);
        updateInlineEditTotals(form);
    });

    const highlightedOrder = new URLSearchParams(window.location.search).get('highlight');
    if (highlightedOrder) {
        const card = document.getElementById(`monitor-order-${highlightedOrder}`);
        if (card) {
            card.style.boxShadow = '0 0 0 3px rgba(245, 158, 11, .35), 0 8px 24px rgba(15, 23, 42, .08)';
            setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'center' }), 250);
        }
    }
})();

document.addEventListener('submit', async function (event) {
    const autoApprovalForm = event.target.closest('[data-auto-approval-form]');
    if (autoApprovalForm) {
        event.preventDefault();

        const status = autoApprovalForm.querySelector('[data-auto-approval-status]');
        const button = autoApprovalForm.querySelector('button[type="submit"]');
        const showAutoApprovalStatus = (message, type) => {
            status.textContent = message;
            status.className = `monitor-auto-status is-visible is-${type}`;
        };

        if (!autoApprovalForm.checkValidity()) {
            autoApprovalForm.reportValidity();
            showAutoApprovalStatus('Vui lòng kiểm tra lại sản lượng và mức chiết khấu.', 'error');
            return;
        }

        if (button) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Đang lưu...';
        }

        try {
            const response = await fetch(autoApprovalForm.action, {
                method: 'POST',
                body: new FormData(autoApprovalForm),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                const validationMessage = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : data.message;
                throw new Error(validationMessage || 'Không thể lưu cấu hình duyệt tự động.');
            }

            showAutoApprovalStatus(data.message, 'success');
            if (typeof showToast === 'function') showToast(data.message, 'success');
        } catch (error) {
            showAutoApprovalStatus(error.message || 'Không thể kết nối máy chủ.', 'error');
            if (typeof showToast === 'function') showToast(error.message || 'Không thể kết nối máy chủ.', 'error');
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText;
            }
        }

        return;
    }

    const form = event.target.closest('.js-monitor-approval-form');
    if (!form) return;

    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    if (button) button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Không thể duyệt đơn.');
        }

        showToast(data.message || 'Đã duyệt đơn.', 'success');
        window.location.reload();
    } catch (error) {
        if (button) button.disabled = false;
        showToast(error.message || 'Không thể kết nối máy chủ.', 'error');
    }
});
</script>
<script>
(() => {
    const money = value => new Intl.NumberFormat('vi-VN').format(Math.round(Number(value) || 0)) + 'đ';
    const escapeHtml = value => {
        const node = document.createElement('div');
        node.textContent = String(value ?? '');
        return node.innerHTML;
    };
    const notify = (message, type = 'error') => typeof window.showToast === 'function'
        ? window.showToast(message, type)
        : window.alert(message);

    function selectedVariantIds(form) {
        return Array.from(form.querySelectorAll('[data-adjustment-item]')).map(row => row.dataset.variantId).filter(Boolean);
    }

    function refreshForm(form) {
        let requiresReturn = false;
        form.querySelectorAll('[data-adjustment-item]').forEach(row => {
            const quantity = Math.max(0, Number(row.querySelector('.adjustment-qty')?.value) || 0);
            const weight = Math.max(0, Number(row.querySelector('.adjustment-weight')?.value) || 0);
            const price = Math.max(0, Number(row.querySelector('.adjustment-price')?.value) || 0);
            requiresReturn ||= quantity < (Number(row.dataset.originalQty) || 0);
            row.querySelector('.adjustment-line-total').textContent = money(price * (row.dataset.pricedByKg === '1' ? weight : quantity));
        });
        const returnWrap = form.querySelector('.monitor-adjustment-return');
        returnWrap.hidden = !requiresReturn;
        returnWrap.querySelector('select').required = requiresReturn;
    }

    async function loadProducts(form, url = null) {
        const results = form.querySelector('.monitor-adjustment-product-results');
        const target = new URL(url || form.dataset.variantUrl, window.location.origin);
        target.searchParams.set('view', 'products');
        target.searchParams.set('search', form.querySelector('.monitor-adjustment-product-search').value.trim());
        target.searchParams.set('per_page', target.searchParams.get('per_page') || '10');
        target.searchParams.set('exclude_ids', selectedVariantIds(form).join(','));
        results.innerHTML = '<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span>Đang tải sản phẩm...</div>';
        try {
            const response = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Không tải được danh sách sản phẩm.');
            results.innerHTML = data.html;
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger py-2 mb-0">${escapeHtml(error.message)}</div>`;
        }
    }

    function addProduct(form, button) {
        const variantId = String(button.dataset.variantId || '');
        if (!variantId || selectedVariantIds(form).includes(variantId)) return notify('Biến thể này đã có trong đơn.');
        const body = form.querySelector('[data-adjustment-items]');
        const index = Number(form.dataset.nextItemIndex || body.children.length);
        form.dataset.nextItemIndex = String(index + 1);
        const name = button.dataset.variantName || 'Sản phẩm';
        const sku = button.dataset.variantSku || '';
        const size = button.dataset.variantSize || '—';
        const price = Math.max(0, Number(button.dataset.variantPrice) || 0);
        const weight = Math.max(0, Number(button.dataset.variantWeight) || 0);
        const pricedByKg = button.dataset.variantIsPricedByKg === '1';
        body.insertAdjacentHTML('beforeend', `<tr class="table-success" data-adjustment-item data-variant-id="${escapeHtml(variantId)}" data-original-qty="0" data-unit-weight="${weight}" data-priced-by-kg="${pricedByKg ? 1 : 0}">
            <td><strong>${escapeHtml(name)}</strong><small>${escapeHtml(sku)} · Hàng bổ sung</small><input type="hidden" name="items[${index}][order_item_id]" value=""><input type="hidden" name="items[${index}][product_variant_id]" value="${escapeHtml(variantId)}"><input type="hidden" name="items[${index}][note]" value="Bổ sung hàng thiếu trong đơn"></td>
            <td><input type="number" min="1" class="form-control form-control-sm adjustment-qty" name="items[${index}][adjusted_quantity]" value="1" required></td><td>${escapeHtml(size)}</td>
            <td><input type="number" min="0" step="0.001" data-auto-weight="1" class="form-control form-control-sm adjustment-weight" name="items[${index}][adjusted_weight]" value="${weight.toFixed(3)}" required></td>
            <td><input type="number" min="0" step="0.01" class="form-control form-control-sm adjustment-price" name="items[${index}][adjusted_price]" value="${price}" required></td>
            <td class="text-end fw-bold adjustment-line-total">${money(price * (pricedByKg ? weight : 1))}</td><td><button type="button" class="btn btn-sm btn-outline-danger monitor-adjustment-remove-item"><i class="bi bi-x"></i></button></td></tr>`);
        button.closest('.monitor-variant-option')?.remove();
        refreshForm(form);
    }

    document.addEventListener('click', async event => {
        const sampleButton = event.target.closest('.monitor-add-to-sample');
        if (sampleButton) {
            const original = sampleButton.innerHTML;
            const sampleCustomerId = sampleButton.dataset.sampleCustomerId || '';
            const removeCustomerSampleActions = () => {
                if (!sampleCustomerId) {
                    sampleButton.remove();
                    return;
                }
                document.querySelectorAll('.monitor-add-to-sample').forEach(button => {
                    if (button.dataset.sampleCustomerId === sampleCustomerId) button.remove();
                });
            };
            sampleButton.disabled = true;
            sampleButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span><span>Đang thêm...</span>';
            try {
                const response = await fetch(sampleButton.dataset.sampleUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const data = await response.json();
                if (! response.ok || ! data.success) {
                    if (response.status === 409) {
                        notify(data.message || 'Đã có đơn mẫu của khách hàng này rồi.', 'warning');
                        removeCustomerSampleActions();
                        return;
                    }
                    throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Không thể tạo đơn mẫu.'));
                }
                notify(data.message || 'Đã cho đơn hàng vào đơn mẫu.', 'success');
                removeCustomerSampleActions();
            } catch (error) {
                sampleButton.disabled = false;
                sampleButton.innerHTML = original;
                notify(error.message || 'Không thể kết nối máy chủ.');
            }
            return;
        }

        const opener = event.target.closest('.monitor-adjustment-open');
        if (opener) {
            const host = document.getElementById(opener.dataset.adjustmentTarget);
            if (host.classList.contains('is-open')) { host.classList.remove('is-open'); return; }
            document.querySelectorAll('.monitor-adjustment-host.is-open').forEach(other => { if (other !== host) other.classList.remove('is-open'); });
            host.classList.add('is-open');
            host.innerHTML = '<div class="monitor-adjustment-loading"><span class="spinner-border spinner-border-sm me-2"></span>Đang tải yêu cầu chỉnh sửa...</div>';
            host.scrollIntoView({ behavior: 'smooth', block: 'center' });
            try {
                const response = await fetch(opener.dataset.adjustmentUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Không tải được biểu mẫu.');
                host.innerHTML = data.html;
                const form = host.querySelector('[data-monitor-adjustment-form]');
                form.dataset.nextItemIndex = String(form.querySelectorAll('[data-adjustment-item]').length);
                refreshForm(form);
            } catch (error) {
                host.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error.message || 'Không thể kết nối máy chủ.')}</div>`;
            }
            return;
        }

        const form = event.target.closest('[data-monitor-adjustment-form]');
        if (!form) return;
        if (event.target.closest('.monitor-adjustment-close')) { form.closest('.monitor-adjustment-host').classList.remove('is-open'); return; }
        if (event.target.closest('.monitor-adjustment-products-toggle')) {
            const picker = form.querySelector('.monitor-adjustment-product-picker');
            picker.hidden = !picker.hidden;
            if (!picker.hidden && !form.querySelector('.monitor-adjustment-product-results').innerHTML.trim()) loadProducts(form);
            return;
        }
        if (event.target.closest('.monitor-adjustment-fees-toggle')) {
            const picker = form.querySelector('.monitor-adjustment-fee-picker');
            picker.hidden = !picker.hidden;
            return;
        }
        if (event.target.closest('.monitor-adjustment-product-search-button')) { loadProducts(form); return; }
        const productChoice = event.target.closest('.monitor-adjustment-product-results .monitor-product-choice');
        if (productChoice) {
            const variants = productChoice.closest('.monitor-product-card').querySelector('.monitor-product-variants');
            variants.hidden = !variants.hidden;
            productChoice.setAttribute('aria-expanded', variants.hidden ? 'false' : 'true');
            return;
        }
        const variant = event.target.closest('.monitor-adjustment-product-results .monitor-variant-option');
        if (variant) { addProduct(form, variant); return; }
        const pageLink = event.target.closest('.monitor-adjustment-product-results .pagination a');
        if (pageLink) { event.preventDefault(); loadProducts(form, pageLink.href); return; }
        const remove = event.target.closest('.monitor-adjustment-remove-item');
        if (remove) { remove.closest('[data-adjustment-item]').remove(); refreshForm(form); }
    });

    document.addEventListener('keydown', event => {
        const form = event.target.closest('[data-monitor-adjustment-form]');
        if (form && event.key === 'Enter' && event.target.matches('.monitor-adjustment-product-search')) { event.preventDefault(); loadProducts(form); }
    });
    document.addEventListener('input', event => {
        const form = event.target.closest('[data-monitor-adjustment-form]');
        if (!form || !event.target.matches('.adjustment-qty, .adjustment-weight, .adjustment-price')) return;
        const row = event.target.closest('[data-adjustment-item]');
        const weightInput = row?.querySelector('.adjustment-weight');
        if (event.target.matches('.adjustment-weight') && weightInput) weightInput.dataset.autoWeight = '0';
        if (event.target.matches('.adjustment-qty') && weightInput?.dataset.autoWeight === '1') {
            weightInput.value = (Math.max(0, Number(event.target.value) || 0) * Math.max(0, Number(row.dataset.unitWeight) || 0)).toFixed(3);
        }
        refreshForm(form);
    });
    document.addEventListener('change', event => {
        const form = event.target.closest('[data-monitor-adjustment-form]');
        if (!form) return;
        if (event.target.matches('.monitor-adjustment-product-results #per-page-select')) {
            const target = new URL(form.dataset.variantUrl, window.location.origin);
            target.searchParams.set('per_page', event.target.value || '10');
            loadProducts(form, target.toString());
        }
    });
    document.addEventListener('submit', async event => {
        const form = event.target.closest('[data-monitor-adjustment-form]');
        if (!form) return;
        event.preventDefault();
        if (!form.reportValidity()) return;
        const button = event.submitter || form.querySelector('button[type="submit"]');
        const original = button.innerHTML;
        const errors = form.querySelector('.monitor-adjustment-errors');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi...';
        errors.hidden = true;
        try {
            const body = new FormData(form);
            body.set('action', button.value || 'submit');
            const response = await fetch(form.action, { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Không gửi được yêu cầu.'));
            form.innerHTML = `<div class="alert alert-success mb-0"><strong>${escapeHtml(data.message)}</strong><div class="mt-2"><a class="btn btn-sm btn-outline-success" href="${escapeHtml(data.url)}">Xem yêu cầu #${escapeHtml(data.adjustment_id)}</a></div></div>`;
            const opener = form.closest('.monitor-order')?.querySelector('.monitor-adjustment-open');
            if (opener) { opener.disabled = true; opener.innerHTML = '<i class="bi bi-check2"></i><span>Đã gửi yêu cầu</span>'; }
            notify(data.message, 'success');
        } catch (error) {
            errors.textContent = error.message || 'Không thể kết nối máy chủ.';
            errors.hidden = false;
            button.disabled = false;
            button.innerHTML = original;
        }
    });
})();
</script>
@endpush
