@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Quản lý đơn hàng</h4>
    <a href="{{ route('orders.create') }}" class="btn btn-success mb-3">+ Thêm đơn hàng</a>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Bộ lọc</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('orders.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Tên khách hàng</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ request('customer_name') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Số điện thoại</label>
                            <input type="text" name="phone_number" id="phone_number" class="form-control" value="{{ request('phone_number') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Người cập nhật</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">Tất cả</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Trạng thái thanh toán</label>
                            <select name="payment_status" id="payment_status" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
                                <option value="partially_paid" {{ request('payment_status') == 'partially_paid' ? 'selected' : '' }}>Thanh toán một phần</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="status" class="form-label">Trạng thái đơn hàng</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Tất cả</option>
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="from_date" class="form-label">Từ ngày</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="to_date" class="form-label">Đến ngày</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Lọc</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Xóa bộ lọc</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Thống kê</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p><strong>Tổng tiền hóa đơn:</strong> {{ number_format($totalInvoiceAmount, 0, ',', '.') }} đ</p>
                </div>
                <div class="col-md-3">
                    <p><strong>Tổng đã thanh toán:</strong> {{ number_format($totalPaidAmount, 0, ',', '.') }} đ</p>
                </div>
                <div class="col-md-3">
                    <p><strong>Tổng còn nợ:</strong> {{ number_format($totalOutstandingAmount, 0, ',', '.') }} đ</p>
                </div>
                <div class="col-md-3">
                    <p><strong>Đơn đã thanh toán:</strong> {{ $fullyPaidOrders }}</p>
                    <p><strong>Đơn chưa thanh toán:</strong> {{ $unpaidOrders }}</p>
                    <p><strong>Đơn thanh toán một phần:</strong> {{ $partiallyPaidOrders }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Nhân viên</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Trạng thái thanh toán</th>
                    <th>Đã thanh toán</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                    <th>QR Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->code }}</td>
                    <td>{{ $order->customer->name ?? '' }}</td>
                    <td>{{ $order->user->name ?? '' }}</td>
                    <td>{{ number_format($order->total, 0, ',', '.') }} đ</td>
                    <td>
                        <button class="btn btn-sm btn-toggle-status" data-id="{{ $order->id }}" data-status="{{ $order->status }}">
                            {{ $order->status }}
                        </button>
                    </td>
                    <td>
                        @if($order->status === \App\Models\Order::STATUS_COMPLETED)
                            <span class="badge bg-success">Đã hoàn thành</span>
                        @elseif($order->isPaid())
                            <span class="badge bg-success">Đã thanh toán đủ</span>
                        @elseif($order->isPartialPaid())
                            <span class="badge bg-warning text-dark">Thanh toán một phần</span>
                        @else
                            <span class="badge bg-danger">Chưa thanh toán</span>
                        @endif
                    </td>
                    <td>{{ $order->created_at }}</td>
                    <td>
                        @php
                            $paid = $order->transactions->where('type', 'payment')->sum('amount') - $order->transactions->where('type', 'refund')->sum('amount');
                        @endphp
                        @if(!$order->isPaid())
                            <span class="text-primary fw-bold">{{ number_format($paid, 0, ',', '.') }} đ</span>
                            <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success btn-sm ms-2">Thanh toán</a>
                        @else
                            <span class="text-success">Đã thanh toán đủ</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">xem</a>
                        @if(!$order->isPaid() && $order->status !== \App\Models\Order::STATUS_COMPLETED)
                            <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-info btn-sm">Sửa</a>
                        @endif
                        <form action="{{ route('orders.destroy', $order->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa đơn hàng này?')">Xóa</button>
                        </form>
                        @if($order->status !== \App\Models\Order::STATUS_COMPLETED && !$order->isPaid())
                            <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success btn-sm">Thanh toán</a>
                        @endif
                    </td>
                    <td>
                        @if($order->qr_code)
                            <img src="data:image/svg+xml;base64,{{ $order->qr_code }}" alt="QR Code">
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $orders->appends(request()->query())->links() }}
    </div>
</div>
@endsection
