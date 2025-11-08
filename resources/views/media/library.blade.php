@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Thư viện Media</h2>
    <div class="row">
        <div class="col-md-12 mb-3">  
            <a href="{{ route('media.create') }}" class="btn btn-primary mb-3">Thêm Media</a>
        </div>
    </div>

   <div class="row">
    @foreach($media as $item)
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm">
                <img src="{{ asset('storage/' . $item->file_path) }}"
                     class="card-img-top"
                     alt="{{ $item->file_name }}"
                     style="max-height: 180px; object-fit: cover;">

                <div class="card-body p-2">
                    <p class="mb-1 text-truncate">{{ $item->file_name }}</p>

                    <div class="d-flex justify-content-between">
                        {{-- Nút Edit --}}
                        <a href="{{ route('media.edit', $item->id) }}"
                           class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>

                        {{-- Nút Delete --}}
                        <form action="{{ route('media.destroy', $item->id) }}"
                              method="POST"
                              onsubmit="return confirm('Bạn có chắc muốn xóa file này?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>


    <!-- Pagination -->
    <div>
        {{ $media->links() }}
    </div>
</div>

<!-- Modal chi tiết (dùng chung với index.blade.php ở trên) -->
<div class="modal fade" id="mediaModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Chi tiết Media</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <img id="media-preview" src="" class="img-fluid mb-3">
            <ul class="list-group">
                <li class="list-group-item"><strong>Tên file:</strong> <span id="media-file-name"></span></li>
                <li class="list-group-item"><strong>Kích thước:</strong> <span id="media-file-size"></span></li>
                <li class="list-group-item"><strong>Loại file:</strong> <span id="media-mime-type"></span></li>
                <li class="list-group-item"><strong>Đường dẫn:</strong> <span id="media-url"></span></li>
                <li class="list-group-item"><strong>Ngày tạo:</strong> <span id="media-created-at"></span></li>
            </ul>
        </div>
        <div class="modal-footer">
            <!-- Chỉ có nút Xoá hoàn toàn (vì không thuộc 1 type cụ thể) -->
            <button id="deleteMedia" class="btn btn-danger">Xoá hoàn toàn</button>
        </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
let selectedMediaId = null;

// Click ảnh -> show modal
$(document).on('click', '.media-item', function() {
    selectedMediaId = $(this).data('id');
    $.get("{{ url('admin/media') }}/" + selectedMediaId, function(data) {
        $('#media-preview').attr('src', data.url);
        $('#media-file-name').text(data.file_name);
        $('#media-file-size').text((data.file_size/1024).toFixed(1) + ' KB');
        $('#media-mime-type').text(data.mime_type);
        $('#media-url').text(data.url);
        $('#media-created-at').text(data.created_at);

        $('#mediaModal').modal('show');
    });
});

// Xoá hoàn toàn
$('#deleteMedia').on('click', function() {
    if (!selectedMediaId) return;
    $.ajax({
        url: `/admin/media/${selectedMediaId}`,
        type: 'DELETE',
        data: {_token: '{{ csrf_token() }}'},
        success: function() { location.reload(); }
    });
});
</script>
@endpush
