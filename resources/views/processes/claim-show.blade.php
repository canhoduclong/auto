@extends($layout)
@section('title','Chi phí ship #'.$claim->id)
@include('processes.styles')
@section('content')
<div class="container process-page"><div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><h1>Chi phí ship #{{ $claim->id }}</h1><div class="text-muted">Shipper: {{ $claim->shipper?->name }} · Quy trình #{{ $claim->run->definition_id }}, phiên bản {{ $claim->run->definition_version }}</div></div><span class="process-status align-self-start">{{ $claim->run->statusLabel() }}</span></div>
@include('processes.messages')
<div class="process-card"><h2>Tiến trình xử lý yêu cầu</h2>@include('processes.shipping-timeline')</div>
@php $canRevise=$claim->run->status==='revision' && (int)$claim->shipper_id===(int)auth()->id(); @endphp
<form method="POST" action="{{ route('shipping-expenses.revise',$claim) }}" class="process-card" enctype="multipart/form-data">@csrf
<h2>Chi phí các đơn</h2><div class="table-responsive"><table class="process-table"><thead><tr><th>STT</th><th>Khách hàng / Đơn hàng</th><th>Ngày giao</th><th>Điều phối giao</th><th>Chi phí đề nghị</th><th>Diễn giải</th></tr></thead><tbody>@foreach($claim->items as $item)<tr><td>{{ $loop->iteration }}<input type="hidden" name="items[{{ $loop->index }}][order_id]" value="{{ $item->order_id }}"></td><td>
@include('processes.shipping-order-identity', ['order' => $item->order])
</td><td class="text-nowrap">{{ $item->order?->delivered_at?->format('d/m/Y H:i') ?? '—' }}</td><td>{{ $item->dispatch?->creator?->name }}<div class="small text-muted">Lịch sử #{{ $item->dispatch_id }}</div></td><td>@if($canRevise)<input type="number" min="0" max="1000000000" step="1" class="form-control" name="items[{{ $loop->index }}][amount]" value="{{ old('items.'.$loop->index.'.amount',$item->amount) }}" required>@else{{ number_format($item->amount,0,',','.') }}đ@endif</td><td>@if($canRevise)<input class="form-control" name="items[{{ $loop->index }}][note]" value="{{ $item->note }}" maxlength="1000">@else{{ $item->note }}@endif</td></tr>@endforeach</tbody><tfoot><tr><td colspan="4" class="text-end fw-bold">Tổng chi phí</td><td class="fw-bold">{{ number_format($claim->total,0,',','.') }}đ</td><td></td></tr></tfoot></table></div>
@if($canRevise)<label class="form-label mt-3">Nội dung điều chỉnh</label><textarea class="form-control mb-3" name="note" maxlength="2000" rows="3">{{ old('note',$claim->note) }}</textarea><input type="file" class="form-control mb-3" name="attachments[]" multiple><button class="btn btn-primary">Gửi lại cho {{ $claim->run->configuration['steps'][$claim->run->resume_step]['name'] }}</button>@elseif($claim->note)<div class="mt-3" style="white-space:pre-wrap">{{ $claim->note }}</div>@endif
</form>
@if($canHandle)@include('processes.action-form',['run'=>$claim->run])@endif
@if((int)$claim->shipper_id===(int)auth()->id() && $claim->run->status==='confirmed' && !$claim->payment_transaction_id)<form method="POST" class="process-card" action="{{ route('shipping-expenses.payment',$claim) }}">@csrf<h2>Yêu cầu thanh toán</h2><p>Chi phí đã được kế toán xác nhận. Gửi phiếu thanh toán theo số liệu đã chốt.</p><button class="btn btn-primary">Gửi yêu cầu thanh toán</button></form>@endif
@if($claim->payment_transaction_id)<div class="process-card">Đã gửi phiếu thanh toán #{{ $claim->payment_transaction_id }} · Trạng thái: {{ $claim->payment?->status }}. Theo dõi trong Phiếu yêu cầu.</div>@endif
@include('processes.event-history',['run'=>$claim->run])
<a class="btn btn-outline-secondary" href="{{ route('shipping-expenses.index') }}">Danh sách yêu cầu</a>
</div>
@endsection
