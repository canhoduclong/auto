
@props(['items' => []])

@php
    $title = $items[0]['label'] ?? '';
    $breadcrumbs = array_slice($items, 1);
@endphp

<div class="breadcrumb-option set-bg mb-4 pb-4" data-setbg="{{ asset('img/breadcrumb-bg.jpg') }}" style="background-image: url(&quot;{{ asset('img/breadcrumb-bg.jpg') }}&quot;);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb__text"> 
                   
                    <h2 class="text-xl font-bold mb-2">{{ $title ?? 'Trang chủ' }}</h2>
                
                    
                    <div class="breadcrumb__links">
                        <a href="{{ url('/') }}"><i class="fa fa-home"></i> Trang chủ</a>
                         @foreach ($breadcrumbs  as $item)
                            <span class="breadcrumbs_delimiter"></span> 
                            @if(isset($item['url']) && $item['url'])
                                <a href="{{ $item['url'] }}"  class="text-gray-500 hover:text-black">
                                    {{ $item['label'] }}
                                </a>
                            @else
                                <span>
                                    {{ $item['label'] }}
                                </span> 
                            @endif
                        @endforeach 
                         
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

 