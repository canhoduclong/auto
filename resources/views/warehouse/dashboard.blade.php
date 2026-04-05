@extends('layouts.warehouse')

@section('title', 'Dashboard Kho hàng')
@section('subtitle_clock', '1')

@push('styles')
<style>
    .wh-filter-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .07);
    }
    .wh-stat-soft {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .07);
    }
    .wh-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        display: inline-block;
    }
    .wh-status-chip {
        font-size: .75rem;
        font-weight: 700;
        border-radius: 999px;
        padding: 5px 10px;
        display: inline-block;
    }
    .wh-table-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .07);
        overflow: hidden;
    }
    .wh-table-card table th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
</style>
@endpush

@section('content')
@php
    $statusMap = [
        'pending_leader_approval' => ['label' => 'Chờ leader duyệt', 'class' => 'bg-warning text-dark'],
        'pending_manager_approval' => ['label' => 'Chờ manager duyệt', 'class' => 'bg-warning text-dark'],
        'pending_warehouse_approval' => ['label' => 'Chờ kho duyệt', 'class' => 'bg-warning text-dark'],
        'approved' => ['label' => 'Đã duyệt', 'class' => 'bg-primary'],
        'ready_to_pack' => ['label' => 'Chờ đóng gói', 'class' => 'bg-primary'],
        'packing' => ['label' => 'Đang đóng gói', 'class' => 'bg-info text-dark'],
        'packed' => ['label' => 'Đã đóng gói', 'class' => 'bg-success'],
        'packed_waiting_pickup' => ['label' => 'Chờ shipper nhận', 'class' => 'bg-success'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'bg-danger'],
    ];
@endphp

<div class="card wh-filter-card mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('warehouse.dashboard') }}">
            <div class="col-md-4">
                <label class="form-label fw-semibold mb-1">Chọn ngày thống kê</label>
                <input type="date" name="date" class="form-control" value="{{ $selectedDate->toDateString() }}">
            </div>
            <div class="col-md-8 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Lọc dữ liệu
                </button>
                <a href="{{ route('warehouse.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-1"></i>Hôm nay
                </a>
                <a href="{{ route('warehouse.orders', ['date' => $selectedDate->toDateString()]) }}" class="btn btn-outline-primary">
                    <i class="bi bi-box2-fill me-1"></i>Xem tất cả đơn theo ngày
                </a>
            </div>
        </form>
        <div class="small text-muted mt-2">
            Dữ liệu đang hiển thị cho ngày <strong>{{ $selectedDate->format('d/m/Y') }}</strong>
        </div>
    </div>
</div>

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
                    <div class="text-muted small">Đóng xong theo ngày</div>
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

{{-- Daily approval stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card wh-stat-soft h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Đơn trong ngày</div>
                    <div class="fs-4 fw-bold">{{ $stats['orders_in_day'] }}</div>
                </div>
                <i class="bi bi-calendar2-week fs-3 text-primary"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card wh-stat-soft h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Đang chờ duyệt</div>
                    <div class="fs-4 fw-bold text-warning">{{ $approvalStats['pending_approval'] }}</div>
                </div>
                <i class="bi bi-hourglass-split fs-3 text-warning"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="card wh-stat-soft h-100">
            <div class="card-body text-center">
                <div class="text-muted small">Duyệt</div>
                <div class="fs-4 fw-bold text-success">{{ $approvalStats['approved'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="card wh-stat-soft h-100">
            <div class="card-body text-center">
                <div class="text-muted small">Từ chối</div>
                <div class="fs-4 fw-bold text-danger">{{ $approvalStats['rejected'] }}</div>
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
                <a href="{{ route('warehouse.orders', ['date' => $selectedDate->toDateString()]) }}" class="btn btn-primary">
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

{{-- Orders by selected day --}}
<div class="card wh-table-card mb-4">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-1 text-primary"></i> Đơn hàng trong ngày {{ $selectedDate->format('d/m/Y') }}</span>
        <a href="{{ route('warehouse.orders', ['date' => $selectedDate->toDateString()]) }}" class="btn btn-sm btn-outline-primary">
            Xử lý đóng gói
        </a>
    </div>
    @if($dailyOrders->isNotEmpty())
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Tạo lúc</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailyOrders as $i => $order)
            @php
                $status = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'bg-secondary'];
            @endphp
            <tr>
                <td class="text-muted">{{ $i + 1 }}</td>
                <td class="fw-semibold">{{ $order->code }}</td>
                <td>{{ $order->customer?->name ?? 'Khach le' }}</td>
                <td>{{ number_format($order->total) }}d</td>
                <td>
                    <span class="wh-status-chip {{ $status['class'] }}">{{ $status['label'] }}</span>
                </td>
                <td class="text-muted small">{{ $order->created_at->format('d/m H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="card-body text-center text-muted py-4">
        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
        Không có đơn hàng trong ngày đã chọn.
    </div>
    @endif
</div>

{{-- Recent packed orders --}}
@if($recentPacked->isNotEmpty())
<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history me-1 text-secondary"></i> Đơn đóng xong gần đây theo ngày đã chọn
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
