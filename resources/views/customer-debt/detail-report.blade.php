<section id="debt-detail" class="debt-report mt-3" aria-labelledby="debt-report-title">
    @php
        $sheetSources = collect($selected['source_keys'] ?? [$selected['key']])
            ->map(fn ($key) => $groups[$key] ?? null)
            ->filter(fn ($source) => $source && empty($source['manual_only']));
        $singleSheetSource = $sheetSources->count() === 1 ? $sheetSources->first() : null;
    @endphp
    <header class="debt-report-header">
        <div>
            <div class="small text-muted debt-report-eyebrow">ĐỐI CHIẾU CÔNG NỢ</div>
            <h2 id="debt-report-title">{{ $selected['name'] }}</h2>
        </div>
        <div class="debt-detail-header-actions">
            @if(!isset($debtDetailAction) && $singleSheetSource)
                @include('customer-debt.mapping-actions', ['source' => $singleSheetSource, 'mappingToolbar' => true])
            @endif
            @if($selected['customer_id'])
                <a class="btn btn-light btn-sm" href="{{ route('customers.show', ['customer' => $selected['customer_id'], 'tab' => 'debt']) }}">Hồ sơ khách hàng</a>
            @endif
        </div>
    </header>
    <div class="debt-detail-context">
        <div class="debt-detail-context-row">
            <div><strong>Hệ thống:</strong> {{ $selected['customer_id'] ? $selected['name'].' · '.($selected['code'] ?: 'Chưa có mã KH') : 'Chưa ghép khách hệ thống' }}</div>
            <div class="small text-muted">Sale: {{ $selected['sale_label'] }}@if($selected['customer_id']) · Tổng hợp mọi nguồn đã ghép @endif</div>
        </div>
        @forelse($sheetSources as $source)
            <div class="mt-2">
                <strong>Google Sheet:</strong> {{ $source['name'] }} · {{ $source['code'] ?: 'Chưa có mã KH' }}
                @if($source['customer_id'] && $source['code'] && $selected['code'] && strcasecmp($source['code'], trim($selected['code'])) !== 0)
                    <span class="small text-danger">Mã khách khác hệ thống</span>
                @endif
                @if(!isset($debtDetailAction) && !$singleSheetSource)
                    @include('customer-debt.mapping-actions')
                @endif
            </div>
        @empty
            <div class="mt-2"><strong>Google Sheet:</strong> Chưa có nguồn đã ghép</div>
        @endforelse
    </div>
    <form method="GET" action="{{ $debtDetailAction ?? route('customer-debt.index') }}#debt-detail" class="debt-report-filters">
        @foreach(['tab','from','to','customer','system_customer','search','sale','alert','recovery','sort','direction','debt_page','balance','min_debt','per_page','scope','range','page','payment_mode','payment_class','reminder'] as $queryKey)
            @if(request()->filled($queryKey))<input type="hidden" name="{{ $queryKey }}" value="{{ request($queryKey) }}">@endif
        @endforeach
        <input type="hidden" name="window" value="custom">
        <div><label for="debt-report-from">Từ ngày</label><input id="debt-report-from" name="report_from" type="date" value="{{ $reportFrom }}" class="form-control" required></div>
        <div><label for="debt-report-to">Đến ngày</label><input id="debt-report-to" name="report_to" type="date" value="{{ $reportTo }}" class="form-control" required></div>
        <button class="btn btn-primary" type="submit">Xem báo cáo</button>
        <a class="btn btn-sm {{ $reportMode === 'recent' ? 'btn-primary' : 'btn-light' }}" href="{{ request()->fullUrlWithQuery(['window'=>'recent','report_from'=>null,'report_to'=>null,'purchase_page'=>1,'payment_page'=>1,'detail_page'=>null]) }}#debt-detail">14 ngày</a>
        <a class="btn btn-sm {{ $reportMode === 'all' ? 'btn-primary' : 'btn-light' }}" href="{{ request()->fullUrlWithQuery(['window'=>'all','report_from'=>null,'report_to'=>null,'purchase_page'=>1,'payment_page'=>1,'detail_page'=>null]) }}#debt-detail">Toàn bộ</a>
        @if((!isset($debtDetailAction) || ($saleDebtDetail ?? false)) && $purchases->total() > 0)
            @foreach(['payment-request' => 'Tạo đề nghị thanh toán', 'receipt' => 'Tạo phiếu thu'] as $type => $label)
                <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener"
                    href="{{ request()->fullUrlWithQuery(['presentation' => $type, 'purchase_page' => null, 'payment_page' => null, 'detail_page' => null]) }}">{{ $label }}</a>
            @endforeach
        @endif
        <div class="debt-detail-filter-actions">
            <a class="btn btn-primary btn-sm" target="_blank" rel="noopener"
                href="{{ request()->fullUrlWithQuery(['presentation' => 'customer', 'purchase_page' => null, 'payment_page' => null, 'detail_page' => null]) }}">Bản gửi khách</a>
            <a class="btn btn-light btn-sm"
                href="{{ request()->fullUrlWithQuery(['customer' => null, 'system_customer' => null, 'report_from' => null, 'report_to' => null, 'window' => null, 'purchase_page' => null, 'payment_page' => null, 'detail_page' => null, 'adjustment_page' => null]) }}">{{ isset($debtDetailAction) && !($saleDebtDetail ?? false) ? 'Đóng chi tiết' : 'Về danh sách công nợ' }}</a>
        </div>
    </form>
    <div class="debt-report-metrics">
        <div class="debt-report-metric"><span>Mua hàng</span><strong>{{ number_format($detailSales, 0, ',', '.') }} đ</strong></div>
        <div class="debt-report-metric"><span>Thanh toán & hàng tặng</span><strong class="text-success">{{ number_format($detailSettlementTotal, 0, ',', '.') }} đ</strong><small>Tiền thu {{ number_format($detailPayments, 0, ',', '.') }} đ · Tặng {{ number_format($detailGifts, 0, ',', '.') }} đ</small></div>
        <div class="debt-report-metric"><span>Công nợ</span><strong class="{{ $closing > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($closing, 0, ',', '.') }} đ</strong><small>{{ $closing < 0 ? 'Khách trả trước tại cuối kỳ' : 'Số dư cuối kỳ' }}</small></div>
        <div class="debt-report-metric"><span>Mua gần nhất</span><strong>{{ $reportLastPurchase ? \Carbon\Carbon::parse($reportLastPurchase)->format('d/m/Y') : 'Chưa có' }}</strong>@if($reportLastPurchase) @php($daysFromToday = (int) \Carbon\CarbonImmutable::parse($reportLastPurchase)->startOfDay()->diffInDays(\Carbon\CarbonImmutable::today(), false))<small title="Tính so với hôm nay {{ \Carbon\CarbonImmutable::today()->format('d/m/Y') }}">{{ $daysFromToday === 0 ? 'Hôm nay · 0 ngày' : ($daysFromToday > 0 ? $daysFromToday.' ngày trước' : 'Sau hôm nay '.abs($daysFromToday).' ngày') }}</small>@endif</div>
        <div class="debt-report-metric"><span>Thanh toán gần nhất</span><strong>{{ $reportLastPayment ? \Carbon\Carbon::parse($reportLastPayment)->format('d/m/Y') : 'Chưa có' }}</strong>@if($reportLastPayment) @php($daysFromToday = (int) \Carbon\CarbonImmutable::parse($reportLastPayment)->startOfDay()->diffInDays(\Carbon\CarbonImmutable::today(), false))<small title="Tính so với hôm nay {{ \Carbon\CarbonImmutable::today()->format('d/m/Y') }}">{{ $daysFromToday === 0 ? 'Hôm nay · 0 ngày' : ($daysFromToday > 0 ? $daysFromToday.' ngày trước' : 'Sau hôm nay '.abs($daysFromToday).' ngày') }}</small>@endif</div>
        <div class="debt-report-metric"><span>Trễ thanh toán</span><strong class="{{ $reportAlerts['overdue_days'] > 0 ? 'text-danger' : '' }}">{{ $reportAlerts['overdue_days'] }} ngày</strong>@if($reportAlerts['due_date'])<small>Hạn {{ \Carbon\Carbon::parse($reportAlerts['due_date'])->format('d/m/Y') }}</small>@endif</div>
        <div class="debt-report-metric"><span>Rủi ro công nợ</span><strong class="debt-report-risk {{ in_array($reportAlerts['risk'], ['high','very_high']) ? 'text-danger' : '' }}">{{ ['medium'=>'Trung bình','high'=>'Cao','very_high'=>'Rất cao'][$reportAlerts['risk']] ?? ($reportAlerts['balance'] > 0 ? 'Dưới 15 ngày' : 'Không còn nợ') }}</strong><small>Tuổi nợ {{ $reportAlerts['age_days'] }} ngày</small></div>
    </div>
    <div class="debt-report-reconciliation"><span>Dư đầu kỳ <strong>{{ number_format($opening, 0, ',', '.') }} đ</strong></span>@if($detailAdjustments != 0)<span>Điều chỉnh trong kỳ <strong>{{ number_format($detailAdjustments, 0, ',', '.') }} đ</strong></span>@endif<span>{{ \Carbon\Carbon::parse($reportFrom)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($reportTo)->format('d/m/Y') }} · Phát sinh theo kỳ; số dư, ngày giao dịch gần nhất và rủi ro tính đến cuối kỳ.</span></div>
    @php($showDetailPolicy = !isset($debtDetailAction) || ($saleDebtDetail ?? false))
    <div class="debt-detail-notes{{ ($showDetailPolicy || ($canManageAllocation ?? false)) ? ' debt-detail-notes-with-policy' : '' }}">
        <div>
            <div class="small text-muted mb-2">Nguồn @if($canLoad ?? false)<a href="https://docs.google.com/spreadsheets/d/{{ $snapshot['spreadsheet_id'] ?? config('services.google_sheets.debt_spreadsheet_id') }}/edit?gid=438108508" target="_blank" rel="noopener">nhatkybanhang</a>@else nhatkybanhang @endif trong kỳ: Thanh toán <strong>{{ number_format($detailSalesPayments, 0, ',', '.') }} đ</strong> · Tặng <strong>{{ number_format($detailGifts, 0, ',', '.') }} đ</strong>. Tặng được trừ riêng khỏi số phải thu.</div>
            @if(!empty($snapshot['issues']) || !empty($snapshot['has_unidentified_payments']))<div class="small text-warning mb-2">Cần đối chiếu các khoản thu chưa xác định khách hàng để bảo đảm công nợ đầy đủ.</div>@endif
            @if($allocation ?? null)
                <div class="small">
                    @if($changeDate ?? null)
                        <strong>{{ \App\Services\CustomerDebtPolicyService::SETTLEMENT_METHODS[$currentSettlementMethod] }} từ {{ \Carbon\Carbon::parse($changeDate)->format('d/m/Y') }}</strong>
                        <div>Đơn trước ngày chuyển giữ cách đối chiếu của từng kỳ.</div>
                        @php($openLines = collect($allocation['lines']))
                        <div class="mt-1">Nợ cũ còn phải thu <strong>{{ number_format($openLines->where('legacy', true)->sum('remaining'), 0, ',', '.') }} đ</strong></div>
                        <div>Đơn từ ngày chuyển còn phải thu <strong>{{ number_format($openLines->where('legacy', false)->sum('remaining'), 0, ',', '.') }} đ</strong></div>
                        <div>Tiền đã thu chờ ghép <strong>{{ number_format(collect($allocation['unmatched'])->where('amount', '>', 0)->sum('amount'), 0, ',', '.') }} đ</strong></div>
                        <div class="text-muted">Các số trên tính đến cuối kỳ đối chiếu và thể hiện phân bổ theo đơn. Số dư công nợ vẫn gồm thanh toán và điều chỉnh đã ghi nhận.</div>
                    @else
                        <strong>Hình thức thanh toán · {{ \App\Services\CustomerDebtPolicyService::SETTLEMENT_METHODS[$settlementMethod] }}</strong>
                    @endif
                    @if($settlementMethod !== 'bill' && empty($changeDate))
                        <div>Khoản theo đợt được phân bổ từ ngày giao cũ nhất. Kết quả tính đến cuối kỳ đối chiếu.</div>
                    @endif
                    @if($allocation['paidThrough'])
                        <div>Đã trả đủ liên tục đến <strong>{{ \Carbon\Carbon::parse($allocation['paidThrough']['date'])->format('d/m/Y') }}</strong>
                            @if($allocation['paidThrough']['code']) · Đơn {{ $allocation['paidThrough']['code'] }} @else · Dòng Sheet {{ $allocation['paidThrough']['row'] }} @endif
                        </div>
                    @endif
                    @if($allocation['firstOpen'])
                        <div>
                            Đơn hoặc dòng mua còn nợ đầu tiên <strong>{{ \Carbon\Carbon::parse($allocation['firstOpen']['date'])->format('d/m/Y') }}</strong>
                            @if($allocation['firstOpen']['code']) · Đơn {{ $allocation['firstOpen']['code'] }} @else · Dòng Sheet {{ $allocation['firstOpen']['row'] }} @endif
                            · Còn <strong>{{ number_format($allocation['firstOpen']['remaining'], 0, ',', '.') }} đ</strong>
                        </div>
                    @endif
                    @if($allocation['unmatched'])
                        <div class="text-danger">{{ count($allocation['unmatched']) }} khoản thu còn cần so khớp. Mở bảng Thanh toán để xem lý do và ghép khoản thu.</div>
                    @endif
                    @if($allocation['credit'] > 0)
                        <div>Tiền theo đợt chưa cần phân bổ <strong>{{ number_format($allocation['credit'], 0, ',', '.') }} đ</strong></div>
                    @elseif($allocation['credit'] < 0)
                        <div class="text-warning">Khoản thu âm theo đợt cần đối chiếu <strong>{{ number_format(abs($allocation['credit']), 0, ',', '.') }} đ</strong></div>
                    @endif
                    @if($settlementMethod !== 'batch' || !empty($changeDate))
                        @if(!empty($changeDate))
                            <div class="text-muted">Ghi <strong>Nợ cũ</strong> cho khoản trả đơn trước kỳ hiện tại chưa có mã bill.
                                @if($currentSettlementMethod !== 'bill') Khoản trả theo đợt trong kỳ hiện tại cấn từ đơn cũ nhất của kỳ đó. @endif
                            </div>
                        @endif
                        <div class="text-muted">Khi thu theo Bill, ghi <strong>Bill 13/11/2026 | Mã đơn: DH123</strong> trong nội dung thanh toán. Khoản ghi trả Bill nhưng thiếu ngày hoặc mã khớp được giữ chờ đối chiếu.</div>
                    @endif
                </div>
            @endif
        </div>
        @if($showDetailPolicy || ($canManageAllocation ?? false))
            <div class="d-grid gap-3">
                @if($showDetailPolicy)
                    @include('customer-debt.detail-policy')
                @endif
                @if($canManageAllocation ?? false)
                    @include('customer-debt.settlement-transition')
                @endif
            </div>
        @endif
    </div>
    @if($canManageAllocation ?? false)
        @include('customer-debt.payment-allocation-tools')
    @endif
    <div class="debt-report-columns{{ !isset($debtDetailAction) && ($reminderHistory ?? null) ? ' debt-report-columns-with-history' : '' }}">
        <article class="debt-report-ledger">
            <header>
                <h3>Mua hàng</h3>
                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                    <span class="small text-muted">{{ number_format($purchases->total(), 0, ',', '.') }} dòng</span>
                    <select class="form-select form-select-sm w-auto" aria-label="Số dòng mua hàng mỗi trang"
                        onchange="window.location.assign(this.value)">
                        @foreach($purchasePageSizes as $pageSize)
                            <option value="{{ request()->fullUrlWithQuery(['purchase_per_page' => $pageSize, 'purchase_page' => 1]) }}#debt-detail"
                                @selected($purchases->perPage() === $pageSize)>{{ $pageSize }} dòng/trang</option>
                        @endforeach
                    </select>
                </div>
            </header>
            <div class="debt-report-scroll" tabindex="0" aria-label="Chi tiết mua hàng">
                <table class="table debt-purchase-table"><colgroup><col class="debt-ledger-date"><col><col span="4" class="debt-ledger-money"></colgroup><thead><tr><th>Ngày mua</th><th>Nội dung</th><th class="text-end">Tiền hàng</th><th class="text-end">Phải thu</th><th class="text-end">Đã phân bổ</th><th class="text-end">Còn phải thu</th></tr></thead><tbody>
                @forelse($purchases as $entry)
                    <tr><td class="debt-ledger-date-cell"><time datetime="{{ $entry['date'] }}">{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }}</time><small>{{ $entry['source_sheet'] ?? ($entry['type'] === 'sale' ? 'nhatkybanhang' : 'Dongtienhoatdong') }} · Dòng {{ $entry['row'] }}</small></td><td>{{ $entry['note'] ?: 'Mua hàng' }} @if(!empty($entry['allocation']['code']))<small>Đơn {{ $entry['allocation']['code'] }}</small>@endif
                        @if(!empty($changeDate) && isset($entry['allocation']))<small>{{ $entry['allocation']['legacy'] ? 'Nợ cũ' : \App\Services\CustomerDebtPolicyService::SETTLEMENT_METHODS[$currentSettlementMethod] }}</small>@endif</td><td class="text-end">{{ number_format($entry['amount'], 0, ',', '.') }}</td><td class="text-end">{{ number_format($entry['amount'] - ($entry['gift'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end text-success">{{ isset($entry['allocation']) ? number_format($entry['allocation']['paid'], 0, ',', '.') : '—' }}</td>
                        <td class="text-end {{ ($entry['allocation']['remaining'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ isset($entry['allocation']) ? number_format($entry['allocation']['remaining'], 0, ',', '.') : '—' }}</td></tr>
                @empty<tr><td colspan="6" class="text-center text-muted py-4">Không có giao dịch mua hàng trong kỳ.</td></tr>@endforelse
                </tbody><tfoot><tr><th colspan="2">Tổng trong kỳ</th><th class="text-end">{{ number_format($detailSales, 0, ',', '.') }}</th><th class="text-end">{{ number_format($detailSales - $detailGifts, 0, ',', '.') }}</th><th colspan="2"></th></tr></tfoot></table>
            </div>
            <footer>
                <div class="d-flex flex-wrap justify-content-between gap-2 small text-muted{{ $purchases->hasPages() ? ' mb-2' : '' }}">
                    <span>Đang xem {{ number_format($purchases->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($purchases->lastItem() ?? 0, 0, ',', '.') }} / {{ number_format($purchases->total(), 0, ',', '.') }} dòng mua hàng</span>
                    <span>Trang {{ $purchases->currentPage() }} / {{ $purchases->lastPage() }}</span>
                </div>
                {{ $purchases->links() }}
            </footer>
        </article>
        <article class="debt-report-ledger">
            <header><h3>Thanh toán & hàng tặng</h3><span>{{ number_format($payments->total(), 0, ',', '.') }} khoản</span></header>
            <div class="debt-report-scroll" tabindex="0" aria-label="Chi tiết thanh toán và hàng tặng">
                <table class="table debt-settlement-table"><colgroup><col class="debt-ledger-date"><col><col class="debt-ledger-money"></colgroup><thead><tr><th>Ngày giao dịch</th><th>Nội dung</th><th class="text-end">Số tiền</th></tr></thead><tbody>
                @forelse($payments as $entry)
                    <tr><td class="debt-ledger-date-cell"><time datetime="{{ $entry['date'] }}">{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }}</time><small>{{ $entry['source_sheet'] ?? ($entry['type'] === 'sale' ? 'nhatkybanhang' : 'Dongtienhoatdong') }} · Dòng {{ $entry['row'] }}</small></td><td>@if($entry['type'] === 'gift')
                        <span class="badge bg-light text-dark">Hàng tặng / giảm trừ</span>
                        @if($entry['note'])<div class="small text-muted">{{ $entry['note'] }}</div>@endif
                    @else
                        {{ $entry['note'] ?: 'Thanh toán' }}
                    @endif
                    @if(!empty($entry['allocation_automatic']) || !empty($entry['allocation_manual']))
                        <ul class="small list-unstyled mb-1">
                            @foreach($entry['allocation_automatic'] as $target => $amount)
                                <li>Tự ghép · {{ $allocation['lines'][$target]['code'] ?: $target }} · {{ number_format($amount, 0, ',', '.') }} đ</li>
                            @endforeach
                            @foreach($entry['allocation_manual'] as $item)
                                <li>Ghép tay · {{ str_starts_with($item['target'], 'legacy') ? 'Nợ cũ' : ($allocation['lines'][$item['target']]['code'] ?: $item['target']) }} · {{ number_format($item['amount'], 0, ',', '.') }} đ</li>
                            @endforeach
                        </ul>
                    @endif
                    @if(!empty($entry['allocation_issue']))<div class="small text-danger">{{ $entry['allocation_issue']['reason'] }} · {{ number_format($entry['allocation_issue']['amount'], 0, ',', '.') }} đ</div>@endif
                    @if(!empty($entry['allocation_invalid']))<div class="small text-danger">{{ $entry['allocation_invalid'] }}</div>@endif
                    @if(($canManageAllocation ?? false) && (!empty($entry['allocation_saved']) || ($entry['allocation_issue']['amount'] ?? 0) > 0))
                        <a class="btn btn-sm btn-outline-primary mt-1" href="{{ request()->fullUrlWithQuery(['allocate' => $entry['allocation_key'], 'transition_preview' => null, 'transition_method' => null]) }}#debt-manual-allocation">{{ $entry['allocation_saved'] ? 'Sửa ghép' : 'Ghép khoản thu' }}</a>
                    @endif
                    </td><td class="text-end text-success">{{ number_format($entry['amount'], 0, ',', '.') }} đ</td></tr>
                @empty<tr><td colspan="3" class="text-center text-muted py-4">Không có khoản thanh toán hoặc hàng tặng trong kỳ.</td></tr>@endforelse
                </tbody><tfoot><tr><th colspan="2">Tổng thanh toán & hàng tặng</th><th class="text-end">{{ number_format($detailSettlementTotal, 0, ',', '.') }} đ</th></tr></tfoot></table>
            </div>
            <footer><div class="small text-muted mb-2">Đang xem {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} / {{ number_format($payments->total(), 0, ',', '.') }} khoản thu và hàng tặng</div>{{ $payments->links() }}</footer>
        </article>
        @if(!isset($debtDetailAction) && ($reminderHistory ?? null))
            @include('customer-debt.detail-reminders')
        @endif
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
