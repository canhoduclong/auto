<button type="button" class="btn btn-sm btn-light" popovertarget="debt-actions-{{ $loop->index }}" data-debt-actions-toggle aria-label="Thao tác cho {{ $debt['name'] }}" title="Thao tác">⋮</button>
<div id="debt-actions-{{ $loop->index }}" class="debt-actions-menu" popover>
    <a href="{{ request()->fullUrlWithQuery(['customer'=>$debt['key'],'system_customer'=>null,'window'=>request('from') ? 'custom' : 'all','report_from'=>request('from') ? $periodFrom : null,'report_to'=>request('to') ? $periodTo : null,'purchase_page'=>null,'payment_page'=>null,'detail_page'=>null]) }}#debt-detail">Đối chiếu</a>
    <button type="button" data-accounting-policy data-key="{{ $debt['key'] }}" data-name="{{ $debt['name'] }}" data-policy='@json($debt["policy"])'>Chính sách thanh toán</button>
    @if($canAdjust) @include('customer-debt.balance-button') @endif
    <button type="button" class="debt-recovery-button" data-key="{{ $debt['key'] }}" data-name="{{ $debt['name'] }}" data-status="{{ $debt['recovery']['status'] }}" data-note="{{ $debt['recovery']['note'] }}" data-version="{{ $debt['recovery']['version'] }}">Phân loại thu hồi</button>
    @if($debt['customer_id'])<a href="{{ accounting_route('customer-debts.show', $debt['customer_id']) }}">Sổ kế toán / Điều chỉnh</a>@endif
    @if(auth()->user()->isAdmin() && $debt['customer_id'] && $debt['policy']['staff_edit_locked'])
    <button type="button" data-unlock-sale-policy data-url="{{ route('customers.debt-policy.unlock', $debt['customer_id']) }}" data-version="{{ $debt['policy']['edit_version'] }}">Mở khóa cho sale sửa 1 lần</button><span class="small text-danger" role="status"></span>
    @endif
</div>
