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
<div class="container"><div class="op-workspace">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1>Điều hành &amp; Giao việc</h1><div class="op-subtitle">Một danh sách chung cho công việc và biểu quyết liên quan đến bạn.</div></div>
        @if($canCreate)<div class="d-flex flex-wrap gap-2"><a href="{{ route('tasks.create') }}" class="btn btn-primary">Tạo / Giao việc</a><a href="{{ route('operating.proposals.create') }}" class="btn btn-outline-primary">Tạo biểu quyết</a></div>@endif
    </div>
    @include('operating.messages')
    <section class="op-panel" id="bieu-quyet">
        @include('operating.list-filters')
        <div class="op-panel-title"><h2>Danh sách công việc &amp; biểu quyết</h2><span class="op-count">{{ number_format($listing->total()) }} kết quả</span></div>
        <div class="table-responsive"><table class="table op-table"><thead><tr><th>STT</th><th>Nội dung</th><th>Ngày tạo</th><th>Loại</th><th>Người giao / Chủ trì</th><th>Hạn</th><th>Trạng thái</th></tr></thead><tbody>
        @forelse($listing as $entry)
            @php
                $entity=$entry->entity;
                $isVote=$entry->type==='vote';
                $tone=$isVote ? match($entity->status){'approved'=>'green','rejected','no_quorum'=>'red',default=>$entity->closes_at->isPast()?'yellow':'blue'} : match($entity->status){'processing','in_progress'=>'blue','done'=>'green','completed'=>'yellow','rejected'=>'red',default=>''};
                $deadline=$isVote?$entity->closes_at:$entity->due_date;
            @endphp
            <tr>
                <td class="op-muted">{{ $listing->firstItem()+$loop->index }}</td>
                <td><a class="op-title" href="{{ $isVote ? route('operating.proposals.show',$entity) : ($entity->trashed()?route('operating.deleted-task',$entity->id):route('tasks.show',$entity)) }}">{{ $entity->title }}</a><div class="op-meta">{{ $isVote?'Đề xuất #'.$entity->id:$entity->code }} @if(!$isVote && $entity->trashed()) · Đã xóa @endif</div></td>
                <td class="op-date">{{ $entity->created_at?->format('d/m/Y') }}<div class="op-meta">{{ $entity->created_at?->format('H:i') }}</div></td>
                <td><span class="op-badge {{ $isVote?'op-badge-blue':'' }}">{{ $isVote?'Biểu quyết':($entity->work_kind==='coordination'?'Yêu cầu phối hợp':'Giao thực hiện') }}</span></td>
                <td>{{ $entity->creator?->name ?? '—' }}<div class="op-meta">@if($isVote){{ $entity->voted_count }} / {{ $entity->votes_count }} thành viên đã bỏ phiếu @else Chủ trì: {{ $entity->assignees->firstWhere('user_id',$entity->accountable_user_id)?->user?->name ?? 'Chưa chỉ định' }} @endif</div></td>
                <td class="op-date">{{ $deadline?->format('d/m/Y H:i') ?? '—' }}@if(!$isVote && $deadline?->isPast() && !in_array($entity->status,['done','cancelled']))<div class="mt-1"><span class="op-badge op-badge-red">Quá hạn</span></div>@endif</td>
                <td>
                    <span class="op-badge op-badge-{{ $tone }}">{{ $isVote?$entity->statusLabel():($entity->status==='in_progress'?'Đang thực hiện':$entity->operatingStatusLabel()) }}</span>
                    @if(!$isVote)
                        @php
                            $myReceipt=$entity->assignees->firstWhere('user_id',auth()->id());
                            $canReceive=!$entity->trashed() && !in_array($entity->status,['done','cancelled','completed','draft'],true)
                                && $myReceipt && $myReceipt->status==='pending' && !$myReceipt->accepted_at;
                        @endphp
                        @if($canReceive)
                            <form method="POST" action="{{ route('tasks.accept',$entity) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Tiếp nhận việc</button>
                            </form>
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
