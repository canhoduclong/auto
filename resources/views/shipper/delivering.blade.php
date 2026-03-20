@extends('layouts.shipper')

@section('title', 'Đơn của tôi')
@section('subtitle', 'Bao gồm đơn đang giao và đơn đã hoàn thành')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-warning text-dark rounded-pill">Đang giao: {{ $orders->where('status', 'delivering')->count() }}</span>
        <span class="badge bg-success rounded-pill">Hoàn thành: {{ $orders->where('status', 'completed')->count() }}</span>
    </div>
    <a href="{{ route('shipper.available') }}" class="btn btn-outline-info btn-sm">
        <i class="bi bi-collection me-1"></i>Nhận thêm đơn
    </a>
</div>

@if($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="bi bi-truck fs-1 text-muted"></i>
        <p class="mt-2 text-muted">Bạn chưa có đơn đang giao hoặc đã hoàn thành.</p>
        <a href="{{ route('shipper.available') }}" class="btn btn-success btn-sm mx-auto" style="width:fit-content">
            <i class="bi bi-collection me-1"></i>Xem đơn có thể nhận
        </a>
    </div>
@else
<div class="row g-3">
    @foreach($orders as $order)
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4 {{ $order->status === 'completed' ? 'border-success' : 'border-warning' }}">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">{{ $order->code }}</span>
                @if($order->status === 'completed')
                    <span class="badge bg-success">Hoàn thành</span>
                @else
                    <span class="badge bg-warning text-dark">Đang giao</span>
                @endif
            </div>
            <div class="card-body">
                {{-- Customer --}}
                <div class="mb-2">
                    <div class="fw-semibold">{{ $order->customer?->name ?? '—' }}</div>
                    @if($order->customer?->phone)
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <a href="tel:{{ $order->customer->phone }}" class="btn btn-outline-success btn-sm py-0">
                                <i class="bi bi-telephone-fill me-1"></i>{{ $order->customer->phone }}
                            </a>
                        </div>
                    @endif
                    @if($order->customer?->address)
                        <div class="text-muted small mt-1">
                            <i class="bi bi-geo-alt me-1"></i>{{ $order->customer->address }}
                        </div>
                    @endif
                </div>
                <hr class="my-2">
                <div class="row g-2 text-center mb-2">
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.72rem;">COD cần thu</div>
                        <div class="fw-bold text-success fs-6">{{ number_format($order->total) }}đ</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.72rem;">Số SP</div>
                        <div class="fw-bold">{{ $order->items->sum('quantity') }}</div>
                    </div>
                </div>
                <div class="bg-light rounded p-2 small">
                    @foreach($order->items->take(3) as $item)
                        <div class="d-flex justify-content-between text-truncate">
                            <span>{{ $item->name ?? $item->variant?->name ?? $item->product?->name ?? 'SP' }}</span>
                            <span class="text-muted ms-1">×{{ $item->quantity }}</span>
                        </div>
                    @endforeach
                    @if($order->items->count() > 3)
                        <div class="text-muted">+ {{ $order->items->count() - 3 }} khác</div>
                    @endif
                </div>
            </div>
            @if($order->status === 'completed')
                <div class="card-footer bg-white border-top">
                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Đơn đã hoàn thành, không còn thao tác</span>
                </div>
            @else
                <div class="card-footer bg-white border-top d-flex gap-2">
                    <a href="{{ route('shipper.delivered-form', $order) }}" class="btn btn-success flex-fill btn-sm">
                        <i class="bi bi-check-circle me-1"></i>Đã giao
                    </a>
                    <a href="{{ route('shipper.return-form', $order) }}" class="btn btn-outline-danger flex-fill btn-sm">
                        <i class="bi bi-arrow-return-left me-1"></i>Trả hàng
                    </a>
                </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
