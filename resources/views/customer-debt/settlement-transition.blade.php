@php
    $detailQuery = $selected['customer_id'] ? ['system_customer' => $selected['customer_id']] : ['customer' => $selected['key']];
    $earliestDate = $latestChangeDate ? \Carbon\Carbon::parse($latestChangeDate)->addDay()->toDateString() : null;
@endphp
<details class="debt-detail-policy" id="debt-settlement-transition" open>
    <summary class="fw-semibold">Chuyển hình thức thanh toán từ ngày giao</summary>
    @if($settlementChanges)
        <ul class="list-unstyled small mt-2 mb-0">
            @foreach($settlementChanges as $date => $method)
                <li>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} · {{ \App\Services\CustomerDebtPolicyService::SETTLEMENT_METHODS[$method] }}@if($date > now()->toDateString()) · Sắp áp dụng @endif</li>
            @endforeach
        </ul>
    @endif
    <form class="d-flex flex-wrap gap-2 align-items-end mt-2" method="GET" action="{{ route('customer-debt.index') }}#debt-settlement-transition">
        @foreach($detailQuery + ['tab' => 'all', 'window' => 'all', 'recovery' => 'all'] as $field => $value)
            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
        @endforeach
        <div>
            <label class="form-label" for="debt-transition-preview">Ngày bắt đầu</label>
            <input class="form-control form-control-sm" id="debt-transition-preview" name="transition_preview" type="date"
                value="{{ $settlementPreview['date'] ?? max(now()->toDateString(), $earliestDate ?? '') }}" @if($earliestDate) min="{{ $earliestDate }}" @endif required>
        </div>
        <div>
            <label class="form-label" for="debt-transition-method">Chuyển sang</label>
            <select class="form-select form-select-sm" id="debt-transition-method" name="transition_method" required>
                @foreach(\App\Services\CustomerDebtPolicyService::SETTLEMENT_METHODS as $method => $label)
                    @continue($method === $nextSettlementMethod)
                    <option value="{{ $method }}" @selected(($settlementPreview['method'] ?? ($nextSettlementMethod === 'bill' ? 'batch' : 'bill')) === $method)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-sm btn-outline-primary">Xem trước</button>
    </form>
    @if(isset($settlementPreview['error']))
        <p class="small text-danger mt-2 mb-0">{{ $settlementPreview['error'] }}</p>
    @elseif($settlementPreview)
        <p class="small mt-2 mb-1">Đơn giao trước {{ \Carbon\Carbon::parse($settlementPreview['date'])->format('d/m/Y') }} giữ cách đối chiếu của từng kỳ. Từ ngày này, đơn mới áp dụng <strong>{{ \App\Services\CustomerDebtPolicyService::SETTLEMENT_METHODS[$settlementPreview['method']] }}</strong>.</p>
        <p class="small mb-2">Nợ cũ còn phải thu <strong>{{ number_format($settlementPreview['remaining'], 0, ',', '.') }} đ</strong> · Tiền thu còn dư hoặc chờ ghép <strong>{{ number_format($settlementPreview['pending'], 0, ',', '.') }} đ</strong></p>
        <form method="POST" action="{{ route('customer-debt.settlement-transition') }}">
            @csrf
            @include('customer-debt.payment-allocation-fields')
            <input type="hidden" name="transition_date" value="{{ $settlementPreview['date'] }}">
            <input type="hidden" name="transition_method" value="{{ $settlementPreview['method'] }}">
            <button class="btn btn-sm btn-primary">Xác nhận ngày chuyển</button>
        </form>
    @endif
</details>
