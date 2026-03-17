 @yield('footer')
 @php
    $footerLogo = null;
    if (isset($settings['footer_logo']) && !empty($settings['footer_logo']->value)) {
        $footerLogo = App\Models\Media::find($settings['footer_logo']->value);
    }
 @endphp
 <footer class="footer set-bg" data-setbg="{{ asset('img/footer-bg.jpg') }}" style="background-image: url(&quot;{{ asset('img/footer-bg.jpg') }}&quot;);">
    <div class="container">
            <div class="row">
                <div class="col-lg-9 col-md-9">
                    <div class="footer__about">
                        <div class="footer__logo mb-4">
                            <a href="{{ route('home') }}">
                                @if($footerLogo)
                                    <img src="{{ asset('storage/' . $footerLogo->file_path) }}" alt="footer logo" height="56">
                                @else
                                    {{ $settings['brand_name']->value ?? __('site.footer_logo_fallback') }}
                                @endif
                            </a>
                        </div>  
                         <ul class="mx-0 px-0 list-unstyled color-white"> 
                                <li class="color-white">{{ __('site.tax_code') }}: {{ $settings['tax_number']->value ?? __('site.not_available') }}</li>
                                <li class="color-white">{{ __('site.address') }}: {{ $settings['address']->value ?? '' }}</li>
                                <li class="color-white">{{ __('site.hotline') }}: {{ $settings['hotline']->value ?? '' }}</li>
                                <li class="color-white">{{ __('site.email') }}: {{ $settings['email']->value ?? '' }}</li>
                         </ul>
                    </div>
                </div> 
                 
                <div class="col-md-3">
                    <div class="footer__widget">
                        <h5>{{ __('site.policy') }}</h5>
                        <p><a href="{{ $settings['policy_page']->value ?? '#' }}">{{ __('site.policy_and_terms') }}</a></p>
                        <h5 class="mt-4 pb-3">{{ __('site.official_channels') }}</h5>
                        <div class="footer__social">
                            <a href="#" class="facebook"><i class="fa fa-facebook"></i></a>
                            <a href="#" class="twitter"><i class="fa fa-twitter"></i></a>
                            <a href="#" class="google"><i class="fa fa-google"></i></a>
                            <a href="#" class="skype"><i class="fa fa-skype"></i></a>
                        </div>

                    </div>
                </div> 
            </div>
            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
            <div class="footer__copyright__text">
                <p>Copyright ©<script>document.write(new Date().getFullYear());</script> {{ __('site.copyright') }}</p>
            </div>
            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
        </div>
    </footer> 