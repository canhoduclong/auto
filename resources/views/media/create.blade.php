@extends('layouts.app')

@section('content')
<div class="container-fluid media-create-page">
    <div class="card border-0 create-hero mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="create-kicker">Media Workspace</div>
                    <h2 class="mb-1">Tải Lên Media Mới</h2>
                    <p class="mb-0 text-muted">Kéo thả hoặc chọn nhiều ảnh cùng lúc. Xem trước trước khi upload để hạn chế sai sót.</p>
                </div>
                <a href="{{ route('media.index') }}" class="btn btn-outline-dark">
                    <i class="ph ph-arrow-left me-1"></i>
                    Về thư viện
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Upload</h5>
                        <span class="badge text-bg-light" id="selected-count">0 file</span>
                    </div>

                    <form id="media-upload-form" action="{{ route('media.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <label for="media-upload-input" class="upload-dropzone" id="upload-dropzone">
                            <div class="upload-icon"><i class="ph ph-cloud-arrow-up"></i></div>
                            <div class="upload-title">Kéo và thả ảnh vào đây</div>
                            <div class="upload-subtitle">hoặc bấm để chọn file từ máy tính</div>
                            <input type="file" name="file[]" id="media-upload-input" required multiple accept="image/*" class="d-none">
                        </label>

                        <div class="progress mt-3 d-none" id="upload-progress-wrap" role="progressbar" aria-label="Upload progress" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" id="upload-progress-bar" style="width: 0%">0%</div>
                        </div>

                        <div id="upload-feedback" class="small mt-2 text-muted">Chưa có tệp nào được chọn.</div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary" id="upload-btn">
                                <i class="ph ph-upload-simple me-1"></i>
                                Upload
                            </button>
                            <button type="button" class="btn btn-light" id="clear-selected-btn">Xóa lựa chọn</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Xem Trước</h5>
                        <small class="text-muted">Tối đa hiển thị 12 ảnh</small>
                    </div>
                    <div id="selected-preview-grid" class="selected-preview-grid">
                        <div class="empty-preview" id="empty-preview">
                            <i class="ph ph-image"></i>
                            <span>Chọn ảnh để xem trước trước khi tải lên</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Ảnh Gần Đây</h5>
                <small class="text-muted">Danh sách được làm mới tự động sau khi upload thành công</small>
            </div>
            <div id="media-list-container">
                @include('media._list')
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.media-create-page {
    padding-bottom: 2rem;
}

.create-hero {
    background: linear-gradient(135deg, #f4f7fb 0%, #e8eef8 100%);
}

.create-kicker {
    text-transform: uppercase;
    letter-spacing: .12em;
    font-size: .72rem;
    color: #334155;
}

.upload-dropzone {
    border: 2px dashed #94a3b8;
    border-radius: 14px;
    padding: 2.2rem 1rem;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: border-color .2s ease, background-color .2s ease, transform .2s ease;
    display: block;
}

.upload-dropzone:hover,
.upload-dropzone.dragover {
    border-color: #2563eb;
    background: #eef4ff;
    transform: translateY(-2px);
}

.upload-icon {
    font-size: 2rem;
    color: #2563eb;
    margin-bottom: .6rem;
}

.upload-title {
    font-weight: 600;
    color: #0f172a;
}

.upload-subtitle {
    color: #64748b;
    font-size: .86rem;
}

.selected-preview-grid {
    min-height: 240px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: .8rem;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .6rem;
    background: #f8fafc;
}

.preview-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #fff;
    aspect-ratio: 1 / 1;
}

.preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-name {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(15, 23, 42, .74), rgba(15, 23, 42, 0));
    color: #fff;
    font-size: .7rem;
    padding: .7rem .45rem .35rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.empty-preview {
    grid-column: 1 / -1;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #64748b;
    gap: .5rem;
}

.empty-preview i {
    font-size: 1.8rem;
}

@media (max-width: 992px) {
    .selected-preview-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .selected-preview-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
@endpush

@push('scripts')
<script>
function formatSelectedCount(count) {
    return count + (count === 1 ? ' file' : ' files');
}

function updateSelectedPreview(files) {
    const previewGrid = document.getElementById('selected-preview-grid');
    const countBadge = document.getElementById('selected-count');
    const feedback = document.getElementById('upload-feedback');

    previewGrid.innerHTML = '';

    if (!files || files.length === 0) {
        countBadge.textContent = '0 file';
        feedback.textContent = 'Chưa có tệp nào được chọn.';
        previewGrid.innerHTML = '<div class="empty-preview" id="empty-preview"><i class="ph ph-image"></i><span>Chọn ảnh để xem trước trước khi tải lên</span></div>';
        return;
    }

    countBadge.textContent = formatSelectedCount(files.length);
    feedback.textContent = 'Sẵn sàng upload ' + formatSelectedCount(files.length) + '.';

    Array.from(files).slice(0, 12).forEach((file) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const item = document.createElement('div');
            item.className = 'preview-item';
            item.innerHTML = '<img src="' + e.target.result + '" alt="preview"><div class="preview-name">' + file.name + '</div>';
            previewGrid.appendChild(item);
        };
        reader.readAsDataURL(file);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('media-upload-input');
    const dropzone = document.getElementById('upload-dropzone');
    const clearBtn = document.getElementById('clear-selected-btn');
    const uploadBtn = document.getElementById('upload-btn');
    const progressWrap = document.getElementById('upload-progress-wrap');
    const progressBar = document.getElementById('upload-progress-bar');

    input.addEventListener('change', function() {
        updateSelectedPreview(this.files);
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', function(e) {
        if (!e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) {
            return;
        }
        input.files = e.dataTransfer.files;
        updateSelectedPreview(input.files);
    });

    clearBtn.addEventListener('click', function() {
        input.value = '';
        updateSelectedPreview([]);
    });

    updateSelectedPreview([]);

    $('#media-upload-form').on('submit', function(e) {
        e.preventDefault();
        if (!input.files || input.files.length === 0) {
            alert('Vui lòng chọn ít nhất 1 file để upload.');
            return;
        }

        var formData = new FormData(this);

        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang upload...';
        progressWrap.classList.remove('d-none');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (!evt.lengthComputable) {
                            return;
                        }
                        const percent = Math.round((evt.loaded / evt.total) * 100);
                        progressBar.style.width = percent + '%';
                        progressBar.textContent = percent + '%';
                    });
                }
                return xhr;
            },
            success: function() {
                $('#media-list-container').load(location.href + ' #media-list-container > *');
                $('#media-upload-input').val('');
                updateSelectedPreview([]);
                progressBar.style.width = '100%';
                progressBar.textContent = '100%';
                document.getElementById('upload-feedback').textContent = 'Upload thành công.';
            },
            error: function() {
                alert('Upload thất bại!');
                document.getElementById('upload-feedback').textContent = 'Upload thất bại, vui lòng thử lại.';
            },
            complete: function() {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="ph ph-upload-simple me-1"></i>Upload';
                setTimeout(function() {
                    progressWrap.classList.add('d-none');
                }, 900);
            }
        });
    });
});
</script>
@endpush
@endsection
