@extends('layouts.shipper')

@section('title', 'Đơn của tôi')
@section('subtitle', 'Bao gồm đơn đang giao và đơn đã hoàn thành')

@push('styles')
<style>
    .sp-my-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        height: 100%;
    }
    .sp-my-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
    }
    .sp-my-order-code {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
    }
    .sp-my-order-time {
        color: #64748b;
        font-size: .78rem;
    }
    .sp-my-meta-label {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .sp-my-meta-value {
        font-weight: 700;
        color: #0f172a;
        font-size: .92rem;  
    }
    .sp-my-section {
        border-top: 1px dashed #e2e8f0;
        padding-top: 8px;
        margin-top: 8px;
    }
    .sp-my-table-head,
    .sp-my-table-row {
        display: grid;
        grid-template-columns: minmax(0, 2fr) 52px 52px 76px 64px 73px 91px;
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
            grid-template-columns: minmax(0, 1.3fr) 46px 56px 84px 64px 84px 96px;
            gap: 6px;
        }
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-warning text-dark rounded-pill">Đang giao: {{ $orders->where('status', 'delivering')->count() }}</span>
        <span class="badge bg-success rounded-pill">Hoàn thành: {{ $orders->where('status', 'completed')->count() }}</span>
    </div>
    <a href="{{ route('shipper.available') }}" class="btn btn-outline-info btn-sm">
        <i class="bi bi-collection me-1"></i>Nhận thêm đơn
    </a>
</div>

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-truck fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Bạn chưa có đơn đang giao hoặc đã hoàn thành.</p>
        <a href="{{ route('shipper.available') }}" class="btn btn-success btn-sm mx-auto" style="width:fit-content">
            <i class="bi bi-collection me-1"></i>Xem đơn có thể nhận
        </a>
    </div>
@else
<div class="row g-3">
    @foreach($orders as $order)
    @php
        $recipientName = $order->recipient_name ?: ($order->customer?->name ?? '—');
        $recipientPhone = $order->recipient_phone ?: ($order->customer?->phone ?? null);
        $deliveryAddress = $order->recipient_address ?: ($order->customer?->address ?? null);
        $customerMainAddress = $order->customer?->address;
        $customerDeliveryTime = $order->delivery_time ?: $order->customer?->delivery_time;
        $sourceWarehouseName = $order->warehouse?->name;

        if (!$sourceWarehouseName) {
            $packingHistory = $order->histories
                ->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
                ->sortByDesc('id')
                ->first();
            $sourceWarehouseName = $packingHistory?->user?->warehouse?->name;
        }

        $normalizedDeliveryAddress = $deliveryAddress ? mb_strtolower(trim($deliveryAddress)) : null;
        $normalizedCustomerAddress = $customerMainAddress ? mb_strtolower(trim($customerMainAddress)) : null;

        $alternateAddress = null;
        if ($customerMainAddress && $normalizedCustomerAddress !== $normalizedDeliveryAddress) {
            $alternateAddress = $customerMainAddress;
        } else {
            $nonDefaultAddress = $order->customer?->addresses
                ?->first(fn($address) => (int) ($address->is_default ?? 0) !== 1);

            if ($nonDefaultAddress) {
                $parts = [
                    $nonDefaultAddress->house_number ?: $nonDefaultAddress->unit_number,
                    $nonDefaultAddress->street,
                    $nonDefaultAddress->ward,
                    $nonDefaultAddress->district,
                    $nonDefaultAddress->city,
                ];
                $alternateAddress = collect($parts)
                    ->filter(fn($part) => !empty($part))
                    ->implode(', ');
            }
        }

        $itemsSubtotal = (float) $order->items->sum(function ($item) {
            if ($item->total !== null) {
                return (float) $item->total;
            }

            return (float) ($item->price ?? 0) * (float) ($item->display_total_value ?? 0);
        });
        $shippingFee = (float) ($order->shipping_fee ?? 0);
        $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
        $codAmount = (float) ($order->total ?? ($itemsSubtotal + $shippingFee + $foamBoxFee));
    @endphp
    <div class="col-md-6 col-xl-6">
        <div class="card sp-my-card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div class="sp-my-head w-100">
                    <div>
                        <div class="sp-my-order-code">{{ $order->code }}</div>
                        <div class="sp-my-order-time">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @if($order->status === 'completed')
                        <span class="badge bg-success">Hoàn thành</span>
                    @else
                        <span class="badge bg-warning text-dark">Đang giao</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="fw-semibold">{{ $recipientName }}</div>
                    @if($recipientPhone)
                        <div class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $recipientPhone }}</div>
                    @endif
                    @if($deliveryAddress)
                        <div class="text-muted"><i class="bi bi-geo-alt me-1"></i>Địa chỉ giao: {{ $deliveryAddress }}</div>
                    @else
                        <div class="text-muted"><i class="bi bi-geo-alt me-1"></i>Chưa có địa chỉ giao hàng</div>
                    @endif
                    @if($alternateAddress)
                        <div class="text-muted"><i class="bi bi-pin-map me-1"></i>Địa chỉ KH khác: {{ $alternateAddress }}</div>
                    @endif
                    <div class="text-muted"><i class="bi bi-clock me-1"></i>Giờ giao hàng: {{ $customerDeliveryTime ?: 'Chưa cập nhật' }}</div>
                    <div class="text-muted"><i class="bi bi-box-seam me-1"></i>Từ kho: {{ $sourceWarehouseName ?: 'Chưa xác định' }}</div>
                </div>
 

                <div class="sp-my-section"> 
                    <div class="sp-my-table-head">
                        <div>Sản phẩm</div>
                        <div class="text-end">SL</div>
                        <div class="text-end">Size</div>
                        <div class="text-end">Tổng</div>
                        <div class="text-end">Kg</div>
                        <div class="text-end">Đơn giá</div>
                        <div class="text-end">Thành tiền</div>
                    </div>
                    <ul class="sp-my-item-list">
                        @foreach($order->items as $item)
                            @php
                                $qty = (int) $item->quantity;
                                $unitPrice = (float) ($item->price ?? 0);
                                $lineTotal = (float) ($item->total ?? ($unitPrice * (float) ($item->display_total_value ?? 0)));
                                $variantSize = $item->variant?->size;
                                $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                    ? rtrim(rtrim(number_format((float) $variantSize, 2, '.', ''), '0'), '.')
                                    : '-';
                                $lineWeight = (float) ($item->actual_weight ?? 0);
                                if ($lineWeight <= 0) {
                                    $lineWeight = (float) ($item->total_weight ?? 0);
                                }
                                if ($lineWeight <= 0) {
                                    $lineWeight = round((float) $qty * (float) ($item->effective_unit_weight ?? 0), 3);
                                }
                                $formattedLineWeight = $lineWeight > 0
                                    ? rtrim(rtrim(number_format($lineWeight, 3, '.', ''), '0'), '.') . ' kg'
                                    : '-';
                            @endphp
                            <li class="sp-my-item-row">
                                <div class="sp-my-table-row">
                                    <div class="sp-my-item-name">
                                        {{ $item->variant?->name ?? $item->variant?->sku ?? 'Sản phẩm' }}
                                        @if($item->variant?->sku)
                                            <span class="text-muted">({{ $item->variant->sku }})</span>
                                        @endif
                                    </div>
                                    <div class="sp-my-item-cell"><strong>{{ $qty }}</strong></div>
                                    <div class="sp-my-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                    <div class="sp-my-item-cell"><strong>{{ $item->display_total_label }}</strong></div>
                                    <div class="sp-my-item-cell"><strong>{{ $formattedLineWeight }}</strong></div>
                                    <div class="sp-my-item-cell">{{ number_format($unitPrice) }}đ</div>
                                    <div class="sp-my-item-cell"><strong>{{ number_format($lineTotal) }}đ</strong></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="sp-my-section">
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
            </div>

            @if($order->status === 'completed')
                <div class="card-footer bg-white border-top">
                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Đơn đã hoàn thành, không còn thao tác</span>
                </div>
            @else
                <div class="card-footer bg-white border-top d-flex gap-2">
                    <a href="{{ route('shipper.delivered-form', $order) }}" class="btn btn-success flex-fill btn-sm">
                        <i class="bi bi-check-circle me-1"></i>Đã giao
                    </a>
                    <a href="{{ route('shipper.return-form', $order) }}" class="btn btn-outline-danger flex-fill btn-sm">
                        <i class="bi bi-arrow-return-left me-1"></i>Trả hàng
                    </a>
                </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
