<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Thống kê khu vực khách hàng</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font: 12px/1.5 Arial, sans-serif; }
        h1 { font-size: 20px; text-align: center; margin: 12px 0 6px; }
        .meta { text-align: center; color: #475569; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; table-layout: fixed; }
        th, td { border: 1px solid #64748b; padding: 8px 10px; overflow-wrap: anywhere; }
        th { background: #eef2f7; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        .center { text-align: center; }
        .total { font-weight: bold; background: #f8fafc; }
        .tools { text-align: right; }
        @media screen { body { max-width: 180mm; margin: 20px auto; } }
        @media print { .tools { display: none; } }
    </style>
</head>
<body>
@php
    $isWardList = $area !== null && $area !== '';
    $unknown = \App\Services\CustomerAreaService::UNKNOWN;
@endphp
<div class="tools"><button onclick="window.print()">In thống kê</button></div>
<h1>THỐNG KÊ KHÁCH HÀNG THEO {{ $isWardList ? 'PHƯỜNG' : 'TỈNH / THÀNH' }}</h1>
<div class="meta">Khu vực: {{ $isWardList ? ($area === $unknown ? 'Chưa cập nhật khu vực' : $area) : 'Tất cả khu vực' }}</div>
<div class="meta">Ngày in: {{ now()->format('d/m/Y H:i') }} · Người in: {{ auth()->user()->name }}</div>
@if($search !== '')<div class="meta">Tìm kiếm khách hàng: {{ $search }}</div>@endif
<div class="meta">{{ $rows->count() }} {{ $isWardList ? 'phường / nhóm khu vực' : 'tỉnh, thành / nhóm khu vực' }} · {{ number_format($rows->sum('customer_count'), 0, ',', '.') }} khách hàng</div>
<table>
    <colgroup><col style="width:10%"><col style="width:65%"><col style="width:25%"></colgroup>
    <thead><tr><th>STT</th><th>{{ $isWardList ? 'Phường' : 'Tỉnh / Thành phố' }}</th><th>Số khách hàng</th></tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $row->name === $unknown ? ($isWardList ? 'Chưa cập nhật phường' : 'Chưa cập nhật khu vực') : $row->name }}</td><td class="center">{{ number_format($row->customer_count, 0, ',', '.') }}</td></tr>
    @empty
        <tr><td colspan="3" class="center">Chưa có khách hàng phù hợp.</td></tr>
    @endforelse
        <tr class="total"><td colspan="2">Tổng cộng</td><td class="center">{{ number_format($rows->sum('customer_count'), 0, ',', '.') }}</td></tr>
    </tbody>
</table>
<script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
