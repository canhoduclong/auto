@php($images = \App\Models\Media::latest()->take(100)->get())
<div class="media-grid row mt-4">
    @foreach($images as $image)
        <div class="col-2 mb-3">
            <div class="card">
                <img src="{{ asset('storage/' . $image->file_path) }}" class="card-img-top" style="object-fit:cover;height:120px">
                <div class="card-body p-2">
                    <small class="d-block text-truncate">{{ $image->file_name }}</small>
                    <div class="d-flex justify-content-between mt-2">
                        <a href="{{ route('media.edit', $image->id) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i> Edit</a>
                        <form action="{{ route('media.destroy', $image->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Bạn có chắc muốn xóa file này?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>