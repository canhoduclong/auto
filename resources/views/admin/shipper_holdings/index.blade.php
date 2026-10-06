@extends('layouts.admin')
@section('title', 'Đơn Shipper đang giữ')
@section('content')
<div class="container-fluid py-3">
<h3>Đơn Shipper đang giữ</h3>
<p class="text-muted">Hiển thị hàng đang vận chuyển trên tất cả các ngày, gồm đơn giao khách và điều chuyển giữa kho. Chọn shipper để tra soát đơn tồn lâu.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form class="row g-2 mb-3" method="GET">
<div class="col-md-4"><label class="form-label">Shipper</label><select class="form-select" name="shipper_id"><option value="">Tất cả shipper</option>@foreach($shippers as $shipper)<option value="{{ $shipper->id }}" @selected((int)$shipperId === $shipper->id)>{{ $shipper->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Mã đơn / khách hàng</label><input class="form-control" name="search" value="{{ $search }}" maxlength="255"></div>
<div class="col-md-4 d-flex align-items-end gap-2"><button class="btn btn-primary">Lọc</button><a class="btn btn-light" href="{{ route('admin.shipper-holdings.index') }}">Tất cả</a></div>
</form>
<div class="alert alert-info">Hoàn tất ngoại lệ: xác nhận giao và hoàn tất đơn giao khách; với điều chuyển, nhập hàng vào kho đích theo số lượng và cân nặng đã đóng. Gỡ ngoại lệ: bỏ giữ hàng ở shipper, hoàn tồn thực xuất về kho lấy hàng. Mỗi thao tác cần lý do và được lưu lịch sử.</div>
@foreach(['transfer'=>['Điều chuyển kho', $transfers], 'order'=>['Đơn giao khách', $orders]] as $kind=>$group)
<h5>{{ $group[0] }} <span class="badge bg-primary">{{ $group[1]->total() }}</span></h5>
<div class="row">
@forelse($group[1] as $holding)
@php($order = $kind === 'transfer' ? $holding->order : $holding)
<div class="col-xl-6"><div class="card mb-3"><div class="card-body">
<div class="d-flex justify-content-between flex-wrap gap-2"><strong><a href="{{ route('orders.show', $order) }}">{{ $order->code }}</a></strong><span class="badge bg-warning text-dark">{{ $holding->shipper?->name ?? 'Không xác định shipper' }} đang giữ</span></div>
<div class="mt-2 fw-semibold">{{ $order->customer?->name ?? '-' }}</div>
@if($kind === 'transfer')
<div>{{ $holding->sourceWarehouse?->name }} → {{ $holding->targetWarehouse?->name }}</div>
<div class="text-muted small">Phiếu điều chuyển #{{ $holding->id }} · Nhận lúc: {{ $holding->picked_up_at?->format('d/m/Y H:i') ?? '-' }}</div>
@else
<div>Kho lấy hàng: {{ $order->warehouse?->name ?? '-' }}</div>
<div class="text-muted small">{{ \App\Models\Order::statusOptions()[$order->status] ?? $order->status }} · Ngày tạo: {{ $order->created_at?->format('d/m/Y H:i') }}</div>
@endif
<ul class="my-2">@foreach($order->items as $item)<li>{{ $item->variant?->product?->name ?? $item->imported_name ?? 'Sản phẩm' }} · {{ $item->variant?->name }} · SL: {{ $item->quantity }}</li>@endforeach</ul>
@if($holding->shipper_id)
<div class="d-flex flex-wrap gap-2">
<button type="button" class="btn btn-success holding-action" data-url="{{ route('admin.shipper-holdings.process', ['kind'=>$kind,'id'=>$holding->id]) }}" data-shipper="{{ $holding->shipper_id }}" data-action="complete" data-label="{{ $order->code }}" data-kind="{{ $kind }}">Hoàn tất ngoại lệ</button>
<button type="button" class="btn btn-outline-danger holding-action" data-url="{{ route('admin.shipper-holdings.process', ['kind'=>$kind,'id'=>$holding->id]) }}" data-shipper="{{ $holding->shipper_id }}" data-action="return" data-label="{{ $order->code }}" data-kind="{{ $kind }}">Gỡ ngoại lệ · Trả kho lấy</button>
</div>
@endif
</div></div></div>
@empty
<div class="col-12"><div class="card card-body text-muted mb-3">Không có đơn đang giữ phù hợp bộ lọc.</div></div>
@endforelse
</div>
{{ $group[1]->links() }}
@endforeach
<h5 class="mt-4">Lịch sử xử lý ngoại lệ gần nhất</h5>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Thời gian</th><th>Đơn</th><th>Admin xử lý</th><th>Nội dung</th></tr></thead><tbody>@forelse($histories as $history)<tr><td>{{ $history->created_at?->format('d/m/Y H:i') }}</td><td>{{ $history->order?->code }}</td><td>{{ $history->user?->name }}</td><td style="white-space:normal;overflow-wrap:anywhere">{{ $history->note }}</td></tr>@empty<tr><td colspan="4">Chưa có xử lý ngoại lệ.</td></tr>@endforelse</tbody></table></div>
</div>
<div class="modal fade" id="holdingExceptionModal" tabindex="-1" aria-labelledby="holdingExceptionTitle" aria-hidden="true"><div class="modal-dialog"><form method="POST" class="modal-content" id="holdingExceptionForm">@csrf
<input type="hidden" name="action"><input type="hidden" name="shipper_id">
<div class="modal-header"><h5 id="holdingExceptionTitle" class="modal-title"></h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
<div class="modal-body"><p id="holdingExceptionDescription"></p><label class="form-label" for="holdingReason">Lý do xử lý ngoại lệ *</label><textarea id="holdingReason" name="reason" class="form-control" required maxlength="1000" rows="4"></textarea></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Xác nhận xử lý</button></div>
</form></div></div>
<script>
document.querySelectorAll('.holding-action').forEach(button => button.addEventListener('click', () => {
 const form = document.getElementById('holdingExceptionForm');
 form.action = button.dataset.url;
 form.elements.action.value = button.dataset.action;
 form.elements.shipper_id.value = button.dataset.shipper;
 form.elements.reason.value = '';
 document.getElementById('holdingExceptionTitle').textContent = (button.dataset.action === 'complete' ? 'Hoàn tất ngoại lệ: ' : 'Gỡ ngoại lệ: ') + button.dataset.label;
 document.getElementById('holdingExceptionDescription').textContent = button.dataset.action === 'return' ? 'Xác nhận hàng đã trả về kho lấy. Hệ thống gỡ khỏi shipper và hoàn tồn đã xuất về kho này.' : button.dataset.kind === 'transfer' ? 'Xác nhận hàng đã đến kho đích. Hệ thống đánh dấu đã giao, tiếp nhận và nhập kho theo dữ liệu đóng hàng.' : 'Xác nhận đơn đã giao khách và hoàn tất. Thông tin thanh toán giữ theo số tiền thực tế đã ghi nhận.';
 bootstrap.Modal.getOrCreateInstance(document.getElementById('holdingExceptionModal')).show();
}));
document.getElementById('holdingExceptionForm').addEventListener('submit', function () {
 this.querySelector('button[type="submit"]').disabled = true;
});
</script>
@endsection
