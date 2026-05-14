@extends('layouts.shipper')

@section('title', 'Lịch trình giao hàng')
@section('subtitle', 'Xem lịch trình giao hàng được manager gửi')

@push('styles')
<style>
    .ds-order-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: all 0.15s;
        padding: 1rem;
    }
    .ds-order-card:hover {
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.1);
        border-color: var(--theme-primary);
    }
    .ds-delivery-time {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--theme-primary);
        min-width: 90px;
    }
    .ds-customer-info {
        font-size: 0.95rem;
        line-height: 1.5;
    }
    .ds-product-item {
        font-size: 0.85rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .ds-product-item:last-child {
        border-bottom: none;
    }
</style>
@endpush

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="fw-bold text-dark">Lịch trình giao hàng hôm nay</div>
                <div class="text-muted small">Các đơn hàng được manager giao phó cho bạn. Tổng: <strong>{{ $orders->count() }}</strong> đơn</div>
            </div>
            <form method="GET" action="{{ route('shipper.delivery-schedules') }}" class="d-flex gap-2 align-items-center">
                <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm" style="max-width: 150px">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>Lọc
                </button>
                <a href="{{ route('shipper.delivery-schedules') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-1"></i>Đặt lại
                </a>
            </form>
        </div>
    </div>
</div>

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="mt-3 mb-0 text-muted">Không có lịch trình giao hàng nào cho ngày này.</p>
        </div>
    </div>
@else
    <form id="delivery-schedule-form" method="POST" action="{{ route('shipper.confirm-delivery-schedule', ['schedule' => 'bulk']) }}">
        @csrf
        <div class="row g-3" id="orders-list">
            @foreach($orders as $idx => $order)
                <div class="col-lg-6 order-item" data-order-id="{{ $order->id }}">
                    <input type="hidden" name="order_ids[]" value="{{ $order->id }}">
                    <div class="card ds-order-card h-100">
                        <div class="d-flex align-items-start gap-3 mb-2">
                            <div class="ds-delivery-time">
                                {{ $order->delivery_time ?: 'N/A' }}
                            </div>
                            <div class="flex-fill">
                                <div class="fw-semibold text-dark">{{ $order->customer?->name ?? $order->recipient_name }}</div>
                                <div class="text-muted small">
                                    {{ $order->recipient_address ?: $order->customer?->address ?: 'Chưa cập nhật' }}
                                </div>
                                <div class="small mt-2">
                                    <span class="badge bg-warning text-dark">{{ $order->items->sum('quantity') }} sp</span>
                                    <span class="badge bg-light text-dark border ms-2">
                                        @php
                                            $statusLabel = match ($order->status) {
                                                \App\Models\Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
                                                \App\Models\Order::STATUS_PACKING => 'Đang đóng gói',
                                                \App\Models\Order::STATUS_READY_TO_SHIP => 'Chờ nhận',
                                                default => strtoupper((string) $order->status),
                                            };
                                        @endphp
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-muted small fw-semibold">Mã: #{{ $order->code }}</div>
                        </div>

                        @if($order->items->isNotEmpty())
                            <div class="mt-3 pt-2 border-top">
                                <div class="text-muted small fw-semibold mb-2">Chi tiết sản phẩm:</div>
                                <div>
                                    @foreach($order->items as $item)
                                        <div class="ds-product-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-fill">
                                                    <span class="fw-semibold">{{ $item->variant?->name ?? $item->product_name }}</span>
                                                    @if($item->variant?->size)
                                                        <span class="text-muted small">- Size: {{ $item->variant->size }}</span>
                                                    @endif
                                                </div>
                                                <span class="badge bg-primary ms-2">x{{ $item->quantity }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-3 pt-2 border-top d-flex gap-2 align-items-center">
                            <span class="badge bg-success">Đơn #<span class="order-index">{{ $idx + 1 }}</span>/{{ $orders->count() }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary move-up" title="Lên trên" @if($idx === 0) disabled @endif><i class="bi bi-arrow-up"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary move-down" title="Xuống dưới" @if($idx === count($orders)-1) disabled @endif><i class="bi bi-arrow-down"></i></button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-primary px-5">
                <i class="bi bi-check2-circle me-1"></i> Xác nhận lịch trình & nhận đơn
            </button>
        </div>
    </form>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function updateOrderIndexes() {
            document.querySelectorAll('#orders-list .order-item').forEach(function(item, idx) {
                item.querySelector('.order-index').textContent = idx + 1;
                // Disable/enable move up/down buttons
                item.querySelector('.move-up').disabled = (idx === 0);
                item.querySelector('.move-down').disabled = (idx === document.querySelectorAll('#orders-list .order-item').length - 1);
            });
        }
        document.querySelectorAll('#orders-list').forEach(function(list) {
            list.addEventListener('click', function(e) {
                if (e.target.closest('.move-up') || e.target.classList.contains('move-up')) {
                    var item = e.target.closest('.order-item');
                    var prev = item.previousElementSibling;
                    if (prev) {
                        item.parentNode.insertBefore(item, prev);
                        updateOrderIndexes();
                    }
                }
                if (e.target.closest('.move-down') || e.target.classList.contains('move-down')) {
                    var item = e.target.closest('.order-item');
                    var next = item.nextElementSibling;
                    if (next) {
                        item.parentNode.insertBefore(next, item);
                        updateOrderIndexes();
                    }
                }
            });
        });
        updateOrderIndexes();
    });
</script>
@endpush
@endif
@endsection
