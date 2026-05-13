@extends('layouts.ceo')

@section('title', 'Chi tiết: ' . $user->name)

@section('content')
@php
    $isOnline = $user->last_seen_at && $user->last_seen_at->gte(now()->subMinutes(5));
    $avatarUrl = $user->avatar
        ? asset($user->avatar)
        : ($user->google_avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'U') . '&background=0F172A&color=F8FAFC&size=200&bold=true');
@endphp

<div class="container-fluid px-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('ceo.users-list') }}">Người dùng</a></li>
            <li class="breadcrumb-item active">{{ $user->name }}</li>
        </ol>
    </nav>
    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm text-center p-4">
                <div class="position-relative d-inline-block mx-auto mb-3">
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}"
                         style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
                    <span class="position-absolute bottom-0 end-0"
                          style="width:16px;height:16px;border-radius:50%;border:2px solid #fff;background:{{ $isOnline ? '#22c55e' : '#94a3b8' }};"></span>
                </div>
                <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                <div class="text-muted small mb-2">{{ $user->email }}</div>
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
                    @foreach($user->roles as $role)
                        <span class="badge rounded-pill bg-info bg-opacity-10 text-info">{{ $role->name }}</span>
                    @endforeach
                </div>
                <hr class="my-2">
                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">ID</span>
                        <span class="fw-semibold">#{{ $user->id }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Team</span>
                        <span class="fw-semibold">{{ $user->team->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Kho</span>
                        <span class="fw-semibold">{{ $user->warehouse->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Phòng ban</span>
                        <span class="fw-semibold">{{ $user->department->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Tham gia</span>
                        <span class="fw-semibold">{{ $user->created_at?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Lần cuối online</span>
                        <span class="fw-semibold">
                            {{ $user->last_seen_at ? $user->last_seen_at->format('d/m H:i') : '—' }}
                        </span>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-column gap-2">
                    <a href="{{ route('ceo.users-list') }}" class="btn btn-light btn-sm w-100 border">
                        <i class="ph ph-arrow-left me-1"></i>Danh sách
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <ul class="nav nav-tabs mb-0" id="userDetailTabs">
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
                <div class="tab-pane fade" id="tab-login">
                    <div class="card border-0 border-top-0 shadow-sm rounded-top-0">
                        @php
                            $loginEvents = $activities->filter(fn($e) => in_array($e->event_type, ['auth', 'login', 'session']));
                        @endphp
                        @if($loginEvents->isEmpty())
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
                                                {{ $user->last_seen_at ? $user->last_seen_at->format('d/m/Y H:i') : 'Chưa ghi nhận' }}
                                            </div>
                                            @if($user->last_seen_at)
                                                <div class="small text-muted">{{ $user->last_seen_at->diffForHumans() }}</div>
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
        </div>
    </div>
</div>
@endsection
