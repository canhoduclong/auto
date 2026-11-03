<div class="task-assigner-signature">
    <strong class="task-assigner-name">{{ $task->assigner?->name ?? '—' }}</strong>
    @if(filled($task->assigner?->job_title))
        <span class="task-assigner-title">{{ $task->assigner->job_title }}</span>
    @endif
</div>
