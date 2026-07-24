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
        min-width: 1120px;
    }
    .route-review-table th {
        background: #0b5f59;
        color: #fff;
        text-align: center;
        vertical-align: middle;
        font-size: .76rem;
        text-transform: uppercase;
        padding: 14px 10px;
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
    .route-review-table .address-cell {
        color: #111827;
    }
    .route-review-table .wrap-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .route-group-row td {
        background: #fff;
        font-weight: 800;
        padding: 10px 6px;
        border-left: 0;
        border-right: 0;
    }
    .order-row {
        background: #fff;
    }
    .review-sequence {
        width: 29px;
        height: 29px;
        border: 1px solid #0f766e;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ff8a1c;
        color: #fff;
        font-weight: 800;
    }
    .review-customer {
        color: #111827;
        font-weight: 800;
    }
    .review-product {
        margin-top: 3px;
        color: #475569;
        font-size: .72rem;
    }
    .route-review-table tfoot td {
        background: #f8fafc;
        font-weight: 800;
        border-top: 1px solid #dbe4ea;
    }
    .review-view-switcher {
        display: inline-flex;
        gap: 4px;
        margin-bottom: 12px;
        padding: 3px;
        border: 1px solid #dbe4ea;
        border-radius: 7px;
        background: #fff;
    }
    .review-view-switcher .btn {
        border: 0;
        border-radius: 5px;
        color: #475569;
        font-size: .76rem;
        font-weight: 700;
    }
    .review-view-switcher .btn.active {
        background: #0b5f59;
        color: #fff;
    }
    .compact-route-table {
        width: 100%;
        min-width: 1160px;
        border-collapse: separate;
        border-spacing: 0 6px;
        font-size: .78rem;
    }
    .compact-route-table th {
        padding: 0 7px 3px;
        border: 0;
        background: transparent;
        color: #111827;
        text-align: left;
        white-space: nowrap;
    }
    .compact-route-table th span {
        display: inline-block;
        padding: 5px 10px;
        background: #0b5f59;
        color: #fff;
        font-size: .72rem;
        text-transform: uppercase;
    }
    .compact-route-table td {
        padding: 3px 7px;
        border: 0;
        color: #111827;
        vertical-align: middle;
    }
    .compact-route-table .compact-strong { font-weight: 800; }
    .compact-route-table .compact-product { color: #475569; font-size: .7rem; white-space: nowrap; }
    .compact-route-table .compact-arrow { width: 36px; padding-inline: 2px; text-align: center; font-weight: 800; }
    .compact-route-table .compact-money { text-align: right; white-space: nowrap; font-weight: 900; }
    .compact-route-table tfoot td { padding-top: 9px; border-top: 1px solid #dbe4ea; font-weight: 800; }
    .route-send-status { display: none; margin-top: 10px; padding: 8px 12px; border-radius: 6px; font-size: .8rem; font-weight: 600; }
    .route-send-status.is-visible { display: block; }
    .route-send-status.is-success { background: #dcfce7; color: #166534; }
    .route-send-status.is-error { background: #fee2e2; color: #991b1b; }
    @media print {
        .sp-sidebar,
        .sp-topbar,
        .review-actions,
        .review-view-switcher {
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
    $combinedTotal = collect($routePlan)->sum(fn ($shipper) => collect($shipper['routes'] ?? [])->sum(function ($route) {
        $orders = collect($route['orders'] ?? []);
        return (float) ($route['combined_fee'] ?? $orders->sum('final_fee'));
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

<div class="review-view-switcher review-actions" role="group" aria-label="Kiểu hiển thị lộ trình">
    <button type="button" class="btn active" data-review-view-switch="grouped" aria-pressed="true">
        <i class="bi bi-diagram-3 me-1"></i>Theo lộ trình
    </button>
    <button type="button" class="btn" data-review-view-switch="compact" aria-pressed="false">
        <i class="bi bi-list-ul me-1"></i>Danh sách gọn
    </button>
</div>

<div class="card border-0 shadow-sm" data-review-view-panel="grouped">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered route-review-table mb-0">
                <colgroup>
                    <col style="width: 50px;">
                    <col style="width: 125px;">
                    <col style="width: 190px;">
                    <col style="width: 100px;">
                    <col style="width: 21%;">
                    <col style="width: 26%;">
                    <col style="width: 115px;">
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Giờ giao</th>
                        <th>Khách hàng</th>
                        <th>Số lượng</th>
                        <th>Điểm đi</th>
                        <th>Điểm đến</th>
                        <th>Số tiền</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowIndex = 1; @endphp
                    @foreach($routePlan as $shipperPlan)
                        @foreach(($shipperPlan['routes'] ?? []) as $route)
                            @php
                                $routeOrders = collect($route['orders'] ?? []);
                                $shipperName = (string) ($shipperPlan['shipper_name'] ?? 'Shipper');
                                $routeLabel = (string) ($route['name'] ?? 'Lộ trình');
                                $shipperSuffix = ' - ' . $shipperName;
                                if (str_ends_with($routeLabel, $shipperSuffix)) {
                                    $routeLabel = substr($routeLabel, 0, -strlen($shipperSuffix));
                                }
                            @endphp
                            <tr class="route-group-row">
                                <td colspan="8">+ {{ $shipperName }} &nbsp;–&nbsp; {{ $routeLabel }}</td>
                            </tr>
                            @foreach($routeOrders as $order)
                                <tr class="order-row">
                                    <td class="center-cell"><span class="review-sequence">{{ $order['sequence'] ?? $rowIndex }}</span></td>
                                    <td class="fw-bold">{{ $order['delivery_time'] ?? 'Chưa hẹn giờ' }}</td>
                                    <td>
                                        <div class="review-customer">{{ $order['customer_name'] ?? '' }}</div>
                                        @if(!empty($order['product_summary']))
                                            <div class="review-product">{{ $order['product_summary'] }}</div>
                                        @endif
                                    </td>
                                    <td class="center-cell">{{ number_format((float) ($order['quantity'] ?? 0), 0, ',', '.') }}</td>
                                    <td>{{ $order['origin'] ?? '' }}</td>
                                    <td class="address-cell"><div class="wrap-2">{{ $order['destination'] ?? '' }}</div></td>
                                    <td class="money-cell">{{ number_format((float) ($order['final_fee'] ?? 0), 0, ',', '.') }} đ</td>
                                    <td>{{ $order['note'] ?? '' }}</td>
                                </tr>
                                @php $rowIndex++; @endphp
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end text-muted">Tổng phí</td>
                        <td class="money-cell">{{ number_format($combinedTotal, 0, ',', '.') }} đ</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm d-none" data-review-view-panel="compact">
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="compact-route-table mb-0">
                <colgroup>
                    <col style="width: 48px;">
                    <col style="width: 128px;">
                    <col style="width: 110px;">
                    <col style="width: 110px;">
                    <col style="width: 185px;">
                    <col style="width: 120px;">
                    <col style="width: 36px;">
                    <col>
                    <col style="width: 105px;">
                    <col style="width: 120px;">
                </colgroup>
                <thead>
                    <tr>
                        <th><span>STT</span></th>
                        <th><span>Giờ giao</span></th>
                        <th>Shipper</th>
                        <th><span>Khách hàng</span></th>
                        <th><span>Số lượng</span></th>
                        <th><span>Điểm đi</span></th>
                        <th></th>
                        <th><span>Điểm đến</span></th>
                        <th><span>Số tiền</span></th>
                        <th><span>Ghi chú</span></th>
                    </tr>
                </thead>
                <tbody>
                    @php $compactRowIndex = 1; @endphp
                    @foreach($routePlan as $shipperPlan)
                        @foreach(($shipperPlan['routes'] ?? []) as $route)
                            @foreach(($route['orders'] ?? []) as $order)
                                <tr>
                                    <td class="center-cell"><span class="review-sequence">{{ $order['sequence'] ?? $compactRowIndex }}</span></td>
                                    <td class="compact-strong">{{ $order['delivery_time'] ?? 'Chưa hẹn giờ' }}</td>
                                    <td class="compact-strong">{{ $shipperPlan['shipper_name'] ?? 'Shipper' }}</td>
                                    <td class="compact-strong">{{ $order['customer_name'] ?? '' }}</td>
                                    <td class="compact-product">{{ $order['product_summary'] ?? number_format((float) ($order['quantity'] ?? 0), 0, ',', '.') }}</td>
                                    <td>{{ $order['origin'] ?? '' }}</td>
                                    <td class="compact-arrow">---&gt;</td>
                                    <td>{{ $order['destination'] ?? '' }}</td>
                                    <td class="compact-money">{{ number_format((float) ($order['final_fee'] ?? 0), 0, ',', '.') }} đ</td>
                                    <td>{{ $order['note'] ?? '' }}</td>
                                </tr>
                                @php $compactRowIndex++; @endphp
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="8" class="text-end text-muted">Tổng phí</td>
                        <td class="compact-money">{{ number_format($combinedTotal, 0, ',', '.') }} đ</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const switchButton = event.target.closest('[data-review-view-switch]');
    if (!switchButton) return;

    const selectedView = switchButton.dataset.reviewViewSwitch;
    document.querySelectorAll('[data-review-view-switch]').forEach(function (button) {
        const isActive = button.dataset.reviewViewSwitch === selectedView;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
    document.querySelectorAll('[data-review-view-panel]').forEach(function (panel) {
        panel.classList.toggle('d-none', panel.dataset.reviewViewPanel !== selectedView);
    });
});

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
