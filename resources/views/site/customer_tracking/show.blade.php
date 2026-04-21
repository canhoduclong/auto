@extends('layouts.site')

@push('styles')
<style>
    .cts-page {
        background: linear-gradient(180deg, #eef3ff 0%, #ffffff 35%, #f8f9fb 100%);
        padding: 32px 0 72px;
        min-height: 82vh;
    }
    .cts-shell { max-width: 1200px; margin: 0 auto; padding: 0 16px; }

    /* ── Back ─────────────────────────────────────────────────── */
    .cts-back {
        display: inline-flex; align-items: center; gap: 7px;
        color: #475569; font-size: .86rem; font-weight: 600;
        text-decoration: none; margin-bottom: 18px;
        padding: 6px 14px; background: #fff; border: 1px solid #d1d9e6;
        border-radius: 10px; transition: background .14s;
    }
    .cts-back:hover { background: #f1f5f9; color: #1e293b; }

    /* ── Hero ─────────────────────────────────────────────────── */
    .cts-hero {
        background: linear-gradient(135deg, #0f2745 0%, #1e40af 55%, #3b82f6 100%);
        border-radius: 22px; color: #fff;
        padding: 24px 28px 26px; margin-bottom: 20px;
        box-shadow: 0 16px 44px rgba(15,39,69,.22);
    }
    .cts-hero-top { display: flex; align-items: flex-start; gap: 20px; flex-wrap: wrap; }
    .cts-avatar {
        width: 62px; height: 62px; border-radius: 50%; flex-shrink: 0;
        background: rgba(255,255,255,.18); border: 2px solid rgba(255,255,255,.28);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.7rem; font-weight: 800;
    }
    .cts-hinfo { flex: 1; min-width: 0; }
    .cts-hname { font-size: 1.35rem; font-weight: 800; margin-bottom: 5px; }
    .cts-hmeta { font-size: .83rem; opacity: .76; display: flex; flex-wrap: wrap; gap: 14px; }
    .cts-kpi-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
    .cts-kpi {
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.13);
        border-radius: 14px; padding: 11px 18px; min-width: 120px; flex: 1 1 110px;
    }
    .cts-kpi-lbl { font-size: .71rem; text-transform: uppercase; letter-spacing: .07em; opacity: .68; margin-bottom: 3px; }
    .cts-kpi-val { font-size: 1.38rem; font-weight: 800; line-height: 1.1; }
    .cts-kpi-val.danger { color: #fca5a5; }

    /* ── Filter bar ───────────────────────────────────────────── */
    .cts-filter {
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 14px; padding: 14px 18px; margin-bottom: 18px;
        display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px;
        box-shadow: 0 3px 12px rgba(0,0,0,.04);
    }
    .cts-fg { display: flex; flex-direction: column; gap: 3px; }
    .cts-fg label { font-size: .76rem; font-weight: 600; color: #475569; }
    .cts-fg .form-control { border-radius: 8px; border-color: #d1d9e6; font-size: .84rem; height: 34px; }
    .cts-type-btns { display: flex; gap: 5px; }
    .cts-tbtn {
        border: 1px solid #d1d9e6; background: #fff; color: #475569;
        border-radius: 7px; padding: 4px 12px; font-size: .79rem; font-weight: 600; cursor: pointer;
    }
    .cts-tbtn.active { background: #2563eb; border-color: #2563eb; color: #fff; }
    .cts-apply {
        background: #2563eb; color: #fff; border: none; border-radius: 8px;
        padding: 6px 18px; font-weight: 700; font-size: .85rem;
        cursor: pointer; height: 34px; align-self: flex-end;
    }
    .cts-apply:hover { background: #1d4ed8; }

    /* ── Card ─────────────────────────────────────────────────── */
    .cts-card {
        background: #fff; border: 1px solid rgba(0,0,0,.07);
        border-radius: 18px; box-shadow: 0 6px 22px rgba(0,0,0,.05);
        overflow: hidden; margin-bottom: 18px;
    }
    .cts-card-hd {
        padding: 14px 20px 10px; border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .cts-card-title { font-weight: 700; font-size: .96rem; color: #1e3a5f; margin: 0; }
    .cts-legend { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .cts-legend-item { display: flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 600; color: #475569; }
    .cts-legend-dot  { width: 12px; height: 12px; border-radius: 3px; }
    .cts-legend-line { width: 20px; height: 3px; border-radius: 2px; }

    /* ── Combo chart ──────────────────────────────────────────── */
    .cts-chart-body { padding: 16px 20px 18px; }
    .cts-chart-h360 { position: relative; height: 360px; }

    /* ── Stats strip ──────────────────────────────────────────── */
    .cts-stat-strip { display: flex; flex-wrap: wrap; gap: 0; border-top: 1px solid #f0f2f7; }
    .cts-stat-item {
        flex: 1 1 0; padding: 12px 18px; text-align: center;
        border-right: 1px solid #f0f2f7;
    }
    .cts-stat-item:last-child { border-right: none; }
    .cts-stat-lbl { font-size: .73rem; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 4px; }
    .cts-stat-val { font-size: 1.18rem; font-weight: 800; color: #1e3a5f; }
    .cts-stat-val.green { color: #059669; }
    .cts-stat-val.blue  { color: #2563eb; }
    .cts-stat-val.red   { color: #dc2626; }

    /* ── Orders table ─────────────────────────────────────────── */
    .cts-tbl { width: 100%; border-collapse: collapse; font-size: .86rem; }
    .cts-tbl th {
        background: #f8fafc; color: #64748b; font-weight: 600; font-size: .75rem;
        text-transform: uppercase; letter-spacing: .06em; padding: 9px 14px;
        border-bottom: 1px solid #e9ecf2; white-space: nowrap;
    }
    .cts-tbl td { padding: 10px 14px; border-bottom: 1px solid #f1f3f8; vertical-align: middle; }
    .cts-tbl tr:last-child td { border-bottom: none; }
    .cts-tbl tr:hover td { background: #f8fbff; }
    .bs { display: inline-block; padding: 2px 9px; border-radius: 6px; font-size: .76rem; font-weight: 700; }
    .bs-done    { background: #dcfce7; color: #15803d; }
    .bs-cancel  { background: #fee2e2; color: #b91c1c; }
    .bs-deliver { background: #dbeafe; color: #1d4ed8; }
    .bs-default { background: #f1f5f9; color: #475569; }

    /* ── Loading ──────────────────────────────────────────────── */
    .cts-spin-wrap {
        display: none; align-items: center; justify-content: center;
        padding: 28px; gap: 9px; color: #64748b; font-size: .86rem;
    }
    .cts-spin-wrap.on { display: flex; }
    .cts-spinner {
        width: 18px; height: 18px; border: 2.5px solid #e2e8f0;
        border-top-color: #2563eb; border-radius: 50%;
        animation: sp .6s linear infinite;
    }
    @keyframes sp { to { transform: rotate(360deg); } }

    @media (max-width: 600px) {
        .cts-chart-h360 { height: 260px; }
        .cts-stat-item { padding: 10px 10px; }
    }
</style>
@endpush

@section('content')
<section class="cts-page">
<div class="cts-shell">

    {{-- Back --}}
    <a href="{{ route('customer-tracking.index') }}" class="cts-back">
        <i class="bi bi-arrow-left"></i> Danh sách theo dõi
    </a>

    {{-- Hero --}}
    <div class="cts-hero">
        <div class="cts-hero-top">
            <div class="cts-avatar">{{ strtoupper(mb_substr($customer->name, 0, 1)) }}</div>
            <div class="cts-hinfo">
                <div class="cts-hname">{{ $customer->name }}</div>
                <div class="cts-hmeta">
                    @if($customer->phone)
                        <span><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</span>
                    @endif
                    @if($customer->email)
                        <span><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</span>
                    @endif
                    @if($customer->company_name)
                        <span><i class="bi bi-building me-1"></i>{{ $customer->company_name }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="cts-kpi-row">
            <div class="cts-kpi">
                <div class="cts-kpi-lbl">Đơn book</div>
                <div class="cts-kpi-val" id="kpi-orders">—</div>
            </div>
            <div class="cts-kpi">
                <div class="cts-kpi-lbl">Sản lượng (sp)</div>
                <div class="cts-kpi-val" id="kpi-qty">—</div>
            </div>
            <div class="cts-kpi">
                <div class="cts-kpi-lbl">Doanh thu</div>
                <div class="cts-kpi-val" id="kpi-revenue">—</div>
            </div>
            <div class="cts-kpi">
                <div class="cts-kpi-lbl">Còn nợ</div>
                <div class="cts-kpi-val danger" id="kpi-due">—</div>
            </div>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="cts-filter">
        <div class="cts-fg">
            <label>Từ ngày</label>
            <input type="date" id="filter-from" class="form-control"
                   value="{{ date('Y-m-d', strtotime('-29 days')) }}">
        </div>
        <div class="cts-fg">
            <label>Đến ngày</label>
            <input type="date" id="filter-to" class="form-control"
                   value="{{ date('Y-m-d') }}">
        </div>
        <div class="cts-fg">
            <label>Nhóm theo</label>
            <div class="cts-type-btns">
                <button class="cts-tbtn active" data-type="day">Ngày</button>
                <button class="cts-tbtn" data-type="week">Tuần</button>
                <button class="cts-tbtn" data-type="month">Tháng</button>
            </div>
        </div>
        <button class="cts-apply" id="btn-apply">
            <i class="bi bi-arrow-clockwise me-1"></i>Cập nhật
        </button>
    </div>

    {{-- Main combo chart: Book hàng + Sản lượng --}}
    <div class="cts-card">
        <div class="cts-card-hd">
            <h3 class="cts-card-title">
                <i class="bi bi-bar-chart-line me-1"></i>
                Book hàng &amp; Sản lượng
                <span id="lbl-period" style="font-weight:400;color:#94a3b8;font-size:.85rem;margin-left:6px">theo ngày</span>
            </h3>
            <div class="cts-legend">
                <div class="cts-legend-item">
                    <div class="cts-legend-dot" style="background:rgba(37,99,235,.85)"></div>
                    Sản lượng (sp)
                </div>
                <div class="cts-legend-item">
                    <div class="cts-legend-line" style="background:#f59e0b"></div>
                    Đơn book
                </div>
            </div>
        </div>
        <div class="cts-chart-body">
            <div class="cts-spin-wrap" id="chart-loading"><div class="cts-spinner"></div> Đang tải…</div>
            <div class="cts-chart-h360">
                <canvas id="combo-chart"></canvas>
            </div>
        </div>
        {{-- Stats strip --}}
        <div class="cts-stat-strip">
            <div class="cts-stat-item">
                <div class="cts-stat-lbl">Tổng đơn book</div>
                <div class="cts-stat-val blue" id="stat-orders">—</div>
            </div>
            <div class="cts-stat-item">
                <div class="cts-stat-lbl">Tổng sản lượng</div>
                <div class="cts-stat-val blue" id="stat-qty">—</div>
            </div>
            <div class="cts-stat-item">
                <div class="cts-stat-lbl">TB sản lượng / đơn</div>
                <div class="cts-stat-val" id="stat-avg">—</div>
            </div>
            <div class="cts-stat-item">
                <div class="cts-stat-lbl">Doanh thu</div>
                <div class="cts-stat-val green" id="stat-revenue">—</div>
            </div>
            <div class="cts-stat-item">
                <div class="cts-stat-lbl">Còn nợ</div>
                <div class="cts-stat-val red" id="stat-due">—</div>
            </div>
        </div>
    </div>

    {{-- Orders table --}}
    <div class="cts-card">
        <div class="cts-card-hd">
            <h3 class="cts-card-title"><i class="bi bi-list-ul me-1"></i>Đơn hàng trong kỳ</h3>
            <small class="text-muted" id="orders-count"></small>
        </div>
        <div class="cts-spin-wrap" id="table-loading"><div class="cts-spinner"></div> Đang tải…</div>
        <div style="overflow-x:auto">
            <table class="cts-tbl">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã đơn</th>
                        <th>Ngày book</th>
                        <th>Sản lượng (sp)</th>
                        <th>Doanh thu</th>
                        <th>Còn nợ</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody id="orders-body">
                    <tr><td colspan="7" class="text-center text-muted py-4">Đang tải…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const DATA_URL   = '{{ route("customer-tracking.customer-data", $customer->id) }}';
    const filterFrom = document.getElementById('filter-from');
    const filterTo   = document.getElementById('filter-to');
    const typeBtns   = document.querySelectorAll('.cts-tbtn');
    const btnApply   = document.getElementById('btn-apply');
    let currentType  = 'day';
    let comboChart   = null;

    // ── Type buttons ──────────────────────────────────────────
    typeBtns.forEach(btn => btn.addEventListener('click', () => {
        typeBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentType = btn.dataset.type;
    }));

    btnApply.addEventListener('click', () => fetchData());

    // ── Status badges ─────────────────────────────────────────
    const STATUS = {
        completed:   ['Hoàn thành', 'bs-done'],
        delivered:   ['Đã giao',    'bs-done'],
        cancelled:   ['Đã hủy',     'bs-cancel'],
        rejected:    ['Từ chối',    'bs-cancel'],
        delivering:  ['Đang giao',  'bs-deliver'],
        in_delivery: ['Đang giao',  'bs-deliver'],
        pending:     ['Chờ xử lý',  'bs-default'],
        processing:  ['Đang xử lý', 'bs-default'],
    };
    function badge(s) {
        const [lbl, cls] = STATUS[s] ?? [s, 'bs-default'];
        return `<span class="bs ${cls}">${lbl}</span>`;
    }

    // ── Fetch ─────────────────────────────────────────────────
    function fetchData() {
        setLoad(true);
        const params = new URLSearchParams({
            from_date:   filterFrom.value,
            to_date:     filterTo.value,
            report_type: currentType,
        });
        const typeName = { day: 'ngày', week: 'tuần', month: 'tháng' }[currentType] ?? currentType;
        document.getElementById('lbl-period').textContent = 'theo ' + typeName;

        fetch(DATA_URL + '?' + params, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            }
        })
        .then(r => r.json())
        .then(d => {
            setLoad(false);
            updateKpis(d.summary);
            renderCombo(d.chart);
            updateStats(d.summary);
            renderOrders(d.recent_orders);
        })
        .catch(() => setLoad(false));
    }

    function setLoad(on) {
        document.getElementById('chart-loading').classList.toggle('on', on);
        document.getElementById('table-loading').classList.toggle('on', on);
    }

    // ── KPIs (hero) ───────────────────────────────────────────
    function updateKpis(s) {
        document.getElementById('kpi-orders').textContent  = fmtNum(s.order_count);
        document.getElementById('kpi-qty').textContent     = fmtNum(s.total_qty);
        document.getElementById('kpi-revenue').textContent = fmtShort(s.total_revenue);
        document.getElementById('kpi-due').textContent     = fmtShort(s.total_due);
    }

    // ── Stats strip ───────────────────────────────────────────
    function updateStats(s) {
        const avg = s.order_count > 0
            ? (s.total_qty / s.order_count).toFixed(1)
            : '0';
        document.getElementById('stat-orders').textContent  = fmtNum(s.order_count);
        document.getElementById('stat-qty').textContent     = fmtNum(s.total_qty);
        document.getElementById('stat-avg').textContent     = fmtNum(avg) + ' sp';
        document.getElementById('stat-revenue').textContent = fmtShort(s.total_revenue);
        document.getElementById('stat-due').textContent     = fmtShort(s.total_due);
    }

    // ── Combo chart (bar = sản lượng, line = đơn book) ────────
    function renderCombo(c) {
        if (!c) return;
        const labels = (c.labels || []).map(fmtLabel);
        const qtys   = c.totalQtys   || [];
        const orders = c.orderCounts || [];

        const ctx = document.getElementById('combo-chart').getContext('2d');
        if (comboChart) comboChart.destroy();

        comboChart = new Chart(ctx, {
            data: {
                labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Sản lượng (sp)',
                        data: qtys,
                        backgroundColor: 'rgba(37,99,235,.75)',
                        borderRadius: 5,
                        borderSkipped: false,
                        yAxisID: 'yQty',
                        order: 2,
                    },
                    {
                        type: 'line',
                        label: 'Đơn book',
                        data: orders,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245,158,11,.10)',
                        borderWidth: 2.5,
                        fill: false,
                        tension: 0.38,
                        pointRadius: orders.length <= 62 ? 4 : 0,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#f59e0b',
                        yAxisID: 'yOrders',
                        order: 1,
                    },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                if (ctx.datasetIndex === 0)
                                    return '  Sản lượng: ' + fmtNum(ctx.raw) + ' sp';
                                return '  Đơn book: ' + fmtNum(ctx.raw) + ' đơn';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 }, maxRotation: 45 }
                    },
                    yQty: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,.045)' },
                        ticks: {
                            font: { size: 10 },
                            callback: v => fmtNum(v) + ' sp'
                        },
                        title: {
                            display: true,
                            text: 'Sản lượng (sp)',
                            font: { size: 10 }, color: '#64748b'
                        }
                    },
                    yOrders: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: {
                            font: { size: 10 },
                            callback: v => fmtNum(v) + ' đơn',
                            stepSize: 1,
                        },
                        title: {
                            display: true,
                            text: 'Số đơn book',
                            font: { size: 10 }, color: '#f59e0b'
                        }
                    }
                }
            }
        });
    }

    // ── Orders table ──────────────────────────────────────────
    function renderOrders(orders) {
        const tbody   = document.getElementById('orders-body');
        const countEl = document.getElementById('orders-count');
        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Không có đơn hàng trong kỳ này</td></tr>';
            if (countEl) countEl.textContent = '';
            return;
        }
        if (countEl) countEl.textContent = orders.length + ' đơn';
        let html = '';
        orders.forEach((o, i) => {
            html += `<tr>
                <td class="text-muted">${i + 1}</td>
                <td class="fw-semibold text-primary">${esc(o.code)}</td>
                <td>${esc(o.created_at)}</td>
                <td><span style="background:#eff6ff;color:#1d4ed8;border-radius:6px;padding:2px 9px;font-weight:700;font-size:.8rem">${fmtNum(o.qty)} sp</span></td>
                <td class="fw-semibold">${fmtMoney(o.total)}</td>
                <td class="${o.amount_due > 0 ? 'text-danger fw-semibold' : 'text-muted'}">${fmtMoney(o.amount_due)}</td>
                <td>${badge(o.status)}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    }

    // ── Helpers ───────────────────────────────────────────────
    function fmtNum(n) { return Number(n ?? 0).toLocaleString('vi-VN'); }
    function fmtMoney(n) {
        return Number(n ?? 0).toLocaleString('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 });
    }
    function fmtShort(n) {
        n = Number(n ?? 0);
        if (n >= 1e9) return (n / 1e9).toFixed(1) + ' tỷ';
        if (n >= 1e6) return (n / 1e6).toFixed(1) + ' tr';
        if (n >= 1e3) return (n / 1e3).toFixed(0) + 'K';
        return fmtNum(n);
    }
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }
    function fmtLabel(l) {
        if (/^\d{4}-\d{2}-\d{2}$/.test(l)) { const [y,m,d]=l.split('-'); return `${d}/${m}`; }
        if (/^\d{4}-W\d{2}$/.test(l))       return l.replace('-W', ' T');
        if (/^\d{4}-\d{2}$/.test(l))        { const [y,m]=l.split('-'); return `${m}/${y}`; }
        return l;
    }

    // ── Init ──────────────────────────────────────────────────
    fetchData();
})();
</script>
@endpush
