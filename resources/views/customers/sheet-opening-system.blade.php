<div class="card h-100 p-3">
    <h2 class="h6">Khách hàng hệ thống</h2>
    <form method="GET" action="{{ route('customer-debt.sheet-opening.index') }}" class="d-flex flex-wrap gap-2 mb-3" data-sheet-search>
        <input type="hidden" name="tab" value="customers">
        @foreach(request()->only(['source_search', 'source_page', 'show_same_name']) as $field => $value)
            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
        @endforeach
        <input type="search" name="system_search" class="form-control w-auto flex-grow-1" maxlength="150"
            aria-label="Tìm khách hệ thống" placeholder="Tên, mã khách hoặc số điện thoại" value="{{ request('system_search') }}">
        <button class="btn btn-outline-primary">Tìm kiếm</button>
    </form>
    <p class="small text-danger" data-sheet-error role="alert" hidden></p>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Khách hàng</th><th class="text-end">Doanh thu đầu kỳ</th><th class="text-end">Doanh thu từ 15/09</th><th class="text-end">Thanh toán</th><th class="text-end">Giảm trừ</th><th class="text-end">Công nợ</th><th>Khách đã ghép</th></tr></thead>
            <tbody>
                @forelse($rows as $customer)
                    <tr>
                        <td>{{ $customer->name }} @if($customer->customer_code)<small class="text-muted d-block">{{ $customer->customer_code }}</small>@endif</td>
                        @foreach([($opening[$customer->id] ?? 0), ($systemSales[$customer->id] ?? 0), ($paid[$customer->id] ?? 0), ($gifts[$customer->id] ?? 0), ($opening[$customer->id] ?? 0) + ($systemSales[$customer->id] ?? 0) - ($paid[$customer->id] ?? 0) - ($gifts[$customer->id] ?? 0)] as $amount)
                            <td class="text-end text-nowrap">{{ number_format($amount, 0, ',', '.') }} đ</td>
                        @endforeach
                        <td>
                            @foreach($linkedSources->get($customer->id, []) as $sourceKey => $link)
                                @php($source = $sourceGroups[$sourceKey])
                                <form method="POST" action="{{ route('customer-debt.sheet-opening.unlink') }}" class="d-flex align-items-center flex-wrap gap-1 my-1" data-unlink-sheet-customer>
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="source_key" value="{{ $sourceKey }}">
                                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                    <small>{{ $source['name'] }}@if($source['code']) · {{ $source['code'] }}@endif</small>
                                    <button class="btn btn-sm btn-outline-danger text-nowrap">Gỡ ghép</button>
                                </form>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Không tìm thấy khách hàng.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <nav data-sheet-pages aria-label="Trang khách hệ thống">{{ $rows->links() }}</nav>
</div>
