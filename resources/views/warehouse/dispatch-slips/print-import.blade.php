<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><title>Phiếu nhập kho tổng {{ $slip->code }}</title>
<style>
@page{size:A4;margin:12mm}*{box-sizing:border-box}body{font-family:"DejaVu Sans",Arial,sans-serif;color:#111;font-size:11px;margin:0}.no-print{position:fixed;right:14px;top:12px}h1{font-size:20px;text-align:center;margin:3px 0}.sub{text-align:center;margin-bottom:10px}.notice{border:1px solid #b45309;background:#fffbeb;padding:7px;text-align:center;font-weight:bold;margin:8px 0}.done{border-color:#15803d;background:#f0fdf4}.meta{display:grid;grid-template-columns:1fr 1fr;gap:5px 22px;margin:12px 0}table{width:100%;border-collapse:collapse;margin:8px 0}th,td{border:1px solid #333;padding:5px;vertical-align:top}th{background:#eee;text-align:left}.num{text-align:right}.center{text-align:center}.danger{color:#b91c1c;font-weight:bold}.sign{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;text-align:center;margin-top:24px}.sign-space{height:65px}.small{font-size:9px;color:#444}@media print{.no-print{display:none}}
</style></head><body>
<button class="no-print" onclick="window.print()">In phiếu</button>
<div class="small">HOÀNG LONG TNT</div><h1>PHIẾU NHẬP KHO TỔNG</h1><div class="sub">Tham chiếu phiếu xuất <strong>{{ $slip->code }}</strong> · Ngày {{ now()->format('d/m/Y') }}</div>
<div class="notice {{ $slip->entry_received === $slip->entry_total && $slip->entry_total > 0 ? 'done' : '' }}">{{ $slip->entry_received === $slip->entry_total && $slip->entry_total > 0 ? 'ĐÃ TIẾP NHẬN HOÀN TẤT' : 'PHIẾU NHẬP TẠM — '.$slip->progress_label }}</div>
<div class="meta"><div>Kho xuất: <strong>{{ $slip->sourceWarehouse?->name }}</strong></div><div>Kho nhập: <strong>{{ $slip->targetWarehouse?->name }}</strong></div><div>Tài xế bàn giao: {{ $slip->shipper?->name }}</div><div>Ngày nghiệp vụ: {{ $slip->business_date->format('d/m/Y') }}</div></div>

<h3>A. ĐỐI CHIẾU TỪNG ĐƠN</h3>
<table><thead><tr><th>STT</th><th>Mã đơn/Khách hàng</th><th class="num">KL xuất</th><th class="num">KL nhận</th><th class="num">Chênh lệch</th><th>Trạng thái</th></tr></thead><tbody>
@forelse($orderRows as $row)@php($out=$row['packed_weight']) @php($received=$row['movement']?->received_total_weight)<tr><td class="center">{{ $loop->iteration }}</td><td><strong>{{ $row['code'] }}</strong><br>{{ $row['customer_name'] }}</td><td class="num">{{ number_format($out,3,',','.') }} kg</td><td class="num">{{ $received === null ? '—' : number_format((float)$received,3,',','.').' kg' }}</td><td class="num {{ $received !== null && abs($out-(float)$received) >= .001 ? 'danger' : '' }}">{{ $received === null ? '—' : number_format($out-(float)$received,3,',','.').' kg' }}</td><td>{{ $row['received'] ? 'Đã tiếp nhận' : 'Chưa tiếp nhận' }}</td></tr>@empty<tr><td colspan="6" class="center">Không có đơn hoàn thiện.</td></tr>@endforelse
</tbody></table>

<h3>B. ĐỐI CHIẾU HÀNG HÓA</h3>
<table><thead><tr><th>Sản phẩm</th><th>SKU/Size</th><th class="num">SL xuất</th><th class="num">SL nhận</th><th class="num">KL xuất</th><th class="num">KL nhận</th><th class="num">Chênh lệch KL</th></tr></thead><tbody>
@foreach($summaryRows as $row)@php($weightDiff=$row['received_weight']===null?null:$row['weight']-$row['received_weight'])<tr><td>{{ $row['product_name'] }}</td><td>{{ $row['sku'] ?: '—' }} / {{ $row['size'] ?: '—' }}</td><td class="num">{{ number_format($row['quantity']) }}</td><td class="num">{{ $row['received_quantity']===null?'—':number_format($row['received_quantity']) }}</td><td class="num">{{ number_format($row['weight'],3,',','.') }} kg</td><td class="num">{{ $row['received_weight']===null?'—':number_format($row['received_weight'],3,',','.').' kg' }}</td><td class="num {{ $weightDiff !== null && abs($weightDiff)>=.001?'danger':'' }}">{{ $weightDiff===null?'—':number_format($weightDiff,3,',','.').' kg' }}</td></tr>@endforeach
</tbody></table>

<div class="sign"><div><strong>TÀI XẾ BÀN GIAO</strong><div class="small">(Ký, ghi rõ họ tên)</div><div class="sign-space"></div>{{ $slip->shipper?->name }}</div><div><strong>THỦ KHO NHẬN</strong><div class="small">(Ký, ghi rõ họ tên)</div><div class="sign-space"></div></div><div><strong>NGƯỜI ĐỐI CHIẾU</strong><div class="small">(Ký, ghi rõ họ tên)</div><div class="sign-space"></div></div></div>
</body></html>
