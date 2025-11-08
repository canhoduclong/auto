@extends('layouts.site')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h1>{{ $post->title }}</h1>
                <p>{{ $post->content }}</p>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Other Posts</div>
                    <div class="card-body">
                        <ul class="list-group">
                            @foreach($otherPosts as $otherPost)
                                <li class="list-group-item">
                                    <a href="{{ route('posts.show', $otherPost) }}">{{ $otherPost->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header">Categories</div>
                    <div class="card-body">
                        <ul class="list-group">
                            @foreach($categories as $category)
                                <li class="list-group-item">
                                    <a href="{{ route('posts.category', $category) }}">{{ $category->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header">Tags</div>
                    <div class="card-body">
                        @foreach($tags as $tag)
                            <a href="#" class="btn btn-sm btn-secondary mb-1">{{ $tag->name }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
