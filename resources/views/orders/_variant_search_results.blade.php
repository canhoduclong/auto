@if($variants->isEmpty())
    <p class="text-center p-3">No products found.</p>
@else
    <div class="d-flex justify-content-between align-items-center mt-2">
        <p class="text-muted mb-0">
            Showing {{ $variants->firstItem() }} to {{ $variants->lastItem() }} of {{ $variants->total() }} results
        </p>
        <div class="d-flex align-items-center">
            <label for="per-page-select" class="form-label me-2 mb-0">Per Page:</label>
            <select class="form-select form-select-sm" id="per-page-select" style="width: auto;">
                <option value="5" {{ $variants->perPage() == 5 ? 'selected' : '' }}>5</option>
                <option value="10" {{ $variants->perPage() == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $variants->perPage() == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $variants->perPage() == 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
    </div>
    <ul class="list-group mt-2">
        @foreach($variants as $variant)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    @php
                        $imageUrl = 'https://via.placeholder.com/60'; // Default placeholder
                        if ($variant->media) {
                            $imageUrl = asset('storage/' . $variant->media->file_path);
                        } elseif ($variant->product->avatar && $variant->product->avatar->media) {
                            $imageUrl = asset('storage/' . $variant->product->avatar->media->file_path);
                        }
                    @endphp
                    <img src="{{ $imageUrl }}" alt="{{ $variant->product->name }}" width="60" class="me-3 rounded">
                    <div>
                        <h6 class="my-0">{{ $variant->product->name }}</h6>
                        <small class="text-muted">SKU: {{ $variant->sku }} | Price: {{ number_format($variant->latestPriceRule?->price ?? 0) }} | Stock: {{ $variant->stock }}</small>
                    </div>
                </div>
                <a
                    href="javascript:void(0);"
                    class="btn btn-sm btn-primary add-variant-to-cart"
                    data-variant-id="{{ $variant->id }}"
                    data-variant-name="{{ $variant->product->name }}"
                    data-variant-sku="{{ $variant->sku }}"
                    data-variant-price="{{ $variant->latestPriceRule?->price ?? 0 }}"
                    data-variant-stock="{{ $variant->stock }}"
                    data-variant-image="{{ $imageUrl }}">
                    Add
                </a>
            </li>
        @endforeach
    </ul>

    <div class="d-flex justify-content-center mt-3">
        {{ $variants->appends(request()->query())->links() }}
    </div>
@endif