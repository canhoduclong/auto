@extends('layouts.package')
@section('title', 'Tiếp nhận đơn trả về')
@section('content')
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>Mã đơn</th><th>Khách hàng</th><th>Lý do</th><th>Sản phẩm</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
<tbody>@forelse($returns as $return)<tr>
<td class="fw-bold">{{ $return->order?->code ?? '—' }}</td><td>{{ $return->order?->customer?->name ?? '—' }}</td><td>{{ $return->reason ?? '—' }}</td><td>{{ $return->returnItems->sum('quantity') }} sp</td>
<td><span class="badge {{ $return->status==='warehouse_received'?'bg-success':'bg-warning text-dark' }}">{{ $return->status==='warehouse_received'?'Đã tiếp nhận':'Chờ tiếp nhận' }}</span></td>
<td class="text-end">@if($return->status!=='warehouse_received')<a class="btn btn-success btn-sm" href="{{ route('package.incoming-returns.receive',$return) }}">Cân và xác nhận</a>@else<span class="text-muted small">Đã xử lý</span>@endif</td>
</tr>@empty<tr><td colspan="6" class="text-center text-muted py-5">Không có đơn trả về.</td></tr>@endforelse</tbody>
</table></div></div>
@endsection
