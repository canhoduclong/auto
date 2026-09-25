<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Phiếu giao hàng {{ date('d-m-Y', strtotime($selectedDate)) }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin:0; color:#111827; font:10px/1.35 Arial, sans-serif; }
        .sheet { min-height: 277mm; break-after: page; page-break-after: always; }
        .sheet:last-child { break-after:auto; page-break-after:auto; }
        .company { display:grid; grid-template-columns:60% 40%; align-items:start; border-bottom:1px solid #111827; padding-bottom:6px; }
        .logo { max-width:34mm; max-height:16mm; object-fit:contain; }
        .company-name { font-size:12px; font-weight:800; text-transform:uppercase; }
        .company-info { font-size:8.5px; }
        h1 { text-align:center; font-size:18px; color:#1f4e79; margin:4px 0 0; }
        .subtitle { text-align:center; font-size:9px; font-style:italic; color:#1f4e79; margin-bottom:6px; }
        .info { display:grid; grid-template-columns:1fr 1fr 1fr; border:1px solid #6b7280; margin-bottom:7px; }
        .info div { padding:3px 5px; min-height:18px; border-bottom:1px solid #d1d5db; }
        .info div:nth-child(3n+1), .info div:nth-child(3n+2) { border-right:1px solid #d1d5db; }
        .info div:nth-last-child(-n+3) { border-bottom:0; }
        .label { font-weight:700; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        .items th, .items td { border:1px solid #4b5563; padding:4px 3px; vertical-align:middle; }
        .items th { background:#e8eef5; color:#1f4e79; text-align:center; }
        .center { text-align:center; } .right { text-align:right; }
        .totals { width:45%; margin:8px 0 16px auto; }
        .totals td { padding:3px 5px; border-bottom:1px solid #d1d5db; }
        .totals td:last-child { text-align:right; font-weight:700; }
        .signatures { display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; text-align:center; margin-top:24px; page-break-inside:avoid; }
        .signature { min-height:86px; font-weight:700; }
        .signature small { display:block; font-weight:400; font-style:italic; }
        .space { height:52px; }
        @media screen { body { background:#e5e7eb; } .sheet { width:210mm; margin:12px auto; padding:10mm; background:#fff; box-shadow:0 2px 10px #94a3b8; } }
        @media print { .sheet { padding:0; } }
    </style>
</head>
<body>
@php
    $companyName = \App\Models\Setting::get('company_legal_name', \App\Models\Setting::get('brand_name', 'CÔNG TY CỔ PHẦN THỰC PHẨM HOÀNG LONG TNT'));
    $companyAddress = \App\Models\Setting::get('company_address', \App\Models\Setting::get('address', ''));
    $companyPhone = \App\Models\Setting::get('company_phone', \App\Models\Setting::get('phone', ''));
    $companyTax = \App\Models\Setting::get('company_tax_code', '');
    $companyLogo = 'https://hoanglongtnt.com/storage/media/1XrclAQJcTDneyC1SUTth1Qk976G0W20LO0e51oO.png';
@endphp
@foreach($orders as $order)
@php
    $totalQuantity = $order->items->sum('quantity');
    $totalWeight = $order->items->sum(fn ($item) => (float) ($item->actual_weight ?? $item->packed_weight ?? $item->total_weight ?? 0));
@endphp
<section class="sheet">
    <div class="company">
        <div>
            <img class="logo" src="{{ $companyLogo }}" alt="Logo"><div class="company-name">{{ $companyName }}</div>
            @if($companyTax)<div class="company-info">MST: {{ $companyTax }}</div>@endif
            @if($companyAddress)<div class="company-info">Địa chỉ: {{ $companyAddress }}</div>@endif
            @if($companyPhone)<div class="company-info">Điện thoại: {{ $companyPhone }}</div>@endif
        </div>
        <div class="company-info right">Kho xuất: <strong>{{ $order->warehouse?->name ?? '—' }}</strong><br>Ngày phiếu: <strong>{{ date('d/m/Y', strtotime($selectedDate)) }}</strong></div>
    </div>
    <h1>PHIẾU GIAO HÀNG</h1>
    <div class="subtitle">Số chứng từ: <strong>PXK-{{ $order->code ?: $order->id }}</strong></div>
    <div class="info">
        <div><span class="label">Khách hàng:</span> {{ $order->recipient_name ?: $order->customer?->name ?: '—' }}</div>
        <div><span class="label">Mã đơn:</span> {{ $order->code ?: '#'.$order->id }}</div>
        <div><span class="label">Người lập:</span> {{ $order->user?->name ?? auth()->user()?->name ?? '—' }}</div>
        <div><span class="label">Địa chỉ giao:</span> {{ $order->recipient_address ?: $order->customer?->address ?: '—' }}</div>
        <div><span class="label">Điện thoại:</span> {{ $order->recipient_phone ?: $order->customer?->phone ?: '—' }}</div>
        <div><span class="label">Tài xế:</span> {{ $order->shipper?->name ?? '—' }}</div>
        <div><span class="label">SĐT tài xế:</span> {{ $order->shipper?->phone ?? '—' }}</div>
        <div><span class="label">Ghi chú:</span> {{ $order->note ?: '—' }}</div>
        <div></div>
    </div>
    <table class="items">
        <thead><tr><th style="width:5%">STT</th><th style="width:11%">Mã hàng</th><th style="width:27%">Tên sản phẩm</th><th style="width:8%">ĐVT</th><th style="width:9%">Số lượng</th><th style="width:11%">Khối lượng</th><th style="width:11%">Thực giao</th><th style="width:9%">Đơn giá</th><th style="width:11%">Thành tiền</th></tr></thead>
        <tbody>
        @forelse($order->items as $index => $item)
            @php
                $product = $item->variant?->product ?: $item->product;
                $unit = $product?->unit_label ?? 'Cái';
                $weight = $item->actual_weight ?? $item->packed_weight ?? $item->total_weight;
                $amount = (float) ($item->total ?? ((float) $item->quantity * (float) $item->price));
            @endphp
            <tr><td class="center">{{ $index + 1 }}</td><td class="center">{{ $item->variant?->sku ?? '—' }}</td><td><strong>{{ $product?->name ?? $item->imported_name ?? '—' }}</strong><br>{{ $item->variant?->name }}</td><td class="center">{{ $unit }}</td><td class="center">{{ number_format((float) $item->quantity, 0, ',', '.') }}</td><td class="center">{{ $weight !== null ? format_kg((float) $weight) : '—' }}</td><td></td><td class="right">{{ number_format((float) $item->price, 0, ',', '.') }}đ</td><td class="right">{{ number_format($amount, 0, ',', '.') }}đ</td></tr>
        @empty
            <tr><td colspan="9" class="center">Không có hàng hóa.</td></tr>
        @endforelse
        </tbody>
    </table>
    <table class="totals">
        <tr><td>Tổng số lượng</td><td>{{ number_format((float) $totalQuantity, 0, ',', '.') }}</td></tr>
        <tr><td>Khối lượng thực tế</td><td>{{ $totalWeight > 0 ? format_kg($totalWeight) : '—' }}</td></tr>
        <tr><td>Phí vận chuyển</td><td>{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}đ</td></tr>
        <tr><td>Tổng cộng</td><td>{{ number_format((float) $order->total, 0, ',', '.') }}đ</td></tr>
    </table>
    <div class="signatures">
        <div class="signature">NGƯỜI LẬP PHIẾU<small>(Ký, ghi rõ họ tên)</small><div class="space"></div>{{ $order->user?->name ?? auth()->user()?->name }}</div>
        <div class="signature">NGƯỜI GIAO HÀNG<small>(Ký, ghi rõ họ tên)</small><div class="space"></div></div>
        <div class="signature">NGƯỜI NHẬN HÀNG<small>(Ký, ghi rõ họ tên)</small><div class="space"></div></div>
        <div class="signature">THỦ KHO<small>(Ký, ghi rõ họ tên)</small><div class="space"></div></div>
    </div>
</section>
@endforeach
<script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
