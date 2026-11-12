<div class="mcl-debt-policy" data-debt-policy-card data-customer-id="{{ $customer->id }}" data-customer-name="{{ $customer->name }}" data-policy="{{ json_encode($debtPolicy, JSON_UNESCAPED_UNICODE) }}" data-update-url="{{ route('customers.debt-policy.update', $customer) }}" data-unlock-url="{{ route('customers.debt-policy.unlock', $customer) }}" data-can-edit="{{ $canEditDebtPolicy ? '1' : '0' }}">
    <span>Cho nợ: <strong data-debt-orders>{{ $debtPolicy['allowed_orders'] }} Bill</strong></span>
    <span>Trần công nợ: <strong data-debt-limit>{{ number_format($debtPolicy['credit_limit'], 0, ',', '.') }} đ</strong></span>
    <span title="Số ngày được phép nợ kể từ ngày giao hàng gần nhất">Ngày nợ: <strong data-debt-days>{{ $debtPolicy['due_days'] }} ngày</strong></span>
    <span class="mcl-debt-lock" data-debt-lock>{{ $debtPolicy['staff_edit_locked'] ? 'Đã khóa' : 'Được lưu 1 lần' }}</span>
    @if($canEditDebtPolicy)<button type="button" class="btn btn-sm btn-outline-primary" data-edit-debt-policy @if($debtPolicy['staff_edit_locked'] && !auth()->user()->isAdmin()) hidden @endif>Sửa nhanh</button>@endif
    @if(auth()->user()->isAdmin())<button type="button" class="btn btn-sm btn-outline-warning" data-unlock-debt-policy @if(!$debtPolicy['staff_edit_locked']) hidden @endif>Mở khóa 1 lần</button>@endif
    <span class="small" data-debt-policy-message role="status"></span>
</div>
