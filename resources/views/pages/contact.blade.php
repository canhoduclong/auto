@extends('layouts.site')
<style type="text/css">
.site-btn {
	font-size: 15px;
	color: #ffffff;
	font-weight: 700;
	display: inline-block;
	padding: 15px 35px 12px 38px;
	background: #56292d;
	border: none;
	border-radius: 2px;
}
.btn-brand{
	font-size: 15px;
	color: #ffffff;
	font-weight: 700;
	display: inline-block;
	padding: 15px 35px 12px 38px;
	background: #56292d;
	border: none;
	border-radius: 2px;
}
.btn-info{
	font-size: 15px;
	color: #ffffff;
	font-weight: 700;
	display: inline-block;
	padding: 15px 35px 12px 38px;
	background: #56292d;
	border: none;
	border-radius: 2px;
}
</style>
@section('breadcrumb')
    <x-breadcrumb
    title="Liên hệ"
    :items="[  
        ['label' => 'Liên hệ', 'url' => '']
    ]"/> 
@endsection 
@section('content')
<section class="contact spad set-bg mb-0 pb-0" data-setbg="{{ asset('img/contact-bg.jpg') }}" style="background-image: url(&quot;{{ asset('img/contact-bg.jpg') }}&quot;);">
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
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.642530245644!2d106.61261387365528!3d10.762008859462329!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752d0078dc7fbf%3A0x1f9e629c52b1072c!2zQ8O0bmcgVHkgVGjhu7FjIHBo4bqpbSBIb8OgbmcgTG9uZyBUTlQ!5e0!3m2!1svi!2s!4v1773195184642!5m2!1svi!2s"
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
                    <button type="submit" class=" site-btn btn-brand">Gửi tin nhắn</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
