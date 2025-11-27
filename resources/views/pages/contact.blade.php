@extends('layouts.site')
@section('breadcrumb')
    <x-breadcrumb
    title="Liên hệ"
    :items="[  
        ['label' => 'Liên hệ', 'url' => '']
    ]"/> 
@endsection 
@section('content')
    <div class="container mb-4 pb-4"> 
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <div class="row">
            <div class="col-md-6"> 

                <div class="sc_googlemap_content_wrap">
                    <div class="sc_googlemap">
                        <iframe
                        src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3917.028662810542!2d106.47366717504458!3d10.961207389198991!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMTDCsDU3JzQwLjQiTiAxMDbCsDI4JzM0LjUiRQ!5e0!3m2!1svi!2s!4v1763396056744!5m2!1svi!2s"
                        scrolling="no"
                        marginheight="0"
                        marginwidth="0"
                        frameborder="0"
                        width="100%"
                        height="650px"
                        aria-label="One"></iframe>
                    </div>
                </div> 
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <h3 class="mb-3 pb-2">Thông tin liên hệ</h3>  
                    <ul>
                        <li><strong>Mã số thuế:</strong> {{ $settings['tax_number']->value ?? 'Chưa có' }}</li>
                        <li><strong>Địa chỉ:</strong> {{ $settings['address']->value ?? '' }}</li>
                        <li><strong>Điện thoại:</strong> {{ $settings['hotline']->value ?? '' }}</li>
                        <li><strong>Email: </strong> <a href="mailto: {{ $settings['email']->value ?? '' }}">{{ $settings['email']->value ?? '' }}</a></li>
                    </ul>
                 </div>
                <h3> Gửi tin nhắn cho chúng tôi</h3>
                <p>Vui lòng điền vào biểu mẫu bên dưới để gửi tin nhắn cho chúng tôi.</p> 
                 
                <form action="{{ route('pages.contact.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Họ và tên</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Tin nhắn</label>
                        <textarea name="message" id="message" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi tin nhắn</button>
                </form>
            </div>
        </div>
    </div>
@endsection
