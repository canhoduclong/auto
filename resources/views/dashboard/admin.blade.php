@extends('layouts.app')

@section('content')
    @php
        $data = $adminData ?? [];
        $dailyStats = $data['dailyStats'] ?? collect();
        $latestOrders = $data['latestOrders'] ?? collect();
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
                <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm">{{ __('dashboard.admin.quick_products') }}</a>
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
                        <h6 class="mb-0">{{ __('dashboard.admin.trend_7_days') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('dashboard.admin.date') }}</th>
                                        <th>{{ __('dashboard.admin.order_count') }}</th>
                                        <th>{{ __('dashboard.admin.order_value') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dailyStats as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td>{{ number_format($row['orders']) }}</td>
                                            <td>{{ number_format($row['amount'], 0, ',', '.') }} đ</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">{{ __('dashboard.admin.no_data') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ __('dashboard.admin.latest_orders') }}</h6>
                        <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm">{{ __('common.actions.view_all') }}</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('dashboard.admin.order_code') }}</th>
                                        <th>{{ __('dashboard.admin.customer') }}</th>
                                        <th>{{ __('dashboard.admin.staff') }}</th>
                                        <th>{{ __('dashboard.admin.total') }}</th>
                                        <th>{{ __('dashboard.admin.status') }}</th>
                                        <th>{{ __('dashboard.admin.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($latestOrders as $order)
                                        <tr>
                                            <td>
                                                <a href="{{ route('orders.show', $order) }}">{{ $order->code ?: ('#' . $order->id) }}</a>
                                            </td>
                                            <td>{{ $order->customer->name ?? __('dashboard.admin.na') }}</td>
                                            <td>{{ $order->user->name ?? __('dashboard.admin.na') }}</td>
                                            <td>{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                                            <td><span class="badge bg-secondary">{{ $order->status }}</span></td>
                                            <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">{{ __('dashboard.admin.no_orders') }}</td>
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

@endsection