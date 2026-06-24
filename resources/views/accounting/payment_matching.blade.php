@extends(accounting_layout())

@section('title', 'Form Thanh toán')
@section('subtitle', 'Nhận chuyển khoản, tìm khách hàng và ghi nhận thanh toán cho đơn hàng')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
@endphp

@push('styles')
<style>
    .pm-wrap {
        display: grid;
        grid-template-columns: minmax(320px, 1fr) minmax(320px, 420px);
        gap: 16px;
        align-items: start;
    }
    .pm-transfer {
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr) 180px;
        gap: 14px;
        align-items: center;
        padding: 14px 0 20px;
        color: #0f172a;
    }
    .pm-transfer .pm-time {
        color: #64748b;
        font-size: .9rem;
    }
    .pm-transfer .pm-content {
        min-height: 76px;
        border-left: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        padding: 6px 16px;
        white-space: pre-line;
    }
    .pm-transfer .pm-money {
        font-weight: 800;
        color: #047857;
        text-align: center;
    }
    .pm-form-grid {
        display: grid;
        grid-template-columns: 130px minmax(0, 1fr) auto;
        gap: 8px 12px;
        align-items: center;
    }
    .pm-form-grid label {
        color: #dc2626;
        font-weight: 700;
        margin: 0;
    }
    .pm-customer-list {
        border: 1px solid #16a34a;
        min-height: 260px;
        padding: 14px;
        background: #fff;
    }
    .pm-customer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 7px 0;
        border-bottom: 1px solid #eef2f7;
    }
    .pm-customer-row:last-child { border-bottom: 0; }
    .pm-customer-row strong {
        display: block;
        color: #0f172a;
        font-size: 1.02rem;
    }
    .pm-card-codes {
        border: 1px solid #94a3b8;
        min-height: 112px;
        padding: 12px;
        background: #fff;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }
    .pm-card-codes .hit {
        color: #dc2626;
        text-decoration: underline;
        text-underline-offset: 4px;
    }
    .pm-orders {
        border: 1px solid #111827;
        background: #fff;
    }
    .pm-order-row {
        display: grid;
        grid-template-columns: 34px 100px minmax(0, 1fr) 130px;
        gap: 12px;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    .pm-order-row:last-child { border-bottom: 0; }
    .pm-radio {
        width: 20px;
        height: 20px;
    }
    @media (max-width: 992px) {
        .pm-wrap,
        .pm-transfer,
        .pm-form-grid,
        .pm-order-row {
            grid-template-columns: 1fr;
        }
        .pm-transfer .pm-money { text-align: left; }
    }
</style>
@endpush

@section('accounting_content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ accounting_route('payment-matching.store') }}" id="paymentMatchingForm">
    @csrf
    <input type="hidden" name="customer_id" id="pmCustomerId" value="{{ old('customer_id') }}">

    <div class="acc-card mb-3">
        <div class="card-body">
            <div class="text-success fw-bold mb-2">Form Thanh toán</div>
            <div class="pm-transfer">
                <div class="pm-time">
                    <div>Thanh toán</div>
                    <div>{{ now()->format('d/m/Y') }}</div>
                    <div>{{ now()->format('H:i:s') }}</div>
                </div>
                <div class="pm-content" id="pmTransferPreview">TKThe: 86852526868, tai MSCBVNVX. Phan Thanh Dat
chuyen khoan nhanh qua Zalo-
O2OO9704220623184O442O26H7WE429485</div>
                <div class="pm-money">+ <span id="pmAmountPreview">0</span> VND</div>
            </div>

            <div class="pm-form-grid">
                <label for="pmTransferContent">Nhập thông tin chuyển khoản</label>
                <textarea class="form-control" id="pmTransferContent" name="transfer_content" rows="2" required>{{ old('transfer_content') }}</textarea>
                <button class="btn btn-outline-danger" type="button" id="pmFindByCard">Tìm khách hàng</button>

                <label for="pmAmount">Số tiền CK</label>
                <input class="form-control" id="pmAmount" name="amount" value="{{ old('amount') }}" placeholder="3.500.000" required>
                <span></span>

                <label for="pmCustomerKeyword">Khách hàng</label>
                <input class="form-control" id="pmCustomerKeyword" value="{{ old('customer_keyword') }}" placeholder="Nhập tên, SĐT hoặc mã KH">
                <button class="btn btn-outline-success" type="button" id="pmLoadCustomers">Load Khách hàng</button>
            </div>
        </div>
    </div>

    <div class="pm-wrap mb-3">
        <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-success fw-bold">Chọn khách hàng</div>
                <div class="small text-muted" id="pmCustomerStatus">Chưa tải danh sách</div>
            </div>
            <div class="pm-customer-list" id="pmCustomerList">
                <div class="text-muted py-5 text-center">Nhập thông tin rồi bấm Load Khách hàng hoặc Tìm khách hàng.</div>
            </div>
        </div>

        <div>
            <div class="mb-3">
                <label class="form-label fw-bold">Số thẻ của khách hàng</label>
                <textarea class="form-control" name="card_codes" id="pmCardCodesInput" rows="5" placeholder="Mỗi dòng một mã thẻ">{{ old('card_codes') }}</textarea>
                <div class="form-text">Các mã này được lưu vào hồ sơ khách hàng để lần sau so khớp tự động.</div>
            </div>
            <div class="pm-card-codes" id="pmCardCodesBox">Chưa chọn khách hàng.</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Tài khoản nhận</label>
            <select class="form-select" name="account_id">
                <option value="">Không chọn</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>{{ $account->name }} - {{ $money($account->balance ?? 0) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Danh mục thu</label>
            <select class="form-select" name="transaction_category_id">
                <option value="">Tự chọn danh mục thu đầu tiên</option>
                @foreach($incomeCategories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('transaction_category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-2 text-success fw-bold">Chọn đơn</div>
    <div class="pm-orders mb-3" id="pmOrdersList">
        <div class="text-muted py-4 text-center">Chọn khách hàng để tải các đơn có công nợ lớn hơn hoặc bằng số tiền CK.</div>
    </div>

    <button class="btn btn-dark px-4" type="submit" id="pmSubmit" disabled>Xác nhận</button>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const transferInput = document.getElementById('pmTransferContent');
    const transferPreview = document.getElementById('pmTransferPreview');
    const amountInput = document.getElementById('pmAmount');
    const amountPreview = document.getElementById('pmAmountPreview');
    const keywordInput = document.getElementById('pmCustomerKeyword');
    const customerIdInput = document.getElementById('pmCustomerId');
    const customerList = document.getElementById('pmCustomerList');
    const customerStatus = document.getElementById('pmCustomerStatus');
    const cardCodesInput = document.getElementById('pmCardCodesInput');
    const cardCodesBox = document.getElementById('pmCardCodesBox');
    const ordersList = document.getElementById('pmOrdersList');
    const submitButton = document.getElementById('pmSubmit');

    const formatMoney = function (value) {
        const number = Number(String(value || '').replace(/[.,\s]/g, '')) || 0;
        return new Intl.NumberFormat('vi-VN').format(number);
    };

    const syncPreview = function () {
        transferPreview.textContent = transferInput.value || transferPreview.textContent;
        amountPreview.textContent = formatMoney(amountInput.value);
    };

    const escapeHtml = function (value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[char];
        });
    };

    const renderCards = function (codes, hits) {
        const hitSet = new Set((hits || []).map(String));
        if (!codes || !codes.length) {
            cardCodesBox.textContent = 'Khách hàng chưa có mã thẻ.';
            cardCodesInput.value = '';
            return;
        }
        cardCodesInput.value = codes.join('\n');
        cardCodesBox.innerHTML = codes.map(function (code) {
            const escaped = escapeHtml(code);
            return hitSet.has(String(code)) ? '<span class="hit">' + escaped + '</span>' : escaped;
        }).join('<br>');
    };

    const renderCustomers = function (customers) {
        customerStatus.textContent = customers.length ? customers.length + ' khách hàng' : 'Không tìm thấy';
        if (!customers.length) {
            customerList.innerHTML = '<div class="text-muted py-5 text-center">Không tìm thấy khách hàng phù hợp.</div>';
            return;
        }
        customerList.innerHTML = customers.map(function (customer) {
            return '<div class="pm-customer-row">' +
                '<label class="d-flex align-items-center gap-2 mb-0">' +
                '<input class="form-check-input pm-radio js-pm-customer" type="radio" name="customer_pick" value="' + customer.id + '">' +
                '<span><strong>' + escapeHtml(customer.name) + '</strong><span class="small text-muted">' + escapeHtml(customer.phone || customer.code || '') + '</span></span>' +
                '</label>' +
                '<button class="btn btn-sm btn-outline-success js-pm-select-customer" type="button" data-id="' + customer.id + '">Chọn</button>' +
                '</div>';
        }).join('');
        customers.forEach(function (customer) {
            const button = customerList.querySelector('.js-pm-select-customer[data-id="' + customer.id + '"]');
            const radio = customerList.querySelector('.js-pm-customer[value="' + customer.id + '"]');
            const select = function () {
                customerIdInput.value = customer.id;
                if (radio) radio.checked = true;
                renderCards(customer.card_codes || [], customer.matched_codes || []);
                loadOrders();
            };
            if (button) button.addEventListener('click', select);
            if (radio) radio.addEventListener('change', select);
        });
    };

    const loadCustomers = function (mode) {
        syncPreview();
        customerStatus.textContent = 'Đang tải...';
        const params = new URLSearchParams({
            mode: mode,
            keyword: keywordInput.value || '',
            transfer_content: transferInput.value || ''
        });
        fetch('{{ accounting_route('payment-matching.customers') }}?' + params.toString(), {
            headers: {'Accept': 'application/json'}
        })
            .then(response => response.json())
            .then(payload => renderCustomers(payload.data || []))
            .catch(() => {
                customerStatus.textContent = 'Lỗi tải dữ liệu';
                customerList.innerHTML = '<div class="text-danger py-5 text-center">Không tải được khách hàng.</div>';
            });
    };

    const loadOrders = function () {
        submitButton.disabled = true;
        const customerId = customerIdInput.value;
        const amount = amountInput.value;
        if (!customerId || !amount) {
            ordersList.innerHTML = '<div class="text-muted py-4 text-center">Chọn khách hàng và nhập số tiền CK.</div>';
            return;
        }
        ordersList.innerHTML = '<div class="text-muted py-4 text-center">Đang tải đơn hàng...</div>';
        const params = new URLSearchParams({customer_id: customerId, amount: amount});
        fetch('{{ accounting_route('payment-matching.orders') }}?' + params.toString(), {
            headers: {'Accept': 'application/json'}
        })
            .then(response => response.json())
            .then(function (payload) {
                const orders = payload.data || [];
                if (!orders.length) {
                    ordersList.innerHTML = '<div class="text-muted py-4 text-center">Không có đơn hàng nào đủ số tiền đã nhập.</div>';
                    return;
                }
                ordersList.innerHTML = orders.map(function (order) {
                    return '<label class="pm-order-row mb-0">' +
                        '<input class="form-check-input pm-radio js-pm-order" type="radio" name="order_id" value="' + order.id + '">' +
                        '<span><strong>' + escapeHtml(order.created_at || '') + '</strong><br><span class="small text-muted">' + escapeHtml(order.code) + '</span></span>' +
                        '<span><strong>' + escapeHtml(order.note || 'Đơn hàng') + '</strong><br><span class="small text-muted">Đã thanh toán ' + formatMoney(order.amount_paid) + 'đ</span></span>' +
                        '<span class="text-danger fw-bold text-end">' + formatMoney(order.amount_due) + 'đ</span>' +
                        '</label>';
                }).join('');
                ordersList.querySelectorAll('.js-pm-order').forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        submitButton.disabled = false;
                    });
                });
            })
            .catch(() => {
                ordersList.innerHTML = '<div class="text-danger py-4 text-center">Không tải được đơn hàng.</div>';
            });
    };

    transferInput.addEventListener('input', syncPreview);
    amountInput.addEventListener('input', function () {
        syncPreview();
        if (customerIdInput.value) loadOrders();
    });
    document.getElementById('pmLoadCustomers').addEventListener('click', function () { loadCustomers('keyword'); });
    document.getElementById('pmFindByCard').addEventListener('click', function () { loadCustomers('card'); });
    syncPreview();
});
</script>
@endpush
