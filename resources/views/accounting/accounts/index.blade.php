@extends('layouts.accounting')

@section('title', 'Tài Khoản')
@section('subtitle', 'Quản lý tài khoản tiền mặt và ngân hàng')

@section('accounting_content')

{{-- Alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Low balance warning --}}
@if($lowBalanceCount > 0)
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <span><strong>Cảnh báo:</strong> {{ $lowBalanceCount }} tài khoản có số dư dưới ngưỡng cảnh báo!</span>
    </div>
@endif

{{-- KPI summary --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Tổng số dư</div>
                <div class="fw-bold fs-4 text-success mt-1">{{ number_format($totalBalance) }}đ</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Số tài khoản</div>
                <div class="fw-bold fs-4 mt-1">{{ $accounts->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="acc-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small text-uppercase fw-semibold">Cảnh báo thấp</div>
                <div class="fw-bold fs-4 mt-1 {{ $lowBalanceCount > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $lowBalanceCount }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-wallet2 me-2 text-primary"></i>Danh sách tài khoản</h5>
    <a href="{{ route('accounting.accounts.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Thêm tài khoản
    </a>
</div>

<div class="acc-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tên tài khoản</th>
                        <th>Loại</th>
                        <th>Chủ thể</th>
                        <th>Số tài khoản / Ngân hàng</th>
                        <th class="text-end">Số dư</th>
                        <th class="text-end">Ngưỡng cảnh báo</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($accounts as $acc)
                    @php $low = $acc->isLowBalance(); @endphp
                    <tr class="{{ $low ? 'table-warning' : '' }}">
                        <td>
                            <div class="fw-semibold">{{ $acc->name }}</div>
                            @if($acc->note)<div class="text-muted small">{{ $acc->note }}</div>@endif
                        </td>
                        <td>
                            @if($acc->type === 'cash')
                                <span class="badge bg-success">Tiền mặt</span>
                            @else
                                <span class="badge bg-info text-dark">Ngân hàng</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $acc->ownerTypeLabel() }}</span>
                            @if($acc->owner_name)
                                <div class="small text-muted mt-1">{{ $acc->owner_name }}</div>
                            @endif
                        </td>
                        <td>
                            @if($acc->account_number)<div>{{ $acc->account_number }}</div>@endif
                            @if($acc->bank_name)<div class="text-muted small">{{ $acc->bank_name }}</div>@endif
                        </td>
                        <td class="text-end fw-bold {{ $low ? 'text-danger' : 'text-success' }}">
                            {{ number_format((float)$acc->balance) }}đ
                            @if($low)<br><span class="badge bg-danger" style="font-size:10px"><i class="bi bi-exclamation-triangle-fill me-1"></i>Dưới ngưỡng</span>@endif
                        </td>
                        <td class="text-end text-muted">{{ number_format((float)$acc->warning_threshold) }}đ</td>
                        <td class="text-center">
                            @if($acc->is_active)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-secondary">Không HĐ</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-success"
                                        data-bs-toggle="modal" data-bs-target="#depositModal{{ $acc->id }}"
                                        title="Nạp tiền">
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning"
                                        data-bs-toggle="modal" data-bs-target="#withdrawModal{{ $acc->id }}"
                                        title="Rút tiền">
                                    <i class="bi bi-dash-circle"></i>
                                </button>
                                <a href="{{ route('accounting.accounts.edit', $acc) }}" class="btn btn-sm btn-outline-secondary" title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Chưa có tài khoản nào. <a href="{{ route('accounting.accounts.create') }}">Thêm ngay</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Deposit / Withdraw modals --}}
@foreach($accounts as $acc)
<div class="modal fade" id="depositModal{{ $acc->id }}" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form action="{{ route('accounting.accounts.deposit', $acc) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-plus-circle text-success me-1"></i>Nạp tiền — {{ $acc->name }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 small text-muted">Số dư hiện tại: <strong>{{ number_format((float)$acc->balance) }}đ</strong></div>
                <label class="form-label">Số tiền nạp</label>
                <input type="number" name="amount" class="form-control" required min="1" step="1000" placeholder="0">
                <label class="form-label mt-2">Ghi chú</label>
                <input type="text" name="note" class="form-control" maxlength="300">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-success btn-sm">Xác nhận nạp</button>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="withdrawModal{{ $acc->id }}" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form action="{{ route('accounting.accounts.withdraw', $acc) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-dash-circle text-warning me-1"></i>Rút tiền — {{ $acc->name }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 small text-muted">Số dư hiện tại: <strong>{{ number_format((float)$acc->balance) }}đ</strong></div>
                <label class="form-label">Số tiền rút</label>
                <input type="number" name="amount" class="form-control" required min="1" step="1000" max="{{ (float)$acc->balance }}" placeholder="0">
                <label class="form-label mt-2">Ghi chú</label>
                <input type="text" name="note" class="form-control" maxlength="300">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-warning btn-sm">Xác nhận rút</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@endsection
