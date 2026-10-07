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
.op-workspace .op-table{font-size:14px;width:100%;min-width:1080px;margin:0;table-layout:auto}
.op-workspace .op-table th{background:#f2f5f9;color:#52647a;font-size:12px;font-weight:700;white-space:nowrap;padding:12px 16px;border-bottom:1px solid #dce4ee}
.op-workspace .op-table td{padding:14px 16px;border-bottom:1px solid #edf0f5;vertical-align:middle}
.op-workspace .op-table tbody tr:hover{background:#f8fbff}
.op-workspace .op-table .op-title{display:block;font-weight:600;color:#245b9b;text-decoration:none;font-size:16px;line-height:1.45;min-width:280px;max-width:520px}
.op-workspace .op-table .op-title:hover{text-decoration:underline}
.op-workspace .op-table .op-date{white-space:nowrap;font-size:13px}
.op-workspace .op-table .op-meta{margin-top:5px;font-size:12px;color:#718198}
.op-workspace .op-badge{display:inline-block;padding:5px 9px;border-radius:5px;font-size:12px;font-weight:600;line-height:1.4;white-space:nowrap;background:#eef1f5;color:#586879}
.op-workspace .op-badge-blue{background:#eaf2ff;color:#235ea5}.op-workspace .op-badge-green{background:#e5f5ed;color:#237448}.op-workspace .op-badge-red{background:#fdecec;color:#b53737}.op-workspace .op-badge-yellow{background:#fff4d8;color:#946600}
.op-workspace .op-footer{padding:14px 20px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}
.op-workspace .op-footer .pagination{margin-bottom:0}.op-workspace .op-empty{text-align:center;color:#718198;padding:40px!important}
@media(max-width:767px){.op-workspace{padding:16px 10px}.op-workspace h1{font-size:21px}.op-workspace .op-filters,.op-workspace .op-panel-title{padding:14px}.op-workspace .op-table td,.op-workspace .op-table th{padding:12px}}
.op-workspace .op-content-meta{display:flex;align-items:center;flex-wrap:wrap;gap:6px 10px}
.op-workspace .op-content-meta .op-badge{padding:3px 7px;font-size:11px}
.op-workspace .op-table th:first-child,.op-workspace .op-table td:first-child{width:52px}
.op-workspace .op-table td:nth-child(6){white-space:nowrap}
.op-workspace .op-table td:nth-child(4){min-width:165px}
.op-workspace .op-table tbody tr:hover>td{background:#f8fbff}
.op-workspace .op-actions{white-space:nowrap;width:125px}
.op-workspace .op-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:4px;min-height:34px;min-width:102px;padding:6px 10px;font-size:12px;font-weight:600;border-radius:5px;white-space:nowrap;text-decoration:none;transition:background-color .15s}
.op-workspace .op-action-receive{background:#0d827a;border-color:#0d827a;color:#fff!important}
.op-workspace .op-action-receive:hover{background:#096c66;border-color:#096c66}
.op-workspace .op-action-update{background:#eaf2ff;border-color:#a9c7f5;color:#235ea5!important}
.op-workspace .op-action-update:hover{background:#dbe9ff}
.op-workspace .op-action-vote{background:#f2ecff;border-color:#cdb9f5;color:#7043ab!important}
.op-workspace .op-action-vote:hover{background:#e6dbfb}
.op-workspace .op-action-complete{background:#e5f5ed;border-color:#a9d8bc;color:#237448!important}
.op-workspace .op-action-complete:hover{background:#d2ecdf}
.op-workspace .op-action-view{background:#f8fafc;border-color:#cbd5e1;color:#52647a!important}
.op-workspace .op-action-view:hover{background:#eaf0f5}
@media(max-width:767px){.op-workspace .op-table .op-title{font-size:15px}}
</style>
<div class="container"><div class="op-workspace">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1>Điều hành &amp; Giao việc</h1><div class="op-subtitle">Một danh sách chung cho công việc và biểu quyết liên quan đến bạn.</div></div>
        @if($canCreate)<div class="d-flex flex-wrap gap-2"><a href="{{ route('tasks.create') }}" class="btn btn-primary">Tạo / Giao việc</a><a href="{{ route('operating.proposals.create') }}" class="btn btn-outline-primary">Tạo biểu quyết</a></div>@endif
    </div>
    @include('operating.messages')
    <section class="op-panel" id="bieu-quyet">
        @include('operating.list-filters')
        <div class="op-panel-title"><h2>Danh sách công việc &amp; biểu quyết</h2><span class="op-count">{{ number_format($listing->total()) }} kết quả</span></div>
        <div class="table-responsive"><table class="table op-table"><thead><tr><th>STT</th><th>Nội dung</th><th>Ngày tạo</th><th>Người giao / Chủ trì</th><th>Hạn</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
        @forelse($listing as $entry)
            @php
                $entity=$entry->entity;
                $isVote=$entry->type==='vote';
                $tone=$isVote ? match($entity->status){'approved'=>'green','rejected','no_quorum'=>'red',default=>$entity->closes_at->isPast()?'yellow':'blue'} : match($entity->status){'processing','in_progress'=>'blue','done'=>'green','completed'=>'yellow','rejected'=>'red',default=>''};
                $deadline=$isVote?$entity->closes_at:$entity->due_date;
            @endphp
            <tr>
                <td class="op-muted">{{ $listing->firstItem()+$loop->index }}</td>
                <td><a class="op-title" href="{{ $isVote ? route('operating.proposals.show',$entity) : ($entity->trashed()?route('operating.deleted-task',$entity->id):route('tasks.show',$entity)) }}">{{ $entity->title }}</a><div class="op-meta op-content-meta"><span>{{ $isVote?'Đề xuất #'.$entity->id:$entity->code }} @if(!$isVote && $entity->trashed()) · Đã xóa @endif</span><span class="op-badge {{ $isVote?'op-badge-blue':'' }}">{{ $isVote?'Biểu quyết':($entity->work_kind==='coordination'?'Yêu cầu phối hợp':'Giao thực hiện') }}</span></div></td>
                <td class="op-date">{{ $entity->created_at?->format('d/m/Y') }}<div class="op-meta">{{ $entity->created_at?->format('H:i') }}</div></td>
                <td>{{ $entity->creator?->name ?? '—' }}<div class="op-meta">@if($isVote){{ $entity->voted_count }} / {{ $entity->votes_count }} thành viên đã bỏ phiếu @else Chủ trì: {{ $entity->assignees->firstWhere('user_id',$entity->accountable_user_id)?->user?->name ?? 'Chưa chỉ định' }} @endif</div></td>
                <td class="op-date">{{ $deadline?->format('d/m/Y H:i') ?? '—' }}@if(!$isVote && $deadline?->isPast() && !in_array($entity->status,['done','cancelled']))<div class="mt-1"><span class="op-badge op-badge-red">Quá hạn</span></div>@endif</td>
                <td>
                    <span class="op-badge op-badge-{{ $tone }}">{{ $isVote?$entity->statusLabel():($entity->status==='in_progress'?'Đang thực hiện':$entity->operatingStatusLabel()) }}</span>
                </td>
                <td class="op-actions">
                    @if($isVote)
                        @php
                            $myBallot=$entity->votes->firstWhere('user_id',auth()->id());
                            $canVote=$entity->status==='open' && $entity->closes_at->isFuture() && $myBallot && !$myBallot->voted_at;
                        @endphp
                        <a class="btn btn-sm {{ $canVote ? 'op-action-vote' : 'op-action-view' }}" href="{{ route('operating.proposals.show',$entity) }}">{{ $canVote ? 'Biểu quyết' : 'Xem chi tiết' }}</a>
                    @else
                        @php
                            $myReceipt=$entity->assignees->firstWhere('user_id',auth()->id());
                            $canReceive=!$entity->trashed() && !in_array($entity->status,['done','cancelled','completed','draft'],true)
                                && $myReceipt && $myReceipt->status==='pending' && !$myReceipt->accepted_at;
                        @endphp
                        @if($canReceive)
                            <form method="POST" action="{{ route('tasks.accept',$entity) }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm op-action-receive" aria-label="Tiếp nhận công việc {{ $entity->title }}"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Tiếp nhận</button>
                            </form>
                        @elseif(!$entity->trashed() && $entity->status==='completed' && ((int)$entity->created_by===(int)auth()->id() || auth()->user()->hasRole('admin')))
                            <a class="btn btn-sm op-action-complete" href="{{ route('task-assignments.verify-form',$entity) }}">Hoàn thành</a>
                        @elseif(!$entity->trashed() && !in_array($entity->status,['done','cancelled','completed','draft'],true) && $myReceipt && in_array($myReceipt->status,['pending','processing','in_progress'],true) && (!$entity->work_kind || $myReceipt->accepted_at))
                            <a class="btn btn-sm op-action-update" href="{{ route('tasks.show',$entity) }}#taskStatusPanel">Cập nhật</a>
                        @else
                            <a class="btn btn-sm op-action-view" href="{{ $entity->trashed()?route('operating.deleted-task',$entity->id):route('tasks.show',$entity) }}">Xem chi tiết</a>
                        @endif
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="7" class="op-empty">{{ empty($selectedKinds)?'Chọn ít nhất một loại nghiệp vụ để xem danh sách.':'Không có kết quả phù hợp với bộ lọc.' }}</td></tr>@endforelse
        </tbody></table></div>
        <div class="op-footer"><span class="op-muted">Hiển thị {{ $listing->firstItem()??0 }}–{{ $listing->lastItem()??0 }} / {{ number_format($listing->total()) }} kết quả</span>{{ $listing->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    </section>
</div></div>
@endsection
