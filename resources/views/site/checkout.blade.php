@extends('layouts.site')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <h2>Thông tin đơn hàng</h2>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <div class="fw-bold mb-1">Không thể tạo đơn hàng:</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('orders.store_from_cart') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_id" id="selected_customer_id" value="{{ old('customer_id') }}">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Thông tin người nhận</h5>

                        @auth
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary" id="btnToggleCustomerPicker">Chọn khách hàng</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnClearCustomer" style="display:none;">Bỏ chọn</button>
                            <div id="selectedCustomerPreview" class="alert alert-info mt-2 mb-0" style="display:none;"></div>
                        </div>

                        <div id="customerPickerPanel" class="border rounded p-3 mb-3" style="display:none;">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-8">
                                    <label for="customer_search" class="form-label">Tìm khách hàng (tên, email hoặc số điện thoại)</label>
                                    <input type="text" id="customer_search" class="form-control" placeholder="Nhập từ khóa tìm kiếm...">
                                </div>
                                <div class="col-md-2">
                                    <label for="customer_per_page" class="form-label">Số dòng</label>
                                    <select id="customer_per_page" class="form-select">
                                        <option value="10">10</option>
                                        <option value="15" selected>15</option>
                                        <option value="20">20</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary w-100" id="btnSearchCustomer">Tìm</button>
                                </div>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tên</th>
                                            <th>Email</th>
                                            <th>Số điện thoại</th>
                                            <th>Địa chỉ</th>
                                            <th>Ghi chú</th>
                                            <th width="110">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="customerSearchResults">
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Nhập từ khóa để tìm khách hàng.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div id="customerPaginationInfo" class="text-muted small"></div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCustomerPrev">Trước</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCustomerNext">Sau</button>
                                </div>
                            </div>
                        </div>
                        @endauth

                        <div class="mb-3">
                            <label for="recipient_name" class="form-label">Họ tên người nhận</label>
                            <input type="text" class="form-control @error('recipient_name') is-invalid @enderror" 
                                id="recipient_name" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name ?? '') }}" required>
                            @error('recipient_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="recipient_email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('recipient_email') is-invalid @enderror"
                                id="recipient_email" name="recipient_email" value="{{ old('recipient_email', auth()->user()->email ?? '') }}">
                            @error('recipient_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="recipient_phone" class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control @error('recipient_phone') is-invalid @enderror" 
                                id="recipient_phone" name="recipient_phone" value="{{ old('recipient_phone') }}" required>
                            @error('recipient_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="recipient_address" class="form-label">Địa chỉ nhận hàng</label>
                            <textarea class="form-control @error('recipient_address') is-invalid @enderror" 
                                id="recipient_address" name="recipient_address" rows="3" required>{{ old('recipient_address') }}</textarea>
                            @error('recipient_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="note" name="note" rows="3">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Đơn hàng của bạn</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Đơn giá</th>
                                        <th>Số lượng</th>
                                        <th>Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $total = 0 @endphp
                                    @foreach(session('cart') as $id => $details)
                                        @php $total += $details['price'] * $details['quantity'] @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($details['image'])
                                                        <img src="{{ asset('storage/' . $details['image']) }}" 
                                                            alt="{{ $details['name'] }}" width="50" class="me-3">
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">{{ $details['name'] }}</h6>
                                                        <small>SKU: {{ $details['sku'] }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ number_format($details['price']) }}đ</td>
                                            <td>{{ $details['quantity'] }}</td>
                                            <td>{{ number_format($details['price'] * $details['quantity']) }}đ</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Tổng tiền:</strong></td>
                                        <td><strong>{{ number_format($total) }}đ</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="{{ route('cart.show') }}" class="btn btn-outline-secondary me-2">Quay lại giỏ hàng</a>
                    <button type="submit" class="btn btn-primary">Đặt hàng</button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tóm tắt đơn hàng</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tạm tính:</span>
                        <span>{{ number_format($total) }}đ</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Tổng cộng:</strong>
                        <strong>{{ number_format($total) }}đ</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@auth
@push('scripts')
<script>
(() => {
    const pickerPanel = document.getElementById('customerPickerPanel');
    const togglePickerBtn = document.getElementById('btnToggleCustomerPicker');
    const clearCustomerBtn = document.getElementById('btnClearCustomer');
    const searchInput = document.getElementById('customer_search');
    const searchBtn = document.getElementById('btnSearchCustomer');
    const perPageSelect = document.getElementById('customer_per_page');
    const resultsBody = document.getElementById('customerSearchResults');
    const infoText = document.getElementById('customerPaginationInfo');
    const prevBtn = document.getElementById('btnCustomerPrev');
    const nextBtn = document.getElementById('btnCustomerNext');
    const selectedPreview = document.getElementById('selectedCustomerPreview');

    const selectedCustomerIdInput = document.getElementById('selected_customer_id');
    const recipientName = document.getElementById('recipient_name');
    const recipientEmail = document.getElementById('recipient_email');
    const recipientPhone = document.getElementById('recipient_phone');
    const recipientAddress = document.getElementById('recipient_address');
    const noteInput = document.getElementById('note');

    const state = {
        page: 1,
        lastPage: 1,
        q: '',
    };

    function setReadonlyBySelection(selected) {
        recipientName.readOnly = selected;
        recipientEmail.readOnly = selected;
        recipientPhone.readOnly = selected;
    }

    function renderLoading() {
        resultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Đang tải...</td></tr>';
    }

    function renderEmpty() {
        resultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Không tìm thấy khách hàng.</td></tr>';
    }

    function applySelectedCustomer(customer) {
        const name = customer.name || '';
        const email = customer.email || '';
        const phone = customer.phone || '';
        const address = customer.address || '';
        const note = customer.note || '';

        recipientName.value = name;
        recipientEmail.value = email;
        recipientPhone.value = phone;
        recipientAddress.value = address;
        noteInput.value = note;

        selectedCustomerIdInput.value = String(customer.id);

        selectedPreview.style.display = 'block';
        selectedPreview.innerHTML = '<strong>Đã chọn:</strong> ' + escapeHtml(name) +
            ' | ' + escapeHtml(email || '-') +
            ' | ' + escapeHtml(phone || '-') +
            ' | ' + escapeHtml(address || '-');
        clearCustomerBtn.style.display = 'inline-block';
        setReadonlyBySelection(true);
    }

    function clearSelectedCustomer() {
        selectedCustomerIdInput.value = '';
        selectedPreview.style.display = 'none';
        selectedPreview.innerHTML = '';
        clearCustomerBtn.style.display = 'none';
        setReadonlyBySelection(false);
    }

    function renderRows(items) {
        if (!items.length) {
            renderEmpty();
            return;
        }

        resultsBody.innerHTML = items.map((customer) => {
            const payload = encodeURIComponent(JSON.stringify({
                id: customer.id,
                name: customer.name || '',
                email: customer.email || '',
                phone: customer.phone || '',
                address: customer.address || '',
                note: customer.note || '',
            }));

            return '<tr>' +
                '<td>' + escapeHtml(customer.name || '') + '</td>' +
                '<td>' + escapeHtml(customer.email || '') + '</td>' +
                '<td>' + escapeHtml(customer.phone || '') + '</td>' +
                '<td>' + escapeHtml(customer.address || '') + '</td>' +
                '<td>' + escapeHtml(customer.note || '') + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-primary btn-select-customer" data-customer="' + payload + '">Chọn</button></td>' +
                '</tr>';
        }).join('');
    }

    async function fetchCustomers(page = 1) {
        state.page = page;
        state.q = (searchInput.value || '').trim();

        renderLoading();

        const params = new URLSearchParams({
            q: state.q,
            page: String(state.page),
            per_page: String(perPageSelect.value || '15'),
        });

        try {
            const res = await fetch('{{ route('cart.customers.search') }}?' + params.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                let message = 'Lỗi tải dữ liệu';
                try {
                    const errJson = await res.json();
                    if (errJson && errJson.message) {
                        message = errJson.message;
                    }
                } catch (e) {
                    // Ignore JSON parse errors for non-JSON responses.
                }
                throw new Error(message);
            }

            const json = await res.json();
            const items = json.data || [];
            const meta = json.meta || {};

            state.page = Number(meta.current_page || 1);
            state.lastPage = Number(meta.last_page || 1);

            renderRows(items);

            const total = Number(meta.total || 0);
            infoText.textContent = total > 0
                ? ('Trang ' + state.page + '/' + state.lastPage + ' - Tổng ' + total + ' khách hàng')
                : 'Không có dữ liệu';

            prevBtn.disabled = state.page <= 1;
            nextBtn.disabled = state.page >= state.lastPage;
        } catch (e) {
            resultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Lỗi khi tải danh sách khách hàng: ' + escapeHtml(e.message || 'unknown') + '</td></tr>';
            infoText.textContent = '';
            prevBtn.disabled = true;
            nextBtn.disabled = true;
        }
    }

    togglePickerBtn.addEventListener('click', () => {
        const opening = pickerPanel.style.display === 'none';
        pickerPanel.style.display = opening ? 'block' : 'none';
        if (opening) {
            fetchCustomers(1);
        }
    });

    searchBtn.addEventListener('click', () => fetchCustomers(1));
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            fetchCustomers(1);
        }
    });
    perPageSelect.addEventListener('change', () => fetchCustomers(1));

    prevBtn.addEventListener('click', () => {
        if (state.page > 1) {
            fetchCustomers(state.page - 1);
        }
    });

    nextBtn.addEventListener('click', () => {
        if (state.page < state.lastPage) {
            fetchCustomers(state.page + 1);
        }
    });

    resultsBody.addEventListener('click', (event) => {
        const btn = event.target.closest('.btn-select-customer');
        if (!btn) {
            return;
        }

        const raw = btn.getAttribute('data-customer');
        if (!raw) {
            return;
        }

        const customer = JSON.parse(decodeURIComponent(raw));
        applySelectedCustomer(customer);
    });

    clearCustomerBtn.addEventListener('click', clearSelectedCustomer);

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (selectedCustomerIdInput.value) {
        setReadonlyBySelection(true);
        clearCustomerBtn.style.display = 'inline-block';
    }
})();
</script>
@endpush
@endauth