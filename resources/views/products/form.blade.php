<div class="mb-3">
    <label for="image" class="form-label">Ảnh sản phẩm</label>
    <div class="input-group">
        <input type="hidden" name="media_id" id="media_id" value="{{ old('media_id', $product->media?->id) }}">
        
        <img id="preview-image" 
             src="{{ $product->media ? asset('storage/' . $product->media->file_path) : 'https://via.placeholder.com/150' }}" 
             class="img-thumbnail" style="max-height: 150px; cursor: pointer;"
             onclick="openMediaLibrary()">
    </div>
</div>

<!-- Modal Media Library -->
<div class="modal fade" id="mediaLibraryModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Chọn ảnh từ Media Library</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          @foreach($media as $item)
              <div class="col-md-2 mb-3">
                  <img src="{{ asset('storage/' . $item->file_path) }}"
                       class="img-thumbnail"
                       style="cursor:pointer;"
                       onclick="selectMedia({{ $item->id }}, '{{ asset('storage/' . $item->file_path) }}')">
              </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select name="category_id" id="category_id" class="form-control">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ isset($product) && $product->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="brand_id">Brand</label>
            <select name="brand_id" id="brand_id" class="form-control">
                <option value="">Select a brand</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ isset($product) && $product->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>

<script>
function openMediaLibrary() {
    var myModal = new bootstrap.Modal(document.getElementById('mediaLibraryModal'));
    myModal.show();
}

function selectMedia(id, url) {
    document.getElementById('media_id').value = id;
    document.getElementById('preview-image').src = url;

    // đóng modal sau khi chọn
    var myModalEl = document.getElementById('mediaLibraryModal');
    var modal = bootstrap.Modal.getInstance(myModalEl);
    modal.hide();
}
</script>

