@extends($layout)
@section('title', 'Điều hành & Giao việc')
@section('content')
<style>
.op-workspace{color:#243449;max-width:1800px;margin:auto;padding:24px;font-size:14px}
.op-workspace .op-subtitle,.op-workspace .op-muted{color:#68788d;font-size:13px}
.op-workspace h1{font-size:24px;font-weight:700;margin:0 0 6px}
.op-workspace .op-guide,.op-workspace .op-panel{background:#fff;border:1px solid #dce4ee;border-radius:12px;overflow:hidden;box-shadow:0 3px 14px rgba(31,51,78,.035)}
.op-workspace .op-guide{padding:16px 20px;margin-bottom:20px}
.op-workspace .op-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}
.op-workspace .op-tabs a{border-radius:20px;font-size:13px;padding:7px 13px}
.op-workspace .op-panel-title{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid #e5eaf1}
.op-workspace h2{font-size:17px;font-weight:700;margin:0}
.op-workspace .op-count{background:#edf3fc;color:#315a90;border-radius:20px;font-size:12px;padding:5px 10px;white-space:nowrap}
.op-workspace .op-filters{padding:16px 20px;background:#f8fafc;border-bottom:1px solid #e5eaf1}
.op-workspace .op-filters label{display:block;font-size:12px;color:#52647a;font-weight:600;margin-bottom:6px}
.op-workspace .form-control,.op-workspace .form-select,.op-workspace .btn{font-size:13px}
.op-workspace .op-table{font-size:14px;width:100%;margin:0}
.op-workspace .op-table th{background:#f2f5f9;color:#52647a;font-size:12px;font-weight:700;white-space:nowrap;padding:12px 16px;border-bottom:1px solid #dce4ee}
.op-workspace .op-table td{padding:14px 16px;border-bottom:1px solid #edf0f5;vertical-align:middle}
.op-workspace .op-table tbody tr:hover{background:#f8fbff}
.op-workspace .op-table .op-title{display:block;font-weight:600;color:#245b9b;text-decoration:none;min-width:220px;max-width:480px}
.op-workspace .op-table .op-title:hover{text-decoration:underline}
.op-workspace .op-table .op-date{white-space:nowrap;font-size:13px}
.op-workspace .op-table .op-meta{margin-top:5px;font-size:12px;color:#718198}
.op-workspace .op-badge{display:inline-block;padding:5px 9px;border-radius:6px;font-size:12px;font-weight:600;background:#eef1f5;color:#586879}
.op-workspace .op-badge-blue{background:#eaf2ff;color:#235ea5}.op-workspace .op-badge-green{background:#e5f5ed;color:#237448}.op-workspace .op-badge-red{background:#fdecec;color:#b53737}.op-workspace .op-badge-yellow{background:#fff4d8;color:#946600}
.op-workspace .op-footer{padding:14px 20px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}
.op-workspace .op-footer .pagination{margin-bottom:0}.op-workspace .op-empty{text-align:center;color:#718198;padding:40px!important}
@media(max-width:767px){.op-workspace{padding:16px 10px}.op-workspace h1{font-size:21px}.op-workspace .op-filters,.op-workspace .op-panel-title{padding:14px}.op-workspace .op-table td,.op-workspace .op-table th{padding:12px}}
</style>
<div class="op-workspace">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1>Điều hành &amp; Giao việc</h1><div class="op-subtitle">Theo dõi công việc, tiến độ thực hiện và các đề xuất biểu quyết.</div></div>
        @if($canCreate)<div class="d-flex flex-wrap gap-2"><a href="{{ route('tasks.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Giao thực hiện / Phối hợp</a><a href="{{ route('operating.proposals.create') }}" class="btn btn-outline-primary">Đề xuất biểu quyết</a></div>@endif
    </div>
    @include('operating.messages')
    <div class="op-guide"><strong>Chọn đúng loại nghiệp vụ</strong><div class="row mt-1 g-3">
        <div class="col-md-4"><strong>Giao thực hiện</strong><div class="op-muted mt-1">Phân công → tiếp nhận → thực hiện → gửi kết quả → nghiệm thu.</div></div>
        <div class="col-md-4"><strong>Yêu cầu phối hợp</strong><div class="op-muted mt-1">Đề nghị hỗ trợ → tiếp nhận hoặc phản hồi → phối hợp → xác nhận.</div></div>
        <div class="col-md-4"><strong>Đề xuất biểu quyết</strong><div class="op-muted mt-1">Mời thành viên → bỏ phiếu → chốt kết quả → giao việc triển khai.</div></div>
    </div></div>
    <nav class="op-tabs" aria-label="Nhóm công việc">@foreach(['mine'=>'Liên quan đến tôi','received'=>'Tôi nhận','unaccepted'=>'Chưa tiếp nhận','working'=>'Đang thực hiện','reported'=>'Báo cáo đã gửi','history'=>'Lịch sử','votes'=>'Biểu quyết','assigned'=>'Tôi giao','execution'=>'Giao thực hiện','coordination'=>'Phối hợp','overdue'=>'Quá hạn','verification'=>'Chờ nghiệm thu'] + (auth()->user()->hasRole('admin') ? ['all'=>'Tất cả công việc','deleted'=>'Đã xóa'] : []) as $key=>$label)<a class="btn {{ $filter===$key?'btn-primary':'btn-outline-secondary' }}" href="{{ route('operating.index',['filter'=>$key]) }}" @if($filter===$key) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
    @if($filter!=='votes')
    <section class="op-panel mb-4" id="cong-viec">
        <div class="op-panel-title"><h2><i class="bi bi-list-task me-2"></i>Công việc</h2><span class="op-count">{{ number_format($tasks->total()) }} công việc</span></div>
        @include('operating.list-filters',['listType'=>'task','statusOptions'=>\App\Models\TaskAssignment::STATUS_LABELS])
        <div class="table-responsive"><table class="table op-table"><thead><tr><th scope="col">STT</th><th scope="col">Công việc</th><th scope="col">Ngày tạo</th><th scope="col">Loại</th><th scope="col">Người giao / Chủ trì</th><th scope="col">Hạn thực hiện</th><th scope="col">Trạng thái</th></tr></thead><tbody>
        @forelse($tasks as $task)
            @php $statusTone=match($task->status){'processing','in_progress'=>'blue','done'=>'green','completed'=>'yellow','rejected'=>'red',default=>''}; @endphp
            <tr><td class="op-muted">{{ $tasks->firstItem()+$loop->index }}</td><td><a class="op-title" href="{{ $task->trashed() ? route('operating.deleted-task',$task->id) : route('tasks.show',$task) }}">{{ $task->title }}</a><div class="op-meta">{{ $task->code }} @if($task->trashed())<span class="op-badge">Đã xóa</span>@endif</div></td>
                <td class="op-date">{{ $task->created_at?->format('d/m/Y') }}<div class="op-meta">{{ $task->created_at?->format('H:i') }}</div></td><td><span class="op-badge {{ $task->work_kind==='coordination'?'op-badge-blue':'' }}">{{ $task->work_kind==='coordination'?'Phối hợp':'Giao thực hiện' }}</span></td>
                <td>{{ $task->creator?->name ?? '—' }}<div class="op-meta">Chủ trì: {{ $task->assignees->firstWhere('user_id',$task->accountable_user_id)?->user?->name ?? 'Chưa chỉ định' }}</div></td>
                <td class="op-date">{{ $task->due_date?->format('d/m/Y H:i') ?? '—' }} @if($task->due_date?->isPast() && !in_array($task->status,['done','cancelled']))<div class="mt-1"><span class="op-badge op-badge-red">Quá hạn</span></div>@endif</td><td><span class="op-badge op-badge-{{ $statusTone }}">{{ $task->status==='in_progress' ? 'Đang thực hiện' : $task->operatingStatusLabel() }}</span></td></tr>
        @empty<tr><td colspan="7" class="op-empty">Không có công việc phù hợp với bộ lọc đã chọn.</td></tr>@endforelse
        </tbody></table></div>
        <div class="op-footer"><span class="op-muted">Hiển thị {{ $tasks->firstItem() ?? 0 }}–{{ $tasks->lastItem() ?? 0 }} / {{ number_format($tasks->total()) }} công việc</span>{{ $tasks->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    </section>
    @endif
    <section class="op-panel" id="bieu-quyet">
        <div class="op-panel-title"><h2><i class="bi bi-ui-checks me-2"></i>{{ auth()->user()->hasRole('admin') ? 'Danh sách biểu quyết' : 'Biểu quyết liên quan đến tôi' }}</h2><span class="op-count">{{ number_format($proposals->total()) }} đề xuất</span></div>
        @include('operating.list-filters',['listType'=>'proposal','statusOptions'=>['open'=>'Đang mở','expired'=>'Hết hạn / Chờ chốt','approved'=>'Thông qua','rejected'=>'Không thông qua','no_quorum'=>'Không đủ số phiếu']])
        <div class="table-responsive"><table class="table op-table"><thead><tr><th scope="col">STT</th><th scope="col">Đề xuất</th><th scope="col">Ngày tạo</th><th scope="col">Người mở</th><th scope="col">Tiến độ bỏ phiếu</th><th scope="col">Hạn bỏ phiếu</th><th scope="col">Trạng thái / Kết quả</th></tr></thead><tbody>
        @forelse($proposals as $proposal)
            @php $proposalTone=match($proposal->status){'approved'=>'green','rejected','no_quorum'=>'red',default=>$proposal->closes_at->isPast()?'yellow':'blue'}; @endphp
            <tr><td class="op-muted">{{ $proposals->firstItem()+$loop->index }}</td><td><a class="op-title" href="{{ route('operating.proposals.show',$proposal) }}">{{ $proposal->title }}</a><div class="op-meta">Đề xuất #{{ $proposal->id }}</div></td><td class="op-date">{{ $proposal->created_at?->format('d/m/Y') }}<div class="op-meta">{{ $proposal->created_at?->format('H:i') }}</div></td><td>{{ $proposal->creator?->name ?? '—' }}</td><td><strong>{{ $proposal->voted_count }} / {{ $proposal->votes_count }}</strong><div class="op-meta">thành viên đã bỏ phiếu</div></td><td class="op-date">{{ $proposal->closes_at->format('d/m/Y H:i') }}</td><td><span class="op-badge op-badge-{{ $proposalTone }}">{{ $proposal->statusLabel() }}</span></td></tr>
        @empty<tr><td colspan="7" class="op-empty">Không có đề xuất biểu quyết phù hợp với bộ lọc đã chọn.</td></tr>@endforelse
        </tbody></table></div>
        <div class="op-footer"><span class="op-muted">Hiển thị {{ $proposals->firstItem() ?? 0 }}–{{ $proposals->lastItem() ?? 0 }} / {{ number_format($proposals->total()) }} đề xuất</span>{{ $proposals->fragment('bieu-quyet')->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    </section>
</div>
@endsection
