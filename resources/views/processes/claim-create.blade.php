@extends($layout)
@section('title', 'Quản lý phí ship của tôi')
@include('processes.styles')
@section('content')
<div class="container-fluid process-page">
    <h1>Quản lý phí ship của tôi</h1>
    <p class="text-muted">Xem các đơn đã giao theo lộ trình và gửi yêu cầu xác nhận chi phí ship.</p>
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
                <select name="per_page" class="form-select" aria-label="Số lộ trình mỗi trang" onchange="this.form.submit()">
                    @foreach([20, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($orders->perPage() === $size)>{{ $size }} lộ trình</option>
                    @endforeach
                </select>
            </form>
            {{ $orders->links() }}
            <span class="text-muted">{{ number_format($orders->total(), 0, ',', '.') }} lộ trình · Trang {{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span>
        </div>
        <button type="button" class="btn btn-primary" id="openExpenseRequest" hidden disabled>Tạo yêu cầu xác nhận chi phí ship</button>
    </div>
    <form method="POST" action="{{ route('shipping-expenses.store') }}" class="process-card" id="shippingExpenseCreate" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="route_dispatch_id" id="expenseRouteId" disabled>
        <div class="table-responsive"><table class="process-table"><thead><tr><th>Lộ trình</th><th>Số đơn</th><th>Phí hiện tại</th><th>Thao tác</th></tr></thead><tbody>
        @forelse($routes as $route)
            <tr>
                <td><strong>Lộ trình #{{ $route['id'] }}</strong><div class="text-muted">{{ $route['date'] }}</div></td>
                <td>{{ $route['orders']->count() }} đơn</td>
                <td>{{ number_format($route['total'], 0, ',', '.') }}đ</td>
                <td><div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-route-toggle="route-orders-{{ $route['id'] }}" aria-expanded="false">Xem nhanh đơn</button>
                    <button type="button" class="btn btn-primary" data-route-send="{{ $route['id'] }}" data-route-label="Lộ trình #{{ $route['id'] }} · {{ $route['date'] }}" @disabled(!$definition || !$route['can_submit'])>Gửi xác nhận chi phí ship</button>
                </div></td>
            </tr>
            <tr id="route-orders-{{ $route['id'] }}" data-route-orders="{{ $route['id'] }}" hidden><td colspan="4">
                @include('processes.route-expense-orders', ['route' => $route])
            </td></tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">Không có lộ trình phù hợp bộ lọc.</td></tr>
        @endforelse
        </tbody></table></div>
        <div class="modal fade" id="expenseRequestModal" tabindex="-1" aria-labelledby="expenseRequestTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><h2 class="modal-title" id="expenseRequestTitle">Tạo yêu cầu xác nhận chi phí ship</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                <div class="modal-body"><div id="expenseRouteLabel" class="alert alert-light border fw-bold mb-3"></div><div class="row g-4">
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
    document.querySelectorAll('[data-route-toggle]').forEach(button => button.addEventListener('click', () => {
        const details = document.getElementById(button.dataset.routeToggle);
        details.hidden = !details.hidden;
        button.setAttribute('aria-expanded', String(!details.hidden));
        button.textContent = details.hidden ? 'Xem nhanh đơn' : 'Thu gọn';
    }));
    document.querySelectorAll('[data-route-send]').forEach(button => button.addEventListener('click', () => {
        form.querySelectorAll('.expense-select').forEach(input => {
            input.checked = !input.disabled && input.closest('[data-route-orders]').dataset.routeOrders === button.dataset.routeSend;
            input.dispatchEvent(new Event('change'));
        });
        const routeInput = document.getElementById('expenseRouteId');
        routeInput.value = button.dataset.routeSend; routeInput.disabled = false;
        document.getElementById('expenseRouteLabel').textContent = button.dataset.routeLabel;
        openButton.click();
    }));
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
        selected().forEach(input => {
            const details = input.closest('[data-route-orders]');
            details.hidden = false;
            const toggle = document.querySelector(`[data-route-toggle="${details.id}"]`);
            toggle?.setAttribute('aria-expanded', 'true');
            if (toggle) toggle.textContent = 'Thu gọn';
        });
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
