@php
    $periodsForTab = array_filter($paymentPeriods ?? [], fn ($period) => $period['method'] === $paymentTabMethod);
    $formName = \App\Services\CustomerDebtPolicyService::PAYMENT_FORMS[$paymentTabMethod];
    $periodToday = today()->toDateString();
@endphp
<section class="debt-cycle-card mb-3" id="debt-payment-periods" aria-labelledby="debt-payment-periods-title">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h3 class="h6 mb-0" id="debt-payment-periods-title">Giai đoạn {{ mb_strtolower($formName) }}</h3>
        <a class="btn btn-sm btn-outline-primary"
            href="{{ request()->fullUrlWithQuery(['debt_section' => null, 'transition_preview' => null, 'transition_end' => null, 'transition_method' => $paymentTabMethod]) }}#debt-settlement-transition">Cấu hình giai đoạn</a>
    </div>
    @if($periodsForTab)
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Từ ngày</th><th>Đến ngày</th><th>Trạng thái</th><th>Đối chiếu</th></tr></thead>
                <tbody>
                    @foreach(array_reverse($periodsForTab) as $period)
                        <tr>
                            <td>{{ $period['from'] ? \Carbon\Carbon::parse($period['from'])->format('d/m/Y') : 'Từ đầu lịch sử' }}</td>
                            <td>{{ $period['to'] ? \Carbon\Carbon::parse($period['to'])->format('d/m/Y') : 'Chưa có ngày kết thúc' }}</td>
                            <td>{{ $period['from'] && $period['from'] > $periodToday ? 'Sắp áp dụng' : ($period['to'] && $period['to'] < $periodToday ? 'Đã kết thúc' : 'Đang áp dụng') }}</td>
                            <td>
                                @if($period['from'] && $period['from'] <= $periodToday)
                                    <a href="{{ request()->fullUrlWithQuery([
                                        'debt_section' => null, 'window' => 'custom', 'report_from' => $period['from'],
                                        'report_to' => min($period['to'] ?? $periodToday, $periodToday), 'purchase_page' => 1, 'payment_page' => 1,
                                    ]) }}#debt-detail">Xem đối chiếu</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="small text-muted mb-0">Chưa cấu hình giai đoạn này.</p>
    @endif
</section>
