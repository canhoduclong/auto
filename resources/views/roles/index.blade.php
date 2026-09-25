@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="mb-1">Danh sách Role</h2>
            <div class="text-muted">Quản lý nhóm quyền và phân bổ chức năng cho từng vai trò.</div>
        </div>
        <a href="{{ route('roles.create') }}" class="btn btn-primary">+ Thêm Role</a>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Tổng role</div>
                    <div class="fs-4 fw-bold">{{ $roles->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Tổng quyền đã gán</div>
                    <div class="fs-4 fw-bold text-success">{{ $roles->sum(fn($r) => $r->permissions->count()) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Role có quyền</div>
                    <div class="fs-4 fw-bold text-primary">{{ $roles->filter(fn($r) => $r->permissions->isNotEmpty())->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <label for="role-search" class="form-label fw-semibold mb-1">Tìm role nhanh</label>
            <input type="text" id="role-search" class="form-control" placeholder="Nhập tên role, mô tả hoặc tên quyền...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="roles-table">
            <thead class="table-light">
                <tr>
                    <th style="width:80px;">ID</th>
                    <th style="min-width:180px;">Tên role</th>
                    <th style="min-width:220px;">Mô tả</th>
                    <th style="min-width:280px;">Quyền</th>
                    <th style="width:190px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $role->name }}</div>
                            <span class="text-muted small">{{ $role->permissions->count() }} quyền</span>
                        </td>
                        <td>{{ $role->description ?: '—' }}</td>
                        <td>
                            @if($role->permissions->isEmpty())
                                <span class="text-muted">Chưa gán quyền</span>
                            @else
                                @foreach($role->permissions->take(8) as $perm)
                                    <span class="badge bg-info text-dark me-1 mb-1">{{ $perm->name }}</span>
                                @endforeach

                                @if($role->permissions->count() > 8)
                                    <span class="badge bg-secondary">+{{ $role->permissions->count() - 8 }} quyền</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-warning">Sửa</a>
                            <form action="{{ route('roles.destroy', $role->id) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button onclick="return confirm('Xóa role này?')" class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Chưa có role nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('role-search');
    const table = document.getElementById('roles-table');

    if (!searchInput || !table) {
        return;
    }

    const rows = Array.from(table.querySelectorAll('tbody tr'));

    searchInput.addEventListener('input', function () {
        const keyword = (searchInput.value || '').trim().toLowerCase();

        rows.forEach(function (row) {
            const text = (row.textContent || '').toLowerCase();
            row.style.display = keyword === '' || text.includes(keyword) ? '' : 'none';
        });
    });
});
</script>
@endsection
