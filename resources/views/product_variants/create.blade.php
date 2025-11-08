@extends('layouts.app')
@section('content')
<div class="container">
    <h4 class="mb-3">Thêm biến thể sản phẩm</h4>
    <form action="{{ route('product-variants.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Sản phẩm</label>
            <select name="product_id" class="form-select" required>
                <option value="">-- Chọn sản phẩm --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">SKU</label>
            <input type="text" name="sku" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Size</label>
            <input type="text" name="size" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Chất lượng</label>
            <input type="text" name="quality" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Ngày sản xuất</label>
            <input type="date" name="production_date" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Tồn kho</label>
            <input type="number" name="stock" class="form-control" value="0">
        </div>
        <div class="mb-3">
            <label class="form-label">Hình ảnh</label>
            <div class="mb-2" id="variant-image-preview-create"></div>
            <input type="hidden" name="media_id" id="variant-media-id-create">
            <button type="button" class="btn btn-info" id="btnSelectVariantImageCreate">Chọn ảnh từ thư viện</button>
        </div>
        <div class="mb-3">
            <label class="form-label">Giá bán (VNĐ)</label>
            <input type="number" name="price" class="form-control" min="0" step="1000">
        </div>
        <button class="btn btn-primary">Thêm biến thể</button>
        <a href="{{ route('product-variants.index') }}" class="btn btn-secondary">Quay lại</a>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('btnSelectVariantImageCreate');
        if (btn) {
                btn.addEventListener('click', function() {
                        let modalHtml = `
                        <div class='modal fade' id='variantImageModalCreate' tabindex='-1'>
                            <div class='modal-dialog modal-lg'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <h5 class='modal-title'>Chọn hình ảnh</h5>
                                        <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                    </div>
                                    <div class='modal-body p-0'>
                                        <iframe id='variantImageIframeCreate' src='{{ route('variants.image-library') }}' frameborder='0' style='width:100%; height:400px;'></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                        let modalDiv = document.createElement('div');
                        modalDiv.innerHTML = modalHtml;
                        document.body.appendChild(modalDiv);
                        let modal = new bootstrap.Modal(document.getElementById('variantImageModalCreate'));
                        modal.show();
                        window.addEventListener('message', function handler(event) {
                                if (event.data && event.data.type === 'mediaSelected') {
                                        document.getElementById('variant-media-id-create').value = event.data.mediaId;
                                        document.getElementById('variant-image-preview-create').innerHTML = `<img src="${event.data.url}" width="120" class="img-thumbnail">`;
                                        modal.hide();
                                        window.removeEventListener('message', handler);
                                }
                        });
                        document.getElementById('variantImageModalCreate').addEventListener('hidden.bs.modal', function () {
                                modalDiv.remove();
                        });
                });
        }
});
</script>
@endpush
    </form>
</div>
@endsection
