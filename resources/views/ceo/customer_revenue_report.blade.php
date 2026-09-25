@extends('layouts.ceo')

@section('title', 'Báo cáo doanh thu: ' . $customer->name)

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Báo cáo doanh thu khách hàng</h2>
        <div class="text-muted">Khách hàng: <strong>{{ $customer->name }}</strong> | SĐT: {{ $customer->phone }} | Email: {{ $customer->email }}</div>
    </div>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="fw-semibold text-muted mb-1">Tổng doanh thu</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($totalRevenue, 0, ',', '.') }} đ</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="fw-semibold text-muted mb-1">Số đơn hoàn thành</div>
                    <div class="fs-3 fw-bold">{{ $orderCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="fw-semibold text-muted mb-1">Đơn đầu tiên</div>
                    <div class="fs-6">{{ $firstOrder ? $firstOrder->created_at->format('d/m/Y H:i') : '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="fw-semibold text-muted mb-1">Đơn gần nhất</div>
                    <div class="fs-6">{{ $lastOrder ? $lastOrder->created_at->format('d/m/Y H:i') : '—' }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Danh sách đơn hàng đã hoàn thành</div>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã đơn</th>
                        <th>Ngày tạo</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $order->code ?? $order->id }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="badge bg-success">{{ __($order->status) }}</span></td>
                            <td>{{ number_format($order->total_amount, 0, ',', '.') }} đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Chưa có đơn hàng hoàn thành</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
