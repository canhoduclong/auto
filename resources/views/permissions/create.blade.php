@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Thêm Permission mới</h2>

    <form action="{{ route('permissions.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Tên Permission</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Mô tả</label>
            <input type="text" name="description" id="description" class="form-control" value="{{ old('description') }}">
        </div>
         <div class="mb-3">
            <label for="group" class="form-label">Nhóm</label>
            <input type="text" name="group" id="group" class="form-control" value="{{ old('group') }}">
        </div>
        <button type="submit" class="btn btn-primary">Lưu</button>
        <a href="{{ route('permissions.index') }}" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
@endsection
