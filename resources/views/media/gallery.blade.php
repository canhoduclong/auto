@extends('layouts.popupGallery')

@section('content')
<style>
    body {
        background:
            radial-gradient(circle at top left, rgba(12, 131, 120, 0.12), transparent 28%),
            linear-gradient(180deg, #f5f8fc 0%, #eef3f9 100%);
    }

    .page-content .sidebar {
        display: none;
    }

    .page-content .content-wrapper {
        margin-left: 0 !important;
        width: 100%;
    }

    .popup-gallery {
        padding: 24px;
    }

    .gallery-shell {
        max-width: 1440px;
        margin: 0 auto;
    }

    .gallery-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding: 24px 28px;
        border-radius: 24px;
        background: linear-gradient(135deg, #0f2942 0%, #106c6a 100%);
        color: #f8fbff;
        box-shadow: 0 18px 50px rgba(15, 41, 66, 0.18);
        margin-bottom: 20px;
    }

    .gallery-hero h2 {
        margin: 0 0 8px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .gallery-hero p {
        margin: 0;
        max-width: 720px;
        color: rgba(248, 251, 255, 0.82);
    }

    .hero-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .gallery-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.9fr);
        gap: 20px;
        align-items: start;
    }

    .gallery-card {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 12px 36px rgba(15, 23, 42, 0.07);
        overflow: hidden;
        backdrop-filter: blur(8px);
    }

    .gallery-card-body {
        padding: 20px;
    }

    .gallery-toolbar {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(170px, 0.45fr) minmax(250px, 0.8fr);
        gap: 12px;
        margin-bottom: 18px;
    }

    .toolbar-field label,
    .panel-label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .toolbar-field input,
    .toolbar-field select,
    .upload-field input {
        width: 100%;
        border-radius: 14px;
        border: 1px solid #d7e3f1;
        background: #f8fbff;
        min-height: 48px;
        padding: 12px 14px;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .toolbar-field input:focus,
    .toolbar-field select:focus,
    .upload-field input:focus {
        outline: none;
        border-color: #57a7a2;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(16, 108, 106, 0.1);
    }

    .upload-inline {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
    }

    .upload-inline .btn,
    .selection-actions .btn,
    .empty-selection .btn {
        border-radius: 14px;
        padding: 11px 16px;
        font-weight: 600;
    }

    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
        gap: 14px;
    }

    .media-item {
        position: relative;
        border: 1px solid #dbe6f3;
        border-radius: 18px;
        background: #fff;
        padding: 10px;
        cursor: pointer;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }

    .media-item:hover {
        transform: translateY(-3px);
        border-color: #8ec6c2;
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.1);
    }

    .media-item.selected {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.14), 0 18px 30px rgba(15, 23, 42, 0.12);
    }

    .media-item-thumb {
        width: 100%;
        aspect-ratio: 1 / 1;
        border-radius: 14px;
        object-fit: cover;
        display: block;
        background: #edf3f9;
        margin-bottom: 10px;
    }

    .media-item-name {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .media-item-meta {
        display: block;
        margin-top: 4px;
        font-size: 11px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .media-check {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.96);
        color: #94a3b8;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        transition: all .18s ease;
        font-size: 14px;
    }

    .media-item.selected .media-check {
        background: #0f766e;
        color: #fff;
    }

    .empty-state {
        padding: 44px 24px;
        text-align: center;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        background: linear-gradient(180deg, #f9fbfd 0%, #f1f6fb 100%);
        color: #64748b;
    }

    .selection-panel {
        position: sticky;
        top: 20px;
    }

    .selection-summary {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 18px;
    }

    .summary-box {
        padding: 14px 16px;
        border-radius: 16px;
        background: linear-gradient(180deg, #f8fbff 0%, #eef5fb 100%);
        border: 1px solid #dde7f3;
    }

    .summary-box strong {
        display: block;
        font-size: 24px;
        line-height: 1;
        color: #0f172a;
        margin-bottom: 6px;
    }

    .summary-box span {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .selected-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .selected-card {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
        border: 1px solid #d7e3f1;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .selected-card img {
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        display: block;
    }

    .selected-card-footer {
        padding: 8px 10px 10px;
    }

    .selected-card-name {
        font-size: 12px;
        font-weight: 600;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .selected-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 30px;
        height: 30px;
        border: 0;
        border-radius: 50%;
        background: rgba(15, 23, 42, 0.68);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .empty-selection {
        padding: 22px;
        border-radius: 18px;
        border: 1px dashed #cbd5e1;
        background: #f8fbff;
        text-align: center;
        color: #64748b;
    }

    .selection-note {
        margin-top: 16px;
        padding: 14px 16px;
        border-radius: 16px;
        background: linear-gradient(180deg, #0f2942 0%, #13385b 100%);
        color: rgba(248, 250, 252, 0.82);
        font-size: 13px;
    }

    .selection-note strong {
        display: block;
        margin-bottom: 6px;
        color: #fff;
        font-size: 13px;
    }

    .selection-actions {
        display: flex;
        gap: 10px;
        margin-top: 18px;
    }

    .selection-actions .btn-primary {
        flex: 1;
    }

    .pagination-wrap {
        margin-top: 18px;
    }

    @media (max-width: 1199px) {
        .gallery-layout {
            grid-template-columns: 1fr;
        }

        .selection-panel {
            position: static;
        }
    }

    @media (max-width: 767px) {
        .popup-gallery {
            padding: 14px;
        }

        .gallery-hero {
            padding: 20px;
            border-radius: 20px;
        }

        .gallery-toolbar,
        .upload-inline,
        .selection-summary,
        .selected-grid,
        .selection-actions {
            grid-template-columns: 1fr;
            display: grid;
        }

        .selection-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="popup-gallery">
    <div class="gallery-shell">
        <div class="gallery-hero">
            <div>
                <span class="hero-chip">Gallery Product</span>
                <h2>Thư viện chọn ảnh gallery</h2>
                <p>Giữ layout 2 cột để thao tác nhanh hơn: bên trái là toàn bộ thư viện và bộ lọc, bên phải là danh sách ảnh đã chọn để kiểm tra trước khi cập nhật cho sản phẩm.</p>
            </div>
            <div class="hero-chip" id="heroSelectionCount">0 ảnh đã chọn</div>
        </div>

        <div class="gallery-layout">
            <div class="gallery-card">
                <div class="gallery-card-body">
                    <div class="gallery-toolbar">
                        <div class="toolbar-field">
                            <label for="gallerySearch">Tìm kiếm</label>
                            <input type="text" id="gallerySearch" placeholder="Nhập tên file hoặc mã ảnh...">
                        </div>
                        <div class="toolbar-field">
                            <label for="galleryFilter">Trạng thái</label>
                            <select id="galleryFilter">
                                <option value="all">Tất cả</option>
                                <option value="selected">Đã chọn</option>
                                <option value="unselected">Chưa chọn</option>
                            </select>
                        </div>
                        <div class="toolbar-field upload-field">
                            <label for="galleryUploadInput">Upload nhanh</label>
                            <form action="{{ route('media.gallery.store') }}" method="POST" enctype="multipart/form-data" class="upload-inline">
                                @csrf
                                <input type="file" id="galleryUploadInput" name="file" required accept="image/*">
                                <button type="submit" class="btn btn-primary">Upload</button>
                            </form>
                        </div>
                    </div>

                    <div class="media-grid" id="mediaGrid">
                        @foreach($media as $item)
                            @php
                                $path = asset('storage/'.$item->file_path);
                                $extension = strtoupper(pathinfo($item->file_name, PATHINFO_EXTENSION));
                            @endphp
                            <div class="media-item" data-id="{{ $item->id }}" data-path="{{ $path }}" data-name="{{ strtolower($item->file_name) }}">
                                <span class="media-check">✓</span>
                                <img src="{{ $path }}" alt="{{ $item->file_name }}" class="media-item-thumb">
                                <span class="media-item-name">{{ $item->file_name }}</span>
                                <span class="media-item-meta">{{ $extension ?: 'IMAGE' }} • #{{ $item->id }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="empty-state d-none" id="emptyState">
                        Không có ảnh phù hợp với điều kiện lọc hiện tại.
                    </div>

                    <div class="pagination-wrap">
                        {{ $media->links() }}
                    </div>
                </div>
            </div>

            <div class="selection-panel">
                <div class="gallery-card">
                    <div class="gallery-card-body">
                        <div class="selection-summary">
                            <div class="summary-box">
                                <strong id="selectedCount">0</strong>
                                <span>Ảnh đã chọn</span>
                            </div>
                            <div class="summary-box">
                                <strong>{{ $media->count() }}</strong>
                                <span>Ảnh hiển thị</span>
                            </div>
                        </div>

                        <label class="panel-label">Danh sách đã chọn</label>
                        <div id="selectedPreviewWrap">
                            <div class="empty-selection" id="emptySelection">
                                <p class="mb-3">Chưa có ảnh nào được chọn. Nhấp vào ảnh ở cột trái để thêm vào gallery.</p>
                                <button type="button" class="btn btn-outline-secondary" id="clearSelectionBtn" disabled>Xóa chọn hiện tại</button>
                            </div>
                            <div class="selected-grid d-none" id="selectedGrid"></div>
                        </div>

                        <div class="selection-note">
                            <strong>Gợi ý thao tác</strong>
                            Bạn có thể chọn nhiều ảnh, kiểm tra lại ở cột phải, sau đó bấm cập nhật để gửi danh sách về form sửa sản phẩm.
                        </div>

                        <div class="selection-actions">
                            <button type="button" class="btn btn-outline-secondary" id="clearSelectionAction">Bỏ chọn tất cả</button>
                            <button type="button" class="btn btn-primary" id="applySelectionBtn">Cập nhật danh sách chọn</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selected = [];

const items = Array.from(document.querySelectorAll('.media-item'));
const selectedGrid = document.getElementById('selectedGrid');
const emptySelection = document.getElementById('emptySelection');
const selectedCount = document.getElementById('selectedCount');
const heroSelectionCount = document.getElementById('heroSelectionCount');
const gallerySearch = document.getElementById('gallerySearch');
const galleryFilter = document.getElementById('galleryFilter');
const emptyState = document.getElementById('emptyState');
const clearSelectionBtn = document.getElementById('clearSelectionBtn');
const clearSelectionAction = document.getElementById('clearSelectionAction');
const applySelectionBtn = document.getElementById('applySelectionBtn');

function getItemById(id) {
    return document.querySelector(`.media-item[data-id="${id}"]`);
}

function updateSummary() {
    const count = selected.length;
    selectedCount.textContent = count;
    heroSelectionCount.textContent = `${count} ảnh đã chọn`;
    clearSelectionBtn.disabled = count === 0;
}

function renderSelectedPreview() {
    selectedGrid.innerHTML = '';

    if (selected.length === 0) {
        emptySelection.classList.remove('d-none');
        selectedGrid.classList.add('d-none');
        updateSummary();
        return;
    }

    emptySelection.classList.add('d-none');
    selectedGrid.classList.remove('d-none');

    selected.forEach((id) => {
        const item = getItemById(id);
        if (!item) {
            return;
        }

        const card = document.createElement('div');
        card.className = 'selected-card';
        card.innerHTML = `
            <button type="button" class="selected-remove" data-id="${id}" aria-label="Bỏ chọn">×</button>
            <img src="${item.dataset.path}" alt="${item.dataset.name}">
            <div class="selected-card-footer">
                <div class="selected-card-name" title="${item.dataset.name}">${item.dataset.name}</div>
            </div>
        `;
        selectedGrid.appendChild(card);
    });

    selectedGrid.querySelectorAll('.selected-remove').forEach((button) => {
        button.addEventListener('click', () => {
            removeSelectedId(button.dataset.id);
            notifyParentRemoved(button.dataset.id);
        });
    });

    updateSummary();
}

function syncItemStates() {
    items.forEach((item) => {
        item.classList.toggle('selected', selected.includes(String(item.dataset.id)));
    });
}

function filterItems() {
    const keyword = (gallerySearch.value || '').trim().toLowerCase();
    const mode = galleryFilter.value || 'all';
    let visibleCount = 0;

    items.forEach((item) => {
        const id = String(item.dataset.id);
        const name = item.dataset.name || '';
        const isSelected = selected.includes(id);
        const matchKeyword = !keyword || name.includes(keyword) || id.includes(keyword);
        const matchFilter = mode === 'all'
            || (mode === 'selected' && isSelected)
            || (mode === 'unselected' && !isSelected);
        const visible = matchKeyword && matchFilter;

        item.classList.toggle('d-none', !visible);
        if (visible) {
            visibleCount += 1;
        }
    });

    emptyState.classList.toggle('d-none', visibleCount !== 0);
}

function toggleSelectedId(id) {
    const value = String(id);
    if (selected.includes(value)) {
        selected = selected.filter((itemId) => itemId !== value);
    } else {
        selected.push(value);
    }
    syncItemStates();
    renderSelectedPreview();
    filterItems();
}

function removeSelectedId(id) {
    const value = String(id);
    selected = selected.filter((itemId) => itemId !== value);
    syncItemStates();
    renderSelectedPreview();
    filterItems();
}

function clearSelection() {
    selected = [];
    syncItemStates();
    renderSelectedPreview();
    filterItems();
}

function notifyParentRemoved(id) {
    window.parent.postMessage({ type: 'removeSelected', id: id }, '*');
}

items.forEach((item) => {
    item.addEventListener('click', () => {
        toggleSelectedId(item.dataset.id);
    });
});

gallerySearch.addEventListener('input', filterItems);
galleryFilter.addEventListener('change', filterItems);
clearSelectionBtn.addEventListener('click', clearSelection);
clearSelectionAction.addEventListener('click', clearSelection);
applySelectionBtn.addEventListener('click', sendSelectedToParent);

window.addEventListener('message', function(event) {
    if (event.data.type === 'initSelected') {
        selected = event.data.data.map(String);
        syncItemStates();
        renderSelectedPreview();
        filterItems();
    }

    if (event.data.type === 'removeSelected') {
        removeSelectedId(String(event.data.id));
    }
});

function sendSelectedToParent() {
    const data = selected.map((id) => {
        const el = getItemById(id);
        return el ? { id: id, path: el.dataset.path } : null;
    }).filter(Boolean);

    window.parent.postMessage({ type: 'gallerySelected', data: data }, '*');
}

syncItemStates();
renderSelectedPreview();
filterItems();
</script>

@endsection
