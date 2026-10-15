<section class="card mb-3" id="reconciliation-adjustments">
    <div class="card-header d-flex flex-wrap justify-content-between gap-2">
        <strong>Yêu cầu điều chỉnh đơn đã giao — chờ Kế toán duyệt</strong>
        <span class="badge bg-warning text-dark">{{ $reconciliationAdjustments->total() }} yêu cầu</span>
    </div>
    <div class="card-body">
        <p class="small text-muted">Danh sách chờ duyệt trên tất cả các ngày. Đối chiếu đơn đã giao với nội dung sale đề nghị; số liệu đơn chỉ thay đổi sau khi yêu cầu được duyệt và hoàn tất các bước xử lý cần thiết.</p>
        @forelse($reconciliationAdjustments as $adjustment)
            <details class="border rounded p-3 mb-2">
                <summary class="fw-semibold" style="cursor:pointer;">
                    {{ $adjustment->order?->code ?: '#'.$adjustment->order_id }} · {{ $adjustment->order?->customer?->name ?: 'Khách hàng' }}
                    · Yêu cầu #{{ $adjustment->id }}
                </summary>
                <div class="small text-muted mt-2">Người gửi: {{ $adjustment->requester?->name ?: '—' }} · {{ $adjustment->submitted_at?->format('d/m/Y H:i') }}</div>
                @if($adjustment->adjustment_note)<div class="my-2">Lý do: {{ $adjustment->adjustment_note }}</div>@endif
                @include('site.orders.adjustments._product_comparison', ['adjustment' => $adjustment, 'comparisonSection' => 'both', 'comparisonShowOrderInformation' => true])
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('site.order-adjustments.show', $adjustment) }}" class="btn btn-outline-primary btn-sm">Chi tiết và bằng chứng</a>
                    <form method="POST" action="{{ route('site.order-adjustments.approve', $adjustment) }}">
                        @csrf
                        <button class="btn btn-success btn-sm" onclick="return confirm('Duyệt yêu cầu điều chỉnh này?');">Duyệt điều chỉnh</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('site.order-adjustments.reject', $adjustment) }}" class="d-flex flex-wrap gap-2 mt-2">
                    @csrf
                    <input class="form-control form-control-sm" name="reason" required maxlength="2000" placeholder="Lý do từ chối">
                    <button class="btn btn-outline-danger btn-sm">Từ chối yêu cầu</button>
                </form>
            </details>
        @empty
            <div class="text-muted small">Không có yêu cầu điều chỉnh đang chờ Kế toán.</div>
        @endforelse
        @if($reconciliationAdjustments->hasPages())
            {{ $reconciliationAdjustments->fragment('reconciliation-adjustments')->links() }}
        @endif
    </div>
</section>
