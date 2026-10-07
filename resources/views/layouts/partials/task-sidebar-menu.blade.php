@auth
@php($taskNavItems = \App\Support\WebTaskNavigation::items(auth()->user()))
@if($taskNavList ?? false)
<li class="nav-item-header"><div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Điều hành & Giao việc</div><i class="ph-dots-three sidebar-resize-show"></i></li>
@foreach($taskNavItems as $item)
<li class="nav-item"><a href="{{ $item['url'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}"><i class="ph-list-checks"></i><span>{{ $item['label'] }}</span></a></li>
@endforeach
@else
<div class="{{ $taskNavHeadingClass ?? 'nav-section' }}">Công việc & Điều hành</div>
@foreach($taskNavItems as $item)
<a href="{{ $item['url'] }}" class="{{ $taskNavLinkClass ?? '' }} {{ $item['active'] ? 'active' : '' }}"><i class="bi bi-{{ $item['icon'] }}"></i><span class="{{ $taskNavLabelClass ?? '' }}">{{ $item['label'] }}</span></a>
@endforeach
@endif
@endauth
