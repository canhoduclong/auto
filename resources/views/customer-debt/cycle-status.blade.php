@php($weekdays = [1 => 'thứ Hai', 2 => 'thứ Ba', 3 => 'thứ Tư', 4 => 'thứ Năm', 5 => 'thứ Sáu', 6 => 'thứ Bảy', 7 => 'Chủ nhật'])
<div class="small mb-1">
    <span class="badge bg-info text-dark">Thanh toán định kỳ</span>
    @if($policy['cycle_type'] === 'monthly')
        <div>Chốt gửi ngày {{ $policy['cycle_close_monthday'] ?? $policy['cycle_monthday'] }} · Thanh toán ngày {{ $policy['cycle_monthday'] }} hằng tháng</div>
    @else
        <div>Chốt gửi {{ $weekdays[$policy['cycle_close_weekday'] ?? $policy['cycle_weekday']] }} · Thanh toán {{ $weekdays[$policy['cycle_weekday']] }} hằng tuần</div>
    @endif
    @if(!empty($alerts['cycle_close_date']) && !empty($alerts['due_date']))
        <div class="text-muted">Kỳ nợ hiện tại · Chốt {{ \Carbon\Carbon::parse($alerts['cycle_close_date'])->format('d/m/Y') }} · Hạn trả {{ \Carbon\Carbon::parse($alerts['due_date'])->format('d/m/Y') }}</div>
    @endif
</div>
