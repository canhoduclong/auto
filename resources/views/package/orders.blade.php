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
.pkg-order-items { display:grid; gap:7px; }
.pkg-order-item {
    display:grid; grid-template-columns:minmax(150px,1fr) 90px 80px;
    gap:8px; padding:8px 10px; border:1px solid #e2e8f0; border-radius:9px; background:#f8fafc;
}
.pkg-lock-note { background:#ecfdf5; color:#166534; border:1px solid #bbf7d0; border-radius:9px; padding:9px 11px; }
@media(max-width:767.98px) {
    .pkg-order-item { grid-template-columns:1fr; }
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

@forelse($orders as $order)
    @php
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
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3"><div class="small text-muted">Sale</div><div class="fw-semibold">{{ $order->user?->name ?? '—' }}</div></div>
                <div class="col-6 col-md-3"><div class="small text-muted">Tổng số lượng</div><div class="fw-semibold">{{ rtrim(rtrim(number_format($totalQty, 3, '.', ''), '0'), '.') }}</div></div>
                <div class="col-6 col-md-3"><div class="small text-muted">Kg thực tế</div><div class="fw-semibold">{{ $order->actual_weight !== null ? number_format((float)$order->actual_weight, 3) : '—' }}</div></div>
                <div class="col-6 col-md-3"><div class="small text-muted">Phí ship</div><div class="fw-semibold">{{ $order->shipping_fee !== null ? number_format((float)$order->shipping_fee) . 'đ' : '—' }}</div></div>
            </div>

            <details class="mb-3">
                <summary class="fw-semibold">Danh sách hàng hóa ({{ $order->items->count() }})</summary>
                <div class="pkg-order-items mt-2">
                    @foreach($order->items as $item)
                        <div class="pkg-order-item">
                            <div class="fw-semibold">{{ $item->product?->name ?? $item->variant?->name ?? 'Sản phẩm' }}</div>
                            <div>Size: {{ $item->variant?->size ?: '—' }}</div>
                            <div>SL: {{ rtrim(rtrim(number_format((float)$item->quantity, 3, '.', ''), '0'), '.') }}</div>
                        </div>
                    @endforeach
                </div>
            </details>

            @if($isPacking)
                <form method="POST" action="{{ route('package.orders.logistics', $order) }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small">Kg thực tế</label>
                        <input type="number" step="0.001" min="0" name="actual_weight" class="form-control" required value="{{ $order->actual_weight }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Phí ship</label>
                        <input type="number" step="1" min="0" name="shipping_fee" class="form-control" required value="{{ $order->shipping_fee }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Phí thùng xốp</label>
                        <input type="number" step="1" min="0" name="foam_box_price" class="form-control" value="{{ $order->foam_box_price }}">
                    </div>
                    <div class="col-12"><button class="btn btn-outline-primary btn-sm"><i class="bi bi-save me-1"></i>Lưu thông tin đóng hàng</button></div>
                </form>
            @endif

            @if($isPacked)
                <div class="pkg-lock-note">
                    <i class="bi bi-lock-fill me-1"></i><strong>Đơn đã đóng hàng và được khóa.</strong>
                    Package không thể mở khóa hoặc chỉnh sửa lại đơn này.
                </div>
            @elseif(!$canProcess)
                <span class="badge bg-secondary">Chỉ được xử lý đơn hôm nay</span>
            @elseif($isReady)
                <form method="POST" action="{{ route('package.orders.start-packing', $order) }}" class="d-grid">
                    @csrf
                    <button class="btn btn-primary"><i class="bi bi-box2 me-1"></i>Bắt đầu đóng hàng</button>
                </form>
            @elseif($isPacking)
                <form method="POST" action="{{ route('package.orders.complete-packing', $order) }}" class="d-grid">
                    @csrf
                    <button class="btn btn-success"><i class="bi bi-lock-fill me-1"></i>Hoàn tất và khóa đơn</button>
                </form>
            @else
                <span class="badge bg-secondary">Không thể xử lý ở trạng thái hiện tại</span>
            @endif
        </div>
    </div>
@empty
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-check2-all fs-1 text-success"></i>
        <p class="mt-2 text-muted">Không có đơn hàng trong bộ lọc này.</p>
    </div>
@endforelse
@endsection
