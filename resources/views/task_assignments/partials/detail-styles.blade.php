@push('styles')
<style>
.task-workspace { color:#24364b; }
.task-workspace .content-header { padding-bottom:20px!important; }
.task-workspace .content-header h4 { font-size:24px; font-weight:700; line-height:1.3; }
.task-workspace .card { border:1px solid #dce4ec; border-radius:10px; overflow:hidden; box-shadow:0 3px 12px rgba(15,23,42,.04)!important; }
.task-workspace .card-header { padding:13px 16px!important; letter-spacing:.02em; }
.task-workspace .task-status-card .card-header,
.task-workspace .task-content-card .card-header,
.task-workspace .task-documents-card .card-header { background:#f1f5f9!important; color:#24364b!important; font-size:14px; font-weight:700; }
.task-workspace .task-content-card .card-body { padding:20px; }
.task-workspace .task-description { font-size:14px; line-height:1.75; color:#24364b; overflow-wrap:anywhere; }
.task-workspace .task-description p { margin-bottom:12px; }
.task-workspace .task-description ul,.task-workspace .task-description ol { padding-left:24px; }
.task-workspace a:not(.btn) { color:#087b80!important; text-decoration:none; }
.task-workspace a:not(.btn):hover,.task-workspace a:not(.btn):focus-visible { color:#07565e!important; text-decoration:underline; }
.task-workspace a:focus-visible { outline:2px solid #0d827a; outline-offset:3px; }
.task-workspace .task-document { gap:12px; padding:14px 0; align-items:flex-start; }
.task-workspace .task-document:first-child { padding-top:0; }
.task-workspace .task-document:last-child { border-bottom:0; padding-bottom:0; }
.task-workspace .task-document img { flex:0 0 68px; width:68px; height:64px; border:1px solid #dce4ec; border-radius:6px; object-fit:contain; background:#f8fafc; }
.task-workspace .task-document > i { flex:0 0 68px; text-align:center; padding:12px 0; border-radius:6px; background:#eef7f7; }
.task-workspace .task-document > div { flex:1; line-height:1.5; }
.task-workspace .task-document strong { display:block; font-size:13px; font-weight:600; color:#24364b; }
.task-workspace .task-document .small { font-size:12px; }
.task-workspace .task-document a { display:inline-block; margin-top:4px; }
@media(max-width:575px){.task-workspace .task-content-card .card-body{padding:14px}.task-workspace .content-header h4{font-size:21px}}

.child-recipient-label { padding:6px 10px; border:1px solid #d9e2ec; border-radius:5px; background:#fff; color:#526b82; font-size:12px; font-weight:600; }
.child-recipient-label[aria-pressed="true"] { border-color:#34d399; background:#ecfdf5; color:#087f5b; }
.child-recipient-label:hover { border-color:#34d399; }
.child-recipient-label:focus-visible { outline:2px solid #087f5b; outline-offset:2px; }

.member-evaluation-row, .member-evaluation-heading { display:grid; grid-template-columns:minmax(0,1fr) 65px 85px; align-items:center; gap:6px; }
.member-evaluation-row .evaluation-compact, .evaluation-compact .evaluation-picker { display:contents; }
.evaluation-compact summary, .evaluation-compact .evaluation-readonly { grid-column:2; grid-row:1; text-align:center; }
.member-evaluation-status { grid-column:3; grid-row:1; justify-self:end; }
.member-evaluation-info { grid-column:1; grid-row:1; min-width:0; overflow-wrap:anywhere; }
.evaluation-picker summary { cursor:pointer; list-style:none; color:#123550; }
.evaluation-picker summary::-webkit-details-marker { display:none; }
.evaluation-value { display:inline-block; padding:3px 8px; border:1px solid transparent; }
.evaluation-picker[open] .evaluation-value { border-color:#c5cbd1; background:#fff; }
.evaluation-options { margin:6px 0 0; }
.evaluation-compact .evaluation-options { grid-column:1 / -1; grid-row:2; }
.evaluation-nodes { display:flex; width:100%; max-width:380px; }
.evaluation-node { flex:1; min-width:0; padding:4px 0; border:0; background:transparent; color:#64748b; font-size:11px; cursor:pointer; }
.evaluation-node span { display:block; height:2px; background:#cbd5e1; margin-top:8px; position:relative; }
.evaluation-node span::after { content:''; position:absolute; width:8px; height:8px; border-radius:50%; background:#cbd5e1; left:50%; top:50%; transform:translate(-50%,-50%); }
.evaluation-node:hover, .evaluation-node.is-selected { color:#d96310; font-weight:700; }
.evaluation-node:hover span::after, .evaluation-node.is-selected span::after { background:#f58220; width:12px; height:12px; }
.evaluation-node:focus-visible, .evaluation-picker summary:focus-visible { outline:2px solid #087f5b; outline-offset:2px; }
.timeline { position: relative; padding-left: 28px; }
.timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
.tl-item { position: relative; margin-bottom: 20px; }
.tl-dot { position: absolute; left: -22px; top: 4px; width: 16px; height: 16px; border-radius: 50%; border: 2px solid; display: flex; align-items: center; justify-content: center; font-size: 8px; }
.tl-dot.approved { border-color: #22c55e; background: #f0fdf4; color: #22c55e; }
.tl-dot.pending  { border-color: #f59e0b; background: #fffbeb; color: #f59e0b; }
.tl-dot.rejected { border-color: #ef4444; background: #fef2f2; color: #ef4444; }
.info-grid { display: grid; grid-template-columns: 140px 1fr; gap: 6px 12px; font-size: 13px; }
.info-grid .ig-label { color: #94a3b8; }
.info-grid .ig-val { font-weight: 600; }
.attachment-thumb { max-width: 80px; max-height: 60px; border-radius: 6px; border: 1px solid #dee2e6; object-fit: cover; }
.task-person { border-bottom:1px solid #e2e8f0; padding:16px 0; }
.task-person-head { display:flex; flex-wrap:wrap; align-items:center; gap:12px; }
.task-person-head > strong { flex:1; min-width:150px; }
.task-person details > summary { cursor:pointer; color:#087f80; }
.task-activity { margin:8px 0; padding-left:14px; border-left:2px solid #dce9e7; font-size:13px; overflow-wrap:anywhere; }
.task-milestones { display:flex; overflow-x:auto; padding:16px 0 24px; }
.task-milestone { flex:1; min-width:120px; position:relative; padding:28px 6px 0; font-size:12px; text-align:center; }
.task-milestone::before { content:''; position:absolute; height:2px; background:#b9d2cc; top:9px; left:0; right:0; }
.task-milestone:first-child::before { left:50%; }
.task-milestone:last-child::before { right:50%; }
.task-milestone-dot { position:absolute; left:50%; top:2px; transform:translateX(-50%); width:16px; height:16px; border:2px solid currentColor; background:#fff; border-radius:50%; z-index:1; }
.task-person-columns { display:grid; grid-template-columns:minmax(0,1fr) 100px 150px; gap:12px; align-items:center; }
.task-person-columns > :nth-child(2), .task-person-columns > :nth-child(3) { text-align:center; justify-self:center; }
.task-person-columns > strong { min-width:0; }
.evaluation-widget { position:relative; }
.evaluation-options { position:absolute; width:280px; max-width:calc(100vw - 48px); right:0; top:100%; padding:14px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 6px 20px #0002; z-index:10; text-align:left; }
.evaluation-options input { width:100%; accent-color:#0d827a; }
@media(max-width:575px) { .task-person-columns { grid-template-columns:minmax(0,1fr) 65px 105px; gap:4px; font-size:12px; } .task-person-columns .badge { white-space:normal; } .evaluation-options { right:-80px; } }
.task-document { display:flex; gap:12px; padding:16px 0; border-bottom:1px solid #e2e8f0; overflow-wrap:anywhere; }
.task-document img { width:85px; height:70px; object-fit:cover; }
.task-document > div { min-width:0; }
@media(max-width:575px) { .content-wrapper.container { padding:0 12px; } .task-person-head { gap:8px; } }
.evaluation-toggle { border:0; background:transparent; padding:2px; color:#075985; cursor:pointer; }
.evaluation-options[hidden] { display:none !important; }
.evaluation-options { width:380px; }
.evaluation-options output { border:1px solid #cbd5e1; }
.evaluation-scale { position:relative; padding-bottom:8px; }
.evaluation-ticks { display:flex; justify-content:space-between; }
.evaluation-ticks button { position:relative; padding:0 0 22px; width:24px; border:0; background:none; color:#526b82; font-size:10px; cursor:pointer; }
.evaluation-ticks button span { position:absolute; bottom:4px; left:50%; transform:translateX(-50%); width:7px; height:7px; border-radius:50%; background:#cbd5e1; }
.evaluation-ticks button.selected { color:#ea7617; font-weight:bold; }
.evaluation-ticks button.selected span { background:#ea7617; }
.evaluation-options .evaluation-range { position:absolute; left:6px; bottom:9px; width:calc(100% - 12px); height:12px; margin:0; accent-color:#f58220; cursor:pointer; }
</style>
@endpush
