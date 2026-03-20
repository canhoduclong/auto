@extends('layouts.shipper')

@section('title', 'Đơn có thể nhận')
@section('subtitle', 'Danh sách đơn đóng gói xong, chưa có shipper')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="badge bg-info rounded-pill">{{ $orders->count() }} đơn sẵn sàng</span>
    <a href="{{ route('shipper.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
</div>

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Chưa có đơn nào sẵn sàng giao lúc này.</p>
    </div>
@else
<div class="row g-3">
    @foreach($orders as $order)
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">{{ $order->code }}</span>
                <span class="badge bg-teal text-white" style="background:#0d9488!important;">Sẵn sàng giao</span>
            </div>
            <div class="card-body">
                @php
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
                @endphp
                {{-- Customer --}}
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
                <hr class="my-2">
                {{-- Order info --}}
                <div class="row g-2 text-center mb-2">
                    <div class="col-4">
                        <div class="text-muted" style="font-size:.72rem;">Số SP</div>
                        <div class="fw-bold">{{ $order->items->sum('quantity') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size:.72rem;">COD</div>
                        <div class="fw-bold text-success">{{ number_format($order->total) }}đ</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size:.72rem;">Tạo lúc</div>
                        <div class="fw-semibold small">{{ $order->created_at->format('d/m') }}</div>
                    </div>
                </div>
                {{-- Products list --}}
                <div class="bg-light rounded p-2 small">
                    @foreach($order->items->take(3) as $item)
                        <div class="d-flex justify-content-between">
                            <span class="text-truncate me-2">{{ $item->name ?? $item->productVariant?->name ?? 'SP' }}</span>
                            <span class="text-muted">×{{ $item->quantity }}</span>
                        </div>
                    @endforeach
                    @if($order->items->count() > 3)
                        <div class="text-muted">+ {{ $order->items->count() - 3 }} sản phẩm khác</div>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-white border-top">
                <form action="{{ route('shipper.accept', $order) }}" method="POST"
                      onsubmit="return confirm('Xác nhận nhận đơn #{{ $order->code }}?')">
                    @csrf
                    <button class="btn btn-success w-100">
                        <i class="bi bi-hand-index-thumb me-1"></i>Nhận đơn này
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
