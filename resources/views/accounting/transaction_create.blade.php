@extends('layouts.accounting')

@section('title', 'Tao Giao Dich')
@section('subtitle', 'Ghi nhan thu chi, thanh toan, hoan tien')

@push('styles')
<style>
/* ── layout ─────────────────────────────────────────── */
.txn-shell { display: grid; grid-template-columns: 1fr 400px; gap: 20px; align-items: start; }
@media (max-width: 1100px) { .txn-shell { grid-template-columns: 1fr; } }

/* ── type selector ───────────────────────────────────── */
.txn-type-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 8px; }
@media (max-width: 700px) { .txn-type-grid { grid-template-columns: repeat(3,1fr); } }
.txn-type-btn {
    border: 2px solid #e2e8f0; border-radius: 10px; padding: 10px 6px;
    text-align: center; cursor: pointer; transition: all .14s;
    background:#fff; font-size:12px; user-select:none;
}
.txn-type-btn:hover { border-color: #94a3b8; background:#f8fafc; }
.txn-type-btn.sel-payment     { border-color:#22c55e; background:#f0fdf4; color:#15803d; }
.txn-type-btn.sel-refund      { border-color:#ef4444; background:#fef2f2; color:#b91c1c; }
.txn-type-btn.sel-fee         { border-color:#f59e0b; background:#fffbeb; color:#b45309; }
.txn-type-btn.sel-extra_income  { border-color:#06b6d4; background:#ecfeff; color:#0e7490; }
.txn-type-btn.sel-extra_expense { border-color:#8b5cf6; background:#f5f3ff; color:#6d28d9; }

/* ── entity card ─────────────────────────────────────── */
.entity-card {
    border: 1.5px solid #e2e8f0; border-radius: 12px;
    padding: 14px 16px; background: #f8fafc; position: relative;
}
.entity-card .ec-header { font-weight: 700; margin-bottom: 10px; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing:.04em; }
.entity-card .ec-row { display: flex; justify-content: space-between; align-items: baseline; padding: 3px 0; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
.entity-card .ec-row:last-child { border-bottom: none; }
.entity-card .ec-label { color: #94a3b8; }
.entity-card .ec-val { font-weight: 600; }
.entity-card .ec-reset { position: absolute; top: 10px; right: 10px; }

/* ── popup modal ─────────────────────────────────────── */
.popup-table { font-size: 13px; }
.popup-table tr { cursor: pointer; }
.popup-table tr:hover td { background: #eff6ff; }
.popup-row-selected td { background: #dbeafe !important; }
.filter-bar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
.filter-bar input, .filter-bar select { font-size: 13px; }

/* ── pending list ────────────────────────────────────── */
.pending-badge { font-size: 11px; }
</style>
@endpush

@section('accounting_content')
<div class="txn-shell">
    {{-- ── LEFT: form ──────────────────────────────────────── --}}
    <div>
        <div class="acc-card mb-3">
            <div class="card-body">
                <div class="fw-bold mb-4 fs-6"><i class="bi bi-plus-circle me-2 text-primary"></i>Tao giao dich moi</div>

                <form id="txnForm" action="{{ route('accounting.transactions.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="order_id"    id="f_order_id">
                    <input type="hidden" name="customer_id" id="f_customer_id">

                    {{-- type --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-uppercase text-muted ls-1">Loai giao dich <span class="text-danger">*</span></label>
                        <div class="txn-type-grid">
                            @foreach([
                                ['payment',       'bi-cash-coin',        'Thanh toan',   'Thu tien'],
                                ['refund',        'bi-arrow-return-left','Hoan tien',    'Tra lai'],
                                ['fee',           'bi-receipt',          'Phi DV',       'Phi ship/PS'],
                                ['extra_income',  'bi-graph-up-arrow',   'Thu them',     'Ngoai don'],
                                ['extra_expense', 'bi-graph-down-arrow', 'Chi them',     'Ngoai don'],
                            ] as [$val, $icon, $label, $desc])
                                <label class="txn-type-btn {{ old('type')===$val ? 'sel-'.$val : '' }}"
                                       id="tl-{{ $val }}" onclick="selectType('{{ $val }}')">
                                    <input type="radio" name="type" value="{{ $val }}"
                                           {{ old('type')===$val ? 'checked' : '' }} class="d-none">
                                    <div><i class="bi {{ $icon }} fs-5"></i></div>
                                    <div class="fw-semibold mt-1">{{ $label }}</div>
                                    <div class="text-muted" style="font-size:10px">{{ $desc }}</div>
                                </label>
                            @endforeach
                        </div>
                        @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- transaction category --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Danh mục giao dịch</label>
                        <input type="hidden" name="transaction_category_id" id="f_cat_id" value="{{ old('transaction_category_id') }}">
                        <div class="input-group">
                            <input type="text" id="f_cat_display" class="form-control" readonly placeholder="-- Chọn danh mục --"
                                   value="{{ old('transaction_category_id') ? \App\Models\TransactionCategory::find(old('transaction_category_id'))?->name : '' }}"
                                   style="cursor:pointer;background:#fff" onclick="openCategoryPopup()">
                            <button type="button" class="btn btn-outline-primary" onclick="openCategoryPopup()">
                                <i class="bi bi-grid-3x3-gap"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearCategory()" id="btn_clear_cat" style="{{ old('transaction_category_id') ? '' : 'display:none' }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>

                    {{-- account --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Tài khoản</label>
                        <select name="account_id" id="f_account_id" class="form-select">
                            <option value="">-- Không chọn --</option>
                            @foreach($accounts as $acc)
                                @php $low = (float)$acc->balance < (float)$acc->warning_threshold; @endphp
                                <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}
                                        style="{{ $low ? 'color:#dc3545' : '' }}">
                                    {{ $acc->name }}
                                    ({{ $acc->type === 'cash' ? 'Tiền mặt' : 'Ngân hàng' }})
                                    — {{ number_format((float)$acc->balance) }}đ
                                    {{ $low ? ' ⚠️' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- order selector --}}
                    <div class="mb-3" id="orderSection">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Don hang lien ket</label>
                        <div id="orderCard" class="entity-card d-none">
                            <div class="ec-header">
                                <i class="bi bi-bag-check me-1 text-primary"></i>Thong tin don hang
                            </div>
                            <div id="orderCardBody"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary ec-reset" onclick="resetOrder()">
                                <i class="bi bi-x-circle me-1"></i>Chon lai
                            </button>
                        </div>
                        <div id="orderPickerBtn">
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="openOrderPopup()">
                                <i class="bi bi-search me-1"></i>Chon don hang...
                            </button>
                        </div>
                    </div>

                    {{-- customer selector (shown only when no order selected) --}}
                    <div class="mb-3" id="customerSection">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Khach hang <span class="text-muted fw-normal">(neu khong co don hang)</span></label>
                        <div id="customerCard" class="entity-card d-none">
                            <div class="ec-header">
                                <i class="bi bi-person me-1 text-success"></i>Thong tin khach hang
                            </div>
                            <div id="customerCardBody"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary ec-reset" onclick="resetCustomer()">
                                <i class="bi bi-x-circle me-1"></i>Chon lai
                            </button>
                        </div>
                        <div id="customerPickerBtn">
                            <button type="button" class="btn btn-outline-success btn-sm"
                                    onclick="openCustomerPopup()">
                                <i class="bi bi-search me-1"></i>Chon khach hang...
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">So tien (VND) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="f_amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}" min="0" step="1000" placeholder="0">
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Phuong thuc</label>
                            <select name="method" class="form-select">
                                <option value="">-- Chon --</option>
                                @foreach(['cash'=>'Tien mat','bank_transfer'=>'Chuyen khoan','momo'=>'MoMo','zalo_pay'=>'ZaloPay','other'=>'Khac'] as $v=>$l)
                                    <option value="{{ $v }}" {{ old('method')===$v ? 'selected':'' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ghi chu</label>
                        <textarea name="note" class="form-control" rows="2" maxlength="1000"
                                  placeholder="Mo ta giao dich...">{{ old('note') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Anh bien lai / chung tu <span class="text-muted small">(tuy chon)</span></label>
                        <input type="file" name="receipt_image" id="receiptFile" class="form-control" accept="image/*">
                        <div id="receiptPreview" class="mt-2"></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-1"></i>Gui phe duyet
                        </button>
                        <a href="{{ route('accounting.cashflow') }}" class="btn btn-outline-secondary">Huy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: pending transactions ─────────────────────── --}}
    <div>
        <div class="acc-card">
            <div class="card-body">
                <div class="fw-bold mb-3 fs-6"><i class="bi bi-hourglass-split me-2 text-warning"></i>Cho duyet</div>
                @php
                    $pendingTxns = \App\Models\Transaction::query()
                        ->with(['submitter:id,name','order:id,code','customer:id,name'])
                        ->where('status', \App\Models\Transaction::STATUS_PENDING_APPROVAL)
                        ->latest()
                        ->limit(20)
                        ->get();
                    $authUser = auth()->user();
                    $approvalSvc = app(\App\Services\ApprovalService::class);
                @endphp
                @forelse($pendingTxns as $txn)
                    @php
                        $canAct = $authUser->hasRole('admin') || $authUser->hasRole('accountant')
                            || $approvalSvc->canApproveTransactionStep($txn, $authUser);
                        $typeColors = ['payment'=>'success','refund'=>'danger','fee'=>'warning','extra_income'=>'info','extra_expense'=>'purple'];
                        $tc = $typeColors[$txn->type] ?? 'secondary';
                    @endphp
                    <div class="border rounded p-2 mb-2 small">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">#{{ $txn->id }}</span>
                            <span class="badge bg-{{ $tc }} pending-badge">{{ $txn->type }}</span>
                        </div>
                        <div class="text-muted">{{ number_format((float)$txn->amount,0,',','.') }}đ
                            @if($txn->method) | {{ $txn->method }} @endif
                        </div>
                        @if($txn->order) <div class="text-muted">Don: {{ $txn->order->code }}</div> @endif
                        @if($txn->customer) <div class="text-muted">KH: {{ $txn->customer->name }}</div> @endif
                        <div class="text-muted">Nguoi tao: {{ $txn->submitter?->name ?? '-' }}</div>
                        @if($txn->note) <div class="fst-italic text-muted">{{ \Str::limit($txn->note,60) }}</div> @endif

                        @if($canAct)
                            <div class="d-flex gap-1 mt-2">
                                <form action="{{ route('accounting.transactions.approve', $txn) }}" method="POST" class="flex-grow-1">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm w-100"
                                            onclick="return confirm('Duyet giao dich #{{ $txn->id }}?')">
                                        <i class="bi bi-check2 me-1"></i>Duyet
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm flex-grow-1"
                                        data-bs-toggle="modal" data-bs-target="#rejectTxnModal{{ $txn->id }}">
                                    <i class="bi bi-x me-1"></i>Tu choi
                                </button>
                            </div>
                        @endif
                    </div>

                    @if($canAct)
                    <div class="modal fade" id="rejectTxnModal{{ $txn->id }}" tabindex="-1">
                        <div class="modal-dialog modal-sm">
                            <form action="{{ route('accounting.transactions.reject', $txn) }}" method="POST" class="modal-content">
                                @csrf
                                <div class="modal-header py-2">
                                    <h6 class="modal-title">Tu choi GD #{{ $txn->id }}</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <textarea name="reason" class="form-control" rows="3" required placeholder="Ly do..."></textarea>
                                </div>
                                <div class="modal-footer py-2">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Huy</button>
                                    <button type="submit" class="btn btn-danger btn-sm">Xac nhan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif
                @empty
                    <div class="text-center text-muted py-3 small">Khong co giao dich cho duyet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ══ Order Popup Modal ═══════════════════════════════════════════ --}}
{{-- ══ Category Popup Modal ══════════════════════════════════════════ --}}
<div class="modal fade" id="categoryPopupModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-grid-3x3-gap me-2"></i>Chọn danh mục giao dịch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 d-flex gap-2">
                    <input type="text" id="cat_search" class="form-control" placeholder="Tìm theo tên hoặc mã...">
                </div>
                <div id="cat_grid" class="row g-2">
                    @foreach($transactionCategories as $cat)
                        <div class="col-6 col-md-4 cat-item" data-search="{{ strtolower($cat->code . ' ' . $cat->name . ' ' . ($cat->flow_direction ?? 'out')) }}">
                            <div class="border rounded p-2 d-flex align-items-center gap-2 cat-btn"
                                 style="cursor:pointer;transition:all .12s"
                                 onclick="selectCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ addslashes($cat->code) }}', '{{ $cat->flow_direction ?? 'out' }}')">
                                <span class="badge bg-primary fw-bold" style="font-size:11px;min-width:60px">{{ $cat->code }}</span>
                                <div>
                                    <span class="small fw-semibold d-block">{{ $cat->name }}</span>
                                    <span class="badge {{ ($cat->flow_direction ?? 'out') === 'in' ? 'bg-success' : 'bg-danger' }}" style="font-size:10px">
                                        {{ ($cat->flow_direction ?? 'out') === 'in' ? 'Thu vào tài khoản' : 'Chi từ tài khoản' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <hr>
                <div>
                    <div class="fw-semibold small text-muted mb-2"><i class="bi bi-plus-circle me-1"></i>Thêm danh mục mới</div>
                    <div class="row g-2 align-items-end">
                        <div class="col-4">
                            <label class="form-label form-label-sm">Mã giao dịch</label>
                            <input type="text" id="new_cat_code" class="form-control form-control-sm" placeholder="VD: CPKD2" maxlength="20">
                        </div>
                        <div class="col-5">
                            <label class="form-label form-label-sm">Tên danh mục</label>
                            <input type="text" id="new_cat_name" class="form-control form-control-sm" placeholder="Tên đầy đủ..." maxlength="100">
                        </div>
                        <div class="col-3">
                            <label class="form-label form-label-sm">Chiều tiền</label>
                            <select id="new_cat_flow" class="form-select form-select-sm">
                                <option value="in">Thu vào TK</option>
                                <option value="out" selected>Chi từ TK</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-success btn-sm w-100" id="btn_save_cat">
                                <i class="bi bi-plus-lg"></i> Lưu
                            </button>
                        </div>
                    </div>
                    <div id="cat_error" class="text-danger small mt-1" style="display:none"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="orderPopupModal" tabindex="-1" aria-labelledby="orderPopupLabel">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderPopupLabel"><i class="bi bi-bag-check me-2"></i>Chon don hang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="filter-bar">
                    <input type="date" id="op_date" class="form-control" style="width:160px" placeholder="Ngay">
                    <input type="text" id="op_keyword" class="form-control" style="width:200px" placeholder="Ma don, ten khach...">
                    <select id="op_per_page" class="form-select" style="width:100px">
                        <option value="15">15/trang</option>
                        <option value="30">30/trang</option>
                        <option value="50">50/trang</option>
                    </select>
                    <button class="btn btn-primary btn-sm" onclick="loadOrders(1)"><i class="bi bi-search"></i> Loc</button>
                </div>
                <div id="orderTableWrap">
                    <div class="text-center text-muted py-4">Dang tai...</div>
                </div>
                <div id="orderPagination" class="d-flex gap-2 mt-2 flex-wrap"></div>
            </div>
        </div>
    </div>
</div>

{{-- ══ Customer Popup Modal ════════════════════════════════════════ --}}
<div class="modal fade" id="customerPopupModal" tabindex="-1" aria-labelledby="customerPopupLabel">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerPopupLabel"><i class="bi bi-people me-2"></i>Chon khach hang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="filter-bar">
                    <input type="text" id="cp_keyword" class="form-control" style="width:220px" placeholder="Ten, SĐT, ma khach...">
                    <select id="cp_sort_by" class="form-select" style="width:130px">
                        <option value="name">Sap theo ten</option>
                        <option value="id">Sap theo ID</option>
                    </select>
                    <select id="cp_sort_dir" class="form-select" style="width:110px">
                        <option value="asc">Tang dan</option>
                        <option value="desc">Giam dan</option>
                    </select>
                    <select id="cp_per_page" class="form-select" style="width:100px">
                        <option value="15">15/trang</option>
                        <option value="30">30/trang</option>
                    </select>
                    <button class="btn btn-primary btn-sm" onclick="loadCustomers(1)"><i class="bi bi-search"></i> Loc</button>
                </div>
                <div id="customerTableWrap">
                    <div class="text-center text-muted py-4">Dang tai...</div>
                </div>
                <div id="customerPagination" class="d-flex gap-2 mt-2 flex-wrap"></div>
            </div>
        </div>
    </div>
</div>

<script>
// ── Type selector ──────────────────────────────────────────────────
function selectType(val) {
    ['payment','refund','fee','extra_income','extra_expense'].forEach(t => {
        const el = document.getElementById('tl-' + t);
        if (!el) return;
        el.className = 'txn-type-btn';
        el.querySelector('input').checked = false;
    });
    const chosen = document.getElementById('tl-' + val);
    if (chosen) { chosen.classList.add('sel-' + val); chosen.querySelector('input').checked = true; }
}

// ── Receipt preview ────────────────────────────────────────────────
document.getElementById('receiptFile').addEventListener('change', function () {
    const p = document.getElementById('receiptPreview');
    p.innerHTML = '';
    if (this.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'max-width:180px;max-height:140px;border-radius:8px;border:1px solid #dee2e6;';
            p.appendChild(img);
        };
        r.readAsDataURL(this.files[0]);
    }
});

// ── Formatters ─────────────────────────────────────────────────────
const fmt = n => Number(n).toLocaleString('vi-VN');
const fmtCur = n => fmt(n) + '₫';

// ── Order state ────────────────────────────────────────────────────
function openOrderPopup() {
    const m = new bootstrap.Modal(document.getElementById('orderPopupModal'));
    m.show();
    loadOrders(1);
}

function resetOrder() {
    document.getElementById('f_order_id').value = '';
    document.getElementById('orderCard').classList.add('d-none');
    document.getElementById('orderCardBody').innerHTML = '';
    document.getElementById('orderPickerBtn').classList.remove('d-none');
    document.getElementById('customerSection').classList.remove('d-none');
    document.getElementById('f_amount').value = '';
}

function selectOrder(orderId) {
    fetch('{{ route('accounting.api.order-detail', ['order' => '__ID__']) }}'.replace('__ID__', orderId))
        .then(r => r.json())
        .then(data => {
            document.getElementById('f_order_id').value = orderId;
            document.getElementById('f_customer_id').value = data.customer?.id ?? '';

            const o = data.order;
            const c = data.customer;
            const payColor = o.amount_due > 0 ? 'danger' : 'success';

            let html = `
                <div class="ec-row"><span class="ec-label">Ma don</span><span class="ec-val">${o.code}</span></div>
                <div class="ec-row"><span class="ec-label">Ngay tao</span><span class="ec-val">${o.created_at}</span></div>
                <div class="ec-row"><span class="ec-label">Tong gia tri</span><span class="ec-val">${fmtCur(o.total)}</span></div>
                <div class="ec-row"><span class="ec-label">Da thanh toan</span><span class="ec-val text-success">${fmtCur(o.amount_paid)}</span></div>
                <div class="ec-row"><span class="ec-label">Con no</span><span class="ec-val text-${payColor} fw-bold">${fmtCur(o.amount_due)}</span></div>
                <div class="ec-row"><span class="ec-label">TT thanh toan</span><span class="ec-val">${o.payment_status}</span></div>
                <div class="ec-row"><span class="ec-label">TT don</span><span class="ec-val">${o.status}</span></div>`;

            if (c) {
                html += `<hr class="my-2">
                <div class="ec-row"><span class="ec-label">Khach hang</span><span class="ec-val">${c.name}</span></div>
                <div class="ec-row"><span class="ec-label">SĐT</span><span class="ec-val">${c.phone ?? '-'}</span></div>
                <div class="ec-row"><span class="ec-label">Tong cong no KH</span><span class="ec-val text-danger">${fmtCur(c.total_debt)}</span></div>`;
            }

            document.getElementById('orderCardBody').innerHTML = html;
            document.getElementById('orderCard').classList.remove('d-none');
            document.getElementById('orderPickerBtn').classList.add('d-none');
            document.getElementById('customerSection').classList.add('d-none');

            if (o.amount_due > 0) {
                document.getElementById('f_amount').value = Math.round(o.amount_due);
            }

            bootstrap.Modal.getInstance(document.getElementById('orderPopupModal'))?.hide();
        });
}

// ── Customer state ─────────────────────────────────────────────────
function openCustomerPopup() {
    const m = new bootstrap.Modal(document.getElementById('customerPopupModal'));
    m.show();
    loadCustomers(1);
}

function resetCustomer() {
    document.getElementById('f_customer_id').value = '';
    document.getElementById('customerCard').classList.add('d-none');
    document.getElementById('customerCardBody').innerHTML = '';
    document.getElementById('customerPickerBtn').classList.remove('d-none');
}

function selectCustomer(customerId) {
    fetch('{{ route('accounting.api.customer-detail', ['customer' => '__ID__']) }}'.replace('__ID__', customerId))
        .then(r => r.json())
        .then(c => {
            document.getElementById('f_customer_id').value = c.id;
            const html = `
                <div class="ec-row"><span class="ec-label">Ho ten</span><span class="ec-val">${c.name}</span></div>
                <div class="ec-row"><span class="ec-label">SDT</span><span class="ec-val">${c.phone ?? '-'}</span></div>
                <div class="ec-row"><span class="ec-label">Email</span><span class="ec-val">${c.email ?? '-'}</span></div>
                <div class="ec-row"><span class="ec-label">Ma KH</span><span class="ec-val">${c.code ?? '-'}</span></div>
                <div class="ec-row"><span class="ec-label">Tong don hang</span><span class="ec-val">${c.total_orders}</span></div>
                <div class="ec-row"><span class="ec-label">Tong gia tri mua</span><span class="ec-val">${fmtCur(c.total_spent)}</span></div>
                <div class="ec-row"><span class="ec-label">Cong no hien tai</span><span class="ec-val text-danger fw-bold">${fmtCur(c.total_debt)}</span></div>
                <div class="ec-row"><span class="ec-label">Don hang cuoi</span><span class="ec-val">${c.last_order_at ?? '-'}</span></div>`;
            document.getElementById('customerCardBody').innerHTML = html;
            document.getElementById('customerCard').classList.remove('d-none');
            document.getElementById('customerPickerBtn').classList.add('d-none');
            bootstrap.Modal.getInstance(document.getElementById('customerPopupModal'))?.hide();
        });
}

// ── Load orders (popup) ────────────────────────────────────────────
let orderPage = 1;
function loadOrders(page) {
    orderPage = page;
    const params = new URLSearchParams({
        page,
        date:     document.getElementById('op_date').value,
        keyword:  document.getElementById('op_keyword').value,
        per_page: document.getElementById('op_per_page').value,
    });
    document.getElementById('orderTableWrap').innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';
    fetch('{{ route('accounting.api.orders-list') }}?' + params)
        .then(r => r.json())
        .then(data => {
            if (!data.data.length) {
                document.getElementById('orderTableWrap').innerHTML = '<div class="text-muted text-center py-3">Khong co ket qua.</div>';
                document.getElementById('orderPagination').innerHTML = '';
                return;
            }
            const statusBadge = s => {
                const map = {paid:'success',partially_paid:'warning',unpaid:'danger'};
                return `<span class="badge bg-${map[s]||'secondary'}" style="font-size:10px">${s}</span>`;
            };
            let rows = data.data.map(o => `
                <tr onclick="selectOrder(${o.id})">
                    <td class="fw-semibold">${o.code}</td>
                    <td>${o.customer_name}</td>
                    <td>${o.sale_name}</td>
                    <td class="text-end">${fmt(o.total)}đ</td>
                    <td class="text-end text-danger">${fmt(o.amount_due)}đ</td>
                    <td>${statusBadge(o.payment_status)}</td>
                    <td class="text-muted">${o.created_at}</td>
                </tr>`).join('');
            document.getElementById('orderTableWrap').innerHTML = `
                <div class="table-responsive">
                <table class="table table-hover popup-table">
                    <thead><tr>
                        <th>Ma don</th><th>Khach hang</th><th>Sale</th>
                        <th class="text-end">Tong</th><th class="text-end">Con no</th>
                        <th>Thanh toan</th><th>Ngay tao</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table></div>
                <small class="text-muted">Hien thi ${data.data.length}/${data.total} ket qua</small>`;
            renderPagination('orderPagination', data.current_page, data.last_page, 'loadOrders');
        });
}

// ── Load customers (popup) ─────────────────────────────────────────
let customerPage = 1;
function loadCustomers(page) {
    customerPage = page;
    const params = new URLSearchParams({
        page,
        keyword:  document.getElementById('cp_keyword').value,
        sort_by:  document.getElementById('cp_sort_by').value,
        sort_dir: document.getElementById('cp_sort_dir').value,
        per_page: document.getElementById('cp_per_page').value,
    });
    document.getElementById('customerTableWrap').innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';
    fetch('{{ route('accounting.api.customers-list') }}?' + params)
        .then(r => r.json())
        .then(data => {
            if (!data.data.length) {
                document.getElementById('customerTableWrap').innerHTML = '<div class="text-muted text-center py-3">Khong co ket qua.</div>';
                document.getElementById('customerPagination').innerHTML = '';
                return;
            }
            let rows = data.data.map(c => `
                <tr onclick="selectCustomer(${c.id})">
                    <td class="fw-semibold">${c.name}</td>
                    <td>${c.phone ?? '-'}</td>
                    <td>${c.code ?? '-'}</td>
                </tr>`).join('');
            document.getElementById('customerTableWrap').innerHTML = `
                <div class="table-responsive">
                <table class="table table-hover popup-table">
                    <thead><tr><th>Ho ten</th><th>SDT</th><th>Ma KH</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table></div>
                <small class="text-muted">Hien thi ${data.data.length}/${data.total} ket qua</small>`;
            renderPagination('customerPagination', data.current_page, data.last_page, 'loadCustomers');
        });
}

// ── Pagination renderer ────────────────────────────────────────────
function renderPagination(containerId, current, last, fn) {
    const el = document.getElementById(containerId);
    let html = '';
    for (let p = 1; p <= last; p++) {
        html += `<button class="btn btn-sm ${p===current?'btn-primary':'btn-outline-secondary'}" onclick="${fn}(${p})">${p}</button>`;
    }
    el.innerHTML = html;
}

// ── Keyboard search triggers ───────────────────────────────────────
document.getElementById('op_keyword').addEventListener('keydown', e => { if(e.key==='Enter'){e.preventDefault();loadOrders(1);} });
document.getElementById('cp_keyword').addEventListener('keydown', e => { if(e.key==='Enter'){e.preventDefault();loadCustomers(1);} });

// ── Category popup ─────────────────────────────────────────────────
function openCategoryPopup() {
    const m = new bootstrap.Modal(document.getElementById('categoryPopupModal'));
    m.show();
}
function clearCategory() {
    document.getElementById('f_cat_id').value = '';
    document.getElementById('f_cat_display').value = '';
    document.getElementById('btn_clear_cat').style.display = 'none';
}
function selectCategory(id, name, code, flowDirection) {
    document.getElementById('f_cat_id').value = id;
    const flowText = flowDirection === 'in' ? 'Thu vào TK' : 'Chi từ TK';
    document.getElementById('f_cat_display').value = '[' + code + '] ' + name + ' - ' + flowText;
    document.getElementById('btn_clear_cat').style.display = '';
    bootstrap.Modal.getInstance(document.getElementById('categoryPopupModal'))?.hide();
}

// Category search filter
document.getElementById('cat_search').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.cat-item').forEach(el => {
        el.style.display = el.dataset.search.includes(q) ? '' : 'none';
    });
});

// Hover effect on category buttons
document.querySelectorAll('.cat-btn').forEach(btn => {
    btn.addEventListener('mouseenter', () => btn.style.background = '#eff6ff');
    btn.addEventListener('mouseleave', () => btn.style.background = '');
});

// Save new category via AJAX
document.getElementById('btn_save_cat').addEventListener('click', function () {
    const code = document.getElementById('new_cat_code').value.trim();
    const name = document.getElementById('new_cat_name').value.trim();
    const flow_direction = document.getElementById('new_cat_flow').value;
    const errDiv = document.getElementById('cat_error');
    if (!code || !name) { errDiv.textContent = 'Vui lòng nhập đủ mã và tên.'; errDiv.style.display = ''; return; }
    this.disabled = true;
    fetch('{{ route('accounting.transaction-categories.store') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ code, name, flow_direction })
    })
    .then(r => r.json())
    .then(data => {
        if (data.id) {
            // Add to grid
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 cat-item';
            col.dataset.search = (data.code + ' ' + data.name + ' ' + (data.flow_direction || 'out')).toLowerCase();
            const flowBadgeClass = (data.flow_direction === 'in') ? 'bg-success' : 'bg-danger';
            const flowLabel = (data.flow_direction === 'in') ? 'Thu vào tài khoản' : 'Chi từ tài khoản';
            col.innerHTML = `<div class="border rounded p-2 d-flex align-items-center gap-2 cat-btn"
                style="cursor:pointer;transition:all .12s"
                onclick="selectCategory(${data.id}, '${data.name.replace(/'/g,"\\'")}', '${data.code.replace(/'/g,"\\'")}', '${(data.flow_direction || 'out').replace(/'/g,"\\'")}')">
                <span class="badge bg-primary fw-bold" style="font-size:11px;min-width:60px">${data.code}</span>
                <div>
                    <span class="small fw-semibold d-block">${data.name}</span>
                    <span class="badge ${flowBadgeClass}" style="font-size:10px">${flowLabel}</span>
                </div>
            </div>`;
            document.getElementById('cat_grid').appendChild(col);
            document.getElementById('new_cat_code').value = '';
            document.getElementById('new_cat_name').value = '';
            document.getElementById('new_cat_flow').value = 'out';
            errDiv.style.display = 'none';
            // Auto-select newly added
            selectCategory(data.id, data.name, data.code, data.flow_direction || 'out');
        } else {
            const msg = Object.values(data.errors || {}).flat()[0] || data.message || 'Lỗi không xác định';
            errDiv.textContent = msg; errDiv.style.display = '';
        }
    })
    .catch(() => { errDiv.textContent = 'Lỗi kết nối.'; errDiv.style.display = ''; })
    .finally(() => { this.disabled = false; });
});
</script>
@endsection

