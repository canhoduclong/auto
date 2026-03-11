@extends('layouts.app')
@section('content')
<div class="container">
    <h4>Chi tiết đơn hàng #{{ $order->code }}</h4>
    <a href="{{ route('orders.index') }}" class="btn btn-secondary mb-3">Quay lại danh sách</a>
    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Khách hàng:</strong> {{ $order->customer->name ?? '' }}</p>
            <p><strong>Nhân viên:</strong> {{ $order->user->name ?? '' }}</p>
            <p><strong>Tổng tiền:</strong> {{ number_format($order->total, 0, ',', '.') }} đ</p> 
            <p><strong>Ngày tạo:</strong> {{ $order->created_at }}</p>
            <p><strong>Trạng thái hiện tại:</strong> {{ $order->status }}</p>
            @if($currentPendingApproval && $currentPendingApproval->step)
                <p><strong>Đang chờ duyệt:</strong> Bước {{ $currentPendingApproval->step->step_order }} (Role: {{ $currentPendingApproval->step->role_slug }})</p>
            @endif
        </div>
        
        <div class="card-footer">
            @if($order->status === 'draft')
                <form action="{{ route('orders.confirm', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="status" value="pending">
                    <button type="submit" class="btn btn-primary">Lên đơn</button>
                </form>
            @endif
            @if($order->status === 'pending')
                <form action="{{ route('orders.confirm', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="btn btn-primary">Xác nhận đơn hàng</button>
                </form>
            @endif
            @if($order->status === 'confirmed')
                <form action="{{ route('orders.picking', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">Bắt đầu Picking</button>
                </form>
            @endif
            @if($order->status === 'picking')
                <form action="{{ route('orders.pickup', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">Shipper lấy hàng (Trừ kho thật)</button>
                </form>
            @endif
            @if($order->status === 'picked_up')
                <form action="{{ route('orders.ship', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-info">Đang giao hàng</button>
                </form>
            @endif
            @if($order->status === 'shipping')
                <form action="{{ route('orders.complete', $order->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-success">Hoàn tất đơn hàng</button>
                </form>
            @endif
            @if(in_array($order->status, ['pending','confirmed','picking']))
                <form action="{{ route('orders.cancel', $order->id) }}" method="POST" style="display:inline; margin-left:8px;">
                    @csrf
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Hủy đơn sẽ release hàng đã booking. Bạn chắc chắn?')">Hủy đơn</button>
                </form>
            @endif
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Xét duyệt đơn hàng</h5>
        </div>
        <div class="card-body">
            @if($order->status === \App\Enums\OrderStatus::Approved->value)
                <span class="badge bg-success">Đơn đã được duyệt hoàn tất</span>
            @elseif($order->status === \App\Enums\OrderStatus::Rejected->value)
                <span class="badge bg-danger">Đơn đã bị từ chối</span>
            @elseif($canApproveCurrentStep)
                <form method="POST" action="{{ route('orders.approve', $order) }}" class="mb-2">
                    @csrf
                    <div class="mb-2">
                        <textarea name="note" class="form-control" rows="2" placeholder="Ghi chú duyệt (không bắt buộc)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
                <form method="POST" action="{{ route('orders.reject', $order) }}">
                    @csrf
                    <div class="mb-2">
                        <textarea name="note" class="form-control" rows="2" placeholder="Lý do từ chối"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </form>
            @else
                <p class="mb-0 text-muted">Bạn không có quyền duyệt bước hiện tại hoặc đơn đã không còn ở trạng thái chờ duyệt.</p>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Lịch sử xét duyệt</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Bước</th>
                            <th>Role</th>
                            <th>Trạng thái</th>
                            <th>Người xử lý</th>
                            <th>Thời gian</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->approvals->sortBy(fn($item) => $item->step->step_order ?? 999) as $approval)
                            <tr>
                                <td>{{ $approval->step->step_order ?? '' }}</td>
                                <td>{{ $approval->step->role_slug ?? '' }}</td>
                                <td>{{ $approval->status }}</td>
                                <td>{{ $approval->approver->name ?? '' }}</td>
                                <td>{{ $approval->approved_at }}</td>
                                <td>{{ $approval->note }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Chưa có dữ liệu xét duyệt.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <h5>Danh sách sản phẩm</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Biến thể</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_variant->product->name ?? '' }}</td>
                <td>{{ $item->product_variant->variant_name ?? '' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
                <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if(!$order->isPaid())
        <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success mb-3">+ Thêm giao dịch/Thanh toán</a>
    @endif
    <h5>Giao dịch liên quan</h5>
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Số tiền</th>
                <th>Loại</th>
                <th>Phương thức</th>
                <th>Ghi chú</th>
                <th>Thời gian</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->transactions as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ number_format($t->amount,0,',','.') }}</td>
                    <td>{{ $t->type }}</td>
                    <td>{{ $t->method }}</td>
                    <td>{{ $t->note }}</td>
                    <td>{{ $t->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
