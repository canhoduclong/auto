@extends('layouts.accounting')

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
    .ma-shipper-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        margin-top: .35rem;
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
    .ma-preview-table {
        font-size: .82rem;
        table-layout: fixed;
    }
    .ma-preview-table th {
        text-align: center;
        vertical-align: middle;
        background: #f8fafc;
        color: #334155;
        font-size: .72rem;
        text-transform: uppercase;
    }
    .ma-preview-table td {
        vertical-align: middle;
        overflow-wrap: anywhere;
    }
    .ma-preview-title {
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: .03em;
        color: #334155;
        text-transform: uppercase;
    }
    .route-planner {
        border: 1px solid #dbe4ea;
        border-radius: 12px;
        background: #fff;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .route-planner-head {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .route-shipper-block {
        border-top: 1px solid #e2e8f0;
        padding: 14px 16px;
    }
    .route-shipper-block:first-of-type {
        border-top: 0;
    }
    .route-definition-row {
        display: grid;
        grid-template-columns: minmax(110px, 1.1fr) 110px 130px 130px minmax(120px, 1.2fr) 38px;
        gap: 8px;
        align-items: end;
        padding: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fffdf2;
        margin-bottom: 8px;
    }
    .route-definition-row .form-label,
    .route-order-table .form-label {
        font-size: .72rem;
        color: #64748b;
        margin-bottom: 3px;
    }
    .route-order-table {
        font-size: .82rem;
    }
    .route-order-table th {
        background: #7c470f;
        color: #fff;
        font-size: .72rem;
        text-transform: uppercase;
        vertical-align: middle;
    }
    .route-order-table td {
        vertical-align: middle;
    }
    .route-row {
        background: #fff8df;
        font-weight: 700;
        cursor: pointer;
    }
    .route-child-row {
        background: #f8fbfd;
    }
    .route-child-row.is-hidden {
        display: none;
    }
    .route-indent {
        padding-left: 24px;
    }
    .route-savings.positive {
        color: #15803d;
    }
    .route-savings.negative {
        color: #dc2626;
    }
    @media (max-width: 992px) {
        .route-definition-row {
            grid-template-columns: 1fr 1fr;
        }
        .route-definition-row .route-remove-wrap {
            grid-column: 1 / -1;
        }
    }
    @media print {
        body * {
            visibility: hidden;
        }
        #assignmentPreviewModal,
        #assignmentPreviewModal * {
            visibility: visible;
        }
        #assignmentPreviewModal {
            position: absolute;
            inset: 0;
            display: block !important;
            overflow: visible !important;
        }
        #assignmentPreviewModal .modal-dialog {
            max-width: none;
            margin: 0;
        }
        #assignmentPreviewModal .modal-content {
            border: 0;
            box-shadow: none;
        }
        #assignmentPreviewModal .modal-header,
        #assignmentPreviewModal .modal-footer,
        #assignmentPreviewModal .btn-close {
            display: none !important;
        }
    }
</style>
@endpush

@section('accounting_content')
<div id="manageAssignmentsApp" data-refresh-url="{{ route('accounting.shippers', ['date' => $selectedDate]) }}">
<div class="row ">
    <div class="col col-md-6">
        <form method="GET" action="{{ route('accounting.shippers') }}" class="d-flex gap-2 align-items-center flex-grow-1">
            <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm" style="max-width: 150px">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-search me-1"></i>Lọc
            </button>
            <a href="{{ route('accounting.shippers', ['date' => now()->toDateString()]) }}" class="btn btn-sm {{ $selectedDate === now()->toDateString() ? 'btn-success' : 'btn-outline-success' }}">
                Hôm nay
            </a>
            <a href="{{ route('accounting.shippers', ['date' => now()->subDay()->toDateString()]) }}" class="btn btn-sm {{ $selectedDate === now()->subDay()->toDateString() ? 'btn-success' : 'btn-outline-success' }}">
                Hôm qua
            </a>
            <a href="{{ route('accounting.shippers') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>Đặt lại
            </a>
        </form>
        <div class="small text-muted mt-2">
            Chỉ hiển thị các đơn được tạo ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}; ngày giao của từng đơn có thể khác ngày tạo.
        </div>
    </div>
    <div class="col col-md-6 d-flex justify-content-end align-items-center">
        <div class="d-flex gap-2 align-items-center ms-auto">
            <input type="text" id="scheduleNotesInput" class="form-control form-control-sm" maxlength="500" placeholder="Ghi chú (tùy chọn)" style="width: 100%">
            <button type="button"
                class="btn btn-sm {{ $assignedOrdersCount > 0 ? 'btn-success' : 'btn-secondary' }}"
                style="min-width: 220px"
                data-bs-toggle="modal"
                data-bs-target="#assignmentPreviewModal"
                title="{{ $assignedOrdersCount > 0 ? 'Xem lại bảng kê trước khi gửi' : 'Chưa có đơn đã gán shipper' }}"
                @disabled($assignedOrdersCount === 0)>
                <i class="bi bi-eye me-1"></i>Xem lại & Gửi xác nhận
            </button>
        </div>
    </div>
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

<div class="route-planner" id="routePlanner">
    <div class="route-planner-head d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-bold text-dark">Tạo lộ trình ghép chuyến</div>
            <div class="text-muted small">Nhập phí sau khi ghép chuyến, chọn đơn vào từng lộ trình và thêm phụ phí cho đơn có hàng nhiều nếu cần.</div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary js-refresh-route-review">
                <i class="bi bi-arrow-repeat me-1"></i>Cập nhật bảng
            </button>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignmentPreviewModal">
                <i class="bi bi-table me-1"></i>Xem bảng kê
            </button>
        </div>
    </div>

    @forelse($assignedOrders as $shipperId => $shipperOrders)
        @if($shipperOrders->isNotEmpty())
            @php
                $shipper = $shippers->firstWhere('id', $shipperId);
                $shipperName = $shipper?->name ?? 'Shipper #' . $shipperId;
                $defaultRouteCode = 'R' . $shipperId . '-1';
            @endphp
            <div class="route-shipper-block js-route-shipper" data-shipper-id="{{ $shipperId }}" data-shipper-name="{{ $shipperName }}">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <div class="fw-bold text-dark"><i class="bi bi-person-badge me-1"></i>{{ $shipperName }}</div>
                        <div class="small text-muted">{{ $shipperOrders->count() }} đơn đã gán</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary js-add-route" data-shipper-id="{{ $shipperId }}">
                        <i class="bi bi-plus-circle me-1"></i>Thêm lộ trình
                    </button>
                </div>

                <div class="js-route-definitions" data-shipper-id="{{ $shipperId }}">
                    <div class="route-definition-row js-route-definition"
                        data-route-code="{{ $defaultRouteCode }}"
                        data-route-index="1">
                        <div>
                            <label class="form-label">Tên lộ trình</label>
                            <input type="text" class="form-control form-control-sm js-route-name" value="Lộ trình 1">
                        </div>
                        <div>
                            <label class="form-label">Km ước tính</label>
                            <input type="number" min="0" step="0.1" class="form-control form-control-sm js-route-km" placeholder="0">
                        </div>
                        <div>
                            <label class="form-label">Phí riêng lẻ</label>
                            <input type="text" class="form-control form-control-sm js-route-original" value="0" readonly>
                        </div>
                        <div>
                            <label class="form-label">Phí sau ghép</label>
                            <input type="number" min="0" step="1000" class="form-control form-control-sm js-route-fee" placeholder="0">
                        </div>
                        <div>
                            <label class="form-label">Ghi chú tuyến</label>
                            <input type="text" class="form-control form-control-sm js-route-note" placeholder="VD: ghép Q7 + Q12">
                        </div>
                        <div class="route-remove-wrap">
                            <button type="button" class="btn btn-sm btn-light js-remove-route" title="Xóa lộ trình" disabled>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered route-order-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 150px;">Lộ trình</th>
                                <th>Khách hàng</th>
                                <th>Điểm đến</th>
                                <th style="width: 80px;">Số lượng</th>
                                <th style="width: 120px;">Phí cố định</th>
                                <th style="width: 120px;">Phí thêm</th>
                                <th style="width: 130px;">Phí đơn cuối</th>
                                <th style="width: 150px;">Ghi chú đơn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shipperOrders as $order)
                                @php
                                    $customer = $order->customer;
                                    $selectedRoute = $customer?->truckRoute;
                                    if (!$selectedRoute && $customer?->truck_station_id) {
                                        $selectedRoute = $customer?->truckRouteByStation;
                                    }
                                    $truckStation = $customer?->truckStation ?: ($selectedRoute?->stops?->last()?->station);
                                    $destination = $order->recipient_address
                                        ?: $customer?->truck_station_address
                                        ?: $truckStation?->address
                                        ?: $truckStation?->name
                                        ?: $customer?->address
                                        ?: 'Chưa cập nhật';
                                    $quantity = (float) $order->items->sum('quantity');
                                    $baseFee = (bool) ($order->charge_shipping_fee ?? true)
                                        ? (float) ($order->shipping_fee ?? 0)
                                        : 0;
                                    $customerName = $customer?->name ?? $order->recipient_name ?? 'Khách hàng';
                                @endphp
                                <tr class="js-route-order"
                                    data-order-id="{{ $order->id }}"
                                    data-shipper-id="{{ $shipperId }}"
                                    data-order-code="{{ $order->code ?: $order->id }}"
                                    data-customer-name="{{ e($customerName) }}"
                                    data-destination="{{ e($destination) }}"
                                    data-quantity="{{ $quantity }}"
                                    data-origin="{{ e($order->warehouse?->name ?: 'Kho') }}"
                                    data-base-fee="{{ $baseFee }}">
                                    <td>
                                        <select class="form-select form-select-sm js-order-route">
                                            <option value="{{ $defaultRouteCode }}">Lộ trình 1</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $customerName }}</div>
                                        <div class="text-muted small">#{{ $order->code ?: $order->id }}</div>
                                    </td>
                                    <td>{{ $destination }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format($quantity, 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end js-base-fee-text">{{ number_format($baseFee, 0, ',', '.') }}</td>
                                    <td>
                                        <input type="number" min="0" step="1000" class="form-control form-control-sm js-order-extra-fee" value="0">
                                    </td>
                                    <td class="text-end fw-bold js-final-fee-text">{{ number_format($baseFee, 0, ',', '.') }}</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm js-order-note" placeholder="Hàng nhiều, cồng kềnh...">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @empty
        <div class="p-4 text-center text-muted">Chưa có đơn đã gán shipper để tạo lộ trình.</div>
    @endforelse
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold text-dark">Đơn hàng chưa gán</div>
                        <div class="text-muted small">Cột trái chỉ gồm đơn chưa gán. Có thể đổi shipper cố định ngay trên thẻ khách hàng.</div>
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
                            @include('accounting.ship.partials.manage-assignment-order-card', ['order' => $order, 'shippers' => $shippers, 'showAssignmentButtons' => true])
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $unassignedOrders->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="border-0   h-100">
            <div class="body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold text-dark">Shipper và đơn đã gán</div>
                        <div class="text-muted small">Đơn tự động gán và lộ trình mới sẽ xuất hiện tại đây để shipper xác nhận.</div>
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
                                @php
                                    $scheduleStatus = $shipperScheduleStatuses[$shipperId] ?? 'waiting';
                                    $scheduleBadgeClass = match ($scheduleStatus) {
                                        'confirmed' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        'draft' => 'bg-secondary',
                                        default => 'bg-warning text-dark',
                                    };
                                    $scheduleLabel = match ($scheduleStatus) {
                                        'confirmed' => 'Đã Xác nhận',
                                        'rejected' => 'Từ chối',
                                        'draft' => 'Chưa gửi',
                                        default => 'Chờ xác nhận',
                                    };
                                @endphp
                                <div class="border rounded-3 p-3 bg-white">
                                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $shipper?->name ?? 'Shipper #' . $shipperId }}</div>
                                            <div class="text-muted small">{{ $shipper?->phone ?? $shipper?->email ?? 'Không có liên hệ' }}</div>
                                            
                                        </div>
                                        <div class="d-flex align-items-center gap-2">                                            
                                            <div class="d-flex">
                                                <span class="badge bg-primary rounded-pill  me-2" style="white-space: nowrap;">{{ $shipperOrders->count() }}</span>
                                                <div class="ma-shipper-meta">
                                                    <span class="badge {{ $scheduleBadgeClass }}">{{ $scheduleLabel }}</span>
                                                </div>
                                                
                                            </div>
                                            <form method="POST" action="{{ route('accounting.shippers.bulk-transfer-assignments') }}" class="d-flex gap-1" style="width: 220px;">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ $selectedDate }}">
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
                                            @include('accounting.ship.partials.manage-assignment-order-card', [
                                                'order' => $order,
                                                'shippers' => $shippers,
                                                'showAssignmentButtons' => false,
                                                'canMoveUp' => $idx > 0,
                                                'canMoveDown' => $idx < $shipperOrders->count() - 1,
                                            ])
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

@php
    $assignmentPreviewRows = collect($assignedOrders)
        ->flatMap(function ($shipperOrders, $shipperId) use ($shippers) {
            $shipper = $shippers->firstWhere('id', (int) $shipperId);

            return collect($shipperOrders)->map(function ($order) use ($shipper) {
                $customer = $order->customer;
                $selectedRoute = $customer?->truckRoute;
                if (!$selectedRoute && $customer?->truck_station_id) {
                    $selectedRoute = $customer?->truckRouteByStation;
                }

                $truckStation = $customer?->truckStation
                    ?: ($selectedRoute?->stops?->last()?->station);
                $destination = $order->recipient_address
                    ?: $customer?->truck_station_address
                    ?: $truckStation?->address
                    ?: $truckStation?->name
                    ?: $customer?->address
                    ?: 'Chưa cập nhật';
                $origin = $order->warehouse?->name ?: 'Kho';
                $quantity = (float) $order->items->sum('quantity');
                $shippingFee = (bool) ($order->charge_shipping_fee ?? true)
                    ? (float) ($order->shipping_fee ?? 0)
                    : 0;

                return [
                    'customer_name' => $customer?->name ?? $order->recipient_name ?? 'Khách hàng',
                    'shipper_name' => $shipper?->name ?? $order->shipper?->name ?? '',
                    'quantity' => $quantity,
                    'origin' => $origin,
                    'destination' => $destination,
                    'km' => '',
                    'group_name' => '',
                    'shipping_fee' => $shippingFee,
                    'note' => trim((string) ($order->shipper_note ?: $order->note ?: '')),
                ];
            });
        })
        ->values();
@endphp

<div class="modal fade" id="assignmentPreviewModal" tabindex="-1" aria-labelledby="assignmentPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="assignmentPreviewModalLabel">Xem lại bảng kê trước khi gửi lộ trình</h5>
                    <div class="small text-muted">Kiểm tra lại danh sách đơn đã điều phối cho ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="fw-bold">CÔNG TY CỔ PHẦN THỰC PHẨM HOÀNG LONG</div>
                        <div class="small text-muted">Địa chỉ: 177C Chiến Lược, Khu phố 16, Phường Bình Trị Đông, TP. Hồ Chí Minh</div>
                    </div>
                    <div class="small fw-semibold">Ngày: {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</div>
                </div>

                <div class="ma-preview-title mb-3">Bảng kê chi phí ship cho từng đơn khách hàng</div>

                <div id="routeReviewTree" class="table-responsive"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>In bảng kê
                </button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Quay lại chỉnh</button>
                <form method="POST" action="{{ route('accounting.shippers.create-delivery-schedule') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="notes" id="previewScheduleNotesInput">
                    <input type="hidden" name="route_plan" id="previewRoutePlanInput">
                    <button type="submit" class="btn btn-success" @disabled(!$hasUnpublishedSchedules || $assignedOrdersCount === 0)>
                        <i class="bi bi-send-check me-1"></i>Xác nhận gửi lộ trình
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="shipperPickerModal" tabindex="-1" aria-labelledby="shipperPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="shipperPickerModalLabel">Chọn shipper</h5>
                    <div class="small text-muted" id="shipperPickerOrderInfo"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <form id="shipperPickerForm" method="POST">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="shipper_id" id="shipperPickerShipperId">
                    <input type="hidden" name="set_default_shipper" id="shipperPickerSetDefault" value="0">
                    <div class="d-grid gap-2">
                        @foreach($shippers as $pickerShipper)
                            <button type="submit" class="btn btn-outline-primary text-start js-pick-shipper" data-shipper-id="{{ $pickerShipper->id }}">
                                <i class="bi bi-person me-2"></i>{{ $pickerShipper->name }}
                                @if($pickerShipper->phone)
                                    <span class="text-muted small ms-1">{{ $pickerShipper->phone }}</span>
                                @endif
                            </button>
                        @endforeach
                        @php $currentManager = auth()->user(); @endphp
                        @if($currentManager && !$shippers->contains('id', $currentManager->id))
                            <button type="submit" class="btn btn-outline-danger text-start js-pick-shipper" data-shipper-id="{{ $currentManager->id }}">
                                <i class="bi bi-person-check me-2"></i>{{ $currentManager->name }} (Tôi)
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="defaultShipperPickerModal" tabindex="-1" aria-labelledby="defaultShipperPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="defaultShipperPickerModalLabel">Đổi shipper cố định</h5>
                    <div class="small text-muted" id="defaultShipperPickerCustomerInfo"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <form id="defaultShipperPickerForm" method="POST">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="shipper_id" id="defaultShipperPickerShipperId">
                    <input type="hidden" name="transfer_pending_orders" value="0">
                    <label class="form-check mb-3">
                        <input type="checkbox" name="transfer_pending_orders" value="1" class="form-check-input" checked>
                        <span class="form-check-label">Chuyển các đơn đang chờ sang shipper mới</span>
                    </label>
                    <div class="d-grid gap-2">
                        @foreach($shippers as $fixedPickerShipper)
                            <button type="submit"
                                class="btn btn-outline-primary text-start js-pick-default-shipper"
                                data-shipper-id="{{ $fixedPickerShipper->id }}">
                                <i class="bi bi-person me-2"></i>{{ $fixedPickerShipper->name }}
                                @if($fixedPickerShipper->phone)
                                    <span class="text-muted small ms-1">{{ $fixedPickerShipper->phone }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const appSelector = '#manageAssignmentsApp';
    const currency = new Intl.NumberFormat('vi-VN');

    function numberValue(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function routeDefinitionsFor(shipperBlock) {
        return Array.from(shipperBlock.querySelectorAll('.js-route-definition')).map(function (definition, index) {
            if (!definition.dataset.routeCode) {
                definition.dataset.routeCode = shipperBlock.dataset.shipperId + '-route-' + (index + 1);
            }

            return {
                code: definition.dataset.routeCode,
                name: definition.querySelector('.js-route-name')?.value?.trim() || ('Lộ trình ' + (index + 1)),
                km: numberValue(definition.querySelector('.js-route-km')?.value),
                combined_fee: numberValue(definition.querySelector('.js-route-fee')?.value),
                note: definition.querySelector('.js-route-note')?.value?.trim() || '',
                element: definition,
            };
        });
    }

    function syncRouteOptions(shipperBlock) {
        const routes = routeDefinitionsFor(shipperBlock);
        const optionsHtml = routes
            .map(route => `<option value="${route.code}">${route.name}</option>`)
            .join('');

        shipperBlock.querySelectorAll('.js-order-route').forEach(function (select) {
            const current = select.value || routes[0]?.code || '';
            select.innerHTML = optionsHtml;
            select.value = routes.some(route => route.code === current) ? current : (routes[0]?.code || '');
        });
    }

    function collectRoutePlan() {
        const plan = [];
        document.querySelectorAll('.js-route-shipper').forEach(function (shipperBlock) {
            syncRouteOptions(shipperBlock);

            const routes = routeDefinitionsFor(shipperBlock).map(function (route) {
                return {
                    code: route.code,
                    name: route.name,
                    km: route.km,
                    combined_fee: route.combined_fee,
                    note: route.note,
                    original_total: 0,
                    additional_total: 0,
                    final_total: 0,
                    orders: [],
                    element: route.element,
                };
            });

            const routeByCode = new Map(routes.map(route => [route.code, route]));
            shipperBlock.querySelectorAll('.js-route-order').forEach(function (row) {
                const routeCode = row.querySelector('.js-order-route')?.value || routes[0]?.code;
                const route = routeByCode.get(routeCode);
                if (!route) return;

                const baseFee = numberValue(row.dataset.baseFee);
                const extraFee = numberValue(row.querySelector('.js-order-extra-fee')?.value);
                const finalFee = baseFee + extraFee;
                const order = {
                    order_id: Number(row.dataset.orderId),
                    order_code: row.dataset.orderCode,
                    customer_name: row.dataset.customerName,
                    destination: row.dataset.destination,
                    origin: row.dataset.origin,
                    quantity: numberValue(row.dataset.quantity),
                    base_fee: baseFee,
                    extra_fee: extraFee,
                    final_fee: finalFee,
                    note: row.querySelector('.js-order-note')?.value?.trim() || '',
                };

                route.orders.push(order);
                route.original_total += baseFee;
                route.additional_total += extraFee;
                route.final_total += finalFee;
            });

            routes.forEach(function (route) {
                const originalInput = route.element.querySelector('.js-route-original');
                if (originalInput) {
                    originalInput.value = currency.format(route.original_total);
                }
            });

            plan.push({
                shipper_id: Number(shipperBlock.dataset.shipperId),
                shipper_name: shipperBlock.dataset.shipperName,
                routes: routes.map(function (route) {
                    const {element, ...payload} = route;
                    return payload;
                }),
            });
        });

        document.querySelectorAll('.js-route-order').forEach(function (row) {
            const baseFee = numberValue(row.dataset.baseFee);
            const extraFee = numberValue(row.querySelector('.js-order-extra-fee')?.value);
            const finalText = row.querySelector('.js-final-fee-text');
            if (finalText) {
                finalText.textContent = currency.format(baseFee + extraFee);
            }
        });

        return plan;
    }

    function renderRouteReview() {
        const plan = collectRoutePlan();
        const target = document.getElementById('routeReviewTree');
        const routePlanInput = document.getElementById('previewRoutePlanInput');
        if (routePlanInput) {
            routePlanInput.value = JSON.stringify(plan);
        }
        if (!target) return plan;

        let rowIndex = 1;
        const rows = [];
        plan.forEach(function (shipper) {
            shipper.routes.forEach(function (route) {
                if (!route.orders.length) return;

                const saved = route.original_total - route.combined_fee;
                rows.push(`
                    <tr class="route-row">
                        <td colspan="2">− ${route.name}</td>
                        <td>${shipper.shipper_name}</td>
                        <td class="text-center">${currency.format(route.orders.reduce((sum, order) => sum + order.quantity, 0))}</td>
                        <td class="text-end">${currency.format(route.original_total)}</td>
                        <td class="text-center">${route.km || ''}</td>
                        <td>${route.note || ''}</td>
                        <td class="text-end">${route.combined_fee ? currency.format(route.combined_fee) : ''}</td>
                        <td class="text-end route-savings ${saved >= 0 ? 'positive' : 'negative'}">${route.combined_fee ? currency.format(saved) : ''}</td>
                        <td></td>
                    </tr>
                `);

                route.orders.forEach(function (order) {
                    rows.push(`
                        <tr class="route-child-row">
                            <td class="text-center">${rowIndex++}</td>
                            <td class="route-indent fw-semibold">${order.customer_name}</td>
                            <td>${shipper.shipper_name}</td>
                            <td class="text-center">${currency.format(order.quantity)}</td>
                            <td>${order.origin}</td>
                            <td>${order.destination}</td>
                            <td>${route.name}</td>
                            <td class="text-end">${currency.format(order.final_fee)}</td>
                            <td class="text-end">${order.extra_fee ? '+' + currency.format(order.extra_fee) : ''}</td>
                            <td>${order.note}</td>
                        </tr>
                    `);
                });
            });
        });

        target.innerHTML = `
            <table class="table table-bordered ma-preview-table mb-0">
                <thead>
                    <tr>
                        <th style="width:48px;">STT</th>
                        <th style="width:180px;">Lộ trình / Khách hàng</th>
                        <th style="width:110px;">Nhân viên ship</th>
                        <th style="width:76px;">Số lượng</th>
                        <th style="width:110px;">Điểm đi / Phí riêng lẻ</th>
                        <th>Điểm đến / Km</th>
                        <th style="width:120px;">Tên đơn ghép cùng</th>
                        <th style="width:120px;">Số tiền cho đơn đó (VNĐ)</th>
                        <th style="width:110px;">Tiết kiệm / Phí thêm</th>
                        <th style="width:120px;">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows.length ? rows.join('') : '<tr><td colspan="10" class="text-center text-muted py-4">Chưa có lộ trình để xem lại.</td></tr>'}
                </tbody>
            </table>
        `;

        return plan;
    }

    function refreshAllRouteBlocks() {
        document.querySelectorAll('.js-route-shipper').forEach(syncRouteOptions);
        collectRoutePlan();
    }

    refreshAllRouteBlocks();

    document.addEventListener('show.bs.modal', function (event) {
        if (event.target.id !== 'assignmentPreviewModal') return;

        const notesInput = document.getElementById('scheduleNotesInput');
        const previewNotesInput = document.getElementById('previewScheduleNotesInput');
        if (notesInput && previewNotesInput) {
            previewNotesInput.value = notesInput.value || '';
        }
        renderRouteReview();
    });

    function notify(message, isError = false) {
        const alert = document.createElement('div');
        alert.className = `alert ${isError ? 'alert-danger' : 'alert-success'} shadow position-fixed top-0 end-0 m-3`;
        alert.style.zIndex = '2000';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3500);
    }

    async function refreshAssignments() {
        const app = document.querySelector(appSelector);
        const response = await fetch(app.dataset.refreshUrl, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        if (!response.ok) throw new Error('Không thể tải lại danh sách điều phối.');
        const html = await response.text();
        const documentFragment = new DOMParser().parseFromString(html, 'text/html');
        const refreshedApp = documentFragment.querySelector(appSelector);
        if (!refreshedApp) throw new Error('Dữ liệu điều phối trả về không hợp lệ.');
        app.replaceWith(refreshedApp);
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        refreshAllRouteBlocks();
    }

    document.addEventListener('click', function (event) {
        const addRouteButton = event.target.closest('.js-add-route');
        if (addRouteButton) {
            const shipperBlock = addRouteButton.closest('.js-route-shipper');
            const definitions = shipperBlock.querySelector('.js-route-definitions');
            const count = definitions.querySelectorAll('.js-route-definition').length + 1;
            const routeCode = 'R' + shipperBlock.dataset.shipperId + '-' + count + '-' + Date.now();
            definitions.insertAdjacentHTML('beforeend', `
                <div class="route-definition-row js-route-definition" data-route-code="${routeCode}" data-route-index="${count}">
                    <div>
                        <label class="form-label">Tên lộ trình</label>
                        <input type="text" class="form-control form-control-sm js-route-name" value="Lộ trình ${count}">
                    </div>
                    <div>
                        <label class="form-label">Km ước tính</label>
                        <input type="number" min="0" step="0.1" class="form-control form-control-sm js-route-km" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label">Phí riêng lẻ</label>
                        <input type="text" class="form-control form-control-sm js-route-original" value="0" readonly>
                    </div>
                    <div>
                        <label class="form-label">Phí sau ghép</label>
                        <input type="number" min="0" step="1000" class="form-control form-control-sm js-route-fee" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label">Ghi chú tuyến</label>
                        <input type="text" class="form-control form-control-sm js-route-note" placeholder="VD: ghép Q7 + Q12">
                    </div>
                    <div class="route-remove-wrap">
                        <button type="button" class="btn btn-sm btn-light js-remove-route" title="Xóa lộ trình">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            refreshAllRouteBlocks();
            return;
        }

        const removeRouteButton = event.target.closest('.js-remove-route');
        if (removeRouteButton && !removeRouteButton.disabled) {
            const shipperBlock = removeRouteButton.closest('.js-route-shipper');
            removeRouteButton.closest('.js-route-definition')?.remove();
            refreshAllRouteBlocks();
            return;
        }

        if (event.target.closest('.js-refresh-route-review')) {
            refreshAllRouteBlocks();
            notify('Đã cập nhật bảng lộ trình.');
            return;
        }

        const button = event.target.closest('.js-open-shipper-picker, .js-open-default-shipper-picker, .js-pick-shipper, .js-pick-default-shipper');
        if (!button) return;

        if (button.classList.contains('js-open-shipper-picker')) {
            const form = document.getElementById('shipperPickerForm');
            form.action = button.dataset.action;
            document.getElementById('shipperPickerShipperId').value = '';
            document.getElementById('shipperPickerSetDefault').value = button.dataset.setDefault;
            document.getElementById('shipperPickerOrderInfo').textContent = 'Đơn ' + button.dataset.orderCode + ' - ' + button.dataset.customerName;
        }
        if (button.classList.contains('js-pick-shipper')) {
            document.getElementById('shipperPickerShipperId').value = button.dataset.shipperId;
        }
        if (button.classList.contains('js-open-default-shipper-picker')) {
            const form = document.getElementById('defaultShipperPickerForm');
            form.action = button.dataset.action;
            document.getElementById('defaultShipperPickerShipperId').value = '';
            document.getElementById('defaultShipperPickerCustomerInfo').textContent = 'Khách hàng: ' + button.dataset.customerName;
            document.querySelectorAll('.js-pick-default-shipper').forEach(function (shipperButton) {
                shipperButton.classList.toggle('active', shipperButton.dataset.shipperId === button.dataset.currentShipperId);
            });
        }
        if (button.classList.contains('js-pick-default-shipper')) {
            document.getElementById('defaultShipperPickerShipperId').value = button.dataset.shipperId;
        }
    });

    document.addEventListener('input', function (event) {
        if (!event.target.closest('#routePlanner')) return;
        refreshAllRouteBlocks();
    });

    document.addEventListener('change', function (event) {
        if (!event.target.closest('#routePlanner')) return;
        refreshAllRouteBlocks();
    });

    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!form.closest(appSelector) || form.method.toLowerCase() === 'get') return;
        event.preventDefault();
        if (form.querySelector('#previewRoutePlanInput')) {
            renderRouteReview();
        }

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: new FormData(form),
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Không thể cập nhật điều phối.');
            }
            document.querySelectorAll('.modal.show').forEach(function (modal) {
                bootstrap.Modal.getInstance(modal)?.hide();
            });
            await refreshAssignments();
            notify(payload.message || 'Đã cập nhật điều phối.');
        } catch (error) {
            if (submitButton) submitButton.disabled = false;
            notify(error.message, true);
        }
    });
});
</script>
@endpush
@endsection
