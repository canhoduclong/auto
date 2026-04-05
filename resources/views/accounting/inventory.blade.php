@extends('layouts.accounting')

@section('title', 'Thong Ke Kho')
@section('subtitle', 'Tong ton he thong va ton theo tung kho')

@section('accounting_content')
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
            <div class="col-md-4">
                <label class="form-label">Kho</label>
                <select class="form-select" name="warehouse_id">
                    <option value="0">Toan bo kho</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $warehouseId === (int) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Bo loc thoi gian</label>
                <select class="form-select" name="time_filter">
                    <option value="today" {{ ($timeFilter ?? 'today') === 'today' ? 'selected' : '' }}>Hom nay</option>
                    <option value="date" {{ ($timeFilter ?? 'today') === 'date' ? 'selected' : '' }}>Chon ngay</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ngay can xem</label>
                <input class="form-control" type="date" name="selected_date" value="{{ $selectedDate ?? now()->toDateString() }}">
            </div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Loc</button></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>San pham</th><th>SKU/Bien the</th><th>Kho</th><th>So luong</th><th>Reserved</th><th>Co san</th></tr></thead>
            <tbody>
            @forelse($inventories as $inv)
                <tr>
                    <td>{{ $inv->productVariant?->product?->name ?? '-' }}</td>
                    <td>{{ $inv->productVariant?->sku ?? '-' }}</td>
                    <td>{{ $inv->warehouse?->name ?? '-' }}</td>
                    <td class="fw-semibold">{{ number_format($inv->quantity) }}</td>
                    <td>{{ number_format($inv->reserved_quantity) }}</td>
                    <td>{{ number_format(max(0, (int) $inv->quantity - (int) $inv->reserved_quantity)) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">Khong co du lieu ton kho.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $inventories->links() }}
    </div>
</div>
@endsection
