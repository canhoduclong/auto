@php $evaluationScore = $ratedAssignment->evaluation_score ?? 0; @endphp
<div class="evaluation-widget {{ !empty($compactEvaluation) ? 'evaluation-compact' : 'small mt-2' }}">
    @if((int) $ratedTask->created_by === (int) auth()->id())
        <details class="evaluation-picker">
            <summary aria-label="Đánh giá {{ $ratedAssignment->user?->name }}: {{ $evaluationScore }}%">
                @if(empty($compactEvaluation))<span>Đánh giá {{ $ratedAssignment->user?->name }}: </span>@endif
                <span class="evaluation-value">{{ $evaluationScore }}%</span>
            </summary>
            <form method="POST" action="{{ route('tasks.assignees.evaluate', [$ratedTask, $ratedAssignment]) }}" class="evaluation-options">
                @csrf
                <div class="evaluation-nodes" aria-label="Chọn điểm đánh giá cho {{ $ratedAssignment->user?->name }}">
                    @foreach(range(0, 100, 10) as $score)
                        <button type="submit" name="evaluation_score" value="{{ $score }}" class="evaluation-node {{ $ratedAssignment->evaluation_score !== null && $evaluationScore === $score ? 'is-selected' : '' }}" aria-label="Đánh giá {{ $score }} phần trăm" aria-pressed="{{ $ratedAssignment->evaluation_score !== null && $evaluationScore === $score ? 'true' : 'false' }}">{{ $score }}<span aria-hidden="true"></span></button>
                    @endforeach
                </div>
            </form>
        </details>
    @else
        <span class="evaluation-readonly">@if(empty($compactEvaluation))Đánh giá {{ $ratedAssignment->user?->name }}: @endif{{ $evaluationScore }}%</span>
    @endif
</div>
