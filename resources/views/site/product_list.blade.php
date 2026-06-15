@extends('layouts.site')

@section('header')
<header class="p-3 mb-3 border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
            <a href="/" class="d-flex align-items-center mb-2 mb-lg-0 text-dark text-decoration-none">
                @if(isset($settings['logo']) && $settings['logo']->value)
                    @php
                        $media = App\Models\Media::find($settings['logo']->value);
                    @endphp
                    @if($media)
                        <img src="{{ asset('storage/' . $media->file_path) }}" alt="logo" height="40">
                    @endif
                @else
                    <h2>{{ $settings['brand_name']->value ?? 'My Website' }}</h2>
                @endif
            </a>

            <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                <li><a href="#" class="nav-link px-2 link-secondary">Home</a></li>
            </ul>

            <div class="text-end">
                <p class="slogan">{{ $settings['slogan']->value ?? 'Your slogan here' }}</p>
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                @endauth
            </div>
        </div>
    </div>
</header>
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-3">
            <h4>Categories</h4>
            <div class="list-group">
                <a href="{{ route('pages.product_list') }}" class="list-group-item list-group-item-action {{ !isset($category) ? 'active' : '' }}">
                    All Categories
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('pages.product_list', ['category' => $cat->slug]) }}" class="list-group-item list-group-item-action {{ (isset($category) && $category->id == $cat->id) ? 'active' : '' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-md-9">
            <h1>Products</h1>

            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>
                                    @if($product->avatar && $product->avatar->media)
                                        <img src="{{ asset('storage/' . $product->avatar->media->file_path) }}" alt="{{ $product->name }}" width="80">
                                    @else
                                        <img src="https://via.placeholder.com/80" alt="placeholder" width="80">
                                    @endif
                                </td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->price }}</td>
                                <td>
                                    <a href="{{ route('pages.product_detail', $product->slug) }}" class="btn btn-info btn-sm">View Details</a>
                                </td>
                            </tr>
                            @if($product->variants->count())
                                <tr>
                                    <td colspan="4" style="background:#f9fafb; padding:0 0 12px 0;">
                                        @php
                                            $sizes = $product->variants->map(function($v){
                                                $size = $v->values->firstWhere('attribute.code', 'size')?->value;
                                                return $size ?: null;
                                            })->filter()->unique()->values();
                                        @endphp
                                        @if($sizes->count())
                                            <div class="product-size-selector mb-2">
                                                <strong>Chọn size:</strong>
                                                @foreach($sizes as $size)
                                                    <button type="button" class="btn btn-outline-primary btn-sm size-btn me-1 mb-1" data-product-id="{{ $product->id }}" data-size="{{ $size }}">{{ $size }}</button>
                                                @endforeach
                                            </div>
                                            <div class="variant-list-by-size" id="variant-list-by-size-{{ $product->id }}"></div>
                                        @else
                                            <div class="variant-list-by-size" id="variant-list-by-size-{{ $product->id }}">
                                                @foreach($product->variants as $variant)
                                                    <div class="row align-items-center border-bottom py-2">
                                                        <div class="col">
                                                            <div><b>{{ $variant->name ?? $product->name }}</b></div>
                                                            <div class="text-muted small">SKU: {{ $variant->sku ?? '' }}</div>
                                                            <div class="text-muted small">Trọng lượng: {{ $variant->weight ? $variant->weight . 'g' : '--' }}</div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <div class="fw-bold text-danger mb-1">{{ number_format($variant->final_price ?? 0, 0, ',', '.') }} VNĐ</div>
                                                            <button class="btn btn-warning btn-sm add-to-cart-direct" data-variant-id="{{ $variant->id }}"><i class="bi bi-cart-plus"></i> Thêm vào giỏ</button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            @php
                            // Chuẩn bị dữ liệu variantsByProductId cho JS, tránh lỗi blade/JS phức tạp
                            $variantsByProductId = $products->mapWithKeys(function($p) {
                                return [$p->id => $p->variants->map(function($v) use ($p) {
                                    return [
                                        'id' => $v->id,
                                        'name' => $v->name,
                                        'sku' => $v->sku,
                                        'weight' => $v->weight ?? null,
                                        'final_price' => $v->final_price,
                                        'media' => $v->media_url,
                                        'size' => $v->values->firstWhere('attribute.code', 'size')?->value,
                                        'product_name' => $p->name,
                                        'product_avatar' => $p->avatar && $p->avatar->media ? asset('storage/' . $p->avatar->media->file_path) : 'https://via.placeholder.com/80',
                                    ];
                                })];
                            });
                            @endphp
                            @push('scripts')
                            <script>
                            $(function() {
                                var variants = @json($variantsByProductId, JSON_UNESCAPED_UNICODE);
                                // Khi click size, render danh sách biến thể theo size
                                $('.size-btn').on('click', function() {
                                    var $btn = $(this);
                                    var productId = $btn.data('product-id');
                                    var size = $btn.data('size');
                                    var $container = $('#variant-list-by-size-' + productId);
                                    var html = '';
                                    if (variants[productId]) {
                                        variants[productId].forEach(function(variant) {
                                            if (variant.size == size) {
                                                html += `<div class="row align-items-center border-bottom py-2">
                                                    <div class="col">
                                                        <div><b>${variant.name || variant.product_name}</b></div>
                                                        <div class="text-muted small">SKU: ${variant.sku || ''}</div>
                                                        <div class="text-muted small">Trọng lượng: ${variant.weight ? variant.weight + 'g' : '--'}</div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <div class="fw-bold text-danger mb-1">${(variant.final_price || 0).toLocaleString('vi-VN')} VNĐ</div>
                                                        <button class="btn btn-warning btn-sm add-to-cart-direct" data-variant-id="${variant.id}"><i class="bi bi-cart-plus"></i> Thêm vào giỏ</button>
                                                    </div>
                                                </div>`;
                                            }
                                        });
                                    }
                                    $container.html(html);
                                });
                                // Thêm vào giỏ hàng bằng AJAX
                                $(document).on('click', '.add-to-cart-direct', function() {
                                    var variantId = $(this).data('variant-id');
                                    var $btn = $(this);
                                    $btn.prop('disabled', true);
                                    if (window.siteCart && typeof window.siteCart.addVariant === 'function') {
                                        window.siteCart.addVariant(variantId, 1)
                                            .then(function(data) {
                                                if (window.showToast) window.showToast(data.message || 'Đã thêm sản phẩm vào giỏ hàng.', 'success');
                                            })
                                            .catch(function(error) {
                                                if (window.showToast) window.showToast(error.message || 'Không thể thêm sản phẩm vào giỏ hàng.', 'error');
                                            })
                                            .finally(function() {
                                                $btn.prop('disabled', false);
                                            });
                                    } else {
                                        if (window.showToast) window.showToast('Không thể kết nối giỏ hàng.', 'error');
                                        $btn.prop('disabled', false);
                                    }
                                });
                            });
                            </script>
                            @endpush
                            @endforeach
                        </tbody>
                    </table>

                    {{ $products->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
<footer class="footer mt-auto py-3 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>Thông tin công ty</h5>
                <p>{{ $settings['brand_name']->value ?? '' }}</p>
                <p>Mã số thuế: {{ $settings['tax_number']->value ?? 'Chưa có' }}</p>
            </div>
            <div class="col-md-4">
                <h5>Liên hệ</h5>
                <p>Địa chỉ: {{ $settings['address']->value ?? '' }}</p>
                <p>Hotline: {{ $settings['hotline']->value ?? '' }}</p>
                <p>Email: {{ $settings['email']->value ?? '' }}</p>
            </div>
            <div class="col-md-4">
                <h5>Chính sách</h5>
                <p><a href="{{ route('pages.privacy_policy') }}">Chính sách quyền riêng tư</a></p>
            </div>
        </div>
    </div>
</footer>
@endsection
