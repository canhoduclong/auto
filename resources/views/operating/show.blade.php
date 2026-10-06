@extends($layout)
@section('title', 'Biểu quyết: '.$proposal->title)
@section('content')
@php
    $cast=$proposal->votes->whereNotNull('voted_at');
    $myVote=$proposal->votes->firstWhere('user_id',auth()->id());
    $canClose=(int)$proposal->created_by===(int)auth()->id() || auth()->user()->hasRole('admin');
    $labels=['open'=>'Đang mở','approved'=>'Thông qua','rejected'=>'Không thông qua','no_quorum'=>'Không đủ số phiếu'];
    $choices=['agree'=>'Đồng ý','disagree'=>'Không đồng ý','abstain'=>'Không ý kiến'];
@endphp
<div class="container-fluid p-3"><a href="{{ route('operating.index') }}">← Điều hành & Giao việc</a><h4 class="mt-3">{{ $proposal->title }}</h4>@include('operating.messages')
<div class="row g-3"><div class="col-lg-8"><div class="card card-body mb-3"><div class="task-description">{!! \App\Support\TaskDescription::render($proposal->description) !!}</div></div>
<div class="card mb-3"><div class="card-header">Phiếu biểu quyết — công khai với các thành viên tham gia</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Thành viên</th><th>Phiếu</th><th>Ý kiến</th><th>Thời điểm</th></tr></thead><tbody>@foreach($proposal->votes as $vote)<tr><td>{{ $vote->user?->name }}</td><td>{{ $choices[$vote->choice] ?? 'Chưa bỏ phiếu' }}</td><td style="white-space:pre-wrap">{{ $vote->comment }}</td><td>{{ $vote->voted_at?->format('d/m/Y H:i') }}</td></tr>@endforeach</tbody></table></div></div>
@if($myVote && !$myVote->voted_at && $proposal->status==='open' && $proposal->closes_at->isFuture())
<form class="card card-body mb-3" method="POST" action="{{ route('operating.proposals.vote',$proposal) }}">@csrf<h5>Phiếu của bạn</h5><div class="d-flex flex-wrap gap-3 mb-3">@foreach($choices as $key=>$label)<label><input type="radio" name="choice" value="{{ $key }}" required> {{ $label }}</label>@endforeach</div><label class="form-label">Ý kiến</label><textarea class="form-control mb-3" name="comment" maxlength="2000" rows="3"></textarea><div><button type="submit" class="btn btn-primary">Gửi phiếu biểu quyết</button></div><small class="text-muted mt-2">Phiếu đã gửi không thể sửa.</small></form>
@endif
@if($proposal->conclusion)<div class="card card-body mb-3"><h5>Kết luận khi chốt</h5><div style="white-space:pre-wrap">{{ $proposal->conclusion }}</div><small class="text-muted">{{ $proposal->closed_at?->format('d/m/Y H:i') }}</small></div>@endif
</div><div class="col-lg-4"><div class="card card-body mb-3"><h5>{{ $proposal->statusLabel() }}</h5><p>Người mở: {{ $proposal->creator?->name }}<br>Hạn: {{ $proposal->closes_at->format('d/m/Y H:i') }}</p><p>Đã bỏ phiếu: <strong>{{ $cast->count() }}/{{ $proposal->votes->count() }}</strong><br>Đồng ý: {{ $cast->where('choice','agree')->count() }}<br>Không đồng ý: {{ $cast->where('choice','disagree')->count() }}<br>Không ý kiến: {{ $cast->where('choice','abstain')->count() }}</p><p class="small">Cần ít nhất {{ $proposal->quorum }} phiếu tham gia và {{ $proposal->approval_percent }}% đồng ý trên toàn bộ {{ $proposal->votes->count() }} người được mời.</p></div>
@if($canClose && $proposal->status==='open')<form class="card card-body mb-3" method="POST" action="{{ route('operating.proposals.close',$proposal) }}">@csrf<label class="form-label">Kết luận *</label><textarea name="conclusion" class="form-control mb-3" required maxlength="5000" rows="3"></textarea><button class="btn btn-success" type="submit" @disabled($proposal->closes_at->isFuture() && $cast->count()<$proposal->votes->count())>Chốt kết quả</button><small class="text-muted mt-2">Chốt khi hết hạn hoặc tất cả thành viên đã bỏ phiếu. Kết quả được tính theo quy tắc đã mở.</small></form>@endif
@if($canClose && $proposal->status==='approved')<a class="btn btn-primary mb-3 w-100" href="{{ route('tasks.create',['proposal_id'=>$proposal->id]) }}">Giao việc từ đề xuất đã thông qua</a>@endif
<div class="card card-body"><h5>Công việc triển khai</h5>@forelse($proposal->tasks as $task)<a href="{{ route('tasks.show',$task) }}">{{ $task->code }} · {{ $task->title }}</a>@empty<span class="text-muted small">Chưa giao công việc triển khai.</span>@endforelse</div>
</div></div></div>
@endsection
@include('task_assignments.partials.description-editor')
