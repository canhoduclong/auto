@extends(accounting_layout())

@section('title', 'Đối soát đơn giao hàng')
@section('subtitle', 'Kế toán xác nhận doanh thu sau khi đơn giao/thu tiền hoàn tất')

@section('accounting_content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $deliveryStatuses = [
        '' => 'Tất cả',
        'delivered' => 'Đã giao',
        'completed' => 'Hoàn thành',
        'returning' => 'Đang trả',
        'returned_completed' => 'Đã trả xong',
        'cancelled' => 'Đã hủy',
    ];
    $paymentStatuses = [
        '' => 'Tất cả',
        'paid' => 'Đã thanh toán',
        'partially_paid' => 'Thanh toán một phần',
        'partial' => 'Thanh toán một phần',
        'unpaid' => 'Chưa thanh toán',
    ];
@endphp

@push('styles')
<style>
    .recon-grid {
        display: grid;
        grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    .recon-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
    }
    .recon-panel .panel-head {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 800;
        color: #0f172a;
    }
    .recon-panel .panel-body { padding: 16px; }
    .recon-stats {
        display: grid;
        gap: 8px;
    }
    .recon-stat {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 10px;
        background: #f8fafc;
        font-size: .88rem;
    }
    .recon-stat strong { color: #0f172a; }
    .recon-order-row {
        cursor: pointer;
    }
    .recon-order-row.active > * {
        background: #fffbeb !important;
    }
    .recon-detail {
        min-height: 260px;
    }
    .recon-inline-detail-row > td {
        padding: 0 0 12px !important;
        border-top: 0;
        background: #f8fafc !important;
    }
    .recon-inline-detail-row:hover > td {
        background: #f8fafc !important;
    }
    .recon-inline-detail-row .recon-detail {
        padding: 10px;
    }
    .recon-bulk-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        margin-bottom: 12px;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        background: #eff6ff;
    }
    .recon-detail-section {
        border-top: 1px solid #e2e8f0;
        padding-top: 12px;
        margin-top: 12px;
    }
    .recon-detail-title {
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
    }
    .recon-kv {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr);
        gap: 6px 10px;
        font-size: .9rem;
    }
    .recon-kv .k { color: #64748b; }
    .recon-kv .v { font-weight: 650; color: #0f172a; }
    .recon-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
    }
    .recon-info-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        padding: 10px 12px;
    }
    .recon-info-card .title {
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #475569;
        margin-bottom: 8px;
    }
    .recon-mini-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-size: .86rem;
        padding: 2px 0;
    }
    .recon-mini-row span:first-child { color: #64748b; }
    .recon-mini-row span:last-child {
        color: #0f172a;
        font-weight: 700;
        text-align: right;
    }
    @media (max-width: 992px) {
        .recon-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

<div class="recon-grid">
    <div class="recon-panel">
        <div class="panel-head">Tổng quan ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</div>
        <div class="panel-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-12">
                    <label class="form-label">Loại ngày</label>
                    <select class="form-select" name="date_field">
                        <option value="business_date" @selected($dateField === 'business_date')>Ngày nghiệp vụ</option>
                        <option value="delivered_at" @selected($dateField === 'delivered_at')>Ngày giao thực tế</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ $dateField === 'business_date' ? 'Ngày nghiệp vụ' : 'Ngày giao thực tế' }}</label>
                    <input class="form-control" type="date" name="date" value="{{ $selectedDate }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Sale</label>
                    <select class="form-select" name="sale_id">
                        <option value="0">Tất cả</option>
                        @foreach($sales as $sale)
                            <option value="{{ $sale->id }}" {{ (int) $saleId === (int) $sale->id ? 'selected' : '' }}>{{ $sale->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Shipper</label>
                    <select class="form-select" name="shipper_id">
                        <option value="0">Tất cả</option>
                        @foreach($shippers as $shipper)
                            <option value="{{ $shipper->id }}" {{ (int) $shipperId === (int) $shipper->id ? 'selected' : '' }}>{{ $shipper->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Giao hàng</label>
                    <select class="form-select" name="status">
                        @foreach($deliveryStatuses as $value => $label)
                            <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Thanh toán</label>
                    <select class="form-select" name="payment_status">
                        @foreach($paymentStatuses as $value => $label)
                            <option value="{{ $value }}" {{ $paymentStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Xác nhận kế toán</label>
                    <select class="form-select" name="accounting_status">
                        <option value="" {{ $accountingStatus === '' ? 'selected' : '' }}>Tất cả</option>
                        <option value="pending" {{ $accountingStatus === 'pending' ? 'selected' : '' }}>Chưa xác nhận</option>
                        <option value="confirmed" {{ $accountingStatus === 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    </select>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-primary">Lọc đối soát</button>
                </div>
            </form>

            <div class="recon-stats">
                <div class="recon-stat"><span>Tổng số đơn</span><strong>{{ number_format($stats['total_orders']) }}</strong></div>
                <div class="recon-stat"><span>Tổng mặt hàng bán</span><strong>{{ number_format($stats['total_items']) }}</strong></div>
                <div class="recon-stat"><span>Tổng giá trị hàng bán</span><strong>{{ $money($stats['total_goods']) }}</strong></div>
                <div class="recon-stat"><span>Tổng doanh thu</span><strong>{{ $money($stats['total_revenue']) }}</strong></div>
                <div class="recon-stat"><span>Tổng đã thanh toán</span><strong class="text-success">{{ $money($stats['total_paid']) }}</strong></div>
                <div class="recon-stat"><span>Tổng còn thiếu</span><strong class="text-danger">{{ $money($stats['total_due']) }}</strong></div>
                <div class="recon-stat"><span>Tổng phí ship</span><strong>{{ $money($stats['total_shipping_fee']) }}</strong></div>
                <div class="recon-stat"><span>Tổng đơn hoàn/trả</span><strong>{{ number_format($stats['return_orders']) }}</strong></div>
                <div class="recon-stat"><span>Đã kế toán xác nhận</span><strong class="text-success">{{ number_format($stats['confirmed']) }}</strong></div>
                <div class="recon-stat"><span>Chưa kế toán xác nhận</span><strong class="text-warning">{{ number_format($stats['pending']) }}</strong></div>
            </div>
        </div>
    </div>

    <div class="recon-panel">
        <div class="panel-head d-flex justify-content-between align-items-center">
            <span>Danh sách đơn hàng</span>
            <span class="badge text-bg-light border">{{ $orders->total() }} đơn</span>
        </div>
        <div class="panel-body">
            <div class="recon-bulk-toolbar">
                <div>
                    <div class="fw-bold">Quản trị đối soát</div>
                    <div class="small text-muted" id="reconSelectionText">Chưa chọn đơn nào</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success" type="button" id="bulkConfirmButton" disabled>
                        Xác nhận đã chọn
                    </button>
                    <button class="btn btn-outline-danger" type="button" id="bulkCancelButton" disabled>
                        Hủy xác nhận đã chọn
                    </button>
                </div>
            </div>
            <div class="alert d-none" id="reconBulkResult" role="alert"></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 38px">
                                <input class="form-check-input" type="checkbox" id="selectAllReconciliation" aria-label="Chọn tất cả đơn có thể xử lý đối soát">
                            </th>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Giao hàng</th>
                            <th>Đã thu</th>
                            <th>Còn thiếu</th>
                            <th>Sale</th>
                            <th>Shipper</th>
                            <th>Phí ship</th>
                            <th>Kế toán</th>
                            <th>{{ $dateField === 'business_date' ? 'Ngày nghiệp vụ' : 'Ngày giao' }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($orders as $order)
                        @php
                            $recon = $order->accountingReconciliation;
                            $isConfirmed = $recon?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED;
                            $canCancel = $isConfirmed && ! $order->accounting_sales_import_batch_id;
                            $canConfirm = (bool) $order->reconciliation_can_confirm;
                            $canSelect = $canConfirm || $canCancel;
                            $selectionTitle = $canConfirm
                                ? 'Chọn đơn để xác nhận'
                                : ($canCancel ? 'Chọn đơn để hủy xác nhận' : ($order->reconciliation_block_reason ?: 'Không thể xử lý đơn này'));
                            $paidAmount = (float) ($order->reconciliation_paid_amount ?? $order->amount_paid ?? 0);
                            $dueAmount = (float) ($order->reconciliation_due_amount ?? $order->amount_due ?? 0);
                        @endphp
                        <tr class="recon-order-row" data-order-id="{{ $order->id }}" data-can-confirm="{{ $canConfirm ? '1' : '0' }}" data-can-cancel="{{ $canCancel ? '1' : '0' }}" data-cancel-allowed="{{ $order->accounting_sales_import_batch_id ? '0' : '1' }}" data-detail-url="{{ route('accounting.reconciliation.detail', $order) }}" data-confirm-url="{{ route('accounting.reconciliation.confirm', $order) }}" data-cancel-url="{{ route('accounting.reconciliation.cancel', $order) }}">
                            <td>
                                <input
                                    class="form-check-input js-recon-select"
                                    type="checkbox"
                                    value="{{ $order->id }}"
                                    aria-label="Chọn đơn {{ $order->code }}"
                                    title="{{ $selectionTitle }}"
                                    {{ $canSelect ? '' : 'disabled' }}
                                >
                            </td>
                            <td class="fw-bold">{{ $order->code }}</td>
                            <td>{{ $order->customer?->name ?? '-' }}</td>
                            <td><span class="badge text-bg-light border">{{ $order->status }}</span></td>
                            <td class="text-success fw-semibold">{{ $money($paidAmount) }}</td>
                            <td class="{{ $dueAmount > 0 ? 'text-danger' : 'text-success' }} fw-semibold">{{ $money($dueAmount) }}</td>
                            <td>{{ $order->user?->name ?? '-' }}</td>
                            <td>{{ $order->shipper?->name ?? '-' }}</td>
                            <td>{{ $money($order->shipping_fee) }}</td>
                            <td class="js-accounting-status">
                                <span class="badge {{ $isConfirmed ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ $isConfirmed ? 'Đã xác nhận' : 'Chưa xác nhận' }}
                                </span>
                            </td>
                            <td>
                                @if($dateField === 'business_date')
                                    {{ $order->is_restored_order
                                        ? (optional($order->delivered_at)->format('d/m/Y H:i') ?: '-')
                                        : ($order->accounting_sales_import_batch_id
                                        ? (optional($order->delivery_date)->format('d/m/Y') ?: '-')
                                        : (optional($order->created_at)->format('d/m/Y H:i') ?: '-')) }}
                                @else
                                    {{ optional($order->delivered_at)->format('d/m/Y H:i') ?: '-' }}
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary js-recon-toggle" type="button">Xem chi tiết</button>
                                    <button
                                        class="btn btn-sm btn-outline-success js-recon-confirm {{ $isConfirmed ? 'd-none' : '' }}"
                                        type="button"
                                        title="{{ $canConfirm ? 'Xác nhận riêng đơn này' : ($order->reconciliation_block_reason ?: 'Không thể xác nhận đơn này') }}"
                                        {{ $canConfirm ? '' : 'disabled' }}
                                    >Xác nhận</button>
                                    <button
                                        class="btn btn-sm btn-outline-danger js-recon-cancel {{ $canCancel ? '' : 'd-none' }}"
                                        type="button"
                                        title="Hủy xác nhận đối soát và gỡ doanh thu, hoa hồng của đơn"
                                    >Hủy đối soát</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-4">Không có đơn giao hàng cần đối soát.</td></tr>
                    @endforelse
                    @if($orders->isNotEmpty())
                        <tr class="recon-inline-detail-row d-none" id="reconciliationDetailRow">
                            <td colspan="12">
                                <div class="recon-detail" id="reconciliationDetail"></div>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>

            {{ $orders->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailBox = document.getElementById('reconciliationDetail');
    const detailRow = document.getElementById('reconciliationDetailRow');
    const selectAllCheckbox = document.getElementById('selectAllReconciliation');
    const bulkConfirmButton = document.getElementById('bulkConfirmButton');
    const bulkCancelButton = document.getElementById('bulkCancelButton');
    const selectionText = document.getElementById('reconSelectionText');
    const bulkResult = document.getElementById('reconBulkResult');
    const bulkConfirmUrl = @json(route('accounting.reconciliation.bulk-confirm'));
    const bulkCancelUrl = @json(route('accounting.reconciliation.bulk-cancel'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const money = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
    const esc = (value) => String(value ?? '-').replace(/[&<>"']/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    let activeOrderId = null;
    let detailRequestId = 0;

    function section(title, rows) {
        return `<div class="recon-detail-section"><div class="recon-detail-title">${esc(title)}</div><div class="recon-kv">${rows.map(([k, v]) => `<div class="k">${esc(k)}</div><div class="v">${v}</div>`).join('')}</div></div>`;
    }

    function infoCard(title, rows) {
        return `<div class="recon-info-card">
            <div class="title">${esc(title)}</div>
            ${rows.map(([k, v]) => `<div class="recon-mini-row"><span>${esc(k)}</span><span>${v}</span></div>`).join('')}
        </div>`;
    }

    function selectedOrderIds() {
        return Array.from(document.querySelectorAll('.js-recon-select:checked')).map(input => input.value);
    }

    function selectedActionOrderIds(action) {
        return Array.from(document.querySelectorAll('.js-recon-select:checked'))
            .filter(input => input.closest('.recon-order-row')?.dataset[action] === '1')
            .map(input => input.value);
    }

    function updateSelectionState() {
        const available = Array.from(document.querySelectorAll('.js-recon-select:not(:disabled)'));
        const selected = selectedOrderIds();
        const confirmable = selectedActionOrderIds('canConfirm');
        const cancellable = selectedActionOrderIds('canCancel');
        if (selectionText) {
            selectionText.textContent = selected.length
                ? `Đã chọn ${selected.length} đơn (${confirmable.length} có thể xác nhận, ${cancellable.length} có thể hủy)`
                : 'Chưa chọn đơn nào';
        }
        if (bulkConfirmButton) {
            bulkConfirmButton.disabled = confirmable.length === 0;
            bulkConfirmButton.textContent = confirmable.length ? `Xác nhận ${confirmable.length} đơn đã chọn` : 'Xác nhận đã chọn';
        }
        if (bulkCancelButton) {
            bulkCancelButton.disabled = cancellable.length === 0;
            bulkCancelButton.textContent = cancellable.length ? `Hủy xác nhận ${cancellable.length} đơn đã chọn` : 'Hủy xác nhận đã chọn';
        }
        if (selectAllCheckbox) {
            selectAllCheckbox.disabled = available.length === 0;
            selectAllCheckbox.checked = available.length > 0 && selected.length === available.length;
            selectAllCheckbox.indeterminate = selected.length > 0 && selected.length < available.length;
        }
    }

    function markRowConfirmed(orderId) {
        const row = document.querySelector(`.recon-order-row[data-order-id="${CSS.escape(String(orderId))}"]`);
        if (!row) return;
        row.dataset.canConfirm = '0';
        row.dataset.canCancel = row.dataset.cancelAllowed;
        const statusCell = row.querySelector('.js-accounting-status');
        if (statusCell) statusCell.innerHTML = '<span class="badge text-bg-success">Đã xác nhận</span>';
        const checkbox = row.querySelector('.js-recon-select');
        if (checkbox) {
            checkbox.checked = false;
            checkbox.disabled = row.dataset.canCancel !== '1';
            checkbox.title = row.dataset.canCancel === '1' ? 'Chọn đơn để hủy xác nhận' : 'Đơn đã được kế toán xác nhận.';
        }
        const confirmButton = row.querySelector('.js-recon-confirm');
        if (confirmButton) {
            confirmButton.disabled = true;
            confirmButton.classList.add('d-none');
            confirmButton.title = 'Đơn đã được kế toán xác nhận.';
        }
        if (row.dataset.canCancel === '1') row.querySelector('.js-recon-cancel')?.classList.remove('d-none');
        updateSelectionState();
    }

    function markRowPending(orderId) {
        const row = document.querySelector(`.recon-order-row[data-order-id="${CSS.escape(String(orderId))}"]`);
        if (!row) return;
        row.dataset.canConfirm = '1';
        row.dataset.canCancel = '0';
        const statusCell = row.querySelector('.js-accounting-status');
        if (statusCell) statusCell.innerHTML = '<span class="badge text-bg-warning">Chưa xác nhận</span>';
        const checkbox = row.querySelector('.js-recon-select');
        if (checkbox) {
            checkbox.checked = false;
            checkbox.disabled = false;
            checkbox.title = 'Chọn đơn để xác nhận';
        }
        const confirmButton = row.querySelector('.js-recon-confirm');
        if (confirmButton) {
            confirmButton.disabled = false;
            confirmButton.classList.remove('d-none');
            confirmButton.title = 'Xác nhận riêng đơn này';
        }
        row.querySelector('.js-recon-cancel')?.classList.add('d-none');
        updateSelectionState();
    }

    function showBulkResult(message, isSuccess = true) {
        if (!bulkResult) return;
        bulkResult.textContent = message;
        bulkResult.className = `alert ${isSuccess ? 'alert-success' : 'alert-danger'}`;
    }

    function renderDetail(data, row) {
        const order = data.order || {};
        const recon = data.reconciliation || {};
        const items = (data.items || []).map(item => `
            <tr>
                <td>
                    <div class="fw-semibold">${esc(item.product_name || item.name)}</div>
                    ${item.variant_name ? `<div class="small text-muted">${esc(item.variant_name)} ${item.sku ? `(${esc(item.sku)})` : ''}</div>` : ''}
                </td>
                <td>${esc(item.size || '-')}</td>
                <td class="text-end">${Number(item.quantity || 0).toLocaleString('vi-VN')}</td>
                <td class="text-end">${esc(item.total_label || '-')}</td>
                <td class="text-end">${Number(item.weight || 0) > 0 ? Number(item.weight || 0).toLocaleString('vi-VN') + ' kg' : '-'}</td>
                <td class="text-end">${money(item.unit_price)}</td>
                <td class="text-end fw-semibold">${money(item.line_total)}</td>
            </tr>
        `).join('');
        const returns = (data.returns || []).length
            ? (data.returns || []).map(ret => `<div class="border rounded p-2 mb-2">
                <div class="fw-semibold">Trạng thái: ${esc(ret.status)} | Hoàn: ${money(ret.refund_amount)}</div>
                <div class="small text-muted">Lý do: ${esc(ret.reason)} | Kho: ${esc(ret.warehouse)} | Nhận: ${esc(ret.confirmed_by)} ${esc(ret.confirmed_at || '')}</div>
                <ul class="mb-0 small">${(ret.items || []).map(item => `<li>${esc(item.name)} - SL ${Number(item.quantity || 0).toLocaleString('vi-VN')}</li>`).join('')}</ul>
            </div>`).join('')
            : '<div class="text-muted">Không có hàng trả.</div>';

        const confirmUrl = row?.dataset.confirmUrl;
        const cancelUrl = row?.dataset.cancelUrl;
        const confirmButton = recon.status === 'confirmed'
            ? `<div>
                <span class="badge text-bg-success">Đã xác nhận bởi ${esc(recon.confirmed_by || '-')} lúc ${esc(recon.confirmed_at || '-')}</span>
                ${recon.can_cancel ? `<form id="reconCancelForm" data-cancel-url="${esc(cancelUrl)}" class="mt-3">
                    <label class="form-label">Lý do hủy đối soát</label>
                    <textarea class="form-control mb-2" name="reason" rows="2" minlength="3" maxlength="1000" required></textarea>
                    <button class="btn btn-outline-danger" type="submit">Hủy đối soát</button>
                </form>` : ''}
            </div>`
            : `<form id="reconConfirmForm" data-confirm-url="${esc(confirmUrl)}" class="mt-2">
                <label class="form-label">Ghi chú xác nhận</label>
                <textarea class="form-control mb-2" name="note" rows="2" maxlength="1000"></textarea>
                <button class="btn btn-success" type="submit" ${recon.can_confirm ? '' : 'disabled'}>Kế toán xác nhận</button>
                ${recon.can_confirm ? '' : `<div class="text-danger small mt-2">${esc(recon.block_reason || 'Chưa đủ điều kiện xác nhận.')}</div>`}
            </form>`;

        detailBox.innerHTML = `
            <div class="recon-panel">
                <div class="panel-head d-flex justify-content-between align-items-center">
                    <span>Chi tiết ${esc(order.code)}</span>
                    <span class="badge ${recon.status === 'confirmed' ? 'text-bg-success' : 'text-bg-warning'}">${recon.status === 'confirmed' ? 'Đã xác nhận' : 'Chưa xác nhận'}</span>
                </div>
                <div class="panel-body">
                    ${section('Thông tin đơn hàng', [
                        ['Khách hàng', esc(order.customer?.name)],
                        ['Số điện thoại', esc(order.customer?.phone)],
                        ['Địa chỉ', esc(order.customer?.address)],
                        ['Tổng tiền hàng', money(order.subtotal_amount)],
                        ['Giảm giá', money(order.total_discount)],
                        ['Tổng phải thu', money(order.total)],
                        ['Doanh thu ghi nhận', `<span class="text-success">${money(order.recognized_revenue)}</span>`],
                    ])}
                    <div class="recon-detail-section">
                        <div class="recon-detail-title">Danh sách sản phẩm</div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Size</th>
                                        <th class="text-end">SL</th>
                                        <th class="text-end">Tổng</th>
                                        <th class="text-end">Khối lượng</th>
                                        <th class="text-end">Đơn giá</th>
                                        <th class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>${items || '<tr><td colspan="7" class="text-muted text-center">Không có sản phẩm.</td></tr>'}</tbody>
                            </table>
                        </div>
                    </div>
                    <div class="recon-detail-section">
                        <div class="recon-detail-title">Luồng xử lý</div>
                        <div class="recon-info-grid">
                            ${infoCard('Duyệt đơn', [
                                ['Người tạo/Sale', esc(data.approval?.created_by)],
                                ['Người duyệt', esc(data.approval?.approved_by)],
                                ['Thời gian', esc(data.approval?.approved_at)],
                                ['Ghi chú', esc(data.approval?.note)],
                            ])}
                            ${infoCard('Đóng gói', [
                                ['Người đóng gói', esc(data.packing?.packed_by)],
                                ['Thời gian', esc(data.packing?.packed_at)],
                                ['Kho xuất', esc(data.packing?.warehouse)],
                                ['Ghi chú', esc(data.packing?.note)],
                            ])}
                            ${infoCard('Giao hàng', [
                                ['Shipper', esc(data.delivery?.shipper)],
                                ['Trạng thái', esc(data.delivery?.status)],
                                ['Thời gian giao', esc(data.delivery?.delivered_at)],
                                ['Phí ship', money(data.delivery?.shipping_fee)],
                                ['Ghi chú', esc(data.delivery?.note)],
                            ])}
                        </div>
                    </div>
                    <div class="recon-detail-section">
                        <div class="recon-detail-title">Thông tin thanh toán</div>
                        <div class="recon-info-grid">
                            ${infoCard('Đối soát thu tiền', [
                                ['Tổng phải thu', money(data.payment?.total_due)],
                                ['Kế toán ghi nhận', money(data.payment?.accounting_paid_amount)],
                                ['Shipper đã thu', money(data.payment?.shipper_collected_amount)],
                                ['Đã thu hiệu lực', `<span class="text-success">${money(data.payment?.paid_amount)}</span>`],
                                ['Còn thiếu', `<span class="${Number(data.payment?.amount_due || 0) > 0 ? 'text-danger' : 'text-success'}">${money(data.payment?.amount_due)}</span>`],
                            ])}
                            ${infoCard('Xác nhận thanh toán', [
                                ['Phương thức', esc(data.payment?.method)],
                                ['Thời gian', esc(data.payment?.paid_at)],
                                ['Người xác nhận', esc(data.payment?.confirmed_by)],
                            ])}
                        </div>
                    </div>
                    <div class="recon-detail-section"><div class="recon-detail-title">Thông tin đơn trả / hoàn hàng</div>${returns}</div>
                    <div class="recon-detail-section"><div class="recon-detail-title">Kế toán xác nhận</div>${confirmButton}</div>
                </div>
            </div>
        `;

        detailBox.querySelector('#reconConfirmForm')?.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!confirm('Xác nhận ghi nhận doanh thu cho đơn này?')) return;
            const form = event.currentTarget;
            const button = form.querySelector('button');
            button.disabled = true;
            try {
                const response = await fetch(form.dataset.confirmUrl, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    body: new FormData(form),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Xác nhận thất bại.');
                markRowConfirmed(row.dataset.orderId);
                await loadDetail(row);
            } catch (error) {
                alert(error.message || 'Xác nhận thất bại.');
                button.disabled = false;
            }
        });

        detailBox.querySelector('#reconCancelForm')?.addEventListener('submit', async function (event) {
            event.preventDefault();
            const form = event.currentTarget;
            const reason = form.querySelector('[name="reason"]')?.value?.trim() || '';
            if (reason.length < 3 || !confirm('Hủy đối soát và gỡ doanh thu, hoa hồng của đơn này?')) return;
            const button = form.querySelector('button');
            button.disabled = true;
            try {
                const response = await fetch(form.dataset.cancelUrl, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    body: new FormData(form),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Hủy đối soát thất bại.');
                markRowPending(row.dataset.orderId);
                showBulkResult(payload.message || 'Đã hủy đối soát đơn hàng.');
                await loadDetail(row);
            } catch (error) {
                showBulkResult(error.message || 'Hủy đối soát thất bại.', false);
                button.disabled = false;
            }
        });
    }

    function resetToggleButtons() {
        document.querySelectorAll('.js-recon-toggle').forEach(button => {
            button.textContent = 'Xem chi tiết';
            button.classList.remove('btn-primary');
            button.classList.add('btn-outline-primary');
        });
    }

    function collapseDetail() {
        document.querySelectorAll('.recon-order-row').forEach(item => item.classList.remove('active'));
        resetToggleButtons();
        activeOrderId = null;
        detailRequestId++;
        detailRow?.classList.add('d-none');
        if (detailBox) detailBox.innerHTML = '';
    }

    function markOpen(row) {
        document.querySelectorAll('.recon-order-row').forEach(item => item.classList.remove('active'));
        resetToggleButtons();
        row.classList.add('active');
        activeOrderId = row.dataset.orderId;
        row.insertAdjacentElement('afterend', detailRow);
        detailRow.classList.remove('d-none');

        const button = row.querySelector('.js-recon-toggle');
        if (button) {
            button.textContent = 'Thu gọn';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-primary');
        }
    }

    function toggleDetail(row) {
        if (activeOrderId === row.dataset.orderId) {
            collapseDetail();
            return;
        }

        loadDetail(row);
    }

    async function loadDetail(row) {
        if (!detailBox || !detailRow) return;
        const requestId = ++detailRequestId;
        markOpen(row);
        detailBox.innerHTML = '<div class="text-center text-muted py-4">Đang tải chi tiết...</div>';
        try {
            const response = await fetch(row.dataset.detailUrl, {headers: {'Accept': 'application/json'}});
            const data = await response.json();
            if (requestId !== detailRequestId || activeOrderId !== row.dataset.orderId) return;
            if (!response.ok) throw new Error(data.message || 'Không tải được chi tiết đơn.');
            renderDetail(data, row);
        } catch (error) {
            if (requestId !== detailRequestId || activeOrderId !== row.dataset.orderId) return;
            detailBox.innerHTML = `<div class="alert alert-danger mb-0">${esc(error.message || 'Không tải được chi tiết đơn.')}</div>`;
        }
    }

    document.querySelectorAll('.recon-order-row').forEach(row => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('button, input, a, label')) {
                return;
            }

            toggleDetail(row);
        });
        row.querySelector('.js-recon-toggle')?.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleDetail(row);
        });
        row.querySelector('.js-recon-select')?.addEventListener('change', updateSelectionState);
        row.querySelector('.js-recon-confirm')?.addEventListener('click', async (event) => {
            event.stopPropagation();
            if (!confirm(`Xác nhận kế toán cho đơn ${row.querySelector('.fw-bold')?.textContent?.trim() || ''}?`)) return;
            const button = event.currentTarget;
            button.disabled = true;
            try {
                const response = await fetch(row.dataset.confirmUrl, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Xác nhận thất bại.');
                markRowConfirmed(row.dataset.orderId);
                showBulkResult(payload.message || 'Đã xác nhận đơn hàng.');
                if (activeOrderId === row.dataset.orderId) await loadDetail(row);
            } catch (error) {
                showBulkResult(error.message || 'Xác nhận thất bại.', false);
                button.disabled = false;
            }
        });
        row.querySelector('.js-recon-cancel')?.addEventListener('click', async (event) => {
            event.stopPropagation();
            const reason = prompt('Nhập lý do hủy đối soát đơn hàng:');
            if (reason === null) return;
            if (reason.trim().length < 3) {
                showBulkResult('Vui lòng nhập lý do hủy đối soát (ít nhất 3 ký tự).', false);
                return;
            }
            if (!confirm('Hủy đối soát và gỡ doanh thu, hoa hồng của đơn này?')) return;
            const button = event.currentTarget;
            button.disabled = true;
            try {
                const body = new FormData();
                body.append('reason', reason.trim());
                const response = await fetch(row.dataset.cancelUrl, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    body,
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Hủy đối soát thất bại.');
                markRowPending(row.dataset.orderId);
                showBulkResult(payload.message || 'Đã hủy đối soát đơn hàng.');
                if (activeOrderId === row.dataset.orderId) await loadDetail(row);
            } catch (error) {
                showBulkResult(error.message || 'Hủy đối soát thất bại.', false);
                button.disabled = false;
            }
        });
    });

    selectAllCheckbox?.addEventListener('change', function () {
        document.querySelectorAll('.js-recon-select:not(:disabled)').forEach(input => {
            input.checked = selectAllCheckbox.checked;
        });
        updateSelectionState();
    });

    bulkConfirmButton?.addEventListener('click', async function () {
        const orderIds = selectedActionOrderIds('canConfirm');
        if (!orderIds.length || !confirm(`Xác nhận kế toán hàng loạt cho ${orderIds.length} đơn đã chọn?`)) return;

        bulkConfirmButton.disabled = true;
        bulkConfirmButton.textContent = 'Đang xác nhận...';
        try {
            const response = await fetch(bulkConfirmUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({order_ids: orderIds}),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Xác nhận hàng loạt thất bại.');
            (payload.confirmed_order_ids || []).forEach(markRowConfirmed);
            showBulkResult(payload.message || 'Đã xác nhận các đơn được chọn.', (payload.confirmed_order_ids || []).length > 0);
            if ((payload.confirmed_order_ids || []).map(String).includes(String(activeOrderId))) {
                const activeRow = document.querySelector(`.recon-order-row[data-order-id="${CSS.escape(String(activeOrderId))}"]`);
                if (activeRow) await loadDetail(activeRow);
            }
        } catch (error) {
            showBulkResult(error.message || 'Xác nhận hàng loạt thất bại.', false);
        } finally {
            updateSelectionState();
        }
    });

    bulkCancelButton?.addEventListener('click', async function () {
        const orderIds = selectedActionOrderIds('canCancel');
        if (!orderIds.length) return;

        const reason = prompt(`Nhập lý do hủy xác nhận đối soát cho ${orderIds.length} đơn đã chọn:`);
        if (reason === null) return;
        if (reason.trim().length < 3) {
            showBulkResult('Vui lòng nhập lý do hủy đối soát (ít nhất 3 ký tự).', false);
            return;
        }
        if (!confirm(`Hủy xác nhận ${orderIds.length} đơn và gỡ doanh thu, hoa hồng liên quan?`)) return;

        bulkCancelButton.disabled = true;
        bulkCancelButton.textContent = 'Đang hủy xác nhận...';
        try {
            const response = await fetch(bulkCancelUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({order_ids: orderIds, reason: reason.trim()}),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Hủy xác nhận hàng loạt thất bại.');
            (payload.cancelled_order_ids || []).forEach(markRowPending);
            showBulkResult(payload.message || 'Đã hủy xác nhận các đơn được chọn.', (payload.cancelled_order_ids || []).length > 0);
            if ((payload.cancelled_order_ids || []).map(String).includes(String(activeOrderId))) {
                const activeRow = document.querySelector(`.recon-order-row[data-order-id="${CSS.escape(String(activeOrderId))}"]`);
                if (activeRow) await loadDetail(activeRow);
            }
        } catch (error) {
            showBulkResult(error.message || 'Hủy xác nhận hàng loạt thất bại.', false);
        } finally {
            updateSelectionState();
        }
    });

    updateSelectionState();

    const requestedOrderId = new URLSearchParams(window.location.search).get('order_id');
    if (requestedOrderId) {
        const requestedRow = document.querySelector(`.recon-order-row[data-order-id="${CSS.escape(requestedOrderId)}"]`);
        if (requestedRow) {
            loadDetail(requestedRow);
            requestedRow.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    }
});
</script>
@endpush
@endsection
