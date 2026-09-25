@extends('layouts.site')
@push('styles')
<style>
    .contact-page {
        padding-bottom: 20px;
        background:
            radial-gradient(circle at top left, rgba(14, 165, 233, 0.08), transparent 26%),
            radial-gradient(circle at top right, rgba(15, 118, 110, 0.08), transparent 24%),
            linear-gradient(180deg, #f8fbff 0%, #ffffff 42%, #f7fafc 100%);
    }
    .contact-hero {
        padding: 22px 0 18px;
        margin-bottom: 24px;
    }
    .contact-hero h2 {
        margin: 0;
        color: #0f172a;
        font-size: clamp(1.8rem, 3vw, 2.7rem);
        font-weight: 900;
        line-height: 1.15;
        letter-spacing: -.03em;
    }
    .contact-hero p {
        margin: 10px 0 0;
        color: #475569;
        font-size: 1rem;
        max-width: 700px;
    }
    .contact-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #0f766e;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 12px;
    }
    .contact-layout {
        display: grid;
        grid-template-columns: 1.05fr .95fr;
        gap: 30px;
        align-items: start;
    }
    .contact-column {
        position: relative;
    }
    .contact-divider {
        height: 1px;
        background: linear-gradient(90deg, rgba(148, 163, 184, 0), rgba(148, 163, 184, .75), rgba(148, 163, 184, 0));
        margin: 18px 0 22px;
    }
    .contact-info-strip {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 18px;
        padding: 18px 0 4px;
    }
    .contact-info-block {
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(226, 232, 240, .9);
    }
    .info-title,
    .form-title,
    .contact-map-title {
        font-size: 1.08rem;
        color: #0f172a;
        font-weight: 800;
        margin-bottom: 12px;
    }
    .contact-info-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 12px;
    }
    .contact-info-item {
        padding: 0;
        color: #334155;
        line-height: 1.55;
    }
    .contact-info-item strong {
        color: #0f172a;
        display: block;
        margin-bottom: 2px;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .contact-info-item a {
        color: #0f766e;
        text-decoration: none;
    }
    .contact-info-item a:hover {
        text-decoration: underline;
    }
    .contact-help {
        color: #64748b;
        font-size: .92rem;
        margin: 2px 0 18px;
        max-width: 560px;
    }
    .contact-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 16px;
    }
    .contact-form .form-label {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 6px;
        font-size: .9rem;
    }
    .contact-form .form-control {
        border-radius: 0;
        border: 0;
        border-bottom: 1px solid #cbd5e1;
        background: transparent;
        padding: 10px 0;
        border-color: #cbd5e1;
        min-height: 44px;
    }
    .contact-form textarea.form-control {
        min-height: 132px;
        resize: vertical;
    }
    .contact-form .form-control:focus {
        border-color: #60a5fa;
        box-shadow: inset 0 -2px 0 rgba(37, 99, 235, .18);
    }
    .contact-form .full-span {
        grid-column: 1 / -1;
    }
    .contact-submit {
        border: none;
        border-radius: 999px;
        background: linear-gradient(135deg, #0f766e 0%, #1d4ed8 100%);
        color: #fff;
        font-weight: 700;
        padding: 12px 22px;
        transition: transform .2s ease, box-shadow .2s ease;
        box-shadow: 0 10px 20px rgba(29, 78, 216, .18);
    }
    .contact-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 24px rgba(29, 78, 216, .22);
    }
    .contact-map-wrap {
        margin-top: 34px;
        padding-top: 22px;
        border-top: 1px solid rgba(226, 232, 240, .95);
    }
    .contact-map-frame {
        border-radius: 0;
        overflow: hidden;
        border-top: 1px solid #cbd5e1;
        border-bottom: 1px solid #cbd5e1;
    }
    .contact-map {
        width: 100%;
        height: 420px;
        border: 0;
        display: block;
    }
    @media (max-width: 991.98px) {
        .contact-layout,
        .contact-form-grid,
        .contact-info-strip {
            grid-template-columns: 1fr;
        }
        .contact-map {
            height: 360px;
        }
    }
</style>
@endpush
@section('breadcrumb')
    <x-breadcrumb
    title="Liên hệ"
    :items="[  
        ['label' => 'Liên hệ', 'url' => '']
    ]"/> 
@endsection 
@section('content')
<section class="contact-page mb-0 pb-0">
    <div class="container mb-4 pb-3">
        <div class="contact-hero">
            <div class="contact-eyebrow">Lien he ho tro</div>
            <h2>Liên hệ với chúng tôi</h2>
            <p>Đội ngũ tư vấn sẽ phản hồi sớm nhất về sản phẩm, báo giá và hỗ trợ đơn hàng của bạn.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="contact-layout">
            <div class="contact-column">
                <div class="contact-info-block">
                    <h3 class="info-title">Thông tin liên hệ</h3>
                    <ul class="contact-info-list">
                        <li class="contact-info-item">
                            <strong>Mã số thuế</strong>
                            <span>{{ $settings['tax_number']->value ?? 'Chưa có' }}</span>
                        </li>
                        <li class="contact-info-item">
                            <strong>Địa chỉ</strong>
                            <span>{{ $settings['address']->value ?? 'Chưa cập nhật' }}</span>
                        </li>
                        <li class="contact-info-item">
                            <strong>Điện thoại</strong>
                            <span>{{ $settings['hotline']->value ?? 'Chưa cập nhật' }}</span>
                        </li>
                        <li class="contact-info-item">
                            <strong>Email</strong>
                            <a href="mailto:{{ $settings['email']->value ?? '' }}">{{ $settings['email']->value ?? 'Chưa cập nhật' }}</a>
                        </li>
                    </ul>
                </div>

                <div class="contact-divider"></div>

                <div class="contact-info-strip">
                    <div>
                        <div class="info-title mb-2">Hỗ trợ nhanh</div>
                        <p class="contact-help mb-0">Phù hợp khi bạn cần báo giá, hỗ trợ đơn hàng, chính sách giao hàng hoặc xác nhận tồn kho.</p>
                    </div>
                    <div>
                        <div class="info-title mb-2">Thời gian phản hồi</div>
                        <p class="contact-help mb-0">Chúng tôi ưu tiên phản hồi trong giờ hành chính và xử lý sớm các yêu cầu cần xác nhận đơn hàng.</p>
                    </div>
                </div>
            </div>

            <div class="contact-column">
                <div>
                    <h3 class="form-title">Gửi tin nhắn cho chúng tôi</h3>
                    <p class="contact-help">Vui lòng điền đầy đủ thông tin, chúng tôi sẽ phản hồi trong thời gian sớm nhất.</p>

                    <form action="{{ route('pages.contact.store') }}" method="POST" class="contact-form">
                        @csrf
                        <div class="contact-form-grid">
                            <div>
                                <label for="name" class="form-label">Họ và tên</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                            </div>

                            <div>
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                            </div>

                            <div class="full-span">
                                <label for="message" class="form-label">Tin nhắn</label>
                                <textarea name="message" id="message" class="form-control" rows="5" required>{{ old('message') }}</textarea>
                            </div>
                        </div>

                        <button type="submit" class="contact-submit">Gửi tin nhắn</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="contact-map-wrap">
            <h3 class="contact-map-title">Vị trí trên bản đồ</h3>
            <div class="contact-map-frame">
                <iframe
                    class="contact-map"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.642530245644!2d106.61261387365528!3d10.762008859462329!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752d0078dc7fbf%3A0x1f9e629c52b1072c!2zQ8O0bmcgVHkgVGjhu7FjIHBo4bqpbSBIb8OgbmcgTG9uZyBUTlQ!5e0!3m2!1svi!2s!4v1773195184642!5m2!1svi!2s"
                    scrolling="no"
                    marginheight="0"
                    marginwidth="0"
                    frameborder="0"
                    aria-label="Google map">
                </iframe>
            </div>
        </div>
    </div>
</section>
@endsection
