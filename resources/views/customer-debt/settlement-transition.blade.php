@php
    $detailQuery = $selected['customer_id'] ? ['system_customer' => $selected['customer_id']] : ['customer' => $selected['key']];
    $returnDate = $settlementReturnDate ?? null;
    $earliestDate = $latestChangeDate && $latestChangeDate !== $returnDate ? \Carbon\Carbon::parse($latestChangeDate)->addDay()->toDateString() : $returnDate;
    $forms = \App\Services\CustomerDebtPolicyService::PAYMENT_FORMS;
    $activeForm = \App\Services\CustomerDebtPolicyService::currentSettlementMethod($selected['policy']);
    $defaultForm = $settlementPreview['method'] ?? request('transition_method', $nextSettlementMethod === 'bill' ? 'batch' : 'bill');
@endphp
<details class="debt-detail-policy" id="debt-settlement-transition" open>
    <summary class="fw-semibold">Cấu hình hình thức thanh toán</summary>
    <p class="small text-muted mt-2 mb-0">Đang áp dụng <strong>{{ $forms[$activeForm] }}</strong>. Sau ngày kết thúc, hệ thống quay về hình thức trước đó nếu chưa có giai đoạn kế tiếp.</p>
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
            <label class="form-label" for="debt-transition-end">Ngày kết thúc</label>
            <input class="form-control form-control-sm" id="debt-transition-end" name="transition_end" type="date"
                value="{{ $settlementPreview['end'] ?? '' }}" required>
        </div>
        <div>
            <label class="form-label" for="debt-transition-method">Chuyển sang</label>
            <select class="form-select form-select-sm" id="debt-transition-method" name="transition_method" required>
                @foreach($forms as $method => $label)
                    <option value="{{ $method }}" @selected($defaultForm === $method)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-sm btn-outline-primary">Xem trước</button>
    </form>
    @if(isset($settlementPreview['error']))
        <p class="small text-danger mt-2 mb-0">{{ $settlementPreview['error'] }}</p>
    @elseif($settlementPreview)
        <p class="small mt-2 mb-1">Đơn giao trước {{ \Carbon\Carbon::parse($settlementPreview['date'])->format('d/m/Y') }} giữ cách đối chiếu của từng kỳ. Hình thức mới <strong>{{ $forms[$settlementPreview['method']] }}</strong>@if(!empty($settlementPreview['end'])) đến hết {{ \Carbon\Carbon::parse($settlementPreview['end'])->format('d/m/Y') }}@endif.</p>
        <p class="small mb-2">Nợ cũ còn phải thu <strong>{{ number_format($settlementPreview['remaining'], 0, ',', '.') }} đ</strong> · Tiền thu còn dư hoặc chờ ghép <strong>{{ number_format($settlementPreview['pending'], 0, ',', '.') }} đ</strong></p>
        <form method="POST" action="{{ route('customer-debt.settlement-transition') }}">
            @csrf
            @include('customer-debt.payment-allocation-fields')
            <input type="hidden" name="transition_date" value="{{ $settlementPreview['date'] }}">
            @if(!empty($settlementPreview['end']))
                <input type="hidden" name="transition_end" value="{{ $settlementPreview['end'] }}">
            @endif
            <input type="hidden" name="transition_method" value="{{ $settlementPreview['method'] }}">
            <button class="btn btn-sm btn-primary">Xác nhận giai đoạn</button>
        </form>
    @endif
</details>
