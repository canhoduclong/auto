<div class="small mt-2">
    @if((int) $ratedTask->created_by === (int) auth()->id())
        <form method="POST" action="{{ route('tasks.assignees.evaluate', [$ratedTask, $ratedAssignment]) }}" class="d-flex align-items-center gap-2 flex-wrap">
            @csrf
            <label for="evaluation-{{ $ratedAssignment->id }}">Đánh giá {{ $ratedAssignment->user?->name }}:</label>
            <input id="evaluation-{{ $ratedAssignment->id }}" name="evaluation_score" type="number" min="0" max="100" step="1" required value="{{ $ratedAssignment->evaluation_score }}" placeholder="0–100" class="form-control form-control-sm" style="width:85px">
            <span>/100</span>
            <button class="btn btn-sm btn-outline-primary" type="submit">Lưu điểm</button>
        </form>
    @else
        <span class="text-muted">Đánh giá {{ $ratedAssignment->user?->name }}:</span>
        <strong>{{ $ratedAssignment->evaluation_score !== null ? $ratedAssignment->evaluation_score . '/100' : 'Chưa đánh giá' }}</strong>
    @endif
</div>
