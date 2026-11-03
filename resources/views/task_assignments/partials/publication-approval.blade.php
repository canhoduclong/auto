@if($task->awaitsPublication())
<div class="card border-warning mb-3">
    <div class="card-body">
        <h5>{{ $task->publication_status === 'rejected' ? 'Bản gửi duyệt cần chỉnh sửa' : 'Bản gửi duyệt — chưa công bố' }}</h5>
        <p class="mb-2">Người duyệt công bố: <strong>{{ $task->publicationApprover?->name }}</strong>. Công việc chưa được gửi đến người chủ trì và những người nhận đã chọn.</p>
        <p class="mb-3">Người nhận sau khi công bố: {{ $publicationRecipients->pluck('name')->join(', ') }}</p>
        @if($task->publication_status === 'rejected')
            <p class="text-danger">{{ $task->statusLogs->sortByDesc('id')->first(fn ($log) => str_starts_with($log->reason ?? '', 'Yêu cầu chỉnh sửa trước khi công bố:'))?->reason }}</p>
        @endif
        @if($task->status === 'draft' && $task->publication_status === 'pending' && (int)$task->publication_approver_id === (int)auth()->id())
            <form action="{{ route('tasks.publication-decision', $task) }}" method="POST" class="mb-3">
                @csrf
                <input type="hidden" name="decision" value="approve">
                <button type="submit" class="btn btn-primary">Duyệt và công bố công việc</button>
            </form>
            <form action="{{ route('tasks.publication-decision', $task) }}" method="POST">
                @csrf
                <input type="hidden" name="decision" value="reject">
                <label for="publication-reason" class="form-label">Nội dung cần chỉnh sửa</label>
                <textarea id="publication-reason" name="reason" class="form-control mb-2" required maxlength="2000" rows="2">{{ old('reason') }}</textarea>
                @error('reason')<div class="text-danger mb-2">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn-outline-warning">Yêu cầu chỉnh sửa</button>
            </form>
        @elseif($task->status === 'draft' && $task->publication_status === 'rejected' && (int)$task->created_by === (int)auth()->id())
            <form action="{{ route('tasks.resubmit-publication', $task) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">Gửi duyệt lại</button>
            </form>
        @endif
    </div>
</div>
@endif
