@php
    $personStatuses = ['pending' => 'Chờ thực hiện', 'in_progress' => 'Đang thực hiện', 'processing' => 'Đang thực hiện', 'completed' => 'Đã báo hoàn thành', 'done' => 'Đã hoàn thành', 'rejected' => 'Không thể thực hiện'];
    $delegations = $task->subTasks->sortBy('due_date')->flatMap(fn ($child) => $child->assignees->map(fn ($assignment) => ['task' => $child, 'assignment' => $assignment]))->groupBy(fn ($entry) => $entry['task']->created_by . ':' . $entry['assignment']->user_id);
@endphp
<section class="card shadow-sm mb-3">
    <div class="card-header fw-semibold">Tiến độ công việc</div>
    <div class="card-body">
        @php
            $milestones = collect([
                ['label' => 'Bắt đầu', 'date' => $task->created_at, 'class' => 'text-success'],
                ['label' => 'Hôm nay', 'date' => now(), 'class' => 'text-primary'],
                ['label' => 'Hạn hoàn thành', 'date' => $task->due_date, 'class' => $task->isOverdue() ? 'text-danger' : 'text-warning'],
            ]);
            if ($task->completed_at) $milestones->push(['label' => 'Báo hoàn thành', 'date' => $task->completed_at, 'class' => 'text-success']);
            $milestones = $milestones->sortBy(fn ($point) => $point['date']?->timestamp ?? PHP_INT_MAX);
        @endphp
        <div class="task-milestones" aria-label="Các mốc thời gian theo thứ tự ngày">
            @foreach($milestones as $point)
                <div class="task-milestone {{ $point['class'] }}"><span class="task-milestone-dot" aria-hidden="true"></span><strong>{{ $point['label'] }}</strong><br><span class="text-muted">{{ $point['date']?->format('d/m/Y') ?? 'Chưa đặt' }}</span></div>
            @endforeach
        </div>
        <div class="task-person-columns small text-muted bg-light p-2"><span>Người thực hiện & tiến độ</span><span>Đánh giá</span><span>Trạng thái</span></div>
        @foreach($task->assignees as $assignment)
            <article class="task-person">
                <div class="task-person-head task-person-columns">
                    <strong>{{ $loop->iteration }}. {{ $assignment->user?->name ?? 'Người dùng đã xóa' }}</strong>
                    @include('task_assignments.partials.evaluation', ['ratedTask' => $task, 'ratedAssignment' => $assignment, 'compactEvaluation' => false])
                    <span class="badge bg-{{ $assignment->statusColor() }}">{{ $personStatuses[$assignment->status] ?? $assignment->status }}</span>
                </div>
                <details class="mt-2">
                    <summary class="small">Chi tiết hoạt động</summary>
                    @forelse($task->statusLogs->where('changed_by', $assignment->user_id)->sortBy('created_at') as $activity)
                        @include('task_assignments.partials.activity')
                    @empty
                        <p class="small text-muted mt-2">Chưa có cập nhật hoạt động.</p>
                    @endforelse
                    @if($assignment->note)<div class="small">Ghi chú hiện tại: {{ $assignment->note }}</div>@endif
                </details>
            </article>
        @endforeach
        @foreach($delegations as $entries)
            @php $recipient = $entries->first()['assignment']; $delegator = $entries->first()['task']->creator; @endphp
            <article class="task-person">
                <div class="task-person-head"><strong>{{ $recipient->user?->name ?? 'Người dùng đã xóa' }}</strong><span class="small text-muted">Được giao bởi <strong>{{ $delegator?->name ?? 'Không xác định' }}</strong> · {{ $entries->count() }} việc con</span></div>
                <details class="mt-2" open>
                    <summary class="small">Chi tiết công việc và hoạt động</summary>
                    @foreach($entries as $entry)
                        @php $child = $entry['task']; $childAssignment = $entry['assignment']; @endphp
                        <div class="border-start ps-3 mt-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <a class="fw-semibold" href="{{ route('tasks.show', $child) }}">{{ $child->title }}</a>
                                <span class="badge bg-{{ $child->statusColor() }}">{{ \App\Models\TaskAssignment::STATUS_LABELS[$child->status] ?? $child->status }}</span>
                            </div>
                            <div class="small text-muted mt-1">Tạo: {{ $child->created_at?->format('d/m/Y') }} · Hạn: {{ $child->due_date?->format('d/m/Y H:i') ?? 'Chưa đặt' }}
                                @if($child->due_date && !in_array($child->status, ['done', 'completed', 'cancelled'], true))<span class="{{ $child->isOverdue() ? 'text-danger' : '' }}"> · {{ $child->isOverdue() ? 'Quá hạn' : 'Còn hạn' }}: {{ $child->due_date->diffForHumans() }}</span>@endif
                            </div>
                            @include('task_assignments.partials.evaluation', ['ratedTask' => $child, 'ratedAssignment' => $childAssignment, 'compactEvaluation' => false])
                            @foreach($child->statusLogs->where('changed_by', $childAssignment->user_id)->sortBy('created_at') as $activity)
                                @include('task_assignments.partials.activity')
                            @endforeach
                            @if($child->completion_content)<p class="small mt-2" style="white-space:pre-wrap">{{ $child->completion_content }}</p>@endif
                            @if((int) $childAssignment->user_id === (int) auth()->id() && in_array($child->status, ['pending','processing','rejected'], true))<a class="small" href="{{ route('tasks.complete-form', $child) }}">Báo hoàn thành / tài liệu</a>@endif
                        </div>
                    @endforeach
                </details>
            </article>
        @endforeach
        @if($task->assignees->isEmpty() && $delegations->isEmpty())<p class="text-muted mt-3">Chưa có người nhận việc.</p>@endif
    </div>
    <div class="card-footer small text-muted">{{ $task->subTasks->whereIn('status', ['completed', 'done'])->count() }}/{{ $task->subTasks->count() }} việc con đã báo hoàn thành.</div>
</section>
