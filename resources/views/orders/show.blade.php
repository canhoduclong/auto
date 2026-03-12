@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Chi tiết đơn hàng #{{ $order->code }}</h4>
        <a href="{{ route('orders.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Khách hàng:</strong> {{ $order->customer->name ?? '' }}</p>
                    <p><strong>Nhân viên:</strong> {{ $order->user->name ?? '' }}</p>
                    <p><strong>Tổng tiền:</strong> {{ number_format($order->total, 0, ',', '.') }} đ</p>
                    <p><strong>Ngày tạo:</strong> {{ $order->created_at }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Trạng thái hiện tại:</strong> {{ $statusLabels[$order->status] ?? $order->status }}</p>
                    <p><strong>Thanh toán:</strong> {{ $order->payment_status }}</p>
                    <p><strong>Giao hàng:</strong> {{ $order->delivery_status }}</p>
                    @if($currentPendingApproval && $currentPendingApproval->step)
                        <p><strong>Đang chờ duyệt:</strong> Bước {{ $currentPendingApproval->step->step_order }} (Role: {{ $currentPendingApproval->step->role_slug }})</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-footer">
            @if($order->status === 'approved' && $canWarehouse)
                <form action="{{ route('orders.picking', $order->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">Xác nhận đóng hàng</button>
                </form>
            @endif

            @if($order->status === 'packing' && $canWarehouse)
                <form action="{{ route('orders.complete-packing', $order->id) }}" method="POST" enctype="multipart/form-data" class="row g-2 mt-2">
                    @csrf
                    <div class="col-md-4">
                        <input type="file" name="packed_image" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="note" class="form-control" placeholder="Ghi chú đóng hàng (optional)">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">Hoàn thiện đóng hàng</button>
                    </div>
                </form>
            @endif

            @if($order->status === 'packed' && $canShipper)
                <form action="{{ route('orders.pickup', $order->id) }}" method="POST" class="d-inline mt-2">
                    @csrf
                    <button type="submit" class="btn btn-primary">Lấy hàng</button>
                </form>
            @endif

            @if($order->status === 'shipping' && $canShipper)
                <form action="{{ route('orders.delivered', $order->id) }}" method="POST" enctype="multipart/form-data" class="row g-2 mt-2">
                    @csrf
                    <div class="col-md-4">
                        <input type="file" name="delivered_image" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="note" class="form-control" placeholder="Ghi chú giao hàng (optional)">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-info w-100">Đã giao hàng</button>
                    </div>
                </form>
            @endif

            @if($order->status === 'delivered' && $canShipper)
                <div class="row g-2 mt-2">
                    <div class="col-md-6">
                        <form action="{{ route('orders.complete-payment', $order->id) }}" method="POST" enctype="multipart/form-data" class="border rounded p-2">
                            @csrf
                            <h6 class="mb-2">Thanh toán</h6>
                            <div class="mb-2">
                                <input type="number" step="0.01" min="0" name="amount" class="form-control" placeholder="Số tiền thanh toán" required>
                            </div>
                            <div class="mb-2">
                                <input type="file" name="receipt_image" class="form-control" required>
                                <small class="text-muted">Ảnh biên lai thanh toán</small>
                            </div>
                            <div class="mb-2">
                                <input type="file" name="delivery_image" class="form-control">
                                <small class="text-muted">Ảnh giao hàng (nếu cần bổ sung)</small>
                            </div>
                            <div class="mb-2">
                                <input type="text" name="note" class="form-control" placeholder="Ghi chú (optional)">
                            </div>
                            <button type="submit" class="btn btn-success">Hoàn thiện đơn hàng</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form action="{{ route('orders.refund', $order->id) }}" method="POST" class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                            @csrf
                            <div>
                                <h6 class="mb-2">Refund</h6>
                                <p class="text-muted mb-2">Tạo đơn hoàn trả liên kết với đơn gốc nếu khách không nhận hàng hoặc trả hàng.</p>
                            </div>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Xác nhận tạo yêu cầu hoàn trả cho đơn này?')">Refund</button>
                        </form>
                    </div>
                </div>
            @endif

            @if(in_array($order->status, ['pending_leader_approval', 'pending_manager_approval', 'approved', 'packing'], true))
                <form action="{{ route('orders.cancel', $order->id) }}" method="POST" class="d-inline ms-2">
                    @csrf
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Hủy đơn sẽ release hàng đã booking. Bạn chắc chắn?')">Hủy đơn</button>
                </form>
            @endif
        </div>
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
            <h5 class="card-title mb-0">Lịch sử xử lý đơn hàng</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Người dùng</th>
                            <th>Vai trò</th>
                            <th>Hành động</th>
                            <th>Trạng thái trước</th>
                            <th>Trạng thái sau</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->histories->sortBy('created_at') as $history)
                            <tr>
                                <td>{{ $history->created_at }}</td>
                                <td>{{ $history->user->name ?? '-' }}</td>
                                <td>{{ $history->role ?? '-' }}</td>
                                <td>{{ $history->action }}</td>
                                <td>{{ $history->status_before ?? '-' }}</td>
                                <td>{{ $history->status_after ?? '-' }}</td>
                                <td>{{ $history->note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Chưa có dữ liệu lịch sử xử lý.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
                <td>{{ $item->variant->product->name ?? '' }}</td>
                <td>{{ $item->variant->variant_name ?? ($item->variant->sku ?? '') }}</td>
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
