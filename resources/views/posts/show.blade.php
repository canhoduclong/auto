@extends('layouts.site')

@push('styles')
<style>
.post-detail-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.post-hero-image {
    width: 100%;
    max-height: 420px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 16px;
}

.post-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    color: #4b5563;
    font-size: 14px;
    margin-bottom: 12px;
}

.post-content {
    line-height: 1.8;
    color: #1f2937;
}

.post-content p {
    margin-bottom: 12px;
}

.social-sharing .btn {
    margin-bottom: 8px;
}
</style>
@endpush

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
                <article class="post-detail">
                    @if($post->image)
                        <img src="{{ asset('storage/' . $post->image) }}" alt="{{ $post->title }}" class="post-hero-image">
                    @endif

                    <h1 class="mb-3">{{ $post->title }}</h1>

                    <div class="post-meta">
                        <span><i class="fa fa-folder-open-o"></i> {{ $post->category->name ?? 'Chưa phân loại' }}</span>
                        <span><i class="fa fa-calendar"></i> {{ optional($post->created_at)->format('d/m/Y H:i') }}</span>
                        <span><i class="fa fa-user"></i> {{ $post->author->name ?? 'Admin' }}</span>
                    </div>

                    <div class="post-content">
                        {!! nl2br(e($post->content)) !!}
                    </div>
                </article>

                <hr>
                <div class="social-sharing"> 
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('posts.show', $post)) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> Facebook</a>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('posts.show', $post)) }}&text={{ urlencode($post->title) }}" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-twitter"></i> Twitter</a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('posts.show', $post)) }}&title={{ urlencode($post->title) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-linkedin"></i> LinkedIn</a>
                    <a href="https://zalo.me/share?url={{ urlencode(route('posts.show', $post)) }}" target="_blank" class="btn btn-info btn-sm">Zalo</a>
                </div>
                <hr>
                <div class="related-posts mt-4">
                    <div class="title mb-4">
                        <span>Bản tin hàng ngày</span>
                        <h3 class="mb-0">Tin tức khác</h3>
                    </div>
                    <div class="row">
                        @foreach($otherPosts as $otherPost)
                            <div class="col-lg-6 col-md-6 mb-4">
                                <div class="latest__blog__item h-100">
                                    @if($otherPost->image)
                                        <div class="latest__blog__item__pic set-bg" data-setbg="{{ asset('storage/' . $otherPost->image) }}" style="background-image: url('{{ asset('storage/' . $otherPost->image) }}');"></div>
                                    @else
                                        <div class="latest__blog__item__pic set-bg" data-setbg="{{ asset('img/latest-blog/lb-1.jpg') }}" style="background-image: url('{{ asset('img/latest-blog/lb-1.jpg') }}');"></div>
                                    @endif
                                    <div class="latest__blog__item__text">
                                        <h5>{{ $otherPost->title }}</h5>
                                        <p class="mb-2"><small class="text-muted"><i class="fa fa-calendar"></i> {{ optional($otherPost->created_at)->format('d/m/Y') }} | <i class="fa fa-folder-open-o"></i> {{ $otherPost->category->name ?? 'Tin tức' }} | <i class="fa fa-user"></i> {{ $otherPost->author->name ?? 'Admin' }}</small></p>
                                        <p>{{ \Illuminate\Support\Str::limit(strip_tags($otherPost->excerpt ?: $otherPost->content), 120) }}</p>
                                        <a href="{{ route('posts.show', $otherPost) }}">Xem thêm <i class="fa fa-long-arrow-right"></i></a>
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
