<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Thông tin giao nhà xe - {{ $order->code }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 18px; background: #eef2f7; color: #090909; font-family: Arial, "DejaVu Sans", sans-serif; }
        .sheet { width: 277mm; min-height: 190mm; margin: auto; padding: 22mm 18mm 16mm; background: #fff; border: 1px solid #94a3b8; }
        h1 { margin: 0 0 14px; text-align: center; font-size: 46px; line-height: 1.15; text-transform: uppercase; }
        .line { margin: 11px 0; text-align: center; font-size: 34px; line-height: 1.25; }
        .line strong { font-weight: 800; }
        .customer-address { font-size: 32px; }
        .station-block { margin-top: 25px; padding-top: 14px; border-top: 1px solid #cbd5e1; }
        .station-line { margin: 5px 0; text-align: center; color: #334155; font-size: 17px; line-height: 1.3; }
        .station-line strong { font-weight: 700; }
        .order-code { margin-top: 12px; text-align: center; color: #475569; font-size: 11px; }
        .print { position: fixed; top: 16px; right: 18px; padding: 9px 15px; border: 0; border-radius: 6px; background: #087f5b; color: #fff; font-weight: 700; cursor: pointer; }
        @media print { body { padding: 0; background: #fff; } .sheet { width: auto; min-height: auto; border: 0; } .print { display: none; } }
    </style>
</head>
<body>
@php
    $customerName = $order->recipient_name ?: $order->customer?->name ?: 'Khách hàng';
    $customerPhone = $order->recipient_phone ?: $order->customer?->phone ?: '—';
    $defaultCustomerAddress = $order->customer?->addresses?->firstWhere('is_default', 1)
        ?? $order->customer?->addresses?->first();
    // Địa chỉ trên nhãn là địa chỉ khách hàng. recipient_address của một số đơn cũ
    // đã từng bị ghi nhầm bằng địa chỉ trạm xe nên không được ưu tiên tại đây.
    $customerAddress = $defaultCustomerAddress?->note
        ?: ($order->customer?->address ?: ($order->recipient_address ?: '—'));
    $stationName = $order->truck_station_name ?: $order->truckStation?->name ?: '—';
    $stationAddress = $order->truck_station_address ?: $order->truckStation?->address ?: '—';
    $stationPhone = $order->truck_station_phone ?: $order->truckStation?->phone ?: '—';
@endphp
<button class="print" onclick="window.print()">In phiếu</button>
<main class="sheet">
    <h1>{{ $customerName }}</h1>
    <div class="line">Tel: <strong>{{ $customerPhone }}</strong></div>
    <div class="line customer-address">Địa chỉ: <strong>{{ $customerAddress }}</strong></div>
    <div class="station-block">
        <div class="station-line">Nhà xe: <strong>{{ $stationName }}</strong></div>
        <div class="station-line">Địa chỉ trạm: <strong>{{ $stationAddress }}</strong></div>
        <div class="station-line">Điện thoại trạm: <strong>{{ $stationPhone }}</strong></div>
    </div>
    <div class="order-code">Đơn hàng: {{ $order->code }}</div>
</main>
</body>
</html>
