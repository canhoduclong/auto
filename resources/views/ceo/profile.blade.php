
@extends('layouts.ceo')

@php
    $profileFields = [
        old('name', $customer->name ?? $user->name),
        old('email', $customer->email ?? $user->email),
        old('phone', $customer->phone ?? ''),
        old('dob', $customer->dob ?? ''),
        old('gender', $customer->gender ?? ''),
        old('note', $customer->note ?? ''),
        $user->avatar,
    ];
    $completedFields = collect($profileFields)->filter(fn ($value) => filled($value))->count();
    $completionRate = (int) round(($completedFields / count($profileFields)) * 100);
    $genderLabels = [ 'male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác', ];
    $currentGender = old('gender', $customer->gender ?? '');
    $avatarUrl = $user->avatar ? asset($user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'CEO') . '&background=0F172A&color=F8FAFC&size=240';
    $joinedDate = optional($user->created_at)->format('d/m/Y');
@endphp

@section('title', 'CEO Hồ sơ cá nhân')
@section('subtitle', 'Cập nhật thông tin CEO')

@push('styles')
<style>
    .profile-page {
        --profile-ink: #0f172a;
        --profile-muted: #64748b;
        --profile-line: rgba(148, 163, 184, 0.22);
        --profile-surface: #fff;
        --profile-accent: #0ea5e9;
        --profile-accent-soft: #e0f2fe;
        --profile-warm: #f59e0b;
        background:
            radial-gradient(circle at 10% 10%, #0ea5e91a 0, transparent 40%),
            radial-gradient(circle at 90% 10%, #f59e0b1a 0, transparent 40%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        padding: 34px 0 48px;
    }
    .profile-shell { max-width: 1200px; 
    overflow: hidden;}
    .profile-hero {
        position: relative;
        overflow: hidden;
        margin-bottom: 40px;
        border: 1.5px solid #e0e7ef;
        border-radius: 28px;
        background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%);
        box-shadow: 0 10px 32px rgba(14, 165, 233, 0.10);
        color: #f8fafc;
    }
    .profile-hero__content { position: relative; z-index: 1; padding: 32px 36px; }
    .profile-eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 7px 16px;
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 999px;
        background: rgba(255,255,255,0.10);
        font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.08);
    }
    .profile-hero h1 { margin: 14px 0 10px; font-size: clamp(2rem, 4vw, 2.7rem); font-weight: 900; letter-spacing: -0.03em; line-height: 1.12; }
    .profile-hero p { max-width: 540px; margin: 0; color: #e0f2fe; font-size: 15px; line-height: 1.6; }
    .profile-card {
        background: var(--profile-surface);
        border-radius: 22px;
        box-shadow: 0 4px 24px rgba(14, 165, 233, 0.07);
        border: 1.5px solid #e0e7ef;
        padding: 28px 24px;
        margin-bottom: 18px;
    }
    .profile-sidebar .profile-avatar-wrap {
        margin-bottom: 18px;
        position: relative;
        display: flex;
        justify-content: center;
    }
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 6px 24px #0ea5e91a;
        background: #e0f2fe;
    }
    .profile-avatar-badge {
        position: absolute;
        right: 8px;
        bottom: 8px;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: linear-gradient(135deg, #0ea5e9, #14b8a6);
        color: #fff;
        box-shadow: 0 4px 12px #0ea5e91a;
        font-size: 1.3rem;
    }
    .profile-name { font-size: 1.4rem; font-weight: 800; color: var(--profile-ink); margin-bottom: 4px; }
    .profile-role { color: var(--profile-accent); font-weight: 700; }
    .profile-meter {
        margin-top: 18px;
        padding: 14px 16px;
        border-radius: 16px;
        background: linear-gradient(180deg, #f8fafc, #e0f2fe);
        border: 1px solid #bae6fd;
    }
    .profile-meter__row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .profile-meter__title { font-size: 1rem; font-weight: 800; color: var(--profile-ink); }
    .profile-meter__value { color: var(--profile-accent); font-weight: 800; }
    .profile-progress { height: 8px; margin-top: 10px; border-radius: 999px; background: #e0e7ef; overflow: hidden; }
    .profile-progress > span { display: block; width: {{ $completionRate }}%; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #0ea5e9, #f59e0b); }
    .profile-detail-list { display: grid; gap: 10px; margin-top: 18px; }
    .profile-detail-item { display: flex; align-items: flex-start; gap: 10px; background: #f1f5f9; border-radius: 10px; padding: 10px 14px; }
    .profile-detail-icon { color: #0ea5e9; font-size: 1.2rem; margin-top: 2px; }
    .profile-section-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
    .profile-section-title { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--profile-ink); }
    .profile-section-desc { color: var(--profile-muted); font-size: 0.98rem; }
    .profile-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; background: #e0f2fe; color: #0ea5e9; font-weight: 700; font-size: 13px; }
    .profile-group-label { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #0ea5e9; margin-bottom: 10px; font-size: 1.08rem; }
    .profile-field label { font-weight: 600; color: var(--profile-ink); }
    .profile-field input, .profile-field select, .profile-field textarea { border-radius: 10px; border: 1.5px solid #e0e7ef; }
    .profile-field input:focus, .profile-field select:focus, .profile-field textarea:focus { border-color: #0ea5e9; box-shadow: 0 0 0 2px #bae6fd55; }
    .profile-error { color: #dc2626; font-size: 0.93rem; margin-top: 2px; display: block; }
    .profile-helper { color: var(--profile-muted); font-size: 0.93rem; margin-top: 2px; display: block; }
    .profile-submit {
        min-width: 180px;
        min-height: 46px;
        border: 0;
        border-radius: 14px;
        background: linear-gradient(135deg, #0ea5e9, #14b8a6);
        color: #fff;
        font-weight: 800;
        letter-spacing: 0.02em;
        box-shadow: 0 8px 24px #0ea5e91a;
        font-size: 1.1rem;
        transition: background 0.18s;
    }
    .profile-submit:hover {
        background: linear-gradient(135deg, #14b8a6, #0ea5e9);
        color: #fff;
    }
    .profile-toast {
        margin-bottom: 16px;
        padding: 12px 14px;
        border: 1px solid #0ea5e9;
        border-radius: 14px;
        background: #e0f2fe;
        color: #0ea5e9;
        font-weight: 700;
        font-size: 0.98rem;
    }
    @media (max-width: 991.98px) {
        .profile-page { padding: 18px 0 24px; }
        .profile-shell { max-width: 100%; }
        .profile-hero__content { padding: 18px 10px; }
        .profile-card { padding: 16px 8px; }
    }
</style>
@endpush

@section('content')
<section class="profile-page">
    <div class="container-fuild profile-shell">
        <div class="profile-hero">
            <div class="profile-hero__content">
                <span class="profile-eyebrow">
                    <i class="bi bi-person-vcard"></i>
                    Hồ sơ CEO
                </span>
                <div class="row align-items-end g-4 mt-1">
                    <div class="col-lg-7">
                        <h1>Hồ sơ CEO</h1>
                        <p>Cập nhật nhanh thông tin liên hệ, ảnh đại diện và ghi chú cần thiết.</p>
                    </div>
                    <div class="col-lg-5">
                        <div class="profile-stat-grid">
                            <div class="profile-stat">
                                <span class="profile-stat__label">Hoàn thiện hồ sơ</span>
                                <span class="profile-stat__value">{{ $completionRate }}%</span>
                            </div>
                            <div class="profile-stat">
                                <span class="profile-stat__label">Ngày tham gia</span>
                                <span class="profile-stat__value">{{ $joinedDate ?: 'Mới' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="profile-content">
            @if(session('success'))
                <div class="profile-toast">
                    <i class="bi bi-check2-circle me-2"></i>{{ session('success') }}
                </div>
            @endif
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-4">
                    <aside class="profile-card profile-sidebar text-center">
                        <div class="profile-avatar-wrap">
                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="profile-avatar" id="profileHeroAvatar">
                            <span class="profile-avatar-badge">
                                <i class="bi bi-shield-check"></i>
                            </span>
                        </div>
                        <h2 class="profile-name">{{ old('name', $customer->name ?? $user->name) }}</h2>
                        <p class="profile-role mb-3">Tài khoản CEO</p>
                        <div class="profile-meter text-start">
                            <div class="profile-meter__row">
                                <h3 class="profile-meter__title">Mức độ hoàn thiện</h3>
                                <span class="profile-meter__value">{{ $completedFields }}/{{ count($profileFields) }}</span>
                            </div>
                            <div class="profile-progress" aria-hidden="true">
                                <span></span>
                            </div>
                            <p class="profile-muted mt-2 mb-0">Bổ sung các mục còn thiếu để hồ sơ đầy đủ hơn.</p>
                        </div>
                        <div class="profile-detail-list text-start">
                            <div class="profile-detail-item">
                                <span class="profile-detail-icon"><i class="bi bi-envelope"></i></span>
                                <div>
                                    <strong>Email</strong>
                                    <span>{{ old('email', $customer->email ?? $user->email ?? 'Chưa cập nhật') }}</span>
                                </div>
                            </div>
                            <div class="profile-detail-item">
                                <span class="profile-detail-icon"><i class="bi bi-telephone"></i></span>
                                <div>
                                    <strong>Số điện thoại</strong>
                                    <span>{{ old('phone', $customer->phone ?? 'Chưa cập nhật') }}</span>
                                </div>
                            </div>
                            <div class="profile-detail-item">
                                <span class="profile-detail-icon"><i class="bi bi-calendar-event"></i></span>
                                <div>
                                    <strong>Ngày sinh</strong>
                                    <span>{{ old('dob', $customer->dob ?? '') ? \Carbon\Carbon::parse(old('dob', $customer->dob))->format('d/m/Y') : 'Chưa cập nhật' }}</span>
                                </div>
                            </div>
                            <div class="profile-detail-item">
                                <span class="profile-detail-icon"><i class="bi bi-person"></i></span>
                                <div>
                                    <strong>Giới tính</strong>
                                    <span>{{ $genderLabels[$currentGender] ?? 'Chưa cập nhật' }}</span>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
                <div class="col-lg-8">
                    <div class="profile-card profile-form-card">
                        <div class="profile-section-head">
                            <div>
                                <h2 class="profile-section-title">Cập nhật thông tin CEO</h2>
                                <p class="profile-section-desc">Biểu mẫu ngắn gọn, chia nhóm rõ ràng để cập nhật nhanh.</p>
                            </div>
                            <span class="profile-pill">
                                <i class="bi bi-lock"></i>
                                Riêng tư
                            </span>
                        </div>
                        <form action="{{ route('ceo.profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="profile-group">
                                <span class="profile-group-label">
                                    <i class="bi bi-person-lines-fill"></i>
                                    Thông tin cơ bản
                                </span>
                                <div class="row">
                                    <div class="col-md-12 profile-field">
                                        <label for="name">Họ và tên</label>
                                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name ?? $customer->name ?? '') }}" placeholder="Nhập họ và tên của bạn">
                                        @error('name')
                                            <span class="profile-error">{{ $message }}</span>
                                        @else
                                            <span class="profile-helper">Tên hiển thị trên hồ sơ.</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 profile-field">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email ?? $customer->email ?? '') }}" placeholder="name@example.com">
                                        @error('email')
                                            <span class="profile-error">{{ $message }}</span>
                                        @else
                                            <span class="profile-helper">Dùng cho thông báo hệ thống.</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 profile-field">
                                        <label for="phone">Số điện thoại</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" placeholder="Nhập số điện thoại liên hệ">
                                        @error('phone')
                                            <span class="profile-error">{{ $message }}</span>
                                        @else
                                            <span class="profile-helper">Ưu tiên số liên hệ chính.</span>
                                        @enderror
                                    </div> 
                                </div>
                            </div>
                            <div class="profile-group">
                                <span class="profile-group-label">
                                    <i class="bi bi-sliders2"></i>
                                    Tùy chỉnh hồ sơ
                                </span>
                                <div class="row">
                                     <div class="col-md-6 profile-field">
                                        <label for="dob">Ngày sinh</label>
                                        <input type="date" class="form-control" id="dob" name="dob" value="{{ old('dob', $customer->dob ?? '') }}">
                                        @error('dob')
                                            <span class="profile-error">{{ $message }}</span>
                                        @else
                                            <span class="profile-helper">Không bắt buộc.</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 profile-field">
                                        <label for="gender">Giới tính</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Chọn giới tính</option>
                                            <option value="male" {{ $currentGender === 'male' ? 'selected' : '' }}>Nam</option>
                                            <option value="female" {{ $currentGender === 'female' ? 'selected' : '' }}>Nữ</option>
                                            <option value="other" {{ $currentGender === 'other' ? 'selected' : '' }}>Khác</option>
                                        </select>
                                        @error('gender')
                                            <span class="profile-error">{{ $message }}</span>
                                        @else
                                            <span class="profile-helper">Có thể bỏ trống nếu không cần.</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-12 mt-4 profile-field">
                                        <label for="avatar">Ảnh đại diện</label>
                                        <div class="profile-upload-box">
                                            <img src="{{ $avatarUrl }}" alt="Xem trước ảnh đại diện" class="profile-upload-preview" id="avatarPreview">
                                            <div class="profile-upload-copy w-100">
                                                <strong>Tải ảnh hồ sơ</strong>
                                                <span class="profile-helper mt-0">Ảnh vuông, tối đa 2MB.</span>
                                                <input type="file" class="form-control mt-3" id="avatar" name="avatar" accept="image/*">
                                            </div>
                                        </div>
                                        @error('avatar')
                                            <span class="profile-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="profile-group mb-0">
                                <span class="profile-group-label">
                                    <i class="bi bi-card-text"></i>
                                    Ghi chú CEO
                                </span>
                                <div class="profile-field mb-0">
                                    <label for="note">Ghi chú</label>
                                    <textarea class="form-control" id="note" name="note" rows="5" placeholder="Ví dụ: thông tin bổ sung, ghi chú riêng cho CEO...">{{ old('note', $customer->note ?? '') }}</textarea>
                                    @error('note')
                                        <span class="profile-error">{{ $message }}</span>
                                    @else
                                        <span class="profile-helper">Thông tin bổ sung nếu cần.</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="profile-actions mt-4">
                                <p class="profile-actions__note mb-2">Lưu để cập nhật hồ sơ CEO ngay.</p>
                                <button type="submit" class="btn profile-submit mb-2">
                                    <i class="bi bi-floppy me-2"></i>Lưu thay đổi
                                </button>
                                <div class="text-center mt-3">
                                    <a href="{{ route('ceo.password.change') }}" class="btn btn-outline-primary" style="border-radius:12px;min-width:160px;font-weight:700;">
                                        <i class="bi bi-key me-2"></i>Đổi mật khẩu
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatarPreview');
        const profileHeroAvatar = document.getElementById('profileHeroAvatar');
        if (!avatarInput || !avatarPreview || !profileHeroAvatar) { return; }
        avatarInput.addEventListener('change', function (event) {
            const [file] = event.target.files || [];
            if (!file) { return; }
            const reader = new FileReader();
            reader.onload = function (loadEvent) {
                const result = loadEvent.target?.result;
                if (typeof result === 'string') {
                    avatarPreview.src = result;
                    profileHeroAvatar.src = result;
                }
            };
            reader.readAsDataURL(file);
        });
    });
</script>
@endpush
