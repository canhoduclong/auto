@extends($layout)
@section('title', 'Mở đề xuất biểu quyết')
@section('content')
<div class="container py-3" style="max-width:1000px"><h4>Mở đề xuất biểu quyết</h4>@include('operating.messages')
<form method="POST" action="{{ route('operating.proposals.store') }}" class="card card-body">@csrf
<label class="form-label">Tiêu đề *</label><input class="form-control mb-3" name="title" required maxlength="255" value="{{ old('title') }}">
<label class="form-label">Nội dung cần quyết định *</label><textarea class="task-description-editor form-control" name="description" maxlength="20000">{{ \App\Support\TaskDescription::render(old('description')) }}</textarea>
<label class="form-label mt-3">Người tham gia biểu quyết *</label><div class="border rounded p-3 mb-3" style="max-height:240px;overflow:auto">@foreach($users as $user)<label class="d-block"><input type="checkbox" name="voter_ids[]" value="{{ $user->id }}" @checked(in_array($user->id,old('voter_ids',[])))> {{ $user->name }}</label>@endforeach</div>
<div class="row g-3"><div class="col-md-4"><label class="form-label">Hạn bỏ phiếu *</label><input type="datetime-local" name="closes_at" required class="form-control" value="{{ old('closes_at') }}"></div><div class="col-md-4"><label class="form-label">Số phiếu tối thiểu *</label><input type="number" min="1" name="quorum" required class="form-control" value="{{ old('quorum',1) }}"></div><div class="col-md-4"><label class="form-label">Tỷ lệ đồng ý tối thiểu (%) *</label><input type="number" min="1" max="100" name="approval_percent" required class="form-control" value="{{ old('approval_percent',51) }}"></div></div>
<p class="small text-muted mt-3">Tỷ lệ đồng ý tính trên toàn bộ người được mời. Phiếu “Không ý kiến” tính vào số phiếu tham gia, không tính đồng ý. Người chưa bỏ phiếu không được tính đồng ý. Nội dung, thành viên và quy tắc được giữ nguyên sau khi mở.</p>
<div><button class="btn btn-primary" type="submit">Mở biểu quyết</button> <a class="btn btn-outline-secondary" href="{{ route('operating.index') }}">Quay lại</a></div>
</form></div>
@endsection
@include('task_assignments.partials.description-editor')
