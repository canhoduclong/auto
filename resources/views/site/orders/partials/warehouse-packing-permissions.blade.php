@php
    $packingOrder = $packingOrder ?? null;
    $packingVariants = \App\Models\ProductVariant::with('product:id,name')->get(['id', 'product_id', 'size']);
    $packingCatalog = $packingVariants->map(fn ($variant) => [
        'id' => $variant->id, 'product_id' => $variant->product_id,
        'name' => $variant->product?->name ?? 'Sản phẩm', 'size' => (float) $variant->size,
    ])->values();
    $packingPolicy = old('warehouse_product_permissions', $packingOrder?->warehouse_product_permissions);
@endphp
<div class="border rounded p-3 js-sale-packing-permissions"
     data-catalog="{{ $packingCatalog->toJson() }}"
     data-policy="{{ json_encode($packingPolicy) }}"
     data-legacy-quantity="{{ (int) ($packingOrder?->warehouse_can_adjust ?? false) }}"
     data-legacy-sizes="{{ json_encode($packingOrder ? $packingOrder->warehouse_allowed_sizes : []) }}">
    <div class="fw-bold mb-2">Cho phép kho linh động điều chỉnh</div>
    <div class="form-text mb-2">Kho chỉ được phối các size đã chọn cho từng sản phẩm. Không chọn size: đóng đúng size đặt hàng.</div>
    <div class="js-product-packing-options"></div>
</div>
@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-sale-packing-permissions').forEach(box => {
        const form = box.closest('form');
        if (!form) return;
        const catalog = JSON.parse(box.dataset.catalog);
        const storedPolicy = JSON.parse(box.dataset.policy);
        const legacySizes = JSON.parse(box.dataset.legacySizes);
        const state = storedPolicy || {};
        const container = box.querySelector('.js-product-packing-options');
        let signature = '';
        function render() {
            const ids = Array.from(form.querySelectorAll('.cart-item-row[data-variant-id], [data-monitor-edit-item][data-variant-id]')).map(row => Number(row.dataset.variantId));
            const products = [...new Set(catalog.filter(v => ids.includes(v.id)).map(v => v.product_id))];
            const nextSignature = products.join(',');
            if (signature === nextSignature && container.childElementCount) return;
            signature = nextSignature;
            container.replaceChildren();
            products.forEach(productId => {
                const variants = catalog.filter(v => v.product_id === productId);
                const sizes = [...new Set(variants.map(v => v.size).filter(s => s > 0))].sort((a,b) => a-b);
                state[productId] ||= {quantity: storedPolicy === null && box.dataset.legacyQuantity === '1', sizes: storedPolicy === null ? (legacySizes ?? sizes) : []};
                const policy = state[productId];
                const section = document.createElement('div');
                section.className = 'mb-3';
                const title = document.createElement('strong');
                title.className = 'd-block mb-2';
                title.textContent = variants[0].name;
                section.append(title);
                const hidden = document.createElement('input');
                hidden.type = 'hidden'; hidden.name = `warehouse_product_permissions[${productId}][quantity]`; hidden.value = '0';
                section.append(hidden);
                function checkbox(labelText, name, checked, change) {
                    const label = document.createElement('label');
                    label.className = 'd-inline-flex align-items-center gap-2 me-3 mb-2';
                    const input = document.createElement('input'); input.type = 'checkbox'; input.className = 'form-check-input m-0';
                    if (name) input.name = name;
                    input.checked = checked; input.value = '1'; input.addEventListener('change', () => change(input));
                    label.append(input, document.createTextNode(labelText)); section.append(label); return input;
                }
                checkbox('Số lượng', hidden.name, policy.quantity === true || policy.quantity === 1 || policy.quantity === '1', input => policy.quantity = input.checked);
                const sizeInputs = [];
                const all = checkbox('Size All', '', false, input => {sizeInputs.forEach(i => i.checked = input.checked); policy.sizes = input.checked ? sizes : [];});
                section.append(document.createElement('br'));
                function syncAll() {all.checked = sizeInputs.length > 0 && sizeInputs.every(i => i.checked); all.indeterminate = sizeInputs.some(i => i.checked) && !all.checked;}
                sizes.forEach(size => {
                    const input = checkbox(`Size ${size.toFixed(1)}`, `warehouse_product_permissions[${productId}][sizes][]`, (policy.sizes || []).map(Number).includes(size), () => {policy.sizes = sizeInputs.filter(i => i.checked).map(i => Number(i.value)); syncAll();});
                    input.value = String(size); sizeInputs.push(input);
                });
                syncAll(); container.append(section);
            });
            if (!products.length) container.textContent = 'Chọn sản phẩm để cấu hình quyền đóng hàng.';
        }
        render();
        new MutationObserver(records => {if (records.some(r => !box.contains(r.target))) render();}).observe(form, {childList:true, subtree:true, attributes:true, attributeFilter:['data-variant-id']});
    });
});
</script>
@endpush
@endonce
