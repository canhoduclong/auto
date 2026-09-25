@extends('layouts.auth')
@section('title', __('auth.login_title'))

@push('styles')
<style>
    :root {
        --login-accent:      #0ea5e9;
        --login-accent-dark: #0369a1;
        --login-border:      #dbe3ef;
        --login-muted:       #64748b;
    }

    body {
        min-height: 100vh;
        background:
            radial-gradient(ellipse 120% 60% at 10% -5%, rgba(14,165,233,.18), transparent 50%),
            radial-gradient(ellipse 100% 55% at 90% 105%, rgba(56,189,248,.2), transparent 52%),
            linear-gradient(135deg, #0b1220, #113a5d);
    }

    /* ── Outer shell ── */
    .login-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 16px;
    }

    /* ── Card ── */
    .login-card {
        width: 100%;
        max-width: 440px;
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 28px 72px rgba(2,8,23,.38);
        padding: 40px 40px 32px;
        animation: loginFadeIn .45s ease-out;
    }

    /* ── Brand header ── */
    .login-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
    }

    .login-brand img {
        height: 52px;
        width: auto;
    }

    .login-brand-name {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: .2px;
    }

    .login-subtitle {
        text-align: center;
        color: var(--login-muted);
        font-size: 14px;
        margin-bottom: 28px;
    }

    /* ── Divider ── */
    .login-divider {
        border: 0;
        border-top: 1px solid #eef0f4;
        margin: 0 0 24px;
    }

    /* ── Form elements ── */
    .form-label {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .15px;
        margin-bottom: 6px;
        color: #1e293b;
    }

    .form-control {
        border: 1px solid var(--login-border);
        border-radius: 10px;
        height: 44px;
        padding: 10px 13px;
        background: #fff;
        font-size: 14px;
        transition: border-color .2s, box-shadow .2s;
    }

    .form-control:focus {
        border-color: var(--login-accent);
        box-shadow: 0 0 0 3px rgba(14,165,233,.14);
    }

    .input-group .form-control {
        border-right: 0;
        border-radius: 10px 0 0 10px;
    }

    .input-group-append .btn {
        border-radius: 0 10px 10px 0;
        border-color: var(--login-border);
        border-left: 0;
        color: #475569;
        font-size: 13px;
        background: #f8fafc;
    }

    .input-group-append .btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .form-check-label {
        font-size: 13px;
        color: #475569;
    }

    /* ── Submit button ── */
    .btn-login {
        height: 46px;
        border: 0;
        border-radius: 11px;
        font-weight: 700;
        font-size: 15px;
        letter-spacing: .2px;
        background: linear-gradient(135deg, var(--login-accent), var(--login-accent-dark));
        box-shadow: 0 8px 22px rgba(14,165,233,.3);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .btn-login:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 28px rgba(14,165,233,.36);
    }

    .btn-google {
        height: 46px;
        border-radius: 11px;
        border: 1px solid #dbe3ef;
        background: #fff;
        color: #0f172a;
        font-weight: 700;
        font-size: 14px;
        letter-spacing: .2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .btn-google:hover {
        border-color: #cbd5e1;
        box-shadow: 0 8px 20px rgba(15,23,42,.09);
        transform: translateY(-1px);
        color: #0f172a;
        text-decoration: none;
    }

    .btn-google svg {
        width: 18px;
        height: 18px;
        display: block;
    }

    .login-or {
        margin: 16px 0;
        text-align: center;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .08em;
    }

    /* ── Bottom links ── */
    .login-links {
        margin-top: 16px;
        text-align: center;
        font-size: 13px;
        color: var(--login-muted);
    }

    .login-register {
        color: #0284c7;
        font-weight: 600;
        text-decoration: none;
    }

    .login-register:hover {
        color: var(--login-accent-dark);
        text-decoration: underline;
    }

    .login-copyright {
        margin-top: 20px;
        text-align: center;
        font-size: 12px;
        color: #94a3b8;
    }

    @keyframes loginFadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 575.98px) {
        .login-card { padding: 28px 22px 24px; }
    }
</style>
@endpush

@section('content')
<div class="login-shell">
    <div class="login-card">

        @php
            $loginLogoMediaId = \App\Models\Setting::get('logo');
            $loginLogoMedia   = $loginLogoMediaId ? \App\Models\Media::find($loginLogoMediaId) : null;
            $loginBrandName   = \App\Models\Setting::get('brand_name', __('auth.company'));
        @endphp

        <div class="login-brand">
            @if($loginLogoMedia)
                <img src="{{ asset('storage/' . $loginLogoMedia->file_path) }}" alt="{{ $loginBrandName }}">
            @else
                <img src="{{ asset('assets/images/logo.png') }}" alt="{{ $loginBrandName }}">
            @endif
            <span class="login-brand-name mt-2">ĐĂNG NHẬP </span>
        </div>
        <p class="login-subtitle">{{ __('auth.login_subtitle') }}</p> 
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <a class="btn btn-google w-100" href="{{ route('auth.google.redirect') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-1.5 3.9-5.5 3.9-3.3 0-6-2.7-6-6s2.7-6 6-6c1.9 0 3.1.8 3.8 1.5l2.6-2.5C16.8 3.5 14.6 2.6 12 2.6 6.9 2.6 2.8 6.7 2.8 11.8S6.9 21 12 21c6.9 0 9.2-4.8 9.2-7.3 0-.5 0-.9-.1-1.3H12z"/>
                <path fill="#34A853" d="M3.8 7.9l3.2 2.3c.9-2.4 2.8-4.1 5-4.1 1.9 0 3.1.8 3.8 1.5l2.6-2.5C16.8 3.5 14.6 2.6 12 2.6 8.4 2.6 5.3 4.7 3.8 7.9z" opacity=".0"/>
                <path fill="#FBBC05" d="M12 21c2.5 0 4.7-.8 6.2-2.2l-2.9-2.3c-.8.6-1.9 1-3.3 1-2.5 0-4.6-1.7-5.4-4l-3.3 2.6C4.9 18.7 8.2 21 12 21z"/>
                <path fill="#4285F4" d="M21.2 13.7c0-.5 0-.9-.1-1.3H12v3.9h5.5c-.3 1.5-1.2 2.8-2.6 3.7l2.9 2.3c1.7-1.6 3.4-4.3 3.4-8.6z"/>
            </svg>
            Đăng nhập với Google
        </a>

        <div class="login-or">HOẶC</div>

        <form method="POST" action="{{ url('/login') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label class="form-label" for="email">{{ __('auth.email') }}</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="you@example.com"
                    required
                    autofocus
                >
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">{{ __('auth.password') }}</label>
                <div class="input-group">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="••••••••"
                        required
                    >
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="togglePwd"
                            data-show="{{ __('auth.show_password') }}"
                            data-hide="{{ __('auth.hide_password') }}">{{ __('auth.show_password') }}</button>
                    </div>
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">{{ __('auth.remember') }}</label>
            </div>

            <button class="btn btn-primary btn-login w-100">{{ __('common.actions.login') }}</button>
        </form> 

        <div class="login-copyright">
            © {{ date('Y') }} {{ $loginBrandName }}
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    const toggleButton = document.getElementById('togglePwd');
    const passwordInput = document.getElementById('password');

    if (toggleButton && passwordInput) {
        toggleButton.addEventListener('click', function () {
            const showText = this.dataset.show;
            const hideText = this.dataset.hide;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.textContent = hideText;
            } else {
                passwordInput.type = 'password';
                this.textContent = showText;
            }
        });
    }
</script>
@endpush
