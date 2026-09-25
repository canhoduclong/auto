@extends('layouts.shipper')

@section('title', 'Review & In ấn')
@section('subtitle', 'Kiểm tra và in phiếu giao hàng theo ngày')

@push('styles')
<style>
    .review-day { min-width: 82px; border: 1px solid #d1fae5; background: #fff; color: #065f46; border-radius: 10px; padding: 8px 12px; text-decoration: none; text-align: center; }
    .review-day:hover, .review-day.active { background: #047857; color: #fff; border-color: #047857; }
    .review-day strong { display:block; font-size:1rem; }
    .review-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .review-printed { background:#f0fdf4; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h5 class="mb-1 fw-bold">Review &amp; In ấn</h5>
        <div class="text-muted small">Danh sách lấy từ lộ trình đã phát hành gần nhất của ngày.</div>
    </div>
    <form method="GET" action="{{ route('shipper.manage-assignments.review.index') }}" class="d-flex gap-2">
        <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm">
        <button class="btn btn-success btn-sm"><i class="bi bi-search me-1"></i>Xem</button>
    </form>
</div>

<div class="d-flex gap-2 overflow-auto pb-2 mb-3">
    @foreach($quickDates as $day)
        <a href="{{ route('shipper.manage-assignments.review.index', ['date' => $day['date']]) }}" class="review-day {{ $selectedDate === $day['date'] ? 'active' : '' }}">
            <strong>{{ $day['label'] }}</strong>
            <span class="small">{{ $day['count'] }} đơn</span>
        </a>
    @endforeach
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<form id="bulk-print-form" method="POST" action="{{ route('shipper.manage-assignments.review.print') }}" target="_blank">
    @csrf
    <input type="hidden" name="date" value="{{ $selectedDate }}">
    <div class="review-card">
        <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>{{ date('d/m/Y', strtotime($selectedDate)) }}</strong>
                @if($dispatch)
                    <span class="badge bg-light text-dark ms-1">Lộ trình lần {{ $dispatch->version }}</span>
                    <span class="text-muted small ms-1">Phát hành {{ optional($dispatch->published_at)->format('H:i d/m/Y') }}</span>
                @endif
            </div>
            <button type="submit" class="btn btn-success btn-sm" {{ $orders->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-printer me-1"></i>In các phiếu đã chọn
            </button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:42px"><input id="select-all" type="checkbox" class="form-check-input"></th>
                        <th>Phiếu / Khách hàng</th><th>Lộ trình</th><th>Shipper</th><th>Trạng thái in</th><th class="text-end">Tổng tiền</th><th style="width:70px" class="text-center">In</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    @php($printed = $printHistories->get($order->id))
                    <tr class="{{ $printed ? 'review-printed' : '' }}">
                        <td><input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="form-check-input order-check"></td>
                        <td>
                            <div class="fw-bold">{{ $order->code ?: '#'.$order->id }}</div>
                            <div>{{ $order->recipient_name ?: $order->customer?->name ?: '—' }}</div>
                            <div class="small text-muted">{{ $order->recipient_address ?: $order->customer?->address ?: '—' }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $routeMeta[$order->id]['route_name'] ?? '—' }}</span><div class="small text-muted mt-1">Thứ tự {{ $routeMeta[$order->id]['sequence'] ?? '—' }}</div></td>
                        <td>{{ $order->shipper?->name ?: ($routeMeta[$order->id]['shipper_name'] ?? '—') }}</td>
                        <td>
                            @if($printed)
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Đã In</span>
                                <div class="small text-muted mt-1">{{ $printed->created_at?->format('H:i d/m/Y') }} · {{ $printed->user?->name }}</div>
                            @else
                                <span class="badge bg-secondary">Chưa in</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">{{ number_format((float) $order->total, 0, ',', '.') }}đ</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-success btn-sm print-one" data-order-id="{{ $order->id }}" title="In phiếu {{ $order->code }}"><i class="bi bi-printer"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Ngày này chưa có đơn trong lộ trình đã phát hành.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<form id="single-print-form" method="POST" action="{{ route('shipper.manage-assignments.review.print') }}" target="_blank" class="d-none">
    @csrf
    <input type="hidden" name="date" value="{{ $selectedDate }}">
    <input id="single-order-id" type="hidden" name="order_ids[]">
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checks = Array.from(document.querySelectorAll('.order-check'));
    const selectAll = document.getElementById('select-all');
    selectAll?.addEventListener('change', () => checks.forEach(check => check.checked = selectAll.checked));
    document.querySelectorAll('.print-one').forEach(button => button.addEventListener('click', function () {
        document.getElementById('single-order-id').value = this.dataset.orderId;
        document.getElementById('single-print-form').submit();
        window.setTimeout(() => window.location.reload(), 1200);
    }));
    document.getElementById('bulk-print-form')?.addEventListener('submit', function (event) {
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
