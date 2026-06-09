@extends('layouts.package')

@section('title', 'Đơn cần đóng hàng')

@push('styles')
<style>
.pkg-order-nav {
    position:sticky; top:72px; z-index:90; padding:12px 14px; margin-bottom:18px;
    background:#fff; border:1px solid #e2e8f0; border-radius:12px;
    box-shadow:0 4px 15px rgba(15,23,42,.08);
}
.pkg-order-nav-pill, .pkg-order-sequence {
    display:inline-flex; align-items:center; justify-content:center; border-radius:999px;
    color:#fff; font-weight:800; text-decoration:none;
}
.pkg-order-nav-pill { min-width:36px; height:36px; padding:0 8px; }
.pkg-order-sequence { width:42px; height:42px; flex:0 0 42px; font-size:1.05rem; }
.is-unpacked { background:#64748b; }
.is-packing { background:#ffc107; color:#212529 !important; }
.is-packed { background:#198754; }
.pkg-order-card { border:0; border-radius:14px; box-shadow:0 8px 20px rgba(15,23,42,.08); scroll-margin-top:145px; }
.pkg-items-scroll { overflow-x:auto; }
.pkg-items-table { min-width:820px; }
.pkg-item-head, .pkg-item-row {
    display:grid; grid-template-columns:52px minmax(170px,1fr) 65px 55px 90px 100px 95px 110px;
    gap:7px; align-items:center;
}
.pkg-item-head {
    padding:8px; border-radius:9px 9px 0 0; background:#eef2f7;
    color:#64748b; font-size:.7rem; font-weight:800; text-transform:uppercase;
}
.pkg-item-row { padding:9px 8px; border-bottom:1px solid #e2e8f0; background:#f8fafc; font-size:.82rem; }
.pkg-item-thumb { width:42px; height:42px; border-radius:8px; object-fit:cover; border:1px solid #e2e8f0; }
.pkg-item-placeholder { width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; background:#e2e8f0; color:#64748b; }
.pkg-column { min-width:0; }
.pkg-lock-note { background:#ecfdf5; color:#166534; border:1px solid #bbf7d0; border-radius:9px; padding:9px 11px; }
@media(max-width:767.98px) {
    .pkg-orders-column-packed { border-left:0 !important; }
}
</style>
@endpush

@section('content')
@php
    $packedStatuses = ['packed', 'packed_waiting_pickup', 'delivering', 'delivered', 'completed'];
    $statusMeta = [
        'approved' => ['label' => 'Chờ đóng hàng', 'class' => 'bg-secondary'],
        'ready_to_pack' => ['label' => 'Chờ đóng hàng', 'class' => 'bg-secondary'],
        'packing' => ['label' => 'Đang đóng hàng', 'class' => 'bg-warning text-dark'],
        'packed' => ['label' => 'Đã đóng hàng', 'class' => 'bg-success'],
        'packed_waiting_pickup' => ['label' => 'Chờ shipper nhận', 'class' => 'bg-success'],
        'delivering' => ['label' => 'Đang giao', 'class' => 'bg-success'],
        'delivered' => ['label' => 'Đã giao', 'class' => 'bg-success'],
        'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-success'],
    ];
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('package.orders') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Ngày</label>
                <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Chờ đóng hàng</option>
                    <option value="ready_to_pack" {{ $status === 'ready_to_pack' ? 'selected' : '' }}>Sẵn sàng đóng</option>
                    <option value="packing" {{ $status === 'packing' ? 'selected' : '' }}>Đang đóng hàng</option>
                    <option value="packed_waiting_pickup" {{ $status === 'packed_waiting_pickup' ? 'selected' : '' }}>Đã đóng hàng</option>
                </select>
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Lọc</button>
                <a href="{{ route('package.orders') }}" class="btn btn-outline-secondary">Hôm nay</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="badge bg-dark">Tổng đơn: {{ $orders->count() }}</span>
    <span class="badge bg-secondary">Chờ đóng: {{ $orders->whereIn('status', ['approved', 'ready_to_pack'])->count() }}</span>
    <span class="badge bg-warning text-dark">Đang đóng: {{ $orders->where('status', 'packing')->count() }}</span>
    <span class="badge bg-success">Đã khóa: {{ $orders->whereIn('status', $packedStatuses)->count() }}</span>
</div>

@if($orders->isNotEmpty())
    <div class="pkg-order-nav">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-bold text-muted me-1"><i class="bi bi-list-ol me-1"></i>Điều hướng nhanh:</span>
            @foreach($orders as $navOrder)
                @php
                    $navClass = $navOrder->status === 'packing' ? 'is-packing' : (in_array($navOrder->status, $packedStatuses, true) ? 'is-packed' : 'is-unpacked');
                @endphp
                <a href="#package-order-{{ $navOrder->id }}" class="pkg-order-nav-pill {{ $navClass }}"
                   onclick="event.preventDefault(); document.getElementById('package-order-{{ $navOrder->id }}')?.scrollIntoView({behavior:'smooth',block:'start'});">
                    {{ $navOrder->daily_sequence ?? '—' }}
                </a>
            @endforeach
        </div>
    </div>
@endif

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-check2-all fs-1 text-success"></i>
        <p class="mt-2 text-muted">Không có đơn hàng trong bộ lọc này.</p>
    </div>
@else
    @php
        $packedOrders = $orders->filter(fn ($order) => in_array((string) $order->status, $packedStatuses, true));
        $unpackedOrders = $orders->reject(fn ($order) => in_array((string) $order->status, $packedStatuses, true));
    @endphp
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold" style="color:#64748b;"><i class="bi bi-box-seam me-2"></i>Chưa đóng hàng</h5>
                <span class="badge bg-secondary">{{ $unpackedOrders->count() }} đơn</span>
            </div>
            @foreach($unpackedOrders as $order)
                @include('package.orders._order_card', compact('order', 'packedStatuses', 'statusMeta', 'selectedDate'))
            @endforeach
        </div>
        <div class="col-12 col-lg-6 border-start pkg-orders-column-packed">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold text-success"><i class="bi bi-check-circle me-2"></i>Đã đóng hàng</h5>
                <span class="badge bg-success">{{ $packedOrders->count() }} đơn</span>
            </div>
            @foreach($packedOrders as $order)
                @include('package.orders._order_card', compact('order', 'packedStatuses', 'statusMeta', 'selectedDate'))
            @endforeach
        </div>
    </div>
@endif
@endsection
