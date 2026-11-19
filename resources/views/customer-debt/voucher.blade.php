<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ $selected['name'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/customer-debt-voucher.css') }}?v={{ filemtime(public_path('css/customer-debt-voucher.css')) }}">
</head>
<body>
<main>
    <div class="voucher-tools">
        <a href="{{ request()->fullUrlWithQuery(['presentation' => null, 'voucher_dates' => null, 'voucher_all' => null, 'voucher_number' => null, 'voucher_method' => null]) }}#debt-detail">Về đối chiếu công nợ</a>
        @if($lines->isNotEmpty())<button type="button" onclick="window.print()">In / Lưu PDF</button>@endif
    </div>
    <details class="voucher-editor" @if($lines->isEmpty()) open @endif>
        <summary>Chọn ngày giao hàng cho {{ mb_strtolower($title) }}</summary>
        <form method="GET">
            @foreach(request()->only(['customer', 'system_customer', 'tab', 'recovery', 'window', 'report_from', 'report_to']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="presentation" value="{{ $presentation }}">
            <p>{{ $selected['name'] }} · {{ \Carbon\Carbon::parse($reportFrom)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($reportTo)->format('d/m/Y') }}</p>
            @if($errors->any())<p class="voucher-error" role="alert">{{ $errors->first() }}</p>@endif
            @if($deliveries->isNotEmpty())
                <p>Chọn ngày cần lập phiếu. Số tiền mỗi ngày là tiền hàng sau khi trừ tặng. Hãy đối chiếu với khoản cần {{ $receipt ? 'ghi trên phiếu thu' : 'đề nghị thanh toán' }} trước khi in.</p>
                <label class="voucher-select-all" for="voucher-select-all" hidden>
                    <input type="checkbox" id="voucher-select-all" name="voucher_all" value="1">
                    Chọn tất cả ngày
                </label>
                <div class="voucher-options" role="group" aria-label="Ngày giao hàng">
                    @foreach($deliveries->reverse() as $date => $amount)
                        <label><input type="checkbox" name="voucher_dates[]" value="{{ $date }}" @checked($lines->has($date))>
                            <span>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
                            <strong>{{ number_format($amount, 0, ',', '.') }} đ</strong>
                        </label>
                    @endforeach
                </div>
                @if($receipt)
                    <div class="voucher-fields">
                        <label>Số phiếu <input type="text" name="voucher_number" maxlength="50" value="{{ $number }}"></label>
                        <label>Hình thức thanh toán
                            <select name="voucher_method">
                                <option value="">Điền khi ký</option>
                                <option value="cash" @selected(request('voucher_method') === 'cash')>Tiền mặt</option>
                                <option value="transfer" @selected(request('voucher_method') === 'transfer')>Chuyển khoản</option>
                            </select>
                        </label>
                    </div>
                @endif
                <button type="submit">Xem trước bản in</button>
            @else
                <p>Kỳ đối chiếu này chưa có ngày giao hàng với số tiền dương.</p>
            @endif
        </form>
    </details>

    @if($lines->isNotEmpty())
        <article class="voucher">
            <header class="voucher-brand">
                <span class="voucher-mark" aria-hidden="true">HL</span>
                <strong>{{ $company }}</strong>
            </header>
            <div class="voucher-body">
                <h1>{{ $title }}</h1>
                <p class="voucher-date">Ngày lập {{ $issuedDate }}</p>
                <h2>{{ $receipt ? 'Người nộp tiền' : 'Kính gửi' }}<span> · </span>Khách hàng {{ $selected['name'] }}</h2>
                <p>{{ $receipt ? 'Lý do thu tiền hàng theo các ngày giao dưới đây.' : 'Kính đề nghị Quý khách thanh toán tiền hàng theo các ngày giao dưới đây.' }}</p>
                <table>
                    <thead><tr><th scope="col">STT</th><th scope="col">Ngày giao hàng</th><th scope="col">Thành tiền (đồng)</th></tr></thead>
                    <tbody>
                    @foreach($lines as $date => $amount)
                        <tr><td>{{ $loop->iteration }}</td><td>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td><td>{{ number_format($amount, 0, ',', '.') }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="voucher-at-end">
                    <div class="voucher-total"><strong>{{ $receipt ? 'Tổng số tiền thu' : 'Tổng đề nghị thanh toán' }}</strong><b>{{ number_format($total, 0, ',', '.') }} đồng</b></div>
                    <p>Bằng chữ · {{ \App\Support\VietnameseMoneyWords::format($total) }}</p>
                    @if($receipt)
                        <p>Số phiếu · {{ $number !== '' ? $number : '………………………………' }}</p>
                        <p>Hình thức thanh toán · {{ $method !== '' ? $method : '……………………………………………………' }}</p>
                        <footer class="voucher-signatures">
                            @foreach(['Người nộp tiền', 'Người thu tiền', 'Người lập phiếu'] as $signature)
                                <div><strong>{{ $signature }}</strong><span>(Ký, ghi rõ họ tên)</span></div>
                            @endforeach
                        </footer>
                    @else
                        <footer class="voucher-office">Phòng Tài chính - Kế toán</footer>
                    @endif
                </div>
            </div>
        </article>
    @endif
</main>
<script src="{{ asset('js/customer-debt-voucher.js') }}?v={{ filemtime(public_path('js/customer-debt-voucher.js')) }}"></script>
</body>
</html>
