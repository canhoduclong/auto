@if($variants->isEmpty())
    <div class="variant-picker-empty">{{ __('orders.empty.no_products_found') }}</div>
@else
    <div class="variant-picker-toolbar">
        <p class="text-muted mb-0 small">
            {{ __('orders.variant_search.showing', ['from' => $variants->firstItem(), 'to' => $variants->lastItem(), 'total' => $variants->total()]) }}
        </p>
        <div class="d-flex align-items-center gap-2">
            <label for="per-page-select" class="form-label mb-0 small text-muted">{{ __('orders.variant_search.per_page') }}</label>
            <select class="form-select form-select-sm" id="per-page-select">
                <option value="5" {{ $variants->perPage() == 5 ? 'selected' : '' }}>5</option>
                <option value="10" {{ $variants->perPage() == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $variants->perPage() == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $variants->perPage() == 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
    </div>
    <div class="variant-picker-list">
        @foreach($variants as $variant)
            @php
                $unitValue = (string) ($variant->product->unit ?? 'cai');
                $weightUnitLabel = $variant->product->unit_label ?? 'Cái';
                $sizeRaw = strtolower(str_replace(',', '.', trim((string) ($variant->size ?? ''))));
                preg_match('/([0-9]*\.?[0-9]+)/', $sizeRaw, $sizeMatches);
                $sizeKg = (float) ($sizeMatches[1] ?? 0);
                if (str_contains($sizeRaw, 'g') && !str_contains($sizeRaw, 'kg')) {
                    $sizeKg = $sizeKg / 1000;
                }
                $defaultWeight = (float) ($variant->kg ?? 0);
                if ($defaultWeight <= 0) {
                    $defaultWeight = (float) ($variant->product->kg ?? 0);
                }
                if ($defaultWeight <= 0) {
                    $defaultWeight = $sizeKg;
                }
                $defaultWeight = round(max(0.01, $defaultWeight), 3);
                $isPricedByKg = $variant->is_priced_by_kg !== null
                    ? (bool) $variant->is_priced_by_kg
                    : (bool) ($variant->product->is_priced_by_kg ?? true);
                // Nếu tính tiền theo kg, nhãn đơn vị KL luôn là "Kg"
                if ($isPricedByKg) {
                    $weightUnitLabel = 'Kg';
                }
                $price = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
                $minPrice = (float) ($variant->latestPriceRule?->min_price ?? 0);
            @endphp
            @php
                $imageUrl = 'https://via.placeholder.com/72';
                if ($variant->media) {
                    $imageUrl = asset('storage/' . $variant->media->file_path);
                } elseif ($variant->product?->avatar?->media) {
                    $imageUrl = asset('storage/' . $variant->product->avatar->media->file_path);
                }
            @endphp
            <div class="variant-picker-item">
                <div class="variant-picker-main">
                    <img src="{{ $imageUrl }}" alt="{{ $variant->product?->name ?? $variant->sku }}" class="variant-picker-thumb">
                    <div class="variant-picker-copy">
                        <div class="variant-picker-name">
                            @if($variant->is_pinned)
                                <span class="variant-picker-star">★</span>
                            @endif
                            {{ $variant->product?->name ?? 'Sản phẩm' }}
                        </div>
                        <div class="variant-picker-meta">
                            <span>SKU {{ $variant->sku ?: '--' }}</span>
                            @if($variant->size)
                                <span>Size {{ $variant->size }}</span>
                            @endif
                            <span>{{ $variant->product->unit_label ?? 'Cái' }}</span>
                            @if($variant->user_sort_order !== null)
                                <span>Thứ tự {{ $variant->user_sort_order }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="variant-picker-stats">
                    <div>
                        <span class="variant-picker-label">Giá bán</span>
                        <strong>{{ number_format($price, 0, ',', '.') }}đ</strong>
                    </div>
                    <div>
                        <span class="variant-picker-label">Tồn</span>
                        <strong>{{ number_format((float) $variant->available_stock, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="variant-picker-actions">
                    <button
                        type="button"
                        class="btn btn-sm {{ $variant->is_pinned ? 'btn-warning' : 'btn-outline-warning' }}"
                        data-variant-id="{{ $variant->id }}"
                        data-pinned="{{ $variant->is_pinned ? '1' : '0' }}"
                        onclick="fetch('{{ route('orders.variant_preference', $variant) }}', {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}'}, body: JSON.stringify({is_pinned: {{ $variant->is_pinned ? 'false' : 'true' }}})}).then(() => this.closest('.variant-picker-item')?.remove())">
                        {{ $variant->is_pinned ? 'Bỏ ghim' : 'Ghim' }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-variant-id="{{ $variant->id }}"
                        data-sort-order="{{ $variant->user_sort_order ?? '' }}"
                        onclick="const value = window.prompt('Nhập thứ tự riêng (số nhỏ hiển thị trước):', this.dataset.sortOrder || ''); if (value !== null) fetch('{{ route('orders.variant_preference', $variant) }}', {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}'}, body: JSON.stringify({sort_order: value === '' ? null : Math.max(0, parseInt(value || '0', 10))})}).then(() => this.closest('.variant-picker-item')?.remove())">
                        Thứ tự
                    </button>
                    <a
                        href="javascript:void(0);"
                        class="btn btn-sm btn-primary add-variant-to-cart"
                        data-variant-id="{{ $variant->id }}"
                        data-variant-name="{{ $variant->product->name }}"
                        data-variant-sku="{{ $variant->sku }}"
                        data-variant-size="{{ $variant->size ?: $variant->name ?? '' }}"
                        data-variant-price="{{ $price }}"
                        data-variant-min-price="{{ $minPrice }}"
                        data-variant-stock="{{ $variant->available_stock }}"
                        data-variant-unit="{{ $unitValue }}"
                        data-variant-unit-label="{{ $variant->product->unit_label ?? 'Cái' }}"
                        data-variant-weight="{{ number_format($defaultWeight, 3, '.', '') }}"
                        data-variant-weight-unit-label="{{ $weightUnitLabel }}"
                        data-variant-is-priced-by-kg="{{ $isPricedByKg ? '1' : '0' }}"
                        data-variant-image="{{ $imageUrl }}">
                        {{ __('inventory.buttons.add_item') }}
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $variants->appends(request()->query())->links() }}
    </div>

@endif
