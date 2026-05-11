@extends($layout ?? 'layouts.admin')

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
    
    <a href="{{ route('task-assignments.my-tasks') }}" class="btn btn-sm btn-outline-secondary me-3">
        <i class="ph ph-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0">Tạo công việc mới</h4>
        <small class="text-muted">Điền thông tin và chọn quy trình phê duyệt</small>
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
                            <label class="form-label fw-semibold">Tiêu đề công việc <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}" maxlength="255" placeholder="Nham tat, mo ta ngan gon...">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mô tả chi tiết</label>
                            <textarea name="description" class="form-control" rows="5" maxlength="5000"
                                      placeholder="Ghi rõ yêu cầu, kết quả cần đạt, ghi chú...">{{ old('description') }}</textarea>
                        </div>

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
                                <input type="datetime-local" name="due_date" id="due_date_input" class="form-control"
                                       value="{{ old('due_date') ? \Carbon\Carbon::parse(old('due_date'))->format('Y-m-d\\TH:i') : '' }}"
                                       step="60">
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

                        {{-- Custom assignees (only shown if user has delegation rights) --}}
                        @if($allowedAssignees->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ph-users me-1 text-primary"></i>Giao việc cho thành viên
                                <span class="badge bg-primary ms-1">Custom</span>
                            </label>
                            <div class="border rounded p-3" style="background:#f8fafc">
                                <p class="small text-muted mb-3">
                                    Bạn được phép giao trực tiếp cho các thành viên sau. Có thể chọn nhiều người.
                                    Công việc hoàn thành khi <strong>tất cả</strong> người nhận báo cáo xong.
                                </p>
                                <div class="row g-2">
                                    @foreach($allowedAssignees as $au)
                                        <div class="col-sm-6 col-md-4">
                                            <label class="d-flex align-items-center gap-2 p-2 border rounded"
                                                   style="cursor:pointer;background:#fff">
                                                <input type="checkbox" name="assignee_ids[]" value="{{ $au->id }}"
                                                       {{ in_array($au->id, old('assignee_ids', [])) ? 'checked' : '' }}>
                                                <span class="small fw-semibold">{{ $au->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('assignee_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Đính kèm <span class="text-muted small">(ảnh, PDF, doc — tối đa 10MB mỗi file)</span></label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,.pdf,.doc,.docx,.xlsx,.zip">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── RIGHT: workflow picker ── --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header py-2">
                        <span class="fw-semibold"><i class="ph-flow-arrow me-1"></i>Quy trình phê duyệt</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Chọn quy trình cho công việc này. Thành viên tương ứng trong từng bước sẽ phê duyệt để hoàn thành công việc.</p>

                        @forelse($workflows as $wf)
                            <label class="wf-card mb-2 d-block {{ old('approval_flow_id') == $wf->id ? 'selected' : '' }}"
                                   id="wfl-{{ $wf->id }}" onclick="selectWf({{ $wf->id }})">
                                <input type="radio" name="approval_flow_id" value="{{ $wf->id }}"
                                       {{ old('approval_flow_id') == $wf->id ? 'checked' : '' }} class="d-none">
                                <div class="fw-semibold" style="font-size:13px">{{ $wf->name }}</div>
                                <div class="text-muted" style="font-size:11px">{{ $wf->code }}</div>
                                <div class="wf-steps-preview">
                                    @foreach($wf->steps->sortBy('step_order') as $stp)
                                        <span class="wf-step-badge">{{ $stp->step_order }}. {{ $stp->role_slug }}</span>
                                        @if(!$loop->last)<span class="ta-meta">→</span>@endif
                                    @endforeach
                                </div>
                            </label>
                        @empty
                            <div class="text-muted small mb-3">Chưa có quy trình nào được cấu hình cho hoạt động "Giao việc". Hãy vào <a href="{{ route('approval-workflows.create') }}">Quy trình duyệt</a> để tạo.</div>
                        @endforelse

                        <label class="wf-card mb-2 d-block {{ old('approval_flow_id') === '' && !old('approval_flow_id') ? '' : '' }}"
                               id="wfl-none" onclick="selectWf(null)">
                            <input type="radio" name="approval_flow_id" value="" class="d-none">
                            <div class="fw-semibold" style="font-size:13px">Không cần phê duyệt</div>
                            <div class="text-muted" style="font-size:11px">Công việc sẽ chuyển sang trạng thái đang thực hiện ngay.</div>
                        </label>
                    </div>
                </div>

                <div class="mt-3 d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph-paper-plane-tilt me-1"></i>Lưu lại
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

    // Input format from datetime-local: YYYY-MM-DDTHH:mm
    const [datePart, timePart] = value.split('T');
    if (!datePart || !timePart) {
        return value;
    }

    const [year, month, day] = datePart.split('-');
    return `${day}/${month}/${year} ${timePart}`;
}

function initDueDatePreview() {
    const input = document.getElementById('due_date_input');
    const preview = document.getElementById('due_date_preview');
    if (!input || !preview) {
        return;
    }

    const render = () => {
        if (!input.value) {
            preview.textContent = 'Chua chon han chot';
            return;
        }

        preview.textContent = `Hien thi: ${formatDueDateForDisplay(input.value)}`;
    };

    input.addEventListener('input', render);
    input.addEventListener('change', render);
    render();
}

document.addEventListener('DOMContentLoaded', initDueDatePreview);
</script>
@endsection
