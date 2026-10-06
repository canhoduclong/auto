@php
    $comparisonRows = \App\Support\OrderAdjustmentComparison::completeRows($adjustment);
    $comparisonNumber = static fn($value) => $value === null ? '—' : rtrim(rtrim(number_format((float)$value, 3, ',', ''), '0'), ',');
    $comparisonMoney = static fn($value) => number_format((float)$value, 0, ',', '.') . 'đ';
    $comparisonSections = $comparisonSection ?? 'both';
    $comparisonStates = $comparisonSections === 'original' ? ['original'] : ($comparisonSections === 'changes' ? ['adjusted'] : ['original','adjusted']);
@endphp
@foreach($comparisonStates as $state)
@php
    $totals = \App\Support\OrderAdjustmentComparison::totals($adjustment, $comparisonRows, $state);
@endphp
<div class="mb-3">
<h6 class="fw-bold">{{ $state === 'original' ? 'Đơn trước điều chỉnh' : 'Đơn sau điều chỉnh (đề nghị)' }}</h6>
<div class="table-responsive"><table class="table table-sm align-middle mb-1">
<thead><tr><th>Sản phẩm / biến thể</th><th class="text-end">Size đặt</th><th class="text-end">Số lượng</th><th class="text-end">Khối lượng</th><th class="text-end">{{ $state === 'original' ? 'Size kho đã đóng' : 'Size bình quân đề nghị' }}</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
<tbody>
@foreach($comparisonRows as $row)
@continue($state === 'original' && $row['new'])
@php
    $highlight = $state === 'adjusted' && $row['changed'];
@endphp
<tr class="{{ $highlight ? 'table-warning' : '' }}">
<td><strong>{{ $row['name'] }}</strong><div class="small text-muted">{{ $row['variant'] }}@if($row['new']) · Bổ sung mới @endif</div></td>
<td class="text-end">{{ $row['size'] ?: '—' }}</td>
<td class="text-end {{ $highlight && $row['originalQuantity'] !== $row['adjustedQuantity'] ? 'fw-bold text-danger' : '' }}">{{ $comparisonNumber($row[$state.'Quantity']) }}</td>
<td class="text-end {{ $highlight && abs($row['originalWeight']-$row['adjustedWeight']) > 0.0001 ? 'fw-bold text-danger' : '' }}">{{ $row['byKg'] ? $comparisonNumber($row[$state.'Weight']).' kg' : '—' }}</td>
<td class="text-end">{{ $comparisonNumber($row[$state.'Size']) }}</td>
<td class="text-end {{ $highlight && $row['originalPrice'] !== $row['adjustedPrice'] ? 'fw-bold text-danger' : '' }}">{{ $comparisonMoney($row[$state.'Price']) }}</td>
<td class="text-end fw-semibold">{{ $comparisonMoney($row[$state.'Total']) }}</td>
</tr>
@endforeach
</tbody>
<tfoot>
<tr><td colspan="6" class="text-end">Tiền hàng</td><td class="text-end fw-semibold">{{ $comparisonMoney($totals['subtotal']) }}</td></tr>
@foreach($totals['fees'] as $fee)
<tr><td colspan="6" class="text-end">{{ $fee['name'] }}</td><td class="text-end">{{ $comparisonMoney($fee['amount']) }}</td></tr>
@endforeach
<tr><td colspan="6" class="text-end fw-bold">Tổng đơn {{ $state === 'adjusted' ? 'đề nghị' : 'trước điều chỉnh' }}</td><td class="text-end fw-bold">{{ $comparisonMoney($totals['total']) }}</td></tr>
</tfoot>
</table></div>
</div>
@endforeach
@if($comparisonSections !== 'original')
<div class="border rounded p-3 mb-3 bg-light">
<div class="fw-bold mb-2">Nội dung thay đổi</div>
<ul class="mb-0 ps-3">
@foreach(array_filter($comparisonRows, fn($row) => $row['changed']) as $row)
@php
    $changes = [];
    if ($row['new']) $changes[] = 'Bổ sung '.$comparisonNumber($row['adjustedQuantity']).', đơn giá '.$comparisonMoney($row['adjustedPrice']);
    else {
        if ($row['originalQuantity'] !== $row['adjustedQuantity']) $changes[] = 'Số lượng '.$comparisonNumber($row['originalQuantity']).' thay đổi thành '.$comparisonNumber($row['adjustedQuantity']);
        if ($row['originalPrice'] !== $row['adjustedPrice']) $changes[] = 'Đơn giá '.$comparisonMoney($row['originalPrice']).' thay đổi thành '.$comparisonMoney($row['adjustedPrice']);
    }
    if ($row['byKg'] && abs($row['originalWeight']-$row['adjustedWeight']) > 0.0001) $changes[] = 'Khối lượng '.$comparisonNumber($row['originalWeight']).' kg thay đổi thành '.$comparisonNumber($row['adjustedWeight']).' kg';
@endphp
<li><strong>{{ $row['name'] }} {{ $row['variant'] }}:</strong> {{ implode('; ', $changes) }}.
@if($row['item']?->note) {{ $row['item']->note }} @endif
@if($row['item']?->warehouse_received_quantity !== null || $row['item']?->warehouse_received_weight !== null)
<span class="small text-muted">Kho xác nhận: {{ $row['item']->warehouse_received_quantity ?? '—' }}; {{ $comparisonNumber($row['item']->warehouse_received_weight) }} kg; {{ $row['item']->warehouse_condition ?: '—' }}.</span>
@endif
</li>
@endforeach
@foreach((array)$adjustment->fee_changes as $code=>$change)
@php
    $oldFee = (array)($change['original'] ?? []);
    $newFee = (array)($change['adjusted'] ?? []);
    $feeChanged = (bool)($oldFee['enabled'] ?? false) !== (bool)($newFee['enabled'] ?? false) || abs((float)($oldFee['value'] ?? 0)-(float)($newFee['value'] ?? 0)) > 0.0001;
    $feeLabel = $change['name'] ?? ['shipping'=>'Phí Ship','vat'=>'VAT','discount'=>'Chiết khấu đơn','foam_box'=>'Phí thùng xốp'][$code] ?? $code;
    $formatFee = static fn($fee) => !($fee['enabled'] ?? false) ? 'Không áp dụng' : (($change['calculation_type'] ?? 'fixed') === 'percent' ? $comparisonNumber($fee['value']).'%' : $comparisonMoney($fee['value']));
@endphp
@if($feeChanged)
<li><strong>{{ $feeLabel }}:</strong> {{ $formatFee($oldFee) }} thay đổi thành <strong>{{ $formatFee($newFee) }}</strong>.</li>
@endif
@endforeach
@foreach((array)$adjustment->order_changes as $field=>$change)
<li><strong>{{ ['recipient_name'=>'Người nhận','recipient_phone'=>'Số điện thoại','delivery_time'=>'Giờ giao'][$field] ?? $field }}:</strong> {{ data_get($change,'original') ?: '—' }} thay đổi thành <strong>{{ data_get($change,'adjusted') ?: '—' }}</strong>.</li>
@endforeach
@if($adjustment->adjustment_note)
<li><strong>Lý do / ghi chú:</strong> {{ $adjustment->adjustment_note }}</li>
@endif
</ul>
</div>
<div class="small text-muted mb-3">Size kho đã đóng lấy từ cân và số lượng đóng thực tế. Size ở đơn đề nghị được tính theo khối lượng / số lượng đề nghị; chưa phải kết quả kho cân lại.</div>
@endif
