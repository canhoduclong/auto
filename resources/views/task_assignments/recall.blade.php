@extends($layout)
@section('title', 'Thu hồi công việc con')
@section('content')
<div class="container py-4" style="max-width:800px">
    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Thu hồi công việc con</div>
        <div class="card-body">
            <p class="text-muted">Công việc cha: {{ $taskAssignment->title }}</p>
            <h5>{{ $child->title }}</h5>
            @if(in_array($child->status, ['done', 'cancelled'], true))
                <div class="alert alert-secondary">Công việc đã hoàn thành hoặc đã thu hồi / hủy.</div>
            @else
                <p>Thu hồi việc này và các việc con bên dưới chưa hoàn thành. Lịch sử và tài liệu được giữ lại.</p>
                <form method="POST" action="{{ route('tasks.subtasks.recall', [$taskAssignment, $child]) }}">
                    @csrf
                    <label for="recall_reason" class="form-label">Lý do thu hồi *</label>
                    <textarea id="recall_reason" name="recall_reason" class="form-control mb-2" rows="3" required maxlength="1000">{{ old('recall_reason') }}</textarea>
                    @error('recall_reason')<div class="text-danger mb-2">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-danger">Xác nhận thu hồi</button>
                </form>
            @endif
            <a href="{{ route('tasks.show', $taskAssignment) }}" class="btn btn-outline-secondary mt-3">Quay lại công việc</a>
        </div>
    </div>
</div>
@endsection
