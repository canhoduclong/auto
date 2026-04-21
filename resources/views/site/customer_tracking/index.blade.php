@extends('layouts.site')

@push('styles')
<style>
    .ct-page {
        background: linear-gradient(180deg, #f0f4ff 0%, #ffffff 40%, #f8f8fb 100%);
        padding: 40px 0 72px;
        min-height: 80vh;
    }
    .ct-shell { max-width: 1280px; margin: 0 auto; padding: 0 16px; }

    /* ── Hero ──────────────────────────────────────────────────── */
    .ct-hero {
        background: linear-gradient(135deg, #0f2745 0%, #1a3a6e 60%, #2d5fa6 100%);
        border-radius: 24px;
        color: #fff;
        padding: 28px 32px;
        margin-bottom: 28px;
        box-shadow: 0 18px 48px rgba(15, 39, 69, 0.20);
    }
    .ct-hero-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 4px; }
    .ct-hero-sub { font-size: .88rem; opacity: .75; }
    .ct-kpi-wrap { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
    .ct-kpi {
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 16px;
        padding: 14px 20px;
        min-width: 150px;
        flex: 1 1 140px;
    }
    .ct-kpi-label { font-size: .74rem; text-transform: uppercase; letter-spacing: .07em; opacity: .68; margin-bottom: 6px; }
    .ct-kpi-value { font-size: 1.55rem; font-weight: 800; line-height: 1; }

    /* ── Layout ────────────────────────────────────────────────── */
    .ct-layout { display: flex; gap: 24px; align-items: flex-start; }
    .ct-sidebar { width: 270px; flex-shrink: 0; }
    .ct-main { flex: 1; min-width: 0; }

    /* ── Filter panel ──────────────────────────────────────────── */
    .ct-filter-card {
        background: #fff;
        border: 1px solid rgba(0,0,0,.07);
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 8px 24px rgba(0,0,0,.05);
        position: sticky;
        top: 80px;
    }
    .ct-filter-title { font-weight: 700; font-size: .9rem; color: #1e3a5f; margin-bottom: 14px; }
    .ct-filter-group { margin-bottom: 14px; }
    .ct-filter-group label { font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: 4px; display: block; }
    .ct-filter-group .form-control,
    .ct-filter-group .form-select { border-radius: 10px; border-color: #d1d9e6; font-size: .85rem; height: 38px; }
    .ct-filter-group .form-control:focus,
    .ct-filter-group .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    .ct-type-btns { display: flex; gap: 6px; }
    .ct-type-btn {
        flex: 1; border: 1px solid #d1d9e6; background: #fff; color: #475569;
        border-radius: 8px; padding: 6px 0; font-size: .8rem; font-weight: 600; cursor: pointer;
        transition: all .15s;
    }
    .ct-type-btn.active { background: #2563eb; border-color: #2563eb; color: #fff; }
    .ct-apply-btn {
        width: 100%; background: #2563eb; color: #fff; border: none;
        border-radius: 10px; padding: 9px; font-weight: 700; font-size: .87rem;
        cursor: pointer; margin-top: 4px; transition: background .15s;
    }
    .ct-apply-btn:hover { background: #1d4ed8; }
    .ct-reset-btn {
        width: 100%; background: #f1f5f9; color: #64748b; border: none;
        border-radius: 10px; padding: 7px; font-weight: 600; font-size: .82rem;
        cursor: pointer; margin-top: 6px;
    }

    /* ── Main content ──────────────────────────────────────────── */
    .ct-card {
        background: #fff;
        border: 1px solid rgba(0,0,0,.07);
        border-radius: 20px;
        box-shadow: 0 8px 24px rgba(0,0,0,.05);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .ct-card-header {
        padding: 18px 22px 14px;
        border-bottom: 1px solid #f0f2f7;
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .ct-card-title { font-weight: 700; font-size: 1rem; color: #1e3a5f; margin: 0; }
    .ct-controls { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .ct-select { border: 1px solid #d1d9e6; border-radius: 8px; padding: 5px 10px; font-size: .82rem; background: #fff; }
    .ct-sort-btn {
        border: 1px solid #d1d9e6; background: #fff; border-radius: 8px;
        padding: 5px 10px; font-size: .82rem; cursor: pointer; white-space: nowrap;
        display: flex; align-items: center; gap: 4px;
    }
    .ct-sort-btn.active { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
    .ct-sort-icon { font-size: .7rem; }

    /* ── Chart ─────────────────────────────────────────────────── */
    .ct-chart-wrap { padding: 20px 22px; }
    .ct-chart-container { position: relative; height: 300px; }

    /* ── Customer card grid ───────────────────────────────────── */
    .ct-cards-grid { 
        padding-top: 9px;
    }
    .ct-cust-card {
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 16px; overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,.04);
        transition: box-shadow .15s, border-color .15s;
        display: flex; flex-direction: column;
        margin-bottom: 9px;
    }
    .ct-cust-card:hover {  ;box-shadow: 0 6px 22px rgba(0,0,0,.1); border-color: #bfdbfe; }
    .ct-cust-card-header {
        padding: 13px 15px 10px;
        display: flex; align-items: flex-start; gap: 10px;
    }
    .ct-cust-av {
        width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg,#1e40af,#3b82f6);
        color: #fff; font-weight: 800; font-size: .95rem;
        display: flex; align-items: center; justify-content: center;
    }
    .ct-cust-info { flex: 1; min-width: 0; }
    .ct-cname {
        font-weight: 700; color: #1e3a5f; font-size: .9rem;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        text-decoration: none; display: block;
    }
    .ct-cname:hover { color: #2563eb; }
    .ct-phone { font-size: .78rem; color: #64748b; margin-top: 1px; }
    .ct-cust-kpis {
        display: flex; gap: 0;
        border-top: 1px solid #f0f2f7; border-bottom: 1px solid #f0f2f7;
    }
    .ct-ckpi {
        flex: 1; text-align: center; padding: 8px 6px;
        border-right: 1px solid #f0f2f7;
    }
    .ct-ckpi:last-child { border-right: none; }
    .ct-ckpi-lbl { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: 2px; }
    .ct-ckpi-val { font-size: .88rem; font-weight: 800; color: #1e3a5f; }
    .ct-ckpi-val.blue  { color: #2563eb; }
    .ct-ckpi-val.green { color: #059669; }
    .ct-ckpi-val.red   { color: #dc2626; }
    .ct-spark-wrap { padding: 8px 12px 2px; flex: 1; }
    .ct-spark-lbl { font-size: .7rem; color: #94a3b8; font-weight: 600; margin-bottom: 4px; }
    .ct-spark-canvas { position: relative; height: 64px; }
    .ct-spark-empty { height: 64px; display: flex; align-items: center; justify-content: center; font-size: .78rem; color: #cbd5e1; }
    .ct-cust-footer {
        padding: 8px 12px 12px;
        display: flex; justify-content: flex-end;
    }
    .ct-detail-btn {
        display: inline-flex; align-items: center; gap: 5px;
        background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;
        border-radius: 8px; padding: 4px 12px; font-size: .78rem; font-weight: 700;
        text-decoration: none; transition: background .12s;
    }
    .ct-detail-btn:hover { background: #dbeafe; color: #1d4ed8; }
    /* regularity badge */
    .ct-regularity {
        font-size: .7rem; font-weight: 700; border-radius: 6px;
        padding: 2px 8px; margin-left: auto; align-self: flex-start; margin-top: 2px;
    }
    .ct-reg-high   { background: #dcfce7; color: #15803d; }
    .ct-reg-mid    { background: #fef9c3; color: #854d0e; }
    .ct-reg-low    { background: #fee2e2; color: #b91c1c; }
    .ct-reg-none   { background: #f1f5f9; color: #64748b; }

    /* ── Pagination ────────────────────────────────────────────── */
    .ct-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 22px; flex-wrap: wrap; gap: 8px; border-top: 1px solid #f0f2f7; }
    .ct-pagination-info { font-size: .82rem; color: #64748b; }
    .ct-pagination-btns { display: flex; gap: 4px; }
    .ct-pag-btn {
        border: 1px solid #d1d9e6; background: #fff; color: #475569;
        border-radius: 7px; padding: 4px 11px; font-size: .82rem; cursor: pointer;
    }
    .ct-pag-btn.active { background: #2563eb; border-color: #2563eb; color: #fff; font-weight: 700; }
    .ct-pag-btn:disabled { opacity: .45; cursor: not-allowed; }

    /* ── Loading ────────────────────────────────────────────────── */
    .ct-loading { display: none; align-items: center; justify-content: center; padding: 36px; gap: 10px; color: #64748b; }
    .ct-loading.show { display: flex; }
    .ct-spinner {
        width: 22px; height: 22px; border: 3px solid #e2e8f0;
        border-top-color: #2563eb; border-radius: 50%;
        animation: ct-spin .65s linear infinite;
    }
    @keyframes ct-spin { to { transform: rotate(360deg); } }

    /* ── Responsive ─────────────────────────────────────────────── */
    @media (max-width: 900px) {
        .ct-layout { flex-direction: column; }
        .ct-sidebar { width: 100%; position: static; }
    }
    @media (max-width: 600px) {
        .ct-hero { padding: 20px 16px; }
        .ct-hero-title { font-size: 1.2rem; }
        .ct-card-header { flex-direction: column; align-items: flex-start; }
    }
</style>
@endpush

@section('content')
<section class="ct-page">
    <div class="ct-shell">

        {{-- Hero --}}
        <div class="ct-hero">
            <div class="ct-hero-title"><i class="bi bi-people-fill me-2"></i>Theo dõi khách hàng</div>
            <div class="ct-hero-sub">Sản lượng, doanh thu và xu hướng tiêu thụ theo thời gian</div>
            <div class="ct-kpi-wrap">
                <div class="ct-kpi">
                    <div class="ct-kpi-label">Khách hàng</div>
                    <div class="ct-kpi-value" id="kpi-customers">—</div>
                </div>
                <div class="ct-kpi">
                    <div class="ct-kpi-label">Đơn hàng</div>
                    <div class="ct-kpi-value" id="kpi-orders">—</div>
                </div>
                <div class="ct-kpi">
                    <div class="ct-kpi-label">Sản lượng (sp)</div>
                    <div class="ct-kpi-value" id="kpi-qty">—</div>
                </div>
                <div class="ct-kpi">
                    <div class="ct-kpi-label">Doanh thu</div>
                    <div class="ct-kpi-value" id="kpi-revenue">—</div>
                </div>
            </div>
        </div>

        {{-- Two-column layout --}}
        <div class="ct-layout">

            {{-- Left: Filter Panel --}}
            <aside class="ct-sidebar">
                <div class="ct-filter-card">
                    <div class="ct-filter-title"><i class="bi bi-funnel me-1"></i> Bộ lọc</div>

                    <div class="ct-filter-group">
                        <label>Từ ngày</label>
                        <input type="date" id="filter-from" class="form-control"
                               value="{{ date('Y-m-d', strtotime('-29 days')) }}">
                    </div>
                    <div class="ct-filter-group">
                        <label>Đến ngày</label>
                        <input type="date" id="filter-to" class="form-control"
                               value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="ct-filter-group">
                        <label>Kiểu báo cáo</label>
                        <div class="ct-type-btns">
                            <button class="ct-type-btn active" data-type="day">Ngày</button>
                            <button class="ct-type-btn" data-type="week">Tuần</button>
                            <button class="ct-type-btn" data-type="month">Tháng</button>
                        </div>
                    </div>

                    <div class="ct-filter-group">
                        <label>Tìm khách hàng</label>
                        <input type="text" id="filter-search" class="form-control"
                               placeholder="Tên, SĐT, email…">
                    </div>

                    <button class="ct-apply-btn" id="btn-apply">
                        <i class="bi bi-search me-1"></i> Áp dụng
                    </button>
                    <button class="ct-reset-btn" id="btn-reset">Đặt lại</button>
                </div>
            </aside>

            {{-- Right: Main content --}}
            <div class="ct-main">

                {{-- Chart card --}}
                <div class="ct-card">
                    <div class="ct-card-header">
                        <h3 class="ct-card-title"><i class="bi bi-bar-chart-line me-1"></i> Biểu đồ thống kê</h3>
                        <div class="ct-controls">
                            <select class="ct-select" id="chart-metric">
                                <option value="qty">Sản lượng</option>
                                <option value="orders">Số đơn</option>
                                <option value="revenue">Doanh thu</option>
                            </select>
                        </div>
                    </div>
                    <div class="ct-chart-wrap">
                        <div class="ct-loading" id="chart-loading">
                            <div class="ct-spinner"></div> Đang tải biểu đồ…
                        </div>
                        <div class="ct-chart-container" id="chart-container">
                            <canvas id="ct-chart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Customer card grid --}}
                <div class="ct-card">
                    <div class="ct-card-header">
                        <h3 class="ct-card-title"><i class="bi bi-grid me-1"></i> Danh sách khách hàng</h3>
                        <div class="ct-controls">
                            <select class="ct-select" id="ctrl-per-page">
                                <option value="12">12 / trang</option>
                                <option value="24" selected>24 / trang</option>
                                <option value="48">48 / trang</option>
                            </select>
                            <button class="ct-sort-btn" data-sort="order_count" id="sort-orders">
                                <i class="bi bi-receipt me-1"></i>Đơn book
                                <span class="ct-sort-icon" id="icon-orders">▼</span>
                            </button>
                            <button class="ct-sort-btn" data-sort="total_qty" id="sort-qty">
                                <i class="bi bi-boxes me-1"></i>Sản lượng
                                <span class="ct-sort-icon" id="icon-qty"></span>
                            </button>
                            <button class="ct-sort-btn" data-sort="name" id="sort-name">
                                <i class="bi bi-sort-alpha-down me-1"></i>Tên
                                <span class="ct-sort-icon" id="icon-name"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="ct-loading" id="table-loading">
                        <div class="ct-spinner"></div> Đang tải dữ liệu…
                    </div>

                    <div id="table-wrap">
                        <div class="ct-cards-grid" id="cards-grid"></div>
                        <div class="ct-pagination" id="pagination-wrap" style="display:none">
                            <div class="ct-pagination-info" id="pag-info"></div>
                            <div class="ct-pagination-btns" id="pag-btns"></div>
                        </div>
                    </div>
                </div>

            </div>{{-- /ct-main --}}
        </div>{{-- /ct-layout --}}

    </div>{{-- /ct-shell --}}
</section>
@endsection

@push('scripts')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    // ── State ─────────────────────────────────────────────────────────
    let currentPage   = 1;
    let currentSort   = 'order_count';
    let currentDir    = 'desc';
    let currentPerPage = 24;
    const sparkCharts  = {};  // canvas id => Chart instance
    let currentType   = 'day';
    let chartInstance = null;
    let lastChartData = null;

    const dataUrl       = '{{ route("customer-tracking.data") }}';
    const detailBaseUrl = '{{ route("customer-tracking.show", ["customer" => "__ID__"]) }}';

    // ── DOM refs ──────────────────────────────────────────────────────
    const filterFrom   = document.getElementById('filter-from');
    const filterTo     = document.getElementById('filter-to');
    const filterSearch = document.getElementById('filter-search');
    const typeBtns     = document.querySelectorAll('.ct-type-btn');
    const btnApply     = document.getElementById('btn-apply');
    const btnReset     = document.getElementById('btn-reset');
    const ctrlPerPage  = document.getElementById('ctrl-per-page');
    const chartMetric  = document.getElementById('chart-metric');
    const cardsGrid    = document.getElementById('cards-grid');
    const pagWrap      = document.getElementById('pagination-wrap');
    const pagInfo      = document.getElementById('pag-info');
    const pagBtns      = document.getElementById('pag-btns');
    const chartLoading = document.getElementById('chart-loading');
    const chartCont    = document.getElementById('chart-container');
    const tableLoading = document.getElementById('table-loading');

    // KPIs
    const kpiCustomers = document.getElementById('kpi-customers');
    const kpiOrders    = document.getElementById('kpi-orders');
    const kpiQty       = document.getElementById('kpi-qty');
    const kpiRevenue   = document.getElementById('kpi-revenue');

    // ── Report type buttons ───────────────────────────────────────────
    typeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            typeBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentType = btn.dataset.type;
        });
    });

    // ── Sort buttons ──────────────────────────────────────────────────
    const sortBtns = document.querySelectorAll('.ct-sort-btn');
    sortBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const field = btn.dataset.sort;
            if (currentSort === field) {
                currentDir = currentDir === 'desc' ? 'asc' : 'desc';
            } else {
                currentSort = field;
                currentDir  = 'desc';
            }
            currentPage = 1;
            updateSortUI();
            fetchData();
        });
    });

    function updateSortUI() {
        const icons = { order_count: 'icon-orders', total_qty: 'icon-qty', name: 'icon-name', total_revenue: 'icon-rev' };
        sortBtns.forEach(btn => {
            const field = btn.dataset.sort;
            const iconId = icons[field];
            const el = iconId ? document.getElementById(iconId) : null;
            btn.classList.toggle('active', field === currentSort);
            if (el) el.textContent = field === currentSort ? (currentDir === 'desc' ? '▼' : '▲') : '';
        });
    }

    // ── Per-page ──────────────────────────────────────────────────────
    ctrlPerPage.addEventListener('change', () => {
        currentPerPage = parseInt(ctrlPerPage.value, 10);
        currentPage    = 1;
        fetchData();
    });

    // ── Chart metric ──────────────────────────────────────────────────
    chartMetric.addEventListener('change', () => {
        if (lastChartData) renderChart(lastChartData);
    });

    // ── Apply / Reset ─────────────────────────────────────────────────
    btnApply.addEventListener('click', () => {
        currentPage = 1;
        fetchData();
    });

    btnReset.addEventListener('click', () => {
        filterFrom.value   = formatDate(new Date(Date.now() - 29 * 86400000));
        filterTo.value     = formatDate(new Date());
        filterSearch.value = '';
        typeBtns.forEach(b => b.classList.toggle('active', b.dataset.type === 'day'));
        currentType    = 'day';
        currentSort    = 'order_count';
        currentDir     = 'desc';
        currentPage    = 1;
        currentPerPage = 24;
        ctrlPerPage.value = '24';
        updateSortUI();
        fetchData();
    });

    // Search: enter key
    filterSearch.addEventListener('keydown', e => {
        if (e.key === 'Enter') { currentPage = 1; fetchData(); }
    });

    // ── Fetch ─────────────────────────────────────────────────────────
    function fetchData() {
        setLoadingState(true);

        const params = new URLSearchParams({
            from_date:   filterFrom.value,
            to_date:     filterTo.value,
            report_type: currentType,
            search:      filterSearch.value,
            sort_by:     currentSort,
            sort_dir:    currentDir,
            per_page:    currentPerPage,
            page:        currentPage,
        });

        fetch(dataUrl + '?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            }
        })
        .then(r => r.json())
        .then(data => {
            setLoadingState(false);
            updateKpis(data.summary);
            renderCards(data.rows, data.page, data.per_page, data.total, data.sparkline_labels || []);
            renderPagination(data.page, data.last_page, data.total);
            lastChartData = data.chart;
            renderChart(data.chart);
        })
        .catch(() => {
            setLoadingState(false);
            cardsGrid.innerHTML = '<div class="p-4 text-center text-danger">Lỗi tải dữ liệu. Vui lòng thử lại.</div>';
        });
    }

    function setLoadingState(on) {
        chartLoading.classList.toggle('show', on);
        chartCont.style.opacity = on ? '0.3' : '1';
        tableLoading.classList.toggle('show', on);
    }

    // ── KPIs ──────────────────────────────────────────────────────────
    function updateKpis(s) {
        kpiCustomers.textContent = fmtNum(s.total_customers);
        kpiOrders.textContent    = fmtNum(s.total_orders);
        kpiQty.textContent       = fmtNum(s.total_qty);
        kpiRevenue.textContent   = fmtMoney(s.total_revenue);
    }

    // ── Cards grid ────────────────────────────────────────────────────
    function renderCards(rows, page, perPage, total, sparkLabels) {
        // Destroy old sparkline charts
        Object.values(sparkCharts).forEach(c => c.destroy());
        Object.keys(sparkCharts).forEach(k => delete sparkCharts[k]);

        if (!rows || rows.length === 0) {
            cardsGrid.innerHTML = '<div class="p-5 text-center text-muted w-100"><i class="bi bi-inbox" style="font-size:2rem"></i><div class="mt-2">Không có dữ liệu</div></div>';
            pagWrap.style.display = 'none';
            return;
        }

        // Format labels for tooltip (short form)
        const fmtLbl = l => {
            if (/^\d{4}-\d{2}-\d{2}$/.test(l)) { const [y,m,d]=l.split('-'); return `${d}/${m}`; }
            if (/^\d{4}-W\d{2}$/.test(l))       return l.replace('-W',' T');
            if (/^\d{4}-\d{2}$/.test(l))        { const [y,m]=l.split('-'); return `${m}/${y}`; }
            return l;
        };
        const displayLabels = sparkLabels.map(fmtLbl);

        let html = '';
        rows.forEach(r => {
            const detailUrl  = detailBaseUrl.replace('__ID__', r.customer_id);
            const initial    = esc((r.customer_name || '?').substring(0, 1).toUpperCase());
            const spark      = r.sparkline || [];
            const maxSpark   = Math.max(...spark, 1);
            // Regularity: what fraction of periods had any order?
            const activePct  = spark.length > 0 ? spark.filter(v => v > 0).length / spark.length : 0;
            let regClass = 'ct-reg-none', regLabel = 'Chưa có';
            if (spark.length > 0) {
                if (activePct >= 0.6)      { regClass = 'ct-reg-high'; regLabel = 'Đều'; }
                else if (activePct >= 0.3) { regClass = 'ct-reg-mid';  regLabel = 'Thỉnh thoảng'; }
                else                       { regClass = 'ct-reg-low';  regLabel = 'Thưa'; }
            }
            const canvasId = `spark-${r.customer_id}`;
            const hasOrders = r.order_count > 0;

            html += `
            <div class="ct-cust-card">
                <div class="ct-cust-card-header">
                    <div class="ct-cust-av">${initial}</div>
                    <div class="ct-cust-info">
                        <a href="${detailUrl}" class="ct-cname">${esc(r.customer_name)}</a>
                        <div class="ct-phone">${esc(r.customer_phone ?? '')}</div>
                    </div>
                    <span class="ct-regularity ${regClass}">${regLabel}</span>
                </div>
                <div class="ct-cust-kpis">
                    <div class="ct-ckpi">
                        <div class="ct-ckpi-lbl">Đơn book</div>
                        <div class="ct-ckpi-val blue">${fmtNum(r.order_count)}</div>
                    </div>
                    <div class="ct-ckpi">
                        <div class="ct-ckpi-lbl">Sản lượng</div>
                        <div class="ct-ckpi-val blue">${fmtNum(r.total_qty)}</div>
                    </div>
                    <div class="ct-ckpi">
                        <div class="ct-ckpi-lbl">Doanh thu</div>
                        <div class="ct-ckpi-val green">${fmtShort(r.total_revenue)}</div>
                    </div>
                    <div class="ct-ckpi">
                        <div class="ct-ckpi-lbl">Còn nợ</div>
                        <div class="ct-ckpi-val ${r.total_due > 0 ? 'red' : ''}">${fmtShort(r.total_due)}</div>
                    </div>
                </div>
                <div class="ct-spark-wrap">
                    <div class="ct-spark-lbl">Tần suất book hàng</div>
                    ${hasOrders
                        ? `<div class="ct-spark-canvas"><canvas id="${canvasId}"></canvas></div>`
                        : `<div class="ct-spark-empty"><i class="bi bi-dash-circle me-1"></i>Chưa có đơn</div>`
                    }
                </div>
                <div class="ct-cust-footer">
                    <a href="${detailUrl}" class="ct-detail-btn">
                        <i class="bi bi-bar-chart-line"></i> Xem chi tiết
                    </a>
                </div>
            </div>`;
        });
        cardsGrid.innerHTML = html;
        pagWrap.style.display = '';

        // Initialize sparkline charts
        rows.forEach(r => {
            if (!r.order_count) return;
            const canvasId = `spark-${r.customer_id}`;
            const canvas   = document.getElementById(canvasId);
            if (!canvas) return;
            const spark  = r.sparkline || [];
            const ctx    = canvas.getContext('2d');
            const maxVal = Math.max(...spark, 1);
            // Color bars: green if high, amber if mid, red if low
            const colors = spark.map(v => {
                const ratio = v / maxVal;
                if (ratio >= 0.6) return 'rgba(37,99,235,.75)';
                if (ratio >= 0.2) return 'rgba(245,158,11,.8)';
                if (v > 0)        return 'rgba(239,68,68,.65)';
                return 'rgba(203,213,225,.4)';
            });
            sparkCharts[canvasId] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: displayLabels,
                    datasets: [{
                        data: spark,
                        backgroundColor: colors,
                        borderRadius: 3,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 300 },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: items => displayLabels[items[0].dataIndex] || '',
                                label: c => c.raw + ' đơn',
                            }
                        }
                    },
                    scales: {
                        x: { display: false },
                        y: { display: false, beginAtZero: true }
                    }
                }
            });
        });
    }

    // ── Pagination ────────────────────────────────────────────────────
    function renderPagination(page, lastPage, total) {
        if (lastPage <= 1) { pagWrap.style.display = 'none'; return; }

        const from = (page - 1) * currentPerPage + 1;
        const to   = Math.min(page * currentPerPage, total);
        pagInfo.textContent = `Hiển thị ${from}–${to} / ${fmtNum(total)} khách hàng`;

        let html = '';
        html += `<button class="ct-pag-btn" onclick="goPage(${page - 1})" ${page === 1 ? 'disabled' : ''}>‹</button>`;

        const range = pageRange(page, lastPage);
        range.forEach(p => {
            if (p === '…') {
                html += `<span class="ct-pag-btn" style="cursor:default">…</span>`;
            } else {
                html += `<button class="ct-pag-btn ${p === page ? 'active' : ''}" onclick="goPage(${p})">${p}</button>`;
            }
        });

        html += `<button class="ct-pag-btn" onclick="goPage(${page + 1})" ${page === lastPage ? 'disabled' : ''}>›</button>`;
        pagBtns.innerHTML = html;
    }

    window.goPage = function (p) {
        currentPage = p;
        fetchData();
    };

    function pageRange(page, last) {
        const range = [];
        for (let i = 1; i <= last; i++) {
            if (i === 1 || i === last || Math.abs(i - page) <= 1) range.push(i);
            else if (range[range.length - 1] !== '…') range.push('…');
        }
        return range;
    }

    // ── Chart ─────────────────────────────────────────────────────────
    function renderChart(data) {
        if (!data) return;
        const metric = chartMetric.value;

        let dataset, label, color;
        if (metric === 'orders') {
            dataset = data.orderCounts;
            label   = 'Số đơn hàng';
            color   = 'rgba(99, 102, 241, 0.85)';
        } else if (metric === 'revenue') {
            dataset = data.revenues;
            label   = 'Doanh thu (VNĐ)';
            color   = 'rgba(16, 185, 129, 0.85)';
        } else {
            dataset = data.totalQtys;
            label   = 'Sản lượng (sản phẩm)';
            color   = 'rgba(37, 99, 235, 0.85)';
        }

        // Format labels for display
        const displayLabels = data.labels.map(l => {
            if (/^\d{4}-\d{2}-\d{2}$/.test(l)) {
                const [y, m, d] = l.split('-');
                return `${d}/${m}`;
            }
            if (/^\d{4}-W\d{2}$/.test(l)) {
                return l.replace('-W', ' T');
            }
            if (/^\d{4}-\d{2}$/.test(l)) {
                const [y, m] = l.split('-');
                return `${m}/${y}`;
            }
            return l;
        });

        const ctx = document.getElementById('ct-chart').getContext('2d');
        if (chartInstance) chartInstance.destroy();

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: displayLabels,
                datasets: [{
                    label,
                    data: dataset,
                    backgroundColor: color,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const val = ctx.raw;
                                if (metric === 'revenue') return ' ' + fmtMoney(val);
                                return ' ' + fmtNum(val);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, maxRotation: 45 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,.05)' },
                        ticks: {
                            font: { size: 11 },
                            callback: v => metric === 'revenue' ? fmtMoneyShort(v) : fmtNum(v)
                        }
                    }
                }
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────
    function fmtNum(n) { return Number(n ?? 0).toLocaleString('vi-VN'); }
    function fmtMoney(n) { return Number(n ?? 0).toLocaleString('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }); }
    function fmtMoneyShort(n) {
        if (n >= 1e9) return (n / 1e9).toFixed(1) + 'B';
        if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
        if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
        return n;
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
    function formatDate(d) {
        return d.getFullYear() + '-' +
               String(d.getMonth() + 1).padStart(2, '0') + '-' +
               String(d.getDate()).padStart(2, '0');
    }

    // ── Init ──────────────────────────────────────────────────────────
    updateSortUI();
    fetchData();
})();
</script>
@endpush
