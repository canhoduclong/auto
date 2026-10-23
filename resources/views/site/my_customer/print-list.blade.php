<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><title>Danh sách khách hàng</title>
<style>
@page { size: A4 landscape; margin: 10mm; }
* { box-sizing: border-box; }
body { margin: 0; font: 11px/1.45 Arial,sans-serif; color: #111827; }
h1 { text-align: center; font-size: 20px; margin: 8px 0; }
table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-top: 14px; }
th, td { border: 1px solid #64748b; padding: 6px 5px; vertical-align: top; white-space: normal; overflow-wrap: anywhere; word-break: normal; }
th { background: #eef2f7; text-align: center; font-size: 10px; }
thead { display: table-header-group; }
tr { break-inside: avoid; page-break-inside: avoid; }
.center { text-align: center; }
.customer-name { font-weight: bold; }
.address { white-space: pre-line; }
.phone { white-space: nowrap; font-size: 10px; letter-spacing: -.2px; }
.needs { font-size: 10px; line-height: 1.35; }
.meta { font-size: 11px; color: #475569; text-align: center; }
.tools { text-align: right; margin: 10px; }
@media screen { body { max-width: 277mm; margin: 20px auto; padding: 0 10px; } }
@media print { .tools { display: none; } }
</style></head>
<body>
<div class="tools"><button onclick="window.print()">In danh sách</button></div>
<h1>DANH SÁCH KHÁCH HÀNG</h1>
<div class="meta">Ngày in: {{ now()->format('d/m/Y H:i') }} · Người in: {{ auth()->user()->name }} · Tổng: {{ number_format($customers->count()) }} khách</div>
<div class="meta">@if($search !== '')Tìm kiếm: {{ $search }} · @endif @if(request()->filled('area'))Khu vực: {{ request('area') === \App\Services\CustomerAreaService::UNKNOWN ? 'Chưa cập nhật' : request('area') }} @endif @if(request()->filled('ward')) · Phường: {{ request('ward') === \App\Services\CustomerAreaService::UNKNOWN ? 'Chưa cập nhật' : request('ward') }}@endif</div>
<table>
<colgroup>
    <col style="width:4%"><col style="width:6%"><col style="width:17%"><col style="width:8%">
    <col style="width:27%"><col style="width:13%"><col style="width:12%"><col style="width:8%"><col style="width:5%">
</colgroup>
<thead><tr><th>STT</th><th>Mã KH</th><th>Khách hàng</th><th>Điện thoại</th><th>Địa chỉ</th><th>Khu vực</th><th>Nhu cầu</th><th>Sale quản lý</th><th>Số đơn</th></tr></thead><tbody>
@forelse($customers as $customer)
@php
    $address = $customer->addresses->firstWhere('is_default', 1) ?? $customer->addresses->first();
    $province = trim((string) ($address?->province?->name ?: $address?->city));
    $ward = trim((string) ($address?->wardModel?->name ?: $address?->ward));
    $areaText = implode(', ', array_filter([$ward, $province]));
    $needs = array_filter([
        $customer->product_name,
        $customer->size ? 'Size: '.$customer->size : null,
        $customer->production ? 'SL: '.$customer->production : null,
        $customer->brand ? 'TH: '.$customer->brand : null,
        $customer->product_note,
    ]);
    $sale = $customer->currentOwner ?? $customer->assignedTo;
    $saleName = trim((string) $sale?->short_name) ?: ($sale?->name ?? '—');
    $addressText = $customer->address ?: ($address?->note ?: implode(', ', array_filter([$address?->house_number, $address?->street, $address?->ward, $address?->city])));
@endphp
<tr><td class="center">{{ $loop->iteration }}</td><td>{{ $customer->customer_code ?: '#'.$customer->id }}</td><td class="customer-name">{{ $customer->name }}</td><td class="phone">{{ $customer->phone }}</td><td class="address">{{ $addressText ?: 'Chưa cập nhật' }}</td><td>{{ $areaText ?: 'Chưa cập nhật' }}</td><td class="needs">{{ $needs ? implode(' · ', $needs) : '—' }}</td><td>{{ $saleName }}</td><td class="center">{{ $customer->orders_count }}</td></tr>
@empty<tr><td colspan="9" class="center">Không có khách hàng phù hợp bộ lọc.</td></tr>@endforelse
</tbody></table>
<script>window.addEventListener('load',()=>window.print());</script>
</body></html>
