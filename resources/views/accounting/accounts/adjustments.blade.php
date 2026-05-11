@extends(accounting_layout())

@section('title', 'Lịch Sử Nạp / Rút Tiền')
@section('subtitle', 'Toàn bộ lịch sử điều chỉnh số dư tài khoản thủ công')

@section('accounting_content')

{{-- KPI --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Số dư tài khoản hiện tại</div>
                <div class="fw-bold fs-4 mt-1 text-primary">{{ number_format((float)$currentBalanceTotal) }}đ</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Tổng nạp vào</div>
                <div class="fw-bold fs-4 text-success mt-1">+{{ number_format((float)$totalDeposit) }}đ</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Tổng rút ra</div>
                <div class="fw-bold fs-4 text-danger mt-1">-{{ number_format((float)$totalWithdraw) }}đ</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Chênh lệch ròng</div>
                @php $net = (float)$totalDeposit - (float)$totalWithdraw; @endphp
                <div class="fw-bold fs-4 mt-1 {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $net >= 0 ? '+' : '' }}{{ number_format($net) }}đ
                </div>
            </div>
        </div>
    </div>
</div>

<div class="acc-card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="fw-bold fs-6"><i class="bi bi-wallet2 me-2 text-primary"></i>Số dư từng tài khoản</div>
                <div class="text-muted small">Hiển thị số dư hiện tại của từng tài khoản đang hoạt động.</div>
            </div>
            <div class="text-end">
                <div class="text-muted small text-uppercase fw-semibold">Tổng số dư hiện tại</div>
                <div class="fw-bold fs-5 text-primary">{{ number_format((float)$currentBalanceTotal) }}đ</div>
            </div>
        </div>

        <div class="row g-2">
            @forelse($currentAccounts as $acc)
                @php $isLow = (float) $acc->balance < (float) $acc->warning_threshold; @endphp
                <div class="col-sm-6 col-lg-4 col-xxl-3">
                    <div class="border rounded p-3 h-100 {{ $isLow ? 'border-danger bg-danger bg-opacity-10' : 'bg-light' }}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">{{ $acc->name }}</div>
                                <div class="text-muted small">{{ $acc->type === 'cash' ? 'Tiền mặt' : 'Ngân hàng' }}</div>
                            </div>
                            @if($isLow)
                                <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                            @endif
                        </div>
                        <div class="fw-bold fs-5 mt-2 {{ $isLow ? 'text-danger' : 'text-success' }}">
                            {{ number_format((float)$acc->balance) }}đ
                        </div>
                        @if($isLow)
                            <div class="text-danger small">Dưới ngưỡng {{ number_format((float)$acc->warning_threshold) }}đ</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-muted small">Không có tài khoản nào để hiển thị.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small fw-semibold text-muted text-uppercase">Tài khoản</label>
                <select name="account_id" class="form-select form-select-sm">
                    <option value="">Tất cả tài khoản</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ (string)$accountId === (string)$acc->id ? 'selected' : '' }}>
                            {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small fw-semibold text-muted text-uppercase">Loại</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    <option value="deposit" {{ $type === 'deposit' ? 'selected' : '' }}>Nạp tiền</option>
                    <option value="withdraw" {{ $type === 'withdraw' ? 'selected' : '' }}>Rút tiền</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small fw-semibold text-muted text-uppercase">Từ ngày</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label small fw-semibold text-muted text-uppercase">Đến ngày</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
            </div>
            <div class="col-sm-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
                <a href="{{ accounting_route('accounts.adjustments') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="acc-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Tài khoản</th>
                        <th class="text-center">Loại</th>
                        <th class="text-end">Số tiền</th>
                        <th class="text-end">Số dư trước</th>
                        <th class="text-end">Số dư sau</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($query as $adj)
                    <tr>
                        <td class="text-muted small">{{ $adj->id }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $adj->created_at->format('d/m/Y') }}</div>
                            <div class="text-muted" style="font-size:12px">{{ $adj->created_at->format('H:i:s') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $adj->performer?->name ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $adj->account?->name ?? '—' }}</div>
                            <div class="small text-muted">
                                {{ $adj->account?->type === 'cash' ? 'Tiền mặt' : 'Ngân hàng' }}
                            </div>
                        </td>
                        <td class="text-center">
                            @if($adj->type === 'deposit')
                                <span class="badge bg-success">
                                    <i class="bi bi-plus-circle me-1"></i>Nạp tiền
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="bi bi-dash-circle me-1"></i>Rút tiền
                                </span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $adj->type === 'deposit' ? 'text-success' : 'text-danger' }}">
                            {{ $adj->type === 'deposit' ? '+' : '-' }}{{ number_format((float)$adj->amount) }}đ
                        </td>
                        <td class="text-end text-muted">{{ number_format((float)$adj->balance_before) }}đ</td>
                        <td class="text-end fw-semibold">{{ number_format((float)$adj->balance_after) }}đ</td>
                        <td class="text-muted small">{{ $adj->note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Chưa có lịch sử nạp / rút tiền nào.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $query->links() }}
</div>

@endsection
