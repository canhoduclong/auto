@extends('layouts.app')

@section('content')
<div class="container-fluid media-library-page">
    <div class="media-hero card border-0 mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="text-uppercase media-hero-kicker">Workspace</div>
                    <h2 class="mb-1">Media Library</h2>
                    <p class="mb-0 text-muted">Quản lý hình ảnh và tệp truyền thông tập trung, rõ ràng và nhanh hơn.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('media.create') }}" class="btn btn-primary">
                        <i class="ph ph-plus-circle me-1"></i>
                        Thêm media
                    </a>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <div class="metric-card">
                        <div class="metric-label">Tổng tệp</div>
                        <div class="metric-value">{{ number_format($media->total()) }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-card">
                        <div class="metric-label">Trang hiện tại</div>
                        <div class="metric-value">{{ $media->count() }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-card">
                        <div class="metric-label">Phân trang</div>
                        <div class="metric-value">{{ $media->currentPage() }}/{{ $media->lastPage() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-lg-8">
                    <div class="input-group input-group-search">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="ph ph-magnifying-glass"></i>
                        </span>
                        <input type="text" id="mediaSearch" class="form-control border-start-0" placeholder="Tìm theo tên file hoặc loại MIME...">
                    </div>
                </div>
                <div class="col-lg-4">
                    <select id="mediaTypeFilter" class="form-select">
                        <option value="all">Tất cả loại tệp</option>
                        <option value="image">Image</option>
                        <option value="video">Video</option>
                        <option value="application">Application</option>
                        <option value="audio">Audio</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3" id="mediaGrid">
        @forelse($media as $item)
            @php
                $url = asset('storage/' . $item->file_path);
                $mimeType = strtolower((string) $item->mime_type);
                $primaryType = explode('/', $mimeType)[0] ?? 'file';
                $isImage = \Illuminate\Support\Str::startsWith($mimeType, 'image/');
                $bytes = (int) ($item->file_size ?? 0);
                if ($bytes >= 1048576) {
                    $sizeLabel = number_format($bytes / 1048576, 2) . ' MB';
                } elseif ($bytes >= 1024) {
                    $sizeLabel = number_format($bytes / 1024, 1) . ' KB';
                } else {
                    $sizeLabel = $bytes . ' B';
                }
            @endphp
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 media-card-wrapper"
                data-name="{{ strtolower($item->file_name) }}"
                data-mime="{{ $mimeType }}"
                data-type="{{ $primaryType }}">
                <article class="card media-card h-100 border-0 shadow-sm">
                    <div class="media-thumb-wrap">
                        @if($isImage)
                            <img src="{{ $url }}" alt="{{ $item->file_name }}" class="media-thumb">
                        @else
                            <div class="media-thumb media-thumb-fallback">
                                <i class="ph ph-file"></i>
                            </div>
                        @endif
                    </div>
                    <div class="card-body p-3">
                        <h6 class="media-name mb-1" title="{{ $item->file_name }}">{{ $item->file_name }}</h6>
                        <div class="media-meta">{{ $mimeType ?: 'unknown' }}</div>
                        <div class="media-meta">{{ $sizeLabel }} • {{ optional($item->created_at)->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-light btn-sm flex-fill btn-preview-media"
                                data-id="{{ $item->id }}"
                                data-name="{{ $item->file_name }}"
                                data-size="{{ $sizeLabel }}"
                                data-mime="{{ $mimeType }}"
                                data-created="{{ optional($item->created_at)->format('d/m/Y H:i:s') }}"
                                data-url="{{ $url }}">
                                Xem
                            </button>
                            <a href="{{ route('media.edit', $item->id) }}" class="btn btn-warning btn-sm">Sửa</a>
                            <form action="{{ route('media.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa file này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                            </form>
                        </div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="ph ph-folder-open"></i>
                        <h5 class="mt-3 mb-1">Chưa có media nào</h5>
                        <p class="text-muted mb-3">Bắt đầu bằng cách tải lên tệp đầu tiên cho thư viện.</p>
                        <a href="{{ route('media.create') }}" class="btn btn-primary">Thêm media</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $media->links() }}
    </div>
</div>

<div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Chi tiết Media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="media-preview" src="" class="img-fluid rounded mb-3" alt="Media preview">
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="meta-box"><span>Tên file</span><strong id="media-file-name"></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="meta-box"><span>Kích thước</span><strong id="media-file-size"></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="meta-box"><span>Loại file</span><strong id="media-mime-type"></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="meta-box"><span>Ngày tạo</span><strong id="media-created-at"></strong></div>
                    </div>
                    <div class="col-12">
                        <div class="meta-box">
                            <span>Đường dẫn</span>
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <strong id="media-url" class="text-truncate"></strong>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="copyMediaUrl">Copy</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.media-library-page {
    padding-bottom: 2rem;
}

.media-hero {
    background: linear-gradient(135deg, #0b132b 0%, #1c2541 60%, #3a506b 100%);
    color: #f8fafc;
    overflow: hidden;
    position: relative;
}

.media-hero::after {
    content: '';
    position: absolute;
    width: 420px;
    height: 420px;
    right: -120px;
    top: -180px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.22) 0%, rgba(255,255,255,0) 70%);
}

.media-hero .card-body {
    position: relative;
    z-index: 1;
}

.media-hero-kicker {
    font-size: .72rem;
    letter-spacing: .14em;
    opacity: .75;
}

.metric-card {
    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .24);
    border-radius: .85rem;
    padding: .9rem 1rem;
}

.metric-label {
    font-size: .75rem;
    text-transform: uppercase;
    opacity: .82;
    margin-bottom: .15rem;
}

.metric-value {
    font-size: 1.15rem;
    font-weight: 700;
}

.input-group-search .input-group-text,
.input-group-search .form-control {
    border-color: #d7deea;
}

.media-card {
    transition: transform .2s ease, box-shadow .2s ease;
}

.media-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(15, 23, 42, .12) !important;
}

.media-thumb-wrap {
    background: #eef2ff;
    border-radius: .75rem .75rem 0 0;
    overflow: hidden;
}

.media-thumb {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    display: block;
}

.media-thumb-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4f46e5;
    font-size: 2rem;
}

.media-name {
    font-size: .9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.media-meta {
    color: #64748b;
    font-size: .75rem;
    line-height: 1.2rem;
}

.empty-state i {
    font-size: 2.2rem;
    color: #94a3b8;
}

.meta-box {
    border: 1px solid #e2e8f0;
    border-radius: .7rem;
    padding: .65rem .8rem;
    background: #f8fafc;
}

.meta-box span {
    display: block;
    color: #64748b;
    font-size: .75rem;
}

.meta-box strong {
    display: block;
    color: #0f172a;
    font-size: .9rem;
    margin-top: .2rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('mediaSearch');
    const typeFilter = document.getElementById('mediaTypeFilter');
    const cards = Array.from(document.querySelectorAll('.media-card-wrapper'));
    const previewButtons = document.querySelectorAll('.btn-preview-media');
    const modalElement = document.getElementById('mediaModal');
    const copyBtn = document.getElementById('copyMediaUrl');

    const applyFilter = () => {
        const keyword = (searchInput?.value || '').trim().toLowerCase();
        const selectedType = typeFilter?.value || 'all';

        cards.forEach((card) => {
            const name = card.dataset.name || '';
            const mime = card.dataset.mime || '';
            const type = card.dataset.type || '';
            const matchedKeyword = name.includes(keyword) || mime.includes(keyword);
            const matchedType = selectedType === 'all' || type === selectedType;

            card.style.display = matchedKeyword && matchedType ? '' : 'none';
        });
    };

    if (searchInput) {
        searchInput.addEventListener('input', applyFilter);
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', applyFilter);
    }

    previewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const url = button.dataset.url || '';
            document.getElementById('media-preview').src = url;
            document.getElementById('media-file-name').textContent = button.dataset.name || '-';
            document.getElementById('media-file-size').textContent = button.dataset.size || '-';
            document.getElementById('media-mime-type').textContent = button.dataset.mime || '-';
            document.getElementById('media-created-at').textContent = button.dataset.created || '-';
            document.getElementById('media-url').textContent = url;

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.show();
        });
    });

    if (copyBtn) {
        copyBtn.addEventListener('click', async () => {
            const url = document.getElementById('media-url').textContent || '';
            if (!url) {
                return;
            }

            try {
                await navigator.clipboard.writeText(url);
                copyBtn.textContent = 'Copied';
                setTimeout(() => {
                    copyBtn.textContent = 'Copy';
                }, 1400);
            } catch (error) {
                alert('Không thể copy đường dẫn.');
            }
        });
    }
});
</script>
@endpush
