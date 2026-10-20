@extends('layouts.site')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4">Hoàn trả đơn #{{ $order->code }}</h1><a href="{{ route('sale-returns.index') }}" class="btn btn-outline-secondary">Theo dõi hoàn trả</a></div>
    <p class="text-muted">{{ $order->customer?->name }} · Chọn số lượng trả, shipper xử lý và kho nhận. Sau khi kho nhận hàng, kế toán sẽ duyệt giá trị hoàn trả.</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if($shippers->isEmpty())<div class="alert alert-warning">Chưa có shipper được bật “Cho phép điều phối xác nhận và hoàn thành giao hàng thay shipper”. Vui lòng liên hệ quản trị viên.</div>@endif
    <form method="POST" action="{{ route('sale-returns.store', $order) }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">Shipper xử lý hoàn trả</label><select name="assigned_shipper_id" class="form-select" required><option value="">Chọn shipper</option>@foreach($shippers as $shipper)<option value="{{ $shipper->id }}" @selected(old('assigned_shipper_id') == $shipper->id)>{{ $shipper->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Kho nhận</label><select name="warehouse_id" class="form-select" required><option value="">Chọn kho</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">Lý do hoàn trả</label><textarea name="reason" class="form-control" required maxlength="2000">{{ old('reason') }}</textarea></div>
            <div class="col-md-4"><label class="form-label">Ảnh hàng trả</label><input type="file" name="evidence_image" accept="image/*" class="form-control" required></div>
        </div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Sản phẩm</th><th>Số lượng đã giao</th><th>Số lượng trả</th></tr></thead><tbody>
        @foreach($order->items as $item)<tr><td>{{ $item->display_name }} {{ $item->variant?->name }}</td><td>{{ $item->quantity }}</td><td><input class="form-control" type="number" min="0" max="{{ $item->quantity }}" name="items[{{ $item->id }}][quantity]" value="{{ old('items.'.$item->id.'.quantity', 0) }}" required></td></tr>@endforeach
        </tbody></table></div>
        <button class="btn btn-primary" @disabled($shippers->isEmpty())>Gửi yêu cầu hoàn trả</button>
    </form>
</div>
@endsection
