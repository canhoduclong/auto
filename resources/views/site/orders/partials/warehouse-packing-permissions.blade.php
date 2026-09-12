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
<style>
    .js-sale-packing-permissions .js-packing-product-title {
        color: #0f172a;
        font-size: .9rem;
        font-weight: 800;
    }
    .js-sale-packing-permissions .js-packing-option-label {
        color: #334155;
        font-size: .82rem;
        font-weight: 600;
    }
    .js-sale-packing-permissions .js-packing-size-label {
        color: #475569;
        font-size: .8rem;
        font-weight: 600;
    }
</style>
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
                const orderedSizes = [...new Set(variants.filter(v => ids.includes(v.id)).map(v => v.size).filter(s => s > 0))];
                state[productId] ||= {quantity: storedPolicy === null && box.dataset.legacyQuantity === '1', sizes: storedPolicy === null ? (legacySizes ?? []) : []};
                const policy = state[productId];
                const section = document.createElement('div');
                section.className = 'mb-3';
                const title = document.createElement('strong');
                title.className = 'd-block mb-2 js-packing-product-title';
                title.textContent = variants[0].name;
                section.append(title);
                const hidden = document.createElement('input');
                hidden.type = 'hidden'; hidden.name = `warehouse_product_permissions[${productId}][quantity]`; hidden.value = '0';
                section.append(hidden);
                function checkbox(labelText, name, checked, change) {
                    const label = document.createElement('label');
                    label.className = 'd-inline-flex align-items-center gap-2 me-3 mb-2 js-packing-option-label';
                    const input = document.createElement('input'); input.type = 'checkbox'; input.className = 'form-check-input m-0';
                    if (name) input.name = name;
                    input.checked = checked; input.value = '1'; input.addEventListener('change', () => change(input));
                    label.append(input, document.createTextNode(labelText)); section.append(label); return input;
                }
                function lockSize(input, size) {
                    input.checked = true;
                    input.disabled = true;
                    const hiddenSize = document.createElement('input');
                    hiddenSize.type = 'hidden';
                    hiddenSize.name = `warehouse_product_permissions[${productId}][sizes][]`;
                    hiddenSize.value = String(size);
                    section.append(hiddenSize);
                    input.parentElement.classList.add('js-packing-size-locked');
                }
                checkbox('1. Sản lượng', hidden.name, policy.quantity === true || policy.quantity === 1 || policy.quantity === '1', input => policy.quantity = input.checked);
                const sizeInputs = [];
                const readable = sizes.length === 1;
                const lockedSizes = readable ? [sizes[0]] : orderedSizes;
                policy.sizes = [...new Set([...(policy.sizes || []).map(Number), ...lockedSizes])];
                const lockedSize = readable ? sizes[0] : null;
                if (readable && !(policy.sizes || []).map(Number).includes(lockedSize)) {
                    policy.sizes = [lockedSize];
                }
                if (!readable) {
                    const all = checkbox('2. Size All', '', false, input => {sizeInputs.forEach(i => i.checked = input.checked); policy.sizes = input.checked ? sizes : [];});
                    all.parentElement.classList.add('js-packing-size-label');
                    section.append(document.createElement('br'));
                    function syncAll() {all.checked = false; all.indeterminate = false;}
                    sizes.forEach(size => {
                        const isLocked = lockedSizes.some(locked => Math.abs(locked - size) < 0.0001);
                        const input = checkbox(`Size ${size.toFixed(1)}`, `warehouse_product_permissions[${productId}][sizes][]`, (policy.sizes || []).map(Number).includes(size), () => {policy.sizes = sizeInputs.filter(i => i.checked).map(i => Number(i.value)); syncAll();});
                        input.parentElement.classList.add('js-packing-size-label');
                        input.value = String(size); sizeInputs.push(input);
                        if (isLocked) lockSize(input, size);
                    });
                } else {
                    const lockedInput = checkbox(`Size ${lockedSize.toFixed(1)}`, `warehouse_product_permissions[${productId}][sizes][]`, true, () => {});
                    lockSize(lockedInput, lockedSize);
                    lockedInput.parentElement.classList.add('js-packing-size-label');
                    lockedInput.value = String(lockedSize);
                    sizeInputs.push(lockedInput);
                    section.append(document.createElement('br'));
                    sizes.filter(size => Math.abs(size - lockedSize) > 0.0001).forEach(size => {
                        const input = checkbox(`Size ${size.toFixed(1)}`, `warehouse_product_permissions[${productId}][sizes][]`, (policy.sizes || []).map(Number).includes(size), () => {
                            const selected = sizeInputs.filter(i => i.checked && !i.disabled).map(i => Number(i.value));
                            const fixed = sizeInputs.filter(i => i.disabled).map(i => Number(i.value));
                            if (selected.length > 1) {
                                const lastSelected = input.value;
                                sizeInputs.forEach(candidate => {
                                    if (candidate !== input && !candidate.disabled) {
                                        candidate.checked = false;
                                    }
                                });
                                policy.sizes = [...fixed, Number(lastSelected)];
                                return;
                            }
                            policy.sizes = [...fixed, ...selected];
                        });
                        input.parentElement.classList.add('js-packing-size-label');
                        input.value = String(size); sizeInputs.push(input);
                        if (lockedSizes.some(locked => Math.abs(locked - size) < 0.0001)) lockSize(input, size);
                    });
                    policy.sizes = [lockedSize];
                }
                container.append(section);
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
