@extends($layout)
@section('title', 'Quản lý phí ship của tôi')
@include('processes.styles')
@section('content')
<div class="container-fluid process-page">
    <h1>Quản lý phí ship của tôi</h1>
    <p class="text-muted">Chọn các đơn đã giao để gửi yêu cầu xác nhận chi phí ship.</p>
    @include('processes.shipping-tabs', ['activeTab' => 'orders'])
    @include('processes.messages')
    @if(!$definition)
        <div class="alert alert-warning">Chưa có quy trình đang áp dụng. Vui lòng liên hệ quản trị.</div>
    @endif
    <form class="process-card row g-3" method="GET" action="{{ route('shipping-expenses.index') }}">
        <input type="hidden" name="sort" value="{{ request('sort', 'date') }}">
        <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
        <input type="hidden" name="per_page" value="{{ request('per_page', 20) }}">
        <div class="col-md-2"><label class="form-label">Ngày giao từ</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Trạng thái đơn</label><select class="form-select" name="order_status"><option value="">Tất cả</option>
            @foreach(['delivered'=>'Đã giao', 'completed'=>'Hoàn thành'] as $key=>$label)
                <option value="{{ $key }}" @selected(request('order_status') === $key)>{{ $label }}</option>
            @endforeach
        </select></div>
        <div class="col-md-3"><label class="form-label">Xác nhận chi phí</label><select class="form-select" name="confirmation"><option value="">Tất cả</option>
            @foreach(['none'=>'Chưa gửi yêu cầu', 'running'=>'Chờ xác nhận', 'revision'=>'Cần điều chỉnh', 'confirmed'=>'Đã xác nhận', 'rejected'=>'Đã từ chối'] as $key=>$label)
                <option value="{{ $key }}" @selected(request('confirmation') === $key)>{{ $label }}</option>
            @endforeach
        </select></div>
        <div class="col-md-2"><label class="form-label">Thanh toán</label><select class="form-select" name="payment"><option value="">Tất cả</option>
            @foreach(['none'=>'Chưa gửi thanh toán', 'pending'=>'Chờ thanh toán', 'paid'=>'Đã thanh toán', 'rejected'=>'Thanh toán bị từ chối'] as $key=>$label)
                <option value="{{ $key }}" @selected(request('payment') === $key)>{{ $label }}</option>
            @endforeach
        </select></div>
        <div class="col-md-1 align-self-end"><button class="btn btn-primary">Lọc</button></div>
    </form>
    <div class="shipping-expense-toolbar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <form method="GET" action="{{ route('shipping-expenses.index') }}">
                @foreach(request()->except(['page', 'per_page']) as $key => $value)
                    @if(is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <select name="per_page" class="form-select" aria-label="Số đơn mỗi trang" onchange="this.form.submit()">
                    @foreach([20, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($orders->perPage() === $size)>{{ $size }} đơn</option>
                    @endforeach
                </select>
            </form>
            {{ $orders->links() }}
            <span class="text-muted">{{ number_format($orders->total(), 0, ',', '.') }} đơn · Trang {{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span>
        </div>
        <button type="button" class="btn btn-primary" id="openExpenseRequest" disabled>Tạo yêu cầu xác nhận chi phí ship</button>
    </div>
    <form method="POST" action="{{ route('shipping-expenses.store') }}" class="process-card" id="shippingExpenseCreate" enctype="multipart/form-data">
        @csrf
        <div class="table-responsive"><table class="process-table"><thead><tr><th>Chọn</th><th>Khách hàng / Đơn hàng</th><th aria-sort="{{ request('sort', 'date') === 'date' ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a class="shipping-sort-link" href="{{ route('shipping-expenses.index', array_merge(request()->except('page'), ['sort' => 'date', 'direction' => request('sort', 'date') === 'date' && request('direction', 'desc') === 'asc' ? 'desc' : 'asc'])) }}">Ngày giao <span aria-hidden="true">{{ request('sort', 'date') === 'date' ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th><th aria-sort="{{ request('sort', 'date') === 'status' ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a class="shipping-sort-link" href="{{ route('shipping-expenses.index', array_merge(request()->except('page'), ['sort' => 'status', 'direction' => request('sort', 'date') === 'status' && request('direction', 'desc') === 'asc' ? 'desc' : 'asc'])) }}">Trạng thái <span aria-hidden="true">{{ request('sort', 'date') === 'status' ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th><th aria-sort="{{ request('sort', 'date') === 'confirmation' ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a class="shipping-sort-link" href="{{ route('shipping-expenses.index', array_merge(request()->except('page'), ['sort' => 'confirmation', 'direction' => request('sort', 'date') === 'confirmation' && request('direction', 'desc') === 'asc' ? 'desc' : 'asc'])) }}">Xác nhận <span aria-hidden="true">{{ request('sort', 'date') === 'confirmation' ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th><th aria-sort="{{ request('sort', 'date') === 'payment' ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a class="shipping-sort-link" href="{{ route('shipping-expenses.index', array_merge(request()->except('page'), ['sort' => 'payment', 'direction' => request('sort', 'date') === 'payment' && request('direction', 'desc') === 'asc' ? 'desc' : 'asc'])) }}">Thanh toán <span aria-hidden="true">{{ request('sort', 'date') === 'payment' ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th><th>Phí đề nghị</th><th>Diễn giải</th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr>
                    <td><input type="checkbox" class="form-check-input expense-select" aria-label="Chọn đơn {{ $order->code }}" @disabled(!$definition || !$order->expense_selectable)></td>
                    <td>
                        @include('processes.shipping-order-identity', ['order' => $order])
                        <input type="hidden" data-expense-input name="items[{{ $loop->index }}][order_id]" value="{{ $order->id }}" disabled></td>
                    <td class="text-nowrap expense-delivery">{{ $order->delivered_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="text-nowrap">{{ $order->status === 'completed' ? 'Hoàn thành' : 'Đã giao' }}</td>
                    <td class="text-nowrap">
                        @if($order->expense_claim_id)
                            <a href="{{ route('shipping-expenses.show', $order->expense_claim_id) }}">{{ ['running'=>'Chờ xác nhận','revision'=>'Cần điều chỉnh','confirmed'=>'Đã xác nhận','rejected'=>'Đã từ chối'][$order->expense_status] ?? 'Đã gửi yêu cầu' }} #{{ $order->expense_claim_id }}</a>
                        @else
                            Chưa gửi yêu cầu
                        @endif
                    </td>
                    <td class="text-nowrap">{{ $order->shipping_fee_transaction_id ? (['approved'=>'Đã thanh toán','pending_approval'=>'Chờ duyệt thanh toán','approved_pending_completion'=>'Chờ thanh toán','rejected'=>'Đã từ chối'][$order->expense_payment_status] ?? 'Đã gửi thanh toán') : 'Chưa gửi thanh toán' }}</td>
                    <td><input type="text" inputmode="numeric" data-money data-expense-input class="form-control text-end" autocomplete="off" name="items[{{ $loop->index }}][amount]" value="{{ number_format($order->shipping_fee, 0, ',', '.') }}" disabled required style="min-width:130px"></td>
                    <td><input data-expense-input class="form-control" name="items[{{ $loop->index }}][note]" maxlength="1000" disabled style="min-width:160px"></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">Không có đơn đã giao phù hợp bộ lọc.</td></tr>
            @endforelse
        </tbody></table></div>
        <p class="small text-muted mt-2">Chọn các đơn trên trang hiện tại. Đơn đang xét duyệt, đã chốt hoặc đã gửi thanh toán không thể tạo yêu cầu trùng.</p>
        <div class="modal fade" id="expenseRequestModal" tabindex="-1" aria-labelledby="expenseRequestTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><h2 class="modal-title" id="expenseRequestTitle">Tạo yêu cầu xác nhận chi phí ship</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                <div class="modal-body"><div class="row g-4">
                    <div class="col-lg-7"><h2>Chi phí các đơn</h2><div class="table-responsive"><table class="process-table"><thead><tr><th>STT</th><th>Khách hàng / Đơn hàng</th><th>Ngày giao</th><th>Chi phí đề nghị</th></tr></thead><tbody id="expenseSelectedOrders"></tbody><tfoot><tr><td colspan="3" class="text-end fw-bold">Tổng chi phí</td><td class="fw-bold text-nowrap" id="expenseSelectedTotal">0đ</td></tr></tfoot></table></div></div>
                    <div class="col-lg-5">
                        <label class="form-label">Ghi chú yêu cầu</label><textarea name="note" class="form-control mb-3" rows="4" maxlength="2000">{{ old('note') }}</textarea>
                        <label class="form-label">Tài liệu đính kèm</label><input type="file" name="attachments[]" class="form-control mb-2" multiple><p class="small text-muted">Tối đa 10 tệp, 20MB/tệp.</p>
                        <button type="submit" class="btn btn-primary" id="submitExpenseRequest">Lưu &amp; Gửi xác nhận</button>
                    </div>
                </div></div>
            </div></div>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const form = document.getElementById('shippingExpenseCreate');
    const openButton = document.getElementById('openExpenseRequest');
    const moneyValue = input => Number(input.value.replace(/\D/g, '') || 0);
    const bindMoney = input => {
        input.addEventListener('input', () => {
            const position = input.selectionStart ?? input.value.length;
            const digitsBefore = input.value.slice(0, position).replace(/\D/g, '').length;
            const digits = input.value.replace(/\D/g, '');
            input.value = digits ? new Intl.NumberFormat('vi-VN').format(Number(digits)) : '';
            input.setCustomValidity(moneyValue(input) > 1000000000 ? 'Phí đề nghị không được vượt quá 1.000.000.000đ.' : '');
            let caret = 0, count = 0;
            while (caret < input.value.length && count < digitsBefore) {
                if (/\d/.test(input.value[caret])) count++;
                caret++;
            }
            input.setSelectionRange(caret, caret);
        });
    };
    form.querySelectorAll('[data-money]').forEach(bindMoney);
    const selected = () => [...form.querySelectorAll('.expense-select:checked')];
    const total = () => {
        const amount = selected().reduce((sum, checkbox) => sum + moneyValue(checkbox.closest('tr').querySelector('[data-money]')), 0);
        document.getElementById('expenseSelectedTotal').textContent = new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
    };
    form.querySelectorAll('.expense-select').forEach(input => input.addEventListener('change', () => {
        input.closest('tr').querySelectorAll('[data-expense-input]').forEach(field => field.disabled = !input.checked);
        openButton.disabled = selected().length === 0;
    }));
    openButton.addEventListener('click', () => {
        if (!selected().length) return;
        const body = document.getElementById('expenseSelectedOrders');
        body.replaceChildren();
        selected().forEach((checkbox, index) => {
            const source = checkbox.closest('tr');
            const row = document.createElement('tr');
            const number = document.createElement('td'); number.textContent = index + 1;
            const identity = document.createElement('td');
            identity.append(source.querySelector('.shipping-order-customer').cloneNode(true), source.querySelector('.shipping-order-meta').cloneNode(true));
            const date = document.createElement('td'); date.className = 'expense-modal-date'; date.textContent = source.querySelector('.expense-delivery').textContent;
            const price = document.createElement('td');
            const original = source.querySelector('[data-money]');
            const input = original.cloneNode(true); input.removeAttribute('name'); input.removeAttribute('data-expense-input'); input.disabled = false;
            bindMoney(input);
            input.addEventListener('input', () => { original.value = input.value; original.setCustomValidity(input.validationMessage); total(); });
            price.append(input); row.append(number, identity, date, price); body.append(row);
        });
        total();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('expenseRequestModal')).show();
    });
    form.addEventListener('submit', event => {
        if (!selected().length) { event.preventDefault(); return; }
        form.querySelectorAll('[data-money][name]:not(:disabled)').forEach(input => input.value = String(moneyValue(input)));
        const button = document.getElementById('submitExpenseRequest');
        button.disabled = true; button.textContent = 'Đang gửi…';
    });
})();
</script>
@endpush
