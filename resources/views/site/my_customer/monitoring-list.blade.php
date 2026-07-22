<style>
    .mcl-page { padding-bottom: 36px; }
    .mcl-title { margin: 0 0 34px; color: #111827; font-size: 1.22rem; font-weight: 800; }
    .mcl-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 16px;
    }
    .mcl-toolbar-left,
    .mcl-toolbar-right,
    .mcl-filter-form,
    .mcl-search-form { display: flex; align-items: center; gap: 8px; }
    .mcl-toolbar .form-select,
    .mcl-toolbar .form-control,
    .mcl-toolbar .btn { min-height: 39px; font-size: .75rem; }
    .mcl-toolbar .btn { display: inline-flex; align-items: center; justify-content: center; gap: 5px; font-weight: 700; white-space: nowrap; }
    .mcl-per-page { width: 166px; }
    .mcl-search-form { width: min(100%, 262px); }
    .mcl-search-form .form-control { min-width: 0; }
    .mcl-search-form .btn { width: 39px; padding: 0; }
    .mcl-view-switch { display: inline-flex; padding: 3px; border: 1px solid #dbe5ef; border-radius: 7px; background: #fff; }
    .mcl-view-switch .btn { min-height: 30px; border: 0; padding: 4px 9px; color: #64748b; }
    .mcl-view-switch .btn.active { background: #e8f5f7; color: #087f5b; }
    .mcl-list { display: grid; gap: 16px; }
    .mcl-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 128px 132px;
        gap: 10px;
        align-items: start;
        position: relative;
    }
    .mcl-card {
        min-width: 0;
        min-height: 106px;
        padding: 13px 15px 11px;
        border: 1px solid #dce4ec;
        border-radius: 7px;
        background: #fff;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .07);
    }
    .mcl-main { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 14px; }
    .mcl-name { color: #0f172a; font-size: 1rem; font-weight: 900; text-transform: uppercase; }
    .mcl-contact { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 5px; color: #52617a; font-size: .7rem; }
    .mcl-phone { color: #0f172a; font-size: .78rem; font-weight: 900; white-space: nowrap; }
    .mcl-row-controls { display: flex; align-items: center; justify-content: flex-end; gap: 6px; padding-top: 2px; }
    .mcl-sort-input { width: 52px; height: 31px; padding: 2px 4px; font-size: .68rem; }
    .mcl-pin,
    .mcl-more { width: 33px; height: 31px; padding: 0; background: #fff; }
    .mcl-pin.is-pinned { color: #fff; border-color: #f59e0b; background: #f59e0b; }
    .mcl-more { border: 1px solid #fdba74; color: #334155; }
    .mcl-more:hover,
    .mcl-more[aria-expanded="true"] { border-color: #f97316; background: #fff7ed; color: #c2410c; }
    .mcl-details {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 14px;
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid #dce4ec;
        color: #52617a;
        font-size: .69rem;
    }
    .mcl-details em { color: #334155; }
    .mcl-updated { margin-left: auto; font-style: italic; }
    .mcl-actions {
        display: none;
        grid-template-columns: 1fr;
        gap: 8px;
        position: relative;
        z-index: 5;
        padding-top: 4px;
    }
    .mcl-row.is-actions-open .mcl-actions { display: grid; }
    .mcl-actions .btn {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border-radius: 6px;
        background: #fff;
        font-size: .7rem;
        font-weight: 800;
    }
    .mcl-actions .btn-success { color: #fff; background: #198754; }
    .mcl-actions::before {
        content: "";
        position: absolute;
        top: 17px;
        left: -17px;
        width: 16px;
        border-top: 1px dashed #f97316;
    }
    .mcl-empty { padding: 45px 20px; border: 1px solid #dce4ec; border-radius: 8px; background: #fff; color: #64748b; text-align: center; }
    .mcl-pagination { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 18px; color: #475569; font-size: .75rem; }
    .mcl-pagination .pagination { margin-bottom: 0; }
    .mcl-pagination .page-link { padding: .32rem .58rem; color: #087f5b; border-color: #86c9ad; }
    .mcl-row.is-compact { grid-template-columns: minmax(0, 1fr) 128px 132px; }
    .mcl-row.is-compact .mcl-card { min-height: 74px; padding-block: 10px; }
    .mcl-row.is-compact .mcl-name { font-size: .9rem; }
    @media (max-width: 991.98px) {
        .mcl-toolbar { align-items: stretch; flex-direction: column; }
        .mcl-toolbar-left, .mcl-toolbar-right { flex-wrap: wrap; }
        .mcl-toolbar-right { justify-content: space-between; }
        .mcl-row, .mcl-row.is-compact { grid-template-columns: minmax(0, 1fr) 128px; }
        .mcl-actions {
            grid-column: 1 / -1;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            padding: 0 0 4px;
        }
        .mcl-actions::before { display: none; }
    }
    @media (max-width: 767.98px) {
        .mcl-title { margin-bottom: 20px; }
        .mcl-toolbar-left, .mcl-toolbar-right, .mcl-filter-form { width: 100%; }
        .mcl-filter-form { flex-wrap: wrap; }
        .mcl-per-page { flex: 1; width: auto; }
        .mcl-search-form { flex: 1; width: auto; }
        .mcl-row, .mcl-row.is-compact { grid-template-columns: minmax(0, 1fr) auto; gap: 7px; }
        .mcl-card { min-height: 0; }
        .mcl-main { grid-template-columns: 1fr; }
        .mcl-phone { white-space: normal; }
        .mcl-row-controls { flex-direction: column; padding-top: 0; }
        .mcl-sort-input { width: 42px; }
        .mcl-details { gap: 8px 12px; }
        .mcl-updated { margin-left: 0; }
        .mcl-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mcl-pagination { align-items: stretch; flex-direction: column; }
    }
</style>

@php
    $queryWithoutPage = request()->except('page');
    $viewUrl = fn (string $mode) => route('pages.my_orders.monitoring', array_merge($queryWithoutPage, ['tab' => 'customers', 'view' => $mode]));
    $sharedFilters = array_filter([
        'tab' => 'customers',
        'view' => $viewMode,
        'sale_id' => $selectedSaleId ?: null,
        'per_page' => $perPage,
    ]);
@endphp

<section class="mcl-page">
    <h1 class="mcl-title">Danh sách khách hàng</h1>

    <div class="mcl-toolbar">
        <div class="mcl-toolbar-left">
            <button type="button" class="btn btn-outline-danger" id="mclBulkDelete" disabled><i class="bi bi-trash"></i> Xóa</button>
            <form method="GET" action="{{ route('pages.my_orders.monitoring') }}" class="mcl-filter-form">
                @foreach($sharedFilters as $name => $value)
                    @if($name !== 'per_page')<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
                @endforeach
                @if($search !== '')<input type="hidden" name="search" value="{{ $search }}">@endif
                <select name="per_page" class="form-select mcl-per-page" onchange="this.form.submit()" aria-label="Số khách hàng mỗi trang">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} Khách / trang</option>
                    @endforeach
                </select>
            </form>
            <div class="mcl-view-switch" aria-label="Kiểu hiển thị">
                <a href="{{ $viewUrl('compact') }}" class="btn btn-sm {{ $viewMode === 'compact' ? 'active' : '' }}" title="View ngắn gọn"><i class="bi bi-view-list"></i> Ngắn gọn</a>
                <a href="{{ $viewUrl('default') }}" class="btn btn-sm {{ $viewMode === 'default' ? 'active' : '' }}" title="View mặc định"><i class="bi bi-card-list"></i> Mặc định</a>
            </div>
        </div>
        <div class="mcl-toolbar-right">
            <form method="GET" action="{{ route('pages.my_orders.monitoring') }}" class="input-group mcl-search-form">
                @foreach($sharedFilters as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                <input name="search" class="form-control" value="{{ $search }}" placeholder="Tìm khách hàng..." aria-label="Tìm khách hàng">
                <button class="btn btn-outline-secondary" type="submit" aria-label="Tìm kiếm"><i class="bi bi-search"></i></button>
            </form>
            <button type="button" class="btn btn-outline-info" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            <a href="{{ route('my_customer.create') }}" class="btn btn-success"><i class="bi bi-plus-circle"></i> Thêm mới</a>
        </div>
    </div>

    <div class="mcl-list">
        @forelse($customers as $customer)
            @php
                $address = $customer->address;
                if (!$address && $customer->addresses->first()) {
                    $fallbackAddress = $customer->addresses->first();
                    $address = implode(', ', array_filter([$fallbackAddress->house_number, $fallbackAddress->street, $fallbackAddress->ward, $fallbackAddress->city]));
                }
                $customerSaleIds = collect([$customer->current_owner_sale_id, $customer->assigned_to, $customer->user_id])
                    ->filter()->map(fn ($id) => (int) $id);
                $canManage = auth()->user()?->hasRole(['admin', 'manager', 'manager_sale', 'director'])
                    || $customerSaleIds->intersect($manageableSaleIds ?? [auth()->id()])->isNotEmpty();
                $statusLabel = match((string) $customer->customer_status) {
                    'free' => 'free',
                    'ordered' => 'ordered',
                    default => (string) ($customer->status ?: 'active'),
                };
            @endphp
            <article class="mcl-row {{ $viewMode === 'compact' ? 'is-compact' : '' }}" data-customer-row data-customer-id="{{ $customer->id }}">
                <div class="mcl-card">
                    <div class="mcl-main">
                        <div>
                            <div class="mcl-name">{{ $customer->name }}</div>
                            <div class="mcl-contact">
                                @if($address)<span><i class="bi bi-geo-alt me-1"></i>{{ $address }}</span>@endif
                                @if($customer->email)<span><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</span>@endif
                            </div>
                        </div>
                        @if($customer->phone)<div class="mcl-phone">{{ $customer->phone }}</div>@endif
                    </div>

                    @if($viewMode === 'default')
                        <div class="mcl-details">
                            @if($canManage)<input type="checkbox" class="form-check-input mcl-check" value="{{ $customer->id }}" aria-label="Chọn {{ $customer->name }}">@endif
                            <em>Mã KH: <strong>{{ $customer->customer_code ?: '#'.$customer->id }}</strong> - Trạng thái: <strong>{{ $statusLabel }}</strong></em>
                            @if($customer->production)<span>Sản lượng: <strong>{{ $customer->production }}</strong></span>@endif
                            @if($customer->size)<span>Size: <strong>{{ $customer->size }}</strong></span>@endif
                            <span>Đơn: <strong>{{ (int) $customer->orders_count }}</strong></span>
                            <span>Công nợ: <strong>{{ number_format((float) ($customer->total_debt ?? 0), 0, ',', '.') }} đ</strong></span>
                            <span class="mcl-updated">{{ $customer->updated_at?->format('d/m/Y') }}</span>
                        </div>
                    @elseif($canManage)
                        <input type="checkbox" class="form-check-input mcl-check mt-2" value="{{ $customer->id }}" aria-label="Chọn {{ $customer->name }}">
                    @endif
                </div>

                <div class="mcl-row-controls">
                    @if($canManage)
                        <input type="number" class="form-control form-control-sm mcl-sort-input" min="0" value="{{ (int) ($customer->sort_order ?? 0) }}" data-sort-order title="Thứ tự hiển thị" aria-label="Thứ tự hiển thị của {{ $customer->name }}">
                        <button type="button" class="btn btn-sm btn-outline-warning mcl-pin {{ $customer->is_pinned ? 'is-pinned' : '' }}" data-pin data-pinned="{{ $customer->is_pinned ? '1' : '0' }}" title="Ghim khách hàng"><i class="bi {{ $customer->is_pinned ? 'bi-star-fill' : 'bi-star' }}"></i></button>
                    @endif
                    <button type="button" class="mcl-more" data-customer-more aria-expanded="false" aria-controls="customer-actions-{{ $customer->id }}" title="Thao tác với {{ $customer->name }}"><i class="bi bi-three-dots-vertical"></i></button>
                </div>

                <div class="mcl-actions" id="customer-actions-{{ $customer->id }}" data-customer-actions>
                    @if($canManage)<a href="{{ route('my_customer.edit', $customer) }}" class="btn btn-outline-success"><i class="bi bi-pencil"></i> Sửa</a>@endif
                    <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-outline-info"><i class="bi bi-eye"></i> Chi tiết</a>
                    <a href="{{ route('my_customer.show', ['customer' => $customer, 'tab' => 'payments']) }}" class="btn btn-outline-secondary"><i class="bi bi-cash"></i> Thanh toán</a>
                    <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-success"><i class="bi bi-check2-circle"></i> Lên đơn</a>
                    @if($canManage)<button type="button" class="btn btn-outline-danger" data-delete-customer><i class="bi bi-trash"></i> Xóa</button>@endif
                </div>
            </article>
        @empty
            <div class="mcl-empty"><i class="bi bi-people fs-2 d-block mb-2"></i>Không tìm thấy khách hàng phù hợp.</div>
        @endforelse
    </div>

    @if($customers->hasPages() || $customers->total() > 0)
        <div class="mcl-pagination">
            <span>Trang {{ $customers->currentPage() }}/{{ max(1, $customers->lastPage()) }}</span>
            {{ $customers->links('pagination::bootstrap-5') }}
        </div>
    @endif
</section>

<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const bulkDelete = document.getElementById('mclBulkDelete');
    const selected = () => Array.from(document.querySelectorAll('.mcl-check:checked'));
    const refreshBulkState = () => { if (bulkDelete) bulkDelete.disabled = selected().length === 0; };
    const closeActionMenus = exceptRow => {
        document.querySelectorAll('[data-customer-row].is-actions-open').forEach(row => {
            if (row === exceptRow) return;
            row.classList.remove('is-actions-open');
            row.querySelector('[data-customer-more]')?.setAttribute('aria-expanded', 'false');
        });
    };
    const destroyCustomer = async id => {
        const response = await fetch(@json(url('/my-customer')) + `/${id}`, {
            method: 'DELETE',
            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
        });
        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            throw new Error(payload.message || 'Không thể xóa khách hàng.');
        }
    };
    const saveSort = async (row, payload) => {
        const response = await fetch(@json(url('/my-customer')) + `/${row.dataset.customerId}/sort-settings`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(payload),
        });
        if (!response.ok) throw new Error('Không thể lưu thứ tự khách hàng.');
        return response.json();
    };

    document.addEventListener('change', async event => {
        if (event.target.matches('.mcl-check')) refreshBulkState();
        if (event.target.matches('[data-sort-order]')) {
            try { await saveSort(event.target.closest('[data-customer-row]'), {sort_order: Number(event.target.value) || 0}); }
            catch (error) { window.alert(error.message); }
        }
    });
    document.addEventListener('click', async event => {
        const moreButton = event.target.closest('[data-customer-more]');
        if (moreButton) {
            const row = moreButton.closest('[data-customer-row]');
            const willOpen = !row.classList.contains('is-actions-open');
            closeActionMenus(row);
            row.classList.toggle('is-actions-open', willOpen);
            moreButton.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            return;
        }

        const openRow = event.target.closest('[data-customer-row].is-actions-open');
        if (!openRow) closeActionMenus();

        const pin = event.target.closest('[data-pin]');
        if (pin) {
            try {
                const payload = await saveSort(pin.closest('[data-customer-row]'), {is_pinned: pin.dataset.pinned !== '1'});
                pin.dataset.pinned = payload.is_pinned ? '1' : '0';
                pin.classList.toggle('is-pinned', payload.is_pinned);
                pin.innerHTML = `<i class="bi ${payload.is_pinned ? 'bi-star-fill' : 'bi-star'}"></i>`;
            } catch (error) { window.alert(error.message); }
            return;
        }

        const deleteButton = event.target.closest('[data-delete-customer]');
        if (deleteButton) {
            if (!window.confirm('Bạn có chắc chắn muốn xóa khách hàng này?')) return;
            try {
                const row = deleteButton.closest('[data-customer-row]');
                await destroyCustomer(row.dataset.customerId);
                row.remove();
                refreshBulkState();
            } catch (error) { window.alert(error.message); }
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeActionMenus();
    });
    bulkDelete?.addEventListener('click', async () => {
        const inputs = selected();
        if (!inputs.length || !window.confirm(`Xóa ${inputs.length} khách hàng đã chọn?`)) return;
        bulkDelete.disabled = true;
        try {
            for (const input of inputs) {
                await destroyCustomer(input.value);
                input.closest('[data-customer-row]')?.remove();
            }
            refreshBulkState();
        } catch (error) {
            window.alert(error.message);
            refreshBulkState();
        }
    });
})();
</script>
