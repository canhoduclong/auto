@if($editableCycleBills ?? false)
    <p class="small text-muted mb-2" data-cycle-assigned-empty @if($cycleBills) hidden @endif>Chưa có bill được gán.</p>
    <div class="table-responsive debt-cycle-picker-scroll" data-cycle-assigned-list @unless($cycleBills) hidden @endunless>
        <table class="table table-sm small mb-2">
            <thead><tr><th>Ngày giao</th><th>Bill / dòng Sheet</th><th class="text-end">Phải thu</th></tr></thead>
            <tbody data-cycle-assigned-rows>
                @foreach($cycleBills as $bill)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($bill['date'])->format('d/m/Y') }}</td>
                        <td>{{ $bill['label'] }}</td>
                        <td class="text-end">{{ number_format($bill['sales'] - $bill['gifts'], 0, ',', '.') }} đ</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif($cycleBills === null)
    <p class="small text-muted mt-2">Kỳ này lưu phát sinh theo ngày giao.</p>
    @if(!empty($cycleDaily))
        <table class="table table-sm small">
            <thead><tr><th>Ngày</th><th class="text-end">Tiền hàng sau giảm trừ</th></tr></thead>
            <tbody>
                @foreach($cycleDaily as $date => $day)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                        <td class="text-end">{{ number_format($day['sales'] - $day['gifts'], 0, ',', '.') }} đ</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@elseif(!$cycleBills)
    <h4 class="h6 mb-2">Bill trong kỳ</h4>
    <p class="small text-muted mb-2">Chưa có bill được gán.</p>
@else
    <div class="table-responsive debt-cycle-picker-scroll">
        <table class="table table-sm small mb-2">
            <caption class="caption-top">Bill trong kỳ ({{ count($cycleBills) }})</caption>
            <thead><tr><th>Ngày giao</th><th>Bill / dòng Sheet</th><th class="text-end">Phải thu</th></tr></thead>
            <tbody>
                @foreach($cycleBills as $bill)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($bill['date'])->format('d/m/Y') }}</td>
                        <td>{{ $bill['label'] }}</td>
                        <td class="text-end">{{ number_format($bill['sales'] - $bill['gifts'], 0, ',', '.') }} đ</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
