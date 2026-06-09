@php
    $fmt = static fn ($value, int $decimals = 3) => rtrim(rtrim(number_format((float) $value, $decimals, ',', '.'), '0'), ',');
    $isReady = in_array($order->status, ['approved', 'ready_to_pack'], true);
    $isPacking = $order->status === 'packing';
    $isPacked = in_array($order->status, $packedStatuses, true);
    $stateClass = $isPacking ? 'is-packing' : ($isPacked ? 'is-packed' : 'is-unpacked');
    $meta = $statusMeta[$order->status] ?? ['label' => $order->status, 'class' => 'bg-secondary'];
    $canProcess = \Illuminate\Support\Carbon::parse($selectedDate)->isToday() && $order->created_at?->isToday();
    $totalQty = (float) $order->items->sum('quantity');
@endphp

<div class="card pkg-order-card mb-3" id="package-order-{{ $order->id }}">
    <div class="card-header bg-white d-flex align-items-center gap-3">
        <span class="pkg-order-sequence {{ $stateClass }}">{{ $order->daily_sequence ?? '—' }}</span>
        <div class="flex-grow-1">
            <div class="fw-bold fs-5">{{ $order->customer?->name ?? '—' }}</div>
            <div class="small text-muted">{{ $order->code }} · {{ $order->created_at?->format('d/m/Y H:i') }}</div>
        </div>
        <span class="badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-6"><div class="small text-muted">Sale</div><div class="fw-semibold">{{ $order->user?->name ?? '—' }}</div></div>
            <div class="col-6"><div class="small text-muted">Tổng số lượng</div><div class="fw-semibold">{{ $fmt($totalQty) }}</div></div>
            <div class="col-6"><div class="small text-muted">Kg thực tế</div><div class="fw-semibold">{{ $order->actual_weight !== null ? $fmt($order->actual_weight) . ' kg' : '—' }}</div></div>
            <div class="col-6"><div class="small text-muted">Phí ship</div><div class="fw-semibold">{{ $order->shipping_fee !== null ? number_format((float)$order->shipping_fee) . 'đ' : '—' }}</div></div>
        </div>

        <div class="pkg-items-scroll mb-3">
            <div class="pkg-items-table">
                <div class="pkg-item-head">
                    <div>Ảnh</div><div>Sản phẩm</div><div class="text-center">Size</div><div class="text-center">SL</div>
                    <div class="text-center">Tổng</div><div class="text-center">Khối lượng</div>
                    <div class="text-end">Đơn giá</div><div class="text-end">Thành tiền</div>
                </div>
                @foreach($order->items as $item)
                    @php
                        $variant = $item->variant;
                        $qty = (float) $item->quantity;
                        $price = (float) ($item->price ?? 0);
                        $pricedByKg = (bool) $item->effective_priced_by_kg;
                        $actualWeight = $item->actual_weight !== null ? (float) $item->actual_weight : null;
                        $computedWeight = round((float) $item->effective_unit_weight * $qty, 3);
                        $displayWeight = $actualWeight !== null && $actualWeight > 0 ? $actualWeight : $computedWeight;
                        $lineTotal = $pricedByKg
                            ? ($actualWeight !== null ? $actualWeight * $price : null)
                            : ($qty * $price);
                        $imagePath = $variant?->avatar?->media?->file_path ?? $item->product?->avatar?->media?->file_path;
                    @endphp
                    <div class="pkg-item-row">
                        <div>
                            @if($imagePath)
                                <img class="pkg-item-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $variant?->name ?? $item->product?->name }}">
                            @else
                                <span class="pkg-item-placeholder"><i class="bi bi-image"></i></span>
                            @endif
                        </div>
                        <div class="pkg-column fw-semibold">
                            {{ $variant?->name ?? $item->product?->name ?? 'Sản phẩm' }}
                            @if($variant?->sku)<div class="small text-muted">{{ $variant->sku }}</div>@endif
                        </div>
                        <div class="text-center fw-semibold">{{ $variant?->size !== null && $variant?->size !== '' ? $fmt($variant->size, 2) : '—' }}</div>
                        <div class="text-center fw-semibold">{{ $fmt($qty) }}</div>
                        <div class="text-center fw-semibold">{{ $item->display_total_label }}</div>
                        <div class="text-center">{{ $fmt($displayWeight) }} {{ $pricedByKg ? 'kg' : ($variant?->product?->unit_label ?? $item->product?->unit_label ?? 'cái') }}</div>
                        <div class="text-end">{{ number_format($price) }}đ</div>
                        <div class="text-end fw-bold">{{ $lineTotal !== null ? number_format($lineTotal) . 'đ' : '---' }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($isPacking)
            <form method="POST" action="{{ route('package.orders.logistics', $order) }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-4"><label class="form-label small">Kg thực tế</label><input type="number" step="0.001" min="0" name="actual_weight" class="form-control" required value="{{ $order->actual_weight }}"></div>
                <div class="col-md-4"><label class="form-label small">Phí ship</label><input type="number" step="1" min="0" name="shipping_fee" class="form-control" required value="{{ $order->shipping_fee }}"></div>
                <div class="col-md-4"><label class="form-label small">Phí thùng xốp</label><input type="number" step="1" min="0" name="foam_box_price" class="form-control" value="{{ $order->foam_box_price }}"></div>
                <div class="col-12"><button class="btn btn-outline-primary btn-sm"><i class="bi bi-save me-1"></i>Lưu thông tin đóng hàng</button></div>
            </form>
        @endif

        @if($isPacked)
            <div class="pkg-lock-note"><i class="bi bi-lock-fill me-1"></i><strong>Đơn đã đóng hàng và được khóa.</strong> Package không thể mở khóa hoặc chỉnh sửa lại đơn này.</div>
        @elseif(!$canProcess)
            <span class="badge bg-secondary">Chỉ được xử lý đơn hôm nay</span>
        @elseif($isReady)
            <form method="POST" action="{{ route('package.orders.start-packing', $order) }}" class="d-grid">@csrf<button class="btn btn-primary"><i class="bi bi-box2 me-1"></i>Bắt đầu đóng hàng</button></form>
        @elseif($isPacking)
            <form method="POST" action="{{ route('package.orders.complete-packing', $order) }}" class="d-grid">@csrf<button class="btn btn-success"><i class="bi bi-lock-fill me-1"></i>Hoàn tất và khóa đơn</button></form>
        @else
            <span class="badge bg-secondary">Không thể xử lý ở trạng thái hiện tại</span>
        @endif
    </div>
</div>
