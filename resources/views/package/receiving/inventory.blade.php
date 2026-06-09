@extends('layouts.package')
@section('title', 'Tiếp nhận hàng điều chuyển')
@section('content')
<div class="d-flex justify-content-between mb-3"><h5 class="fw-bold">Phiếu điều chuyển tồn kho</h5><span class="badge bg-warning text-dark">{{ $transfers->where('status','pending_receive')->count() }} chờ nhận</span></div>
<div class="row g-3">
@forelse($transfers as $transfer)
<div class="col-12 col-lg-6"><div class="card border-0 shadow-sm h-100">
<div class="card-header bg-white d-flex justify-content-between"><div><strong>{{ $transfer->transfer_code }}</strong><div class="small text-muted">Từ {{ $transfer->sourceWarehouse?->name ?? '—' }}</div></div><span class="badge {{ $transfer->status==='pending_receive'?'bg-warning text-dark':'bg-success' }}">{{ $transfer->status==='pending_receive'?'Chờ tiếp nhận':'Đã tiếp nhận' }}</span></div>
<div class="card-body">@foreach($transfer->items as $item)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $item->variant?->product?->name }} - {{ $item->variant?->name }}</span><strong>{{ number_format($item->quantity) }}</strong></div>@endforeach</div>
@if($transfer->status==='pending_receive')<div class="card-footer bg-white text-end"><form method="POST" action="{{ route('package.incoming-inventory.confirm',$transfer) }}" onsubmit="return confirm('Xác nhận nhập kho phiếu này?')">@csrf<button class="btn btn-success btn-sm">Xác nhận nhập kho</button></form></div>@endif
</div></div>
@empty <div class="text-muted text-center py-5">Không có phiếu điều chuyển.</div> @endforelse
</div><div class="mt-3">{{ $transfers->links() }}</div>
@endsection
