@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Brands</h1>
    <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">Create Brand</a>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($brands as $brand)
            <tr>
                <td>{{ $brand->id }}</td>
                <td>{{ $brand->name }}</td>
                <td>{{ $brand->slug }}</td>
                <td>
                    <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" style="display: inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
