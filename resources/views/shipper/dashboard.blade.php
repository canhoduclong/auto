@extends('layouts.shipper')

@section('title', 'Dashboard Shipper')
@section('subtitle', 'Thống kê hôm nay')

@section('content')
{{-- KPI Cards --}}
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
