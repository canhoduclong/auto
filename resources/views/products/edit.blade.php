@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h4 class="mb-0">Chỉnh sửa sản phẩm</h4> 

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
                @csrf
                @method('PUT')

                {{-- Tên sản phẩm --}}
                <div class="mb-3">
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

                {{-- Danh mục --}}
                <div class="mb-3">
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

                <div class="mb-3">
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

                {{-- Ảnh đại diện --}}
                <div class="form-group">
                    <label>Ảnh đại diện</label>
                    <div>
                        <button type="button" class="btn btn-primary" id="btnSelectMedia">
                            Chọn ảnh
                        </button>
                        <input type="hidden" name="media_id" id="media_id"
                            value="{{ old('media_id', $product->avatar->media_id ?? '') }}">
                    </div>
                    <div id="mediaPreview" style="margin-top:10px;">
                        @if(!empty($product->avatar) && $product->avatar->media)
                            <img src="{{ asset('storage/'.$product->avatar->media->file_path) }}"
                                 width="120" class="img-thumbnail">
                        @endif
                    </div>
                </div>

                {{-- Gallery --}}
                <div class="mt-3">
                    <label>Gallery</label>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#mediaGalleryModal">
                        Chọn ảnh
                    </button>
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

                {{-- Biến thể --}}
                <div class="mb-3 mt-4">
                    <label class="form-label">Biến thể</label>
                    <table class="table table-bordered" id="variant-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Size</th>
                                <th>Chất lượng</th>
                                <th>Ngày SX</th>
                                <th>Hình ảnh</th>
                                <th>Giá bán</th>
                                <th>Ngày áp dụng</th>
                                <th>Số lượng tồn</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->variants as $variant)
                            <tr data-variant-id="{{ $variant->id }}">
                                {{-- key là id => server dùng để nhận diện existing variant --}}
                                <td>
                                    <input type="text" name="variants[{{ $variant->id }}][sku]" class="form-control" value="{{ old('variants.'.$variant->id.'.sku', $variant->sku) }}">
                                </td>
                                <td>
                                    <input type="text" name="variants[{{ $variant->id }}][size]" class="form-control"
                                        value="{{ old('variants.'.$variant->id.'.size', $variant->size) }}">
                                </td>
                                <td>
                                    <input type="text" name="variants[{{ $variant->id }}][quality]" class="form-control"
                                        value="{{ old('variants.'.$variant->id.'.quality', $variant->quality) }}">
                                </td>
                                <td>
                                    <input type="date" name="variants[{{ $variant->id }}][production_date]" class="form-control"
                                        value="{{ old('variants.'.$variant->id.'.production_date', $variant->production_date ? \Carbon\Carbon::parse($variant->production_date)->format('Y-m-d') : '') }}">
                                </td>
                                <td>
                                    <div class="variant-image-preview mb-1">
                                        @if($variant->media)
                                            <img src="{{ asset('storage/' . $variant->media->file_path) }}" width="50" class="rounded">
                                        @endif
                                    </div>
                                    <div>
                                        <input type="hidden" name="variants[{{ $variant->id }}][media_id]" id="variant-media-id-{{ $variant->id }}" value="{{ old('variants.'.$variant->id.'.media_id', $variant->media_id ?? '') }}">
                                        <button type="button" class="btn btn-sm btn-secondary select-variant-image" data-variant-id="{{ $variant->id }}">Chọn ảnh</button>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" name="variants[{{ $variant->id }}][price]" class="form-control" min="0" step="1000" value="{{ old('variants.'.$variant->id.'.price', $variant->final_price) }}">
                                </td>
                                <td>
                                    @php
                                        $latestRule = $variant->priceRules()->whereNull('end_date')->latest('start_date')->first();
                                    @endphp
                                    <div>{{ $latestRule ? \Carbon\Carbon::parse($latestRule->start_date)->format('d/m/Y H:i') : '' }}</div>
                                </td>
                                <td>
                                    <input type="number" name="variants[{{ $variant->id }}][stock]" class="form-control"
                                        value="{{ old('variants.'.$variant->id.'.stock', $variant->stock) }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-variant">X</button>
                                    {{-- nút điều chỉnh giá --}}
                                    <a href="{{ route('variants.edit-price', $variant->id) }}" class="btn btn-sm btn-warning mt-1">Điều chỉnh giá</a>
                                    <button type="button" class="btn btn-info btn-sm mt-1 clone-variant" title="Nhân bản biến thể" data-variant-id="{{ $variant->id }}">Nhân bản</button>
                                    <button type="button" class="btn btn-success btn-sm mt-1 quick-edit-variant" title="Sửa nhanh" data-variant-id="{{ $variant->id }}">Sửa nhanh</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>

                    </table>
                    <button type="button" class="btn btn-primary btn-sm" id="addVariant">+ Thêm biến thể</button>
                                <!-- Modal riêng cho chọn ảnh biến thể -->
                                <div class="modal fade" id="variantImageModal" tabindex="-1" aria-labelledby="variantImageModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="variantImageModalLabel">Quản trị hình ảnh biến thể</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div id="variant-image-manager" class="mb-3 text-center">
                                                                        <div id="variant-image-preview-modal" class="mb-2"></div>
                                                                        <button type="button" class="btn btn-primary" id="btnSelectVariantImageFromLibrary">Chọn ảnh từ thư viện</button>
                                                                        <input type="hidden" id="variant-image-modal-media-id">
                                                                    </div>
                                                                    <div id="variant-image-library-container" style="display:none">
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

                {{-- Mô tả --}}
                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea name="description" 
                              id="description" 
                              rows="3" 
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Nút submit --}}
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">Cập nhật sản phẩm</button> 
                    <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Hủy</a>  
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Avatar --}}
<div class="modal fade" id="mediaModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Chọn hình ảnh</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">×</button>
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

<script>
// Thêm biến thể mới (có cột hình ảnh)

document.addEventListener('DOMContentLoaded', function () {
    var addVariantBtn = document.getElementById('addVariant');
    if (addVariantBtn) {
        addVariantBtn.addEventListener('click', function () {
            let tbody = document.querySelector('#variant-table tbody');
            let index = Date.now();
            let row = `
                <tr>
                    <td><input type="text" name="variants[new_${index}][sku]" class="form-control"></td>
                    <td><input type="text" name="variants[new_${index}][size]" class="form-control"></td>
                    <td><input type="text" name="variants[new_${index}][quality]" class="form-control"></td>
                    <td><input type="date" name="variants[new_${index}][production_date]" class="form-control"></td>
                    <td>
                        <div class="variant-image-preview mb-1"></div>
                        <div>
                            <input type="hidden" name="variants[new_${index}][media_id]" id="variant-media-id-new_${index}">
                            <button type="button" class="btn btn-sm btn-secondary select-variant-image" data-variant-id="new_${index}">Chọn ảnh</button>
                        </div>
                    </td>
                    <td><input type="number" name="variants[new_${index}][price]" class="form-control" min="0" step="1000"></td>
                    <td>-</td>
                    <td><input type="number" name="variants[new_${index}][stock]" class="form-control"></td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-variant">X</button></td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    // Xóa biến thể
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-variant')) {
            e.target.closest('tr').remove();
        }
    });

    // Avatar chọn ảnh
    var btnSelectMedia = document.getElementById('btnSelectMedia');
    if (btnSelectMedia) {
        btnSelectMedia.addEventListener('click', function() {
            $('#mediaModal').modal('show');
        });
    }
});

function selectMedia(id, url) {
    document.getElementById('media_id').value = id;
    document.getElementById('mediaPreview').innerHTML =
        `<img src="${url}" width="120" class="img-thumbnail">`;
    $('#mediaModal').modal('hide');
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
    // Nhận ảnh từ modal chọn ảnh biến thể
    if (event.data.type === 'variantImageSelected') {
        let variantId = event.data.variantId;
        let mediaId = event.data.mediaId;
        let url = event.data.url;
        document.getElementById('variant-media-id-' + variantId).value = mediaId;
        let preview = document.querySelector(`tr[data-variant-id="${variantId}"] .variant-image-preview`)
            || document.querySelector(`#variant-media-id-${variantId}`)?.closest('td').querySelector('.variant-image-preview');
        if (preview) {
            preview.innerHTML = `<img src="${url}" width="50" class="rounded">`;
        }
        $('#variantImageModal').modal('hide');
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
        currentVariantId = e.target.dataset.variantId;
        // Lấy ảnh hiện tại nếu có
        let preview = document.querySelector(`tr[data-variant-id="${currentVariantId}"] .variant-image-preview img`);
        let mediaIdInput = document.getElementById('variant-media-id-' + currentVariantId);
        currentVariantImageUrl = preview ? preview.src : null;
        currentVariantMediaId = mediaIdInput ? mediaIdInput.value : null;
        // Hiển thị modal
        let modal = new bootstrap.Modal(document.getElementById('variantImageModal'));
        document.getElementById('variant-image-preview-modal').innerHTML = currentVariantImageUrl ? `<img src="${currentVariantImageUrl}" width="120" class="img-thumbnail">` : '<span class="text-muted">Chưa có hình ảnh</span>';
        document.getElementById('variant-image-modal-media-id').value = currentVariantMediaId || '';
        document.getElementById('variant-image-library-container').style.display = 'none';
        modal.show();
    }
});

// Nút chọn ảnh từ thư viện trong modal
document.addEventListener('DOMContentLoaded', function () {
    var btnSelectVariantImageFromLibrary = document.getElementById('btnSelectVariantImageFromLibrary');
    if (btnSelectVariantImageFromLibrary) {
        btnSelectVariantImageFromLibrary.addEventListener('click', function() {
            document.getElementById('variant-image-library-container').style.display = '';
        });
    }
    // Nhận ảnh từ iframe thư viện
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'mediaSelected') {
            // event.data.mediaId, event.data.url
            document.getElementById('variant-image-modal-media-id').value = event.data.mediaId;
            document.getElementById('variant-image-preview-modal').innerHTML = `<img src="${event.data.url}" width="120" class="img-thumbnail">`;
            // Ẩn thư viện sau khi chọn
            document.getElementById('variant-image-library-container').style.display = 'none';
        }
    });
    // Gán ảnh cho biến thể
    var btnApplyVariantImage = document.getElementById('btnApplyVariantImage');
    if (btnApplyVariantImage) {
        btnApplyVariantImage.addEventListener('click', function() {
            if (!currentVariantId) return;
            let mediaId = document.getElementById('variant-image-modal-media-id').value;
            let url = document.getElementById('variant-image-preview-modal').querySelector('img')?.src;
            // Gán vào input hidden và preview của dòng biến thể
            let input = document.getElementById('variant-media-id-' + currentVariantId);
            if (input) input.value = mediaId;
            let preview = document.querySelector(`tr[data-variant-id="${currentVariantId}"] .variant-image-preview`)
                || document.querySelector(`#variant-media-id-${currentVariantId}`)?.closest('td').querySelector('.variant-image-preview');
            if (preview && url) {
                preview.innerHTML = `<img src="${url}" width="50" class="rounded">`;
            }
            // Đóng modal
            let modal = bootstrap.Modal.getInstance(document.getElementById('variantImageModal'));
            if (modal) modal.hide();
        });
    }
});
</script>
