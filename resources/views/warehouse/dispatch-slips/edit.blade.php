@extends($dispatchLayout ?? 'layouts.warehouse')

@section('title', 'Sửa '.$dispatchSlip->code)

@push('styles')
<style>
.dispatch-edit-panel{border:1px solid #dbe5e3;border-radius:12px;background:#fff;padding:18px}.dispatch-edit-list{max-height:390px;overflow:auto;border:1px solid #e2e8f0;border-radius:9px}.dispatch-edit-row{display:grid;grid-template-columns:24px minmax(0,1fr) auto;gap:9px;align-items:start;padding:10px 12px;border-bottom:1px solid #eef2f7}.dispatch-edit-row:last-child{border-bottom:0}.dispatch-edit-row.is-hidden{display:none}.dispatch-edit-empty{padding:22px;text-align:center;color:#64748b}
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
    <div>
        <a href="{{ route($dispatchRoutePrefix.'.show', $dispatchSlip) }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Quay lại phiếu</a>
        <h4 class="fw-bold mt-1 mb-1">Sửa {{ $dispatchSlip->code }}</h4>
        <div class="text-muted">Chỉ phiếu đang mở mới được thay đổi nội dung bàn giao.</div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger"><strong>Chưa thể cập nhật:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route($dispatchRoutePrefix.'.update', $dispatchSlip) }}" id="dispatchEditForm" class="dispatch-edit-panel">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-lg-3"><label class="form-label fw-semibold">Kho xuất</label><input class="form-control" value="{{ $dispatchSlip->sourceWarehouse?->name }}" readonly></div>
        <div class="col-lg-3"><label class="form-label fw-semibold">Kho nhận</label><select name="target_warehouse_id" id="dispatchEditTarget" class="form-select" required><option value="">-- Chọn kho nhận --</option>@foreach($targetWarehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((int) old('target_warehouse_id', $dispatchSlip->target_warehouse_id) === (int) $warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
        <div class="col-lg-3"><label class="form-label fw-semibold">Tài xế điều chuyển</label><select name="shipper_id" id="dispatchEditShipper" class="form-select" required><option value="">-- Chọn tài xế --</option>@foreach($shippers as $shipper)<option value="{{ $shipper->id }}" @selected((int) old('shipper_id', $dispatchSlip->shipper_id) === (int) $shipper->id)>{{ $shipper->short_name ?: $shipper->name }}</option>@endforeach</select></div>
        <div class="col-lg-3"><label class="form-label fw-semibold">Ngày nghiệp vụ</label><input type="date" name="business_date" class="form-control" value="{{ old('business_date', $dispatchSlip->business_date->toDateString()) }}" required></div>
    </div>

    @php
        $selectedOrderTransfers = collect(old('order_transfer_ids', $dispatchSlip->entries->pluck('order_transfer_id')->filter()->all()))->map(fn ($id) => (int) $id)->all();
        $selectedWarehouseTransfers = collect(old('warehouse_transfer_ids', $dispatchSlip->entries->pluck('warehouse_transfer_id')->filter()->all()))->map(fn ($id) => (int) $id)->all();
        $selectedInventoryTransfers = collect(old('inventory_transfer_ids', $dispatchSlip->entries->pluck('inventory_transfer_id')->filter()->all()))->map(fn ($id) => (int) $id)->all();
    @endphp
    <div class="row g-3 mt-1">
        <div class="col-xl-4">
            <div class="d-flex justify-content-between align-items-center mb-2"><div><strong>Nhóm đơn đã đóng gói</strong><div class="small text-muted">Chọn các nhóm cùng kho nhận và tài xế.</div></div><span class="badge bg-light text-dark border">{{ $orderTransfers->count() }} nhóm</span></div>
            <div class="dispatch-edit-list" data-edit-list>
                @forelse($orderTransfers as $transfer)
                    <label class="dispatch-edit-row" data-target="{{ $transfer->warehouse_id }}" data-shipper="{{ $transfer->shipper_id }}"><input type="checkbox" class="form-check-input mt-1 dispatch-edit-check" name="order_transfer_ids[]" value="{{ $transfer->id }}" @checked(in_array($transfer->id, $selectedOrderTransfers, true))><span><strong>Nhóm đơn #{{ $transfer->id }}</strong><span class="d-block small text-muted">{{ $transfer->orders->count() }} đơn · SL: {{ number_format((int) $transfer->orders->sum(fn ($order) => $order->items->sum('quantity'))) }}</span><span class="d-block small">{{ $transfer->orders->pluck('code')->filter()->join(', ') }}</span></span><span class="small text-end">{{ $transfer->shipper?->short_name ?: $transfer->shipper?->name }}<br>{{ $transfer->warehouse?->name }}</span></label>
                @empty<div class="dispatch-edit-empty">Không có nhóm đơn khả dụng.</div>@endforelse
                <div class="dispatch-edit-empty d-none" data-filter-empty>Không có nhóm phù hợp kho nhận và tài xế đã chọn.</div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="d-flex justify-content-between align-items-center mb-2"><div><strong>Đơn giao tài xế riêng</strong><div class="small text-muted">Các phiếu tạo trực tiếp từ đơn.</div></div><span class="badge bg-light text-dark border">{{ $warehouseTransfers->count() }} phiếu</span></div>
            <div class="dispatch-edit-list" data-edit-list>
                @forelse($warehouseTransfers as $transfer)
                    <label class="dispatch-edit-row" data-target="{{ $transfer->target_warehouse_id }}" data-shipper="{{ $transfer->shipper_id }}"><input type="checkbox" class="form-check-input mt-1 dispatch-edit-check" name="warehouse_transfer_ids[]" value="{{ $transfer->id }}" @checked(in_array($transfer->id, $selectedWarehouseTransfers, true))><span><strong>{{ $transfer->order?->code ?: 'Đơn #'.$transfer->order_id }}</strong><span class="d-block small text-muted">{{ $transfer->order?->customer?->name ?: 'Không rõ khách' }} · SL: {{ number_format((int) $transfer->order?->items?->sum('quantity')) }}</span></span><span class="small text-end">{{ $transfer->shipper?->short_name ?: $transfer->shipper?->name }}<br>{{ $transfer->targetWarehouse?->name }}</span></label>
                @empty<div class="dispatch-edit-empty">Không có đơn riêng khả dụng.</div>@endforelse
                <div class="dispatch-edit-empty d-none" data-filter-empty>Không có đơn phù hợp kho nhận và tài xế đã chọn.</div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="d-flex justify-content-between align-items-center mb-2"><div><strong>Hàng điều chuyển</strong><div class="small text-muted">Các phiếu hàng chưa thuộc phiếu tổng khác.</div></div><span class="badge bg-light text-dark border">{{ $inventoryTransfers->count() }} phiếu</span></div>
            <div class="dispatch-edit-list" data-edit-list>
                @forelse($inventoryTransfers as $transfer)
                    <label class="dispatch-edit-row" data-target="{{ $transfer->target_warehouse_id }}"><input type="checkbox" class="form-check-input mt-1 dispatch-edit-check" name="inventory_transfer_ids[]" value="{{ $transfer->id }}" @checked(in_array($transfer->id, $selectedInventoryTransfers, true))><span><strong>{{ $transfer->transfer_code ?: '#'.$transfer->id }}</strong><span class="d-block small text-muted">{{ $transfer->items->count() }} mặt hàng · SL: {{ number_format((int) $transfer->items->sum('quantity')) }}</span></span><span class="small text-end">{{ $transfer->targetWarehouse?->name }}</span></label>
                @empty<div class="dispatch-edit-empty">Không có phiếu hàng khả dụng.</div>@endforelse
                <div class="dispatch-edit-empty d-none" data-filter-empty>Không có phiếu hàng phù hợp kho nhận đã chọn.</div>
            </div>
        </div>
    </div>

    <div class="mt-3"><label class="form-label fw-semibold">Ghi chú bàn giao</label><textarea name="notes" class="form-control" rows="3" maxlength="2000" placeholder="Số kiện, lưu ý bảo quản, thông tin bàn giao...">{{ old('notes', $dispatchSlip->notes) }}</textarea></div>
    <div class="d-flex justify-content-end gap-2 mt-3"><a href="{{ route($dispatchRoutePrefix.'.show', $dispatchSlip) }}" class="btn btn-outline-secondary">Hủy</a><button class="btn btn-primary fw-bold" type="submit"><i class="bi bi-check2-circle me-1"></i>Lưu thay đổi</button></div>
</form>

<script>
(() => {
    const target = document.getElementById('dispatchEditTarget');
    const shipper = document.getElementById('dispatchEditShipper');
    const refresh = () => document.querySelectorAll('[data-edit-list]').forEach(list => {
        let visibleCount = 0;
        list.querySelectorAll('.dispatch-edit-row').forEach(row => {
            const visible = (!target.value || row.dataset.target === target.value)
                && (!row.dataset.shipper || !shipper.value || row.dataset.shipper === shipper.value);
            row.classList.toggle('is-hidden', !visible);
            if (!visible) row.querySelector('input').checked = false;
            if (visible) visibleCount++;
        });
        list.querySelector('[data-filter-empty]')?.classList.toggle('d-none', visibleCount > 0);
    });
    target.addEventListener('change', refresh);
    shipper.addEventListener('change', refresh);
    refresh();
    document.getElementById('dispatchEditForm').addEventListener('submit', event => {
        if (!document.querySelector('.dispatch-edit-check:checked')) {
            event.preventDefault();
            alert('Phiếu phải có ít nhất một nội dung bàn giao.');
        }
    });
})();
</script>
@endsection
