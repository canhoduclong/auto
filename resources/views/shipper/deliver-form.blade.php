@extends('layouts.shipper')

@section('title', 'Xác nhận đã giao hàng')
@section('subtitle', 'Đơn #{{ $order->code }}')

@section('content')
@php
    $customerAddress = $order->customer?->address
        ?? $order->customer?->addresses?->first()?->address
        ?? 'Chưa có địa chỉ';
    $deliveryTime = $order->delivery_time
        ?? $order->customer?->delivery_time
        ?? 'Chưa có khung giờ giao';
    $itemsSubtotal = (float) $order->items->sum(function ($item) {
        return (float) $item->price * (int) $item->quantity;
    });
    $shippingFee = (float) ($order->shipping_fee ?? 0);
    $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
    $codAmount = (float) ($order->total ?? ($itemsSubtotal + $shippingFee + $foamBoxFee));
@endphp
<style>
    .shipper-deliver-shell .card {
        border-radius: 14px;
    }
    .shipper-meta-grid {
        display: grid;
        grid-template-columns: 132px 1fr;
        gap: 8px 10px;
        font-size: .92rem;
    }
    .shipper-meta-key {
        color: #64748b;
        font-weight: 600;
    }
    .shipper-customer-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        margin-top: 12px;
    }
    .shipper-customer-title {
        font-size: .82rem;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .shipper-quick-note {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e3a8a;
        border-radius: 10px;
        padding: 8px 10px;
        font-size: .83rem;
        margin-bottom: 12px;
    }
    .sp-my-table-head,
    .sp-my-table-row {
        display: grid;
        grid-template-columns: minmax(0, 2fr) 64px 100px 120px;
        gap: 8px;
        align-items: center;
    }
    .sp-my-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 4px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .sp-my-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .sp-my-item-row {
        display: grid;
        gap: 4px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .sp-my-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .sp-my-item-name {
        font-size: .88rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sp-my-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: right;
    }
    .sp-my-item-cell strong {
        color: #0f172a;
    }
    .sp-my-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-size: .82rem;
        padding: 2px 0;
        color: #475569;
    }
    .sp-my-summary-row.total {
        margin-top: 4px;
        padding-top: 6px;
        border-top: 1px dashed #cbd5e1;
        font-weight: 800;
        color: #0f172a;
        font-size: .95rem;
    }
    @media (max-width: 575px) {
        .sp-my-table-head,
        .sp-my-table-row {
            grid-template-columns: minmax(0, 1.3fr) 50px 84px 96px;
            gap: 6px;
        }
    }
</style>
<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8 shipper-deliver-shell">
        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <div class="fw-semibold mb-1">Không thể xác nhận giao hàng</div>
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-info-circle me-1 text-primary"></i>Thông tin đơn hàng
            </div>
            <div class="card-body">
                <div class="shipper-meta-grid">
                    <div class="shipper-meta-key">Mã đơn:</div>
                    <div class="fw-semibold">{{ $order->code }}</div>
                    <div class="shipper-meta-key">Khách hàng:</div>
                    <div>{{ $order->customer?->name ?? '—' }}</div>
                    <div class="shipper-meta-key">Số điện thoại:</div>
                    <div>{{ $order->customer?->phone ?? '—' }}</div>
                    
                </div>

                <div class="shipper-customer my-4">
                    <div class="shipper-customer-title">Thông tin giao hàng</div>
                    <div class="small mb-2"><i class="bi bi-geo-alt me-1"></i><strong>Địa chỉ:</strong> {{ $customerAddress }}</div>
                    <div class="small"><i class="bi bi-clock me-1"></i><strong>Giờ giao:</strong> {{ $deliveryTime }}</div>
                </div>
            
                <div class="sp-my-table-head">
                    <div>Sản phẩm</div>
                    <div class="text-end">SL</div>
                    <div class="text-end">Đơn giá</div>
                    <div class="text-end">Thành tiền</div>
                </div>

                <ul class="sp-my-item-list">
                    @foreach($order->items as $item)
                        @php
                            $qty = (int) $item->quantity;
                            $unitPrice = (float) ($item->price ?? 0);
                            $lineTotal = $qty * $unitPrice;
                        @endphp
                        <li class="sp-my-item-row">
                            <div class="sp-my-table-row">
                                <div class="sp-my-item-name">
                                    {{ $item->variant?->name ?? $item->variant?->sku ?? 'Sản phẩm' }}
                                    @if($item->variant?->sku)
                                        <span class="text-muted small">({{ $item->variant->sku }})</span>
                                    @endif
                                </div>
                                <div class="sp-my-item-cell"><strong>{{ $qty }}</strong></div>
                                <div class="sp-my-item-cell">{{ number_format($unitPrice) }}đ</div>
                                <div class="sp-my-item-cell"><strong>{{ number_format($lineTotal) }}đ</strong></div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-2">
                    <div class="sp-my-summary-row">
                        <span>Tiền hàng</span>
                        <strong>{{ number_format($itemsSubtotal) }}đ</strong>
                    </div>
                    <div class="sp-my-summary-row">
                        <span>Phí ship</span>
                        <strong>{{ number_format($shippingFee) }}đ</strong>
                    </div>
                    <div class="sp-my-summary-row">
                        <span>Thùng xốp</span>
                        <strong>{{ number_format($foamBoxFee) }}đ</strong>
                    </div>
                    <div class="sp-my-summary-row total">
                        <span>COD cần thu</span>
                        <span class="text-success">{{ number_format($codAmount) }}đ</span>
                    </div>
                </div>
            
                <div class="shipper-quick-note mt-4">
                    <i class="bi bi-shield-check me-1"></i>Vui lòng kiểm tra đúng số tiền thu và ảnh bằng chứng trước khi xác nhận.
                </div>

                <form action="{{ route('shipper.mark-delivered', $order) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số tiền đã thu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₫</span>
                            <input type="number" name="collected_amount" class="form-control"
                                   value="{{ old('collected_amount', $order->total) }}"
                                   step="1000" min="0" required>
                        </div>
                        <div class="form-text text-muted">COD cần thu: {{ number_format($order->total) }}đ</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phương thức thanh toán <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pm_cash" value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'checked' : '' }}>
                                <label class="form-check-label" for="pm_cash">
                                    <i class="bi bi-cash me-1"></i>Tiền mặt
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pm_transfer" value="transfer" {{ old('payment_method') === 'transfer' ? 'checked' : '' }}>
                                <label class="form-check-label" for="pm_transfer">
                                    <i class="bi bi-bank me-1"></i>Chuyển khoản
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ảnh xác nhận giao hàng <span class="text-danger">*</span></label>
                        <input type="file" name="proof_image" class="form-control" accept="image/*" required id="proofPreviewInput">
                        <div class="form-text text-muted">Ảnh chụp khi giao hàng (tối đa 5MB)</div>
                        <div id="proofPreview" class="mt-2 d-none">
                            <img id="proofPreviewImg" src="" class="img-fluid rounded" style="max-height:200px;">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('shipper.my-orders') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Quay lại
                        </a>
                        <button type="submit" class="btn btn-success flex-fill">
                            <i class="bi bi-check-circle me-1"></i>Xác nhận đã giao hàng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('proofPreviewInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('proofPreviewImg').src = ev.target.result;
        document.getElementById('proofPreview').classList.remove('d-none');
    };
    reader.readAsDataURL(file);
});
</script>
@endpush
@endsection
