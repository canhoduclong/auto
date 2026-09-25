@extends('layouts.site')

@section('breadcrumb')
<div class="breadcrumb-option set-bg mb-0" data-setbg="{{ asset('img/breadcrumb-bg.jpg') }}"
     style="background-image: url('{{ asset('img/breadcrumb-bg.jpg') }}');">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb__text">
                    <h2>Giới thiệu</h2>
                    <div class="breadcrumb__links">
                        <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                        <span> Giới thiệu</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* ── Global ──────────────────────────────── */
.about-page { font-family: inherit; }
.section-label {
    display: inline-block;
    background: #e0f2fe;
    color: #0369a1;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
    padding: 4px 14px;
    border-radius: 20px;
    margin-bottom: 12px;
}
.section-title {
    font-size: 2rem;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.2;
    margin-bottom: 12px;
}
.section-lead {
    font-size: 1rem;
    color: #475569;
    line-height: 1.7;
    max-width: 640px;
}

/* ── Hero / Intro ────────────────────────── */
.about-intro {
    padding: 72px 0 64px;
    background: #fff;
}
.about-intro__img {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(15,23,42,.13);
    height: 100%;
    min-height: 340px;
    background: #e2e8f0;
}
.about-intro__img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.about-intro__text { padding: 12px 0 12px 20px; }
.about-intro__text .section-lead { max-width: unset; }
.about-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f1f5f9;
    border-radius: 10px;
    padding: 10px 16px;
    font-size: .82rem;
    font-weight: 600;
    color: #334155;
    text-decoration: none;
    margin: 4px;
}
.about-tag i { color: #0ea5e9; font-size: 1rem; }

/* ── Vision / Mission ────────────────────── */
.vm-section {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
    padding: 72px 0;
    color: #fff;
}
.vm-card {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 18px;
    padding: 32px 28px;
    height: 100%;
    transition: background .25s, transform .2s;
}
.vm-card:hover {
    background: rgba(255,255,255,.1);
    transform: translateY(-4px);
}
.vm-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 18px;
}
.vm-icon.blue  { background: rgba(14,165,233,.25); color: #38bdf8; }
.vm-icon.green { background: rgba(16,185,129,.25); color: #34d399; }
.vm-icon.gold  { background: rgba(245,158,11,.25);  color: #fbbf24; }
.vm-card h4 {
    font-size: 1.1rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 10px;
}
.vm-card p {
    font-size: .9rem;
    color: rgba(255,255,255,.7);
    line-height: 1.7;
    margin: 0;
}

/* ── Core Values ─────────────────────────── */
.values-section { padding: 72px 0; background: #f8fafc; }
.value-card {
    background: #fff;
    border-radius: 16px;
    padding: 28px 24px;
    height: 100%;
    box-shadow: 0 4px 18px rgba(15,23,42,.06);
    transition: box-shadow .25s, transform .2s;
    text-align: center;
    border-top: 4px solid transparent;
}
.value-card:hover {
    box-shadow: 0 14px 36px rgba(15,23,42,.1);
    transform: translateY(-4px);
}
.value-card.c1 { border-top-color: #0ea5e9; }
.value-card.c2 { border-top-color: #10b981; }
.value-card.c3 { border-top-color: #f59e0b; }
.value-card.c4 { border-top-color: #8b5cf6; }
.value-card.c5 { border-top-color: #ef4444; }
.value-card.c6 { border-top-color: #06b6d4; }
.value-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px;
}
.c1 .value-icon { background: #e0f2fe; color: #0ea5e9; }
.c2 .value-icon { background: #d1fae5; color: #10b981; }
.c3 .value-icon { background: #fef3c7; color: #f59e0b; }
.c4 .value-icon { background: #ede9fe; color: #8b5cf6; }
.c5 .value-icon { background: #fee2e2; color: #ef4444; }
.c6 .value-icon { background: #cffafe; color: #06b6d4; }
.value-card h5 {
    font-size: .95rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}
.value-card p {
    font-size: .82rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

/* ── Stats ───────────────────────────────── */
.stats-section {
    background: linear-gradient(90deg, #0ea5e9 0%, #2563eb 100%);
    padding: 56px 0;
    color: #fff;
}
.stat-item { text-align: center; }
.stat-item__num {
    font-size: 2.8rem;
    font-weight: 900;
    line-height: 1;
    color: #fff;
}
.stat-item__lbl {
    font-size: .82rem;
    color: rgba(255,255,255,.75);
    margin-top: 6px;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.stat-sep {
    width: 1px;
    background: rgba(255,255,255,.2);
    height: 60px;
    align-self: center;
}

/* ── Contact CTA ─────────────────────────── */
.contact-section { padding: 72px 0; background: #fff; }
.contact-info-card {
    background: #f8fafc;
    border-radius: 16px;
    padding: 28px 24px;
    height: 100%;
}
.contact-info-card h5 { font-weight: 800; color: #0f172a; margin-bottom: 16px; }
.contact-row {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid #e2e8f0;
    font-size: .87rem;
    color: #334155;
}
.contact-row:last-child { border-bottom: 0; }
.contact-row i { color: #0ea5e9; font-size: 1.05rem; margin-top: 1px; flex-shrink: 0; }
.contact-map {
    border-radius: 16px;
    overflow: hidden;
    height: 100%;
    min-height: 280px;
    background: #e2e8f0;
    box-shadow: 0 4px 18px rgba(15,23,42,.07);
}
.contact-map iframe { width: 100%; height: 100%; border: 0; display: block; min-height: 280px; }

@media (max-width: 767px) {
    .section-title { font-size: 1.5rem; }
    .about-intro__text { padding: 24px 0 0; }
    .stat-sep { display: none; }
    .stat-item__num { font-size: 2rem; }
}
</style>
@endpush

@section('content')
<div class="about-page">

    {{-- ── Section 1: Giới thiệu công ty ──────── --}}
    <section class="about-intro">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5">
                    <div class="about-intro__img">
                        @if(!empty($settings['logo']?->value))
                            <img src="{{ asset('storage/' . $settings['logo']->value) }}" alt="Công ty">
                        @else
                            <img src="{{ asset('img/about/about-us.jpg') }}" alt="Công ty"
                                 onerror="this.style.display='none';this.parentElement.style.background='linear-gradient(135deg,#0ea5e9,#2563eb)';">
                        @endif
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="about-intro__text">
                        <span class="section-label"><i class="bi bi-building me-1"></i>Về chúng tôi</span>
                        <h2 class="section-title">{{ $settings['brand_name']?->value ?? 'Tên Công Ty' }}</h2>
                        @if(!empty($settings['slogan']?->value))
                            <p class="fw-600 text-primary mb-3" style="font-size:1rem;">
                                "{{ $settings['slogan']->value }}"
                            </p>
                        @endif
                        <p class="section-lead mb-4">
                            {!! $pages->first()?->content ?? '<p>Chúng tôi là đơn vị chuyên cung cấp sản phẩm và dịch vụ chất lượng cao, đáp ứng mọi nhu cầu của khách hàng. Với đội ngũ nhân viên tận tâm, chuyên nghiệp và kinh nghiệm nhiều năm trong ngành, chúng tôi tự hào mang đến những giải pháp tối ưu và trải nghiệm mua sắm tuyệt vời nhất.</p>' !!}
                        </p>
                        <div class="d-flex flex-wrap mt-2">
                            <span class="about-tag"><i class="bi bi-patch-check"></i> Uy tín</span>
                            <span class="about-tag"><i class="bi bi-award"></i> Chất lượng</span>
                            <span class="about-tag"><i class="bi bi-people"></i> Tận tâm</span>
                            <span class="about-tag"><i class="bi bi-lightning-charge"></i> Chuyên nghiệp</span>
                            @if(!empty($settings['tax_number']?->value))
                            <span class="about-tag"><i class="bi bi-file-text"></i> MST: {{ $settings['tax_number']->value }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Section 2: Tầm nhìn – Sứ mệnh ─────── --}}
    <section class="vm-section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-label" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);">Định hướng</span>
                <h2 class="section-title" style="color:#fff;">Tầm nhìn & Sứ mệnh</h2>
                <p class="section-lead mx-auto" style="color:rgba(255,255,255,.65);">
                    Hành trình xây dựng và phát triển của chúng tôi được định hướng bởi những giá trị bền vững.
                </p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="vm-card">
                        <div class="vm-icon blue"><i class="bi bi-eye"></i></div>
                        <h4>Tầm nhìn</h4>
                        <p>Trở thành đơn vị hàng đầu trong lĩnh vực, được khách hàng tin tưởng và là đối tác chiến lược lâu dài của mọi doanh nghiệp và cá nhân trên toàn quốc.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="vm-card">
                        <div class="vm-icon green"><i class="bi bi-bullseye"></i></div>
                        <h4>Sứ mệnh</h4>
                        <p>Cung cấp sản phẩm và dịch vụ chất lượng vượt trội, mang lại giá trị thực sự cho khách hàng, đồng thời đóng góp tích cực vào sự phát triển của cộng đồng.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="vm-card">
                        <div class="vm-icon gold"><i class="bi bi-stars"></i></div>
                        <h4>Triết lý kinh doanh</h4>
                        <p>Khách hàng là trung tâm. Mọi quyết định và hành động của chúng tôi đều hướng đến việc tạo ra giá trị tốt nhất và trải nghiệm hài lòng cho khách hàng.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Section 3: Giá trị cốt lõi ─────────── --}}
    <section class="values-section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-label"><i class="bi bi-gem me-1"></i>Cốt lõi</span>
                <h2 class="section-title">Giá trị cốt lõi</h2>
                <p class="section-lead mx-auto">
                    6 giá trị nền tảng định hình văn hoá doanh nghiệp và mọi hoạt động của chúng tôi.
                </p>
            </div>
            <div class="row g-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="value-card c1">
                        <div class="value-icon"><i class="bi bi-shield-check"></i></div>
                        <h5>Uy tín & Trung thực</h5>
                        <p>Cam kết minh bạch trong từng giao dịch, xây dựng niềm tin bền vững với khách hàng và đối tác.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="value-card c2">
                        <div class="value-icon"><i class="bi bi-patch-check"></i></div>
                        <h5>Chất lượng vượt trội</h5>
                        <p>Không ngừng cải tiến sản phẩm và dịch vụ, đảm bảo đáp ứng và vượt qua kỳ vọng của khách hàng.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="value-card c3">
                        <div class="value-icon"><i class="bi bi-people-fill"></i></div>
                        <h5>Khách hàng là trọng tâm</h5>
                        <p>Lắng nghe, thấu hiểu và đặt lợi ích của khách hàng lên hàng đầu trong mọi quyết định.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="value-card c4">
                        <div class="value-icon"><i class="bi bi-lightbulb"></i></div>
                        <h5>Đổi mới & Sáng tạo</h5>
                        <p>Không ngừng học hỏi, đổi mới tư duy và ứng dụng công nghệ để mang lại giải pháp tốt nhất.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="value-card c5">
                        <div class="value-icon"><i class="bi bi-hand-thumbs-up"></i></div>
                        <h5>Tận tâm & Trách nhiệm</h5>
                        <p>Đội ngũ luôn tận tâm, chịu trách nhiệm với công việc và cam kết hoàn thành mọi nhiệm vụ xuất sắc.</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="value-card c6">
                        <div class="value-icon"><i class="bi bi-tree"></i></div>
                        <h5>Phát triển bền vững</h5>
                        <p>Kinh doanh có trách nhiệm với xã hội và môi trường, hướng tới sự phát triển bền vững lâu dài.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Section 4: Số liệu nổi bật ─────────── --}}
    <section class="stats-section">
        <div class="container">
            <div class="row g-4 align-items-center justify-content-center">
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <div class="stat-item__num">5+</div>
                        <div class="stat-item__lbl">Năm kinh nghiệm</div>
                    </div>
                </div>
                <div class="col-auto d-none d-md-block stat-sep"></div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <div class="stat-item__num">10K+</div>
                        <div class="stat-item__lbl">Khách hàng tin tưởng</div>
                    </div>
                </div>
                <div class="col-auto d-none d-md-block stat-sep"></div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <div class="stat-item__num">50K+</div>
                        <div class="stat-item__lbl">Đơn hàng hoàn thành</div>
                    </div>
                </div>
                <div class="col-auto d-none d-md-block stat-sep"></div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <div class="stat-item__num">99%</div>
                        <div class="stat-item__lbl">Khách hàng hài lòng</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Section 5: Liên hệ ───────────────────── --}}
    <section class="contact-section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-label"><i class="bi bi-envelope me-1"></i>Liên hệ</span>
                <h2 class="section-title">Kết nối với chúng tôi</h2>
                <p class="section-lead mx-auto">
                    Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn. Hãy liên hệ ngay để được tư vấn miễn phí.
                </p>
            </div>
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="contact-info-card">
                        <h5><i class="bi bi-info-circle me-2 text-primary"></i>Thông tin liên hệ</h5>
                        @if(!empty($settings['address']?->value))
                        <div class="contact-row">
                            <i class="bi bi-geo-alt-fill"></i>
                            <div>
                                <div class="fw-600 small text-muted mb-1">Địa chỉ</div>
                                <div>{{ $settings['address']->value }}</div>
                            </div>
                        </div>
                        @endif
                        @if(!empty($settings['hotline']?->value))
                        <div class="contact-row">
                            <i class="bi bi-telephone-fill"></i>
                            <div>
                                <div class="fw-600 small text-muted mb-1">Hotline</div>
                                <a href="tel:{{ $settings['hotline']->value }}" class="text-decoration-none fw-700 text-primary">
                                    {{ $settings['hotline']->value }}
                                </a>
                            </div>
                        </div>
                        @endif
                        @if(!empty($settings['email']?->value))
                        <div class="contact-row">
                            <i class="bi bi-envelope-fill"></i>
                            <div>
                                <div class="fw-600 small text-muted mb-1">Email</div>
                                <a href="mailto:{{ $settings['email']->value }}" class="text-decoration-none text-primary">
                                    {{ $settings['email']->value }}
                                </a>
                            </div>
                        </div>
                        @endif
                        @if(!empty($settings['tax_number']?->value))
                        <div class="contact-row">
                            <i class="bi bi-file-earmark-text"></i>
                            <div>
                                <div class="fw-600 small text-muted mb-1">Mã số thuế</div>
                                <div>{{ $settings['tax_number']->value }}</div>
                            </div>
                        </div>
                        @endif
                        <div class="contact-row">
                            <i class="bi bi-clock-fill"></i>
                            <div>
                                <div class="fw-600 small text-muted mb-1">Giờ làm việc</div>
                                <div>Thứ 2 – Thứ 7: 8:00 – 18:00</div>
                                <div class="text-muted small">Chủ nhật: 8:00 – 12:00</div>
                            </div>
                        </div>
                        <div class="mt-4 d-flex gap-2 flex-wrap">
                            <a href="{{ route('pages.contact') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-send me-1"></i> Gửi tin nhắn
                            </a>
                            @if(!empty($settings['hotline']?->value))
                            <a href="tel:{{ $settings['hotline']->value }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-telephone me-1"></i> Gọi ngay
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="contact-map">
                        @if(!empty($settings['address']?->value))
                        <iframe
                            src="https://maps.google.com/maps?q={{ urlencode($settings['address']->value) }}&output=embed"
                            allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            title="Bản đồ {{ $settings['brand_name']?->value ?? '' }}">
                        </iframe>
                        @else
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted" style="min-height:280px;">
                            <div class="text-center">
                                <i class="bi bi-map" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
                                <p class="mb-0 small">Chưa cấu hình địa chỉ</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
