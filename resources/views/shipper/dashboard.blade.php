@extends('layouts.shipper')

@section('title', 'Dashboard Shipper')
@section('subtitle', 'Thống kê hôm nay')

@section('content')
{{-- KPI Cards --}}

 
    <style>
       
        
        /* Bỏ đường viền mặc định của card và tăng độ cong */
        .order-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); /* Đổ bóng nhẹ */
            transition: transform 0.2s ease;
        }
        
        .order-card:active {
            transform: scale(0.98); /* Hiệu ứng nhấn trên mobile */
        }

        /* Khung hiển thị số thứ tự và thời gian */
        .time-block {
            min-width: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
    </style> 

    
    



<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-calendar-check fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Tổng đơn hôm nay</div>
                    <div class="fs-3 fw-bold text-primary">{{ $stats['today_total'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-truck fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Đang giao</div>
                    <div class="fs-3 fw-bold text-warning">{{ $stats['delivering'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Đã giao hôm nay</div>
                    <div class="fs-3 fw-bold text-success">{{ $stats['delivered_today'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-arrow-return-left fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Đơn trả hàng</div>
                    <div class="fs-3 fw-bold text-danger">{{ $stats['returning'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-emerald bg-opacity-10" style="background:rgba(16,185,129,.12);">
                    <i class="bi bi-cash-coin fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">COD đã thu hôm nay</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($stats['cod_today']) }}đ</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-info bg-opacity-10 text-info">
                    <i class="bi bi-collection fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Đơn có thể nhận</div>
                    <div class="fs-3 fw-bold text-info">{{ $stats['available'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delivery schedule confirmation --}}
@php
    $scheduleStatusMeta = match ($deliveryScheduleStatus ?? 'none') {
        'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'bg-success', 'icon' => 'bi-check2-circle'],
        'rejected' => ['label' => 'Đã từ chối', 'class' => 'bg-danger', 'icon' => 'bi-x-circle'],
        'changed' => ['label' => 'Có thay đổi cần xác nhận lại', 'class' => 'bg-warning text-dark', 'icon' => 'bi-exclamation-triangle'],
        'waiting' => ['label' => 'Chờ xác nhận', 'class' => 'bg-primary', 'icon' => 'bi-clock-history'],
        default => ['label' => 'Chưa có lộ trình', 'class' => 'bg-secondary', 'icon' => 'bi-inbox'],
    };
    $needsScheduleDecision = ($deliveryScheduleOrders ?? collect())->isNotEmpty()
        && !in_array(($deliveryScheduleStatus ?? 'none'), ['confirmed'], true);
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="mb-0 fw-bold">Xác nhận Lộ trình giao hàng</h5>
                    <span class="badge {{ $scheduleStatusMeta['class'] }}">
                        <i class="bi {{ $scheduleStatusMeta['icon'] }} me-1"></i>{{ $scheduleStatusMeta['label'] }}
                    </span>
                </div>
                <div class="text-muted small mt-1">
                    Lộ trình do Shipper Manager tạo ngày {{ \Carbon\Carbon::parse($selectedDate ?? now())->format('d/m/Y') }}.
                    Shipper cần xác nhận để đơn hàng được hiển thị trong mục “Có thể nhận” trên app.
                </div>
            </div>
            <a href="{{ route('shipper.delivery-schedules') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-map me-1"></i>Xem chi tiết
            </a>
        </div>

        @if(($deliveryScheduleOrders ?? collect())->isEmpty())
            <div class="text-center text-muted py-4 border rounded-3 bg-light">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                Hôm nay chưa có lộ trình giao hàng cần xác nhận.
            </div>
        @else
            <form method="POST" action="{{ route('shipper.confirm-delivery-schedule', ['schedule' => 'bulk']) }}">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate }}">

                <div class="d-flex flex-column gap-2">
                    @foreach($deliveryScheduleOrders as $idx => $order)
                        <input type="hidden" name="order_ids[]" value="{{ $order->id }}">
                        <div class="d-flex align-items-stretch border rounded-3 overflow-hidden bg-white">
                            <div class="time-block p-3 bg-light">
                                <div class="fw-bold text-primary fs-4">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</div>
                                <small class="fw-semibold">{{ $order->delivery_time ?: '--:--' }}</small>
                            </div>
                            <div class="p-3 flex-grow-1">
                                <div class="d-flex justify-content-between gap-2 flex-wrap">
                                    <div class="fw-bold">
                                        <i class="bi bi-person me-1 text-muted"></i>
                                        {{ $order->customer?->name ?? $order->recipient_name ?? 'Khách hàng' }}
                                    </div>
                                    <span class="badge bg-light text-dark border">#{{ $order->code ?: $order->id }}</span>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    {{ $order->recipient_address ?: $order->customer?->address ?: 'Chưa cập nhật địa chỉ' }}
                                </div>
                                <div class="small mt-2">
                                    <span class="badge bg-warning text-dark">{{ $order->items->sum('quantity') }} sp</span>
                                    <span class="badge bg-secondary ms-1">{{ $order->status }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-end gap-2 flex-wrap mt-3">
                    @if(($deliveryScheduleStatus ?? 'none') === 'confirmed')
                        <a href="{{ route('shipper.available') }}" class="btn btn-success">
                            <i class="bi bi-collection me-1"></i>Đi tới đơn có thể nhận
                        </a>
                    @else
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i>Xác nhận lộ trình
                        </button>
                        <button type="submit"
                            class="btn btn-outline-danger px-4"
                            formaction="{{ route('shipper.reject-delivery-schedule', ['schedule' => 'bulk']) }}">
                            <i class="bi bi-x-circle me-1"></i>Từ chối
                        </button>
                    @endif
                </div>

                @if($needsScheduleDecision)
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Các đơn trong lộ trình này sẽ chưa xuất hiện ở “Có thể nhận” cho đến khi bạn xác nhận.
                    </div>
                @endif
            </form>
        @endif
    </div>
</div>

{{-- Quick action buttons --}}
<div class="row g-3">
    <div class="col-md-4">
        <a href="{{ route('shipper.available') }}" class="card text-decoration-none h-100 shadow-sm border-0 border-start border-4 border-info">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-collection-fill fs-2 text-info"></i>
                <div>
                    <div class="fw-semibold">Nhận đơn mới</div>
                    <div class="text-muted small">{{ $stats['available'] }} đơn sẵn sàng</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('shipper.my-orders') }}" class="card text-decoration-none h-100 shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-truck fs-2 text-warning"></i>
                <div>
                    <div class="fw-semibold">Đơn đang giao</div>
                    <div class="text-muted small">{{ $stats['delivering'] }} đơn đang giao</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('shipper.history') }}" class="card text-decoration-none h-100 shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-clock-history fs-2 text-success"></i>
                <div>
                    <div class="fw-semibold">Lịch sử giao hàng</div>
                    <div class="text-muted small">Xem tất cả lịch sử</div>
                </div>
            </div>
        </a>
    </div>
</div>

@endsection
