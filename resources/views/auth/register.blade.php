@extends('layouts.auth')
@section('title', __('auth.register_title'))

@section('content')
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="card shadow-lg" style="max-width: 460px; width:100%;">
        <div class="card-body p-4">

            {{-- Logo & Slogan --}}
            <div class="text-center mb-4">
                @if(isset($logoMedia) && $logoMedia)
                    <img src="{{ asset('storage/' . $logoMedia->file_path) }}" alt="{{ $brandName ?? '' }}" style="height:52px;object-fit:contain;">
                @else
                    <img src="{{ asset('assets/images/logo.png') }}" alt="{{ $brandName ?? '' }}" style="height:52px;object-fit:contain;">
                @endif
                @if(!empty($brandName))
                    <h5 class="mt-2 mb-0 fw-bold">{{ $brandName }}</h5>
                @endif
                @if(!empty($slogan))
                    <small class="text-muted">{{ $slogan }}</small>
                @endif
                <div class="mt-2 text-secondary fw-semibold">Tạo tài khoản mới</div>
            </div>

            {{-- Lỗi --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ url('/register') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label">{{ __('auth.name') }}</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror"
                        placeholder="Tên của bạn"
                        required
                        autofocus
                    >
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('auth.email') }}</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="you@example.com"
                        required
                    >
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('auth.password') }}</label>
                    <div class="input-group">
                        <input
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                            required
                            id="reg_password"
                        >
                        <button class="btn btn-outline-secondary" type="button" id="togglePwd">Hiện</button>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-text">Tối thiểu 8 ký tự.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('auth.password_confirmation') }}</label>
                    <div class="input-group">
                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control"
                            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                            required
                            id="reg_password_confirmation"
                        >
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirm">Hiện</button>
                    </div>
                </div>

                <button class="btn btn-primary w-100">{{ __('common.actions.register') }}</button>

                <div class="text-center mt-3">
                    <a href="{{ url('/login') }}">{{ __('auth.has_account_login') }}</a>
                </div>
            </form>
        </div>
        <div class="card-footer text-center small text-muted">
            &copy; {{ date('Y') }} {{ $brandName ?? __('auth.company') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Ẩn';
    } else {
        input.type = 'password';
        btn.textContent = 'Hiện';
    }
}
document.getElementById('togglePwd').addEventListener('click', function () {
    togglePassword('reg_password', this);
});
document.getElementById('toggleConfirm').addEventListener('click', function () {
    togglePassword('reg_password_confirmation', this);
});
</script>
@endpush


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

            <form method="POST" action="{{ url('/register') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label">{{ __('auth.name') }}</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror"
                        placeholder="Tên của bạn"
                        required
                        autofocus
                    >
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('auth.email') }}</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="you@example.com"
                        required
                    >
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('auth.password') }}</label>
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

                <div class="mb-3">
                    <label class="form-label">{{ __('auth.password_confirmation') }}</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        placeholder="••••••••"
                        required
                        id="password_confirmation"
                    >
                </div>

                <button class="btn btn-primary w-100">{{ __('common.actions.register') }}</button>

                <div class="text-center mt-3">
                    <a href="{{ url('/login') }}">{{ __('auth.has_account_login') }}</a>
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
