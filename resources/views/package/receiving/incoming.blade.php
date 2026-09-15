@extends('layouts.package')
@section('title', 'Tiếp nhận đơn & hàng')
@section('content')
@php
    $pendingOrders = $orderTransfers->where('status', 'delivered_waiting_receive');
    $pendingInventory = $inventoryTransfers->where('status', 'pending_receive');
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="fw-bold mb-0">Tiếp nhận vào kho</h5>
    <div><span class="badge bg-warning text-dark me-1">{{ $pendingOrders->count() }} đơn chờ nhận</span><span class="badge bg-primary">{{ $pendingInventory->count() }} phiếu hàng chờ nhận</span></div>
</div>
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#incoming-orders" type="button">Đơn điều chuyển <span class="badge bg-secondary">{{ $pendingOrders->count() }}</span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#incoming-inventory" type="button">Hàng điều chuyển <span class="badge bg-secondary">{{ $pendingInventory->count() }}</span></button></li>
</ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="incoming-orders"><div class="row g-3">
@forelse($orderTransfers as $transfer)
<div class="col-12 col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white d-flex justify-content-between"><div><strong>{{ $transfer->order?->customer?->name ?? 'Khách hàng' }}</strong><div class="small text-muted">{{ $transfer->order?->code }} · từ {{ $transfer->sourceWarehouse?->name ?? '—' }}</div></div><span class="badge {{ $transfer->status==='delivered_waiting_receive'?'bg-warning text-dark':'bg-success' }}">{{ $transfer->status==='delivered_waiting_receive'?'Chờ tiếp nhận':'Đã tiếp nhận' }}</span></div><div class="card-body">@foreach($transfer->order?->items ?? [] as $item)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $item->variant?->product?->name ?? $item->product?->name ?? 'Sản phẩm' }} · Size {{ $item->variant?->size ?? '—' }}</span><strong>{{ number_format((float)$item->quantity) }}</strong></div>@endforeach</div>@if($transfer->status==='delivered_waiting_receive')<div class="card-footer bg-white text-end"><form method="POST" action="{{ route('package.incoming-orders.confirm',$transfer) }}" onsubmit="return confirm('Xác nhận tiếp nhận đơn và nhập kho?')">@csrf<button class="btn btn-success btn-sm">Xác nhận nhận vào kho</button></form></div>@endif</div></div>
@empty <div class="text-muted text-center py-5">Không có đơn điều chuyển.</div> @endforelse
</div></div>
<div class="tab-pane fade" id="incoming-inventory"><div class="row g-3">
@forelse($inventoryTransfers as $transfer)
<div class="col-12 col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white d-flex justify-content-between"><div><strong>{{ $transfer->transfer_code }}</strong><div class="small text-muted">Từ {{ $transfer->sourceWarehouse?->name ?? '—' }}</div></div><span class="badge {{ $transfer->status==='pending_receive'?'bg-warning text-dark':'bg-success' }}">{{ $transfer->status==='pending_receive'?'Chờ tiếp nhận':'Đã tiếp nhận' }}</span></div><div class="card-body">@foreach($transfer->items as $item)<div class="d-flex justify-content-between border-bottom py-2 gap-2"><span>{{ $item->variant?->product?->name }} · Size {{ $item->variant?->size ?? '—' }}</span><strong class="text-nowrap">{{ number_format($item->quantity) }} · {{ number_format((float)$item->weight_kg,3,',','.') }} kg</strong></div>@endforeach<div class="text-end fw-bold text-primary pt-2">Tổng KL nhận: {{ number_format((float)$transfer->items->sum('weight_kg'),3,',','.') }} kg</div></div>@if($transfer->status==='pending_receive')<div class="card-footer bg-white text-end"><form method="POST" action="{{ route('package.incoming-inventory.confirm',$transfer) }}" onsubmit="return confirm('Xác nhận nhập kho phiếu này?')">@csrf<button class="btn btn-success btn-sm">Xác nhận nhập kho</button></form></div>@endif</div></div>
@empty <div class="text-muted text-center py-5">Không có phiếu hàng điều chuyển.</div> @endforelse
</div></div>
</div>
@endsection
