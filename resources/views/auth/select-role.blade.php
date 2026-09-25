@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-lg-5">
                    <div class="mb-4">
                        <h2 class="mb-2">Chon giao dien lam viec</h2>
                        <p class="text-muted mb-0">
                            Tai khoan {{ $user->name }} dang co nhieu layout hop le. Chon layout can vao ngay bay gio,
                            va co the dat lam mac dinh cho cac lan dang nhap sau.
                        </p>
                    </div>

                    @if(session('warning'))
                        <div class="alert alert-warning">{{ session('warning') }}</div>
                    @endif

                    <form action="{{ route('layout-selection.store') }}" method="POST">
                        @csrf

                        <div class="vstack gap-3 mb-4">
                            @foreach($availableWorkspaces as $workspace)
                                <label class="border rounded-3 p-3 d-flex gap-3 align-items-start cursor-pointer">
                                    <input
                                        type="radio"
                                        name="workspace"
                                        value="{{ $workspace['key'] }}"
                                        class="form-check-input mt-1"
                                        {{ $currentWorkspaceKey === $workspace['key'] ? 'checked' : '' }}
                                    >
                                    <span>
                                        <span class="d-block fw-semibold">{{ $workspace['label'] }}</span>
                                        @if($workspace['description'] !== '')
                                            <span class="d-block text-muted small">{{ $workspace['description'] }}</span>
                                        @endif
                                        <span class="d-block text-muted small mt-1">
                                            Vai tro hop le: {{ collect($workspace['matched_roles'])->map(fn ($role) => ucfirst($role))->join(', ') }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('workspace')
                            <div class="text-danger small mb-3">{{ $message }}</div>
                        @enderror

                        <div class="form-check mb-4">
                            <input
                                type="checkbox"
                                name="remember_default"
                                id="remember_default"
                                value="1"
                                class="form-check-input"
                                {{ old('remember_default', !empty($user->default_workspace)) ? 'checked' : '' }}
                            >
                            <label for="remember_default" class="form-check-label">
                                Dat lam layout mac dinh cho cac lan dang nhap tiep theo
                            </label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Vao giao dien da chon</button>
                            <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">Quan ly trong profile</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection