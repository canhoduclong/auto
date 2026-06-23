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
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Tìm tên, tên ngắn, chức danh, email...">
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

    @if(request()->filled('user_id'))
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="fw-semibold text-muted small mb-0">DANH SÁCH NGƯỜI DÙNG</div>
                    </div>
                    <div class="card-body p-0" style="max-height: calc(100vh - 360px); overflow-y: auto;">
                        @forelse($users as $user)
                            @php
                                $isOnline = $user->last_seen_at && $user->last_seen_at->gte($onlineWindow);
                                $avatarUrl = $user->avatar
                                    ? asset($user->avatar)
                                    : ($user->google_avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'U') . '&background=0F172A&color=F8FAFC&size=80&bold=true');
                                $isSelected = $selectedUser && $selectedUser->id === $user->id;
                            @endphp
                            <a href="{{ route('users.index', array_merge(request()->query(), ['user_id' => $user->id])) }}"
                               class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none {{ $isSelected ? 'bg-light border-start border-start-3 border-primary' : '' }}"
                               style="color:inherit;transition:background-color .15s;">
                                <div class="position-relative flex-shrink-0">
                                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}"
                                         style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                    <span class="position-absolute bottom-0 end-0 translate-middle-x"
                                          style="width:9px;height:9px;border-radius:50%;border:2px solid #fff;background:{{ $isOnline ? '#22c55e' : '#94a3b8' }};"></span>
                                </div>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold text-truncate" style="font-size:.9rem;color:#1e293b;">{{ $user->name }}</div>
                                        @if($user->short_name || $user->job_title)
                                            <div class="small text-muted text-truncate">
                                                {{ $user->short_name ?: '—' }}@if($user->job_title) · {{ $user->job_title }}@endif
                                            </div>
                                        @endif
                                        <div class="small text-muted text-truncate">{{ $user->email }}</div>
                                    @if($user->team)
                                        <div class="small text-muted" style="font-size:.75rem;">
                                            <i class="ph ph-users me-1"></i>{{ $user->team->name }}
                                        </div>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-center text-muted">
                                <i class="ph ph-users" style="font-size:2rem;opacity:.3;"></i>
                                <div class="mt-2 small">Không có dữ liệu</div>
                            </div>
                        @endforelse
                    </div>
                    @if($users->hasPages())
                        <div class="card-footer bg-transparent p-2 text-center">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-8">
                @if($selectedUser)
                    @php
                        $isOnline = $selectedUser->last_seen_at && $selectedUser->last_seen_at->gte($onlineWindow);
                        $avatarUrl = $selectedUser->avatar
                            ? asset($selectedUser->avatar)
                            : ($selectedUser->google_avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($selectedUser->name ?? 'U') . '&background=0F172A&color=F8FAFC&size=200&bold=true');
                    @endphp

                    <div class="card border-0 shadow-sm text-center p-4">
                        <div class="position-relative d-inline-block mx-auto mb-3">
                            <img src="{{ $avatarUrl }}" alt="{{ $selectedUser->name }}"
                                 style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
                            <span class="position-absolute bottom-0 end-0"
                                  style="width:16px;height:16px;border-radius:50%;border:2px solid #fff;background:{{ $isOnline ? '#22c55e' : '#94a3b8' }};"></span>
                        </div>

                        <h5 class="fw-bold mb-0">{{ $selectedUser->name }}</h5>
                        @if($selectedUser->short_name || $selectedUser->job_title)
                            <div class="small text-primary fw-semibold mt-1">
                                {{ $selectedUser->short_name ?: '—' }}@if($selectedUser->job_title) · {{ $selectedUser->job_title }}@endif
                            </div>
                        @endif
                        <div class="text-muted small mb-2">{{ $selectedUser->email }}</div>

                        @if($isOnline)
                            <span class="badge rounded-pill d-inline-flex align-items-center gap-1 mx-auto mb-3"
                                  style="background:rgba(34,197,94,.12);color:#16a34a;">
                                <span style="width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
                                Đang Online
                            </span>
                        @else
                            <span class="badge rounded-pill d-inline-flex align-items-center gap-1 mx-auto mb-3"
                                  style="background:rgba(148,163,184,.15);color:#64748b;">
                                <span style="width:7px;height:7px;border-radius:50%;background:#94a3b8;display:inline-block;"></span>
                                Offline
                            </span>
                        @endif

                        <div class="mb-3">
                            @foreach($selectedUser->roles as $role)
                                <span class="badge rounded-pill bg-info bg-opacity-10 text-info">{{ $role->name }}</span>
                            @endforeach
                        </div>

                        <hr class="my-2">

                        <div class="text-start small">
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">ID</span>
                                <span class="fw-semibold">#{{ $selectedUser->id }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Team</span>
                                <span class="fw-semibold">{{ $selectedUser->team->name ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Tên ngắn</span>
                                <span class="fw-semibold">{{ $selectedUser->short_name ?: '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Chức danh</span>
                                <span class="fw-semibold">{{ $selectedUser->job_title ?: '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Kho</span>
                                <span class="fw-semibold">{{ $selectedUser->warehouse->name ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Phòng ban</span>
                                <span class="fw-semibold">{{ $selectedUser->department->name ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Tham gia</span>
                                <span class="fw-semibold">{{ $selectedUser->created_at?->format('d/m/Y') ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Lần cuối online</span>
                                <span class="fw-semibold">{{ $selectedUser->last_seen_at ? $selectedUser->last_seen_at->format('d/m H:i') : '—' }}</span>
                            </div>
                        </div>

                        <div class="mt-3 d-flex flex-column gap-2">
                            <a href="{{ route('users.edit', $selectedUser->id) }}" class="btn btn-warning btn-sm w-100">
                                <i class="ph ph-pencil me-1"></i>Chỉnh sửa
                            </a>
                            <a href="{{ route('users.show', $selectedUser->id) }}" class="btn btn-light btn-sm w-100 border">
                                <i class="ph ph-arrow-up-right me-1"></i>Xem chi tiết đầy đủ
                            </a>
                        </div>
                    </div>
                @else
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5 text-center text-muted">
                            <i class="ph ph-user" style="font-size:3rem;opacity:.3;"></i>
                            <div class="mt-3 fw-semibold">Không tìm thấy user theo user_id hiện tại</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
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
                            <th style="width:150px;">Hành động</th>
                            <th style="width:100px;">View 2</th>
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
                                        @if($user->short_name || $user->job_title)
                                            <div class="small text-muted text-truncate" style="max-width:160px;">{{ $user->short_name ?: '—' }}@if($user->job_title) · {{ $user->job_title }}@endif</div>
                                        @endif
                                        <div class="small text-muted text-truncate" style="max-width:160px;">{{ $user->email }}</div>
                                        @if($user->zalo_name)<div class="small text-primary text-truncate" style="max-width:160px;">Zalo: {{ $user->zalo_name }}</div>@endif
                                    </div>
                                </div>
                            </td>
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
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="badge rounded-pill bg-info bg-opacity-10 text-info" style="font-size:.72rem;">{{ $role->name }}</span>
                                @endforeach
                            </td>
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
                            <td class="small text-muted">
                                {{ $user->last_seen_at ? $user->last_seen_at->format('d/m/Y H:i') : '—' }}
                                @if($user->last_seen_at && !$isOnline)
                                    <div style="font-size:.7rem;">{{ $user->last_seen_at->diffForHumans() }}</div>
                                @endif
                            </td>
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
                            <td>
                                <a href="{{ route('users.index', array_merge(request()->query(), ['user_id' => $user->id])) }}" class="btn btn-outline-primary btn-sm" title="Xem layout 2 cột">
                                    View 2
                                </a>
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
    @endif
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
