@extends('layouts.shipper')

@section('title', 'Đơn có thể nhận')
@section('subtitle', 'Danh sách đơn đóng gói xong, chưa có shipper')

@push('styles')
<style>
    .sp-av-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        height: 100%;
    }
    .sp-av-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
    }
    .sp-av-order-code {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
    }
    .sp-av-order-time {
        color: #64748b;
        font-size: .78rem;
    }
    .sp-av-meta-label {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .sp-av-meta-value {
        font-weight: 700;
        color: #0f172a;
        font-size: .92rem;
    }
    .sp-av-section {
        border-top: 1px dashed #e2e8f0;
        padding-top: 8px;
        margin-top: 8px;
    }
    .sp-av-table-head,
    .sp-av-table-row {
        display: grid;
        grid-template-columns: minmax(0, 2fr) 36px 64px 85px 100px;
        gap: 8px;
        align-items: center;
    }
    .sp-av-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 4px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .sp-av-item-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .sp-av-item-row {
        display: grid;
        gap: 4px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .sp-av-item-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .sp-av-item-top {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .sp-av-item-name {
        font-size: .88rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sp-av-item-cell {
        font-size: .8rem;
        color: #475569;
        text-align: right;
    }
    .sp-av-item-cell strong {
        color: #0f172a;
    }
    .sp-av-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-size: .82rem;
        padding: 2px 0;
        color: #475569;
    }
    .sp-av-summary-row.total {
        margin-top: 4px;
        padding-top: 6px;
        border-top: 1px dashed #cbd5e1;
        font-weight: 800;
        color: #0f172a;
        font-size: .95rem;
    }
    .sp-av-quick-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .sp-av-quick-pill {
        border-radius: 999px;
        padding: 6px 10px;
        font-size: .78rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
    }
    .sp-av-quick-pill.active {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .sp-av-quick-pill.disabled {
        opacity: .55;
        background: #f8fafc;
        color: #64748b;
        border-style: dashed;
        pointer-events: none;
    }
    .sp-av-quick-count {
        min-width: 20px;
        height: 20px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
        background: #e2e8f0;
        color: #334155;
        padding: 0 6px;
    }
    .sp-av-quick-pill.active .sp-av-quick-count {
        background: #2563eb;
        color: #fff;
    }
    @media (max-width: 575px) {
        .sp-av-table-head,
        .sp-av-table-row {
            grid-template-columns: minmax(0, 1.3fr) 40px 58px 74px 86px;
            gap: 6px;
        }
    }
</style>
@endpush

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Xem đơn theo ngày</label>
                <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">                
            </div>
            <div class="col-md-10 d-flex gap-2 justify-content-md-start justify-content-end">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
                 <a href="{{ route('shipper.available') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-calendar-day me-1"></i>Hôm nay
                </a>
                <div class="sp-av-quick-wrap ml-4">
                    @foreach($quickDates as $quickDate)
                        @if($quickDate['available'])
                            <a href="{{ route('shipper.available', ['date' => $quickDate['date']]) }}"
                            class="sp-av-quick-pill {{ $quickDate['active'] ? 'active' : '' }}">
                                {{ $quickDate['label'] }}
                                <span class="sp-av-quick-count">{{ $quickDate['count'] }}</span>
                            </a>
                        @else
                            <span class="sp-av-quick-pill disabled">
                                {{ $quickDate['label'] }}
                                <span class="sp-av-quick-count">0</span>
                            </span>
                        @endif
                    @endforeach
                </div> 
            </div>
        </form>

         
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="badge bg-info rounded-pill">{{ $orders->count() }} đơn sẵn sàng trong ngày {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
</div>

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Không có đơn sẵn sàng giao trong ngày {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}.</p>
    </div>
@else
<div class="row g-3">
    @foreach($orders as $order)
    @php
        $canAcceptToday = \Illuminate\Support\Carbon::parse($selectedDate)->isToday();
        $recipientName = $order->recipient_name ?: ($order->customer?->name ?? '—');
        $recipientPhone = $order->recipient_phone ?: ($order->customer?->phone ?? null);
        $deliveryAddress = $order->recipient_address ?: ($order->customer?->address ?? null);
        $customerMainAddress = $order->customer?->address;
        $customerDeliveryTime = $order->delivery_time ?: $order->customer?->delivery_time;

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
            return (float) $item->price * (int) $item->quantity;
        });
        $shippingFee = (float) ($order->shipping_fee ?? 0);
        $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
        $codAmount = (float) ($order->total ?? ($itemsSubtotal + $shippingFee + $foamBoxFee));
    @endphp
    <div class="col-md-6 col-xl-4">
        <div class="card sp-av-card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div class="sp-av-head w-100">
                    <div>
                        <div class="sp-av-order-code">{{ $order->code }}</div>
                        <div class="sp-av-order-time">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <span class="badge bg-teal text-white" style="background:#0d9488!important;">Sẵn sàng giao</span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="fw-semibold">{{ $recipientName }}</div>
                    @if($recipientPhone)
                        <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $recipientPhone }}</div>
                    @endif
                    @if($deliveryAddress)
                        <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>Địa chỉ giao: {{ $deliveryAddress }}</div>
                    @else
                        <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>Chưa có địa chỉ giao hàng</div>
                    @endif
                    @if($alternateAddress)
                        <div class="text-muted small"><i class="bi bi-pin-map me-1"></i>Địa chỉ KH khác: {{ $alternateAddress }}</div>
                    @endif
                    @if($customerDeliveryTime)
                        <div class="text-muted small"><i class="bi bi-clock me-1"></i>Giờ giao hàng: {{ $customerDeliveryTime }}</div>
                    @endif
                </div>

 

                <div class="sp-av-section"> 
                    <div class="sp-av-table-head">
                        <div>Sản phẩm</div>
                        <div class="text-end">SL</div>
                        <div class="text-end">Size</div>
                        <div class="text-end">Đơn giá</div>
                        <div class="text-end">Thành tiền</div>
                    </div>
                    <ul class="sp-av-item-list">
                        @foreach($order->items as $item)
                            @php
                                $qty = (int) $item->quantity;
                                $unitPrice = (float) ($item->price ?? 0);
                                $lineTotal = $qty * $unitPrice;
                                $variantSize = $item->variant?->size;
                                $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                    ? rtrim(rtrim(number_format((float) $variantSize, 2, '.', ''), '0'), '.')
                                    : '-';
                            @endphp
                            <li class="sp-av-item-row">
                                <div class="sp-av-table-row">
                                    <div class="sp-av-item-name">
                                        {{ $item->variant?->name ?? $item->variant?->sku ?? 'Sản phẩm' }}
                                        @if($item->variant?->sku)
                                            <span class="text-muted small">({{ $item->variant->sku }})</span>
                                        @endif
                                    </div>
                                    <div class="sp-av-item-cell"><strong>{{ $qty }}</strong></div>
                                    <div class="sp-av-item-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                    <div class="sp-av-item-cell">{{ number_format($unitPrice) }}đ</div>
                                    <div class="sp-av-item-cell"><strong>{{ number_format($lineTotal) }}đ</strong></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="sp-av-section">
                    <div class="sp-av-summary-row">
                        <span>Tiền hàng</span>
                        <strong>{{ number_format($itemsSubtotal) }}đ</strong>
                    </div>
                    <div class="sp-av-summary-row">
                        <span>Phí ship</span>
                        <strong>{{ number_format($shippingFee) }}đ</strong>
                    </div>
                    <div class="sp-av-summary-row">
                        <span>Thùng xốp</span>
                        <strong>{{ number_format($foamBoxFee) }}đ</strong>
                    </div>
                    <div class="sp-av-summary-row total">
                        <span>COD cần thu</span>
                        <span class="text-success">{{ number_format($codAmount) }}đ</span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-top">
                @if($canAcceptToday && $order->created_at->isToday())
                    <form action="{{ route('shipper.accept', $order) }}" method="POST"
                          onsubmit="return confirm('Xác nhận nhận đơn #{{ $order->code }}?')">
                        @csrf
                        <button class="btn btn-success w-100">
                            <i class="bi bi-hand-index-thumb me-1"></i>Nhận đơn này
                        </button>
                    </form>
                @else
                    <button class="btn btn-outline-secondary w-100" disabled>
                        <i class="bi bi-calendar-x me-1"></i>Chỉ nhận đơn có ngày hôm nay
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
