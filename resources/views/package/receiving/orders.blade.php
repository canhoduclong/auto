@extends('layouts.package')
@section('title', 'Tiếp nhận đơn điều chuyển')
@section('content')
@php
    $pending = $transfers->where('status', 'delivered_waiting_receive');
    $received = $transfers->where('status', 'received_completed');
@endphp
<div class="row g-4">
@foreach([['title'=>'Cần tiếp nhận','items'=>$pending,'color'=>'warning'],['title'=>'Đã tiếp nhận','items'=>$received,'color'=>'success']] as $group)
<div class="col-12 col-lg-6">
    <div class="d-flex justify-content-between mb-3"><h5 class="fw-bold">{{ $group['title'] }}</h5><span class="badge bg-{{ $group['color'] }}">{{ $group['items']->count() }} đơn</span></div>
    @forelse($group['items'] as $transfer)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between"><div><strong>{{ $transfer->order?->customer?->name ?? 'Khách hàng' }}</strong><div class="small text-muted">{{ $transfer->order?->code }} · từ {{ $transfer->sourceWarehouse?->name ?? '—' }}</div></div><span class="badge bg-{{ $group['color'] }}">{{ $group['title'] }}</span></div>
        <div class="card-body">
            @foreach($transfer->order?->items ?? [] as $item)
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}</span><strong>{{ number_format((float)$item->quantity) }}</strong></div>
            @endforeach
        </div>
        @if($transfer->status === 'delivered_waiting_receive')
        <div class="card-footer bg-white text-end"><form method="POST" action="{{ route('package.incoming-orders.confirm',$transfer) }}" onsubmit="return confirm('Xác nhận tiếp nhận đơn và nhập kho?')">@csrf<button class="btn btn-success btn-sm">Xác nhận nhận vào kho</button></form></div>
        @endif
    </div>
    @empty <div class="text-muted text-center py-4">Không có dữ liệu.</div> @endforelse
</div>
@endforeach
</div>
@endsection
