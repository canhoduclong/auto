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
