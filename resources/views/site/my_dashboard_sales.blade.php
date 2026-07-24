@extends('layouts.site')

@section('title', 'Bảng điều khiển')

@push('styles')
<style>
    .my-dashboard {
        --dashboard-maroon: #5b2b2f;
        --dashboard-blue: #075985;
        --dashboard-border: #d7e2eb;
        --dashboard-orange: #c65b00;
        background: #f5f8fb;
        min-height: calc(100vh - 80px);
        padding: 32px 0 70px;
    }
    .dashboard-shell {
        display: grid;
        grid-template-columns: 232px minmax(0, 680px) 332px;
        gap: 28px;
        width: calc(100% - 32px);
        max-width: 1300px;
        margin: 0 auto;
        align-items: start;
    }
    .dashboard-sidebar,
    .dashboard-main,
    .dashboard-price-column { min-width: 0; }
    .dashboard-sidebar { display: grid; gap: 8px; }
    .dashboard-menu-link {
        display: flex;
        align-items: center;
        gap: 11px;
        min-height: 45px;
        padding: 10px 13px;
        border: 1px solid #d4e1eb;
        border-radius: 3px;
        background: #fff;
        color: var(--dashboard-blue);
        font-size: .83rem;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 2px 7px rgba(15, 23, 42, .025);
        transition: background-color .18s ease, border-color .18s ease;
    }
    .dashboard-menu-link i {
        width: 18px;
        color: #527394;
        font-size: 1rem;
        text-align: center;
    }
    .dashboard-menu-link:hover {
        border-color: #b9d4de;
        background: #eaf5f6;
        color: var(--dashboard-blue);
    }
    .dashboard-menu-link.is-active {
        min-height: 40px;
        margin: 8px 4px 11px;
        border-color: var(--dashboard-maroon);
        border-radius: 0;
        background: var(--dashboard-maroon);
        color: #fff;
        box-shadow: none;
    }
    .dashboard-menu-link.is-active i { color: #d9eef4; }
    .dashboard-card {
        border: 1px solid var(--dashboard-border);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 3px 9px rgba(15, 23, 42, .035);
    }
    .dashboard-commission {
        min-height: 69px;
        margin-bottom: 14px;
        padding: 13px 12px;
        overflow: hidden;
    }
    .dashboard-section-title {
        margin: 0;
        color: #263645;
        font-size: .82rem;
        font-weight: 500;
    }
    .commission-feed-list { margin-top: 7px; }
    .feed-item {
        padding: 7px 0;
        border-top: 1px dashed #e2e8f0;
    }
    .feed-item:first-child { padding-top: 0; border-top: 0; }
    .dashboard-empty {
        color: #64748b;
        font-size: .76rem;
    }
    .dashboard-chart-card {
        margin-bottom: 27px;
        padding: 15px 11px 10px;
    }
    .dashboard-chart-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }
    .dashboard-chart-note {
        color: #64748b;
        font-size: .7rem;
        white-space: nowrap;
    }
    .dashboard-chart-wrap {
        position: relative;
        height: 245px;
    }
    .dashboard-main .dept-broadcast-card {
        margin: 0 !important;
        border: 0;
        border-radius: 0;
        background: #fff9e6;
        box-shadow: none;
    }
    .dashboard-main .dept-broadcast-head {
        padding: 8px 10px;
        border-bottom: 1px solid #f4cf70;
    }
    .dashboard-main .dept-broadcast-title {
        color: #8d3e00;
        font-size: .78rem;
    }
    .dashboard-main .dept-broadcast-subtitle {
        color: #b45309;
        font-size: .73rem;
    }
    .dashboard-main .dept-broadcast-list { padding: 0 10px; }
    .dashboard-main .dept-broadcast-item { padding: 10px 0; }
    .dashboard-main .dept-broadcast-item-title { font-size: .9rem; }
    .dashboard-main .dept-broadcast-message { font-size: .82rem; }
    .dashboard-main .dept-broadcast-sender,
    .dashboard-main .dept-broadcast-time { font-size: .7rem; }
    .price-board-card {
        border: 1px solid #d77742;
        background: #fff;
        padding: 11px 12px 15px;
    }
    .price-board-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 5px;
    }
    .price-board-title {
        margin: 0;
        color: var(--dashboard-orange);
        font-size: .88rem;
        font-weight: 500;
        text-transform: uppercase;
    }
    .price-board-badge {
        padding: 3px 9px;
        border-radius: 5px;
        background: #ffc400;
        color: #211700;
        font-size: .68rem;
        font-weight: 800;
    }
    .price-board-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        font-size: .77rem;
    }
    .price-board-table td {
        padding: 7px 4px;
        border-bottom: 1px solid #ecd5ab;
        vertical-align: middle;
    }
    .price-board-product-name,
    .price-board-group td { color: #202938; font-weight: 700; }
    .price-board-group td { padding-top: 9px; padding-bottom: 2px; border-bottom: 0; }
    .price-board-variant-name { padding-left: 18px !important; color: #384152; }
    .price-board-variant-name::before {
        content: "–";
        margin-right: 7px;
        color: var(--dashboard-orange);
    }
    .price-update-price {
        color: var(--dashboard-orange);
        font-weight: 800;
        white-space: nowrap;
        text-align: right;
    }
    .price-update-note {
        margin-top: 7px;
        padding-top: 2px;
        color: #9a3f00;
        font-size: .75rem;
        line-height: 1.5;
    }
    .manager-board { display: grid; gap: 14px; color: #1f2937; }
    .manager-board-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 14px; }
    .manager-board-head h1 { margin: 0; color: #17376e; font-size: 1.25rem; font-weight: 850; text-transform: uppercase; }
    .manager-board-head p { margin: 3px 0 0; color: #64748b; font-size: .72rem; }
    .manager-date-filter { display: flex; align-items: end; gap: 5px; padding: 6px; border: 1px solid var(--dashboard-border); border-radius: 7px; background: #fff; }
    .manager-date-filter label span { display: block; margin-bottom: 2px; color: #64748b; font-size: .58rem; }
    .manager-date-filter input { width: 112px; border: 0; color: #334155; font-size: .67rem; outline: 0; }
    .manager-date-filter button { width: 29px; height: 29px; border: 0; border-radius: 5px; background: #17376e; color: #fff; }
    .manager-summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    .manager-summary-card { min-height: 120px; padding: 12px 11px 10px; border: 1px solid var(--dashboard-border); border-radius: 8px; background: #fff; box-shadow: 0 2px 7px rgba(15,23,42,.035); }
    .manager-summary-label { display: flex; align-items: center; gap: 7px; font-size: .65rem; font-weight: 800; text-transform: uppercase; }
    .manager-summary-label i { display: grid; width: 28px; height: 28px; place-items: center; border-radius: 50%; background: currentColor; font-size: .8rem; }
    .manager-summary-label i::before { color: #fff; }
    .manager-summary-card > strong { display: block; margin: 10px 0 8px; color: #111827; font-size: 1.05rem; text-align: center; white-space: nowrap; }
    .manager-summary-card strong em { font-size: .65rem; font-style: normal; font-weight: 500; }
    .manager-summary-card > small { display: block; color: #64748b; font-size: .61rem; text-align: center; }
    .manager-summary-card .is-good { color: #14804a; } .manager-summary-card .is-bad { color: #c52b3c; } .manager-summary-card .is-neutral { color: #64748b; }
    .tone-green { color: #15803d; } .tone-blue { color: #1261a6; } .tone-purple { color: #5835a5; }
    .tone-orange { color: #df7009; } .tone-teal { color: #08717b; } .tone-red { color: #cf2237; }
    .manager-detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .manager-panel { overflow: hidden; border: 1px solid var(--dashboard-border); border-radius: 8px; background: #fff; }
    .manager-panel > h2 { margin: 0; padding: 7px 11px; background: var(--panel-color, #17376e); color: #fff; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
    .panel-blue { --panel-color:#1463a5; } .panel-orange { --panel-color:#e36b08; } .panel-teal { --panel-color:#08717b; }
    .panel-red { --panel-color:#cf2237; } .panel-navy { --panel-color:#17376e; } .panel-purple { --panel-color:#55309b; }
    .manager-size-body { display: flex; align-items: center; gap: 14px; min-height: 178px; padding: 13px; }
    .manager-donut { display: grid; flex: 0 0 112px; width: 112px; height: 112px; place-items: center; border-radius: 50%; background: var(--manager-donut); }
    .manager-donut::before { content:""; grid-area:1/1; width: 68px; height: 68px; border-radius:50%; background:#fff; }
    .manager-donut span { z-index:1; grid-area:1/1; font-size:.6rem; text-align:center; } .manager-donut strong { display:block; font-size:.9rem; }
    .manager-size-legend { display:grid; gap:8px; font-size:.6rem; }
    .manager-size-legend > div { display:flex; gap:6px; align-items:flex-start; }
    .manager-size-legend i { flex:0 0 8px; width:8px; height:8px; margin-top:3px; border-radius:50%; }
    .manager-size-legend small { display:block; color:#64748b; }
    .manager-metric-list { margin:0; padding:8px 12px; }
    .manager-metric-list > div { display:flex; align-items:center; justify-content:space-between; gap:8px; min-height:39px; border-bottom:1px solid #e9eef3; }
    .manager-metric-list > div:last-child { border:0; } .manager-metric-list dt { color:#475569; font-size:.65rem; font-weight:500; }
    .manager-metric-list dd { margin:0; font-size:.72rem; font-weight:800; white-space:nowrap; }
    .manager-reasons { display:flex; flex-wrap:wrap; gap:4px 10px; padding:8px 12px; border-top:1px solid #e9eef3; font-size:.58rem; }
    .manager-reasons b { width:100%; } .manager-reasons span { color:#64748b; }
    .manager-performance table { width:100%; border-collapse:collapse; font-size:.58rem; }
    .manager-performance th, .manager-performance td { padding:7px 6px; border:1px solid #e5eaf0; white-space:nowrap; text-align:right; }
    .manager-performance th:first-child, .manager-performance td:first-child { text-align:left; }
    .manager-performance thead { background:#f2f6fa; } .manager-table-empty { padding:18px!important; color:#64748b; text-align:center!important; }
    .manager-progress { display:inline-block; width:30px; height:4px; margin-right:4px; overflow:hidden; border-radius:4px; background:#e5e7eb; vertical-align:middle; }
    .manager-progress i { display:block; height:100%; background:#16865c; }
    .manager-kpi-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); padding:12px 6px; }
    .manager-kpi-grid > div { display:grid; min-height:90px; padding:4px 8px; place-items:center; align-content:center; gap:5px; border-left:1px solid #e5eaf0; text-align:center; }
    .manager-kpi-grid > div:first-child { border-left:0; } .manager-kpi-grid i { color:#17376e; font-size:1.1rem; }
    .manager-kpi-grid span { min-height:26px; font-size:.58rem; } .manager-kpi-grid strong { font-size:.82rem; }
    @media (max-width: 1340px) {
        .dashboard-shell {
            grid-template-columns: 220px minmax(0, 1fr);
            max-width: 1000px;
        }
        .dashboard-price-column { grid-column: 2; }
    }
    @media (max-width: 767.98px) {
        .my-dashboard { padding-top: 18px; }
        .dashboard-shell { display: block; width: calc(100% - 24px); }
        .dashboard-sidebar {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 16px;
        }
        .dashboard-menu-link.is-active { grid-column: 1 / -1; margin: 0; }
        .dashboard-menu-link { padding: 9px 10px; font-size: .76rem; }
        .dashboard-chart-wrap { height: 215px; }
        .dashboard-chart-head { align-items: flex-start; }
        .dashboard-chart-note { white-space: normal; text-align: right; }
        .dashboard-price-column { margin-top: 18px; }
        .manager-board-head { display:block; } .manager-date-filter { margin-top:10px; width:100%; }
        .manager-date-filter label { flex:1; } .manager-date-filter input { width:100%; }
        .manager-summary-grid, .manager-detail-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .manager-kpi-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .manager-kpi-grid > div { border-bottom:1px solid #e5eaf0; }
        .manager-size-body { flex-direction:column; align-items:flex-start; }
    }
</style>
@endpush

@section('content')
@php
    $monitorRoute = fn (string $tab) => route('pages.my_orders.monitoring', ['tab' => $tab]);
    $latestPriceBoardDate = collect($productPriceAppliedDates ?? [])
        ->filter()
        ->map(fn ($date) => \Carbon\Carbon::parse($date))
        ->sortByDesc(fn ($date) => $date->timestamp)
        ->first();
@endphp
<div class="my-dashboard">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar" aria-label="Điều hướng đơn hàng">
            <a href="{{ route('pages.my_dashboard') }}" class="dashboard-menu-link is-active" aria-current="page">
                <i class="bi bi-house-door"></i><span>Bảng điều khiển</span>
            </a>
            <a href="{{ $monitorRoute('today') }}" class="dashboard-menu-link">
                <i class="bi bi-file-earmark-text"></i><span>Đơn hôm nay</span>
            </a>
            <a href="{{ $monitorRoute('drafts') }}" class="dashboard-menu-link">
                <i class="bi bi-file-earmark-text"></i><span>Đơn hàng Mẫu</span>
            </a>
            <a href="{{ $monitorRoute('my_orders') }}" class="dashboard-menu-link">
                <i class="bi bi-bag-check"></i><span>Đơn của tôi</span>
            </a>
        </aside>

        <main class="dashboard-main">
            @if($isManagerDashboard ?? false)
                @include('site.partials.manager_dashboard')
            @else
            <section class="dashboard-card dashboard-commission" id="commission-feed">
                <h2 class="dashboard-section-title">Chúc mừng nhận hoa hồng</h2>
                <div class="commission-feed-list">
                    @forelse($commissionFeed as $item)
                        <div class="feed-item">
                            <div class="small fw-semibold">{{ $item->order_code ?: ('#' . $item->order_id) }} - {{ $item->customer_name ?: 'Khách hàng' }}</div>
                            <div class="dashboard-empty">
                                Giá trị đơn: {{ number_format((float) $item->order_total, 0, ',', '.') }}đ ·
                                Hoa hồng: <span class="text-success fw-semibold">{{ number_format((float) $item->commission_amount, 0, ',', '.') }}đ</span>
                            </div>
                        </div>
                    @empty
                        <div class="dashboard-empty">Chưa có bản ghi hoa hồng.</div>
                    @endforelse
                </div>
            </section>

            <section class="dashboard-card dashboard-chart-card">
                <div class="dashboard-chart-head">
                    <h2 class="dashboard-section-title">Biểu đồ doanh số</h2>
                    <span class="dashboard-chart-note">Cập nhật tự động mỗi 30 giây</span>
                </div>
                <div class="dashboard-chart-wrap">
                    <canvas id="salesChart"></canvas>
                </div>
            </section>
            @endif

            @include('layouts.partials.department_broadcasts', ['showEmpty' => true])
        </main>

        <aside class="dashboard-price-column">
            <section class="price-board-card">
                <div class="price-board-head">
                    <h2 class="price-board-title">Bảng báo giá sản phẩm</h2>
                    <span class="price-board-badge">Mới</span>
                </div>
                @if(($productPriceBoard ?? collect())->isNotEmpty())
                    <div class="table-responsive">
                        <table class="price-board-table" aria-label="Bảng báo giá sản phẩm">
                            <tbody>
                                @foreach($productPriceBoard as $priceProduct)
                                    @if(!empty($priceProduct['has_mixed_prices']))
                                        <tr class="price-board-group">
                                            <td colspan="2">{{ $priceProduct['product_name'] }}</td>
                                        </tr>
                                        @foreach($priceProduct['variants'] as $priceVariant)
                                            <tr>
                                                <td class="price-board-variant-name">
                                                    {{ $priceVariant['size_label'] ? $priceVariant['size_label'] . ' kg' : $priceVariant['name'] }}
                                                </td>
                                                <td class="price-update-price">{{ number_format((float) ($priceVariant['price'] ?? 0), 0, ',', '.') }}đ/{{ $priceVariant['price_unit'] ?? 'kg' }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="price-board-product-name">{{ $priceProduct['product_name'] }}</td>
                                            <td class="price-update-price">{{ number_format((float) ($priceProduct['representative_price'] ?? 0), 0, ',', '.') }}đ/{{ $priceProduct['representative_price_unit'] ?? 'kg' }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="dashboard-empty py-3">Chưa có bảng giá sản phẩm.</div>
                @endif
                <div class="price-update-note">
                    @if($latestPriceBoardDate)
                        <div>Áp dụng từ {{ $latestPriceBoardDate->format('d/m/Y') }}</div>
                    @endif
                    <div>Giá bán chưa bao gồm VAT.</div>
                    <div>Miễn phí vận chuyển nội thành 5kg từ 20 con.</div>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartCanvas = document.getElementById('salesChart');
    if (!chartCanvas || typeof Chart === 'undefined') return;

    const salesChart = new Chart(chartCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: @json($salesChart['labels'] ?? []),
            datasets: [{
                data: @json($salesChart['values'] ?? []),
                borderColor: '#168a84',
                backgroundColor: 'rgba(22, 138, 132, .08)',
                borderWidth: 2,
                pointRadius: 2,
                pointHoverRadius: 4,
                tension: .15,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(148, 163, 184, .28)' }, ticks: { maxRotation: 48, minRotation: 48, font: { size: 9 } } },
                y: { grid: { color: 'rgba(148, 163, 184, .28)' }, ticks: { font: { size: 9 } } }
            }
        }
    });

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[char]));
    const formatNumber = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0));

    function renderCommissionFeed(feed) {
        const container = document.getElementById('commission-feed');
        if (!container) return;

        const rows = Array.isArray(feed) && feed.length
            ? feed.map((item) => `
                <div class="feed-item">
                    <div class="small fw-semibold">${escapeHtml(item.order_code || ('#' + item.order_id))} - ${escapeHtml(item.customer_name || 'Khách hàng')}</div>
                    <div class="dashboard-empty">Giá trị đơn: ${formatNumber(item.order_total)}đ · Hoa hồng: <span class="text-success fw-semibold">${formatNumber(item.commission_amount)}đ</span></div>
                </div>`).join('')
            : '<div class="dashboard-empty">Chưa có bản ghi hoa hồng.</div>';

        container.innerHTML = '<h2 class="dashboard-section-title">Chúc mừng nhận hoa hồng</h2><div class="commission-feed-list">' + rows + '</div>';
    }

    function refreshDashboard() {
        fetch("{{ route('pages.my_dashboard.stats') }}", {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then((response) => response.ok ? response.json() : Promise.reject())
        .then((data) => {
            if (data.salesChart) {
                salesChart.data.labels = data.salesChart.labels || [];
                salesChart.data.datasets[0].data = data.salesChart.values || [];
                salesChart.update();
            }
            renderCommissionFeed(data.commissionFeed || []);
        })
        .catch(() => {});
    }

    window.setInterval(refreshDashboard, 30000);
});
</script>
@endpush
