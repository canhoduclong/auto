<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Phiếu xuất kho tổng</title>
    <style>
        @page{size:A4 portrait;margin:10mm}*{box-sizing:border-box}body{margin:0;padding:22px;background:#e5e7eb;color:#17202a;font:11px/1.4 Arial,"DejaVu Sans",sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact}.sheet{width:210mm;min-height:297mm;margin:0 auto 18px;padding:11mm;background:#fff;box-shadow:0 3px 18px rgba(15,23,42,.16);page-break-after:always}.sheet:last-child{page-break-after:auto}.no-print{position:fixed;right:22px;top:18px;z-index:10;border:0;border-radius:7px;background:#0f766e;color:#fff;font-weight:700;padding:9px 16px;cursor:pointer}h1{margin:3px 0;text-align:center;font-size:21px}h2{margin:14px 0 6px;padding-bottom:4px;border-bottom:1.5px solid #0f766e;color:#0f5f59;font-size:13px}.sub{text-align:center;margin-bottom:13px}.meta{display:grid;grid-template-columns:1fr 1fr;gap:5px 22px;margin:12px 0;padding:9px 11px;border:1px solid #94a3b8;background:#f7faf9}table{width:100%;border-collapse:collapse;table-layout:fixed;margin:7px 0 12px}thead{display:table-header-group}tr{break-inside:avoid}th,td{border:1px solid #64748b;padding:5px 6px;vertical-align:top;overflow-wrap:anywhere}th{background:#dcece9;font-size:10px}.combined-orders{font-size:9.5px}.combined-orders th{font-size:9px}.combined-orders th:nth-child(1){width:5%}.combined-orders th:nth-child(2){width:24%}.combined-orders th:nth-child(3){width:10%}.combined-orders th:nth-child(4){width:9%}.combined-orders th:nth-child(5){width:13%}.combined-orders th:nth-child(6){width:16%}.combined-orders th:nth-child(7){width:23%}.order-note{white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.35}.num{text-align:right;white-space:nowrap}.center{text-align:center}.muted{color:#64748b;font-size:10px}.draft{text-align:center;color:#b91c1c;font-weight:700}.sign{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:24px;text-align:center}.sign-space{height:58px}.note{min-height:55px;padding:8px;border:1px solid #94a3b8;white-space:pre-wrap}.summary{font-weight:700;background:#eef6f4}@media print{body{padding:0;background:#fff}.no-print{display:none}.sheet{width:auto;min-height:auto;margin:0;padding:0;box-shadow:none}}
    </style>
</head>
<body>
@php
    $formatKg = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',').' kg';
    $formatMoney = static fn ($value): string => number_format((float) $value, 0, ',', '.').'đ';
    $totalOrders = $documents->sum(fn ($document) => $document['orderRows']->count());
    $totalQuantity = $totalExportSummaryRows->sum('quantity');
    $totalWeight = $totalExportSummaryRows->sum('weight');
@endphp
<button class="no-print" onclick="window.print()">In phiếu tổng</button>

<main class="sheet">
    <div class="muted">HOÀNG LONG TNT</div>
    <h1>PHIẾU XUẤT KHO TỔNG</h1>
    <div class="sub">Ngày in {{ now()->format('d/m/Y H:i') }} · {{ $documents->count() }} phiếu đã chọn</div>
    <div class="meta">
        <div>Tổng tài xế/phiếu: <strong>{{ number_format($documents->count()) }}</strong></div>
        <div>Tổng đơn hàng: <strong>{{ number_format($totalOrders) }}</strong></div>
        <div>Tổng số lượng: <strong>{{ number_format($totalQuantity) }}</strong></div>
        <div>Tổng khối lượng: <strong>{{ $formatKg($totalWeight) }}</strong></div>
    </div>
    <h2>DANH SÁCH PHIẾU XUẤT THEO TÀI XẾ</h2>
    <table>
        <thead><tr><th class="center" style="width:6%">STT</th><th style="width:17%">Mã phiếu</th><th style="width:19%">Tài xế</th><th>Tuyến kho</th><th class="num" style="width:10%">Đơn</th><th class="num" style="width:11%">Số lượng</th><th class="num" style="width:13%">Khối lượng</th></tr></thead>
        <tbody>
        @foreach($documents as $document)
            @php($slip = $document['slip'])
            <tr><td class="center">{{ $loop->iteration }}</td><td><strong>{{ $slip->code }}</strong><div class="muted">{{ $slip->business_date->format('d/m/Y') }}</div></td><td><strong>{{ $slip->shipper?->short_name ?: $slip->shipper?->name }}</strong><div class="muted">{{ $slip->shipper?->phone }}</div></td><td>{{ $slip->sourceWarehouse?->name }} → {{ $slip->targetWarehouse?->name }}</td><td class="num">{{ number_format($document['orderRows']->count()) }}</td><td class="num">{{ number_format($document['summaryRows']->sum('quantity')) }}</td><td class="num">{{ $formatKg($document['summaryRows']->sum('weight')) }}</td></tr>
        @endforeach
        </tbody>
        <tfoot><tr class="summary"><td colspan="4">TỔNG CỘNG</td><td class="num">{{ number_format($totalOrders) }}</td><td class="num">{{ number_format($totalQuantity) }}</td><td class="num">{{ $formatKg($totalWeight) }}</td></tr></tfoot>
    </table>
    <h2>DANH SÁCH ĐƠN HÀNG GỘP</h2>
    <table class="combined-orders">
        <thead><tr><th class="center">STT</th><th>Khách hàng / Mã đơn</th><th>Sale</th><th class="num">Số lượng</th><th class="num">KL thực đóng</th><th class="num">Tổng tiền hàng</th><th>Ghi chú đơn</th></tr></thead>
        <tbody>
        @forelse($totalOrderRows as $row)
            <tr><td class="center">{{ $loop->iteration }}</td><td><strong>{{ $row['customer_name'] ?: 'Không rõ khách hàng' }}</strong><div class="muted">{{ $row['code'] }}</div></td><td>{{ $row['sale_name'] ?: '—' }}</td><td class="num">{{ number_format($row['item_quantity']) }}</td><td class="num">{{ $formatKg($row['packed_weight']) }}</td><td class="num">{{ $formatMoney($row['product_amount']) }}</td><td class="order-note">{{ filled($row['order_note']) ? $row['order_note'] : '—' }}</td></tr>
        @empty
            <tr><td colspan="7" class="center">Không có đơn hàng trong các phiếu đã chọn.</td></tr>
        @endforelse
        </tbody>
        <tfoot><tr class="summary"><td colspan="3">TỔNG CỘNG</td><td class="num">{{ number_format($totalOrderRows->sum('item_quantity')) }}</td><td class="num">{{ $formatKg($totalOrderRows->sum('packed_weight')) }}</td><td class="num">{{ $formatMoney($totalOrderRows->sum('product_amount')) }}</td><td></td></tr></tfoot>
    </table>
    <h2>TỔNG HỢP HÀNG HÓA</h2>
    @include('warehouse.dispatch-slips._export_products', ['rows' => $totalExportSummaryRows])
    <div class="sign"><div><strong>NGƯỜI LẬP PHIẾU</strong><div class="muted">(Ký, ghi rõ họ tên)</div><div class="sign-space"></div></div><div><strong>THỦ KHO XUẤT</strong><div class="muted">(Ký, ghi rõ họ tên)</div><div class="sign-space"></div></div><div><strong>NGƯỜI DUYỆT</strong><div class="muted">(Ký, ghi rõ họ tên)</div><div class="sign-space"></div></div></div>
</main>
</body>
</html>
