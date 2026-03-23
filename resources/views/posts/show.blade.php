@extends('layouts.site')

@push('styles')
<style>
    /* ── Layout ─────────────────────────────────── */
    .post-page { padding: 48px 0 64px; }

    /* ── Hero ───────────────────────────────────── */
    .post-hero {
        position: relative;
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 32px;
        box-shadow: 0 16px 40px rgba(15,23,42,.12);
        background: #0f172a;
        min-height: 340px;
        display: flex;
        align-items: flex-end;
    }
    .post-hero__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: .55;
    }
    .post-hero__body {
        position: relative;
        z-index: 1;
        padding: 32px 36px;
        width: 100%;
        background: linear-gradient(to top, rgba(15,23,42,.88) 0%, transparent 100%);
    }
    .post-hero__cat {
        display: inline-block;
        background: #0ea5e9;
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: 3px 10px;
        border-radius: 20px;
        margin-bottom: 10px;
        text-decoration: none;
    }
    .post-hero__cat:hover { background: #0284c7; color: #fff; }
    .post-hero__title {
        font-size: 1.8rem;
        font-weight: 900;
        color: #fff;
        line-height: 1.2;
        margin-bottom: 12px;
    }
    .post-hero__meta {
        font-size: .78rem;
        color: rgba(255,255,255,.7);
        display: flex;
        gap: 18px;
        flex-wrap: wrap;
        align-items: center;
    }
    .post-hero__meta i { margin-right: 4px; }

    /* ── Article body ───────────────────────────── */
    .post-article {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 6px 24px rgba(15,23,42,.07);
        padding: 36px 40px;
        margin-bottom: 28px;
    }
    .post-article .post-content {
        font-size: .97rem;
        line-height: 1.85;
        color: #1e293b;
    }
    .post-article .post-content p { margin-bottom: 1.1em; }
    .post-article .post-content h2,
    .post-article .post-content h3 {
        font-weight: 800;
        margin-top: 1.6em;
        margin-bottom: .6em;
        color: #0f172a;
    }
    .post-article .post-content img {
        max-width: 100%;
        border-radius: 10px;
        margin: 12px 0;
    }
    .post-article .post-content blockquote {
        border-left: 4px solid #0ea5e9;
        padding: 12px 20px;
        background: #f0f9ff;
        border-radius: 0 10px 10px 0;
        color: #0369a1;
        font-style: italic;
        margin: 1em 0;
    }

    /* ── Tags row ───────────────────────────────── */
    .post-tags { margin-bottom: 28px; }
    .post-tags .tag-item {
        display: inline-block;
        background: #f1f5f9;
        color: #475569;
        font-size: .75rem;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        text-decoration: none;
        margin: 3px;
        transition: background .15s, color .15s;
    }
    .post-tags .tag-item:hover { background: #0ea5e9; color: #fff; }

    /* ── Social share ───────────────────────────── */
    .post-share {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        padding: 18px 0;
        border-top: 1px dashed #e2e8f0;
        border-bottom: 1px dashed #e2e8f0;
        margin-bottom: 36px;
    }
    .post-share__label {
        font-size: .8rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-right: 4px;
    }
    .share-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .78rem;
        font-weight: 700;
        padding: 6px 16px;
        border-radius: 8px;
        text-decoration: none;
        transition: opacity .15s, transform .15s;
        border: 0;
    }
    .share-btn:hover { opacity: .88; transform: translateY(-1px); }
    .share-btn-fb  { background: #1877f2; color: #fff; }
    .share-btn-x   { background: #0f172a; color: #fff; }
    .share-btn-zalo { background: #0068ff; color: #fff; }
    .share-btn-copy { background: #f1f5f9; color: #334155; }

    /* ── Related posts ──────────────────────────── */
    .related-section { margin-bottom: 16px; }
    .related-section__head {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 18px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .news-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 6px 20px rgba(15,23,42,.07);
        overflow: hidden;
        transition: box-shadow .25s, transform .2s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .news-card:hover { box-shadow: 0 14px 36px rgba(15,23,42,.12); transform: translateY(-3px); }
    .news-card__img-wrap {
        position: relative; height: 165px; overflow: hidden;
        background: #e2e8f0; flex-shrink: 0;
    }
    .news-card__img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
    .news-card:hover .news-card__img { transform: scale(1.05); }
    .news-card__img-placeholder {
        width:100%; height:100%; display:flex; align-items:center;
        justify-content:center; color:#94a3b8; font-size:2rem;
    }
    .news-card__cat {
        position:absolute; top:10px; left:10px;
        background:#0ea5e9; color:#fff; font-size:.66rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.05em;
        padding:2px 9px; border-radius:20px; text-decoration:none;
    }
    .news-card__body { padding:14px 16px 12px; flex:1; display:flex; flex-direction:column; }
    .news-card__title {
        font-size:.88rem; font-weight:700; color:#0f172a; line-height:1.35;
        margin-bottom:6px; display:-webkit-box; -webkit-line-clamp:2;
        -webkit-box-orient:vertical; overflow:hidden; text-decoration:none;
    }
    .news-card__title:hover { color:#0ea5e9; }
    .news-card__meta { font-size:.7rem; color:#94a3b8; margin-top:auto; padding-top:8px; }

    /* ── Sidebar ────────────────────────────────── */
    .news-sidebar-box {
        border:0; border-radius:14px;
        box-shadow:0 6px 20px rgba(15,23,42,.07);
        overflow:hidden; margin-bottom:24px;
    }
    .news-sidebar-box__head {
        background:#0f172a; color:#fff; font-weight:700;
        font-size:.82rem; text-transform:uppercase; letter-spacing:.06em;
        padding:12px 18px;
    }
    .news-sidebar-box__body { padding:14px 18px; background:#fff; }
    .news-cat-item {
        display:flex; justify-content:space-between; align-items:center;
        padding:7px 0; border-bottom:1px solid #f1f5f9; text-decoration:none;
        color:#334155; font-size:.85rem; transition:color .15s;
    }
    .news-cat-item:last-child { border-bottom:0; }
    .news-cat-item:hover { color:#0ea5e9; }
    .tag-cloud { display:flex; flex-wrap:wrap; gap:6px; }
    .tag-item {
        display:inline-block; background:#f1f5f9; color:#475569;
        font-size:.75rem; font-weight:600; padding:4px 12px;
        border-radius:20px; text-decoration:none; transition:background .15s, color .15s;
    }
    .tag-item:hover { background:#0ea5e9; color:#fff; }

    @media (max-width: 767px) {
        .post-article { padding: 22px 18px; }
        .post-hero__body { padding: 20px 20px; }
        .post-hero__title { font-size: 1.3rem; }
    }
</style>
@endpush

@section('breadcrumb')
<div class="breadcrumb-option set-bg mb-4 pb-4"
     data-setbg="{{ asset('img/breadcrumb-bg.jpg') }}"
     style="background-image: url('{{ asset('img/breadcrumb-bg.jpg') }}');">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb__text">
                    <h2>Tin tức</h2>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                        <a href="{{ route('posts.list') }}"> Tin tức</a>
                        <span> {{ Str::limit($post->title, 40) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="post-page">
    <div class="container">
        <div class="row g-4">

            {{-- ── Main column ──────────────────────────── --}}
            <div class="col-lg-8">

                {{-- Hero --}}
                <div class="post-hero">
                    @if($post->image)
                        <img src="{{ asset('storage/' . $post->image) }}"
                             alt="{{ $post->title }}"
                             class="post-hero__img">
                    @else
                        <img src="{{ asset('img/latest-blog/lb-1.jpg') }}"
                             alt="{{ $post->title }}"
                             class="post-hero__img">
                    @endif
                    <div class="post-hero__body">
                        @if($post->category)
                            <a href="{{ route('posts.category', $post->category) }}" class="post-hero__cat">
                                {{ $post->category->name }}
                            </a>
                        @endif
                        <h1 class="post-hero__title">{{ $post->title }}</h1>
                        <div class="post-hero__meta">
                            <span><i class="bi bi-calendar3"></i>{{ $post->created_at->format('d/m/Y') }}</span>
                            @if($post->author)
                                <span><i class="bi bi-person"></i>{{ $post->author->name }}</span>
                            @endif
                            @if($post->tags->isNotEmpty())
                                <span><i class="bi bi-tags"></i>
                                    @foreach($post->tags->take(3) as $t) {{ $t->name }}@unless($loop->last), @endunless @endforeach
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Article --}}
                <div class="post-article">
                    <div class="post-content">
                        {!! $post->content !!}
                    </div>
                </div>

                {{-- Tags --}}
                @if($post->tags->isNotEmpty())
                <div class="post-tags d-flex align-items-center flex-wrap gap-1">
                    <span class="text-muted small me-1"><i class="bi bi-tags me-1"></i>Tags:</span>
                    @foreach($post->tags as $tag)
                        <a href="#" class="tag-item">{{ $tag->name }}</a>
                    @endforeach
                </div>
                @endif

                {{-- Social share --}}
                <div class="post-share">
                    <span class="post-share__label"><i class="bi bi-share me-1"></i>Chia sẻ</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"
                       target="_blank" rel="noopener" class="share-btn share-btn-fb">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($post->title) }}"
                       target="_blank" rel="noopener" class="share-btn share-btn-x">
                        <i class="bi bi-twitter-x"></i> X
                    </a>
                    <a href="https://zalo.me/share?url={{ urlencode(request()->url()) }}"
                       target="_blank" rel="noopener" class="share-btn share-btn-zalo">
                        Zalo
                    </a>
                    <button type="button" class="share-btn share-btn-copy" onclick="navigator.clipboard.writeText(window.location.href);this.innerHTML='<i class=\'bi bi-check2\'></i> Đã sao chép'">
                        <i class="bi bi-link-45deg"></i> Sao chép link
                    </button>
                </div>

                {{-- Related posts --}}
                @if($otherPosts->count())
                <div class="related-section">
                    <div class="related-section__head">
                        <i class="bi bi-newspaper text-primary"></i> Bài viết khác
                    </div>
                    <div class="row g-3">
                        @foreach($otherPosts as $other)
                        <div class="col-sm-6">
                            <div class="news-card">
                                <div class="news-card__img-wrap">
                                    @if($other->image)
                                        <img src="{{ asset('storage/' . $other->image) }}"
                                             alt="{{ $other->title }}"
                                             class="news-card__img">
                                    @else
                                        <div class="news-card__img-placeholder">
                                            <i class="bi bi-file-earmark-richtext"></i>
                                        </div>
                                    @endif
                                    @if($other->category)
                                        <a href="{{ route('posts.category', $other->category) }}"
                                           class="news-card__cat">{{ $other->category->name }}</a>
                                    @endif
                                </div>
                                <div class="news-card__body">
                                    <a href="{{ route('posts.show', $other) }}" class="news-card__title">
                                        {{ $other->title }}
                                    </a>
                                    <div class="news-card__meta">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $other->created_at->format('d/m/Y') }}
                                        @if($other->author)
                                            &nbsp;&bull;&nbsp;<i class="bi bi-person me-1"></i>{{ $other->author->name }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($otherPosts->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $otherPosts->links() }}
                    </div>
                    @endif
                </div>
                @endif

            </div>

            {{-- ── Sidebar ──────────────────────────────── --}}
            <div class="col-lg-4">

                {{-- Categories --}}
                @if($categories->count())
                <div class="news-sidebar-box">
                    <div class="news-sidebar-box__head">
                        <i class="bi bi-grid me-1"></i> Chuyên mục
                    </div>
                    <div class="news-sidebar-box__body">
                        @foreach($categories as $cat)
                            <a href="{{ route('posts.category', $cat) }}" class="news-cat-item">
                                <span>{{ $cat->name }}</span>
                                @if(isset($cat->posts_count))
                                    <span class="badge rounded-pill bg-light text-secondary border">{{ $cat->posts_count }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Tags --}}
                @if($tags->count())
                <div class="news-sidebar-box">
                    <div class="news-sidebar-box__head">
                        <i class="bi bi-tags me-1"></i> Từ khoá
                    </div>
                    <div class="news-sidebar-box__body">
                        <div class="tag-cloud">
                            @foreach($tags as $tag)
                                <a href="#" class="tag-item">{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Sticky recent posts --}}
                <div class="news-sidebar-box" style="position:sticky;top:24px;">
                    <div class="news-sidebar-box__head">
                        <i class="bi bi-clock-history me-1"></i> Bài viết mới nhất
                    </div>
                    <div class="news-sidebar-box__body p-0">
                        @foreach($otherPosts->take(5) as $recent)
                            <a href="{{ route('posts.show', $recent) }}"
                               class="d-flex gap-3 px-3 py-2 text-decoration-none align-items-center border-bottom news-cat-item">
                                @if($recent->image)
                                    <img src="{{ asset('storage/' . $recent->image) }}"
                                         alt="{{ $recent->title }}"
                                         style="width:52px;height:40px;object-fit:cover;border-radius:6px;flex-shrink:0;">
                                @else
                                    <div style="width:52px;height:40px;background:#f1f5f9;border-radius:6px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#94a3b8;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                                <div style="min-width:0;">
                                    <div style="font-size:.8rem;font-weight:600;color:#0f172a;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        {{ $recent->title }}
                                    </div>
                                    <div style="font-size:.7rem;color:#94a3b8;">{{ $recent->created_at->format('d/m/Y') }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
