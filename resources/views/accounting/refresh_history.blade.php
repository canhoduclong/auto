@extends(accounting_layout())

@section('title', 'Lịch sử refresh số dư')
@section('subtitle', 'Xem lại các lần làm mới số dư và tài khoản nào đã thay đổi')

@section('accounting_content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Lịch sử refresh số dư</h5>
        <div class="text-muted small">Mỗi lần refresh sẽ ghi lại tài khoản bị chỉnh, số dư trước/sau và chênh lệch.</div>
    </div>
    <a href="{{ accounting_route('cashflow', request()->only(['account_id', 'from_date', 'to_date', 'type'])) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Quay lại cashflow
    </a>
</div>

<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted text-uppercase">Tài khoản</label>
                <select name="account_id" class="form-select form-select-sm">
                    <option value="">Tất cả tài khoản</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ (string)$accountId === (string)$acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted text-uppercase">Từ ngày</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted text-uppercase">Đến ngày</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
                <a href="{{ accounting_route('refresh-history') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Tổng lượt refresh</div>
                <div class="fw-bold fs-4 mt-1">{{ number_format($runs->total()) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Tài khoản đã cập nhật</div>
                <div class="fw-bold fs-4 text-warning mt-1">{{ number_format($runs->getCollection()->sum('accounts_updated')) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Tổng chênh lệch</div>
                <div class="fw-bold fs-4 text-danger mt-1">{{ number_format((float) $runs->getCollection()->sum('total_amount_adjusted')) }}đ</div>
            </div>
        </div>
    </div>
</div>

<div class="acc-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Phạm vi</th>
                        <th class="text-center">Kiểm tra</th>
                        <th class="text-center">Cập nhật</th>
                        <th class="text-end">Tổng chênh lệch</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                        @php
                            $updatedItems = collect($run->results_json ?? [])->filter(fn ($item) => !empty($item['updated']))->values();
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $run->created_at->format('d/m/Y H:i') }}</div>
                                <div class="small text-muted">{{ $run->created_at->diffForHumans() }}</div>
                            </td>
                            <td>{{ $run->performer?->name ?? 'Hệ thống' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $run->filterAccount?->name ?? 'Tất cả tài khoản' }}</div>
                                <div class="small text-muted">
                                    @if($run->from_date || $run->to_date)
                                        {{ $run->from_date?->format('d/m/Y') ?? '...' }} - {{ $run->to_date?->format('d/m/Y') ?? '...' }}
                                    @else
                                        Không giới hạn ngày
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">{{ number_format((int) $run->accounts_reconciled) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $run->accounts_updated > 0 ? 'bg-warning text-dark' : 'bg-success' }}">
                                    {{ number_format((int) $run->accounts_updated) }}
                                </span>
                            </td>
                            <td class="text-end fw-bold {{ (float) $run->total_amount_adjusted > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format((float) $run->total_amount_adjusted) }}đ
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#refresh-detail-{{ $run->id }}">
                                    Xem chi tiết
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" class="bg-light border-0 p-0">
                                <div class="collapse" id="refresh-detail-{{ $run->id }}">
                                    <div class="p-3">
                                        <div class="fw-semibold mb-2">Các tài khoản đã thay đổi</div>
                                        @if($updatedItems->isEmpty())
                                            <div class="text-muted small">Không có tài khoản nào cần cập nhật.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered bg-white mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Tài khoản</th>
                                                            <th class="text-end">Số dư cũ</th>
                                                            <th class="text-end">Tính lại</th>
                                                            <th class="text-end">Chênh lệch</th>
                                                            <th class="text-end">Số giao dịch</th>
                                                            <th>Ghi chú</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($updatedItems as $item)
                                                            <tr>
                                                                <td>
                                                                    <div class="fw-semibold">{{ $item['account_name'] ?? '—' }}</div>
                                                                    <div class="small text-muted">{{ $item['account_type'] ?? '' }}</div>
                                                                </td>
                                                                <td class="text-end">{{ number_format((float) ($item['old_balance'] ?? 0)) }}đ</td>
                                                                <td class="text-end">{{ number_format((float) ($item['calculated_balance'] ?? 0)) }}đ</td>
                                                                <td class="text-end fw-bold {{ (float) ($item['difference'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                                                    {{ number_format((float) ($item['difference'] ?? 0)) }}đ
                                                                </td>
                                                                <td class="text-end">{{ number_format((int) ($item['transaction_count'] ?? 0)) }}</td>
                                                                <td class="small text-muted">
                                                                    Opening: {{ number_format((float) ($item['opening_balance'] ?? 0)) }}đ,
                                                                    Net GD: {{ number_format((float) ($item['txn_net'] ?? 0)) }}đ
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Chưa có lịch sử refresh nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $runs->links() }}
</div>
@endsection
