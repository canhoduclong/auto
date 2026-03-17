@extends('layouts.auth')
@section('title', __('auth.login_title'))

@section('content')
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="card shadow-lg" style="max-width: 420px; width:100%;">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" style="height:42px">
                <h5 class="mt-2 mb-0">{{ __('auth.login_heading') }}</h5>
                <small class="text-muted">{{ __('auth.login_subtitle') }}</small>
            </div>

            {{-- Hiển thị lỗi --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label">{{ __('auth.email') }}</label>
                    <input
                        type="email"
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
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">{{ __('auth.password') }}</label>
                        {{-- <a href="#" class="small">Quên mật khẩu?</a> --}}
                    </div>
                    <div class="input-group">
                        <input
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="••••••••"
                            required
                            id="password"
                        >
                        <button class="btn btn-outline-secondary" type="button" id="togglePwd" data-show="{{ __('auth.show_password') }}" data-hide="{{ __('auth.hide_password') }}">{{ __('auth.show_password') }}</button>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1"
                        {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">{{ __('auth.remember') }}</label>
                </div>

                <button class="btn btn-primary w-100">{{ __('common.actions.login') }}</button>

                <div class="text-center mt-3">
                    <a href="{{ url('/register') }}">{{ __('auth.register_link') }}</a>
                </div>

                 
            </form>
        </div>
        <div class="card-footer text-center small text-muted">
            © {{ date('Y') }} - {{ __('auth.company') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('togglePwd').addEventListener('click', function() {
    const input = document.getElementById('password');
    const showText = this.dataset.show;
    const hideText = this.dataset.hide;
    if (input.type === 'password') {
        input.type = 'text';
        this.textContent = hideText;
    } else {
        input.type = 'password';
        this.textContent = showText;
    }
});
</script>
@endpush
