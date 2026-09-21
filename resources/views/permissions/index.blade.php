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
            <div class="row g-2 align-items-end">
                <div class="col-lg-8"><label for="permission-search" class="form-label fw-semibold mb-1">Tìm quyền nhanh</label><input type="text" id="permission-search" class="form-control" placeholder="Nhập tên quyền, mô tả hoặc URI..."></div>
                <div class="col-lg-4"><label for="permission-group-filter" class="form-label fw-semibold mb-1">Nhóm chức năng</label><select id="permission-group-filter" class="form-select"><option value="">Tất cả nhóm</option>@foreach($groupOptions as $group)<option value="{{ strtolower($group) }}">{{ ucfirst(str_replace('-', ' ', $group)) }} ({{ $groupedPermissions[$group]->count() }})</option>@endforeach</select></div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn btn-sm btn-outline-primary" id="expand-all-groups"><i class="bi bi-arrows-expand me-1"></i>Mở tất cả nhóm</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="collapse-all-groups"><i class="bi bi-arrows-collapse me-1"></i>Thu gọn tất cả</button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="permissions-table">
            <thead class="table-light">
                <tr>
                    <th style="width:70px;">ID</th>
                    <th style="min-width:220px;">Tên quyền</th>
                    <th style="min-width:145px;">Thao tác</th>
                    <th style="min-width:160px;">Nhóm</th>
                    <th style="min-width:110px;">Method</th>
                    <th style="min-width:220px;">URI</th>
                    <th style="min-width:220px;">Mô tả</th>
                    <th style="width:170px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groupedPermissions as $group => $groupPermissions)
                    @php($groupMeta = $groupCatalog[$group])
                    <tr class="table-primary permission-group-header" data-group="{{ strtolower($group) }}"><td colspan="8"><button type="button" class="btn btn-link text-decoration-none text-start text-dark w-100 p-0 js-toggle-permission-group" data-group="{{ strtolower($group) }}" aria-expanded="true"><div class="d-flex justify-content-between align-items-center gap-3"><span><strong><i class="bi bi-folder2-open me-2 js-group-icon"></i>{{ $groupMeta['label'] }}</strong><small class="d-block text-muted mt-1">{{ $groupMeta['description'] }}</small></span><span class="text-nowrap"><span class="badge bg-primary me-2">{{ $groupPermissions->count() }} quyền</span><i class="bi bi-chevron-up js-group-chevron"></i></span></div></button></td></tr>
                    @foreach($groupPermissions as $p)
                    <tr class="permission-row" data-group="{{ strtolower($group) }}">
                        <td>{{ $p->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $p->name }}</div>
                            <small class="text-muted">{{ $permissionCatalog[$p->id]['explanation'] }}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $permissionCatalog[$p->id]['label'] }}</span></td>
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
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Chưa có quyền nào.</td>
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

    const rows = Array.from(table.querySelectorAll('tbody .permission-row'));
    const groupHeaders = Array.from(table.querySelectorAll('tbody .permission-group-header'));
    const groupFilter = document.getElementById('permission-group-filter');
    const manuallyCollapsedGroups = new Set();

    function paintGroup(group) {
        const header = groupHeaders.find(item => item.dataset.group === group);
        const collapsed = manuallyCollapsedGroups.has(group);
        rows.filter(row => row.dataset.group === group).forEach(row => {
            if (collapsed) row.style.display = 'none';
        });
        const button = header?.querySelector('.js-toggle-permission-group');
        button?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        const icon = header?.querySelector('.js-group-icon');
        if (icon) icon.className = `bi ${collapsed ? 'bi-folder2' : 'bi-folder2-open'} me-2 js-group-icon`;
        const chevron = header?.querySelector('.js-group-chevron');
        if (chevron) chevron.className = `bi ${collapsed ? 'bi-chevron-down' : 'bi-chevron-up'} js-group-chevron`;
    }

    function applyPermissionFilters() {
        const keyword = (searchInput.value || '').trim().toLowerCase();
        const selectedGroup = groupFilter?.value || '';

        rows.forEach(function (row) {
            const text = (row.textContent || '').toLowerCase();
            const matchesKeyword = keyword === '' || text.includes(keyword);
            const matchesGroup = selectedGroup === '' || row.dataset.group === selectedGroup;
            const expanded = !manuallyCollapsedGroups.has(row.dataset.group);
            row.style.display = matchesKeyword && matchesGroup && expanded ? '' : 'none';
        });
        groupHeaders.forEach(function (header) {
            const hasMatchingRows = rows.some(row => {
                const matchesKeyword = keyword === '' || (row.textContent || '').toLowerCase().includes(keyword);
                const matchesGroup = selectedGroup === '' || row.dataset.group === selectedGroup;
                return row.dataset.group === header.dataset.group && matchesKeyword && matchesGroup;
            });
            header.style.display = hasMatchingRows ? '' : 'none';
        });
    }
    searchInput.addEventListener('input', applyPermissionFilters);
    groupFilter?.addEventListener('change', applyPermissionFilters);
    document.querySelectorAll('.js-toggle-permission-group').forEach(button => button.addEventListener('click', function () {
        const group = button.dataset.group;
        manuallyCollapsedGroups.has(group) ? manuallyCollapsedGroups.delete(group) : manuallyCollapsedGroups.add(group);
        applyPermissionFilters();
        paintGroup(group);
    }));
    document.getElementById('expand-all-groups')?.addEventListener('click', function () {
        manuallyCollapsedGroups.clear();
        applyPermissionFilters();
        groupHeaders.forEach(header => paintGroup(header.dataset.group));
    });
    document.getElementById('collapse-all-groups')?.addEventListener('click', function () {
        groupHeaders.forEach(header => manuallyCollapsedGroups.add(header.dataset.group));
        applyPermissionFilters();
        groupHeaders.forEach(header => paintGroup(header.dataset.group));
    });
});
</script>
@endsection
