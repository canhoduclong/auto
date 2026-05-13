@extends(accounting_layout())

@section('title', 'Danh Sach Don Hang Hang Ngay')
@section('subtitle', 'Theo doi don theo ngay, khach, thanh toan va kho')

@section('accounting_content')
<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Kieu loc ngay</label>
                <select class="form-select" name="filter_mode">
                    <option value="day" {{ ($filterMode ?? 'day') === 'day' ? 'selected' : '' }}>Theo ngay</option>
                    <option value="month" {{ ($filterMode ?? 'day') === 'month' ? 'selected' : '' }}>Theo thang</option>
                    <option value="custom" {{ ($filterMode ?? 'day') === 'custom' ? 'selected' : '' }}>Tu ngay den ngay</option>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Ngay</label><input type="date" class="form-control" name="date" value="{{ $date }}"></div>
            <div class="col-md-2"><label class="form-label">Thang</label><input type="month" class="form-control" name="month" value="{{ $month ?? now()->format('Y-m') }}"></div>
            <div class="col-md-2"><label class="form-label">Tu ngay</label><input type="date" class="form-control" name="from_date" value="{{ $fromDate ?? $date }}"></div>
            <div class="col-md-2"><label class="form-label">Den ngay</label><input type="date" class="form-control" name="to_date" value="{{ $toDate ?? $date }}"></div>
            <div class="col-md-3">
                <label class="form-label">Khach hang</label>
                <select class="form-select" name="customer_id"><option value="0">Tat ca</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" {{ $customerId === (int)$customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>@endforeach</select>
            </div>
            <div class="col-md-2"><label class="form-label">Thanh toan</label><input class="form-control" name="payment_status" value="{{ $paymentStatus }}" placeholder="paid/partial/..." /></div>
            <div class="col-md-3">
                <label class="form-label">Kho</label>
                <select class="form-select" name="warehouse_id"><option value="0">Tat ca kho</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" {{ $warehouseId === (int)$warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>@endforeach</select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Loc</button></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Ma don</th><th>Khach hang</th><th>Tong tien</th><th>TT thanh toan</th><th>TT giao hang</th><th>Kho xuat</th><th>Ngay tao</th></tr></thead>
            <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->code }}</td>
                    <td>{{ $order->customer?->name ?? '-' }}</td>
                    <td class="fw-semibold">{{ number_format($order->total) }} d</td>
                    <td><span class="badge text-bg-light border">{{ $order->payment_status ?? '-' }}</span></td>
                    <td>{{ $order->status }}</td>
                    <td>{{ $order->warehouse?->name ?? '-' }}</td>
                    <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Khong co don hang.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $orders->links() }}
    </div>
</div>
@endsection
