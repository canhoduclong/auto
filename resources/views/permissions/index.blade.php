@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="mb-1">Danh sách quyền</h2>
            <div class="text-muted">Quản lý chức năng và đồng bộ quyền tự động theo Route.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form action="{{ route('permissions.sync-routes') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Cập nhật chức năng từ Route ngay bây giờ?')">
                    Cập nhật chức năng theo Route
                </button>
            </form>
            <a href="{{ route('permissions.create') }}" class="btn btn-primary">+ Thêm quyền</a>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Tổng quyền</div>
                    <div class="fs-4 fw-bold">{{ $stats['total'] ?? $permissions->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Có metadata route</div>
                    <div class="fs-4 fw-bold text-success">{{ $stats['with_route_meta'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Nhóm chức năng</div>
                    <div class="fs-4 fw-bold text-primary">{{ $stats['groups'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <label for="permission-search" class="form-label fw-semibold mb-1">Tìm quyền nhanh</label>
            <input type="text" id="permission-search" class="form-control" placeholder="Nhập tên quyền, mô tả, nhóm hoặc URI...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="permissions-table">
            <thead class="table-light">
                <tr>
                    <th style="width:70px;">ID</th>
                    <th style="min-width:220px;">Tên quyền</th>
                    <th style="min-width:160px;">Nhóm</th>
                    <th style="min-width:110px;">Method</th>
                    <th style="min-width:220px;">URI</th>
                    <th style="min-width:220px;">Mô tả</th>
                    <th style="width:170px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $p->name }}</div>
                        </td>
                        <td>
                            @if(!empty($p->group))
                                <span class="badge bg-info text-dark">{{ $p->group }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($p->method))
                                <code>{{ $p->method }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($p->uri))
                                <code>{{ $p->uri }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $p->description ?: '—' }}</td>
                        <td>
                            <a href="{{ route('permissions.edit', $p->id) }}" class="btn btn-sm btn-warning">Sửa</a>
                            <form action="{{ route('permissions.destroy', $p->id) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button onclick="return confirm('Xóa quyền này?')" class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Chưa có quyền nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('permission-search');
    const table = document.getElementById('permissions-table');
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
