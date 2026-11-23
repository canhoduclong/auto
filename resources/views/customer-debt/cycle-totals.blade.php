<tr><th scope="row">Công nợ cũ (dư đầu kỳ)</th><td>{{ number_format($cycle['opening'], 0, ',', '.') }} đ</td></tr>
@if($cycle['prior_correction'] !== 0)
    <tr><th scope="row">Chênh lệch dữ liệu kỳ đã chốt</th><td>{{ number_format($cycle['prior_correction'], 0, ',', '.') }} đ</td></tr>
@endif
@if($cycle['gifts'] !== 0 || $cycle['adjustments'] !== 0)
    <tr><th scope="row">Tiền hàng trong kỳ</th><td data-cycle-total="sales">{{ number_format($cycle['sales'], 0, ',', '.') }} đ</td></tr>
@endif
@if($cycle['gifts'] !== 0)
    <tr><th scope="row">Hàng tặng / giảm trừ</th><td data-cycle-total="gifts">{{ number_format($cycle['gifts'], 0, ',', '.') }} đ</td></tr>
@endif
@if($cycle['adjustments'] !== 0)
    <tr><th scope="row">Điều chỉnh trong kỳ</th><td>{{ number_format($cycle['adjustments'], 0, ',', '.') }} đ</td></tr>
@endif
@if(($allowManualAdjustment ?? false) || !empty($cycle['manual_adjustment']))
    <tr><th scope="row">
        Điều chỉnh khi chốt kỳ
        @if(!($allowManualAdjustment ?? false) && !empty($cycle['adjustment_reason']))
            <small class="d-block">{{ $cycle['adjustment_reason'] }}</small>
        @endif
    </th><td data-cycle-total="manual_adjustment">{{ number_format($cycle['manual_adjustment'] ?? 0, 0, ',', '.') }} đ</td></tr>
@endif
<tr><th scope="row">Tổng chi phí phát sinh trong kỳ</th><td><strong data-cycle-total="net_new">{{ number_format($cycle['net_new'], 0, ',', '.') }} đ</strong></td></tr>
<tr><th scope="row">Đã thanh toán trong kỳ</th><td>{{ number_format($cycle['payments'], 0, ',', '.') }} đ</td></tr>
<tr><th scope="row" data-cycle-closing-label>{{ $cycle['closing'] < 0 ? 'Khách trả trước cuối kỳ' : 'Còn phải thu cuối kỳ' }}</th><td><strong data-cycle-total="closing">{{ number_format(abs($cycle['closing']), 0, ',', '.') }} đ</strong></td></tr>
