@extends('layouts.warehouse')

@section('title', 'Review & In ấn')

@push('styles')
<style>
    .review-day { min-width:82px; border:1px solid #d1fae5; background:#fff; color:#065f46; border-radius:10px; padding:8px 12px; text-decoration:none; text-align:center; }
    .review-day:hover,.review-day.active { background:#047857; color:#fff; border-color:#047857; }
    .review-day strong { display:block; font-size:1rem; }
    .review-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .review-printed { background:#f0fdf4; }
    .review-priority { min-width:44px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; background:#047857; color:#fff; font-size:1.05rem; font-weight:700; }
    .review-customer-name { font-size:1rem; font-weight:700; color:#0f172a; }
    .review-order-code { font-size:.75rem; font-weight:400; color:#64748b; text-transform:lowercase; }
    .review-contact { font-size:.82rem; color:#475569; }
    .review-item-line { min-height:24px; padding:2px 0; border-bottom:1px dashed #e2e8f0; }
    .review-item-line:last-child { border-bottom:0; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h5 class="mb-1 fw-bold">Review &amp; In ấn của Kho</h5>
        <div class="text-muted small">Hiển thị đơn toàn hệ thống theo ngày giao, kể cả đơn chưa nhập hoặc chưa gán kho.</div>
    </div>
    <form method="GET" action="{{ route('warehouse.assignment-review.index') }}" class="d-flex gap-2">
        <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm">
        <button class="btn btn-success btn-sm"><i class="bi bi-search me-1"></i>Xem</button>
    </form>
</div>

<div class="d-flex gap-2 overflow-auto pb-2 mb-3">
    @foreach($quickDates as $day)
        <a href="{{ route('warehouse.assignment-review.index', ['date' => $day['date']]) }}" class="review-day {{ $selectedDate === $day['date'] ? 'active' : '' }}">
            <strong>{{ $day['label'] }}</strong><span class="small">{{ $day['count'] }} đơn</span>
        </a>
    @endforeach
</div>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<form id="warehouse-bulk-print" method="POST" action="{{ route('warehouse.assignment-review.print') }}" target="_blank">
    @csrf
    <input type="hidden" name="date" value="{{ $selectedDate }}">
    <div class="review-card">
        <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>{{ date('d/m/Y', strtotime($selectedDate)) }}</strong>
                @if($dispatch)
                    <span class="badge bg-light text-dark ms-1">Lộ trình lần {{ $dispatch->version }}</span>
                @endif
            </div>
            <button class="btn btn-success btn-sm" {{ $orders->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-printer me-1"></i>In các phiếu đã chọn
            </button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr>
                    <th style="width:42px"><input id="warehouse-select-all" type="checkbox" class="form-check-input"></th>
                    <th class="text-center">STT ưu tiên</th><th>Khách hàng / Đơn hàng</th><th>Sản phẩm</th><th>Size</th><th class="text-center">Số lượng</th><th class="text-end">Khối lượng</th><th>Shipper</th><th>Trạng thái in của Kho</th><th class="text-end">Tổng tiền</th><th class="text-center">In</th>
                </tr></thead>
                <tbody>
                @forelse($orders as $order)
                    @php($printed = $printHistories->get($order->id))
                    <tr class="{{ $printed ? 'review-printed' : '' }}">
                        <td><input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="form-check-input warehouse-order-check"></td>
                        <td class="text-center"><span class="review-priority">{{ $order->daily_sequence ?? '—' }}</span></td>
                        <td>
                            <div class="review-customer-name">{{ $order->recipient_name ?: $order->customer?->name ?: '—' }}</div>
                            <div class="review-order-code">{{ \Illuminate\Support\Str::lower($order->code ?: '#'.$order->id) }}</div>
                            <div class="review-contact"><i class="bi bi-telephone me-1"></i>{{ $order->recipient_phone ?: $order->customer?->phone ?: '—' }}</div>
                            <div class="review-contact"><i class="bi bi-geo-alt me-1"></i>{{ $order->recipient_address ?: $order->customer?->address ?: '—' }}</div>
                        </td>
                        <td>
                            @forelse($order->items as $item)
                                <div class="review-item-line fw-semibold">{{ $item->display_name }}</div>
                            @empty
                                <div class="review-item-line text-muted">—</div>
                            @endforelse
                        </td>
                        <td>
                            @forelse($order->items as $item)
                                @php($size = $item->variant?->size)
                                <div class="review-item-line">{{ $size !== null ? rtrim(rtrim(number_format((float) $size, 2, ',', '.'), '0'), ',') : '—' }}</div>
                            @empty
                                <div class="review-item-line text-muted">—</div>
                            @endforelse
                        </td>
                        <td class="text-center">
                            @forelse($order->items as $item)
                                <div class="review-item-line">{{ number_format((float) ($item->quantity ?? 0), 0, ',', '.') }}</div>
                            @empty
                                <div class="review-item-line text-muted">—</div>
                            @endforelse
                        </td>
                        <td class="text-end">
                            @forelse($order->items as $item)
                                @php($weight = $item->actual_weight ?? $item->packed_weight ?? $item->total_weight ?? ((float) $item->effective_unit_weight * (float) ($item->quantity ?? 0)))
                                <div class="review-item-line text-nowrap">{{ rtrim(rtrim(number_format((float) $weight, 3, ',', '.'), '0'), ',') }} kg</div>
                            @empty
                                <div class="review-item-line text-muted">—</div>
                            @endforelse
                        </td>
                        <td>{{ $order->shipper?->name ?: ($routeMeta[$order->id]['shipper_name'] ?? '—') }}</td>
                        <td>
                            @if($printed)
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Kho đã in</span>
                                <div class="small text-muted mt-1">{{ $printed->created_at?->format('H:i d/m/Y') }} · {{ $printed->user?->name }}</div>
                            @else
                                <span class="badge bg-secondary">Kho chưa in</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">{{ number_format((float) $order->total, 0, ',', '.') }}đ</td>
                        <td class="text-center"><button type="button" class="btn btn-outline-success btn-sm warehouse-print-one" data-order-id="{{ $order->id }}"><i class="bi bi-printer"></i></button></td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Không có đơn trong ngày giao này.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<form id="warehouse-single-print" method="POST" action="{{ route('warehouse.assignment-review.print') }}" target="_blank" class="d-none">
    @csrf
    <input type="hidden" name="date" value="{{ $selectedDate }}">
    <input id="warehouse-single-order-id" type="hidden" name="order_ids[]">
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checks = Array.from(document.querySelectorAll('.warehouse-order-check'));
    document.getElementById('warehouse-select-all')?.addEventListener('change', function () {
        checks.forEach(check => check.checked = this.checked);
    });
    document.querySelectorAll('.warehouse-print-one').forEach(button => button.addEventListener('click', function () {
        document.getElementById('warehouse-single-order-id').value = this.dataset.orderId;
        document.getElementById('warehouse-single-print').submit();
        window.setTimeout(() => window.location.reload(), 1200);
    }));
    document.getElementById('warehouse-bulk-print')?.addEventListener('submit', function (event) {
        if (!checks.some(check => check.checked)) {
            event.preventDefault();
            window.alert('Vui lòng chọn ít nhất một phiếu để in.');
            return;
        }
        window.setTimeout(() => window.location.reload(), 1200);
    });
});
</script>
@endpush
