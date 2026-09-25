@extends('layouts.app')

@section('title', 'Làm lại nguyên ngày')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h3 class="mb-1"><i class="ph-arrow-counter-clockwise me-2"></i>Làm lại nguyên ngày</h3>
            <div class="text-muted">Chức năng quản trị đặc biệt để hoàn tác tồn và đưa đơn trong một ngày trở lại quy trình đóng hàng.</div>
        </div>
        <a href="{{ route('admin.text-order-import.index', ['delivery_date' => $date]) }}" class="btn btn-outline-primary"><i class="ph-textbox me-1"></i>Lên đơn bổ sung</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <form method="GET" action="{{ route('admin.daily-rebuild.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label">Ngày cần làm lại</label><input type="date" name="date" class="form-control" value="{{ $date }}" required></div>
            <div class="col-md-5"><label class="form-label">Kho xử lý</label><select name="warehouse_id" class="form-select" required>@foreach($warehouses as $warehouseOption)<option value="{{ $warehouseOption->id }}" @selected($warehouseOption->id === $warehouseId)>{{ $warehouseOption->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><button class="btn btn-primary w-100"><i class="ph-magnifying-glass me-1"></i>Xem trước dữ liệu</button></div>
        </form>
    </div></div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card border-warning h-100"><div class="card-body"><div class="text-muted small">Đơn bị hủy</div><div class="display-6 fw-bold text-warning">{{ $cancelledOrders->count() }}</div><div class="small">Sẽ đưa về Chờ đóng gói.</div></div></div></div>
        <div class="col-md-4"><div class="card border-success h-100"><div class="card-body"><div class="text-muted small">Đơn đã giao / hoàn thành</div><div class="display-6 fw-bold text-success">{{ $deliveredOrders->count() }}</div><div class="small">Sẽ hoàn tác xuất kho và xóa dữ liệu logistics liên quan.</div></div></div></div>
        <div class="col-md-4"><div class="card border-info h-100"><div class="card-body"><div class="text-muted small">Lần đồng bộ Google Sheet</div><div class="display-6 fw-bold text-info">{{ $syncs->count() }}</div><div class="small">Sẽ hoàn tác và chuyển sang bước đồng bộ lại.</div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white"><strong>Đơn sẽ được phục hồi</strong></div><div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead class="table-light"><tr><th>Mã đơn</th><th>Khách hàng</th><th>Sale</th><th>Kho</th><th>Trạng thái hiện tại</th></tr></thead>
        <tbody>@forelse($orders as $order)<tr><td>{{ $order->code ?: '#'.$order->id }}</td><td>{{ $order->customer?->name ?: '—' }}</td><td>{{ $order->user?->name ?: '—' }}</td><td>{{ $order->warehouse?->name ?: '—' }}</td><td><span class="badge {{ $order->status === \App\Models\Order::STATUS_CANCELLED ? 'bg-warning text-dark' : 'bg-success' }}">{{ $order->status }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Không có đơn hủy hoặc đã giao trong ngày và kho này.</td></tr>@endforelse</tbody>
    </table></div></div>

    <div class="alert alert-warning">
        <strong>Trình tự thực hiện:</strong> hệ thống giải phóng booking, hoàn tác phiếu xuất và điều chuyển, reset lần đồng bộ tồn, đưa đơn về Chờ đóng gói; sau đó chuyển sang màn hình Google Sheet để Admin kiểm tra và áp dụng lại tồn. Chứng từ ngoài phạm vi các đơn trên không bị xóa.
    </div>

    <div class="card border-danger shadow-sm"><div class="card-body">
        <h5 class="text-danger">Xác nhận làm lại ngày {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} · {{ $warehouse?->name }}</h5>
        <form method="POST" action="{{ route('admin.daily-rebuild.execute') }}" onsubmit="return confirm('Đây là thao tác lớn. Xác nhận làm lại toàn bộ ngày đã chọn?');">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
            <div class="row g-3">
                <div class="col-md-7"><label class="form-label">Lý do thực hiện</label><input name="reason" class="form-control" minlength="5" maxlength="500" required value="{{ old('reason') }}"></div>
                <div class="col-md-5"><label class="form-label">Nhập lại ngày để xác nhận</label><input name="confirmation_date" class="form-control" placeholder="{{ $date }}" required></div>
                <div class="col-12"><label class="form-check p-3 border border-danger rounded bg-danger-subtle"><input class="form-check-input" type="checkbox" name="confirm_rebuild" value="1" required><span class="form-check-label text-danger">Tôi đã kiểm tra danh sách và đồng ý hoàn tác dữ liệu nghiệp vụ của ngày này.</span></label></div>
                <div class="col-12"><button class="btn btn-danger btn-lg" @disabled(!$warehouse)><i class="ph-arrow-counter-clockwise me-1"></i>Làm lại nguyên ngày</button></div>
            </div>
        </form>
    </div></div>
</div>
@endsection
