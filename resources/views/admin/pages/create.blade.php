@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Create Pagess</h1>
        <form action="{{ route('admin.pages.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" class="form-control">
            </div>
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" name="slug" id="slug" class="form-control">
            </div>
            <div class="form-group">
                <label for="content">Content</label> 
                <textarea name="content" id="editor">{{ old('content', $post->content ?? '') }}</textarea>

                <script>
                    CKEDITOR.replace('editor', {
                        height: 400,
                        filebrowserBrowseUrl: '/media/browse',  
                        filebrowserImageBrowseUrl: '/media/browse?type=image',
                        filebrowserUploadUrl: '/media/upload?_token={{ csrf_token() }}',
                        filebrowserImageUploadUrl: '/media/upload?type=image&_token={{ csrf_token() }}'
                    });
                </script>

            </div>
            <button type="submit" class="btn btn-primary">Create</button>
        </form>
    </div>
@endsection
