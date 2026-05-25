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
                <select name="supplier_id" class="form-select" {{ $suppliers->isEmpty() ? 'disabled' : 'required' }}>
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
        <div id="itemsContainerIn">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="min-width:1100px;">
                    <thead class="table-light align-middle">
                        <tr style="text-align:center;vertical-align:middle;">
                            <th style="width:48px;">STT</th>
                            <th style="min-width:260px;">Sản phẩm / Biến thể</th>
                            <th style="width:90px;">Số lượng</th>
                            <th style="width:90px;">ĐVT</th>
                            <th style="width:110px;">Khối lượng</th>
                            <th style="width:130px;">Đơn giá nhập (đ)</th>
                            <th style="min-width:120px;">Ghi chú</th>
                            <th style="width:120px;">Thành tiền</th>
                            <th style="width:60px;"></th>
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
const productVariants = @json($productVariants ?? []);
let rowIdx = 0;

function renderRow(idx, data = {}) {
    let options = '<option value="">-- Chọn sản phẩm --</option>';
    productVariants.forEach(v => {
        options += `<option value="${v.id}" data-unit="${v.product?.unit_label ?? 'Cái'}"${data.product_variant_id == v.id ? ' selected' : ''}>${v.product?.name || ''} - ${v.name}</option>`;
    });
    
    // If the data was provided from the modal instead of productVariants array matching
    if (data.product_variant_id && !productVariants.some(v => v.id == data.product_variant_id)) {
        options += `<option value="${data.product_variant_id}" data-unit="${data.unit_label ?? ''}" selected>${data.label ?? 'Sản phẩm đã chọn'}</option>`;
    }

    const qty = data.quantity ?? 1;
    const price = data.unit_cost ?? 0;
    const lineTotal = (qty * price).toLocaleString('vi-VN');
    return `
    <tr class="item-row" data-item-row data-idx="${idx}">
        <td class="stt text-center align-middle">${idx + 1}</td>
        <td class="align-middle d-flex align-items-center gap-2" style="min-width:260px;">
            <select name="items[${idx}][product_variant_id]" class="form-select form-select-sm variant-select text-center flex-grow-1" required style="min-width:180px;">
                ${options}
            </select>
        </td>
        <td class="align-middle"><input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm text-center" min="1" value="${qty}" required></td>
        <td class="align-middle"><input type="text" class="form-control form-control-sm unit-label-input text-center" value="${data.unit_label ?? ''}" readonly tabindex="-1"></td>
        <td class="align-middle"><input type="number" name="items[${idx}][weight]" class="form-control form-control-sm text-center" min="0" step="0.01" value="${data.weight ?? 0}"></td>
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

document.addEventListener('DOMContentLoaded', function () {
    const stockinRows = document.getElementById('stockinRows');
    
    // Add default row if empty
    if (stockinRows.querySelectorAll('.item-row').length === 0) {
        window.addRow();
    }
    
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
        }
        if (e.target.name && (e.target.name.includes('[quantity]') || e.target.name.includes('[unit_cost]'))) {
            updateLineTotal(row);
        }
    });
    
    stockinRows.addEventListener('input', function (e) {
        const row = e.target.closest('.item-row');
        if (!row) return;
        if (e.target.name && (e.target.name.includes('[quantity]') || e.target.name.includes('[unit_cost]'))) {
            updateLineTotal(row);
        }
    });
});
</script>
@endpush
@endsection
