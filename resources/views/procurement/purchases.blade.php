@extends('layouts.procurement')
@section('title', request()->routeIs('procurement.warehouse-shipments.*') ? 'Danh sách nhập kho' : 'Nhật ký thu mua')
@section('subtitle', 'Theo dõi mua vịt lông, vịt thịt, thanh toán và tiếp nhận kho')
@section('content')
@unless(request()->routeIs('procurement.warehouse-shipments.*'))
    <div class="d-flex justify-content-end mb-3"><button type="button" class="btn btn-primary" data-purchase-form-toggle><i class="bi bi-plus-circle me-1"></i>Tạo thu mua</button></div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white"><strong><i class="bi bi-clipboard-plus me-1"></i>Dán nhật ký từ Excel / Google Sheets</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-2">Copy cả dòng tiêu đề và các dòng dữ liệu rồi dán vào ô dưới đây. Hệ thống nhận các cột theo tên nên có thể bỏ trống cột chưa có dữ liệu.</p>
            <form method="POST" action="{{ route('procurement.purchases.import-pasted') }}" id="pasteImportForm">
                @csrf
                <textarea class="form-control font-monospace" rows="8" name="paste_data" id="pasteData" required placeholder="NGÀY THÁNG&#9;CHỦ TRẠI&#9;ĐỊA CHỈ&#9;SỐ ĐIỆN THOẠI&#9;TRẠI&#9;SL CON&#9;KHỐI LƯỢNG&#9;TB SIZE&#9;GIÁ MUA&#9;TỔNG TIỀN&#9;THANH TOÁN&#9;CÒN LẠI&#9;NGÀY PHẢI TRẢ&#9;GHI CHÚ&#9;Loại Vịt">{{ old('paste_data') }}</textarea>
                <div class="d-flex justify-content-between align-items-center mt-2 gap-2 flex-wrap"><span class="small text-muted" id="pasteRowCount">Chưa có dữ liệu.</span><button class="btn btn-success"><i class="bi bi-cloud-arrow-up me-1"></i>Nhập nhật ký</button></div>
            </form>
            <div class="table-responsive mt-3 d-none" id="pastePreviewWrap"><table class="table table-sm table-bordered mb-0"><thead id="pastePreviewHead"></thead><tbody id="pastePreviewBody"></tbody></table><div class="small text-muted mt-1">Xem trước tối đa 5 dòng đầu.</div></div>
        </div>
    </div>
    @if(session('import_errors'))<div class="alert alert-warning"><strong>Một số dòng chưa nhập được:</strong><ul class="mb-0 mt-1">@foreach(session('import_errors') as $importError)<li>{{ $importError }}</li>@endforeach</ul></div>@endif
    @include('procurement.partials.purchase_form')
@endunless
<div class="card border-0 shadow-sm mb-3"><div class="card-body"><form class="row g-2"><div class="col-md-3"><label>Từ ngày</label><input type="date" name="from_date" value="{{ $from }}" class="form-control"></div><div class="col-md-3"><label>Đến ngày</label><input type="date" name="to_date" value="{{ $to }}" class="form-control"></div><div class="col-md-2 align-self-end"><button class="btn btn-primary w-100">Lọc</button></div></form></div></div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Thời gian/Mã</th><th>Loại/Nguồn</th><th>Số lượng</th><th>Tình trạng</th><th class="text-end">Tổng tiền</th><th>Thanh toán</th><th>Nhập kho</th><th>Thao tác</th></tr></thead><tbody>
@forelse($purchases as $p)
    <tr><td>{{ $p->purchased_at->format('d/m/Y H:i') }}<div class="fw-bold">{{ $p->code }}</div></td><td>{{ $p->purchase_type === 'live_duck' ? 'Vịt lông' : 'Vịt thịt' }}<div class="small text-muted">{{ $p->farm?->name ?? $p->supplier?->name }}</div><div class="small">{{ $p->duck_type ?: '—' }} · Trại {{ $p->farm_type ?: '—' }}</div></td><td>{{ number_format($p->quantity) }} con<div class="small">{{ number_format($p->total_weight, 1) }}kg · TB {{ number_format($p->average_weight, 2) }}</div></td><td>{{ $p->notes ?: ($p->duck_condition ?: '—') }}</td><td class="text-end"><div class="fw-bold">{{ number_format($p->total_amount) }}đ</div><div class="small text-muted">Thu mua: {{ number_format($p->procurement_fee) }}đ</div><div class="small text-muted">Vận chuyển: {{ number_format($p->transportation_fee) }}đ</div></td><td><span class="badge {{ $p->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $p->payment_status }}</span><div class="small text-success">Đã trả: {{ number_format($p->paid_amount) }}đ</div><div class="small text-danger">Còn: {{ number_format($p->remaining_amount) }}đ</div>@if($p->payment_due_date)<div class="small">Hạn: {{ $p->payment_due_date->format('d/m/Y') }}</div>@endif @if($p->payment_transaction_id)<div class="small">Phiếu #{{ $p->payment_transaction_id }}</div>@endif</td><td><span class="badge bg-secondary">{{ $p->status }}</span><div class="small">{{ $p->warehouse?->name }}</div>@if($p->received_at)<div class="small text-success">{{ $p->received_at->format('d/m H:i') }} · {{ $p->warehouse_rating }}★</div><div class="small text-muted">{{ $p->warehouse_condition }}</div>@endif</td><td>@if(!$p->payment_transaction_id && (float) $p->remaining_amount > 0)<button type="button" class="btn btn-sm btn-outline-danger js-preview-payment" data-action="{{ route('procurement.purchases.request-payment', $p) }}" data-code="{{ $p->code }}" data-source="{{ $p->farm?->name ?? $p->supplier?->name ?? 'Nhà cung cấp' }}" data-date="{{ $p->purchased_at->format('d/m/Y H:i') }}" data-description="{{ $p->purchase_type === 'live_duck' ? 'Vịt lông' : 'Vịt thịt' }} · {{ number_format($p->quantity) }} con · {{ number_format($p->total_weight, 1) }}kg" data-subtotal="{{ (float) $p->subtotal }}" data-broker="{{ (float) $p->broker_fee }}" data-processing="{{ (float) $p->processing_fee }}" data-procurement="{{ (float) $p->procurement_fee }}" data-transportation="{{ (float) $p->transportation_fee }}" data-other="{{ (float) $p->other_fee }}" data-total="{{ (float) $p->total_amount }}" data-paid="{{ (float) $p->paid_amount }}" data-remaining="{{ (float) $p->remaining_amount }}" data-due="{{ $p->payment_due_date?->format('d/m/Y') ?? 'Chưa đặt hạn' }}" data-notes="{{ $p->notes ?: 'Không có ghi chú' }}">Yêu cầu thanh toán</button>@else — @endif</td></tr>
    <tr class="table-light"><td colspan="8"><strong>Dự kiến/Phân size:</strong> @foreach($p->items->where('stage', 'expected') as $i)<span class="badge bg-light text-dark border me-1">{{ $i->item_type === 'processed_duck' ? 'Size '.$i->size : ($i->item_type === 'feathers' ? 'Lông' : 'Lòng') }}: {{ number_format($i->quantity) }}</span>@endforeach @if($p->warehouse_comment)<div class="mt-1"><strong>Kho đánh giá:</strong> {{ $p->warehouse_condition }} — {{ $p->warehouse_comment }}</div>@endif</td></tr>
@empty
    <tr><td colspan="8" class="text-center py-5 text-muted">Chưa có dữ liệu.</td></tr>
@endforelse
</tbody></table></div><div class="card-footer">{{ $purchases->links() }}</div></div>

<div class="modal fade" id="paymentReviewModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><div><h5 class="modal-title">Xem lại yêu cầu thanh toán</h5><div class="small text-muted" id="reviewPaymentCode"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-3 mb-3"><div class="col-md-6"><div class="small text-muted">Trang trại / Nhà cung cấp</div><div class="fw-semibold" id="reviewPaymentSource"></div></div><div class="col-md-3"><div class="small text-muted">Ngày thu mua</div><div id="reviewPaymentDate"></div></div><div class="col-md-3"><div class="small text-muted">Ngày phải trả</div><div id="reviewPaymentDue"></div></div><div class="col-12"><div class="small text-muted">Nội dung thu mua</div><div id="reviewPaymentDescription"></div></div></div>
        <div class="table-responsive"><table class="table table-bordered align-middle mb-3"><tbody>
            <tr><td>Tiền hàng</td><td class="text-end" id="reviewSubtotal"></td></tr>
            <tr><td>Phí cò</td><td class="text-end" id="reviewBroker"></td></tr>
            <tr><td>Phí sơ chế (gia công)</td><td class="text-end" id="reviewProcessing"></td></tr>
            <tr><td>Chi phí thu mua chuyến</td><td class="text-end" id="reviewProcurement"></td></tr>
            <tr><td>Chi phí vận chuyển</td><td class="text-end" id="reviewTransportation"></td></tr>
            <tr><td>Phí khác</td><td class="text-end" id="reviewOther"></td></tr>
            <tr class="table-light"><th>Tổng giá trị thu mua</th><th class="text-end" id="reviewTotal"></th></tr>
            <tr><td class="text-success">Đã thanh toán</td><td class="text-end text-success" id="reviewPaid"></td></tr>
            <tr class="table-warning"><th>Số tiền gửi yêu cầu thanh toán</th><th class="text-end text-danger fs-5" id="reviewRemaining"></th></tr>
        </tbody></table></div>
        <div class="small text-muted"><strong>Ghi chú:</strong> <span id="reviewPaymentNotes"></span></div>
        <div class="alert alert-warning mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Vui lòng kiểm tra kỹ. Sau khi xác nhận, phiếu sẽ được gửi vào quy trình duyệt của kế toán.</div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Quay lại kiểm tra</button><form method="POST" id="confirmPaymentForm">@csrf<button class="btn btn-danger"><i class="bi bi-send me-1"></i>Xác nhận gửi yêu cầu</button></form></div>
</div></div></div>
@endsection
@push('scripts')
<script>
(() => {
    const input = document.getElementById('pasteData');
    if (!input) return;
    const count = document.getElementById('pasteRowCount');
    const wrap = document.getElementById('pastePreviewWrap');
    const head = document.getElementById('pastePreviewHead');
    const body = document.getElementById('pastePreviewBody');
    const escapeHtml = value => { const element = document.createElement('div'); element.textContent = value; return element.innerHTML; };
    const preview = () => {
        const rows = input.value.trim().split(/\r?\n/).filter(row => row.trim()).map(row => row.split('\t'));
        if (!rows.length) { count.textContent = 'Chưa có dữ liệu.'; wrap.classList.add('d-none'); return; }
        const hasHeader = /ngày|chủ|chủ|địa chỉ|số điện thoại/i.test(rows[0].join(' '));
        count.textContent = `${Math.max(0, rows.length - (hasHeader ? 1 : 0))} dòng dữ liệu được nhận diện.`;
        const headers = hasHeader ? rows[0] : ['Ngày', 'Chủ trại', 'Địa chỉ', 'Điện thoại', 'Trại', 'SL', 'Kg', 'TB size', 'Giá', 'Tổng', 'Thanh toán', 'Còn lại', 'Ngày trả', 'Ghi chú', 'Loại vịt'];
        const data = hasHeader ? rows.slice(1, 6) : rows.slice(0, 5);
        head.innerHTML = '<tr>' + headers.map(value => `<th class="text-nowrap">${escapeHtml(value)}</th>`).join('') + '</tr>';
        body.innerHTML = data.map(row => '<tr>' + headers.map((_, index) => `<td class="text-nowrap">${escapeHtml(row[index] || '')}</td>`).join('') + '</tr>').join('');
        wrap.classList.remove('d-none');
    };
    input.addEventListener('input', preview);
    input.addEventListener('paste', () => setTimeout(preview));
    preview();
})();
(() => {
    const modalElement = document.getElementById('paymentReviewModal');
    if (!modalElement) return;
    const money = value => Number(value || 0).toLocaleString('vi-VN') + 'đ';
    document.querySelectorAll('.js-preview-payment').forEach(button => button.addEventListener('click', () => {
        document.getElementById('confirmPaymentForm').action = button.dataset.action;
        document.getElementById('reviewPaymentCode').textContent = button.dataset.code;
        document.getElementById('reviewPaymentSource').textContent = button.dataset.source;
        document.getElementById('reviewPaymentDate').textContent = button.dataset.date;
        document.getElementById('reviewPaymentDue').textContent = button.dataset.due;
        document.getElementById('reviewPaymentDescription').textContent = button.dataset.description;
        document.getElementById('reviewPaymentNotes').textContent = button.dataset.notes;
        document.getElementById('reviewSubtotal').textContent = money(button.dataset.subtotal);
        document.getElementById('reviewBroker').textContent = money(button.dataset.broker);
        document.getElementById('reviewProcessing').textContent = money(button.dataset.processing);
        document.getElementById('reviewProcurement').textContent = money(button.dataset.procurement);
        document.getElementById('reviewTransportation').textContent = money(button.dataset.transportation);
        document.getElementById('reviewOther').textContent = money(button.dataset.other);
        document.getElementById('reviewTotal').textContent = money(button.dataset.total);
        document.getElementById('reviewPaid').textContent = money(button.dataset.paid);
        document.getElementById('reviewRemaining').textContent = money(button.dataset.remaining);
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }));
})();
</script>
@endpush
