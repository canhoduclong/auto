@extends('layouts.app')

@push('styles')
<style>
    .edit-product-shell {
        width: 100%;
    }

    .page-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .page-head h4 {
        margin: 0;
        font-weight: 700;
        color: #0f172a;
    }

    .page-head .sub {
        margin-top: 4px;
        color: #64748b;
        font-size: .88rem;
    }

    .form-section {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        padding: 14px;
        margin-bottom: 14px;
    }

    .section-title {
        font-size: .82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #475569;
        margin-bottom: 10px;
    }

    .form-label {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: .35rem;
    }

    .media-panel {
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 12px;
        background: #f8fafc;
        height: 100%;
    }

    .description-panel {
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
        padding: 12px;
        height: 100%;
    }

    .description-panel textarea {
        min-height: 210px;
        resize: vertical;
    }

    .gallery-item img {
        width: 84px;
        height: 84px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
    }

    .variant-section {
        padding-top: 12px;
    }

    .variant-headbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .variant-headbar p {
        margin: 0;
        color: #64748b;
        font-size: .82rem;
    }

    .variant-count-badge {
        font-size: .74rem;
        font-weight: 700;
        border: 1px solid #cbd5e1;
        color: #334155;
        background: #f8fafc;
        border-radius: 999px;
        padding: 4px 10px;
    }

    .variant-block {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        padding: 12px;
    }

    .variant-list {
        display: grid;
        gap: 10px;
    }

    .variant-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fcfdff;
        padding: 10px;
    }

    .variant-grid {
        display: grid;
        grid-template-columns: 1.2fr .8fr .7fr .9fr .7fr 1fr 1.1fr;
        gap: 10px;
        align-items: start;
    }

    .variant-cell-label {
        display: block;
        margin-bottom: 4px;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #64748b;
    }

    .variant-grid .form-control {
        font-size: .86rem;
    }

    .variant-image-preview img {
        width: 52px;
        height: 52px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
    }

    .variant-select-image-btn {
        width: 100%;
    }

    .variant-actions {
        display: grid;
        gap: 6px;
    }

    .variant-actions .btn {
        width: 100%;
        white-space: nowrap;
    }

    .variant-toolbar {
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
    }

    .action-bar {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding-top: 4px;
    }

    .action-bar .btn {
        min-width: 120px;
    }

    @media (max-width: 991.98px) {
        .variant-grid {
            grid-template-columns: 1fr 1fr;
        }

        .variant-grid .variant-cell.variant-cell-actions {
            grid-column: 1 / -1;
        }

        .variant-grid .variant-cell.variant-cell-image {
            grid-column: 1 / -1;
        }

        .action-bar {
            flex-wrap: wrap;
        }

        .action-bar .btn {
            min-width: 0;
            flex: 1 1 auto;
        }
    }
</style>
@endpush

@section('content')
<div class="content">
    <div class="edit-product-shell">
            <div class="page-head">
                <div>
                    <h4>Chỉnh sửa sản phẩm</h4>
                    <div class="sub">Cập nhật thông tin, hình ảnh và biến thể trong cùng một màn hình.</div>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">Quay lại danh sách</a>
            </div>

            {{-- Hiển thị lỗi --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Form edit sản phẩm --}}
            <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="page" value="{{ $page ?? request('page', 1) }}">
                                <input type="hidden" name="perPage" value="{{ $perPage ?? request('perPage', 10) }}">
                @csrf
                @method('PUT')

                <div class="form-section">
                    <div class="section-title">Thông Tin Cơ Bản</div>
                    <div class="row g-3">
                        <div class="col-xl-7">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label for="name" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $product->name) }}"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label for="category_id" class="form-label">Danh mục <span class="text-danger">*</span></label>
                                    <select name="category_id" id="category_id"
                                            class="form-select @error('category_id') is-invalid @enderror" required>
                                        <option value="">-- Chọn danh mục --</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label for="brand_id" class="form-label">Brand</label>
                                    <select name="brand_id" id="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
                                        <option value="">-- Select a brand --</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}"
                                                {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('brand_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label for="unit" class="form-label">Đơn vị tính <span class="text-danger">*</span></label>
                                    <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror" required>
                                        @foreach($unitOptions as $value => $label)
                                            <option value="{{ $value }}" {{ old('unit', $product->unit ?? 'cai') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('unit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label for="product_type" class="form-label">Loại hình <span class="text-danger">*</span></label>
                                    <select name="product_type" id="product_type" class="form-select @error('product_type') is-invalid @enderror" required>
                                        @foreach($typeOptions as $value => $label)
                                            <option value="{{ $value }}" {{ old('product_type', $product->product_type ?? \App\Models\Product::TYPE_WHOLE) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('product_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label for="sort_order" class="form-label">Thứ tự hiển thị</label>
                                    <input type="number"
                                           name="sort_order"
                                           id="sort_order"
                                           class="form-control @error('sort_order') is-invalid @enderror"
                                           min="0"
                                           step="1"
                                           value="{{ old('sort_order', $product->sort_order ?? 0) }}">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-5">
                            <div class="description-panel">
                                <label for="description" class="form-label">Mô tả</label>
                                <textarea name="description"
                                          id="description"
                                          rows="8"
                                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">Hình Ảnh</div>
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="media-panel">
                                <label class="form-label mb-2">Ảnh đại diện</label>
                                <div>
                                    <button type="button" class="btn btn-primary btn-sm" id="btnSelectMedia">Chọn ảnh</button>
                                    <input type="hidden" name="media_id" id="media_id"
                                        value="{{ old('media_id', $product->avatar->media_id ?? '') }}">
                                </div>
                                <div id="mediaPreview" class="mt-2">
                                    @if(!empty($product->avatar) && $product->avatar->media)
                                        <img src="{{ asset('storage/'.$product->avatar->media->file_path) }}"
                                             width="120" class="img-thumbnail">
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="media-panel">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <label class="form-label mb-0">Gallery</label>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mediaGalleryModal">Chọn ảnh</button>
                                </div>
                                <div id="gallery-preview" class="mt-2 d-flex flex-wrap gap-2">
                                    @foreach($product->gallery as $link)
                                        @if($link->media)
                                            <div class="gallery-item position-relative" data-id="{{ $link->media->id }}">
                                                <img src="{{ asset('storage/' . $link->media->file_path) }}" width="80" class="rounded">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-gallery">&times;</button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <input type="hidden" name="gallery_ids" id="gallery-ids">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Biến thể --}}
                <div class="form-section variant-section">
                    <div class="variant-headbar">
                        <div>
                            <div class="section-title mb-1">Biến Thể Sản Phẩm</div>
                            <p>Quản lý SKU, kích thước, khối lượng quy đổi và ảnh theo từng biến thể.</p>
                        </div>
                        <span class="variant-count-badge">Tổng biến thể: <span id="variant-count">{{ $product->variants->count() }}</span></span>
                    </div>
                    <div class="variant-block">
                        <div id="variant-list" class="variant-list">
                            @foreach($product->variants as $variant)
                            <div class="variant-item" data-variant-id="{{ $variant->id }}">
                                <div class="variant-grid">
                                    <div class="variant-cell">
                                        <label class="variant-cell-label">SKU</label>
                                        <input type="text" name="variants[{{ $variant->id }}][sku]" class="form-control" value="{{ old('variants.'.$variant->id.'.sku', $variant->sku) }}">
                                    </div>
                                    <div class="variant-cell">
                                        <label class="variant-cell-label">Size</label>
                                        <input type="text" name="variants[{{ $variant->id }}][size]" class="form-control" value="{{ old('variants.'.$variant->id.'.size', $variant->size) }}">
                                    </div>
                                    <div class="variant-cell">
                                        <label class="variant-cell-label">Thứ tự</label>
                                        <input type="number" name="variants[{{ $variant->id }}][sort_order]" class="form-control" min="0" step="1" value="{{ old('variants.'.$variant->id.'.sort_order', $variant->sort_order ?? 0) }}">
                                    </div>
                                    <div class="variant-cell">
                                        <label class="variant-cell-label">Số Kg quy đổi</label>
                                        <input type="number" name="variants[{{ $variant->id }}][kg]" class="form-control" min="0.01" step="0.01" value="{{ old('variants.'.$variant->id.'.kg', $variant->kg ?? 1) }}" required>
                                    </div>
                                    <div class="variant-cell">
                                        <label class="variant-cell-label">Theo Kg</label>
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input" name="variants[{{ $variant->id }}][is_priced_by_kg]" value="1" {{ old('variants.'.$variant->id.'.is_priced_by_kg', $variant->is_priced_by_kg ?? true) ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <div class="variant-cell variant-cell-image">
                                        <label class="variant-cell-label">Hình ảnh</label>
                                        <div class="variant-image-preview mb-1">
                                            @if($variant->media)
                                                <img src="{{ asset('storage/' . $variant->media->file_path) }}" width="50" class="rounded">
                                            @endif
                                        </div>
                                        <input type="hidden" name="variants[{{ $variant->id }}][media_id]" id="variant-media-id-{{ $variant->id }}" value="{{ old('variants.'.$variant->id.'.media_id', $variant->media_id ?? '') }}">
                                        <button type="button" class="btn btn-sm btn-outline-secondary variant-select-image-btn select-variant-image" data-variant-id="{{ $variant->id }}">Chọn hình ảnh</button>
                                    </div>
                                    <div class="variant-cell variant-cell-actions">
                                        <label class="variant-cell-label">Thao tác</label>
                                        <div class="variant-actions">
                                            <button type="button" class="btn btn-danger btn-sm remove-variant">Xóa</button>
                                            <a href="{{ route('variants.edit-price', $variant->id) }}" class="btn btn-sm btn-warning">Điều chỉnh giá</a>
                                            <button type="button" class="btn btn-info btn-sm clone-variant" title="Nhân bản biến thể" data-variant-id="{{ $variant->id }}">Nhân bản</button>
                                            <button type="button" class="btn btn-success btn-sm quick-edit-variant" title="Sửa nhanh" data-variant-id="{{ $variant->id }}">Sửa nhanh</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="variant-toolbar">
                            <button type="button" class="btn btn-primary btn-sm" id="addVariant">+ Thêm biến thể</button>
                        </div>
                    </div>
                                <!-- Modal riêng cho chọn ảnh biến thể -->
                                <div class="modal fade" id="variantImageModal" tabindex="-1" aria-labelledby="variantImageModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="variantImageModalLabel">Chọn hình ảnh biến thể</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div id="variant-image-manager" class="mb-3 text-center">
                                                                        <div id="variant-image-preview-modal" class="mb-2"></div>
                                                                            <button type="button" class="btn btn-outline-secondary" id="btnSelectVariantImageFromLibrary">Hiển thị thư viện hình ảnh</button>
                                                                        <input type="hidden" id="variant-image-modal-media-id">
                                                                    </div>
                                                                        <div id="variant-image-library-container">
                                                                          <iframe id="variantImageIframe" src="{{ route('variants.image-library') }}" frameborder="0" style="width:100%; height:400px;"></iframe>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-success" id="btnApplyVariantImage">Gán ảnh cho biến thể</button>
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                </div>

                @if(($product->product_type ?? \App\Models\Product::TYPE_WHOLE) === \App\Models\Product::TYPE_WHOLE)
                    <div class="form-section" id="cutting-components">
                        <div class="section-title">Thành Phần Pha Lóc</div>
                        <div class="text-muted small mb-3">
                            Chọn các sản phẩm thuộc loại Pha lóc để làm mẫu thành phần cho sản phẩm nguyên con này.
                            Số liệu khối lượng thực tế sẽ nhập riêng cho từng biến thể tại trang biến thể sản phẩm.
                        </div>

                        @if($cutComponentVariants->isEmpty())
                            <div class="alert alert-warning mb-0">
                                Chưa có sản phẩm nào được khai báo Loại hình = Pha lóc.
                            </div>
                        @else
                            @php
                                $selectedCutComponentIds = $product->cuttingComponents
                                    ->pluck('component_product_variant_id')
                                    ->map(fn($id) => (int) $id)
                                    ->all();
                            @endphp
                            <div class="row g-2">
                                @foreach($cutComponentVariants as $componentVariant)
                                    <div class="col-lg-4 col-md-6">
                                        <label class="border rounded p-2 d-flex gap-2 align-items-start h-100">
                                            <input type="checkbox"
                                                   class="form-check-input mt-1"
                                                   name="cutting_component_variant_ids[]"
                                                   value="{{ $componentVariant->id }}"
                                                   @checked(in_array((int) $componentVariant->id, $selectedCutComponentIds, true))>
                                            <span>
                                                <span class="fw-semibold d-block">{{ $componentVariant->product?->name }}</span>
                                                <span class="text-muted small">{{ $componentVariant->name ?: 'Mặc định' }}</span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 small text-muted">
                                Sau khi lưu, vào <a href="{{ route('product-variants.index', ['product_id' => $product->id]) }}">Biến thể sản phẩm</a>
                                để nhập khối lượng chuẩn và tỷ lệ % cho từng size.
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Nút submit --}}
                <div class="action-bar">
                    <button type="submit" class="btn btn-primary me-2">Cập nhật sản phẩm</button> 
                    <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Hủy</a>  
                </div>
            </form>
    </div>
</div>
<div class="modal fade" id="mediaModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Chọn hình ảnh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <iframe src="{{ route('media.library.popup') }}" frameborder="0"
                style="width:100%; height:500px;"></iframe>
      </div>
    </div>
  </div>
</div>

{{-- Modal Gallery --}}
<div class="modal fade" id="mediaGalleryModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Chọn ảnh</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <iframe src="{{ route('media.gallery.popup') }}" frameborder="0"
                style="width:100%; height:500px;"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="confirmMediaSelect" data-bs-dismiss="modal">Chọn</button>
      </div>
    </div>
  </div>
</div>
@endsection


@push('scripts')
<script>
// Đảm bảo cập nhật nhanh ảnh đại diện sản phẩm khi chọn từ popup
document.addEventListener('DOMContentLoaded', function () {
    const btnSelectMedia = document.getElementById('btnSelectMedia');
    const mediaPreview = document.getElementById('mediaPreview');
    const mediaIdInput = document.getElementById('media_id');
    const mediaModalEl = document.getElementById('mediaModal');
    let mediaModal = null;

    function cleanupModalBackdrop() {
        document.querySelectorAll('.modal-backdrop').forEach(function (el) {
            el.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
    }

    function closeMediaModal() {
        if (window.bootstrap && mediaModalEl) {
            const instance = bootstrap.Modal.getInstance(mediaModalEl) || bootstrap.Modal.getOrCreateInstance(mediaModalEl);
            instance.hide();
        } else if (window.$ && typeof $('#mediaModal').modal === 'function') {
            $('#mediaModal').modal('hide');
        }

        // Fallback for mixed bootstrap/jquery modal states.
        setTimeout(cleanupModalBackdrop, 100);
    }

    if (mediaModalEl) {
        mediaModalEl.addEventListener('hidden.bs.modal', cleanupModalBackdrop);
    }

    if (btnSelectMedia) {
        btnSelectMedia.addEventListener('click', function () {
            window.__productEditImageTarget = 'avatar';

            if (!mediaModal) {
                mediaModal = new bootstrap.Modal(mediaModalEl);
            }
            mediaModal.show();
        });
    }

    // Lắng nghe postMessage từ popup media (ổn định, không phụ thuộc window.selectMedia)
    window.addEventListener('message', function (event) {
        if (event.data && event.data.type === 'mediaSelected') {
            const target = window.__productEditImageTarget || 'avatar';

            if (target !== 'avatar') {
                return;
            }

            if (mediaIdInput) {
                mediaIdInput.value = event.data.mediaId;
            }

            if (mediaPreview) {
                mediaPreview.innerHTML = `<img src="${event.data.url}" width="120" class="img-thumbnail">`;
            }

            closeMediaModal();
        }
    });
});
</script>
@endpush

<script>
// Thêm biến thể mới (có cột hình ảnh)

document.addEventListener('DOMContentLoaded', function () {
    var addVariantBtn = document.getElementById('addVariant');
    if (addVariantBtn) {
        addVariantBtn.addEventListener('click', function () {
            let variantList = document.getElementById('variant-list');
            let index = Date.now();
            let item = `
                <div class="variant-item" data-variant-id="new_${index}">
                    <div class="variant-grid">
                        <div class="variant-cell">
                            <label class="variant-cell-label">SKU</label>
                            <input type="text" name="variants[new_${index}][sku]" class="form-control">
                        </div>
                        <div class="variant-cell">
                            <label class="variant-cell-label">Size</label>
                            <input type="text" name="variants[new_${index}][size]" class="form-control">
                        </div>
                        <div class="variant-cell">
                            <label class="variant-cell-label">Thứ tự</label>
                            <input type="number" name="variants[new_${index}][sort_order]" class="form-control" min="0" step="1" value="0">
                        </div>
                        <div class="variant-cell">
                            <label class="variant-cell-label">Số Kg quy đổi</label>
                            <input type="number" name="variants[new_${index}][kg]" class="form-control" min="0.01" step="0.01" value="1" required>
                        </div>
                        <div class="variant-cell">
                            <label class="variant-cell-label">Theo Kg</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" name="variants[new_${index}][is_priced_by_kg]" value="1" checked>
                            </div>
                        </div>
                        <div class="variant-cell variant-cell-image">
                            <label class="variant-cell-label">Hình ảnh</label>
                            <div class="variant-image-preview mb-1"></div>
                            <input type="hidden" name="variants[new_${index}][media_id]" id="variant-media-id-new_${index}">
                            <button type="button" class="btn btn-sm btn-outline-secondary variant-select-image-btn select-variant-image" data-variant-id="new_${index}">Chọn hình ảnh</button>
                        </div>
                        <div class="variant-cell variant-cell-actions">
                            <label class="variant-cell-label">Thao tác</label>
                            <div class="variant-actions">
                                <button type="button" class="btn btn-danger btn-sm remove-variant">Xóa</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            variantList.insertAdjacentHTML('beforeend', item);
            updateVariantCount();
        });
    }

    // Xóa biến thể
    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-variant');
        if (removeBtn) {
            removeBtn.closest('tr')?.remove();
            updateVariantCount();
        }
    });

});

function updateVariantCount() {
    const countNode = document.getElementById('variant-count');
    const list = document.getElementById('variant-list');
    if (!countNode || !list) {
        return;
    }

    countNode.textContent = String(list.querySelectorAll('.variant-item').length);
}

function selectMedia(id, url) {
    document.getElementById('media_id').value = id;
    document.getElementById('mediaPreview').innerHTML =
        `<img src="${url}" width="120" class="img-thumbnail">`;

    if (window.bootstrap && document.getElementById('mediaModal')) {
        const modalEl = document.getElementById('mediaModal');
        const instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.hide();
        return;
    }

    if (window.$ && typeof $('#mediaModal').modal === 'function') {
        $('#mediaModal').modal('hide');
    }

    window.__productEditImageTarget = 'avatar';
}

// Gallery xử lý chọn ảnh
let gallerySelected = @json(
    isset($product) && $product->gallery 
        ? $product->gallery->map(fn($g) => [
            'id' => $g->media->id,
            'path' => asset('storage/' . $g->media->file_path)
        ])
        : []
);

document.addEventListener("DOMContentLoaded", function () {
    updatePreview();
});

window.addEventListener("message", function(event) {
    if (event.data.type === 'gallerySelected') {
        gallerySelected = event.data.data;
        updatePreview();
    }

    if (event.data.type === 'mediaSelected' && (window.__productEditImageTarget === 'variant')) {
        const mediaIdInput = document.getElementById('variant-image-modal-media-id');
        const previewModal = document.getElementById('variant-image-preview-modal');
        const libraryContainer = document.getElementById('variant-image-library-container');

        if (mediaIdInput) {
            mediaIdInput.value = event.data.mediaId || '';
        }

        if (previewModal && event.data.url) {
            previewModal.innerHTML = `<img src="${event.data.url}" width="120" class="img-thumbnail">`;
        }

        if (libraryContainer) {
              libraryContainer.style.display = '';
        }
    }
});


document.addEventListener('DOMContentLoaded', function () {
    var confirmMediaSelectBtn = document.getElementById('confirmMediaSelect');
    if (confirmMediaSelectBtn) {
        confirmMediaSelectBtn.addEventListener('click', () => {
            let iframe = document.querySelector('#mediaGalleryModal iframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.sendSelectedToParent();
            }
        });
    }

    var mediaGalleryModal = document.getElementById('mediaGalleryModal');
    if (mediaGalleryModal) {
        mediaGalleryModal.addEventListener('show.bs.modal', function () {
            let iframe = document.querySelector('#mediaGalleryModal iframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage({
                    type: 'initSelected',
                    data: gallerySelected.map(x => x.id)
                }, '*');
            }
        });
    }
});

function updatePreview() {
    let preview = document.getElementById('gallery-preview');
    preview.innerHTML = '';
    gallerySelected.forEach(item => {
        let div = document.createElement('div');
        div.classList.add('gallery-item', 'position-relative', 'm-2');
        div.dataset.id = item.id;
        div.innerHTML = `
            <img src="${item.path}" width="80" class="rounded">
            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-gallery">&times;</button>
            <input type="hidden" name="gallery[]" value="${item.id}">
        `;
        preview.appendChild(div);
    });
    document.getElementById('gallery-ids').value = gallerySelected.map(x => x.id).join(',');
    document.querySelectorAll('.remove-gallery').forEach(btn => {
        btn.addEventListener('click', (e) => {
            let parent = e.target.closest('.gallery-item');
            let id = parent.dataset.id;
            gallerySelected = gallerySelected.filter(x => String(x.id) !== String(id));
            updatePreview();
            let iframe = document.querySelector('#mediaGalleryModal iframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage({
                    type: 'removeSelected',
                    id: id
                }, '*');
            }
        });
    });
}

// Quản trị hình ảnh biến thể với modal riêng
let currentVariantId = null;
let currentVariantImageUrl = null;
let currentVariantMediaId = null;

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('select-variant-image')) {
        window.__productEditImageTarget = 'variant';
        currentVariantId = e.target.dataset.variantId;
        window.__productEditCurrentVariantId = currentVariantId;

        let preview = document.querySelector(`.variant-item[data-variant-id="${currentVariantId}"] .variant-image-preview img`);
        let mediaIdInput = document.getElementById('variant-media-id-' + currentVariantId);
        currentVariantImageUrl = preview ? preview.src : null;
        currentVariantMediaId = mediaIdInput ? mediaIdInput.value : null;

        const variantPreviewModal = document.getElementById('variant-image-preview-modal');
        const variantMediaModalInput = document.getElementById('variant-image-modal-media-id');
        const variantLibraryContainer = document.getElementById('variant-image-library-container');

        if (variantPreviewModal) {
            variantPreviewModal.innerHTML = currentVariantImageUrl
                ? `<img src="${currentVariantImageUrl}" width="120" class="img-thumbnail">`
                : '<span class="text-muted">Chưa có hình ảnh</span>';
        }

        if (variantMediaModalInput) {
            variantMediaModalInput.value = currentVariantMediaId || '';
        }

        if (variantLibraryContainer) {
            variantLibraryContainer.style.display = '';
        }

        if (!window.bootstrap) {
            return;
        }

        const variantModalEl = document.getElementById('variantImageModal');
        const modal = bootstrap.Modal.getInstance(variantModalEl) || bootstrap.Modal.getOrCreateInstance(variantModalEl);
        modal.show();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const btnSelectVariantImageFromLibrary = document.getElementById('btnSelectVariantImageFromLibrary');
    if (btnSelectVariantImageFromLibrary) {
        btnSelectVariantImageFromLibrary.addEventListener('click', function () {
            window.__productEditImageTarget = 'variant';
            const container = document.getElementById('variant-image-library-container');
            if (container) {
                container.style.display = '';
                container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

    const btnApplyVariantImage = document.getElementById('btnApplyVariantImage');
    if (btnApplyVariantImage) {
        btnApplyVariantImage.addEventListener('click', function () {
            if (!currentVariantId) {
                return;
            }

            const modalMediaInput = document.getElementById('variant-image-modal-media-id');
            const modalPreviewImage = document.querySelector('#variant-image-preview-modal img');
            const mediaId = modalMediaInput ? modalMediaInput.value : '';
            const url = modalPreviewImage ? modalPreviewImage.src : '';

            const input = document.getElementById('variant-media-id-' + currentVariantId);
            if (input) {
                input.value = mediaId;
            }

            const preview = document.querySelector(`.variant-item[data-variant-id="${currentVariantId}"] .variant-image-preview`)
                || document.querySelector(`#variant-media-id-${currentVariantId}`)?.closest('.variant-cell')?.querySelector('.variant-image-preview');
            if (preview && url) {
                preview.innerHTML = `<img src="${url}" width="50" class="rounded">`;
            }

            if (window.bootstrap) {
                const variantModalEl = document.getElementById('variantImageModal');
                const modal = bootstrap.Modal.getInstance(variantModalEl) || bootstrap.Modal.getOrCreateInstance(variantModalEl);
                modal.hide();
            }

            window.__productEditImageTarget = 'avatar';
        });
    }

    const variantModalEl = document.getElementById('variantImageModal');
    if (variantModalEl) {
        variantModalEl.addEventListener('hidden.bs.modal', function () {
            window.__productEditImageTarget = 'avatar';
        });
    }
});
</script>
