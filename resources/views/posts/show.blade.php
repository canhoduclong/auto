@extends('layouts.site')

@section('breadcrumb')
<div class="breadcrumb-option set-bg mb-4 pb-4" data-setbg="{{ asset('img/breadcrumb-bg.jpg') }}" style="background-image: url(&quot;img/breadcrumb-bg.jpg&quot;);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb__text">
                        <h2>Tin tức</h2>
                        <div class="breadcrumb__links">
                            <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                            <a href="{{ route('posts.list') }}"><i class="fa fa-home"></i> Tin tức</a>
                            <span> {{ $post->title }}</span>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div> 
@endsection
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h1>{{ $post->title }}</h1>
                <p>{{ $post->content }}</p>

                <hr>
                <div class="social-sharing">
                    <h5>Share this post:</h5>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('posts.show', $post)) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> Facebook</a>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('posts.show', $post)) }}&text={{ urlencode($post->title) }}" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-twitter"></i> Twitter</a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('posts.show', $post)) }}&title={{ urlencode($post->title) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-linkedin"></i> LinkedIn</a>
                    <a href="https://zalo.me/share?url={{ urlencode(route('posts.show', $post)) }}" target="_blank" class="btn btn-info btn-sm">Zalo</a>
                </div>

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
