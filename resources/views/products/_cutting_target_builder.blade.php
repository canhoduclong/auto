@php
    $cuttingChoices = $cutComponentVariants->map(fn ($variant) => [
        'id' => (int) $variant->id,
        'name' => $variant->product?->name ?? 'Sản phẩm',
        'variant' => $variant->name ?: ($variant->size ?: 'Mặc định'),
        'image' => ($variant->avatar?->media ?? $variant->product?->avatar?->media)?->url,
    ])->values();
    $savedTargets = $product->cutting_targets ?? [];
    if (old('cutting_targets_present')) {
        $savedTargets = collect(old('cutting_targets', []))->filter(fn ($row) => !empty($row['enabled']))
            ->map(fn ($row) => $row['remaining'] ?? [])->all();
    }
@endphp
<div data-cutting-builder>
    <input type="hidden" name="cutting_targets_present" value="1">
    @foreach($product->cuttingComponents as $templateComponent)
        <input type="hidden" name="cutting_component_variant_ids[]" value="{{ $templateComponent->component_product_variant_id }}">
    @endforeach
    <div class="cutting-builder-heading"><strong>Thành phần chính</strong><strong>Thành phần phụ</strong></div>
    <div data-cutting-rows></div>
    <button type="button" class="btn btn-success mt-3" data-add-cutting-main><i class="bi bi-plus-circle me-1"></i>Thêm loại Pha Lóc</button>
    <div class="border rounded p-3 mt-3 bg-light" data-cutting-picker hidden>
        <div class="d-flex justify-content-between align-items-center mb-2"><strong data-picker-title></strong><button type="button" class="btn btn-sm btn-outline-secondary" data-picker-close>Đóng</button></div>
        <input type="search" class="form-control mb-3" placeholder="Tìm sản phẩm hoặc biến thể..." aria-label="Tìm thành phần pha lóc" data-picker-search>
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
    function render() {
        rows.replaceChildren(); inputs.replaceChildren();
        if (!targets.size) { const empty = document.createElement('p'); empty.className = 'text-muted'; empty.textContent = 'Bấm + Thêm loại Pha Lóc để chọn thành phần chính.'; rows.append(empty); }
        for (const [id, children] of targets) {
            hidden(`cutting_targets[${id}][enabled]`, 1);
            const row = document.createElement('div'); row.className = 'cutting-builder-row';
            row.append(card(id, () => { targets.delete(id); picker.hidden = true; render(); }));
            const secondary = document.createElement('div'); secondary.className = 'd-flex flex-wrap gap-2 align-items-start';
            children.forEach(child => {
                hidden(`cutting_targets[${id}][remaining][]`, child);
                secondary.append(card(child, () => { targets.set(id, children.filter(value => value !== child)); render(); }));
            });
            const add = document.createElement('button'); add.type = 'button'; add.className = 'cutting-builder-add'; add.textContent = '+ Thành phần phụ'; add.onclick = () => openPicker(id); secondary.append(add);
            row.append(secondary); rows.append(row);
        }
    }
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
