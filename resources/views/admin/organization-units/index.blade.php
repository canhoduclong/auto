@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Quản lý Khối & Phòng ban</h4>
            <div class="text-muted small">Phòng ban thuộc một khối, user có thể được gán vào phòng ban/khối tại màn quản trị users.</div>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ph ph-user-gear me-1"></i>Quản trị users
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <div class="fw-bold">Thêm khối</div>
                    <div class="small text-muted">Ví dụ: Khối Kinh doanh, Khối Vận hành</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.organization-units.blocks.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Tên khối <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="255" value="{{ old('name') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                                <span class="form-check-label">Đang hoạt động</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100"><i class="ph ph-plus me-1"></i>Thêm khối</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="fw-bold">Danh sách khối</div>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($blocks as $block)
                        <div class="list-group-item">
                            <form method="POST" action="{{ route('admin.organization-units.blocks.update', $block) }}" class="row g-2 align-items-center">
                                @csrf
                                @method('PUT')
                                <div class="col-12">
                                    <input type="text" name="name" class="form-control form-control-sm fw-semibold" value="{{ $block->name }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-check small mb-0">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($block->is_active)>
                                        <span class="form-check-label">Hoạt động</span>
                                    </label>
                                </div>
                                <div class="col-6 text-end small text-muted">
                                    {{ $block->departments_count }} phòng ban · {{ $block->users_count }} user
                                </div>
                                <div class="col-12 d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm flex-fill">Lưu</button>
                                    <button type="submit" form="delete-block-{{ $block->id }}" class="btn btn-outline-danger btn-sm" onclick="return confirm('Xóa khối này?')">Xóa</button>
                                </div>
                            </form>
                            <form id="delete-block-{{ $block->id }}" method="POST" action="{{ route('admin.organization-units.blocks.destroy', $block) }}" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @empty
                        <div class="list-group-item text-muted text-center py-4">Chưa có khối.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <div class="fw-bold">Thêm phòng ban</div>
                    <div class="small text-muted">Mỗi phòng ban cần thuộc một khối.</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.organization-units.departments.store') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Khối <span class="text-danger">*</span></label>
                            <select name="block_id" class="form-select" required>
                                <option value="">-- Chọn khối --</option>
                                @foreach($blocks as $block)
                                    <option value="{{ $block->id }}" @selected((string) old('block_id') === (string) $block->id)>{{ $block->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Tên phòng ban <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="255" value="{{ old('department_name') }}">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100"><i class="ph ph-plus me-1"></i>Thêm phòng ban</button>
                        </div>
                    </form>
                </div>
            </div>

            @forelse($blocks as $block)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">{{ $block->name }}</div>
                            <div class="small text-muted">{{ $block->departments_count }} phòng ban · {{ $block->users_count }} user</div>
                        </div>
                        <span class="badge {{ $block->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $block->is_active ? 'Hoạt động' : 'Tạm tắt' }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Phòng ban</th>
                                    <th style="width:120px">User</th>
                                    <th style="width:140px">Trạng thái</th>
                                    <th class="text-end" style="width:170px">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($block->departments as $department)
                                    <tr>
                                        <td>
                                            <form id="dept-update-{{ $department->id }}" method="POST" action="{{ route('admin.organization-units.departments.update', $department) }}" class="d-flex gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="block_id" value="{{ $block->id }}">
                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $department->name }}" required>
                                            </form>
                                        </td>
                                        <td>{{ $department->users_count }}</td>
                                        <td>
                                            <label class="form-check mb-0 small">
                                                <input form="dept-update-{{ $department->id }}" type="checkbox" name="is_active" value="1" class="form-check-input" @checked($department->is_active)>
                                                <span class="form-check-label">Hoạt động</span>
                                            </label>
                                        </td>
                                        <td class="text-end">
                                            <button form="dept-update-{{ $department->id }}" class="btn btn-outline-primary btn-sm">Lưu</button>
                                            <button type="submit" form="delete-dept-{{ $department->id }}" class="btn btn-outline-danger btn-sm" onclick="return confirm('Xóa phòng ban này?')">Xóa</button>
                                            <form id="delete-dept-{{ $department->id }}" method="POST" action="{{ route('admin.organization-units.departments.destroy', $department) }}" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Chưa có phòng ban trong khối này.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-5">Hãy tạo khối đầu tiên để bắt đầu quản lý phòng ban.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
