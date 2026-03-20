@extends('layouts.warehouse')

@section('title', 'Dashboard Kho hàng')
@section('subtitle', 'Tổng quan hoạt động hôm nay')

@section('content')
{{-- KPI Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-inbox-fill fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Chờ đóng gói</div>
                    <div class="fs-3 fw-bold text-primary">{{ $stats['ready_to_pack'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Đang đóng gói</div>
                    <div class="fs-3 fw-bold text-warning">{{ $stats['packing'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Đóng xong hôm nay</div>
                    <div class="fs-3 fw-bold text-success">{{ $stats['packed_today'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-arrow-return-left fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Đơn trả về</div>
                    <div class="fs-3 fw-bold text-danger">{{ $stats['returning'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick actions row --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-boxes text-primary"></i> Xử lý đóng gói nhanh
            </div>
            <div class="card-body d-flex gap-3 flex-wrap">
                <a href="{{ route('warehouse.orders') }}" class="btn btn-primary">
                    <i class="bi bi-box2-fill me-1"></i>Xem đơn cần đóng gói
                    @if($stats['ready_to_pack'] + $stats['packing'] > 0)
                        <span class="badge bg-light text-primary ms-1">{{ $stats['ready_to_pack'] + $stats['packing'] }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-arrow-return-left text-danger"></i> Hàng trả về
            </div>
            <div class="card-body d-flex gap-3 flex-wrap">
                <a href="{{ route('warehouse.returns') }}" class="btn btn-outline-danger">
                    <i class="bi bi-clipboard-check me-1"></i>Xác nhận nhập kho hàng trả
                    @if($stats['returning'] > 0)
                        <span class="badge bg-danger ms-1">{{ $stats['returning'] }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Recent packed orders --}}
@if($recentPacked->isNotEmpty())
<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history me-1 text-secondary"></i> Đơn đóng xong gần đây hôm nay
    </div>
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Tổng tiền</th>
                <th>Cập nhật lúc</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentPacked as $i => $order)
            <tr>
                <td class="text-muted">{{ $i + 1 }}</td>
                <td class="fw-semibold">{{ $order->code }}</td>
                <td>{{ $order->customer?->name ?? '—' }}</td>
                <td>{{ number_format($order->total) }}đ</td>
                <td class="text-muted small">{{ $order->updated_at->format('H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
