@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Upload media mới</h2>
    <form id="media-upload-form" action="{{ route('media.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file[]" id="media-upload-input" required multiple accept="image/*">
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
    <div id="media-list-container">
        @include('media._list')
    </div>
</div>

@push('scripts')
<script>
$('#media-upload-form').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function() {
            // Sau khi upload thành công, load lại danh sách hình ảnh
            $('#media-list-container').load(location.href + ' #media-list-container > *');
            $('#media-upload-input').val('');
        },
        error: function(xhr) {
            alert('Upload thất bại!');
        }
    });
});
</script>
@endpush
@endsection
