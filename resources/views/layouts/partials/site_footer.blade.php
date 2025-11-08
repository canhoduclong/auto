 @yield('footer')
 <footer class="footer set-bg" data-setbg="img/footer-bg.jpg" style="background-image: url(&quot;img/footer-bg.jpg&quot;);">
    <div class="container">
            <div class="footer__contact">
                <div class="row">
                    <div class="col-lg-6 col-md-6">
                        <div class="footer__contact__title">
                            <h2>HOTLINE CỨU HỘ 24/7</h2>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="footer__contact__option">
                            <div class="option__item"><i class="fa fa-phone"></i> (+12) 345 678 910</div>
                            <div class="option__item email"><i class="fa fa-envelope-o"></i> autotaybac@gmail.com</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-7 col-md-7">
                    <div class="footer__about">
                        <div class="footer__logo mb-4">
                            <a href="./"> 
                                <img src="{{ asset('storage/media/1FfdEsaXZYGT5wk42Pq1yHW7rLoeh9fuNhPlw540.png' ) }}" alt="logo" height="60">
                                   
                            </a>
                        </div>  
                         <ul class="mx-0 px-0 list-unstyled color-white"> 
                                <li class="color-white">Mã số thuế: {{ $settings['tax_number']->value ?? 'Chưa có' }}</li> 
                                <li class="color-white">Địa chỉ: {{ $settings['address']->value ?? '' }}</li>
                                <li class="color-white">Hotline: {{ $settings['hotline']->value ?? '' }}</li>
                                <li class="color-white">Email: {{ $settings['email']->value ?? '' }}</li>
                         </ul>
                    </div>
                </div> 
                 <div class="col-lg-2 col-md-6">
                    <div class="footer__brand">
                        <h5>Top Brand</h5>
                        <ul>
                            <li><a href="#"><i class="fa fa-angle-right"></i> Abarth</a></li>
                            <li><a href="#"><i class="fa fa-angle-right"></i> Acura</a></li>
                            <li><a href="#"><i class="fa fa-angle-right"></i> Alfa Romeo</a></li>
                            <li><a href="#"><i class="fa fa-angle-right"></i> Audi</a></li>
                        </ul>
                       
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="footer__widget">
                        <h5>Chính sách</h5>
                        <p><a href="{{ $settings['policy_page']->value ?? '#' }}">Chính sách và quy định</a></p>
                        <h5 class="mt-4 pb-3">Kênh chính thức</h5>
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
                <p>Copyright ©<script>document.write(new Date().getFullYear());</script>2025 All rights reserved | This template is made with</p>
            </div>
            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
        </div>
    </footer> 