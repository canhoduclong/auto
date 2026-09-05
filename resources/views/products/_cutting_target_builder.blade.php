@php
    $cuttingChoices = $cutComponentVariants->groupBy('product_id')->map(fn ($variants) => [
        'id' => (int) $variants->first()->product_id,
        'name' => $variants->first()->product?->name ?? 'Sản phẩm',
        'variant' => $variants->count().' biến thể',
        'percentage' => $variants->first()->product?->cutting_percentage,
        'edit_url' => route('products.edit', $variants->first()->product_id),
        'image' => $variants->first()->product?->avatar?->media?->url,
    ])->values();
    $savedTargets = $product->cutting_product_targets;
    if ($savedTargets === null) {
        $savedTargets = [];
        $legacyIds = collect(array_keys($product->cutting_targets ?? []))->merge(collect($product->cutting_targets ?? [])->flatten());
        $legacyProducts = \App\Models\ProductVariant::whereIn('id', $legacyIds)->pluck('product_id', 'id');
        foreach ($product->cutting_targets ?? [] as $targetId => $remaining) {
            $mainId = $legacyProducts->get($targetId);
            if (!$mainId) continue;
            $savedTargets[$mainId] = collect($savedTargets[$mainId] ?? [])->merge(collect($remaining)->map(fn ($id) => $legacyProducts->get($id)))->filter(fn ($id) => $id && $id !== $mainId)->unique()->values()->all();
        }
    }
    if (old('cutting_product_targets_present')) {
        $savedTargets = collect(old('cutting_product_targets', []))->filter(fn ($row) => !empty($row['enabled']))
            ->map(fn ($row) => $row['remaining'] ?? [])->all();
    }
@endphp
<div data-cutting-builder>
    <input type="hidden" name="cutting_product_targets_present" value="1">
    @foreach($product->cuttingComponents as $templateComponent)
        <input type="hidden" name="cutting_component_variant_ids[]" value="{{ $templateComponent->component_product_variant_id }}">
    @endforeach
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
        <label for="cutting-preview-size">Chọn size nguyên con để xem khối lượng:</label>
        <select id="cutting-preview-size" class="form-select w-auto" data-cutting-size>
            @foreach($product->variants as $sourceSize)
                <option value="{{ (float) $sourceSize->effective_kg }}">{{ $sourceSize->name ?: $sourceSize->size }} — {{ (float) $sourceSize->effective_kg }} kg</option>
            @endforeach
        </select>
        <span class="small text-muted">Khối lượng = size × tỷ lệ %. Thành phần chính = 100% − tổng thành phần phụ.</span>
    </div>
    <div class="cutting-builder-heading"><strong>Thành phần chính</strong><strong>Thành phần phụ</strong></div>
    <div data-cutting-rows></div>
    <button type="button" class="btn btn-success mt-3" data-add-cutting-main><i class="bi bi-plus-circle me-1"></i>Thêm loại Pha Lóc</button>
    <div class="border rounded p-3 mt-3 bg-light" data-cutting-picker hidden>
        <div class="d-flex justify-content-between align-items-center mb-2"><strong data-picker-title></strong><button type="button" class="btn btn-sm btn-outline-secondary" data-picker-close>Đóng</button></div>
        <input type="search" class="form-control mb-3" placeholder="Tìm sản phẩm pha lóc..." aria-label="Tìm thành phần pha lóc" data-picker-search>
        <div class="d-flex flex-wrap gap-2" style="max-height:320px;overflow:auto" data-picker-options></div>
    </div>
    <div data-cutting-inputs></div>
</div>
@push('styles')
<style>
.cutting-builder-heading,.cutting-builder-row{display:grid;grid-template-columns:300px minmax(0,1fr);gap:32px;margin-bottom:20px}
.cutting-builder-card{display:flex;align-items:center;gap:10px;position:relative;border:1px solid #dfe5ed;border-radius:10px;background:white;padding:12px 32px 12px 12px;min-height:86px;text-align:left;color:#10243a;max-width:100%}
.cutting-builder-card img{width:58px;height:58px;object-fit:contain;border-radius:6px}
.cutting-builder-card small{display:block;color:#64748b;margin-top:5px}
.cutting-builder-remove{position:absolute;right:5px;top:3px;border:0;background:transparent;color:#b42318;font-size:20px}
.cutting-builder-add{border:1px dashed #16815c;border-radius:10px;background:#f0fdf6;color:#16815c;padding:12px;min-height:86px}
@media(max-width:767px){.cutting-builder-heading{display:none}.cutting-builder-row{grid-template-columns:1fr;gap:12px;padding-bottom:16px;border-bottom:1px solid #eee}}
</style>
@endpush
@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-cutting-builder]');
    if (!root) return;
    const choices = @json($cuttingChoices);
    const saved = @json($savedTargets);
    const percentages = @json(old('cutting_percentages', $product->cutting_percentages ?? []));
    const sizeInput = root.querySelector('[data-cutting-size]');
    const percentState = Object.fromEntries(Object.entries(percentages).map(([id, values]) => [id, {...values}]));
    const byId = new Map(choices.map(choice => [String(choice.id), choice]));
    const targets = new Map(Object.entries(saved).map(([id, children]) => [id, children.map(String)]));
    const rows = root.querySelector('[data-cutting-rows]');
    const inputs = root.querySelector('[data-cutting-inputs]');
    const picker = root.querySelector('[data-cutting-picker]');
    const search = root.querySelector('[data-picker-search]');
    let parent = null;
    function hidden(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = name; input.value = value; inputs.append(input);
    }
    function card(id, onRemove) {
        const choice = byId.get(String(id)) || {name: 'Thành phần #' + id, variant: 'Đã lưu'};
        const node = document.createElement('div'); node.className = 'cutting-builder-card';
        if (choice.image) { const img = document.createElement('img'); img.src = choice.image; img.alt = ''; node.append(img); }
        const text = document.createElement('div'); const title = document.createElement('strong'); title.textContent = choice.name;
        const subtitle = document.createElement('small'); subtitle.textContent = choice.variant;
        text.append(title, subtitle); node.append(text);
        if (onRemove) { const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'cutting-builder-remove'; remove.textContent = '×'; remove.setAttribute('aria-label', 'Bỏ ' + choice.name); remove.onclick = onRemove; node.append(remove); }
        return node;
    }
    function withPercentage(id, mainId, onRemove) {
        const node = card(id, onRemove);
        const field = document.createElement('div');
        field.className = 'mt-2';
        const label = document.createElement('label'); label.className = 'small'; label.textContent = 'Tỷ lệ khối lượng (%)';
        const input = document.createElement('input'); input.type = 'number'; input.min = id === mainId ? '0.001' : '0'; input.max = '100'; input.step = '0.001'; input.className = 'form-control form-control-sm';
        input.readOnly = true;
        input.style.width = '115px';
        input.value = id === mainId ? (percentState[mainId]?.[id] ?? '') : (byId.get(id)?.percentage ?? ''); input.placeholder = 'Nhập %';
        input.dataset.percentMain = mainId; input.dataset.percentComponent = id;
        label.append(input); field.append(label);
        if (id !== mainId && byId.get(id)?.edit_url) { const link = document.createElement('a'); link.href = byId.get(id).edit_url; link.target = '_blank'; link.rel = 'noopener'; link.textContent = 'Cập nhật tỷ lệ sản phẩm'; link.className = 'small d-block'; field.append(link); }
        const weight = document.createElement('small'); weight.dataset.componentWeight = id; field.append(weight);
        node.querySelector('strong').parentElement.append(field);
        input.oninput = () => { percentState[mainId] ||= {}; percentState[mainId][id] = input.value; updateWeights(); };
        return node;
    }
    function updateWeights() {
        const size = Number(sizeInput.value) || 0;
        for (const [mainId, children] of targets) {
            const fields = [...rows.querySelectorAll('input[data-percent-main]')].filter(input => input.dataset.percentMain === mainId);
            const mainInput = fields.find(input => input.dataset.percentComponent === mainId);
            const secondary = fields.filter(input => input !== mainInput);
            const any = secondary.every(input => input.value !== '');
            const secondaryTotal = secondary.reduce((sum, input) => sum + (Number(input.value) || 0), 0);
            mainInput.value = any ? String(Number(Math.max(0, 100 - secondaryTotal).toFixed(6))) : '';
            percentState[mainId] ||= {};
            percentState[mainId][mainId] = mainInput.value;
            const total = secondaryTotal + (Number(mainInput.value) || 0);
            fields.forEach(input => {
                input.required = any && input !== mainInput;
                input.setCustomValidity(total > 100.000001 ? 'Tổng tỷ lệ của thành phần chính và phụ không được vượt 100%.' : '');
                input.closest('.cutting-builder-card').querySelector('[data-component-weight]').textContent = input.value === '' ? 'Chưa cấu hình tỷ lệ' : (size * Number(input.value) / 100).toLocaleString('vi-VN', {maximumFractionDigits:3}) + ' kg / con';
            });
            const summary = rows.querySelector(`[data-percent-summary="${mainId}"]`);
            if (summary) summary.textContent = any ? `Tổng: ${total.toLocaleString('vi-VN')}% · Thành phần chính tự tính: ${mainInput.value}%` : 'Cập nhật tỷ lệ chung tại các sản phẩm thành phần phụ để tính khối lượng.';
        }
    }
    function render() {
        rows.replaceChildren(); inputs.replaceChildren();
        if (!targets.size) { const empty = document.createElement('p'); empty.className = 'text-muted'; empty.textContent = 'Bấm + Thêm loại Pha Lóc để chọn thành phần chính.'; rows.append(empty); }
        for (const [id, children] of targets) {
            hidden(`cutting_product_targets[${id}][enabled]`, 1);
            const row = document.createElement('div'); row.className = 'cutting-builder-row';
            row.append(withPercentage(id, id, () => { targets.delete(id); picker.hidden = true; render(); }));
            const secondary = document.createElement('div'); secondary.className = 'd-flex flex-wrap gap-2 align-items-start';
            children.forEach(child => {
                hidden(`cutting_product_targets[${id}][remaining][]`, child);
                secondary.append(withPercentage(child, id, () => { targets.set(id, children.filter(value => value !== child)); render(); }));
            });
            const add = document.createElement('button'); add.type = 'button'; add.className = 'cutting-builder-add'; add.textContent = '+ Thành phần phụ'; add.onclick = () => openPicker(id); secondary.append(add);
            const summary = document.createElement('div'); summary.dataset.percentSummary = id; summary.className = 'small text-muted w-100'; secondary.append(summary);
            row.append(secondary); rows.append(row);
        }
        updateWeights();
    }
    sizeInput.onchange = updateWeights;
    function renderPicker() {
        const options = root.querySelector('[data-picker-options]'); options.replaceChildren();
        const term = search.value.trim().toLocaleLowerCase('vi');
        choices.filter(choice => {
            const id = String(choice.id);
            return (parent === null ? !targets.has(id) : id !== parent && !targets.get(parent)?.includes(id))
                && `${choice.name} ${choice.variant}`.toLocaleLowerCase('vi').includes(term);
        }).forEach(choice => {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'border-0 bg-transparent p-0'; button.append(card(choice.id));
            button.onclick = () => {
                const id = String(choice.id);
                if (parent === null) targets.set(id, []);
                else targets.set(parent, [...targets.get(parent), id]);
                picker.hidden = true; render();
            };
            options.append(button);
        });
        if (!options.children.length) options.textContent = 'Không còn thành phần phù hợp.';
    }
    function openPicker(id) {
        parent = id; search.value = ''; picker.hidden = false;
        root.querySelector('[data-picker-title]').textContent = id === null ? 'Chọn thành phần chính' : 'Chọn thành phần phụ cho ' + (byId.get(id)?.name || id);
        renderPicker(); search.focus(); picker.scrollIntoView({block:'nearest', behavior:'smooth'});
    }
    root.querySelector('[data-add-cutting-main]').onclick = () => openPicker(null);
    root.querySelector('[data-picker-close]').onclick = () => { picker.hidden = true; };
    search.oninput = renderPicker;
    render();
})();
</script>
@endpush
