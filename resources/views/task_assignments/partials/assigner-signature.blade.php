<div class="task-assigner-signature">
    <span class="task-assigner-label">NGƯỜI GIAO VIỆC</span>
    @if(filled($task->assigner?->job_title))
        <span class="task-assigner-title">{{ $task->assigner->job_title }}</span>
    @endif
    <strong class="task-assigner-name">{{ $task->assigner?->name ?? '—' }}</strong>
</div>
