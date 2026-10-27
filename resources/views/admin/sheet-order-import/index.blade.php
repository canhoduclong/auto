@extends('layouts.admin')
@section('title', 'Import Đơn')
@section('content')
<div class="container-fluid py-3">
    <h3>Import Đơn từ dữ liệu Excel / Google Sheet</h3>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card"><div class="card-body">
        <p>Sao chép vùng dữ liệu gồm dòng tiêu đề từ Excel hoặc Google Sheet rồi dán bên dưới. Không cần cấp quyền truy cập Sheet.</p>
        <form method="POST" action="{{ route('admin.sheet-order-import.load') }}" class="row g-3 align-items-end">@csrf
            <div class="col-md-6">
                <label for="initial-sale-id" class="form-label fw-semibold">Sale phụ trách đơn import <span class="text-danger">*</span></label>
                <select id="initial-sale-id" name="sale_id" class="form-select" required>
                    <option value="">Chọn sale để gán các đơn import</option>
                    @foreach($sales as $sale)
                        <option value="{{ $sale->id }}" @selected((string) old('sale_id', $selection['sale_id'] ?? $snapshot['sale_id'] ?? '') === (string) $sale->id)>{{ $sale->name }}</option>
                    @endforeach
                </select>
                <div class="form-text">Chọn sale trước khi đọc dữ liệu. Bạn có thể thay đổi ở bước xem trước.</div>
            </div>
            <div class="col-12"><label for="source-text" class="form-label">Dữ liệu đơn hàng (có dòng tiêu đề)</label>
                <textarea id="source-text" name="source_text" class="form-control font-monospace" rows="8" required maxlength="2000000" placeholder="Ngày tháng&#9;Tháng&#9;Mã KH&#9;Khách hàng&#9;NVKD&#9;DVT&#9;SL&#9;Kg/con&#9;Tổng&#9;Đơn giá&#9;Tổng tiền&#10;04/05/2026&#9;5&#9;CT7188&#9;Anh Huyên lông&#9;CTY&#9;bộ lông&#9;1295&#9;1,00&#9;1295&#9;11&#9;14.245.000">{{ old('source_text', $snapshot['source_text'] ?? '') }}</textarea>
                <div class="form-text">Giữ các cột cách nhau bằng tab khi sao chép ô. Hỗ trợ dấu phẩy thập phân và dấu chấm phân cách hàng nghìn.</div>
            </div>
            <div class="col-md-4"><label class="form-label" for="from-date">Từ ngày (ngày trên Sheet)</label><input id="from-date" type="date" name="from_date" class="form-control" value="{{ old('from_date', $snapshot['from_date'] ?? now()->startOfMonth()->toDateString()) }}" required></div>
            <div class="col-md-4"><label class="form-label" for="to-date">Đến ngày</label><input id="to-date" type="date" name="to_date" class="form-control" value="{{ old('to_date', $snapshot['to_date'] ?? now()->toDateString()) }}" required></div>
            <div class="col-md-4"><button class="btn btn-primary">Đọc dữ liệu đã dán</button></div>
        </form>
    </div></div>
    @if($snapshot)
    <div class="card"><div class="card-body">
        <h5>Dữ liệu đã tải: {{ count($snapshot['rows']) }} dòng, {{ $groups->count() }} khách</h5>
        <p class="text-muted">Các dòng cùng khách và ngày được gộp thành một đơn. Ngày đã import được tự động bỏ qua. Bản tải có hiệu lực 1 giờ.</p>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Khách trong dữ liệu dán</th><th>Dòng hàng</th><th>Số đơn</th><th>Chưa import</th></tr></thead><tbody>
        @forelse($groups as $group)<tr><td>{{ $group['name'] }}</td><td>{{ $group['rows'] }}</td><td>{{ $group['orders'] }}</td><td>{{ $group['remaining'] }}</td></tr>@empty<tr><td colspan="4">Không có đơn trong khoảng ngày đã chọn.</td></tr>@endforelse
        </tbody></table></div>
        @if($groups->sum('remaining') > 0)
        <form method="POST" action="{{ route('admin.sheet-order-import.review') }}" class="row g-3">@csrf
            <input type="hidden" name="preview" value="{{ $previewToken }}">
            <div class="col-md-6"><label for="source-customer" class="form-label">Khách trong dữ liệu dán cần import</label><select id="source-customer" name="source_customer" class="form-select" required><option value="">Chọn khách nguồn</option>@foreach($groups as $group)@if($group['remaining'])<option value="{{ $group['key'] }}" @selected(old('source_customer', $selection['source_customer'] ?? '') === $group['key'])>{{ $group['name'] }} — {{ $group['remaining'] }} đơn chưa nhập</option>@endif @endforeach</select></div>
            <div class="col-md-6">
                <label for="customer-search" class="form-label">Bổ sung đơn vào khách trong hệ thống (khách B)</label>
                <input type="hidden" name="customer_id" id="target-customer" value="{{ $selectedCustomer?->id }}">
                <div class="alert alert-info py-2 mb-2" id="selected-customer" aria-live="polite">{{ $selectedCustomer ? 'Đã chọn: '.$selectedCustomer->name.' · '.($selectedCustomer->customer_code ?: '#'.$selectedCustomer->id) : 'Chưa chọn khách B' }}</div>
                <input type="search" id="customer-search" class="form-control mb-2" placeholder="Tìm tên khách, mã KH hoặc tên cũ" maxlength="150" autocomplete="off">
                <div id="customer-search-status" class="small text-muted mb-2" role="status" aria-live="polite"></div>
                <div id="customer-search-results" class="list-group" style="max-height:320px;overflow-y:auto;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="customers-prev" disabled>Trang trước</button>
                    <span id="customers-page" class="small text-muted"></span>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="customers-next" disabled>Trang sau</button>
                </div>
                <div class="form-text">Chỉ hiển thị khách đang do sale đã chọn quản lý, mỗi trang 20 khách. Tên khách nguồn được lưu vào Tên cũ của khách B.</div>
            </div>
            <div class="col-md-4"><label for="sale-id" class="form-label">Sale phụ trách đơn import</label><select id="sale-id" name="sale_id" class="form-select" required><option value="">Chọn sale</option>@foreach($sales as $sale)<option value="{{ $sale->id }}" @selected((string) old('sale_id', $selection['sale_id'] ?? $snapshot['sale_id'] ?? '') === (string) $sale->id)>{{ $sale->name }}</option>@endforeach</select><div class="form-text">Sale được chọn sẽ phụ trách toàn bộ đơn trong lần import này, thay cho NVKD trong dữ liệu dán.</div></div>
            <div class="col-md-4"><label for="warehouse-id" class="form-label">Kho</label><select id="warehouse-id" name="warehouse_id" class="form-select" required><option value="">Chọn kho</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $selection['warehouse_id'] ?? '') === (string) $warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="shipper-id" class="form-label">Shipper (bắt buộc khi đã giao/hoàn tất)</label><select id="shipper-id" name="shipper_id" class="form-select"><option value="">Chưa phân công</option>@foreach($shippers as $shipper)<option value="{{ $shipper->id }}" @selected((string) old('shipper_id', $selection['shipper_id'] ?? '') === (string) $shipper->id)>{{ $shipper->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label for="stage" class="form-label">Trạng thái đơn sau khi import</label><select id="stage" name="stage" class="form-select" required>@foreach(['created' => 'Tạo đơn — chờ duyệt', 'delivered' => 'Đã giao hàng — chờ kế toán', 'completed' => 'Hoàn tất — kế toán đã xác nhận'] as $key => $label)<option value="{{ $key }}" @selected(old('stage', $selection['stage'] ?? 'created') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-12"><p class="small text-muted">Import dữ liệu lịch sử không cộng/trừ tồn kho hiện tại. Đơn chưa ghi nhận thu tiền; “Hoàn tất” xác nhận kế toán và ghi nhận công nợ, không có nghĩa là khách đã thanh toán.</p><button class="btn btn-outline-primary">Xem trước các đơn sẽ tạo</button></div>
        </form>
        @endif
    </div></div>
    @endif
    @isset($review)
    <div class="card border-primary" id="import-review"><div class="card-body">
        <h5>Xem trước: {{ $review['order_count'] }} đơn · {{ count($review['rows']) }} dòng</h5>
        <p>Khách nhận đơn: <strong>{{ $customers->firstWhere('id', $selection['customer_id'])?->name }}</strong> · Sale được gán: <strong>{{ $sales->firstWhere('id', $selection['sale_id'])?->name }}</strong> · Trạng thái: <strong>{{ ['created' => 'Chờ duyệt', 'delivered' => 'Đã giao — chờ kế toán', 'completed' => 'Hoàn tất — kế toán xác nhận'][$selection['stage']] }}</strong></p>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Ngày</th><th>Khách B</th><th>Hàng / DVT</th><th>SL</th><th>Kg/con</th><th>Tổng KL</th><th>Đơn giá</th><th>Thành tiền</th><th>Kiểm tra</th></tr></thead><tbody>
        @foreach($review['rows'] as $row)<tr><td>{{ $row['data']['entry_date'] }}</td><td>{{ $row['data']['customer_name'] }}</td><td>{{ $row['data']['unit'] }}</td><td>{{ $row['data']['quantity'] }}</td><td>{{ $row['data']['unit_weight'] }}</td><td>{{ $row['data']['total_quantity'] }}</td><td>{{ number_format($row['data']['unit_price'] ?? 0) }}</td><td>{{ number_format($row['data']['total_amount']) }}</td><td>@foreach($row['errors'] as $error)<div class="text-danger">{{ $error }}</div>@endforeach @foreach($row['warnings'] as $warning)<div class="text-warning">{{ $warning }}</div>@endforeach</td></tr>@endforeach
        </tbody><tfoot><tr><th colspan="7">Tổng tiền</th><th>{{ number_format(collect($review['rows'])->sum('data.total_amount')) }}</th><td></td></tr></tfoot></table></div>
        @if(!($review['counts']['error'] ?? 0))
        <form method="POST" action="{{ route('admin.sheet-order-import.store') }}" onsubmit="return confirm('Tạo các đơn theo bản xem trước và lưu tên khách nguồn vào Tên cũ?')">@csrf<input type="hidden" name="confirmation" value="{{ $confirmation }}"><button class="btn btn-success">Xác nhận import {{ $review['order_count'] }} đơn</button></form>
        @else<div class="alert alert-danger">Chưa thể import. Vui lòng sửa các lỗi trong dữ liệu nguồn rồi tải lại.</div>@endif
    </div></div>
    @endisset
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sale = document.getElementById('sale-id');
    if (!sale) return;
    const initialSale = document.getElementById('initial-sale-id');
    const selected = document.getElementById('target-customer');
    const selectedLabel = document.getElementById('selected-customer');
    const search = document.getElementById('customer-search');
    const results = document.getElementById('customer-search-results');
    const status = document.getElementById('customer-search-status');
    const previous = document.getElementById('customers-prev');
    const next = document.getElementById('customers-next');
    const pageLabel = document.getElementById('customers-page');
    const review = document.getElementById('import-review');
    let page = 1;
    let lastPage = 1;
    let controller;
    let debounce;
    let sequence = 0;
    function invalidateReview() { if (review) review.hidden = true; }
    function escapeHtml(value) {
        const element = document.createElement('span');
        element.textContent = value ?? '';
        return element.innerHTML;
    }
    function updateSelectedHighlight() {
        results.querySelectorAll('[data-customer-id]').forEach(button => {
            const active = button.dataset.customerId === selected.value;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', String(active));
        });
    }
    async function loadCustomers(targetPage = 1) {
        clearTimeout(debounce);
        controller?.abort();
        const requestSequence = ++sequence;
        previous.disabled = next.disabled = true;
        results.replaceChildren();
        pageLabel.textContent = '';
        if (!sale.value) { status.textContent = 'Chọn sale phụ trách để tải khách.'; return; }
        status.textContent = 'Đang tải khách do sale quản lý…';
        controller = new AbortController();
        try {
            const url = new URL(@json(route('admin.sheet-order-import.customers')), window.location.origin);
            url.search = new URLSearchParams({sale_id: sale.value, search: search.value.trim(), page: targetPage});
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, signal: controller.signal});
            const data = await response.json();
            if (requestSequence !== sequence) return;
            if (!response.ok) throw new Error(data.message || 'Không tải được danh sách khách.');
            page = data.pagination.current_page;
            lastPage = data.pagination.last_page;
            status.textContent = data.pagination.total ? `Tìm thấy ${data.pagination.total} khách.` : 'Không có khách phù hợp do sale này quản lý.';
            pageLabel.textContent = `Trang ${page}/${lastPage}`;
            for (const customer of data.customers) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action text-start';
                button.dataset.customerId = String(customer.id);
                const label = `${customer.name} · ${customer.customer_code || '#' + customer.id}`;
                button.innerHTML = `<strong>${escapeHtml(customer.name)}</strong> · ${escapeHtml(customer.customer_code || '#' + customer.id)}${customer.old_name ? `<div class="small text-break">Tên cũ: ${escapeHtml(customer.old_name)}</div>` : ''}`;
                button.addEventListener('click', () => {
                    selected.value = String(customer.id);
                    selectedLabel.textContent = 'Đã chọn: ' + label;
                    updateSelectedHighlight();
                    invalidateReview();
                });
                results.appendChild(button);
            }
            updateSelectedHighlight();
            previous.disabled = page <= 1;
            next.disabled = page >= lastPage;
        } catch (error) {
            if (error.name === 'AbortError' || requestSequence !== sequence) return;
            status.textContent = 'Không tải được khách: ' + error.message + ' Hãy tìm lại hoặc chọn lại sale để thử.';
        }
    }
    function changeSale() {
        selected.value = '';
        selectedLabel.textContent = 'Chưa chọn khách B';
        search.value = '';
        invalidateReview();
        loadCustomers(1);
    }
    sale.addEventListener('change', () => { initialSale.value = sale.value; changeSale(); });
    initialSale.addEventListener('change', () => { sale.value = initialSale.value; changeSale(); });
    search.addEventListener('input', () => {
        controller?.abort();
        sequence++;
        previous.disabled = next.disabled = true;
        results.replaceChildren();
        status.textContent = 'Đang tìm khách…';
        clearTimeout(debounce);
        debounce = setTimeout(() => loadCustomers(1), 300);
    });
    search.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); loadCustomers(1); } });
    previous.addEventListener('click', () => loadCustomers(page - 1));
    next.addEventListener('click', () => loadCustomers(page + 1));
    sale.form.addEventListener('change', invalidateReview);
    sale.form.addEventListener('submit', event => {
        if (!selected.value) { event.preventDefault(); status.textContent = 'Vui lòng chọn khách B trong danh sách.'; search.focus(); }
    });
    loadCustomers(1);
});
</script>
@endpush
