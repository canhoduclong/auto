<div class="task-activity d-flex flex-wrap align-items-start justify-content-between gap-2">
    <div class="flex-grow-1"><span class="text-muted">{{ $activity->created_at?->format('d/m/Y H:i') }} · {{ $activity->changedBy?->name ?? 'Hệ thống' }}</span> — {{ $activity->reason ?: (\App\Models\TaskAssignment::STATUS_LABELS[$activity->to_status] ?? $activity->to_status) }}</div>
    @if($activity->documents->isNotEmpty())
    <div class="d-flex flex-column gap-1">
        @foreach($activity->documents as $activityDocument)
            <a class="small" href="{{ $activityDocument->getImageUrl() }}" target="_blank" rel="noopener" title="{{ $activityDocument->original_filename }}">Mở / tải tài liệu {{ $activity->documents->count()>1 ? $loop->iteration : '' }} · {{ $activityDocument->original_filename ?: 'Tài liệu' }}</a>
        @endforeach
    </div>
    @endif
</div>
