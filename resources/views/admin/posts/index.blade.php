@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Posts</h1>
        <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">Create Post</a>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($posts as $post)
                    <tr>
                        <td>{{ $post->id }}</td>
                        <td>{{ $post->title }}</td>
                        <td>
                            <a href="{{ route('admin.posts.edit', $post->id) }}" class="btn btn-secondary">Edit</a>
                            <form action="{{ route('admin.posts.destroy', $post->id) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
