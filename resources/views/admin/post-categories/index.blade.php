@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Post Categories</h1>
        <a href="{{ route('admin.post-categories.create') }}" class="btn btn-primary">Create Category</a>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($postCategories as $category)
                    <tr>
                        <td>{{ $category->id }}</td>
                        <td>{{ $category->name }}</td>
                        <td>
                            <a href="{{ route('admin.post-categories.edit', $category->id) }}" class="btn btn-secondary">Edit</a>
                            <form action="{{ route('admin.post-categories.destroy', $category->id) }}" method="POST" style="display: inline-block;">
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
