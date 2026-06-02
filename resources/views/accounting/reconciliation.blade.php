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
                    <label class="form-label">Ngày giao hàng</label>
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
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Giao hàng</th>
                            <th>Đã thu</th>
                            <th>Còn thiếu</th>
                            <th>Sale</th>
                            <th>Shipper</th>
                            <th>Phí ship</th>
                            <th>Kế toán</th>
                            <th>Ngày giao</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($orders as $order)
                        @php
                            $recon = $order->accountingReconciliation;
                            $isConfirmed = $recon?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED;
                        @endphp
                        <tr class="recon-order-row" data-order-id="{{ $order->id }}" data-detail-url="{{ route('accounting.reconciliation.detail', $order) }}">
                            <td class="fw-bold">{{ $order->code }}</td>
                            <td>{{ $order->customer?->name ?? '-' }}</td>
                            <td><span class="badge text-bg-light border">{{ $order->status }}</span></td>
                            <td class="text-success fw-semibold">{{ $money($order->amount_paid) }}</td>
                            <td class="text-danger fw-semibold">{{ $money($order->amount_due) }}</td>
                            <td>{{ $order->user?->name ?? '-' }}</td>
                            <td>{{ $order->shipper?->name ?? '-' }}</td>
                            <td>{{ $money($order->shipping_fee) }}</td>
                            <td>
                                <span class="badge {{ $isConfirmed ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ $isConfirmed ? 'Đã xác nhận' : 'Chưa xác nhận' }}
                                </span>
                            </td>
                            <td>{{ optional($order->delivered_at)->format('d/m/Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Không có đơn giao hàng cần đối soát.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $orders->links() }}

            <div class="recon-detail mt-3" id="reconciliationDetail">
                <div class="alert alert-info mb-0">Chọn một đơn hàng để xem chi tiết và xác nhận kế toán.</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailBox = document.getElementById('reconciliationDetail');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const money = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
    const esc = (value) => String(value ?? '-').replace(/[&<>"']/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));

    function section(title, rows) {
        return `<div class="recon-detail-section"><div class="recon-detail-title">${esc(title)}</div><div class="recon-kv">${rows.map(([k, v]) => `<div class="k">${esc(k)}</div><div class="v">${v}</div>`).join('')}</div></div>`;
    }

    function renderDetail(data, row) {
        const order = data.order || {};
        const recon = data.reconciliation || {};
        const items = (data.items || []).map(item => `
            <tr>
                <td>${esc(item.name)} ${item.sku ? `<span class="text-muted small">(${esc(item.sku)})</span>` : ''}</td>
                <td>${esc(item.size || '-')}</td>
                <td class="text-end">${Number(item.quantity || 0).toLocaleString('vi-VN')}</td>
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

        const confirmUrl = row?.dataset.confirmUrl || row?.dataset.detailUrl?.replace('/detail', '/confirm');
        const confirmButton = recon.status === 'confirmed'
            ? `<span class="badge text-bg-success">Đã xác nhận bởi ${esc(recon.confirmed_by || '-')} lúc ${esc(recon.confirmed_at || '-')}</span>`
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
                                <thead><tr><th>Sản phẩm</th><th>Size</th><th class="text-end">SL</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
                                <tbody>${items || '<tr><td colspan="5" class="text-muted text-center">Không có sản phẩm.</td></tr>'}</tbody>
                            </table>
                        </div>
                    </div>
                    ${section('Thông tin duyệt đơn', [
                        ['Người tạo/Sale', esc(data.approval?.created_by)],
                        ['Người duyệt', esc(data.approval?.approved_by)],
                        ['Thời gian duyệt', esc(data.approval?.approved_at)],
                        ['Ghi chú duyệt', esc(data.approval?.note)],
                    ])}
                    ${section('Thông tin đóng gói', [
                        ['Người đóng gói', esc(data.packing?.packed_by)],
                        ['Thời gian đóng gói', esc(data.packing?.packed_at)],
                        ['Kho xuất hàng', esc(data.packing?.warehouse)],
                        ['Ghi chú đóng gói', esc(data.packing?.note)],
                    ])}
                    ${section('Thông tin giao hàng', [
                        ['Shipper', esc(data.delivery?.shipper)],
                        ['Trạng thái', esc(data.delivery?.status)],
                        ['Thời gian giao', esc(data.delivery?.delivered_at)],
                        ['Phí ship', money(data.delivery?.shipping_fee)],
                        ['Ghi chú giao', esc(data.delivery?.note)],
                    ])}
                    ${section('Thông tin thanh toán', [
                        ['Tổng phải thu', money(data.payment?.total_due)],
                        ['Đã thu', money(data.payment?.paid_amount)],
                        ['Còn thiếu', money(data.payment?.amount_due)],
                        ['Phương thức', esc(data.payment?.method)],
                        ['Thời gian thanh toán', esc(data.payment?.paid_at)],
                        ['Người xác nhận', esc(data.payment?.confirmed_by)],
                    ])}
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
                row.querySelector('td:nth-child(9)').innerHTML = '<span class="badge text-bg-success">Đã xác nhận</span>';
                await loadDetail(row);
            } catch (error) {
                alert(error.message || 'Xác nhận thất bại.');
                button.disabled = false;
            }
        });
    }

    async function loadDetail(row) {
        document.querySelectorAll('.recon-order-row').forEach(item => item.classList.remove('active'));
        row.classList.add('active');
        detailBox.innerHTML = '<div class="text-center text-muted py-4">Đang tải chi tiết...</div>';
        try {
            const response = await fetch(row.dataset.detailUrl, {headers: {'Accept': 'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Không tải được chi tiết đơn.');
            renderDetail(data, row);
        } catch (error) {
            detailBox.innerHTML = `<div class="alert alert-danger mb-0">${esc(error.message || 'Không tải được chi tiết đơn.')}</div>`;
        }
    }

    document.querySelectorAll('.recon-order-row').forEach(row => {
        row.dataset.confirmUrl = row.dataset.detailUrl.replace('/detail', '/confirm');
        row.addEventListener('click', () => loadDetail(row));
    });
});
</script>
@endpush
@endsection
