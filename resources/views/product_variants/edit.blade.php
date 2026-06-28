@extends('layouts.app')
@section('content')
<div class="container">
    <h4 class="mb-3">Sửa biến thể sản phẩm</h4>
    <form action="{{ route('product-variants.update', $variant->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
                    <div class="mb-3">
                        <label class="form-label">Tên biến thể <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $variant->name) }}" required>
                    </div>
            <label class="form-label">Sản phẩm</label>
            <select name="product_id" class="form-select" required>
                <option value="">-- Chọn sản phẩm --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ $variant->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">SKU</label>
            <input type="text" name="sku" class="form-control" value="{{ $variant->sku }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Size</label>
            <input type="text" name="size" class="form-control" value="{{ $variant->size }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Thứ tự hiển thị</label>
            <input type="number" name="sort_order" class="form-control" min="0" step="1" value="{{ old('sort_order', $variant->sort_order ?? 0) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Kg quy đổi <span class="text-danger">*</span></label>
            <input type="number" name="kg" class="form-control" value="{{ old('kg', $variant->kg ?? 1) }}" min="0.01" step="0.01" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">DVT (Sản phẩm)</label>
            <div class="d-flex align-items-center gap-2">
                <input type="text" class="form-control" value="{{ $variant->product?->unit_label ?? 'Chưa có' }}" readonly disabled>
                <a href="{{ route('products.edit', $variant->product_id) }}" class="btn btn-outline-secondary btn-sm text-nowrap" target="_blank" title="Sửa sản phẩm để thay đổi DVT">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Sửa SP
                </a>
            </div>
            <div class="form-text text-muted">DVT được lấy từ sản phẩm. Chỉnh sửa tại trang sản phẩm.</div>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_priced_by_kg" name="is_priced_by_kg" value="1" {{ old('is_priced_by_kg', $variant->is_priced_by_kg ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_priced_by_kg">Tính tiền theo kg</label>
        </div>
        <div class="mb-3">
            <label class="form-label">Chất lượng</label>
            <input type="text" name="quality" class="form-control" value="{{ $variant->quality }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Ngày sản xuất</label>
            <input type="date" name="production_date" class="form-control" value="{{ $variant->production_date }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Tồn kho</label>
            <input type="number" name="stock" class="form-control" value="{{ $variant->stock }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Hình ảnh</label>
            <div class="mb-2" id="variant-image-preview-edit">
                @if($variant->mediaLink?->media)
                    <img src="{{ asset('storage/' . $variant->mediaLink->media->file_path) }}" width="60" class="img-thumbnail" alt="{{ $variant->sku }}">
                @endif
            </div>
            <input type="hidden" name="media_id" id="variant-media-id-edit" value="{{ old('media_id', $variant->mediaLink?->media_id) }}">
            <button type="button" class="btn btn-info" id="btnSelectVariantImageEdit">Chọn ảnh từ thư viện</button>
        </div>

        @if(($variant->product?->product_type ?? '') === \App\Models\Product::TYPE_WHOLE)
            <div class="card mb-3" id="cutting-components">
                <div class="card-header fw-semibold">Thành phần pha lóc của biến thể</div>
                <div class="card-body">
                    @if($variant->product->cuttingComponents->isEmpty())
                        <div class="alert alert-warning mb-0">
                            Sản phẩm này chưa có mẫu thành phần pha lóc.
                            <a href="{{ route('products.edit', $variant->product_id) }}#cutting-components">Thêm mẫu thành phần</a>
                        </div>
                    @else
                        @php
                            $ratiosByComponent = $variant->componentRatios->keyBy('component_product_variant_id');
                        @endphp
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm pha lóc</th>
                                        <th style="width:180px;">Khối lượng chuẩn</th>
                                        <th style="width:160px;">Tỷ lệ %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($variant->product->cuttingComponents as $component)
                                        @php
                                            $componentVariant = $component->componentVariant;
                                            $ratio = $ratiosByComponent->get($component->component_product_variant_id);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $componentVariant?->product?->name ?? 'Sản phẩm' }}</div>
                                                <div class="text-muted small">{{ $componentVariant?->name ?: 'Mặc định' }}</div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                           name="component_weights[{{ $component->component_product_variant_id }}]"
                                                           class="form-control"
                                                           min="0"
                                                           step="0.001"
                                                           value="{{ old('component_weights.'.$component->component_product_variant_id, $ratio?->standard_weight ?? '') }}">
                                                    <span class="input-group-text">kg</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number"
                                                           name="component_percentages[{{ $component->component_product_variant_id }}]"
                                                           class="form-control"
                                                           min="0"
                                                           max="100"
                                                           step="0.001"
                                                           value="{{ old('component_percentages.'.$component->component_product_variant_id, $ratio?->percentage ?? '') }}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <button class="btn btn-primary">Cập nhật biến thể</button>
        <a href="{{ route('product-variants.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btnSelectVariantImageEdit');
    if (btn) {
        btn.addEventListener('click', function() {
            let modalHtml = `
            <div class='modal fade' id='variantImageModalEdit' tabindex='-1'>
              <div class='modal-dialog modal-lg'>
                <div class='modal-content'>
                  <div class='modal-header'>
                    <h5 class='modal-title'>Chọn hình ảnh</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                  </div>
                  <div class='modal-body p-0'>
                    <iframe id='variantImageIframeEdit' src='{{ route('variants.image-library') }}' frameborder='0' style='width:100%; height:400px;'></iframe>
                  </div>
                </div>
              </div>
            </div>`;
            let modalDiv = document.createElement('div');
            modalDiv.innerHTML = modalHtml;
            document.body.appendChild(modalDiv);
            let modal = new bootstrap.Modal(document.getElementById('variantImageModalEdit'));
            modal.show();
            window.addEventListener('message', function handler(event) {
                if (event.data && event.data.type === 'mediaSelected') {
                    document.getElementById('variant-media-id-edit').value = event.data.mediaId;
                    document.getElementById('variant-image-preview-edit').innerHTML = `<img src="${event.data.url}" width="120" class="img-thumbnail">`;
                    modal.hide();
                    window.removeEventListener('message', handler);
                }
            });
            document.getElementById('variantImageModalEdit').addEventListener('hidden.bs.modal', function () {
                modalDiv.remove();
            });
        });
    }
});
</script>
@endpush
@endsection
