@extends('layouts.site')

@section('breadcrumb')
<div class="breadcrumb-option set-bg mb-4 pb-4" data-setbg="{{ asset('img/breadcrumb-bg.jpg') }}" style="background-image: url(&quot;{{ asset('img/breadcrumb-bg.jpg') }}&quot;);">
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
                <hr>
                <div class="related-posts mt-4">
                    <h3>Tin tức khác</h3>
                    <div class="row">
                        @foreach($otherPosts as $otherPost)
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    @if($otherPost->image)
                                        <a href="{{ route('posts.show', $otherPost) }}">
                                            <img src="{{ asset('storage/' . $otherPost->image) }}" class="card-img-top" alt="{{ $otherPost->title }}" style="height: 150px; object-fit: cover;">
                                        </a>
                                    @else
                                         <a href="{{ route('posts.show', $otherPost) }}">
                                            <img src="https://via.placeholder.com/300x200" class="card-img-top" alt="{{ $otherPost->title }}" style="height: 150px; object-fit: cover;">
                                        </a>
                                    @endif
                                    <div class="card-body">
                                        <h5 class="card-title" style="min-height: 50px;"><a href="{{ route('posts.show', $otherPost) }}">{{ Str::limit($otherPost->title, 50) }}</a></h5>
                                        <p class="card-text"><small class="text-muted">{{ $otherPost->created_at->format('d/m/Y') }}</small></p>
                                        <a href="{{ route('posts.show', $otherPost) }}" class="btn btn-sm btn-outline-primary">Xem thêm</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $otherPosts->links() }}
                    </div>
                </div>
            </div>
            <div class="col-md-4"> 
                <div class="card mt-3">
                    <div class="card-header">Chuyên mục</div>
                    <div class="card-body">
                        <ul class="list-group">
                            @foreach($categories as $category)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ route('posts.category', $category) }}">{{ $category->name }}</a>
                                    <span class="badge bg-primary rounded-pill">{{ $category->posts_count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header">Thẻ</div>
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
