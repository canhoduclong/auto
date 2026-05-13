@extends(accounting_layout())

@section('title', 'Cong No Nha Cung Cap')
@section('subtitle', 'Theo doi so tien can thanh toan cho nha cung cap')

@section('accounting_content')
<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Tim nha cung cap</label>
                <input type="text" class="form-control" name="keyword" value="{{ $keyword }}" placeholder="Ten nha cung cap...">
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Loc</button></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Nha cung cap</th><th>So tien can thanh toan</th><th>Han thanh toan</th><th>Trang thai</th></tr></thead>
            <tbody>
            @forelse($companies as $company)
                @php $payable = $supplierPayables[$company->id] ?? null; @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $company->name }}</div>
                        <div class="text-muted small">{{ $company->phone ?? '-' }}</div>
                    </td>
                    <td class="fw-semibold">{{ number_format((float) ($payable->amount_due ?? 0)) }} d</td>
                    <td>{{ isset($payable->due_date) && $payable->due_date ? \Carbon\Carbon::parse($payable->due_date)->format('d/m/Y') : '-' }}</td>
                    <td><span class="badge text-bg-light border">{{ $payable->status ?? 'chua_cau_hinh' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Khong co nha cung cap.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $companies->links() }}
    </div>
</div>
@endsection
