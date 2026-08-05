@extends('layouts.accounting')

@section('title', 'Mô phỏng toàn bộ quy trình đơn hàng')
@section('subtitle', 'Thực hiện tuần tự toàn bộ quy trình trên một màn hình')

@section('accounting_content')
@php $wizardStep = max(1, min(6, (int) request('step', old('wizard_step', 1)))); @endphp
<style>
.wf-card{border:0;border-radius:14px;box-shadow:0 3px 16px rgba(15,23,42,.07)}
.wf-action{border-left:4px solid #2563eb}.wf-action.green{border-color:#16a34a}.wf-action.orange{border-color:#f59e0b}.wf-action.purple{border-color:#7c3aed}
.wf-nav{display:grid;grid-template-columns:repeat(6,minmax(135px,1fr));gap:8px;overflow-x:auto}
.wf-nav-btn{min-width:135px;border:1px solid #dbe4ee;border-radius:12px;padding:10px;text-align:left;background:#fff;color:#475569}
.wf-nav-btn .n{width:27px;height:27px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#e2e8f0;font-weight:800;margin-right:5px}
.wf-nav-btn.active{border-color:#2563eb;background:#eff6ff;color:#1d4ed8}.wf-nav-btn.active .n{background:#2563eb;color:#fff}
.wf-nav-btn.done{border-color:#86efac;background:#f0fdf4}.wf-nav-btn.done .n{background:#16a34a;color:#fff}
.wf-panel-title{display:flex;align-items:center;gap:10px;margin-bottom:16px}.wf-panel-title .n{width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#2563eb;color:#fff;font-weight:800}
.wf-form-list{max-height:210px;overflow:auto}.wf-table th{white-space:nowrap;font-size:12px}.wf-table td{font-size:13px;vertical-align:middle}
.wf-bulk-text{min-height:235px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px}.wf-preview td,.wf-preview th{font-size:12px;white-space:nowrap}
.wf-stock-list{max-height:430px;overflow:auto;border:1px solid #dbe4ee;border-radius:10px}
.wf-stock-columns{padding:7px 9px;background:#f1f5f9;border:1px solid #dbe4ee;border-bottom:0;border-radius:10px 10px 0 0;color:#475569;font-size:12px;font-weight:700}
.wf-stock-columns+.wf-stock-list{border-radius:0 0 10px 10px}
.wf-stock-row{background:#fff;border:0;border-bottom:1px solid #e2e8f0;border-radius:0;padding:6px 8px}.wf-stock-row:last-child{border-bottom:0}
.wf-stock-row .form-label{display:none}.wf-stock-row .form-control,.wf-stock-row .form-select,.wf-stock-row .btn{min-height:34px;padding-top:.3rem;padding-bottom:.3rem}
.wf-product-picker{max-height:55vh;overflow:auto}.wf-product-picker thead{position:sticky;top:0;z-index:2}.wf-product-picker td{vertical-align:middle}
.wf-inventory-table{max-height:360px;overflow:auto}.wf-inventory-table thead{position:sticky;top:0;z-index:1}
.wf-stock-ok{color:#15803d;font-weight:700}.wf-stock-short{color:#b91c1c;font-weight:800}.wf-shortage-order{border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:12px;background:#fff}
.wf-adjust-row{padding:8px 0;border-top:1px solid #e5e7eb}.wf-adjust-row:first-child{border-top:0}.wf-available-hint{font-size:12px;color:#64748b}.wf-available-hint.is-short{color:#b91c1c;font-weight:700}
.wf-stocktake-card{border:1px solid #bae6fd;border-left:4px solid #0284c7}.wf-stocktake-input{min-width:125px;text-align:right}.wf-fulfillment-short{background:#fff7ed}.wf-stocktake-help{border-radius:10px;background:#f0f9ff;color:#075985}
.wf-stocktake-card>summary{cursor:pointer;list-style:none}.wf-stocktake-card>summary::-webkit-details-marker{display:none}.wf-stocktake-toggle{color:#0369a1;font-size:.8rem;font-weight:700}.wf-stocktake-toggle .bi-chevron-down{transition:transform .2s ease}.wf-stocktake-card[open] .wf-stocktake-toggle .bi-chevron-down{transform:rotate(180deg)}
.wf-select-all-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:.5rem 0;padding:.45rem .65rem;border:1px solid #dbe4ee;border-radius:8px;background:#f8fafc}.wf-select-all-btn{padding:0;border:0;background:transparent;color:#1d4ed8;font-size:.82rem;font-weight:750}.wf-select-all-count{color:#64748b;font-size:.75rem}
@media(max-width:991px){.wf-nav{grid-template-columns:repeat(6,155px)}.wf-stock-columns{display:none}.wf-stock-list{border-radius:10px}.wf-stock-row{padding:10px}.wf-stock-row .form-label{display:block}.wf-stock-row .form-control,.wf-stock-row .form-select,.wf-stock-row .btn{min-height:38px}}
</style>

<div class="container-fluid py-3" id="workflow-simulation-page" data-start-step="{{ $wizardStep }}">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div><h3 class="mb-1">Mô phỏng toàn bộ quy trình đơn hàng</h3><div class="text-muted">Đi theo từng bước; nút Tiếp tục chỉ chuyển công đoạn, không tự ghi dữ liệu.</div></div>
        <div class="text-end"><div class="small text-muted">Ngày đang chọn</div><b class="text-primary">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</b></div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card wf-card mb-3"><div class="card-body"><div class="wf-nav">
        @php
            $wizardNav = [
                [1,'Chọn ngày',\Carbon\Carbon::parse($date)->format('d/m')],
                [2,'Nhập kho',$stats['stock_in'].' phiếu'],
                [3,'Sale lên đơn',$stats['orders'].' đơn'],
                [4,'Duyệt đơn',$stats['manager'].'/'.$stats['orders']],
                [5,'Kho & điều chuyển',$stats['transfer_received'].'/'.$stats['orders']],
                [6,'Giao & kế toán',$stats['accounted'].'/'.$stats['orders']],
            ];
        @endphp
        @foreach($wizardNav as [$number,$label,$meta])
        <button type="button" class="wf-nav-btn" data-wf-go="{{ $number }}"><span class="n">{{ $number }}</span><b>{{ $label }}</b><div class="small mt-1">{{ $meta }}</div></button>
        @endforeach
    </div></div></div>

    <section data-wf-panel="1">
        <div class="card wf-card"><div class="card-body p-4">
            <div class="wf-panel-title"><span class="n">1</span><div><h5 class="mb-0">Chọn ngày vận hành</h5><div class="small text-muted">Dữ liệu của ngày được tải ngầm, không tải lại toàn bộ trang.</div></div></div>
            <form id="wf-date-form" class="row g-3 align-items-end" action="{{ route('accounting.workflow-simulation.index') }}">
                <div class="col-md-5"><label class="form-label fw-semibold">Ngày thực hiện</label><input class="form-control form-control-lg" type="date" name="date" value="{{ $date }}" required></div>
                <div class="col-md-7"><button class="btn btn-primary btn-lg" type="submit"><span class="wf-date-label">Tiếp tục: Nhập kho</span><span class="spinner-border spinner-border-sm ms-1 d-none" aria-hidden="true"></span><i class="bi bi-arrow-right ms-1"></i></button></div>
            </form>
        </div></div>
    </section>

    <section data-wf-panel="2" hidden>
        <div class="card wf-card wf-action green"><div class="card-body p-4">
            <div class="wf-panel-title"><span class="n">2</span><div><h5 class="mb-0">Nhập kho nhiều sản phẩm</h5><div class="small text-muted">Một lần lưu tạo một phiếu nhập gồm tất cả sản phẩm bên dưới.</div></div></div>
            <form method="POST" action="{{ route('accounting.workflow-simulation.stock-in') }}" id="wf-stock-form">@csrf
                <input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="2"><input type="hidden" name="next_step" value="3">
                <div class="row g-2 mb-3"><div class="col-md-6"><label class="form-label fw-semibold">Kho nhập</label><select class="form-select" name="warehouse_id" required><option value="">Chọn kho</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string)old('warehouse_id')===(string)$warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div></div>
                <div class="wf-stock-columns"><div class="row g-2"><div class="col-lg-6">Sản phẩm / quy cách</div><div class="col-lg-2">Số lượng</div><div class="col-lg-3">Giá vốn / đơn vị</div><div class="col-lg-1 text-center">Xóa</div></div></div>
                <div id="wf-stock-items" class="wf-stock-list">
                    @foreach(old('items', [['product_variant_id'=>'','quantity'=>10,'unit_cost'=>0]]) as $itemIndex => $stockItem)
                    <div class="wf-stock-row" data-stock-row><div class="row g-2 align-items-end">
                        <div class="col-lg-6"><label class="form-label small">Sản phẩm / quy cách</label><select class="form-select" name="items[{{ $itemIndex }}][product_variant_id]" required><option value="">Chọn sản phẩm</option>@foreach($variants as $variant)<option value="{{ $variant->id }}" @selected((string)($stockItem['product_variant_id'] ?? '')===(string)$variant->id)>{{ $variant->product?->name }} — {{ $variant->name ?: $variant->sku }}</option>@endforeach</select></div>
                        <div class="col-5 col-lg-2"><label class="form-label small">Số lượng</label><input class="form-control" type="number" min="1" name="items[{{ $itemIndex }}][quantity]" value="{{ $stockItem['quantity'] ?? 10 }}" required></div>
                        <div class="col-5 col-lg-3"><label class="form-label small">Giá vốn / đơn vị</label><input class="form-control" type="number" min="0" step="1000" name="items[{{ $itemIndex }}][unit_cost]" value="{{ $stockItem['unit_cost'] ?? 0 }}" required></div>
                        <div class="col-2 col-lg-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-stock title="Bỏ dòng"><i class="bi bi-x-lg"></i></button></div>
                    </div></div>
                    @endforeach
                </div>
                <div class="d-flex flex-wrap justify-content-between gap-2 mt-3"><div class="d-flex flex-wrap gap-2"><button class="btn btn-outline-primary" type="button" id="wf-add-stock"><i class="bi bi-plus-lg me-1"></i>Thêm một sản phẩm</button><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#wf-product-picker-modal"><i class="bi bi-ui-checks-grid me-1"></i>Chọn nhiều sản phẩm</button></div><div class="d-flex gap-2"><button class="btn btn-outline-secondary" type="button" data-wf-go="3">Bỏ qua nhập kho</button><button class="btn btn-success" @disabled($variants->isEmpty() || $warehouses->isEmpty())><i class="bi bi-box-arrow-in-down me-1"></i>Nhập kho &amp; sang bước 3</button></div></div>
            </form>
        </div></div>

        @php
            $shortageOrders = $orders->filter(function ($order) use ($orderInventory) {
                return !($orderInventory->get($order->id)['sufficient'] ?? false)
                    && $order->items->isNotEmpty()
                    && in_array((string) $order->status, [
                        'pending',
                        \App\Models\Order::STATUS_PENDING_LEADER_APPROVAL,
                        \App\Models\Order::STATUS_PENDING_MANAGER_APPROVAL,
                        \App\Models\Order::STATUS_APPROVED,
                        \App\Models\Order::STATUS_READY_TO_PACK,
                        \App\Models\Order::STATUS_PACKING,
                    ], true);
            });
            $fulfillmentByWarehouse = $fulfillmentRows->groupBy('warehouse_id');
        @endphp
        <details class="card wf-card wf-stocktake-card mt-3">
            <summary class="card-header bg-white py-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div><b><i class="bi bi-clipboard2-check me-1 text-info"></i>Kiểm kê kho để hoàn thiện đơn</b><div class="small text-muted">Kiểm đếm thực tế các SKU liên quan, ghi nhận chênh lệch và tự kiểm tra lại khả năng đóng toàn bộ đơn trong ngày.</div></div>
                <span class="d-flex align-items-center gap-2"><span class="badge {{ $fulfillmentRows->sum('shortage') > 0 ? 'bg-danger' : 'bg-success' }}">Thiếu tổng {{ number_format($fulfillmentRows->sum('shortage'), 0, ',', '.') }}</span><span class="wf-stocktake-toggle">Mở / thu gọn <i class="bi bi-chevron-down ms-1"></i></span></span>
            </summary>
            <div class="card-body">
                <div class="wf-stocktake-help p-3 mb-3 small"><i class="bi bi-info-circle me-1"></i><b>Quy trình:</b> cân/đếm hàng thực tế → nhập cột “Thực đếm” → hoàn tất kiểm kê. Hệ thống tạo phiếu kiểm kê, lịch sử điều chỉnh và cập nhật lại trạng thái đủ/thiếu của đơn. Không nhập số ước lượng nếu chưa kiểm đếm thực tế.</div>
                @forelse($fulfillmentByWarehouse as $stocktakeWarehouseId => $warehouseRows)
                    <form method="POST" action="{{ route('accounting.workflow-simulation.stocktake') }}" class="border rounded mb-3" data-workflow-stocktake-form>
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">
                        <input type="hidden" name="warehouse_id" value="{{ $stocktakeWarehouseId }}">
                        <div class="p-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center"><b>{{ $warehouseRows->first()['warehouse_name'] }}</b><span class="small text-muted">{{ $warehouseRows->count() }} SKU phục vụ đơn</span></div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Sản phẩm / quy cách</th><th>Đơn liên quan</th><th class="text-end">Tồn hệ thống</th><th class="text-end">Đang giữ</th><th class="text-end">Tổng cần</th><th class="text-end">Thiếu</th><th class="text-end">Thực đếm</th></tr></thead>
                                <tbody>
                                @foreach($warehouseRows as $stocktakeIndex => $fulfillmentRow)
                                    <tr class="{{ $fulfillmentRow['shortage'] > 0 ? 'wf-fulfillment-short' : '' }}">
                                        <td><b>{{ $fulfillmentRow['product_name'] }}</b><div class="small text-muted">{{ $fulfillmentRow['variant_name'] }}</div></td>
                                        <td class="small">{{ implode(', ', $fulfillmentRow['orders']) }}</td>
                                        <td class="text-end">{{ number_format($fulfillmentRow['on_hand'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($fulfillmentRow['reserved'], 0, ',', '.') }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($fulfillmentRow['required'], 0, ',', '.') }}</td>
                                        <td class="text-end {{ $fulfillmentRow['shortage'] > 0 ? 'wf-stock-short' : 'wf-stock-ok' }}">{{ number_format($fulfillmentRow['shortage'], 0, ',', '.') }}</td>
                                        <td class="text-end">
                                            <input type="hidden" name="items[{{ $stocktakeIndex }}][product_variant_id]" value="{{ $fulfillmentRow['variant_id'] }}">
                                            <input type="hidden" name="items[{{ $stocktakeIndex }}][expected_quantity]" value="{{ $fulfillmentRow['on_hand'] }}">
                                            <input class="form-control form-control-sm wf-stocktake-input ms-auto" type="number" min="0" step="0.001" name="items[{{ $stocktakeIndex }}][counted_quantity]" placeholder="Nhập thực tế" aria-label="Số lượng thực đếm {{ $fulfillmentRow['product_name'] }} {{ $fulfillmentRow['variant_name'] }}">
                                            @if($fulfillmentRow['shortage'] > 0)<div class="small text-danger mt-1">Để đủ đơn cần thực có ≥ {{ number_format($fulfillmentRow['minimum_counted'], 0, ',', '.') }}</div>@endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top d-flex justify-content-between align-items-center gap-2 flex-wrap"><span class="small text-muted">Chỉ các dòng đã nhập “Thực đếm” mới được ghi vào phiếu.</span><button class="btn btn-info text-white" onclick="return confirm('Xác nhận số liệu đã được cân/đếm thực tế và cập nhật tồn kho?')"><i class="bi bi-check2-square me-1"></i>Hoàn tất kiểm kê &amp; kiểm tra lại đơn</button></div>
                    </form>
                @empty
                    <div class="text-center text-muted py-4">Chưa có sản phẩm từ đơn hàng ngày này để kiểm kê.</div>
                @endforelse
            </div>
        </details>
        <div class="row g-3 mt-1">
            <div class="col-xl-7">
                <div class="card wf-card h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center gap-2">
                        <div><b><i class="bi bi-boxes me-1 text-primary"></i>Tồn kho hiện tại</b><div class="small text-muted">Tồn khả dụng = tồn thực tế − số lượng đang giữ.</div></div>
                        <span class="badge bg-primary">{{ $inventoryRows->count() }} SKU/kho</span>
                    </div>
                    <div class="table-responsive wf-inventory-table">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Kho</th><th>Sản phẩm / quy cách</th><th class="text-end">Thực tế</th><th class="text-end">Đang giữ</th><th class="text-end">Khả dụng</th><th>Trạng thái</th></tr></thead>
                            <tbody>
                            @forelse($inventoryRows as $inventoryRow)
                                @php
                                    $isLowStock = $inventoryRow['available'] <= $inventoryRow['low_stock_threshold'];
                                @endphp
                                <tr>
                                    <td>{{ $inventoryRow['warehouse_name'] }}</td>
                                    <td><b>{{ $inventoryRow['product_name'] }}</b><div class="small text-muted">{{ $inventoryRow['variant_name'] }}</div></td>
                                    <td class="text-end">{{ number_format($inventoryRow['on_hand'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($inventoryRow['reserved'], 0, ',', '.') }}</td>
                                    <td class="text-end {{ $inventoryRow['available'] > 0 ? 'wf-stock-ok' : 'wf-stock-short' }}">{{ number_format($inventoryRow['available'], 0, ',', '.') }}</td>
                                    <td><span class="badge {{ $isLowStock ? 'bg-warning text-dark' : 'bg-success' }}">{{ $isLowStock ? 'Sắp hết' : 'Sẵn sàng' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có tồn kho tại các kho.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card wf-card h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center gap-2">
                        <div><b><i class="bi bi-exclamation-triangle me-1 text-danger"></i>Đơn thiếu hàng cần chỉnh</b><div class="small text-muted">Giảm số lượng, bỏ dòng hoặc đổi sang SKU còn tồn.</div></div>
                        <span class="badge {{ $shortageOrders->isEmpty() ? 'bg-success' : 'bg-danger' }}">{{ $shortageOrders->count() }} đơn</span>
                    </div>
                    <div class="card-body">
                        @forelse($shortageOrders as $shortageOrder)
                            @php
                                $stockStatus = $orderInventory->get($shortageOrder->id, []);
                            @endphp
                            <div class="wf-shortage-order p-3 mb-3">
                                <div class="d-flex justify-content-between gap-2 mb-2">
                                    <div><b>{{ $shortageOrder->code ?: '#'.$shortageOrder->id }}</b> — {{ $shortageOrder->customer?->name }}<div class="small text-muted">{{ $stockStatus['warehouse_name'] ?? 'Chưa chọn kho' }} · {{ $shortageOrder->status }}</div></div>
                                    <span class="badge bg-danger align-self-start">Thiếu hàng</span>
                                </div>
                                <div class="small mb-2">
                                    @foreach(($stockStatus['items'] ?? []) as $stockItem)
                                        <div class="d-flex justify-content-between gap-2 {{ $stockItem['sufficient'] ? 'text-muted' : 'wf-stock-short' }}">
                                            <span>{{ $stockItem['label'] }}</span>
                                            <span>Cần {{ number_format($stockItem['required'], 0, ',', '.') }} / Có {{ number_format($stockItem['available'], 0, ',', '.') }}@if(!$stockItem['sufficient']) · Thiếu {{ number_format($stockItem['shortage'], 0, ',', '.') }}@endif</span>
                                        </div>
                                    @endforeach
                                </div>
                                <form method="POST" action="{{ route('accounting.workflow-simulation.orders.adjust-stock', $shortageOrder) }}" data-adjust-stock-form>
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="date" value="{{ $date }}">
                                    <input type="hidden" name="return_step" value="2">
                                    @foreach($shortageOrder->items as $itemIndex => $orderItem)
                                        @php
                                            $available = $inventoryMap[((int) $shortageOrder->warehouse_id).':'.((int) $orderItem->product_variant_id)] ?? 0;
                                        @endphp
                                        <div class="wf-adjust-row" data-adjust-row data-warehouse-id="{{ $shortageOrder->warehouse_id }}">
                                            <input type="hidden" name="items[{{ $itemIndex }}][item_id]" value="{{ $orderItem->id }}">
                                            <label class="form-label small mb-1">Sản phẩm / quy cách</label>
                                            <select class="form-select form-select-sm mb-1" name="items[{{ $itemIndex }}][product_variant_id]" data-adjust-variant required>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" @selected((int) $orderItem->product_variant_id === (int) $variant->id)>{{ $variant->product?->name }} — {{ $variant->name ?: $variant->sku }}</option>
                                                @endforeach
                                            </select>
                                            <div class="row g-2 align-items-center">
                                                <div class="col-5"><input class="form-control form-control-sm" type="number" min="0" name="items[{{ $itemIndex }}][quantity]" value="{{ (int) $orderItem->quantity }}" data-adjust-quantity required></div>
                                                <div class="col-7"><div class="wf-available-hint {{ (int) $orderItem->quantity > $available ? 'is-short' : '' }}" data-available-hint>Khả dụng: {{ number_format($available, 0, ',', '.') }} · nhập 0 để bỏ</div></div>
                                            </div>
                                        </div>
                                    @endforeach
                                    <button class="btn btn-danger btn-sm mt-2" onclick="return confirm('Lưu thay đổi đơn và kiểm tra lại tồn kho?')"><i class="bi bi-pencil-square me-1"></i>Lưu đơn &amp; kiểm tra lại tồn</button>
                                </form>
                            </div>
                        @empty
                            <div class="text-center py-4"><i class="bi bi-check-circle-fill text-success fs-2"></i><div class="fw-semibold text-success mt-2">Các đơn hiện đủ tồn kho</div><div class="small text-muted">Có thể chuyển sang bước Kho &amp; điều chuyển để tiếp tục đóng hàng.</div></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <template id="wf-stock-template"><div class="wf-stock-row" data-stock-row><div class="row g-2 align-items-end"><div class="col-lg-6"><label class="form-label small">Sản phẩm / quy cách</label><select class="form-select" name="items[__INDEX__][product_variant_id]" required><option value="">Chọn sản phẩm</option>@foreach($variants as $variant)<option value="{{ $variant->id }}">{{ $variant->product?->name }} — {{ $variant->name ?: $variant->sku }}</option>@endforeach</select></div><div class="col-5 col-lg-2"><label class="form-label small">Số lượng</label><input class="form-control" type="number" min="1" name="items[__INDEX__][quantity]" value="10" required></div><div class="col-5 col-lg-3"><label class="form-label small">Giá vốn / đơn vị</label><input class="form-control" type="number" min="0" step="1000" name="items[__INDEX__][unit_cost]" value="0" required></div><div class="col-2 col-lg-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-stock><i class="bi bi-x-lg"></i></button></div></div></div></template>

        <div class="modal fade" id="wf-product-picker-modal" tabindex="-1" aria-labelledby="wf-product-picker-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
                <div class="modal-header"><div><h5 class="modal-title" id="wf-product-picker-title">Chọn nhiều sản phẩm nhập kho</h5><div class="small text-muted">Tick sản phẩm, nhập số lượng và giá vốn rồi bấm Thêm vào phiếu nhập.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                <div class="modal-body">
                    <div class="input-group mb-3"><span class="input-group-text"><i class="bi bi-search"></i></span><input class="form-control" id="wf-product-search" placeholder="Tìm tên sản phẩm, quy cách hoặc SKU..."><button class="btn btn-outline-secondary" type="button" id="wf-select-visible">Chọn tất cả đang hiển thị</button></div>
                    <div class="table-responsive border rounded wf-product-picker"><table class="table table-hover mb-0"><thead class="table-light"><tr><th style="width:48px"></th><th>Sản phẩm / quy cách</th><th style="width:150px">Số lượng</th><th style="width:190px">Giá vốn / đơn vị</th></tr></thead><tbody>
                        @foreach($variants as $variant)
                        @php $variantLabel=trim(($variant->product?->name ?? '').' — '.($variant->name ?: $variant->sku)); @endphp
                        <tr data-picker-row data-search="{{ mb_strtolower($variantLabel.' '.($variant->sku ?? '')) }}"><td><input class="form-check-input" type="checkbox" data-picker-check value="{{ $variant->id }}"></td><td><b>{{ $variant->product?->name }}</b><div class="small text-muted">{{ $variant->name ?: 'Không có quy cách' }}@if($variant->sku) · {{ $variant->sku }}@endif</div></td><td><input class="form-control form-control-sm" type="number" min="1" value="10" data-picker-quantity></td><td><input class="form-control form-control-sm" type="number" min="0" step="1000" value="0" data-picker-cost></td></tr>
                        @endforeach
                    </tbody></table></div>
                    @if($variants->isEmpty())<div class="text-center text-muted py-4">Chưa có sản phẩm để lựa chọn.</div>@endif
                </div>
                <div class="modal-footer justify-content-between"><span class="small text-muted"><b id="wf-picker-count">0</b> sản phẩm được chọn</span><div><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="wf-apply-products" @disabled($variants->isEmpty())><i class="bi bi-plus-circle me-1"></i>Thêm vào phiếu nhập</button></div></div>
            </div></div>
        </div>
    </section>

    <section data-wf-panel="3" hidden>
        <div class="wf-panel-title"><span class="n">3</span><div><h5 class="mb-0">Sale lên đơn</h5><div class="small text-muted">Chọn một trong hai cách; cả hai đều bổ sung đơn vào ngày {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}.</div></div></div>
        <div class="row g-3 align-items-stretch">
            <div class="col-xl-5" id="create-order-actions"><div class="card wf-card wf-action h-100"><div class="card-body"><h5>Lên từng đơn</h5><div class="small text-muted mb-3">Phù hợp khi cần bổ sung nhanh một đơn.</div>
                <form method="POST" action="{{ route('accounting.workflow-simulation.orders.create') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="3"><input type="hidden" name="next_step" value="3">
                    <div class="row g-2"><div class="col-md-6"><label class="form-label small">Khách hàng</label><select class="form-select" name="customer_id" required><option value="">Chọn khách</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label small">Sale phụ trách</label><select class="form-select" name="sale_id" required><option value="">Chọn sale</option>@foreach($sales as $sale)<option value="{{ $sale->id }}">{{ $sale->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label small">Kho xuất</label><select class="form-select" name="warehouse_id" required><option value="">Chọn kho</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label small">Sản phẩm</label><select class="form-select" name="product_variant_id" required><option value="">Chọn sản phẩm</option>@foreach($variants as $variant)<option value="{{ $variant->id }}">{{ $variant->product?->name }} — {{ $variant->name ?: $variant->sku }}</option>@endforeach</select></div><div class="col-6"><label class="form-label small">Số lượng</label><input class="form-control" type="number" min="1" name="quantity" value="1" required></div><div class="col-6"><label class="form-label small">Đơn giá</label><input class="form-control" type="number" min="0" step="1000" name="price" value="0" required></div></div>
                    <button class="btn btn-primary mt-3" @disabled($customers->isEmpty() || $sales->isEmpty() || $variants->isEmpty())><i class="bi bi-cart-plus me-1"></i>Tạo đơn</button>
                </form>
            </div></div></div>

            <div class="col-xl-7" id="bulk-order-actions"><div class="card wf-card wf-action h-100"><div class="card-body"><div class="d-flex justify-content-between gap-2"><div><h5>Lên đơn hàng loạt từ Excel / Google Sheets</h5><div class="small text-muted mb-3">Dán cả tiêu đề; hệ thống tự gom dòng theo ngày và khách hàng.</div></div>@if($bulkResult)<div class="text-nowrap"><span class="badge bg-dark">{{ $bulkResult['order_count'] ?? 0 }} đơn</span> <span class="badge bg-danger">{{ $bulkResult['counts']['error'] ?? 0 }} lỗi</span></div>@endif</div>
                <form method="POST" action="{{ route('accounting.workflow-simulation.orders.bulk') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="3"><input type="hidden" name="next_step" value="4">
                    <label class="form-label small">Kho xuất hàng</label><select class="form-select mb-2" name="warehouse_id" required><option value="">Chọn kho nguồn</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string)old('warehouse_id')===(string)$warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select>
                    <textarea class="form-control wf-bulk-text" name="text_data" required placeholder="Ngày tháng&#9;Tháng&#9;Mã KH&#9;Khách hàng&#9;NVKD&#9;DVT&#9;SL&#9;Kg/con&#9;Tổng&#9;Đơn giá&#9;Tổng tiền">{{ old('text_data') }}</textarea>
                    @if(!empty($bulkResult['sale_mappings'] ?? []))<div class="border rounded mt-2 p-2"><b class="small">Ánh xạ NVKD</b>@foreach($bulkResult['sale_mappings'] as $mapping)<div class="row g-2 align-items-center mt-1"><div class="col-4 small">{{ $mapping['imported_name'] }}</div><div class="col-8"><select class="form-select form-select-sm" name="sale_mapping[{{ $mapping['key'] }}]"><option value="">Chọn tài khoản</option>@foreach($bulkSales as $saleUser)<option value="{{ $saleUser->id }}" @selected((int)($mapping['selected_user_id'] ?? 0)===(int)$saleUser->id)>{{ $saleUser->short_name ? $saleUser->short_name.' — ' : '' }}{{ $saleUser->name }}</option>@endforeach</select></div></div>@endforeach</div>@endif
                    <div class="d-flex gap-2 mt-2"><button class="btn btn-outline-primary" name="text_action" value="preview"><i class="bi bi-eye me-1"></i>Kiểm tra</button>@if($bulkResult && ($bulkResult['counts']['error'] ?? 0)===0 && !($bulkResult['duplicate'] ?? false))<button class="btn btn-success" name="text_action" value="import" onclick="return confirm('Tạo {{ $bulkResult['order_count'] ?? 0 }} đơn chờ duyệt?')">Lên {{ $bulkResult['order_count'] ?? 0 }} đơn &amp; sang bước 4</button>@endif</div>
                </form>
                @if($bulkResult && !empty($bulkResult['rows']))<div class="table-responsive border rounded mt-3"><table class="table table-sm wf-preview mb-0"><thead><tr><th>Dòng</th><th>Mã KH</th><th>Khách hàng</th><th>NVKD</th><th>DVT</th><th>Tổng tiền</th><th>Kết quả</th></tr></thead><tbody>@foreach($bulkResult['rows'] as $row) @php $data=$row['data']; @endphp<tr><td>{{ $row['line'] }}</td><td>{{ $data['customer_code'] ?: '—' }}</td><td>{{ $data['customer_name'] }}</td><td>{{ $data['sale_name'] }}</td><td>{{ $data['unit'] }}</td><td>{{ number_format($data['total_amount'],0,',','.') }}</td><td class="{{ $row['action']==='error'?'text-danger':'text-success' }}"><b>{{ $row['action']==='error'?'Lỗi':'Sẵn sàng' }}</b>@foreach($row['errors'] as $message)<div>{{ $message }}</div>@endforeach</td></tr>@endforeach</tbody></table></div>@endif
            </div></div></div>
        </div>
        <div class="d-flex justify-content-between mt-3"><button class="btn btn-outline-secondary" type="button" data-wf-go="2"><i class="bi bi-arrow-left me-1"></i>Quay lại</button><button class="btn btn-primary" type="button" data-wf-go="4">Tiếp tục: Duyệt đơn <i class="bi bi-arrow-right ms-1"></i></button></div>
    </section>

    <section data-wf-panel="4" hidden id="approval-actions">
        <div class="wf-panel-title"><span class="n">4</span><div><h5 class="mb-0">Duyệt đơn</h5><div class="small text-muted">Thực hiện lần lượt Trưởng phòng rồi Manager.</div></div></div>
        @php $approvalBlocks=[['Trưởng phòng duyệt','leader_approve',$leaderCandidates,'btn-outline-primary',4],['Manager duyệt','manager_approve',$managerCandidates,'btn-primary',5]]; @endphp
        <div class="row g-3">@foreach($approvalBlocks as [$title,$action,$candidates,$buttonClass,$nextStep])<div class="col-xl-6"><div class="card wf-card wf-action h-100"><div class="card-body"><h5>{{ $title }}</h5><form method="POST" action="{{ route('accounting.workflow-simulation.orders.advance') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="action" value="{{ $action }}"><input type="hidden" name="wizard_step" value="4"><input type="hidden" name="next_step" value="{{ $nextStep }}"><div class="border rounded p-2 mb-2 wf-form-list">@forelse($candidates as $order)<label class="d-block py-1"><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"> <b>{{ $order->code }}</b> — {{ $order->customer?->name }}</label>@empty<span class="text-muted small">Không có đơn chờ xử lý.</span>@endforelse</div><button class="btn {{ $buttonClass }}" @disabled($candidates->isEmpty())>Duyệt các đơn đã chọn</button></form></div></div></div>@endforeach</div>
        <div class="d-flex justify-content-between mt-3"><button class="btn btn-outline-secondary" type="button" data-wf-go="3">Quay lại</button><button class="btn btn-primary" type="button" data-wf-go="5">Tiếp tục: Kho &amp; điều chuyển <i class="bi bi-arrow-right ms-1"></i></button></div>
    </section>

    <section data-wf-panel="5" hidden>
        <div class="wf-panel-title"><span class="n">5</span><div><h5 class="mb-0">Kho, đóng hàng và điều chuyển</h5><div class="small text-muted">Hoàn tất theo thứ tự từ trái sang phải, từ trên xuống dưới.</div></div></div>
        <div class="row g-3" id="packing-actions">
            <div class="col-xl-6"><div class="card wf-card wf-action orange h-100"><div class="card-body"><h5>Kho xác nhận đóng hàng</h5><form method="POST" action="{{ route('accounting.workflow-simulation.orders.advance') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="action" value="warehouse_confirm"><input type="hidden" name="wizard_step" value="5"><input type="hidden" name="next_step" value="5"><div class="border rounded p-2 my-2 wf-form-list">@forelse($warehouseCandidates as $order)<label class="d-block py-1"><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"> <b>{{ $order->code }}</b> — {{ $order->warehouse?->name }}</label>@empty<span class="text-muted small">Không có đơn chờ kho.</span>@endforelse</div><button class="btn btn-warning" @disabled($warehouseCandidates->isEmpty())>Bắt đầu đóng hàng</button></form></div></div></div>
            <div class="col-xl-6"><div class="card wf-card wf-action orange h-100"><div class="card-body"><h5>Hoàn tất đóng hàng</h5><form method="POST" action="{{ route('accounting.workflow-simulation.orders.advance') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="action" value="complete_packing"><input type="hidden" name="wizard_step" value="5"><input type="hidden" name="next_step" value="5"><div class="border rounded p-2 my-2 wf-form-list">@forelse($packingCandidates as $order)<label class="d-block py-1"><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"> <b>{{ $order->code }}</b> — {{ number_format((float)$order->total_weight,3) }} kg</label>@empty<span class="text-muted small">Không có đơn đang đóng.</span>@endforelse</div><button class="btn btn-dark" @disabled($packingCandidates->isEmpty())>Hoàn tất đóng hàng</button></form></div></div></div>
            <div class="col-xl-7" id="transfer-actions"><div class="card wf-card wf-action h-100"><div class="card-body"><h5>Tạo và vận chuyển phiếu điều chuyển</h5><form method="POST" action="{{ route('accounting.workflow-simulation.transfers.create') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="5"><input type="hidden" name="next_step" value="5"><div class="border rounded p-2 my-2 wf-form-list">@forelse($transferCandidates as $order)<label class="d-block py-1"><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"> <b>{{ $order->code }}</b> — {{ $order->warehouse?->name }}</label>@empty<span class="text-muted small">Không có đơn chờ điều chuyển.</span>@endforelse</div><div class="row g-2"><div class="col-md-6"><select class="form-select" name="target_warehouse_id" required><option value="">Kho nhận</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div><div class="col-md-6"><select class="form-select" name="shipper_id" required><option value="">Shipper điều chuyển</option>@foreach($shippers as $shipper)<option value="{{ $shipper->id }}">{{ $shipper->name }}</option>@endforeach</select></div></div><button class="btn btn-primary mt-2" @disabled($transferCandidates->isEmpty())>Tạo phiếu</button></form><div class="d-flex gap-2 mt-3"><form method="POST" action="{{ route('accounting.workflow-simulation.transfers.advance') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="5"><input type="hidden" name="next_step" value="5"><input type="hidden" name="action" value="pickup"><button class="btn btn-outline-primary" @disabled($pendingPickup->isEmpty())>Ship nhận ({{ $pendingPickup->count() }})</button></form><form method="POST" action="{{ route('accounting.workflow-simulation.transfers.advance') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="5"><input type="hidden" name="next_step" value="5"><input type="hidden" name="action" value="deliver"><button class="btn btn-outline-primary" @disabled($inTransit->isEmpty())>Giao kho đích ({{ $inTransit->count() }})</button></form></div></div></div></div>
            <div class="col-xl-5" id="receive-actions"><div class="card wf-card wf-action green h-100"><div class="card-body"><h5>Kho đích nhận hàng</h5><p>Đang chờ nhận: <b>{{ $waitingReceive->count() }}</b> phiếu</p><form method="POST" action="{{ route('accounting.workflow-simulation.transfers.receive-all') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="5"><input type="hidden" name="next_step" value="5"><button class="btn btn-success" @disabled($waitingReceive->isEmpty()) onclick="return confirm('Kho đích nhận tất cả phiếu?')">Nhận tất cả</button></form></div></div></div>
        </div>
        <div class="d-flex justify-content-between mt-3"><button class="btn btn-outline-secondary" type="button" data-wf-go="4">Quay lại</button><button class="btn btn-primary" type="button" data-wf-go="6">Tiếp tục: Giao &amp; kế toán <i class="bi bi-arrow-right ms-1"></i></button></div>
    </section>

    <section data-wf-panel="6" hidden>
        <div class="wf-panel-title"><span class="n">6</span><div><h5 class="mb-0">Điều phối, giao khách và kế toán</h5><div class="small text-muted">Công đoạn cuối của quy trình.</div></div></div>
        <div class="row g-3">
            <div class="col-xl-6" id="assignment-actions"><div class="card wf-card wf-action orange h-100"><div class="card-body"><h5>Điều phối shipper giao khách</h5><form method="POST" action="{{ route('accounting.workflow-simulation.assign-orders') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="6"><input type="hidden" name="next_step" value="6"><div class="border rounded p-2 my-2 wf-form-list">@forelse($assignmentCandidates as $order)<label class="d-block py-1"><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"> <b>{{ $order->code }}</b> — {{ $order->customer?->name }}</label>@empty<span class="text-muted small">Không có đơn sẵn sàng.</span>@endforelse</div><select class="form-select" name="shipper_id" required><option value="">Chọn shipper</option>@foreach($shippers as $shipper)<option value="{{ $shipper->id }}">{{ $shipper->name }}</option>@endforeach</select><button class="btn btn-warning mt-2" @disabled($assignmentCandidates->isEmpty())>Gắn shipper</button></form></div></div></div>
            <div class="col-xl-6" id="delivery-actions"><div class="card wf-card wf-action purple h-100"><div class="card-body"><h5>Giao hàng và thu tiền</h5><form method="POST" action="{{ route('accounting.workflow-simulation.deliver-orders') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="6"><input type="hidden" name="next_step" value="6"><div class="border rounded p-2 my-2 wf-form-list">@forelse($deliveryCandidates as $order)<label class="d-block py-1"><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"> <b>{{ $order->code }}</b> — {{ number_format($order->total) }}đ</label>@empty<span class="text-muted small">Không có đơn chờ giao.</span>@endforelse</div><select class="form-select" name="payment_mode"><option value="paid">Đã thu đủ tiền</option><option value="debt">Khách trả sau</option></select><button class="btn btn-dark mt-2" @disabled($deliveryCandidates->isEmpty())>Hoàn thiện giao hàng</button></form></div></div></div>
            <div class="col-12" id="accounting-actions"><div class="card wf-card wf-action green"><div class="card-body"><h5>Kế toán xác nhận</h5><form method="POST" action="{{ route('accounting.workflow-simulation.confirm-orders') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="wizard_step" value="6"><input type="hidden" name="next_step" value="6"><div class="table-responsive"><table class="table table-sm"><thead><tr><th></th><th>Đơn</th><th>Khách</th><th>Sale</th><th>Đã thu</th><th>Còn nợ</th></tr></thead><tbody>@forelse($accountingCandidates as $order)<tr><td><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"></td><td><b>{{ $order->code }}</b></td><td>{{ $order->customer?->name }}</td><td>{{ $order->user?->name }}</td><td>{{ number_format(max((float)$order->amount_paid,(float)$order->collected_amount)) }}đ</td><td>{{ number_format(max(0,(float)$order->total-max((float)$order->amount_paid,(float)$order->collected_amount))) }}đ</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">Không có đơn chờ xác nhận.</td></tr>@endforelse</tbody></table></div><button class="btn btn-success" @disabled($accountingCandidates->isEmpty())>Xác nhận đơn đã chọn</button></form></div></div></div>
        </div>
        <div class="mt-3"><button class="btn btn-outline-secondary" type="button" data-wf-go="5"><i class="bi bi-arrow-left me-1"></i>Quay lại</button></div>
    </section>

    <div class="card wf-card mt-3"><div class="card-header bg-white py-3 d-flex justify-content-between"><div><b>Đơn hàng ngày {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</b><div class="small text-muted">Bảng theo dõi xuyên suốt các công đoạn.</div></div><span class="badge bg-primary align-self-center">{{ $orders->count() }} đơn</span></div><div class="table-responsive"><table class="table table-hover wf-table mb-0"><thead><tr><th>Mã đơn</th><th>Khách / Sale</th><th>Trạng thái</th><th>Kho</th><th>Điều chuyển</th><th>Shipper</th><th>Thanh toán</th><th>Kế toán</th></tr></thead><tbody>@forelse($orders as $order) @php $transfer=$order->warehouseTransfers->first(); @endphp<tr><td><b>{{ $order->code ?: '#'.$order->id }}</b></td><td>{{ $order->customer?->name }}<div class="small text-muted">{{ $order->user?->name }}</div></td><td><span class="badge bg-secondary">{{ $order->status }}</span></td><td>{{ $order->warehouse?->name ?: '—' }}</td><td>{{ $transfer?->status ?: '—' }}</td><td>{{ $order->shipper?->name ?: 'Chưa gán' }}</td><td>{{ $order->payment_status ?: 'unpaid' }}</td><td>{{ $order->accountingReconciliation?->status==='confirmed'?'Đã xác nhận':'Chờ' }}</td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-4">Chưa có đơn trong ngày.</td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection

@push('scripts')
<script>
window.initAccountingWorkflowWizard = function (requestedStep) {
    const page = document.getElementById('workflow-simulation-page');
    if (!page) return;
    let current = Number(requestedStep || page.dataset.startStep || 1);
    const show = function (step) {
        current = Math.max(1, Math.min(6, Number(step) || 1));
        page.querySelectorAll('[data-wf-panel]').forEach(panel => { panel.hidden = Number(panel.dataset.wfPanel) !== current; });
        page.querySelectorAll('.wf-nav-btn').forEach(button => {
            const number = Number(button.dataset.wfGo);
            button.classList.toggle('active', number === current);
            button.classList.toggle('done', number < current);
        });
        page.dataset.startStep = String(current);
        window.scrollTo({top: page.offsetTop - 20, behavior: 'smooth'});
    };
    page.querySelectorAll('[data-wf-go]').forEach(button => button.addEventListener('click', () => show(button.dataset.wfGo)));

    page.querySelectorAll('[data-wf-panel="5"] form').forEach(form => {
        const checkboxes = Array.from(form.querySelectorAll('input[name="order_ids[]"]:not(:disabled)'));
        const list = form.querySelector('.wf-form-list');
        if (!list || !checkboxes.length || form.querySelector('[data-wf-select-all]')) return;

        const bar = document.createElement('div');
        bar.className = 'wf-select-all-bar';
        bar.setAttribute('data-wf-select-all', '');
        bar.innerHTML = '<button type="button" class="wf-select-all-btn"><i class="bi bi-check2-square me-1"></i><span>Chọn tất cả</span></button><span class="wf-select-all-count"></span>';
        list.before(bar);

        const button = bar.querySelector('button');
        const label = bar.querySelector('.wf-select-all-btn span');
        const count = bar.querySelector('.wf-select-all-count');
        const refresh = () => {
            const selected = checkboxes.filter(checkbox => checkbox.checked).length;
            const allSelected = selected === checkboxes.length;
            label.textContent = allSelected ? 'Bỏ chọn tất cả' : 'Chọn tất cả';
            count.textContent = selected.toLocaleString('vi-VN') + '/' + checkboxes.length.toLocaleString('vi-VN') + ' đơn đã chọn';
        };
        button.addEventListener('click', () => {
            const shouldCheck = !checkboxes.every(checkbox => checkbox.checked);
            checkboxes.forEach(checkbox => { checkbox.checked = shouldCheck; });
            refresh();
        });
        checkboxes.forEach(checkbox => checkbox.addEventListener('change', refresh));
        refresh();
    });

    const inventoryMap = @json($inventoryMap ?? []);
    page.querySelectorAll('[data-adjust-row]').forEach(row => {
        const variant = row.querySelector('[data-adjust-variant]');
        const quantity = row.querySelector('[data-adjust-quantity]');
        const hint = row.querySelector('[data-available-hint]');
        const refreshAvailability = () => {
            const key = String(row.dataset.warehouseId || '') + ':' + String(variant?.value || '');
            const available = Number(inventoryMap[key] || 0);
            const requested = Number(quantity?.value || 0);
            if (hint) {
                hint.textContent = 'Khả dụng: ' + available.toLocaleString('vi-VN') + ' · nhập 0 để bỏ';
                hint.classList.toggle('is-short', requested > available);
            }
        };
        variant?.addEventListener('change', refreshAvailability);
        quantity?.addEventListener('input', refreshAvailability);
        refreshAvailability();
    });

    const stockItems = page.querySelector('#wf-stock-items');
    const template = page.querySelector('#wf-stock-template');
    const addStock = page.querySelector('#wf-add-stock');
    let stockIndex = stockItems ? stockItems.querySelectorAll('[data-stock-row]').length : 0;
    if (addStock && stockItems && template) addStock.addEventListener('click', () => {
        stockItems.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(stockIndex++)));
    });
    if (stockItems) stockItems.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-stock]');
        if (!remove) return;
        const rows = stockItems.querySelectorAll('[data-stock-row]');
        if (rows.length > 1) remove.closest('[data-stock-row]').remove();
    });

    const pickerModal = page.querySelector('#wf-product-picker-modal');
    const pickerSearch = page.querySelector('#wf-product-search');
    const pickerCount = page.querySelector('#wf-picker-count');
    const updatePickerCount = () => {
        if (pickerCount) pickerCount.textContent = String(page.querySelectorAll('[data-picker-check]:checked').length);
    };
    if (pickerSearch) pickerSearch.addEventListener('input', () => {
        const term = pickerSearch.value.trim().toLocaleLowerCase('vi');
        page.querySelectorAll('[data-picker-row]').forEach(row => { row.hidden = term !== '' && !row.dataset.search.includes(term); });
    });
    page.querySelectorAll('[data-picker-check]').forEach(check => check.addEventListener('change', updatePickerCount));
    page.querySelector('#wf-select-visible')?.addEventListener('click', () => {
        page.querySelectorAll('[data-picker-row]:not([hidden]) [data-picker-check]').forEach(check => { check.checked = true; });
        updatePickerCount();
    });
    if (pickerModal && stockItems) pickerModal.addEventListener('show.bs.modal', () => {
        page.querySelectorAll('[data-picker-check]').forEach(check => { check.checked = false; });
        stockItems.querySelectorAll('[data-stock-row]').forEach(stockRow => {
            const variantId = stockRow.querySelector('select')?.value;
            if (!variantId) return;
            const pickerRow = Array.from(page.querySelectorAll('[data-picker-row]')).find(row => row.querySelector('[data-picker-check]')?.value === variantId);
            if (!pickerRow) return;
            pickerRow.querySelector('[data-picker-check]').checked = true;
            pickerRow.querySelector('[data-picker-quantity]').value = stockRow.querySelector('input[name$="[quantity]"]')?.value || 10;
            pickerRow.querySelector('[data-picker-cost]').value = stockRow.querySelector('input[name$="[unit_cost]"]')?.value || 0;
        });
        updatePickerCount();
    });
    page.querySelector('#wf-apply-products')?.addEventListener('click', () => {
        const selectedRows = Array.from(page.querySelectorAll('[data-picker-check]:checked')).map(check => check.closest('[data-picker-row]'));
        if (!selectedRows.length) { window.alert('Vui lòng chọn ít nhất một sản phẩm.'); return; }
        selectedRows.forEach(pickerRow => {
            const variantId = pickerRow.querySelector('[data-picker-check]').value;
            let stockRow = Array.from(stockItems.querySelectorAll('[data-stock-row]')).find(row => row.querySelector('select')?.value === variantId);
            if (!stockRow) stockRow = Array.from(stockItems.querySelectorAll('[data-stock-row]')).find(row => !row.querySelector('select')?.value);
            if (!stockRow && template) {
                stockItems.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(stockIndex++)));
                stockRow = stockItems.lastElementChild;
            }
            if (!stockRow) return;
            stockRow.querySelector('select').value = variantId;
            stockRow.querySelector('input[name$="[quantity]"]').value = pickerRow.querySelector('[data-picker-quantity]').value || 1;
            stockRow.querySelector('input[name$="[unit_cost]"]').value = pickerRow.querySelector('[data-picker-cost]').value || 0;
        });
        window.bootstrap?.Modal.getOrCreateInstance(pickerModal).hide();
    });

    const dateForm = page.querySelector('#wf-date-form');
    if (dateForm) dateForm.addEventListener('submit', async event => {
        event.preventDefault();
        const date = dateForm.querySelector('[name="date"]').value;
        if (!date) return;
        const url = new URL(dateForm.action, window.location.origin);
        url.searchParams.set('date', date); url.searchParams.set('step', '2');
        const button = dateForm.querySelector('button[type="submit"]');
        button.disabled = true; button.querySelector('.spinner-border')?.classList.remove('d-none');
        try {
            const response = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
            if (!response.ok) throw new Error('Không thể tải dữ liệu ngày đã chọn.');
            const documentNext = new DOMParser().parseFromString(await response.text(), 'text/html');
            const pageNext = documentNext.getElementById('workflow-simulation-page');
            if (!pageNext) throw new Error('Dữ liệu trả về không hợp lệ.');
            page.replaceWith(pageNext); history.pushState({step:2}, '', url);
            window.initAccountingWorkflowWizard(2);
        } catch (error) {
            button.disabled = false; button.querySelector('.spinner-border')?.classList.add('d-none');
            window.alert(error.message || 'Không thể chuyển ngày.');
        }
    });
    show(current);
};
document.addEventListener('DOMContentLoaded', () => window.initAccountingWorkflowWizard());
</script>
@endpush
