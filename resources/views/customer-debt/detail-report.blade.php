<section id="debt-detail" class="debt-report mt-3" aria-labelledby="debt-report-title">
    <header class="debt-report-header">
        <div><h2 id="debt-report-title">{{ $selected['name'] }}</h2><div class="small text-muted">{{ $selected['code'] ?: 'Chưa có mã KH' }} · Sale: {{ $selected['sale_label'] }}@if($selected['customer_id']) · Tổng hợp mọi nguồn đã ghép @endif</div></div>
        <a class="btn btn-light btn-sm" href="{{ request()->fullUrlWithQuery(['customer'=>null,'system_customer'=>null,'report_from'=>null,'report_to'=>null,'window'=>null,'purchase_page'=>null,'payment_page'=>null,'detail_page'=>null]) }}">Đóng chi tiết</a>
    </header>
    <form method="GET" action="{{ route('customer-debt.index') }}#debt-detail" class="debt-report-filters">
        @foreach(['tab','from','to','customer','system_customer','search','sale','alert','recovery','sort','direction','debt_page'] as $queryKey)
            @if(request()->filled($queryKey))<input type="hidden" name="{{ $queryKey }}" value="{{ request($queryKey) }}">@endif
        @endforeach
        <input type="hidden" name="window" value="custom">
        <div><label for="debt-report-from">Từ ngày</label><input id="debt-report-from" name="report_from" type="date" value="{{ $reportFrom }}" class="form-control" required></div>
        <div><label for="debt-report-to">Đến ngày</label><input id="debt-report-to" name="report_to" type="date" value="{{ $reportTo }}" class="form-control" required></div>
        <button class="btn btn-primary" type="submit">Xem báo cáo</button>
        <a class="btn btn-sm {{ $reportMode === 'recent' ? 'btn-primary' : 'btn-light' }}" href="{{ request()->fullUrlWithQuery(['window'=>'recent','report_from'=>null,'report_to'=>null,'purchase_page'=>1,'payment_page'=>1,'detail_page'=>null]) }}#debt-detail">14 ngày</a>
        <a class="btn btn-sm {{ $reportMode === 'all' ? 'btn-primary' : 'btn-light' }}" href="{{ request()->fullUrlWithQuery(['window'=>'all','report_from'=>null,'report_to'=>null,'purchase_page'=>1,'payment_page'=>1,'detail_page'=>null]) }}#debt-detail">Toàn bộ</a>
    </form>
    <div class="debt-report-metrics">
        <div class="debt-report-metric"><span>Mua hàng</span><strong>{{ number_format($detailSales, 0, ',', '.') }} đ</strong><small>Tặng: {{ number_format($detailGifts, 0, ',', '.') }} đ</small></div>
        <div class="debt-report-metric"><span>Thanh toán</span><strong class="text-success">{{ number_format($detailPayments, 0, ',', '.') }} đ</strong><small>Trong kỳ báo cáo</small></div>
        <div class="debt-report-metric"><span>Công nợ</span><strong class="{{ $closing > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($closing, 0, ',', '.') }} đ</strong><small>{{ $closing < 0 ? 'Khách trả trước tại cuối kỳ' : 'Số dư cuối kỳ' }}</small></div>
        <div class="debt-report-metric"><span>Mua gần nhất</span><strong>{{ $reportLastPurchase ? \Carbon\Carbon::parse($reportLastPurchase)->format('d/m/Y') : 'Chưa có' }}</strong></div>
        <div class="debt-report-metric"><span>Thanh toán gần nhất</span><strong>{{ $reportLastPayment ? \Carbon\Carbon::parse($reportLastPayment)->format('d/m/Y') : 'Chưa có' }}</strong></div>
        <div class="debt-report-metric"><span>Trễ thanh toán</span><strong class="{{ $reportAlerts['overdue_days'] > 0 ? 'text-danger' : '' }}">{{ $reportAlerts['overdue_days'] }} ngày</strong>@if($reportAlerts['due_date'])<small>Hạn {{ \Carbon\Carbon::parse($reportAlerts['due_date'])->format('d/m/Y') }}</small>@endif</div>
        <div class="debt-report-metric"><span>Rủi ro công nợ</span><strong class="debt-report-risk {{ in_array($reportAlerts['risk'], ['high','very_high']) ? 'text-danger' : '' }}">{{ ['medium'=>'Trung bình','high'=>'Cao','very_high'=>'Rất cao'][$reportAlerts['risk']] ?? ($reportAlerts['balance'] > 0 ? 'Dưới 15 ngày' : 'Không còn nợ') }}</strong><small>Tuổi nợ {{ $reportAlerts['age_days'] }} ngày</small></div>
    </div>
    <div class="debt-report-reconciliation"><span>Dư đầu kỳ <strong>{{ number_format($opening, 0, ',', '.') }} đ</strong></span>@if($detailAdjustments != 0)<span>Điều chỉnh trong kỳ <strong>{{ number_format($detailAdjustments, 0, ',', '.') }} đ</strong></span>@endif<span>{{ \Carbon\Carbon::parse($reportFrom)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($reportTo)->format('d/m/Y') }} · Phát sinh theo kỳ; số dư, ngày giao dịch gần nhất và rủi ro tính đến cuối kỳ.</span></div>
    @if(!empty($snapshot['issues']))<div class="small text-warning px-3 pb-3">Cần đối chiếu các khoản thu chưa xác định khách hàng để bảo đảm công nợ đầy đủ.</div>@endif
    <div class="debt-report-columns">
        <article class="debt-report-ledger">
            <header><h3>Mua hàng</h3><span>{{ number_format($purchases->total(), 0, ',', '.') }} dòng</span></header>
            <div class="debt-report-scroll" tabindex="0" aria-label="Chi tiết mua hàng">
                <table class="table"><thead><tr><th>Ngày mua</th><th>Nội dung</th><th class="text-end">Tiền hàng</th><th class="text-end">Tặng</th><th class="text-end">Phải thu</th></tr></thead><tbody>
                @forelse($purchases as $entry)
                    <tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }}<small>Dòng {{ $entry['row'] }}</small></td><td>{{ $entry['note'] ?: 'Mua hàng' }}</td><td class="text-end">{{ number_format($entry['amount'], 0, ',', '.') }}</td><td class="text-end">{{ number_format($entry['gift'] ?? 0, 0, ',', '.') }}</td><td class="text-end">{{ number_format($entry['amount'] - ($entry['gift'] ?? 0), 0, ',', '.') }}</td></tr>
                @empty<tr><td colspan="5" class="text-center text-muted py-4">Không có giao dịch mua hàng trong kỳ.</td></tr>@endforelse
                </tbody><tfoot><tr><th colspan="2">Tổng trong kỳ</th><th class="text-end">{{ number_format($detailSales, 0, ',', '.') }}</th><th class="text-end">{{ number_format($detailGifts, 0, ',', '.') }}</th><th class="text-end">{{ number_format($detailSales - $detailGifts, 0, ',', '.') }}</th></tr></tfoot></table>
            </div>
            <footer>{{ $purchases->links() }}</footer>
        </article>
        <article class="debt-report-ledger">
            <header><h3>Thanh toán</h3><span>{{ number_format($payments->total(), 0, ',', '.') }} khoản</span></header>
            <div class="debt-report-scroll" tabindex="0" aria-label="Chi tiết thanh toán">
                <table class="table"><thead><tr><th>Ngày thanh toán</th><th>Nội dung</th><th class="text-end">Số tiền</th></tr></thead><tbody>
                @forelse($payments as $entry)
                    <tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }}<small>Dòng {{ $entry['row'] }}</small></td><td>{{ $entry['note'] ?: 'Thanh toán' }}</td><td class="text-end text-success">{{ number_format($entry['amount'], 0, ',', '.') }} đ</td></tr>
                @empty<tr><td colspan="3" class="text-center text-muted py-4">Không có khoản thanh toán trong kỳ.</td></tr>@endforelse
                </tbody><tfoot><tr><th colspan="2">Tổng trong kỳ</th><th class="text-end">{{ number_format($detailPayments, 0, ',', '.') }} đ</th></tr></tfoot></table>
            </div>
            <footer>{{ $payments->links() }}</footer>
        </article>
    </div>
    @if($balanceAdjustments->total())
    <details class="debt-adjustment-history" open>
        <summary>Lịch sử điều chỉnh trong kỳ · {{ $balanceAdjustments->total() }} lần</summary>
        <div class="table-responsive"><table class="table"><thead><tr><th>Thời gian / Người sửa</th><th class="text-end">Số cũ</th><th class="text-end">Số mới</th><th class="text-end">Chênh lệch</th><th>Lý do</th></tr></thead><tbody>
        @foreach($balanceAdjustments as $entry)
        <tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($entry['created_at'])->format('d/m/Y H:i') }}<br><small>{{ $entry['actor_name'] }}</small></td><td class="text-end text-nowrap">{{ number_format($entry['previous_balance'], 0, ',', '.') }} đ</td><td class="text-end text-nowrap">{{ number_format($entry['new_balance'], 0, ',', '.') }} đ</td><td class="text-end text-nowrap">{{ number_format($entry['amount'], 0, ',', '.') }} đ</td><td>{{ $entry['note'] }}</td></tr>
        @endforeach
        </tbody></table></div>{{ $balanceAdjustments->links() }}
    </details>
    @endif
</section>
