@extends('layouts.package')
@section('title', 'Cân và tiếp nhận đơn trả')
@section('content')
<form method="POST" action="{{ route('package.incoming-returns.confirm',$orderReturn) }}">@csrf
<div class="card border-0 shadow-sm"><div class="card-header bg-white"><strong>Đơn {{ $orderReturn->order?->code }}</strong> · {{ $orderReturn->order?->customer?->name }}</div>
<div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Sản phẩm</th><th>SL</th><th>KL gốc</th><th>KL thực nhận</th></tr></thead><tbody>
@foreach($orderReturn->returnItems as $item)<tr><td>{{ $item->productVariant?->product?->name }} - {{ $item->productVariant?->name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->original_weight,3) }} kg</td><td><input type="hidden" name="item_weights[{{ $loop->index }}][item_id]" value="{{ $item->id }}"><input class="form-control" type="number" step="0.001" min="0" required name="item_weights[{{ $loop->index }}][received_weight]" value="{{ $item->received_weight }}"></td></tr>@endforeach
</tbody></table></div><div class="card-footer bg-white d-flex justify-content-between"><a href="{{ route('package.incoming-returns') }}" class="btn btn-outline-secondary">Quay lại</a><button class="btn btn-success">Xác nhận trả hàng và nhập kho</button></div></div>
</form>
@endsection
