@extends($layout)
@section('title','Gửi hồ sơ xét duyệt')
@include('processes.styles')
@section('content')
<div class="container process-page"><h1>Gửi hồ sơ xét duyệt</h1>@include('processes.messages')<form class="process-card" method="POST" enctype="multipart/form-data" action="{{ route('process-inbox.submit',$definition) }}">@csrf<input type="hidden" name="subject_id" value="{{ $entity->id }}"><h2>{{ $definition->name }}</h2><p>{{ $entity->code ?? $entity->name }} · Phiên bản {{ $definition->version }}</p><label class="form-label">Nội dung đề nghị xét duyệt</label><textarea name="note" rows="5" class="form-control mb-3" maxlength="5000" required>{{ old('note') }}</textarea><label class="form-label">Tài liệu đính kèm</label><input name="attachments[]" type="file" multiple class="form-control mb-2"><p class="small text-muted">Tối đa 10 tệp, 20MB/tệp.</p><button class="btn btn-primary">Gửi yêu cầu</button></form></div>
@endsection
