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
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">
            <i class="bi bi-check2-circle me-1"></i>Thêm
        </button>
    </form>
</div>
@endsection
