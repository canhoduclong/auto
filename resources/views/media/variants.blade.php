@php($images = \App\Models\Media::latest()->take(100)->get())
<div class="contrainer">
    <div class="row">
        <div class="media-grid">
            @foreach($images as $image)
                <div class="col">
                    <div class="card h-100 border-primary border-2 shadow-sm">
                        <img src="{{ asset('storage/' . $image->file_path) }}" class="card-img-top" style="cursor:pointer;object-fit:cover;height:180px" onclick="selectVariantImage({{ $image->id }}, '{{ asset('storage/' . $image->file_path) }}')">
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
<div class="mb-3">
    <form action="{{ route('media.store') }}" method="POST" enctype="multipart/form-data" target="upload_target" id="uploadForm">
        @csrf
        <label class="btn btn-primary mb-2">
            <i class="ph-upload"></i> Upload nhiều hình
            <input type="file" name="file[]" accept="image/*" multiple style="display:none" onchange="this.form.submit()">
        </label>
    </form>
    <iframe name="upload_target" style="display:none"></iframe>
</div>
<script>
function selectVariantImage(mediaId, url) {
    // Gửi message về parent window (modal quản trị biến thể)
    if (window.parent) {
        window.parent.postMessage({ type: 'mediaSelected', mediaId: mediaId, url: url }, '*');
    }
}
// Sau khi upload xong, reload lại grid ảnh
window.uploadDone = function() {
    window.location.reload();
}
</script>
