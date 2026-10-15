<section class="stocktake-card mb-3" id="packed-orders" aria-labelledby="packed-orders-title">
    <div class="p-3 border-bottom">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <h5 class="mb-0 fw-semibold" id="packed-orders-title"><i class="bi bi-lock-fill me-1"></i>Hàng tồn theo đơn đã đóng</h5>
            <span class="badge bg-primary align-self-start">Chỉ xem · {{ $packedOrderReservations->total() }} dòng giữ hàng</span>
        </div>
        <div class="small text-muted mt-2">
            Hàng đang được giữ riêng tại {{ $warehouse->name }} ở thời điểm hiện tại, không phải số dư lịch sử theo ngày kiểm kê.
            Phần này không thể sửa trong kiểm kê. Hàng được giữ đến khi shipper lấy hoặc đơn bị hủy; khi chuyển kho, phần giữ riêng chuyển theo hàng sang kho nhận.
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Đơn / Khách hàng</th>
                    <th>Sản phẩm / Biến thể</th>
                    <th>SKU</th>
                    <th class="text-end">SL giữ riêng</th>
                    <th class="text-end">Kg đã đóng</th>
                    <th>Chốt giữ hàng lúc</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packedOrderReservations as $reservation)
                    @php
                        $packedOrder = $reservation->orderItem?->order;
                        $packedVariant = $reservation->inventory?->productVariant;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $packedOrder?->code ?: '#'.$packedOrder?->id }}</div>
                            <div class="small text-muted">{{ $packedOrder?->customer?->name ?: $packedOrder?->recipient_name ?: 'Chưa cập nhật' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $packedVariant?->product?->name ?: 'Sản phẩm' }}</div>
                            <div class="small text-muted">{{ $packedVariant?->name ?: '—' }}</div>
                        </td>
                        <td>{{ $packedVariant?->sku ?: '—' }}</td>
                        <td class="text-end fw-bold text-primary">{{ number_format((float) $reservation->quantity, 0, ',', '.') }}</td>
                        <td class="text-end">{{ $reservation->packed_weight_kg !== null ? number_format((float) $reservation->packed_weight_kg, 3, ',', '.') : 'Chưa ghi nhận' }}</td>
                        <td>{{ $reservation->packed_at?->format('d/m/Y H:i') ?: 'Chưa ghi nhận' }}</td>
                        <td><span class="badge bg-secondary"><i class="bi bi-lock-fill me-1"></i>Giữ riêng · không chỉnh sửa</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Không có hàng đã đóng giữ riêng phù hợp tại kho này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($packedOrderReservations->hasPages())
        <div class="p-3 border-top">{{ $packedOrderReservations->fragment('packed-orders')->links() }}</div>
    @endif
</section>
