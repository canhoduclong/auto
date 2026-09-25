@props(['status', 'style' => 'bootstrap'])

@php
    $colors = [
        'pending'     => 'secondary',  // Gray
        'processing'  => 'primary',    // Blue
        'completed'   => 'warning',    // Yellow
        'done'        => 'success',    // Green
        'rejected'    => 'danger',     // Red
        'cancelled'   => 'secondary',  // Gray
        'draft'       => 'light',      // Light
    ];
    
    $labels = [
        'pending'     => 'Chờ thực hiện',
        'processing'  => 'Đang thực hiện',
        'completed'   => 'Chờ xác nhận',
        'done'        => 'Đã hoàn thành',
        'rejected'    => 'Yêu cầu làm lại',
        'cancelled'   => 'Hủy',
        'draft'       => 'Nháp',
    ];
    
    $icons = [
        'pending'     => 'fas fa-pause-circle',
        'processing'  => 'fas fa-play-circle',
        'completed'   => 'fas fa-hourglass-end',
        'done'        => 'fas fa-check-circle',
        'rejected'    => 'fas fa-times-circle',
        'cancelled'   => 'fas fa-ban',
        'draft'       => 'fas fa-file',
    ];
    
    $color = $colors[$status] ?? 'secondary';
    $label = $labels[$status] ?? ucfirst($status);
    $icon = $icons[$status] ?? 'fas fa-info-circle';
@endphp

@if ($style === 'bootstrap')
    <span class="badge bg-{{ $color }}" title="{{ $label }}">
        <i class="{{ $icon }}"></i>
        {{ $label }}
    </span>
@elseif ($style === 'pill')
    <span class="badge rounded-pill bg-{{ $color }}" title="{{ $label }}">
        <i class="{{ $icon }}"></i>
        {{ $label }}
    </span>
@elseif ($style === 'dot')
    <span class="d-inline-flex align-items-center gap-2">
        <span class="badge rounded-circle bg-{{ $color }}" style="width: 12px; height: 12px; padding: 0;"></span>
        <small>{{ $label }}</small>
    </span>
@endif
