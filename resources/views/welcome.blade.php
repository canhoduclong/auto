@extends('layouts.site')



@section('content')
<div class="container">
    <div class="jumbotron">
        <h1 class="display-4">Chào mừng đến với {{ $settings['brand_name']->value ?? 'Công ty chúng tôi' }}!</h1>
        <p class="lead">Chúng tôi chuyên cung cấp các sản phẩm và dịch vụ chất lượng cao. Hãy khám phá trang web của chúng tôi để biết thêm chi tiết.</p>
        <hr class="my-4">
        <a class="btn btn-primary btn-lg" href="{{ route('site.variants') }}" role="button">Xem sản phẩm</a>
    </div>

    <!-- Danh mục sản phẩm -->
    <div class="row mt-5">
        <div class="col-md-3">
            <h2>Danh mục sản phẩm</h2>
            <div class="list-group">
                @foreach($categories as $category)
                    <a href="#" class="list-group-item list-group-item-action">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>
        <div class="col-md-9">
            <!-- Sản phẩm mới -->
            <div class="row">
                <div class="col-12">
                    <h2>Sản phẩm mới</h2>
                </div>
                @foreach($products as $product)
                    <div class="col-md-4">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">{{ $product->name }}</h5>
                                <p class="card-text">{{ $product->short_description }}</p>
                                <a href="#" class="btn btn-primary">Xem chi tiết</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Tin tức mới -->
            <div class="row mt-5">
                <div class="col-12">
                    <h2>Tin tức mới</h2>
                </div>
                @foreach($posts as $post)
                    <div class="col-md-4">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">{{ $post->title }}</h5>
                                <p class="card-text">{{ $post->excerpt }}</p>
                                <a href="#" class="btn btn-primary">Đọc thêm</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
</div>
@endsection

@section('footer')
<footer class="footer mt-auto py-3 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>Thông tin công ty</h5>
                <p>{{ $settings['brand_name']->value ?? '' }}</p>
                <p>Mã số thuế: {{ $settings['tax_number']->value ?? 'Chưa có' }}</p>
            </div>
            <div class="col-md-4">
                <h5>Liên hệ</h5>
                <p>Địa chỉ: {{ $settings['address']->value ?? '' }}</p>
                <p>Hotline: {{ $settings['hotline']->value ?? '' }}</p>
                <p>Email: {{ $settings['email']->value ?? '' }}</p>
            </div>
            <div class="col-md-4">
                <h5>Chính sách</h5>
                <p><a href="{{ $settings['policy_page']->value ?? '#' }}">Chính sách và quy định</a></p>
            </div>
        </div>
    </div>
</footer>
@endsection