@extends('layouts.warehouse')

@section('title', 'Tạo Phiếu Nhập Kho')

@push('styles')
<style>
    .form-section { background: #fff; border-radius: 12px; box-shadow: 0 4px 14px rgba(15,23,42,.07); padding: 24px 28px; margin-bottom: 24px; }
    .form-section h5 { font-weight: 700; color: #0f172a; border-bottom: 2px solid #e0f2fe; padding-bottom: 10px; margin-bottom: 18px; }
    .stockin-items-scroll { max-height: 340px; overflow-y: auto; overflow-x: auto; padding-right: 4px; }
    .stockin-items-head, .stockin-item-grid { min-width: 980px; display: grid; grid-template-columns: 4.3fr 1.2fr 1fr 1.7fr 2fr 1.3fr 36px; gap: 8px; align-items: center; }
    .stockin-items-head { font-size: .72rem; text-transform: uppercase; }
    .stockin-item-grid .line-total { text-align: right; white-space: nowrap; }
    .calculated-weight-input { background: #f8fafc; font-weight: 700; color: #0f766e; }
    .stockin-item-grid .item-remove { justify-self: center; }
    .btn-add-row { border: 2px dashed #93c5fd; background: #eff6ff; color: #1d4ed8; border-radius: 8px; padding: 8px 18px; font-size: .82rem; font-weight: 700; transition: background .15s; }
    .btn-add-row:hover { background: #dbeafe; }
    .item-remove { color: #ef4444; background: none; border: 0; font-size: 1.1rem; line-height: 1; padding: 2px 4px; cursor: pointer; }
    .item-remove:hover { color: #b91c1c; }
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
        <div id="supplierRequiredNotice" class="alert alert-warning py-2 small mb-2">
            Vui lòng chọn nhà cung cấp trước.
        </div>
        <div id="supplierPriceNotice" class="alert alert-info py-2 small mb-2 d-none">
            Chỉ hiển thị sản phẩm thuộc nhà cung cấp đã chọn. Sản phẩm chưa có bảng giá hiện hành vẫn có thể nhập tay đơn giá.
        </div>
        <div id="itemsContainerIn">
            <div class="table-responsive">
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

@push('scripts')
<script>
let productVariants = [];
let rowIdx = 0;

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
    const requiredNotice = document.getElementById('supplierRequiredNotice');
    const priceNotice = document.getElementById('supplierPriceNotice');

    if (selectButton) {
        selectButton.disabled = !hasSupplier;
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
    const noProductsFound = document.getElementById('noProductsFound');
    if (noProductsFound) {
        noProductsFound.style.display = allowed.size > 0 ? 'none' : '';
    }
}

function resetStockInRows() {
    const rows = document.getElementById('stockinRows');
    if (!rows) return;
    rows.innerHTML = '';
    rowIdx = 0;
}

document.addEventListener('DOMContentLoaded', function () {
    const stockinRows = document.getElementById('stockinRows');
    const supplierSelect = document.getElementById('supplierSelect');
    syncSupplierState();
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
