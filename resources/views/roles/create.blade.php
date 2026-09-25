@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="mb-1">
                    <i class="bi bi-shield-plus me-2 text-success"></i>Tạo Vai trò mới
                </h2>
                <div class="text-muted">Thiết lập tên và mô tả vai trò để hiển thị đồng nhất trên hệ thống.</div>
            </div>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
            </a>
        </div>
    </div>

    <form action="{{ route('roles.store') }}" method="POST">
        @csrf

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Tên Role</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            class="form-control @error('name') is-invalid @enderror"
                            placeholder="Ví dụ: leader_sale"
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="description" class="form-label">Mô tả (tuỳ chọn)</label>
                        <input
                            type="text"
                            name="description"
                            id="description"
                            value="{{ old('description') }}"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="Ví dụ: Trưởng phòng Kinh Doanh"
                        >
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @php
                        $websiteLayouts = collect($layoutCatalog)->filter(fn ($layout) => ($layout['platform'] ?? 'website') === 'website');
                        $mobileLayouts = collect($layoutCatalog)->filter(fn ($layout) => ($layout['platform'] ?? 'website') === 'my_app');
                    @endphp

                    <div class="col-md-6">
                        <label for="layout_web_slug" class="form-label">Website layout</label>
                        <select
                            name="layout_web_slug"
                            id="layout_web_slug"
                            class="form-select @error('layout_web_slug') is-invalid @enderror"
                            required
                        >
                            <option value="">-- Chọn 1 layout website --</option>
                            @foreach($websiteLayouts as $slug => $layout)
                                <option value="{{ $slug }}" data-label="{{ $layout['label'] ?? $slug }}" {{ old('layout_web_slug') === $slug ? 'selected' : '' }}>
                                    {{ $layout['label'] ?? $slug }} | {{ $layout['route'] ?? '' }} | roles: {{ collect($layout['role_hints'] ?? [])->join(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('layout_web_slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="layout_web_name" class="form-label">Website layout name</label>
                        <input
                            type="text"
                            name="layout_web_name"
                            id="layout_web_name"
                            value="{{ old('layout_web_name') }}"
                            class="form-control @error('layout_web_name') is-invalid @enderror"
                            placeholder="Tự lấy theo layout nếu bỏ trống"
                        >
                        @error('layout_web_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="layout_mobile_slug" class="form-label">My_app mobile layout</label>
                        <select
                            name="layout_mobile_slug"
                            id="layout_mobile_slug"
                            class="form-select @error('layout_mobile_slug') is-invalid @enderror"
                            required
                        >
                            <option value="">-- Chọn 1 layout mobile --</option>
                            @foreach($mobileLayouts as $slug => $layout)
                                <option value="{{ $slug }}" data-label="{{ $layout['label'] ?? $slug }}" {{ old('layout_mobile_slug') === $slug ? 'selected' : '' }}>
                                    {{ $layout['label'] ?? $slug }} | {{ $layout['route'] ?? '' }} | roles: {{ collect($layout['role_hints'] ?? [])->join(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('layout_mobile_slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">My_app là app Flutter viết lại các tính năng website trên mobile.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="layout_mobile_name" class="form-label">Mobile layout name</label>
                        <input
                            type="text"
                            name="layout_mobile_name"
                            id="layout_mobile_name"
                            value="{{ old('layout_mobile_name') }}"
                            class="form-control @error('layout_mobile_name') is-invalid @enderror"
                            placeholder="Tự lấy theo layout nếu bỏ trống"
                        >
                        @error('layout_mobile_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">
            <i class="bi bi-check2-circle me-1"></i>Thêm
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const syncLayoutName = function (selectId, inputId) {
        const select = document.getElementById(selectId);
        const input = document.getElementById(inputId);
        if (!select || !input) return;

        select.addEventListener('change', function () {
            if (input.value.trim() !== '') return;
            input.value = select.options[select.selectedIndex]?.dataset.label || '';
        });
    };

    syncLayoutName('layout_web_slug', 'layout_web_name');
    syncLayoutName('layout_mobile_slug', 'layout_mobile_name');
});
</script>
@endpush
