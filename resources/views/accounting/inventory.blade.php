@extends(accounting_layout())

@section('title', 'Thong Ke Kho')
@section('subtitle', 'Tong ton he thong va ton theo tung kho')

@section('accounting_content')
@php
    $sortBy = $sortBy ?? 'product_variant';
    $sortDir = $sortDir ?? 'asc';
    $nextDir = function (string $column) use ($sortBy, $sortDir): string {
        return ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
    };
    $sortIcon = function (string $column) use ($sortBy, $sortDir): string {
        if ($sortBy !== $column) {
            return 'bi-arrow-down-up text-muted';
        }
        return $sortDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down';
    };
@endphp
<div class="acc-kpi mb-3">
    <div class="item"><div class="label">Nhap trong ky</div><div class="value text-success">{{ number_format($imports) }}</div></div>
    <div class="item"><div class="label">Xuat trong ky</div><div class="value text-danger">{{ number_format($exports) }}</div></div>
    <div class="item"><div class="label">Ton cuoi ky</div><div class="value">{{ number_format($closingStock) }}</div></div>
</div>

<div class="mb-2">
    <span class="badge text-bg-light border">{{ $rangeLabel }}</span>
</div>

<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Kho</label>
                <select class="form-select" name="warehouse_id">
                    <option value="0">Toan bo kho</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $warehouseId === (int) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Bo loc thoi gian</label>
                <select class="form-select" name="time_filter">
                    <option value="today" {{ ($timeFilter ?? 'today') === 'today' ? 'selected' : '' }}>Hom nay</option>
                    <option value="date" {{ ($timeFilter ?? 'today') === 'date' ? 'selected' : '' }}>Chon ngay</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ngay can xem</label>
                <input class="form-control" type="date" name="selected_date" value="{{ $selectedDate ?? now()->toDateString() }}">
            </div>
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Loc</button></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>
                        <a class="text-decoration-none text-dark" href="{{ request()->fullUrlWithQuery(['sort_by' => 'product_variant', 'sort_dir' => $nextDir('product_variant'), 'page' => 1]) }}">
                            San pham
                            <i class="bi {{ $sortIcon('product_variant') }} ms-1"></i>
                        </a>
                    </th>
                    <th>SKU/Bien the</th>
                    <th>
                        <a class="text-decoration-none text-dark" href="{{ request()->fullUrlWithQuery(['sort_by' => 'warehouse', 'sort_dir' => $nextDir('warehouse'), 'page' => 1]) }}">
                            Kho
                            <i class="bi {{ $sortIcon('warehouse') }} ms-1"></i>
                        </a>
                    </th>
                    <th class="text-end">
                        <a class="text-decoration-none text-dark" href="{{ request()->fullUrlWithQuery(['sort_by' => 'quantity', 'sort_dir' => $nextDir('quantity'), 'page' => 1]) }}">
                            So luong
                            <i class="bi {{ $sortIcon('quantity') }} ms-1"></i>
                        </a>
                    </th>
                    <th class="text-end">Reserved</th>
                    <th class="text-end">Co san</th>
                    <th class="text-end">
                        <a class="text-decoration-none text-dark" href="{{ request()->fullUrlWithQuery(['sort_by' => 'selling_price', 'sort_dir' => $nextDir('selling_price'), 'page' => 1]) }}">
                            Gia ban
                            <i class="bi {{ $sortIcon('selling_price') }} ms-1"></i>
                        </a>
                    </th>
                    <th class="text-end">
                        <a class="text-decoration-none text-dark" href="{{ request()->fullUrlWithQuery(['sort_by' => 'amount', 'sort_dir' => $nextDir('amount'), 'page' => 1]) }}">
                            Thanh tien
                            <i class="bi {{ $sortIcon('amount') }} ms-1"></i>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
            @forelse($inventories as $inv)
                @php
                    $qty = (int) ($inv->quantity ?? 0);
                    $reserved = (int) ($inv->reserved_quantity ?? 0);
                    $available = max(0, $qty - $reserved);
                    $sellingPrice = (float) ($inv->selling_price ?? 0);
                    $lineAmount = $qty * $sellingPrice;
                @endphp
                <tr>
                    <td>{{ $inv->productVariant?->product?->name ?? '-' }}</td>
                    <td>{{ $inv->productVariant?->sku ?? '-' }}</td>
                    <td>{{ $inv->warehouse?->name ?? '-' }}</td>
                    <td class="fw-semibold text-end">{{ number_format($qty) }}</td>
                    <td class="text-end">{{ number_format($reserved) }}</td>
                    <td class="text-end">{{ number_format($available) }}</td>
                    <td class="text-end">{{ number_format($sellingPrice, 0, ',', '.') }} đ</td>
                    <td class="text-end fw-semibold">{{ number_format($lineAmount, 0, ',', '.') }} đ</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">Khong co du lieu ton kho.</td></tr>
            @endforelse
            </tbody>
            @if($inventories->isNotEmpty())
                <tfoot>
                    <tr class="table-light">
                        <td colspan="7" class="text-end fw-semibold">Tong thanh tien trang nay:</td>
                        <td class="text-end fw-bold text-success">{{ number_format($inventories->sum(fn($inv) => ((int) ($inv->quantity ?? 0)) * ((float) ($inv->selling_price ?? 0))), 0, ',', '.') }} đ</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-end fw-semibold text-muted">Tong thanh tien theo bo loc:</td>
                        <td class="text-end fw-bold">{{ number_format((float) ($totalAmount ?? 0), 0, ',', '.') }} đ</td>
                    </tr>
                </tfoot>
            @endif
        </table>
        {{ $inventories->links() }}
    </div>
</div>
@endsection
