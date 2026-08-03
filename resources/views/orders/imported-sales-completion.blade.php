@extends($layout)
@section('title', 'Hoàn chỉnh đơn lịch sử')
@section('content')
<style>
.hist-card{border:0;border-radius:12px;box-shadow:0 3px 16px rgba(15,23,42,.07)}
.hist-order{border-left:4px solid #f59e0b}.hist-order.done{border-left-color:#16a34a}
</style>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div><h3 class="mb-1">Hoàn chỉnh đơn lịch sử</h3><div class="text-muted">Đơn đã hoàn thành và ghi nhận doanh thu; bổ sung Kho và Shipper để hoàn thiện dữ liệu vận hành.</div></div>
        <div class="btn-group"><a class="btn {{ !$showCompleted?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.imported-sales-orders.index') }}">Cần hoàn chỉnh</a><a class="btn {{ $showCompleted?'btn-success':'btn-outline-success' }}" href="{{ route('admin.imported-sales-orders.index',['completed'=>1]) }}">Đã hoàn chỉnh</a></div>
    </div>
    <div class="alert alert-warning"><b>Lưu ý:</b> đây là đơn lịch sử đã hoàn thành. Việc chọn Kho và Shipper chỉ bổ sung dữ liệu quản trị, không tạo phiếu xuất kho và không trừ tồn kho lần nữa.</div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @forelse($orders as $order)
    <div class="card hist-card hist-order {{ $order->needs_operational_completion?'':'done' }} mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="d-flex gap-2 align-items-center mb-2"><span class="badge bg-dark">{{ $order->code ?: '#'.$order->id }}</span><span class="badge bg-success">Đã hoàn thành</span><span class="badge bg-info text-dark">Phiên #{{ $order->accounting_sales_import_batch_id }}</span></div>
                <div class="fw-bold fs-5">{{ $order->customer?->name ?: $order->recipient_name }}</div>
                <div class="text-muted">Mã KH: {{ $order->customer?->customer_code ?: '—' }} · Ngày: {{ optional($order->delivery_date)->format('d/m/Y') }}</div>
                <div class="mt-2">NVKD: <b>{{ $order->user?->name ?: '—' }}</b></div>
                <div>Doanh số xác nhận: <b class="text-success">{{ number_format((float)($order->accountingReconciliation?->recognized_revenue ?? $order->total),0,',','.') }} đ</b></div>
            </div>
            <div class="col-lg-4">
                <div class="small fw-semibold mb-1">Chi tiết kế toán</div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>DVT</th><th>SL</th><th>Tổng</th><th>Tiền</th></tr></thead><tbody>
                    @foreach($order->accountingSalesEntries as $line)<tr><td>{{ $line->unit }}</td><td>{{ number_format((float)$line->quantity,1,',','.') }}</td><td>{{ number_format((float)$line->total_quantity,1,',','.') }}</td><td>{{ number_format((float)$line->total_amount,0,',','.') }}</td></tr>@endforeach
                </tbody></table></div>
            </div>
            <div class="col-lg-4">
                @if($order->needs_operational_completion)
                <form method="POST" action="{{ route('admin.imported-sales-orders.update',$order) }}">@csrf @method('PUT')
                    <div class="mb-2"><label class="form-label small fw-semibold">Kho phụ trách</label><select class="form-select" name="warehouse_id" required><option value="">-- Chọn kho --</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((int)old('warehouse_id',$order->warehouse_id)===(int)$warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Shipper</label><select class="form-select" name="shipper_id" required><option value="">-- Chọn shipper --</option>@foreach($shippers as $shipper)<option value="{{ $shipper->id }}" @selected((int)old('shipper_id',$order->shipper_id)===(int)$shipper->id)>{{ $shipper->name }}</option>@endforeach</select></div>
                    <div class="mb-2"><label class="form-label small">Ghi chú hoàn chỉnh</label><textarea class="form-control" rows="2" name="operational_completion_note"></textarea></div>
                    <button class="btn btn-primary w-100" onclick="return confirm('Xác nhận Kho và Shipper cho đơn lịch sử này?')">Lưu và đánh dấu hoàn chỉnh</button>
                </form>
                @else
                <div class="alert alert-success mb-2">Đã hoàn chỉnh lúc {{ optional($order->operational_completed_at)->format('d/m/Y H:i') }}</div>
                <div>Kho: <b>{{ $order->warehouse?->name ?: '—' }}</b></div><div>Shipper: <b>{{ $order->shipper?->name ?: '—' }}</b></div><div class="text-muted small mt-2">{{ $order->operational_completion_note }}</div>
                @endif
            </div>
        </div>
    </div></div>
    @empty<div class="card hist-card"><div class="card-body text-center text-muted py-5">Không có đơn nào trong nhóm này.</div></div>@endforelse
    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
