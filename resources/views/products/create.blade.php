@extends('layouts.app')

@section('content')


 
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card shadow-lg rounded-3">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Thêm sản phẩm mới</h4>
                </div>
                <div class="card-body">

                    {{-- Hiển thị lỗi --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Form thêm sản phẩm --}}
                    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="perPage" value="{{ request('perPage', 10) }}">
                        @csrf

                        {{-- Tên sản phẩm --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <div class="form-group">
                                <label for="category_id">Danh mục:</label>
                                <select class="form-control @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                                    <option value="">-- Chọn danh mục --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @if(old('category_id') == $category->id) selected @endif>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>  
                        </div>      

                        <div class="mb-3">
                            <label for="unit" class="form-label">Đơn vị tính <span class="text-danger">*</span></label>
                            <select
                                name="unit"
                                id="unit"
                                class="form-select @error('unit') is-invalid @enderror"
                                required
                            >
                                @foreach($unitOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('unit', 'cai') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="product_type" class="form-label">Loại hình <span class="text-danger">*</span></label>
                            <select
                                name="product_type"
                                id="product_type"
                                class="form-select @error('product_type') is-invalid @enderror"
                                required
                            >
                                @foreach($typeOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('product_type', \App\Models\Product::TYPE_WHOLE) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('product_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Thứ tự hiển thị</label>
                            <input type="number"
                                   name="sort_order"
                                   id="sort_order"
                                   class="form-control @error('sort_order') is-invalid @enderror"
                                   min="0"
                                   step="1"
                                   value="{{ old('sort_order', 0) }}">
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>



                        {{-- Hình ảnh --}}
                        <div class="form-group">
                            <label>Ảnh đại diện</label>
                            <div>
                                <button type="button" class="btn btn-primary" id="btnSelectAvatar">
                                    Chọn ảnh
                                </button>
                                <input type="hidden" name="media_id" id="media_id"
                                    value="{{ old('media_id') }}">
                            </div>
                            <div id="avatarPreview" style="margin-top:10px;">
                                <span class="text-muted">Chưa chọn ảnh</span>
                            </div>
                        </div>
                        <div>
                            <label>Gallery</label>
                            <button type="button" onclick="openMediaPopup()">Chọn ảnh</button>
                            <div id="gallery-preview" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;"></div>

                            <input type="hidden" name="gallery_ids" id="gallery-ids">
                        </div>

                        {{-- Mô tả --}}
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea name="description" 
                                      id="description" 
                                      rows="3" 
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            <div class="row align-items-center mt-2 gx-2">
                                <div class="col-md-5 col-12 mb-2 mb-md-0">
                                    <input type="text" id="ai-desc-requirement" class="form-control" placeholder="Yêu cầu mô tả (ví dụ: nhấn mạnh chất lượng, phù hợp trẻ em...)">
                                </div>
                                <div class="col-md-2 col-6 mb-2 mb-md-0">
                                    <input type="number" id="ai-word-count" value="80" min="20" max="500" class="form-control" title="Số từ" placeholder="Số từ">
                                </div>
                                <div class="col-md-3 col-6">
                                    <button type="button" class="btn btn-outline-info w-100" id="btn-ai-description">Tạo mô tả AI</button>
                                </div>
                                <div class="col-md-2 col-12">
                                    <div id="ai-desc-loading" style="display:none"><small>Đang tạo mô tả...</small></div>
                                </div>
                            </div>
                            <div class="mb-2 small text-muted mt-2">
                                <strong>Gợi ý:</strong>
                                <ul style="margin-bottom:0;padding-left:18px">
                                    <li>Nhấn mạnh chất lượng sản phẩm, độ bền cao</li>
                                    <li>Phù hợp cho trẻ em, an toàn khi sử dụng</li>
                                    <li>Thích hợp làm quà tặng, đóng gói đẹp</li>
                                    <li>Nêu bật tính năng nổi bật, công nghệ mới</li>
                                    <li>Mô tả cảm giác khi sử dụng sản phẩm</li>
                                    <li>Hướng đến khách hàng nữ, phong cách thời trang</li>
                                    <li>Đề cao giá trị sức khỏe, thân thiện môi trường</li>
                                    <li>So sánh với sản phẩm cùng loại trên thị trường</li>
                                </ul>
                            </div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nút submit --}}
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Hủy</a>
                            <button type="submit" class="btn btn-success">Lưu sản phẩm</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div> 

<div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn ảnh đại diện</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe src="{{ route('media.library.popup') }}" frameborder="0" style="width:100%;height:500px;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function openMediaPopup() {
    window.open("{{ route('media.library.popup') }}", "Chọn ảnh", "width=1000,height=600");
}

document.addEventListener('DOMContentLoaded', function () {
    var btnSelectAvatar = document.getElementById('btnSelectAvatar');
    if (btnSelectAvatar) {
        btnSelectAvatar.addEventListener('click', function () {
            var modal = new bootstrap.Modal(document.getElementById('mediaModal'));
            modal.show();
        });
    }
});

function selectMedia(id, url) {
    document.getElementById('media_id').value = id;
    document.getElementById('avatarPreview').innerHTML =
        `<img src="${url}" width="120" class="img-thumbnail">`;

    var modalEl = document.getElementById('mediaModal');
    var modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
        modal.hide();
    }
}

function setSelectedImages(ids) {
    document.getElementById('gallery-ids').value = ids.join(',');
    let preview = document.getElementById('gallery-preview');
    preview.innerHTML = '';
    ids.forEach(id => {
        let img = document.querySelector(`[data-id="${id}"] img`).cloneNode();
        img.width = 80;
        preview.appendChild(img);
    });
}

$('#btn-ai-description').on('click', function() {
    let name = $('#name').val();
    let category = $('#category_id option:selected').text();
    let wordCount = $('#ai-word-count').val() || 80;
    let requirement = $('#ai-desc-requirement').val() || '';
    if (!name) { alert('Vui lòng nhập tên sản phẩm trước!'); return; }
    $('#ai-desc-loading').show();
    $.ajax({
        url: '/ai/generate-description',
        method: 'POST',
        data: {
            name: name,
            category: category,
            word_count: wordCount,
            requirement: requirement,
            _token: '{{ csrf_token() }}'
        },
        success: function(res) {
            $('#description').val(res.description);
        },
        error: function() {
            alert('Không thể tạo mô tả tự động!');
        },
        complete: function() {
            $('#ai-desc-loading').hide();
        }
    });
});
</script>
@endsection
