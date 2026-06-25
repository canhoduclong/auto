@extends('layouts.app')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Danh sách biến thể sản phẩm</h4>
            <div class="text-muted small">Tổng cộng: {{ $variants->total() }} biến thể</div>
        </div>
        <a href="{{ route('product-variants.create') }}" class="btn btn-success">Thêm biến thể mới</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="get" id="filter-form">
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="SKU, size, chất lượng, tên sản phẩm..." value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="product_id" class="form-select form-select-sm">
                            <option value="">-- Lọc theo sản phẩm --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-md-2">
                        <select name="per_page" class="form-select form-select-sm">
                            <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20 / trang</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 / trang</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 / trang</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="min_stock" class="form-control form-control-sm" placeholder="Tồn kho từ" value="{{ request('min_stock') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="max_stock" class="form-control form-control-sm" placeholder="Tồn kho đến" value="{{ request('max_stock') }}">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <button class="btn btn-danger btn-sm" id="bulk-delete-btn">Xóa các mục đã chọn</button>
        <span class="text-muted small">Hiển thị theo nhóm sản phẩm trong cùng một bảng</span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40"><input type="checkbox" id="select-all"></th>
                        <th width="200">Sản phẩm</th>
                        <th width="60">ID</th>
                        <th width="70">Ảnh</th>
                        <th width="180">Tên biến thể</th>
                        <th>SKU</th>
                        <th width="90">Size</th>
                        <th width="80">Thứ tự</th>
                        <th width="110">Giá bán</th>
                        <th width="90" style="cursor:pointer; user-select:none;">
                            @php
                                $sortDir = request('sort') === 'stock' ? request('direction', 'asc') : 'asc';
                                $nextDir = ($sortDir === 'asc') ? 'desc' : 'asc';
                                $sortUrl = request()->fullUrlWithQuery(['sort' => 'stock', 'direction' => $nextDir, 'page' => 1]);
                            @endphp
                            <a href="{{ $sortUrl }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Tồn kho
                                @if(request('sort') === 'stock')
                                    <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @else
                                    <span class="text-muted" style="opacity:.4">↕</span>
                                @endif
                            </a>
                        </th>
                        <th width="250">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupedVariants as $productId => $productVariants)
                        @php
                            $product = $productVariants->first()->product;
                        @endphp
                        <tr class="table-group-row">
                            <td>
                                <input type="checkbox" class="group-select" data-product-id="{{ $productId }}">
                            </td>
                            <td colspan="10" class="fw-semibold text-primary bg-light">
                                {{ $product->name ?? 'Không xác định' }}
                                <span class="text-muted ms-2">({{ $productVariants->count() }} biến thể)</span>
                            </td>
                        </tr>

                        @foreach($productVariants as $variant)
                            <tr class="variant-row" data-product-id="{{ $productId }}">
                                <td>
                                    <input type="checkbox" class="variant-checkbox" value="{{ $variant->id }}" data-product-id="{{ $productId }}">
                                </td>
                                <td class="text-muted small">{{ $product->name ?? '-' }}</td>
                                <td>{{ $variant->id }}</td>
                                <td class="variant-image-cell">
                                    @php
                                        $imgPath = null;
                                        if ($variant->mediaLink && $variant->mediaLink->media) {
                                            $imgPath = asset('storage/' . $variant->mediaLink->media->file_path);
                                        } elseif ($variant->product && $variant->product->avatar && $variant->product->avatar->media) {
                                            $imgPath = asset('storage/' . $variant->product->avatar->media->file_path);
                                        }
                                    @endphp
                                    @if($imgPath)
                                        <img src="{{ $imgPath }}" class="variant-image" alt="{{ $variant->sku }}">
                                    @else
                                        <span class="text-muted text-center d-block" style="line-height: 72px;">—</span>
                                    @endif
                                </td>
                                <td>{{ $variant->name ?: '-' }}</td>
                                <td class="fw-semibold">{{ $variant->sku }}</td>
                                <td>{{ $variant->size ?: '-' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $variant->sort_order ?? 0 }}</span></td>
                                <td>
                                    @php
                                        $latestPrice = $variant->latestPriceRule ? $variant->latestPriceRule->price : $variant->final_price;
                                    @endphp
                                    <span class="text-success fw-semibold">{{ number_format($latestPrice ?? 0, 0, ',', '.') }} đ</span>
                                </td>
                                <td>
                                    @php
                                        $stock = $variant->stock ?? 0;
                                        $stockClass = $stock > 50 ? 'success' : ($stock > 10 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge bg-{{ $stockClass }}">{{ $stock }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('product-variants.edit', $variant->id) }}" class="btn btn-outline-warning btn-sm">Sửa</a>
                                        <a href="{{ route('variants.edit-price', $variant->id) }}?from=product-variants" class="btn btn-outline-info btn-sm">Giá</a>
                                        <button type="button" class="btn btn-outline-primary btn-sm clone-variant-index" data-variant-id="{{ $variant->id }}">Nhân bản</button>
                                        <button type="button" class="btn btn-outline-success btn-sm quick-edit-variant-index" data-variant-id="{{ $variant->id }}">Sửa nhanh</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">Không có dữ liệu phù hợp</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $variants->links() }}
    </div>
</div>

<style>
    .variant-row {
        height: 72px;
    }

    .variant-image-cell {
        padding: 0;
    }

    .variant-image {
        width: 100%;
        height: 72px;
        display: block;
        object-fit: cover;
    }
</style>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $(document).on('change', '#select-all', function() {
        $('.variant-checkbox').prop('checked', $(this).prop('checked'));
        $('.group-select').prop('checked', $(this).prop('checked'));
    });

    $(document).on('change', '.group-select', function() {
        const productId = $(this).data('product-id');
        $(`.variant-checkbox[data-product-id="${productId}"]`).prop('checked', $(this).prop('checked'));
    });

    $(document).on('click', '.clone-variant-index', function() {
        const variantId = $(this).data('variant-id');
        if (!variantId) return;

        if (!confirm('Bạn có chắc chắn muốn nhân bản biến thể này?')) return;

        $.ajax({
            url: `/product-variants/${variantId}/duplicate`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                location.reload();
            },
            error: function() {
                alert('Có lỗi xảy ra khi nhân bản.');
            }
        });
    });

    $(document).on('click', '.quick-edit-variant-index', function() {
        const variantId = $(this).data('variant-id');
        const tr = $(this).closest('tr');

        if (!variantId || tr.next().hasClass('quick-edit-row')) return;

        const tds = tr.find('td');
        const sku = $(tds[5]).text().trim() || '';
        const size = $(tds[6]).text().trim() || '';
        const sortOrder = $(tds[7]).text().trim().replace(/[^0-9]/g, '') || '0';
        const price = $(tds[8]).text().trim().replace(/[^0-9]/g, '') || '0';
        const stock = $(tds[9]).text().trim().replace(/[^0-9]/g, '') || '0';

        const html = `
            <tr class="quick-edit-row">
                <td colspan="11" class="bg-light">
                    <form method="POST" action="/product-variants/${variantId}" class="quick-edit-form d-flex flex-wrap gap-2 align-items-end p-2">
                        <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                        <input type="hidden" name="_method" value="PUT">
                        <div>
                            <label class="form-label small mb-1">SKU</label>
                            <input type="text" name="sku" value="${sku}" class="form-control form-control-sm" style="width: 120px;">
                        </div>
                        <div>
                            <label class="form-label small mb-1">Size</label>
                            <input type="text" name="size" value="${size === '-' ? '' : size}" class="form-control form-control-sm" style="width: 90px;">
                        </div>
                        <div>
                            <label class="form-label small mb-1">Thứ tự</label>
                            <input type="number" min="0" name="sort_order" value="${sortOrder}" class="form-control form-control-sm" style="width: 90px;">
                        </div>
                        <div>
                            <label class="form-label small mb-1">Giá</label>
                            <input type="number" min="0" name="price" value="${price}" class="form-control form-control-sm" style="width: 120px;">
                        </div>
                        <div>
                            <label class="form-label small mb-1">Tồn kho</label>
                            <input type="number" min="0" name="stock" value="${stock}" class="form-control form-control-sm" style="width: 100px;">
                        </div>
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                            <button type="button" class="btn btn-secondary btn-sm cancel-quick-edit">Hủy</button>
                        </div>
                    </form>
                </td>
            </tr>
        `;

        tr.after(html);
    });

    $(document).on('click', '.cancel-quick-edit', function() {
        $(this).closest('.quick-edit-row').remove();
    });

    $(document).on('submit', '.quick-edit-form', function(e) {
        e.preventDefault();
        const form = $(this);

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function() {
                location.reload();
            },
            error: function() {
                alert('Có lỗi xảy ra khi cập nhật.');
            }
        });
    });

    $('#bulk-delete-btn').on('click', function() {
        const selected = $('.variant-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selected.length === 0) {
            alert('Vui lòng chọn ít nhất một biến thể để xóa.');
            return;
        }

        if (!confirm(`Bạn có chắc chắn muốn xóa ${selected.length} biến thể đã chọn?`)) {
            return;
        }

        $.ajax({
            url: "{{ route('product-variants.bulk-delete') }}",
            method: 'POST',
            data: {
                ids: selected,
                _token: '{{ csrf_token() }}'
            },
            success: function() {
                location.reload();
            },
            error: function() {
                alert('Có lỗi xảy ra khi xóa.');
            }
        });
    });

    $(document).on('change', 'select[name=per_page]', function() {
        $('#filter-form').submit();
    });
});
</script>
@endpush
