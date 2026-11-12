@extends(auth()->user()->isAdmin() ? 'layouts.admin' : (auth()->user()->hasRole(['account','accountant','accounting']) ? 'layouts.accounting' : 'layouts.site'))
@section('title', 'Công nợ & cảnh báo đơn')
@section('content')
<div class="debt-page">
    <header class="debt-page-header">
        <div><h1>Công nợ & cảnh báo đơn</h1><div class="debt-updated">@if($snapshot)Cập nhật {{ \Carbon\Carbon::parse($snapshot['loaded_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}@else Chưa tải dữ liệu Sheet @endif</div></div>
        <div class="debt-header-actions">
            @if($snapshot && !empty($snapshot['issues']))
                @if($canLoad)<a class="debt-reconcile-link" href="#debt-issues" data-open-debt-issues title="{{ number_format($issueTotal, 0, ',', '.') }} đ chưa xác định khách hàng">{{ count($snapshot['issues']) }} khoản cần đối chiếu</a>@else<span class="debt-reconcile-link">Có khoản thu cần đối chiếu</span>@endif
            @endif
            @include('customer-debt.page-tools')
            @if($canLoad)<form method="POST" action="{{ route('customer-debt.load') }}" data-sheet-refresh>@csrf<input type="hidden" name="source" value="google"><button class="btn btn-primary" type="submit">Cập nhật Google Sheet</button></form>@endif
        </div>
    </header>
    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $color)
        @if(session($key))<div class="alert alert-{{ $color }} py-2">{{ session($key) }}</div>@endif
    @endforeach
    @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
    @if(!$snapshot)<div class="alert alert-warning py-2">Chưa có dữ liệu Google Sheet để xác định công nợ.</div>@endif
    <nav class="nav debt-tabs">
        <a class="nav-link {{ $tab === 'orders' ? 'active' : '' }}" href="{{ route('customer-debt.index', ['tab'=>'orders','from'=>$from,'to'=>$to]) }}">Đơn hàng & khách hàng</a>
        <a class="nav-link {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('customer-debt.index', ['tab'=>'all']) }}">Quản lý công nợ chung</a>
    </nav>
    <form method="GET" class="debt-filters">
        <input type="hidden" name="tab" value="{{ $tab }}">
        @if(request('sort') === 'overdue_days')<input type="hidden" name="sort" value="overdue_days"><input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">@endif
        @if($tab === 'orders')
        <div class="debt-filter-field"><label class="form-label" for="debt-from">Ngày giao từ</label><input id="debt-from" type="date" name="from" value="{{ $from }}" class="form-control" required></div>
        <div class="debt-filter-field"><label class="form-label" for="debt-to">Đến ngày</label><input id="debt-to" type="date" name="to" value="{{ $to }}" class="form-control" required></div>
        @endif
        <div class="debt-filter-field debt-filter-search"><label class="form-label" for="debt-search">Mã / tên khách hàng</label><input id="debt-search" name="search" value="{{ request('search') }}" class="form-control" maxlength="150"></div>
        <div class="debt-filter-field"><label class="form-label" for="debt-sale-filter">Sale phụ trách</label><select id="debt-sale-filter" name="sale" class="form-select"><option value="">Tất cả sale</option>@foreach($saleOptions as $saleOption)<option value="{{ $saleOption['key'] }}" @selected(request('sale') === $saleOption['key'])>{{ $saleOption['label'] }}</option>@endforeach</select></div>
        <div class="debt-filter-field"><label class="form-label" for="debt-alert-filter">Cảnh báo</label><select id="debt-alert-filter" name="alert" class="form-select"><option value="">Tất cả</option>@foreach(['overdue'=>'Trễ thanh toán','remind'=>'Nhắc thu tiền','medium'=>'Rủi ro Trung bình','high'=>'Rủi ro Cao','very_high'=>'Rủi ro Rất cao'] as $value=>$label)<option value="{{ $value }}" @selected(request('alert') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="debt-filter-actions"><button class="btn btn-primary">Lọc</button><a class="btn btn-light" href="{{ route('customer-debt.index', ['tab'=>$tab]) }}">Xóa lọc</a> @if($tab === 'orders')<a class="btn btn-light" href="{{ route('customer-debt.index') }}">Hôm nay</a>@endif</div>
    </form>
    <div class="row g-3">
        @if($tab === 'orders')
        <div class="col-lg-6"><div class="card"><div class="card-header">Đơn hàng theo ngày giao · {{ $orders->total() }} đơn</div><div class="table-responsive">
            <table class="table table-hover"><thead><tr><th>Đơn / Ngày giao</th><th>Khách hàng</th><th class="text-end">Giá trị đơn</th><th>Cảnh báo</th></tr></thead><tbody>
            @forelse($orders as $order)
                @php($debt = $customerDebts[$order->customer_id] ?? null)
                <tr><td>{{ $order->code }}<br><small>{{ $order->delivery_date?->format('d/m/Y') }} · {{ $order->status }}</small></td><td>{{ $order->customer?->name ?? $order->recipient_name }}<br><small>{{ $order->customer?->customer_code }}</small></td><td class="text-end">{{ number_format($order->total, 0, ',', '.') }} đ</td><td>
                @if(!$snapshot || !$debt)<span class="badge bg-warning text-dark">Chưa có dữ liệu công nợ</span>
                @elseif($debt['balance'] > 0)<span class="badge bg-danger">Còn nợ {{ number_format($debt['balance'], 0, ',', '.') }} đ</span>
                @elseif($debt['balance'] < 0)<span class="badge bg-info text-dark">Khách trả trước</span>
                @else<span class="badge bg-success">Đã cân đối</span>@endif
                @if($debt)<div class="mt-2">@include('customer-debt.alerts', ['alert'=>$debt['alerts']])</div><br><a href="{{ request()->fullUrlWithQuery(['customer'=>null,'system_customer'=>$order->customer_id,'window'=>'recent','detail_page'=>null,'purchase_page'=>1,'payment_page'=>1,'report_from'=>null,'report_to'=>null]) }}#debt-detail">Xem nhanh 14 ngày</a>@endif
                </td></tr>
            @empty<tr><td colspan="4" class="text-center text-muted">Không có đơn trong khoảng ngày đã chọn.</td></tr>@endforelse
            </tbody></table>
        </div><div class="card-body">{{ $orders->links() }}</div></div></div>
        @endif
        @if($tab === 'orders')
            @include('customer-debt.order-customers')
        @else
        <div class="col-12"><div class="card debt-list"><div class="card-header debt-list-header"><strong>Danh sách công nợ</strong><span>{{ number_format($debts->total(), 0, ',', '.') }} khách hàng</span></div><div class="table-responsive debt-table-scroll">
            <table class="table table-hover debt-table"><thead><tr><th>Khách hàng</th><th>Sale</th><th class="text-end">Doanh thu</th><th class="text-end">Tặng</th><th class="text-end">Đã thanh toán</th><th class="text-end">Công nợ</th><th class="text-end" aria-sort="{{ request('sort') === 'overdue_days' ? (request('direction', 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a class="debt-sort-link" href="{{ request()->fullUrlWithQuery(['sort'=>'overdue_days','direction'=>request('sort') === 'overdue_days' && request('direction', 'desc') === 'desc' ? 'asc' : 'desc','debt_page'=>1]) }}" title="Sắp xếp số ngày quá hạn thanh toán">Trễ TT <span aria-hidden="true">{{ request('sort') === 'overdue_days' ? (request('direction', 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th><th>Ngày mua</th><th>Ngày Thanh toán</th><th>Cảnh báo & hạn mức</th><th></th></tr></thead><tbody>
            @forelse($debts as $debt)
                <tr><td>{{ $debt['name'] }}<br><small>{{ $debt['code'] ?: 'Chưa có mã KH' }}</small>@if($debt['customer_id'] && $debt['matched_by'] === 'name')<br><small class="text-muted">Ghép theo tên duy nhất</small>@endif @if(!$debt['customer_id'])<br><span class="badge bg-warning text-dark">Chưa ghép được khách hệ thống</span>@endif @if($debt['ambiguous'])<br><span class="badge bg-danger">Trùng tên, cần kiểm tra mã</span>@endif
                    @if($debt['system_customer'])<div class="small text-success mt-1" title="{{ $debt['system_customer']->customer_code }} · {{ $debt['system_customer']->name }}">Khách hệ thống: {{ $debt['system_customer']->customer_code }} · {{ $debt['system_customer']->name }}@if($debt['matched_by'] === 'manual') (ghép thủ công)@endif</div>@endif
                    @if($canLoad && (!$debt['customer_id'] || $debt['manual_mapping']))
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 debt-map-button" data-key="{{ $debt['key'] }}" data-name="{{ $debt['name'] }}" data-code="{{ $debt['code'] }}" data-customer="{{ $debt['customer_id'] }}">{{ $debt['customer_id'] ? 'Đổi ghép' : 'Chọn khách để ghép' }}</button>
                        @if($debt['manual_mapping'])<form method="POST" action="{{ route('customer-debt.mapping.destroy') }}" class="d-inline">@csrf @method('DELETE')<input type="hidden" name="source_key" value="{{ $debt['key'] }}"><button class="btn btn-sm btn-link mt-2">Bỏ ghép thủ công</button></form>@endif
                    @endif
                </td>
                    <td class="text-nowrap">{{ $debt['sale_label'] }}@if($debt['sale_origin'] === 'sheet')<div class="small text-muted">Theo Sheet</div>@endif</td>
                    <td class="text-end">{{ number_format($debt['sales'], 0, ',', '.') }}</td><td class="text-end">{{ number_format($debt['gifts'], 0, ',', '.') }}</td><td class="text-end">{{ number_format($debt['payments'], 0, ',', '.') }}</td><td class="text-end {{ $debt['balance'] > 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($debt['balance'], 0, ',', '.') }} đ</td>
                    <td class="text-end debt-overdue-days {{ ($debt['alerts']['overdue_days'] ?? 0) > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ isset($debt['alerts']['overdue_days']) ? $debt['alerts']['overdue_days'].' ngày' : '—' }}</td>
                    <td class="text-nowrap">{{ $debt['last_purchase_date'] ? \Carbon\Carbon::parse($debt['last_purchase_date'])->format('d/m/Y') : 'Chưa có' }}</td>
                    <td class="text-nowrap">{{ $debt['last_payment_date'] ? \Carbon\Carbon::parse($debt['last_payment_date'])->format('d/m/Y') : 'Chưa có' }}</td>
                    <td class="debt-alert-cell">@include('customer-debt.alerts', ['alert'=>$debt['alerts'], 'compact'=>true])
                        <div class="debt-policy-inline"><div class="debt-terms" title="Số Bill được phép nợ · Trần nợ · Hạn thanh toán từ ngày giao hàng">{{ $debt['policy']['allowed_orders'] }} Bill · {{ number_format($debt['policy']['credit_limit'], 0, ',', '.') }} đ · {{ $debt['policy']['due_days'] }} ngày</div>

                        <button type="button" class="btn btn-link btn-sm debt-policy-button" data-key="{{ $debt['key'] }}" data-name="{{ $debt['system_customer']?->name ?? $debt['name'] }}" data-orders="{{ $debt['policy']['allowed_orders'] }}" data-limit="{{ $debt['policy']['credit_limit'] }}" data-days="{{ $debt['policy']['due_days'] }}" data-version="{{ $debt['policy']['edit_version'] }}" @disabled($debt['policy']['staff_edit_locked'] && !auth()->user()->isAdmin())>Sửa tiêu chí</button>
                        @if($debt['policy']['staff_edit_locked'])<span class="small text-muted">Đã khóa</span>@endif
                        @if(auth()->user()->isAdmin() && $debt['policy']['staff_edit_locked'])<form method="POST" action="{{ route('customer-debt.policy.unlock') }}">@csrf<input type="hidden" name="source_key" value="{{ $debt['key'] }}"><button class="btn btn-link btn-sm" type="submit">Mở khóa 1 lần</button></form>@endif
                        </div>
                    </td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrlWithQuery(['customer'=>$debt['key'],'system_customer'=>null,'window'=>$tab === 'orders' ? 'recent' : 'all','detail_page'=>null,'purchase_page'=>1,'payment_page'=>1,'report_from'=>null,'report_to'=>null]) }}#debt-detail">{{ $tab === 'orders' ? 'Xem nhanh' : 'Chi tiết' }}</a></td></tr>
            @empty<tr><td colspan="11" class="text-center text-muted">Chưa có dữ liệu khách hàng phù hợp.</td></tr>@endforelse
            </tbody></table>
        </div><div class="card-body debt-list-footer">{{ $debts->links() }}</div></div></div>
        @endif
    </div>
    @if($selected)
        @include('customer-debt.detail-report')
    @endif
    @if($canLoad && $issueRows->total())
    <details id="debt-issues" class="card mt-3 debt-issues" @if(request()->filled('issue_page')) open @endif><summary class="card-header">Đối chiếu khoản thu · {{ $issueRows->total() }} khoản · {{ number_format($issueTotal, 0, ',', '.') }} đ</summary>
        <div class="card-body">Bổ sung tên / mã khách tại đúng dòng trong tab Dongtienhoatdong rồi tải lại. Hệ thống giữ nguyên nội dung nguồn để đối chiếu.</div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Dòng nguồn</th><th>Ngày</th><th>Nội dung</th><th class="text-end">Tiền thu</th></tr></thead><tbody>
        @foreach($issueRows as $issue)<tr><td><a href="https://docs.google.com/spreadsheets/d/{{ config('services.google_sheets.debt_spreadsheet_id') }}/edit#gid={{ config('services.google_sheets.debt_payment_sheet_id') }}&range=A{{ $issue['row'] }}:H{{ $issue['row'] }}" target="_blank" rel="noopener">{{ $issue['row'] }}</a></td><td>{{ $issue['date'] }}</td><td>{{ $issue['note'] }}</td><td class="text-end">{{ number_format($issue['amount'], 0, ',', '.') }} đ</td></tr>@endforeach
        </tbody></table></div><div class="card-body">{{ $issueRows->links() }}</div>
    </details>
    @endif
</div>
<dialog id="debt-policy-dialog" aria-labelledby="debt-policy-title" style="width:min(550px,95vw);max-height:90vh;overflow:auto;border:1px solid #cbd5e1;border-radius:12px;padding:24px;">
    <div class="d-flex justify-content-between align-items-center mb-3"><h5 id="debt-policy-title" class="mb-0">Sửa nhanh tiêu chí công nợ</h5><button type="button" id="debt-policy-close" class="btn btn-sm btn-light">Đóng</button></div>
    <p id="debt-policy-name" class="fw-bold"></p>
    <form method="POST" action="{{ route('customer-debt.policy') }}">@csrf @method('PATCH')
        <input type="hidden" id="debt-policy-key" name="source_key"><input type="hidden" id="debt-policy-version" name="policy_version">
        <label for="debt-policy-orders" class="form-label">Số Bill được phép nợ</label><input id="debt-policy-orders" name="allowed_orders" type="number" min="0" max="100000" step="1" class="form-control mb-3" placeholder="Mặc định: 0 Bill">
        <label for="debt-policy-limit" class="form-label">Trần công nợ (đ)</label><input id="debt-policy-limit" name="credit_limit" inputmode="numeric" class="form-control mb-3" placeholder="Mặc định: 0 đ">
        <label for="debt-policy-days" class="form-label">Ngày nợ (từ ngày giao hàng gần nhất)</label><input id="debt-policy-days" name="due_days" type="number" min="0" max="3650" step="1" class="form-control mb-3" placeholder="Mặc định: 1 ngày">
        <p class="small text-muted">Để trống để dùng mặc định: cho nợ 0 Bill, trần nợ 0 đ, hạn thanh toán 1 ngày kể từ ngày giao hàng gần nhất. Nhập hạn thanh toán 0 ngày nếu cần thanh toán trong ngày. Tiêu chí dùng chung cho mọi nguồn đã ghép. Nhân viên lưu một lần rồi khóa; chỉ admin được mở khóa.</p>
        <button type="submit" class="btn btn-primary">Lưu & cập nhật cảnh báo</button>
    </form>
</dialog>
@if($canLoad)
<dialog id="debt-map-dialog" aria-labelledby="debt-map-title" style="width:min(850px,95vw);max-height:90vh;overflow:auto;border:1px solid #cbd5e1;border-radius:12px;padding:24px;">
    <div class="d-flex justify-content-between align-items-center mb-3"><h5 id="debt-map-title" class="mb-0">Ghép khách hàng hệ thống</h5><button type="button" id="debt-map-close" class="btn btn-sm btn-light" aria-label="Đóng">Đóng</button></div>
    <p id="debt-map-source" class="fw-bold"></p>
    <form id="debt-map-search-form" class="d-flex gap-2 align-items-end mb-3">
        <div class="flex-grow-1"><label for="debt-map-search" class="form-label">Tìm theo mã, tên hoặc số điện thoại</label><input id="debt-map-search" type="search" class="form-control" autocomplete="off" maxlength="150" placeholder="Nhập mã, tên hoặc số điện thoại"></div>
        <button class="btn btn-outline-primary" type="submit">Tìm kiếm</button>
    </form>
    <div id="debt-map-status" role="status" aria-live="polite" class="small text-muted mb-2"></div>
    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Mã KH</th><th>Tên khách hàng</th><th>Số điện thoại</th><th>Thao tác</th></tr></thead><tbody id="debt-map-results"></tbody></table></div>
    <nav id="debt-map-pages" aria-label="Phân trang khách hàng" class="d-flex flex-wrap gap-2 align-items-center justify-content-center mb-3"></nav>
    <form id="debt-map-form" method="POST" action="{{ route('customer-debt.mapping') }}">@csrf
        <input type="hidden" id="debt-map-key" name="source_key">
        <input type="hidden" id="debt-map-customer" name="customer_id">
        <div id="debt-map-selected" class="alert alert-info py-2" hidden></div>
        <p class="small text-muted">Nhiều tên hoặc mã nguồn có thể ghép vào cùng một khách hệ thống. Cảnh báo theo đơn sẽ cộng công nợ của tất cả nguồn đã ghép.</p>
        <button id="debt-map-save" class="btn btn-primary" type="submit" disabled>Lưu ghép khách hàng</button>
    </form>
</dialog>
@endif
@endsection

@if($canLoad)
@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('debt-map-dialog');
    const search = document.getElementById('debt-map-search');
    const results = document.getElementById('debt-map-results');
    const pages = document.getElementById('debt-map-pages');
    const status = document.getElementById('debt-map-status');
    const customerId = document.getElementById('debt-map-customer');
    const selected = document.getElementById('debt-map-selected');
    const save = document.getElementById('debt-map-save');
    let controller;
    let debounce;
    let requestNumber = 0;
    const textCell = value => {
        const cell = document.createElement('td');
        cell.textContent = value || '—';
        return cell;
    };
    const selectCustomer = customer => {
        customerId.value = customer.id;
        selected.textContent = `Đã chọn: ${customer.customer_code || 'Chưa có mã'} · ${customer.name}${customer.phone ? ' · ' + customer.phone : ''}`;
        selected.hidden = false;
        save.disabled = false;
        results.querySelectorAll('tr[data-id]').forEach(row => {
            const active = row.dataset.id === String(customer.id);
            row.classList.toggle('table-primary', active);
            const button = row.querySelector('button');
            button.textContent = active ? 'Đã chọn' : 'Chọn khách này';
            button.setAttribute('aria-pressed', String(active));
        });
    };
    const loadCustomers = async (page = 1) => {
        clearTimeout(debounce);
        if (controller) controller.abort();
        controller = new AbortController();
        const number = ++requestNumber;
        results.replaceChildren();
        pages.replaceChildren();
        status.textContent = 'Đang tải danh sách khách hàng…';
        results.setAttribute('aria-busy', 'true');
        try {
            const url = new URL(@json(route('customer-debt.customers')), window.location.href);
            url.searchParams.set('search', search.value.trim());
            url.searchParams.set('page', page);
            const response = await fetch(url, {signal: controller.signal, headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) throw new Error(response.status === 401 || response.status === 403 ? 'Phiên đăng nhập hoặc quyền truy cập không hợp lệ. Vui lòng tải lại trang.' : 'Không tải được danh sách khách hàng. Hãy thử lại.');
            const data = await response.json();
            if (number !== requestNumber || !dialog.open) return;
            data.customers.forEach(customer => {
                const row = document.createElement('tr');
                row.dataset.id = customer.id;
                row.append(textCell(customer.customer_code), textCell(customer.name), textCell(customer.phone));
                const action = document.createElement('td');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-primary';
                const active = String(customer.id) === customerId.value;
                row.classList.toggle('table-primary', active);
                button.textContent = active ? 'Đã chọn' : 'Chọn khách này';
                button.setAttribute('aria-pressed', String(active));
                button.addEventListener('click', () => selectCustomer(customer));
                action.append(button);
                row.append(action);
                results.append(row);
            });
            const pagination = data.pagination;
            status.textContent = pagination.total ? `Hiển thị ${pagination.from}–${pagination.to} / ${pagination.total} khách hàng` : 'Không tìm thấy khách hàng phù hợp.';
            const addPage = (label, pageNumber, disabled = false, current = false) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = current ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-secondary';
                button.textContent = label;
                button.disabled = disabled;
                if (current) button.setAttribute('aria-current', 'page');
                button.addEventListener('click', () => loadCustomers(pageNumber));
                pages.append(button);
            };
            if (pagination.last_page > 1) {
                addPage('Trước', page - 1, page <= 1);
                const first = Math.max(1, Math.min(page - 2, pagination.last_page - 4));
                for (let index = first; index <= Math.min(pagination.last_page, first + 4); index++) addPage(String(index), index, index === page, index === page);
                addPage('Sau', page + 1, page >= pagination.last_page);
                const info = document.createElement('span');
                info.className = 'small text-muted';
                info.textContent = `Trang ${page}/${pagination.last_page}`;
                pages.append(info);
            }
        } catch (error) {
            if (error.name === 'AbortError' || number !== requestNumber || !dialog.open) return;
            status.textContent = error.message;
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'btn btn-sm btn-outline-primary';
            retry.textContent = 'Thử lại';
            retry.addEventListener('click', () => loadCustomers(page));
            pages.append(retry);
        } finally {
            if (number === requestNumber) results.removeAttribute('aria-busy');
        }
    };
    document.querySelectorAll('.debt-map-button').forEach(button => button.addEventListener('click', () => {
        document.getElementById('debt-map-key').value = button.dataset.key;
        document.getElementById('debt-map-source').textContent = `${button.dataset.code || 'Chưa có mã'} · ${button.dataset.name}`;
        search.value = '';
        customerId.value = '';
        selected.hidden = true;
        selected.textContent = '';
        save.disabled = true;
        dialog.showModal();
        search.focus();
        loadCustomers();
    }));
    search.addEventListener('input', () => {
        clearTimeout(debounce);
        if (controller) controller.abort();
        requestNumber++;
        results.replaceChildren();
        pages.replaceChildren();
        status.textContent = 'Đang tìm khách hàng…';
        debounce = setTimeout(() => loadCustomers(), 300);
    });
    document.getElementById('debt-map-search-form').addEventListener('submit', event => {
        event.preventDefault();
        loadCustomers();
    });
    document.getElementById('debt-map-form').addEventListener('submit', event => {
        if (!customerId.value) event.preventDefault();
        else { save.disabled = true; save.textContent = 'Đang lưu…'; }
    });
    dialog.addEventListener('close', () => {
        clearTimeout(debounce);
        if (controller) controller.abort();
        requestNumber++;
    });
    document.getElementById('debt-map-close').addEventListener('click', () => dialog.close());
})();
</script>
@endpush
@endif

@push('styles')
<style>
#debt-map-dialog::backdrop { background: rgba(15, 23, 42, .45); }
#debt-map-results button { white-space: nowrap; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('debt-policy-dialog');
    document.querySelectorAll('.debt-policy-button').forEach(button => button.addEventListener('click', () => {
        document.getElementById('debt-policy-key').value = button.dataset.key;
        document.getElementById('debt-policy-version').value = button.dataset.version;
        document.getElementById('debt-policy-name').textContent = button.dataset.name;
        document.getElementById('debt-policy-orders').value = button.dataset.orders;
        document.getElementById('debt-policy-limit').value = button.dataset.limit === '' ? '' : Number(button.dataset.limit).toLocaleString('vi-VN');
        document.getElementById('debt-policy-days').value = button.dataset.days;
        dialog.showModal();
    }));
    document.getElementById('debt-policy-close').addEventListener('click', () => dialog.close());
    const tools = document.querySelector('.debt-tools');
    document.addEventListener('click', event => { if (tools && tools.open && !tools.contains(event.target)) tools.open = false; });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && tools) tools.open = false; });
    if (window.location.hash === '#debt-issues') {
        const issues = document.getElementById('debt-issues');
        if (issues) issues.open = true;
    }
    document.querySelectorAll('[data-open-debt-issues]').forEach(link => link.addEventListener('click', () => {
        const issues = document.getElementById('debt-issues');
        if (issues) issues.open = true;
    }));
    document.querySelectorAll('form[data-sheet-refresh]').forEach(form => form.addEventListener('submit', () => {
        const button = form.querySelector('button');
        button.disabled = true;
        button.textContent = 'Đang cập nhật dữ liệu từ Google Sheet…';
    }));
})();
</script>
@endpush
@push('styles')
<style>
#debt-policy-dialog::backdrop { background: rgba(15,23,42,.45); }
#debt-alert-filter, #debt-sale-filter { display:block !important; }
#debt-alert-filter + .nice-select, #debt-sale-filter + .nice-select { display:none !important; }
</style>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customer-debt.css') }}?v={{ filemtime(public_path('css/customer-debt.css')) }}">
@endpush
