@extends('layouts.site')

@php
    $fallbackImage = 'https://via.placeholder.com/640x520?text=San+pham';
    $mainImage = $product->avatar?->media?->file_path
        ? asset('storage/' . $product->avatar->media->file_path)
        : $fallbackImage;
    $galleryImages = collect([$product->avatar?->media])
        ->merge($product->gallery->pluck('media'))
        ->filter()
        ->unique('id')
        ->values();
    $variantPayload = $product->variants->map(function ($variant) use ($product) {
        $attributeSize = $variant->values->firstWhere('attribute.code', 'size')?->value;
        $size = $variant->size ?: $attributeSize ?: ($variant->name ?: 'Mặc định');
        $price = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
        $image = $variant->media?->file_path
            ? asset('storage/' . $variant->media->file_path)
            : null;

        return [
            'id' => $variant->id,
            'name' => $variant->name ?: $product->name,
            'sku' => $variant->sku,
            'size' => $size,
            'price' => $price,
            'stock' => max(0, (int) ($variant->available_stock ?? 0)),
            'unit_label' => strtolower($product->unit_label),
            'kg' => (float) ($variant->kg ?: $product->kg ?: 0),
            'priced_by_kg' => $variant->is_priced_by_kg !== null ? (bool) $variant->is_priced_by_kg : (bool) $product->is_priced_by_kg,
            'image' => $image,
        ];
    })->values();
    $variantPrices = $variantPayload->pluck('price')->map(fn ($price) => (float) $price)->unique()->values();
    $commonPrice = $variantPrices->count() === 1 ? (float) $variantPrices->first() : null;
    $hasCommonPrice = $commonPrice !== null;
@endphp

@section('content')
<div class="container py-4 product-detail-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <a href="{{ route('pages.product_list') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left"></i> Danh sách sản phẩm
        </a>
        <x-cart-widget :cartCount="count(session('cart', []))" />
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="product-gallery">
                <div class="product-gallery__main">
                    <img id="main-product-image" src="{{ $mainImage }}" alt="{{ $product->name }}">
                </div>
                @if($galleryImages->count() > 1)
                    <div class="product-gallery__thumbs">
                        @foreach($galleryImages as $image)
                            <button type="button" class="product-thumb {{ $loop->first ? 'active' : '' }}" data-image="{{ asset('storage/' . $image->file_path) }}">
                                <img src="{{ asset('storage/' . $image->file_path) }}" alt="{{ $product->name }}">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="product-purchase">
                <div class="text-muted mb-1">{{ $product->category?->name ?? 'Sản phẩm' }}</div>
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-2">
                    <h1 class="h3 mb-0">{{ $product->name }}</h1>
                    @if($hasCommonPrice)
                        <div class="product-common-price">
                            <span>Giá</span>
                            <strong>{{ $commonPrice > 0 ? number_format($commonPrice, 0, ',', '.') . 'đ' : 'Liên hệ' }}</strong>
                        </div>
                    @endif
                </div>
                @if($product->brand)
                    <div class="text-muted mb-3">Thương hiệu: {{ $product->brand->name }}</div>
                @endif
                @if($product->description)
                    <div class="product-description mb-4">{!! nl2br(e($product->description)) !!}</div>
                @endif

                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div class="fw-semibold">Chọn size</div>
                        <div class="small text-muted" id="selected-count">Chưa chọn size</div>
                    </div>
                    <div class="variant-grid" id="variant-grid">
                        @forelse($variantPayload as $variant)
                            @php
                                $showVariantPrice = !$hasCommonPrice || (float) $variant['price'] !== (float) $commonPrice;
                            @endphp
                            <button
                                type="button"
                                class="variant-option"
                                data-variant-id="{{ $variant['id'] }}"
                            >
                                <span class="variant-option__size">{{ $variant['size'] }}</span>
                                @if($showVariantPrice)
                                    <span class="variant-option__price">{{ $variant['price'] > 0 ? number_format($variant['price'], 0, ',', '.') . 'đ' : 'Liên hệ' }}</span>
                                @endif
                                <span class="variant-option__stock">
                                    {{ $variant['stock'] > 0 ? 'Còn ' . $variant['stock'] . ' ' . $variant['unit_label'] : 'Hết hàng - vẫn lên đơn' }}
                                </span>
                            </button>
                        @empty
                            <div class="alert alert-warning mb-0">Sản phẩm này chưa có biến thể để đặt hàng.</div>
                        @endforelse
                    </div>
                </div>

                <div id="selected-variant-panel" class="selected-variant-panel d-none">
                    <div class="selected-panel-head">
                        <div>
                            <div class="fw-semibold">Biến thể đã chọn</div>
                            <div class="small text-muted">Nhập số lượng cho từng size trước khi thêm vào giỏ.</div>
                        </div>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0" id="clear-selected-btn">Bỏ chọn</button>
                    </div>

                    <div id="selected-variant-list" class="selected-variant-list"></div>

                    <div class="selected-total mt-3">
                        <span>Tạm tính</span>
                        <strong id="total-price">0đ</strong>
                    </div>

                    <div class="selected-actions mt-3">
                        <button id="add-to-cart-btn" class="btn btn-primary">
                            <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                        </button>
                        <button id="order-now-btn" class="btn btn-order-now">
                            <i class="bi bi-receipt-cutoff"></i> Lên đơn ngay
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(($relatedProducts ?? collect())->count())
        <section class="related-products mt-5">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <h2 class="h5 mb-0">Sản phẩm liên quan</h2>
                <a href="{{ route('pages.product_list', ['category' => $product->category?->slug]) }}" class="btn btn-outline-primary btn-sm">Xem thêm</a>
            </div>
            <div class="row g-3">
                @foreach($relatedProducts as $related)
                    @php
                        $relatedImage = $related->avatar?->media?->file_path
                            ? asset('storage/' . $related->avatar->media->file_path)
                            : 'https://via.placeholder.com/420x320?text=San+pham';
                        $relatedAvailableVariants = $related->variants->filter(fn ($variant) => (int) ($variant->available_stock ?? 0) > 0);
                        $relatedPrices = $related->variants->map(fn ($variant) => (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0))->filter(fn ($price) => $price > 0);
                    @endphp
                    <div class="col-sm-6 col-lg-3">
                        <article class="related-card h-100">
                            <a href="{{ route('pages.product_detail', $related->slug) }}" class="related-card__image">
                                <img src="{{ $relatedImage }}" alt="{{ $related->name }}">
                            </a>
                            <div class="related-card__body">
                                <div class="small text-muted">{{ $related->category?->name ?? 'Sản phẩm' }}</div>
                                <h3 class="h6 mb-2">
                                    <a href="{{ route('pages.product_detail', $related->slug) }}">{{ $related->name }}</a>
                                </h3>
                                <div class="small text-muted mb-2">{{ $relatedAvailableVariants->count() }} size còn hàng</div>
                                <div class="fw-bold text-danger">
                                    {{ $relatedPrices->min() ? number_format($relatedPrices->min(), 0, ',', '.') . 'đ' : 'Liên hệ' }}
                                </div>
                                <a href="{{ route('pages.product_detail', $related->slug) }}" class="btn related-card__button mt-3">
                                    <i class="bi bi-check2-square"></i> Chọn sản phẩm
                                </a>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

@push('styles')
<style>
    .product-detail-page {
        --earth: #56292d;
        --earth-dark: #3f1e21;
        --earth-soft: #f7efe4;
        --amber: #f59e0b;
        --amber-soft: #fff7e6;
        --warm-line: #ead8bf;
        --warm-muted: #7c5b3f;
        color: #2f1f17;
    }
    .product-detail-page::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: -1;
        background:
            linear-gradient(180deg, rgba(255, 247, 230, .8), rgba(255, 255, 255, .98) 330px),
            #fffaf2;
    }
    .product-gallery__main {
        display: grid;
        place-items: center;
        aspect-ratio: 5 / 4;
        overflow: hidden;
        border: 1px solid var(--warm-line);
        border-radius: 8px;
        background: #fffdf8;
        box-shadow: 0 14px 32px rgba(86, 41, 45, .08);
    }
    .product-gallery__main img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .product-gallery__thumbs {
        display: flex;
        gap: 8px;
        margin-top: 10px;
        overflow-x: auto;
        padding-bottom: 2px;
    }
    .product-thumb {
        width: 72px;
        height: 72px;
        flex: 0 0 72px;
        overflow: hidden;
        border: 2px solid transparent;
        border-radius: 8px;
        background: #fff;
        padding: 0;
    }
    .product-thumb.active { border-color: var(--amber); }
    .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .product-purchase {
        border: 1px solid var(--warm-line);
        border-radius: 8px;
        background: #fff;
        padding: 20px;
        box-shadow: 0 16px 34px rgba(86, 41, 45, .08);
    }
    .product-common-price {
        min-width: 92px;
        text-align: right;
    }
    .product-common-price span {
        display: block;
        color: var(--warm-muted);
        font-size: .78rem;
    }
    .product-common-price strong {
        color: #b45309;
        font-size: 1rem;
    }
    .product-description {
        color: #5f4633;
        line-height: 1.6;
    }
    .variant-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
    }
    .variant-option {
        display: grid;
        align-content: center;
        gap: 3px;
        min-height: 64px;
        border: 1px solid var(--warm-line);
        border-radius: 8px;
        background: #fffdf8;
        padding: 8px 10px;
        text-align: left;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .variant-option:hover:not(:disabled),
    .variant-option.active {
        border-color: var(--amber);
        background: var(--amber-soft);
        box-shadow: 0 0 0 2px rgba(245, 158, 11, .18);
    }
    .variant-option:disabled {
        cursor: not-allowed;
        opacity: .55;
    }
    .variant-option__size {
        font-weight: 800;
        font-size: .98rem;
        line-height: 1.15;
    }
    .variant-option__price {
        color: #b45309;
        font-size: .88rem;
        font-weight: 700;
        line-height: 1.15;
    }
    .variant-option__stock {
        color: var(--warm-muted);
        font-size: .8rem;
        line-height: 1.2;
    }
    .selected-variant-panel {
        border: 1px solid var(--warm-line);
        border-radius: 8px;
        background: #fffaf2;
        padding: 14px;
    }
    .selected-panel-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--warm-line);
    }
    .selected-variant-list {
        display: grid;
        gap: 8px;
        margin-top: 10px;
    }
    .selected-variant-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 112px 34px;
        align-items: center;
        gap: 10px;
        padding: 9px;
        border: 1px solid #f0dfc7;
        border-radius: 8px;
        background: #fff;
    }
    .selected-variant-row__title {
        font-weight: 700;
        line-height: 1.2;
    }
    .selected-variant-row__meta {
        color: var(--warm-muted);
        font-size: .78rem;
        line-height: 1.3;
    }
    .selected-variant-row .form-control {
        height: 34px;
        padding: 4px 8px;
    }
    .selected-variant-remove {
        width: 34px;
        height: 34px;
        border: 1px solid #fecaca;
        border-radius: 7px;
        background: #fff;
        color: #dc2626;
    }
    .selected-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid var(--warm-line);
        padding-top: 12px;
    }
    .selected-total strong {
        color: #b45309;
        font-size: 1.15rem;
    }
    .selected-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 10px;
    }
    .related-card {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid var(--warm-line);
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 22px rgba(86, 41, 45, .07);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .related-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 28px rgba(86, 41, 45, .12);
    }
    .related-card__image {
        display: block;
        aspect-ratio: 4 / 3;
        overflow: hidden;
        background: #fff7e6;
    }
    .related-card__image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .related-card__body {
        display: flex;
        flex: 1;
        flex-direction: column;
        padding: 12px;
    }
    .related-card h3 a {
        color: #2f1f17;
        text-decoration: none;
    }
    .related-card h3 a:hover { color: var(--earth); }
    .related-card__button,
    .product-detail-page .btn-primary,
    .product-detail-page .btn-order-now {
        border: 0;
        border-radius: 7px;
        background: var(--earth);
        color: #fff;
        font-weight: 700;
    }
    .related-card__button:hover,
    .product-detail-page .btn-primary:hover,
    .product-detail-page .btn-order-now:hover {
        background: var(--earth-dark);
        color: #fff;
    }
    .product-detail-page .btn-order-now {
        background: #b45309;
    }
    .product-detail-page .btn-order-now:hover {
        background: #92400e;
    }
    .product-detail-page .btn-outline-primary {
        border-color: var(--earth);
        color: var(--earth);
    }
    .product-detail-page .btn-outline-primary:hover {
        background: var(--earth);
        color: #fff;
    }
    @media (max-width: 575.98px) {
        .variant-grid { grid-template-columns: 1fr; }
        .product-purchase { padding: 16px; }
        .selected-variant-row {
            grid-template-columns: minmax(0, 1fr) 88px 34px;
            gap: 8px;
        }
        .selected-actions { grid-template-columns: 1fr; }
    }
    @media (min-width: 576px) and (max-width: 767.98px) {
        .variant-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 768px) and (max-width: 1199.98px) {
        .variant-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const variants = @json($variantPayload, JSON_UNESCAPED_UNICODE);
    const hasCommonPrice = @json($hasCommonPrice);
    const commonPrice = @json($commonPrice);
    const checkoutUrl = @json(route('cart.checkout'));
    const variantMap = new Map(variants.map(variant => [String(variant.id), variant]));
    const selectedVariants = new Map();
    const mainImage = document.getElementById('main-product-image');
    const addButton = document.getElementById('add-to-cart-btn');
    const orderNowButton = document.getElementById('order-now-btn');
    const selectedPanel = document.getElementById('selected-variant-panel');
    const selectedList = document.getElementById('selected-variant-list');
    const selectedCount = document.getElementById('selected-count');
    const clearSelectedButton = document.getElementById('clear-selected-btn');
    const totalPrice = document.getElementById('total-price');

    function formatMoney(value) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(value || 0));
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[char];
        });
    }

    function priceLabel(variant) {
        if (hasCommonPrice || Number(variant.price || 0) === Number(commonPrice || 0)) {
            return '';
        }

        return Number(variant.price || 0) > 0 ? formatMoney(variant.price) : 'Liên hệ';
    }

    function sanitizeQuantity(input, variant) {
        const max = Math.max(Number(variant.stock || 0), 0);
        let quantity = parseInt(input.value, 10) || 1;
        quantity = max > 0 ? Math.min(Math.max(quantity, 1), max) : Math.max(quantity, 1);
        input.value = quantity;
        return quantity;
    }

    function updateTotal() {
        let total = 0;
        let hasContactPrice = false;

        selectedList.querySelectorAll('.selected-variant-qty').forEach(input => {
            const variant = variantMap.get(String(input.dataset.variantId));
            if (!variant) {
                return;
            }

            const quantity = sanitizeQuantity(input, variant);
            const price = Number(variant.price || 0);
            if (price <= 0) {
                hasContactPrice = true;
                return;
            }

            total += price * quantity;
        });

        totalPrice.textContent = hasContactPrice && total <= 0 ? 'Liên hệ' : formatMoney(total);
    }

    function renderSelected() {
        const count = selectedVariants.size;
        selectedPanel.classList.toggle('d-none', count < 1);
        selectedCount.textContent = count > 0 ? count + ' size đã chọn' : 'Chưa chọn size';
        addButton.disabled = count < 1;
        orderNowButton.disabled = count < 1;

        selectedList.innerHTML = '';
        selectedVariants.forEach(variant => {
            const row = document.createElement('div');
            row.className = 'selected-variant-row';
            row.dataset.variantId = variant.id;
            const variantPriceLabel = priceLabel(variant);
            row.innerHTML = `
                <div>
                    <div class="selected-variant-row__title">${escapeHtml(variant.size)}</div>
                    <div class="selected-variant-row__meta">
                        ${variant.sku ? 'SKU: ' + escapeHtml(variant.sku) + ' | ' : ''}
                        ${Number(variant.stock || 0) > 0 ? 'Tồn: ' + escapeHtml(variant.stock) + ' ' + escapeHtml(variant.unit_label) : 'Hết hàng - vẫn lên đơn'}
                        ${variantPriceLabel ? ' | Giá: ' + escapeHtml(variantPriceLabel) : ''}
                    </div>
                </div>
                <input type="number" class="form-control selected-variant-qty" data-variant-id="${escapeHtml(variant.id)}" value="1" min="1" ${Number(variant.stock || 0) > 0 ? 'max="' + escapeHtml(variant.stock) + '"' : ''} step="1" aria-label="Số lượng ${escapeHtml(variant.size)}">
                <button type="button" class="selected-variant-remove" data-variant-id="${escapeHtml(variant.id)}" aria-label="Bỏ chọn ${escapeHtml(variant.size)}">
                    <i class="bi bi-x-lg"></i>
                </button>
            `;
            selectedList.appendChild(row);
        });

        updateTotal();
    }

    function toggleVariant(variantId) {
        const key = String(variantId);
        const variant = variantMap.get(key);
        if (!variant) {
            return;
        }

        if (selectedVariants.has(key)) {
            selectedVariants.delete(key);
        } else {
            selectedVariants.set(key, variant);
            if (variant.image && mainImage) {
                mainImage.src = variant.image;
            }
        }

        document.querySelectorAll('.variant-option').forEach(button => {
            button.classList.toggle('active', selectedVariants.has(String(button.dataset.variantId)));
        });

        renderSelected();
    }

    document.querySelectorAll('.product-thumb').forEach(button => {
        button.addEventListener('click', function () {
            document.querySelectorAll('.product-thumb').forEach(item => item.classList.remove('active'));
            button.classList.add('active');
            if (mainImage) {
                mainImage.src = button.dataset.image;
            }
        });
    });

    document.querySelectorAll('.variant-option').forEach(button => {
        button.addEventListener('click', function () {
            toggleVariant(button.dataset.variantId);
        });
    });

    selectedList.addEventListener('input', function (event) {
        if (event.target.classList.contains('selected-variant-qty')) {
            updateTotal();
        }
    });

    selectedList.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.selected-variant-remove');
        if (!removeButton) {
            return;
        }

        selectedVariants.delete(String(removeButton.dataset.variantId));
        document.querySelector(`.variant-option[data-variant-id="${CSS.escape(String(removeButton.dataset.variantId))}"]`)?.classList.remove('active');
        renderSelected();
    });

    clearSelectedButton?.addEventListener('click', function () {
        selectedVariants.clear();
        document.querySelectorAll('.variant-option.active').forEach(button => button.classList.remove('active'));
        renderSelected();
    });

    function selectedItems() {
        return Array.from(selectedList.querySelectorAll('.selected-variant-qty')).map(input => {
            const variant = variantMap.get(String(input.dataset.variantId));
            return {
                variant,
                quantity: variant ? sanitizeQuantity(input, variant) : 0
            };
        }).filter(item => item.variant && item.quantity > 0);
    }

    async function addSelectedItems(options = {}) {
        if (selectedVariants.size < 1) {
            window.showToast('Vui lòng chọn ít nhất một size.', 'warning');
            return false;
        }

        const items = selectedItems();

        if (items.length < 1) {
            window.showToast('Vui lòng nhập số lượng hợp lệ.', 'warning');
            return false;
        }

        addButton.disabled = true;
        orderNowButton.disabled = true;

        try {
            for (const item of items) {
                await window.siteCart.addVariant(item.variant.id, item.quantity);
            }

            if (options.redirectToCheckout) {
                window.location.href = checkoutUrl;
                return true;
            }

            window.showToast('Đã thêm các size đã chọn vào giỏ.', 'success');
            selectedVariants.clear();
            document.querySelectorAll('.variant-option.active').forEach(button => button.classList.remove('active'));
            renderSelected();
            return true;
        } catch (error) {
            window.showToast(error.message || 'Một số size chưa thêm được vào giỏ.', 'error');
            return false;
        } finally {
            addButton.disabled = selectedVariants.size < 1;
            orderNowButton.disabled = selectedVariants.size < 1;
        }
    }

    addButton?.addEventListener('click', async function () {
        await addSelectedItems();
    });

    orderNowButton?.addEventListener('click', async function () {
        await addSelectedItems({ redirectToCheckout: true });
    });

    const defaultVariant = variants.reduce((best, variant) => {
        if (!best) {
            return variant;
        }

        return Number(variant.stock || 0) > Number(best.stock || 0) ? variant : best;
    }, null);

    if (defaultVariant) {
        toggleVariant(defaultVariant.id);
    }
});
</script>
@endpush
