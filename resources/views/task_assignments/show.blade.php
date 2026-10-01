@extends($layout ?? 'layouts.admin')

@section('title', $task->code . ' — ' . $task->title)

@push('styles')
<style>
.timeline { position: relative; padding-left: 28px; }
.timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
.tl-item { position: relative; margin-bottom: 20px; }
.tl-dot { position: absolute; left: -22px; top: 4px; width: 16px; height: 16px; border-radius: 50%; border: 2px solid; display: flex; align-items: center; justify-content: center; font-size: 8px; }
.tl-dot.approved { border-color: #22c55e; background: #f0fdf4; color: #22c55e; }
.tl-dot.pending  { border-color: #f59e0b; background: #fffbeb; color: #f59e0b; }
.tl-dot.rejected { border-color: #ef4444; background: #fef2f2; color: #ef4444; }
.info-grid { display: grid; grid-template-columns: 140px 1fr; gap: 6px 12px; font-size: 13px; }
.info-grid .ig-label { color: #94a3b8; }
.info-grid .ig-val { font-weight: 600; }
.attachment-thumb { max-width: 80px; max-height: 60px; border-radius: 6px; border: 1px solid #dee2e6; object-fit: cover; }
</style>
@endpush

@section('content')
<div class="content-wrapper container">
<div class="content-header d-flex align-items-center py-3 gap-3">
    <a href="{{ route($indexRoute ?? 'task-assignments.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ph ph-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0">{{ $task->title }}</h4>
            <span class="badge bg-{{ $task->statusColor() }} ms-1">
                {{ \App\Models\TaskAssignment::STATUS_LABELS[$task->status] ?? $task->status }}
            </span>
            <span class="badge bg-{{ $task->priorityColor() }}">
                {{ \App\Models\TaskAssignment::PRIORITY_LABELS[$task->priority] }}
            </span>
        </div>
        <small class="text-muted">{{ $task->code }} &bull; Tao boi {{ $task->creator?->name }} &bull; {{ $task->created_at?->format('d/m/Y H:i') }}</small>
    </div>
    @if($task->canBeEditedBy(auth()->user()))
            <a href="{{ route('task-assignments.edit', $task) }}" class="btn btn-sm btn-outline-primary">
                <i class="ph-pencil me-1"></i> Chinh sua
            </a>
            <form action="{{ route('task-assignments.cancel', $task) }}" method="POST"
                  onsubmit="return confirm('Huy cong viec nay?')" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="ph-x me-1"></i> Huy cong viec
                </button>
            </form>
    @endif
</div>

<div class="content-body pb-4">
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row g-4">
        {{-- ── LEFT: detail + sub-tasks ── --}}
        <div class="col-lg-8">

            {{-- Description --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2 fw-semibold small text-uppercase text-muted">
                    <i class="ph-file-text me-1"></i>Noi dung cong viec
                </div>
                <div class="card-body">
                    <div class="info-grid mb-3">
                        <span class="ig-label">Ma cong viec</span>
                        <span class="ig-val">{{ $task->code }}</span>

                        <span class="ig-label">Nguoi tao</span>
                        <span class="ig-val">{{ $task->creator?->name ?? '-' }}</span>

                        <span class="ig-label">Quy trinh</span>
                        <span class="ig-val">{{ $task->workflow?->name ?? 'Khong co' }}</span>

                        @if($task->parent)
                            <span class="ig-label">Cong viec cha</span>
                            <span class="ig-val">
                                <a href="{{ route($showRoute ?? 'task-assignments.show', $task->parent) }}">
                                    {{ $task->parent->code }} — {{ $task->parent->title }}
                                </a>
                            </span>
                        @endif

                        @if($task->due_date)
                            <span class="ig-label">Han chot</span>
                            <span class="ig-val {{ $task->isOverdue() ? 'text-danger' : '' }}">
                                {{ $task->due_date->format('d/m/Y H:i') }}
                                @if($task->isOverdue()) <span class="badge bg-danger">Tre han</span> @endif
                            </span>
                        @endif

                        @if($task->completed_at)
                            <span class="ig-label">Hoan thanh luc</span>
                            <span class="ig-val text-success">{{ $task->completed_at->format('d/m/Y H:i') }}</span>
                        @endif

                        @if($task->reject_reason)
                            <span class="ig-label">Ly do tu choi</span>
                            <span class="ig-val text-danger">{{ $task->reject_reason }}</span>
                        @endif
                    </div>

                    @if($task->description)
                        <div class="border-top pt-3">
                            <div class="small fw-semibold text-muted mb-1">Mo ta:</div>
                            <div style="white-space: pre-wrap; font-size:14px">{{ $task->description }}</div>
                        </div>
                    @endif

                    {{-- Attachments --}}
                    @if($task->attachments && count($task->attachments) > 0)
                        <div class="border-top pt-3 mt-3">
                            <div class="small fw-semibold text-muted mb-2">Dinh kem:</div>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach($task->attachments as $att)
                                    @php $ext = pathinfo($att, PATHINFO_EXTENSION); @endphp
                                    @if(in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp']))
                                        <a href="{{ Storage::url($att) }}" target="_blank">
                                            <img src="{{ Storage::url($att) }}" class="attachment-thumb" alt="Dinh kem">
                                        </a>
                                    @else
                                        <a href="{{ Storage::url($att) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="ph-file-arrow-down me-1"></i>{{ basename($att) }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Tiến độ công việc · {{ $task->code }}</div>
                <div class="card-body timeline ms-3">
                    <div class="tl-item"><span class="tl-dot approved"></span><strong>{{ $task->title }}</strong><div class="small text-muted">Tạo {{ $task->created_at?->format('d/m/Y H:i') }} · Hạn: {{ $task->due_date?->format('d/m/Y H:i') ?? 'Chưa đặt' }}</div></div>
                    @foreach($task->subTasks->sortBy('due_date') as $sub)
                    <div class="tl-item">
                        <span class="tl-dot {{ $sub->status === 'done' ? 'approved' : 'pending' }}"></span>
                        <div class="d-flex gap-2 align-items-start flex-wrap">
                            <input type="checkbox" disabled @checked(in_array($sub->status, ['completed', 'done'], true)) aria-label="Đã báo hoàn thành {{ $sub->title }}">
                            <div class="flex-grow-1"><a href="{{ route('tasks.show', $sub) }}" class="fw-semibold">{{ $sub->title }}</a>
                                <div class="small text-muted">Người tạo / giao: {{ $sub->creator?->name ?? 'Không xác định' }}</div><div class="small text-muted">Người thực hiện: {{ $sub->assignees->map(fn ($assignment) => $assignment->user?->name)->filter()->join(', ') ?: 'Chưa có' }}</div>
                                <div class="small {{ $sub->isOverdue() ? 'text-danger' : 'text-muted' }}">Hạn: {{ $sub->due_date?->format('d/m/Y H:i') ?? 'Chưa đặt' }}</div>
                                <span class="badge bg-{{ $sub->statusColor() }}">{{ \App\Models\TaskAssignment::STATUS_LABELS[$sub->status] ?? $sub->status }}</span>
                                @if($sub->completion_content)<div class="small mt-2" style="white-space:pre-wrap">{{ $sub->completion_content }}</div>@endif
                            </div>
                            @if($sub->assignees->contains('user_id', auth()->id()) && in_array($sub->status, ['pending', 'processing', 'rejected'], true))
                                <a class="btn btn-sm btn-outline-success" href="{{ route('tasks.complete-form', $sub) }}">Báo hoàn thành / tài liệu</a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    <div class="tl-item"><span class="tl-dot {{ $task->status === 'done' ? 'approved' : 'pending' }}"></span>{{ $task->status === 'done' ? 'Đã hoàn thành công việc chính' : 'Tổng hợp kết quả và xác nhận công việc chính' }}</div>
                </div>
                <div class="card-footer small text-muted">{{ $task->subTasks->whereIn('status', ['completed', 'done'])->count() }}/{{ $task->subTasks->count() }} việc con đã báo hoàn thành. Kết quả và tài liệu được lưu trong từng việc con.</div>
            </div>
            @if(!in_array($task->status, ['done', 'cancelled'], true) && (auth()->user()->hasRole('admin') || (int) $task->created_by === (int) auth()->id() || $myAssignee))
                <form class="card card-body shadow-sm" action="{{ route('tasks.subtasks.store', $task) }}" method="POST">
                    @csrf
                    <h6>Thêm công việc con</h6>
                    <div class="border rounded p-3 mb-3 bg-light d-flex align-items-start gap-2">
                        <i class="ph ph-user-circle fs-4 text-primary" aria-hidden="true"></i>
                        <div style="min-width:0">
                            <div class="small text-muted">Người tạo / giao việc con</div>
                            <div class="fw-semibold">{{ auth()->user()->name }}</div>
                            @if(auth()->user()->email)
                                <div class="small text-muted" style="overflow-wrap:anywhere">{{ auth()->user()->email }}</div>
                            @endif

                            <div class="small text-muted mt-1">Người giao công việc chính: {{ $task->creator?->name ?? 'Không xác định' }}</div>
                        </div>
                    </div>
                    <p class="small text-muted">Chọn người thực hiện việc con; kết quả được dùng để bổ sung hồ sơ hoàn thành công việc chính.</p>
                    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                    <div class="mb-3" id="child-recipients">
                        <label class="form-label" for="child-recipient-picker">Giao cho <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-2 mb-2" data-recipient-tags></div>
                        <select id="child-recipient-picker" class="form-select" aria-label="Chọn thêm người nhận">
                            <option value="">Bấm chọn người nhận…</option>
                            @foreach($subTaskAssignees as $recipient)
                                <option value="{{ $recipient->id }}" data-selected="{{ in_array((string) $recipient->id, array_map('strval', (array) old('assignee_ids', [])), true) ? '1' : '0' }}">{{ $recipient->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Có thể chọn nhiều người. Bấm × để bỏ người đã chọn.</small>
                    </div>
                    @if($subTaskAssignees->isEmpty())<div class="alert alert-warning">Chưa có người nhận được phép. Vui lòng nhờ admin cấu hình quyền giao việc.</div>@endif
                    <label class="form-label" for="child-title">Nội dung việc con</label>
                    <input id="child-title" class="form-control mb-2" name="title" value="{{ old('title') }}" maxlength="255" required>
                    <label class="form-label" for="child-due">Hạn hoàn thành</label>
                    <input id="child-due" class="form-control mb-2" type="date" name="due_date" min="{{ today()->toDateString() }}" value="{{ old('due_date') }}" required>
                    <small class="text-muted mb-3">Hạn mặc định: cuối ngày đã chọn (24:00).</small>
                    <label class="form-label" for="child-description">Mô tả / yêu cầu tài liệu</label>
                    <textarea id="child-description" class="form-control mb-3" name="description" maxlength="5000">{{ old('description') }}</textarea>
                    <button class="btn btn-primary align-self-start" type="submit" @disabled($subTaskAssignees->isEmpty())>Giao việc con</button>
                </form>
            @endif

        </div>

        {{-- ── RIGHT: approval chain + actions ── --}}
        <div class="col-lg-4">

            {{-- My assignee action card --}}
            @if($myAssignee && in_array($myAssignee->status, ['pending', 'in_progress', 'processing']))
                <div class="card border-primary shadow-sm mb-3">
                    <div class="card-header bg-primary bg-opacity-10 py-2">
                        <span class="fw-semibold text-primary"><i class="ph-clipboard-text me-1"></i>Viec duoc giao cho ban</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">
                            Trang thai hien tai: <span class="badge bg-{{ $myAssignee->statusColor() }}">{{ $myAssignee->status }}</span>
                        </p>
                        <form action="{{ route('task-assignments.assignee-update', $task) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="in_progress" {{ in_array($myAssignee->status, ['in_progress', 'processing']) ? 'selected' : '' }}>Dang thuc hien</option>
                                    <option value="rejected">Khong the thuc hien</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <textarea name="note" class="form-control form-control-sm" rows="2"
                                          placeholder="Ghi chu ket qua (tuy chon)...">{{ $myAssignee->note }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ph-check me-1"></i>Cap nhat trang thai
                            </button>
                        </form>
                        <a href="{{ route('task-assignments.complete-form', $task) }}" class="btn btn-success btn-sm w-100 mt-2">
                            <i class="ph-check-circle me-1"></i>Hoan thanh cong viec
                        </a>
                    </div>
                </div>
            @endif

            {{-- Assignees list --}}
            @if($task->assignees->isNotEmpty())
                <div class="card shadow-sm mb-3">
                    <div class="card-header py-2 fw-semibold small text-uppercase text-muted">
                        <i class="ph-users me-1"></i>Thanh vien nhan viec ({{ $task->assignees->count() }})
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($task->assignees as $ta)
                                <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                                    <div>
                                        <div class="fw-semibold small">{{ $ta->user?->name }}</div>
                                        @if($ta->note)
                                            <div class="text-muted" style="font-size:11px">{{ \Str::limit($ta->note, 50) }}</div>
                                        @endif
                                        @if($ta->completed_at)
                                            <div class="text-success" style="font-size:11px">
                                                Xong: {{ $ta->completed_at->format('d/m H:i') }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="badge bg-{{ $ta->statusColor() }}">{{ $ta->status }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Action card (for current workflow step actor) --}}
            @if($canAct && $current)
                <div class="card border-warning shadow-sm mb-3">
                    <div class="card-header bg-warning bg-opacity-10 py-2">
                        <span class="fw-semibold text-warning"><i class="ph-bell-ringing me-1"></i>Can ban phe duyet</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">
                            Buoc <strong>{{ $current->step?->step_order }}</strong>:
                            vai tro <strong>{{ $current->step?->role_slug }}</strong>
                            can xac nhan.
                        </p>

                        <form action="{{ route('task-assignments.approve', $task) }}" method="POST" class="mb-2">
                            @csrf
                            <div class="mb-2">
                                <textarea name="note" class="form-control form-control-sm" rows="2"
                                          placeholder="Ghi chu (tuy chon)..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="ph-check me-1"></i>Xac nhan / Phe duyet buoc nay
                            </button>
                        </form>

                        <button type="button" class="btn btn-outline-danger btn-sm w-100"
                                data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="ph-x me-1"></i>Tu choi cong viec
                        </button>
                    </div>
                </div>
            @endif

            {{-- Approval timeline --}}
            <div class="card shadow-sm">
                <div class="card-header py-2 fw-semibold small text-uppercase text-muted">
                    <i class="ph-flow-arrow me-1"></i>Quy trinh phe duyet
                    @if($task->workflow)
                        <span class="text-muted fw-normal ms-1">({{ $task->workflow->name }})</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($task->approvalSteps->isEmpty())
                        <div class="text-muted small">Cong viec nay khong co quy trinh phe duyet.</div>
                    @else
                        <div class="timeline">
                            @foreach($task->approvalSteps->sortBy('id') as $aStep)
                                <div class="tl-item">
                                    <div class="tl-dot {{ $aStep->status }}">
                                        @if($aStep->status === 'approved') ✓
                                        @elseif($aStep->status === 'rejected') ✗
                                        @else ○
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:13px">
                                            Buoc {{ $aStep->step?->step_order ?? '?' }}:
                                            {{ $aStep->step?->role_slug ?? 'Khong xac dinh' }}
                                        </div>
                                        <div class="small text-muted">
                                            @if($aStep->status === 'approved')
                                                <span class="text-success">Da phe duyet</span>
                                                boi {{ $aStep->approver?->name ?? '-' }}
                                                luc {{ $aStep->approved_at?->format('d/m/Y H:i') }}
                                            @elseif($aStep->status === 'rejected')
                                                <span class="text-danger">Tu choi</span>
                                                boi {{ $aStep->approver?->name ?? '-' }}
                                                @if($aStep->note) — {{ $aStep->note }} @endif
                                            @else
                                                <span class="text-warning">Cho phe duyet...</span>
                                            @endif
                                        </div>
                                        @if($aStep->note && $aStep->status === 'approved')
                                            <div class="fst-italic small text-muted mt-1">"{{ $aStep->note }}"</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Reject modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form action="{{ route('task-assignments.reject', $task) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title">Tu choi cong viec</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea name="reason" class="form-control" rows="3" required
                          placeholder="Ly do tu choi..."></textarea>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Huy</button>
                <button type="submit" class="btn btn-danger btn-sm">Xac nhan tu choi</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.getElementById('child-recipients');
    if (!root) return;
    const picker = root.querySelector('select'), tags = root.querySelector('[data-recipient-tags]');
    const selected = new Map();
    function render() {
        tags.replaceChildren();
        selected.forEach((name, id) => {
            const tag = document.createElement('span'); tag.className = 'badge bg-primary d-inline-flex align-items-center gap-2 p-2';
            const label = document.createElement('span'); label.textContent = name;
            const input = document.createElement('input'); input.type = 'hidden'; input.name = 'assignee_ids[]'; input.value = id;
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn-close btn-close-white'; remove.setAttribute('aria-label', 'Bỏ ' + name);
            remove.addEventListener('click', () => { selected.delete(id); render(); });
            tag.append(label, input, remove); tags.append(tag);
        });
        Array.from(picker.options).forEach(option => { if (option.value) option.hidden = option.disabled = selected.has(option.value); });
        picker.value = ''; picker.required = selected.size === 0;
    }
    Array.from(picker.options).filter(option => option.dataset.selected === '1').forEach(option => selected.set(option.value, option.textContent));
    picker.addEventListener('change', () => { if (picker.value) selected.set(picker.value, picker.selectedOptions[0].textContent); render(); });
    render();
})();
</script>
@endpush
