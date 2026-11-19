@if($alert['threshold_reached'] ?? false)
    <span class="badge {{ $alert['above_threshold'] ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $alert['above_threshold'] ? 'Vượt ngưỡng công nợ' : 'Chạm ngưỡng công nợ' }}</span>
    @if($alert['threshold_age_days'] !== null)
        <span class="small text-muted">
            Vượt {{ $alert['threshold_age_days'] }} ngày
            @if($alert['threshold_grace_elapsed']) · Đã đến hạn xử lý @endif
        </span>
    @endif
@endif
