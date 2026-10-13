@if($checklistProgress ?? null)
<section class="card mb-3"><div class="card-header fw-semibold">Hạng mục & tiến độ tổng hợp</div><div class="card-body">
<div class="d-flex flex-wrap justify-content-between gap-2 mb-2"><strong>{{ $checklistProgress['percent'] }}% hoàn thành</strong><span>{{ $checklistProgress['done'] }}/{{ $checklistProgress['total'] }} hạng mục · {{ $checklistProgress['awaiting'] }} chờ xác nhận</span></div>
<progress value="{{ $checklistProgress['percent'] }}" max="100" style="width:100%;height:16px" aria-label="Tiến độ chung"></progress>
<p class="small text-muted mt-2">Tính theo hạng mục cuối cùng, mỗi hạng mục có trọng số bằng nhau. Việc bị hủy không tính; báo hoàn thành phải được xác nhận mới tính hoàn thành.</p>
@foreach($checklistProgress['people'] as $person)
<div class="border-bottom py-2"><strong>{{ $person['name'] }}</strong> <span class="small text-muted">· {{ $person['department'] }}</span><div>{{ $person['percent'] }}% · {{ $person['done'] }}/{{ $person['total'] }} hạng mục · {{ $person['awaiting'] }} chờ xác nhận</div></div>
@endforeach
<details class="mt-3" open><summary class="fw-semibold">Danh sách hạng mục</summary>
@forelse($checklistProgress['items'] as $item)
<div class="border-bottom py-2 d-flex flex-wrap justify-content-between gap-2"><div><a href="{{ route('tasks.show',$item) }}">{{ $item->code }} · {{ $item->title }}</a><div class="small text-muted">{{ $item->assignees->where('status','!=','cancelled')->map(fn($a)=>$a->user?->name)->filter()->join(', ') }}</div></div><span class="badge align-self-start bg-{{ $item->statusColor() }}">{{ $item->operatingStatusLabel() }}</span></div>
@empty<p class="text-muted mt-2">Chưa có hạng mục. Bấm giao việc con để thêm hạng mục cho bộ phận hoặc người thực hiện.</p>@endforelse
</details></div></section>
@endif
