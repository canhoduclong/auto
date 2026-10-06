@php
    $reminderTasks = \App\Models\TaskAssignment::whereHas('assignees', fn($q) => $q->where('user_id', auth()->id()))
        ->whereNotIn('status', ['done','cancelled'])
        ->where(fn($q) => $q->whereIn('priority', ['high','urgent'])->orWhere('task_type','debt_collection'))
        ->with('assignees')->orderBy('due_date')->get();
@endphp
@if($reminderTasks->isNotEmpty())
@once
<style>
.work-reminders { margin-top: 24px; overflow: hidden; border: 1px solid #dce5ee; border-radius: 12px; background: #fff; box-shadow: 0 3px 12px rgba(15, 23, 42, .04); }
.work-reminders-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 16px 18px; background: #f4f7fb; border-bottom: 1px solid #e5ebf2; }
.work-reminders-head h2 { margin: 0; color: #173b61; font-size: 1rem; font-weight: 750; }
.work-reminders-count { padding: 4px 10px; border-radius: 20px; background: #e0edfa; color: #185a92; font-size: .8rem; white-space: nowrap; }
.work-reminder { padding: 16px 18px; }
.work-reminder + .work-reminder { border-top: 1px solid #edf1f5; }
.work-reminder-top { display: flex; justify-content: space-between; align-items: start; gap: 12px; }
.work-reminder-title { color: #173b61; font-weight: 700; text-decoration: none; overflow-wrap: anywhere; }
.work-reminder-title:hover { color: #0879bd; text-decoration: underline; }
.work-reminder-status { padding: 3px 8px; border-radius: 6px; font-size: .75rem; background: #e8f1fc; color: #23528c; }
.work-reminder-status.is-waiting { background: #fff3d2; color: #956400; }
.work-reminder-meta { display: flex; flex-wrap: wrap; gap: 8px 16px; margin-top: 8px; color: #64748b; font-size: .8rem; }
.work-reminder-progress { height: 5px; margin-top: 10px; overflow: hidden; border-radius: 8px; background: #edf2f7; }
.work-reminder-progress span { display: block; height: 100%; background: #168b93; border-radius: inherit; }
.work-reminder-debts { margin-top: 10px; color: #64748b; font-size: .8rem; }
@media (max-width: 575px) { .work-reminder-top { flex-direction: column; gap: 6px; } }
</style>
@endonce
<section class="work-reminders mb-3" aria-label="Giao việc và nhắc việc">
    <div class="work-reminders-head">
        <h2><i class="bi bi-list-check me-2"></i>Giao việc &amp; nhắc việc</h2>
        <span class="work-reminders-count">{{ $reminderTasks->count() }} nhiệm vụ</span>
    </div>
    @foreach($reminderTasks as $reminder)
    @php
        $completed = $reminder->assignees->whereIn('status',['completed','done'])->count();
        $totalAssignees = $reminder->assignees->count();
        $debts = collect($reminder->debt_items ?? []);
        $debtTarget = (float)$debts->sum('target');
        $isDebt = $reminder->task_type === 'debt_collection';
        $progress = $isDebt ? ($debtTarget > 0 ? $debts->sum('collected') / $debtTarget * 100 : 0) : ($totalAssignees > 0 ? $completed / $totalAssignees * 100 : 0);
        $statusLabel = \App\Models\TaskAssignment::STATUS_LABELS[$reminder->status] ?? ['in_progress'=>'Đang thực hiện'][$reminder->status] ?? $reminder->status;
    @endphp
    <article class="work-reminder">
        <div class="work-reminder-top">
            <a class="work-reminder-title" href="{{ route('tasks.show', $reminder) }}">{{ $reminder->title }}</a>
            <span class="work-reminder-status {{ $reminder->status === 'completed' ? 'is-waiting' : '' }}">{{ $statusLabel }}</span>
        </div>
        <div class="work-reminder-meta">
            <span class="{{ $reminder->isOverdue() ? 'text-danger' : '' }}"><i class="bi bi-calendar-event me-1"></i>Hạn: {{ $reminder->due_date?->format('d/m/Y') ?? 'Chưa đặt' }}{{ $reminder->isOverdue() ? ' · Quá hạn' : '' }}</span>
            @if($isDebt)
            <span>Đã thu {{ number_format($debts->sum('collected'),0,',','.') }} / {{ number_format($debtTarget,0,',','.') }}đ</span>
            @else
            <span><i class="bi bi-people me-1"></i>{{ $completed }}/{{ $totalAssignees }} người báo hoàn thành</span>
            @endif
            <span>{{ round($progress,1) }}%</span>
        </div>
        <div class="work-reminder-progress" role="progressbar" aria-label="Tiến độ nhiệm vụ" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(100,max(0,round($progress,1))) }}"><span style="width: {{ min(100,max(0,$progress)) }}%"></span></div>
        @if($isDebt && $debts->isNotEmpty())
        <details class="work-reminder-debts"><summary>Chi tiết thu nợ · {{ $debts->count() }} khách hàng</summary>
            @foreach($debts as $debt)
            <div class="mt-1">{{ $debt['customer_name'] ?? 'Khách hàng' }} · {{ $debt['sale_name'] ?? '—' }} · {{ round((float)($debt['collected'] ?? 0) / max(1,(float)($debt['target'] ?? 0)) * 100,1) }}% {{ ($debt['collected'] ?? 0) >= ($debt['target'] ?? 0) ? '(đạt mục tiêu)' : '(đang thực hiện)' }}</div>
            @endforeach
        </details>
        @endif
    </article>
    @endforeach
</section>
@endif
