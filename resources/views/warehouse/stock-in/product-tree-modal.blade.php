<div class="modal fade" id="productTreeModal" tabindex="-1" aria-labelledby="productTreeModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width:800px;">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="productTreeModalLabel">
                    <i class="bi bi-box-seam me-2"></i>Chọn sản phẩm từ danh mục
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom sticky-top bg-white">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="productTreeSearchInput" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm tên sản phẩm, biến thể, SKU...">
                    </div>
                </div>
                <div class="product-tree-scroll" style="max-height: 420px; overflow-y: auto;">
                    <ul class="list-unstyled mb-0" id="productTreeList">
                        <!-- Tree will be rendered by JS -->
                    </ul>
                </div>
                <div class="d-flex justify-content-end p-3 border-top bg-light">
                    <button type="button" class="btn btn-primary btn-confirm-selection" id="btnConfirmProductTreeSelection">
                        <i class="bi bi-check2-circle me-1"></i> Xác nhận chọn
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
.product-tree-scroll { max-height: 420px; overflow-y: auto; }
.product-tree-product { font-weight: bold; background: #f3f4f6; padding: 8px 12px; border-radius: 6px; margin-bottom: 2px; cursor: pointer; display: flex; align-items: center; }
.product-tree-product .tree-toggle { margin-right: 8px; font-size: 1.1em; cursor: pointer; }
.product-tree-variant { padding: 7px 0 7px 36px; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; align-items: center; }
.product-tree-variant:hover { background: #fef9c3; }
.product-tree-variant .variant-label { flex: 1; }
.product-tree-variant .variant-stock { color: #2563eb; font-size: .95em; margin-left: 8px; }
.product-tree-variant .variant-unit { color: #64748b; font-size: .95em; margin-left: 8px; }
.product-tree-product.selected, .product-tree-variant.selected { background: #e0e7ff !important; }
</style>
@endpush
@push('scripts')
<script>
// Example data structure: [{id, name, variants: [{id, name, sku, stock, unit_label}]}]
const productTreeData = @json($productTreeData ?? []);
let expandedProducts = JSON.parse(localStorage.getItem('productTreeExpanded') || '{}');
let selectedVariants = {};
function renderProductTree(filter = '') {
    const list = document.getElementById('productTreeList');
    list.innerHTML = '';
    let found = false;
    productTreeData.forEach(product => {
        // Filter logic
        const matchProduct = product.name.toLowerCase().includes(filter) || (product.variants || []).some(v => v.name.toLowerCase().includes(filter) || (v.sku||'').toLowerCase().includes(filter));
        if (!matchProduct) return;
        found = true;
        const li = document.createElement('li');
        li.className = 'product-tree-product';
        li.setAttribute('data-product-id', product.id);
        // Expand/collapse icon
        const toggle = document.createElement('span');
        toggle.className = 'tree-toggle';
        toggle.innerHTML = expandedProducts[product.id] ? '▼' : '►';
        toggle.onclick = function(e) {
            expandedProducts[product.id] = !expandedProducts[product.id];
            localStorage.setItem('productTreeExpanded', JSON.stringify(expandedProducts));
            renderProductTree(document.getElementById('productTreeSearchInput').value.toLowerCase());
            e.stopPropagation();
        };
        li.appendChild(toggle);
        // Product name
        const nameSpan = document.createElement('span');
        nameSpan.textContent = product.name;
        li.appendChild(nameSpan);
        li.onclick = function(e) { toggle.onclick(e); };
        list.appendChild(li);
        // Variants
        if (expandedProducts[product.id]) {
            (product.variants || []).forEach(variant => {
                if (filter && !(variant.name.toLowerCase().includes(filter) || (variant.sku||'').toLowerCase().includes(filter) || product.name.toLowerCase().includes(filter))) return;
                const vli = document.createElement('li');
                vli.className = 'product-tree-variant';
                vli.setAttribute('data-variant-id', variant.id);
                vli.innerHTML = `<input type='checkbox' class='form-check-input me-2 variant-checkbox' data-variant-id='${variant.id}' ${selectedVariants[variant.id] ? 'checked' : ''}>` +
                    `<span class='variant-label'>${variant.name}${variant.sku ? ' <span class='text-muted'>['+variant.sku+']</span>' : ''}</span>` +
                    `<span class='variant-stock'>Tồn: ${variant.stock ?? 0}</span>` +
                    `<span class='variant-unit'>${variant.unit_label ?? ''}</span>`;
                // Checkbox event
                vli.querySelector('.variant-checkbox').onclick = function(e) {
                    if (this.checked) {
                        selectedVariants[variant.id] = variant;
                    } else {
                        delete selectedVariants[variant.id];
                    }
                    e.stopPropagation();
                };
                list.appendChild(vli);
            });
        }
    });
    if (!found) {
        const li = document.createElement('li');
        li.className = 'text-muted text-center py-4';
        li.textContent = 'Không tìm thấy sản phẩm phù hợp.';
        list.appendChild(li);
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('productTreeSearchInput');
    renderProductTree('');
    searchInput.addEventListener('input', function() {
        renderProductTree(this.value.toLowerCase());
    });
    document.getElementById('btnConfirmProductTreeSelection').addEventListener('click', function() {
        // Trả về danh sách variant đã chọn
        if (window.selectMultipleProductVariantsFromTree) {
            window.selectMultipleProductVariantsFromTree(Object.values(selectedVariants));
        }
        // Đóng modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('productTreeModal'));
        if (modal) modal.hide();
        // Reset chọn sau khi xác nhận
        selectedVariants = {};
        renderProductTree(document.getElementById('productTreeSearchInput').value.toLowerCase());
    });
});
</script>
@endpush
