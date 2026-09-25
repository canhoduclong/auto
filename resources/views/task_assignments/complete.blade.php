@extends($layout ?? 'layouts.app')

@section('title', 'Hoàn thành công việc: ' . $task->code)

@push('styles')
<style>
    .completion-form {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 30px;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-group-title {
        font-weight: 600;
        color: #333;
        margin-top: 25px;
        margin-bottom: 15px;
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
    }
    
    .image-upload-area {
        border: 2px dashed #007bff;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        background: #f0f4ff;
        transition: all 0.3s ease;
    }
    
    .image-upload-area:hover {
        border-color: #0056b3;
        background: #e7f0ff;
    }
    
    .image-upload-area.drag-over {
        border-color: #0056b3;
        background: #cfe2ff;
    }
    
    .image-preview-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .image-preview {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .image-preview img {
        width: 100%;
        height: 100px;
        object-fit: cover;
    }
    
    .image-remove {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        line-height: 1;
    }
    
    .image-remove:hover {
        background: #c82333;
    }
    
    .completion-info {
        background: white;
        border-left: 4px solid #28a745;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .completion-info strong {
        color: #28a745;
    }
</style>
@endpush

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <h3 class="mb-4">
                <i class="bi bi-list-task"></i>
                Hoàn thành công việc: <strong>{{ $task->code }}</strong>
            </h3>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Tiêu đề:</strong> {{ $task->title }}</p>
                            <p><strong>Mức độ ưu tiên:</strong> <span class="badge bg-{{ $task->getStatusColor() }}">{{ $task->getPriorityLabel() }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Ngày hết hạn:</strong> {{ $task->due_date?->format('d/m/Y H:i') ?? 'Không có' }}</p>
                            <p><strong>Người giao:</strong> {{ $task->creator->name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="completion-form">
                <form id="completionForm" action="{{ route($submitRoute ?? 'task-assignments.complete-with-content', $task) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="completion-info">
                        <strong><i class="fas fa-info-circle"></i> Lưu ý:</strong>
                        Vui lòng cung cấp thông tin chi tiết về công việc đã thực hiện, bao gồm nội dung hoàn thành và các hình ảnh minh chứng.
                    </div>

                    {{-- Nội dung hoàn thành --}}
                    <div class="form-group-title">
                        <i class="fas fa-pencil-alt"></i> Nội dung hoàn thành <span class="text-danger">*</span>
                    </div>
                    <div class="mb-3">
                        <label for="completion_content" class="form-label">
                            Mô tả chi tiết công việc đã thực hiện
                        </label>
                        <textarea 
                            class="form-control @error('completion_content') is-invalid @enderror"
                            id="completion_content"
                            name="completion_content"
                            rows="6"
                            placeholder="Nhập nội dung công việc đã hoàn thành tối thiểu 10 ký tự"
                            minlength="10"
                            maxlength="5000"
                            required
                        >{{ old('completion_content') }}</textarea>
                        <small class="form-text text-muted">Tối thiểu 10 ký tự, tối đa 5000 ký tự</small>
                        @error('completion_content')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Ghi chú bổ sung --}}
                    <div class="form-group-title">
                        <i class="fas fa-sticky-note"></i> Ghi chú hoàn thành <span class="text-danger">*</span>
                    </div>
                    <div class="mb-3">
                        <label for="completion_notes" class="form-label">
                            Thêm ghi chú hoặc ý kiến bổ sung
                        </label>
                        <textarea 
                            class="form-control @error('completion_notes') is-invalid @enderror"
                            id="completion_notes"
                            name="completion_notes"
                            rows="4"
                            placeholder="Ví dụ: Công việc hoàn thành sớm hơn dự kiến, khách hàng rất hài lòng..."
                            minlength="5"
                            maxlength="2000"
                            required
                        >{{ old('completion_notes') }}</textarea>
                        <small class="form-text text-muted">Bắt buộc, tối thiểu 5 ký tự, tối đa 2000 ký tự</small>
                        @error('completion_notes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Upload hình ảnh --}}
                    <div class="form-group-title">
                        <i class="fas fa-image"></i> Tải lên hình ảnh minh chứng <span class="text-danger">*</span>
                    </div>
                    <div class="mb-3">
                        <div 
                            class="image-upload-area"
                            id="imageUploadArea"
                            ondrop="handleDrop(event)"
                            ondragover="handleDragOver(event)"
                            ondragleave="handleDragLeave(event)"
                        >
                            <div style="font-size: 2rem; margin-bottom: 10px;">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <p class="mb-0"><strong>Kéo thả hình ảnh vào đây</strong></p>
                            <p class="text-muted small mb-2">hoặc</p>
                            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('images').click()">
                                Chọn hình ảnh từ máy
                            </button>
                            <input 
                                type="file" 
                                id="images" 
                                name="images[]" 
                                multiple 
                                accept="image/*"
                                required
                                style="display: none;"
                                onchange="handleFileSelect(event)"
                            >
                            <p class="text-muted small mt-2">
                                Hỗ trợ: JPG, PNG, GIF, WebP (Tối đa 5MB/ảnh)
                            </p>
                        </div>

                        <div id="imagePreviewContainer" class="image-preview-container" style="display: none;"></div>

                        @error('images')
                            <div class="alert alert-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="d-flex gap-2 mt-5">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check-circle"></i> Gửi hoàn thành
                        </button>
                        <a href="{{ route($showRoute ?? 'task-assignments.show', $task) }}" class="btn btn-secondary btn-lg">
                            Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedFiles = [];

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('imageUploadArea').classList.add('drag-over');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('imageUploadArea').classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('imageUploadArea').classList.remove('drag-over');
    
    const files = e.dataTransfer.files;
    addFiles(files);
}

function handleFileSelect(e) {
    const files = e.target.files;
    addFiles(files);
}

function addFiles(files) {
    selectedFiles = Array.from(files).filter(f => f.type.startsWith('image/'));
    updateImagePreview();
}

function updateImagePreview() {
    const container = document.getElementById('imagePreviewContainer');
    
    if (selectedFiles.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'grid';
    container.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'image-preview';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview ${index + 1}">
                <button type="button" class="image-remove" onclick="removeImage(${index})">×</button>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

function removeImage(index) {
    selectedFiles.splice(index, 1);
    updateImagePreview();
}

// Update file input when preview is changed
document.getElementById('completionForm').addEventListener('submit', function(e) {
    if (selectedFiles.length === 0) {
        e.preventDefault();
        alert('Vui long tai len it nhat 1 hinh anh minh chung.');
        return;
    }

    // Create FormData with files
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => dataTransfer.items.add(file));
    document.getElementById('images').files = dataTransfer.files;
});
</script>
@endpush

@endsection
