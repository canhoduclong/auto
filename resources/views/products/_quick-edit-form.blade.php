<form method='POST' action='/products/{{ $product->id }}' class="quick-edit-form-instance">
    <input type='hidden' name='_token' value='{{ csrf_token() }}'>
    <input type='hidden' name='_method' value='PUT'>
    <input type="hidden" name="media_id" id="quick-edit-media-id-{{ $product->id }}">
    <div style="display: inline-block; margin-right: 10px;">
        <label>Tên sản phẩm</label>
        <input type='text' name='name' value='{{ $product->name }}' class='form-control'>
    </div>
    <div style="display: inline-block; margin-right: 10px;">
        <label>Giá</label>
        <input type='text' name='price' value='{{ $product->price }}' class='form-control'>
    </div>
    <div style="display: inline-block; margin-right: 10px;">
        <label>Tồn kho</label>
        <input type='text' name='stock' value='{{ $product->stock }}' class='form-control'>
    </div>
    <div style="display: inline-block; margin-right: 10px;">
        <label>Ảnh</label>
        <div>
            <button type="button" class="btn btn-primary btn-sm choose-image-btn" data-product-id="{{ $product->id }}">Chọn ảnh</button>
        </div>
        <img src="{{ $product->avatar && $product->avatar->media ? asset('storage/' . $product->avatar->media->file_path) : '' }}" id="quick-edit-preview-image-{{ $product->id }}" width="100" class="mt-2">
    </div>
    <button type='submit' class='btn btn-primary btn-sm'>Lưu</button>
    <button type='button' class='btn btn-secondary btn-sm cancel-quick-edit'>Hủy</button>
</form>
