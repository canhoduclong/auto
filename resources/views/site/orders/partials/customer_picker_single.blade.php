@if($customers->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-2">
            <thead class="table-light">
                <tr>
                    <th>
                        <a href="#" class="customer-sort-link text-decoration-none text-dark d-flex align-items-center gap-1"
                            data-sort-by="name"
                            data-sort-dir="{{ ($sortBy === 'name' && $sortDir === 'asc') ? 'desc' : 'asc' }}">
                            Tên khách hàng
                            @if($sortBy === 'name')
                                <span class="text-primary">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="#" class="customer-sort-link text-decoration-none text-dark d-flex align-items-center gap-1"
                            data-sort-by="phone"
                            data-sort-dir="{{ ($sortBy === 'phone' && $sortDir === 'asc') ? 'desc' : 'asc' }}">
                            Số điện thoại
                            @if($sortBy === 'phone')
                                <span class="text-primary">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="#" class="customer-sort-link text-decoration-none text-dark d-flex align-items-center gap-1"
                            data-sort-by="email"
                            data-sort-dir="{{ ($sortBy === 'email' && $sortDir === 'asc') ? 'desc' : 'asc' }}">
                            Email
                            @if($sortBy === 'email')
                                <span class="text-primary">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </a>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                    <tr class="{{ in_array((int) $customer->id, $selectedCustomerIds ?? [], true) ? 'table-primary' : '' }}">
                        <td>
                            <strong>
                                @if($customer->is_pinned)
                                    <span class="text-warning me-1">★</span>
                                @endif
                                @if((int) ($customer->sort_order ?? 0) > 0)
                                    <span class="badge bg-light text-dark border me-1">{{ (int) $customer->sort_order }}</span>
                                @endif
                                {{ $customer->name }}
                            </strong>
                        </td>
                        <td>{{ $customer->phone ?: '—' }}</td>
                        <td class="text-muted small">{{ $customer->email ?: '—' }}</td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary select-customer-btn"
                                data-customer-id="{{ $customer->id }}"
                                data-customer-name="{{ $customer->name }}"
                                data-customer-phone="{{ $customer->phone }}"
                                data-customer-email="{{ $customer->email }}"
                                data-customer-company="{{ $customer->company_name ?? '' }}"
                                data-customer-code="{{ $customer->customer_code ?? '' }}"
                                data-customer-address="{{ $customer->address ?? '' }}"
                                data-customer-use-truck-station="{{ $customer->use_truck_station ? '1' : '0' }}"
                                data-customer-truck-station-id="{{ $customer->truck_station_id ?? '' }}"
                                data-customer-truck-station-name="{{ $customer->truckStation?->name ?? '' }}"
                                data-customer-truck-station-address="{{ $customer->truck_station_address ?: ($customer->truckStation?->address ?? '') }}"
                                data-customer-truck-station-phone="{{ $customer->truck_station_phone ?: ($customer->truckStation?->phone ?? '') }}"
                                data-customer-truck-receive-time="{{ $customer->truck_receive_time ?? '' }}">
                                Chọn
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <div class="small text-muted">
            {{ $customers->firstItem() }} – {{ $customers->lastItem() }} / {{ $customers->total() }} khách hàng
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary customer-page-btn"
                data-page="{{ max($customers->currentPage() - 1, 1) }}"
                {{ $customers->onFirstPage() ? 'disabled' : '' }}>
                &laquo; Trước
            </button>
            <span class="small text-muted">Trang {{ $customers->currentPage() }}/{{ $customers->lastPage() }}</span>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary customer-page-btn"
                data-page="{{ min($customers->currentPage() + 1, $customers->lastPage()) }}"
                {{ $customers->hasMorePages() ? '' : 'disabled' }}>
                Sau &raquo;
            </button>
        </div>
    </div>
@else
    <div class="text-center text-muted py-4">
        <div class="mb-2" style="font-size:2rem;">🔍</div>
        Không tìm thấy khách hàng phù hợp.
    </div>
@endif
