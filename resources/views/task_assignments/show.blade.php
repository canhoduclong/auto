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

            {{-- Sub-tasks --}}
            @if($task->subTasks->isNotEmpty())
                <div class="card shadow-sm mb-3">
                    <div class="card-header py-2 fw-semibold small text-uppercase text-muted">
                        <i class="ph-tree-structure me-1"></i>Cong viec con ({{ $task->subTasks->count() }})
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($task->subTasks as $sub)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route($showRoute ?? 'task-assignments.show', $sub) }}" class="fw-semibold text-decoration-none">
                                            {{ $sub->title }}
                                        </a>
                                        <div class="small text-muted">{{ $sub->code }} &bull; {{ $sub->creator?->name }}</div>
                                    </div>
                                    <span class="badge bg-{{ $sub->statusColor() }}">
                                        {{ \App\Models\TaskAssignment::STATUS_LABELS[$sub->status] ?? $sub->status }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Create sub-task shortcut --}}
            @if(in_array($task->status, [\App\Models\TaskAssignment::STATUS_PENDING, \App\Models\TaskAssignment::STATUS_IN_PROGRESS]))
                <a href="{{ route($createRoute ?? 'task-assignments.create', ['parent_id' => $task->id]) }}"
                   class="btn btn-sm btn-outline-primary">
                    <i class="ph-plus me-1"></i>Tao cong viec con
                </a>
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
