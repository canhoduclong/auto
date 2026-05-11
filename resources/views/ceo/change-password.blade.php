@extends('layouts.ceo')

@section('title', 'Đổi mật khẩu')
@section('subtitle', 'Thay đổi mật khẩu tài khoản CEO')

@push('styles')
<style>
    .change-password-card {
        max-width: 480px;
        margin: 40px auto;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 24px #0ea5e91a;
        padding: 32px 28px;
    }
    .change-password-title { font-size: 1.3rem; font-weight: 800; color: #0ea5e9; margin-bottom: 18px; }
    .form-label { font-weight: 600; color: #0f172a; }
    .form-control { border-radius: 10px; border: 1.5px solid #e0e7ef; }
    .form-control:focus { border-color: #0ea5e9; box-shadow: 0 0 0 2px #bae6fd55; }
    .profile-error { color: #dc2626; font-size: 0.93rem; margin-top: 2px; display: block; }
    .btn-primary { background: linear-gradient(135deg, #0ea5e9, #14b8a6); border: none; border-radius: 12px; font-weight: 700; min-width: 140px; }
    .btn-primary:hover { background: linear-gradient(135deg, #14b8a6, #0ea5e9); }
</style>
@endpush

@section('content')
<div class="change-password-card">
    <div class="change-password-title">Đổi mật khẩu tài khoản</div>
    <form method="POST" action="{{ route('ceo.password.update') }}">
        @csrf
        @php $hasPassword = !empty($user->password); @endphp
        @if($hasPassword)
        <div class="mb-3">
            <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
            @error('current_password')
                <span class="profile-error">{{ $message }}</span>
            @enderror
        </div>
        @endif
        <div class="mb-3">
            <label for="password" class="form-label">Mật khẩu mới</label>
            <input type="password" class="form-control" id="password" name="password" required>
            @error('password')
                <span class="profile-error">{{ $message }}</span>
            @enderror
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Xác nhận mật khẩu mới</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
    </form>
</div>
@endsection
