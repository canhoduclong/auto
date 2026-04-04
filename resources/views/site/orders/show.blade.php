@extends('layouts.site')

@php
    $orderCode = $order->code ?: ('#' . $order->id);

    $statusClass = match((string) $order->status) {
        'completed', 'delivered' => 'success',
        'shipping', 'packed', 'packing' => 'info',
        'cancelled', 'rejected' => 'danger',
        default => 'warning',
    };

    $paymentClass = match((string) $order->payment_status) {
        'paid' => 'success',
        'partial' => 'warning',
        default => 'danger',
    };

    $deliveryClass = match((string) $order->delivery_status) {
        'delivered' => 'success',
        'shipping', 'shipped' => 'info',
        default => 'secondary',
    };

    $createdAt = optional($order->created_at)->format('d/m/Y H:i');
    $itemCount = (int) $order->items->sum('quantity');
    $lineCount = (int) $order->items->count();
    $orderSubtotalAmount = (float) ($order->subtotal_amount ?? $order->items->sum(function ($item) {
        return (float) (($item->base_price ?? $item->price ?? 0) * ($item->quantity ?? 0));
    }));
    $orderItemDiscount = (float) ($order->item_discount_total ?? $order->items->sum('discount_total'));
    $orderExtraDiscount = (float) ($order->extra_discount_total ?? $order->order_discount ?? 0);
    $orderTotalDiscount = (float) ($order->total_discount ?? ($orderItemDiscount + $orderExtraDiscount));
    $orderTotalWeight = (float) ($order->total_weight ?? $order->items->sum('total_weight'));
    $isCopiedOrder = !empty($order->copied_from_order_id);
    $canEdit = $isCopiedOrder
        || ($order->status === \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL
            && $order->created_at?->isToday());
@endphp

@push('styles')
<style>
    .order-detail-page {
        --order-ink: #0f172a;
        --order-muted: #64748b;
        --order-line: rgba(148, 163, 184, 0.26);
        --order-surface: #ffffff;
        background:
            radial-gradient(circle at top left, rgba(20, 184, 166, 0.09), transparent 24%),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 24%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        padding: 32px 0 48px;
    }

    .order-shell {
        max-width: 1180px;
    }

    .order-hero {
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(15, 118, 110, 0.88));
        box-shadow: 0 16px 42px rgba(15, 23, 42, 0.14);
        color: #f8fafc;
        padding: 22px 24px;
        margin-bottom: 16px;
    }

    .order-hero h1 {
        margin: 8px 0 6px;
        font-size: clamp(1.35rem, 2.2vw, 1.95rem);
        font-weight: 900;
        letter-spacing: -0.02em;
    }

    .order-hero p {
        margin: 0;
        color: rgba(248, 250, 252, 0.84);
        font-size: 0.9rem;
    }

    .order-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.08);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
    }

    .order-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 14px;
    }

    .order-kpi {
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.1);
    }

    .order-kpi-label {
        display: block;
        font-size: 0.72rem;
        color: rgba(248, 250, 252, 0.72);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 4px;
    }

    .order-kpi-value {
        font-size: 1.03rem;
        font-weight: 800;
        color: #fff;
    }

    .order-panel {
        border: 1px solid var(--order-line);
        border-radius: 18px;
        background: var(--order-surface);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .order-panel-body {
        padding: 18px;
    }

    .order-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: var(--order-ink);
    }

    .order-subtitle {
        margin: 6px 0 0;
        color: var(--order-muted);
        font-size: 0.86rem;
    }

    .order-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 14px;
    }

    .order-meta-item {
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 12px;
        padding: 10px 12px;
        background: #f8fafc;
    }

    .order-meta-label {
        display: block;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--order-muted);
        margin-bottom: 4px;
    }

    .order-meta-value {
        font-size: 0.93rem;
        font-weight: 700;
        color: var(--order-ink);
    }

    .order-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    .order-table {
        margin-bottom: 0;
        vertical-align: middle;
    }

    .order-table thead th {
        white-space: nowrap;
        background: #f8fafc;
        color: #334155;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .order-product {
        min-width: 230px;
    }

    .order-product strong {
        display: block;
        color: var(--order-ink);
    }

    .order-product small {
        color: var(--order-muted);
    }

    .order-money,
    .order-qty {
        font-weight: 700;
        color: var(--order-ink);
        white-space: nowrap;
    }

    .order-actions {
        margin-top: 14px;
        display: flex;
        justify-content: flex-end;
        padding: 0 18px 18px;
    }

    @media (max-width: 991.98px) {
        .order-kpis,
        .order-meta-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="order-detail-page">
    <div class="container order-shell">
        <div class="order-hero">
            <span class="order-eyebrow"><i class="bi bi-receipt"></i> Chi tiết đơn hàng</span>
            <h1>Đơn {{ $orderCode }}</h1>
            <p>Thông tin đơn, người nhận và danh sách sản phẩm được trình bày rõ ràng để theo dõi nhanh và chính xác.</p>

            <div class="order-kpis">
                <div class="order-kpi">
                    <span class="order-kpi-label">Ngày tạo</span>
                    <span class="order-kpi-value">{{ $createdAt ?: '-' }}</span>
                </div>
                <div class="order-kpi">
                    <span class="order-kpi-label">Số lượng sản phẩm</span>
                    <span class="order-kpi-value">{{ number_format($itemCount, 0, ',', '.') }}</span>
                </div>
                <div class="order-kpi">
                    <span class="order-kpi-label">Tổng thanh toán</span>
                    <span class="order-kpi-value">{{ number_format((float) $order->total, 0, ',', '.') }}đ</span>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="order-panel h-100">
                    <div class="order-panel-body">
                        <h2 class="order-title">Thông tin đơn hàng</h2>
                        <p class="order-subtitle">Các chỉ số trạng thái chính của đơn hiện tại.</p>

                        <div class="order-badges">
                            <span class="badge text-bg-{{ $statusClass }}">Trạng thái: {{ $order->status ?: 'N/A' }}</span>
                            <span class="badge text-bg-{{ $paymentClass }}">Thanh toán: {{ $order->payment_status ?: 'N/A' }}</span>
                            <span class="badge text-bg-{{ $deliveryClass }}">Giao hàng: {{ $order->delivery_status ?: 'N/A' }}</span>
                        </div>

                        <div class="order-meta-grid">
                            <div class="order-meta-item">
                                <span class="order-meta-label">Mã đơn</span>
                                <div class="order-meta-value">{{ $orderCode }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Khách hàng</span>
                                <div class="order-meta-value">{{ optional($order->customer)->name ?? 'N/A' }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Số dòng sản phẩm</span>
                                <div class="order-meta-value">{{ number_format($lineCount, 0, ',', '.') }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tổng tiền</span>
                                <div class="order-meta-value">{{ number_format((float) $order->total, 0, ',', '.') }}đ</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tiền hàng</span>
                                <div class="order-meta-value">{{ number_format($orderSubtotalAmount, 0, ',', '.') }}đ</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tiền giảm (discount)</span>
                                <div class="order-meta-value">{{ number_format($orderItemDiscount, 0, ',', '.') }}đ</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Giảm thêm (discount ngoài)</span>
                                <div class="order-meta-value">{{ number_format($orderExtraDiscount, 0, ',', '.') }}đ</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tổng tiền cuối cùng</span>
                                <div class="order-meta-value">{{ number_format((float) $order->total, 0, ',', '.') }}đ</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tổng discount</span>
                                <div class="order-meta-value">{{ number_format($orderTotalDiscount, 0, ',', '.') }}đ</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tổng khối lượng</span>
                                <div class="order-meta-value">{{ number_format($orderTotalWeight, 3, ',', '.') }} kg</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="order-panel h-100">
                    <div class="order-panel-body">
                        <h2 class="order-title">Thông tin người nhận</h2>
                        <p class="order-subtitle">Địa chỉ và liên hệ giao nhận của đơn hàng.</p>

                        <div class="order-meta-grid">
                            <div class="order-meta-item">
                                <span class="order-meta-label">Tên người nhận</span>
                                <div class="order-meta-value">{{ $order->recipient_name ?: 'N/A' }}</div>
                            </div>
                            <div class="order-meta-item">
                                <span class="order-meta-label">Số điện thoại</span>
                                <div class="order-meta-value">{{ $order->recipient_phone ?: 'N/A' }}</div>
                            </div>
                            <div class="order-meta-item" style="grid-column: 1 / -1;">
                                <span class="order-meta-label">Địa chỉ</span>
                                <div class="order-meta-value">{{ $order->recipient_address ?: 'N/A' }}</div>
                            </div>
                            @if($order->note)
                            <div class="order-meta-item" style="grid-column: 1 / -1;">
                                <span class="order-meta-label">Ghi chú</span>
                                <div class="order-meta-value">{{ $order->note }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="order-panel mt-3">
            <div class="order-panel-body">
                <h2 class="order-title">Danh sách sản phẩm</h2>
                <p class="order-subtitle">Chi tiết từng dòng sản phẩm trong đơn hàng.</p>

                <div class="table-responsive mt-3">
                    <table class="table order-table">
                        <thead>
                            <tr>
                                <th>Ảnh</th>
                                <th>Sản phẩm</th>
                                <th>Biến thể</th>
                                <th>SL</th>
                                <th>ĐVT</th>
                                <th>Kg/SP</th>
                                <th>Đơn giá</th>
                                <th>Discount</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            @php
                                $variant = $item->variant;
                                $unitLabel = optional(optional($item->variant)->product)->unit_label ?? 'Cái';
                                $weightUnitLabel = in_array((string) (optional(optional($item->variant)->product)->unit ?? 'cai'), ['con', 'cai'], true)
                                    ? 'Kg'
                                    : $unitLabel;
                                $imageUrl = $variant?->media_url
                                    ?? ($variant?->product?->avatar?->media
                                        ? asset('storage/' . $variant->product->avatar->media->file_path)
                                        : 'https://via.placeholder.com/56');
                            @endphp
                            <tr>
                                <td>
                                    <img src="{{ $imageUrl }}" alt="{{ optional(optional($item->variant)->product)->name ?? 'Product' }}" width="56" class="rounded border">
                                </td>
                                <td class="order-product">
                                    <strong>{{ optional(optional($item->variant)->product)->name ?? 'N/A' }}</strong>
                                    <small>SKU: {{ optional($item->variant)->sku ?? 'N/A' }}</small>
                                </td>
                                <td>{{ optional($item->variant)->name ?? 'N/A' }}</td>
                                <td class="order-qty">{{ number_format((float) $item->quantity, 0, ',', '.') }}</td>
                                <td class="order-qty">{{ $unitLabel }}</td>
                                <td class="order-qty">{{ number_format((float) ($item->unit_weight ?? 0), 3, ',', '.') }} {{ $weightUnitLabel }}</td>
                                <td class="order-money">{{ number_format((float) $item->price, 0, ',', '.') }}đ</td>
                                <td class="order-money">{{ number_format((float) ($item->discount_total ?? 0), 0, ',', '.') }}đ</td>
                                <td class="order-money">{{ number_format((float) ($item->price * $item->quantity), 0, ',', '.') }}đ</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" class="text-end"><strong>Tổng cộng</strong></td>
                                <td class="order-money"><strong>{{ number_format((float) $order->total, 0, ',', '.') }}đ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="order-actions">
                @if($canEdit)
                    <a href="{{ route('site.orders.edit', $order) }}" class="btn btn-primary me-2"><i class="fa fa-pencil me-1"></i> Sửa đơn</a>
                @endif
                <a href="{{ route('pages.my_orders') }}" class="btn btn-outline-primary">Quay lại danh sách đơn</a>
            </div>
        </div>
    </div>
</section>
@endsection
