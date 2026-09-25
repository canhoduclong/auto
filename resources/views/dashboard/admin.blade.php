@extends('layouts.app')

@push('styles')
<style>
    .dash-product-stats {
        padding: .75rem;
        background: #f8fafc;
        border-bottom: 1px solid #e9ecef;
    }
    .dash-product-vertical {
        display: grid;
        gap: .35rem;
        margin-top: .45rem;
    }
    .dash-product-vertical-row {
        display: grid;
        grid-template-columns: 44px 1.6fr repeat(4, minmax(80px, auto));
        gap: .35rem;
        border: 1px solid #e5edf7;
        border-radius: 8px;
        padding: .36rem .5rem;
        background: #fff;
        font-size: .8rem;
        align-items: center;
    }
    .dash-product-vertical-head {
        background: #eef2f7;
        color: #475569;
        font-size: .73rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 700;
    }
    @media (max-width: 767.98px) {
        .dash-product-vertical-row {
            grid-template-columns: 1fr 1fr;
        }
        .dash-product-vertical-head { display: none; }
    }
</style>
@endpush

@section('content')
    @php
        $data = $adminData ?? [];
        $dailyStats = $data['dailyStats'] ?? collect();
        $latestOrders = $data['latestOrders'] ?? collect();
        $latestOrdersProductStats = $data['latestOrdersProductStats'] ?? collect();
        $dailyProductPrices = $data['dailyProductPrices'] ?? collect();
        $topProducts = $data['topProducts'] ?? collect();
        $ordersByStatus = $data['ordersByStatus'] ?? collect();
    @endphp

    <div class="page-header">
        <div class="page-header-content d-lg-flex align-items-center justify-content-between">
            <div>
                <h4 class="page-title mb-1">{{ __('dashboard.admin.title') }}</h4>
                <div class="text-muted">{{ __('dashboard.admin.welcome', ['name' => $user->name]) }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('orders.index') }}" class="btn btn-primary btn-sm">{{ __('dashboard.admin.quick_orders') }}</a>
                <a href="{{ route('orders.monitoring') }}" class="btn btn-outline-secondary btn-sm">{{ __('dashboard.admin.quick_order_monitoring') }}</a>
                <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm">{{ __('dashboard.admin.quick_products') }}</a>
                <a href="{{ route('reports.revenue') }}" class="btn btn-outline-info btn-sm">{{ __('dashboard.admin.quick_revenue') }}</a>
                <a href="{{ route('products.price-management.index') }}" class="btn btn-outline-success btn-sm">Quản lý giá</a>
                <a href="{{ route('approval-workflows.index') }}" class="btn btn-outline-dark btn-sm">{{ __('dashboard.admin.quick_approval_workflows') }}</a>
            </div>
        </div>
    </div>

    <div class="content pt-0">
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">{{ __('dashboard.admin.total_orders') }}</div>
                        <h3 class="mb-0">{{ number_format($data['totalOrders'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">{{ __('dashboard.admin.today_orders') }}</div>
                        <h3 class="mb-0">{{ number_format($data['todayOrders'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">{{ __('dashboard.admin.pending_orders') }}</div>
                        <h3 class="mb-0 text-warning">{{ number_format($data['pendingOrders'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">{{ __('dashboard.admin.pending_approvals') }}</div>
                        <h3 class="mb-0 text-danger">{{ number_format($data['pendingApprovals'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">{{ __('dashboard.admin.monthly_revenue') }}</div>
                        <h4 class="mb-0 text-success">{{ number_format($data['netRevenueThisMonth'] ?? 0, 0, ',', '.') }} đ</h4>
                        <small class="text-muted">{{ __('dashboard.admin.monthly_income') }}: {{ number_format($data['grossPaymentsThisMonth'] ?? 0, 0, ',', '.') }} đ</small><br>
                        <small class="text-muted">{{ __('dashboard.admin.monthly_refund') }}: {{ number_format($data['refundsThisMonth'] ?? 0, 0, ',', '.') }} đ</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">{{ __('dashboard.admin.active_products') }}</div>
                        <h3 class="mb-0">{{ number_format($data['activeProducts'] ?? 0) }}</h3>
                        <small class="text-muted">{{ __('dashboard.admin.inactive_products') }}: {{ number_format($data['inactiveProducts'] ?? 0) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">{{ __('dashboard.admin.out_of_stock_variants') }}</div>
                        <h3 class="mb-0 text-danger">{{ number_format($data['outOfStockVariants'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">{{ __('dashboard.admin.customers') }}</div>
                        <h3 class="mb-0">{{ number_format($data['totalCustomers'] ?? 0) }}</h3>
                        <small class="text-muted">{{ __('dashboard.admin.new_customers_30d') }}: {{ number_format($data['newCustomers30d'] ?? 0) }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8"> 

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ __('dashboard.admin.latest_orders') }}</h6>
                        <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm">{{ __('common.actions.view_all') }}</a>
                    </div>

                    {{-- Thống kê hàng hóa --}}
                    @if($latestOrdersProductStats->isNotEmpty())
                    <div class="dash-product-stats">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="text-muted small fw-semibold text-uppercase mb-0" style="letter-spacing:.06em;">
                                Hàng - Số lượng
                                <span class="text-secondary fw-normal">({{ $latestOrders->count() }} đơn gần nhất)</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="dashToggleProductStats">
                                <i class="bi bi-chevron-expand"></i> Chi tiết
                            </button>
                        </div>
                        <div class="d-none" id="dashProductStatsWrap">
                            <div class="dash-product-vertical" id="dashProductStatsList">
                                <div class="dash-product-vertical-row dash-product-vertical-head">
                                    <div>STT</div>
                                    <div>Sản phẩm</div>
                                    <div>Số lượng</div>
                                    <div>Tổng tiền</div>
                                    <div>ĐVT</div>
                                    <div></div>
                                </div>
                                @foreach($latestOrdersProductStats as $i => $ps)
                                <div class="dash-product-vertical-row">
                                    <div class="text-muted">{{ $i + 1 }}</div>
                                    <div class="fw-semibold">{{ $ps['product_name'] }}</div>
                                    <div class="text-primary fw-bold">{{ rtrim(rtrim(number_format($ps['total_qty'], 2, '.', ''), '0'), '.') }}</div>
                                    <div>{{ number_format($ps['total_amount'], 0, ',', '.') }}đ</div>
                                    <div class="text-muted">{{ $ps['unit_label'] }}</div>
                                    <div></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="min-width:640px;">
                                <thead>
                                    <tr>
                                        <th style="width:110px;">Thứ tự / Mã đơn</th>
                                        <th>Khách hàng / Nhân viên</th>
                                        <th>Nguồn lên đơn</th>
                                        <th>{{ __('dashboard.admin.total') }}</th>
                                        <th>{{ __('dashboard.admin.status') }}</th>
                                        <th>{{ __('dashboard.admin.date') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($latestOrders as $order)
                                        @php
                                            $seq = $order->daily_sequence;
                                            $orderCode = $order->code ?: ('#' . $order->id);
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($seq)
                                                    <div class="fw-black text-primary" style="font-size:1.45rem;line-height:1;">{{ $seq }}</div>
                                                @else
                                                    <div class="text-muted" style="font-size:1.1rem;line-height:1;">—</div>
                                                @endif
                                                <small class="text-muted d-block mt-1">
                                                    <a href="{{ route('orders.show', $order) }}" class="text-muted text-decoration-none">{{ $orderCode }}</a>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $order->customer->name ?? __('dashboard.admin.na') }}</div>
                                                <small class="text-muted">{{ $order->user->name ?? __('dashboard.admin.na') }}</small>
                                            </td>
                                            <td>
                                                <div class="small">{{ $order->dashboard_order_source_label ?? 'Sale tự lên đơn' }}</div>
                                                <small class="text-muted">{{ $order->dashboard_sale_confirmation_label ?? 'Không xác nhận của sale' }}</small>
                                            </td>
                                            <td class="fw-semibold">{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                                            <td><span class="badge bg-secondary">{{ $order->status }}</span></td>
                                            <td class="text-muted small">{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <button class="btn btn-outline-secondary btn-sm"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#qv_{{ $order->id }}"
                                                    aria-expanded="false">
                                                    <i class="bi bi-eye"></i> Xem nhanh
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="collapse" id="qv_{{ $order->id }}">
                                            <td colspan="7" class="p-0">
                                                <div class="p-3" style="background:#f8fafc;border-top:1px solid #e9ecef;">
                                                    @if($order->items->isNotEmpty())
                                                        <table class="table table-sm mb-0">
                                                            <thead>
                                                                <tr class="text-muted" style="font-size:.78rem;">
                                                                    <th>Sản phẩm</th>
                                                                    <th>SKU / Size</th>
                                                                    <th>SL</th>
                                                                    <th>Đơn giá</th>
                                                                    <th class="text-end">Tạm tính</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($order->items as $item)
                                                                <tr>
                                                                    <td class="fw-semibold small">{{ $item->product?->name ?? $item->variant?->product?->name ?? 'Sản phẩm' }}</td>
                                                                    <td class="text-muted small">{{ $item->variant?->sku ?? '—' }} / {{ $item->variant?->size ?? '—' }}</td>
                                                                    <td class="small">{{ rtrim(rtrim(number_format((float)$item->quantity, 2, '.', ''), '0'), '.') }}</td>
                                                                    <td class="small">{{ number_format((float)$item->price, 0, ',', '.') }}đ</td>
                                                                    <td class="text-end small fw-semibold">{{ number_format((float)$item->total, 0, ',', '.') }}đ</td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                                                            <span class="text-muted small">{{ $order->items->count() }} dòng — {{ number_format((float)$order->items->sum('quantity'), 0) }} sp</span>
                                                            <a href="{{ route('orders.show', $order) }}" class="btn btn-primary btn-sm">Xem chi tiết</a>
                                                        </div>
                                                    @else
                                                        <div class="text-muted small py-2">Không có dữ liệu sản phẩm.</div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">{{ __('dashboard.admin.no_orders') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Bảng giá sản phẩm theo ngày</h6>
                        <a href="{{ route('products.price-management.index') }}" class="btn btn-light btn-sm">Quản lý giá</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Ảnh</th>
                                        <th>Sản phẩm</th>
                                        <th>Giá bán</th>
                                        <th>Ghi chú</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dailyProductPrices as $row)
                                        <tr>
                                            <td>
                                                @if(!empty($row['product_avatar_path']))
                                                    <img
                                                        src="{{ asset('storage/' . $row['product_avatar_path']) }}"
                                                        alt="{{ $row['product_name'] }}"
                                                        width="44"
                                                        height="44"
                                                        class="rounded object-fit-cover"
                                                    >
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $row['product_name'] }}</td>
                                            <td>{{ number_format((float) $row['representative_price'], 0, ',', '.') }} đ</td>
                                            <td>
                                                @if($row['is_uniform_price'])
                                                    Áp dụng cho toàn bộ {{ number_format((int) $row['total_variants_count']) }} biến thể
                                                @else
                                                    Giá đại diện cho {{ number_format((int) $row['representative_variants_count']) }}/{{ number_format((int) $row['total_variants_count']) }} biến thể
                                                @endif
                                            </td>
                                        </tr>

                                        @if(!$row['is_uniform_price'])
                                            @foreach($row['different_variants'] as $variant)
                                                <tr class="table-light">
                                                    <td></td>
                                                    <td class="ps-4">- Biến thể {{ $variant['variant_sku'] }}</td>
                                                    <td>{{ number_format((float) $variant['price'], 0, ',', '.') }} đ</td>
                                                    <td>Biến thể khác giá</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Chưa có dữ liệu bảng giá theo ngày.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('dashboard.admin.top_products') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('dashboard.admin.product') }}</th>
                                        <th>{{ __('dashboard.admin.quantity') }}</th>
                                        <th>{{ __('dashboard.admin.revenue') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topProducts as $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? ('#' . $item->product_id) }}</td>
                                            <td>{{ number_format((int) $item->sold_qty) }}</td>
                                            <td>{{ number_format((float) $item->sold_amount, 0, ',', '.') }} đ</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">{{ __('dashboard.admin.no_sales_data') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ __('dashboard.admin.order_status') }}</h6>
                        <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm">{{ __('dashboard.admin.manage_orders') }}</a>
                    </div>
                    <div class="card-body">
                        @forelse($ordersByStatus as $status)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">{{ $status->status }}</span>
                                <span class="badge bg-primary">{{ number_format($status->total) }}</span>
                            </div>
                        @empty
                            <div class="text-muted">{{ __('dashboard.admin.no_status_data') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
(function () {
    var toggleBtn = document.getElementById('dashToggleProductStats');
    var statsWrap = document.getElementById('dashProductStatsWrap');
    var visible = false;

    if (toggleBtn && statsWrap) {
        toggleBtn.addEventListener('click', function () {
            visible = !visible;
            statsWrap.classList.toggle('d-none', !visible);
            toggleBtn.innerHTML = visible
                ? '<i class="bi bi-chevron-contract"></i> Ẩn chi tiết'
                : '<i class="bi bi-chevron-expand"></i> Chi tiết';
        });
    }
})();
</script>
@endpush

@endsection