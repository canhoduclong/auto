<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $row['number'] }} · {{ $selected['name'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/customer-debt-voucher.css') }}?v={{ filemtime(public_path('css/customer-debt-voucher.css')) }}">
</head>
<body>
@php
    $collectionReceipt = $collectionReceipt ?? false;
    $paymentConfirmation = $paymentConfirmation ?? false;
    $linkedReceipt = $collectionReceipt || $paymentConfirmation;
    $receipt = $row['type'] === 'receipt';
    $money = fn ($value) => number_format($value, 0, ',', '.').' đ';
    $printedAmount = $receipt && !$collectionReceipt ? $row['paid'] : $row['remaining'];
    $visibleAmount = fn ($amount) => (float) $amount !== 0.0;
    $summaryLabels = [
        'total' => $collectionReceipt ? 'Tổng cộng' : 'Tổng phải thu',
        'paid' => 'Đã thu theo phiếu',
    ];
    if (!$collectionReceipt) {
        $summaryLabels['remaining'] = $receipt ? 'Số tiền Thu' : 'Còn phải thu';
    }
    $summaryLabels = array_filter($summaryLabels, fn ($key) => $visibleAmount($row[$key]), ARRAY_FILTER_USE_KEY);
@endphp
<main>
    <div class="voucher-tools">
        <a href="{{ request()->boolean('from_register') || !auth()->user()?->canManageCustomerDebt() ? route('customer-debt.payment-requests') : route('customer-debt.index', $query + ['debt_section' => 'vouchers']).'#debt-vouchers' }}">Về danh sách phiếu</a>
        <button type="button" onclick="window.print()">In / Lưu PDF</button>
    </div>
    <article class="voucher voucher-record{{ $receipt ? ' voucher-receipt' : '' }}">
        <header class="voucher-brand"><strong>{{ $company }}</strong></header>
        <div class="voucher-body">
            <h1>{{ $paymentConfirmation ? 'Xác nhận đã nhận tiền' : \App\Services\CustomerDebtVouchers::TYPES[$row['type']] }}</h1>
            <div class="voucher-record-meta">
                <span><strong>{{ $paymentConfirmation ? 'Tham chiếu phiếu thu' : 'Số phiếu' }}</strong> {{ $row['number'] }}</span>
                @if($linkedReceipt)
                    <span><strong>Đề nghị thanh toán</strong> {{ $row['request_number'] }}</span>
                    @if($paymentConfirmation && !empty($row['payment_recorded_at']))
                        <span><strong>Ngày ghi nhận thu</strong> {{ \Carbon\Carbon::parse($row['payment_recorded_at'])->timezone(config('app.display_timezone'))->format('d/m/Y') }}</span>
                    @endif
                @endif
                @unless($paymentConfirmation)
                    <span><strong>Ngày lập</strong> {{ \Carbon\Carbon::parse($row['created_at'])->timezone(config('app.display_timezone'))->format('d/m/Y') }}</span>
                @endunless
            </div>
            <h2><span>Khách hàng</span> {{ $selected['name'] }}</h2>
            @if($address || $phone)
                <dl class="voucher-record-contact">
                    @if($address)<div><dt>Địa chỉ</dt><dd>{{ $address }}</dd></div>@endif
                    @if($phone)<div><dt>Số điện thoại</dt><dd>{{ $phone }}</dd></div>@endif
                </dl>
            @endif
            @if(!$receipt && !($row['by_purchase_days'] ?? !empty($row['purchase_days'])))
                <p>Chốt công nợ đến {{ \Carbon\Carbon::parse($row['through'])->format('d/m/Y') }}</p>
            @endif
            @if(!$paymentConfirmation && ($summaryLabels || $visibleAmount($row['carry']) || $visibleAmount($row['new_amount'])))
                <table class="voucher-record-table">
                    <thead><tr><th scope="col">Nội dung</th><th scope="col">Số tiền</th></tr></thead>
                    <tbody>
                        @if($visibleAmount($row['carry']))
                            <tr><td>Nợ chuyển từ phiếu trước</td><td>{{ $money($row['carry']) }}</td></tr>
                        @endif
                        @forelse($row['purchase_days'] ?? [] as $date => $amount)
                            @if($visibleAmount($amount))
                                <tr><td>Mua hàng ngày {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td><td>{{ $money($amount) }}</td></tr>
                            @endif
                        @empty
                            @if($visibleAmount($row['new_amount']))
                                <tr><td>Công nợ đưa vào phiếu</td><td>{{ $money($row['new_amount']) }}</td></tr>
                            @endif
                        @endforelse
                    </tbody>
                    @if($summaryLabels)
                        <tfoot>
                            @foreach($summaryLabels as $key => $label)
                                <tr><th scope="row">{{ $label }}</th><td>{{ $money($row[$key]) }}</td></tr>
                            @endforeach
                        </tfoot>
                    @endif
                </table>
                @if($receipt && !$collectionReceipt && $visibleAmount($row['remaining']))
                    <p class="voucher-record-words"><strong>Bằng chữ</strong> {{ \App\Support\VietnameseMoneyWords::format($row['remaining']) }}</p>
                @endif
            @endif
            <div class="voucher-at-end">
                @if($collectionReceipt || $visibleAmount($printedAmount))
                    <div class="voucher-total"><strong>{{ $collectionReceipt ? 'Số tiền cần thu' : ($paymentConfirmation ? 'Số tiền công ty đã nhận' : ($receipt ? 'Số tiền thực thu' : 'Số tiền đề nghị thanh toán')) }}</strong><b>{{ $money($printedAmount) }}</b></div>
                    <p class="voucher-record-words"><strong>Bằng chữ</strong> {{ \App\Support\VietnameseMoneyWords::format($printedAmount) }}</p>
                @endif
                @if($collectionReceipt)
                    <p>Số tiền thực nhận từ khách ................................................ đ</p>
                    <p>Ngày thu ........ / ........ / ............</p>
                @endif
                @if(!$collectionReceipt && $row['method'])<p>{{ ['cash' => 'Tiền mặt', 'transfer' => 'Chuyển khoản'][$row['method']] }}</p>@endif
                @if(!$collectionReceipt && $row['note'])<p>{{ $row['note'] }}</p>@endif
                <footer class="voucher-signatures">
                    @foreach(($receipt ? ['Người nộp tiền', $collectionReceipt ? 'Shipper thu tiền' : 'Người thu tiền', 'Người lập phiếu'] : ['Người lập phiếu', 'Phòng Tài chính - Kế toán']) as $signature)
                        <div><strong>{{ $signature }}</strong><span>{{ $signature === 'Người lập phiếu' && $creatorName ? $creatorName : '(Ký, ghi rõ họ tên)' }}</span></div>
                    @endforeach
                </footer>
            </div>
        </div>
    </article>
</main>
</body>
</html>
