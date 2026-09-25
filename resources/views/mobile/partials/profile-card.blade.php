@php
    $user = auth()->user();
    $roleLabel = $roleLabel ?? ($user?->job_title ?: $title ?? 'My App');
    $avatar = trim((string) ($user?->avatar ?: $user?->google_avatar ?: ''));
    if ($avatar !== '' && !str_starts_with($avatar, 'http://') && !str_starts_with($avatar, 'https://')) {
        $avatar = asset(ltrim($avatar, '/'));
    }
    $avatar = $avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($user?->name ?? 'User') . '&background=0B2540&color=F8FAFC&size=256&bold=true';
@endphp

<section class="m-profile-card" aria-label="Thông tin người dùng">
    <div class="m-profile-photo-wrap">
        <img class="m-profile-photo" src="{{ $avatar }}" alt="{{ $user?->name ?? 'User' }}">
    </div>
    <div class="m-profile-brand">HL</div>
    <div class="m-profile-info">
        <h1 class="m-profile-name">{{ $user?->name ?? 'User' }}</h1>
        <p class="m-profile-role">{{ $roleLabel }}</p>
        <div class="m-profile-line">
            <span class="m-profile-icon">✉</span>
            <span>{{ $user?->email ?: 'Chưa cập nhật email' }}</span>
        </div>
        <div class="m-profile-line">
            <span class="m-profile-icon">☎</span>
            <span>{{ $user?->phone ?: 'Chưa cập nhật SĐT' }}</span>
        </div>
    </div>
</section>
