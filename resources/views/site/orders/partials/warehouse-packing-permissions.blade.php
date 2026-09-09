@php
    $packingOrder = $packingOrder ?? null;
    $packingSizes = \App\Models\ProductVariant::query()->where('size', '>', 0)
        ->distinct()->orderBy('size')->pluck('size')->map(fn ($size) => (string) (float) $size)->unique()->values();
    $selectedPackingSizes = collect(old('warehouse_allowed_sizes', ($packingOrder ? ($packingOrder->warehouse_allowed_sizes ?? $packingSizes->all()) : [])))
        ->map(fn ($size) => (string) (float) $size)->all();
@endphp
<div class="border rounded p-3 js-sale-packing-permissions">
    <div class="fw-bold mb-2">Cho phép kho linh động điều chỉnh</div>
    <input type="hidden" name="warehouse_can_adjust" value="0">
    <label class="d-flex align-items-center gap-2 mb-2">
        <input class="form-check-input m-0" type="checkbox" name="warehouse_can_adjust" value="1"
               @checked(old('warehouse_can_adjust', $packingOrder?->warehouse_can_adjust ?? false))>
        <span>Số lượng</span>
    </label>
    <input type="hidden" name="warehouse_allowed_sizes" value="">
    <label class="d-flex align-items-center gap-2 mb-2">
        <input class="form-check-input m-0 js-sale-size-all" type="checkbox"
               @checked($packingSizes->isNotEmpty() && count(array_intersect($packingSizes->all(), $selectedPackingSizes)) === $packingSizes->count())
               onchange="this.closest('.js-sale-packing-permissions').querySelectorAll('.js-sale-size').forEach(input => input.checked = this.checked)">
        <span>Size All</span>
    </label>
    <div class="d-flex flex-wrap gap-3 ps-3">
        @foreach($packingSizes as $packingSize)
            <label class="d-flex align-items-center gap-2 mb-0">
                <span>Size {{ number_format((float) $packingSize, 1, '.', '') }}</span>
                <input class="form-check-input m-0 js-sale-size" type="checkbox" name="warehouse_allowed_sizes[]" value="{{ $packingSize }}"
                       @checked(in_array($packingSize, $selectedPackingSizes, true))
                       onchange="const box = this.closest('.js-sale-packing-permissions'); const sizes = Array.from(box.querySelectorAll('.js-sale-size')); box.querySelector('.js-sale-size-all').checked = sizes.every(input => input.checked); box.querySelector('.js-sale-size-all').indeterminate = sizes.some(input => input.checked) && !sizes.every(input => input.checked)">
            </label>
        @endforeach
    </div>
    <div class="form-text mt-2">Kho chỉ được phối các size đã chọn cho từng sản phẩm. Không chọn size: đóng đúng size đặt hàng. Khối lượng được tính theo cơ cấu thực đóng.</div>
</div>
