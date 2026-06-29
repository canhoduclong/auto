@extends('layouts.app')

@section('content')
@php
    $money = fn ($value) => number_format((float) ($value ?? 0), 0, ',', '.') . ' đ';
    $statusLabel = $statusOptions[$order->status] ?? $order->status;
@endphp

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h4 class="mb-1">Chỉnh sửa đơn {{ $order->code ?: '#' . $order->id }}</h4>
            <div class="text-muted">
                Tạo lúc {{ optional($order->created_at)->format('d/m/Y H:i') }} · Cập nhật {{ optional($order->updated_at)->format('d/m/Y H:i') }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-secondary">Xem chi tiết</a>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('orders.buttons.cancel') }}</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Vui lòng kiểm tra lại thông tin.</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <form action="{{ route('orders.update', $order->id) }}" method="POST" class="card">
                @csrf
                @method('PUT')

                <div class="card-header">
                    <h5 class="mb-0">Thông tin đơn hàng</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">{{ __('orders.labels.status') }}</label>
                            <select name="status" id="status" class="form-select">
                                @foreach($statusOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $order->status) == $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="customer_id" class="form-label">{{ __('orders.labels.customer') }}</label>
                            <select name="customer_id" id="customer_id" class="form-select" required>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id', $order->customer_id) == $customer->id)>
                                        {{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="user_id" class="form-label">{{ __('orders.labels.employee') }}</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id', $order->user_id) == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="shipper_id" class="form-label">Shipper phụ trách</label>
                            <select name="shipper_id" id="shipper_id" class="form-select">
                                <option value="">Chưa gán shipper</option>
                                @foreach($shippers as $shipper)
                                    <option value="{{ $shipper->id }}" @selected(old('shipper_id', $order->shipper_id) == $shipper->id)>{{ $shipper->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="warehouse_id" class="form-label">Kho xuất</label>
                            <select name="warehouse_id" id="warehouse_id" class="form-select">
                                <option value="">Chưa chọn kho</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $order->warehouse_id) == $warehouse->id)>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="return_warehouse_id" class="form-label">Kho nhận hoàn</label>
                            <select name="return_warehouse_id" id="return_warehouse_id" class="form-select">
                                <option value="">Theo kho mặc định</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected(old('return_warehouse_id', $order->return_warehouse_id) == $warehouse->id)>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="delivery_date" class="form-label">Ngày giao</label>
                            <input type="date" name="delivery_date" id="delivery_date" class="form-control" value="{{ old('delivery_date', optional($order->delivery_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="delivery_time" class="form-label">Khung giờ giao</label>
                            <input type="text" name="delivery_time" id="delivery_time" class="form-control" value="{{ old('delivery_time', $order->delivery_time) }}" placeholder="Ví dụ: 8h-10h">
                        </div>
                        <div class="col-md-4">
                            <label for="actual_weight" class="form-label">Cân thực tế (kg)</label>
                            <input type="number" step="0.001" min="0" name="actual_weight" id="actual_weight" class="form-control" value="{{ old('actual_weight', $order->actual_weight) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="delivery_status" class="form-label">{{ __('orders.labels.delivery_status') }}</label>
                            <select name="delivery_status" id="delivery_status" class="form-select">
                                <option value="">Chưa xác định</option>
                                @foreach($deliveryStatusOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('delivery_status', $order->delivery_status) == $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="recipient_name" class="form-label">Người nhận</label>
                            <input type="text" name="recipient_name" id="recipient_name" class="form-control" value="{{ old('recipient_name', $order->recipient_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="recipient_phone" class="form-label">SĐT người nhận</label>
                            <input type="text" name="recipient_phone" id="recipient_phone" class="form-control" value="{{ old('recipient_phone', $order->recipient_phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="recipient_email" class="form-label">Email người nhận</label>
                            <input type="email" name="recipient_email" id="recipient_email" class="form-control" value="{{ old('recipient_email', $order->recipient_email) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="recipient_address" class="form-label">Địa chỉ giao</label>
                            <input type="text" name="recipient_address" id="recipient_address" class="form-control" value="{{ old('recipient_address', $order->recipient_address) }}">
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="payment_status" class="form-label">Trạng thái thanh toán</label>
                            <select name="payment_status" id="payment_status" class="form-select">
                                <option value="">Chưa xác định</option>
                                @foreach($paymentStatusOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('payment_status', $order->payment_status) == $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="payment_method" class="form-label">Phương thức thanh toán</label>
                            <input type="text" name="payment_method" id="payment_method" class="form-control" value="{{ old('payment_method', $order->payment_method) }}" placeholder="cash, transfer, cod...">
                        </div>
                        <div class="col-md-4">
                            <label for="amount_paid" class="form-label">Đã thanh toán</label>
                            <input type="number" step="1000" min="0" name="amount_paid" id="amount_paid" class="form-control" value="{{ old('amount_paid', $order->amount_paid) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="collected_amount" class="form-label">Shipper đã thu</label>
                            <input type="number" step="1000" min="0" name="collected_amount" id="collected_amount" class="form-control" value="{{ old('collected_amount', $order->collected_amount) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="shipping_fee" class="form-label">Phí ship</label>
                            <input type="number" step="1000" min="0" name="shipping_fee" id="shipping_fee" class="form-control" value="{{ old('shipping_fee', $order->shipping_fee) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="foam_box_price" class="form-label">Phí thùng xốp</label>
                            <input type="number" step="1000" min="0" name="foam_box_price" id="foam_box_price" class="form-control" value="{{ old('foam_box_price', $order->foam_box_price) }}">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-md-4">
                                <input type="hidden" name="charge_shipping_fee" value="0">
                                <input class="form-check-input" type="checkbox" name="charge_shipping_fee" id="charge_shipping_fee" value="1" @checked(old('charge_shipping_fee', $order->charge_shipping_fee))>
                                <label class="form-check-label" for="charge_shipping_fee">Tính phí ship cho đơn này</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-md-4">
                                <input type="hidden" name="charge_foam_box_fee" value="0">
                                <input class="form-check-input" type="checkbox" name="charge_foam_box_fee" id="charge_foam_box_fee" value="1" @checked(old('charge_foam_box_fee', $order->charge_foam_box_fee))>
                                <label class="form-check-label" for="charge_foam_box_fee">Tính phí thùng xốp</label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="note" class="form-label">Ghi chú đơn</label>
                            <textarea name="note" id="note" class="form-control" rows="3">{{ old('note', $order->note) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="shipper_note" class="form-label">Ghi chú shipper</label>
                            <textarea name="shipper_note" id="shipper_note" class="form-control" rows="3">{{ old('shipper_note', $order->shipper_note) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label for="return_reason" class="form-label">Lý do hoàn/trả nếu có</label>
                            <textarea name="return_reason" id="return_reason" class="form-control" rows="2">{{ old('return_reason', $order->return_reason) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('orders.buttons.update') }}</button>
                </div>
            </form>

            <div class="card mt-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">Sản phẩm trong đơn</h5>
                    <div class="d-flex gap-2">
                        <select id="edit-variant-select" class="form-select form-select-sm" style="min-width:280px">
                            <option value="">-- {{ __('orders.labels.variant') }} --</option>
                            @foreach(\App\Models\ProductVariant::with('product')->orderBy('product_id')->get() as $variant)
                                <option value="{{ $variant->id }}">{{ $variant->product->name ?? '' }} - {{ $variant->variant_name ?? $variant->name ?? ($variant->sku ?? $variant->id) }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="edit-add-variant" class="btn btn-success btn-sm">{{ __('inventory.buttons.add_item') }}</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="edit-variant-list"></div>
                    <div class="mt-3 text-end">
                        <strong>{{ __('orders.labels.total') }}: <span id="edit-order-total">{{ $money($order->total) }}</span></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Đơn thuộc về ai</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Khách hàng</dt>
                        <dd class="col-7">{{ $order->customer?->name ?? 'Chưa có' }}</dd>
                        <dt class="col-5">SĐT khách</dt>
                        <dd class="col-7">{{ $order->customer?->phone ?? '—' }}</dd>
                        <dt class="col-5">Địa chỉ khách</dt>
                        <dd class="col-7">{{ $order->customer?->address ?? '—' }}</dd>
                        <dt class="col-5">Sale tạo đơn</dt>
                        <dd class="col-7">{{ $order->user?->name ?? 'Chưa có' }}</dd>
                        <dt class="col-5">Sale phụ trách khách</dt>
                        <dd class="col-7">{{ $order->customer?->assignedTo?->name ?? 'Chưa có' }}</dd>
                        <dt class="col-5">Owner hiện tại</dt>
                        <dd class="col-7">{{ $order->customer?->currentOwner?->name ?? 'Chưa có' }}</dd>
                        <dt class="col-5">Shipper</dt>
                        <dd class="col-7">{{ $order->shipper?->name ?? 'Chưa gán' }}</dd>
                        <dt class="col-5">Shipper cố định</dt>
                        <dd class="col-7">{{ $order->customer?->defaultShipper?->name ?? 'Chưa đặt' }}</dd>
                        <dt class="col-5">Kho xuất</dt>
                        <dd class="col-7">{{ $order->warehouse?->name ?? 'Chưa chọn' }}</dd>
                        <dt class="col-5">Trạng thái</dt>
                        <dd class="col-7"><span class="badge bg-info-subtle text-info">{{ $statusLabel }}</span></dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Tài chính</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span>Tạm tính</span><strong>{{ $money($order->subtotal_amount ?? $order->total) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Giảm giá</span><strong>{{ $money($order->total_discount ?? 0) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Phí ship</span><strong>{{ $money($order->shipping_fee ?? 0) }}</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Đã thu/đã trả</span><strong>{{ $money(max((float) ($order->amount_paid ?? 0), (float) ($order->collected_amount ?? 0))) }}</strong></div>
                    <div class="d-flex justify-content-between border-top pt-2"><span>Tổng đơn</span><strong>{{ $money($order->total) }}</strong></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Lịch sử gần nhất</h5></div>
                <div class="list-group list-group-flush">
                    @forelse($order->histories->sortByDesc('created_at')->take(8) as $history)
                        <div class="list-group-item">
                            <div class="fw-semibold">{{ $history->action }}</div>
                            <div class="small text-muted">
                                {{ optional($history->created_at)->format('d/m/Y H:i') }}
                                @if($history->user)
                                    · {{ $history->user->name }}
                                @endif
                            </div>
                            @if($history->note)
                                <div class="small">{{ $history->note }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item text-muted">Chưa có lịch sử.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let orderId = {{ $order->id }};
function loadEditVariants() {
    $.get(`/orders/${orderId}/list-variant`, function(html) {
        $('#edit-variant-list').html(html);
        $('#edit-order-total').text(($('#edit-list-total').text() || 0) + ' đ');
    });
}
$(function() {
    loadEditVariants();
    $('#edit-variant-list').on('click', '.remove-variant-btn', function() {
        let vid = $(this).data('variant-id');
        $.post(`/orders/${orderId}/remove-variant`, {variant_id: vid, _token: '{{ csrf_token() }}'}, function() {
            loadEditVariants();
        });
    });
    $('#edit-add-variant').on('click', function() {
        let vid = $('#edit-variant-select').val();
        if (vid) {
            $.post(`/orders/${orderId}/add-variant`, {variant_id: vid, _token: '{{ csrf_token() }}'}, function() {
                $('#edit-variant-select').val('');
                loadEditVariants();
            });
        }
    });
});
</script>
@endpush
@endsection
