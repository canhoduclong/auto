@extends('layouts.site')

@section('breadcrumb')
<div class="breadcrumb-option set-bg mb-4 pb-4" data-setbg="{{ asset('img/breadcrumb-bg.jpg') }}"
     style="background-image: url('{{ asset('img/breadcrumb-bg.jpg') }}');">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb__text">
                    <h2>Tin tức</h2>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                        <span> Tin tức</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* ── Layout ─────────────────────────────────── */
    .news-page { padding: 48px 0 64px; }

    /* ── Featured post ──────────────────────────── */
    .news-featured {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(15,23,42,.12);
        margin-bottom: 40px;
        background: #0f172a;
        min-height: 380px;
        display: flex;
        align-items: flex-end;
    }
    .news-featured__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: .55;
        transition: opacity .4s;
    }
    .news-featured:hover .news-featured__img { opacity: .45; }
    .news-featured__body {
        position: relative;
        z-index: 1;
        padding: 32px 36px;
        width: 100%;
        background: linear-gradient(to top, rgba(15,23,42,.9) 0%, transparent 100%);
    }
    .news-featured__cat {
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
    }
    .news-featured__title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #fff;
        line-height: 1.25;
        margin-bottom: 10px;
    }
    .news-featured__meta {
        font-size: .78rem;
        color: rgba(255,255,255,.65);
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        align-items: center;
    }
    .news-featured__excerpt {
        color: rgba(255,255,255,.75);
        font-size: .88rem;
        margin: 8px 0 14px;
        line-height: 1.55;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .btn-news-read {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #0ea5e9;
        color: #fff;
        font-size: .82rem;
        font-weight: 700;
        border: 0;
        border-radius: 8px;
        padding: 8px 20px;
        text-decoration: none;
        transition: background .2s, transform .15s;
    }
    .btn-news-read:hover { background: #0284c7; color: #fff; transform: translateY(-1px); }

    /* ── Post card ──────────────────────────────── */
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
    .news-card:hover {
        box-shadow: 0 14px 36px rgba(15,23,42,.12);
        transform: translateY(-3px);
    }
    .news-card__img-wrap {
        position: relative;
        height: 190px;
        overflow: hidden;
        background: #e2e8f0;
        flex-shrink: 0;
    }
    .news-card__img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .4s ease;
    }
    .news-card:hover .news-card__img { transform: scale(1.04); }
    .news-card__img-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 2.5rem;
    }
    .news-card__cat {
        position: absolute;
        top: 12px;
        left: 12px;
        background: #0ea5e9;
        color: #fff;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        padding: 3px 10px;
        border-radius: 20px;
        text-decoration: none;
    }
    .news-card__cat:hover { background: #0284c7; color: #fff; }
    .news-card__body {
        padding: 18px 20px 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .news-card__title {
        font-size: .96rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-decoration: none;
    }
    .news-card__title:hover { color: #0ea5e9; }
    .news-card__excerpt {
        font-size: .8rem;
        color: #64748b;
        line-height: 1.55;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
        margin-bottom: 12px;
    }
    .news-card__footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
    }
    .news-card__meta {
        font-size: .72rem;
        color: #94a3b8;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .news-card__link {
        font-size: .78rem;
        font-weight: 700;
        color: #0ea5e9;
        text-decoration: none;
        white-space: nowrap;
    }
    .news-card__link:hover { color: #0284c7; }

    /* ── Sidebar ────────────────────────────────── */
    .news-sidebar-box {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 6px 20px rgba(15,23,42,.07);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .news-sidebar-box__head {
        background: #0f172a;
        color: #fff;
        font-weight: 700;
        font-size: .82rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: 12px 18px;
    }
    .news-sidebar-box__body { padding: 14px 18px; background: #fff; }
    .news-cat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 7px 0;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: #334155;
        font-size: .85rem;
        transition: color .15s;
    }
    .news-cat-item:last-child { border-bottom: 0; }
    .news-cat-item:hover { color: #0ea5e9; }
    .news-cat-item .badge { font-size: .68rem; }
    .tag-cloud { display: flex; flex-wrap: wrap; gap: 6px; }
    .tag-item {
        display: inline-block;
        background: #f1f5f9;
        color: #475569;
        font-size: .75rem;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        text-decoration: none;
        transition: background .15s, color .15s;
    }
    .tag-item:hover { background: #0ea5e9; color: #fff; }

    /* ── Pagination ─────────────────────────────── */
    .news-page .pagination .page-link {
        border-radius: 8px !important;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-weight: 600;
        font-size: .82rem;
        margin: 0 2px;
        transition: background .15s, color .15s;
    }
    .news-page .pagination .page-item.active .page-link {
        background: #0ea5e9;
        border-color: #0ea5e9;
        color: #fff;
    }
    .news-page .pagination .page-link:hover { background: #e0f2fe; color: #0284c7; }
</style>
@endpush

@section('content')
<section class="news-page">
    <div class="container">

        {{-- ── Featured post ──────────────────────────── --}}
        @if($featured)
        <div class="news-featured">
            @if($featured->image)
                <img src="{{ asset('storage/' . $featured->image) }}"
                     alt="{{ $featured->title }}"
                     class="news-featured__img">
            @else
                <img src="{{ asset('img/latest-blog/lb-1.jpg') }}"
                     alt="{{ $featured->title }}"
                     class="news-featured__img">
            @endif
            <div class="news-featured__body">
                @if($featured->category)
                    <a href="{{ route('posts.category', $featured->category) }}" class="news-featured__cat">
                        {{ $featured->category->name }}
                    </a>
                @endif
                <h2 class="news-featured__title">{{ $featured->title }}</h2>
                <p class="news-featured__excerpt">
                    {{ Str::limit(strip_tags($featured->excerpt ?: $featured->content), 160) }}
                </p>
                <div class="news-featured__meta">
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $featured->created_at->format('d/m/Y') }}</span>
                    @if($featured->author)
                        <span><i class="bi bi-person me-1"></i>{{ $featured->author->name }}</span>
                    @endif
                </div>
                <div class="mt-3">
                    <a href="{{ route('posts.show', $featured) }}" class="btn-news-read">
                        Đọc ngay <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Main columns ────────────────────────── --}}
        <div class="row g-4">
            {{-- Posts grid --}}
            <div class="col-lg-8">
                @if($posts->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-newspaper fs-1"></i>
                        <p class="mt-2">Chưa có bài viết nào.</p>
                    </div>
                @else
                <div class="row g-4">
                    @foreach($posts as $post)
                    <div class="col-sm-6 col-md-4">
                        <div class="news-card">
                            <div class="news-card__img-wrap">
                                @if($post->image)
                                    <img src="{{ asset('storage/' . $post->image) }}"
                                         alt="{{ $post->title }}"
                                         class="news-card__img">
                                @else
                                    <div class="news-card__img-placeholder">
                                        <i class="bi bi-file-earmark-richtext"></i>
                                    </div>
                                @endif
                                @if($post->category)
                                    <a href="{{ route('posts.category', $post->category) }}"
                                       class="news-card__cat">{{ $post->category->name }}</a>
                                @endif
                            </div>
                            <div class="news-card__body">
                                <a href="{{ route('posts.show', $post) }}" class="news-card__title">
                                    {{ $post->title }}
                                </a>
                                <p class="news-card__excerpt">
                                    {{ Str::limit(strip_tags($post->excerpt ?: $post->content), 100) }}
                                </p>
                                <div class="news-card__footer">
                                    <div class="news-card__meta">
                                        <span><i class="bi bi-calendar3 me-1"></i>{{ $post->created_at->format('d/m/Y') }}</span>
                                        @if($post->author)
                                            <span><i class="bi bi-person me-1"></i>{{ $post->author->name }}</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('posts.show', $post) }}" class="news-card__link">
                                        Đọc &rsaquo;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($posts->hasPages())
                <div class="d-flex justify-content-center mt-5">
                    {{ $posts->links() }}
                </div>
                @endif
                @endif
            </div>

            {{-- Sidebar --}}
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

                {{-- Recent posts --}}
                @if($posts->total() > 1)
                <div class="news-sidebar-box">
                    <div class="news-sidebar-box__head">
                        <i class="bi bi-clock-history me-1"></i> Bài viết mới nhất
                    </div>
                    <div class="news-sidebar-box__body p-0">
                        @foreach($posts->take(4) as $recent)
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
                @endif
            </div>
        </div>

    </div>
</section>
@endsection
