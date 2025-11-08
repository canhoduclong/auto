<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th><input type="checkbox" id="select-all"></th>
            <th>ID</th>
            <th>Ảnh</th>
            <th>SKU</th>
            <th>Sản phẩm</th>
            <th>Size</th>
            <th>Chất lượng</th>
            <th>Ngày SX</th>
            <th>Giá bán</th>
            <th>Tồn kho</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        @foreach($variants as $v)
        <tr>
            <td><input type="checkbox" class="variant-checkbox" value="{{ $v->id }}"></td>
            <td>{{ $v->id }}</td>
            <td> 
                @if($v->media)
                    <img src="{{ asset('storage/' . $v->media->file_path) }}" width="50" class="rounded">
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
                <a href="{{ route('product-variants.edit', $v->id) }}" class="btn btn-sm btn-warning">Sửa</a>
                <a href="{{ route('variants.edit-price', $v->id) }}?from=product-variants" class="btn btn-sm btn-info mt-1">Điều chỉnh giá</a>
                <button type="button" class="btn btn-sm btn-primary mt-1 clone-variant-index" data-variant-id="{{ $v->id }}" data-variant='@json($v)'>Nhân bản</button>
                <button type="button" class="btn btn-sm btn-success mt-1 quick-edit-variant-index" data-variant-id="{{ $v->id }}">Sửa nhanh</button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div>
    {{ $variants->links() }}
</div>
