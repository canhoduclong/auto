@auth
@php
    $items=collect(\App\Support\WebTaskNavigation::items(auth()->user()))->unique('url');
    $groups=[
        'Công việc của tôi'=>['Cần xử lý','Tổng quan công việc','Nhận việc / Chưa tiếp nhận','Việc tôi nhận','Đang thực hiện','Báo cáo đã gửi / Chờ xác nhận','Lịch sử công việc'],
        'Giao việc & Điều hành'=>['Tạo công việc / Giao việc','Việc tôi giao','Chờ nghiệm thu','Yêu cầu phối hợp','Mở đề xuất biểu quyết','Biểu quyết liên quan đến tôi','Quản trị tất cả công việc','Công việc đã xóa'],
        'Cấu hình giao việc'=>['Quyền công việc theo vai trò','Quyền giao việc cho từng người','Cấu hình quy trình phê duyệt'],
    ];
    $known=collect($groups)->flatten();
    $extra=$items->reject(fn($item)=>$known->contains($item['label']))->pluck('label')->all();
    if($extra)$groups['Công việc khác']=$extra;
@endphp
@if($items->isNotEmpty())
<div class="nav-section">Công việc</div>
@foreach($groups as $heading=>$labels)
@php $links=$items->filter(fn($item)=>in_array($item['label'],$labels,true)); @endphp
@if($links->isNotEmpty())
<details class="acc-task-group" @if($links->contains(fn($item)=>$item['active'])) open @endif>
    <summary title="{{ $heading }}"><i class="bi bi-{{ $loop->first ? 'list-task' : ($heading==='Cấu hình giao việc' ? 'gear' : 'briefcase') }}" aria-hidden="true"></i><span>{{ $heading }}</span><i class="bi bi-chevron-down acc-task-chevron" aria-hidden="true"></i></summary>
    <div class="acc-task-links">
    @foreach($links as $item)
    <a href="{{ $item['url'] }}" title="{{ $item['label'] }}" class="{{ $item['active'] ? 'active' : '' }}" @if($item['active']) aria-current="page" @endif><i class="bi bi-{{ $item['icon'] }}" aria-hidden="true"></i><span>{{ $item['label'] }}</span></a>
    @endforeach
    </div>
</details>
@endif
@endforeach
@endif
@endauth
