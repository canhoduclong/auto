@extends($layout)
@section('title', 'Điều hành & Giao việc')
@section('content')
<div class="container-fluid p-3">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h4>Điều hành & Giao việc</h4>
        @if($canCreate)<div class="d-flex gap-2"><a href="{{ route('tasks.create') }}" class="btn btn-primary">Giao thực hiện / Phối hợp</a><a href="{{ route('operating.proposals.create') }}" class="btn btn-outline-primary">Đề xuất biểu quyết</a></div>@endif
    </div>
    @include('operating.messages')
    <div class="card mb-3"><div class="card-body">
        <strong>Chọn đúng loại nghiệp vụ</strong>
        <div class="row mt-2 g-3">
            <div class="col-md-4"><strong>Giao thực hiện</strong><div class="small text-muted">Người giao phân công → tiếp nhận → thực hiện → gửi kết quả → nghiệm thu.</div></div>
            <div class="col-md-4"><strong>Yêu cầu phối hợp</strong><div class="small text-muted">Đề nghị hỗ trợ → tiếp nhận hoặc phản hồi → phối hợp → gửi kết quả → xác nhận.</div></div>
            <div class="col-md-4"><strong>Đề xuất biểu quyết</strong><div class="small text-muted">Chọn người tham gia → bỏ phiếu → chốt theo quy tắc → giao việc từ đề xuất được thông qua.</div></div>
        </div>
    </div></div>
    <div class="d-flex flex-wrap gap-2 mb-3">@foreach(['mine'=>'Liên quan đến tôi','received'=>'Tôi nhận','assigned'=>'Tôi giao','execution'=>'Giao thực hiện','coordination'=>'Phối hợp','overdue'=>'Quá hạn','verification'=>'Chờ tôi nghiệm thu'] as $key=>$label)<a class="btn btn-sm {{ $filter===$key?'btn-primary':'btn-outline-secondary' }}" href="{{ route('operating.index',['filter'=>$key]) }}">{{ $label }}</a>@endforeach</div>
    <div class="card mb-4"><div class="card-header fw-semibold">Công việc</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Công việc</th><th>Loại</th><th>Người giao / Chủ trì</th><th>Hạn</th><th>Trạng thái</th></tr></thead><tbody>
    @forelse($tasks as $task)<tr><td><a href="{{ route('tasks.show',$task) }}">{{ $task->title }}</a><div class="small text-muted">{{ $task->code }}</div></td><td>{{ $task->work_kind==='coordination'?'Phối hợp':'Giao thực hiện' }}</td><td>{{ $task->creator?->name }}<div class="small text-muted">{{ $task->assignees->firstWhere('user_id',$task->accountable_user_id)?->user?->name ?? 'Chưa chỉ định chủ trì (việc cũ)' }}</div></td><td>{{ $task->due_date?->format('d/m/Y H:i') }} @if($task->due_date?->isPast() && !in_array($task->status,['done','cancelled']))<span class="badge bg-danger">Quá hạn</span>@endif</td><td>{{ $task->operatingStatusLabel() }}</td></tr>
    @empty<tr><td colspan="5" class="text-muted p-3">Chưa có công việc phù hợp.</td></tr>@endforelse
    </tbody></table></div><div class="card-body">{{ $tasks->links() }}</div></div>
    <div class="card" id="bieu-quyet"><div class="card-header fw-semibold">Biểu quyết liên quan đến tôi</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Đề xuất</th><th>Người mở</th><th>Thành viên</th><th>Hạn bỏ phiếu</th><th>Kết quả</th></tr></thead><tbody>
    @forelse($proposals as $proposal)<tr><td><a href="{{ route('operating.proposals.show',$proposal) }}">{{ $proposal->title }}</a></td><td>{{ $proposal->creator?->name }}</td><td>{{ $proposal->votes_count }}</td><td>{{ $proposal->closes_at->format('d/m/Y H:i') }}</td><td>{{ $proposal->statusLabel() }}</td></tr>@empty<tr><td colspan="5" class="p-3 text-muted">Chưa có đề xuất biểu quyết.</td></tr>@endforelse
    </tbody></table></div><div class="card-body">{{ $proposals->links() }}</div></div>
</div>
@endsection
