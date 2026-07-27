@php
    $groupedAvailableVariants = collect($availableVariants ?? [])->groupBy('product_id');
@endphp

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
                <div class="p-3 border-bottom sticky-top bg-white" style="z-index:3;">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="productSearchInput" class="form-control border-start-0 ps-0" placeholder="Tìm theo tên sản phẩm, biến thể hoặc SKU...">
                    </div>
                    <div class="small text-muted mt-2">
                        Bấm vào sản phẩm để xem và chọn biến thể.
                    </div>
                </div>
                <div id="productSelectionList" class="stockin-product-groups">
                    @foreach($groupedAvailableVariants as $productId => $variants)
                        @php
                            $firstVariant = $variants->first();
                            $productName = $firstVariant['product_name'] ?? 'Sản phẩm';
                            $productSku = $firstVariant['product_sku'] ?? '';
                        @endphp
                        <section class="stockin-product-group d-none"
                            data-product-id="{{ $productId }}"
                            data-product-search="{{ mb_strtolower(trim($productName.' '.$productSku)) }}">
                            <button type="button" class="stockin-product-toggle" aria-expanded="false">
                                <span class="stockin-product-chevron"><i class="bi bi-chevron-right"></i></span>
                                <span class="flex-grow-1 text-start">
                                    <span class="d-block fw-bold">{{ $productName }}</span>
                                    <span class="small text-muted">
                                        @if($productSku) SKU: {{ $productSku }} · @endif
                                        <span class="js-variant-count">0 biến thể</span>
                                    </span>
                                </span>
                                <span class="badge bg-light text-dark border js-product-stock">Tồn 0</span>
                            </button>

                            <div class="stockin-variant-list d-none">
                                <div class="stockin-variant-heading">
                                    <span>Biến thể</span>
                                    <span class="text-center">ĐVT</span>
                                    <span class="text-center">Tồn kho</span>
                                    <span class="text-end">Thao tác</span>
                                </div>
                                @foreach($variants as $variant)
                                    <div class="product-selection-row d-none"
                                        data-product-id="{{ $productId }}"
                                        data-search="{{ mb_strtolower(trim(($variant['variant_name'] ?? '').' '.($variant['variant_sku'] ?? '').' '.($variant['attributes'] ?? '').' '.($variant['label'] ?? ''))) }}"
                                        data-supplier-allowed="0"
                                        data-available="{{ $variant['available'] ?? 0 }}">
                                        <div class="stockin-variant-name">
                                            <span class="fw-semibold">{{ $variant['variant_name'] ?? $variant['label'] }}</span>
                                            @if(!empty($variant['variant_sku']))
                                                <span class="small text-muted d-block">SKU: {{ $variant['variant_sku'] }}</span>
                                            @endif
                                            @if(!empty($variant['attributes']))
                                                <span class="small text-muted d-block">{{ $variant['attributes'] }}</span>
                                            @endif
                                        </div>
                                        <div class="text-center">{{ $variant['unit_label'] }}</div>
                                        <div class="text-center">
                                            <span class="badge bg-primary rounded-pill">{{ number_format($variant['available'] ?? 0) }}</span>
                                        </div>
                                        <div class="text-end">
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
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                    <div id="noProductsFound" class="text-center text-muted py-5">
                        Vui lòng chọn nhà cung cấp để xem sản phẩm.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .stockin-product-groups { padding: 12px; background: #f8fafc; }
    .stockin-product-group { margin-bottom: 10px; border: 1px solid #dbe4ee; border-radius: 9px; background: #fff; overflow: hidden; }
    .stockin-product-toggle { width: 100%; border: 0; background: #fff; padding: 13px 14px; display: flex; align-items: center; gap: 10px; color: #0f172a; }
    .stockin-product-toggle:hover, .stockin-product-toggle[aria-expanded="true"] { background: #eef8f7; }
    .stockin-product-chevron { color: #0f766e; width: 20px; transition: transform .15s ease; }
    .stockin-product-toggle[aria-expanded="true"] .stockin-product-chevron { transform: rotate(90deg); }
    .stockin-variant-list { border-top: 1px solid #dbe4ee; }
    .stockin-variant-heading, .product-selection-row { display: grid; grid-template-columns: minmax(260px, 1fr) 75px 90px 100px; gap: 10px; align-items: center; }
    .stockin-variant-heading { padding: 8px 14px; background: #f1f5f9; color: #475569; font-size: .76rem; font-weight: 700; text-transform: uppercase; }
    .product-selection-row { padding: 10px 14px; border-top: 1px solid #edf2f7; }
    .product-selection-row:hover { background: #f8fafc; }
    @media (max-width: 767.98px) {
        .stockin-variant-heading { display: none; }
        .product-selection-row { grid-template-columns: minmax(0, 1fr) auto; }
        .product-selection-row > :nth-child(2), .product-selection-row > :nth-child(3) { display: none; }
    }
</style>
@endpush

<script>
(function () {
    const searchInput = document.getElementById('productSearchInput');
    const groups = Array.from(document.querySelectorAll('.stockin-product-group'));
    const noProductsFound = document.getElementById('noProductsFound');

    function visibleAllowedRows(group, term) {
        const productMatches = !term || (group.dataset.productSearch || '').includes(term);
        return Array.from(group.querySelectorAll('.product-selection-row')).filter(row => {
            if (row.dataset.supplierAllowed !== '1') return false;
            return productMatches || (row.dataset.search || '').includes(term);
        });
    }

    function setExpanded(group, expanded) {
        const toggle = group.querySelector('.stockin-product-toggle');
        const list = group.querySelector('.stockin-variant-list');
        toggle?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        list?.classList.toggle('d-none', !expanded);
    }

    function refreshGroups() {
        const term = (searchInput?.value || '').toLowerCase().trim();
        let visibleGroupCount = 0;

        groups.forEach(group => {
            const allowedRows = Array.from(group.querySelectorAll('.product-selection-row'))
                .filter(row => row.dataset.supplierAllowed === '1');
            const matchedRows = visibleAllowedRows(group, term);
            const productNameMatches = term && (group.dataset.productSearch || '').includes(term);
            const showGroup = matchedRows.length > 0;

            group.classList.toggle('d-none', !showGroup);
            if (!showGroup) return;
            visibleGroupCount++;

            const allowedIds = new Set(matchedRows.map(row => row.querySelector('.js-select-product')?.dataset.id));
            group.querySelectorAll('.product-selection-row').forEach(row => {
                row.classList.toggle('d-none', !allowedIds.has(row.querySelector('.js-select-product')?.dataset.id));
            });

            const count = group.querySelector('.js-variant-count');
            const stock = group.querySelector('.js-product-stock');
            if (count) count.textContent = `${allowedRows.length} biến thể`;
            if (stock) {
                const total = allowedRows.reduce((sum, row) => sum + Number(row.dataset.available || 0), 0);
                stock.textContent = `Tồn ${total.toLocaleString('vi-VN')}`;
            }
            if (term) setExpanded(group, productNameMatches || matchedRows.length > 0);
        });

        if (noProductsFound) {
            noProductsFound.classList.toggle('d-none', visibleGroupCount > 0);
            const hasSupplier = !!document.getElementById('supplierSelect')?.value;
            noProductsFound.textContent = !hasSupplier
                ? 'Vui lòng chọn nhà cung cấp để xem sản phẩm.'
                : term
                    ? 'Không tìm thấy sản phẩm hoặc biến thể phù hợp.'
                    : 'Nhà cung cấp này chưa có sản phẩm.';
        }
    }

    groups.forEach(group => {
        group.querySelector('.stockin-product-toggle')?.addEventListener('click', function () {
            setExpanded(group, this.getAttribute('aria-expanded') !== 'true');
        });
    });

    searchInput?.addEventListener('input', refreshGroups);
    window.syncProductSelectionGroups = refreshGroups;

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

            const modalEl = document.getElementById('productSelectionModal');
            const bsModal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
            if (bsModal) bsModal.hide();
        });
    });

    refreshGroups();
})();
</script>
