@extends($layout ?? 'layouts.admin')

@section('title', $task->code . ' — ' . $task->title)

@include('task_assignments.partials.detail-styles')

@section('content')
<div class="content-wrapper container task-workspace">
<div class="content-header d-flex align-items-center flex-wrap py-3 gap-3">
    <a href="{{ route($indexRoute ?? 'task-assignments.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ph ph-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h4 class="mb-0">{{ $task->title }}</h4>
            <span class="badge bg-{{ $task->statusColor() }} ms-1">
                {{ $task->operatingStatusLabel() }}
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

            @if($task->work_kind)<div class="alert alert-light border"><strong>{{ $task->work_kind==='coordination'?'Yêu cầu phối hợp':'Giao thực hiện' }}</strong> · Chủ trì: {{ $task->assignees->firstWhere('user_id',$task->accountable_user_id)?->user?->name ?? '—' }} @if($task->proposal_id) · <a href="{{ route('operating.proposals.show',$task->proposal_id) }}">Nguồn biểu quyết #{{ $task->proposal_id }}</a>@endif</div>@endif
            {{-- Description --}}
            <div class="card shadow-sm mb-3 task-content-card">
                <div class="card-header py-2 fw-semibold small text-uppercase text-muted">
                    <i class="ph-file-text me-1"></i>Nội dung công việc
                </div>
                <div class="card-body">
                    @if($task->parent)
                        <a class="small d-block mb-2" href="{{ route('tasks.show', $task->parent) }}">Công việc chính: {{ $task->parent->title }}</a>
                    @endif
                    @if($task->reject_reason)<div class="alert alert-warning">{{ $task->reject_reason }}</div>@endif
                    @if($task->description)
                        <div>
                            <div class="task-description">{!! \App\Support\TaskDescription::render($task->description) !!}</div>
                        </div>
                    @endif

                </div>
            </div>

            @include('task_assignments.partials.debt-progress')
            @include('task_assignments.partials.progress')
            <div class="task-action-bar mb-3">
                @if(!in_array($task->status,['done','cancelled'],true) && (auth()->user()->hasRole('admin') || (int)$task->created_by===(int)auth()->id() || $myAssignee))
                <button type="button" class="btn task-action-button" data-bs-toggle="collapse" data-bs-target="#taskChildPanel" aria-controls="taskChildPanel" aria-expanded="{{ $errors->any() && !$errors->has('evaluation_score') ? 'true' : 'false' }}">Giao việc con</button>
                @endif
                @if($myAssignee && in_array($myAssignee->status,['pending','in_progress','processing']))
                <button type="button" class="btn task-action-button" data-bs-toggle="collapse" data-bs-target="#taskStatusPanel" aria-controls="taskStatusPanel" aria-expanded="false">Cập nhật trạng thái</button>
                @if(!$task->work_kind || $myAssignee->started_at)
                <button type="button" class="btn task-action-button" data-bs-toggle="collapse" data-bs-target="#taskReportPanel" aria-controls="taskReportPanel" aria-expanded="false">Báo cáo hoàn thành</button>
                @endif
                @endif
            </div>
            @if(!in_array($task->status, ['done', 'cancelled'], true) && (auth()->user()->hasRole('admin') || (int) $task->created_by === (int) auth()->id() || $myAssignee))
                <div id="taskChildPanel" class="collapse mb-3 {{ $errors->any() && !$errors->has('evaluation_score') ? 'show' : '' }}">
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
                    <label class="form-label">Người chủ trì *</label><select name="accountable_user_id" required class="form-select mb-2"><option value="">Chọn người chịu trách nhiệm chính</option>@foreach($subTaskAssignees as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select>
                    <label class="form-label">Hạn tiếp nhận *</label><input type="datetime-local" name="acceptance_due_at" required class="form-control mb-2" value="{{ now()->addDay()->format('Y-m-d\TH:i') }}">
                    <label class="form-label" for="child-title">Tên việc con *</label>
                    <input id="child-title" class="form-control mb-2" name="title" value="{{ old('title') }}" maxlength="255" required>
                    <div class="mb-3">
                        <label class="form-label" for="child-description">Nội dung việc con</label>
                        <textarea id="child-description" class="form-control task-description-editor" name="description" rows="12" data-editor-height="400" maxlength="20000" placeholder="Nhập nội dung công việc, kết quả cần đạt và yêu cầu tài liệu…">{{ \App\Support\TaskDescription::render(old('description')) }}</textarea>
                    </div>
                    <label class="form-label" for="child-due">Hạn hoàn thành</label>
                    <input id="child-due" class="form-control mb-2" type="date" name="due_date" min="{{ today()->toDateString() }}" value="{{ old('due_date') }}" required>
                    <small class="text-muted mb-3">Hạn mặc định: cuối ngày đã chọn (24:00).</small>
                    <button class="btn btn-primary align-self-start" type="submit" @disabled($subTaskAssignees->isEmpty())>Giao việc con</button>
                </form>
                </div>
            @endif

            {{-- My assignee action card --}}
            @if($myAssignee && in_array($myAssignee->status, ['pending', 'in_progress', 'processing']))
                <div class="collapse mb-3" id="taskStatusPanel"><div class="card border-primary shadow-sm task-status-card">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="task-status-title">Cập nhật công việc của bạn</span>
                        <span class="small text-muted fw-normal">Trạng thái hiện tại: <span class="badge bg-{{ $myAssignee->statusColor() }}">{{ $task->work_kind && !$myAssignee->accepted_at ? 'Chưa tiếp nhận' : ($myAssignee->accepted_at && !$myAssignee->started_at ? 'Đã tiếp nhận' : (in_array($myAssignee->status,['processing','in_progress']) ? 'Đang thực hiện' : $myAssignee->status)) }}</span></span>
                    </div>
                    <div class="card-body">
                        @if($task->work_kind && !$myAssignee->accepted_at)
                            <form method="POST" action="{{ route('tasks.accept',$task) }}">@csrf
                                <button type="submit" class="btn btn-primary w-100">Tiếp nhận {{ $task->work_kind==='coordination'?'yêu cầu phối hợp':'công việc' }}</button>
                            </form>
                            @if($task->work_kind==='coordination' && (int)$task->accountable_user_id===(int)auth()->id())
                                <details class="mt-2"><summary class="small text-danger">Từ chối phối hợp</summary><form method="POST" action="{{ route('tasks.decline-coordination',$task) }}" class="mt-2">@csrf<label class="form-label small">Lý do *</label><textarea name="reason" class="form-control mb-2" required maxlength="2000" rows="4"></textarea><button type="submit" class="btn btn-outline-danger btn-sm">Gửi phản hồi từ chối</button></form></details>
                            @endif
                            <small class="text-muted d-block mt-2">Hạn tiếp nhận: {{ $task->acceptance_due_at?->format('d/m/Y H:i') ?? '—' }}</small>
                        @else
                            @if($myAssignee->accepted_at)<div class="small text-muted mb-2">Đã tiếp nhận: {{ $myAssignee->accepted_at->format('d/m/Y H:i') }}</div>@endif
                        <form action="{{ route('task-assignments.assignee-update', $task) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3"><div class="col-md-6">
                            <label class="form-label small" for="personal-status">Trạng thái</label><div class="mb-4">
                                <select id="personal-status" name="status" class="form-select">
                                    <option value="in_progress" {{ in_array($myAssignee->status, ['in_progress', 'processing']) ? 'selected' : '' }}>Đang thực hiện</option>
                                    <option value="rejected">Không thể thực hiện</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small" for="personal-note">Nội dung đã thực hiện / kết quả</label><textarea id="personal-note" name="note" class="form-control" rows="2" maxlength="1000">{{ $myAssignee->note }}</textarea>
                            </div>
                            </div><div class="col-md-6"><label class="form-label small" for="status-documents">Tài liệu đính kèm</label><input id="status-documents" type="file" name="documents[]" multiple class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt"><div class="form-text">Tối đa 10 tệp, 20MB/tệp.</div></div></div>
                            <button type="submit" class="btn btn-primary btn-sm mt-3" @disabled($task->work_kind && $task->approvalSteps->where('status','pending')->isNotEmpty())>
                                <i class="ph-check me-1"></i>Cập nhật trạng thái
                            </button>
                        </form>

                        @endif
                    </div>
                </div></div>
            @endif
            @if($myAssignee && in_array($myAssignee->status,['pending','in_progress','processing']) && (!$task->work_kind || $myAssignee->started_at))
            <div class="collapse" id="taskReportPanel">
                @include('task_assignments.partials.completion-report',['submitRoute'=>'tasks.complete','showRoute'=>'tasks.show'])
            </div>
            @endif
        </div>

        {{-- ── RIGHT: approval chain + actions ── --}}
        <div class="col-lg-4">



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

            @if($task->status==='completed' && ((int)$task->created_by===(int)auth()->id() || auth()->user()->hasRole('admin')))
                <div class="card card-body mb-3"><h6>Nghiệm thu kết quả</h6><a class="btn btn-success" href="{{ route('task-assignments.verify-form',$task) }}">Kiểm tra và nghiệm thu</a></div>
            @endif
            {{-- Approval timeline --}}
            @if($task->approvalSteps->isNotEmpty())
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2 fw-semibold small text-uppercase text-muted">
                    <i class="ph-flow-arrow me-1"></i>Quy trình phê duyệt
                    @if($task->workflow)
                        <span class="text-muted fw-normal ms-1">({{ $task->workflow->name }})</span>
                    @endif
                </div>
                <div class="card-body">
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
                                            Bước {{ $aStep->step?->step_order ?? '?' }}:
                                            {{ $aStep->step?->role_slug ?? 'Không xác định' }}
                                        </div>
                                        <div class="small text-muted">
                                            @if($aStep->status === 'approved')
                                                <span class="text-success">Đã phê duyệt</span>
                                                bởi {{ $aStep->approver?->name ?? '-' }}
                                                lúc {{ $aStep->approved_at?->format('d/m/Y H:i') }}
                                            @elseif($aStep->status === 'rejected')
                                                <span class="text-danger">Từ chối</span>
                                                bởi {{ $aStep->approver?->name ?? '-' }}
                                                @if($aStep->note) — {{ $aStep->note }} @endif
                                            @else
                                                <span class="text-warning">Chờ phê duyệt…</span>
                                            @endif
                                        </div>
                                        @if($aStep->note && $aStep->status === 'approved')
                                            <div class="fst-italic small text-muted mt-1">"{{ $aStep->note }}"</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                </div>
            </div>
            @endif

            @include('task_assignments.partials.documents')
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
    if(!['#taskStatusPanel','#taskReportPanel','#taskChildPanel'].includes(window.location.hash))return;
    const panel=document.querySelector(window.location.hash);
    if(panel){ panel.classList.add('show'); document.querySelectorAll('[data-bs-target="'+window.location.hash+'"]').forEach(button=>button.setAttribute('aria-expanded','true')); panel.scrollIntoView({block:'start'}); }
});
</script>
@endpush
