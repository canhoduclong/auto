@extends('layouts.shipper')

@section('title', 'Xem lại lộ trình giao hàng')
@section('subtitle', 'Kiểm tra các chuyến ghép đơn trước khi gửi shipper xác nhận')

@push('styles')
<style>
    .review-toolbar {
        background: #fff;
        border: 1px solid #dbe4ea;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 14px;
    }
    .route-review-table {
        font-size: .82rem;
        table-layout: fixed;
    }
    .route-review-table th {
        background: #0b5f59;
        color: #fff;
        text-align: center;
        vertical-align: middle;
        font-size: .72rem;
        text-transform: uppercase;
    }
    .route-review-table td {
        vertical-align: middle;
        overflow-wrap: anywhere;
        padding: 9px 10px;
        line-height: 1.35;
    }
    .route-review-table .money-cell {
        white-space: nowrap;
        text-align: right;
        font-weight: 700;
    }
    .route-review-table .center-cell {
        text-align: center;
        white-space: nowrap;
    }
    .route-review-table .muted-cell {
        color: #475569;
    }
    .route-review-table .address-cell {
        color: #111827;
    }
    .route-review-table .wrap-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .shipper-row {
        background: #e6fffb;
        font-weight: 800;
    }
    .trip-row {
        background: #fff8df;
        font-weight: 700;
    }
    .order-row {
        background: #f8fbfd;
    }
    .order-indent {
        padding-left: 24px !important;
    }
    .saving-positive {
        color: #15803d;
        font-weight: 700;
    }
    .saving-negative {
        color: #dc2626;
        font-weight: 700;
    }
    .route-send-status { display: none; margin-top: 10px; padding: 8px 12px; border-radius: 6px; font-size: .8rem; font-weight: 600; }
    .route-send-status.is-visible { display: block; }
    .route-send-status.is-success { background: #dcfce7; color: #166534; }
    .route-send-status.is-error { background: #fee2e2; color: #991b1b; }
    @media print {
        .sp-sidebar,
        .sp-topbar,
        .review-actions {
            display: none !important;
        }
        .sp-main {
            margin-left: 0 !important;
        }
        .sp-content {
            padding: 0 !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $tripCount = collect($routePlan)->sum(fn ($shipper) => count($shipper['routes'] ?? []));
    $orderCount = collect($routePlan)->sum(fn ($shipper) => collect($shipper['routes'] ?? [])->sum(fn ($route) => count($route['orders'] ?? [])));
    $originalTotal = collect($routePlan)->sum(fn ($shipper) => collect($shipper['routes'] ?? [])->sum(function ($route) {
        $orders = collect($route['orders'] ?? []);
        return (float) ($route['original_total'] ?? $orders->sum('base_fee'));
    }));
    $combinedTotal = collect($routePlan)->sum(fn ($shipper) => collect($shipper['routes'] ?? [])->sum(function ($route) {
        $orders = collect($route['orders'] ?? []);
        return (float) ($route['combined_fee'] ?? $orders->sum('final_fee'));
    }));
    $extraTotal = collect($routePlan)->sum(fn ($shipper) => collect($shipper['routes'] ?? [])->sum(function ($route) {
        $orders = collect($route['orders'] ?? []);
        return (float) ($route['additional_total'] ?? $orders->sum('extra_fee'));
    }));
@endphp

<div class="review-toolbar d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <div class="fw-bold text-dark">Bảng kê chi phí ship cho từng chuyến</div>
        <div class="text-muted small">Ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }} · {{ $tripCount }} chuyến · {{ $orderCount }} đơn</div>
        @if($notes)
            <div class="small mt-1"><span class="text-muted">Ghi chú:</span> {{ $notes }}</div>
        @endif
    </div>
    <div class="review-actions d-flex flex-wrap gap-2">
        <a href="{{ route('shipper.manage-assignments', ['date' => $selectedDate]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Quay lại chỉnh
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>In
        </button>
        <form method="POST" action="{{ route('shipper.create-delivery-schedule') }}" class="d-inline" data-route-send-form data-success-url="{{ route('shipper.manage-assignments', ['date' => $selectedDate]) }}">
            @csrf
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <input type="hidden" name="notes" value="{{ $notes }}">
            <input type="hidden" name="route_plan" value="{{ $routePlanJson }}">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-send-check me-1"></i>Gửi lộ trình cho shipper
            </button>
        </form>
    </div>
    <div class="route-send-status w-100" data-route-send-status role="status" aria-live="polite"></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body py-3">
            <div class="text-muted small">Tổng phí riêng lẻ</div>
            <div class="fs-5 fw-bold">{{ number_format($originalTotal, 0, ',', '.') }} đ</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body py-3">
            <div class="text-muted small">Phí sau ghép chuyến</div>
            <div class="fs-5 fw-bold text-primary">{{ number_format($combinedTotal, 0, ',', '.') }} đ</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body py-3">
            <div class="text-muted small">Điều chỉnh theo đơn</div>
            <div class="fs-5 fw-bold text-warning">{{ number_format($extraTotal, 0, ',', '.') }} đ</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm"><div class="card-body py-3">
            <div class="text-muted small">Chênh lệch ghép chuyến</div>
            @php $savingTotal = $originalTotal - $combinedTotal; @endphp
            <div class="fs-5 fw-bold {{ $savingTotal >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($savingTotal, 0, ',', '.') }} đ</div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered route-review-table mb-0">
                <colgroup>
                    <col style="width: 42px;">
                    <col style="width: 15%;">
                    <col style="width: 9%;">
                    <col style="width: 68px;">
                    <col style="width: 9%;">
                    <col style="width: 34%;">
                    <col style="width: 10%;">
                    <col style="width: 9%;">
                    <col style="width: 9%;">
                    <col style="width: 9%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Lộ trình / Khách hàng</th>
                        <th>Nhân viên ship</th>
                        <th>Số lượng</th>
                        <th>Điểm đi / Phí riêng lẻ</th>
                        <th>Điểm đến / Km</th>
                        <th>Tên đơn ghép cùng</th>
                        <th>Số tiền cho đơn đó</th>
                        <th>Tiết kiệm / Điều chỉnh</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowIndex = 1; @endphp
                    @foreach($routePlan as $shipperPlan)
                        <tr class="shipper-row">
                            <td colspan="10">+ {{ $shipperPlan['shipper_name'] ?? 'Shipper' }}</td>
                        </tr>
                        @foreach(($shipperPlan['routes'] ?? []) as $route)
                            @php
                                $routeOrders = collect($route['orders'] ?? []);
                                $routeOriginal = (float) ($route['original_total'] ?? $routeOrders->sum('base_fee'));
                                $routeCombined = (float) ($route['combined_fee'] ?? $routeOrders->sum('final_fee'));
                                $saving = $routeOriginal - $routeCombined;
                            @endphp
                            <tr class="trip-row">
                                <td class="center-cell"></td>
                                <td>− {{ $route['name'] ?? 'Chuyến' }}</td>
                                <td>{{ $shipperPlan['shipper_name'] ?? '-' }}</td>
                                <td class="center-cell">{{ number_format($routeOrders->sum('quantity'), 0, ',', '.') }}</td>
                                <td class="money-cell">{{ number_format($routeOriginal, 0, ',', '.') }} đ</td>
                                <td class="center-cell">{{ !empty($route['km']) ? $route['km'] . ' km' : '' }}</td>
                                <td>{{ $route['note'] ?? '' }}</td>
                                <td class="money-cell">{{ $routeCombined > 0 ? number_format($routeCombined, 0, ',', '.') . ' đ' : '' }}</td>
                                <td class="money-cell {{ $saving >= 0 ? 'saving-positive' : 'saving-negative' }}">{{ $routeCombined > 0 ? number_format($saving, 0, ',', '.') . ' đ' : '' }}</td>
                                <td></td>
                            </tr>
                            @foreach($routeOrders as $order)
                                <tr class="order-row">
                                    <td class="center-cell">{{ $rowIndex++ }}</td>
                                    <td class="order-indent fw-semibold">{{ $order['customer_name'] ?? '' }}</td>
                                    <td>{{ $shipperPlan['shipper_name'] ?? '-' }}</td>
                                    <td class="center-cell">{{ number_format((float) ($order['quantity'] ?? 0), 0, ',', '.') }}</td>
                                    <td class="muted-cell">{{ $order['origin'] ?? '' }}</td>
                                    <td class="address-cell"><div class="wrap-2">{{ $order['destination'] ?? '' }}</div></td>
                                    <td><div class="wrap-2">{{ $route['name'] ?? '' }}</div></td>
                                    <td class="money-cell">{{ number_format((float) ($order['final_fee'] ?? 0), 0, ',', '.') }} đ</td>
                                    <td class="money-cell">
                                        @if(!empty($order['extra_fee']))
                                            {{ (float) $order['extra_fee'] > 0 ? '+' : '' }}{{ number_format((float) $order['extra_fee'], 0, ',', '.') }} đ
                                        @endif
                                    </td>
                                    <td>{{ $order['note'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('submit', async function (event) {
    const form = event.target.closest('[data-route-send-form]');
    if (!form) return;

    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const status = document.querySelector('[data-route-send-status]');
    const showStatus = (message, type) => {
        status.textContent = message;
        status.className = `route-send-status w-100 is-visible is-${type}`;
    };

    if (button) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Đang gửi...';
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (!response.ok) {
            const validationMessage = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : data.message;
            throw new Error(validationMessage || 'Không thể gửi lộ trình cho shipper.');
        }

        showStatus(data.message || 'Đã gửi lộ trình cho shipper.', 'success');
        window.setTimeout(() => window.location.assign(form.dataset.successUrl), 700);
    } catch (error) {
        showStatus(error.message || 'Không thể kết nối máy chủ.', 'error');
        if (button) {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText;
        }
    }
});
</script>
@endpush
