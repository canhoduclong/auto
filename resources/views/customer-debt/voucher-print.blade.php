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
@endphp
<main>
    <div class="voucher-tools">
        <a href="{{ route('customer-debt.index', $query + ['debt_section' => 'vouchers']) }}#debt-vouchers">Về danh sách phiếu</a>
        <button type="button" onclick="window.print()">In / Lưu PDF</button>
    </div>
    <article class="voucher{{ $receipt ? ' voucher-receipt' : '' }}">
        <header class="voucher-brand"><strong>{{ $company }}</strong></header>
        <div class="voucher-body">
            <h1>{{ \App\Services\CustomerDebtVouchers::TYPES[$row['type']] }}</h1>
            <p class="voucher-date">Số {{ $row['number'] }} · Ngày lập {{ \Carbon\Carbon::parse($row['created_at'])->timezone(config('app.display_timezone'))->format('d/m/Y') }}</p>
            <h2>Khách hàng · {{ $selected['name'] }}</h2>
            <p>Chốt công nợ đến {{ \Carbon\Carbon::parse($row['through'])->format('d/m/Y') }}</p>
            <table>
                <thead><tr><th scope="col">Nội dung</th><th scope="col">Số tiền</th></tr></thead>
                <tbody>
                    <tr><td>Nợ chuyển từ phiếu trước</td><td>{{ $money($row['carry']) }}</td></tr>
                    @forelse($row['purchase_days'] ?? [] as $date => $amount)
                        <tr><td>Mua hàng ngày {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td><td>{{ $money($amount) }}</td></tr>
                    @empty
                        <tr><td>Công nợ đưa vào phiếu</td><td>{{ $money($row['new_amount']) }}</td></tr>
                    @endforelse
                    <tr><td>Tổng phải thu</td><td>{{ $money($row['total']) }}</td></tr>
                    <tr><td>Đã thu theo phiếu</td><td>{{ $money($row['paid']) }}</td></tr>
                    <tr><td>Còn phải thu</td><td>{{ $money($row['remaining']) }}</td></tr>
                </tbody>
            </table>
            <div class="voucher-at-end">
                <div class="voucher-total"><strong>{{ $receipt ? 'Số tiền thực thu' : 'Số tiền đề nghị thanh toán' }}</strong><b>{{ $money($printedAmount) }}</b></div>
                <p>Bằng chữ · {{ \App\Support\VietnameseMoneyWords::format($printedAmount) }}</p>
                @if($row['method'])<p>{{ ['cash' => 'Tiền mặt', 'transfer' => 'Chuyển khoản'][$row['method']] }}</p>@endif
                @if($row['note'])<p>{{ $row['note'] }}</p>@endif
                <footer class="voucher-signatures">
                    @foreach(($receipt ? ['Người nộp tiền', 'Người thu tiền', 'Người lập phiếu'] : ['Người lập phiếu', 'Phòng Tài chính - Kế toán']) as $signature)
                        <div><strong>{{ $signature }}</strong><span>(Ký, ghi rõ họ tên)</span></div>
                    @endforeach
                </footer>
            </div>
        </div>
    </article>
</main>
</body>
</html>
