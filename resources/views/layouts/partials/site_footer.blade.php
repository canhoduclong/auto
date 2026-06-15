 @yield('footer')
 @php
    $footerLogo = null;
    if (isset($settings['footer_logo']) && !empty($settings['footer_logo']->value)) {
        $footerLogo = App\Models\Media::find($settings['footer_logo']->value);
    }

    $brandName = $settings['brand_name']->value ?? __('site.footer_logo_fallback');
    $taxNumber = $settings['tax_number']->value ?? __('site.not_available');
    $address = $settings['address']->value ?? '';
    $hotline = $settings['hotline']->value ?? ($settings['HOTLINE']->value ?? '');
    $email = $settings['email']->value ?? '';
    $policyLink = route('pages.privacy_policy');

    $normalizedPhone = preg_replace('/\s+/', '', $hotline);

    $socialLinks = [
        [
            'name' => 'Facebook',
            'icon' => 'fa-facebook',
            'url' => '#',
            'class' => 'facebook',
        ],
        [
            'name' => 'YouTube',
            'icon' => 'fa-youtube-play',
            'url' => '#',
            'class' => 'youtube',
        ],
        [
            'name' => 'Zalo',
            'icon' => 'fa-commenting',
            'url' => '#',
            'class' => 'zalo',
        ],
    ];
 @endphp
 <style>
    .site-footer-pro {
        position: relative;
        color: #e2e8f0;
        background: #8c4b06;
        padding: 56px 0 0;
        overflow: hidden;
    }

    .site-footer-pro::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: radial-gradient(circle at 15% 10%, rgba(45, 212, 191, 0.14), transparent 28%);
    }

    .site-footer-pro .container {
        position: relative;
        z-index: 1;
    }

    .site-footer-pro__brand {
        max-width: 420px;
    }

    .site-footer-pro__brand p {
        color: rgba(226, 232, 240, 0.8);
        margin-bottom: 16px;
        line-height: 1.7;
    }

    .site-footer-pro__logo {
        display: inline-flex;
        align-items: center;
        margin-bottom: 14px;
    }

    .site-footer-pro__logo img {
        max-height: 58px;
        width: auto;
    }

    .site-footer-pro__brand-title {
        font-size: 26px;
        letter-spacing: 0.02em;
        font-weight: 800;
        color: #f8fafc;
    }

    .site-footer-pro__list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .site-footer-pro__list li {
        color: rgba(226, 232, 240, 0.86);
        margin-bottom: 10px;
        line-height: 1.55;
    }

    .site-footer-pro__heading {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 16px;
        color: #f8fafc;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .site-footer-pro__link {
        color: rgba(226, 232, 240, 0.86);
        transition: color 0.2s ease;
    }

    .site-footer-pro__link:hover {
        color: #2dd4bf;
    }

    .site-footer-pro__contact-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid rgba(45, 212, 191, 0.45);
        border-radius: 999px;
        color: #ccfbf1;
        padding: 8px 14px;
        margin: 0 10px 10px 0;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s ease;
    }

    .site-footer-pro__contact-btn:hover {
        background: rgba(45, 212, 191, 0.15);
        color: #ffffff;
    }

    .site-footer-pro__social {
        display: flex;
        gap: 10px;
    }

    .site-footer-pro__social a {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #e2e8f0;
        border: 1px solid rgba(148, 163, 184, 0.35);
        transition: all 0.2s ease;
    }

    .site-footer-pro__social a:hover {
        border-color: #2dd4bf;
        color: #2dd4bf;
        transform: translateY(-2px);
    }

    .site-footer-pro__bottom {
        margin-top: 34px;
        border-top: 1px solid rgba(148, 163, 184, 0.25);
        padding: 16px 0;
        font-size: 13px;
        color: rgba(226, 232, 240, 0.72);
        background-color: #593005;
    }
  .site-footer-pro__bottom p {
        color: rgba(226, 232, 240, 0.72);
        transition: color 0.2s ease;
    }
    @media (max-width: 767.98px) {
        .site-footer-pro {
            padding-top: 44px;
        }

        .site-footer-pro__brand {
            margin-bottom: 22px;
        }
    }
 </style>

 <footer class="site-footer-pro">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-5 col-md-12">
                <div class="site-footer-pro__brand">
                    <a href="{{ route('home') }}" class="site-footer-pro__logo">
                        @if($footerLogo)
                            <img src="{{ asset('storage/' . $footerLogo->file_path) }}" alt="footer logo">
                        @else
                            <span class="site-footer-pro__brand-title">{{ $brandName }}</span>
                        @endif
                    </a>
                    <p>
                        {{ $brandName }} cam ket mang den trai nghiem mua sam linh kien va phu tung xe chat luong,
                        quy trinh minh bach va ho tro tan tam cho tung don hang.
                    </p>
                    <a href="{{ $policyLink }}" class="site-footer-pro__contact-btn">
                        <i class="fa fa-shield"></i>
                        {{ __('site.policy_and_terms') }}
                    </a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <h5 class="site-footer-pro__heading">{{ __('site.address') }} & {{ __('site.contact') }}</h5>
                <ul class="site-footer-pro__list">
                    <li>{{ __('site.tax_code') }}: {{ $taxNumber }}</li>
                    <li>{{ __('site.address') }}: {{ $address }}</li>
                    <li>{{ __('site.hotline') }}: {{ $hotline }}</li>
                    <li>{{ __('site.email') }}: {{ $email }}</li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="site-footer-pro__heading">{{ __('site.support') }}</h5>
                @if(!empty($hotline))
                    <a href="tel:{{ $normalizedPhone }}" class="site-footer-pro__contact-btn">
                        <i class="fa fa-phone"></i>
                        {{ __('site.call_now') }}
                    </a>
                @endif
                @if(!empty($email))
                    <a href="mailto:{{ $email }}" class="site-footer-pro__contact-btn">
                        <i class="fa fa-envelope"></i>
                        Email support
                    </a>
                @endif
                <h5 class="site-footer-pro__heading mt-3">{{ __('site.official_channels') }}</h5>
                <div class="site-footer-pro__social">
                    @foreach($socialLinks as $social)
                        <a href="{{ $social['url'] }}" class="{{ $social['class'] }}" aria-label="{{ $social['name'] }}">
                            <i class="fa {{ $social['icon'] }}"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="site-footer-pro__bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 ">
         <div class="container">
            <div class="row gy-4">
                <div class="col-lg-12 col-md-12  d-flex justify-content-between">

                    <p class="mb-0">Copyright &copy; {{ date('Y') }} {{ __('site.copyright') }}</p>
                    <p class="mb-0">{{ $brandName }} | Auto Parts & Service Platform</p>
                </div>
            </div>
        </div>
    </div>
    
 </footer>
