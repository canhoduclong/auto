@extends('layouts.popup')

@section('content')
<div class="container-fluid">
    <div class="row">
        @foreach($media as $m)
        <div class="col-md-2 mb-3">
            <div class="card media-card" data-media-id="{{ $m->id }}" data-media-url="{{ asset('storage/' . $m->file_path) }}">
                <img src="{{ asset('storage/' . $m->file_path) }}" class="card-img-top" alt="{{ $m->file_name }}">
                <div class="card-body">
                    <p class="card-text">{{ $m->file_name }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.media-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var mediaId = this.dataset.mediaId;
            var url = this.dataset.mediaUrl;
            selectImage(mediaId, url);
        });
    });
});

function selectImage(mediaId, url) {
    const productId = new URLSearchParams(window.location.search).get('product_id');
    const message = {
        type: 'MEDIA_SELECTED',
        productId: productId,
        mediaId: mediaId,
        url: url
    };
    window.parent.postMessage(message, '*');
}
</script>
@endsection
