@extends('layouts.warehouse')
@section('title', 'Điều chuyển kho')
@push('styles')
<style>
.tw{min-width:0;color:#193846}.tw-panel{background:#fff;border:1px solid #dce7ea;border-radius:12px;padding:16px;margin-bottom:16px;min-width:0}.tw-columns{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:20px}.tw-toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px}.tw .btn-primary{background:#0f766e;border-color:#0f766e}.tw .form-control,.tw .form-select{min-width:0}.tw-picker{display:flex;gap:8px;flex-wrap:wrap}.tw-picker select{flex:1;min-width:140px}.tw-days{display:flex;gap:8px;overflow-x:auto;padding:4px 0;max-width:100%}.tw-days a{white-space:nowrap;border:1px solid #cbd8e4;border-radius:24px;padding:7px 12px;font-size:13px;text-decoration:none}.tw-days a.active{background:#edf3ff;border-color:#2962ff;color:#174cff}.tw-days b{background:#e5eaf0;border-radius:10px;padding:1px 5px}.tw-order{padding:12px;margin:10px 0;background:#fff;border:1px solid #dce7ea;border-radius:10px;overflow-wrap:anywhere}.tw-order-head{display:flex;align-items:center;gap:10px}.tw-order-info{flex:1;min-width:0}.tw-order small{color:#607080}.tw-sequence{border-radius:50%;background:#168753;color:white;min-width:34px;height:34px;display:grid;place-items:center;font-weight:bold}.tw-product{display:grid;grid-template-columns:24px minmax(120px,1fr) 70px 70px 94px 32px;gap:8px;align-items:center;padding:10px 0;border-bottom:1px solid #e7edf0}.tw-product label{font-size:12px;color:#657884}.tw-product input{width:100%;padding:6px}.tw-product input[type=checkbox]{width:18px;height:18px}.tw-remove{border:1px solid #dc3545;color:#dc3545;background:white;border-radius:5px;width:30px;height:30px}.tw-destination{border-left:4px solid #0f766e}.tw-empty{padding:25px 12px;text-align:center;color:#667984;background:#f6f9fa;border-radius:8px}.tw table{min-width:0!important;width:100%}.tw td,.tw th{vertical-align:middle;white-space:normal;overflow-wrap:anywhere}.tw button,.tw input[type=checkbox]{touch-action:manipulation}.tw-slip-actions{display:flex;gap:6px;flex-wrap:wrap}.tw-help{font-size:13px;color:#5e7480}.tw-select-all{display:flex;gap:8px;align-items:center}.tw-selection select{max-width:230px}.tw .table-responsive{min-width:0}.tw-product-picker{max-height:260px;overflow:auto;border:1px solid #e0e9eb;border-radius:8px;scrollbar-width:thin}
.tw-stock-head,.tw-product-picker .tw-stock-row{display:grid;grid-template-columns:minmax(0,1fr) 72px 58px 28px;align-items:center;gap:8px;padding:6px 10px}
.tw-stock-head{position:sticky;top:0;z-index:1;background:#f2f7f8;color:#657884;font-size:11px;font-weight:600;border-bottom:1px solid #e0e9eb}
.tw-stock-head>span:not(:first-child){text-align:center}
.tw-product-picker .tw-stock-row{width:100%;min-height:48px;text-align:left;border:0;border-bottom:1px solid #edf1f2;background:white;font-size:13px;line-height:1.3;color:inherit}
.tw-product-picker .tw-stock-row:last-child{border-bottom:0}
.tw-product-picker .tw-stock-row:hover,.tw-product-picker .tw-stock-row:focus-visible{background:#edf8f5}
.tw-product-picker .tw-stock-row:focus-visible{outline:2px solid #0f766e;outline-offset:-2px}
.tw-stock-name{min-width:0}.tw-stock-name strong,.tw-stock-name small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tw-stock-name strong{font-size:13px;font-weight:600}.tw-stock-name small{font-size:11px;color:#6b7f87;margin-top:2px}
.tw-stock-size{font-size:12px;text-align:center;white-space:nowrap}.tw-stock-available{background:#eaf5f0;color:#126b4f;border-radius:5px;padding:3px 5px;text-align:center;font-size:12px;font-weight:700}
.tw-stock-add{display:grid;place-items:center;width:26px;height:26px;border:1px solid #b6d9d2;border-radius:5px;color:#0f766e;font-size:18px;line-height:1}
@media(max-width:575px){.tw-stock-head,.tw-product-picker .tw-stock-row{grid-template-columns:minmax(0,1fr) 52px 42px 24px;gap:5px;padding:6px}.tw-stock-name strong{font-size:12px}.tw-stock-size{font-size:11px}.tw-stock-add{width:24px;height:26px}}

@media(max-width:1300px){.tw-columns{grid-template-columns:minmax(0,1fr)}.tw-product{grid-template-columns:24px minmax(100px,1fr) 65px 65px 90px 32px}}
@media(max-width:575px){.tw-panel{padding:12px}.tw-order-head{flex-wrap:wrap}.tw-product{grid-template-columns:24px minmax(0,1fr) 32px;gap:8px}.tw-product .tw-product-name{grid-column:2}.tw-product .tw-product-field{grid-row:2}.tw-product .tw-product-field:nth-of-type(2){grid-column:1/2}.tw-product .tw-remove{grid-column:3;grid-row:1}.tw-product .tw-qty{grid-column:1/2;min-width:55px}.tw-product .tw-size{grid-column:2;padding-left:35px}.tw-product .tw-weight{grid-column:3;min-width:80px;justify-self:end}.tw-product{padding-bottom:14px;grid-template-columns:55px minmax(85px,1fr) 80px}.tw-product .tw-product-name{grid-column:2}.tw-selection{width:100%}.tw-selection select{max-width:none;flex:1}.tw-slip-table thead{display:none}.tw-slip-table tr{display:block;border-bottom:1px solid #ccdbe0;padding:12px 0}.tw-slip-table td{display:block;border:0;padding:5px 0;overflow-wrap:anywhere}.tw-slip-table td:before{content:attr(data-label);font-weight:600;margin-right:8px}.tw-toolbar .btn{white-space:normal}.tw-days{width:100%}}
</style>
@endpush
@section('content')
<div class="tw" id="transfer-workbench">
<div class="tw-toolbar justify-content-between"><div><h4 class="mb-1">Điều chuyển kho</h4><span class="tw-help">Kho nguồn: <strong>{{ $sourceWarehouse?->name }}</strong></span></div><a class="btn btn-outline-primary btn-sm" href="{{ route('warehouse.inventory-transfers.incoming') }}">Tiếp nhận điều chuyển ({{ $incomingPendingCount }})</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert"><strong>Chưa tạo điều chuyển.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="get" class="tw-panel" id="tw-filters">
<div class="tw-toolbar"><label>Ngày nghiệp vụ<input class="form-control" name="date" type="date" value="{{ $businessDate }}" required></label><label>Trạng thái đơn<select name="status" class="form-select"><option value="">Tất cả trạng thái</option>@foreach(['ready_to_ship'=>'Sẵn sàng giao','packed'=>'Đã đóng gói','packed_waiting_pickup'=>'Chờ lấy hàng'] as $value=>$label)<option value="{{ $value }}" @selected($status===$value)>{{ $label }}</option>@endforeach</select></label><button class="btn btn-primary align-self-end">Lọc</button></div>
<div class="tw-days" aria-label="Chọn nhanh ngày nghiệp vụ">@foreach($quickDays as $day)<a @class(['active'=>$businessDate===$day['date']]) href="{{ route('warehouse.inventory-transfers.index', ['date'=>$day['date'],'status'=>$status]) }}">{{ $day['label'] }} <b>{{ $day['count'] }}</b></a>@endforeach</div>
<div class="tw-help mt-2">Đơn theo ngày lên đơn, ngày nhập lịch sử hoặc ngày hoàn tất đóng gói. Phiếu tổng bên dưới theo ngày nghiệp vụ.</div>
</form>
<form action="{{ route('warehouse.inventory-transfers.batch') }}" method="post" id="tw-submit-form">
@csrf
<input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
<input type="hidden" name="business_date" value="{{ old('business_date', $businessDate) }}">
<div id="tw-payload"></div>
<div class="tw-columns">
<section>
<div class="tw-panel"><div class="tw-toolbar justify-content-between"><h5 class="mb-0">Sản phẩm tồn kho</h5><button type="button" class="btn btn-primary btn-sm" id="tw-add-product">+ Thêm sản phẩm</button></div>
<div id="tw-picker" hidden><input type="search" id="tw-product-search" class="form-control mb-2" placeholder="Tìm tên sản phẩm, size hoặc mã hàng" aria-label="Tìm sản phẩm"><div class="tw-product-picker" id="tw-product-results"></div></div>
<div id="tw-products"></div>
<div class="tw-toolbar tw-selection mt-3"><label class="tw-select-all"><input type="checkbox" id="tw-all-products"> Tất cả</label><select class="form-select form-select-sm" id="tw-product-target" aria-label="Kho nhận sản phẩm"><option value="">Chọn kho nhận</option>@foreach($targetWarehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select><button type="button" class="btn btn-primary btn-sm" id="tw-assign-products">Chuyển qua kho</button></div></div>
<div class="tw-panel"><h5>Đơn hàng <span id="tw-order-count" class="badge text-bg-secondary"></span></h5><div class="tw-toolbar tw-selection"><label class="tw-select-all"><input type="checkbox" id="tw-all-orders"> Tất cả</label><select id="tw-order-target" class="form-select form-select-sm" aria-label="Kho nhận đơn hàng"><option value="">Chọn kho nhận</option>@foreach($targetWarehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select><button type="button" class="btn btn-primary btn-sm" id="tw-assign-orders">Chuyển qua kho</button></div><div id="tw-orders"></div></div>
</section>
<section><div class="tw-panel"><div class="tw-toolbar justify-content-between"><h5 class="mb-0">Điều chuyển đến</h5><button type="submit" id="tw-execute" class="btn btn-primary">Chốt & xuất phiếu tổng</button></div><p class="tw-help">Mỗi kho nhận tạo một phiếu tổng gồm các đơn và hàng tồn đã chọn. Phiếu được chốt ngay sau khi tạo để shipper nhận hàng điều chuyển.</p><div id="tw-client-error" class="alert alert-danger" role="alert" hidden></div><div id="tw-destinations"></div></div></section>
</div>
</form>
<section class="tw-panel"><h5>Phiếu điều chuyển hàng đã tạo</h5>
<div class="table-responsive"><table class="table tw-slip-table"><thead><tr><th>Mã phiếu</th><th>Kho nhận</th><th>Sản phẩm</th><th>Trạng thái / thao tác</th></tr></thead><tbody>
@forelse($outgoingTransfers as $transfer)
<tr><td data-label="Phiếu">{{ $transfer->transfer_code }}<div class="small text-muted">{{ $transfer->created_at?->format('d/m/Y H:i') }}</div></td><td data-label="Kho nhận">{{ $transfer->targetWarehouse?->name }}</td><td data-label="Hàng chuyển">@foreach($transfer->items as $item)<div class="small">{{ $item->variant?->product?->name }} · {{ $item->variant?->name }} × {{ $item->quantity }} · {{ format_kg($item->weight_kg) }}</div>@endforeach<strong class="small">Tổng: {{ format_kg($transfer->items->sum('weight_kg')) }}</strong></td><td data-label="Trạng thái">{{ ['pending_receive'=>'Chờ kho nhận','received_completed'=>'Đã tiếp nhận','cancelled'=>'Đã hủy'][$transfer->status] ?? $transfer->status }}
@if($transfer->dispatchEntry?->slip)<a class="d-block" href="{{ route('warehouse.dispatch-slips.show', $transfer->dispatchEntry->slip) }}">{{ $transfer->dispatchEntry->slip->code }}</a>
@if($transfer->dispatchEntry->slip->status === 'draft')<form method="POST" action="{{ route('warehouse.dispatch-slips.finalize', $transfer->dispatchEntry->slip) }}" onsubmit="return confirm('Chốt toàn bộ phiếu tổng này để shipper nhận hàng?')">@csrf<button type="submit" class="btn btn-success btn-sm mt-1">Chốt phiếu tổng</button></form>@endif
@elseif($transfer->status === 'pending_receive' && !$transfer->order_id)<a class="btn btn-outline-primary btn-sm" href="{{ route('warehouse.inventory-transfers.edit', $transfer) }}">Sửa</a><a class="btn btn-success btn-sm ms-1" href="{{ route('warehouse.dispatch-slips.index') }}">Lập phiếu tổng để chốt</a>@endif
</td></tr>
@empty<tr><td colspan="4" class="text-muted">Chưa có phiếu điều chuyển hàng.</td></tr>@endforelse
</tbody></table></div>{{ $outgoingTransfers->withQueryString()->links() }}
</section>
<section class="tw-panel" id="tw-slips"><div class="tw-toolbar justify-content-between"><h5 class="mb-0">Phiếu xuất tổng · {{ \Carbon\Carbon::parse($businessDate)->format('d/m/Y') }} và các phiếu chưa chốt</h5><a class="btn btn-outline-secondary btn-sm" href="{{ route('warehouse.dispatch-slips.index') }}">Tất cả phiếu / lập từ điều chuyển có sẵn</a></div>
<div class="table-responsive"><table class="table tw-slip-table"><thead><tr><th>Mã phiếu</th><th>Kho nhận / tài xế</th><th>Nội dung</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
@forelse($dispatchSlips as $slip)
<tr><td data-label="Phiếu"><a href="{{ route('warehouse.dispatch-slips.show', $slip) }}">{{ $slip->code }}</a><div class="small text-muted">{{ $slip->business_date?->format('d/m/Y') }}</div></td><td data-label="Kho nhận">{{ $slip->targetWarehouse?->name }}<div class="small text-muted">{{ $slip->shipper?->name }}</div></td><td data-label="Nội dung">{{ $slip->entries->sum(fn($entry)=>$entry->orderTransfer?->orders->count() ?? 0) }} đơn · {{ $slip->entries->sum(fn($entry)=>$entry->inventoryTransfer?->items->sum('quantity') ?? 0) }} SP tồn kho<div class="small">@foreach($slip->entries as $entry)@if($entry->inventoryTransfer){{ $entry->inventoryTransfer->transfer_code }} · {{ format_kg($entry->inventoryTransfer->items->sum('weight_kg')) }}@endif @endforeach</div></td><td data-label="Trạng thái">{{ ['draft'=>'Đang mở','finalized'=>'Đã chốt','cancelled'=>'Đã hủy'][$slip->status] ?? $slip->status }}<div class="small text-muted">{{ $slip->print_count ? 'Đã mở in '.$slip->print_count.' lần' : 'Chưa in' }}</div></td><td><div class="tw-slip-actions">@if($slip->status === 'draft')<form method="POST" action="{{ route('warehouse.dispatch-slips.finalize', $slip) }}" onsubmit="return confirm('Chốt phiếu {{ $slip->code }} để shipper nhận hàng?')">@csrf<button type="submit" class="btn btn-success btn-sm">Chốt phiếu</button></form>@endif<a class="btn btn-primary btn-sm" target="_blank" rel="noopener" href="{{ route('warehouse.dispatch-slips.print-export', $slip) }}">In phiếu tổng</a><a class="btn btn-outline-secondary btn-sm" href="{{ route('warehouse.dispatch-slips.show', $slip) }}">Xem phiếu</a></div></td></tr>
@empty<tr><td colspan="5" class="text-center text-muted py-4">Chưa có phiếu xuất tổng trong ngày này.</td></tr>@endforelse
</tbody></table></div>{{ $dispatchSlips->links() }}
</section>
</div>
@php
$orderData = $orders->map(fn($order) => [
'id'=>$order->id,'code'=>$order->code,'sequence'=>$order->daily_sequence,'customer'=>$order->customer?->name ?? 'Khách hàng',
'phone'=>$order->recipient_phone ?: $order->customer?->phone,
'station'=>$order->use_truck_station ? implode(' · ', array_filter([$order->truck_station_name ?: $order->truckStation?->name,$order->truck_station_address ?: $order->truckStation?->address,$order->truck_station_phone ?: $order->truckStation?->phone])) : null,
'date'=>$order->created_at?->format('d/m/Y H:i'),
'items'=>$order->items->map(fn($item)=>['name'=>$item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm','size'=>$item->variant?->name,'quantity'=>$item->quantity,'weight'=>(float)($item->packed_weight ?? $item->total_weight ?? 0)])->values(),
]);
@endphp
@endsection
@push('scripts')
<script>
window.transferWorkbenchData = {
    products: @json($availableVariants), orders: @json($orderData), warehouses: @json($targetWarehouses), shippers: @json($shippers), oldGroups: @json(old('groups', []))
};
</script>
<script src="{{ asset('js/warehouse/transfer-workbench.js') }}?v={{ filemtime(public_path('js/warehouse/transfer-workbench.js')) }}"></script>
@endpush
