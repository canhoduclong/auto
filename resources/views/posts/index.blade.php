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
                            <span> Tin tức</span>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div> 
@endsection
@section('content')
<section class="blog spad">
    <div class="container"> 
        <div class="row">
            <div class="col-md-8">
                 

                <div class="row">
                    @foreach($posts as $post)
                    <div class="col-lg-6 col-md-6 mb-4">
                        <div class="latest__blog__item h-100">
                            @if($post->image)
                                <div class="latest__blog__item__pic set-bg" data-setbg="{{ asset('storage/' . $post->image) }}" style="background-image: url('{{ asset('storage/' . $post->image) }}');"></div>
                            @else
                                <div class="latest__blog__item__pic set-bg" data-setbg="{{ asset('img/latest-blog/lb-1.jpg') }}" style="background-image: url('{{ asset('img/latest-blog/lb-1.jpg') }}');"></div>
                            @endif
                            <div class="latest__blog__item__text">
                                <h5>{{ $post->title }}</h5>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->content), 120) }}</p>
                                <a href="{{ route('posts.show', $post) }}">Xem thêm <i class="fa fa-long-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{ $posts->links() }}
            </div> 
            <div class="col-md-4"> 
                <ul class="list-group">
                    <li class="list-group-item">Chuyên mục</li>
                    @foreach($categories as $category)
                        <li class="list-group-item">
                            <a href="{{ route('posts.category', $category) }}">{{ $category->name }}</a>
                        </li>
                    @endforeach
                </ul>
                   
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
</section>
@endsection
