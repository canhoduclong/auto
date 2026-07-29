@if($products->isEmpty())
    <div class="text-center text-muted py-4">Không tìm thấy sản phẩm phù hợp.</div>
@else
    <div class="monitor-product-toolbar">
        <p class="text-muted mb-0 small">
            Hiển thị {{ $products->firstItem() }} đến {{ $products->lastItem() }} trên tổng {{ $products->total() }} sản phẩm
        </p>
        <div class="d-flex align-items-center gap-2">
            <label for="per-page-select" class="form-label mb-0 small text-muted">Mỗi trang</label>
            <select class="form-select form-select-sm" id="per-page-select">
                @foreach([5, 10, 25, 50] as $option)
                    <option value="{{ $option }}" {{ $products->perPage() === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="monitor-product-list">
        @foreach($products as $product)
            @php
                $imageUrl = $product->avatar?->media?->file_path
                    ? asset('storage/' . $product->avatar->media->file_path)
                    : 'https://via.placeholder.com/72?text=SP';
            @endphp
            <article class="monitor-product-card" data-product-id="{{ $product->id }}">
                <button type="button" class="monitor-product-choice" aria-expanded="false">
                    <span class="monitor-product-main">
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="monitor-product-thumb">
                        <span>
                            <strong class="monitor-product-name">{{ $product->name }}</strong>
                            <span class="monitor-product-meta">{{ $product->variants->count() }} biến thể · {{ $product->unit_label }}</span>
                        </span>
                    </span>
                    <span class="monitor-product-choice-label">Chọn sản phẩm <i class="bi bi-chevron-down ms-1"></i></span>
                </button>

                <div class="monitor-product-variants" hidden>
                    <div class="small fw-bold mb-2">Chọn biến thể</div>
                    <div class="monitor-variant-grid">
                        @foreach($product->variants as $variant)
                            @php
                                $sizeRaw = strtolower(str_replace(',', '.', trim((string) ($variant->size ?? ''))));
                                preg_match('/([0-9]*\.?[0-9]+)/', $sizeRaw, $sizeMatches);
                                $sizeKg = (float) ($sizeMatches[1] ?? 0);
                                if (str_contains($sizeRaw, 'g') && !str_contains($sizeRaw, 'kg')) {
                                    $sizeKg /= 1000;
                                }
                                $weight = (float) ($variant->kg ?: $product->kg ?: $sizeKg);
                                $weight = round(max(0.01, $weight), 3);
                                $isPricedByKg = $variant->is_priced_by_kg !== null
                                    ? (bool) $variant->is_priced_by_kg
                                    : (bool) $product->is_priced_by_kg;
                                $price = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
                                $minPrice = (float) ($variant->latestPriceRule?->min_price ?? 0);
                                $variantImage = $variant->media?->file_path
                                    ? asset('storage/' . $variant->media->file_path)
                                    : $imageUrl;
                                $size = $variant->size ?: ($variant->name ?: 'Mặc định');
                                $availableStock = max(0, (float) ($variant->available_stock ?? 0));
                                $onHandStock = max(0, (float) ($variant->on_hand_stock ?? 0));
                                $reservedStock = max(0, (float) ($variant->reserved_stock ?? 0));
                                $stockUnit = strtolower((string) $product->unit_label);
                            @endphp
                            <button
                                type="button"
                                class="monitor-variant-option"
                                data-variant-id="{{ $variant->id }}"
                                data-variant-name="{{ $product->name }}"
                                data-variant-sku="{{ $variant->sku }}"
                                data-variant-size="{{ $size }}"
                                data-variant-price="{{ $price }}"
                                data-variant-min-price="{{ $minPrice }}"
                                data-variant-stock="{{ $availableStock }}"
                                data-variant-on-hand-stock="{{ $onHandStock }}"
                                data-variant-reserved-stock="{{ $reservedStock }}"
                                data-variant-unit-label="{{ $product->unit_label }}"
                                data-variant-weight="{{ number_format($weight, 3, '.', '') }}"
                                data-variant-is-priced-by-kg="{{ $isPricedByKg ? '1' : '0' }}"
                                data-variant-image="{{ $variantImage }}">
                                <span class="monitor-variant-size">{{ $size }}</span>
                                <span>{{ number_format($price, 0, ',', '.') }}đ</span>
                                <small class="monitor-variant-availability {{ $availableStock > 0 ? 'is-available' : 'is-unavailable' }}">
                                    Khả dụng: {{ number_format($availableStock) }} {{ $stockUnit }}{{ $availableStock <= 0 ? ' · vẫn lên đơn' : '' }}
                                </small>
                                <small class="monitor-variant-inventory">
                                    Tồn nhà máy: {{ number_format($onHandStock) }} · Đã giữ: {{ number_format($reservedStock) }}
                                </small>
                                @if($variant->production_date)
                                    <small class="monitor-variant-production">Ngày SX: {{ \Carbon\Carbon::parse($variant->production_date)->format('d/m/Y') }}</small>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $products->links() }}
    </div>
@endif
