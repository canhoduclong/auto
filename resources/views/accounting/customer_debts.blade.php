@extends(accounting_layout())

@section('title', 'Cong No Khach Hang')
@section('subtitle', 'Danh sach khach hang con no va lich su thanh toan')

@section('accounting_content')
<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Tim khach hang</label>
                <input type="text" class="form-control" name="keyword" value="{{ $keyword }}" placeholder="Ten, SDT, Email...">
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Loc</button></div>
            <div class="col-md-4 text-end"><span class="badge text-bg-light border">Tong no: {{ number_format($totalDebt) }} d</span></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Khach hang</th><th>No hien tai</th><th>Han thanh toan</th><th>Trang thai</th><th>Lich su thanh toan</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $row['customer']->name }}</div>
                        <div class="text-muted small">{{ $row['customer']->phone ?? '-' }}</div>
                    </td>
                    <td class="fw-semibold text-danger">{{ number_format($row['debt']) }} d</td>
                    <td>{{ $row['due_date'] ? $row['due_date']->format('d/m/Y') : '-' }}</td>
                    <td><span class="badge text-bg-warning">{{ $row['status'] }}</span></td>
                    <td>{{ $row['payment_history'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">Khong co du lieu cong no.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $customers->links() }}
    </div>
</div>
@endsection
