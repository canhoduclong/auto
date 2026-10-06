@extends($layout ?? 'layouts.admin')

@section('title', $task->code . ' — ' . $task->title)

@push('styles')
<style>
.child-recipient-label { padding:6px 10px; border:1px solid #d9e2ec; border-radius:5px; background:#fff; color:#526b82; font-size:12px; font-weight:600; }
.child-recipient-label[aria-pressed="true"] { border-color:#34d399; background:#ecfdf5; color:#087f5b; }
.child-recipient-label:hover { border-color:#34d399; }
.child-recipient-label:focus-visible { outline:2px solid #087f5b; outline-offset:2px; }

.member-evaluation-row, .member-evaluation-heading { display:grid; grid-template-columns:minmax(0,1fr) 65px 85px; align-items:center; gap:6px; }
.member-evaluation-row .evaluation-compact, .evaluation-compact .evaluation-picker { display:contents; }
.evaluation-compact summary, .evaluation-compact .evaluation-readonly { grid-column:2; grid-row:1; text-align:center; }
.member-evaluation-status { grid-column:3; grid-row:1; justify-self:end; }
.member-evaluation-info { grid-column:1; grid-row:1; min-width:0; overflow-wrap:anywhere; }
.evaluation-picker summary { cursor:pointer; list-style:none; color:#123550; }
.evaluation-picker summary::-webkit-details-marker { display:none; }
.evaluation-value { display:inline-block; padding:3px 8px; border:1px solid transparent; }
.evaluation-picker[open] .evaluation-value { border-color:#c5cbd1; background:#fff; }
.evaluation-options { margin:6px 0 0; }
.evaluation-compact .evaluation-options { grid-column:1 / -1; grid-row:2; }
.evaluation-nodes { display:flex; width:100%; max-width:380px; }
.evaluation-node { flex:1; min-width:0; padding:4px 0; border:0; background:transparent; color:#64748b; font-size:11px; cursor:pointer; }
.evaluation-node span { display:block; height:2px; background:#cbd5e1; margin-top:8px; position:relative; }
.evaluation-node span::after { content:''; position:absolute; width:8px; height:8px; border-radius:50%; background:#cbd5e1; left:50%; top:50%; transform:translate(-50%,-50%); }
.evaluation-node:hover, .evaluation-node.is-selected { color:#d96310; font-weight:700; }
.evaluation-node:hover span::after, .evaluation-node.is-selected span::after { background:#f58220; width:12px; height:12px; }
.evaluation-node:focus-visible, .evaluation-picker summary:focus-visible { outline:2px solid #087f5b; outline-offset:2px; }
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
.task-person { border-bottom:1px solid #e2e8f0; padding:16px 0; }
.task-person-head { display:flex; flex-wrap:wrap; align-items:center; gap:12px; }
.task-person-head > strong { flex:1; min-width:150px; }
.task-person details > summary { cursor:pointer; color:#087f80; }
.task-activity { margin:8px 0; padding-left:14px; border-left:2px solid #dce9e7; font-size:13px; overflow-wrap:anywhere; }
.task-milestones { display:flex; overflow-x:auto; padding:16px 0 24px; }
.task-milestone { flex:1; min-width:120px; position:relative; padding:28px 6px 0; font-size:12px; text-align:center; }
.task-milestone::before { content:''; position:absolute; height:2px; background:#b9d2cc; top:9px; left:0; right:0; }
.task-milestone:first-child::before { left:50%; }
.task-milestone:last-child::before { right:50%; }
.task-milestone-dot { position:absolute; left:50%; top:2px; transform:translateX(-50%); width:16px; height:16px; border:2px solid currentColor; background:#fff; border-radius:50%; z-index:1; }
.task-person-columns { display:grid; grid-template-columns:minmax(0,1fr) 100px 150px; gap:12px; align-items:center; }
.task-person-columns > :nth-child(2), .task-person-columns > :nth-child(3) { text-align:center; justify-self:center; }
.task-person-columns > strong { min-width:0; }
.evaluation-widget { position:relative; }
.evaluation-options { position:absolute; width:280px; max-width:calc(100vw - 48px); right:0; top:100%; padding:14px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 6px 20px #0002; z-index:10; text-align:left; }
.evaluation-options input { width:100%; accent-color:#0d827a; }
@media(max-width:575px) { .task-person-columns { grid-template-columns:minmax(0,1fr) 65px 105px; gap:4px; font-size:12px; } .task-person-columns .badge { white-space:normal; } .evaluation-options { right:-80px; } }
.task-document { display:flex; gap:12px; padding:16px 0; border-bottom:1px solid #e2e8f0; overflow-wrap:anywhere; }
.task-document img { width:85px; height:70px; object-fit:cover; }
.task-document > div { min-width:0; }
@media(max-width:575px) { .content-wrapper.container { padding:0 12px; } .task-person-head { gap:8px; } }
.evaluation-toggle { border:0; background:transparent; padding:2px; color:#075985; cursor:pointer; }
.evaluation-options[hidden] { display:none !important; }
.evaluation-options { width:380px; }
.evaluation-options output { border:1px solid #cbd5e1; }
.evaluation-scale { position:relative; padding-bottom:8px; }
.evaluation-ticks { display:flex; justify-content:space-between; }
.evaluation-ticks button { position:relative; padding:0 0 22px; width:24px; border:0; background:none; color:#526b82; font-size:10px; cursor:pointer; }
.evaluation-ticks button span { position:absolute; bottom:4px; left:50%; transform:translateX(-50%); width:7px; height:7px; border-radius:50%; background:#cbd5e1; }
.evaluation-ticks button.selected { color:#ea7617; font-weight:bold; }
.evaluation-ticks button.selected span { background:#ea7617; }
.evaluation-options .evaluation-range { position:absolute; left:6px; bottom:9px; width:calc(100% - 12px); height:12px; margin:0; accent-color:#f58220; cursor:pointer; }
</style>
@endpush

@section('content')
<div class="content-wrapper container">
<div class="content-header d-flex align-items-center flex-wrap py-3 gap-3">
    <a href="{{ route($indexRoute ?? 'task-assignments.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ph ph-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h4 class="mb-0">{{ $task->title }}</h4>
            <span class="badge bg-{{ $task->statusColor() }} ms-1">
                {{ \App\Models\TaskAssignment::STATUS_LABELS[$task->status] ?? $task->status }}
            </span>
            <span class="badge bg-{{ $task->priorityColor() }}">
                {{ \App\Models\TaskAssignment::PRIORITY_LABELS[$task->priority] }}
            </span>
        </div>
        <small class="text-muted">{{ $task->code }} &bull; Tạo bởi {{ $task->creator?->name }} &bull; {{ $task->created_at?->format('d/m/Y H:i') }} · Hạn chót: <strong class="{{ $task->isOverdue() ? 'text-danger' : '' }}">{{ $task->due_date?->format('d/m/Y H:i') ?? 'Chưa đặt' }}</strong></small>
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
    @if($errors->has('collected') || $errors->has('note'))<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @error('recall_reason')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('evaluation_score')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row g-4">
        {{-- ── LEFT: detail + sub-tasks ── --}}
        <div class="col-lg-8">

            {{-- Description --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2 fw-semibold small text-uppercase text-muted">
                    <i class="ph-file-text me-1"></i>Nội dung công việc
                </div>
                <div class="card-body">
                    @if($task->parent)
                        <a class="small d-block mb-2" href="{{ route('tasks.show', $task->parent) }}">Công việc chính: {{ $task->parent->title }}</a>
                    @endif
                    @if($task->reject_reason)<div class="alert alert-warning">{{ $task->reject_reason }}</div>@endif
                    @if($task->description)
                        <div class="border-top pt-3">
                            <div class="small fw-semibold text-muted mb-1">Mo ta:</div>
                            <div class="task-description">{!! \App\Support\TaskDescription::render($task->description) !!}</div>
                        </div>
                    @endif

                </div>
            </div>

            @include('task_assignments.partials.debt-progress')
            @include('task_assignments.partials.progress')
            @if(!in_array($task->status, ['done', 'cancelled'], true) && (auth()->user()->hasRole('admin') || (int) $task->created_by === (int) auth()->id() || $myAssignee))
                <details class="mb-3" @if($errors->any() && !$errors->has('evaluation_score')) open @endif>
                <summary class="btn btn-primary mb-2">Giao việc con</summary>
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
                    <fieldset class="mb-3" id="child-recipients">
                        <legend class="form-label fs-6">Giao cho <span class="text-danger">*</span></legend>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($subTaskAssignees as $recipient)
                                @php $selectedRecipient = in_array((string) $recipient->id, array_map('strval', (array) old('assignee_ids', [])), true); @endphp
                                <button type="button" class="child-recipient-label" data-recipient-id="{{ $recipient->id }}" aria-pressed="{{ $selectedRecipient ? 'true' : 'false' }}">{{ $recipient->name }}</button>
                            @endforeach
                        </div>
                        <div data-recipient-inputs></div>
                        <small class="text-muted d-block mt-2">Bấm tên để chọn nhiều người; bấm lại để bỏ chọn.</small>
                        <div class="text-danger small mt-1" data-recipient-error role="alert" hidden>Vui lòng chọn ít nhất một người nhận việc.</div>
                    </fieldset>
                    @if($subTaskAssignees->isEmpty())<div class="alert alert-warning">Chưa có người nhận được phép. Vui lòng nhờ admin cấu hình quyền giao việc.</div>@endif
                    <label class="form-label" for="child-title">Nội dung việc con</label>
                    <input id="child-title" class="form-control mb-2" name="title" value="{{ old('title') }}" maxlength="255" required>
                    <label class="form-label" for="child-due">Hạn hoàn thành</label>
                    <input id="child-due" class="form-control mb-2" type="date" name="due_date" min="{{ today()->toDateString() }}" value="{{ old('due_date') }}" required>
                    <small class="text-muted mb-3">Hạn mặc định: cuối ngày đã chọn (24:00).</small>
                    <label class="form-label" for="child-description">Mô tả / yêu cầu tài liệu</label>
                    <textarea id="child-description" class="form-control mb-3 task-description-editor" name="description" maxlength="20000">{{ \App\Support\TaskDescription::render(old('description')) }}</textarea>
                    <button class="btn btn-primary align-self-start" type="submit" @disabled($subTaskAssignees->isEmpty())>Giao việc con</button>
                </form>
                </details>
            @endif

        </div>

        {{-- ── RIGHT: approval chain + actions ── --}}
        <div class="col-lg-4">

            @include('task_assignments.partials.documents')

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
            <div class="card shadow-sm mb-3">
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

            {{-- My assignee action card --}}
            @if($myAssignee && in_array($myAssignee->status, ['pending', 'in_progress', 'processing']))
                <div class="card border-primary shadow-sm mb-3">
                    <div class="card-header bg-primary bg-opacity-10 py-2">
                        <span class="fw-semibold text-primary"><i class="ph-clipboard-text me-1"></i>Cập nhật công việc của bạn</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">
                            Trạng thái hiện tại: <span class="badge bg-{{ $myAssignee->statusColor() }}">{{ $myAssignee->status }}</span>
                        </p>
                        <form action="{{ route('task-assignments.assignee-update', $task) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="in_progress" {{ in_array($myAssignee->status, ['in_progress', 'processing']) ? 'selected' : '' }}>Đang thực hiện</option>
                                    <option value="rejected">Không thể thực hiện</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <textarea name="note" class="form-control form-control-sm" rows="2"
                                          placeholder="Nội dung đã thực hiện / kết quả...">{{ $myAssignee->note }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ph-check me-1"></i>Cập nhật trạng thái
                            </button>
                        </form>
                        <a href="{{ route('task-assignments.complete-form', $task) }}" class="btn btn-success btn-sm w-100 mt-2">
                            <i class="ph-check-circle me-1"></i>Hoàn thành công việc
                        </a>
                    </div>
                </div>
            @endif
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
    const buttons = Array.from(root.querySelectorAll('[data-recipient-id]'));
    const inputs = root.querySelector('[data-recipient-inputs]');
    const error = root.querySelector('[data-recipient-error]');
    function sync() {
        inputs.replaceChildren();
        buttons.filter(button => button.getAttribute('aria-pressed') === 'true').forEach(button => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'assignee_ids[]'; input.value = button.dataset.recipientId;
            inputs.append(input);
        });
        if (inputs.children.length) error.hidden = true;
    }
    buttons.forEach(button => button.addEventListener('click', () => {
        button.setAttribute('aria-pressed', button.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
        sync();
    }));
    root.closest('form').addEventListener('submit', event => {
        if (!inputs.children.length) {
            event.preventDefault(); error.hidden = false; buttons[0]?.focus();
        }
    });
    sync();
})();
</script>
@endpush

@include('task_assignments.partials.description-editor')
