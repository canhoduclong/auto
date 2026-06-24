@extends(accounting_layout())

@section('title', 'Form Thanh toán')
@section('subtitle', 'Nhận chuyển khoản, tìm khách hàng và ghi nhận thanh toán cho đơn hàng')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
@endphp

@push('styles')
<style>
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
    .pm-selected-customer {
        border: 1px solid #111827;
        background: #fff;
        padding: 12px 16px;
        min-height: 64px;
    }
    .pm-selected-customer strong {
        color: #0f172a;
        font-size: 1rem;
    }
    .pm-modal-grid {
        display: grid;
        grid-template-columns: minmax(300px, 1fr) minmax(280px, 380px);
        gap: 16px;
        align-items: start;
    }
    .pm-search-tools {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 8px;
        margin-bottom: 12px;
    }
    @media (max-width: 992px) {
        .pm-transfer,
        .pm-form-grid,
        .pm-order-row,
        .pm-modal-grid,
        .pm-search-tools {
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
    <textarea name="card_codes" id="pmCardCodesInput" class="d-none">{{ old('card_codes') }}</textarea>

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

                <label for="pmSelectedCustomerName">Khách hàng</label>
                <input class="form-control" id="pmSelectedCustomerName" value="{{ old('customer_keyword') }}" placeholder="Chưa chọn khách hàng" readonly>
                <button class="btn btn-outline-success" type="button" id="pmLoadCustomers">Load Khách hàng</button>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <div class="mb-2 text-success fw-bold">Đơn hàng</div>
        <div class="pm-selected-customer mb-3">
            <strong id="pmSelectedCustomerLabel">Chưa chọn khách hàng</strong>
            <div class="small text-muted mt-1" id="pmSelectedCustomerMeta">Bấm Load Khách hàng hoặc Tìm khách hàng để mở popup và gán khách vào form.</div>
        </div>
    </div>

    <div class="modal fade" id="pmCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="pmCustomerModalTitle">Chọn khách hàng</h5>
                        <div class="small text-muted" id="pmCustomerStatus">Chưa tải danh sách</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="pm-modal-grid">
                        <div>
                            <div class="pm-search-tools">
                                <input class="form-control" id="pmCustomerKeyword" value="{{ old('customer_keyword') }}" placeholder="Nhập tên, SĐT hoặc mã KH">
                                <button class="btn btn-outline-success" type="button" id="pmModalSearch">Tìm</button>
                            </div>
                            <div class="pm-customer-list" id="pmCustomerList">
                                <div class="text-muted py-5 text-center">Chưa có dữ liệu khách hàng.</div>
                            </div>
                        </div>
                        <div>
                            <label class="form-label fw-bold">Số thẻ của khách hàng</label>
                            <textarea class="form-control" id="pmModalCardCodesInput" rows="6" placeholder="Mỗi dòng một mã thẻ"></textarea>
                            <div class="form-text mb-2">Có thể bổ sung/sửa mã thẻ trước khi chọn khách hàng.</div>
                            <div class="pm-card-codes" id="pmCardCodesBox">Chưa chọn khách hàng.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-success" id="pmApplyCustomer" disabled>Chọn khách hàng</button>
                </div>
            </div>
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
    const selectedCustomerName = document.getElementById('pmSelectedCustomerName');
    const selectedCustomerLabel = document.getElementById('pmSelectedCustomerLabel');
    const selectedCustomerMeta = document.getElementById('pmSelectedCustomerMeta');
    const customerList = document.getElementById('pmCustomerList');
    const customerStatus = document.getElementById('pmCustomerStatus');
    const cardCodesInput = document.getElementById('pmCardCodesInput');
    const modalCardCodesInput = document.getElementById('pmModalCardCodesInput');
    const cardCodesBox = document.getElementById('pmCardCodesBox');
    const ordersList = document.getElementById('pmOrdersList');
    const submitButton = document.getElementById('pmSubmit');
    const customerModalEl = document.getElementById('pmCustomerModal');
    const customerModal = customerModalEl ? new bootstrap.Modal(customerModalEl) : null;
    const customerModalTitle = document.getElementById('pmCustomerModalTitle');
    const applyCustomerButton = document.getElementById('pmApplyCustomer');
    let pendingCustomer = null;
    let lastCustomerSearchMode = 'keyword';

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
            modalCardCodesInput.value = '';
            return;
        }
        modalCardCodesInput.value = codes.join('\n');
        cardCodesBox.innerHTML = codes.map(function (code) {
            const escaped = escapeHtml(code);
            return hitSet.has(String(code)) ? '<span class="hit">' + escaped + '</span>' : escaped;
        }).join('<br>');
    };

    const refreshCardPreviewFromModal = function () {
        const codes = modalCardCodesInput.value.split(/\r?\n|,|;/).map(function (code) {
            return code.trim();
        }).filter(Boolean);

        if (!codes.length) {
            cardCodesBox.textContent = 'Khách hàng chưa có mã thẻ.';
            return;
        }

        cardCodesBox.innerHTML = codes.map(function (code) {
            return escapeHtml(code);
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
                pendingCustomer = customer;
                if (radio) radio.checked = true;
                applyCustomerButton.disabled = false;
                renderCards(customer.card_codes || [], customer.matched_codes || []);
            };
            if (button) button.addEventListener('click', select);
            if (radio) radio.addEventListener('change', select);
        });
    };

    const loadCustomers = function (mode) {
        syncPreview();
        lastCustomerSearchMode = mode;
        pendingCustomer = null;
        applyCustomerButton.disabled = true;
        if (customerModalTitle) {
            customerModalTitle.textContent = mode === 'card' ? 'Tìm khách hàng theo nội dung chuyển khoản' : 'Load khách hàng';
        }
        if (customerModal) customerModal.show();
        customerStatus.textContent = 'Đang tải...';
        customerList.innerHTML = '<div class="text-muted py-5 text-center">Đang tải khách hàng...</div>';
        cardCodesBox.textContent = 'Chọn một khách hàng để xem mã thẻ.';
        modalCardCodesInput.value = '';
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

    const applyPendingCustomer = function () {
        if (!pendingCustomer) return;

        const codes = modalCardCodesInput.value.split(/\r?\n|,|;/).map(function (code) {
            return code.trim();
        }).filter(Boolean);

        customerIdInput.value = pendingCustomer.id;
        selectedCustomerName.value = pendingCustomer.name || '';
        selectedCustomerLabel.textContent = pendingCustomer.name || 'Khách hàng đã chọn';
        selectedCustomerMeta.textContent = [
            pendingCustomer.phone || '',
            pendingCustomer.code || '',
            codes.length ? ('Số thẻ: ' + codes.join(', ')) : 'Chưa có mã thẻ'
        ].filter(Boolean).join(' · ');
        cardCodesInput.value = codes.join('\n');
        if (customerModal) customerModal.hide();
        loadOrders();
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
    modalCardCodesInput.addEventListener('input', refreshCardPreviewFromModal);
    amountInput.addEventListener('input', function () {
        syncPreview();
        if (customerIdInput.value) loadOrders();
    });
    document.getElementById('pmLoadCustomers').addEventListener('click', function () { loadCustomers('keyword'); });
    document.getElementById('pmFindByCard').addEventListener('click', function () { loadCustomers('card'); });
    document.getElementById('pmModalSearch').addEventListener('click', function () { loadCustomers(lastCustomerSearchMode); });
    keywordInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadCustomers('keyword');
        }
    });
    applyCustomerButton.addEventListener('click', applyPendingCustomer);
    syncPreview();
});
</script>
@endpush
