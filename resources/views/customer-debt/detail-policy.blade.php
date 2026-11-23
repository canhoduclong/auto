<aside class="debt-detail-policy" aria-label="Tiêu chí công nợ">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="h6 mb-0">{{ $detailPolicyTitle ?? 'Tiêu chí công nợ' }}</h3>
        @if($detailPolicyAction)
            <button type="button" class="btn btn-outline-primary btn-sm debt-policy-button"
                data-policy-action="{{ $detailPolicyAction }}" data-key="{{ $detailPolicyKey }}" data-name="{{ $selected['name'] }}"
                @include('customer-debt.policy-cycle-values', ['policy' => $selected['policy']])
                data-fixed="{{ $selected['policy']['fixed_debt'] }}" data-limit="{{ $selected['policy']['credit_limit'] }}"
                data-threshold-days="{{ $selected['policy']['threshold_days'] }}" data-version="{{ $selected['policy']['edit_version'] }}"
                @disabled($selected['policy']['staff_edit_locked'] && !auth()->user()->canManageCustomerDebt())>{{ $detailPolicyButtonLabel ?? 'Sửa tiêu chí' }}</button>
        @endif
    </div>
    <dl class="debt-detail-policy-values">
        <div><dt>Hình thức thanh toán</dt><dd>{{ \App\Services\CustomerDebtPolicyService::PAYMENT_FORMS[$currentSettlementMethod ?? \App\Services\CustomerDebtPolicyService::currentSettlementMethod($selected['policy'])] }}@if(!empty($changeDate)) từ {{ \Carbon\Carbon::parse($changeDate)->format('d/m/Y') }}@endif</dd></div>
        <div><dt>Công nợ cố định</dt><dd>{{ $selected['policy']['fixed_debt'] === null ? 'Chưa đặt' : number_format($selected['policy']['fixed_debt'], 0, ',', '.').' đ' }}</dd></div>
        <div><dt>Trần công nợ</dt><dd>{{ number_format($selected['policy']['credit_limit'], 0, ',', '.') }} đ</dd></div>
        <div><dt>Ngưỡng cảnh báo</dt><dd>{{ number_format($selected['policy']['warning_threshold'], 0, ',', '.') }} đ</dd></div>
        <div><dt>Số ngày cho vượt ngưỡng</dt><dd>{{ $selected['policy']['threshold_days'] }} ngày</dd></div>
        @if($selected['policy']['payment_mode'] === 'default')
            <div><dt>Thời hạn thanh toán</dt><dd>{{ !empty($selected['policy']['rolling_only']) ? 'Gối đầu' : $selected['policy']['due_days'].' ngày' }}</dd></div>
        @endif
    </dl>
    @if(($currentSettlementMethod ?? \App\Services\CustomerDebtPolicyService::currentSettlementMethod($selected['policy'])) === 'cycle')
        @include('customer-debt.cycle-status', ['policy' => $selected['policy'], 'alerts' => $reportAlerts])
    @endif
    @if($selected['policy']['staff_edit_locked'] && !auth()->user()->canManageCustomerDebt())
        <div class="small text-muted mt-1">Tiêu chí đã khóa.</div>
    @endif
    @if(!($saleDebtDetail ?? false) && auth()->user()->canManageCustomerDebt() && $selected['customer_id'] && $selected['policy']['staff_edit_locked'])
        <form class="mt-2" method="POST" action="{{ route('customers.debt-policy.unlock', $selected['customer_id']) }}">
            @csrf
            <input type="hidden" name="policy_version" value="{{ $selected['policy']['edit_version'] }}">
            <input type="hidden" name="return_to_report" value="1">
            <button class="btn btn-light btn-sm">Mở khóa cho sale sửa 1 lần</button>
        </form>
    @endif
</aside>
