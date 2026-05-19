@extends('layouts.app')
@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 fw-bold text-primary">
                <i class="fas fa-cube me-2"></i>Quản lý Biến Thể Sản Phẩm
            </h3>
            <p class="text-muted small mt-1">Tổng cộng: {{ $allVariants->count() }} biến thể</p>
        </div>
        <a href="{{ route('product-variants.create') }}" class="btn btn-success btn-lg">
            <i class="fas fa-plus me-2"></i>Thêm Biến Thể Mới
        </a>
    </div>

    <!-- Search & Filter Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="mb-0" id="filter-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary small">Tìm kiếm</label>
                        <input type="text" name="q" class="form-control form-control-sm" 
                               placeholder="SKU, size, chất lượng..." value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary small">Sản Phẩm</label>
                        <select name="product_id" class="form-select form-select-sm">
                            <option value="">-- Tất cả sản phẩm --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">Từ Ngày</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">Đến Ngày</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">&nbsp;</label>
                        <button class="btn btn-primary btn-sm w-100" type="submit">
                            <i class="fas fa-search me-1"></i>Lọc
                        </button>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">Số Dòng</label>
                        <select name="per_page" class="form-select form-select-sm">
                            <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20 / trang</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 / trang</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 / trang</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">Tồn kho Từ</label>
                        <input type="number" name="min_stock" class="form-control form-control-sm" value="{{ request('min_stock') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-secondary small">Tồn kho Đến</label>
                        <input type="number" name="max_stock" class="form-control form-control-sm" value="{{ request('max_stock') }}">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="mb-3">
        <button class="btn btn-danger btn-sm" id="bulk-delete-btn">
            <i class="fas fa-trash me-1"></i>Xoá Các Mục Đã Chọn
        </button>
    </div>

    <!-- Grouped Variants by Product -->
    <div id="variants-container">
        @forelse($paginatedGroups as $productId => $productVariants)
            @php
                $product = $productVariants->first()->product;
            @endphp
            <div class="card border-left-4 border-left-primary shadow-sm mb-4">
                <!-- Product Header -->
                <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="fas fa-boxes me-2"></i>{{ $product->name ?? 'N/A' }}
                        </h5>
                        <small class="text-muted">
                            <i class="fas fa-layer-group me-1"></i>
                            {{ $productVariants->count() }} biến thể
                        </small>
                    </div>
                </div>

                <!-- Product Variants Table -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40"><input type="checkbox" class="variant-checkbox-all" data-product-id="{{ $productId }}"></th>
                                <th width="50">ID</th>
                                <th width="60">Ảnh</th>
                                <th>SKU</th>
                                <th>Size</th>
                                <th>Chất Lượng</th>
                                <th>Ngày SX</th>
                                <th width="100">Giá Bán</th>
                                <th width="80">Tồn Kho</th>
                                <th width="280">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productVariants as $variant)
                            <tr class="variant-row" data-variant-id="{{ $variant->id }}">
                                <td>
                                    <input type="checkbox" class="variant-checkbox" value="{{ $variant->id }}">
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $variant->id }}</span>
                                </td>
                                <td>
                                    @if($variant->media_url)
                                        <img src="{{ $variant->media_url }}" width="45" height="45" class="rounded-2" 
                                             alt="{{ $variant->sku }}" style="object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded d-inline-flex align-items-center justify-content-center" 
                                             style="width: 45px; height: 45px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-dark">{{ $variant->sku }}</strong>
                                </td>
                                <td>
                                    @if($variant->size)
                                        <span class="badge bg-secondary">{{ $variant->size }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($variant->quality)
                                        {{ $variant->quality }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($variant->production_date)
                                        {{ \Carbon\Carbon::parse($variant->production_date)->format('d/m/Y') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $latestPrice = $variant->latestPriceRule ? $variant->latestPriceRule->price : $variant->final_price;
                                    @endphp
                                    <strong class="text-success">{{ number_format($latestPrice ?? 0, 0, ',', '.') }} đ</strong>
                                </td>
                                <td>
                                    @php
                                        $stock = $variant->stock ?? 0;
                                        $stockClass = $stock > 50 ? 'success' : ($stock > 10 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge bg-{{ $stockClass }}">{{ $stock }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('product-variants.edit', $variant->id) }}" 
                                           class="btn btn-outline-warning btn-sm" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('variants.edit-price', $variant->id) }}?from=product-variants" 
                                           class="btn btn-outline-info btn-sm" title="Điều chỉnh giá">
                                            <i class="fas fa-dollar-sign"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-primary btn-sm clone-variant-index" 
                                                data-variant-id="{{ $variant->id }}" data-variant='@json($variant)' 
                                                title="Nhân bản">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm quick-edit-variant-index" 
                                                data-variant-id="{{ $variant->id }}" title="Sửa nhanh">
                                            <i class="fas fa-lightning-bolt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="alert alert-info border-0 text-center py-5">
                <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                <p class="mb-0 mt-3">Không có biến thể nào phù hợp với tiêu chí tìm kiếm</p>
            </div>
        @endforelse

        <!-- Pagination -->
        @if($paginator->hasPages())
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                {{ $paginator->links() }}
            </ul>
        </nav>
        @endif
    </div>
</div>

<style>
    .border-left-4 {
        border-left: 4px solid;
    }
    .border-left-primary {
        border-left-color: #0d6efd;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }
    .btn-group-sm > .btn {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
</style>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all checkboxes in a product group
    $(document).on('change', '.variant-checkbox-all', function() {
        const productId = $(this).data('product-id');
        const isChecked = this.checked;
        $('input.variant-checkbox').prop('checked', isChecked);
    });

    // Clone variant - using event delegation
    $(document).on('click', '.clone-variant-index', function() {
        const variantId = $(this).data('variant-id');
        
        if (!variantId) return;
        
        if (confirm('Bạn có chắc chắn muốn nhân bản biến thể này?')) {
            $.ajax({
                url: `/product-variants/${variantId}/duplicate`,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showNotification('Đã nhân bản biến thể thành công!', 'success');
                    setTimeout(() => location.reload(), 1500);
                },
                error: function() {
                    showNotification('Có lỗi xảy ra khi nhân bản', 'error');
                }
            });
        }
    });

    // Quick edit variant - using event delegation
    $(document).on('click', '.quick-edit-variant-index', function() {
        const variantId = $(this).data('variant-id');
        const tr = $(this).closest('tr');
        
        if (!variantId) return;
        
        // Kiểm tra nếu đã có form sửa
        if (tr.next().hasClass('quick-edit-row')) return;
        
        // Get current values from row
        const tds = tr.find('td');
        const sku = $(tds[3]).find('strong').text().trim() || '';
        const size = $(tds[4]).find('.badge').text().trim() || '';
        const quality = $(tds[5]).text().trim() || '';
        const price = $(tds[7]).find('strong').text().trim().replace(/[^0-9]/g, '') || '0';
        const stock = $(tds[8]).find('.badge').text().trim() || '0';

        const html = `
            <tr class="quick-edit-row">
                <td colspan="10">
                    <div class="p-3 bg-light rounded">
                        <form method="POST" action="/product-variants/${variantId}" class="quick-edit-form">
                            <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                            <input type="hidden" name="_method" value="PUT">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <label class="form-label small fw-semibold">SKU</label>
                                    <input type="text" name="sku" value="${sku}" class="form-control form-control-sm" style="width: 100px;">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label small fw-semibold">Size</label>
                                    <input type="text" name="size" value="${size}" class="form-control form-control-sm" style="width: 80px;">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label small fw-semibold">Chất Lượng</label>
                                    <input type="text" name="quality" value="${quality}" class="form-control form-control-sm" style="width: 100px;">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label small fw-semibold">Giá</label>
                                    <input type="number" name="price" value="${price}" class="form-control form-control-sm" style="width: 100px;">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label small fw-semibold">Tồn Kho</label>
                                    <input type="number" name="stock" value="${stock}" class="form-control form-control-sm" style="width: 80px;">
                                </div>
                                <div class="col-auto pt-3">
                                    <button type="submit" class="btn btn-sm btn-primary">Lưu</button>
                                    <button type="button" class="btn btn-sm btn-secondary cancel-quick-edit">Hủy</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </td>
            </tr>
        `;

        tr.after(html);
    });

    // Cancel quick edit
    $(document).on('click', '.cancel-quick-edit', function() {
        $(this).closest('.quick-edit-row').remove();
    });
    
    // Submit quick edit form
    $(document).on('submit', '.quick-edit-form', function(e) {
        e.preventDefault();
        const form = $(this);
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function() {
                showNotification('Cập nhật thành công!', 'success');
                setTimeout(() => location.reload(), 1500);
            },
            error: function(xhr) {
                showNotification('Có lỗi xảy ra', 'error');
            }
        });
    });

    // Bulk delete
    $('#bulk-delete-btn').on('click', function() {
        const selected = $('input.variant-checkbox:checked').map(function() {
            return this.value;
        }).get();

        if (selected.length === 0) {
            alert('Vui lòng chọn ít nhất một biến thể để xoá');
            return;
        }

        if (confirm('Bạn có chắc chắn muốn xoá ' + selected.length + ' biến thể đã chọn?')) {
            $.ajax({
                url: "{{ route('product-variants.bulk-delete') }}",
                method: 'POST',
                data: {
                    ids: selected,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    showNotification(response.success, 'success');
                    setTimeout(() => location.reload(), 1500);
                },
                error: function() {
                    showNotification('Có lỗi xảy ra khi xoá', 'error');
                }
            });
        }
    });

    function showNotification(message, type = 'success') {
        const container = $('#notification-container').length ? 
            $('#notification-container') : 
            $('<div id="notification-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>').appendTo('body');

        const toast = $(`
            <div class="toast" role="alert">
                <div class="toast-header">
                    <strong class="me-auto">${type.toUpperCase()}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);

        container.append(toast);
        new bootstrap.Toast(toast[0]).show();
    }

    // Filter form submission
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        const url = "{{ route('product-variants.index') }}" + "?" + $(this).serialize();
        window.location.href = url;
    });
});
</script>
@endpush
