@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Create Post Category</h1>
        <form action="{{ route('admin.post-categories.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Create</button>
        </form>
    </div>
@endsection
