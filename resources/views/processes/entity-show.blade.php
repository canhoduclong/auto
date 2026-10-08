@extends($layout)
@section('title','Hồ sơ quy trình #'.$run->id)
@include('processes.styles')
@section('content')
<div class="container process-page"><div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><h1>Hồ sơ #{{ $run->id }}</h1><span class="process-status">{{ $run->statusLabel() }}</span></div>
@include('processes.messages')
<div class="process-card"><h2>{{ \App\Support\ProcessActivities::all()[$run->activity]['label'] ?? $run->activity }}</h2><p class="text-muted">{{ $run->initiator?->name }} · Phiên bản {{ $run->definition_version }}</p>@foreach($run->events->last()?->snapshot['subjects']??[] as $subject)<div>{{ $subject['subject_type']==='order'?'Đơn hàng':'Sản phẩm' }}: <strong>{{ $subject['label']??('#'.$subject['subject_id']) }}</strong>@if(isset($subject['customer'])) · {{ $subject['customer'] }} · {{ number_format($subject['total']??0,0,',','.') }}đ@endif</div>@endforeach<p style="white-space:pre-wrap" class="mt-3">{{ $run->request_note }}</p><div class="process-steps">@foreach($run->configuration['steps'] as $step)<span class="process-status">{{ $loop->iteration }}. {{ $step['name'] }}</span>@endforeach</div></div>
@if($canHandle)@include('processes.action-form')@endif
@if($run->status==='revision' && (int)$run->initiator_id===(int)auth()->id())<form class="process-card" method="POST" enctype="multipart/form-data" action="{{ route('process-inbox.revise',$run) }}">@csrf<h2>Bổ sung hồ sơ</h2><label class="form-label">Nội dung bổ sung</label><textarea name="note" class="form-control mb-3" rows="5" maxlength="5000" required>{{ old('note',$run->request_note) }}</textarea><input type="file" class="form-control mb-3" name="attachments[]" multiple><button class="btn btn-primary">Gửi lại cho {{ $run->configuration['steps'][$run->resume_step]['name'] }}</button></form>@endif
@include('processes.event-history')
<a href="{{ route('process-inbox.index') }}" class="btn btn-outline-secondary">Về Cần xử lý</a></div>
@endsection
