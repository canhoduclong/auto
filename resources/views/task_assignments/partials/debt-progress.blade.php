@if($task->task_type === 'debt_collection')
@php $debtRows = collect($task->debt_items ?? []); $target = $debtRows->sum('target'); $collected = $debtRows->sum('collected'); $percent = $target > 0 ? round($collected / $target * 100, 1) : 0; @endphp
<section class="card mb-3"><div class="card-header fw-semibold">Thu nợ · {{ $percent }}% thực hiện</div><div class="card-body">
<p>{{ number_format($collected, 0, ',', '.') }} / {{ number_format($target, 0, ',', '.') }} đ đã báo thu</p>
<p class="small text-muted">Tiến độ do người phụ trách cập nhật, không thay thế xác nhận thu tiền của kế toán.</p>
@foreach($debtRows as $index => $debt)
<div class="border-top py-3"><strong>{{ $debt['customer_name'] }}</strong><div class="small">Sale: {{ $debt['sale_name'] }} · {{ $debt['collected'] >= $debt['target'] ? 'Đã đạt mục tiêu' : ($debt['collected'] > 0 ? 'Đang thu' : 'Chưa thu') }}</div>
<div>{{ number_format($debt['collected'], 0, ',', '.') }} / {{ number_format($debt['target'], 0, ',', '.') }} đ · {{ round($debt['collected'] / max(1, $debt['target']) * 100, 1) }}%</div>
@if(!in_array($task->status, ['done','cancelled','completed'], true) && ((int) $task->created_by === (int) auth()->id() || ((int) $debt['sale_id'] === (int) auth()->id() && $task->assignees->contains('user_id', auth()->id()))))
<form action="{{ route('tasks.debt-progress', [$task, $index]) }}" method="POST" class="d-flex flex-wrap gap-2 mt-2">@csrf
<label class="small">Tổng tiền đã thu (đ)<input name="collected" type="number" min="0" max="{{ $debt['target'] }}" step="0.01" value="{{ $debt['collected'] }}" required class="form-control form-control-sm"></label>
<label class="small flex-grow-1">Ghi chú cập nhật<input name="note" required maxlength="1000" class="form-control form-control-sm"></label><button class="btn btn-sm btn-primary align-self-end">Lưu tiến độ</button></form>
@endif</div>
@endforeach
</div></section>
@endif
