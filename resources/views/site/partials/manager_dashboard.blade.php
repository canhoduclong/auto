@php
    $manager = $managerDashboard ?? [];
    $previousDashboardDays = collect([1, 2])->map(fn (int $daysAgo) => now()->subDays($daysAgo));
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
            <button type="submit" aria-label="Lọc dữ liệu"><i class="bi bi-funnel-fill me-1"></i>Lọc</button>
            <button type="submit" name="period" value="today" class="manager-today-button"><i class="bi bi-arrow-clockwise me-1"></i>Hôm nay</button>
            <div class="manager-quick-days" aria-label="Lọc nhanh hai ngày trước">
                @foreach($previousDashboardDays as $previousDay)
                    <a href="{{ route('pages.my_dashboard', ['from' => $previousDay->toDateString(), 'to' => $previousDay->toDateString()]) }}"
                       class="manager-quick-day {{ ($manager['from'] ?? '') === $previousDay->toDateString() && ($manager['to'] ?? '') === $previousDay->toDateString() ? 'is-active' : '' }}">
                        {{ $previousDay->format('d/m') }}
                    </a>
                @endforeach
            </div>
        </form>
    </header>

    <div class="manager-detail-grid">
        <article class="manager-panel manager-product-summary panel-blue">
            <h2>Sản phẩm · Số lượng · Giá TB</h2>
            @forelse(($manager['products'] ?? []) as $index => $product)
                <section class="manager-product-block">
                    <div class="manager-product-heading">
                        <strong>{{ $index + 1 }}. {{ $product['name'] }}</strong>
                        <span>Doanh thu: {{ number_format($product['revenue'], 0, ',', '.') }} đ</span>
                    </div>
                    <div class="table-responsive">
                        <table class="manager-product-table">
                            <thead><tr><th>Size</th><th>Giá TB</th><th>Số lượng</th></tr></thead>
                            <tbody>
                            @forelse(($product['sizes'] ?? []) as $size)
                                <tr>
                                    <td>{{ \Illuminate\Support\Str::after($size['label'], 'Size ') }}</td>
                                    <td>{{ number_format($size['average_price'], 0, ',', '.') }} đ</td>
                                    <td>{{ number_format($size['quantity'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td>—</td><td>{{ number_format($product['average_price'] ?? 0, 0, ',', '.') }} đ</td><td>{{ number_format($product['quantity'], 0, ',', '.') }}</td></tr>
                            @endforelse
                            </tbody>
                            <tfoot><tr><td></td><td>{{ number_format($product['average_price'] ?? 0, 0, ',', '.') }} đ</td><td>{{ number_format($product['quantity'], 0, ',', '.') }}</td></tr></tfoot>
                        </table>
                    </div>
                </section>
            @empty
                <div class="manager-table-empty">Chưa có mặt hàng bán trong khoảng ngày đã chọn.</div>
            @endforelse
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
                <thead><tr><th>Hạng</th><th>Sale</th><th>Đơn</th><th>Sản lượng</th><th>Doanh thu</th><th>KH mới</th></tr></thead>
                <tbody>
                @forelse(($manager['employees'] ?? []) as $employee)
                    <tr>
                        <td>{{ $employee['rank'] }}</td><td>{{ $employee['name'] }}</td><td>{{ number_format($employee['orders'], 0, ',', '.') }}</td>
                        <td>{{ number_format($employee['quantity'], 0, ',', '.') }}</td>
                        <td>{{ number_format($employee['revenue'], 0, ',', '.') }}đ</td><td>{{ $employee['new_customers'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="manager-table-empty">Chưa có dữ liệu sale trong khoảng ngày đã chọn.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </article>

</section>
