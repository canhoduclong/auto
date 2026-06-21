@extends('layouts.app')

@section('title', 'Chi tiết: ' . $user->name)

@push('styles')
<style>
    .usage-heatmap-wrap { overflow-x: auto; padding-bottom: 8px; }
    .usage-heatmap { min-width: 980px; border-collapse: separate; border-spacing: 4px; }
    .usage-heatmap th { font-size: .68rem; color: #64748b; text-align: center; font-weight: 600; }
    .usage-heatmap .usage-day { min-width: 92px; text-align: left; white-space: nowrap; }
    .usage-hour-cell {
        width: 28px; height: 28px; border: 0; border-radius: 6px;
        background: #f1f5f9; color: #94a3b8; font-size: .66rem; font-weight: 700;
    }
    .usage-hour-cell.level-1 { background: #dcfce7; color: #15803d; }
    .usage-hour-cell.level-2 { background: #86efac; color: #166534; }
    .usage-hour-cell.level-3 { background: #16a34a; color: #fff; }
    .usage-hour-cell:not(:disabled):hover { outline: 3px solid rgba(22, 163, 74, .22); transform: scale(1.08); }
    .tooltip-inner { white-space: pre-line; text-align: left; max-width: 390px; }
</style>
@endpush

@section('content')
@php
    $isOnline = $presence['is_online'];
    $lastActivityAt = $presence['last_activity_at'];
    $avatarUrl = $user->avatar
        ? asset($user->avatar)
        : ($user->google_avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name ?? 'U') . '&background=0F172A&color=F8FAFC&size=200&bold=true');
@endphp

<div class="container-fluid px-4">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Người dùng</a></li>
            <li class="breadcrumb-item active">{{ $user->name }}</li>
        </ol>
    </nav>

    <div class="row g-3">

        {{-- ===== LEFT: Profile card ===== --}}
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm text-center p-4">
                {{-- Avatar --}}
                <div class="position-relative d-inline-block mx-auto mb-3">
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}"
                         style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0;">
                    <span class="position-absolute bottom-0 end-0"
                          style="width:16px;height:16px;border-radius:50%;border:2px solid #fff;background:{{ $isOnline ? '#22c55e' : '#94a3b8' }};"></span>
                </div>

                <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                <div class="text-muted small mb-2">{{ $user->email }}</div>

                {{-- Online badge --}}
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

                {{-- Roles --}}
                <div class="mb-3">
                    @foreach($user->roles as $role)
                        <span class="badge rounded-pill bg-info bg-opacity-10 text-info">{{ $role->name }}</span>
                    @endforeach
                </div>

                <hr class="my-2">

                {{-- Info rows --}}
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
                            {{ $lastActivityAt ? $lastActivityAt->format('d/m/Y H:i') : '—' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-top">
                        <span class="text-muted">Địa chỉ IP</span>
                        <span class="fw-semibold font-monospace">{{ $presence['last_ip_address'] ?: '—' }}</span>
                    </div>
                </div>

                <div class="mt-3 d-flex flex-column gap-2">
                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm w-100">
                        <i class="ph ph-pencil me-1"></i>Chỉnh sửa
                    </a>
                    <a href="{{ route('users.index') }}" class="btn btn-light btn-sm w-100 border">
                        <i class="ph ph-arrow-left me-1"></i>Danh sách
                    </a>
                </div>
            </div>
        </div>

        {{-- ===== RIGHT: Tabs ===== --}}
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
                        <i class="ph ph-sign-in me-1"></i>Lịch sử đăng nhập 7 ngày
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
                        <div class="p-3 border-bottom">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card border bg-light h-100 p-3">
                                        <div class="small text-muted mb-1">Trạng thái hiện tại</div>
                                        <div class="fw-bold fs-5" style="color:{{ $isOnline ? '#16a34a' : '#64748b' }}">
                                            ● {{ $isOnline ? 'Đang Online' : 'Offline' }}
                                        </div>
                                        <div class="small text-muted">Online khi có hoạt động trong 5 phút gần nhất</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border bg-light h-100 p-3">
                                        <div class="small text-muted mb-1">Hoạt động gần nhất</div>
                                        <div class="fw-bold">{{ $lastActivityAt?->format('d/m/Y H:i:s') ?? 'Chưa ghi nhận' }}</div>
                                        @if($lastActivityAt)<div class="small text-muted">{{ $lastActivityAt->diffForHumans() }}</div>@endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border bg-light h-100 p-3">
                                        <div class="small text-muted mb-1">Địa chỉ IP gần nhất</div>
                                        <div class="fw-bold font-monospace">{{ $presence['last_ip_address'] ?: 'Chưa ghi nhận' }}</div>
                                        <div class="small text-muted">IP mạng, không phải địa chỉ nhà chính xác</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 border-bottom">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Mức độ sử dụng theo khung giờ</h6>
                                    <div class="small text-muted">Mỗi ô là một giờ. Di chuột vào ô có màu để xem thời điểm, nguồn và lý do ghi nhận hoạt động.</div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-light text-dark border">{{ $usageSummary['points'] }} điểm online</span>
                                    <span class="badge bg-light text-dark border">{{ $usageSummary['active_hours'] }} giờ hoạt động</span>
                                    <span class="badge bg-light text-dark border">{{ $usageSummary['active_days'] }}/7 ngày có sử dụng</span>
                                </div>
                            </div>

                            <div class="usage-heatmap-wrap">
                                <table class="usage-heatmap" aria-label="Lịch sử sử dụng trong 7 ngày theo giờ">
                                    <thead>
                                        <tr>
                                            <th class="usage-day">Ngày</th>
                                            @foreach(range(0, 23) as $hour)
                                                <th>{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</th>
                                            @endforeach
                                            <th>Tổng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($usageGrid as $day)
                                            <tr>
                                                <th class="usage-day">
                                                    <div>{{ $day['date']->isToday() ? 'Hôm nay' : $day['date']->translatedFormat('D') }}</div>
                                                    <div class="fw-normal text-muted">{{ $day['date']->format('d/m/Y') }}</div>
                                                </th>
                                                @foreach($day['hours'] as $hour => $cell)
                                                    @php
                                                        $level = $cell['count'] === 0 ? 0 : ($cell['count'] <= 2 ? 1 : ($cell['count'] <= 5 ? 2 : 3));
                                                        $tooltip = $day['date']->format('d/m/Y') . ' · ' . str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00–' . str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':59' . "\n" . $cell['tooltip'];
                                                    @endphp
                                                    <td>
                                                        <button type="button"
                                                            class="usage-hour-cell level-{{ $level }}"
                                                            @disabled($cell['count'] === 0)
                                                            data-bs-toggle="tooltip"
                                                            data-bs-container="body"
                                                            data-bs-placement="top"
                                                            data-bs-title="{{ $tooltip }}"
                                                            aria-label="{{ $tooltip }}">
                                                            {{ $cell['count'] ?: '·' }}
                                                        </button>
                                                    </td>
                                                @endforeach
                                                <td class="text-center fw-bold small">{{ $day['points'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex align-items-center gap-2 small text-muted mt-2">
                                <span>Ít</span>
                                <span class="usage-hour-cell level-1 d-inline-grid place-items-center" style="width:18px;height:18px"></span>
                                <span class="usage-hour-cell level-2 d-inline-grid" style="width:18px;height:18px"></span>
                                <span class="usage-hour-cell level-3 d-inline-grid" style="width:18px;height:18px"></span>
                                <span>Nhiều</span>
                            </div>
                        </div>

                        @if($presence['web_sessions']->isNotEmpty() || $presence['mobile_sessions']->isNotEmpty())
                            <div class="table-responsive border-bottom">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Loại phiên</th>
                                            <th>Thiết bị / trình duyệt</th>
                                            <th>Địa chỉ IP</th>
                                            <th>Hoạt động cuối</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($presence['web_sessions'] as $session)
                                            <tr>
                                                <td><i class="ph ph-browser me-1"></i>Web</td>
                                                <td class="small" title="{{ $session->user_agent }}">{{ Str::limit($session->user_agent ?: 'Không xác định', 70) }}</td>
                                                <td class="font-monospace small">{{ $session->ip_address ?: '—' }}</td>
                                                <td class="small">{{ $session->last_activity_at->format('d/m/Y H:i:s') }}<div class="text-muted">{{ $session->last_activity_at->diffForHumans() }}</div></td>
                                                <td><span class="badge {{ $session->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $session->is_active ? 'Online' : 'Offline' }}</span></td>
                                            </tr>
                                        @endforeach
                                        @foreach($presence['mobile_sessions'] as $token)
                                            <tr>
                                                <td><i class="ph ph-device-mobile me-1"></i>Ứng dụng</td>
                                                <td class="small">{{ $token->device_name ?: ($token->platform ?: 'Thiết bị di động') }} @if($token->app_version)<span class="text-muted">v{{ $token->app_version }}</span>@endif</td>
                                                <td class="font-monospace small">{{ $token->ip_address ?: '—' }}</td>
                                                <td class="small">{{ $token->last_used_at?->format('d/m/Y H:i:s') ?? '—' }} @if($token->last_used_at)<div class="text-muted">{{ $token->last_used_at->diffForHumans() }}</div>@endif</td>
                                                <td><span class="badge {{ $token->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $token->is_active ? 'Online' : 'Offline' }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
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
                                                {{ $lastActivityAt ? $lastActivityAt->format('d/m/Y H:i') : 'Chưa ghi nhận' }}
                                            </div>
                                            @if($lastActivityAt)
                                                <div class="small text-muted">{{ $lastActivityAt->diffForHumans() }}</div>
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

            </div>{{-- end tab-content --}}
        </div>{{-- end col-lg-9 --}}
    </div>{{-- end row --}}
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
        bootstrap.Tooltip.getOrCreateInstance(element);
    });
});
</script>
@endpush
