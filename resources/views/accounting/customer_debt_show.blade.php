@extends(accounting_layout())

@section('title', 'Chi Tiết Công Nợ')
@section('subtitle', $customer->name)

@section('accounting_content')
<div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-1">{{ $customer->name }}</h5>
        <div class="text-muted small">{{ $customer->phone ?: '-' }} @if($customer->email) · {{ $customer->email }} @endif</div>
    </div>
    <a href="{{ accounting_route('customer-debts') }}" class="btn btn-outline-secondary btn-sm">
        Quay lại
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="acc-card"><div class="card-body">
            <div class="text-muted small">Tăng công nợ</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['debt_increase'], 0, ',', '.') }} đ</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="acc-card"><div class="card-body">
            <div class="text-muted small">Trong đó đơn hàng</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['orders_total'], 0, ',', '.') }} đ</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="acc-card"><div class="card-body">
            <div class="text-muted small">Thanh toán</div>
            <div class="fs-5 fw-bold text-success">{{ number_format($summary['payments'], 0, ',', '.') }} đ</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="acc-card"><div class="card-body">
            <div class="text-muted small">Còn nợ</div>
            <div class="fs-5 fw-bold text-danger">{{ number_format($summary['current_debt'], 0, ',', '.') }} đ</div>
        </div></div>
    </div>
</div>

<div class="acc-card mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Bổ sung công nợ khách hàng</h6>
        <form action="{{ accounting_route('customer-debts.adjustments.store', $customer) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2">
                <label class="form-label">Loại công nợ</label>
                <select name="adjustment_type" class="form-select" required>
                    <option value="opening" @selected(old('adjustment_type') === 'opening')>Công nợ đầu kỳ</option>
                    <option value="additional" @selected(old('adjustment_type') === 'additional')>Công nợ bổ sung</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ngày ghi nhận</label>
                <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', now()->toDateString()) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Số tiền</label>
                <input type="text" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="VD: 5.000.000" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nội dung/Lý do</label>
                <input type="text" name="note" class="form-control" value="{{ old('note') }}" placeholder="Nhập lý do phát sinh công nợ" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Lưu công nợ</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="acc-card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Công nợ tăng lên</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Nội dung</th>
                                <th class="text-end">Phát sinh</th>
                                <th class="text-end">Còn lại</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($debtIncreases as $row)
                                <tr>
                                    <td>{{ optional($row['date'])->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">
                                            @if($row['url'])
                                                <a href="{{ $row['url'] }}">{{ $row['label'] }}</a>
                                            @else
                                                {{ $row['label'] }}
                                            @endif
                                        </div>
                                        <div class="text-muted small">{{ $row['description'] }}</div>
                                    </td>
                                    <td class="text-end text-danger fw-semibold">{{ number_format($row['amount'], 0, ',', '.') }} đ</td>
                                    <td class="text-end">{{ number_format($row['remaining'], 0, ',', '.') }} đ</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Chưa có phát sinh công nợ.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="acc-card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Thanh toán của khách hàng</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Nội dung</th>
                                <th class="text-end">Thanh toán</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>{{ optional($payment->created_at)->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $payment->order?->code ? ('Đơn ' . $payment->order->code) : 'Thanh toán công nợ' }}</div>
                                        <div class="text-muted small">{{ $payment->note ?: ($payment->method ?: '-') }}</div>
                                    </td>
                                    <td class="text-end text-success fw-semibold">{{ number_format((float) $payment->amount, 0, ',', '.') }} đ</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">Chưa có thanh toán.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
