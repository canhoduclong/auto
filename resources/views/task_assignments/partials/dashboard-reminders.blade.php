@php
$reminderTasks = \App\Models\TaskAssignment::whereHas('assignees', fn($q) => $q->where('user_id', auth()->id()))->whereNotIn('status', ['done','cancelled'])->where(fn($q) => $q->whereIn('priority', ['high','urgent'])->orWhere('task_type','debt_collection'))->with('assignees')->orderBy('due_date')->get();
@endphp
@if($reminderTasks->isNotEmpty())
<section class="card mb-3"><div class="card-header fw-semibold">Nhắc việc hằng ngày · {{ $reminderTasks->count() }} nhiệm vụ</div><div class="card-body">
@foreach($reminderTasks as $reminder)
<div class="border-bottom py-2"><a class="fw-semibold" href="{{ route('tasks.show', $reminder) }}">{{ $reminder->title }}</a><div class="small {{ $reminder->isOverdue() ? 'text-danger' : 'text-muted' }}">Hạn: {{ $reminder->due_date?->format('d/m/Y') ?? 'Chưa đặt' }} · {{ \App\Models\TaskAssignment::STATUS_LABELS[$reminder->status] ?? $reminder->status }}</div>
@if($reminder->task_type === 'debt_collection')
@php $debts = collect($reminder->debt_items ?? []); $total = $debts->sum('target'); @endphp
<div class="small">Thu nợ: {{ $total > 0 ? round($debts->sum('collected') / $total * 100, 1) : 0 }}% · {{ number_format($debts->sum('collected'),0,',','.') }} / {{ number_format($total,0,',','.') }} đ</div>
@foreach($debts as $debt)<div class="small text-muted">{{ $debt['customer_name'] }} · {{ $debt['sale_name'] }} · {{ round($debt['collected'] / max(1,$debt['target']) * 100,1) }}% {{ $debt['collected'] >= $debt['target'] ? '(đạt mục tiêu)' : '(đang thực hiện)' }}</div>@endforeach
@else
<div class="small">{{ $reminder->assignees->whereIn('status',['completed','done'])->count() }}/{{ $reminder->assignees->count() }} người đã báo hoàn thành</div>
@endif</div>
@endforeach
</div></section>
@endif
