<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu gửi nhà xe - {{ $order->code }}</title>
    <style>
        @page { size: A4 landscape; margin: 15mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; background: #eee; font-family: Arial, sans-serif; }
        .toolbar { padding: 16px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .toolbar form { margin: 0; }
        button { padding: 10px 16px; cursor: pointer; }
        .help { width: 100%; margin: 0; }
        .label { background: white; margin: 0 auto; max-width: 297mm; min-height: 180mm; padding: 28mm 12mm; text-align: center; overflow-wrap: anywhere; }
        h1 { margin: 0 0 8mm; font-size: 32pt; text-transform: uppercase; line-height: 1.25; }
        .customer { font-size: 26pt; line-height: 1.45; }
        .station { margin-top: 15mm; font-size: 26pt; line-height: 1.4; }
        .station-address { font-size: 16pt; margin-top: 3mm; }
        .station-phone { font-size: 11pt; margin-top: 3mm; }
        @media print { body { background: white; } .toolbar { display: none; } .label { width: 100%; margin: 0; padding: 15mm 0; min-height: 0; } }
        @media screen and (max-width: 600px) { .label { padding: 28px 16px; } h1 { font-size: 26px; } .customer, .station { font-size: 22px; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">In phiếu</button>
        <strong>{{ $order->shipping_label_printed_at ? 'Đã in' : 'Chưa in' }}</strong>
        @if($order->shipping_label_printed_at)
            <span>{{ $order->shipping_label_printed_at->format('d/m/Y H:i') }}</span>
        @endif
        <form method="POST" action="{{ route('warehouse.orders.shipping-label.update', $order) }}">
            @csrf
            <input type="hidden" name="printed" value="{{ $order->shipping_label_printed_at ? 0 : 1 }}">
            <button type="submit">{{ $order->shipping_label_printed_at ? 'Đánh dấu chưa in' : 'Đánh dấu đã in' }}</button>
        </form>
        <p class="help">Đơn {{ $order->code }} · Sau khi in thành công, bấm “Đánh dấu đã in”. Nếu hủy in, giữ trạng thái chưa in.</p>
    </div>
    <main class="label">
        <h1>{{ $label['customer_name'] }}</h1>
        <div class="customer">Tel: <strong>{{ $label['customer_phone'] }}</strong></div>
        <div class="customer">Địa chỉ: <strong>{{ $label['customer_address'] }}</strong></div>
        <div class="station">Nhà xe: <strong>{{ $label['station_name'] }}</strong></div>
        <div class="station-address">{{ $label['station_address'] }}</div>
        <div class="station-phone">Điện thoại: {{ $label['station_phone'] }}</div>
    </main>
    @if(session('shipping_label_updated'))
        <script>
            if (window.opener) {
                window.opener.postMessage({ type: 'shipping-label-updated', orderId: {{ (int) $order->id }}, printed: @json((bool) $order->shipping_label_printed_at) }, window.location.origin);
            }
        </script>
    @endif
</body>
</html>
