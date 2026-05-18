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
    .ds-product-section {
        border-top: 1px dashed #e2e8f0;
        padding-top: 8px;
        margin-top: 8px;
    }
    .ds-product-table-head,
    .ds-product-table-row {
        display: grid;
        grid-template-columns: minmax(0, 2fr) 36px 44px 34px 92px 110px;
        gap: 8px;
        align-items: center;
    }
    .ds-product-table-head {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        font-weight: 700;
        padding: 0 0 4px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 6px;
    }
    .ds-product-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 6px;
    }
    .ds-product-row {
        display: grid;
        gap: 4px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 6px;
    }
    .ds-product-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .ds-product-name {
        font-size: .88rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ds-product-cell {
        font-size: .8rem;
        color: #475569;
        text-align: right;
    }
    .ds-product-cell strong {
        color: #0f172a;
    }
    @media (max-width: 575px) {
        .ds-product-table-head,
        .ds-product-table-row {
            grid-template-columns: minmax(0, 1.3fr) 40px 58px 74px 86px;
            gap: 6px;
        }
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
        <input type="hidden" name="date" value="{{ $selectedDate }}">
        <div class="d-flex flex-column gap-3" id="orders-list">
            @foreach($orders as $idx => $order)
                <div class="order-item w-100" data-order-id="{{ $order->id }}">
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
                            <div class="mt-3 pt-2 border-top ds-product-section">
                                <div class="text-muted small fw-semibold mb-2">Chi tiết sản phẩm:</div>
                                <div class="ds-product-table-head">
                                    <div>Sản phẩm</div>
                                    <div class="text-end">SL</div>
                                    <div class="text-end">Size</div>
                                    <div class="text-end">Kg</div>
                                    <div class="text-end">Đơn giá</div>
                                    <div class="text-end">Thành tiền</div>
                                </div>
                                <ul class="ds-product-list">
                                    @foreach($order->items as $item)
                                        @php
                                            $qty = (int) $item->quantity;
                                            $unitPrice = (float) ($item->price ?? 0);
                                            $lineTotal = $qty * $unitPrice;
                                            $variantSize = $item->variant?->size;
                                            $formattedVariantSize = (!is_null($variantSize) && $variantSize !== '')
                                                ? rtrim(rtrim(number_format((float) $variantSize, 2, '.', ''), '0'), '.')
                                                : '-';
                                            $itemActualWeight = null;
                                            if ($item->actual_weight) {
                                                $itemActualWeight = rtrim(rtrim(number_format((float) $item->actual_weight, 2, '.', ''), '0'), '.');
                                            } else {
                                                $itemActualWeight = '-';
                                            }
                                        @endphp
                                        <li class="ds-product-row">
                                            <div class="ds-product-table-row">
                                                <div class="ds-product-name">
                                                    {{ $item->variant?->name ?? $item->variant?->sku ?? $item->product_name ?? 'Sản phẩm' }}
                                                    @if($item->variant?->sku)
                                                        <span class="text-muted small">({{ $item->variant->sku }})</span>
                                                    @endif
                                                </div>
                                                <div class="ds-product-cell"><strong>{{ $qty }}</strong></div>
                                                <div class="ds-product-cell"><strong>{{ $formattedVariantSize }}</strong></div>
                                                <div class="ds-product-cell"><strong>{{ $itemActualWeight }}</strong></div>
                                                <div class="ds-product-cell">{{ number_format($unitPrice) }}đ</div>
                                                <div class="ds-product-cell"><strong>{{ number_format($lineTotal) }}đ</strong></div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
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
        <div class="mt-4 text-center d-flex justify-content-center gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary px-5" formaction="{{ route('shipper.confirm-delivery-schedule', ['schedule' => 'bulk']) }}" @if($scheduleAlreadyConfirmed) disabled @endif>
                <i class="bi bi-check2-circle me-1"></i> Xác nhận lịch trình & nhận đơn
            </button>
            <button type="submit" class="btn btn-outline-danger px-5" formaction="{{ route('shipper.reject-delivery-schedule', ['schedule' => 'bulk']) }}">
                <i class="bi bi-x-circle me-1"></i> Từ chối nhận
            </button>
        </div>
        @if($scheduleAlreadyConfirmed)
            <div class="text-center mt-2 text-success small fw-semibold">
                Lịch trình đã được xác nhận. Chỉ còn có thể từ chối nếu cần.
            </div>
        @endif
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
