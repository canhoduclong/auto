@extends('layouts.warehouse')

@section('title', 'Phiếu Nhập Kho')

@push('styles')
<style>
.doc-header { background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); color: #fff; border-radius: 14px; padding: 24px 28px; margin-bottom: 24px; }
.doc-header h1 { font-size: 1.6rem; font-weight: 800; margin: 0; }
.doc-header p  { margin: 4px 0 0; opacity: .85; font-size: .9rem; }
.doc-stat { background: #fff; border-radius: 12px; padding: 16px; text-align: center; box-shadow: 0 4px 14px rgba(15,23,42,.07); }
.doc-stat-val { font-size: 1.7rem; font-weight: 800; color: #0ea5e9; }
.doc-stat-lbl { font-size: .78rem; color: #64748b; margin-top: 2px; }
.doc-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 14px rgba(15,23,42,.07); overflow: hidden; margin-bottom: 24px; }
.doc-card .table { margin: 0; }
.doc-card thead { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
.doc-card th { padding: 12px 14px; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #475569; }
.doc-card td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.doc-card tbody tr:hover { background: #f8fafc; }
.doc-code { font-weight: 800; font-size: .92rem; color: #0ea5e9; }
.doc-user { display: inline-flex; align-items: center; gap: 6px; }
.doc-avatar { width: 28px; height: 28px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; font-size: .72rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.form-section { background: #fff; border-radius: 12px; box-shadow: 0 4px 14px rgba(15,23,42,.07); padding: 24px 28px; margin-bottom: 24px; }
.form-section h5 { font-weight: 700; color: #0f172a; border-bottom: 2px solid #e0f2fe; padding-bottom: 10px; margin-bottom: 18px; }
.item-row { background: #f8fafc; border-radius: 8px; padding: 12px; margin-bottom: 8px; }
.btn-add-row { border: 2px dashed #93c5fd; background: #eff6ff; color: #1d4ed8; border-radius: 8px; padding: 8px 18px; font-size: .82rem; font-weight: 700; transition: background .15s; }
.btn-add-row:hover { background: #dbeafe; }
.item-remove { color: #ef4444; background: none; border: 0; font-size: 1.1rem; line-height: 1; padding: 2px 4px; cursor: pointer; }
.item-remove:hover { color: #b91c1c; }
</style>
@endpush

@section('content')
<div class="doc-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-box-seam me-2"></i>Phiếu Nhập Kho</h1>
            <p>Ghi nhận hàng hoá nhập vào kho — theo dõi người nhập, số lượng, đơn giá</p>
        </div>
        <button type="button" class="btn btn-light btn-sm fw-700" data-bs-toggle="modal" data-bs-target="#modalCreateStockIn">
            <i class="bi bi-plus-circle me-1"></i> Tạo phiếu nhập
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger rounded-3">
    <i class="bi bi-exclamation-circle me-2"></i>
    @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
</div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="doc-stat">
            <div class="doc-stat-val">{{ $stockInDocuments->total() }}</div>
            <div class="doc-stat-lbl">Tổng phiếu nhập</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="doc-stat">
            <div class="doc-stat-val">{{ number_format($stockInDocuments->flatMap(fn($d)=>$d->items)->sum('quantity')) }}</div>
            <div class="doc-stat-lbl">Tổng SL nhập (trang này)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="doc-stat">
            <div class="doc-stat-val">{{ number_format($stockInDocuments->flatMap(fn($d)=>$d->items)->sum(fn($i)=>$i->quantity*$i->unit_cost)) }}đ</div>
            <div class="doc-stat-lbl">Tổng giá trị nhập</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="doc-stat">
            <div class="doc-stat-val">{{ $stockInDocuments->count() }}</div>
            <div class="doc-stat-lbl">Phiếu trên trang này</div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="doc-card mb-3">
    <div style="padding:16px 20px;">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-600 mb-1">Từ ngày</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $from }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-600 mb-1">Đến ngày</label>
                <input type="date" name="to_date"   class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-600 mb-1">Nhà cung cấp</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">-- Tất cả --</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ (string) $supplierId === (string) $sup->id ? 'selected' : '' }}>
                            {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm me-1"><i class="bi bi-search me-1"></i>Lọc</button>
                <a href="{{ route('warehouse.stock-in') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
@if($stockInDocuments->count())
<div class="doc-card">
    <table class="table">
        <thead>
            <tr>
                <th>Mã Phiếu</th>
                <th>Ngày Nhập</th>
                <th>Kho</th>
                <th>Nhà cung cấp</th>
                <th>Người Tạo Phiếu</th>
                <th class="text-center">Số Dòng</th>
                <th class="text-end">Tổng SL</th>
                <th class="text-end">Giá Trị</th>
                <th class="text-center">Lần sửa</th>
                <th>Ghi Chú</th>
                <th class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockInDocuments as $doc)
            <tr>
                <td><span class="doc-code">{{ $doc->document_number ?? '#'.$doc->id }}</span></td>
                <td>{{ $doc->document_date->format('d/m/Y') }}</td>
                <td>{{ $doc->warehouse->name ?? '—' }}</td>
                <td>{{ $doc->supplier?->name ?? '—' }}</td>
                <td>
                    <div class="doc-user">
                        <div class="doc-avatar">{{ strtoupper(substr($doc->user?->name ?? 'U', 0, 1)) }}</div>
                        <div>
                            <div style="font-size:.82rem;font-weight:600;line-height:1.2;">{{ $doc->user?->name ?? '—' }}</div>
                            <div style="font-size:.7rem;color:#94a3b8;">{{ $doc->created_at->format('H:i d/m/Y') }}</div>
                        </div>
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge bg-light text-secondary border">{{ $doc->items->count() }}</span>
                </td>
                <td class="text-end fw-700 text-success">{{ number_format($doc->items->sum('quantity')) }}</td>
                <td class="text-end">{{ number_format($doc->items->sum(fn($i)=>$i->quantity*$i->unit_cost)) }}đ</td>
                <td class="text-center">
                    @if((int) $doc->edit_count > 0)
                        <a href="{{ route('warehouse.stock-in.show', $doc) }}#edit-history" class="badge bg-warning text-dark text-decoration-none" title="Xem lịch sử chỉnh sửa">
                            {{ (int) $doc->edit_count }} lần
                        </a>
                    @else
                        <span class="badge bg-light text-secondary border">0</span>
                    @endif
                </td>
                <td><small class="text-muted">{{ Str::limit($doc->notes, 28) }}</small></td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <a href="{{ route('warehouse.stock-in.show', $doc) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                            <i class="bi bi-eye"></i>
                        </a>
                        @php $editsLeft = $maxEdits - (int)$doc->edit_count; @endphp
                        @if(!\Carbon\Carbon::parse($doc->document_date)->isToday())
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Chỉ được điều chỉnh phiếu trong ngày hôm nay">
                                <i class="bi bi-calendar-x"></i>
                            </button>
                        @elseif($editsLeft > 0)
                            <button type="button" class="btn btn-sm btn-outline-warning btn-edit-doc"
                                    data-doc-id="{{ $doc->id }}"
                                    data-edit-url="{{ route('warehouse.stock-in.edit', $doc) }}"
                                    data-update-url="{{ route('warehouse.stock-in.update', $doc) }}"
                                    title="Điều chỉnh phiếu (còn {{ $editsLeft }} lần)">
                                <i class="bi bi-pencil-square"></i>
                                <span class="badge bg-secondary ms-1" style="font-size:.65rem;">{{ $editsLeft }}</span>
                            </button>
                        @else
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Đã dùng hết lượt chỉnh sửa">
                                <i class="bi bi-lock"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-center mt-3">{{ $stockInDocuments->appends(request()->query())->links() }}</div>
@else
<div class="doc-card" style="padding:3rem;text-align:center;color:#94a3b8;">
    <i class="bi bi-box-seam" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>
    <p class="mb-0">Chưa có phiếu nhập nào trong khoảng thời gian này.</p>
    <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#modalCreateStockIn">
        <i class="bi bi-plus-circle me-1"></i> Tạo phiếu nhập đầu tiên
    </button>
</div>
@endif

{{-- Modal Create --}}
<div class="modal fade" id="modalCreateStockIn" tabindex="-1" aria-labelledby="modalCreateStockInLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#0ea5e9,#2563eb);color:#fff;">
                <h5 class="modal-title fw-800" id="modalCreateStockInLabel">
                    <i class="bi bi-box-seam me-2"></i>Tạo Phiếu Nhập Kho
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('warehouse.stock-in.store') }}" method="POST">
                @csrf
                <div class="modal-body" style="background:#f8fafc;">

                    {{-- Header info --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-600 small">Ngày nhập <span class="text-danger">*</span></label>
                            <input type="date" name="document_date" class="form-control"
                                   value="{{ old('document_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-600 small">Kho nhập <span class="text-danger">*</span></label>
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">-- Chọn kho --</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ old('warehouse_id', $warehouses->count() === 1 ? $wh->id : '') == $wh->id ? 'selected' : '' }}>
                                        {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-600 small">Nhà cung cấp <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">-- Chọn nhà cung cấp --</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>
                                        {{ $sup->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-600 small">Phí vận chuyển (đ)</label>
                            <input type="number" name="shipping_fee" class="form-control" min="0" step="1000"
                                   value="{{ old('shipping_fee', 0) }}" placeholder="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-600 small">Ghi chú</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Ghi chú về đợt nhập hàng này…">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Items --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-700" style="color:#0f172a;"><i class="bi bi-list-ul me-1"></i>Danh sách hàng hoá</span>
                        <button type="button" class="btn-add-row" id="btnAddItemIn">
                            <i class="bi bi-plus me-1"></i> Thêm dòng
                        </button>
                    </div>

                    <div id="itemsContainerIn">
                        <div class="row g-2 mb-1 align-items-end small fw-600 text-muted px-1" style="font-size:.72rem;text-transform:uppercase;">
                            <div class="col-5">Sản phẩm / Biến thể</div>
                            <div class="col-2">Số lượng</div>
                            <div class="col-1">ĐVT</div>
                            <div class="col-2">Khối lượng</div>
                            <div class="col-3">Đơn giá nhập (đ)</div>
                            <div class="col-1 text-end">Thành tiền</div>
                            <div class="col-1"></div>
                        </div>
                        <div class="item-row" data-item-row>
                            <div class="row g-2 align-items-center">
                                <div class="col-5">
                                    <select name="items[0][product_variant_id]" class="form-select form-select-sm variant-select" required>
                                        <option value="">-- Chọn sản phẩm --</option>
                                        @foreach($productVariants as $v)
                                            @php
                                                $sizeRaw = strtolower(str_replace(',', '.', trim((string) ($v->size ?? ''))));
                                                preg_match('/([0-9]*\.?[0-9]+)/', $sizeRaw, $sizeMatches);
                                                $defaultWeight = (float) ($sizeMatches[1] ?? 0);
                                                if (str_contains($sizeRaw, 'g') && !str_contains($sizeRaw, 'kg')) {
                                                    $defaultWeight = $defaultWeight / 1000;
                                                }
                                                $defaultWeight = round(max(0, $defaultWeight), 3);
                                                $weightUnitLabel = in_array((string) ($v->product->unit ?? 'cai'), ['con', 'cai'], true)
                                                    ? 'Kg'
                                                    : ($v->product->unit_label ?? 'Cái');
                                            @endphp
                                            <option value="{{ $v->id }}" data-unit-label="{{ $v->product->unit_label ?? 'Cái' }}" data-default-weight="{{ number_format($defaultWeight, 3, '.', '') }}" data-weight-unit-label="{{ $weightUnitLabel }}">{{ $v->product?->name }} – {{ $v->name }} {{ $v->sku ? '('.$v->sku.')' : '' }} - {{ $v->product->unit_label ?? 'Cái' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-2">
                                    <input type="number" name="items[0][quantity]" class="form-control form-control-sm qty-input" min="1" value="1" required>
                                </div>
                                <div class="col-1">
                                    <input type="text" class="form-control form-control-sm unit-label" value="Cái" readonly>
                                </div>
                                <div class="col-2">
                                    <div class="d-flex align-items-center gap-1">
                                        <input type="number" class="form-control form-control-sm weight-input" value="0.000" step="0.001" min="0" readonly>
                                        <span class="text-muted small weight-unit-label" style="white-space: nowrap;">Kg</span>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <input type="number" name="items[0][unit_cost]" class="form-control form-control-sm cost-input" min="0" step="1000" value="0" required>
                                </div>
                                <div class="col-1 text-end line-total fw-700" style="font-size:.82rem;color:#059669;">0đ</div>
                                <div class="col-1 text-center">
                                    <button type="button" class="item-remove" onclick="removeRow(this)" title="Xoá dòng"><i class="bi bi-x-circle"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3 gap-4">
                        <div class="text-end">
                            <div class="text-muted small">Tổng tiền hàng</div>
                            <div id="grandTotalIn" class="fw-800" style="font-size:1.1rem;color:#0ea5e9;">0đ</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary fw-700">
                        <i class="bi bi-check-circle me-1"></i>Lưu phiếu nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── Modal Điều Chỉnh Phiếu Nhập ──────────────────────────────────────── --}}
<div class="modal fade" id="modalEditStockIn" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#b45309);color:#fff;">
                <h5 class="modal-title fw-800">
                    <i class="bi bi-pencil-square me-2"></i>Điều Chỉnh Phiếu Nhập Kho
                    <small id="editDocCode" class="ms-2 fw-400 opacity-75"></small>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- Loading spinner --}}
            <div id="editModalSpinner" class="modal-body text-center py-5">
                <div class="spinner-border text-warning" role="status"></div>
                <div class="mt-2 text-muted small">Đang tải dữ liệu phiếu…</div>
            </div>

            {{-- Edit form (hidden until loaded) --}}
            <div id="editModalBody" style="display:none;">
                <form id="formEditStockIn" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="modal-body" style="background:#fffbeb;">
                        {{-- Lượt còn lại --}}
                        <div id="editLimitBadge" class="alert alert-warning py-2 px-3 mb-3 small fw-600">
                            <i class="bi bi-info-circle me-1"></i>
                            <span id="editLimitText"></span>
                        </div>

                        {{-- Header --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-600 small">Phí vận chuyển (đ)</label>
                                <input type="number" name="shipping_fee" id="editShippingFee" class="form-control" min="0" step="1000" value="0">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-600 small">Ghi chú phiếu</label>
                                <input type="text" name="notes" id="editNotes" class="form-control" placeholder="Ghi chú về phiếu nhập…">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-600 small text-warning">
                                    <i class="bi bi-chat-left-text me-1"></i>Lý do điều chỉnh <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="edit_notes" id="editReasonNotes" class="form-control" required
                                       placeholder="Ví dụ: Nhập nhầm số lượng, điều chỉnh đơn giá theo hoá đơn thực tế…">
                            </div>
                        </div>

                        {{-- Items table --}}
                        <div class="fw-700 mb-2" style="color:#0f172a;">
                            <i class="bi bi-list-ul me-1"></i>Danh sách hàng hoá
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle" style="font-size:.85rem;">
                                <thead style="background:#fef3c7;">
                                    <tr>
                                        <th>Sản phẩm / Biến thể</th>
                                        <th class="text-center" style="width:130px;">Số lượng mới</th>
                                        <th class="text-center" style="width:150px;">Đơn giá mới (đ)</th>
                                        <th class="text-end" style="width:120px;">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody id="editItemsBody"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-700">Tổng cộng</td>
                                        <td class="text-end fw-800 text-warning" id="editGrandTotal">0đ</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer bg-white">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-warning fw-700 text-dark">
                            <i class="bi bi-check-circle me-1"></i>Lưu điều chỉnh
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    // ── Create-modal logic ──────────────────────────────────────────────────
    let idx = 1;

    function calcRow(row) {
        const qty  = parseFloat(row.querySelector('.qty-input').value)  || 0;
        const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
        const tot  = qty * cost;
        row.querySelector('.line-total').textContent = tot.toLocaleString('vi-VN') + 'đ';
        return tot;
    }

    function calcAll() {
        let total = 0;
        document.querySelectorAll('#itemsContainerIn [data-item-row]').forEach(r => { total += calcRow(r); });
        document.getElementById('grandTotalIn').textContent = total.toLocaleString('vi-VN') + 'đ';
    }

    function syncUnitLabel(row) {
        const select = row.querySelector('.variant-select');
        const label = row.querySelector('.unit-label');
        const weightInput = row.querySelector('.weight-input');
        const weightUnitLabel = row.querySelector('.weight-unit-label');
        if (!select || !label) {
            return;
        }

        const opt = select.options[select.selectedIndex];
        label.value = (opt && opt.dataset.unitLabel) ? opt.dataset.unitLabel : 'Cái';
        if (weightInput) {
            const weight = (opt && opt.dataset.defaultWeight) ? parseFloat(opt.dataset.defaultWeight) : 0;
            weightInput.value = Number.isFinite(weight) ? weight.toFixed(3) : '0.000';
        }
        if (weightUnitLabel) {
            weightUnitLabel.textContent = (opt && opt.dataset.weightUnitLabel) ? opt.dataset.weightUnitLabel : 'Kg';
        }
    }

    document.getElementById('itemsContainerIn').addEventListener('input', calcAll);
    document.getElementById('itemsContainerIn').addEventListener('change', function (e) {
        if (e.target.classList.contains('variant-select')) {
            syncUnitLabel(e.target.closest('[data-item-row]'));
        }
    });

    document.getElementById('btnAddItemIn').addEventListener('click', function () {
        const first = document.querySelector('#itemsContainerIn [data-item-row]');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('select, input').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, '[' + idx + ']');
            if (el.tagName === 'SELECT') el.value = '';
            else if (el.classList.contains('qty-input'))  el.value = 1;
            else if (el.classList.contains('cost-input')) el.value = 0;
        });
        const unitLabel = clone.querySelector('.unit-label');
        if (unitLabel) {
            unitLabel.value = 'Cái';
        }
        const weightInput = clone.querySelector('.weight-input');
        if (weightInput) {
            weightInput.value = '0.000';
        }
        const weightUnitLabel = clone.querySelector('.weight-unit-label');
        if (weightUnitLabel) {
            weightUnitLabel.textContent = 'Kg';
        }
        clone.querySelector('.line-total').textContent = '0đ';
        document.getElementById('itemsContainerIn').appendChild(clone);
        calcAll();
        idx++;
    });

    window.removeRow = function (btn) {
        const rows = document.querySelectorAll('#itemsContainerIn [data-item-row]');
        if (rows.length <= 1) { alert('Phiếu phải có ít nhất 1 sản phẩm.'); return; }
        btn.closest('[data-item-row]').remove();
        calcAll();
    };

    document.querySelectorAll('#itemsContainerIn [data-item-row]').forEach(syncUnitLabel);
})();

// ── Edit-modal logic ───────────────────────────────────────────────────────
(function () {
    function calcEditTotal() {
        let total = 0;
        document.querySelectorAll('#editItemsBody tr[data-item-row]').forEach(function (row) {
            const qty  = parseFloat(row.querySelector('.eq-qty').value)  || 0;
            const cost = parseFloat(row.querySelector('.eq-cost').value) || 0;
            const line = qty * cost;
            row.querySelector('.eq-line').textContent = line.toLocaleString('vi-VN') + 'đ';
            total += line;
        });
        document.getElementById('editGrandTotal').textContent = total.toLocaleString('vi-VN') + 'đ';
    }

    document.getElementById('editItemsBody').addEventListener('input', calcEditTotal);

    // Delegate click on each edit button in table
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-doc');
        if (!btn) return;

        const editUrl   = btn.dataset.editUrl;
        const updateUrl = btn.dataset.updateUrl;

        // Reset modal state
        document.getElementById('editModalSpinner').style.display = '';
        document.getElementById('editModalBody').style.display    = 'none';
        document.getElementById('editDocCode').textContent        = '';
        document.getElementById('editItemsBody').innerHTML        = '';
        document.getElementById('editReasonNotes').value          = '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditStockIn'));
        modal.show();

        fetch(editUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) {
            if (!r.ok) {
                return r.json().then(function (d) { throw new Error(d.message || 'Lỗi tải dữ liệu.'); });
            }
            return r.json();
        })
        .then(function (data) {
            if (!data.ok) { throw new Error(data.message || 'Không thể mở phiếu.'); }

            const doc   = data.document;
            const items = data.items;

            document.getElementById('editDocCode').textContent      = doc.document_number;
            document.getElementById('editShippingFee').value        = doc.shipping_fee;
            document.getElementById('editNotes').value              = doc.notes || '';
            document.getElementById('editLimitText').textContent    =
                'Đã điều chỉnh ' + doc.edit_count + '/' + doc.max_edits + ' lần. ' +
                'Còn ' + (doc.max_edits - doc.edit_count) + ' lần điều chỉnh.';

            // Set form action
            document.getElementById('formEditStockIn').action = updateUrl;

            // Build items table
            const tbody = document.getElementById('editItemsBody');
            tbody.innerHTML = '';
            items.forEach(function (item) {
                const tr = document.createElement('tr');
                tr.dataset.itemRow = '1';
                tr.innerHTML =
                    '<td>' +
                        '<div class="fw-600" style="font-size:.85rem;">' + escHtml(item.variant_name) + '</div>' +
                        (item.sku ? '<small class="text-muted">' + escHtml(item.sku) + '</small>' : '') +
                        '<input type="hidden" name="items[' + item.id + '][id]" value="' + item.id + '">' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<input type="number" name="items[' + item.id + '][quantity]" class="form-control form-control-sm eq-qty text-center" ' +
                               'min="0" value="' + item.quantity + '" required>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<input type="number" name="items[' + item.id + '][unit_cost]" class="form-control form-control-sm eq-cost text-center" ' +
                               'min="0" step="1000" value="' + item.unit_cost + '" required>' +
                    '</td>' +
                    '<td class="text-end fw-700 eq-line" style="color:#b45309;">0đ</td>';
                tbody.appendChild(tr);
            });

            calcEditTotal();
            document.getElementById('editModalSpinner').style.display = 'none';
            document.getElementById('editModalBody').style.display    = '';
        })
        .catch(function (err) {
            modal.hide();
            alert(err.message);
        });
    });

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
})();
</script>
@endpush
@endsection
