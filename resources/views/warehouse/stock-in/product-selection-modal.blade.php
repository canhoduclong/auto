<div class="modal fade" id="productSelectionModal" tabindex="-1" aria-labelledby="productSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="productSelectionModalLabel">
                    <i class="bi bi-box-seam me-2"></i>Chọn sản phẩm nhập kho
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom sticky-top bg-white">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="productSearchInput" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm theo tên sản phẩm hoặc biến thể...">
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 60%; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Sản phẩm - Biến thể</th>
                                <th class="text-center">ĐVT</th>
                                <th class="text-center">Tồn kho</th>
                                <th class="text-end pe-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="productSelectionList">
                            @foreach($availableVariants as $variant)
                                <tr class="product-selection-row" data-search="{{ mb_strtolower($variant['label']) }}">
                                    <td class="ps-3 fw-medium">{{ $variant['label'] }}</td>
                                    <td class="text-center">{{ $variant['unit_label'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill">{{ number_format($variant['available'] ?? 0) }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-sm btn-outline-success js-select-product" 
                                            data-id="{{ $variant['variant_id'] }}"
                                            data-product_id="{{ $variant['product_id'] ?? '' }}"
                                            data-label="{{ $variant['label'] }}"
                                            data-unit_label="{{ $variant['unit_label'] }}"
                                            data-weight_per_unit="{{ $variant['weight_per_unit'] ?? 1 }}"
                                            data-latest_price=""
                                            data-price_id=""
                                            data-available="{{ $variant['available'] ?? 0 }}">
                                            <i class="bi bi-plus-circle me-1"></i>Chọn
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="noProductsFound" style="display: none;">
                                <td colspan="4" class="text-center text-muted py-4">Không tìm thấy sản phẩm phù hợp.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const tableBody = document.getElementById('stockinRows');
    const searchInput = document.getElementById('productSearchInput');
    const productRows = document.querySelectorAll('.product-selection-row');
    const noProductsFound = document.getElementById('noProductsFound');

    // Filter products
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            let hasVisible = false;
            productRows.forEach(function (row) {
                const searchData = row.getAttribute('data-search') || '';
                const supplierAllowed = row.dataset.supplierAllowed === '1';
                if (supplierAllowed && searchData.includes(term)) {
                    row.style.display = '';
                    hasVisible = true;
                } else {
                    row.style.display = 'none';
                }
            });
            if (noProductsFound) {
                noProductsFound.style.display = hasVisible ? 'none' : '';
            }
        });
    }

    // Select product
    document.querySelectorAll('.js-select-product').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const variantId = this.getAttribute('data-id');
            const unitLabel = this.getAttribute('data-unit_label');
            const weightPerUnit = this.getAttribute('data-weight_per_unit') || '1';
            const latestPrice = this.getAttribute('data-latest_price') || '';
            const priceId = this.getAttribute('data-price_id') || '';

            if (typeof window.addOrIncreaseVariantRow === 'function') {
                const result = window.addOrIncreaseVariantRow({
                    product_variant_id: variantId,
                    unit_label: unitLabel,
                    weight_per_unit: weightPerUnit,
                    unit_cost: latestPrice || 0,
                    source_price_id: priceId,
                    quantity: 1,
                    note: ''
                }, 1);

                if (result.status === 'added' && !latestPrice) {
                    alert('Sản phẩm chưa có bảng giá hiện hành. Bạn có thể nhập tay đơn giá hoặc cập nhật bảng giá thu mua.');
                }
            }

            // Close modal
            const modalEl = document.getElementById('productSelectionModal');
            if (modalEl) {
                const bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) bsModal.hide();
            }
        });
    });
})();
</script>
