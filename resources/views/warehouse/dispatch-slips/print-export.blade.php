<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><title>Phiếu xuất kho tổng {{ $slip->code }}</title>
<style>
@page{size:A4;margin:12mm}*{box-sizing:border-box}body{font-family:"DejaVu Sans",Arial,sans-serif;color:#111;font-size:11px;margin:0}.no-print{position:fixed;right:14px;top:12px}h1{font-size:20px;text-align:center;margin:3px 0}h3{margin:12px 0 5px}.sub{text-align:center;margin-bottom:12px}.meta{display:grid;grid-template-columns:1fr 1fr;gap:5px 22px;margin:12px 0}.box{border:1px solid #222;padding:8px;margin:8px 0}.handover-note{min-height:70px;white-space:pre-wrap;overflow-wrap:anywhere}.order-note{font-size:9px;white-space:pre-wrap;overflow-wrap:anywhere;min-width:90px}table{width:100%;border-collapse:collapse;margin:8px 0}th,td{border:1px solid #333;padding:5px 6px;vertical-align:top}th{background:#eee;text-align:left}.num{text-align:right;white-space:nowrap}.center{text-align:center}.sign{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;text-align:center;margin-top:24px}.sign-space{height:65px}.draft{color:#b91c1c;text-align:center;font-size:13px;font-weight:bold}.small{font-size:9px;color:#444}@media print{.no-print{display:none}}
</style></head><body>
@php($formatKg = static fn (float|int|string $value): string => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',').' kg')
<button class="no-print" onclick="window.print()">In phiếu</button>
<div class="small">HOÀNG LONG TNT</div><h1>PHIẾU XUẤT KHO TỔNG</h1><div class="sub"><strong>{{ $slip->code }}</strong> · Ngày {{ $slip->business_date->format('d/m/Y') }}</div>
@if($slip->status === 'draft')<div class="draft">BẢN NHÁP — PHIẾU CHƯA CHỐT</div>@endif
<div class="meta"><div>Kho xuất: <strong>{{ $slip->sourceWarehouse?->name }}</strong></div><div>Kho nhận: <strong>{{ $slip->targetWarehouse?->name }}</strong></div><div>Địa chỉ xuất: {{ $slip->sourceWarehouse?->address ?: '—' }}</div><div>Địa chỉ nhận: {{ $slip->targetWarehouse?->address ?: '—' }}</div><div>Tài xế: <strong>{{ $slip->shipper?->name }}</strong></div><div>Điện thoại: {{ $slip->shipper?->phone ?: '—' }}</div><div>Người lập: {{ $slip->creator?->name }}</div><div>Chốt lúc: {{ optional($slip->finalized_at)->format('d/m/Y H:i') ?: 'Chưa chốt' }}</div></div>

<h3>A. DANH SÁCH ĐƠN HOÀN THIỆN</h3>
<table><thead><tr><th class="center">STT</th><th>Mã đơn</th><th>Khách hàng</th><th>Sale</th><th class="num">SL dòng hàng</th><th class="num">KL bàn giao</th><th>Ghi chú đơn</th></tr></thead><tbody>
@forelse($orderRows as $row)<tr><td class="center">{{ $loop->iteration }}</td><td>{{ $row['code'] }}</td><td>{{ $row['customer_name'] }}</td><td>{{ $row['sale_name'] }}</td><td class="num">{{ number_format($row['item_quantity']) }}</td><td class="num">{{ $formatKg($row['packed_weight']) }}</td><td class="order-note">{{ filled($row['order_note']) ? $row['order_note'] : '—' }}</td></tr>@empty<tr><td colspan="7" class="center">Không có đơn hoàn thiện.</td></tr>@endforelse
</tbody></table>

<h3>B. PHIẾU ĐIỀU CHUYỂN HÀNG</h3>
<table><thead><tr><th class="center">STT</th><th>Mã phiếu điều chuyển</th><th class="num">Số mặt hàng</th><th class="num">Tổng số lượng</th><th class="num">Tổng khối lượng</th><th>Ghi chú điều chuyển</th></tr></thead><tbody>
@forelse($inventoryTransferRows as $row)<tr><td class="center">{{ $loop->iteration }}</td><td><strong>{{ $row['code'] }}</strong></td><td class="num">{{ number_format($row['item_count']) }}</td><td class="num">{{ number_format($row['quantity']) }}</td><td class="num">{{ $formatKg($row['weight']) }}</td><td class="order-note">{{ filled($row['note']) ? $row['note'] : '—' }}</td></tr>@empty<tr><td colspan="6" class="center">Phiếu không có hàng điều chuyển.</td></tr>@endforelse
</tbody></table>

<h3>C. BẢNG TỔNG HỢP HÀNG HÓA</h3>
<table><thead><tr><th class="center">STT</th><th>Sản phẩm</th><th>SKU</th><th>Size</th><th class="num">Số lượng</th><th class="num">Khối lượng</th></tr></thead><tbody>
@foreach($summaryRows as $row)<tr><td class="center">{{ $loop->iteration }}</td><td>{{ $row['product_name'] }}</td><td>{{ $row['sku'] ?: '—' }}</td><td>{{ $row['size'] ?: '—' }}</td><td class="num">{{ number_format($row['quantity']) }}</td><td class="num">{{ $formatKg($row['weight']) }}</td></tr>@endforeach
</tbody><tfoot><tr><th colspan="4">Tổng cộng</th><th class="num">{{ number_format($summaryRows->sum('quantity')) }}</th><th class="num">{{ $formatKg($summaryRows->sum('weight')) }}</th></tr></tfoot></table>

<h3>D. GHI CHÚ BÀN GIAO</h3>
<div class="box handover-note">{{ filled($slip->notes) ? $slip->notes : "........................................................................................................................................\n........................................................................................................................................" }}</div>
<div class="sign"><div><strong>NGƯỜI LẬP PHIẾU</strong><div class="small">(Ký, ghi rõ họ tên)</div><div class="sign-space"></div>{{ $slip->creator?->name }}</div><div><strong>THỦ KHO XUẤT</strong><div class="small">(Ký, ghi rõ họ tên)</div><div class="sign-space"></div></div><div><strong>TÀI XẾ NHẬN HÀNG</strong><div class="small">(Ký, ghi rõ họ tên)</div><div class="sign-space"></div>{{ $slip->shipper?->name }}</div></div>
</body></html>
