@php
    $shown = $entries->take(12);
    $rangeFrom = $shown->min('date');
    $rangeTo = $shown->max('date');
    $rangeDays = $rangeFrom ? (int) \Carbon\CarbonImmutable::parse($rangeFrom)->diffInDays(\Carbon\CarbonImmutable::parse($rangeTo)) + 1 : 0;
@endphp
<p class="statement-period-summary" data-statement-summary data-unit="{{ $unit }}">@if($rangeFrom){{ $shown->count() }} {{ $unit }} gần nhất · {{ $rangeDays }} ngày (từ {{ \Carbon\Carbon::parse($rangeFrom)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($rangeTo)->format('d/m/Y') }})@else Không có giao dịch trong kỳ đã chọn. @endif</p>
