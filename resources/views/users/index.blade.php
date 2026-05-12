@extends('layouts.app')

@section('content')
@php
    $onlineWindow = now()->subMinutes(5);
@endphp
<div class="container-fluid px-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Quản lý Người dùng</h4>
            <div class="text-muted small">{{ $users->total() }} người dùng</div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" form="bulk-delete-form" class="btn btn-outline-danger btn-sm" id="bulk-delete-button">
                <i class="ph ph-trash me-1"></i>Xóa đã chọn
            </button>
            <a href="{{ route('users.bulk-assign-team.form') }}" class="btn btn-outline-primary btn-sm">
                <i class="ph ph-users me-1"></i>Gán hàng loạt
            </a>
            @if($canCreateUsers ?? true)
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                    <i class="ph ph-plus me-1"></i>Thêm User
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form id="bulk-delete-form" action="{{ route('users.bulk-delete') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
        <div id="bulk-delete-inputs"></div>
    </form>

    {{-- Filter card --}}
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Tìm tên hoặc email...">
                </div>
                <div class="col-md-3">
                    <select name="team_id" class="form-select form-select-sm">
                        <option value="">Tất cả team</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="role_id" class="form-select form-select-sm">
                        <option value="">Tất cả role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ (string) request('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-fill">Lọc</button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;">
                            <input type="checkbox" id="select-all-users" class="form-check-input">
                        </th>
                        <th style="width:220px;">Người dùng</th>
                        <th>Team / Kho</th>
                        <th>Quyền</th>
                        <th>Trạng thái</th>
                        <th>Lần cuối online</th>
                        <th style="width:130px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    @php
                        $isOnline = $user->last_seen_at && $user->last_seen_at->gte($onlineWindow);
                        $avatarUrl = $user->avatar
                            ? asset($user->avatar)
                            : ($user->google_avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'U') . '&background=0F172A&color=F8FAFC&size=80&bold=true');
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input user-checkbox" value="{{ $user->id }}">
                        </td>
                        {{-- Avatar + Name --}}
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="position-relative flex-shrink-0">
                                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}"
                                         style="width:38px;height:38px;border-radius:50%;object-fit:cover;">
                                    <span class="position-absolute bottom-0 end-0 translate-middle-x"
                                          style="width:10px;height:10px;border-radius:50%;border:2px solid #fff;background:{{ $isOnline ? '#22c55e' : '#94a3b8' }};"></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate" style="max-width:160px;">{{ $user->name }}</div>
                                    <div class="small text-muted text-truncate" style="max-width:160px;">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        {{-- Team / Warehouse --}}
                        <td>
                            <div class="small">
                                @if($user->team)
                                    <div><i class="ph ph-users text-muted me-1"></i>{{ $user->team->name }}</div>
                                @endif
                                @if($user->warehouse)
                                    <div><i class="ph ph-warehouse text-muted me-1"></i>{{ $user->warehouse->name }}</div>
                                @endif
                                @if(!$user->team && !$user->warehouse)
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </td>
                        {{-- Roles --}}
                        <td>
                            @foreach($user->roles as $role)
                                <span class="badge rounded-pill bg-info bg-opacity-10 text-info" style="font-size:.72rem;">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        {{-- Status --}}
                        <td>
                            @if($isOnline)
                                <span class="badge rounded-pill d-inline-flex align-items-center gap-1" style="background:rgba(34,197,94,.12);color:#16a34a;font-size:.75rem;">
                                    <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;"></span> Online
                                </span>
                            @else
                                <span class="badge rounded-pill d-inline-flex align-items-center gap-1" style="background:rgba(148,163,184,.15);color:#64748b;font-size:.75rem;">
                                    <span style="width:7px;height:7px;border-radius:50%;background:#94a3b8;display:inline-block;"></span> Offline
                                </span>
                            @endif
                        </td>
                        {{-- Last seen --}}
                        <td class="small text-muted">
                            {{ $user->last_seen_at ? $user->last_seen_at->format('d/m/Y H:i') : '—' }}
                            @if($user->last_seen_at && !$isOnline)
                                <div style="font-size:.7rem;">{{ $user->last_seen_at->diffForHumans() }}</div>
                            @endif
                        </td>
                        {{-- Actions --}}
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('users.show', $user->id) }}" class="btn btn-light btn-sm border" title="Xem chi tiết">
                                    <i class="ph ph-eye"></i>
                                </a>
                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm" title="Sửa">
                                    <i class="ph ph-pencil"></i>
                                </a>
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline-block">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Xóa"
                                            onclick="return confirm('Bạn có chắc chắn muốn xóa không?')">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-transparent">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('select-all-users');
    var bulkDeleteForm = document.getElementById('bulk-delete-form');
    var bulkDeleteInputs = document.getElementById('bulk-delete-inputs');
    var bulkDeleteButton = document.getElementById('bulk-delete-button');

    function getUserCheckboxes() {
        return Array.from(document.querySelectorAll('.user-checkbox'));
    }

    function syncBulkDeleteButton() {
        var hasSelection = getUserCheckboxes().some(function (checkbox) {
            return checkbox.checked;
        });

        bulkDeleteButton.disabled = !hasSelection;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            getUserCheckboxes().forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });

            syncBulkDeleteButton();
        });
    }

    getUserCheckboxes().forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var checkboxes = getUserCheckboxes();
            var checkedCount = checkboxes.filter(function (item) {
                return item.checked;
            }).length;

            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            }

            syncBulkDeleteButton();
        });
    });

    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function (event) {
            var selectedIds = getUserCheckboxes()
                .filter(function (checkbox) {
                    return checkbox.checked;
                })
                .map(function (checkbox) {
                    return checkbox.value;
                });

            if (selectedIds.length === 0) {
                event.preventDefault();
                alert('Vui lòng chọn ít nhất một user để xóa.');
                return;
            }

            if (!window.confirm('Bạn có chắc chắn muốn xóa các user đã chọn không?')) {
                event.preventDefault();
                return;
            }

            bulkDeleteInputs.innerHTML = '';

            selectedIds.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_ids[]';
                input.value = id;
                bulkDeleteInputs.appendChild(input);
            });
        });
    }

    syncBulkDeleteButton();
});
</script>
@endpush
