@extends('layouts.warehouse')

@section('title', 'Tạo Phiếu Nhập Kho')

@push('styles')
<style>
    .form-section { background: #fff; border-radius: 12px; box-shadow: 0 4px 14px rgba(15,23,42,.07); padding: 24px 28px; margin-bottom: 24px; }
    .form-section h5 { font-weight: 700; color: #0f172a; border-bottom: 2px solid #e0f2fe; padding-bottom: 10px; margin-bottom: 18px; }
    .stockin-items-scroll { max-height: 420px; overflow-y: auto; overflow-x: auto; padding-right: 4px; scrollbar-gutter: stable; }
    .stockin-items-scroll thead th { position: sticky; top: 0; z-index: 2; }
    .stockin-items-head, .stockin-item-grid { min-width: 980px; display: grid; grid-template-columns: 4.3fr 1.2fr 1fr 1.7fr 2fr 1.3fr 36px; gap: 8px; align-items: center; }
    .stockin-items-head { font-size: .72rem; text-transform: uppercase; }
    .stockin-item-grid .line-total { text-align: right; white-space: nowrap; }
    .calculated-weight-input { background: #f8fafc; font-weight: 700; color: #0f766e; }
    .stockin-item-grid .item-remove { justify-self: center; }
    .btn-add-row { border: 2px dashed #93c5fd; background: #eff6ff; color: #1d4ed8; border-radius: 8px; padding: 8px 18px; font-size: .82rem; font-weight: 700; transition: background .15s; }
    .btn-add-row:hover { background: #dbeafe; }
    .item-remove { color: #ef4444; background: none; border: 0; font-size: 1.1rem; line-height: 1; padding: 2px 4px; cursor: pointer; }
    .item-remove:hover { color: #b91c1c; }
    .restock-table td, .restock-table th { vertical-align: middle; }
    .restock-row-active { background: #f0fdf4; }
</style>
@endpush

@section('content')
<div class="form-section">
    <h5><i class="bi bi-box-seam me-2"></i>Tạo Phiếu Nhập Kho</h5>
    <form action="{{ route('warehouse.stock-in.store') }}" method="POST">
        @csrf
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label fw-600 small">Ngày nhập <span class="text-danger">*</span></label>
                <input type="date" name="document_date" class="form-control" value="{{ old('document_date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-600 small">Nhà cung cấp <span class="text-danger">*</span></label>
                <select name="supplier_id" id="supplierSelect" class="form-select" {{ $suppliers->isEmpty() ? 'disabled' : 'required' }}>
                    <option value="">-- Chọn nhà cung cấp --</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}{{ $sup->is_active ? '' : ' (ngưng hoạt động)' }}</option>
                    @endforeach
                </select>
                @if($suppliers->isEmpty())
                    <div class="small text-danger mt-1">
                        Chưa có nhà cung cấp. Vui lòng thêm tại <a href="{{ route('admin.suppliers.index') }}" class="text-decoration-underline">quản lý nhà cung cấp</a>.
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label fw-600 small">Phí vận chuyển (đ)</label>
                <input type="number" name="shipping_fee" class="form-control" min="0" step="1000" value="{{ old('shipping_fee', 0) }}" placeholder="0">
            </div>
            <div class="col-12">
                <label class="form-label fw-600 small">Ghi chú</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Ghi chú về đợt nhập hàng này…">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-700" style="color:#0f172a;"><i class="bi bi-list-ul me-1"></i>Danh sách hàng hoá</span>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnSelectProductVariant" data-bs-toggle="modal" data-bs-target="#productSelectionModal">
                <i class="bi bi-search me-1"></i> Chọn sản phẩm
            </button>
        </div>
        <div class="card border-primary-subtle bg-primary-subtle mb-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div>
                        <div class="fw-700 text-primary-emphasis">
                            <i class="bi bi-bookmark-star me-1"></i>Mẫu sản phẩm nhập kho
                        </div>
                        <div class="small text-muted">Áp dụng nhanh danh sách sản phẩm và số lượng thường nhập.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" id="btnOpenSaveTemplate" data-bs-toggle="modal" data-bs-target="#saveStockInTemplateModal">
                        <i class="bi bi-bookmark-plus me-1"></i>Lưu danh sách hiện tại thành mẫu
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-8">
                        <select class="form-select form-select-sm" id="stockInTemplateSelect">
                            <option value="">-- Chọn mẫu sản phẩm --</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success flex-grow-1" id="btnApplyStockInTemplate">
                            <i class="bi bi-lightning-charge me-1"></i>Áp dụng mẫu
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnDeleteStockInTemplate" title="Xóa mẫu đang chọn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div id="stockInTemplateSummary" class="small text-muted mt-2"></div>
            </div>
        </div>
        <div id="supplierRequiredNotice" class="alert alert-warning py-2 small mb-2">
            Vui lòng chọn nhà cung cấp trước.
        </div>
        <div id="supplierPriceNotice" class="alert alert-info py-2 small mb-2 d-none">
            Chỉ hiển thị sản phẩm thuộc nhà cung cấp đã chọn. Sản phẩm chưa có bảng giá hiện hành vẫn có thể nhập tay đơn giá.
        </div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fw-700 text-dark">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Sản phẩm cần nhập bổ sung
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-dark"
                        id="btnToggleRestockVariants"
                        data-bs-toggle="collapse"
                        data-bs-target="#restockVariantsCollapse"
                        aria-expanded="true"
                        aria-controls="restockVariantsCollapse"
                    >
                        <i class="bi bi-chevron-up me-1" id="restockToggleIcon"></i>
                        <span id="restockToggleLabel">Thu gọn</span>
                    </button>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="restockCheckAll">
                        <label class="form-check-label small fw-600" for="restockCheckAll">Chọn tất cả</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="btnAddRestockSelected">
                        <i class="bi bi-plus-circle me-1"></i>Thêm vào phiếu nhập
                    </button>
                </div>
            </div>
            <div class="collapse show" id="restockVariantsCollapse">
            <div class="card-body p-0">
                @if(($lowStockVariants ?? collect())->isEmpty())
                    <div class="p-3 text-muted small">Hiện chưa có sản phẩm thiếu hàng.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 restock-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:44px;" class="text-center">#</th>
                                    <th>Sản phẩm / Biến thể</th>
                                    <th class="text-center" style="width:140px;">Tồn hiện tại</th>
                                    <th class="text-center" style="width:180px;">Số lượng thiếu</th>
                                </tr>
                            </thead>
                            <tbody id="restockVariantList">
                                @foreach(($lowStockVariants ?? collect()) as $variant)
                                    @php
                                        $shortageQty = max(0, (int) ($variant['shortage_qty'] ?? 0));
                                    @endphp
                                    <tr class="js-restock-row" data-variant-id="{{ $variant['variant_id'] }}" data-label="{{ $variant['label'] }}" data-shortage-qty="{{ $shortageQty }}">
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                class="form-check-input js-restock-item"
                                                value="{{ $variant['variant_id'] }}"
                                                data-label="{{ $variant['label'] }}"
                                                data-shortage-qty="{{ $shortageQty }}"
                                            >
                                        </td>
                                        <td>
                                            <div class="fw-600">{{ $variant['label'] }}</div>
                                            <div class="small text-muted">ĐVT: {{ $variant['unit_label'] }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ (int) ($variant['available'] ?? 0) <= 0 ? 'bg-danger' : 'bg-warning text-dark' }} rounded-pill">
                                                {{ number_format((int) ($variant['available'] ?? 0)) }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-700 text-danger">{{ number_format($shortageQty) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            </div>
        </div>
        <div id="itemsContainerIn">
            <div class="table-responsive stockin-items-scroll">
                <table class="table table-bordered align-middle mb-0" style="min-width:1100px;">
                    <thead class="table-light align-middle">
                        <tr style="text-align:center;vertical-align:middle;">
                            <th style="width:48px;">STT</th>
                            <th style="min-width:260px;">Sản phẩm / Biến thể</th>
                            <th style="min-width:90px;">Số lượng</th>
                            <th style="min-width:90px;">ĐVT</th>
                            <th style="min-width:110px;">Khối lượng</th>
                            <th style="min-width:130px;">Đơn giá nhập (đ)</th>
                            <th style="min-min-width:120px;">Ghi chú</th>
                            <th style="min-width:120px;">Thành tiền</th>
                            <th style="min-width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="stockinRows">
                        <!-- Rows will be rendered here by JS -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-success fw-semibold">
                <i class="bi bi-check2-circle me-1"></i>Lưu phiếu nhập kho
            </button>
        </div>
    </form>
</div>

@include('warehouse.stock-in.product-selection-modal', ['availableVariants' => $availableVariants ?? []])

<div class="modal fade" id="saveStockInTemplateModal" tabindex="-1" aria-labelledby="saveStockInTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveStockInTemplateModalLabel">
                    <i class="bi bi-bookmark-plus me-2"></i>Lưu mẫu sản phẩm nhập kho
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <label for="stockInTemplateName" class="form-label fw-semibold">Tên mẫu <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="stockInTemplateName" maxlength="150" placeholder="Ví dụ: Nhập hàng sáng hằng ngày">
                <div class="form-text">Mẫu sẽ lưu nhà cung cấp, sản phẩm và số lượng hiện tại. Đơn giá sẽ lấy theo bảng giá mới nhất khi áp dụng.</div>
                <div id="stockInTemplateError" class="alert alert-danger py-2 small mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btnSaveStockInTemplate">
                    <i class="bi bi-check2 me-1"></i>Lưu mẫu
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let productVariants = [];
let rowIdx = 0;
let stockInTemplates = @json($stockInTemplates ?? []);

const restockVariantsCollapse = document.getElementById('restockVariantsCollapse');
const restockToggleIcon = document.getElementById('restockToggleIcon');
const restockToggleLabel = document.getElementById('restockToggleLabel');

function updateRestockToggle(isOpen) {
    if (restockToggleLabel) {
        restockToggleLabel.textContent = isOpen ? 'Thu gọn' : 'Mở ra';
    }
    if (restockToggleIcon) {
        restockToggleIcon.classList.toggle('bi-chevron-up', isOpen);
        restockToggleIcon.classList.toggle('bi-chevron-down', !isOpen);
    }
}

restockVariantsCollapse?.addEventListener('shown.bs.collapse', () => updateRestockToggle(true));
restockVariantsCollapse?.addEventListener('hidden.bs.collapse', () => updateRestockToggle(false));

function toNumber(value, fallback = 0) {
    const parsed = parseFloat(String(value ?? '').replace(',', '.'));
    return Number.isFinite(parsed) ? parsed : fallback;
}

function variantWeightPerUnit(variant) {
    const variantKg = toNumber(variant?.kg, 0);
    if (variantKg > 0) return variantKg;

    const productKg = toNumber(variant?.product?.kg, 0);
    if (productKg > 0) return productKg;

    return 1;
}

function formatWeight(value) {
    const rounded = Math.round((toNumber(value, 0) + Number.EPSILON) * 1000) / 1000;
    return rounded.toLocaleString('vi-VN', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3
    });
}

function findVariant(variantId) {
    return productVariants.find(v => String(v.id) === String(variantId));
}

function renderRow(idx, data = {}) {
    let options = '<option value="">-- Chọn sản phẩm --</option>';
    productVariants.forEach(v => {
        const variantId = v.id ?? v.variant_id;
        const weightPerUnit = toNumber(v.weight_per_unit, variantWeightPerUnit(v));
        options += `<option value="${variantId}" data-unit="${v.unit_label ?? v.product?.unit_label ?? 'Cái'}" data-weight-per-unit="${weightPerUnit}"${data.product_variant_id == variantId ? ' selected' : ''}>${v.label || ((v.product?.name || '') + ' - ' + v.name)}</option>`;
    });
    
    // If the data was provided from the modal instead of productVariants array matching
    if (data.product_variant_id && !productVariants.some(v => v.id == data.product_variant_id)) {
        options += `<option value="${data.product_variant_id}" data-unit="${data.unit_label ?? ''}" data-weight-per-unit="${data.weight_per_unit ?? 1}" selected>${data.label ?? 'Sản phẩm đã chọn'}</option>`;
    }

    const qty = data.quantity ?? 1;
    const price = data.unit_cost ?? data.latest_price ?? 0;
    const lineTotal = (qty * price).toLocaleString('vi-VN');
    const selectedVariant = findVariant(data.product_variant_id);
    const weightPerUnit = data.weight_per_unit ?? (selectedVariant ? variantWeightPerUnit(selectedVariant) : 1);
    const totalWeight = toNumber(data.weight, qty * weightPerUnit);
    return `
    <tr class="item-row" data-item-row data-idx="${idx}">
        <td class="stt text-center align-middle">${idx + 1}</td>
        <td class="align-middle d-flex align-items-center gap-2" style="min-width:260px;">
            <select name="items[${idx}][product_variant_id]" class="form-select form-select-sm variant-select text-center flex-grow-1" required style="min-width:180px;">
                ${options}
            </select>
        </td>
        <td class="align-middle"><input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm text-center" min="1" value="${qty}" required></td>
        <input type="hidden" name="items[${idx}][source_price_id]" class="source-price-id-input" value="${data.source_price_id ?? data.price_id ?? ''}">
        <td class="align-middle"><input type="text" class="form-control form-control-sm unit-label-input text-center" value="${data.unit_label ?? ''}" readonly tabindex="-1"></td>
        <td class="align-middle">
            <input type="text" class="form-control form-control-sm text-center calculated-weight-input" value="${formatWeight(totalWeight)} Kg"  tabindex="-1" data-weight-per-unit="${weightPerUnit}">
              
        </td>
        <td class="align-middle"><input type="number" name="items[${idx}][unit_cost]" class="form-control form-control-sm text-center" min="0" step="1000" value="${price}"></td>
        <td class="align-middle"><input type="text" name="items[${idx}][note]" class="form-control form-control-sm text-center" value="${data.note ?? ''}"></td>
        <td class="line-total text-end align-middle">${lineTotal}</td>
        <td class="item-actions text-center align-middle">
            <button type="button" class="btn btn-link text-danger p-0 btn-remove-row" title="Xoá dòng"><i class="bi bi-x-circle-fill"></i></button>
        </td>
    </tr>`;
}

window.addRow = function(data = {}) {
    const container = document.getElementById('stockinRows');
    container.insertAdjacentHTML('beforeend', renderRow(rowIdx, data));
    rowIdx++;
    reindexRows();
};

window.addOrIncreaseVariantRow = function(data = {}, quantityToAdd = 1) {
    const container = document.getElementById('stockinRows');
    const variantId = String(data.product_variant_id ?? data.id ?? data.variant_id ?? '');
    const safeQuantity = Math.max(1, parseInt(quantityToAdd, 10) || 1);

    if (!variantId) {
        return {status: 'invalid'};
    }

    let matchedRow = null;
    container.querySelectorAll('.item-row').forEach((row) => {
        const select = row.querySelector('select[name*="product_variant_id"]');
        if (select && String(select.value) === variantId) {
            matchedRow = row;
        }
    });

    if (matchedRow) {
        const qtyInput = matchedRow.querySelector('input[name*="[quantity]"]');
        if (qtyInput) {
            qtyInput.value = (parseInt(qtyInput.value || '0', 10) || 0) + safeQuantity;
            qtyInput.dispatchEvent(new Event('input', {bubbles: true}));
            qtyInput.focus();
        }
        matchedRow.classList.add('table-warning');
        setTimeout(() => matchedRow.classList.remove('table-warning'), 500);
        return {status: 'incremented', row: matchedRow};
    }

    window.addRow({...data, product_variant_id: variantId, quantity: safeQuantity});
    const newRow = container.querySelector('.item-row:last-child');
    if (newRow) {
        const qtyInput = newRow.querySelector('input[name*="[quantity]"]');
        if (qtyInput) {
            qtyInput.focus();
        }
    }
    return {status: 'added', row: newRow};
};

function updateSTT() {
    document.querySelectorAll('#stockinRows .item-row').forEach((row, i) => {
        row.querySelector('.stt').textContent = i + 1;
    });
}

function reindexRows() {
    document.querySelectorAll('#stockinRows .item-row').forEach((row, i) => {
        row.setAttribute('data-idx', i);
        row.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.name) {
                input.name = input.name.replace(/items\[\d+\]/, `items[${i}]`);
            }
        });
    });
    updateSTT();
}

function updateLineTotal(row) {
    const qty = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
    const price = parseFloat(row.querySelector('input[name*="[unit_cost]"]').value) || 0;
    row.querySelector('.line-total').textContent = (qty * price).toLocaleString('vi-VN');
}

function updateRowWeight(row) {
    const qty = toNumber(row.querySelector('input[name*="[quantity]"]')?.value, 0);
    const select = row.querySelector('.variant-select');
    const selected = select?.selectedOptions?.[0];
    const weightInput = row.querySelector('.calculated-weight-input');
    const formula = row.querySelector('.weight-formula');

    if (!weightInput) return;

    const selectedWeight = toNumber(selected?.getAttribute('data-weight-per-unit'), 1);
    const totalWeight = qty * selectedWeight;
    weightInput.dataset.weightPerUnit = selectedWeight;
    weightInput.value = `${formatWeight(totalWeight)} Kg`;
    if (formula) {
        formula.textContent = `${formatWeight(selectedWeight)} Kg / đơn vị`;
    }
}

function syncRowCalculatedFields(row) {
    updateRowWeight(row);
    updateLineTotal(row);
}

function supplierSelected() {
    return !!document.getElementById('supplierSelect')?.value;
}

function syncSupplierState() {
    const hasSupplier = supplierSelected();
    const selectButton = document.getElementById('btnSelectProductVariant');
    const addRestockButton = document.getElementById('btnAddRestockSelected');
    const requiredNotice = document.getElementById('supplierRequiredNotice');
    const priceNotice = document.getElementById('supplierPriceNotice');

    if (selectButton) {
        selectButton.disabled = !hasSupplier;
    }
    if (addRestockButton) {
        addRestockButton.disabled = !hasSupplier;
    }
    requiredNotice?.classList.toggle('d-none', hasSupplier);
    priceNotice?.classList.toggle('d-none', !hasSupplier);
}

async function loadSupplierProducts(supplierId) {
    productVariants = [];
    if (!supplierId) {
        filterProductModalRows([]);
        return;
    }

    const response = await fetch(`{{ url('/api/suppliers') }}/${supplierId}/products`, {
        headers: {'Accept': 'application/json'}
    });
    const payload = await response.json();
    const products = payload.data || [];
    productVariants = products.flatMap(product => product.variants || []).map(variant => ({
        ...variant,
        id: variant.id ?? variant.variant_id,
        source_price_id: variant.source_price_id ?? variant.price_id,
        unit_cost: variant.unit_cost ?? variant.latest_price ?? 0
    }));
    filterProductModalRows(productVariants.map(variant => String(variant.variant_id || variant.id)));
}

function filterProductModalRows(allowedVariantIds) {
    const allowed = new Set(allowedVariantIds);
    const variantMeta = new Map(productVariants.map(variant => [String(variant.variant_id || variant.id), variant]));
    document.querySelectorAll('.product-selection-row').forEach(row => {
        const button = row.querySelector('.js-select-product');
        const variantId = button?.getAttribute('data-id');
        const isAllowed = allowed.size > 0 && allowed.has(String(variantId));
        row.dataset.supplierAllowed = isAllowed ? '1' : '0';
        row.style.display = isAllowed ? '' : 'none';

        const meta = variantMeta.get(String(variantId));
        if (button && meta) {
            button.dataset.latest_price = meta.latest_price ?? '';
            button.dataset.price_id = meta.price_id ?? '';
            button.dataset.weight_per_unit = meta.weight_per_unit ?? button.dataset.weight_per_unit ?? 1;
            button.dataset.unit_label = meta.unit_label ?? button.dataset.unit_label ?? '';
            button.dataset.label = meta.label ?? button.dataset.label ?? '';
        }
    });
    if (typeof window.syncProductSelectionGroups === 'function') {
        window.syncProductSelectionGroups();
    }
}

function resetStockInRows() {
    const rows = document.getElementById('stockinRows');
    if (!rows) return;
    rows.innerHTML = '';
    rowIdx = 0;
}

function selectedStockInTemplate() {
    const templateId = document.getElementById('stockInTemplateSelect')?.value;
    return stockInTemplates.find(template => String(template.id) === String(templateId));
}

function renderStockInTemplateOptions(selectedId = '') {
    const select = document.getElementById('stockInTemplateSelect');
    if (!select) return;

    select.innerHTML = '<option value="">-- Chọn mẫu sản phẩm --</option>';
    stockInTemplates.forEach(template => {
        const option = document.createElement('option');
        option.value = template.id;
        option.textContent = `${template.name} - ${template.supplier_name} (${template.items.length} sản phẩm)`;
        option.selected = String(template.id) === String(selectedId);
        select.appendChild(option);
    });
    updateStockInTemplateSummary();
}

function updateStockInTemplateSummary() {
    const summary = document.getElementById('stockInTemplateSummary');
    const template = selectedStockInTemplate();
    if (!summary) return;

    summary.textContent = template
        ? `Nhà cung cấp: ${template.supplier_name}. ${template.items.map(item => `${item.label}: ${item.quantity}`).join('; ')}`
        : stockInTemplates.length > 0
            ? `Hiện có ${stockInTemplates.length} mẫu cho kho này.`
            : 'Chưa có mẫu nào. Hãy chọn sản phẩm rồi lưu thành mẫu để dùng lại.';
}

function currentRowsForTemplate() {
    const itemsByVariant = new Map();
    document.querySelectorAll('#stockinRows .item-row').forEach(row => {
        const variantId = row.querySelector('select[name*="[product_variant_id]"]')?.value;
        const quantity = Math.max(1, parseInt(row.querySelector('input[name*="[quantity]"]')?.value || '1', 10) || 1);
        if (!variantId) return;

        const existing = itemsByVariant.get(String(variantId));
        itemsByVariant.set(String(variantId), {
            product_variant_id: Number(variantId),
            quantity: quantity + (existing?.quantity || 0)
        });
    });
    return Array.from(itemsByVariant.values());
}

document.addEventListener('DOMContentLoaded', function () {
    const stockinRows = document.getElementById('stockinRows');
    const supplierSelect = document.getElementById('supplierSelect');
    const restockCheckAll = document.getElementById('restockCheckAll');
    const btnAddRestockSelected = document.getElementById('btnAddRestockSelected');
    const templateSelect = document.getElementById('stockInTemplateSelect');
    const btnApplyTemplate = document.getElementById('btnApplyStockInTemplate');
    const btnDeleteTemplate = document.getElementById('btnDeleteStockInTemplate');
    const btnSaveTemplate = document.getElementById('btnSaveStockInTemplate');
    const getRestockItems = () => Array.from(document.querySelectorAll('.js-restock-item'));

    function syncRestockCheckAllState() {
        const items = getRestockItems();
        if (!restockCheckAll || items.length === 0) {
            return;
        }

        const checkedCount = items.filter((item) => item.checked).length;
        restockCheckAll.checked = checkedCount > 0 && checkedCount === items.length;
        restockCheckAll.indeterminate = checkedCount > 0 && checkedCount < items.length;
    }

    function markRestockRow(item) {
        const row = item.closest('.js-restock-row');
        if (!row) return;
        row.classList.toggle('restock-row-active', item.checked);
    }

    syncSupplierState();
    renderStockInTemplateOptions();
    if (!supplierSelected()) {
        filterProductModalRows([]);
    }
    
    // Add default row if empty
    if (supplierSelected() && stockinRows.querySelectorAll('.item-row').length === 0) {
        window.addRow();
    }
    stockinRows.querySelectorAll('.item-row').forEach(syncRowCalculatedFields);

    supplierSelect?.addEventListener('change', async function () {
        resetStockInRows();
        syncSupplierState();

        if (!this.value) {
            productVariants = [];
            filterProductModalRows([]);
            return;
        }

        try {
            await loadSupplierProducts(this.value);
        } catch (error) {
            alert('Không tải được danh sách sản phẩm theo nhà cung cấp.');
        }
    });

    if (supplierSelect?.value) {
        loadSupplierProducts(supplierSelect.value).then(() => window.addRow()).catch(() => {});
    }

    templateSelect?.addEventListener('change', updateStockInTemplateSummary);

    btnApplyTemplate?.addEventListener('click', async function () {
        const template = selectedStockInTemplate();
        if (!template) {
            alert('Vui lòng chọn một mẫu sản phẩm.');
            return;
        }

        if (stockinRows.querySelectorAll('.item-row').length > 0
            && !confirm('Áp dụng mẫu sẽ thay thế danh sách sản phẩm hiện tại. Tiếp tục?')) {
            return;
        }

        supplierSelect.value = String(template.supplier_id);
        resetStockInRows();
        syncSupplierState();

        try {
            await loadSupplierProducts(template.supplier_id);
            let skipped = 0;
            template.items.forEach(item => {
                const supplierVariant = productVariants.find(variant => String(variant.id ?? variant.variant_id) === String(item.product_variant_id));
                if (!supplierVariant) {
                    skipped++;
                    return;
                }
                window.addRow({
                    product_variant_id: item.product_variant_id,
                    quantity: item.quantity,
                    unit_label: supplierVariant.unit_label ?? '',
                    weight_per_unit: supplierVariant.weight_per_unit ?? 1,
                    unit_cost: supplierVariant.latest_price ?? 0,
                    source_price_id: supplierVariant.price_id ?? ''
                });
            });

            alert(skipped > 0
                ? `Đã áp dụng mẫu. Bỏ qua ${skipped} sản phẩm không còn thuộc nhà cung cấp.`
                : 'Đã áp dụng mẫu sản phẩm vào phiếu nhập.');
        } catch (error) {
            alert('Không thể tải sản phẩm để áp dụng mẫu.');
        }
    });

    btnSaveTemplate?.addEventListener('click', async function () {
        const errorBox = document.getElementById('stockInTemplateError');
        const nameInput = document.getElementById('stockInTemplateName');
        const items = currentRowsForTemplate();
        errorBox?.classList.add('d-none');

        if (!nameInput?.value.trim() || !supplierSelected() || items.length === 0) {
            if (errorBox) {
                errorBox.textContent = 'Vui lòng nhập tên mẫu, chọn nhà cung cấp và có ít nhất một sản phẩm.';
                errorBox.classList.remove('d-none');
            }
            return;
        }

        this.disabled = true;
        try {
            const response = await fetch(@json(route('warehouse.stock-in-templates.store')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token())
                },
                body: JSON.stringify({
                    name: nameInput.value.trim(),
                    supplier_id: Number(supplierSelect.value),
                    items
                })
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || Object.values(payload.errors || {}).flat().join(' ') || 'Không thể lưu mẫu.');
            }

            stockInTemplates.push(payload.template);
            stockInTemplates.sort((a, b) => a.name.localeCompare(b.name, 'vi'));
            renderStockInTemplateOptions(payload.template.id);
            nameInput.value = '';
            bootstrap.Modal.getInstance(document.getElementById('saveStockInTemplateModal'))?.hide();
            alert(payload.message);
        } catch (error) {
            if (errorBox) {
                errorBox.textContent = error.message;
                errorBox.classList.remove('d-none');
            }
        } finally {
            this.disabled = false;
        }
    });

    btnDeleteTemplate?.addEventListener('click', async function () {
        const template = selectedStockInTemplate();
        if (!template) {
            alert('Vui lòng chọn mẫu cần xóa.');
            return;
        }
        if (!confirm(`Xóa mẫu "${template.name}"?`)) return;

        const deleteUrl = @json(route('warehouse.stock-in-templates.destroy', ['template' => '__ID__'])).replace('__ID__', template.id);
        try {
            const response = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token())
                }
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Không thể xóa mẫu.');

            stockInTemplates = stockInTemplates.filter(item => String(item.id) !== String(template.id));
            renderStockInTemplateOptions();
            alert(payload.message);
        } catch (error) {
            alert(error.message);
        }
    });

    restockCheckAll?.addEventListener('change', function () {
        getRestockItems().forEach((item) => {
            item.checked = this.checked;
            markRestockRow(item);
        });
        syncRestockCheckAllState();
    });

    getRestockItems().forEach((item) => {
        item.addEventListener('change', function () {
            markRestockRow(this);
            syncRestockCheckAllState();
        });
    });

    btnAddRestockSelected?.addEventListener('click', function () {
        if (!supplierSelected()) {
            alert('Vui lòng chọn nhà cung cấp trước.');
            return;
        }

        if (productVariants.length === 0) {
            alert('Chưa có sản phẩm khả dụng cho nhà cung cấp đã chọn hoặc dữ liệu đang tải.');
            return;
        }

        const selectedItems = getRestockItems().filter((item) => item.checked);
        if (selectedItems.length === 0) {
            alert('Vui lòng chọn ít nhất một sản phẩm cần nhập bổ sung.');
            return;
        }

        let addedOrUpdated = 0;
        let skipped = 0;

        selectedItems.forEach((item) => {
            const variantId = String(item.value);
            const qty = Math.max(1, parseInt(item.dataset.shortageQty || '1', 10) || 1);
            const supplierVariant = productVariants.find((variant) => String(variant.id ?? variant.variant_id) === variantId);

            if (!supplierVariant) {
                skipped++;
                return;
            }

            const result = window.addOrIncreaseVariantRow({
                product_variant_id: variantId,
                unit_label: supplierVariant.unit_label ?? '',
                weight_per_unit: supplierVariant.weight_per_unit ?? 1,
                unit_cost: supplierVariant.latest_price ?? 0,
                source_price_id: supplierVariant.price_id ?? '',
                note: 'Bổ sung hàng thiếu'
            }, qty);

            if (result.status === 'added' || result.status === 'incremented') {
                addedOrUpdated++;
                item.checked = false;
                markRestockRow(item);
            }
        });

        syncRestockCheckAllState();

        if (addedOrUpdated === 0) {
            alert('Không thể thêm sản phẩm đã chọn vào phiếu nhập.');
            return;
        }

        if (skipped > 0) {
            alert(`Đã thêm/cập nhật ${addedOrUpdated} sản phẩm. Bỏ qua ${skipped} sản phẩm không thuộc nhà cung cấp đã chọn.`);
            return;
        }

        alert(`Đã thêm/cập nhật ${addedOrUpdated} sản phẩm vào phiếu nhập.`);
    });

    stockinRows.closest('form')?.addEventListener('submit', function (event) {
        if (!supplierSelected()) {
            event.preventDefault();
            alert('Vui lòng chọn nhà cung cấp trước.');
            return;
        }

        if (stockinRows.querySelectorAll('.item-row').length === 0) {
            event.preventDefault();
            alert('Vui lòng chọn ít nhất một sản phẩm của nhà cung cấp.');
        }
    });
    
    stockinRows.addEventListener('click', function (e) {
        const row = e.target.closest('.item-row');
        if (e.target.closest('.btn-remove-row') && row) {
            row.remove();
            reindexRows();
        }
    });
    
    stockinRows.addEventListener('change', function (e) {
        const row = e.target.closest('.item-row');
        if (!row) return;
        
        if (e.target.classList.contains('variant-select')) {
            const selected = e.target.selectedOptions[0];
            const unit = selected.getAttribute('data-unit') || '';
            row.querySelector('.unit-label-input').value = unit;
            updateRowWeight(row);
        }
        if (e.target.name && (e.target.name.includes('[quantity]') || e.target.name.includes('[unit_cost]'))) {
            syncRowCalculatedFields(row);
        }
    });
    
    stockinRows.addEventListener('input', function (e) {
        const row = e.target.closest('.item-row');
        if (!row) return;
        if (e.target.name && (e.target.name.includes('[quantity]') || e.target.name.includes('[unit_cost]'))) {
            syncRowCalculatedFields(row);
        }
    });
});
</script>
@endpush
@endsection
