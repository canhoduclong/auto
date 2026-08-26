@extends('layouts.warehouse')

@section('title', 'Phiếu xuất kho tổng')

@push('styles')
<style>
.dispatch-hero,.dispatch-panel{border:1px solid #dbe5e3;border-radius:12px;background:#fff}.dispatch-hero{padding:18px;background:linear-gradient(135deg,#0f766e,#115e59);color:#fff}.dispatch-panel{padding:16px}.dispatch-source-list{max-height:440px;overflow:auto;border:1px solid #e2e8f0;border-radius:9px}.dispatch-source-row{display:grid;grid-template-columns:24px minmax(0,1fr) auto;gap:9px;align-items:start;padding:10px 12px;border-bottom:1px solid #eef2f7}.dispatch-source-row:last-child{border-bottom:0}.dispatch-source-row.is-hidden{display:none}.dispatch-order-list{margin-top:8px;border-top:1px dashed #dbe5e3}.dispatch-order-line{display:grid;grid-template-columns:minmax(105px,.8fr) minmax(150px,1.35fr) auto;gap:8px;padding:7px 0;border-bottom:1px dashed #edf2f7}.dispatch-order-line:last-child{border-bottom:0}.dispatch-order-products{font-size:.78rem;color:#64748b}.dispatch-quantity{white-space:nowrap;text-align:right;font-size:.8rem}.dispatch-slip-card{border:1px solid #dbe5e3;border-left:4px solid #0f766e;border-radius:10px;padding:13px;background:#fff}.dispatch-progress{height:7px;border-radius:99px;background:#e2e8f0;overflow:hidden}.dispatch-progress>span{display:block;height:100%;background:#0f766e}.dispatch-empty{padding:24px;text-align:center;color:#64748b}@media(max-width:767.98px){.dispatch-order-line{grid-template-columns:1fr auto}.dispatch-order-products{grid-column:1/-1}.dispatch-source-row{grid-template-columns:24px minmax(0,1fr)}.dispatch-source-row>span:last-child{grid-column:2;text-align:left!important}}
</style>
@endpush

@section('content')
<div class="dispatch-hero mb-3 d-flex justify-content-between align-items-start gap-3 flex-wrap">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Phiếu xuất kho tổng</h4>
        <div class="small opacity-75">Gom đơn hoàn thiện và hàng điều chuyển theo tài xế để bàn giao, in và đối chiếu.</div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger"><strong>Chưa thể lập phiếu:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="dispatch-panel mb-4">
    <h6 class="fw-bold mb-3">Lập phiếu mới</h6>
    <form method="POST" action="{{ route('warehouse.dispatch-slips.store') }}" id="dispatchCreateForm">
        @csrf
        <div class="row g-3">
            <div class="col-lg-3">
                <label class="form-label fw-semibold">Kho xuất</label>
                @if($managedWarehouseId)
                    <input type="hidden" name="source_warehouse_id" value="{{ $managedWarehouseId }}">
                    <input class="form-control" value="{{ $sourceWarehouses->firstWhere('id', $managedWarehouseId)?->name ?? 'Kho hiện tại' }}" readonly>
                @else
                    <select name="source_warehouse_id" class="form-select" onchange="location.href='{{ route('warehouse.dispatch-slips.index') }}?source_warehouse_id='+this.value">
                        @foreach($sourceWarehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($sourceWarehouseId === $warehouse->id)>{{ $warehouse->name }}</option>@endforeach
                    </select>
                @endif
            </div>
            <div class="col-lg-3">
                <label class="form-label fw-semibold">Kho nhận</label>
                <select name="target_warehouse_id" id="dispatchTarget" class="form-select" required>
                    <option value="">-- Chọn kho nhận --</option>
                    @foreach($targetWarehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected(old('target_warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label fw-semibold">Tài xế điều chuyển</label>
                <select name="shipper_id" id="dispatchShipper" class="form-select" required>
                    <option value="">-- Chọn tài xế --</option>
                    @foreach($shippers as $shipper)<option value="{{ $shipper->id }}" @selected(old('shipper_id') == $shipper->id)>{{ $shipper->short_name ?: $shipper->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label fw-semibold">Ngày nghiệp vụ</label>
                <input type="date" name="business_date" class="form-control" value="{{ old('business_date', now()->toDateString()) }}" required>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-xl-5">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div><strong>Phiếu điều chuyển đơn</strong><div class="small text-muted">Hiển thị đầy đủ từng đơn và số lượng trong nhóm đã đóng gói.</div></div>
                    <span class="badge bg-light text-dark border">{{ $orderTransfers->sum(fn ($transfer) => $transfer->orders->count()) }} đơn / {{ $orderTransfers->count() }} nhóm</span>
                </div>
                <div class="dispatch-source-list" id="dispatchOrderSources">
                    @forelse($orderTransfers as $transfer)
                        @php
                            $groupQuantity = $transfer->orders->sum(fn ($order) => $order->items->sum('quantity'));
                            $groupWeight = $transfer->orders->sum(fn ($order) => (float) $order->warehouseTransfers->first()?->packed_total_weight);
                        @endphp
                        <label class="dispatch-source-row" data-target="{{ $transfer->warehouse_id }}" data-shipper="{{ $transfer->shipper_id }}">
                            <input type="checkbox" class="form-check-input mt-1 dispatch-entry-check" name="order_transfer_ids[]" value="{{ $transfer->id }}" @checked(in_array($transfer->id, old('order_transfer_ids', [])))>
                            <span>
                                <strong>Nhóm đơn #{{ $transfer->id }}</strong>
                                <span class="d-block small text-muted">{{ $transfer->orders->count() }} đơn · Tổng SL: {{ number_format($groupQuantity) }} · {{ number_format($groupWeight, 3, ',', '.') }} kg</span>
                                <span class="dispatch-order-list d-block">
                                    @foreach($transfer->orders as $order)
                                        @php
                                            $movement = $order->warehouseTransfers->first();
                                            $products = $order->items->map(function ($item) {
                                                $name = $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm';
                                                $variant = collect([$item->variant?->sku, $item->variant?->size])->filter()->join('/');

                                                return $name.($variant ? ' ('.$variant.')' : '').' × '.number_format((int) $item->quantity);
                                            })->join(', ');
                                        @endphp
                                        <span class="dispatch-order-line">
                                            <span><strong>{{ $order->code ?: 'Đơn #'.$order->id }}</strong><span class="d-block small text-muted">{{ $order->customer?->name ?: 'Không rõ khách' }}</span></span>
                                            <span class="dispatch-order-products">{{ $products ?: 'Chưa có chi tiết hàng hóa' }}</span>
                                            <span class="dispatch-quantity"><strong>SL: {{ number_format((int) $order->items->sum('quantity')) }}</strong><br>{{ number_format((float) $movement?->packed_total_weight, 3, ',', '.') }} kg</span>
                                        </span>
                                    @endforeach
                                </span>
                            </span>
                            <span class="small text-end">{{ $transfer->shipper?->short_name ?: $transfer->shipper?->name }}<br>{{ $transfer->warehouse?->name }}</span>
                        </label>
                    @empty
                        <div class="dispatch-empty">Không có nhóm đơn đang chờ tài xế nhận.</div>
                    @endforelse
                    <div class="dispatch-empty d-none" data-filter-empty>Chọn đúng kho nhận và tài xế để xem nhóm đơn.</div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div><strong>Đơn giao tài xế riêng</strong><div class="small text-muted">Phiếu điều chuyển tạo trực tiếp từ đơn đã đóng gói.</div></div>
                    <span class="badge bg-light text-dark border">{{ $warehouseTransfers->count() }} phiếu</span>
                </div>
                <div class="dispatch-source-list" id="dispatchWarehouseSources">
                    @forelse($warehouseTransfers as $transfer)
                        <label class="dispatch-source-row" data-target="{{ $transfer->target_warehouse_id }}" data-shipper="{{ $transfer->shipper_id }}">
                            <input type="checkbox" class="form-check-input mt-1 dispatch-entry-check" name="warehouse_transfer_ids[]" value="{{ $transfer->id }}" @checked(in_array($transfer->id, old('warehouse_transfer_ids', [])))>
                            <span><strong>{{ $transfer->order?->code ?: 'Đơn #'.$transfer->order_id }}</strong><span class="d-block small text-muted">{{ $transfer->order?->customer?->name ?: 'Không rõ khách' }} · SL: {{ number_format((int) $transfer->order?->items?->sum('quantity')) }} · {{ number_format((float) $transfer->packed_total_weight, 3, ',', '.') }} kg</span><span class="dispatch-order-products d-block mt-1">{{ $transfer->order?->items?->map(function ($item) { $name = $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm'; $variant = collect([$item->variant?->sku, $item->variant?->size])->filter()->join('/'); return $name.($variant ? ' ('.$variant.')' : '').' × '.number_format((int) $item->quantity); })->join(', ') ?: 'Chưa có chi tiết hàng hóa' }}</span></span>
                            <span class="small text-end">{{ $transfer->shipper?->short_name ?: $transfer->shipper?->name }}<br>{{ $transfer->targetWarehouse?->name }}</span>
                        </label>
                    @empty
                        <div class="dispatch-empty">Không có đơn giao tài xế riêng đang chờ nhận.</div>
                    @endforelse
                    <div class="dispatch-empty d-none" data-filter-empty>Chọn đúng kho nhận và tài xế để xem đơn.</div>
                </div>
            </div>
            <div class="col-xl-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div><strong>Hàng điều chuyển</strong><div class="small text-muted">Các phiếu hàng cùng kho nhận, chưa thuộc phiếu tổng khác.</div></div>
                    <span class="badge bg-light text-dark border">{{ $inventoryTransfers->count() }} phiếu khả dụng</span>
                </div>
                <div class="dispatch-source-list" id="dispatchInventorySources">
                    @forelse($inventoryTransfers as $transfer)
                        <label class="dispatch-source-row" data-target="{{ $transfer->target_warehouse_id }}">
                            <input type="checkbox" class="form-check-input mt-1 dispatch-entry-check" name="inventory_transfer_ids[]" value="{{ $transfer->id }}" @checked(in_array($transfer->id, old('inventory_transfer_ids', [])))>
                            <span><strong>{{ $transfer->transfer_code ?: '#'.$transfer->id }}</strong><span class="d-block small text-muted">{{ $transfer->items->count() }} mặt hàng · {{ number_format((float) $transfer->items->sum('weight_kg'), 3, ',', '.') }} kg</span></span>
                            <span class="small text-end">{{ $transfer->targetWarehouse?->name }}</span>
                        </label>
                    @empty
                        <div class="dispatch-empty">Không có phiếu hàng đang chờ tiếp nhận.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1 align-items-end">
            <div class="col-lg-9"><label class="form-label fw-semibold">Ghi chú bàn giao</label><textarea name="notes" class="form-control" rows="2" placeholder="Số kiện, lưu ý bảo quản, thông tin bàn giao...">{{ old('notes') }}</textarea></div>
            <div class="col-lg-3 d-grid"><button class="btn btn-success fw-bold" type="submit"><i class="bi bi-plus-circle me-1"></i>Lập phiếu tổng</button></div>
        </div>
    </form>
</div>

<div class="dispatch-panel">
    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
        <h6 class="fw-bold mb-0">Tra cứu phiếu theo ngày</h6>
        <form method="GET" class="row g-2 align-items-end flex-grow-1 justify-content-end">
            <div class="col-auto"><label class="small">Từ ngày</label><input type="date" name="from_date" value="{{ $from }}" class="form-control form-control-sm"></div>
            <div class="col-auto"><label class="small">Đến ngày</label><input type="date" name="to_date" value="{{ $to }}" class="form-control form-control-sm"></div>
            <div class="col-auto"><label class="small">Mã phiếu</label><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="PXKT-..."></div>
            <div class="col-auto"><button class="btn btn-primary btn-sm">Lọc</button></div>
        </form>
    </div>
    <div class="d-grid gap-2">
        @forelse($slips as $slip)
            @php $percent = $slip->entry_total ? round($slip->entry_received * 100 / $slip->entry_total) : 0; @endphp
            <div class="dispatch-slip-card">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div><a class="fw-bold text-decoration-none" href="{{ route('warehouse.dispatch-slips.show', $slip) }}">{{ $slip->code }}</a><div class="small text-muted">{{ $slip->business_date->format('d/m/Y') }} · {{ $slip->sourceWarehouse?->name }} → {{ $slip->targetWarehouse?->name }}</div></div>
                    <span class="badge {{ $slip->status === 'draft' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $slip->status === 'draft' ? 'Đang mở' : 'Đã chốt' }}</span>
                </div>
                <div class="row g-2 mt-1 small"><div class="col-md-4">Tài xế: <strong>{{ $slip->shipper?->short_name ?: $slip->shipper?->name }}</strong></div><div class="col-md-4">Nội dung: <strong>{{ $slip->entry_total }} mục</strong></div><div class="col-md-4">{{ $slip->progress_label }}</div></div>
                <div class="dispatch-progress mt-2"><span style="width:{{ $percent }}%"></span></div>
            </div>
        @empty
            <div class="dispatch-empty">Chưa có phiếu trong thời gian đã chọn.</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $slips->links() }}</div>
</div>

<script>
(() => {
    const target = document.getElementById('dispatchTarget');
    const shipper = document.getElementById('dispatchShipper');
    const refresh = () => {
        document.querySelectorAll('#dispatchOrderSources .dispatch-source-row').forEach(row => {
            const visible = (!target.value || row.dataset.target === target.value) && (!shipper.value || row.dataset.shipper === shipper.value);
            row.classList.toggle('is-hidden', !visible);
            if (!visible) row.querySelector('input').checked = false;
        });
        document.querySelectorAll('#dispatchInventorySources .dispatch-source-row').forEach(row => {
            const visible = !target.value || row.dataset.target === target.value;
            row.classList.toggle('is-hidden', !visible);
            if (!visible) row.querySelector('input').checked = false;
        });
        document.querySelectorAll('#dispatchWarehouseSources .dispatch-source-row').forEach(row => {
            const visible = (!target.value || row.dataset.target === target.value) && (!shipper.value || row.dataset.shipper === shipper.value);
            row.classList.toggle('is-hidden', !visible);
            if (!visible) row.querySelector('input').checked = false;
        });
    };
    target?.addEventListener('change', refresh);
    shipper?.addEventListener('change', refresh);
    refresh();
    document.getElementById('dispatchCreateForm')?.addEventListener('submit', event => {
        if (!document.querySelector('.dispatch-entry-check:checked')) {
            event.preventDefault();
            alert('Vui lòng chọn ít nhất một nhóm đơn hoặc phiếu điều chuyển hàng.');
        }
    });
})();
</script>
@endsection
