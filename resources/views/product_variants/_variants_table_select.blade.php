<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>ID</th>
            <th>Ảnh</th>
            <th>SKU</th>
            <th>Sản phẩm</th>
            <th>Size</th>
            <th>Chất lượng</th>
            <th>Ngày SX</th>
            <th>Giá bán</th>
            <th>Tồn kho</th>
            <th>Chọn</th>
        </tr>
    </thead>
    <tbody>
        @forelse($variants as $v)
        <tr>
            <td>{{ $v->id }}</td>
            <td>
                @if($v->media_url)
                    <img src="{{ $v->media_url }}" width="50" class="rounded" alt="{{ $v->sku }}">
                @endif
            </td>
            <td>{{ $v->sku }}</td>
            <td>{{ $v->product->name ?? '' }}</td>
            <td>{{ $v->size }}</td>
            <td>{{ $v->quality }}</td>
            <td>{{ $v->production_date }}</td>
            <td>
                @php
                    $latestPrice = $v->latestPriceRule ? $v->latestPriceRule->price : $v->final_price;
                @endphp
                {{ number_format($latestPrice ?? 0, 0, ',', '.') }} đ
            </td>
            <td>{{ $v->stock }}</td>
            <td>
                <button type="button" class="btn btn-sm btn-success add-variant-to-cart"
                    data-variant-id="{{ $v->id }}"
                    data-variant-name="{{ $v->product->name ?? '' }}"
                    data-variant-sku="{{ $v->sku }}"
                    data-variant-size="{{ $v->size }}"
                    data-variant-price="{{ $latestPrice ?? 0 }}"
                    data-variant-stock="{{ $v->stock }}"
                    data-variant-image="{{ $v->media_url ?? '' }}"
                    data-variant-unit-label="{{ $v->product->unit_label ?? 'Cái' }}"
                    data-variant-weight="{{ $v->kg ?? 0 }}"
                    data-variant-weight-unit-label="{{ $v->product->unit_label ?? 'Kg' }}"
                    data-variant-is-priced-by-kg="{{ $v->is_priced_by_kg ? 1 : 0 }}"
                    data-variant-min-price="{{ \App\Support\OrderPriceBounds::minimum((float) ($v->latestPriceRule->min_price ?? 0)) }}"
                >Thêm dòng</button>
            </td>
        </tr>
        @empty
        <tr><td colspan="10" class="text-center text-muted">Không có dữ liệu</td></tr>
        @endforelse
    </tbody>
</table>
<div>
    {{ $variants->links() }}
</div>
