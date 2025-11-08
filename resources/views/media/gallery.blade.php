@extends('layouts.popupGallery')

@section('content')
 <style>
    .media-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }
    .media-item {
        border: 2px solid transparent;
        cursor: pointer;
    }
    .media-item.selected {
        border-color: red;
    }
</style>
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
            <form action="{{ route('media.gallery.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" required>
                <button type="submit" class="btn btn-primary">Upload</button>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="media-grid">
            @foreach($media as $item)
                <div class=" mb-3 text-center media-item"  data-id="{{ $item->id }}" data-path="{{ asset('storage/'.$item->file_path) }}">
                    <img src="{{ asset('storage/'.$item->file_path) }}"
                        alt="media"
                        class="img-thumbnail"
                        style="cursor:pointer; max-height:120px;" 
                        >
                </div>
            @endforeach
        </div>  
        </div>
    <div>
        {{ $media->links() }}
    </div>
</div>

<script>
let selected = [];

document.querySelectorAll('.media-item').forEach(item => {
    item.addEventListener('click', () => {
        item.classList.toggle('border-danger');
        let id = item.dataset.id;
        if (selected.includes(id)) {
            selected = selected.filter(x => x !== id);
        } else {
            selected.push(id);
        }
    });
});
// Nhận danh sách ảnh đã chọn từ parent (mỗi lần mở modal)
window.addEventListener("message", function(event) {
    if (event.data.type === 'initSelected') {
        selected = event.data.data.map(String); // ép thành string

        // Reset UI
        document.querySelectorAll('.media-item').forEach(el => {
            let id = el.dataset.id;
            if (selected.includes(id)) {
                el.classList.add('border-danger');
            } else {
                el.classList.remove('border-danger');
            }
        });
    }

     // Gỡ bỏ chọn khi parent xóa trong preview
    if (event.data.type === 'removeSelected') {
        let id = String(event.data.id);
        selected = selected.filter(x => x !== id);

        let el = document.querySelector(`.media-item[data-id="${id}"]`);
        if (el) {
            el.classList.remove('border-danger');
        }
    }

});

// Hàm gửi dữ liệu ra parent khi click "Chọn"
function sendSelectedToParent() {
    let data = selected.map(id => {
        let el = document.querySelector(`.media-item[data-id="${id}"]`);
        return { id: id, path: el.dataset.path };
    });

    // Gửi ra ngoài
    window.parent.postMessage({ type: 'gallerySelected', data: data }, '*');
}
</script>

@endsection
