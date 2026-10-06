@php
    $comparisonRows = \App\Support\OrderAdjustmentComparison::completeRows($adjustment);
    $comparisonNumber = static fn($value) => $value === null ? '—' : rtrim(rtrim(number_format((float)$value, 3, ',', ''), '0'), ',');
    $comparisonMoney = static fn($value) => number_format((float)$value, 0, ',', '.') . 'đ';
    $comparisonSections = $comparisonSection ?? 'both';
    $comparisonStates = $comparisonSections === 'original' ? ['original'] : ($comparisonSections === 'changes' ? ['adjusted'] : ['original','adjusted']);
@endphp
@once
<style>
.adjustment-comparison-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 24px; align-items: start; }
.adjustment-comparison-orders, .adjustment-comparison-notes { min-width: 0; }
.adjustment-comparison-original { background: #f1f3f5; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; }
.adjustment-comparison-original .table { --bs-table-bg: transparent; background: transparent; }
.adjustment-comparison-original .table > :not(caption) > * > * { background-color: transparent; }
.adjustment-comparison-grid .table td, .adjustment-comparison-grid .table th { padding: .35rem .25rem; }
.adjustment-comparison-grid .table th { white-space: nowrap; }
@media (min-width: 1200px) {
    .adjustment-comparison-grid { grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); }
    .adjustment-comparison-grid.adjustment-comparison-stacked { grid-template-columns: minmax(0, 1fr); }
    .adjustment-review-actions { width: calc(60% - 14px); }
}
</style>
@endonce
<div class="adjustment-comparison-grid {{ ($comparisonStacked ?? false) ? 'adjustment-comparison-stacked' : '' }}">
<div class="adjustment-comparison-orders">
@foreach($comparisonStates as $state)
@php
    $totals = \App\Support\OrderAdjustmentComparison::totals($adjustment, $comparisonRows, $state);
@endphp
<div class="mb-3 {{ $state === 'original' ? 'adjustment-comparison-original' : '' }}">
<h6 class="fw-bold">{{ $state === 'original' ? 'Đơn trước điều chỉnh' : 'Đơn sau điều chỉnh (đề nghị)' }}</h6>
@if($state === 'original' && ($comparisonShowOrderInformation ?? false) && $adjustment->order)
    @include('site.orders.adjustments._original_order_information', ['adjustment' => $adjustment])
@endif
<div class="table-responsive"><table class="table table-sm align-middle mb-1">
<thead><tr><th>Sản phẩm / biến thể</th><th class="text-end">Size đặt</th><th class="text-end">Số lượng</th><th class="text-end">Khối lượng</th><th class="text-end"><span title="{{ $state === 'original' ? 'Size kho đã đóng thực tế' : 'Size bình quân theo đề nghị điều chỉnh' }}">Size</span></th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
<tbody>
@foreach($comparisonRows as $row)
@continue($state === 'original' && $row['new'])
@php
    $highlight = $state === 'adjusted' && $row['changed'];
@endphp
<tr class="{{ $highlight ? 'table-warning' : '' }}">
<td class="text-nowrap"><strong>{{ $row['name'] }}</strong>
@if($row['sku'] || $row['variant'])
 <span class="small text-muted">({{ $row['sku'] ?: $row['variant'] }})</span>
@endif
@if($row['new'])
 <span class="badge text-bg-success">Bổ sung mới</span>
@endif
</td>
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
@php
    $shippingChange = (array) data_get($adjustment->fee_changes, 'shipping', []);
    if ($shippingChange && $adjustment->order) $shippingChange = \App\Services\OrderFeeService::shippingChange($adjustment->order, $shippingChange);
    $oldShipping = (bool) data_get($shippingChange, 'original.enabled', false) ? (float) data_get($shippingChange, 'original.value', 0) : 0;
    $newShipping = (bool) data_get($shippingChange, 'adjusted.enabled', false) ? (float) data_get($shippingChange, 'adjusted.value', 0) : 0;
@endphp
@foreach($totals['fees'] as $fee)
<tr><td colspan="6" class="text-end">
@if($state === 'adjusted' && ($fee['code'] ?? '') === 'shipping' && $oldShipping <= 0 && $newShipping > 0)
<span class="badge text-bg-warning me-3">Thêm</span>
@endif
{{ $fee['name'] }}</td><td class="text-end">{{ $comparisonMoney($fee['amount']) }}</td></tr>
@endforeach
<tr><td colspan="6" class="text-end fw-bold">Tổng đơn {{ $state === 'adjusted' ? 'đề nghị' : 'trước điều chỉnh' }}</td><td class="text-end fw-bold">{{ $comparisonMoney($totals['total']) }}</td></tr>
@if($state === 'original' && ($comparisonShowOrderInformation ?? false) && $adjustment->order)
@php
    $originalPaid = max(0, (float)$adjustment->order->amount_paid, (float)($adjustment->order->collected_amount ?? 0));
    $originalDue = max(0, $totals['total'] - $originalPaid);
    $originalPaymentStatus = $originalDue <= 0 ? 'Đã thanh toán' : ($originalPaid > 0 ? 'Thanh toán một phần' : 'Chưa thanh toán');
@endphp
<tr><td colspan="6" class="text-end">Đã thanh toán</td><td class="text-end">{{ $comparisonMoney($originalPaid) }}</td></tr>
<tr><td colspan="6" class="text-end">Còn phải thu</td><td class="text-end fw-semibold">{{ $comparisonMoney($originalDue) }}</td></tr>
<tr><td colspan="6" class="text-end">Thanh toán</td><td class="text-end">{{ $originalPaymentStatus }}</td></tr>
@endif
</tfoot>
</table></div>
</div>
@endforeach
</div>
<div class="adjustment-comparison-notes">
@if($comparisonSections !== 'original')
<div class="border rounded p-3 mb-3 bg-light">
    <div class="fw-bold mb-2">Nội dung yêu cầu từ Sale</div>
    @if($adjustment->requester)
        <div class="small text-muted mb-2">{{ $adjustment->requester->short_name ?: $adjustment->requester->name }}</div>
    @endif
    <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $adjustment->adjustment_note ?: 'Không có nội dung bổ sung.' }}</div>
</div>
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
    if ($code === 'shipping' && $adjustment->order) $change = \App\Services\OrderFeeService::shippingChange($adjustment->order, $change);
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
</ul>
</div>
<div class="small text-muted mb-3">Size kho đã đóng lấy từ cân và số lượng đóng thực tế. Size ở đơn đề nghị được tính theo khối lượng / số lượng đề nghị; chưa phải kết quả kho cân lại.</div>
@endif

</div>
</div>
