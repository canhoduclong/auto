@extends('layouts.popup')

@section('content')
<div class="container-fluid media-popup py-3">
    <div class="popup-head card border-0 mb-3">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h3 class="mb-1">Thư viện Media</h3>
                    <p class="mb-0 text-muted">Chọn 1 ảnh để cập nhật nhanh cho sản phẩm.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill text-bg-light" id="selectedMediaHint">Chưa chọn ảnh</span>
                    <button type="button" class="btn btn-light btn-sm" id="closePopupBtn">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-6">
                    <label for="mediaSearch" class="form-label mb-1 small text-muted">Tìm kiếm</label>
                    <input type="text" id="mediaSearch" class="form-control" placeholder="Nhập tên file hoặc định dạng...">
                </div>
                <div class="col-lg-3">
                    <label for="mediaTypeFilter" class="form-label mb-1 small text-muted">Loại</label>
                    <select id="mediaTypeFilter" class="form-select">
                        <option value="all">Tất cả</option>
                        <option value="image">Image</option>
                        <option value="video">Video</option>
                        <option value="application">Application</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <form action="{{ route('media.popup.store') }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2" id="popupUploadForm">
                        @csrf
                        <input type="file" name="file[]" class="form-control" multiple required id="popupUploadInput" accept="image/*">
                        <button type="submit" class="btn btn-primary text-nowrap">Upload</button>
                    </form>
                </div>
            </div>
            <div class="upload-preview d-none" id="uploadPreviewWrap">
                <div class="upload-preview-title">Ảnh sẽ upload:</div>
                <div class="upload-preview-grid" id="uploadPreviewGrid"></div>
            </div>
        </div>
    </div>

    <div class="media-grid" id="mediaGrid">
        @foreach($media as $item)
            @php
                $url = asset('storage/'.$item->file_path);
                $mime = strtolower((string) $item->mime_type);
                $type = explode('/', $mime)[0] ?? 'file';
            @endphp
            <button
                type="button"
                class="media-card-btn"
                data-id="{{ $item->id }}"
                data-url="{{ $url }}"
                data-name="{{ strtolower($item->file_name) }}"
                data-mime="{{ $mime }}"
                data-type="{{ $type }}"
                title="{{ $item->file_name }}">
                <img src="{{ $url }}" alt="{{ $item->file_name }}" class="media-thumb">
                <span class="media-name">{{ $item->file_name }}</span>
            </button>
        @endforeach
    </div>

    <div class="mt-3">
        {{ $media->links() }}
    </div>

    <div class="media-action-bar card border-0 shadow-sm mt-3">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center gap-2">
            <small class="text-muted" id="selectedMediaName">Vui lòng chọn một ảnh để sử dụng.</small>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="dismissPopupBtn">Hủy</button>
                <button type="button" class="btn btn-primary btn-sm" id="useSelectedMediaBtn" disabled>Dùng ảnh đã chọn</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.media-popup {
    background: #f4f7fb;
    min-height: 100vh;
}

.page-content .sidebar {
    display: none;
}

.page-content .content-wrapper {
    margin-left: 0 !important;
    width: 100%;
}

.popup-head {
    background: linear-gradient(125deg, #12395f 0%, #0b7a75 100%);
    color: #f8fafc;
}

.popup-head p {
    color: #d7e6f4 !important;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.upload-preview {
    margin-top: 12px;
    border: 1px dashed #c8d8ee;
    border-radius: 10px;
    background: #f8fbff;
    padding: 10px;
}

.upload-preview-title {
    font-size: 12px;
    color: #5b6f8a;
    font-weight: 700;
    margin-bottom: 8px;
    text-transform: uppercase;
}

.upload-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
    gap: 8px;
}

.upload-preview-item {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #d8e4f2;
    background: #fff;
    position: relative;
}

.upload-preview-item img {
    width: 100%;
    height: 66px;
    object-fit: cover;
    display: block;
}

.upload-preview-item span {
    display: block;
    font-size: 10px;
    color: #334155;
    padding: 3px 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.media-card-btn {
    border: 1px solid #d9e4f2;
    border-radius: 12px;
    background: #fff;
    padding: 8px;
    text-align: left;
    transition: all .18s ease;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
}

.media-card-btn:hover {
    transform: translateY(-2px);
    border-color: #84b7f6;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.11);
}

.media-card-btn.is-selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.14);
}

.media-thumb {
    width: 100%;
    height: 126px;
    object-fit: cover;
    border-radius: 9px;
    display: block;
    background: #eff4fa;
}

.media-name {
    display: block;
    margin-top: 8px;
    font-size: 12px;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.media-action-bar {
    position: sticky;
    bottom: 8px;
    z-index: 20;
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const cards = Array.from(document.querySelectorAll('.media-card-btn'));
    const hint = document.getElementById('selectedMediaHint');
    const selectedName = document.getElementById('selectedMediaName');
    const searchInput = document.getElementById('mediaSearch');
    const typeFilter = document.getElementById('mediaTypeFilter');
    const useSelectedBtn = document.getElementById('useSelectedMediaBtn');
    const dismissPopupBtn = document.getElementById('dismissPopupBtn');
    const closePopupBtn = document.getElementById('closePopupBtn');
    const uploadInput = document.getElementById('popupUploadInput');
    const uploadPreviewWrap = document.getElementById('uploadPreviewWrap');
    const uploadPreviewGrid = document.getElementById('uploadPreviewGrid');

    let selectedMedia = null;

    function closeHostPopup() {
        if (window.parent && window.parent !== window) {
            if (window.parent.bootstrap && window.parent.document.getElementById('mediaModal')) {
                const modalEl = window.parent.document.getElementById('mediaModal');
                const instance = window.parent.bootstrap.Modal.getInstance(modalEl) || window.parent.bootstrap.Modal.getOrCreateInstance(modalEl);
                instance.hide();
                return;
            }

            if (window.parent.$ && typeof window.parent.$('#mediaModal').modal === 'function') {
                window.parent.$('#mediaModal').modal('hide');
                return;
            }
        }

        if (window.opener) {
            window.close();
        }
    }

    function emitToHost(id, url) {
        if (window.parent && window.parent !== window && typeof window.parent.selectMedia === 'function') {
            window.parent.selectMedia(id, url);
            return;
        }

        if (window.opener && typeof window.opener.selectMedia === 'function') {
            window.opener.selectMedia(id, url);
            window.close();
            return;
        }

        if (window.parent && window.parent !== window) {
            window.parent.postMessage({ type: 'mediaSelected', mediaId: id, url: url }, '*');
        }
    }

    function renderUploadPreview(files) {
        uploadPreviewGrid.innerHTML = '';

        if (!files || files.length === 0) {
            uploadPreviewWrap.classList.add('d-none');
            return;
        }

        uploadPreviewWrap.classList.remove('d-none');

        Array.from(files).slice(0, 12).forEach((file) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const item = document.createElement('div');
                item.className = 'upload-preview-item';
                item.innerHTML = `<img src="${e.target.result}" alt="preview"><span title="${file.name}">${file.name}</span>`;
                uploadPreviewGrid.appendChild(item);
            };
            reader.readAsDataURL(file);
        });
    }

    function filterGrid() {
        const keyword = (searchInput.value || '').trim().toLowerCase();
        const selectedType = typeFilter.value || 'all';

        cards.forEach((card) => {
            const name = card.dataset.name || '';
            const mime = card.dataset.mime || '';
            const type = card.dataset.type || '';
            const okKeyword = name.includes(keyword) || mime.includes(keyword);
            const okType = selectedType === 'all' || type === selectedType;
            card.style.display = (okKeyword && okType) ? '' : 'none';
        });
    }

    cards.forEach((card) => {
        card.addEventListener('click', () => {
            cards.forEach((x) => x.classList.remove('is-selected'));
            card.classList.add('is-selected');
            hint.textContent = 'Đã chọn #' + card.dataset.id;
            selectedName.textContent = card.getAttribute('title') || ('Ảnh #' + card.dataset.id);
            useSelectedBtn.disabled = false;
            selectedMedia = {
                id: card.dataset.id,
                url: card.dataset.url,
            };
        });

        card.addEventListener('dblclick', () => {
            emitToHost(card.dataset.id, card.dataset.url);
            closeHostPopup();
        });
    });

    useSelectedBtn.addEventListener('click', () => {
        if (!selectedMedia) {
            return;
        }
        emitToHost(selectedMedia.id, selectedMedia.url);
        closeHostPopup();
    });

    dismissPopupBtn.addEventListener('click', closeHostPopup);
    closePopupBtn.addEventListener('click', closeHostPopup);

    uploadInput.addEventListener('change', function() {
        renderUploadPreview(this.files);
    });

    searchInput.addEventListener('input', filterGrid);
    typeFilter.addEventListener('change', filterGrid);
})();
</script>
@endpush
