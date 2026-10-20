@php
    $actor = auth()->user();
    $returnLayout = $actor->hasRole(['account','accountant','accounting']) ? 'layouts.accounting' : ($actor->hasRole(['shipper','ship','manager_shipper']) ? 'layouts.shipper' : ($actor->hasRole('warehouse') ? 'layouts.warehouse' : 'layouts.site'));
    $labels = ['sale_requested'=>'Chờ shipper xác nhận', 'sale_in_transit'=>'Chờ kho nhận', 'sale_accounting_pending'=>'Kho đã nhận · Chờ kế toán duyệt', 'warehouse_received'=>'Kế toán đã duyệt'];
@endphp
@extends($returnLayout)
@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Hoàn trả từ sale</h1>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @forelse($returns as $return)
    <div class="card mb-3"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2"><strong>Phiếu #{{ $return->id }} · Đơn {{ $return->order?->code }} · {{ $return->order?->customer?->name }}</strong><span class="badge bg-info text-dark">{{ $labels[$return->status] ?? $return->status }}</span></div>
        <p class="my-2">Shipper: {{ $return->assignedShipper?->name }} · Kho nhận: {{ $return->warehouse?->name }}<br>Lý do: {{ $return->reason }}</p>
        @if($return->evidence_image_path)<a href="{{ asset('storage/'.$return->evidence_image_path) }}" target="_blank" rel="noopener">Xem ảnh hàng trả</a>@endif
        <div class="small text-muted my-2">Shipper xác nhận: {{ $return->ship_confirmed_at?->format('d/m/Y H:i:s') ?? '—' }} · Kho nhận: {{ $return->warehouse_confirmed_at?->format('d/m/Y H:i:s') ?? '—' }} · Kế toán duyệt: {{ $return->accounting_confirmed_at?->format('d/m/Y H:i:s') ?? '—' }}</div>
        @php $canReceive = $return->status === 'sale_in_transit' && ($actor->hasRole('admin') || ($actor->hasRole('warehouse') && $actor->warehouse_id && (int)$actor->warehouse_id === (int)$return->warehouse_id)); @endphp
        <form action="{{ route('sale-returns.receive', $return) }}" method="POST">@csrf
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Sản phẩm</th><th>SL trả</th><th>Kg thực nhận</th></tr></thead><tbody>
        @foreach($return->returnItems as $item)<tr><td>{{ $item->productVariant?->product?->name }} {{ $item->productVariant?->name }}</td><td>{{ $item->quantity }}</td><td>@if($canReceive && $item->original_weight !== null)<input class="form-control form-control-sm" type="number" name="weights[{{ $item->id }}]" min="0" step="0.001" required value="{{ old('weights.'.$item->id) }}">@else{{ $item->received_weight ?? '—' }}@endif</td></tr>@endforeach
        </tbody></table></div>
        @if($canReceive)<button class="btn btn-success">Xác nhận kho đã nhận · Chuyển kế toán</button>@endif
        </form>
        @if($return->status === 'sale_requested' && ($actor->hasRole('admin') || (int)$actor->id === (int)$return->assigned_shipper_id || ($actor->hasRole('manager_shipper') && \App\Models\Setting::enabled('shipper_auto_complete_'.$return->assigned_shipper_id))))
        <form method="POST" action="{{ route('sale-returns.ship', $return) }}">@csrf<button class="btn btn-primary">Xác nhận hoàn trả về kho</button></form>
        @endif
        @if($return->warehouse_confirmed_at)<p class="mt-2 mb-1">Giá trị hoàn trả {{ $return->status === 'sale_accounting_pending' ? 'chờ duyệt' : 'đã duyệt' }}: <strong>{{ number_format((float)$return->refund_amount,0,',','.') }}đ</strong></p>@endif
        @if($return->status === 'sale_accounting_pending' && $actor->hasRole(['admin','account','accountant','accounting']))
        <form method="POST" action="{{ route('sale-returns.approve', $return) }}" onsubmit="return confirm('Duyệt hoàn trả và cập nhật đối soát/công nợ?')">@csrf<button class="btn btn-success">Kế toán duyệt hoàn trả</button></form>
        @endif
    </div></div>
    @empty<div class="alert alert-info">Chưa có yêu cầu hoàn trả.</div>@endforelse
    {{ $returns->links() }}
</div>
@endsection
