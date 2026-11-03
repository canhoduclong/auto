@extends($layout ?? \App\Support\TaskWorkspace::layout(auth()->user()))

@section('title', 'Tao Cong Viec Moi')

@push('styles')
<style>
.wf-card { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; cursor: pointer; transition: all .14s; }
.wf-card:hover { border-color: #3b82f6; background: #eff6ff; }
.wf-card.selected { border-color: #3b82f6; background: #dbeafe; }
.wf-steps-preview { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.wf-step-badge { padding: 2px 8px; background: #f1f5f9; border-radius: 20px; font-size: 11px; color: #475569; }
</style>
@endpush

@section('content')
<div class="content-wrapper container">
<div class="content-header d-flex align-items-center py-3 px-4">
    
    <a href="{{ route('task-assignments.assigned-to-me') }}" class="btn btn-sm btn-outline-secondary me-3">
        <i class="ph ph-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0">Tạo công việc mới</h4>
        <small class="text-muted">Soạn công việc và gửi người duyệt trước khi công bố</small>
    </div>
</div>

<div class="content-body px-4 pb-4">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route($storeRoute ?? 'task-assignments.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            {{-- ── LEFT: main form ── --}}
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Loại nghiệp vụ *</label>
                            <select name="work_kind" class="form-select">
                                <option value="execution" @selected(old('work_kind')!=='coordination')>Giao thực hiện</option>
                                <option value="coordination" @selected(old('work_kind')==='coordination')>Yêu cầu phối hợp</option>
                            </select>
                            <div class="small text-muted mt-1">Việc cần lấy ý kiến đồng ý/không đồng ý: <a href="{{ route('operating.proposals.create') }}">Mở đề xuất biểu quyết</a>.</div>
                        </div>
                        @if(request('proposal_id'))
                            <input type="hidden" name="proposal_id" value="{{ request('proposal_id') }}">
                            <div class="alert alert-info">Công việc triển khai từ đề xuất biểu quyết #{{ request('proposal_id') }} đã thông qua.</div>
                        @endif
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">Người chủ trì *</label><select name="accountable_user_id" class="form-select" required><option value="">Chọn người chịu trách nhiệm chính</option>@foreach($allowedAssignees as $person)<option value="{{ $person->id }}" @selected((string)old('accountable_user_id')===(string)$person->id)>{{ $person->name }}</option>@endforeach</select><small class="text-muted">Đồng thời chọn người này trong danh sách người nhận bên dưới.</small></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Hạn tiếp nhận *</label><input type="datetime-local" name="acceptance_due_at" required class="form-control" value="{{ old('acceptance_due_at', now()->addDay()->format('Y-m-d\TH:i')) }}"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tiêu đề công việc <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}" maxlength="255" placeholder="Nham tat, mo ta ngan gon...">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mô tả chi tiết</label>
                            <textarea name="description" class="form-control task-description-editor" rows="5" maxlength="20000"
                                      placeholder="Ghi rõ yêu cầu, kết quả cần đạt, ghi chú...">{{ \App\Support\TaskDescription::render(old('description')) }}</textarea>
                        </div>

                        @include('task_assignments.partials.create-attachments')

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Mức ưu tiên <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select">
                                    @foreach(\App\Models\TaskAssignment::PRIORITY_LABELS as $v => $l)
                                        <option value="{{ $v }}" {{ old('priority', 'medium') == $v ? 'selected' : '' }}>
                                            {{ $l }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Hạn chót</label>
                                <input type="date" name="due_date" id="due_date_input" required class="form-control"
                                       value="{{ old('due_date') ? \Carbon\Carbon::parse(old('due_date'))->format('Y-m-d') : '' }}"
                                       >
                                <small id="due_date_preview" class="text-muted d-block mt-1"></small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Công việc cha <span class="text-muted small">(tùy chọn — để tạo công việc con)</span></label>
                            <select name="parent_id" class="form-select">
                                <option value="">-- Không có --</option>
                                @foreach(\App\Models\TaskAssignment::where('status', '!=', 'completed')
                                    ->where('status', '!=', 'cancelled')
                                    ->where('created_by', auth()->id())
                                    ->latest()->limit(50)->get() as $pt)
                                    <option value="{{ $pt->id }}" {{ (old('parent_id', $parentId ?? '')) == $pt->id ? 'selected' : '' }}>
                                        {{ $pt->code }} — {{ $pt->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @include('task_assignments.partials.debt-create')
                        {{-- Multiple assignees allowed according to the current user's permissions. --}}
                        @if($allowedAssignees->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ph-users me-1 text-primary"></i>Giao việc cho thành viên
                                <span class="badge bg-primary ms-1">Có thể chọn nhiều người</span>
                            </label>
                            <div class="border rounded p-3" style="background:#f8fafc">
                                <p class="small text-muted mb-3">
                                    Chọn một hoặc nhiều thành viên cùng thực hiện công việc.
                                    Công việc hoàn thành khi <strong>tất cả</strong> người nhận báo cáo xong.
                                </p>
                                @include('task_assignments.partials.assignee-picker', ['selectedAssigneeIds' => old('assignee_ids', $currentAssigneeIds ?? [])])
                                @error('assignee_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        @endif


                    </div>
                </div>
            </div>

            {{-- ── RIGHT: workflow picker ── --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header py-2">
                        <span class="fw-semibold"><i class="ph-flow-arrow me-1"></i>Duyệt công bố công việc</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Công việc mới là bản gửi duyệt. Chỉ khi người được chọn đồng ý, công việc mới được gửi cho người chủ trì và những người nhận đã chọn.</p>
                        <label for="publication-approver" class="form-label fw-semibold">Người duyệt công bố <span class="text-danger">*</span></label>
                        <select id="publication-approver" name="publication_approver_id" class="form-select" required>
                            <option value="">Chọn người duyệt công việc</option>
                            @foreach($users as $person)
                                @if((int)$person->id !== (int)auth()->id())
                                <option value="{{ $person->id }}" @selected((string)old('publication_approver_id') === (string)$person->id)>{{ $person->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('publication_approver_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <small class="text-muted d-block mt-2">Người duyệt có thể duyệt công bố hoặc yêu cầu bạn chỉnh sửa. Người nhận chưa thấy công việc trong thời gian chờ duyệt.</small>
                    </div>
                </div>

                <div class="mt-3 d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph-paper-plane-tilt me-1"></i>Gửi duyệt
                    </button>
                    <a href="{{ route($indexRoute ?? 'task-assignments.index') }}" class="btn btn-outline-secondary">Huy</a>
                </div>
            </div>
        </div>
    </form>
</div>
</div>

<script>
function selectWf(id) {
    document.querySelectorAll('.wf-card').forEach(c => {
        c.classList.remove('selected');
        const inp = c.querySelector('input[type=radio]');
        if (inp) inp.checked = false;
    });
    const target = id !== null ? document.getElementById('wfl-' + id) : document.getElementById('wfl-none');
    if (target) {
        target.classList.add('selected');
        const inp = target.querySelector('input[type=radio]');
        if (inp) inp.checked = true;
    }
}

function formatDueDateForDisplay(value) {
    if (!value) {
        return '';
    }

    const [year, month, day] = value.split('-');
    return `${day}/${month}/${year} (cuối ngày · 24:00)`;
}

function initDueDatePreview() {
    const input = document.getElementById('due_date_input');
    const preview = document.getElementById('due_date_preview');
    if (!input || !preview) {
        return;
    }

    const render = () => {
        if (!input.value) {
            preview.textContent = 'Chưa chọn hạn chót';
            return;
        }

        preview.textContent = `Hạn chót: ${formatDueDateForDisplay(input.value)}`;
    };

    input.addEventListener('input', render);
    input.addEventListener('change', render);
    render();
}

document.addEventListener('DOMContentLoaded', initDueDatePreview);
</script>
@endsection

@include('task_assignments.partials.description-editor')
