@php
    $comparisonRows = \App\Support\OrderAdjustmentComparison::rows($adjustment);
    $comparisonNumber = static fn($value) => $value === null ? '—' : rtrim(rtrim(number_format((float)$value, 3, ',', ''), '0'), ',');
    $comparisonMoney = static fn($value) => number_format((float)$value, 0, ',', '.') . 'đ';
    $comparisonSections = $comparisonSection ?? 'both';
@endphp
@if(in_array($comparisonSections, ['both', 'original'], true))
<div class="mb-3">
<h6 class="fw-bold">Sản phẩm trước điều chỉnh</h6>
<div class="table-responsive"><table class="table table-sm align-middle">
<thead><tr><th>Sản phẩm / biến thể</th><th class="text-end">Size đặt</th><th class="text-end">Số lượng</th><th class="text-end">Khối lượng</th><th class="text-end">Size thực tế</th><th class="text-end">Đơn giá</th></tr></thead>
<tbody>
@foreach($comparisonRows as $row)
@if(!$row['new'])
<tr><td><strong>{{ $row['name'] }}</strong><div class="small text-muted">{{ $row['variant'] }}</div>
@if($row['packedWeight'] !== null)<div class="small text-muted">Kho đã đóng: {{ $comparisonNumber($row['packedQuantity']) }} · {{ $comparisonNumber($row['packedWeight']) }} kg</div>@endif
</td><td class="text-end">{{ $row['size'] ?: '—' }}</td><td class="text-end">{{ $comparisonNumber($row['originalQuantity']) }}</td><td class="text-end">{{ $row['byKg'] ? $comparisonNumber($row['originalWeight']).' kg' : '—' }}</td><td class="text-end">{{ $comparisonNumber($row['originalSize']) }}</td><td class="text-end">{{ $comparisonMoney($row['originalPrice']) }}</td></tr>
@endif
@endforeach
@foreach($adjustment->order?->items ?? [] as $orderItem)
@if(!$adjustment->items->contains('order_item_id', $orderItem->id) && !$adjustment->items->contains(fn($item) => !$item->order_item_id && (int)$item->product_variant_id === (int)$orderItem->product_variant_id))
@php
    $weight = $orderItem->quantity > 0 ? (float) ($orderItem->actual_weight ?? $orderItem->total_weight ?? 0) : 0;
    $packedQuantity = (float) ($orderItem->packed_quantity ?? $orderItem->quantity);
@endphp
<tr><td><strong>{{ $orderItem->variant?->product?->name ?? $orderItem->imported_name ?? 'Sản phẩm' }}</strong><div class="small text-muted">{{ $orderItem->variant?->name }} · Không đề nghị thay đổi</div></td><td class="text-end">{{ $orderItem->variant?->size ?: '—' }}</td><td class="text-end">{{ $comparisonNumber($orderItem->quantity) }}</td><td class="text-end">{{ $orderItem->effective_priced_by_kg ? $comparisonNumber($weight).' kg' : '—' }}</td><td class="text-end">{{ $comparisonNumber($orderItem->effective_priced_by_kg && $packedQuantity > 0 && $orderItem->packed_weight !== null ? $orderItem->packed_weight / $packedQuantity : null) }}</td><td class="text-end">{{ $comparisonMoney($orderItem->price) }}</td></tr>
@endif
@endforeach
</tbody></table></div>
<div class="small text-muted">Size thực tế trước điều chỉnh = khối lượng kho đã đóng / số lượng kho đã đóng; chưa có cân đóng thực tế thì hiển thị “—”. Size sau điều chỉnh là bình quân theo số lượng và khối lượng đề nghị. Size đặt là size của biến thể sản phẩm. Các dòng trong yêu cầu dùng số liệu gốc đã lưu.</div>
</div>
@endif
@if(in_array($comparisonSections, ['both', 'changes'], true))
<div class="mb-3">
<h6 class="fw-bold">Sản phẩm đề nghị thay đổi</h6>
<div class="table-responsive"><table class="table table-sm align-middle">
<thead><tr><th>Sản phẩm / biến thể</th><th class="text-end">Size đặt</th><th class="text-end">Số lượng</th><th class="text-end">Khối lượng</th><th class="text-end">Size thực tế</th><th class="text-end">Đơn giá</th><th>Nội dung thay đổi</th></tr></thead>
<tbody>
@forelse(array_filter($comparisonRows, fn($row) => $row['changed']) as $row)
<tr class="table-warning"><td><strong>{{ $row['name'] }}</strong><div class="small text-muted">{{ $row['variant'] }}</div>@if($row['item']->note)<div class="small">{{ $row['item']->note }}</div>@endif</td>
<td class="text-end">{{ $row['size'] ?: '—' }}</td>
<td class="text-end">{{ $comparisonNumber($row['originalQuantity']) }} → <strong>{{ $comparisonNumber($row['adjustedQuantity']) }}</strong></td>
<td class="text-end">@if($row['byKg']){{ $comparisonNumber($row['originalWeight']) }} → <strong>{{ $comparisonNumber($row['adjustedWeight']) }} kg</strong>@else — @endif</td>
<td class="text-end">@if($row['byKg']){{ $comparisonNumber($row['originalSize']) }} → <strong>{{ $comparisonNumber($row['adjustedSize']) }}</strong>@else — @endif</td>
<td class="text-end">{{ $comparisonMoney($row['originalPrice']) }} → <strong>{{ $comparisonMoney($row['adjustedPrice']) }}</strong></td>
<td>
@if($row['new'])<div><strong>Bổ sung sản phẩm:</strong> {{ $comparisonNumber($row['adjustedQuantity']) }}, đơn giá {{ $comparisonMoney($row['adjustedPrice']) }}</div>@endif
@if($row['originalQuantity'] !== $row['adjustedQuantity'])<div>Số lượng {{ $comparisonNumber($row['originalQuantity']) }} <strong>thay đổi thành {{ $comparisonNumber($row['adjustedQuantity']) }}</strong></div>@endif
@if($row['byKg'] && abs($row['originalWeight']-$row['adjustedWeight']) > 0.0001)<div>Khối lượng {{ $comparisonNumber($row['originalWeight']) }} kg <strong>thay đổi thành {{ $comparisonNumber($row['adjustedWeight']) }} kg</strong></div>@endif
@if($row['byKg'] && $row['originalSize'] !== null && abs((float)$row['originalSize']-(float)$row['adjustedSize']) > 0.0001)<div>Size thực tế {{ $comparisonNumber($row['originalSize']) }} <strong>thay đổi thành {{ $comparisonNumber($row['adjustedSize']) }}</strong></div>@endif
@if($row['originalPrice'] !== $row['adjustedPrice'])<div>Đơn giá {{ $comparisonMoney($row['originalPrice']) }} <strong>thay đổi thành {{ $comparisonMoney($row['adjustedPrice']) }}</strong></div>@endif
@if($comparisonSections === 'changes')
<div class="small text-muted">Kho xác nhận: {{ $row['item']->warehouse_received_quantity ?? '—' }}
@if($row['item']->warehouse_received_weight !== null)
 · {{ $comparisonNumber($row['item']->warehouse_received_weight) }} kg
@endif
 · {{ $row['item']->warehouse_condition ?: '—' }}</div>
@endif

</td></tr>
@empty<tr><td colspan="7" class="text-muted">Không đề nghị thay đổi sản phẩm. Xem nội dung yêu cầu và phí / chiết khấu.</td></tr>@endforelse
</tbody></table></div>
</div>
@endif
