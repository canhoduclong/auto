@extends(accounting_layout())

@section('title', 'Công Nợ Khách Hàng')
@section('subtitle', 'Theo dõi công nợ đầu kỳ, bổ sung, đơn hàng và thanh toán')

@section('accounting_content')
<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Tìm khách hàng</label>
                <input type="text" class="form-control" name="keyword" value="{{ $keyword }}" placeholder="Tên, SĐT, Email...">
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Lọc</button></div>
            <div class="col-md-4 text-end"><span class="badge text-bg-light border">Tổng nợ trang này: {{ number_format($totalDebt, 0, ',', '.') }} đ</span></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Khách hàng</th>
                    <th class="text-end">Tăng công nợ</th>
                    <th class="text-end">Thanh toán</th>
                    <th class="text-end">Nợ hiện tại</th>
                    <th>Hạn thanh toán</th>
                    <th>Trạng thái</th>
                    <th>Lịch sử thanh toán</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $row['customer']->name }}</div>
                        <div class="text-muted small">{{ $row['customer']->phone ?? '-' }}</div>
                    </td>
                    <td class="text-end">{{ number_format($row['debt_increase'], 0, ',', '.') }} đ</td>
                    <td class="text-end text-success">{{ number_format($row['payments'], 0, ',', '.') }} đ</td>
                    <td class="text-end fw-semibold {{ $row['debt'] > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($row['debt'], 0, ',', '.') }} đ</td>
                    <td>{{ $row['due_date'] ? $row['due_date']->format('d/m/Y') : '-' }}</td>
                    <td><span class="badge {{ $row['debt'] > 0 ? 'text-bg-warning' : 'text-bg-success' }}">{{ $row['status'] }}</span></td>
                    <td>{{ $row['payment_history'] }}</td>
                    <td class="text-end">
                        <a href="{{ accounting_route('customer-debts.show', $row['customer']) }}" class="btn btn-sm btn-outline-primary">
                            Chi tiết
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">Không có dữ liệu công nợ.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $customers->links() }}
    </div>
</div>
@endsection
