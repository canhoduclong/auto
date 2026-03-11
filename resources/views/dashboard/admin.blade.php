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
                <h4 class="page-title mb-1">Dashboard Admin</h4>
                <div class="text-muted">Xin chao {{ $user->name }}, du lieu duoc cap nhat theo thoi gian thuc.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('orders.index') }}" class="btn btn-primary btn-sm">Don hang</a>
                <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm">San pham</a>
                <a href="{{ route('approval-workflows.index') }}" class="btn btn-outline-dark btn-sm">Quy trinh duyet</a>
            </div>
        </div>
    </div>

    <div class="content pt-0">
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">Tong don hang</div>
                        <h3 class="mb-0">{{ number_format($data['totalOrders'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">Don hom nay</div>
                        <h3 class="mb-0">{{ number_format($data['todayOrders'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">Don cho xu ly</div>
                        <h3 class="mb-0 text-warning">{{ number_format($data['pendingOrders'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">Muc duyet dang cho</div>
                        <h3 class="mb-0 text-danger">{{ number_format($data['pendingApprovals'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">Doanh thu thang</div>
                        <h4 class="mb-0 text-success">{{ number_format($data['netRevenueThisMonth'] ?? 0, 0, ',', '.') }} đ</h4>
                        <small class="text-muted">Thu: {{ number_format($data['grossPaymentsThisMonth'] ?? 0, 0, ',', '.') }} đ</small><br>
                        <small class="text-muted">Hoan: {{ number_format($data['refundsThisMonth'] ?? 0, 0, ',', '.') }} đ</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">San pham dang ban</div>
                        <h3 class="mb-0">{{ number_format($data['activeProducts'] ?? 0) }}</h3>
                        <small class="text-muted">Tam an: {{ number_format($data['inactiveProducts'] ?? 0) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">Bien the het hang</div>
                        <h3 class="mb-0 text-danger">{{ number_format($data['outOfStockVariants'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="text-muted mb-1">Khach hang</div>
                        <h3 class="mb-0">{{ number_format($data['totalCustomers'] ?? 0) }}</h3>
                        <small class="text-muted">Moi 30 ngay: {{ number_format($data['newCustomers30d'] ?? 0) }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Xu huong 7 ngay gan nhat</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Ngay</th>
                                        <th>So don</th>
                                        <th>Gia tri don</th>
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
                                            <td colspan="3" class="text-center text-muted">Chua co du lieu</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Don hang moi nhat</h6>
                        <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm">Xem tat ca</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Ma don</th>
                                        <th>Khach hang</th>
                                        <th>Nhan vien</th>
                                        <th>Tong</th>
                                        <th>Trang thai</th>
                                        <th>Ngay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($latestOrders as $order)
                                        <tr>
                                            <td>
                                                <a href="{{ route('orders.show', $order) }}">{{ $order->code ?: ('#' . $order->id) }}</a>
                                            </td>
                                            <td>{{ $order->customer->name ?? 'N/A' }}</td>
                                            <td>{{ $order->user->name ?? 'N/A' }}</td>
                                            <td>{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                                            <td><span class="badge bg-secondary">{{ $order->status }}</span></td>
                                            <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Chua co don hang</td>
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
                        <h6 class="mb-0">Top san pham ban chay</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>San pham</th>
                                        <th>SL</th>
                                        <th>Doanh so</th>
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
                                            <td colspan="3" class="text-center text-muted">Chua co du lieu ban hang</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Trang thai don hang</h6>
                        <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm">Quan ly don</a>
                    </div>
                    <div class="card-body">
                        @forelse($ordersByStatus as $status)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">{{ $status->status }}</span>
                                <span class="badge bg-primary">{{ number_format($status->total) }}</span>
                            </div>
                        @empty
                            <div class="text-muted">Chua co du lieu trang thai don hang.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection