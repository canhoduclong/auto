@php
    $manager = $managerDashboard ?? [];
    $summary = $manager['summary'] ?? [];
    $changes = $manager['changes'] ?? [];
    $sizes = collect($manager['sizes'] ?? []);
    $trend = function (string $key, bool $lowerIsBetter = false) use ($changes) {
        $value = $changes[$key] ?? null;
        if ($value === null) return ['text' => 'Kỳ trước chưa có dữ liệu', 'class' => 'is-neutral', 'icon' => '—'];
        $positive = $lowerIsBetter ? $value <= 0 : $value >= 0;
        return [
            'text' => number_format(abs($value), 1, ',', '.') . '% so với kỳ trước',
            'class' => $positive ? 'is-good' : 'is-bad',
            'icon' => $value > 0 ? '▲' : ($value < 0 ? '▼' : '•'),
        ];
    };
    $segments = [];
    $cursor = 0;
    foreach ($sizes as $size) {
        $end = min(100, $cursor + (float) ($size['percentage'] ?? 0));
        if ($end > $cursor) $segments[] = ($size['color'] ?? '#94a3b8') . " {$cursor}% {$end}%";
        $cursor = $end;
    }
    if ($cursor < 100) $segments[] = "#e8eef3 {$cursor}% 100%";
    $donutGradient = implode(', ', $segments) ?: '#e8eef3 0 100%';
@endphp

<section class="manager-board" aria-labelledby="manager-board-title">
    <header class="manager-board-head">
        <div>
            <h1 id="manager-board-title">Bảng điều hành phòng kinh doanh</h1>
            <p>Doanh thu và sản lượng đối soát theo Nhật ký bán hàng đã giao/hoàn tất</p>
        </div>
        <form method="GET" action="{{ route('pages.my_dashboard') }}" class="manager-date-filter">
            <label><span>Từ ngày</span><input type="date" name="from" value="{{ $manager['from'] ?? '' }}"></label>
            <label><span>Đến ngày</span><input type="date" name="to" value="{{ $manager['to'] ?? '' }}"></label>
            <button type="submit" aria-label="Áp dụng khoảng ngày"><i class="bi bi-arrow-clockwise"></i></button>
        </form>
    </header>

    <div class="manager-summary-grid">
        @php $customerTrend = $trend('customers'); @endphp
        <article class="manager-summary-card tone-green">
            <div class="manager-summary-label"><i class="bi bi-people-fill"></i><span>Khách hàng</span></div>
            <strong>{{ number_format($summary['customers'] ?? 0, 0, ',', '.') }}</strong>
            <small class="{{ $customerTrend['class'] }}">{{ $customerTrend['icon'] }} {{ $customerTrend['text'] }}</small>
        </article>
        @php $quantityTrend = $trend('quantity'); @endphp
        <article class="manager-summary-card tone-blue">
            <div class="manager-summary-label"><i class="bi bi-box-seam-fill"></i><span>Sản lượng bán</span></div>
            <strong>{{ number_format($summary['quantity'] ?? 0, 0, ',', '.') }} <em>con</em></strong>
            <small class="{{ $quantityTrend['class'] }}">{{ $quantityTrend['icon'] }} {{ $quantityTrend['text'] }}</small>
        </article>
        @php $revenueTrend = $trend('revenue'); @endphp
        <article class="manager-summary-card tone-purple">
            <div class="manager-summary-label"><i class="bi bi-cash-stack"></i><span>Doanh thu bán hàng</span></div>
            <strong>{{ number_format($summary['revenue'] ?? 0, 0, ',', '.') }}đ</strong>
            <small class="{{ $revenueTrend['class'] }}">{{ $revenueTrend['icon'] }} {{ $revenueTrend['text'] }}</small>
        </article>
        @php $defectTrend = $trend('defect_rate', true); @endphp
        <article class="manager-summary-card tone-orange">
            <div class="manager-summary-label"><i class="bi bi-exclamation-triangle-fill"></i><span>Lỗi hàng</span></div>
            <strong>{{ number_format($summary['defect_rate'] ?? 0, 2, ',', '.') }}%</strong>
            <small class="{{ $defectTrend['class'] }}">{{ $defectTrend['icon'] }} {{ $defectTrend['text'] }}</small>
        </article>
        @php $debtTrend = $trend('receivables', true); @endphp
        <article class="manager-summary-card tone-teal">
            <div class="manager-summary-label"><i class="bi bi-wallet2"></i><span>Công nợ phải thu</span></div>
            <strong>{{ number_format($summary['receivables'] ?? 0, 0, ',', '.') }}đ</strong>
            <small class="{{ $debtTrend['class'] }}">{{ $debtTrend['icon'] }} {{ $debtTrend['text'] }}</small>
        </article>
        @php $shipTrend = $trend('shipping_cost', true); @endphp
        <article class="manager-summary-card tone-red">
            <div class="manager-summary-label"><i class="bi bi-truck"></i><span>Chi phí ship</span></div>
            <strong>{{ number_format($summary['shipping_cost'] ?? 0, 0, ',', '.') }}đ</strong>
            <small class="{{ $shipTrend['class'] }}">{{ $shipTrend['icon'] }} {{ $shipTrend['text'] }}</small>
        </article>
    </div>

    <div class="manager-detail-grid">
        <article class="manager-panel panel-blue">
            <h2>Sản lượng bán theo size</h2>
            <div class="manager-size-body">
                <div class="manager-donut" style="--manager-donut: conic-gradient({{ $donutGradient }})">
                    <span><strong>{{ number_format($summary['quantity'] ?? 0, 0, ',', '.') }}</strong>con</span>
                </div>
                <div class="manager-size-legend">
                    @foreach($sizes as $size)
                        <div><i style="background:{{ $size['color'] }}"></i><span><b>{{ $size['label'] }}</b> ({{ $size['range'] }})<small>{{ number_format($size['quantity'], 0, ',', '.') }} con ({{ number_format($size['percentage'], 1, ',', '.') }}%)</small></span></div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="manager-panel panel-orange">
            <h2>Lỗi hàng</h2>
            <dl class="manager-metric-list">
                <div><dt>Tỷ lệ lỗi hàng</dt><dd>{{ number_format($summary['defect_rate'] ?? 0, 2, ',', '.') }}%</dd></div>
                <div><dt>Số lượng hàng lỗi</dt><dd>{{ number_format($summary['returned_quantity'] ?? 0, 0, ',', '.') }} con</dd></div>
                <div><dt>Giá trị hàng lỗi</dt><dd>{{ number_format($summary['returned_value'] ?? 0, 0, ',', '.') }}đ</dd></div>
            </dl>
            <div class="manager-reasons"><b>Nguyên nhân ghi nhận</b>
                @forelse(($manager['return_reasons'] ?? []) as $reason => $count)
                    <span>{{ $reason }}: {{ $count }}</span>
                @empty
                    <span>Chưa có phiếu lỗi trong kỳ</span>
                @endforelse
            </div>
        </article>

        <article class="manager-panel panel-teal">
            <h2>Công nợ phải thu</h2>
            <dl class="manager-metric-list">
                <div><dt>Tiền hàng</dt><dd>{{ number_format($summary['goods_revenue'] ?? 0, 0, ',', '.') }}đ</dd></div>
                <div><dt>Phí/VAT/chiết khấu</dt><dd>{{ number_format($summary['fee_revenue'] ?? 0, 0, ',', '.') }}đ</dd></div>
                <div><dt>Tổng doanh thu Nhật ký</dt><dd>{{ number_format($summary['revenue'] ?? 0, 0, ',', '.') }}đ</dd></div>
                <div><dt>Đã thu</dt><dd>{{ number_format($summary['collected'] ?? 0, 0, ',', '.') }}đ</dd></div>
                <div><dt>Công nợ phải thu</dt><dd>{{ number_format($summary['receivables'] ?? 0, 0, ',', '.') }}đ</dd></div>
                <div><dt>Công nợ quá hạn trên 7 ngày</dt><dd>{{ number_format($summary['overdue_receivables'] ?? 0, 0, ',', '.') }}đ</dd></div>
                <div><dt>Tỷ lệ công nợ quá hạn</dt><dd>{{ number_format($summary['overdue_rate'] ?? 0, 2, ',', '.') }}%</dd></div>
            </dl>
        </article>

        <article class="manager-panel panel-red">
            <h2>Chi phí ship</h2>
            <dl class="manager-metric-list">
                <div><dt>Tổng chi phí ship</dt><dd>{{ number_format($summary['shipping_cost'] ?? 0, 0, ',', '.') }}đ</dd></div>
                <div><dt>Chi phí ship / sản lượng</dt><dd>{{ number_format($summary['shipping_per_unit'] ?? 0, 0, ',', '.') }}đ/con</dd></div>
                <div><dt>Số đơn phát sinh phí ship</dt><dd>{{ number_format($summary['shipping_orders'] ?? 0, 0, ',', '.') }} đơn</dd></div>
            </dl>
        </article>
    </div>

    <div class="manager-detail-grid">
        <article class="manager-panel manager-performance panel-blue">
            <h2>Danh sách mặt hàng bán chạy</h2>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>#</th><th>Mặt hàng</th><th>Đơn</th><th>SL</th><th>Tổng KL</th><th>Doanh thu</th></tr></thead>
                    <tbody>
                    @forelse(($manager['products'] ?? []) as $index => $product)
                        <tr>
                            <td>{{ $index + 1 }}</td><td>{{ $product['name'] }}</td>
                            <td>{{ number_format($product['orders'], 0, ',', '.') }}</td>
                            <td>{{ number_format($product['quantity'], 0, ',', '.') }}</td>
                            <td>{{ number_format($product['weight'], 1, ',', '.') }}</td>
                            <td>{{ number_format($product['revenue'], 0, ',', '.') }}đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="manager-table-empty">Chưa có mặt hàng bán trong khoảng ngày đã chọn.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="manager-panel manager-performance panel-green">
            <h2>Top khách hàng</h2>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>#</th><th>Khách hàng</th><th>Đơn</th><th>SL</th><th>Doanh thu</th><th>Công nợ</th></tr></thead>
                    <tbody>
                    @forelse(($manager['customers'] ?? []) as $index => $customer)
                        <tr>
                            <td>{{ $index + 1 }}</td><td>{{ $customer['name'] }}</td>
                            <td>{{ number_format($customer['orders'], 0, ',', '.') }}</td>
                            <td>{{ number_format($customer['quantity'], 0, ',', '.') }}</td>
                            <td>{{ number_format($customer['revenue'], 0, ',', '.') }}đ</td>
                            <td>{{ number_format($customer['debt'], 0, ',', '.') }}đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="manager-table-empty">Chưa có khách hàng trong khoảng ngày đã chọn.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <article class="manager-panel manager-performance panel-navy">
        <h2>Xếp hạng sale bán nhiều</h2>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Hạng</th><th>Sale</th><th>Đơn</th><th>Sản lượng</th><th>Doanh thu</th><th>KH mới</th><th>Lỗi hàng</th><th>Công nợ</th><th>Phí ship</th></tr></thead>
                <tbody>
                @forelse(($manager['employees'] ?? []) as $employee)
                    <tr>
                        <td>{{ $employee['rank'] }}</td><td>{{ $employee['name'] }}</td><td>{{ number_format($employee['orders'], 0, ',', '.') }}</td>
                        <td>{{ number_format($employee['quantity'], 0, ',', '.') }}</td>
                        <td>{{ number_format($employee['revenue'], 0, ',', '.') }}đ</td><td>{{ $employee['new_customers'] }}</td>
                        <td>{{ number_format($employee['defect_rate'], 1, ',', '.') }}%</td><td>{{ number_format($employee['debt'], 0, ',', '.') }}đ</td>
                        <td>{{ number_format($employee['shipping_cost'], 0, ',', '.') }}đ</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="manager-table-empty">Chưa có dữ liệu sale trong khoảng ngày đã chọn.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <article class="manager-panel manager-kpi panel-purple">
        <h2>KPI tổng hợp</h2>
        <div class="manager-kpi-grid">
            <div><i class="bi bi-bullseye"></i><span>Tỷ lệ hoàn tất đơn</span><strong>{{ number_format(data_get($manager, 'kpis.completion_rate', 0), 1, ',', '.') }}%</strong></div>
            <div><i class="bi bi-bar-chart-line-fill"></i><span>Tỷ lệ giao hàng đúng hẹn</span><strong>{{ number_format(data_get($manager, 'kpis.on_time_rate', 0), 1, ',', '.') }}%</strong></div>
            <div><i class="bi bi-exclamation-triangle"></i><span>Tỷ lệ lỗi hàng</span><strong>{{ number_format($summary['defect_rate'] ?? 0, 2, ',', '.') }}%</strong></div>
            <div><i class="bi bi-currency-dollar"></i><span>Tỷ lệ công nợ quá hạn</span><strong>{{ number_format($summary['overdue_rate'] ?? 0, 2, ',', '.') }}%</strong></div>
            <div><i class="bi bi-truck"></i><span>Chi phí ship / sản lượng</span><strong>{{ number_format($summary['shipping_per_unit'] ?? 0, 0, ',', '.') }}đ</strong></div>
        </div>
    </article>
</section>
