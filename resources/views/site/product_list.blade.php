@extends('layouts.site')

@section('content')
<div class="container py-4 product-list-page">
    <div class="product-list-hero mb-4">
        <div>
            <div class="product-list-eyebrow">Hoàng Long TNT</div>
            <h1>Danh sách sản phẩm</h1>
            <p>Chọn sản phẩm để xem size, tồn kho, thêm vào giỏ hoặc lên đơn ngay.</p>
        </div>
    </div>

    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="product-filter">
                <div class="product-filter__head">
                    <h2>Danh mục</h2>
                    <p>Lọc nhanh theo nhóm sản phẩm</p>
                </div>
                <div class="list-group list-group-flush product-category-list">
                    <a href="{{ route('pages.product_list') }}" class="list-group-item list-group-item-action {{ !isset($category) ? 'active' : '' }}">
                        <span><i class="bi bi-grid"></i> Tất cả sản phẩm</span>
                        <strong>{{ $categories->sum('products_count') }}</strong>
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('pages.product_list', ['category' => $cat->slug]) }}" class="list-group-item list-group-item-action {{ (isset($category) && $category->id == $cat->id) ? 'active' : '' }}">
                            <span><i class="bi bi-tag"></i> {{ $cat->name }}</span>
                            <strong>{{ $cat->products_count }}</strong>
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>

        <main class="col-lg-9">
            <div class="product-list-toolbar mb-3">
                <div>
                    <div class="small text-muted">Đang hiển thị</div>
                    <strong>{{ $category->name ?? 'Tất cả sản phẩm' }}</strong>
                </div>
                <div class="product-list-count">{{ $products->total() }} sản phẩm</div>
            </div>

            @if($products->count())
                <div class="row g-3">
                    @foreach($products as $product)
                        @php
                            $image = $product->avatar?->media?->file_path
                                ? asset('storage/' . $product->avatar->media->file_path)
                                : 'https://via.placeholder.com/420x320?text=San+pham';
                            $availableVariants = $product->variants->filter(fn ($variant) => (int) ($variant->available_stock ?? 0) > 0);
                            $prices = $product->variants->map(fn ($variant) => (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0))->filter(fn ($price) => $price > 0);
                            $minPrice = $prices->min();
                            $sizeLabels = $availableVariants->map(function ($variant) {
                                $attributeSize = $variant->values->firstWhere('attribute.code', 'size')?->value;
                                return $variant->size ?: $attributeSize;
                            })->filter()->unique()->values();
                        @endphp
                        <div class="col-md-6 col-xl-4">
                            <article class="product-card h-100">
                                <a href="{{ route('pages.product_detail', $product->slug) }}" class="product-card__image">
                                    <img src="{{ $image }}" alt="{{ $product->name }}">
                                </a>
                                <div class="product-card__body">
                                    <div class="product-card__category">{{ $product->category?->name ?? 'Chưa phân loại' }}</div>
                                    <h2>
                                        <a href="{{ route('pages.product_detail', $product->slug) }}">{{ $product->name }}</a>
                                    </h2>

                                    <div class="product-card__meta">
                                        <span><i class="bi bi-rulers"></i> {{ $sizeLabels->count() }} size còn hàng</span>
                                        <span><i class="bi bi-box-seam"></i> {{ $availableVariants->sum('available_stock') }} {{ strtolower($product->unit_label) }}</span>
                                    </div>

                                    <div class="product-card__sizes">
                                        @forelse($sizeLabels->take(6) as $size)
                                            <span>{{ $size }}</span>
                                        @empty
                                            <span class="is-muted">Chưa có size còn hàng</span>
                                        @endforelse
                                        @if($sizeLabels->count() > 6)
                                            <span>+{{ $sizeLabels->count() - 6 }}</span>
                                        @endif
                                    </div>

                                    <div class="product-card__footer">
                                        <div>
                                            <div class="small text-muted">Giá từ</div>
                                            <div class="product-card__price">
                                                {{ $minPrice ? number_format($minPrice, 0, ',', '.') . 'đ' : 'Liên hệ' }}
                                            </div>
                                        </div>
                                        <a href="{{ route('pages.product_detail', $product->slug) }}" class="btn product-card__button">
                                            <i class="bi bi-check2-square"></i> Chọn sản phẩm
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 product-pagination">
                    {{ $products->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="empty-products">
                    <i class="bi bi-inboxes"></i>
                    <div class="fw-semibold">Chưa có sản phẩm phù hợp</div>
                    <div class="text-muted">Vui lòng chọn danh mục khác.</div>
                </div>
            @endif
        </main>
    </div>
</div>
@endsection

@push('styles')
<style>
    .product-list-page {
        --earth: #56292d;
        --earth-dark: #3f1e21;
        --amber: #f59e0b;
        --amber-soft: #fff7e6;
        --warm-line: #ead8bf;
        --warm-muted: #7c5b3f;
        color: #2f1f17;
    }
    .product-list-page::before {
        content: "";
        position: fixed;
        inset: 0;
        z-index: -1;
        background:
            linear-gradient(180deg, rgba(255, 247, 230, .86), rgba(255, 255, 255, .98) 360px),
            #fffaf2;
    }
    .product-list-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        border: 1px solid var(--warm-line);
        border-radius: 10px;
        background: #fff;
        padding: 22px 24px;
        box-shadow: 0 16px 34px rgba(86, 41, 45, .08);
    }
    .product-list-eyebrow {
        color: var(--warm-muted);
        font-size: .82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .product-list-hero h1 {
        margin: 4px 0 6px;
        color: #2f1f17;
        font-size: clamp(1.55rem, 2vw, 2.2rem);
        font-weight: 800;
    }
    .product-list-hero p {
        margin: 0;
        color: #5f4633;
    }
    .product-filter {
        position: sticky;
        top: 88px;
        overflow: hidden;
        border: 1px solid var(--warm-line);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(86, 41, 45, .07);
    }
    .product-filter__head {
        padding: 15px 16px;
        border-bottom: 1px solid var(--warm-line);
        background: linear-gradient(180deg, #fff7e6, #fff);
    }
    .product-filter__head h2 {
        margin: 0;
        color: var(--earth);
        font-size: .96rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .product-filter__head p {
        margin: 3px 0 0;
        color: var(--warm-muted);
        font-size: .78rem;
    }
    .product-category-list {
        padding: 10px;
    }
    .product-category-list .list-group-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 0;
        border-radius: 7px;
        margin-bottom: 4px;
        padding: 9px 10px;
        color: #3f2a1f;
        background: transparent;
    }
    .product-category-list .list-group-item span {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }
    .product-category-list .list-group-item strong {
        min-width: 28px;
        border-radius: 999px;
        background: #fff7e6;
        color: #92400e;
        font-size: .75rem;
        line-height: 22px;
        text-align: center;
    }
    .product-category-list .list-group-item.active {
        background: var(--earth);
        color: #fff;
    }
    .product-category-list .list-group-item.active strong {
        background: rgba(255,255,255,.2);
        color: #fff;
    }
    .product-list-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        border: 1px solid var(--warm-line);
        border-radius: 10px;
        background: #fff;
        padding: 13px 16px;
    }
    .product-list-count {
        border-radius: 999px;
        background: var(--amber-soft);
        color: #92400e;
        padding: 6px 12px;
        font-size: .85rem;
        font-weight: 700;
    }
    .product-card {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid var(--warm-line);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 10px 22px rgba(86, 41, 45, .07);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 32px rgba(86, 41, 45, .13);
    }
    .product-card__image {
        display: block;
        aspect-ratio: 4 / 3;
        background: #fff7e6;
        overflow: hidden;
    }
    .product-card__image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .2s ease;
    }
    .product-card:hover .product-card__image img {
        transform: scale(1.03);
    }
    .product-card__body {
        display: flex;
        flex: 1;
        flex-direction: column;
        padding: 15px;
    }
    .product-card__category {
        color: var(--warm-muted);
        font-size: .78rem;
        margin-bottom: 4px;
    }
    .product-card h2 {
        min-height: 42px;
        margin: 0 0 10px;
        font-size: 1rem;
        line-height: 1.3;
        font-weight: 800;
    }
    .product-card h2 a {
        color: #2f1f17;
        text-decoration: none;
    }
    .product-card h2 a:hover { color: var(--earth); }
    .product-card__meta {
        display: grid;
        gap: 5px;
        margin-bottom: 10px;
        color: #5f4633;
        font-size: .86rem;
    }
    .product-card__sizes {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        min-height: 34px;
        margin-bottom: 14px;
    }
    .product-card__sizes span {
        display: inline-flex;
        align-items: center;
        min-height: 25px;
        padding: 3px 8px;
        border: 1px solid #f0dfc7;
        border-radius: 6px;
        background: #fffaf2;
        color: #5f4633;
        font-size: .8rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .product-card__sizes .is-muted {
        color: #9a7a5f;
        font-weight: 500;
    }
    .product-card__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid #f0dfc7;
    }
    .product-card__price {
        color: #b45309;
        font-size: 1.05rem;
        font-weight: 800;
    }
    .product-card__button {
        border: 0;
        border-radius: 7px;
        background: var(--earth);
        color: #fff;
        font-size: .85rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .product-card__button:hover {
        background: var(--earth-dark);
        color: #fff;
    }
    .product-pagination .page-link {
        color: var(--earth);
    }
    .product-pagination .active > .page-link,
    .product-pagination .page-link.active {
        border-color: var(--earth);
        background: var(--earth);
        color: #fff;
    }
    .empty-products {
        display: grid;
        place-items: center;
        min-height: 260px;
        border: 1px dashed #d7bd98;
        border-radius: 10px;
        background: #fffaf2;
        text-align: center;
    }
    .empty-products i {
        color: #b45309;
        font-size: 2rem;
    }
    @media (max-width: 991.98px) {
        .product-filter { position: static; }
    }
    @media (max-width: 575.98px) {
        .product-list-hero {
            align-items: flex-start;
            flex-direction: column;
            padding: 18px;
        }
        .product-card__footer {
            align-items: stretch;
            flex-direction: column;
        }
        .product-card__button {
            width: 100%;
        }
    }
</style>
@endpush
