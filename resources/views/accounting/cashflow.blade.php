@extends(accounting_layout())

@section('title', 'Quan Ly Thu Chi')
@section('subtitle', 'Phieu thu, phieu chi va luong tien phat sinh')

@section('accounting_content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    @if(current_accounting_route_prefix() !== 'ceo.')
        <a href="{{ accounting_route('transactions.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Tao giao dich
        </a>
    @endif
</div>
<div class="acc-kpi mb-3">
    <div class="item"><div class="label">Tong thu</div><div class="value text-success">{{ number_format($incomeTotal) }} d</div></div>
    <div class="item"><div class="label">Tong chi</div><div class="value text-danger">{{ number_format($expenseTotal) }} d</div></div>
    <div class="item"><div class="label">Can doi</div><div class="value">{{ number_format($incomeTotal - $expenseTotal) }} d</div></div>
</div>

@if(($pendingRequests ?? collect())->isNotEmpty())
<div class="acc-card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="fw-bold">Phiếu tài chính chờ duyệt</div>
                <div class="small text-muted">Phiếu yêu cầu thu/chi và đề nghị thanh toán từ các bộ phận</div>
            </div>
            <span class="badge bg-warning text-dark">{{ $pendingRequests->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Bộ phận</th>
                        <th>Phiếu</th>
                        <th>Dòng tiền</th>
                        <th class="text-end">Số tiền</th>
                        <th>Người gửi</th>
                        <th>Ngày gửi</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingRequests as $requestTx)
                        @php $flow = $requestTx->transactionCategory?->flow_direction === 'in' ? 'in' : 'out'; @endphp
                        <tr>
                            <td class="text-muted">#{{ $requestTx->id }}</td>
                            <td><span class="badge text-bg-light border">{{ $requestTx->request_department ?: $requestTx->request_source }}</span></td>
                            <td>
                                <div class="mb-1"><span class="badge text-bg-light border">{{ $requestTx->request_form_type === \App\Models\Transaction::REQUEST_FORM_PAYMENT ? 'Đề nghị thanh toán' : 'Yêu cầu thu/chi' }}</span></div>
                                <div class="fw-semibold">{{ $requestTx->request_title ?: 'Phiếu yêu cầu' }}</div>
                                <div class="small text-muted">{{ $requestTx->transactionCategory?->name ?: '-' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $flow === 'in' ? 'success' : 'danger' }}">{{ $flow === 'in' ? 'Thu' : 'Chi' }}</span>
                            </td>
                            <td class="text-end fw-bold">{{ number_format((float) $requestTx->amount) }} d</td>
                            <td>{{ $requestTx->submitter?->name ?: '-' }}</td>
                            <td>{{ optional($requestTx->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ accounting_route('cashflow.print', $requestTx) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="In phiếu">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <a href="{{ accounting_route('cashflow.show', $requestTx) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-eye me-1"></i>Xem/Duyệt
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="acc-filter">
            <div>
                <label class="form-label">Chu ky</label>
                <select class="form-select" name="range">
                    <option value="day" {{ request('range') === 'day' ? 'selected' : '' }}>Ngay</option>
                    <option value="week" {{ request('range') === 'week' ? 'selected' : '' }}>Tuan</option>
                    <option value="month" {{ request('range', 'month') === 'month' ? 'selected' : '' }}>Thang</option>
                    <option value="year" {{ request('range') === 'year' ? 'selected' : '' }}>Nam</option>
                    <option value="custom" {{ request('range') === 'custom' ? 'selected' : '' }}>Khoang</option>
                </select>
            </div>
            <div><label class="form-label">Tu ngay</label><input class="form-control" type="date" name="from_date" value="{{ request('from_date', $from->format('Y-m-d')) }}"></div>
            <div><label class="form-label">Den ngay</label><input class="form-control" type="date" name="to_date" value="{{ request('to_date', $to->format('Y-m-d')) }}"></div>
            <div>
                <label class="form-label">Loai</label>
                <select class="form-select" name="type">
                    <option value="">Tat ca</option>
                    <option value="payment" {{ $type === 'payment' ? 'selected' : '' }}>Thu (payment)</option>
                    <option value="refund" {{ $type === 'refund' ? 'selected' : '' }}>Chi (refund)</option>
                    <option value="fee" {{ $type === 'fee' ? 'selected' : '' }}>Chi (fee)</option>
                    <option value="extra_income" {{ $type === 'extra_income' ? 'selected' : '' }}>Thu khac (extra_income)</option>
                    <option value="extra_expense" {{ $type === 'extra_expense' ? 'selected' : '' }}>Chi khac (extra_expense)</option>
                    <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Chi (expense)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Tai khoan</label>
                <select class="form-select" name="account_id">
                    <option value="">Tat ca</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ (string)$accountId === (string)$acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-end"><button class="btn btn-primary w-100">Loc</button></div>
        </form>
    </div>
</div>

<div class="acc-card mb-3">
    <div class="card-body table-responsive">
        <div class="fw-semibold mb-2">Tong hop theo tai khoan</div>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Tai khoan</th>
                    <th class="text-center">So GD</th>
                    <th class="text-end">Tong thu</th>
                    <th class="text-end">Tong chi</th>
                    <th class="text-end">Can doi ky</th>
                    <th class="text-end">So du hien tai</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accountSummaries as $sum)
                    @php
                        $periodBalance = (float)$sum->total_in - (float)$sum->total_out;
                        $isLow = (float)$sum->balance < (float)$sum->warning_threshold;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $sum->name }}</div>
                            <div class="small text-muted">{{ $sum->type === 'cash' ? 'Tien mat' : 'Ngan hang' }}</div>
                        </td>
                        <td class="text-center">{{ number_format((int)$sum->txn_count) }}</td>
                        <td class="text-end text-success">{{ number_format((float)$sum->total_in) }} d</td>
                        <td class="text-end text-danger">{{ number_format((float)$sum->total_out) }} d</td>
                        <td class="text-end fw-semibold {{ $periodBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($periodBalance) }} d</td>
                        <td class="text-end fw-bold {{ $isLow ? 'text-danger' : 'text-success' }}">
                            {{ number_format((float)$sum->balance) }} d
                            @if($isLow)
                                <span class="badge bg-danger ms-1" style="font-size:10px">Canh bao thap</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Khong co du lieu tai khoan trong ky loc.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Deposit/Withdraw Summary Section --}}
@php
    // Get account adjustments from the accounting module
    $depositTotal = 0;
    $withdrawTotal = 0;
    $adjustmentsList = [];
    
    if (class_exists('App\Models\AccountAdjustment')) {
        $query = \App\Models\AccountAdjustment::query()
            ->with(['account', 'performer']);
        
        if ($accountId) {
            $query->where('account_id', $accountId);
        }
        
        $adjustmentsList = $query->latest('created_at')->get();
        $depositTotal = $adjustmentsList->where('type', 'deposit')->sum('amount');
        $withdrawTotal = $adjustmentsList->where('type', 'withdraw')->sum('amount');
    }
@endphp

<div class="acc-card mb-3">
    <div class="card-body p-0">
        <button class="btn btn-link text-start w-100 p-3" data-bs-toggle="collapse" data-bs-target="#depositWithdrawSummary" style="text-decoration: none; border-bottom: 1px solid var(--acc-line);">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-primary fs-5">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div class="text-start">
                        <div class="fw-bold text-dark">Hoạt động Nạp/Rút Tiền Tài Khoản</div>
                        <div class="small text-muted">{{ count($adjustmentsList) }} hoạt động</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-4">
                    <div class="text-end">
                        <div class="small text-muted fw-semibold">Nạp: <span class="text-success">+{{ number_format($depositTotal) }}đ</span></div>
                        <div class="small text-muted fw-semibold">Rút: <span class="text-danger">-{{ number_format($withdrawTotal) }}đ</span></div>
                    </div>
                    <i class="bi bi-chevron-down" style="transition: transform 0.3s;"></i>
                </div>
            </div>
        </button>
        
        <div class="collapse show" id="depositWithdrawSummary">
            <div style="border-top: 1px solid var(--acc-line);" class="table-responsive">
                @if(count($adjustmentsList) > 0)
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
                        @foreach($adjustmentsList as $adj)
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
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-5 d-block mb-2"></i>
                        Chưa có hoạt động nạp/rút tiền
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="acc-card">
    <div class="card-body table-responsive">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <table class="table table-hover align-middle">
            <thead><tr><th>Loai</th><th>Tai khoan</th><th>Danh muc</th><th>Dong tien</th><th>So tien</th><th>Trang thai</th><th>Noi dung</th><th>Don hang</th><th>Khach hang</th><th>Ngay tao</th><th></th></tr></thead>
            <tbody>
            @forelse($transactions as $tx)
                <tr>
                    <td><span class="badge text-bg-light border">{{ $tx->type }}</span></td>
                    <td>
                        @if($tx->account)
                            <div class="fw-semibold">{{ $tx->account->name }}</div>
                            <div class="small text-muted">So du: {{ number_format((float)$tx->account->balance) }} d</div>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($tx->transactionCategory)
                            <div><span class="badge bg-primary">{{ $tx->transactionCategory->code }}</span></div>
                            <div class="small text-muted">{{ $tx->transactionCategory->name }}</div>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($tx->transactionCategory)
                            @if($tx->transactionCategory->flow_direction === 'in')
                                <span class="badge bg-success">Thu vào tài khoản</span>
                            @else
                                <span class="badge bg-danger">Chi từ tài khoản</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="fw-semibold">{{ number_format($tx->amount) }} d</td>
                    <td>
                        @if($tx->status === \App\Models\Transaction::STATUS_APPROVED)
                            <span class="badge text-bg-success">Da duyet</span>
                        @elseif($tx->status === \App\Models\Transaction::STATUS_REJECTED)
                            <span class="badge text-bg-danger">Da tu choi</span>
                        @else
                            <span class="badge text-bg-warning">Cho duyet</span>
                        @endif
                    </td>
                    <td>{{ $tx->note ?? '-' }}</td>
                    <td>{{ $tx->order?->code ?? '-' }}</td>
                    <td>{{ $tx->customer?->name ?? '-' }}</td>
                    <td>{{ optional($tx->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ accounting_route('cashflow.show', $tx) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Chi tiet
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted">Khong co giao dich.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $transactions->links() }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle chevron rotation for deposit/withdraw summary
    const summaryBtn = document.querySelector('[data-bs-target="#depositWithdrawSummary"]');
    const summaryCollapse = document.getElementById('depositWithdrawSummary');
    
    if (summaryBtn && summaryCollapse) {
        const chevron = summaryBtn.querySelector('.bi-chevron-down');
        
        summaryCollapse.addEventListener('show.bs.collapse', () => {
            if (chevron) chevron.style.transform = 'rotate(-180deg)';
        });
        
        summaryCollapse.addEventListener('hide.bs.collapse', () => {
            if (chevron) chevron.style.transform = 'rotate(0deg)';
        });
        
        // Set initial state
        if (summaryCollapse.classList.contains('show')) {
            if (chevron) chevron.style.transform = 'rotate(-180deg)';
        }
    }
});
</script>

@endsection
