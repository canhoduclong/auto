@extends(accounting_layout())

@section('title', 'Chi Tiet Thu Chi')
@section('subtitle', 'Thong tin day du va duyet giao dich')

@section('accounting_content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ accounting_route('cashflow') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Quay lai
    </a>
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ accounting_route('transactions.edit', $transaction) }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-pencil me-1"></i> Sua giao dich
        </a>
        @if($transaction->status === \App\Models\Transaction::STATUS_APPROVED)
            <span class="badge text-bg-success">Da duyet</span>
        @elseif($transaction->status === \App\Models\Transaction::STATUS_REJECTED)
            <span class="badge text-bg-danger">Da tu choi</span>
        @else
            <span class="badge text-bg-warning">Cho duyet</span>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="acc-card mb-3">
    <div class="card-body">
        <h5 class="mb-3">Giao dich #{{ $transaction->id }}</h5>
        <div class="row g-3">
            @if($transaction->request_source)
                <div class="col-md-4">
                    <div class="text-muted small">Bộ phận yêu cầu</div>
                    <div><span class="badge text-bg-light border">{{ $transaction->request_department ?: $transaction->request_source }}</span></div>
                </div>
                <div class="col-md-8">
                    <div class="text-muted small">Tiêu đề phiếu</div>
                    <div class="fw-semibold">{{ $transaction->request_title ?: 'Phiếu yêu cầu' }}</div>
                </div>
            @endif
            <div class="col-md-4">
                <div class="text-muted small">Loai</div>
                <div><span class="badge text-bg-light border">{{ $transaction->type }}</span></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">So tien</div>
                <div class="fw-bold">{{ number_format($transaction->amount) }} d</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Loai chi</div>
                <div>{{ $transaction->expenseType?->name ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Danh muc giao dich</div>
                <div>
                    @if($transaction->transactionCategory)
                        <span class="badge bg-primary">{{ $transaction->transactionCategory->code }}</span>
                        {{ $transaction->transactionCategory->name }}
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Dong tien tai khoan</div>
                <div>
                    @if($transaction->transactionCategory)
                        @if($transaction->transactionCategory->flow_direction === 'in')
                            <span class="badge bg-success">Thu vao tai khoan</span>
                        @else
                            <span class="badge bg-danger">Chi tu tai khoan</span>
                        @endif
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Doi tuong chi</div>
                <div>{{ $transaction->payeeUser?->name ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Don hang</div>
                <div>{{ $transaction->order?->code ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Khach hang</div>
                <div>{{ $transaction->customer?->name ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Tai khoan</div>
                <div>{{ $transaction->account?->name ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Phuong thuc</div>
                <div>{{ $transaction->method ?: '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Nguoi tao</div>
                <div>{{ $transaction->submitter?->name ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Ngay tao</div>
                <div>{{ optional($transaction->created_at)->format('d/m/Y H:i') }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Trang thai</div>
                <div>{{ $transaction->status }}</div>
            </div>
            <div class="col-12">
                <div class="text-muted small">Noi dung</div>
                <div>{{ $transaction->note ?: '-' }}</div>
            </div>
            @if($transaction->status === \App\Models\Transaction::STATUS_REJECTED)
                <div class="col-12">
                    <div class="text-muted small">Ly do tu choi</div>
                    <div class="text-danger">{{ $transaction->reject_reason ?: '-' }}</div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($transaction->request_source)
    @php
        $requestItems = collect($transaction->request_items ?: []);
        if ($requestItems->isEmpty()) {
            $requestItems = collect([[
                'content' => $transaction->note ?: $transaction->request_title,
                'unit' => '',
                'quantity' => 1,
                'unit_price' => (float) $transaction->amount,
                'line_total' => (float) $transaction->amount,
            ]]);
        }

        $requestSubtotal = (float) ($transaction->request_subtotal ?? $requestItems->sum('line_total'));
        $requestVat = (float) ($transaction->request_vat ?? 0);
        $requestTotal = (float) ($transaction->request_total ?? $transaction->amount);
    @endphp
    <div class="acc-card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Chi tiết nội dung phiếu yêu cầu</h5>
                    <div class="small text-muted">Kế toán kiểm tra nội dung, VAT và tổng cộng trước khi duyệt.</div>
                </div>
                <span class="badge text-bg-light border">{{ $transaction->request_department ?: $transaction->request_source }}</span>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:56px">STT</th>
                            <th>Nội dung</th>
                            <th class="text-center" style="width:90px">ĐVT</th>
                            <th class="text-end" style="width:110px">Số lượng</th>
                            <th class="text-end" style="width:140px">Đơn giá</th>
                            <th class="text-end" style="width:150px">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requestItems as $index => $item)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $item['content'] ?? '-' }}</td>
                                <td class="text-center">{{ $item['unit'] ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) ($item['quantity'] ?? 0), 2) }}</td>
                                <td class="text-end">{{ number_format((float) ($item['unit_price'] ?? 0)) }} d</td>
                                <td class="text-end fw-semibold">{{ number_format((float) ($item['line_total'] ?? 0)) }} d</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Tổng tiền</th>
                            <th class="text-end">{{ number_format($requestSubtotal) }} d</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">VAT</th>
                            <th class="text-end">{{ number_format($requestVat) }} d</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">Tổng cộng</th>
                            <th class="text-end text-primary fs-6">{{ number_format($requestTotal) }} d</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endif

@if($canReview)
<div class="row g-3">
    <div class="col-lg-6">
        <div class="acc-card h-100">
            <div class="card-body">
                <h6 class="mb-3">Duyet giao dich</h6>
                <form method="POST" action="{{ accounting_route('transactions.approve', $transaction) }}">
                    @csrf
                    @if($transaction->request_source)
                        <div class="mb-2">
                            <label class="form-label">Tài khoản thực hiện <span class="text-danger">*</span></label>
                            <select name="account_id" class="form-select" required>
                                <option value="">-- Chọn tài khoản --</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) old('account_id', $transaction->account_id) === (string) $account->id)>
                                        {{ $account->name }} - {{ $account->type === 'cash' ? 'Tiền mặt' : 'Ngân hàng' }} ({{ number_format((float) $account->balance) }}đ)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-2">
                        <label class="form-label">Ghi chu (tuy chon)</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Ghi chu phe duyet..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Duyet
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="acc-card h-100">
            <div class="card-body">
                <h6 class="mb-3">Tu choi giao dich</h6>
                <form method="POST" action="{{ accounting_route('transactions.reject', $transaction) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Ly do tu choi <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Nhap ly do tu choi..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Ban chac chan muon tu choi giao dich nay?')">
                        <i class="bi bi-x-circle"></i> Tu choi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
