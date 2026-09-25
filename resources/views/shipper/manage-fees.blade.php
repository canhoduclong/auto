@extends('layouts.shipper')

@section('title', 'Quản lý phí ship')
@section('subtitle', 'Cập nhật phí giao hàng cho từng đơn')

@push('styles')
<style>
    .mf-order-row {
        display: grid;
        grid-template-columns: 100px 1fr 120px 120px 130px;
        gap: 1rem;
        align-items: center;
        padding: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 0.75rem;
    }
    .mf-order-row.is-requested {
        background: #e5e7eb;
        border-color: #cbd5e1;
        opacity: .82;
    }
    @media (max-width: 992px) {
        .mf-order-row {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        .mf-label {
            display: inline-block;
            min-width: 80px;
            font-weight: 600;
            color: #64748b;
            font-size: 0.75rem;
        }
    }
    .mf-order-code {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.9rem;
    }
    .mf-customer {
        font-size: 0.85rem;
        color: #475569;
    }
    .mf-fee-input {
        font-size: 0.85rem;
        padding: 0.4rem 0.75rem;
    }
    .mf-fee-current {
        font-size: 0.85rem;
        padding: 0.4rem 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        text-align: right;
    }
    .mf-btn-update {
        font-size: 0.8rem;
        padding: 0.4rem 0.75rem;
    }
    .mf-bulk-section {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    .mf-filter-group {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .mf-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .mf-stat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
    }
    .mf-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--theme-primary);
    }
    .mf-stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 4px;
    }
</style>
@endpush

@section('content')
<div class="mf-filter-group">
    <form method="GET" action="{{ route('shipper.manage-fees') }}" class="d-flex gap-2 align-items-center flex-grow-1">
        <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm" style="max-width: 150px">
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-search me-1"></i>Lọc
        </button>
        <a href="{{ route('shipper.manage-fees') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-clockwise me-1"></i>Đặt lại
        </a>
    </form>
</div>

<div class="mf-stats">
    <div class="mf-stat-card">
        <div class="mf-stat-value">{{ $orders->total() }}</div>
        <div class="mf-stat-label">Tổng đơn</div>
    </div>
    <div class="mf-stat-card">
        <div class="mf-stat-value">{{ number_format($orders->sum('shipping_fee'), 0, ',', '.') }}</div>
        <div class="mf-stat-label">Tổng phí (đ)</div>
    </div>
    <div class="mf-stat-card">
        <div class="mf-stat-value">{{ $orderReturns->count() }}</div>
        <div class="mf-stat-label">Phiếu trả hàng</div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="mb-1"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Tạo phiếu yêu cầu chi phí ship</h6>
            <div class="small text-muted">Chọn các đơn đã giao bên dưới. Mỗi đơn sẽ trở thành một dòng trong bảng Nội dung của phiếu yêu cầu.</div>
        </div>
        <span class="badge bg-secondary">Đã tạo: {{ $feeRequestSummary['requested_orders'] }}/{{ $feeRequestSummary['total_orders'] }} đơn</span>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <div class="small text-muted">Đơn chưa tạo phiếu</div>
                <div class="fs-5 fw-bold text-primary">{{ $feeRequestSummary['pending_orders'] }} đơn</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Tổng phí chưa gửi</div>
                <div class="fs-5 fw-bold text-danger">{{ number_format($feeRequestSummary['pending_total'], 0, ',', '.') }} đ</div>
            </div>
            <div class="col-md-6">
                <form id="shippingFeeRequestForm" method="POST" action="{{ route('shipper.shipping-fee-requests.store') }}" onsubmit="return confirm('Tạo phiếu yêu cầu từ các đơn đã chọn? Sau khi tạo, phí ship của các đơn này sẽ bị khóa.');">
                    @csrf
                    <input type="hidden" name="selected_date" value="{{ $selectedDate }}">
                    <div class="d-flex gap-2">
                        <input type="text" name="note" class="form-control" placeholder="Ghi chú cho phiếu chi phí ship (không bắt buộc)">
                        <button type="submit" class="btn btn-primary text-nowrap">
                            <i class="bi bi-send-check me-1"></i>Tạo phiếu từ đơn đã chọn
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="form-text mt-3"><span class="badge bg-secondary me-1">Màu xám</span> Đơn đã được tính vào phiếu yêu cầu trước đó và không thể chọn lại.</div>
    </div>
</div>

<!-- Bulk Fee Update Section -->
<div class="mf-bulk-section">
    <h6 class="mb-3"><i class="bi bi-sliders me-2"></i>Cập nhật hàng loạt</h6>
    <form id="bulkFeeForm" action="{{ route('shipper.bulk-update-fees') }}" method="POST">
        @csrf
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label small">Loại điều chỉnh</label>
                <select name="adjustment_type" class="form-select form-select-sm" required>
                    <option value="fixed">Số tiền cố định</option>
                    <option value="percent">Phần trăm (%)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Giá trị</label>
                <input type="number" name="fee_adjustment" class="form-control form-control-sm" step="0.01" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Ghi chú</label>
                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Lý do điều chỉnh...">
            </div>
            <div class="col-md-2">
                <label class="form-label small">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-success w-100">
                    <i class="bi bi-check-circle me-1"></i>Áp dụng
                </button>
            </div>
        </div>
        <div class="form-text mt-2">Chọn các đơn bên dưới, sau đó nhấn "Áp dụng" để cập nhật hàng loạt</div>
    </form>
</div>

<!-- Individual Order Fee Updates -->
@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Không có đơn hàng nào để quản lý phí.</p>
    </div>
@else
<div>
    @foreach($orders as $order)
        @php
            $customer = $order->customer;
            $shipper = $order->shipper;
            $alreadyRequested = (bool) $order->shipping_fee_transaction_id;
            $feeLocked = $alreadyRequested || (
                $order->accountingReconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED
                && !$order->accounting_sales_import_batch_id
            );
            $requestEligible = !$alreadyRequested
                && in_array($order->status, [\App\Models\Order::STATUS_DELIVERED, \App\Models\Order::STATUS_COMPLETED], true)
                && (float) ($order->shipping_fee ?? 0) > 0;
        @endphp
        <div class="mf-order-row {{ $alreadyRequested ? 'is-requested' : '' }}">
            <div class="text-center">
                @if($requestEligible)
                    <label class="d-block small fw-semibold text-primary mb-1">
                        <input type="checkbox" name="request_order_ids[]" value="{{ $order->id }}" form="shippingFeeRequestForm" class="form-check-input me-1">
                        Phiếu chi
                    </label>
                @elseif($alreadyRequested)
                    <span class="badge bg-secondary mb-1" title="{{ $order->shippingFeeRequest?->request_title }}">Đã vào phiếu #{{ $order->shipping_fee_transaction_id }}</span>
                @endif
                @unless($feeLocked)
                    <label class="d-block small text-muted mb-1">
                        <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" form="bulkFeeForm" class="form-check-input me-1" aria-label="Chọn đơn {{ $order->code }}">
                        Sửa hàng loạt
                    </label>
                @endunless
                <div class="mf-order-code">#{{ $order->code }}</div>
                <div style="font-size: 0.7rem; color: #94a3b8;">{{ $order->created_at->format('d/m') }}</div>
            </div>
            <div>
                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $customer?->name ?? 'N/A' }}</div>
                <div class="mf-customer">
                    {{ $shipper?->name ?? 'Chưa gán' }} 
                    <span class="badge bg-light text-dark ms-1">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                </div>
            </div>
            <div>
                <div class="mf-label d-lg-none">Phí hiện tại:</div>
                <div class="mf-fee-current">
                    {{ number_format($order->shipping_fee ?? $order->customer?->shipping_fee ?? 0, 0, ',', '.') }} đ
                </div>
                @if($order->customer?->shipping_fee !== null)
                    <div class="mt-1 small text-muted">Phí mặc định theo khách: {{ number_format($order->customer->shipping_fee, 0, ',', '.') }} đ</div>
                @endif
            </div>
            <div>
                <div class="mf-label d-lg-none">Phí mới:</div>
                <form action="{{ route('shipper.update-fee', $order->id) }}" method="POST" class="d-inline-block w-100">
                    @csrf
                    <input type="number" name="shipping_fee" class="form-control mf-fee-input" value="{{ $order->shipping_fee ?? $order->customer?->shipping_fee ?? 0 }}" step="1000" required style="width: 100%;" @disabled($feeLocked)>
            </div>
            <div>
                <div class="mf-label d-lg-none">Hành động:</div>
                <button type="submit" class="btn btn-sm {{ $feeLocked ? 'btn-secondary' : 'btn-primary' }} mf-btn-update w-100" @disabled($feeLocked)>
                    <i class="bi {{ $feeLocked ? 'bi-lock' : 'bi-check' }} me-1"></i>{{ $feeLocked ? 'Đã chốt' : 'Cập nhật' }}
                </button>
                </form>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-4">
    {{ $orders->links('pagination::bootstrap-5') }}
</div>
@endif

<div class="mt-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-arrow-return-left me-2 text-warning"></i>Đơn trả về - Phí ship trả về</span>
            <span class="badge bg-warning text-dark">{{ $orderReturns->count() }} phiếu</span>
        </div>
        <div class="card-body">
            @if($orderReturns->isEmpty())
                <div class="text-muted">Không có phiếu trả hàng trong ngày đã chọn.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Phiếu trả</th>
                                <th>Đơn hàng</th>
                                <th>Khách hàng</th>
                                <th>Kho trả về</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Phí hiện tại</th>
                                <th style="min-width: 260px;">Cập nhật phí ship trả về</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orderReturns as $return)
                                <tr>
                                    <td class="fw-semibold">#{{ $return->id }}</td>
                                    <td>
                                        @if($return->order)
                                            {{ $return->order->code }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $return->order?->customer?->name ?? '—' }}</td>
                                    <td>{{ $return->warehouse?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $return->status }}</span>
                                    </td>
                                    <td class="text-end fw-semibold text-danger">{{ number_format((float) ($return->return_shipping_fee ?? 0), 0, ',', '.') }} đ</td>
                                    <td>
                                        <form action="{{ route('shipper.update-return-fee', $return) }}" method="POST" class="row g-2 align-items-center">
                                            @csrf
                                            <div class="col-5">
                                                <input
                                                    type="number"
                                                    name="return_shipping_fee"
                                                    class="form-control form-control-sm"
                                                    value="{{ (float) ($return->return_shipping_fee ?? 0) }}"
                                                    step="1000"
                                                    min="0"
                                                    required
                                                >
                                            </div>
                                            <div class="col-4">
                                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Ghi chú">
                                            </div>
                                            <div class="col-3">
                                                <button type="submit" class="btn btn-sm btn-warning w-100">Lưu</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
