@extends('layouts.shipper')

@section('title', 'Gán đơn cho ship')
@section('subtitle', 'Quản lý gán đơn hàng đến từng người giao')

@push('styles')
<style>
    .ma-order-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: all 0.15s;
    }
    .ma-order-card:hover {
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.1);
        border-color: var(--theme-primary);
    }
    .ma-order-code {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }
    .ma-customer-info {
        font-size: 0.85rem;
        color: #475569;
        line-height: 1.4;
    }
    .ma-address-badge {
        display: inline-block;
        background: #f0fdfa;
        color: var(--theme-primary);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 4px;
    }
    .ma-shipper-btn {
        font-size: 0.8rem;
        padding: 6px 12px;
        white-space: nowrap;
    }
    .ma-filter-group {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        background: white;
        padding: 1rem;
        border-radius: 0.75rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
    }
    .ma-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .ma-stat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
    }
    .ma-stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--theme-primary);
    }
    .ma-stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 4px;
    }
</style>
@endpush

@section('content')
<div class="ma-filter-group">
    <form method="GET" action="{{ route('shipper.manage-assignments') }}" class="d-flex gap-2 align-items-center flex-grow-1">
        <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm" style="max-width: 150px">
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-search me-1"></i>Lọc
        </button>
        <a href="{{ route('shipper.manage-assignments') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-clockwise me-1"></i>Đặt lại
        </a>
    </form>
    
    <form method="POST" action="{{ route('shipper.create-delivery-schedule') }}" class="d-flex gap-2 align-items-center ms-auto">
        @csrf
        <input type="text" name="notes" class="form-control form-control-sm" maxlength="500" placeholder="Ghi chú (tùy chọn)" style="max-width: 280px">
        <button type="submit" class="btn btn-sm btn-success" title="Gửi lịch trình cho tất cả shipper">
            <i class="bi bi-check-circle me-1"></i>Hoàn thành & Gửi xác nhận
        </button>
    </form>
</div>

<div class="d-flex align-items-center gap-3 mb-3">
    @if(!empty($confirmedShipperIds) && count($confirmedShipperIds) > 0)
        @php
            $confirmedShippers = $shippers->whereIn('id', $confirmedShipperIds)->pluck('name')->implode(', ');
        @endphp
        <div class="alert alert-success alert-sm d-inline-flex align-items-center gap-2 mb-0 py-2 px-3" style="border-radius: 0.5rem;">
            <i class="bi bi-check-circle"></i>
            <span class="fw-semibold" style="font-size: 0.9rem;">Đã duyệt: {{ $confirmedShippers }}</span>
        </div>
    @endif
</div>

<div class="ma-stats">
    <div class="ma-stat-card">
        <div class="ma-stat-value">{{ $totalOrdersCount }}</div>
        <div class="ma-stat-label">Tổng đơn trong luồng</div>
    </div>
    <div class="ma-stat-card">
        <div class="ma-stat-value">{{ $assignedOrdersCount }}</div>
        <div class="ma-stat-label">Đã gán shipper</div>
    </div>
    <div class="ma-stat-card">
        <div class="ma-stat-value">{{ $unassignedOrdersCount }}</div>
        <div class="ma-stat-label">Chưa gán shipper</div>
    </div>
    <div class="ma-stat-card">
        <div class="ma-stat-value">{{ $shippers->count() }}</div>
        <div class="ma-stat-label">Shipper sẵn sàng</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold text-dark">Đơn hàng chưa gán</div>
                        <div class="text-muted small">Đơn sẽ biến mất khỏi đây sau khi gán shipper.</div>
                    </div>
                    <span class="badge bg-primary rounded-pill">{{ $unassignedOrders->total() }}</span>
                </div>

                @if($unassignedOrders->isEmpty())
                    <div class="text-center py-5 border rounded-3 bg-light">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="mt-2 mb-0 text-muted">Không có đơn chưa gán trong ngày này.</p>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach($unassignedOrders as $order)
                            @include('shipper.partials.manage-assignment-order-card', ['order' => $order, 'shippers' => $shippers, 'showAssignmentButtons' => true])
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $unassignedOrders->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="border-0   h-100">
            <div class="body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold text-dark">Shipper và đơn đã gán</div>
                        <div class="text-muted small">Mỗi shipper hiển thị các đơn đang thuộc về họ trong ngày.</div>
                    </div>
                    <span class="badge bg-success rounded-pill">{{ $assignedOrdersCount }}</span>
                </div>

                @if($assignedOrdersCount === 0)
                    <div class="text-center py-5 border rounded-3 bg-light">
                        <i class="bi bi-truck fs-1 text-muted"></i>
                        <p class="mt-2 mb-0 text-muted">Chưa có đơn nào được gán shipper.</p>
                    </div>
                @else
                    <div class="d-grid gap-3">
                        @foreach($assignedOrders as $shipperId => $shipperOrders)
                            @if($shipperOrders->isNotEmpty())
                                @php $shipper = $shippers->firstWhere('id', $shipperId); @endphp
                                <div class="border rounded-3 p-3 bg-white">
                                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $shipper?->name ?? 'Shipper #' . $shipperId }}</div>
                                            <div class="text-muted small">{{ $shipper?->phone ?? $shipper?->email ?? 'Không có liên hệ' }}</div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary rounded-pill" style="white-space: nowrap;">{{ $shipperOrders->count() }}</span>
                                            <form method="POST" action="{{ route('shipper.bulk-transfer-assignments') }}" class="d-flex gap-1" style="width: 220px;">
                                                @csrf
                                                <input type="hidden" name="from_shipper_id" value="{{ $shipperId }}">
                                                <select name="to_shipper_id" class="form-select form-select-sm" required style="flex: 1; font-size: 0.8rem;">
                                                    <option value="">-- Chuyển --</option>
                                                    @foreach($shippers as $s)
                                                        @if($s->id != $shipperId)
                                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-warning px-2" title="Chuyển tất cả {{ $shipperOrders->count() }} đơn">
                                                    <i class="bi bi-arrow-right"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2">
                                        @foreach($shipperOrders as $idx => $order)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="flex-fill">
                                                    @include('shipper.partials.manage-assignment-order-card', ['order' => $order, 'shippers' => $shippers, 'showAssignmentButtons' => false])
                                                </div>
                                                <div class="d-flex flex-column align-items-center" style="min-width:32px;">
                                                    <form action="{{ route('shipper.move-order-up', [$order->id]) }}" method="POST" style="margin-bottom:2px;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light btn-sm px-1 py-0" title="Lên trên" {{ $idx === 0 ? 'disabled' : '' }}><i class="bi bi-arrow-up"></i></button>
                                                    </form>
                                                    <form action="{{ route('shipper.move-order-down', [$order->id]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light btn-sm px-1 py-0" title="Xuống dưới" {{ $idx === $shipperOrders->count()-1 ? 'disabled' : '' }}><i class="bi bi-arrow-down"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
