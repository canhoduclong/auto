@extends('layouts.auth')
@section('title', 'Tài khoản đang chờ phân công')

@push('styles')
<style>
    :root {
        --ty-accent: #0ea5e9;
        --ty-accent-dark: #0369a1;
    }

    body {
        min-height: 100vh;
        background:
            radial-gradient(ellipse 120% 60% at 10% -5%, rgba(14,165,233,.18), transparent 50%),
            radial-gradient(ellipse 100% 55% at 90% 105%, rgba(56,189,248,.2), transparent 52%),
            linear-gradient(135deg, #0b1220, #113a5d);
    }

    .ty-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 16px;
    }

    .ty-card {
        width: 100%;
        max-width: 480px;
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 28px 72px rgba(2,8,23,.38);
        padding: 48px 40px 36px;
        text-align: center;
        animation: tyFadeIn .45s ease-out;
    }

    .ty-icon-wrap {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e0f2fe, #bae6fd);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
    }

    .ty-icon-wrap svg {
        width: 40px;
        height: 40px;
        color: var(--ty-accent-dark);
    }

    .ty-title {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 10px;
    }

    .ty-subtitle {
        font-size: 14px;
        color: #64748b;
        line-height: 1.7;
        margin-bottom: 32px;
    }

    .ty-info-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 28px;
        text-align: left;
    }

    .ty-info-box p {
        font-size: 13px;
        color: #475569;
        margin: 0;
        line-height: 1.8;
    }

    .ty-info-box strong {
        color: #1e293b;
    }

    .ty-user-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 50px;
        padding: 6px 16px;
        font-size: 13px;
        color: #334155;
        font-weight: 600;
        margin-bottom: 28px;
    }

    .ty-user-badge svg {
        width: 16px;
        height: 16px;
        color: var(--ty-accent);
    }

    .btn-ty-logout {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 46px;
        padding: 0 28px;
        border-radius: 11px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        color: #475569;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        transition: border-color .18s ease, color .18s ease, box-shadow .18s ease;
    }

    .btn-ty-logout:hover {
        border-color: #94a3b8;
        color: #0f172a;
        box-shadow: 0 4px 12px rgba(15,23,42,.08);
        text-decoration: none;
    }

    .ty-copyright {
        margin-top: 24px;
        font-size: 12px;
        color: #94a3b8;
    }

    @keyframes tyFadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 575.98px) {
        .ty-card { padding: 32px 22px 28px; }
    }
</style>
@endpush

@section('content')
<div class="ty-shell">
    <div class="ty-card">

        <div class="ty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>

        @php
            $loginLogoMediaId = \App\Models\Setting::get('logo');
            $loginLogoMedia   = $loginLogoMediaId ? \App\Models\Media::find($loginLogoMediaId) : null;
            $loginBrandName   = \App\Models\Setting::get('brand_name', config('app.name', 'Hệ thống'));
        @endphp

        <h1 class="ty-title">Tài khoản chưa được phân công</h1>

        <p class="ty-subtitle">
            Tài khoản của bạn đã được tạo thành công.<br>
            Vui lòng liên hệ quản trị viên để được phân công vào <strong>nhóm (team)</strong>
            hoặc <strong>kho (warehouse)</strong> trước khi sử dụng hệ thống.
        </p>

        <div class="ty-user-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
            {{ Auth::user()->name }} &mdash; {{ Auth::user()->email }}
        </div>

        <div class="ty-info-box">
            <p>
                <strong>Cần làm gì?</strong><br>
                Liên hệ quản trị viên hệ thống và cung cấp địa chỉ email của bạn để được phân quyền.<br>
                Sau khi được phân công, hãy <strong>đăng xuất và đăng nhập lại</strong> để truy cập hệ thống.
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ty-logout">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                </svg>
                Đăng xuất
            </button>
        </form>

        <div class="ty-copyright">
            © {{ date('Y') }} {{ $loginBrandName }}
        </div>

    </div>
</div>
@endsection
