@extends('layouts.site')

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

    $completedFields = collect($profileFields)
        ->filter(fn ($value) => filled($value))
        ->count();

    $completionRate = (int) round(($completedFields / count($profileFields)) * 100);

    $genderLabels = [
        'male' => 'Nam',
        'female' => 'Nữ',
        'other' => 'Khác',
    ];

    $currentGender = old('gender', $customer->gender ?? '');
    $avatarUrl = $user->avatar ? asset($user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'User') . '&background=0F172A&color=F8FAFC&size=240';
    $joinedDate = optional($user->created_at)->format('d/m/Y');
@endphp

@push('styles')
<style>
    .profile-page {
        --profile-ink: #0f172a;
        --profile-muted: #64748b;
        --profile-line: rgba(148, 163, 184, 0.22);
        --profile-surface: rgba(255, 255, 255, 0.94);
        --profile-accent: #0f766e;
        --profile-accent-soft: #ccfbf1;
        --profile-warm: #f59e0b;
        background:
            radial-gradient(circle at top left, rgba(20, 184, 166, 0.14), transparent 28%),
            radial-gradient(circle at top right, rgba(245, 158, 11, 0.16), transparent 22%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        padding: 34px 0 48px;
    }

    .profile-shell {
        max-width: 1140px;
    }

    .profile-hero {
        position: relative;
        overflow: hidden;
        margin-bottom: 40px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 22px;
        background: linear-gradient(135deg, rgba(134, 56, 8, 0.96), rgba(179, 91, 38, 0.86));
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.14);
        color: #f8fafc;
    }

    .profile-hero::before,
    .profile-hero::after {
        content: "";
        position: absolute;
        border-radius: 999px;
        pointer-events: none;
    }

    .profile-hero::before {
        inset: -120px auto auto -80px;
        width: 220px;
        height: 220px;
        background: rgba(45, 212, 191, 0.14);
    }

    .profile-hero::after {
        right: -90px;
        bottom: -140px;
        width: 240px;
        height: 240px;
        background: rgba(251, 191, 36, 0.1);
    }

    .profile-hero__content {
        position: relative;
        z-index: 1;
        padding: 24px 26px;
    }

    .profile-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .profile-hero h1 {
        margin: 12px 0 8px;
        font-size: clamp(1.65rem, 3vw, 2.45rem);
        font-weight: 900;
        letter-spacing: -0.03em;
        line-height: 1.12;
    }

    .profile-hero p {
        max-width: 560px;
        margin: 0;
        color: rgba(248, 250, 252, 0.78);
        font-size: 14px;
        line-height: 1.6;
    }

    .profile-stat-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .profile-stat {
        padding: 14px 16px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.09);
        border: 1px solid rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(8px);
    }

    .profile-stat__label {
        display: block;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: rgba(248, 250, 252, 0.62);
        margin-bottom: 4px;
    }

    .profile-stat__value {
        font-size: 1.1rem;
        font-weight: 800;
        color: #ffffff;
    }

    .profile-content {
        margin-top: -30px;
        position: relative;
        z-index: 2;
    }

    .profile-card {
        border: 1px solid var(--profile-line);
        border-radius: 22px;
        background: var(--profile-surface);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
    }

    .profile-sidebar,
    .profile-form-card {
        padding: 22px;
        height: 100%;
    }

    .profile-avatar-wrap {
        position: relative;
        width: 110px;
        height: 110px;
        margin: 0 auto 16px;
    }

    .profile-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 24px;
        border: 3px solid rgba(255, 255, 255, 0.92);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
        background: #e2e8f0;
    }

    .profile-avatar-badge {
        position: absolute;
        right: -6px;
        bottom: -6px;
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: linear-gradient(135deg, #0f766e, #14b8a6);
        color: #fff;
        box-shadow: 0 10px 20px rgba(15, 118, 110, 0.22);
    }

    .profile-name {
        margin-bottom: 4px;
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--profile-ink);
    }

    .profile-role,
    .profile-muted {
        color: var(--profile-muted);
    }

    .profile-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #ecfeff;
        color: #115e59;
        font-weight: 700;
        font-size: 12px;
    }

    .profile-meter {
        margin-top: 20px;
        padding: 14px 16px;
        border-radius: 18px;
        background: linear-gradient(180deg, #f8fafc, #eef6f6);
        border: 1px solid rgba(15, 118, 110, 0.12);
    }

    .profile-meter__row,
    .profile-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .profile-meter__title,
    .profile-section-title {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 800;
        color: var(--profile-ink);
    }

    .profile-meter__value {
        color: var(--profile-accent);
        font-weight: 800;
    }

    .profile-progress {
        height: 8px;
        margin-top: 10px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.18);
        overflow: hidden;
    }

    .profile-progress > span {
        display: block;
        width: {{ $completionRate }}%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #14b8a6, #f59e0b);
    }

    .profile-detail-list {
        display: grid;
        gap: 10px;
        margin-top: 18px;
    }

    .profile-detail-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    .profile-detail-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .profile-detail-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #f8fafc;
        color: var(--profile-accent);
        font-size: 0.95rem;
    }

    .profile-detail-item strong {
        display: block;
        margin-bottom: 4px;
        color: var(--profile-ink);
        font-size: 0.95rem;
    }

    .profile-detail-item span {
        color: var(--profile-muted);
        line-height: 1.55;
    }

    .profile-form-card {
        position: relative;
    }

    .profile-form-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        border-radius: 22px 22px 0 0;
        background: linear-gradient(90deg, #99540b, #d39c18, #f59e0b);
    }

    .profile-form-card .profile-section-head {
        margin-bottom: 18px;
        padding-top: 8px;
    }

    .profile-section-desc {
        margin: 6px 0 0;
        color: var(--profile-muted);
        line-height: 1.55;
        font-size: 0.92rem;
    }

    .profile-group {
        margin-bottom: 22px;
    }

    .profile-group-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        color: var(--profile-ink);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .profile-group-label i {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: var(--profile-accent-soft);
        color: var(--profile-accent);
    }

    .profile-field {
        margin-bottom: 16px;
    }

    .profile-field label {
        margin-bottom: 6px;
        color: var(--profile-ink);
        font-weight: 700;
        font-size: 0.95rem;
    }

    .profile-field .form-control,
    .profile-field .form-select {
        min-height: 46px;
        border: 1px solid rgba(148, 163, 184, 0.32);
        border-radius: 14px;
        background: #ffffff;
        box-shadow: none;
        padding: 0.72rem 0.92rem;
        color: var(--profile-ink);
    }

    .profile-field textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    .profile-field .form-control:focus,
    .profile-field .form-select:focus {
        border-color: rgba(20, 184, 166, 0.8);
        box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.14);
    }

    .profile-helper,
    .profile-error {
        display: block;
        margin-top: 6px;
        font-size: 0.83rem;
    }

    .profile-helper {
        color: var(--profile-muted);
    }

    .profile-error {
        color: #dc2626;
    }

    .profile-upload-box {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        border: 1px dashed rgba(15, 118, 110, 0.3);
        border-radius: 18px;
        background: linear-gradient(180deg, #fbffff, #f5fbfb);
    }

    .profile-upload-preview {
        width: 72px;
        height: 72px;
        border-radius: 18px;
        object-fit: cover;
        background: #e2e8f0;
        flex-shrink: 0;
    }

    .profile-upload-copy strong {
        display: block;
        color: var(--profile-ink);
        margin-bottom: 4px;
    }

    .profile-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-top: 4px;
    }

    .profile-actions__note {
        max-width: 360px;
        color: var(--profile-muted);
        font-size: 0.86rem;
        line-height: 1.55;
    }

    .profile-submit {
        min-width: 180px;
        min-height: 46px;
        border: 0;
        border-radius: 14px;
        background: linear-gradient(135deg, #815106, #c15405);
        color: #fff;
        font-weight: 800;
        letter-spacing: 0.02em;
        box-shadow: 0 12px 24px rgba(15, 118, 110, 0.18);
    }

    .profile-submit:hover {
        background: linear-gradient(135deg, #115e59, #0f766e);
        color: #fff;
    }

    .profile-toast {
        margin-bottom: 16px;
        padding: 12px 14px;
        border: 1px solid rgba(20, 184, 166, 0.22);
        border-radius: 14px;
        background: rgba(240, 253, 250, 0.96);
        color: #115e59;
        font-weight: 700;
        font-size: 0.9rem;
    }

    @media (max-width: 991.98px) {
        .profile-page {
            padding: 28px 0 40px;
        }

        .profile-content {
            margin-top: 18px;
        }

        .profile-sidebar,
        .profile-form-card {
            padding: 18px;
        }
    }

    @media (max-width: 767.98px) {
        .profile-hero__content {
            padding: 18px;
        }

        .profile-stat-grid {
            grid-template-columns: 1fr;
        }

        .profile-upload-box,
        .profile-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .profile-submit {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<section class="profile-page">
    <div class="container profile-shell">
        <div class="profile-hero">
            <div class="profile-hero__content">
                <span class="profile-eyebrow">
                    <i class="bi bi-person-vcard"></i>
                    Hồ sơ cá nhân
                </span>

                <div class="row align-items-end g-4 mt-1">
                    <div class="col-lg-7">
                        <h1>Hồ sơ cá nhân</h1>
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
                        <p class="profile-role mb-3">Tài khoản khách hàng</p>

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
                                <h2 class="profile-section-title">Cập nhật thông tin cá nhân</h2>
                                <p class="profile-section-desc">Biểu mẫu ngắn gọn, chia nhóm rõ ràng để cập nhật nhanh.</p>
                            </div>
                            <span class="profile-pill">
                                <i class="bi bi-lock"></i>
                                Riêng tư
                            </span>
                        </div>

                        <form action="{{ route('pages.update_profile') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="profile-group">
                                <span class="profile-group-label">
                                    <i class="bi bi-person-lines-fill"></i>
                                    Thông tin cơ bản
                                </span>

                                <div class="row">
                                    <div class="col-md-6 profile-field">
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
                                            <span class="profile-helper">Dùng cho thông báo đơn hàng.</span>
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

                                    <div class="col-md-6 profile-field">
                                        <label for="dob">Ngày sinh</label>
                                        <input type="date" class="form-control" id="dob" name="dob" value="{{ old('dob', $customer->dob ?? '') }}">
                                        @error('dob')
                                            <span class="profile-error">{{ $message }}</span>
                                        @else
                                            <span class="profile-helper">Không bắt buộc.</span>
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

                                    <div class="col-md-6 profile-field">
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
                                    Ghi chú cá nhân
                                </span>

                                <div class="profile-field mb-0">
                                    <label for="note">Ghi chú</label>
                                    <textarea class="form-control" id="note" name="note" rows="5" placeholder="Ví dụ: khung giờ nhận cuộc gọi thuận tiện, yêu cầu xuất hóa đơn, ghi chú liên hệ...">{{ old('note', $customer->note ?? '') }}</textarea>
                                    @error('note')
                                        <span class="profile-error">{{ $message }}</span>
                                    @else
                                        <span class="profile-helper">Thông tin bổ sung nếu cần.</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="profile-actions mt-4">
                                <p class="profile-actions__note mb-0">Lưu để cập nhật hồ sơ ngay.</p>
                                <button type="submit" class="btn profile-submit">
                                    <i class="bi bi-floppy me-2"></i>Lưu thay đổi
                                </button>
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

        if (!avatarInput || !avatarPreview || !profileHeroAvatar) {
            return;
        }

        avatarInput.addEventListener('change', function (event) {
            const [file] = event.target.files || [];

            if (!file) {
                return;
            }

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
