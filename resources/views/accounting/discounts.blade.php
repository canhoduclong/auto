@extends(accounting_layout())

@section('title', 'Chiet Khau Khach Hang')
@section('subtitle', 'Cau hinh chiet khau va dieu kien ap dung')

@section('accounting_content')
@if($missingTable)
    <div class="alert alert-warning">Chua co bang du lieu chiet khau. Chay migration de kich hoat tinh nang.</div>
@endif

<div class="acc-card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ accounting_route('discounts.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Khach hang</label>
                <select class="form-select" name="customer_id" required>
                    <option value="">Chon khach hang</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Loai</label>
                <select class="form-select" name="type"><option value="percent">%</option><option value="fixed">So tien</option></select>
            </div>
            <div class="col-md-2"><label class="form-label">Gia tri</label><input type="number" step="0.01" class="form-control" name="value" required></div>
            <div class="col-md-2"><label class="form-label">Ngay ap dung</label><input type="date" class="form-control" name="effective_date" required></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Luu</button></div>
            <div class="col-12"><label class="form-label">Dieu kien ap dung</label><input type="text" class="form-control" name="condition_note"></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Khach hang</th><th>Loai</th><th>Gia tri</th><th>Ngay ap dung</th><th>Dieu kien</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->customer_name ?? '-' }}</td>
                    <td>{{ $row->type }}</td>
                    <td>{{ number_format((float)$row->value) }}{{ $row->type === 'percent' ? '%' : ' d' }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->effective_date)->format('d/m/Y') }}</td>
                    <td>{{ $row->condition_note ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">Chua co cau hinh chiet khau.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if(method_exists($rows, 'links')){{ $rows->links() }}@endif
    </div>
</div>
@endsection
