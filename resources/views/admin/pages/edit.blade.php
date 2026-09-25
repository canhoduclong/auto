@extends('layouts.app')

@section('content')


    <div class="container">
        <h1>Edit Page</h1>
        <form action="{{ route('admin.pages.update', $page->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" class="form-control" value="{{ $page->title }}">
            </div>
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" name="slug" id="slug" class="form-control" value="{{ $page->slug }}">
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="editor" name="content" class="form-control" rows="10">
                    {{ old('content', $page->content ?? '') }}
                </textarea> 
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
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
 

@endsection
