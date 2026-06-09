@extends('layouts.app')

@section('content')
@php
    $websiteLayouts = collect($layoutCatalog)->filter(fn ($layout) => ($layout['platform'] ?? 'website') === 'website');
    $mobileLayouts = collect($layoutCatalog)->filter(fn ($layout) => ($layout['platform'] ?? 'website') === 'my_app');
@endphp

<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="mb-1"><i class="ph-layout me-2 text-primary"></i>Quản lý Layout</h2>
            <div class="text-muted">Gán giao diện Website và Mobile cho từng role trong hệ thống.</div>
        </div>
        <span class="badge bg-primary fs-6">{{ $roles->count() }} roles</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('layouts.update') }}">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:150px">Role</th>
                            <th style="min-width:260px">Website layout</th>
                            <th style="min-width:220px">Tên hiển thị Website</th>
                            <th style="min-width:260px">Mobile layout</th>
                            <th style="min-width:220px">Tên hiển thị Mobile</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            @php
                                $roleName = strtolower((string) $role->name);
                                $compatibleWeb = $websiteLayouts->filter(fn ($layout) => empty($layout['role_hints']) || in_array($roleName, array_map('strtolower', $layout['role_hints']), true));
                                $compatibleMobile = $mobileLayouts->filter(fn ($layout) => empty($layout['role_hints']) || in_array($roleName, array_map('strtolower', $layout['role_hints']), true));
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $role->name }}</div>
                                    <small class="text-muted">{{ $role->description ?: 'Chưa có mô tả' }}</small>
                                </td>
                                <td>
                                    <select name="roles[{{ $role->id }}][layout_web_slug]" class="form-select" required>
                                        <option value="">-- Chọn Website layout --</option>
                                        @foreach($compatibleWeb as $slug => $layout)
                                            <option value="{{ $slug }}" {{ old("roles.{$role->id}.layout_web_slug", $role->layout_web_slug) === $slug ? 'selected' : '' }}>
                                                {{ $layout['label'] ?? $slug }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input
                                        name="roles[{{ $role->id }}][layout_web_name]"
                                        value="{{ old("roles.{$role->id}.layout_web_name", $role->layout_web_name) }}"
                                        class="form-control"
                                        placeholder="Tự lấy theo layout"
                                    >
                                </td>
                                <td>
                                    <select name="roles[{{ $role->id }}][layout_mobile_slug]" class="form-select" required>
                                        <option value="">-- Chọn Mobile layout --</option>
                                        @foreach($compatibleMobile as $slug => $layout)
                                            <option value="{{ $slug }}" {{ old("roles.{$role->id}.layout_mobile_slug", $role->layout_mobile_slug) === $slug ? 'selected' : '' }}>
                                                {{ $layout['label'] ?? $slug }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input
                                        name="roles[{{ $role->id }}][layout_mobile_name]"
                                        value="{{ old("roles.{$role->id}.layout_mobile_name", $role->layout_mobile_name) }}"
                                        class="form-control"
                                        placeholder="Tự lấy theo layout"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">
                    <i class="ph-floppy-disk me-1"></i>Lưu cấu hình Layout
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
