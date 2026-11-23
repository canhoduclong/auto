<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biên bản xác nhận công nợ · {{ $statement['number'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/customer-debt-voucher.css') }}?v={{ filemtime(public_path('css/customer-debt-voucher.css')) }}">
</head>
<body>
<main>
    <div class="voucher-tools">
        <a href="{{ route('customer-debt.index', ['tab' => 'all', 'search' => $statement['customer_code'] ?: $statement['customer_name'], 'recovery' => 'all']) }}">Về quản lý công nợ</a>
        <button type="button" onclick="window.print()">In / Lưu PDF</button>
    </div>
    <article class="voucher voucher-receipt cycle-statement">
        <header class="voucher-brand"><strong>{{ $statement['company'] }}</strong></header>
        <div class="voucher-body">
            <h1>Biên bản đối chiếu và xác nhận công nợ</h1>
            <p class="voucher-date">Số {{ $statement['number'] }} · Ngày lập {{ \Carbon\Carbon::parse($statement['closed_at'])->timezone(config('app.display_timezone'))->format('d/m/Y') }}</p>
            <p><strong>Bên bán</strong> · {{ $statement['company'] }}</p>
            <p><strong>Bên mua</strong> · {{ $statement['customer_name'] }} @if($statement['customer_code']) · Mã KH {{ $statement['customer_code'] }} @endif</p>
            @if(!empty($statement['name']))
                <p><strong>Tên kỳ công nợ</strong> · {{ $statement['name'] }}</p>
            @endif
            <p><strong>Kỳ đối chiếu</strong> · {{ \Carbon\Carbon::parse($statement['from'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($statement['to'])->format('d/m/Y') }}</p>
            <table>
                <thead><tr><th scope="col">Nội dung</th><th scope="col">Số tiền</th></tr></thead>
                <tbody>
                    @include('customer-debt.cycle-totals', ['cycle' => $statement])
                </tbody>
            </table>
            @if(!empty($statement['daily']))
                <h2>Chi tiết phát sinh theo ngày</h2>
                <table class="cycle-daily">
                    <thead><tr><th scope="col">Ngày</th><th scope="col">Tiền hàng</th><th scope="col">Giảm trừ</th><th scope="col">Điều chỉnh</th><th scope="col">Đã thu</th></tr></thead>
                    <tbody>
                        @foreach($statement['daily'] as $date => $day)
                            <tr>
                                <th scope="row">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</th>
                                @foreach(['sales', 'gifts', 'adjustments', 'payments'] as $field)
                                    <td>{{ number_format($day[$field], 0, ',', '.') }} đ</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            @if(!empty($statement['bills']))
                <h2>Bill được gán vào kỳ</h2>
                <table>
                    <thead><tr><th scope="col">Ngày giao</th><th scope="col">Bill / dòng Sheet</th><th scope="col">Phải thu</th></tr></thead>
                    <tbody>
                        @foreach($statement['bills'] as $bill)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($bill['date'])->format('d/m/Y') }}</td>
                                <td>{{ $bill['label'] }}</td>
                                <td>{{ number_format($bill['sales'] - $bill['gifts'], 0, ',', '.') }} đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="voucher-at-end">
                <div class="voucher-total">
                    <strong>{{ $statement['closing'] < 0 ? 'Khách trả trước cuối kỳ' : 'Tổng công nợ · Còn phải thu cuối kỳ' }}</strong>
                    <b>{{ number_format(abs($statement['closing']), 0, ',', '.') }} đồng</b>
                </div>
                <p>Bằng chữ · {{ \App\Support\VietnameseMoneyWords::format(abs($statement['closing'])) }}</p>
                @if($statement['closing'] > 0)
                    <p>Hạn thanh toán theo kỳ · {{ \Carbon\Carbon::parse($statement['due'])->format('d/m/Y') }}</p>
                @endif
                <p>Hai bên đối chiếu và xác nhận các số liệu công nợ nêu trên đến hết ngày {{ \Carbon\Carbon::parse($statement['to'])->format('d/m/Y') }}.</p>
                <footer class="voucher-signatures">
                    <div><strong>Đại diện bên bán</strong><span>(Ký, ghi rõ họ tên và chức vụ)</span></div>
                    <div><strong>Đại diện bên mua</strong><span>(Ký, ghi rõ họ tên và chức vụ)</span></div>
                </footer>
            </div>
        </div>
    </article>
</main>
</body>
</html>
