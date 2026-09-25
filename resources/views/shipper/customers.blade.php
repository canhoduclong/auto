@extends('layouts.shipper')

@section('title', !empty($isManagerShipper) ? 'Quản lý khách hàng' : 'Khách hàng')
@section('subtitle', !empty($isManagerShipper) ? 'Gắn shipper cố định cho khách hàng để tự động áp dụng khi lên đơn' : 'Khách hàng trong lịch giao theo ngày')

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            @if(!empty($isManagerShipper))
                <div class="col-md-3">
                    <label class="form-label">Tìm khách hàng</label>
                    <input type="search" name="q" value="{{ $keyword ?? '' }}" class="form-control" placeholder="Tên, SĐT, địa chỉ">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Trạng thái gán</label>
                    <select name="assignment_status" class="form-select">
                        <option value="all" @selected(($assignmentStatus ?? 'all') === 'all')>Tất cả</option>
                        <option value="fixed" @selected(($assignmentStatus ?? 'all') === 'fixed')>Đã gán</option>
                        <option value="unassigned" @selected(($assignmentStatus ?? 'all') === 'unassigned')>Chưa gán</option>
                    </select>
                </div>
            @endif
            <div class="col-md-4">
                <label class="form-label">{{ !empty($isManagerShipper) ? 'Ngày thống kê đơn' : 'Ngày giao' }}</label>
                <input type="date" name="date" value="{{ $selectedDate }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sắp xếp theo</label>
                <select name="sort" class="form-select">
                    <option value="delivery_time" @selected($sort === 'delivery_time')>Giờ giao</option>
                    <option value="name" @selected($sort === 'name')>Tên khách hàng</option>
                    <option value="orders_count" @selected($sort === 'orders_count')>Số đơn</option>
                    <option value="total" @selected($sort === 'total')>Tổng tiền</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Thứ tự</label>
                <select name="direction" class="form-select">
                    <option value="asc" @selected($direction === 'asc')>Tăng dần</option>
                    <option value="desc" @selected($direction === 'desc')>Giảm dần</option>
                </select>
            </div>
            <div class="col-md-{{ !empty($isManagerShipper) ? '12' : '2' }}">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Lọc</button>
            </div>
        </form>
    </div>
</div>

@if(!empty($isManagerShipper))
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <strong>Danh sách khách hàng</strong>
                <div class="small text-muted">Shipper cố định sẽ được tự động gán cho đơn mới của khách hàng.</div>
            </div>
            <span class="badge bg-primary">{{ $customers->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Khách hàng</th>
                        <th>Liên hệ</th>
                        <th>Giờ giao</th>
                        <th>Shipper cố định</th>
                        <th style="min-width: 210px">Phí ship mặc định</th>
                        <th class="text-center">Số đơn</th>
                        <th class="text-end">Tổng tiền</th>
                        <th class="text-end">Gắn shipper</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $customer->name }}</div>
                                <div class="small text-muted">{{ $customer->address ?: 'Chưa có địa chỉ' }}</div>
                            </td>
                            <td>{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->delivery_time ?: '—' }}</td>
                            <td>
                                @if($customer->defaultShipper)
                                    <span class="badge bg-success">{{ $customer->defaultShipper->name }}</span>
                                @else
                                    <span class="badge bg-secondary">Chưa gán</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('shipper.customers.shipping-fee.update', $customer) }}" class="d-flex gap-2">
                                    @csrf
                                    <input type="number" name="shipping_fee" value="{{ $customer->shipping_fee ?? 0 }}" min="0" step="1000" class="form-control form-control-sm" required aria-label="Phí ship mặc định">
                                    <button class="btn btn-sm btn-outline-success" title="Lưu phí ship"><i class="bi bi-check-lg"></i></button>
                                </form>
                            </td>
                            <td class="text-center">{{ $customer->orders_count }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $customer->orders_total) }}đ</td>
                            <td class="text-end">
                                <button type="button"
                                    class="btn btn-sm {{ $customer->default_shipper_id ? 'btn-outline-primary' : 'btn-primary' }} js-open-customer-shipper-picker"
                                    data-bs-toggle="modal"
                                    data-bs-target="#customerShipperPickerModal"
                                    data-action="{{ route('shipper.customers.default-shipper.update', $customer) }}"
                                    data-customer-name="{{ $customer->name }}"
                                    data-current-shipper-id="{{ $customer->default_shipper_id }}"
                                    data-has-default="{{ $customer->default_shipper_id ? '1' : '0' }}">
                                    <i class="bi bi-person-check me-1"></i>{{ $customer->default_shipper_id ? 'Đổi shipper' : 'Gắn shipper' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Không tìm thấy khách hàng phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="customerShipperPickerModal" tabindex="-1" aria-labelledby="customerShipperPickerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="customerShipperPickerModalLabel">Chọn shipper cố định</h5>
                        <div class="small text-muted" id="customerShipperPickerInfo"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <form id="customerShipperPickerForm" method="POST">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="shipper_id" id="customerShipperPickerShipperId">
                        <input type="hidden" name="transfer_pending_orders" value="0">

                        <label class="form-check mb-3" id="customerShipperTransferWrapper">
                            <input type="checkbox" name="transfer_pending_orders" value="1" class="form-check-input">
                            <span class="form-check-label">Chuyển các đơn đang chờ sang shipper mới</span>
                        </label>

                        <div class="d-grid gap-2">
                            @foreach($shippers as $pickerShipper)
                                <button type="submit"
                                    class="btn btn-outline-primary text-start js-pick-customer-shipper"
                                    data-shipper-id="{{ $pickerShipper->id }}">
                                    <i class="bi bi-person me-2"></i>{{ $pickerShipper->name }}
                                    @if($pickerShipper->phone ?? false)
                                        <span class="text-muted small ms-1">{{ $pickerShipper->phone }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@else
    @foreach([
        ['title' => 'Khách hàng được gán cố định', 'customers' => $fixedCustomers, 'class' => 'success'],
        ['title' => 'Khách hàng chưa được gán cố định', 'customers' => $unassignedCustomers, 'class' => 'secondary'],
    ] as $section)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>{{ $section['title'] }}</strong>
                <span class="badge bg-{{ $section['class'] }}">{{ $section['customers']->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Khách hàng</th>
                            <th>Liên hệ</th>
                            <th>Giờ giao</th>
                            <th class="text-center">Số đơn</th>
                            <th class="text-end">Tổng tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($section['customers'] as $customer)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $customer->name }}</div>
                                    <div class="small text-muted">{{ $customer->address ?: 'Chưa có địa chỉ' }}</div>
                                </td>
                                <td>{{ $customer->phone ?: '—' }}</td>
                                <td>{{ $customer->delivery_time ?: '—' }}</td>
                                <td class="text-center">{{ $customer->orders_count }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $customer->orders_total) }}đ</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Không có khách hàng trong ngày này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endif
@endsection

@push('scripts')
@if(!empty($isManagerShipper))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pickerForm = document.getElementById('customerShipperPickerForm');
    const shipperIdInput = document.getElementById('customerShipperPickerShipperId');
    const customerInfo = document.getElementById('customerShipperPickerInfo');
    const transferWrapper = document.getElementById('customerShipperTransferWrapper');
    const transferCheckbox = transferWrapper?.querySelector('input[type="checkbox"]');

    document.addEventListener('click', function (event) {
        const openButton = event.target.closest('.js-open-customer-shipper-picker');
        if (openButton && pickerForm && shipperIdInput && customerInfo) {
            pickerForm.action = openButton.dataset.action;
            shipperIdInput.value = '';
            customerInfo.textContent = 'Khách hàng: ' + openButton.dataset.customerName;

            const hasDefault = openButton.dataset.hasDefault === '1';
            if (transferWrapper) transferWrapper.classList.toggle('d-none', !hasDefault);
            if (transferCheckbox) transferCheckbox.checked = hasDefault;

            document.querySelectorAll('.js-pick-customer-shipper').forEach(function (shipperButton) {
                shipperButton.classList.toggle('active', shipperButton.dataset.shipperId === openButton.dataset.currentShipperId);
            });
        }

        const pickerButton = event.target.closest('.js-pick-customer-shipper');
        if (pickerButton && shipperIdInput) {
            shipperIdInput.value = pickerButton.dataset.shipperId;
        }
    });
});
</script>
@endif
@endpush
