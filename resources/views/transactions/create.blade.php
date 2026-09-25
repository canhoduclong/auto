@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{ __('transactions.titles.create') }}</h2>
    <form action="{{ route('transactions.store') }}" method="POST">
        @csrf
        @if(request('order_id'))
            @php
                $order = $orders->where('id', request('order_id'))->first();
                $paid = $order ? ($order->transactions->where('type', 'payment')->sum('amount') - $order->transactions->where('type', 'refund')->sum('amount')) : 0;
                $remain = $order ? max(0, $order->total - $paid) : 0;
            @endphp
            <input type="hidden" name="order_id" value="{{ request('order_id') }}">
            <script>window.remainAmount = {{ $remain ?? 0 }};</script>
            <div class="mb-3">
                <label>{{ __('transactions.labels.order') }}</label>
                <input type="text" class="form-control mb-2" value="#{{ request('order_id') }}" disabled>
                @if($order)
                <div class="alert alert-info p-2 mb-1">
                    <div><b>{{ __('transactions.labels.total') }}:</b> {{ number_format($order->total, 0, ',', '.') }} đ</div>
                    <div><b>{{ __('transactions.labels.paid') }}:</b> {{ number_format($paid, 0, ',', '.') }} đ</div>
                    <div><b>{{ __('transactions.labels.remaining') }}:</b> <span class="text-danger fw-bold">{{ number_format($remain, 0, ',', '.') }} đ</span></div>
                </div>
                @endif
            </div>
        @else
            <div class="mb-3">
                <label>{{ __('transactions.placeholders.order_optional') }}</label>
                <select name="order_id" class="form-select" id="order_id_select">
                    <option value="">{{ __('transactions.placeholders.no_link') }}</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}">#{{ $order->code }} - {{ $order->customer->name ?? '' }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="mb-3" id="order_total_box" style="display:none;">
            <label>{{ __('transactions.labels.total') }}: <span id="order_total_text" class="fw-bold text-danger"></span></label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="pay_full_order">
                <label class="form-check-label" for="pay_full_order">{{ __('transactions.placeholders.pay_full') }}</label>
            </div>
        </div>
        @if(!request('order_id'))
        <div class="mb-3">
            <label>{{ __('transactions.placeholders.customer_optional') }}</label>
            <div class="input-group">
                <input type="text" id="customer_name" class="form-control" placeholder="{{ __('transactions.placeholders.choose_customer') }}" readonly>
                <input type="hidden" name="customer_id" id="customer_id">
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#customerModal">{{ __('transactions.buttons.select_customer') }}</button>
            </div>
        </div>
        @endif
        <div class="mb-3">
            <label>{{ __('transactions.labels.amount') }}</label>
            <div class="input-group">
                <input type="text" name="amount" class="form-control format-number" id="amount_input" required @if(request('order_id') && $order) max="{{ $remain }}" @endif>
                @if(request('order_id') && $order)
                <span class="input-group-text bg-white">
                    <input type="checkbox" id="pay_full_order"> <label for="pay_full_order" class="ms-1 mb-0">{{ __('transactions.placeholders.pay_full_short') }}</label>
                </span>
                @endif
            </div>
        </div>
        <div class="mb-3">
            <label>{{ __('transactions.labels.type') }}</label>
            <select name="type" class="form-select" id="type_select" required>
                <option value="payment">{{ __('transactions.types.payment') }}</option>
                <option value="refund">{{ __('transactions.types.refund') }}</option>
                <option value="fee">{{ __('transactions.types.fee') }}</option>
                <option value="extra_income">{{ __('transactions.types.extra_income') }}</option>
                <option value="extra_expense">{{ __('transactions.types.extra_expense') }}</option>
            </select>
        </div>
        {{-- Chi khác: expense type dropdown (visible only when type = extra_expense) --}}
        <div class="mb-3" id="expense_type_box" style="display:none;">
            <label class="form-label">Loại chi <span class="text-muted fw-normal">(tùy chọn)</span></label>
            <div class="input-group">
                <select name="expense_type_id" id="expense_type_select" class="form-select">
                    <option value="">-- Chọn loại chi --</option>
                    @foreach($expenseTypes as $et)
                        <option value="{{ $et->id }}" {{ old('expense_type_id') == $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-secondary" id="btn_add_expense_type" title="Thêm loại chi mới">
                    <i class="bi bi-plus-lg"></i> Thêm mới
                </button>
            </div>
            {{-- Inline add form --}}
            <div id="new_expense_type_form" class="mt-2" style="display:none;">
                <div class="input-group input-group-sm">
                    <input type="text" id="new_expense_type_name" class="form-control" placeholder="Tên loại chi mới..." maxlength="100">
                    <button type="button" class="btn btn-success" id="btn_save_expense_type">Lưu</button>
                    <button type="button" class="btn btn-outline-secondary" id="btn_cancel_expense_type">Hủy</button>
                </div>
                <div id="expense_type_error" class="text-danger small mt-1" style="display:none;"></div>
            </div>
        </div>

        {{-- Đối tượng chi (user) - chỉ hiện khi extra_expense --}}
        <div class="mb-3" id="payee_user_box" style="display:none;">
            <label class="form-label">Đối tượng chi <span class="text-muted fw-normal">(người nhận)</span></label>
            <select name="payee_user_id" id="payee_user_select" class="form-select">
                <option value="">-- Chọn nhân viên --</option>
                @foreach(\App\Models\User::orderBy('name')->get(['id','name']) as $u)
                    <option value="{{ $u->id }}" {{ old('payee_user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>{{ __('transactions.labels.method') }}</label>
            <input type="text" name="method" class="form-control">
        </div>
        <div class="mb-3">
            <label>{{ __('transactions.labels.note') }}</label>
            <input type="text" name="note" class="form-control">
        </div>
        <button class="btn btn-primary">{{ __('transactions.buttons.save') }}</button>
    </form>
</div>
@include('customers.popup_select')
@endsection

@push('scripts')
<script>
(function () {
    const typeSelect = document.getElementById('type_select');
    const expenseBox = document.getElementById('expense_type_box');
    const payeeBox   = document.getElementById('payee_user_box');
    const expenseSelect = document.getElementById('expense_type_select');
    const btnAdd = document.getElementById('btn_add_expense_type');
    const newForm = document.getElementById('new_expense_type_form');
    const nameInput = document.getElementById('new_expense_type_name');
    const btnSave = document.getElementById('btn_save_expense_type');
    const btnCancel = document.getElementById('btn_cancel_expense_type');
    const errorDiv = document.getElementById('expense_type_error');

    function toggleExpenseBox() {
        const show = typeSelect.value === 'extra_expense';
        expenseBox.style.display = show ? '' : 'none';
        payeeBox.style.display   = show ? '' : 'none';
    }
    typeSelect.addEventListener('change', toggleExpenseBox);
    toggleExpenseBox();

    btnAdd.addEventListener('click', function () {
        newForm.style.display = '';
        nameInput.focus();
    });
    btnCancel.addEventListener('click', function () {
        newForm.style.display = 'none';
        nameInput.value = '';
        errorDiv.style.display = 'none';
    });

    btnSave.addEventListener('click', function () {
        const name = nameInput.value.trim();
        if (!name) { errorDiv.textContent = 'Vui lòng nhập tên loại chi.'; errorDiv.style.display = ''; return; }
        btnSave.disabled = true;
        fetch('{{ route('expense-types.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ name })
        })
        .then(r => r.json())
        .then(data => {
            if (data.id) {
                const opt = new Option(data.name, data.id, true, true);
                expenseSelect.add(opt);
                newForm.style.display = 'none';
                nameInput.value = '';
                errorDiv.style.display = 'none';
            } else {
                const msg = data.errors?.name?.[0] || data.message || 'Lỗi không xác định';
                errorDiv.textContent = msg; errorDiv.style.display = '';
            }
        })
        .catch(() => { errorDiv.textContent = 'Lỗi kết nối.'; errorDiv.style.display = ''; })
        .finally(() => { btnSave.disabled = false; });
    });

    nameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); btnSave.click(); }
    });
})();
</script>
@endpush
