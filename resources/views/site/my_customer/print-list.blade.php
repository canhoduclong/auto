<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><title>Danh sách khách hàng</title>
<style>
@page{size:A4 landscape;margin:12mm}body{font:12px/1.4 Arial,sans-serif;color:#111827}h1{text-align:center;font-size:21px;margin:8px 0}table{width:100%;border-collapse:collapse;margin-top:14px}th,td{border:1px solid #64748b;padding:7px;vertical-align:top}th{background:#eef2f7}thead{display:table-header-group}tr{break-inside:avoid}.center{text-align:center}.meta{font-size:11px;color:#475569;text-align:center}.tools{text-align:right;margin:10px}@media print{.tools{display:none}}
</style></head>
<body>
<div class="tools"><button onclick="window.print()">In danh sách</button></div>
<h1>DANH SÁCH KHÁCH HÀNG</h1>
<div class="meta">Ngày in: {{ now()->format('d/m/Y H:i') }} · Người in: {{ auth()->user()->name }} · Tổng: {{ number_format($customers->count()) }} khách</div>
<div class="meta">@if($search !== '')Tìm kiếm: {{ $search }} · @endif @if(request()->filled('area'))Khu vực: {{ request('area') === \App\Services\CustomerAreaService::UNKNOWN ? 'Chưa cập nhật' : request('area') }} @endif @if(request()->filled('ward')) · Phường: {{ request('ward') === \App\Services\CustomerAreaService::UNKNOWN ? 'Chưa cập nhật' : request('ward') }}@endif</div>
<table><thead><tr><th style="width:4%">STT</th><th style="width:10%">Mã KH</th><th style="width:22%">Khách hàng</th><th style="width:13%">Điện thoại</th><th>Địa chỉ</th><th style="width:17%">Sale đang quản lý</th><th style="width:6%">Số đơn</th></tr></thead><tbody>
@forelse($customers as $customer)
@php
    $address = $customer->addresses->firstWhere('is_default', 1) ?? $customer->addresses->first();
    $addressText = $customer->address ?: ($address?->note ?: implode(', ', array_filter([$address?->house_number, $address?->street, $address?->ward, $address?->city])));
@endphp
<tr><td class="center">{{ $loop->iteration }}</td><td>{{ $customer->customer_code ?: '#'.$customer->id }}</td><td>{{ $customer->name }}</td><td>{{ $customer->phone }}</td><td>{{ $addressText }}</td><td>{{ $customer->currentOwner?->name ?? $customer->assignedTo?->name ?? 'Chưa có sale quản lý' }}</td><td class="center">{{ $customer->orders_count }}</td></tr>
@empty<tr><td colspan="7" class="center">Không có khách hàng phù hợp bộ lọc.</td></tr>@endforelse
</tbody></table>
<script>window.addEventListener('load',()=>window.print());</script>
</body></html>
