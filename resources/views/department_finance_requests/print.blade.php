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
    $createdAt = $transaction->created_at ?: now();
    $flow = $transaction->transactionCategory?->flow_direction === 'in' || $transaction->type === 'extra_income' ? 'Thu' : 'Chi';
    $isPaymentProposal = $transaction->request_form_type === \App\Models\Transaction::REQUEST_FORM_PAYMENT;
    $documentTitle = $isPaymentProposal ? 'Phiếu đề nghị thanh toán' : 'Phiếu yêu cầu ' . mb_strtolower($flow);
    $departmentName = $transaction->submitter?->department?->name
        ?: $transaction->submitter?->block?->name
        ?: ($transaction->request_department ?: ($config['label'] ?? '-'));

    $companyName = Setting::get('company_legal_name', Setting::get('brand_name', 'CÔNG TY CỔ PHẦN THỰC PHẨM HOÀNG LONG TNT'));
    $companyDisplayName = str_replace(
        'CÔNG TY CỔ PHẦN THỰC PHẨM HOÀNG LONG TNT',
        "CÔNG TY CỔ PHẦN THỰC PHẨM\nHOÀNG LONG TNT",
        $companyName
    );
    $amountText = function (float $amount): string {
        $number = (int) round($amount);
        if ($number === 0) {
            return 'Không đồng.';
        }

        $digits = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $units = ['', 'ngàn', 'triệu', 'tỷ'];

        $readThree = function (int $value, bool $full) use ($digits): string {
            $hundreds = intdiv($value, 100);
            $tens = intdiv($value % 100, 10);
            $ones = $value % 10;
            $parts = [];

            if ($hundreds > 0 || $full) {
                $parts[] = $digits[$hundreds] . ' trăm';
            }

            if ($tens > 1) {
                $parts[] = $digits[$tens] . ' mươi';
                if ($ones === 1) {
                    $parts[] = 'mốt';
                } elseif ($ones === 5) {
                    $parts[] = 'lăm';
                } elseif ($ones > 0) {
                    $parts[] = $digits[$ones];
                }
            } elseif ($tens === 1) {
                $parts[] = 'mười';
                if ($ones === 5) {
                    $parts[] = 'lăm';
                } elseif ($ones > 0) {
                    $parts[] = $digits[$ones];
                }
            } elseif ($ones > 0) {
                if ($hundreds > 0 || $full) {
                    $parts[] = 'lẻ';
                }
                $parts[] = $digits[$ones];
            }

            return trim(implode(' ', $parts));
        };

        $groups = [];
        while ($number > 0) {
            $groups[] = $number % 1000;
            $number = intdiv($number, 1000);
        }

        $words = [];
        for ($i = count($groups) - 1; $i >= 0; $i--) {
            $group = (int) $groups[$i];
            if ($group === 0) {
                continue;
            }

            $full = $i < count($groups) - 1 && $group < 100;
            $words[] = trim($readThree($group, $full) . ' ' . ($units[$i] ?? ''));
        }

        $text = trim(implode(' ', $words)) . ' đồng.';
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    };

    $formatQuantity = function ($quantity): string {
        $quantity = (float) $quantity;
        if (floor($quantity) == $quantity) {
            return str_pad((string) (int) $quantity, 2, '0', STR_PAD_LEFT);
        }

        return rtrim(rtrim(number_format($quantity, 2, ',', '.'), '0'), ',');
    };

    $minimumRows = 4;
    $emptyRows = max(0, $minimumRows - $items->count());
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentTitle }} #{{ $transaction->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e5e7eb;
            color: #111;
            font-family: "Times New Roman", Times, serif;
            font-size: 15px;
            line-height: 1.28;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px;
            background: #fff;
            border-bottom: 1px solid #d1d5db;
        }
        .btn {
            border: 1px solid #111827;
            background: #111827;
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
            padding: 18mm 16mm;
            background: #fff;
            box-shadow: 0 10px 32px rgba(15, 23, 42, .18);
        }
        .top {
            display: grid;
            grid-template-columns: 1fr 290px;
            gap: 18px;
            align-items: start;
        }
        .company-block {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .company {
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            max-width: 330px;
            line-height: 1.12;
        }
        .voucher-no {
            margin-top: 12px;
            text-align: center;
        }
        .form-code {
            text-align: center;
            font-weight: 700;
            line-height: 1.25;
        }
        .form-code-line {
            white-space: nowrap;
        }
        .form-code em {
            display: block;
            font-weight: 400;
        }
        h1 {
            margin: 16px 0 12px;
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 25px;
            text-transform: uppercase;
            letter-spacing: .2px;
        }
        .date-line {
            text-align: center;
            font-style: italic;
            margin-bottom: 10px;
        }
        .info-lines {
            margin: 0 8px 12px;
        }
        .info-row {
            display: flex;
            gap: 8px;
            margin: 6px 0;
        }
        .info-label {
            font-weight: 700;
            font-style: italic;
            white-space: nowrap;
        }
        .money {
            font-weight: 800;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #111;
            padding: 7px 8px;
            vertical-align: top;
        }
        th {
            text-align: center;
            font-weight: 800;
            white-space: nowrap;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: 800; }
        .empty-row td { height: 26px; }
        .summary-label {
            text-align: center;
            font-weight: 800;
        }
        .note {
            margin: 22px 8px 0;
        }
        .signatures {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 34px;
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 13px;
        }
        .signature-box {
            min-height: 88px;
        }
        .signature-title {
            font-weight: 800;
            margin-bottom: 6px;
        }
        .signature-hint {
            color: #374151;
            font-weight: 400;
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
                margin: 16mm;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn" onclick="window.print()">In chứng từ</button>
    </div>

    <main class="page">
        <div class="top">
            <div class="company-block">
                <div class="company">{!! nl2br(e($companyDisplayName)) !!}</div>
                <div class="voucher-no"><strong>Số phiếu:</strong> #{{ $transaction->id }}</div>
            </div>
            <div class="form-code">
                Mẫu số: 05-TT
                <em class="form-code-line">(Ban hành theo Thông tư 200/2014/TT-BTC)</em>
                <em>ngày 24/12/2014 của Bộ trưởng BTC</em>
            </div>
        </div>

        <h1>{{ $documentTitle }}</h1>
        <div class="date-line">
            Ngày {{ $createdAt->format('d') }} tháng {{ $createdAt->format('m') }} năm {{ $createdAt->format('Y') }}.
        </div>

        <div class="info-lines">
            <div class="info-row">
                <span class="info-label">Kính gửi:</span>
                <span>Ban lãnh đạo và bộ phận kế toán</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ $isPaymentProposal ? 'Họ và tên người đề nghị thanh toán:' : 'Họ và tên người yêu cầu:' }}</span>
                <span>{{ $transaction->submitter?->name ?: '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Bộ phận:</span>
                <span>{{ $departmentName }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ $isPaymentProposal ? 'Nội dung thanh toán:' : 'Nội dung yêu cầu:' }}</span>
                <span>{{ $transaction->request_title ?: ($transaction->note ?: '-') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Số tiền:</span>
                <span>
                    <span class="money">{{ number_format($total, 0, ',', '.') }}</span>
                    vnđ (Viết bằng chữ): <em>{{ $amountText($total) }}</em>
                </span>
            </div>
            @if($flow === 'Chi' && $transaction->account)
                <div class="info-row">
                    <span class="info-label">Tài khoản chi:</span>
                    <span>{{ $transaction->account->name }}</span>
                </div>
            @endif
            <div class="info-row">
                <span class="info-label">Nơi nhận tiền:</span>
                <span>
                    @if($transaction->destination_type === 'internal')
                        {{ $transaction->destinationAccount?->name ?: '-' }}
                        @if($transaction->destinationAccount?->account_number)
                            - {{ $transaction->destinationAccount->account_number }}
                        @endif
                        @if($transaction->destinationAccount?->bank_name)
                            ({{ $transaction->destinationAccount->bank_name }})
                        @endif
                    @elseif($transaction->destination_type === 'cash')
                        Tiền mặt
                    @else
                        {{ $transaction->external_recipient ?: 'Bên ngoài' }}
                        @if($transaction->external_account_number)
                            - STK: {{ $transaction->external_account_number }}
                        @endif
                        @if($transaction->external_bank_name)
                            - {{ $transaction->external_bank_name }}{{ $transaction->external_bank_branch ? ' / ' . $transaction->external_bank_branch : '' }}
                        @endif
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">(Kèm theo:</span>
                <span>........................................................ chứng từ gốc).</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:38px">STT</th>
                    <th>Nội dung</th>
                    <th style="width:68px">ĐVT</th>
                    <th style="width:76px">Số lượng</th>
                    <th style="width:94px">Đơn giá</th>
                    <th style="width:94px">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['content'] ?? '' }}</td>
                        <td class="text-center">{{ $item['unit'] ?? '' }}</td>
                        <td class="text-center">{{ $formatQuantity($item['quantity'] ?? 0) }}</td>
                        <td class="text-end">{{ number_format((float) ($item['unit_price'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) ($item['line_total'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                @for($i = 0; $i < $emptyRows; $i++)
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
                <tr>
                    <td colspan="5" class="summary-label">TỔNG</td>
                    <td class="text-end fw-bold">{{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="summary-label">VAT</td>
                    <td class="text-end">{{ number_format($vat, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="summary-label">TỔNG CỘNG</td>
                    <td class="text-end fw-bold">{{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="note">
            <strong>Nội dung/Lý do:</strong>
            <div>{{ $transaction->note ?: '-' }}</div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-title">{{ $isPaymentProposal ? 'Người đề nghị' : 'Người lập phiếu' }}</div>
                <div class="signature-hint">(Ký, ghi rõ họ tên)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Kế toán xác nhận</div>
                <div class="signature-hint">(Ký, ghi rõ họ tên)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Director</div>
                <div class="signature-hint">(Ký, ghi rõ họ tên)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Kế toán hoàn thành</div>
                <div class="signature-hint">(Ký, ghi rõ họ tên)</div>
            </div>
        </div>
    </main>
</body>
</html>
