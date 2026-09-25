@extends('layouts.shipper')

@section('title', 'Sắp xếp tuyến đường')
@section('subtitle', 'Tối ưu hóa các tuyến giao hàng cho từng shipper')

@push('styles')
<style>
    .rp-filter-group {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .rp-shipper-section {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 2rem;
        overflow: hidden;
    }
    .rp-shipper-header {
        background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-primary-hover) 100%);
        color: white;
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .rp-shipper-name {
        font-size: 1rem;
        font-weight: 700;
    }
    .rp-order-count {
        background: rgba(255, 255, 255, 0.3);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .rp-order-card {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        align-items: center;
    }
    .rp-order-card:last-child {
        border-bottom: none;
    }
    @media (max-width: 1200px) {
        .rp-order-card {
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
    }
    @media (max-width: 768px) {
        .rp-order-card {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
    }
    .rp-label {
        display: none;
        font-weight: 600;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
    }
    @media (max-width: 1200px) {
        .rp-label { display: inline; margin-right: 0.5rem; }
    }
    .rp-order-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .rp-order-code {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.9rem;
    }
    .rp-customer {
        font-size: 0.8rem;
        color: #475569;
    }
    .rp-address {
        font-size: 0.75rem;
        color: #64748b;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 200px;
    }
    .rp-status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .rp-status-ready {
        background: #fef3c7;
        color: #92400e;
    }
    .rp-status-delivering {
        background: #dbeafe;
        color: #1e40af;
    }
    .rp-fee {
        text-align: right;
        font-weight: 600;
        color: #0f766e;
        font-size: 0.9rem;
    }
    .rp-empty {
        padding: 2rem;
        text-align: center;
        color: #94a3b8;
    }
    .rp-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .rp-stat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
    }
    .rp-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--theme-primary);
    }
    .rp-stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 4px;
    }
    .rp-items-preview {
        font-size: 0.75rem;
        color: #64748b;
        max-height: 40px;
        overflow: hidden;
    }
</style>
@endpush

@section('content')
<div class="rp-filter-group">
    <form method="GET" action="{{ route('shipper.route-planning') }}" class="d-flex gap-2 align-items-center flex-grow-1">
        <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm" style="max-width: 150px">
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-search me-1"></i>Lọc
        </button>
        <a href="{{ route('shipper.route-planning') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-clockwise me-1"></i>Đặt lại
        </a>
    </form>
</div>

<div class="rp-stats">
    <div class="rp-stat-card">
        <div class="rp-stat-value">{{ $orders->count() }}</div>
        <div class="rp-stat-label">Tổng đơn</div>
    </div>
    <div class="rp-stat-card">
        <div class="rp-stat-value">{{ $ordersByShipper->count() }}</div>
        <div class="rp-stat-label">Shipper có đơn</div>
    </div>
    <div class="rp-stat-card">
        <div class="rp-stat-value">{{ number_format($orders->sum('shipping_fee') ?? 0, 0, ',', '.') }}</div>
        <div class="rp-stat-label">Tổng phí (đ)</div>
    </div>
</div>

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Không có đơn hàng nào để sắp xếp tuyến đường.</p>
    </div>
@else
    @foreach($shippers as $shipper)
        @php
            $shipperOrders = $ordersByShipper->get($shipper->id) ?? collect();
        @endphp
        <div class="rp-shipper-section">
            <div class="rp-shipper-header">
                <div>
                    <div class="rp-shipper-name">
                        <i class="bi bi-person-badge me-2"></i>{{ $shipper->name }}
                    </div>
                </div>
                <div class="rp-order-count">
                    {{ $shipperOrders->count() }} đơn
                </div>
            </div>

            @if($shipperOrders->isEmpty())
                <div class="rp-empty">
                    <i class="bi bi-inbox text-muted"></i>
                    <p class="mt-2 mb-0">Chưa có đơn hàng</p>
                </div>
            @else
                <div>
                    @foreach($shipperOrders as $order)
                        @php
                            $customer = $order->customer;
                            $address = $order->recipient_address ?: $customer?->address;
                            $itemsText = $order->items->map(fn($item) => ($item->quantity . ' ' . mb_substr($item->variant?->name ?? 'N/A', 0, 15)))->join(', ');
                        @endphp
                        <div class="rp-order-card">
                            <div>
                                <span class="rp-label">Mã đơn:</span>
                                <div class="rp-order-info">
                                    <div class="rp-order-code">#{{ $order->code }}</div>
                                    <div class="rp-customer">{{ $customer?->name ?? 'N/A' }}</div>
                                </div>
                            </div>

                            <div>
                                <span class="rp-label">Địa chỉ giao:</span>
                                <div class="rp-address" title="{{ $address }}">
                                    <i class="bi bi-geo-alt-fill me-1" style="color: var(--theme-primary);"></i>{{ $address ?: 'N/A' }}
                                </div>
                                @if($order->delivery_time)
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">
                                        <i class="bi bi-clock me-1"></i>{{ $order->delivery_time }}
                                    </div>
                                @endif
                            </div>

                            <div>
                                <span class="rp-label">Sản phẩm:</span>
                                <div class="rp-items-preview" title="{{ $itemsText }}">
                                    {{ mb_substr($itemsText, 0, 50) }}{{ mb_strlen($itemsText) > 50 ? '...' : '' }}
                                </div>
                            </div>

                            <div>
                                <span class="rp-label">Trạng thái & Phí:</span>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="rp-status-badge {{ $order->status === 'packed_waiting_pickup' ? 'rp-status-ready' : 'rp-status-delivering' }}">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                    <div class="rp-fee">{{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }} đ</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    @if($ordersByShipper->count() < $shippers->count())
        <div class="rp-shipper-section border-warning">
            <div class="rp-shipper-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div>
                    <div class="rp-shipper-name">
                        <i class="bi bi-clock-history me-2"></i>Chưa gán shipper
                    </div>
                </div>
                @php
                    $unassignedCount = $orders->whereNull('shipper_id')->count();
                @endphp
                @if($unassignedCount > 0)
                    <div class="rp-order-count">{{ $unassignedCount }} đơn</div>
                @endif
            </div>

            @php
                $unassignedOrders = $orders->whereNull('shipper_id');
            @endphp

            @if($unassignedOrders->isEmpty())
                <div class="rp-empty">
                    <i class="bi bi-check-circle text-success"></i>
                    <p class="mt-2 mb-0">Tất cả đơn đã được gán</p>
                </div>
            @else
                <div>
                    @foreach($unassignedOrders as $order)
                        @php
                            $customer = $order->customer;
                            $address = $order->recipient_address ?: $customer?->address;
                            $itemsText = $order->items->map(fn($item) => ($item->quantity . ' ' . mb_substr($item->variant?->name ?? 'N/A', 0, 15)))->join(', ');
                        @endphp
                        <div class="rp-order-card">
                            <div>
                                <span class="rp-label">Mã đơn:</span>
                                <div class="rp-order-info">
                                    <div class="rp-order-code">#{{ $order->code }}</div>
                                    <div class="rp-customer">{{ $customer?->name ?? 'N/A' }}</div>
                                </div>
                            </div>

                            <div>
                                <span class="rp-label">Địa chỉ giao:</span>
                                <div class="rp-address" title="{{ $address }}">
                                    <i class="bi bi-geo-alt-fill me-1" style="color: var(--theme-primary);"></i>{{ $address ?: 'N/A' }}
                                </div>
                            </div>

                            <div>
                                <span class="rp-label">Sản phẩm:</span>
                                <div class="rp-items-preview" title="{{ $itemsText }}">
                                    {{ mb_substr($itemsText, 0, 50) }}{{ mb_strlen($itemsText) > 50 ? '...' : '' }}
                                </div>
                            </div>

                            <div>
                                <span class="rp-label">Phí:</span>
                                <div class="rp-fee">{{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }} đ</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
@endif
@endsection
