@auth
@php
    $excludedTaskUrls=collect($taskNavExcludedRoutes ?? [])->map(fn($route)=>route($route));
    $taskNavItems = collect(\App\Support\WebTaskNavigation::items(auth()->user()))
        ->reject(fn($item)=>$excludedTaskUrls->contains($item['url']))
        ->unique('url')->values()->all();
@endphp
@if($taskNavList ?? false)
@php
    $shortTaskLabels = [
        'Tổng quan công việc'=>'Tổng quan công việc', 'Nhận việc / Chưa tiếp nhận'=>'Chờ nhận việc',
        'Việc tôi nhận'=>'Việc tôi nhận', 'Đang thực hiện'=>'Đang thực hiện',
        'Báo cáo đã gửi / Chờ xác nhận'=>'Báo cáo đã gửi', 'Lịch sử công việc'=>'Lịch sử công việc',
        'Tạo công việc / Giao việc'=>'Tạo / Giao việc', 'Việc tôi giao'=>'Việc tôi giao',
        'Chờ nghiệm thu'=>'Chờ nghiệm thu', 'Yêu cầu phối hợp'=>'Phối hợp',
        'Mở đề xuất biểu quyết'=>'Tạo biểu quyết', 'Biểu quyết liên quan đến tôi'=>'Biểu quyết của tôi',
        'Quản trị tất cả công việc'=>'Tất cả công việc', 'Công việc đã xóa'=>'Công việc đã xóa',
        'Quyền công việc theo vai trò'=>'Quyền theo vai trò', 'Quyền giao việc cho từng người'=>'Quyền giao việc',
        'Cấu hình quy trình phê duyệt'=>'Quy trình duyệt',
    ];
    $taskMenuGroups = [
        'Công việc của tôi'=>['Tổng quan công việc','Nhận việc / Chưa tiếp nhận','Việc tôi nhận','Đang thực hiện','Báo cáo đã gửi / Chờ xác nhận','Lịch sử công việc'],
        'Giao việc & Điều hành'=>['Tạo công việc / Giao việc','Việc tôi giao','Chờ nghiệm thu','Yêu cầu phối hợp','Mở đề xuất biểu quyết','Biểu quyết liên quan đến tôi','Quản trị tất cả công việc','Công việc đã xóa'],
        'Cấu hình giao việc'=>['Quyền công việc theo vai trò','Quyền giao việc cho từng người','Cấu hình quy trình phê duyệt'],
    ];
@endphp
@foreach($taskMenuGroups as $heading=>$labels)
    @php
        $groupItems=collect($taskNavItems)->filter(fn($item)=>in_array($item['label'],$labels,true));
    @endphp
    @if($groupItems->isNotEmpty())
    <li class="nav-item-header"><div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">{{ $heading }}</div><i class="ph-dots-three sidebar-resize-show"></i></li>
    @foreach($groupItems as $item)
    <li class="nav-item"><a href="{{ $item['url'] }}" title="{{ $item['label'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}"><i class="ph-list-checks"></i><span>{{ $shortTaskLabels[$item['label']] ?? $item['label'] }}</span></a></li>
    @endforeach
    @endif
@endforeach
@else
<div class="{{ $taskNavHeadingClass ?? 'nav-section' }}">Công việc & Điều hành</div>
@foreach($taskNavItems as $item)
<a href="{{ $item['url'] }}" class="{{ $taskNavLinkClass ?? '' }} {{ $item['active'] ? 'active' : '' }}"><i class="bi bi-{{ $item['icon'] }}"></i><span class="{{ $taskNavLabelClass ?? '' }}">{{ $item['label'] }}</span></a>
@endforeach
@endif
@endauth
