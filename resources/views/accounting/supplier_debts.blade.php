@extends(accounting_layout())

@section('title', 'Công nợ nhà cung cấp')
@section('subtitle', 'Theo dõi đơn mua, khối lượng nhận, hạn nợ và từng lần thanh toán')

@section('accounting_content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>Chưa thể ghi nhận thanh toán.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Công nợ theo bộ lọc</div><div class="fs-3 fw-bold text-danger">{{ number_format($totalDebt) }}đ</div><div class="small">{{ $supplierDebts->where('remaining_amount', '>', 0)->count() }} nguồn hàng còn nợ</div></div></div></div>
    <div class="col-md-4"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Nợ quá hạn</div><div class="fs-3 fw-bold text-warning">{{ number_format($totalOverdue) }}đ</div><div class="small">Các khoản có hạn trả trước hôm nay</div></div></div></div>
    <div class="col-md-4"><div class="acc-card h-100"><div class="card-body"><div class="text-muted small">Đã thanh toán</div><div class="fs-3 fw-bold text-success">{{ number_format($totalPaid) }}đ</div><div class="small">Trên các đơn mua đang hiển thị</div></div></div></div>
</div>

<div class="acc-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3"><label class="form-label">Tìm nguồn hàng / mã đơn</label><input type="text" class="form-control" name="keyword" value="{{ $keyword }}" placeholder="Tên, điện thoại hoặc mã TM..."></div>
            <div class="col-lg-2"><label class="form-label">Loại nguồn</label><select class="form-select" name="source_type"><option value="">Tất cả</option><option value="farm" @selected($sourceType === 'farm')>Trang trại</option><option value="supplier" @selected($sourceType === 'supplier')>Nhà cung cấp</option></select></div>
            <div class="col-lg-2"><label class="form-label">Tình trạng nợ</label><select class="form-select" name="debt_status"><option value="outstanding" @selected($debtStatus === 'outstanding')>Còn nợ</option><option value="overdue" @selected($debtStatus === 'overdue')>Quá hạn</option><option value="due_soon" @selected($debtStatus === 'due_soon')>Đến hạn 7 ngày</option><option value="paid" @selected($debtStatus === 'paid')>Đã tất toán</option><option value="all" @selected($debtStatus === 'all')>Tất cả đơn</option></select></div>
            <div class="col-lg-2"><label class="form-label">Từ ngày mua</label><input type="date" class="form-control" name="from_date" value="{{ request('from_date') }}"></div>
            <div class="col-lg-2"><label class="form-label">Đến ngày mua</label><input type="date" class="form-control" name="to_date" value="{{ request('to_date') }}"></div>
            <div class="col-lg-1 d-flex gap-1"><button class="btn btn-primary flex-grow-1" title="Lọc"><i class="bi bi-funnel"></i></button><a href="{{ route('accounting.supplier-debts') }}" class="btn btn-outline-secondary" title="Đặt lại"><i class="bi bi-arrow-counterclockwise"></i></a></div>
        </form>
    </div>
</div>

<div class="acc-card">
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Nguồn hàng</th><th class="text-center">Đơn mua</th><th class="text-end">Số lượng / khối lượng</th><th class="text-end">Tổng tiền</th><th class="text-end">Đã trả</th><th class="text-end">Còn nợ</th><th>Hạn gần nhất</th><th></th></tr></thead>
        <tbody>
        @forelse($supplierDebts as $debt)
            @php
                $source = $debt['source'];
            @endphp
            <tr>
                <td><div class="fw-semibold">{{ $source?->name ?? 'Nguồn hàng đã xóa' }}</div><div class="small text-muted"><span class="badge text-bg-light border me-1">{{ $debt['type'] }}</span>{{ $source?->phone ?: 'Chưa có SĐT' }}</div></td>
                <td class="text-center"><span class="badge text-bg-secondary">{{ $debt['purchase_count'] }}</span></td>
                <td class="text-end">{{ number_format($debt['quantity']) }} con<div class="small text-muted">{{ number_format($debt['weight'], 1) }} kg</div></td>
                <td class="text-end">{{ number_format($debt['total_amount']) }}đ</td>
                <td class="text-end text-success">{{ number_format($debt['paid_amount']) }}đ</td>
                <td class="text-end fw-bold {{ $debt['remaining_amount'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($debt['remaining_amount']) }}đ@if($debt['overdue_amount'] > 0)<div class="small text-warning">Quá hạn {{ number_format($debt['overdue_amount']) }}đ</div>@endif</td>
                <td>@if($debt['nearest_due_date']){{ \Carbon\Carbon::parse($debt['nearest_due_date'])->format('d/m/Y') }}@else<span class="text-muted">Chưa đặt hạn</span>@endif</td>
                <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#supplierDebt{{ $debt['key'] }}"><i class="bi bi-eye me-1"></i>Chi tiết</button></td>
            </tr>
            <tr class="collapse" id="supplierDebt{{ $debt['key'] }}"><td colspan="8" class="bg-light p-3">
                <div class="table-responsive"><table class="table table-sm align-middle mb-0 supplier-detail-table">
                    <thead><tr><th>Ngày / Mã mua</th><th>Khối lượng đặt</th><th>Kho thực nhận</th><th>Trạng thái kho</th><th class="text-end">Giá trị</th><th class="text-end">Đã trả / Còn nợ</th><th>Hạn trả</th><th>Phiếu / thao tác</th></tr></thead>
                    <tbody>
                    @foreach($debt['purchases'] as $purchase)
                        @php
                            $receivedItems = $purchase->items->where('stage', 'received')->whereIn('item_type', ['processed_duck', 'reject']);
                            $receivedQuantity = (int) $receivedItems->sum('quantity');
                            $receivedWeight = (float) $receivedItems->sum('weight');
                            $requestStatus = $purchase->paymentRequest?->status;
                            $requestLabel = match($requestStatus) {'pending_approval' => 'Chờ duyệt', 'approved_pending_completion' => 'Chờ chuyển tiền', 'approved' => 'Đã chuyển tiền', 'rejected' => 'Đã từ chối', default => null};
                        @endphp
                        <tr>
                            <td>{{ $purchase->purchased_at->format('d/m/Y') }}<div class="fw-semibold">{{ $purchase->code }}</div><div class="small text-muted">{{ $purchase->entry_mode === 'product_lines' ? 'Theo sản phẩm' : ($purchase->purchase_type === 'live_duck' ? 'Vịt lông' : 'Vịt sơ chế') }}</div>@if($purchase->entry_mode === 'product_lines')<div class="small">@foreach($purchase->productItems as $productItem)<span class="badge text-bg-light border">{{ $productItem->productVariant?->product?->name }} - {{ $productItem->productVariant?->name }}</span>@endforeach</div>@endif</td>
                            <td>{{ number_format($purchase->quantity) }} đơn vị<div class="small text-muted">{{ number_format($purchase->total_weight, 1) }} kg @if($purchase->entry_mode !== 'product_lines')· {{ number_format($purchase->unit_price) }}đ/kg@endif</div></td>
                            <td>@if($purchase->status === \App\Models\ProcurementPurchase::STATUS_RECEIVED){{ number_format($receivedQuantity) }} con<div class="small text-muted">{{ $receivedWeight > 0 ? number_format($receivedWeight, 1).' kg' : 'Kho chưa nhập kg chi tiết' }}</div>@else<span class="text-muted">Chưa tiếp nhận</span>@endif</td>
                            <td><span class="badge {{ $purchase->status === 'received' ? 'text-bg-success' : ($purchase->status === 'sent_to_warehouse' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ match($purchase->status) {'received' => 'Đã nhập kho', 'sent_to_warehouse' => 'Chờ kho nhận', default => 'Nháp'} }}</span>@if($purchase->warehouse)<div class="small text-muted">{{ $purchase->warehouse->name }}</div>@endif</td>
                            <td class="text-end">{{ number_format($purchase->total_amount) }}đ<div class="small text-muted">Tiền hàng {{ number_format($purchase->subtotal) }}đ</div></td>
                            <td class="text-end"><span class="text-success">{{ number_format($purchase->paid_amount) }}đ</span><div class="fw-semibold {{ $purchase->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">Còn {{ number_format($purchase->remaining_amount) }}đ</div>@if($purchase->debtPayments->isNotEmpty())<button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="collapse" data-bs-target="#paymentHistory{{ $purchase->id }}">{{ $purchase->debtPayments->count() }} lần trả</button>@endif</td>
                            <td>@if($purchase->payment_due_date)<span class="{{ $purchase->remaining_amount > 0 && $purchase->payment_due_date->isBefore(today()) ? 'text-danger fw-semibold' : '' }}">{{ $purchase->payment_due_date->format('d/m/Y') }}</span>@else<span class="text-muted">—</span>@endif</td>
                            <td>@if($purchase->paymentRequest)<a class="badge text-bg-info text-decoration-none mb-1" href="{{ route('accounting.cashflow.show', $purchase->paymentRequest) }}">Phiếu #{{ $purchase->paymentRequest->id }} · {{ $requestLabel }}</a>@else<div class="small text-muted mb-1">Chưa có phiếu đề nghị</div>@endif @if((float)$purchase->remaining_amount <= 0)<span class="badge text-bg-success">Đã tất toán</span>@elseif(in_array($requestStatus, [\App\Models\Transaction::STATUS_PENDING_APPROVAL, \App\Models\Transaction::STATUS_APPROVED_PENDING_COMPLETION], true))<a href="{{ route('accounting.cashflow.show', $purchase->paymentRequest) }}" class="btn btn-sm btn-outline-primary">Xử lý trên phiếu</a>@else<button type="button" class="btn btn-sm btn-success js-record-payment" data-action="{{ route('accounting.supplier-debts.payments.store', $purchase) }}" data-code="{{ $purchase->code }}" data-source="{{ $source?->name }}" data-remaining="{{ (float)$purchase->remaining_amount }}"><i class="bi bi-cash-coin me-1"></i>Ghi nhận trả</button>@endif</td>
                        </tr>
                        @if($purchase->debtPayments->isNotEmpty())
                            <tr class="collapse" id="paymentHistory{{ $purchase->id }}"><td colspan="8" class="payment-history-cell"><div class="small fw-semibold mb-1">Lịch sử thanh toán</div>@foreach($purchase->debtPayments as $payment)<div class="d-flex flex-wrap gap-3 border-top py-1"><span>{{ $payment->paid_at->format('d/m/Y H:i') }}</span><span class="text-success fw-semibold">{{ number_format($payment->amount) }}đ</span><span>{{ $payment->recorder?->name ?? 'Dữ liệu chuyển đổi' }}</span>@if($payment->transaction)<a href="{{ route('accounting.cashflow.show', $payment->transaction) }}">Giao dịch #{{ $payment->transaction_id }}</a>@endif<span class="text-muted">{{ $payment->note }}</span></div>@endforeach</td></tr>
                        @endif
                    @endforeach
                    </tbody>
                </table></div>
            </td></tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-5">Không có đơn mua hoặc công nợ phù hợp bộ lọc.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

@if($legacyPayables->isNotEmpty())
<div class="alert alert-warning mt-3"><strong>Dữ liệu công nợ cũ chưa gắn với đơn mua</strong><div class="small">Các khoản dưới đây thuộc bảng cũ `accounting_supplier_payables`, chỉ dùng để đối chiếu và chưa thể cập nhật thanh toán theo đơn.</div></div>
<div class="acc-card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Công ty</th><th class="text-end">Số tiền cũ</th><th>Hạn</th><th>Trạng thái</th><th>Ghi chú</th></tr></thead><tbody>@foreach($legacyPayables as $payable)<tr><td>{{ $payable->company_name }}</td><td class="text-end">{{ number_format($payable->amount) }}đ</td><td>{{ $payable->due_date ? \Carbon\Carbon::parse($payable->due_date)->format('d/m/Y') : '—' }}</td><td>{{ $payable->status }}</td><td>{{ $payable->note }}</td></tr>@endforeach</tbody></table></div></div>
@endif

<div class="modal fade" id="recordSupplierPaymentModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="POST" class="modal-content" id="recordSupplierPaymentForm">@csrf
    <div class="modal-header"><div><h5 class="modal-title">Ghi nhận thanh toán công nợ</h5><div class="small text-muted" id="paymentPurchaseInfo"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="alert alert-info py-2 small">Thao tác này ghi một giao dịch chi thực tế và cập nhật ngay số đã trả/còn nợ của đơn mua.</div><div class="mb-3"><label class="form-label">Số tiền thanh toán</label><div class="input-group"><input type="number" min="1" step="1" class="form-control" name="amount" id="supplierPaymentAmount" required><span class="input-group-text">đ</span></div><div class="form-text" id="supplierPaymentRemaining"></div></div><div class="mb-3"><label class="form-label">Tài khoản chi</label><select class="form-select" name="account_id" required><option value="">Chọn tài khoản</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }} · Số dư {{ number_format($account->balance) }}đ</option>@endforeach</select></div><div class="mb-3"><label class="form-label">Ngày thanh toán</label><input type="datetime-local" class="form-control" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></div><div><label class="form-label">Ghi chú / chứng từ</label><textarea class="form-control" name="note" rows="2" placeholder="Số UNC, nội dung chuyển khoản..."></textarea></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Xác nhận đã trả</button></div>
</form></div></div>

@push('styles')
<style>.supplier-detail-table{font-size:.86rem}.supplier-detail-table>tbody>tr>td{background:#fff}.payment-history-cell{background:#f8fafc!important;padding:.65rem 1rem!important}.acc-card .table thead th{white-space:nowrap}</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('recordSupplierPaymentModal');
    const modal = modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
    const form = document.getElementById('recordSupplierPaymentForm');
    const amount = document.getElementById('supplierPaymentAmount');
    document.querySelectorAll('.js-record-payment').forEach(button => button.addEventListener('click', () => {
        const remaining = Number(button.dataset.remaining || 0);
        form.action = button.dataset.action;
        amount.value = Math.round(remaining);
        amount.max = remaining;
        document.getElementById('paymentPurchaseInfo').textContent = `${button.dataset.source} · ${button.dataset.code}`;
        document.getElementById('supplierPaymentRemaining').textContent = `Công nợ còn lại: ${remaining.toLocaleString('vi-VN')}đ`;
        modal.show();
    }));
});
</script>
@endpush
@endsection
