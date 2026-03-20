@extends('layouts.warehouse')

@section('title', 'Đơn hàng cần xử lý')
@section('subtitle', 'Tất cả đơn hàng trong ngày')

@push('styles')
<style>
    .wh-orders-shell {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .wh-summary-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        background: #ffffff;
        padding: 14px;
    }
    .wh-summary-pill {
        border-radius: 999px;
        padding: 8px 12px;
        font-size: .82rem;
        font-weight: 700;
    }
    .wh-orders-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .wh-orders-table {
        margin-bottom: 0;
        min-width: 980px;
    }
    .wh-orders-table thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        white-space: nowrap;
    }
    .wh-orders-table tbody td {
        vertical-align: middle;
    }
    .wh-timeline {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: .72rem;
        flex-wrap: wrap;
    }
    .wh-mobile-list {
        display: none;
        padding: 14px;
        background: #f8fafc;
        gap: 12px;
    }
    .wh-mobile-item {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        padding: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }
    .wh-mobile-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 10px;
    }
    .wh-mobile-code {
        font-weight: 800;
        font-size: .95rem;
        color: #0f172a;
    }
    .wh-mobile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 10px;
    }
    .wh-mobile-label {
        display: block;
        font-size: .72rem;
        color: #64748b;
        margin-bottom: 2px;
    }
    .wh-mobile-value {
        font-size: .86rem;
        font-weight: 700;
        color: #1e293b;
    }
    .wh-mobile-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .wh-mobile-actions .btn {
        flex: 1 1 auto;
        min-width: 130px;
    }
    @media (max-width: 991.98px) {
        .wh-summary-top {
            flex-direction: column;
            align-items: stretch !important;
            gap: 10px;
        }
    }
    @media (max-width: 767.98px) {
        .wh-desktop-table {
            display: none;
        }
        .wh-mobile-list {
            display: grid;
        }
        .wh-mobile-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@php
    $statusMeta = [
        'approved' => ['label' => 'Chờ đóng gói', 'class' => 'bg-primary'],
        'ready_to_pack' => ['label' => 'Chờ đóng gói', 'class' => 'bg-primary'],
        'packing' => ['label' => 'Đang đóng gói', 'class' => 'bg-warning text-dark'],
        'packed' => ['label' => 'Đã đóng gói', 'class' => 'bg-info text-dark'],
        'packed_waiting_pickup' => ['label' => 'Chờ shipper nhận', 'class' => 'bg-info text-dark'],
        'delivering' => ['label' => 'Đang giao', 'class' => 'bg-secondary'],
        'delivered' => ['label' => 'Đã giao', 'class' => 'bg-success'],
        'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-success'],
        'pending' => ['label' => 'Chờ duyệt', 'class' => 'bg-light text-dark'],
        'pending_leader_approval' => ['label' => 'Chờ trưởng nhóm duyệt', 'class' => 'bg-light text-dark'],
        'pending_manager_approval' => ['label' => 'Chờ quản lý duyệt', 'class' => 'bg-light text-dark'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'bg-danger'],
    ];
@endphp
<div class="wh-orders-shell">
    <div class="wh-summary-card">
        <div class="d-flex justify-content-between align-items-center wh-summary-top">
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-dark wh-summary-pill">
                    Tổng đơn hôm nay: {{ $orders->count() }}
                </span>
                <span class="badge bg-primary wh-summary-pill">
                    Chờ đóng gói: {{ $orders->whereIn('status', ['approved', 'ready_to_pack'])->count() }}
                </span>
                <span class="badge bg-warning text-dark wh-summary-pill">
                    Đang đóng: {{ $orders->where('status', 'packing')->count() }}
                </span>
            </div>
            <a href="{{ route('warehouse.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    @if($orders->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <i class="bi bi-check2-all fs-1 text-success"></i>
            <p class="mt-2 text-muted">Không có đơn nào cần xử lý lúc này.</p>
        </div>
    @else
    <div class="wh-orders-card">
        <div class="table-responsive wh-desktop-table">
            <table class="table table-hover align-middle wh-orders-table">
                <thead class="table-light sticky-top">
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Sản phẩm</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Timeline</th>
                        <th>Tạo lúc</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $i => $order)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $order->code }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $order->customer?->name ?? '—' }}</div>
                            @if($order->customer?->phone)
                                <div class="text-muted" style="font-size:.75rem;">{{ $order->customer->phone }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="small">
                                {{ $order->items->sum('quantity') }} sản phẩm
                            </div>
                            <div class="text-muted" style="font-size:.72rem;">
                                {{ $order->items->take(2)->map(function ($item) {
                                    return $item->variant?->name ?? $item->product?->name ?? 'SP';
                                })->implode(', ') }}
                                @if($order->items->count() > 2)
                                    <span>... +{{ $order->items->count() - 2 }} khác</span>
                                @endif
                            </div>
                        </td>
                        <td class="fw-semibold">{{ number_format($order->total) }}đ</td>
                        <td>
                            @php $meta = $statusMeta[$order->status] ?? ['label' => $order->status, 'class' => 'bg-secondary']; @endphp
                            <span class="badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                        </td>
                        <td>
                            <div class="wh-timeline">
                                @php
                                    $steps = [
                                        ['label'=>'Tạo','done'=>true],
                                        ['label'=>'Gói','done'=>in_array($order->status,['packing','packed','packed_waiting_pickup','delivering','delivered','completed'])],
                                        ['label'=>'Xong','done'=>in_array($order->status,['packed','packed_waiting_pickup','delivering','delivered','completed'])],
                                        ['label'=>'Giao','done'=>in_array($order->status,['delivering','delivered','completed'])],
                                        ['label'=>'Hoàn','done'=>in_array($order->status,['delivered','completed'])],
                                    ];
                                @endphp
                                @foreach($steps as $idx => $step)
                                    @if($idx > 0)
                                        <span class="text-muted">›</span>
                                    @endif
                                    <span class="{{ $step['done'] ? 'text-success fw-semibold' : 'text-muted' }}">
                                        {{ $step['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-muted small">{{ $order->created_at->format('d/m H:i') }}</td>
                        <td class="text-end">
                            @if(in_array($order->status, ['approved', 'ready_to_pack'], true))
                                <form action="{{ route('warehouse.orders.start-packing', $order) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-primary btn-sm">
                                        <i class="bi bi-box2 me-1"></i>Đóng hàng
                                    </button>
                                </form>
                            @elseif($order->status === 'packing')
                                <form action="{{ route('warehouse.orders.complete-packing', $order) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-success btn-sm">
                                        <i class="bi bi-check2-all me-1"></i>Hoàn thành đóng gói
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-secondary">Đã xử lý</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="wh-mobile-list">
            @foreach($orders as $order)
                @php
                    $steps = [
                        ['label'=>'Tạo','done'=>true],
                        ['label'=>'Gói','done'=>in_array($order->status,['packing','packed','packed_waiting_pickup','delivering','delivered','completed'])],
                        ['label'=>'Xong','done'=>in_array($order->status,['packed','packed_waiting_pickup','delivering','delivered','completed'])],
                        ['label'=>'Giao','done'=>in_array($order->status,['delivering','delivered','completed'])],
                        ['label'=>'Hoàn','done'=>in_array($order->status,['delivered','completed'])],
                    ];
                @endphp
                <div class="wh-mobile-item">
                    <div class="wh-mobile-head">
                        <div>
                            <div class="wh-mobile-code">{{ $order->code }}</div>
                            <small class="text-muted">{{ $order->created_at->format('d/m H:i') }}</small>
                        </div>
                        @php $meta = $statusMeta[$order->status] ?? ['label' => $order->status, 'class' => 'bg-secondary']; @endphp
                        <span class="badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                    </div>

                    <div class="wh-mobile-grid">
                        <div>
                            <span class="wh-mobile-label">Khách hàng</span>
                            <div class="wh-mobile-value">{{ $order->customer?->name ?? '—' }}</div>
                            @if($order->customer?->phone)
                                <small class="text-muted">{{ $order->customer->phone }}</small>
                            @endif
                        </div>
                        <div>
                            <span class="wh-mobile-label">Tổng tiền</span>
                            <div class="wh-mobile-value">{{ number_format($order->total) }}đ</div>
                        </div>
                        <div>
                            <span class="wh-mobile-label">Số sản phẩm</span>
                            <div class="wh-mobile-value">{{ $order->items->sum('quantity') }} sản phẩm</div>
                        </div>
                        <div>
                            <span class="wh-mobile-label">Timeline</span>
                            <div class="wh-timeline">
                                @foreach($steps as $idx => $step)
                                    @if($idx > 0)
                                        <span class="text-muted">›</span>
                                    @endif
                                    <span class="{{ $step['done'] ? 'text-success fw-semibold' : 'text-muted' }}">
                                        {{ $step['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="wh-mobile-actions mt-2">
                        @if(in_array($order->status, ['approved', 'ready_to_pack'], true))
                            <form action="{{ route('warehouse.orders.start-packing', $order) }}" method="POST" class="w-100">
                                @csrf
                                <button class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-box2 me-1"></i>Đóng hàng
                                </button>
                            </form>
                        @elseif($order->status === 'packing')
                            <form action="{{ route('warehouse.orders.complete-packing', $order) }}" method="POST" class="w-100">
                                @csrf
                                <button class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-check2-all me-1"></i>Hoàn thành đóng gói
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
