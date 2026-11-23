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
    $receipt = $row['type'] === 'receipt';
    $money = fn ($value) => number_format($value, 0, ',', '.').' đ';
    $printedAmount = $receipt ? $row['paid'] : $row['remaining'];
    $visibleAmount = fn ($amount) => (float) $amount !== 0.0;
    $showTotals = $visibleAmount($row['total']) || $visibleAmount($row['paid']) || $visibleAmount($row['remaining']);
@endphp
<main>
    <div class="voucher-tools">
        <a href="{{ route('customer-debt.index', $query + ['debt_section' => 'vouchers']) }}#debt-vouchers">Về danh sách phiếu</a>
        <button type="button" onclick="window.print()">In / Lưu PDF</button>
    </div>
    <article class="voucher voucher-record{{ $receipt ? ' voucher-receipt' : '' }}">
        <header class="voucher-brand"><strong>{{ $company }}</strong></header>
        <div class="voucher-body">
            <h1>{{ \App\Services\CustomerDebtVouchers::TYPES[$row['type']] }}</h1>
            <div class="voucher-record-meta">
                <span><strong>Số phiếu</strong> {{ $row['number'] }}</span>
                <span><strong>Ngày lập</strong> {{ \Carbon\Carbon::parse($row['created_at'])->timezone(config('app.display_timezone'))->format('d/m/Y') }}</span>
            </div>
            <h2><span>Khách hàng</span> {{ $selected['name'] }}</h2>
            @if(!$receipt && empty($row['purchase_days']))
                <p>Chốt công nợ đến {{ \Carbon\Carbon::parse($row['through'])->format('d/m/Y') }}</p>
            @endif
            @if($showTotals || $visibleAmount($row['carry']) || $visibleAmount($row['new_amount']))
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
                    @if($showTotals)
                        <tfoot>
                            @foreach(['total' => 'Tổng phải thu', 'paid' => 'Đã thu theo phiếu', 'remaining' => $receipt ? 'Số tiền Thu' : 'Còn phải thu'] as $key => $label)
                                @if($visibleAmount($row[$key]))
                                    <tr><th scope="row">{{ $label }}</th><td>{{ $money($row[$key]) }}</td></tr>
                                @endif
                            @endforeach
                        </tfoot>
                    @endif
                </table>
            @endif
            <div class="voucher-at-end">
                @if($visibleAmount($printedAmount))
                    <div class="voucher-total"><strong>{{ $receipt ? 'Số tiền thực thu' : 'Số tiền đề nghị thanh toán' }}</strong><b>{{ $money($printedAmount) }}</b></div>
                    <p class="voucher-record-words"><strong>Bằng chữ</strong> {{ \App\Support\VietnameseMoneyWords::format($printedAmount) }}</p>
                @endif
                @if($row['method'])<p>{{ ['cash' => 'Tiền mặt', 'transfer' => 'Chuyển khoản'][$row['method']] }}</p>@endif
                @if($row['note'])<p>{{ $row['note'] }}</p>@endif
                <footer class="voucher-signatures">
                    @foreach(($receipt ? ['Người nộp tiền', 'Người thu tiền', 'Người lập phiếu'] : ['Người lập phiếu', 'Phòng Tài chính - Kế toán']) as $signature)
                        <div><strong>{{ $signature }}</strong><span>{{ $signature === 'Người lập phiếu' && $creatorName ? $creatorName : '(Ký, ghi rõ họ tên)' }}</span></div>
                    @endforeach
                </footer>
            </div>
        </div>
    </article>
</main>
</body>
</html>
