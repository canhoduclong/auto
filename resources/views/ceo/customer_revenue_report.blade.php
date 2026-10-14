@extends('layouts.ceo')

@section('title', 'Báo cáo doanh thu: ' . $customer->name)

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Báo cáo doanh thu khách hàng</h2>
        <div class="text-muted">Khách hàng: <strong>{{ $customer->name }}</strong> | SĐT: {{ $customer->phone }} | Email: {{ $customer->email }}</div>
    </div>
    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">Từ ngày tạo đơn</label>
                    <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control">
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $toDate }}" class="form-control">
                </div>
                <div class="col-sm-2">
                    <label class="form-label fw-semibold">Dòng / trang</label>
                    <select name="per_page" class="form-select">
                        @foreach([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1">Lọc</button>
                    @if($fromDate || $toDate || $perPage !== 20)
                        <a href="{{ route('ceo.customer-revenue-report', $customer) }}" class="btn btn-outline-secondary">Đặt lại</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <p class="small text-muted mb-3">
        Tổng giá trị các đơn đã giao / hoàn thành sau phí và chiết khấu, bao gồm VAT nếu đã tính.
        Số liệu được tính trên toàn bộ khoảng lọc và không phải số tiền đã thu.
    </p>
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
                            <td>{{ $orders->firstItem() + $loop->index }}</td>
                            <td>{{ $order->code ?? $order->id }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="badge bg-success">{{ \App\Models\Order::statusOptions()[$order->status] ?? $order->status }}</span></td>
                            <td class="text-nowrap fw-semibold">{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Chưa có đơn hàng hoàn thành</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small text-muted">{{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} / {{ $orders->total() }} đơn</span>
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
