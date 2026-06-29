@if($customers->count() > 0)
    <div class="customer-list-scroll">
        <label class="customer-list-item d-flex align-items-start gap-2">
            <input
                type="checkbox"
                class="form-check-input customer-checkbox"
                data-customer-id="0"
            >
            <span>
                <strong class="customer-list-name">Tất cả khách hàng</strong>
                <span class="small text-muted">Bỏ chọn toàn bộ để xem tất cả đơn</span>
            </span>
        </label>

        @foreach($customers as $customer)
            <label class="customer-list-item d-flex align-items-start gap-2 {{ in_array((int) $customer->id, $selectedCustomerIds ?? [], true) ? 'active' : '' }}">
                <input
                    type="checkbox"
                    class="form-check-input customer-checkbox"
                    data-customer-id="{{ $customer->id }}"
                    data-customer-name="{{ $customer->name }}"
                    {{ in_array((int) $customer->id, $selectedCustomerIds ?? [], true) ? 'checked' : '' }}
                >
                <span>
                    <strong class="customer-list-name">
                        @if($customer->is_pinned)
                            <span class="text-warning me-1">★</span>
                        @endif
                        @if((int) ($customer->sort_order ?? 0) > 0)
                            <span class="badge bg-light text-dark border me-1">{{ (int) $customer->sort_order }}</span>
                        @endif
                        {{ $customer->name }}
                    </strong>
                    <span class="small text-muted">
                        {{ $customer->phone ?: 'Chưa có SĐT' }}
                        @if($customer->email)
                            | {{ $customer->email }}
                        @endif
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
        <div class="small text-muted">
            {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} / {{ $customers->total() }} khách hàng
        </div>
        <div class="d-flex gap-2">
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                data-customer-page="{{ max($customers->currentPage() - 1, 1) }}"
                {{ $customers->onFirstPage() ? 'disabled' : '' }}
            >
                Trước
            </button>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                data-customer-page="{{ min($customers->currentPage() + 1, $customers->lastPage()) }}"
                {{ $customers->hasMorePages() ? '' : 'disabled' }}
            >
                Sau
            </button>
        </div>
    </div>
@else
    <div class="small text-muted">Không tìm thấy khách hàng phù hợp.</div>
@endif
