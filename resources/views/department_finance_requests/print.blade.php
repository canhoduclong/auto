@php
    use App\Models\Setting;

    $items = collect($transaction->request_items ?: []);
    if ($items->isEmpty()) {
        $items = collect([[
            'stt' => 1,
            'content' => $transaction->note ?: $transaction->request_title,
            'unit' => '',
            'quantity' => 1,
            'unit_price' => (float) $transaction->amount,
            'line_total' => (float) $transaction->amount,
        ]]);
    }

    $subtotal = (float) ($transaction->request_subtotal ?? $items->sum('line_total'));
    $vat = (float) ($transaction->request_vat ?? 0);
    $total = (float) ($transaction->request_total ?? $transaction->amount);
    $flow = $transaction->transactionCategory?->flow_direction === 'in' ? 'Thu' : 'Chi';
    $statusLabels = [
        \App\Models\Transaction::STATUS_PENDING_APPROVAL => 'Chờ duyệt',
        \App\Models\Transaction::STATUS_APPROVED => 'Đã duyệt',
        \App\Models\Transaction::STATUS_REJECTED => 'Từ chối',
    ];
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu yêu cầu #{{ $transaction->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e5e7eb;
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 13px;
        }
        .toolbar {
            position: sticky;
            top: 0;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px;
            background: #fff;
            border-bottom: 1px solid #d1d5db;
        }
        .btn {
            border: 1px solid #0f766e;
            background: #0f766e;
            color: #fff;
            border-radius: 4px;
            padding: 8px 14px;
            cursor: pointer;
            font-weight: 700;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 16px auto;
            padding: 18mm;
            background: #fff;
            box-shadow: 0 10px 32px rgba(15, 23, 42, .18);
        }
        .header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
        }
        .brand {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .muted { color: #6b7280; }
        .doc-code {
            text-align: right;
            line-height: 1.7;
        }
        h1 {
            margin: 22px 0 6px;
            text-align: center;
            font-size: 24px;
            text-transform: uppercase;
        }
        .subtitle {
            text-align: center;
            margin-bottom: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 24px;
            margin-bottom: 16px;
        }
        .info-item {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #111827;
            padding: 7px 8px;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            text-align: center;
            font-weight: 800;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .totals {
            width: 44%;
            margin-left: auto;
            margin-top: 12px;
        }
        .totals td {
            border-color: #6b7280;
        }
        .total-label {
            font-weight: 800;
        }
        .note {
            margin-top: 18px;
            line-height: 1.55;
        }
        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-top: 34px;
            text-align: center;
        }
        .signature-box {
            min-height: 92px;
        }
        .signature-title {
            font-weight: 800;
            margin-bottom: 6px;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            @page {
                size: A4;
                margin: 14mm;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn" onclick="window.print()">In chứng từ</button>
    </div>

    <main class="page">
        <div class="header">
            <div>
                <div class="brand">{{ Setting::get('brand_name', 'Hoàng Long TNT') }}</div>
                <div class="muted">{{ Setting::get('company_address', '') }}</div>
                <div class="muted">{{ Setting::get('company_phone', '') }}</div>
            </div>
            <div class="doc-code">
                <div><strong>Số phiếu:</strong> #{{ $transaction->id }}</div>
                <div><strong>Ngày:</strong> {{ optional($transaction->created_at)->format('d/m/Y H:i') }}</div>
                <div><strong>Trạng thái:</strong> {{ $statusLabels[$transaction->status] ?? $transaction->status }}</div>
            </div>
        </div>

        <h1>Phiếu yêu cầu {{ $flow }}</h1>
        <div class="subtitle">{{ $transaction->request_title ?: 'Phiếu yêu cầu' }}</div>

        <div class="info-grid">
            <div class="info-item">
                <strong>Bộ phận:</strong>
                <span>{{ $transaction->request_department ?: $config['label'] }}</span>
            </div>
            <div class="info-item">
                <strong>Người lập:</strong>
                <span>{{ $transaction->submitter?->name ?: '-' }}</span>
            </div>
            <div class="info-item">
                <strong>Danh mục:</strong>
                <span>{{ $transaction->transactionCategory?->code }} - {{ $transaction->transactionCategory?->name }}</span>
            </div>
            <div class="info-item">
                <strong>Phương thức:</strong>
                <span>{{ $transaction->method ?: '-' }}</span>
            </div>
            <div class="info-item">
                <strong>Tài khoản:</strong>
                <span>{{ $transaction->account?->name ?: 'Kế toán chọn khi duyệt' }}</span>
            </div>
            <div class="info-item">
                <strong>Người duyệt:</strong>
                <span>{{ $transaction->approver?->name ?: '-' }}</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:42px">STT</th>
                    <th>Nội dung</th>
                    <th style="width:80px">ĐVT</th>
                    <th style="width:90px">Số lượng</th>
                    <th style="width:120px">Đơn giá</th>
                    <th style="width:130px">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['content'] ?? '' }}</td>
                        <td class="text-center">{{ $item['unit'] ?? '' }}</td>
                        <td class="text-end">{{ number_format((float) ($item['quantity'] ?? 0), 2) }}</td>
                        <td class="text-end">{{ number_format((float) ($item['unit_price'] ?? 0)) }}</td>
                        <td class="text-end">{{ number_format((float) ($item['line_total'] ?? 0)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Tổng tiền</td>
                <td class="text-end">{{ number_format($subtotal) }}đ</td>
            </tr>
            <tr>
                <td>VAT</td>
                <td class="text-end">{{ number_format($vat) }}đ</td>
            </tr>
            <tr>
                <td class="total-label">Tổng cộng</td>
                <td class="text-end total-label">{{ number_format($total) }}đ</td>
            </tr>
        </table>

        <div class="note">
            <strong>Nội dung/Lý do:</strong>
            <div>{{ $transaction->note ?: '-' }}</div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-title">Người lập phiếu</div>
                <div class="muted">(Ký, ghi rõ họ tên)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Trưởng bộ phận</div>
                <div class="muted">(Ký, ghi rõ họ tên)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Kế toán duyệt</div>
                <div class="muted">(Ký, ghi rõ họ tên)</div>
            </div>
        </div>
    </main>
</body>
</html>
