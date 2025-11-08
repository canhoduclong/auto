@extends('layouts.popup')

@section('content')
<div class="container"> 
    <h2>Thư viện Media</h2>
    <div class="row">
        <div class="col-md-12 mb-3">  
            <a href="#" class="btn btn-primary mb-3">Thêm Media</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">  
            <h2>Upload media mới</h2>
            <form action="{{ route('media.popup.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file[]" multiple required>
                <button type="submit" class="btn btn-primary">Upload</button>
            </form>
        </div>
    </div>
    <div class="row">
        @foreach($media as $item)
            <div class="col-md-2 mb-3 text-center">
                <img src="{{ asset('storage/'.$item->file_path) }}"
                     alt="media"
                     class="img-thumbnail"
                     style="cursor:pointer; max-height:120px;"
                     onclick="selectImage('{{ $item->id }}', '{{ asset('storage/'.$item->file_path) }}')">
            </div>
        @endforeach
    </div>
    <div>
        {{ $media->links() }}
    </div>
</div>

<script>
    function selectImage(id, url) {
        // gọi function ở parent (edit.blade.php)
        window.parent.selectMedia(id, url);
    }
</script>
@endsection
