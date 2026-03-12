@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">Báo cáo khách hàng</h4>
            <div class="text-muted">
                {{ $customer->name }}
                @if($customer->phone)
                    - {{ $customer->phone }}
                @endif
            </div>
        </div>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Quay lại danh sách</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('customers.report', $customer) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="from_date" class="form-label">Từ ngày</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="per_page" class="form-label">Số dòng / trang</label>
                    <select name="per_page" id="per_page" class="form-select">
                        @foreach([10,15,25,50,100] as $pp)
                            <option value="{{ $pp }}" {{ (int) request('per_page', 15) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Lọc báo cáo</button>
                    <a href="{{ route('customers.report', $customer) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Tổng giá trị đơn hàng</div>
                    <h5 class="mb-0">{{ number_format($totalInvoiceAmount, 0, ',', '.') }} đ</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Tổng đã thanh toán</div>
                    <h5 class="mb-0 text-success">{{ number_format($totalPaidAmount, 0, ',', '.') }} đ</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Tổng công nợ</div>
                    <h5 class="mb-0 text-danger">{{ number_format($totalOutstandingAmount, 0, ',', '.') }} đ</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Mã đơn</th>
                    <th>Ngày tạo</th>
                    <th>Nhân viên</th>
                    <th>Tổng tiền</th>
                    <th>Đã thanh toán</th>
                    <th>Công nợ</th>
                    <th>Trạng thái đơn</th>
                    <th>Trạng thái thanh toán</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $paid = (float) $order->transactions->where('type', 'payment')->sum('amount')
                              - (float) $order->transactions->where('type', 'refund')->sum('amount');
                        $outstanding = max((float) $order->total - $paid, 0);
                    @endphp
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->code }}</td>
                        <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ optional($order->user)->name ?? '-' }}</td>
                        <td>{{ number_format($order->total, 0, ',', '.') }} đ</td>
                        <td class="text-success">{{ number_format($paid, 0, ',', '.') }} đ</td>
                        <td class="text-danger">{{ number_format($outstanding, 0, ',', '.') }} đ</td>
                        <td>{{ $order->status }}</td>
                        <td>
                            @if($order->isPaid())
                                <span class="badge bg-success">Đã thanh toán đủ</span>
                            @elseif($order->isPartialPaid())
                                <span class="badge bg-warning text-dark">Thanh toán một phần</span>
                            @else
                                <span class="badge bg-danger">Chưa thanh toán</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">Xem đơn</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">Khách hàng này chưa có đơn hàng.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $orders->links() }}
    </div>
</div>
@endsection
