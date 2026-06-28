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

@section('content')
<div id="manageAssignmentsApp" data-refresh-url="{{ route('shipper.manage-assignments', ['date' => $selectedDate]) }}">
<div class="row ">
    <div class="col col-md-6">
        <form method="GET" action="{{ route('shipper.manage-assignments') }}" class="d-flex gap-2 align-items-center flex-grow-1">
            <input type="date" name="date" value="{{ $selectedDate }}" class="form-control form-control-sm" style="max-width: 150px">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-search me-1"></i>Lọc
            </button>
            <a href="{{ route('shipper.manage-assignments', ['date' => now()->toDateString()]) }}" class="btn btn-sm {{ $selectedDate === now()->toDateString() ? 'btn-success' : 'btn-outline-success' }}">
                Hôm nay
            </a>
            <a href="{{ route('shipper.manage-assignments', ['date' => now()->subDay()->toDateString()]) }}" class="btn btn-sm {{ $selectedDate === now()->subDay()->toDateString() ? 'btn-success' : 'btn-outline-success' }}">
                Hôm qua
            </a>
            <a href="{{ route('shipper.manage-assignments') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>Đặt lại
            </a>
        </form>
        <div class="small text-muted mt-2">
            Đang hiển thị đơn lên ngày {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}; ngày giao của từng đơn có thể là hôm nay hoặc ngày mai.
        </div>
    </div>
    <div class="col col-md-6 d-flex justify-content-end align-items-center">
        <div class="d-flex gap-2 align-items-center ms-auto">
            <input type="text" id="scheduleNotesInput" class="form-control form-control-sm" maxlength="500" placeholder="Ghi chú (tùy chọn)" style="width: 100%">
            <button type="button"
                class="btn btn-sm {{ $hasUnpublishedSchedules ? 'btn-success' : 'btn-secondary' }}"
                style="min-width: 220px"
                data-bs-toggle="modal"
                data-bs-target="#assignmentPreviewModal"
                title="{{ $hasUnpublishedSchedules ? 'Xem lại bảng kê trước khi gửi' : 'Lộ trình hiện tại đã được gửi' }}"
                @disabled(!$hasUnpublishedSchedules)>
                <i class="bi bi-eye me-1"></i>{{ $hasUnpublishedSchedules ? 'Xem lại & Gửi xác nhận' : 'Đã gửi lộ trình' }}
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
                                            <form method="POST" action="{{ route('shipper.bulk-transfer-assignments') }}" class="d-flex gap-1" style="width: 220px;">
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
                                            @include('shipper.partials.manage-assignment-order-card', [
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

                <div class="table-responsive">
                    <table class="table table-bordered ma-preview-table mb-0">
                        <thead>
                            <tr>
                                <th style="width:48px;">STT</th>
                                <th style="width:180px;">Tên khách hàng</th>
                                <th style="width:110px;">Nhân viên ship</th>
                                <th style="width:76px;">Số lượng</th>
                                <th style="width:110px;">Điểm đi</th>
                                <th>Điểm đến</th>
                                <th style="width:90px;">Số Km<br>ước tính</th>
                                <th style="width:120px;">Tên đơn<br>ghép cùng</th>
                                <th style="width:120px;">Số tiền cho<br>đơn đó (VNĐ)</th>
                                <th style="width:120px;">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignmentPreviewRows as $rowIndex => $row)
                                <tr>
                                    <td class="text-center">{{ $rowIndex + 1 }}</td>
                                    <td class="fw-semibold">{{ $row['customer_name'] }}</td>
                                    <td>{{ $row['shipper_name'] ?: '-' }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format((float) $row['quantity'], 2, ',', '.'), '0'), ',') }}</td>
                                    <td>{{ $row['origin'] }}</td>
                                    <td>{{ $row['destination'] }}</td>
                                    <td class="text-center">{{ $row['km'] }}</td>
                                    <td>{{ $row['group_name'] }}</td>
                                    <td class="text-end">{{ $row['shipping_fee'] > 0 ? number_format($row['shipping_fee'], 0, ',', '.') : '' }}</td>
                                    <td>{{ $row['note'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Chưa có đơn đã gán shipper để xem lại.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>In bảng kê
                </button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Quay lại chỉnh</button>
                <form method="POST" action="{{ route('shipper.create-delivery-schedule') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="notes" id="previewScheduleNotesInput">
                    <button type="submit" class="btn btn-success" @disabled(!$hasUnpublishedSchedules || $assignmentPreviewRows->isEmpty())>
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

    document.addEventListener('show.bs.modal', function (event) {
        if (event.target.id !== 'assignmentPreviewModal') return;

        const notesInput = document.getElementById('scheduleNotesInput');
        const previewNotesInput = document.getElementById('previewScheduleNotesInput');
        if (notesInput && previewNotesInput) {
            previewNotesInput.value = notesInput.value || '';
        }
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
    }

    document.addEventListener('click', function (event) {
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

    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!form.closest(appSelector) || form.method.toLowerCase() === 'get') return;
        event.preventDefault();

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
