@extends('layouts.ceo')

@section('title', 'Danh sách user')
@section('subtitle', 'Danh sách user theo layout quản trị, chỉ xem và chi tiết')

@section('content')
@php
    $onlineWindow = now()->subMinutes(5);
@endphp

<div class="container-fluid px-1">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Quản lý Người dùng</h4>
            <div class="text-muted small">{{ $users->total() }} người dùng</div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('ceo.users-list') }}" class="row g-2 align-items-end">
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
                    <a href="{{ route('ceo.users-list') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
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
                            <a href="{{ route('ceo.users-list', array_merge(request()->query(), ['user_id' => $user->id])) }}"
                               class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none {{ $isSelected ? 'bg-light border-start border-start-3 border-primary' : '' }}"
                               style="color:inherit;transition:background-color .15s;">
                                <div class="position-relative flex-shrink-0">
                                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                    <span class="position-absolute bottom-0 end-0 translate-middle-x" style="width:9px;height:9px;border-radius:50%;border:2px solid #fff;background:{{ $isOnline ? '#22c55e' : '#94a3b8' }};"></span>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold text-truncate" style="font-size:.9rem;color:#1e293b;">{{ $user->name }}</div>
                                    <div class="small text-muted text-truncate">{{ $user->email }}</div>
                                    @if($user->team)
                                        <div class="small text-muted" style="font-size:.75rem;"><i class="ph ph-users me-1"></i>{{ $user->team->name }}</div>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-center text-muted">Không có dữ liệu</div>
                        @endforelse
                    </div>
                    @if($users->hasPages())
                        <div class="card-footer bg-transparent p-2 text-center">{{ $users->links() }}</div>
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
                            <img src="{{ $avatarUrl }}" alt="{{ $selectedUser->name }}" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
                            <span class="position-absolute bottom-0 end-0" style="width:16px;height:16px;border-radius:50%;border:2px solid #fff;background:{{ $isOnline ? '#22c55e' : '#94a3b8' }};"></span>
                        </div>
                        <h5 class="fw-bold mb-0">{{ $selectedUser->name }}</h5>
                        <div class="text-muted small mb-2">{{ $selectedUser->email }}</div>

                        <div class="mb-3">
                            @foreach($selectedUser->roles as $role)
                                <span class="badge rounded-pill bg-info bg-opacity-10 text-info">{{ $role->name }}</span>
                            @endforeach
                        </div>

                        <div class="text-start small">
                            <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">ID</span><span class="fw-semibold">#{{ $selectedUser->id }}</span></div>
                            <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Team</span><span class="fw-semibold">{{ $selectedUser->team->name ?? '—' }}</span></div>
                            <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Kho</span><span class="fw-semibold">{{ $selectedUser->warehouse->name ?? '—' }}</span></div>
                            <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Phòng ban</span><span class="fw-semibold">{{ $selectedUser->department->name ?? '—' }}</span></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Lần cuối online</span><span class="fw-semibold">{{ $selectedUser->last_seen_at ? $selectedUser->last_seen_at->format('d/m H:i') : '—' }}</span></div>
                        </div>

                        <div class="mt-3 text-muted small">Chi tiết user đang hiển thị trực tiếp trong khung này.</div>
                    </div>

                    <ul class="nav nav-tabs mt-3 mb-0" id="userDetailTabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-activity">
                                <i class="ph ph-clock-clockwise me-1"></i>Lịch sử hoạt động
                                <span class="badge bg-secondary ms-1">{{ $activities->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-login">
                                <i class="ph ph-sign-in me-1"></i>Lịch sử đăng nhập
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        {{-- ---- Tab: Activity history ---- --}}
                        <div class="tab-pane fade show active" id="tab-activity">
                            <div class="card border-0 border-top-0 shadow-sm rounded-top-0">
                                @if($activities->isEmpty())
                                    <div class="p-5 text-center text-muted">
                                        <i class="ph ph-clock-clockwise" style="font-size:2rem;opacity:.3;"></i>
                                        <div class="mt-2">Chưa có lịch sử hoạt động</div>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:160px;">Thời gian</th>
                                                    <th style="width:110px;">Loại</th>
                                                    <th style="width:110px;">Hành động</th>
                                                    <th>Nội dung</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($activities as $event)
                                                <tr>
                                                    <td class="small text-muted" title="{{ $event->created_at->format('d/m/Y H:i:s') }}">
                                                        {{ $event->created_at->format('d/m/Y H:i') }}
                                                        <div style="font-size:.68rem;">{{ $event->created_at->diffForHumans() }}</div>
                                                    </td>
                                                    <td>
                                                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:.7rem;">
                                                            {{ $event->event_type }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge rounded-pill
                                                            @if(in_array($event->action, ['create','store'])) bg-success bg-opacity-10 text-success
                                                            @elseif(in_array($event->action, ['update','edit'])) bg-warning bg-opacity-10 text-warning
                                                            @elseif(in_array($event->action, ['delete','destroy'])) bg-danger bg-opacity-10 text-danger
                                                            @else bg-secondary bg-opacity-10 text-secondary @endif"
                                                            style="font-size:.7rem;">
                                                            {{ $event->action }}
                                                        </span>
                                                    </td>
                                                    <td class="small">
                                                        <div class="fw-semibold">{{ $event->title }}</div>
                                                        @if($event->message)
                                                            <div class="text-muted">{{ Str::limit($event->message, 100) }}</div>
                                                        @endif
                                                        @if($event->url)
                                                            <a href="{{ $event->url }}" class="text-muted" style="font-size:.7rem;" target="_blank">
                                                                <i class="ph ph-link me-1"></i>{{ Str::limit($event->url, 60) }}
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if($activities->count() >= 50)
                                        <div class="p-2 text-center small text-muted border-top">Hiển thị 50 hoạt động gần nhất</div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- ---- Tab: Login history ---- --}}
                        <div class="tab-pane fade" id="tab-login">
                            <div class="card border-0 border-top-0 shadow-sm rounded-top-0">
                                @php
                                    $loginEvents = $activities->filter(fn($e) => in_array($e->event_type, ['auth', 'login', 'session']));
                                @endphp
                                @if($loginEvents->isEmpty())
                                    {{-- Fallback: show last_seen_at summary --}}
                                    <div class="p-4">
                                        <div class="alert alert-light border mb-3">
                                            <i class="ph ph-info me-1"></i>
                                            Chưa ghi nhận sự kiện đăng nhập chi tiết. Dưới đây là thông tin từ lần hoạt động cuối.
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <div class="card border bg-light p-3">
                                                    <div class="small text-muted mb-1">Lần cuối truy cập hệ thống</div>
                                                    <div class="fw-bold fs-5">
                                                        {{ $selectedUser->last_seen_at ? $selectedUser->last_seen_at->format('d/m/Y H:i') : 'Chưa ghi nhận' }}
                                                    </div>
                                                    @if($selectedUser->last_seen_at)
                                                        <div class="small text-muted">{{ $selectedUser->last_seen_at->diffForHumans() }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="card border bg-light p-3">
                                                    <div class="small text-muted mb-1">Trạng thái hiện tại</div>
                                                    <div class="fw-bold fs-5">
                                                        @if($isOnline)
                                                            <span style="color:#16a34a;">● Đang Online</span>
                                                        @else
                                                            <span style="color:#94a3b8;">● Offline</span>
                                                        @endif
                                                    </div>
                                                    <div class="small text-muted">Cập nhật mỗi 5 phút</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Thời gian đăng nhập</th>
                                                    <th>Chi tiết</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($loginEvents as $event)
                                                <tr>
                                                    <td class="small">
                                                        {{ $event->created_at->format('d/m/Y H:i') }}
                                                        <div class="text-muted" style="font-size:.68rem;">{{ $event->created_at->diffForHumans() }}</div>
                                                    </td>
                                                    <td class="small">{{ $event->title }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card border-0 shadow-sm"><div class="card-body p-5 text-center text-muted">Không tìm thấy user theo user_id hiện tại</div></div>
                @endif
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:220px;">Người dùng</th>
                            <th>Team / Kho</th>
                            <th>Quyền</th>
                            <th>Trạng thái</th>
                            <th>Lần cuối online</th>
                            <th style="width:130px;">Chi tiết</th>
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
                                <div class="d-flex align-items-center gap-2">
                                    <div class="position-relative flex-shrink-0">
                                        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">
                                        <span class="position-absolute bottom-0 end-0 translate-middle-x" style="width:10px;height:10px;border-radius:50%;border:2px solid #fff;background:{{ $isOnline ? '#22c55e' : '#94a3b8' }};"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate" style="max-width:160px;">{{ $user->name }}</div>
                                        <div class="small text-muted text-truncate" style="max-width:160px;">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    @if($user->team)<div><i class="ph ph-users text-muted me-1"></i>{{ $user->team->name }}</div>@endif
                                    @if($user->warehouse)<div><i class="ph ph-warehouse text-muted me-1"></i>{{ $user->warehouse->name }}</div>@endif
                                    @if(!$user->team && !$user->warehouse)<span class="text-muted">—</span>@endif
                                </div>
                            </td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="badge rounded-pill bg-info bg-opacity-10 text-info" style="font-size:.72rem;">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($isOnline)
                                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1" style="background:rgba(34,197,94,.12);color:#16a34a;font-size:.75rem;"><span style="width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;"></span> Online</span>
                                @else
                                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1" style="background:rgba(148,163,184,.15);color:#64748b;font-size:.75rem;"><span style="width:7px;height:7px;border-radius:50%;background:#94a3b8;display:inline-block;"></span> Offline</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $user->last_seen_at ? $user->last_seen_at->format('d/m/Y H:i') : '—' }}</td>
                            <td>
                                <a href="{{ route('ceo.users-list', array_merge(request()->query(), ['user_id' => $user->id])) }}" class="btn btn-light btn-sm border" title="Xem chi tiết"><i class="ph ph-eye"></i></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="card-footer bg-transparent">{{ $users->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
