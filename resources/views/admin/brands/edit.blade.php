@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Edit Brand</h1>
<form action="{{ route('admin.brands.update', $brand) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $brand->name }}">
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" name="slug" id="slug" class="form-control" value="{{ $brand->slug }}">
        </div>
        <div class="form-group">
            <label for="image">Image</label>
            <input type="file" name="image" id="image" class="form-control">
            @if($brand->image)
                <img src="{{ asset($brand->image) }}" alt="{{ $brand->name }}" width="100">
            @endif
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control">{{ $brand->description }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
