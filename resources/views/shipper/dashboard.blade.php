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

<div class="row my-4">
        <div class="col-md-6 col-lg-6 col-xl-6">   
            <div class="mb-4 px-2"> 
                <p class="text-muted mb-0">...</p>
            </div>          
            <div class="d-flex flex-column gap-3">
            
                <div class="card order-card bg-white">
                    <div class="d-flex">
                        <div class="time-block p-3" style="background-color: #e6f4ea;">
                            <h1 class="fw-bold mb-0 text-dark" style="font-size: 2.5rem;">01</h1>
                            <small class="fw-bold text-dark mt-1">2:15 PM</small>
                        </div>
                        <div class="p-3 flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold fs-6"><i class="bi bi-person me-2 text-muted"></i>Chị Mai</span>
                                <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border">
                                    <i class="bi bi-clock me-1"></i>Đang chờ
                                </span>
                            </div>
                            <div class="text-muted" style="font-size: 0.9rem;">
                                <i class="bi bi-geo-alt me-2"></i>45 Lê Văn Quới, Bình Tân
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card order-card bg-white">
                    <div class="d-flex">
                        <div class="time-block p-3" style="background-color: #e8f0fe;">
                            <h1 class="fw-bold mb-0 text-dark" style="font-size: 2.5rem;">02</h1>
                            <small class="fw-bold text-dark mt-1">2:45 PM</small>
                        </div>
                        <div class="p-3 flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold fs-6"><i class="bi bi-person me-2 text-muted"></i>Nguyễn Kiệtss</span>
                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border-0">
                                    <i class="bi bi-clock me-1"></i>Đang chờ
                                </span>
                            </div>
                            <div class="text-muted" style="font-size: 0.9rem;">
                                <i class="bi bi-geo-alt me-2"></i>117 Nguyễn Thị Tú, Bình Hưng Hòa, Bình Tân
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card order-card bg-white">
                    <div class="d-flex">
                        <div class="time-block p-3" style="background-color: #fff3e0;">
                            <h1 class="fw-bold mb-0 text-dark" style="font-size: 2.5rem;">03</h1>
                            <small class="fw-bold text-dark mt-1">3:30 PM</small>
                        </div>
                        <div class="p-3 flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold fs-6"><i class="bi bi-person me-2 text-muted"></i>Anh Hùng</span>
                                <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border-0 text-dark">
                                    <i class="bi bi-clock me-1"></i>Đang chờ
                                </span>
                            </div>
                            <div class="text-muted" style="font-size: 0.9rem;">
                                <i class="bi bi-geo-alt me-2"></i>Số 3 Đường 22, Bình Hưng Hòa B, Bình Tân
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-6 col-xl-6">
             <div class="mb-4 px-2"> 
                <p class="text-muted mb-0">----</p>
            </div> 
            <div class="order-list">
                <div class="d-flex flex-column gap-3">
                     <div class="card order-card bg-white">
                        <div class="d-flex">
                            <div class="time-block p-3" style="background-color: #fff3e0;">
                                <h1 class="fw-bold mb-0 text-dark" style="font-size: 2.5rem;">03</h1>
                                <small class="fw-bold text-dark mt-1">3:30 PM</small>
                            </div>
                            <div class="p-3 flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold fs-6"><i class="bi bi-person me-2 text-muted"></i>Anh Hùng</span>
                                    <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border-0 text-dark">
                                        <i class="bi bi-clock me-1"></i>Đang chờ
                                    </span>
                                </div>
                                <div class="text-muted" style="font-size: 0.9rem;">
                                    <i class="bi bi-geo-alt me-2"></i>Số 3 Đường 22, Bình Hưng Hòa B, Bình Tân
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div> 

@endsection
