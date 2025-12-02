@extends('layouts.site')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-lg-12">
            @include('partials.product-search')
        </div>
    </div>
    <div class="row mt-5">
        <div class="col-lg-12">
            <div class="section-title">
                <h2>Kết quả tìm kiếm cho: "{{ $keyword }}"</h2>
            </div>
            @if($variants->isEmpty())
                <p>Không tìm thấy sản phẩm nào phù hợp.</p>
            @else
                <div class="row">
                    @foreach($variants as $variant)
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="car__item">
                                <div class="car__item__pic__slider owl-carousel">
                                    @if(!empty($variant->product->avatar) && $variant->product->avatar->media)
                                       <img src="{{ asset('storage/'.$variant->product->avatar->media->file_path) }}" >
                                    @endif
                                    @foreach($variant->product->gallery as $link)
                                       @if($link->media)
                                           <img src="{{ asset('storage/' . $link->media->file_path) }}">
                                       @endif
                                   @endforeach
                                </div>
                                <div class="car__item__text">
                                    <div class="car__item__text__inner"> 
                                        <h5><a href="{{ route('pages.variant_detail', $variant) }}" class=" text-uppercase ">{{ $variant->product->name }} - {{ $variant->name }}</a></h5>
                                        @if($variant->product->brand)
                                            <p class="card-text">Thương hiệu: {{ $variant->product->brand->name }}</p>
                                        @endif 
                                        @if($variant->sku)
                                            <p class="card-text">Mã sản phẩm: {{ $variant->sku }}</p>
                                        @endif
                                        <p class="card-text">Giá: {{ number_format($variant->final_price, 0, '.', ',') }} VNĐ</p>
                                        <div class="car__item__prưice mt-2 mb-4"> 
                                            <a href="{{ route('pages.variant_detail', $variant) }}" class="btn btn-info  btn-brand btn-sm">Chi tiết</a>
                                            <button class="btn btn-warning btn-sm add-to-cart" data-variant-id="{{ $variant->id }}">
                                                <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                                            </button>  
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-center mt-4">
                    {{ $variants->appends(request()->input())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
