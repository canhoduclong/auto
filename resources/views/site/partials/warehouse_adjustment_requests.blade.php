@php($warehouseRequests = collect($pendingWarehouseAdjustments ?? []))
<section class="dashboard-card dashboard-approval-queue" id="warehouse-adjustment-requests" aria-label="Yêu cầu xác nhận thay đổi đơn từ kho và đóng hàng">
    <div class="dashboard-approval-head">
        <h2><i class="bi bi-box-seam me-1"></i>Yêu cầu xác nhận từ kho / đóng hàng</h2>
        <span class="dashboard-approval-count">{{ $warehouseRequests->count() }}</span>
    </div>
    <div class="dashboard-approval-list">
        @forelse($warehouseRequests as $order)
            <article class="dashboard-approval-item" id="warehouse-adjustment-{{ $order->id }}">
                <div class="dashboard-approval-title">{{ $order->customer?->name ?: 'Khách hàng' }} · Đơn {{ $order->code ?: '#'.$order->id }}</div>
                <div class="dashboard-approval-meta">
                    {{ $order->warehouse?->name ?: 'Kho / đóng hàng' }} · Người gửi: {{ $order->warehouseAdjustmentRequester?->name ?: '—' }}
                    · {{ $order->warehouse_adjustment_requested_at?->format('H:i d/m/Y') }}
                </div>
                <div class="dashboard-approval-note"><strong>Lý do:</strong> {{ $order->warehouse_adjustment_note }}</div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Sản phẩm / quy cách</th><th>SL hiện tại</th><th>SL đề nghị</th></tr></thead>
                        <tbody>
                            @foreach($order->warehouse_adjustment_changes ?? [] as $change)
                                <tr>
                                    <td>{{ $change['product_name'] ?? 'Sản phẩm' }}
                                        @if(!empty($change['sku']))<span class="text-muted"> · {{ $change['sku'] }}</span>@endif
                                        @if(!empty($change['size']))<span class="text-muted"> · {{ $change['size'] }} kg</span>@endif
                                    </td>
                                    <td>{{ $change['old_quantity'] ?? 0 }}</td>
                                    <td class="fw-semibold">{{ $change['new_quantity'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('pages.my_dashboard.order_adjustments.confirm', $order) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Xác nhận thay đổi</button>
                </form>
                <details class="mt-2">
                    <summary class="text-danger">Từ chối yêu cầu</summary>
                    <form method="POST" action="{{ route('pages.my_dashboard.order_adjustments.reject', $order) }}" class="mt-2">
                        @csrf
                        <label for="reject-reason-{{ $order->id }}" class="form-label">Lý do từ chối</label>
                        <textarea id="reject-reason-{{ $order->id }}" name="reject_reason" class="form-control mb-2" required maxlength="2000" rows="2"></textarea>
                        <button type="submit" class="btn btn-outline-danger btn-sm">Gửi từ chối</button>
                    </form>
                </details>
            </article>
        @empty
            <p class="dashboard-empty mb-0">Không có yêu cầu thay đổi đơn đang chờ xác nhận.</p>
        @endforelse
    </div>
</section>
