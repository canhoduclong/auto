@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Edit Media</h4>

    <form action="{{ route('media.update', $media->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="file_name" class="form-label">Tên hiển thị</label>
            <input type="text" class="form-control" name="file_name" value="{{ old('file_name', $media->file_name) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">File hiện tại</label><br>
            <img src="{{ asset('storage/' . $media->file_path) }}" width="200" class="mb-2">
        </div>

        <div class="mb-3">
            <label for="file" class="form-label">Thay thế file (nếu cần)</label>
            <input type="file" class="form-control" name="file">
        </div>

        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('media.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
    
</div>
@endsection
